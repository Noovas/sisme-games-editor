<?php
/**
 * File: /sisme-games-editor/includes/migration/migration-admin-interface.php
 * Interface d'administration pour la migration de données de jeu
 * 
 * RESPONSABILITÉ:
 * - Interface web pour gérer la migration
 * - Prévisualisation et exécution sécurisée
 * - Affichage des rapports et statistiques
 * - Gestion de la restauration
 * 
 * SÉCURITÉ :
 * - Accès administrateur uniquement
 * - Confirmation obligatoire pour migrations réelles
 * - Sauvegarde automatique avant migration
 * - Possibilité de restauration
 */

if (!defined('ABSPATH')) {
    die('Accès direct interdit');
}

// Inclure le script de migration
require_once plugin_dir_path(__FILE__) . 'game-data-migration.php';

class Sisme_Migration_Admin {
    
    /**
     * Hook pour ajouter la page d'administration
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('wp_ajax_sisme_migration_preview', [__CLASS__, 'ajax_migration_preview']);
        add_action('wp_ajax_sisme_migration_execute', [__CLASS__, 'ajax_migration_execute']);
        add_action('wp_ajax_sisme_migration_restore', [__CLASS__, 'ajax_migration_restore']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_scripts']);
    }
    
    /**
     * Ajouter le menu d'administration dans le plugin Sisme Games
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'sisme-games-game-data',        // Parent menu (votre plugin principal)
            'Migration données jeux',        // Page title
            '🔄 Migration',                 // Menu title
            'manage_options',               // Capability
            'sisme-game-migration',         // Menu slug
            [__CLASS__, 'admin_page']       // Callback
        );
    }
    
    /**
     * Charger les scripts admin
     */
    public static function enqueue_admin_scripts($hook) {
        // Vérifier que c'est bien notre page de migration
        if ($hook !== 'sisme-games_page_sisme-game-migration') {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'sismeMigration', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_migration_nonce')
        ]);
    }
    
    /**
     * Page d'administration
     */
    public static function admin_page() {
        ?>
        <div class="wrap">
            <h1>🔄 Migration des données de jeu</h1>
            
            <div class="notice notice-warning">
                <p><strong>⚠️ Attention :</strong> Cette opération modifie la structure des données de jeu. 
                Assurez-vous d'avoir une sauvegarde complète de votre site avant de continuer.</p>
            </div>
            
            <?php self::render_status_section(); ?>
            <?php self::render_preview_section(); ?>
            <?php self::render_migration_section(); ?>
            <?php self::render_restore_section(); ?>
            
        </div>
        
        <style>
        .migration-section {
            background: rgba(0,0,0,0.3);
            border: 1px solid #ddd;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .migration-log {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            font-family: monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            margin-top: 10px;
        }
        .status-good { color: #27ae60; font-weight: bold; }
        .status-warning { color: #f39c12; font-weight: bold; }
        .status-error { color: #e74c3c; font-weight: bold; }
        .migration-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .stat-box {
            background: rgba(255, 255, 255, 0.8);
            padding: 15px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            
            // Prévisualisation
            $('#btn-preview').click(function() {
                $(this).prop('disabled', true).text('Analyse en cours...');
                $('#preview-results').html('<div class="migration-log">Analyse des données en cours...</div>');
                
                $.post(sismeMigration.ajaxurl, {
                    action: 'sisme_migration_preview',
                    nonce: sismeMigration.nonce
                }, function(response) {
                    if (response.success) {
                        displayPreviewResults(response.data);
                    } else {
                        $('#preview-results').html('<div class="notice notice-error"><p>Erreur: ' + response.data + '</p></div>');
                    }
                    $('#btn-preview').prop('disabled', false).text('📊 Analyser les données');
                });
            });
            
            // Migration
            $('#btn-migrate').click(function() {
                if (!confirm('⚠️ ÊTES-VOUS SÛR ?\n\nCette opération va modifier définitivement vos données de jeu.\nAssurez-vous d\'avoir une sauvegarde complète.\n\nContinuer ?')) {
                    return;
                }
                
                $(this).prop('disabled', true).text('Migration en cours...');
                $('#migration-results').html('<div class="migration-log">Migration en cours...\nCela peut prendre plusieurs minutes.</div>');
                
                $.post(sismeMigration.ajaxurl, {
                    action: 'sisme_migration_execute',
                    nonce: sismeMigration.nonce
                }, function(response) {
                    if (response.success) {
                        displayMigrationResults(response.data);
                        location.reload(); // Recharger pour mettre à jour le statut
                    } else {
                        $('#migration-results').html('<div class="notice notice-error"><p>Erreur: ' + response.data + '</p></div>');
                    }
                    $('#btn-migrate').prop('disabled', false).text('🚀 Exécuter la migration');
                });
            });
            
            // Restauration
            $('.btn-restore').click(function() {
                var termId = $(this).data('term-id');
                var gameName = $(this).data('game-name');
                
                if (!confirm('Restaurer le jeu "' + gameName + '" ?\n\nCela supprimera les données migrées et restaurera l\'ancien format.')) {
                    return;
                }
                
                $(this).prop('disabled', true).text('Restauration...');
                
                $.post(sismeMigration.ajaxurl, {
                    action: 'sisme_migration_restore',
                    term_id: termId,
                    nonce: sismeMigration.nonce
                }, function(response) {
                    if (response.success) {
                        alert('✅ Jeu restauré avec succès');
                        location.reload();
                    } else {
                        alert('❌ Erreur lors de la restauration: ' + response.data);
                    }
                });
            });
            
            function displayPreviewResults(data) {
                var html = '<div class="migration-stats">';
                html += '<div class="stat-box"><div class="stat-number">' + data.total_games + '</div><div class="stat-label">Jeux analysés</div></div>';
                html += '<div class="stat-box"><div class="stat-number">' + data.migrated + '</div><div class="stat-label">Jeux à migrer</div></div>';
                html += '<div class="stat-box"><div class="stat-number">' + data.errors.length + '</div><div class="stat-label">Erreurs détectées</div></div>';
                html += '<div class="stat-box"><div class="stat-number">' + Object.keys(data.transformations).length + '</div><div class="stat-label">Jeux avec transformations</div></div>';
                html += '</div>';
                
                if (data.errors.length > 0) {
                    html += '<div class="notice notice-warning"><p><strong>⚠️ Erreurs détectées :</strong></p><ul>';
                    data.errors.forEach(function(error) {
                        html += '<li>' + error + '</li>';
                    });
                    html += '</ul></div>';
                }
                
                html += '<div class="migration-log">' + data.formatted_report + '</div>';
                $('#preview-results').html(html);
                
                // Activer le bouton de migration si pas d'erreurs critiques
                if (data.migrated > 0 && data.errors.length === 0) {
                    $('#btn-migrate').prop('disabled', false);
                }
            }
            
            function displayMigrationResults(data) {
                var html = '<div class="migration-stats">';
                html += '<div class="stat-box"><div class="stat-number">' + data.total_games + '</div><div class="stat-label">Jeux traités</div></div>';
                html += '<div class="stat-box"><div class="stat-number">' + data.migrated + '</div><div class="stat-label">Migrations réussies</div></div>';
                html += '<div class="stat-box"><div class="stat-number">' + data.errors.length + '</div><div class="stat-label">Échecs</div></div>';
                html += '</div>';
                
                if (data.migrated > 0) {
                    html += '<div class="notice notice-success"><p><strong>✅ Migration terminée avec succès !</strong></p></div>';
                }
                
                if (data.errors.length > 0) {
                    html += '<div class="notice notice-error"><p><strong>❌ Erreurs pendant la migration :</strong></p><ul>';
                    data.errors.forEach(function(error) {
                        html += '<li>' + error + '</li>';
                    });
                    html += '</ul></div>';
                }
                
                html += '<div class="migration-log">' + data.formatted_report + '</div>';
                $('#migration-results').html(html);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Section statut système
     */
    private static function render_status_section() {
        $status = self::get_migration_status();
        ?>
        <div class="migration-section">
            <h2>📊 Statut du système</h2>
            
            <div class="migration-stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo $status['total_games']; ?></div>
                    <div class="stat-label">Jeux total</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $status['migrated_games']; ?></div>
                    <div class="stat-label">Déjà migrés</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $status['pending_games']; ?></div>
                    <div class="stat-label">En attente</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $status['with_backups']; ?></div>
                    <div class="stat-label">Avec sauvegarde</div>
                </div>
            </div>
            
            <?php if ($status['pending_games'] === 0): ?>
                <div class="notice notice-success">
                    <p><strong>✅ Tous les jeux sont déjà migrés !</strong></p>
                </div>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><strong>ℹ️ <?php echo $status['pending_games']; ?> jeu(x) en attente de migration.</strong></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Section prévisualisation
     */
    private static function render_preview_section() {
        ?>
        <div class="migration-section">
            <h2>🔍 Prévisualisation</h2>
            <p>Analyser les données pour voir les changements qui seront effectués (aucune modification).</p>
            
            <button type="button" id="btn-preview" class="button button-secondary">
                📊 Analyser les données
            </button>
            
            <div id="preview-results"></div>
        </div>
        <?php
    }
    
    /**
     * Section migration
     */
    private static function render_migration_section() {
        $status = self::get_migration_status();
        ?>
        <div class="migration-section">
            <h2>🚀 Migration</h2>
            <p>Exécuter la migration réelle des données. <strong>Cette opération est irréversible sans restauration.</strong></p>
            
            <div class="notice notice-warning">
                <p><strong>Points de contrôle avant migration :</strong></p>
                <ul>
                    <li>✅ Sauvegarde complète du site effectuée</li>
                    <li>✅ Prévisualisation analysée et validée</li>
                    <li>✅ Trafic du site en mode maintenance (recommandé)</li>
                    <li>✅ Accès FTP/SSH disponible en cas de problème</li>
                </ul>
            </div>
            
            <button type="button" id="btn-migrate" class="button button-primary" 
                    <?php echo $status['pending_games'] === 0 ? 'disabled' : ''; ?>>
                🚀 Exécuter la migration
            </button>
            
            <div id="migration-results"></div>
        </div>
        <?php
    }
    
    /**
     * Section restauration
     */
    private static function render_restore_section() {
        $migrated_games = self::get_migrated_games_with_backup();
        
        if (empty($migrated_games)) {
            return;
        }
        ?>
        <div class="migration-section">
            <h2>🔄 Restauration</h2>
            <p>Restaurer des jeux individuels vers leur état d'avant migration.</p>
            
            <div class="notice notice-info">
                <p><strong>ℹ️ Jeux migrés avec sauvegarde disponible :</strong></p>
            </div>
            
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom du jeu</th>
                        <th>Date migration</th>
                        <th>Version</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($migrated_games as $game): ?>
                    <tr>
                        <td><?php echo $game['id']; ?></td>
                        <td><strong><?php echo esc_html($game['name']); ?></strong></td>
                        <td><?php echo $game['migrated_at']; ?></td>
                        <td><?php echo $game['version']; ?></td>
                        <td>
                            <button type="button" class="button button-secondary btn-restore" 
                                    data-term-id="<?php echo $game['id']; ?>" 
                                    data-game-name="<?php echo esc_attr($game['name']); ?>">
                                🔄 Restaurer
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * AJAX - Prévisualisation
     */
    public static function ajax_migration_preview() {
        check_ajax_referer('sisme_migration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permissions insuffisantes');
        }
        
        try {
            $report = Sisme_Game_Data_Migration::migrate_all_games(true); // Mode simulation
            $report['formatted_report'] = Sisme_Game_Data_Migration::format_migration_report($report);
            
            wp_send_json_success($report);
        } catch (Exception $e) {
            wp_send_json_error('Erreur lors de la prévisualisation: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX - Exécution migration
     */
    public static function ajax_migration_execute() {
        check_ajax_referer('sisme_migration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permissions insuffisantes');
        }
        
        try {
            // Vérification finale
            $preview = Sisme_Game_Data_Migration::migrate_all_games(true);
            if (!empty($preview['errors'])) {
                wp_send_json_error('Erreurs détectées lors de la vérification finale. Migration annulée.');
            }
            
            // Exécution réelle
            $report = Sisme_Game_Data_Migration::migrate_all_games(false);
            $report['formatted_report'] = Sisme_Game_Data_Migration::format_migration_report($report);
            
            wp_send_json_success($report);
        } catch (Exception $e) {
            wp_send_json_error('Erreur lors de la migration: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX - Restauration
     */
    public static function ajax_migration_restore() {
        check_ajax_referer('sisme_migration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Permissions insuffisantes');
        }
        
        $term_id = intval($_POST['term_id']);
        if (!$term_id) {
            wp_send_json_error('ID de jeu invalide');
        }
        
        try {
            $success = Sisme_Game_Data_Migration::restore_from_backup($term_id);
            
            if ($success) {
                wp_send_json_success('Jeu restauré avec succès');
            } else {
                wp_send_json_error('Aucune sauvegarde trouvée pour ce jeu');
            }
        } catch (Exception $e) {
            wp_send_json_error('Erreur lors de la restauration: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtenir le statut de migration
     */
    private static function get_migration_status() {
        global $wpdb;
        
        // Total des jeux
        $total_games = $wpdb->get_var("
            SELECT COUNT(DISTINCT tm.term_id) 
            FROM {$wpdb->termmeta} tm 
            WHERE tm.meta_key = 'game_description'
        ");
        
        // Jeux déjà migrés
        $migrated_games = $wpdb->get_var("
            SELECT COUNT(DISTINCT tm.term_id) 
            FROM {$wpdb->termmeta} tm 
            WHERE tm.meta_key = '_sisme_migrated_at'
        ");
        
        // Jeux avec sauvegarde
        $with_backups = $wpdb->get_var("
            SELECT COUNT(DISTINCT tm.term_id) 
            FROM {$wpdb->termmeta} tm 
            WHERE tm.meta_key = '_sisme_old_backup'
        ");
        
        return [
            'total_games' => intval($total_games),
            'migrated_games' => intval($migrated_games),
            'pending_games' => intval($total_games) - intval($migrated_games),
            'with_backups' => intval($with_backups)
        ];
    }
    
    /**
     * Obtenir les jeux migrés avec sauvegarde
     */
    private static function get_migrated_games_with_backup() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT t.term_id, t.name,
                   tm1.meta_value as migrated_at,
                   tm2.meta_value as version
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->termmeta} tm1 ON t.term_id = tm1.term_id AND tm1.meta_key = '_sisme_migrated_at'
            INNER JOIN {$wpdb->termmeta} tm2 ON t.term_id = tm2.term_id AND tm2.meta_key = '_sisme_migration_version'
            INNER JOIN {$wpdb->termmeta} tm3 ON t.term_id = tm3.term_id AND tm3.meta_key = '_sisme_old_backup'
            ORDER BY tm1.meta_value DESC
        ");
        
        $games = [];
        foreach ($results as $result) {
            $games[] = [
                'id' => $result->term_id,
                'name' => $result->name,
                'migrated_at' => $result->migrated_at,
                'version' => $result->version
            ];
        }
        
        return $games;
    }
}

// Initialiser seulement si on est en admin
if (is_admin()) {
    Sisme_Migration_Admin::init();
}