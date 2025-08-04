<?php
/**
 * File: /sisme-games-editor/includes/dev-editor-manager/dev-editor-manager-admin.php
 * Interface admin pour la gestion des développeurs/éditeurs
 * 
 * RESPONSABILITÉ:
 * - Page admin avec tableau des entités
 * - Formulaires d'ajout/modification
 * - Traitement des actions POST
 * - Messages de feedback utilisateur
 * 
 * DÉPENDANCES:
 * - Sisme_Dev_Editor_Manager (API CRUD)
 * - module-admin-page-wrapper.php
 * - WordPress admin styles
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_sisme_dev_editor_create', ['Sisme_Dev_Editor_Manager_Admin', 'ajax_create_entity']);
add_action('wp_ajax_sisme_dev_editor_update', ['Sisme_Dev_Editor_Manager_Admin', 'ajax_update_entity']);
add_action('wp_ajax_sisme_dev_editor_delete', ['Sisme_Dev_Editor_Manager_Admin', 'ajax_delete_entity']);

class Sisme_Dev_Editor_Manager_Admin {
    
    public static function render_page() {
        if (!class_exists('Sisme_Admin_Page_Wrapper')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
        }
        
        $success_message = '';
        $error_message = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = self::handle_post_actions();
            if (is_wp_error($result)) {
                $error_message = $result->get_error_message();
            } else {
                $success_message = $result;
            }
        }
        
        $page = new Sisme_Admin_Page_Wrapper(
            'Gestion Développeurs/Éditeurs',
            'Gérer les studios de développement et maisons d\'édition',
            'admin',
            admin_url('admin.php?page=sisme-games-game-data'),
            'Retour au Dashboard'
        );
        
        $entities = Sisme_Dev_Editor_Manager::get_all_entities();
        $stats = Sisme_Dev_Editor_Manager::get_entities_stats();
        
        $page->render_start();
        
        if (!empty($success_message)) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ ' . esc_html($success_message) . '</p></div>';
        }
        
        if (!empty($error_message)) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ ' . esc_html($error_message) . '</p></div>';
        }
        
        self::render_stats_section($stats);
        self::render_add_form();
        self::render_entities_table($entities);
        
        $page->render_end();
    }
    
    private static function handle_post_actions() {
        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'sisme_dev_editor_action')) {
            return new WP_Error('nonce_failed', 'Erreur de sécurité');
        }
        
        $action = sanitize_text_field($_POST['action'] ?? '');
        
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
        <div class="sisme-dev-editor-stats">
            <div class="sisme-stats-grid">
                <div class="sisme-stat-card">
                    <div class="sisme-stat-number"><?php echo intval($stats['total_entities']); ?></div>
                    <div class="sisme-stat-label">Total entités</div>
                </div>
                <div class="sisme-stat-card">
                    <div class="sisme-stat-number"><?php echo intval($stats['with_website']); ?></div>
                    <div class="sisme-stat-label">Avec site web</div>
                </div>
                <div class="sisme-stat-card">
                    <div class="sisme-stat-number"><?php echo intval($stats['total_games_linked']); ?></div>
                    <div class="sisme-stat-label">Jeux liés</div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private static function render_add_form() {
        ?>
        <div class="sisme-dev-editor-add-form">
            <h3>➕ Ajouter un développeur/éditeur</h3>
            <form method="post" class="sisme-entity-form">
                <?php wp_nonce_field('sisme_dev_editor_action'); ?>
                <input type="hidden" name="action" value="create">
                
                <div class="sisme-form-row">
                    <div class="sisme-form-field">
                        <label for="entity_name">Nom *</label>
                        <input type="text" 
                               id="entity_name" 
                               name="entity_name" 
                               class="sisme-form-input" 
                               placeholder="Nom du studio/éditeur"
                               required>
                    </div>
                    
                    <div class="sisme-form-field">
                        <label for="entity_website">Site web</label>
                        <input type="url" 
                               id="entity_website" 
                               name="entity_website" 
                               class="sisme-form-input" 
                               placeholder="https://www.example.com">
                    </div>
                    
                    <div class="sisme-form-field">
                        <button type="submit" class="sisme-btn-primary">Créer</button>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    private static function render_entities_table($entities) {
        ?>
        <div class="sisme-dev-editor-table-container">
            <div class="sisme-dev-editor-table-header">
                <h3>📋 Liste des développeurs/éditeurs</h3>
                <div class="sisme-search-bar">
                    <input type="text" 
                           id="sisme-entity-search" 
                           class="sisme-search-input" 
                           placeholder="Rechercher par nom...">
                </div>
            </div>
            
            <?php if (empty($entities)): ?>
                <div class="sisme-empty-state">
                    <p>Aucun développeur/éditeur enregistré.</p>
                </div>
            <?php else: ?>
                <table class="sisme-dev-editor-table">
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
                            <tr data-entity-id="<?php echo esc_attr($entity['id']); ?>">
                                <td class="sisme-entity-name">
                                    <strong><?php echo esc_html($entity['name']); ?></strong>
                                </td>
                                <td class="sisme-entity-website">
                                    <?php if (!empty($entity['website'])): ?>
                                        <a href="<?php echo esc_url($entity['website']); ?>" 
                                           target="_blank" 
                                           class="sisme-website-link">
                                            <?php echo esc_html($entity['website']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="sisme-no-data">Aucun</span>
                                    <?php endif; ?>
                                </td>
                                <td class="sisme-entity-games">
                                    <span class="sisme-games-count"><?php echo intval($entity['games_count']); ?></span>
                                </td>
                                <td class="sisme-entity-actions">
                                    <button type="button" 
                                            class="sisme-btn-edit" 
                                            data-entity-id="<?php echo esc_attr($entity['id']); ?>"
                                            data-entity-name="<?php echo esc_attr($entity['name']); ?>"
                                            data-entity-website="<?php echo esc_attr($entity['website']); ?>">
                                        ✏️ Modifier
                                    </button>
                                    
                                    <?php if ($entity['games_count'] === 0): ?>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('sisme_dev_editor_action'); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="entity_id" value="<?php echo esc_attr($entity['id']); ?>">
                                            <button type="submit" 
                                                    class="sisme-btn-delete"
                                                    onclick="return confirm('Supprimer définitivement &quot;<?php echo esc_js($entity['name']); ?>&quot; ?')">
                                                🗑️ Supprimer
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button type="button" 
                                                class="sisme-btn-disabled" 
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
        </div>
        
        <div id="sisme-edit-modal" class="sisme-modal" style="display: none;">
            <div class="sisme-modal-content">
                <div class="sisme-modal-header">
                    <h3>✏️ Modifier l'entité</h3>
                    <span class="sisme-modal-close">&times;</span>
                </div>
                
                <form method="post" class="sisme-entity-form">
                    <?php wp_nonce_field('sisme_dev_editor_action'); ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="entity_id" id="edit_entity_id">
                    
                    <div class="sisme-form-field">
                        <label for="edit_entity_name">Nom *</label>
                        <input type="text" 
                               id="edit_entity_name" 
                               name="entity_name" 
                               class="sisme-form-input" 
                               required>
                    </div>
                    
                    <div class="sisme-form-field">
                        <label for="edit_entity_website">Site web</label>
                        <input type="url" 
                               id="edit_entity_website" 
                               name="entity_website" 
                               class="sisme-form-input" 
                               placeholder="https://www.example.com">
                    </div>
                    
                    <div class="sisme-modal-actions">
                        <button type="button" class="sisme-btn-secondary sisme-modal-close">Annuler</button>
                        <button type="submit" class="sisme-btn-primary">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
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