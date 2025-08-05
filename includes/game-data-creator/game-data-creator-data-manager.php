<?php
/**
 * File: /sisme-games-editor/includes/game-data-creator/game-data-creator-data-manager.php
 * Gestionnaire de données CRUD pour le module Game Creator
 * 
 * RESPONSABILITÉ:
 * - CRUD WordPress (terms + term_meta)
 * - Couche d'abstraction pour la base de données
 * - Gestion des métadonnées avec les bonnes clés
 * - Opérations atomiques (création + meta en une fois)
 * 
 * DÉPENDANCES:
 * - game-data-creator-constants.php
 * - WordPress Terms API
 * - WordPress Meta API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_Creator_Data_Manager {
    
    /**
     * Créer un jeu complet (term + métadonnées)
     * @param array $game_data Données validées et sanitisées
     * @return int|WP_Error ID du terme créé ou erreur
     */
    public static function create_game($game_data) {
        if (empty($game_data['name'])) {
            return new WP_Error('missing_name', 'Le nom du jeu est requis');
        }
        
        // 1. Créer le terme WordPress
        $term_result = self::create_game_term($game_data['name']);
        if (is_wp_error($term_result)) {
            return $term_result;
        }
        
        $term_id = $term_result['term_id'];
        
        // 2. Sauvegarder toutes les métadonnées
        $meta_result = self::save_game_metadata($term_id, $game_data);
        if (is_wp_error($meta_result)) {
            // Nettoyer le terme créé en cas d'erreur meta
            wp_delete_term($term_id, Sisme_Game_Creator_Constants::TAXONOMY_GAMES);
            return $meta_result;
        }
        
        // 3. Mettre à jour la date de dernière modification
        self::update_last_modified($term_id);
        
        // 4. Hook pour permettre des actions supplémentaires
        do_action('sisme_game_creator_game_created', $term_id, $game_data);
        
        return $term_id;
    }
    
    /**
     * Mettre à jour un jeu existant
     * @param int $term_id ID du terme existant
     * @param array $game_data Nouvelles données validées
     * @return bool|WP_Error Succès ou erreur
     */
    public static function update_game($term_id, $game_data) {
        // Vérifier que le terme existe
        $existing_term = get_term($term_id, Sisme_Game_Creator_Constants::TAXONOMY_GAMES);
        if (!$existing_term || is_wp_error($existing_term)) {
            return new WP_Error('term_not_found', 'Jeu introuvable');
        }
        
        // 1. Mettre à jour le nom si fourni
        if (!empty($game_data['name']) && $game_data['name'] !== $existing_term->name) {
            $update_result = wp_update_term($term_id, Sisme_Game_Creator_Constants::TAXONOMY_GAMES, array(
                'name' => $game_data['name'],
                'slug' => sanitize_title($game_data['name'])
            ));
            
            if (is_wp_error($update_result)) {
                return $update_result;
            }
        }
        
        // 2. Mettre à jour les métadonnées
        $meta_result = self::save_game_metadata($term_id, $game_data);
        if (is_wp_error($meta_result)) {
            return $meta_result;
        }
        
        // 3. Mettre à jour la date de dernière modification
        self::update_last_modified($term_id);
        
        // 4. Hook pour actions supplémentaires
        do_action('sisme_game_creator_game_updated', $term_id, $game_data);
        
        return true;
    }
    
    /**
     * Supprimer un jeu complètement
     * @param int $term_id ID du terme à supprimer
     * @param bool $cleanup_files Supprimer aussi les fichiers liés
     * @return bool|WP_Error Succès ou erreur
     */
    public static function delete_game($term_id, $cleanup_files = false) {
        // Vérifier que le terme existe
        $existing_term = get_term($term_id, Sisme_Game_Creator_Constants::TAXONOMY_GAMES);
        if (!$existing_term || is_wp_error($existing_term)) {
            return new WP_Error('term_not_found', 'Jeu introuvable');
        }
        
        // 1. Hook avant suppression pour nettoyage
        do_action('sisme_game_creator_before_delete', $term_id, $existing_term, $cleanup_files);
        
        // 2. Récupérer les IDs de médias pour nettoyage optionnel
        $media_ids = array();
        if ($cleanup_files) {
            $media_ids = self::get_game_media_ids($term_id);
        }
        
        // 3. Supprimer toutes les métadonnées
        self::delete_all_game_metadata($term_id);
        
        // 4. Supprimer le terme
        $delete_result = wp_delete_term($term_id, Sisme_Game_Creator_Constants::TAXONOMY_GAMES);
        if (is_wp_error($delete_result)) {
            return $delete_result;
        }
        
        // 5. Nettoyage des fichiers si demandé
        if ($cleanup_files && !empty($media_ids)) {
            self::cleanup_media_files($media_ids);
        }
        
        // 6. Hook après suppression
        do_action('sisme_game_creator_game_deleted', $term_id, $existing_term);
        
        return true;
    }
    
    /**
     * Récupérer les données complètes d'un jeu
     * @param int $term_id ID du terme
     * @return array|false Données du jeu ou false si introuvable
     */
    public static function get_game_data($term_id) {
        $term = get_term($term_id, Sisme_Game_Creator_Constants::TAXONOMY_GAMES);
        if (!$term || is_wp_error($term)) {
            return false;
        }
        
        $game_data = array(
            Sisme_Game_Creator_Constants::KEY_TERM_ID => $term->term_id,
            Sisme_Game_Creator_Constants::KEY_NAME => $term->name,
            Sisme_Game_Creator_Constants::KEY_SLUG => $term->slug
        );
        
        // Récupérer toutes les métadonnées
        $meta_keys = Sisme_Game_Creator_Constants::get_all_meta_keys();
        foreach ($meta_keys as $meta_key) {
            $value = get_term_meta($term_id, $meta_key, true);
            $api_key = self::convert_meta_key_to_api_key($meta_key);
            $game_data[$api_key] = $value ?: null;
        }
        
        return $game_data;
    }
    
    /**
     * Vérifier si un jeu existe
     * @param int $term_id ID du terme
     * @return bool Existe ou non
     */
    public static function game_exists($term_id) {
        $term = get_term($term_id, Sisme_Game_Creator_Constants::TAXONOMY_GAMES);
        return $term && !is_wp_error($term);
    }
    
    /**
     * Créer un terme WordPress pour le jeu
     * @param string $name Nom du jeu
     * @return array|WP_Error Résultat de wp_insert_term
     */
    private static function create_game_term($name) {
        $slug = sanitize_title($name);
        
        // Vérifier si le slug existe déjà
        $existing = get_term_by('slug', $slug, Sisme_Game_Creator_Constants::TAXONOMY_GAMES);
        if ($existing) {
            return new WP_Error('term_exists', 'Un jeu avec ce nom existe déjà');
        }
        
        return wp_insert_term($name, Sisme_Game_Creator_Constants::TAXONOMY_GAMES, array(
            'slug' => $slug
        ));
    }
    
    /**
     * Sauvegarder toutes les métadonnées d'un jeu
     * @param int $term_id ID du terme
     * @param array $game_data Données du jeu
     * @return bool|WP_Error Succès ou erreur
     */
    private static function save_game_metadata($term_id, $game_data) {
        $meta_mapping = array(
            'description' => Sisme_Game_Creator_Constants::META_DESCRIPTION,
            'release_date' => Sisme_Game_Creator_Constants::META_RELEASE_DATE,
            'platforms' => Sisme_Game_Creator_Constants::META_PLATFORMS,
            'genres' => Sisme_Game_Creator_Constants::META_GENRES,
            'modes' => Sisme_Game_Creator_Constants::META_MODES,
            'is_team_choice' => Sisme_Game_Creator_Constants::META_TEAM_CHOICE,
            'external_links' => Sisme_Game_Creator_Constants::META_EXTERNAL_LINKS,
            'trailer_link' => Sisme_Game_Creator_Constants::META_TRAILER_LINK,
            'screenshots' => Sisme_Game_Creator_Constants::META_SCREENSHOTS,
            'developers' => Sisme_Game_Creator_Constants::META_DEVELOPERS,
            'publishers' => Sisme_Game_Creator_Constants::META_PUBLISHERS,
            'sections' => Sisme_Game_Creator_Constants::META_SECTIONS
        );
        
        // Sauvegarder chaque métadonnée
        foreach ($meta_mapping as $data_key => $meta_key) {
            if (array_key_exists($data_key, $game_data)) {
                $value = $game_data[$data_key];
                
                // Traitement spécial pour les booléens
                if ($data_key === 'is_team_choice') {
                    $value = $value ? '1' : '0';
                }
                
                $result = update_term_meta($term_id, $meta_key, $value);
                if (false === $result) {
                    return new WP_Error('meta_save_failed', "Erreur lors de la sauvegarde de {$data_key}");
                }
            }
        }
        
        // Gestion spéciale des covers
        if (isset($game_data['covers']) && is_array($game_data['covers'])) {
            $covers_mapping = array(
                'main' => Sisme_Game_Creator_Constants::META_COVER_MAIN,
                'news' => Sisme_Game_Creator_Constants::META_COVER_NEWS,
                'patch' => Sisme_Game_Creator_Constants::META_COVER_PATCH,
                'test' => Sisme_Game_Creator_Constants::META_COVER_TEST,
                'horizontal' => Sisme_Game_Creator_Constants::META_COVER_MAIN,
                'vertical' => 'cover_vertical'
            );
            
            foreach ($covers_mapping as $cover_type => $meta_key) {
                if (isset($game_data['covers'][$cover_type])) {
                    update_term_meta($term_id, $meta_key, $game_data['covers'][$cover_type]);
                }
            }
        }
        
        return true;
    }
    
    /**
     * Supprimer toutes les métadonnées d'un jeu
     * @param int $term_id ID du terme
     */
    private static function delete_all_game_metadata($term_id) {
        $meta_keys = Sisme_Game_Creator_Constants::get_all_meta_keys();
        
        // Ajouter les clés spéciales
        $meta_keys[] = 'cover_vertical';
        $meta_keys[] = Sisme_Game_Creator_Constants::META_LAST_UPDATE;
        
        foreach ($meta_keys as $meta_key) {
            delete_term_meta($term_id, $meta_key);
        }
    }
    
    /**
     * Mettre à jour la date de dernière modification
     * @param int $term_id ID du terme
     */
    private static function update_last_modified($term_id) {
        update_term_meta($term_id, Sisme_Game_Creator_Constants::META_LAST_UPDATE, current_time('mysql'));
    }
    
    /**
     * Récupérer tous les IDs de médias liés à un jeu
     * @param int $term_id ID du terme
     * @return array IDs des médias
     */
    private static function get_game_media_ids($term_id) {
        $media_ids = array();
        
        // Covers
        $cover_keys = array(
            Sisme_Game_Creator_Constants::META_COVER_MAIN,
            Sisme_Game_Creator_Constants::META_COVER_NEWS,
            Sisme_Game_Creator_Constants::META_COVER_PATCH,
            Sisme_Game_Creator_Constants::META_COVER_TEST,
            'cover_vertical'
        );
        
        foreach ($cover_keys as $cover_key) {
            $cover_id = get_term_meta($term_id, $cover_key, true);
            if ($cover_id && is_numeric($cover_id)) {
                $media_ids[] = intval($cover_id);
            }
        }
        
        // Screenshots
        $screenshots = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_SCREENSHOTS, true);
        if (is_array($screenshots)) {
            foreach ($screenshots as $screenshot_id) {
                if (is_numeric($screenshot_id)) {
                    $media_ids[] = intval($screenshot_id);
                }
            }
        }
        
        // Images des sections
        $sections = get_term_meta($term_id, Sisme_Game_Creator_Constants::META_SECTIONS, true);
        if (is_array($sections)) {
            foreach ($sections as $section) {
                if (isset($section['image_id']) && is_numeric($section['image_id'])) {
                    $media_ids[] = intval($section['image_id']);
                }
            }
        }
        
        return array_unique(array_filter($media_ids));
    }
    
    /**
     * Nettoyer les fichiers médias
     * @param array $media_ids IDs des médias à supprimer
     */
    private static function cleanup_media_files($media_ids) {
        foreach ($media_ids as $media_id) {
            // Vérifier que le média n'est pas utilisé ailleurs
            if (self::is_media_used_elsewhere($media_id)) {
                continue;
            }
            
            // Supprimer le fichier et l'attachment
            wp_delete_attachment($media_id, true);
        }
    }
    
    /**
     * Vérifier si un média est utilisé dans d'autres jeux
     * @param int $media_id ID du média
     * @return bool Utilisé ailleurs ou non
     */
    private static function is_media_used_elsewhere($media_id) {
        global $wpdb;
        
        // Chercher dans toutes les term_meta
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->termmeta} 
             WHERE meta_value LIKE %s OR meta_value = %s",
            '%' . $media_id . '%',
            $media_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Convertir une clé META en clé API
     * @param string $meta_key Clé de métadonnée
     * @return string Clé API correspondante
     */
    private static function convert_meta_key_to_api_key($meta_key) {
        $mapping = array(
            Sisme_Game_Creator_Constants::META_DESCRIPTION => Sisme_Game_Creator_Constants::KEY_DESCRIPTION,
            Sisme_Game_Creator_Constants::META_RELEASE_DATE => Sisme_Game_Creator_Constants::KEY_RELEASE_DATE,
            Sisme_Game_Creator_Constants::META_LAST_UPDATE => Sisme_Game_Creator_Constants::KEY_LAST_UPDATE,
            Sisme_Game_Creator_Constants::META_PLATFORMS => Sisme_Game_Creator_Constants::KEY_PLATFORMS,
            Sisme_Game_Creator_Constants::META_GENRES => Sisme_Game_Creator_Constants::KEY_GENRES,
            Sisme_Game_Creator_Constants::META_MODES => Sisme_Game_Creator_Constants::KEY_MODES,
            Sisme_Game_Creator_Constants::META_TEAM_CHOICE => Sisme_Game_Creator_Constants::KEY_IS_TEAM_CHOICE,
            Sisme_Game_Creator_Constants::META_EXTERNAL_LINKS => Sisme_Game_Creator_Constants::KEY_EXTERNAL_LINKS,
            Sisme_Game_Creator_Constants::META_TRAILER_LINK => Sisme_Game_Creator_Constants::KEY_TRAILER_LINK,
            Sisme_Game_Creator_Constants::META_SCREENSHOTS => Sisme_Game_Creator_Constants::KEY_SCREENSHOTS,
            Sisme_Game_Creator_Constants::META_DEVELOPERS => Sisme_Game_Creator_Constants::KEY_DEVELOPERS,
            Sisme_Game_Creator_Constants::META_PUBLISHERS => Sisme_Game_Creator_Constants::KEY_PUBLISHERS,
            Sisme_Game_Creator_Constants::META_SECTIONS => Sisme_Game_Creator_Constants::KEY_SECTIONS
        );
        
        return $mapping[$meta_key] ?? $meta_key;
    }
    
    /**
     * Dupliquer un jeu existant
     * @param int $source_term_id ID du jeu source
     * @param string $new_name Nom du nouveau jeu
     * @return int|WP_Error ID du nouveau jeu ou erreur
     */
    public static function duplicate_game($source_term_id, $new_name) {
        $source_data = self::get_game_data($source_term_id);
        if (!$source_data) {
            return new WP_Error('source_not_found', 'Jeu source introuvable');
        }
        
        // Modifier le nom et supprimer l'ID
        $source_data['name'] = $new_name;
        unset($source_data[Sisme_Game_Creator_Constants::KEY_TERM_ID]);
        unset($source_data[Sisme_Game_Creator_Constants::KEY_SLUG]);
        
        return self::create_game($source_data);
    }
    
    /**
     * Obtenir la liste de tous les jeux
     * @param array $args Arguments pour get_terms
     * @return array Liste des termes
     */
    public static function get_all_games($args = array()) {
        $default_args = array(
            'taxonomy' => Sisme_Game_Creator_Constants::TAXONOMY_GAMES,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $default_args);
        
        return get_terms($args);
    }

    /**
     * Génération URL fiche jeu avec priorité article puis tag
     * @param int $game_id ID du jeu
     * @return string URL de la fiche
     */
    public static function get_game_url($game_id) {
        $fiche_post = get_posts([
            'post_type' => 'post',
            'meta_query' => [
                [
                    'key' => 'associated_game_id',
                    'value' => $game_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ]);
        $term = get_term($game_id, 'post_tag');
        if (!empty($fiche_post)) {
            return get_permalink($fiche_post[0]->ID);
        } else {
            return home_url($term->slug . '/');
        }
    }
}