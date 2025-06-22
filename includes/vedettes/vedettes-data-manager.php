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
        error_log("Force initialize BRUTAL pour terme $term_id");
        
        if (!term_exists($term_id, 'post_tag')) {
            error_log("Terme $term_id n'existe pas");
            return false;
        }
        
        // Données par défaut
        $default_data = array(
            'game_is_featured' => 'false',
            'game_featured_priority' => 0,
            'game_featured_start_date' => null,
            'game_featured_end_date' => null,
            'game_featured_sponsor' => '',
            'game_featured_created_at' => current_time('mysql'),
            'game_featured_stats' => array('views' => 0, 'clicks' => 0)
        );
        
        $success = true;
        
        foreach ($default_data as $meta_key => $value) {
            // BRUTAL: Supprimer puis recréer
            delete_term_meta($term_id, $meta_key);
            $result = add_term_meta($term_id, $meta_key, $value, true);
            
            if (!$result) {
                // Fallback avec update
                update_term_meta($term_id, $meta_key, $value);
            }
        }
        
        error_log("Jeu $term_id initialisé en mode BRUTAL");
        return true; // On considère toujours que ça a marché
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
        error_log("Update vedette data BRUTAL pour terme $term_id");
        
        if (!term_exists($term_id, 'post_tag')) {
            error_log("Terme $term_id n'existe pas");
            return false;
        }
        
        // Mapper les clés internes vers les meta_keys
        $meta_mapping = array(
            'is_featured' => 'game_is_featured',
            'featured_priority' => 'game_featured_priority',
            'featured_start_date' => 'game_featured_start_date',
            'featured_end_date' => 'game_featured_end_date',
            'featured_sponsor' => 'game_featured_sponsor',
            'featured_created_at' => 'game_featured_created_at',
            'featured_stats' => 'game_featured_stats'
        );
        
        foreach ($vedette_data as $key => $value) {
            if (isset($meta_mapping[$key])) {
                $meta_key = $meta_mapping[$key];
                
                // Conversion pour is_featured
                if ($key === 'is_featured') {
                    $value = $value ? 'true' : 'false';  // Bool vers string
                }
                
                // BRUTAL: Supprimer puis recréer
                delete_term_meta($term_id, $meta_key);
                $result = add_term_meta($term_id, $meta_key, $value, true);
                
                if (!$result) {
                    // Fallback avec update
                    update_term_meta($term_id, $meta_key, $value);
                }
                
                error_log("$meta_key = " . var_export($value, true) . " pour terme $term_id");
            }
        }
        
        error_log("Vedette data mis à jour en mode BRUTAL pour terme $term_id");
        return true;
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
        error_log("Set as featured BRUTAL pour terme $term_id avec priorité $priority");
        
        if (!term_exists($term_id, 'post_tag')) {
            error_log("Terme $term_id n'existe pas");
            return false;
        }
        
        // Données vedette
        $featured_data = array(
            'game_is_featured' => 'true',  // STRING "true" !
            'game_featured_priority' => (int) $priority,
            'game_featured_sponsor' => (string) $sponsor,
            'game_featured_start_date' => $start_date,
            'game_featured_end_date' => $end_date,
            'game_featured_created_at' => current_time('mysql'),
            'game_featured_stats' => array('views' => 0, 'clicks' => 0)
        );
        
        foreach ($featured_data as $meta_key => $value) {
            // BRUTAL: Supprimer puis recréer
            delete_term_meta($term_id, $meta_key);
            $result = add_term_meta($term_id, $meta_key, $value, true);
            
            if (!$result) {
                // Fallback avec update
                update_term_meta($term_id, $meta_key, $value);
            }
            
            error_log("$meta_key = " . var_export($value, true) . " pour terme $term_id");
        }
        
        // Vider le cache
        if (class_exists('Sisme_Vedettes_API')) {
            Sisme_Vedettes_API::clear_cache();
        }
        
        error_log("Jeu $term_id mis en vedette en mode BRUTAL");
        return true;
    }
    
    /**
     * Retirer un jeu des vedettes
     * 
     * @param int $term_id ID du terme
     * @return bool Succès
     */
    public static function remove_from_featured($term_id) {
        error_log("Remove from featured BRUTAL pour terme $term_id");
        
        if (!term_exists($term_id, 'post_tag')) {
            error_log("Terme $term_id n'existe pas");
            return false;
        }
        
        // Données non-vedette
        $non_featured_data = array(
            'game_is_featured' => 'false',  // STRING "false" !
            'game_featured_priority' => 0,
            'game_featured_sponsor' => '',
            'game_featured_start_date' => null,
            'game_featured_end_date' => null
            // On garde created_at et stats
        );
        
        foreach ($non_featured_data as $meta_key => $value) {
            // BRUTAL: Supprimer puis recréer
            delete_term_meta($term_id, $meta_key);
            $result = add_term_meta($term_id, $meta_key, $value, true);
            
            if (!$result) {
                // Fallback avec update
                update_term_meta($term_id, $meta_key, $value);
            }
            
            error_log("$meta_key = " . var_export($value, true) . " pour terme $term_id");
        }
        
        // Vider le cache
        if (class_exists('Sisme_Vedettes_API')) {
            Sisme_Vedettes_API::clear_cache();
        }
        
        error_log("Jeu $term_id retiré des vedettes en mode BRUTAL");
        return true;
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
        error_log("Initialize new game BRUTAL pour terme $term_id");
        
        if (!term_exists($term_id, 'post_tag')) {
            error_log("Terme $term_id n'existe pas");
            return false;
        }
        
        // Utiliser la même méthode brutale
        return self::force_initialize_game($term_id);
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
        
        // Récupérer les stats actuelles
        $current_stats = get_term_meta($term_id, 'game_featured_stats', true);
        if (!is_array($current_stats)) {
            $current_stats = array('views' => 0, 'clicks' => 0);
        }
        
        // Incrémenter
        $current_stats[$stat_type] = (int) $current_stats[$stat_type] + 1;
        
        // BRUTAL: Supprimer puis recréer
        delete_term_meta($term_id, 'game_featured_stats');
        $result = add_term_meta($term_id, 'game_featured_stats', $current_stats, true);
        
        if (!$result) {
            // Fallback avec update
            update_term_meta($term_id, 'game_featured_stats', $current_stats);
        }
        
        error_log("Stat $stat_type incrémentée pour terme $term_id: " . $current_stats[$stat_type]);
        return true;
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

    /**
     * 7. FONCTION DE RÉPARATION GLOBALE (bonus)
     */
    public static function repair_all_games() {
        error_log("=== RÉPARATION GLOBALE DE TOUS LES JEUX ===");
        
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
        
        if (is_wp_error($all_games)) {
            return false;
        }
        
        $repaired = 0;
        foreach ($all_games as $game) {
            if (self::force_initialize_game($game->term_id)) {
                $repaired++;
            }
        }
        
        error_log("=== RÉPARATION TERMINÉE: $repaired jeux réparés ===");
        return $repaired;
    }

    /**
     * Rechercher des jeux par nom (pour autocomplete)
     * 
     * @param string $search Terme de recherche
     * @param bool $only_non_featured Uniquement les jeux non en vedette
     * @return array Jeux correspondants
     */
    public static function search_games($search, $only_non_featured = false) {
        $args = array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'name__like' => $search,
            'meta_query' => array(
                array(
                    'key' => 'game_description',
                    'compare' => 'EXISTS'
                )
            ),
            'number' => 20,
            'orderby' => 'name',
            'order' => 'ASC'
        );
        
        $games = get_terms($args);
        
        if (is_wp_error($games)) {
            return array();
        }
        
        $results = array();
        
        foreach ($games as $game) {
            $vedette_data = self::get_vedette_data($game->term_id);
            
            // Filtrer par statut si demandé
            if ($only_non_featured && $vedette_data['is_featured']) {
                continue;
            }
            
            $results[] = array(
                'term_id' => $game->term_id,
                'name' => $game->name,
                'slug' => $game->slug,
                'is_featured' => $vedette_data['is_featured']
            );
        }
        
        return $results;
    }
}

?>