<?php
/**
 * File: /sisme-games-editor/includes/user/user-notifications/user-notifications-loader.php
 * Loader du module notifications utilisateur
 * 
 * RESPONSABILITÉ:
 * - Charger tous les composants de notifications
 * - Initialiser les shortcodes et hooks AJAX
 * - Gérer les assets CSS/JS conditionnels
 * - Intégration avec le système user principal
 * 
 * DÉPENDANCES:
 * - Module user principal
 * - Design tokens frontend
 * - jQuery (WordPress core)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Notifications_Loader {
    
    private static $instance = null;
    private static $modules_loaded = false;
    private $assets_loaded = false;
    
    /**
     * Singleton pattern
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialisation du module notifications
     */
    private function init() {
        $this->load_notifications_modules();
        $this->register_hooks();
        $this->init_shortcodes();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Notifications] Module notifications utilisateur initialisé');
        }
    }
    
    /**
     * Charger tous les modules des notifications
     */
    private function load_notifications_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $notifications_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-notifications/';
        
        $required_modules = [
            'user-notifications-data-manager.php',
            'user-notifications-api.php',
            'user-notifications-ajax.php'
        ];
        
        foreach ($required_modules as $module) {
            $file_path = $notifications_dir . $module;
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Notifications] Module chargé: {$module}");
                }
            }
        }
        
        self::$modules_loaded = true;
    }
    
    /**
     * Enregistrer les hooks WordPress
     */
    private function register_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 15);
        
        if (class_exists('Sisme_User_Notifications_Ajax')) {
            Sisme_User_Notifications_Ajax::init();
        }
    }
    
    /**
     * Initialiser les shortcodes
     */
    private function init_shortcodes() {
        if (class_exists('Sisme_User_Notifications_API')) {
            add_shortcode('sisme_user_notifications_badge', [
                'Sisme_User_Notifications_API', 
                'render_badge_shortcode'
            ]);
            
            add_shortcode('sisme_user_notifications_panel', [
                'Sisme_User_Notifications_API', 
                'render_panel_shortcode'
            ]);
        }
    }
    
    /**
     * Charger les assets frontend conditionnellement
     */
    public function enqueue_frontend_assets() {
        if (is_admin() || $this->assets_loaded) {
            return;
        }
        
        global $post;
        $should_load = false;
        
        if (has_shortcode($post->post_content ?? '', 'sisme_user_notifications_badge') ||
            has_shortcode($post->post_content ?? '', 'sisme_user_notifications_panel') ||
            has_shortcode($post->post_content ?? '', 'sisme_user_dashboard')) {
            $should_load = true;
        }
        
        if (is_page(['sisme-user-tableau-de-bord'])) {
            $should_load = true;
        }
        
        /*if (!$should_load) {
            return;
        }*/
        
        if (!wp_style_is('sisme-frontend-tokens-global', 'enqueued')) {
            wp_enqueue_style(
                'sisme-frontend-tokens-global',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }
        
        wp_enqueue_style(
            'sisme-user-notifications',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-notifications/assets/user-notifications.css',
            array('sisme-frontend-tokens-global'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_script(
            'sisme-user-notifications',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-notifications/assets/user-notifications.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        wp_localize_script('sisme-user-notifications', 'sismeUserNotifications', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('sisme_user_notifications_nonce'),
            'user_id' => get_current_user_id(),
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);
        
        $this->assets_loaded = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Notifications] Assets CSS/JS chargés');
        }
    }
    
    /**
     * Forcer le chargement des assets
     */
    public function force_load_assets() {
        $this->enqueue_frontend_assets();
    }
    
    /**
     * Vérifier si les assets sont chargés
     */
    public function are_assets_loaded() {
        return $this->assets_loaded;
    }
}