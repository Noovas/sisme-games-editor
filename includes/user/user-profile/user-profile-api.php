<?php
/**
 * File: /sisme-games-editor/includes/user/user-profile/user-profile-api.php
 * API et shortcode pour les profils publics utilisateur
 * 
 * RESPONSABILITÉ:
 * - Shortcode [sisme_user_profile]
 * - Gestion paramètres URL et attributs
 * - Vérification permissions et validation ID
 * - Rendu via renderer partagé dashboard
 * - Gestion erreurs et accès refusé
 * - Dépendance Sisme_User_Profile_Permissions
 * - Dépendance Sisme_User_Dashboard_Renderer
 * - Dépendance Sisme_User_Dashboard_Data_Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Profile_API {
    
    /**
     * Shortcode principal [sisme_user_profile]
     * @param array $atts Attributs du shortcode
     * @return string HTML du profil
     */
    public static function render_profile($atts = []) {
        $defaults = [
            'id' => '',
            'container_class' => 'sisme-user-dashboard'
        ];
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_profile');
        
        $profile_user_id = self::get_target_user_id($atts['id']);
        if (!$profile_user_id) {
            return self::render_no_user_message();
        }
        
        $viewer_id = get_current_user_id();
        
        if (!Sisme_User_Profile_Permissions::can_view_profile($viewer_id, $profile_user_id)) {
            return self::render_access_denied($viewer_id, $profile_user_id);
        }
        
        return self::render_user_profile($viewer_id, $profile_user_id, $atts);
    }
    
    /**
     * Déterminer l'ID utilisateur à afficher
     * @param string $id_attr Attribut id du shortcode
     * @return int|false ID utilisateur ou false
     */
    private static function get_target_user_id($id_attr) {
        $user = false;
        if (!empty($id_attr)) {
            if (is_numeric($id_attr)) {
                $user = get_userdata(intval($id_attr));
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Profile] Recherche par ID shortcode: {$id_attr}");
                }
            } else {
                if (class_exists('Sisme_User_Auth_Security')) {
                    $user = Sisme_Utils_Users::get_user_by_slug($id_attr);
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("[Sisme User Profile] Recherche par slug shortcode: {$id_attr}");
                    }
                }
            }
        }
        elseif (!empty($_GET['user'])) {
            $user_param = sanitize_text_field($_GET['user']);
            if (is_numeric($user_param)) {
                $user = get_userdata(intval($user_param));
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Profile] Recherche par ID URL: {$user_param}");
                }
            } else {
                if (class_exists('Sisme_User_Auth_Security')) {
                    $user = Sisme_Utils_Users::get_user_by_slug($user_param);
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("[Sisme User Profile] Recherche par slug URL: {$user_param}");
                    }
                }
            }
        }
        else {
            $user_id = get_current_user_id();
            if ($user_id) {
                $user = get_userdata($user_id);
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Profile] Fallback utilisateur connecté: {$user_id}");
                }
            }
        }
        if (!$user || !($user instanceof WP_User)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Profile] Aucun utilisateur valide trouvé");
            }
            return false;
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Profile] ✅ Utilisateur trouvé - ID: {$user->ID}, Slug: {$user->user_nicename}, Display: {$user->display_name}");
        }
        return $user->ID;
    }
    
    /**
     * Rendu du profil utilisateur
     * @param int $viewer_id ID utilisateur qui consulte
     * @param int $profile_user_id ID propriétaire du profil
     * @param array $atts Attributs du shortcode
     * @return string HTML du profil
     */
    private static function render_user_profile($viewer_id, $profile_user_id, $atts) {
        if (!class_exists('Sisme_User_Dashboard_Data_Manager')) {
            return '<div class="sisme-error">Erreur : Gestionnaire de données non disponible.</div>';
        }
        
        if (!class_exists('Sisme_User_Dashboard_Renderer')) {
            return '<div class="sisme-error">Erreur : Renderer non disponible.</div>';
        }
        
        // Forcer le chargement des assets dashboard
        if (class_exists('Sisme_User_Profile_Loader')) {
            $profile_loader = Sisme_User_Profile_Loader::get_instance();
            if (method_exists($profile_loader, 'force_load_assets')) {
                $profile_loader->force_load_assets();
            }
        }
        
        $dashboard_data = Sisme_User_Dashboard_Data_Manager::get_dashboard_data($profile_user_id);
        
        $filtered_data = Sisme_User_Profile_Permissions::filter_profile_data(
            $dashboard_data, 
            $viewer_id, 
            $profile_user_id
        );
        
        if (!$filtered_data) {
            return self::render_access_denied($viewer_id, $profile_user_id);
        }
        
        $context = Sisme_User_Profile_Permissions::create_render_context($viewer_id, $profile_user_id);
        
        $container_class = esc_attr($atts['container_class']);
        
        ob_start();
        ?>
        <div class="<?php echo $container_class; ?>">
            <?php
            echo Sisme_User_Dashboard_Renderer::render_dashboard_header(
                $filtered_data['user_info'], 
                $filtered_data['gaming_stats'], 
                $context
            );
            
            echo Sisme_User_Dashboard_Renderer::render_dashboard_grid(
                $filtered_data, 
                $context
            );
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Message d'erreur : utilisateur non trouvé
     * @return string HTML du message
     */
    private static function render_no_user_message() {
        ob_start();
        ?>
        <div class="sisme-user-profile-error">
            <div class="sisme-error-icon">👤</div>
            <h3>Utilisateur non trouvé</h3>
            <p>L'utilisateur que vous cherchez n'existe pas ou l'ID fourni n'est pas valide.</p>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Message d'erreur : accès refusé
     * @param int $viewer_id ID utilisateur qui consulte
     * @param int $profile_user_id ID propriétaire du profil
     * @return string HTML du message
     */
    private static function render_access_denied($viewer_id, $profile_user_id) {
        $message = Sisme_User_Profile_Permissions::get_access_denied_message($viewer_id, $profile_user_id);
        
        ob_start();
        ?>
        <div class="sisme-user-profile-error">
            <div class="sisme-error-icon">🔒</div>
            <h3>Accès non autorisé</h3>
            <p><?php echo esc_html($message); ?></p>
            <?php if (!$viewer_id): ?>
                <a href="<?php echo esc_url(home_url('/sisme-user-login/')); ?>" class="sisme-button sisme-button-primary">
                    Se connecter
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}