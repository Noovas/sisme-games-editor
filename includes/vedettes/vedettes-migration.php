<?php
/**
 * File: /sisme-games-editor/includes/vedettes/vedettes-migration.php
 * Script de migration pour initialiser le système vedettes
 * 
 * RESPONSABILITÉ:
 * - Migrer tous les jeux existants avec vedette = false, priorité = 0
 * - Vérifier l'intégrité des données vedettes
 * - Nettoyer les données obsolètes
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/vedettes/vedettes-data-manager.php';

class Sisme_Vedettes_Migration {
    
    /**
     * Exécuter la migration complète (VERSION CORRIGÉE)
     * 
     * @return array Résultats de la migration
     */
    public static function run_migration() {
        $results = array(
            'success' => false,
            'total_games' => 0,
            'migrated_games' => 0,
            'already_migrated' => 0,
            'errors' => array(),
            'execution_time' => 0
        );
        
        $start_time = microtime(true);
        
        error_log("Sisme Vedettes Migration: DÉBUT migration BRUTALE");
        
        try {
            // Récupérer TOUS les jeux (termes avec game_description)
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
                throw new Exception('Erreur récupération des jeux: ' . $all_games->get_error_message());
            }
            
            $results['total_games'] = count($all_games);
            
            // Structure des données par défaut
            $default_data = array(
                'game_is_featured' => 'false',
                'game_featured_priority' => 0,
                'game_featured_start_date' => null,
                'game_featured_end_date' => null,
                'game_featured_sponsor' => '',
                'game_featured_created_at' => current_time('mysql'),
                'game_featured_stats' => array('views' => 0, 'clicks' => 0)
            );
            
            foreach ($all_games as $game_term) {
                try {
                    $game_id = $game_term->term_id;
                    $game_name = $game_term->name;
                    
                    error_log("Migration jeu: $game_name (ID: $game_id)");
                    
                    // MÉTHODE BRUTALE: Supprimer puis recréer TOUTES les metas
                    foreach ($default_data as $meta_key => $default_value) {
                        // Supprimer l'ancienne valeur (peu importe si elle existe)
                        delete_term_meta($game_id, $meta_key);
                        
                        // Ajouter la nouvelle valeur
                        $add_result = add_term_meta($game_id, $meta_key, $default_value, true);
                        
                        if ($add_result) {
                            error_log("✅ $meta_key ajouté pour jeu $game_id");
                        } else {
                            // Essayer avec update au cas où
                            $update_result = update_term_meta($game_id, $meta_key, $default_value);
                            error_log("🔄 $meta_key mis à jour pour jeu $game_id (résultat: " . var_export($update_result, true) . ")");
                        }
                    }
                    
                    $results['migrated_games']++;
                    error_log("✅ Jeu '$game_name' (ID: $game_id) migré avec succès");
                    
                } catch (Exception $e) {
                    $error_msg = "Erreur jeu '{$game_term->name}' (ID: {$game_term->term_id}): " . $e->getMessage();
                    $results['errors'][] = $error_msg;
                    error_log("❌ " . $error_msg);
                }
            }
            
            $results['success'] = true;
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            error_log("❌ ERREUR FATALE Migration: " . $e->getMessage());
        }
        
        $results['execution_time'] = microtime(true) - $start_time;
        
        error_log("Sisme Vedettes Migration: FIN - " . 
                 "Total: {$results['total_games']}, " .
                 "Migrés: {$results['migrated_games']}, " .
                 "Erreurs: " . count($results['errors']) . ", " .
                 "Temps: " . round($results['execution_time'], 2) . "s");
        
        return $results;
    }
    
    /**
     * Vérifier l'intégrité des données d'un jeu
     * 
     * @param int $term_id ID du jeu
     * @return bool Intégrité OK
     */
    private static function verify_game_integrity($term_id) {
        $vedette_data = Sisme_Vedettes_Data_Manager::get_vedette_data($term_id);
        
        $fixes_needed = array();
        
        // Vérifier que les données sont cohérentes
        if (!is_bool($vedette_data['is_featured'])) {
            $fixes_needed[] = 'is_featured type incorrect';
        }
        
        if (!is_int($vedette_data['featured_priority']) || 
            $vedette_data['featured_priority'] < 0 || 
            $vedette_data['featured_priority'] > 100) {
            $fixes_needed[] = 'featured_priority hors limites';
        }
        
        if (!is_array($vedette_data['featured_stats'])) {
            $fixes_needed[] = 'featured_stats format incorrect';
        }
        
        // Corriger si nécessaire
        if (!empty($fixes_needed)) {
            error_log("Sisme Vedettes Migration: Correction nécessaire pour terme $term_id: " . implode(', ', $fixes_needed));
            
            return Sisme_Vedettes_Data_Manager::update_vedette_data($term_id, $vedette_data);
        }
        
        return true;
    }
    
    /**
     * Nettoyer les données vedettes obsolètes
     * 
     * @return array Résultats du nettoyage
     */
    public static function cleanup_obsolete_data() {
        $results = array(
            'cleaned_terms' => 0,
            'errors' => array()
        );
        
        // Récupérer tous les termes qui ont des méta vedettes mais ne sont plus des jeux
        $obsolete_terms = get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'game_is_featured',
                    'compare' => 'EXISTS'
                ),
                array(
                    'key' => 'game_description',
                    'compare' => 'NOT EXISTS'
                )
            )
        ));
        
        if (!is_wp_error($obsolete_terms)) {
            foreach ($obsolete_terms as $term) {
                try {
                    // Supprimer toutes les méta vedettes
                    foreach (Sisme_Vedettes_Data_Manager::META_KEYS as $meta_key) {
                        delete_term_meta($term->term_id, $meta_key);
                    }
                    
                    $results['cleaned_terms']++;
                    error_log("Sisme Vedettes Cleanup: Nettoyage terme obsolète '{$term->name}' (ID: {$term->term_id})");
                    
                } catch (Exception $e) {
                    $results['errors'][] = "Erreur nettoyage terme {$term->term_id}: " . $e->getMessage();
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Obtenir un rapport sur l'état actuel des vedettes
     * 
     * @return array Rapport détaillé
     */
    public static function get_migration_report() {
        $report = array(
            'total_games' => 0,
            'games_with_vedette_data' => 0,
            'featured_games' => 0,
            'games_by_priority' => array(),
            'games_with_sponsor' => 0,
            'games_with_dates' => 0,
            'total_views' => 0,
            'total_clicks' => 0
        );
        
        // Récupérer tous les jeux
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
            return $report;
        }
        
        $report['total_games'] = count($all_games);
        
        foreach ($all_games as $game) {
            $vedette_data = Sisme_Vedettes_Data_Manager::get_vedette_data($game->term_id);
            
            // Compter les jeux avec données vedettes
            $has_vedette_meta = get_term_meta($game->term_id, 'game_is_featured', true);
            if ($has_vedette_meta !== '') {
                $report['games_with_vedette_data']++;
            }
            
            // Compter les jeux en vedette
            if ($vedette_data['is_featured']) {
                $report['featured_games']++;
                
                // Grouper par priorité
                $priority = $vedette_data['featured_priority'];
                if (!isset($report['games_by_priority'][$priority])) {
                    $report['games_by_priority'][$priority] = 0;
                }
                $report['games_by_priority'][$priority]++;
                
                // Compter ceux avec sponsor
                if (!empty($vedette_data['featured_sponsor'])) {
                    $report['games_with_sponsor']++;
                }
                
                // Compter ceux avec dates
                if (!empty($vedette_data['featured_start_date']) || !empty($vedette_data['featured_end_date'])) {
                    $report['games_with_dates']++;
                }
                
                // Additionner les stats
                $report['total_views'] += $vedette_data['featured_stats']['views'];
                $report['total_clicks'] += $vedette_data['featured_stats']['clicks'];
            }
        }
        
        return $report;
    }
}

?>