<?php
/**
 * File: /sisme-games-editor/includes/user/user-dashboard/user-dashboard-api.php
 * API et shortcode pour le dashboard utilisateur avec système d'onglets
 * 
 * RESPONSABILITÉ:
 * - Shortcode [sisme_user_dashboard]
 * - Rendu HTML complet du dashboard
 * - Système de navigation par onglets dynamique
 * - Gestion des sections : overview, favorites, library, activity
 * - Vérifications de sécurité et permissions
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('sisme_user_dashboard', ['Sisme_User_Dashboard_API', 'render_dashboard']);

class Sisme_User_Dashboard_API {
    
    /**
     * Shortcode principal [sisme_user_dashboard]
     */
    public static function render_dashboard($atts = []) {
        if (!is_user_logged_in()) {
            return Sisme_Utils_Users::render_login_required();
        }
        $defaults = [
            'container_class' => 'sisme-user-dashboard',
            'user_id' => '',
            Sisme_Utils_Games::KEY_TITLE => 'Mon Dashboard'
        ];
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_dashboard');
        $user_id = !empty($atts['user_id']) ? intval($atts['user_id']) : get_current_user_id();
        if ($user_id !== get_current_user_id() && !current_user_can('manage_users')) {
            return Sisme_User_Dashboard_Renderer::render_access_denied();
        }
        if (class_exists('Sisme_User_Dashboard_Loader')) {
            $loader = Sisme_User_Dashboard_Loader::get_instance();
            if (method_exists($loader, 'force_load_assets')) {
                $loader->force_load_assets();
            }
        }
        if (!class_exists('Sisme_User_Dashboard_Data_Manager')) {
            return Sisme_User_Dashboard_Renderer::render_error('Module de données non disponible');
        }
        $dashboard_data = Sisme_User_Dashboard_Data_Manager::get_dashboard_data($user_id);
        if (!$dashboard_data) {
            return Sisme_User_Dashboard_Renderer::render_error('Impossible de charger les données utilisateur');
        }
        Sisme_User_Dashboard_Data_Manager::update_last_dashboard_visit($user_id);
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>" data-user-id="<?php echo esc_attr($user_id); ?>">
            <?php echo Sisme_User_Dashboard_Renderer::render_dashboard_header($dashboard_data['user_info'], $dashboard_data['gaming_stats']); ?>
            <?php echo Sisme_User_Dashboard_Renderer::render_dashboard_grid($dashboard_data); ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

