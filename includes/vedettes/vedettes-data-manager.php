<?php
/**
 * File: /sisme-games-editor/includes/vedettes/vedettes-data-manager.php
 * Gestionnaire de données pour le système de vedettes
 * 
 * RESPONSABILITÉ:
 * - CRUD des données vedettes (term_meta)
 * - Validation des données
 * - Méthodes helper pour récupérer/modifier les vedettes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Vedettes_Data_Manager {
    
    /**
     * Structure des données vedettes par défaut
     */
    const DEFAULT_VEDETTE_DATA = array(
        'is_featured' => 'false',        // STRING "false" directement !
        'featured_priority' => 0,
        'featured_start_date' => null,
        'featured_end_date' => null,
        'featured_sponsor' => '',
        'featured_created_at' => null,
        'featured_stats' => array(
            'views' => 0,
            'clicks' => 0
        )
    );
    
    /**
     * Clés term_meta utilisées
     */
    const META_KEYS = array(
        'is_featured' => 'game_is_featured',
        'featured_priority' => 'game_featured_priority',
        'featured_start_date' => 'game_featured_start_date',
        'featured_end_date' => 'game_featured_end_date',
        'featured_sponsor' => 'game_featured_sponsor',
        'featured_created_at' => 'game_featured_created_at',
        'featured_stats' => 'game_featured_stats'
    );

    /**
     * Forcer l'initialisation d'un jeu (même s'il existe déjà)
     * 
     * @param int $term_id ID du terme (jeu)
     * @return bool Succès de l'initialisation
     */
    public static function force_initialize_game($term_id) {
        // Vérifier que le terme existe
        if (!term_exists($term_id, 'post_tag')) {
            error_log("Sisme Vedettes: Impossible d'initialiser - terme $term_id n'existe pas");
            return false;
        }
        
        // Forcer l'initialisation avec les valeurs par défaut
        $init_data = self::DEFAULT_VEDETTE_DATA;
        $init_data['featured_created_at'] = current_time('mysql');
        
        // ✨ FORCER L'ÉCRITURE avec add_term_meta si vide, update_term_meta sinon
        $success = true;
        foreach (self::META_KEYS as $key => $meta_key) {
            if (isset($init_data[$key])) {
                $value = $init_data[$key];
                $existing = get_term_meta($term_id, $meta_key, true);
                
                if ($existing === '' || $existing === null) {
                    // Utiliser add_term_meta pour créer
                    $result = add_term_meta($term_id, $meta_key, $value, true);
                    if (!$result) {
                        error_log("Sisme Vedettes: ÉCHEC add_term_meta $meta_key pour $term_id");
                        $success = false;
                    }
                } else {
                    // Utiliser update_term_meta pour mettre à jour
                    $result = update_term_meta($term_id, $meta_key, $value);
                    // Note: update_term_meta retourne false si la valeur n'a pas changé (normal)
                }
            }
        }
        
        if ($success) {
            error_log("Sisme Vedettes: Jeu $term_id forcé à l'initialisation");
        } else {
            error_log("Sisme Vedettes: Échec initialisation forcée jeu $term_id");
        }
        
        return $success;
    }
    
    /**
     * Récupérer les données vedette d'un jeu
     * 
     * @param int $term_id ID du terme (jeu)
     * @return array Données vedette
     */
    public static function get_vedette_data($term_id) {
        $data = self::DEFAULT_VEDETTE_DATA;
        
        foreach (self::META_KEYS as $key => $meta_key) {
            $value = get_term_meta($term_id, $meta_key, true);
            
            // Conversion des types
            switch ($key) {
                case 'is_featured':
                    // Lire string et convertir en bool - traiter les valeurs vides
                    if ($value === '' || $value === null) {
                        $data[$key] = false;  // Valeur vide = false par défaut
                    } else {
                        $data[$key] = ($value === 'true');
                    }
                    break;
                case 'featured_priority':
                    $data[$key] = (int) $value;
                    break;
                case 'featured_stats':
                    $data[$key] = is_array($value) ? $value : self::DEFAULT_VEDETTE_DATA['featured_stats'];
                    break;
                default:
                    $data[$key] = $value ?: $data[$key];
            }
        }
        
        return $data;
    }
    
    /**
     * Mettre à jour les données vedette d'un jeu
     * 
     * @param int $term_id ID du terme (jeu)
     * @param array $vedette_data Nouvelles données
     * @return bool Succès de la mise à jour
     */
    public static function update_vedette_data($term_id, $vedette_data) {
        // Validation du term_id avec plus de debug
        $term = get_term($term_id, 'post_tag');
        if (!$term || is_wp_error($term)) {
            error_log("Sisme Vedettes: Terme $term_id n'existe pas ou erreur");
            return false;
        }
        
        // Merger avec les données existantes
        $current_data = self::get_vedette_data($term_id);
        $vedette_data = array_merge($current_data, $vedette_data);
        
        // Validation des données
        $vedette_data = self::validate_vedette_data($vedette_data);
        
        // Sauvegarder chaque méta
        $success = true;
        foreach (self::META_KEYS as $key => $meta_key) {
            if (isset($vedette_data[$key])) {
                $value = $vedette_data[$key];
                $existing_value = get_term_meta($term_id, $meta_key, true);
                
                // Si pas de valeur existante, utiliser add_term_meta
                if ($existing_value === '') {
                    $result = add_term_meta($term_id, $meta_key, $value);
                    if (!$result) {
                        $success = false;
                        error_log("Sisme Vedettes: ÉCHEC add_term_meta $meta_key pour terme $term_id");
                    }
                } else {
                    // Valeur existante, utiliser update_term_meta
                    $result = update_term_meta($term_id, $meta_key, $value);
                    
                    // update_term_meta retourne false si la valeur n'a pas changé (comportement normal)
                    if ($result === false) {
                        // Vérifier si la valeur est réellement identique
                        $current_value = get_term_meta($term_id, $meta_key, true);
                        if ($current_value != $value) {
                            // Vraie erreur
                            $success = false;
                            error_log("Sisme Vedettes: ERREUR $meta_key pour terme $term_id");
                        }
                    }
                }
            }
        }
        
        // Log simple pour debug
        if ($success) {
            error_log("Sisme Vedettes: Jeu $term_id mis à jour - Featured: " . 
                     ($vedette_data['is_featured'] ? 'YES' : 'NO') . 
                     " Priority: " . $vedette_data['featured_priority']);
        }
        
        return $success;
    }
    
    /**
     * Marquer un jeu comme vedette
     * 
     * @param int $term_id ID du terme
     * @param int $priority Priorité (0-100)
     * @param string $sponsor Nom du sponsor (optionnel)
     * @param string $start_date Date début (optionnel)
     * @param string $end_date Date fin (optionnel)
     * @return bool Succès
     */
    public static function set_as_featured($term_id, $priority = 50, $sponsor = '', $start_date = null, $end_date = null) {
        $vedette_data = array(
            'is_featured' => true,
            'featured_priority' => $priority,
            'featured_sponsor' => $sponsor,
            'featured_start_date' => $start_date,
            'featured_end_date' => $end_date,
            'featured_created_at' => current_time('mysql')
        );
        
        return self::update_vedette_data($term_id, $vedette_data);
    }
    
    /**
     * Retirer un jeu des vedettes
     * 
     * @param int $term_id ID du terme
     * @return bool Succès
     */
    public static function remove_from_featured($term_id) {
        $vedette_data = array(
            'is_featured' => false,
            'featured_priority' => 0,
            'featured_sponsor' => '',
            'featured_start_date' => null,
            'featured_end_date' => null
        );
        
        return self::update_vedette_data($term_id, $vedette_data);
    }
    
    /**
     * Récupérer tous les jeux en vedette, triés par priorité (VERSION CORRIGÉE)
     * 
     * @param bool $only_active Seulement les vedettes actives (selon dates)
     * @return array Liste des jeux vedettes avec leurs données
     */
    public static function get_featured_games($only_active = true) {
        // ✨ RÉCUPÉRER TOUS LES JEUX D'ABORD
        $all_games = get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'game_description',
                    'compare' => 'EXISTS'
                )
            )
        ));
        
        if (is_wp_error($all_games) || empty($all_games)) {
            error_log("Sisme Vedettes: Aucun jeu trouvé pour get_featured_games");
            return array();
        }
        
        $featured_games = array();
        $current_date = current_time('Y-m-d H:i:s');
        
        foreach ($all_games as $term) {
            $vedette_data = self::get_vedette_data($term->term_id);
            
            // ✨ VÉRIFIER SI RÉELLEMENT EN VEDETTE
            if (!$vedette_data['is_featured']) {
                continue; // Ignorer les jeux avec is_featured = false
            }
            
            // Filtrage par dates si demandé
            if ($only_active) {
                $start_date = $vedette_data['featured_start_date'];
                $end_date = $vedette_data['featured_end_date'];
                
                if ($start_date && $start_date > $current_date) {
                    continue; // Pas encore actif
                }
                
                if ($end_date && $end_date < $current_date) {
                    continue; // Expiré
                }
            }
            
            $featured_games[] = array(
                'term_id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'vedette_data' => $vedette_data
            );
        }
        
        // Trier par priorité (plus haute priorité en premier)
        usort($featured_games, function($a, $b) {
            return $b['vedette_data']['featured_priority'] - $a['vedette_data']['featured_priority'];
        });
        
        error_log("Sisme Vedettes: " . count($featured_games) . " jeux en vedette trouvés");
        
        return $featured_games;
    }
    
    /**
     * Valider les données vedette
     * 
     * @param array $data Données à valider
     * @return array Données validées
     */
    private static function validate_vedette_data($data) {
        // ✅ SOLUTION PROPRE: Utiliser des strings "true"/"false"
        $data['is_featured'] = $data['is_featured'] ? 'true' : 'false';
        
        // Valider featured_priority (0-100)
        $data['featured_priority'] = max(0, min(100, (int) $data['featured_priority']));
        
        // Valider featured_sponsor
        $data['featured_sponsor'] = sanitize_text_field($data['featured_sponsor']);
        
        // Valider les dates
        if ($data['featured_start_date'] && !self::is_valid_date($data['featured_start_date'])) {
            $data['featured_start_date'] = null;
        }
        if ($data['featured_end_date'] && !self::is_valid_date($data['featured_end_date'])) {
            $data['featured_end_date'] = null;
        }
        
        // Valider les stats
        if (!is_array($data['featured_stats'])) {
            $data['featured_stats'] = self::DEFAULT_VEDETTE_DATA['featured_stats'];
        } else {
            $data['featured_stats']['views'] = max(0, (int) ($data['featured_stats']['views'] ?? 0));
            $data['featured_stats']['clicks'] = max(0, (int) ($data['featured_stats']['clicks'] ?? 0));
        }
        
        return $data;
    }
    
    /**
     * Vérifier si une date est valide
     * 
     * @param string $date Date au format Y-m-d H:i:s
     * @return bool
     */
    private static function is_valid_date($date) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $date);
        return $d && $d->format('Y-m-d H:i:s') === $date;
    }
    
    /**
     * Initialiser automatiquement les données vedettes pour un nouveau jeu
     * 
     * @param int $term_id ID du terme (jeu)
     * @return bool Succès de l'initialisation
     */
    public static function initialize_new_game($term_id) {
        // Vérifier que le terme existe
        if (!term_exists($term_id, 'post_tag')) {
            error_log("Sisme Vedettes: Impossible d'initialiser - terme $term_id n'existe pas");
            return false;
        }
        
        // Vérifier si déjà initialisé
        $existing_featured = get_term_meta($term_id, self::META_KEYS['is_featured'], true);
        if ($existing_featured !== '') {
            error_log("Sisme Vedettes: Jeu $term_id déjà initialisé");
            return true; // Déjà initialisé, pas d'erreur
        }
        
        // Initialiser avec les valeurs par défaut
        $init_data = self::DEFAULT_VEDETTE_DATA;
        $init_data['featured_created_at'] = current_time('mysql');
        
        $success = self::update_vedette_data($term_id, $init_data);
        
        if ($success) {
            error_log("Sisme Vedettes: Jeu $term_id initialisé automatiquement");
        } else {
            error_log("Sisme Vedettes: Échec initialisation jeu $term_id");
        }
        
        return $success;
    }
    
    /**
     * Incrémenter les stats d'un jeu vedette
     * 
     * @param int $term_id ID du terme
     * @param string $stat_type 'views' ou 'clicks'
     * @return bool Succès
     */
    public static function increment_stat($term_id, $stat_type) {
        if (!in_array($stat_type, ['views', 'clicks'])) {
            return false;
        }
        
        $vedette_data = self::get_vedette_data($term_id);
        $vedette_data['featured_stats'][$stat_type]++;
        
        return self::update_vedette_data($term_id, $vedette_data);
    }

    /**
     * Fonction de debug pour vérifier l'état d'un jeu
     * 
     * @param int $term_id ID du terme
     * @return array État complet du jeu
     */
    public static function debug_game_state($term_id) {
        $debug_info = array(
            'term_id' => $term_id,
            'term_exists' => term_exists($term_id, 'post_tag'),
            'has_game_description' => get_term_meta($term_id, 'game_description', true) !== '',
            'raw_meta' => array(),
            'vedette_data' => self::get_vedette_data($term_id)
        );
        
        // Récupérer toutes les metas brutes
        foreach (self::META_KEYS as $key => $meta_key) {
            $debug_info['raw_meta'][$meta_key] = get_term_meta($term_id, $meta_key, true);
        }
        
        return $debug_info;
    }
}

?>