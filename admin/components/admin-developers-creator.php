<?php
/**
 * File: /sisme-games-editor/admin/components/admin-developers-creator.php
 * Composant admin pour la gestion des développeurs/éditeurs
 * 
 * RESPONSABILITÉ:
 * - Composant intégré dans admin-developers.php
 * - Formulaires d'ajout/modification de studios/éditeurs
 * - Traitement des actions CRUD via AJAX
 * - Interface de gestion des entités développeur/éditeur
 * 
 * DÉPENDANCES:
 * - Sisme_Dev_Editor_Manager (API CRUD)
 * - admin-shared.css (styles cohérents)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Developers_Creator {
    
    /**
     * Initialise les hooks AJAX pour la gestion des entités
     */
    public static function init() {
        // Charger le module dev-editor-manager si nécessaire
        self::ensure_dev_editor_manager_loaded();
        
        // Hooks AJAX
        add_action('wp_ajax_sisme_dev_editor_create', [__CLASS__, 'ajax_create_entity']);
        add_action('wp_ajax_sisme_dev_editor_update', [__CLASS__, 'ajax_update_entity']);
        add_action('wp_ajax_sisme_dev_editor_delete', [__CLASS__, 'ajax_delete_entity']);
    }
    
    /**
     * S'assure que le module dev-editor-manager est chargé
     */
    private static function ensure_dev_editor_manager_loaded() {
        if (!class_exists('Sisme_Dev_Editor_Manager')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/dev-editor-manager/dev-editor-manager-loader.php';
            
            // Initialiser le loader pour charger les dépendances
            $loader = Sisme_Dev_Editor_Manager_Loader::get_instance();
        }
    }
    
    /**
     * Enqueue les assets nécessaires pour ce composant
     */
    public static function enqueue_assets($hook) {
        // Ne charger les assets que sur la page des développeurs
        if ($hook !== 'sisme-games-editor_page_sisme-games-developers') {
            return;
        }
        
        wp_enqueue_style(
            'sisme-dev-editor-manager',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/dev-editor-manager/assets/dev-editor-manager.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_script(
            'sisme-dev-editor-manager',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/dev-editor-manager/assets/dev-editor-manager.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        wp_localize_script('sisme-dev-editor-manager', 'sismeDevEditorAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_dev_editor_nonce')
        ]);
    }
    
    /**
     * Render le composant de gestion des studios/éditeurs
     */
    public static function render_component() {
        self::ensure_dev_editor_manager_loaded();
        
        $success_message = '';
        $error_message = '';
        
        // Traitement des actions POST si nécessaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dev_editor_action'])) {
            $result = self::handle_post_actions();
            if (is_wp_error($result)) {
                $error_message = $result->get_error_message();
            } else {
                $success_message = $result;
            }
        }
        
        $entities = class_exists('Sisme_Dev_Editor_Manager') ? Sisme_Dev_Editor_Manager::get_all_entities() : [];
        $stats = class_exists('Sisme_Dev_Editor_Manager') ? Sisme_Dev_Editor_Manager::get_entities_stats() : [
            'total_entities' => 0,
            'with_website' => 0,
            'total_games_linked' => 0
        ];
        
        // Afficher les messages
        if (!empty($success_message)) {
            echo '<div class="sisme-admin-alert sisme-admin-alert-success"><p>✅ ' . esc_html($success_message) . '</p></div>';
        }
        
        if (!empty($error_message)) {
            echo '<div class="sisme-admin-alert sisme-admin-alert-danger"><p>❌ ' . esc_html($error_message) . '</p></div>';
        }
        
        // Render les sections du composant
        self::render_stats_section($stats);
        self::render_add_form();
        self::render_entities_table($entities);
    }
    
    private static function handle_post_actions() {
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'sisme_dev_editor_action')) {
            return new WP_Error('nonce_failed', 'Erreur de sécurité');
        }
        
        $action = sanitize_text_field($_POST['dev_editor_action'] ?? '');
        
        switch ($action) {
            case 'create':
                return self::handle_create_entity();
            case 'update':
                return self::handle_update_entity();
            case 'delete':
                return self::handle_delete_entity();
            default:
                return new WP_Error('invalid_action', 'Action non reconnue');
        }
    }
    
    private static function handle_create_entity() {
        $name = sanitize_text_field($_POST['entity_name'] ?? '');
        $website = esc_url_raw($_POST['entity_website'] ?? '');
        
        if (empty($name)) {
            return new WP_Error('empty_name', 'Le nom est requis');
        }
        
        $result = Sisme_Dev_Editor_Manager::create_entity($name, $website);
        if (is_wp_error($result)) {
            return $result;
        }
        
        return "Entité \"{$name}\" créée avec succès";
    }
    
    private static function handle_update_entity() {
        $entity_id = intval($_POST['entity_id'] ?? 0);
        $name = sanitize_text_field($_POST['entity_name'] ?? '');
        $website = esc_url_raw($_POST['entity_website'] ?? '');
        
        if (!$entity_id || empty($name)) {
            return new WP_Error('invalid_params', 'ID et nom requis');
        }
        
        $result = Sisme_Dev_Editor_Manager::update_entity($entity_id, $name, $website);
        if (is_wp_error($result)) {
            return $result;
        }
        
        return "Entité \"{$name}\" mise à jour avec succès";
    }
    
    private static function handle_delete_entity() {
        $entity_id = intval($_POST['entity_id'] ?? 0);
        
        if (!$entity_id) {
            return new WP_Error('invalid_id', 'ID requis');
        }
        
        $entity = Sisme_Dev_Editor_Manager::get_entity_by_id($entity_id);
        if (!$entity) {
            return new WP_Error('not_found', 'Entité introuvable');
        }
        
        $result = Sisme_Dev_Editor_Manager::delete_entity($entity_id);
        if (is_wp_error($result)) {
            return $result;
        }
        
        return "Entité \"{$entity['name']}\" supprimée avec succès";
    }
    
    private static function render_stats_section($stats) {
        ?>
        <div class="sisme-admin-card sisme-admin-flex-col sisme-admin-gap-4 sisme-mb-6">
            <h3 class="sisme-admin-card-header">📊 Statistiques des Studios</h3>
            <div class="sisme-admin-stats sisme-mb-6">
                <div class="sisme-admin-stat-card sisme-admin-stat-primary">
                    <div class="sisme-admin-stat-number"><?php echo intval($stats['total_entities']); ?></div>
                    <div class="sisme-admin-stat-label">🏢 Total Studios</div>
                </div>
                <div class="sisme-admin-stat-card sisme-admin-stat-warning">
                    <div class="sisme-admin-stat-number"><?php echo intval($stats['with_website']); ?></div>
                    <div class="sisme-admin-stat-label">🌐 Avec site web</div>
                </div>
                <div class="sisme-admin-stat-card sisme-admin-stat-success">
                    <div class="sisme-admin-stat-number"><?php echo intval($stats['total_games_linked']); ?></div>
                    <div class="sisme-admin-stat-label">🎮 Jeux liés</div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private static function render_add_form() {
        ?>
        <div class="sisme-admin-card sisme-mb-6">
            <h3 class="sisme-admin-section-title">➕ Ajouter un développeur/éditeur</h3>
            <form method="post" class="sisme-admin-flex sisme-admin-gap-4">
                <?php wp_nonce_field('sisme_dev_editor_action'); ?>
                <input type="hidden" name="dev_editor_action" value="create">
                
                <div class="sisme-admin-flex-1">
                    <label for="entity_name" class="sisme-admin-label">Nom *</label>
                    <input type="text" 
                           id="entity_name" 
                           name="entity_name" 
                           class="sisme-admin-p-sm sisme-admin-border sisme-admin-rounded" 
                           placeholder="Nom du studio/éditeur"
                           style="width: 100%;"
                           required>
                </div>
                
                <div class="sisme-admin-flex-1">
                    <label for="entity_website" class="sisme-admin-label">Site web</label>
                    <input type="url" 
                           id="entity_website" 
                           name="entity_website" 
                           class="sisme-admin-p-sm sisme-admin-border sisme-admin-rounded" 
                           placeholder="https://www.example.com"
                           style="width: 100%;">
                </div>
                
                <div class="sisme-admin-flex sisme-align-center" style="margin-top: 24px;">
                    <button type="submit" class="sisme-admin-btn sisme-admin-btn-primary">✅ Créer</button>
                </div>
            </form>
        </div>
        <?php
    }
    
    private static function render_entities_table($entities) {
        ?>
        <div class="sisme-admin-modal-container">
            <div class="sisme-admin-flex sisme-admin-flex-between sisme-mb-4">
                <h3 class="sisme-admin-section-title">🏢 Liste des Studios</h3>
                <div class="sisme-admin-flex sisme-align-center">
                    <input type="text" 
                           id="sisme-entity-search" 
                           class="sisme-admin-p-sm sisme-admin-border sisme-admin-rounded" 
                           placeholder="Rechercher par nom..."
                           style="margin-right: var(--sisme-admin-spacing-sm);">
                    <button type="button" 
                            id="clear-entity-search" 
                            class="sisme-admin-btn sisme-admin-btn-secondary sisme-admin-btn-sm" 
                            onclick="sismesClearEntitySearch()">
                        🗑️ Effacer
                    </button>
                </div>
            </div>
            
            <?php if (empty($entities)): ?>
                <div class="sisme-admin-empty-state">
                    <p>Aucun Studio enregistré.</p>
                </div>
            <?php else: ?>
                <table class="sisme-admin-table wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Site web</th>
                            <th>Jeux liés</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entities as $entity): ?>
                            <tr data-entity-id="<?php echo esc_attr($entity['id']); ?>"
                                data-entity-name="<?php echo esc_attr(strtolower($entity['name'])); ?>">
                                <td class="sisme-entity-name">
                                    <strong><?php echo esc_html($entity['name']); ?></strong>
                                </td>
                                <td class="sisme-entity-website">
                                    <?php if (!empty($entity['website'])): ?>
                                        <a href="<?php echo esc_url($entity['website']); ?>" 
                                           target="_blank" 
                                           class="sisme-admin-link">
                                            <?php echo esc_html($entity['website']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="sisme-admin-text-muted">Aucun</span>
                                    <?php endif; ?>
                                </td>
                                <td class="sisme-entity-games">
                                    <span class="sisme-admin-badge sisme-admin-badge-info"><?php echo intval($entity['games_count']); ?></span>
                                </td>
                                <td class="sisme-entity-actions">
                                    <button type="button" 
                                            class="sisme-admin-btn sisme-admin-btn-primary sisme-admin-btn-sm sisme-btn-edit" 
                                            data-entity-id="<?php echo esc_attr($entity['id']); ?>"
                                            data-entity-name="<?php echo esc_attr($entity['name']); ?>"
                                            data-entity-website="<?php echo esc_attr($entity['website']); ?>">
                                        ✏️ Modifier
                                    </button>
                                    
                                    <?php if ($entity['games_count'] === 0): ?>
                                        <form method="post" class="sisme-inline-form">
                                            <?php wp_nonce_field('sisme_dev_editor_action'); ?>
                                            <input type="hidden" name="dev_editor_action" value="delete">
                                            <input type="hidden" name="entity_id" value="<?php echo esc_attr($entity['id']); ?>">
                                            <button type="submit" 
                                                    class="sisme-admin-btn sisme-admin-btn-danger sisme-admin-btn-sm"
                                                    onclick="return confirm('Supprimer définitivement &quot;<?php echo esc_js($entity['name']); ?>&quot; ?')">
                                                🗑️ Supprimer
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="sisme-admin-btn sisme-admin-btn-secondary sisme-admin-btn-sm sisme-admin-cursor-not-allowed" 
                                                disabled
                                                title="Impossible de supprimer : <?php echo intval($entity['games_count']); ?> jeu(x) lié(s)">
                                            🔒 Protégé
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Modal de modification -->
            <div id="sisme-edit-modal" class="sisme-admin-modal" style="display: none;">
                <div class="sisme-admin-modal-content">
                    <div class="sisme-admin-modal-header sisme-admin-flex sisme-admin-flex-between sisme-align-center">
                        <h3>✏️ Modifier l'entité</h3>
                        <span class="sisme-admin-modal-close" style="cursor: pointer; font-size: 20px;">&times;</span>
                    </div>
                    
                    <form method="post" class="sisme-admin-flex-col sisme-admin-gap-4">
                        <?php wp_nonce_field('sisme_dev_editor_action'); ?>
                        <input type="hidden" name="dev_editor_action" value="update">
                        <input type="hidden" name="entity_id" id="edit_entity_id">
                        
                        <div>
                            <label for="edit_entity_name" class="sisme-admin-label">Nom *</label>
                            <input type="text" 
                                id="edit_entity_name" 
                                name="entity_name" 
                                class="sisme-admin-p-sm sisme-admin-border sisme-admin-rounded" 
                                style="width: 100%;"
                                required>
                        </div>
                        
                        <div>
                            <label for="edit_entity_website" class="sisme-admin-label">Site web</label>
                            <input type="url" 
                                id="edit_entity_website" 
                                name="entity_website" 
                                class="sisme-admin-p-sm sisme-admin-border sisme-admin-rounded" 
                                placeholder="https://www.example.com"
                                style="width: 100%;">
                        </div>
                        
                        <div class="sisme-admin-flex sisme-admin-gap-2" style="justify-content: flex-end;">
                            <button type="button" class="sisme-admin-btn sisme-admin-btn-secondary sisme-admin-modal-close">Annuler</button>
                            <button type="submit" class="sisme-admin-btn sisme-admin-btn-primary">Mettre à jour</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Gestion du modal de modification
            $('.sisme-btn-edit').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const entityId = $(this).data('entity-id');
                const entityName = $(this).data('entity-name');
                const entityWebsite = $(this).data('entity-website');
                
                $('#edit_entity_id').val(entityId);
                $('#edit_entity_name').val(entityName);
                $('#edit_entity_website').val(entityWebsite);
                
                // Faire défiler le conteneur modal vers le haut
                const $modalContainer = $('.sisme-admin-modal-container');
                if ($modalContainer.length) {
                    $modalContainer.animate({ scrollTop: 0 }, 300);
                }
                
                // Afficher le modal immédiatement
                const $modal = $('#sisme-edit-modal');
                $modal.addClass('sisme-admin-modal-visible').show();
            });
            
            // Fermer le modal
            $('.sisme-admin-modal-close').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#sisme-edit-modal').removeClass('sisme-admin-modal-visible').hide();
            });
            
            // Fermer le modal en cliquant à l'extérieur
            $('#sisme-edit-modal').on('click', function(e) {
                if (e.target === this) {
                    $(this).removeClass('sisme-admin-modal-visible').hide();
                }
            });
            
            // Recherche d'entités (studios)
            $('#sisme-entity-search').on('input', function() {
                sismeFilterEntities(this.value);
            });
            
            window.sismeFilterEntities = function(searchTerm) {
                const entityRows = $('table tbody tr[data-entity-name]');
                const lowerSearchTerm = searchTerm.toLowerCase().trim();
                
                if (lowerSearchTerm === '') {
                    // Afficher tous les studios si la recherche est vide
                    entityRows.show();
                    return;
                }
                
                entityRows.each(function() {
                    const name = $(this).data('entity-name') || '';
                    const isVisible = name.includes(lowerSearchTerm);
                    $(this).toggle(isVisible);
                });
            };
            
            window.sismesClearEntitySearch = function() {
                $('#sisme-entity-search').val('');
                sismeFilterEntities('');
            };
        });
        </script>
        <?php
    }
    
    public static function ajax_create_entity() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_dev_editor_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        $website = esc_url_raw($_POST['website'] ?? '');
        
        if (empty($name)) {
            wp_send_json_error(['message' => 'Le nom est requis']);
        }
        
        $result = Sisme_Dev_Editor_Manager::create_entity($name, $website);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => "Entité \"{$name}\" créée avec succès",
            'entity' => $result
        ]);
    }
    
    public static function ajax_update_entity() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_dev_editor_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        $entity_id = intval($_POST['entity_id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $website = esc_url_raw($_POST['website'] ?? '');
        
        if (!$entity_id || empty($name)) {
            wp_send_json_error(['message' => 'ID et nom requis']);
        }
        
        $result = Sisme_Dev_Editor_Manager::update_entity($entity_id, $name, $website);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => "Entité \"{$name}\" mise à jour avec succès",
            'entity' => $result
        ]);
    }
    
    public static function ajax_delete_entity() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_dev_editor_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        $entity_id = intval($_POST['entity_id'] ?? 0);
        
        if (!$entity_id) {
            wp_send_json_error(['message' => 'ID requis']);
        }
        
        $entity = Sisme_Dev_Editor_Manager::get_entity_by_id($entity_id);
        if (!$entity) {
            wp_send_json_error(['message' => 'Entité introuvable']);
        }
        
        $result = Sisme_Dev_Editor_Manager::delete_entity($entity_id);
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => "Entité \"{$entity['name']}\" supprimée avec succès"
        ]);
    }
}