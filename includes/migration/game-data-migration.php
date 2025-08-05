<?php
/**
 * File: /sisme-games-editor/includes/migration/game-data-migration.php
 * Script de migration sécurisé : Ancien format vers nouveau format
 * 
 * RESPONSABILITÉ:
 * - Migration automatique des données de jeu
 * - Transformation screenshots, modes, covers
 * - Validation et nettoyage des données
 * - Sauvegarde et restauration
 * 
 * CHANGEMENTS PRINCIPAUX :
 * 1. Screenshots : chaîne "ID,ID,ID" → tableau PHP sérialisé
 * 2. Modes de jeu : "cooperatif" → "coop"
 * 3. Nettoyage covers (garder seulement main et vertical)
 * 4. Restructuration game_sections
 * 5. Ajout nouveaux champs featured
 */

if (!defined('ABSPATH')) {
    die('Accès direct interdit');
}

class Sisme_Game_Data_Migration {
    
    /**
     * Configuration de migration
     */
    const BATCH_SIZE = 10; // Traiter par lots pour éviter timeout
    const BACKUP_META_PREFIX = '_sisme_old_'; // Préfixe sauvegarde
    
    /**
     * Mapping des transformations
     */
    private static $mode_mapping = [
        'cooperatif' => 'coop',
        'multijoueur' => 'multijoueur',
        'solo' => 'solo'
    ];
    
    /**
     * Point d'entrée principal de la migration
     * 
     * @param bool $dry_run Mode simulation (pas de modifications)
     * @return array Rapport de migration
     */
    public static function migrate_all_games($dry_run = true) {
        $report = [
            'total_games' => 0,
            'migrated' => 0,
            'errors' => [],
            'skipped' => [],
            'transformations' => [],
            'dry_run' => $dry_run
        ];
        
        // Obtenir tous les jeux existants
        $games = self::get_all_games_to_migrate();
        $report['total_games'] = count($games);
        
        if (empty($games)) {
            $report['errors'][] = 'Aucun jeu trouvé à migrer';
            return $report;
        }
        
        foreach ($games as $game_term) {
            try {
                $migration_result = self::migrate_single_game($game_term->term_id, $dry_run);
                
                if ($migration_result['success']) {
                    $report['migrated']++;
                    if (!empty($migration_result['transformations'])) {
                        $report['transformations'][$game_term->term_id] = $migration_result['transformations'];
                    }
                } else {
                    $report['errors'][] = "Jeu {$game_term->term_id} ({$game_term->name}): {$migration_result['error']}";
                }
                
            } catch (Exception $e) {
                $report['errors'][] = "Jeu {$game_term->term_id}: Exception - {$e->getMessage()}";
            }
        }
        
        return $report;
    }
    
    /**
     * Migrer un jeu unique
     * 
     * @param int $term_id ID du jeu
     * @param bool $dry_run Mode simulation
     * @return array Résultat de migration
     */
    public static function migrate_single_game($term_id, $dry_run = true) {
        $result = [
            'success' => false,
            'error' => '',
            'transformations' => []
        ];
        
        // Vérifier que le jeu existe
        $term = get_term($term_id, 'post_tag');
        if (!$term || is_wp_error($term)) {
            $result['error'] = 'Jeu introuvable';
            return $result;
        }
        
        // Récupérer toutes les métadonnées actuelles
        $current_meta = self::get_all_term_meta($term_id);
        
        if (empty($current_meta)) {
            $result['error'] = 'Aucune métadonnée trouvée';
            return $result;
        }
        
        // Sauvegarder l'état actuel (si pas dry run)
        if (!$dry_run) {
            self::backup_current_meta($term_id, $current_meta);
        }
        
        // Transformer les données
        $new_meta = self::transform_game_meta($current_meta, $result['transformations']);
        
        // Valider les nouvelles données
        $validation = self::validate_transformed_data($new_meta);
        if (!$validation['valid']) {
            $result['error'] = 'Validation échouée: ' . implode(', ', $validation['errors']);
            return $result;
        }
        
        // Appliquer les changements (si pas dry run)
        if (!$dry_run) {
            $apply_result = self::apply_new_meta($term_id, $new_meta);
            if (!$apply_result) {
                $result['error'] = 'Erreur lors de l\'application des changements';
                return $result;
            }
        }
        
        $result['success'] = true;
        return $result;
    }
    
