<?php
/**
 * File: /sisme-games-editor/includes/user/user-notifications/user-notifications-admin.php
 * Page admin simple pour la gestion des notifications utilisateur
 * 
 * RESPONSABILITÉ:
 * - Voir les notifications par utilisateur
 * - Vider les notifications par utilisateur
 * - Vider toutes les notifications
 * 
 * DÉPENDANCES:
 * - Sisme_User_Notifications_Data_Manager
 * - Sisme_Admin_Page_Wrapper
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Notifications {

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_hidden_page'));
    }

    /**
     * Ajouter comme page cachée
     */
    public static function add_hidden_page() {
        add_submenu_page(
            null,
            'Notifications Utilisateur',
            'Notifications Utilisateur',
            'manage_options',
            'sisme-games-notifications',
            array(__CLASS__, 'render')
        );
    }
    
    public static function render() {
        if (!class_exists('Sisme_Admin_Page_Wrapper')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
        }
        
        $success_message = '';
        $error_message = '';
        
        // Traitement des actions POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'sisme_notifications_admin')) {
            $result = self::handle_post_actions();
            if (is_wp_error($result)) {
                $error_message = $result->get_error_message();
            } else {
                $success_message = $result;
            }
        }
        
        $page = new Sisme_Admin_Page_Wrapper(
            'Gestion des Notifications',
            'Voir et gérer les notifications utilisateurs',
            'email',
            admin_url('admin.php?page=sisme-games-game-data'),
            'Retour au Dashboard'
        );
        
        $page->render_start();
        
        // Messages de feedback
        if (!empty($success_message)) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ ' . esc_html($success_message) . '</p></div>';
        }
        
        if (!empty($error_message)) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ ' . esc_html($error_message) . '</p></div>';
        }
        
        // Statistiques
        self::render_stats_section();
        
        // Formulaire de recherche utilisateur
        self::render_user_search_form();
        
        // Actions globales
        self::render_global_actions();
        
        // Affichage des notifications si utilisateur sélectionné
        if (isset($_GET['user_id'])) {
            $user_id = intval($_GET['user_id']);
            self::render_user_notifications($user_id);
        }
        
        $page->render_end();
    }
    
    private static function handle_post_actions() {
        $action = sanitize_text_field($_POST['action'] ?? '');
        
        switch ($action) {
            case 'clear_user_notifications':
                $user_id = intval($_POST['user_id'] ?? 0);
                if ($user_id > 0) {
                    $success = delete_user_meta($user_id, Sisme_User_Notifications_Data_Manager::META_KEY);
                    return $success ? "Notifications supprimées pour l'utilisateur ID $user_id" : new WP_Error('delete_failed', 'Erreur lors de la suppression');
                }
                return new WP_Error('invalid_user', 'ID utilisateur invalide');
                
            case 'clear_all_notifications':
                global $wpdb;
                $deleted = $wpdb->delete(
                    $wpdb->usermeta,
                    ['meta_key' => Sisme_User_Notifications_Data_Manager::META_KEY]
                );
                return $deleted !== false ? "Toutes les notifications supprimées ($deleted entrées)" : new WP_Error('delete_all_failed', 'Erreur lors de la suppression globale');
                
            default:
                return new WP_Error('invalid_action', 'Action non reconnue');
        }
    }
    
    private static function render_stats_section() {
        global $wpdb;
        
        // Statistiques générales
        $total_users_with_notifs = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = %s
        ", Sisme_User_Notifications_Data_Manager::META_KEY));
        
        $total_entries = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = %s
        ", Sisme_User_Notifications_Data_Manager::META_KEY));
        
        ?>
        <div class="sisme-admin-stats">
            <h3>📊 Statistiques</h3>
            <div class="sisme-stats-grid">
                <div class="sisme-stat-item">
                    <strong><?php echo $total_users_with_notifs; ?></strong>
                    <span>Utilisateurs avec notifications</span>
                </div>
                <div class="sisme-stat-item">
                    <strong><?php echo $total_entries; ?></strong>
                    <span>Entrées en base</span>
                </div>
            </div>
        </div>
        <?php
    }
    
    private static function render_user_search_form() {
        global $wpdb;
        
        // Récupérer les utilisateurs avec notifications
        $users_with_notifications = $wpdb->get_results($wpdb->prepare("
            SELECT u.ID, u.user_login, u.display_name, u.user_email
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
            WHERE um.meta_key = %s
            ORDER BY u.display_name
        ", Sisme_User_Notifications_Data_Manager::META_KEY));
        
        $current_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : '';
        ?>
        <div class="sisme-admin-section">
            <h3>👤 Sélectionner un utilisateur</h3>
            <form method="get" action="">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
                <table class="form-table">
                    <tr>

                        <td>
                            <select name="user_id" id="user_id" class="regular-text">
                                <option value="">-- Choisir un utilisateur --</option>
                                <?php foreach ($users_with_notifications as $user) : ?>
                                    <option value="<?php echo $user->ID; ?>" <?php selected($current_user_id, $user->ID); ?>>
                                        <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_login); ?>) - ID: <?php echo $user->ID; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="submit" value="Voir les notifications" class="button button-primary">
                        </td>
                    </tr>
                </table>
            </form>
            
            <?php if (empty($users_with_notifications)) : ?>
                <p><em>ℹ️ Aucun utilisateur n'a de notifications actuellement.</em></p>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private static function render_global_actions() {
        ?>
        <div class="sisme-admin-section">
            <h3>⚡ Actions globales</h3>
            
            <form method="post" action="" style="margin-bottom: 20px;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer TOUTES les notifications ?');">
                <?php wp_nonce_field('sisme_notifications_admin'); ?>
                <input type="hidden" name="action" value="clear_all_notifications">
                <input type="submit" value="🗑️ Vider toutes les notifications" class="button button-secondary" style="background: #dc3545; color: white; border-color: #dc3545;">
            </form>
            
            <p><em>⚠️ Cette action supprimera définitivement toutes les notifications de tous les utilisateurs.</em></p>
        </div>
        <?php
    }
    
    private static function render_user_notifications($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            echo '<div class="notice notice-error"><p>❌ Utilisateur introuvable (ID: ' . $user_id . ')</p></div>';
            return;
        }
        
        $notifications = Sisme_User_Notifications_Data_Manager::get_user_notifications($user_id, false);
        $enriched_notifications = Sisme_User_Notifications_Data_Manager::get_enriched_notifications($user_id);
        
        ?>
        <div class="sisme-admin-section">
            <h3>👤 Notifications de <?php echo esc_html($user->display_name); ?> (ID: <?php echo $user_id; ?>)</h3>
            
            <?php if (!empty($notifications)) : ?>
                
                <!-- Bouton vider pour cet utilisateur -->
                <form method="post" action="" style="margin-bottom: 20px;" onsubmit="return confirm('Vider les notifications de cet utilisateur ?');">
                    <?php wp_nonce_field('sisme_notifications_admin'); ?>
                    <input type="hidden" name="action" value="clear_user_notifications">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="submit" value="🗑️ Vider les notifications de cet utilisateur" class="button button-secondary">
                </form>
                
                <!-- Liste des notifications -->
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Index</th>
                            <th>Jeu</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Données brutes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notifications as $index => $notification) : ?>
                            <?php
                            $enriched = isset($enriched_notifications[$index]) ? $enriched_notifications[$index] : null;
                            $game_name = $enriched ? $enriched['game_name'] : 'Jeu ID: ' . $notification['game_id'];
                            $date = isset($notification['timestamp']) ? date('d/m/Y H:i', $notification['timestamp']) : 'N/A';
                            ?>
                            <tr>
                                <td><strong><?php echo $index; ?></strong></td>
                                <td>
                                    <?php echo esc_html($game_name); ?>
                                    <?php if ($enriched && isset($enriched['game_url'])) : ?>
                                        <br><small><a href="<?php echo esc_url($enriched['game_url']); ?>" target="_blank">Voir le jeu</a></small>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo esc_html($notification['type'] ?? 'N/A'); ?></code></td>
                                <td><?php echo esc_html($date); ?></td>
                                <td><small><code><?php echo esc_html(json_encode($notification, JSON_PRETTY_PRINT)); ?></code></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <p><strong>Total :</strong> <?php echo count($notifications); ?> notification(s)</p>
                
            <?php else : ?>
                <p>🔔 Aucune notification pour cet utilisateur.</p>
            <?php endif; ?>
        </div>
        <?php
    }
}