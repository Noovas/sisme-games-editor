<?php
/**
 * File: /sisme-games-editor/includes/game-page-creator/game-page-creator-publisher.php
 * Publisher du module Game Page Creator - Création d'articles WordPress
 * 
 * RESPONSABILITÉ:
 * - Écouter la publication de Game Data depuis l'admin
 * - Créer l'article WordPress avec assignations complètes
 * - Utiliser notre module pour générer le contenu
 * - Respecter les standards WordPress (SEO, thème, etc.)
 * 
 * DÉPENDANCES:
 * - game-data-creator (pour les hooks de publication)
 * - game-page-creator.php (pour génération HTML)
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-data-creator/game-data-creator-constants.php';

class Sisme_Game_Page_Creator_Publisher {
    
    /**
     * Initialiser les hooks d'intégration
     */
    public static function init() {
        add_action('sisme_game_creator_game_created', array(__CLASS__, 'on_game_created'), 10, 2);
        add_action('sisme_game_fiche_created', array(__CLASS__, 'redirect_to_game_fiche'), 10, 2);
    }
    
    /**
     * Créer une fiche complète (article WordPress + contenu généré)
     * 
     * @param int $tag_id ID du tag du jeu (doit déjà exister)
     * @return array Résultat avec post_id et URLs
     */
    public static function create_game_fiche($tag_id) {
        $tag_data = get_term($tag_id, 'post_tag');
        if (!$tag_data || is_wp_error($tag_data)) {
            return array(
                'success' => false,
                'post_id' => null,
                'message' => 'Jeu introuvable'
            );
        }
        $existing_fiche = self::find_existing_fiche($tag_id);
        if ($existing_fiche) {
            return array(
                'success' => false,
                'post_id' => $existing_fiche,
                'message' => 'Une fiche existe déjà pour ce jeu'
            );
        }
        $post_result = self::create_wordpress_post($tag_id, $tag_data);
        if (!$post_result['success']) {
            return $post_result;
        }
        $post_id = $post_result['post_id'];
        $assignments_result = self::assign_wordpress_data($post_id, $tag_id);
        if (!$assignments_result['success']) {
            wp_delete_post($post_id, true);
            return $assignments_result;
        }
        $content_result = self::generate_fiche_content($post_id, $tag_id);
        if (!$content_result['success']) {
            wp_delete_post($post_id, true);
            return $content_result;
        }
        self::send_notifications($tag_id);
        do_action('sisme_game_fiche_created', $post_id, $tag_id);
        return array(
            'success' => true,
            'post_id' => $post_id,
            'message' => 'Fiche créée avec succès',
            'post_url' => get_permalink($post_id),
            'edit_url' => get_edit_post_link($post_id, 'raw')
        );
    }
    
    /**
     * Mettre à jour une fiche existante
     * 
     * @param int $post_id ID de l'article
     * @param int $tag_id ID du tag du jeu
     * @return array Résultat de mise à jour
     */
    public static function update_game_fiche($post_id, $tag_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post') {
            return array(
                'success' => false,
                'message' => 'Article introuvable'
            );
        }
        $sections = get_term_meta($tag_id, Sisme_Game_Creator_Constants::META_SECTIONS, true) ?: array();
        update_post_meta($post_id, '_sisme_game_sections', $sections);
        $content_result = self::generate_fiche_content($post_id, $tag_id);
        return array(
            'success' => $content_result['success'],
            'message' => $content_result['success'] ? 'Fiche mise à jour avec succès' : $content_result['message']
        );
    }
    
    /**
     * Hook appelé lors de la création de Game Data
     * 
     * @param int $term_id ID du terme créé
     * @param array $game_data Données du jeu
     */
    public static function on_game_created($term_id, $game_data) {
        $has_sections = !empty($game_data['sections']);
        $has_description = !empty($game_data['description']);
        if ($has_sections || $has_description) {
            $result = self::create_game_fiche($term_id);
            if (!$result['success']) {
                error_log("[Game Page Creator Publisher] Échec création fiche pour {$term_id}: {$result['message']}");
            }
        }
    }
    
    /**
     * Créer l'article WordPress de base
     * 
     * @param int $tag_id ID du tag
     * @param WP_Term $tag_data Données du tag
     * @return array Résultat avec post_id
     */
    private static function create_wordpress_post($tag_id, $tag_data) {
        $post_data = array(
            'post_title' => $tag_data->name,
            'post_content' => '',
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return array(
                'success' => false,
                'post_id' => null,
                'message' => 'Erreur création article: ' . $post_id->get_error_message()
            );
        }
        
        return array(
            'success' => true,
            'post_id' => $post_id
        );
    }
    
    /**
     * Assignations WordPress obligatoires
     * 
     * @param int $post_id ID de l'article
     * @param int $tag_id ID du tag du jeu
     * @return array Résultat des assignations
     */
    private static function assign_wordpress_data($post_id, $tag_id) {
        $tag_result = wp_set_post_terms($post_id, array($tag_id), 'post_tag');
        if (is_wp_error($tag_result)) {
            return array(
                'success' => false,
                'message' => 'Erreur assignation tag: ' . $tag_result->get_error_message()
            );
        }
        $game_genres = get_term_meta($tag_id, Sisme_Game_Creator_Constants::META_GENRES, true) ?: array();
        if (!empty($game_genres)) {
            $categories_result = wp_set_post_categories($post_id, $game_genres);
            if (is_wp_error($categories_result)) {
                return array(
                    'success' => false,
                    'message' => 'Erreur assignation catégories: ' . $categories_result->get_error_message()
                );
            }
        }
        $cover_main_id = get_term_meta($tag_id, Sisme_Game_Creator_Constants::META_COVER_MAIN, true);
        if (!empty($cover_main_id)) {
            $thumbnail_result = set_post_thumbnail($post_id, $cover_main_id);
            if (!$thumbnail_result) {
                error_log("[Game Page Creator Publisher] Avertissement: Impossible de définir l'image à la une pour {$post_id}");
            }
        }
        $sections = get_term_meta($tag_id, Sisme_Game_Creator_Constants::META_SECTIONS, true) ?: array();
        update_post_meta($post_id, '_sisme_game_sections', $sections);
        return array(
            'success' => true,
            'message' => 'Assignations complétées'
        );
    }
    
    /**
     * Générer le contenu avec notre module
     * 
     * @param int $post_id ID de l'article
     * @param int $tag_id ID du tag du jeu
     * @return array Résultat de génération
     */
    private static function generate_fiche_content($post_id, $tag_id) {
        try {
            $sections = get_term_meta($tag_id, Sisme_Game_Creator_Constants::META_SECTIONS, true) ?: array();
            update_post_meta($post_id, '_sisme_game_sections', $sections);
            update_post_meta($post_id, '_sisme_created_with_game_page_creator', true);
            update_post_meta($post_id, '_sisme_game_page_creator_version', '1.0.0');
            return array(
                'success' => true,
                'message' => 'Fiche créée avec succès (rendu dynamique)'
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => 'Erreur technique lors de la génération'
            );
        }
    }
    
    /**
     * Envoyer les notifications
     * 
     * @param int $tag_id ID du tag du jeu
     */
    private static function send_notifications($tag_id) {
        if (class_exists('Sisme_Utils_Notification')) {
            Sisme_Utils_Notification::send_new_game_notification($tag_id);
        }
    }
    
    /**
     * Vérifier si une fiche existe déjà pour un jeu
     * 
     * @param int $tag_id ID du tag du jeu
     * @return int|false ID de la fiche ou false si aucune
     */
    public static function find_existing_fiche($tag_id) {
        $posts = get_posts(array(
            'tag_id' => $tag_id,
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => 1,
            'meta_query' => array(
                array(
                    'key' => '_sisme_game_sections',
                    'compare' => 'EXISTS'
                )
            )
        ));
        if (!empty($posts)) {
            return $posts[0]->ID;
        }
        return false;
    }
    
    /**
     * Redirection après création de fiche
     * 
     * @param int $post_id ID de l'article créé
     * @param int $tag_id ID du tag du jeu
     */
    public static function redirect_to_game_fiche($post_id, $tag_id) {
        $post_url = get_permalink($post_id);
        if ($post_url === false) {
            return;
        } else {
            wp_redirect($post_url);
            return;
        }
    }
    
    /**
     * Supprimer une fiche et nettoyer
     * 
     * @param int $post_id ID de l'article à supprimer
     * @return array Résultat de suppression
     */
    public static function delete_game_fiche($post_id) {
        $is_game_fiche = get_post_meta($post_id, '_sisme_game_sections', true);
        if (empty($is_game_fiche)) {
            return array(
                'success' => false,
                'message' => 'Ce n\'est pas une fiche de jeu'
            );
        }
        $delete_result = wp_delete_post($post_id, true);
        if (!$delete_result) {
            return array(
                'success' => false,
                'message' => 'Erreur lors de la suppression'
            );
        }
        return array(
            'success' => true,
            'message' => 'Fiche supprimée avec succès'
        );
    }
    
    /**
     * Obtenir les statistiques des fiches
     * 
     * @return array Statistiques
     */
    public static function get_fiches_stats() {
        global $wpdb;
        $total_fiches = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID) 
            FROM {$wpdb->posts} p 
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
            WHERE p.post_type = 'post' 
            AND p.post_status = 'publish' 
            AND pm.meta_key = '_sisme_game_sections'
        ");
        return array(
            'total_fiches' => intval($total_fiches)
        );
    }
}

Sisme_Game_Page_Creator_Publisher::init();