    /**
     * Récupérer tous les jeux à migrer
     * 
     * @return array Liste des termes WP_Term
     */
    private static function get_all_games_to_migrate() {
        return get_terms([
            'taxonomy' => 'post_tag',
            'meta_query' => [
                [
                    'key' => 'game_description',
                    'compare' => 'EXISTS'
                ]
                // SUPPRIMER la condition NOT EXISTS pour permettre re-migration
            ],
            'hide_empty' => false,
            'number' => 0
        ]);
    }
    
    /**
     * Récupérer toutes les métadonnées d'un jeu avec nettoyage
     * 
     * @param int $term_id ID du terme
     * @return array Métadonnées
     */
    private static function get_all_term_meta($term_id) {
        global $wpdb;
        
        $meta_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$wpdb->termmeta} WHERE term_id = %d",
            $term_id
        ));
        
        $meta_data = [];
        foreach ($meta_rows as $row) {
            $value = $row->meta_value;
            
            $meta_data[$row->meta_key] = maybe_unserialize($value);
        }
        
        // Ajouter l'ID du terme pour la recherche de sections
        $meta_data['term_id'] = $term_id;
        
        error_log("[Migration Debug] Jeu {$term_id} - Meta chargées: " . implode(', ', array_keys($meta_data)));
        return $meta_data;
    }
    
    /**
     * Transformer les métadonnées vers le nouveau format
     * 
     * @param array $current_meta Métadonnées actuelles
     * @param array &$transformations Log des transformations
     * @return array Nouvelles métadonnées
     */
    private static function transform_game_meta($current_meta, &$transformations) {
        $new_meta = $current_meta; // Copie de base
        
        // 1. TRANSFORMATION SCREENSHOTS
        if (isset($current_meta['screenshots']) && is_string($current_meta['screenshots'])) {
            $screenshot_ids = array_filter(array_map('intval', explode(',', $current_meta['screenshots'])));
            if (!empty($screenshot_ids)) {
                $new_meta['screenshots'] = $screenshot_ids;
                $transformations[] = "Screenshots: chaîne → tableau (" . count($screenshot_ids) . " éléments)";
            }
        }
        
        // 2. TRANSFORMATION MODES DE JEU
        if (isset($current_meta['game_modes']) && is_array($current_meta['game_modes'])) {
            $transformed_modes = [];
            foreach ($current_meta['game_modes'] as $mode) {
                $transformed_modes[] = self::$mode_mapping[$mode] ?? $mode;
            }
            if ($transformed_modes !== $current_meta['game_modes']) {
                $new_meta['game_modes'] = $transformed_modes;
                $transformations[] = "Modes: " . implode(', ', $current_meta['game_modes']) . " → " . implode(', ', $transformed_modes);
            }
        }
        
        // 3. NETTOYAGE COVERS (garder seulement main et vertical)
        self::clean_covers($current_meta, $new_meta, $transformations);
        
        // 4. RÉCUPÉRATION SECTIONS DEPUIS POST_META
        $sections_from_posts = self::get_sections_from_posts($current_meta['term_id'] ?? 0);
        if (!empty($sections_from_posts)) {
            $new_meta['game_sections'] = $sections_from_posts;
            $transformations[] = "Sections: récupérées depuis post_meta (" . count($sections_from_posts) . " sections)";
        } elseif (isset($current_meta['game_sections'])) {
            // Fallback sur les sections déjà en term_meta
            if (is_array($current_meta['game_sections'])) {
                $new_meta['game_sections'] = $current_meta['game_sections'];
                $transformations[] = "Sections: conservées (déjà en term_meta)";
            } else {
                $unserialized = @unserialize($current_meta['game_sections']);
                if (is_array($unserialized)) {
                    $new_meta['game_sections'] = $unserialized;
                    $transformations[] = "Sections: désérialisées avec succès";
                }
            }
        }
        
        // 5. VALIDATION EXTERNAL_LINKS
        if (isset($current_meta['external_links'])) {
            $cleaned_links = self::clean_external_links($current_meta['external_links']);
            if ($cleaned_links !== $current_meta['external_links']) {
                $new_meta['external_links'] = $cleaned_links;
                $transformations[] = "External links: validation et nettoyage";
            }
        }
        
        // 6. FORCER LA RÉÉCRITURE DE TOUTES LES DONNÉES (même non modifiées)
        self::force_rewrite_all_meta($current_meta, $new_meta, $transformations);
        
        return $new_meta;
    }
    
    /**
     * Nettoyer les covers - garder seulement main et vertical
     * 
     * @param array $current_meta Métadonnées actuelles
     * @param array &$new_meta Nouvelles métadonnées
     * @param array &$transformations Log
     */
    private static function clean_covers($current_meta, &$new_meta, &$transformations) {
        $covers_to_keep = ['cover_main', 'cover_vertical'];
        $covers_to_remove = ['cover_news', 'cover_patch', 'cover_test'];
        
        $removed_covers = [];
        
        // Supprimer les covers non utilisées
        foreach ($covers_to_remove as $cover_key) {
            if (isset($new_meta[$cover_key])) {
                unset($new_meta[$cover_key]);
                $removed_covers[] = $cover_key;
            }
        }
        
        // S'assurer que cover_main existe
        if (empty($new_meta['cover_main'])) {
            // Chercher une cover de fallback parmi les anciennes
            foreach ($covers_to_remove as $cover_key) {
                if (!empty($current_meta[$cover_key])) {
                    $new_meta['cover_main'] = $current_meta[$cover_key];
                    $transformations[] = "Cover main: récupérée depuis {$cover_key}";
                    break;
                }
            }
        }
        
        if (!empty($removed_covers)) {
            $transformations[] = "Covers supprimées: " . implode(', ', $removed_covers);
        }
    }
    
    /**
     * Récupérer les sections depuis les post_meta des articles
     * 
     * @param int $term_id ID du terme (jeu)
     * @return array Sections trouvées
     */
    private static function get_sections_from_posts($term_id) {
        global $wpdb;
        
        if (!$term_id) {
            return [];
        }
        
        // Chercher les posts qui ont ce tag ET des sections
        $posts_with_sections = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, pm.meta_value 
            FROM {$wpdb->posts} p
            JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE tr.term_taxonomy_id = (
                SELECT term_taxonomy_id 
                FROM {$wpdb->term_taxonomy} 
                WHERE term_id = %d AND taxonomy = 'post_tag'
            )
            AND pm.meta_key = '_sisme_game_sections'
            AND pm.meta_value != ''
            ORDER BY p.post_date DESC
            LIMIT 1
        ", $term_id));
        
        if (empty($posts_with_sections)) {
            return [];
        }
        
        $sections_data = maybe_unserialize($posts_with_sections[0]->meta_value);
        
        if (!is_array($sections_data)) {
            return [];
        }
        
        error_log("[Migration Debug] Jeu {$term_id} - Sections trouvées dans post_meta: " . count($sections_data) . " sections");
        return $sections_data;
    }
    
    /**
     * Nettoyer les liens externes
     * 
     * @param mixed $links Liens actuels
     * @return array Liens nettoyés
     */
    private static function clean_external_links($links) {
        if (!is_array($links)) {
            return [];
        }
        
        $cleaned = [];
        $valid_platforms = ['steam', 'epic', 'gog', 'xbox', 'playstation', 'nintendo', 'itch', 'amazon'];
        
        foreach ($links as $platform => $url) {
            if (in_array($platform, $valid_platforms) && filter_var($url, FILTER_VALIDATE_URL)) {
                $cleaned[$platform] = esc_url_raw($url);
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Forcer la réécriture de toutes les métadonnées importantes
     * 
     * @param array $current_meta Métadonnées actuelles
     * @param array &$new_meta Nouvelles métadonnées
     * @param array &$transformations Log
     */
    private static function force_rewrite_all_meta($current_meta, &$new_meta, &$transformations) {
        // Lister toutes les meta_keys importantes qui doivent être réécrites
        $meta_keys_to_rewrite = [
            'game_description',
            'release_date', 
            'game_platforms',
            'game_genres',
            'game_modes', 
            'game_developers',
            'game_publishers',
            'external_links',
            'trailer_link',
            'screenshots',
            'game_sections',  // PROBLÉMATIQUE !
            'cover_main',
            'cover_vertical',
            'last_update'
        ];
        
        $rewritten_keys = [];
        
        foreach ($meta_keys_to_rewrite as $meta_key) {
            if (isset($current_meta[$meta_key])) {
                // VÉRIFICATION SPÉCIALE POUR GAME_SECTIONS
                if ($meta_key === 'game_sections' && isset($new_meta[$meta_key])) {
                    // Si les sections ont été nettoyées et sont devenues vides, garder les originales
                    if (empty($new_meta[$meta_key]) && !empty($current_meta[$meta_key])) {
                        $new_meta[$meta_key] = $current_meta[$meta_key];
                        $transformations[] = "Sections: conservation des données originales (nettoyage a vidé)";
                    }
                } else {
                    // Forcer la présence dans new_meta pour réécriture
                    if (!isset($new_meta[$meta_key])) {
                        $new_meta[$meta_key] = $current_meta[$meta_key];
                    }
                }
                $rewritten_keys[] = $meta_key;
            }
        }
        
        // Ajouter les nouveaux champs requis
        self::add_required_new_fields($new_meta, $transformations);
        
        if (!empty($rewritten_keys)) {
            $transformations[] = "Réécriture forcée: " . implode(', ', $rewritten_keys);
        }
    }
    
    /**
     * Ajouter les nouveaux champs requis
     * 
     * @param array &$new_meta Nouvelles métadonnées
     * @param array &$transformations Log
     */
    private static function add_required_new_fields(&$new_meta, &$transformations) {
        $now = current_time('mysql');
        
        // Champs featured (FORCER LA RÉÉCRITURE)
        $new_meta['game_is_featured'] = $new_meta['game_is_featured'] ?? 'false';
        $new_meta['game_featured_priority'] = $new_meta['game_featured_priority'] ?? '0';
        $new_meta['game_featured_start_date'] = $new_meta['game_featured_start_date'] ?? '';
        $new_meta['game_featured_end_date'] = $new_meta['game_featured_end_date'] ?? '';
        $new_meta['game_featured_sponsor'] = $new_meta['game_featured_sponsor'] ?? '';
        $new_meta['game_featured_created_at'] = $new_meta['game_featured_created_at'] ?? $now;
        $new_meta['game_featured_stats'] = $new_meta['game_featured_stats'] ?? ['views' => 0, 'clicks' => 0];
        
        // Champ is_team_choice (FORCER LA RÉÉCRITURE)
        $new_meta['is_team_choice'] = $new_meta['is_team_choice'] ?? '0';
        
        // Mettre à jour last_update
        $new_meta['last_update'] = $now;
        
        $added_fields = [
            'game_is_featured', 'game_featured_priority', 'game_featured_start_date',
            'game_featured_end_date', 'game_featured_sponsor', 'game_featured_created_at', 
            'game_featured_stats', 'is_team_choice', 'last_update'
        ];
        
        $transformations[] = "Champs standardisés: " . implode(', ', $added_fields);
    }
    
    /**
     * Valider les données transformées
     * 
     * @param array $meta Métadonnées à valider
     * @return array Résultat de validation
     */
    private static function validate_transformed_data($meta) {
        $errors = [];
        
        // Validation description
        if (isset($meta['game_description']) && strlen($meta['game_description']) > 500) {
            $errors[] = "Description trop longue (" . strlen($meta['game_description']) . " caractères)";
        }
        
        // Validation screenshots
        if (isset($meta['screenshots']) && is_array($meta['screenshots'])) {
            if (count($meta['screenshots']) > 10) {
                $errors[] = "Trop de screenshots (" . count($meta['screenshots']) . ")";
            }
        }
        
        // Validation sections
        if (isset($meta['game_sections']) && is_array($meta['game_sections'])) {
            if (count($meta['game_sections']) > 10) {
                $errors[] = "Trop de sections (" . count($meta['game_sections']) . ")";
            }
        }
        
        // Validation date
        if (isset($meta['release_date']) && !empty($meta['release_date'])) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $meta['release_date'])) {
                $errors[] = "Format de date invalide: " . $meta['release_date'];
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Sauvegarder l'état actuel avant migration
     * 
     * @param int $term_id ID du terme
     * @param array $current_meta Métadonnées actuelles
     */
    private static function backup_current_meta($term_id, $current_meta) {
        $backup_data = [
            'timestamp' => current_time('mysql'),
            'meta_data' => $current_meta
        ];
        
        update_term_meta($term_id, self::BACKUP_META_PREFIX . 'backup', $backup_data);
    }
    
    /**
     * Appliquer les nouvelles métadonnées avec réécriture forcée
     * 
     * @param int $term_id ID du terme
     * @param array $new_meta Nouvelles métadonnées
     * @return bool Succès
     */
    private static function apply_new_meta($term_id, $new_meta) {
        // FORCER LA RÉÉCRITURE DE TOUTES LES META_KEYS
        // Même si la valeur semble identique, on la réécrit pour s'assurer de la cohérence
        
        foreach ($new_meta as $meta_key => $meta_value) {
            // Utiliser update_term_meta qui remplace toujours la valeur
            $result = update_term_meta($term_id, $meta_key, $meta_value);
            
            // update_term_meta retourne false seulement en cas d'erreur, pas si la valeur est identique
            if ($result === false && get_term_meta($term_id, $meta_key, true) !== $meta_value) {
                error_log("[Migration] Erreur mise à jour {$meta_key} pour terme {$term_id}");
                return false;
            }
        }
        
        // Marquer comme migré avec timestamp
        update_term_meta($term_id, '_sisme_migrated_at', current_time('mysql'));
        update_term_meta($term_id, '_sisme_migration_version', '1.0');
        
        return true;
    }
    
    /**
     * Restaurer depuis la sauvegarde
     * 
     * @param int $term_id ID du terme
     * @return bool Succès
     */
    public static function restore_from_backup($term_id) {
        $backup = get_term_meta($term_id, self::BACKUP_META_PREFIX . 'backup', true);
        
        if (empty($backup) || !isset($backup['meta_data'])) {
            return false;
        }
        
        // Supprimer toutes les métadonnées actuelles
        $current_meta_keys = array_keys(self::get_all_term_meta($term_id));
        foreach ($current_meta_keys as $meta_key) {
            if (strpos($meta_key, self::BACKUP_META_PREFIX) !== 0) {
                delete_term_meta($term_id, $meta_key);
            }
        }
        
        // Restaurer les anciennes données
        foreach ($backup['meta_data'] as $meta_key => $meta_value) {
            update_term_meta($term_id, $meta_key, $meta_value);
        }
        
        return true;
    }
    
    /**
     * Générer un rapport détaillé
     * 
     * @param array $report Rapport de migration
     * @return string Rapport formaté
     */
    public static function format_migration_report($report) {
        $output = "=== RAPPORT DE MIGRATION ===\n";
        $output .= "Mode: " . ($report['dry_run'] ? 'SIMULATION' : 'RÉEL') . "\n";
        $output .= "Total jeux: {$report['total_games']}\n";
        $output .= "Migrés: {$report['migrated']}\n";
        $output .= "Erreurs: " . count($report['errors']) . "\n\n";
        
        if (!empty($report['errors'])) {
            $output .= "=== ERREURS ===\n";
            foreach ($report['errors'] as $error) {
                $output .= "- {$error}\n";
            }
            $output .= "\n";
        }
        
        if (!empty($report['transformations'])) {
            $output .= "=== TRANSFORMATIONS ===\n";
            foreach ($report['transformations'] as $term_id => $transforms) {
                $output .= "Jeu {$term_id}:\n";
                foreach ($transforms as $transform) {
                    $output .= "  - {$transform}\n";
                }
                $output .= "\n";
            }
        }
        
        return $output;
    }
}

/**
 * UTILISATION DU SCRIPT
 * 
 * 1. Mode simulation (recommandé d'abord) :
 * $report = Sisme_Game_Data_Migration::migrate_all_games(true);
 * echo Sisme_Game_Data_Migration::format_migration_report($report);
 * 
 * 2. Migration réelle (après validation) :
 * $report = Sisme_Game_Data_Migration::migrate_all_games(false);
 * 
 * 3. Restaurer un jeu spécifique :
 * Sisme_Game_Data_Migration::restore_from_backup($term_id);
 * 
 * 4. Migration d'un jeu unique :
 * $result = Sisme_Game_Data_Migration::migrate_single_game($term_id, false);
 */