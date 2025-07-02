<?php
/**
 * File: /sisme-games-editor/includes/user/user-profile/user-profile-loader.php
 * Loader du module profils publics utilisateur
 * 
 * RESPONSABILITÉ:
 * - Charger les composants du profil public
 * - Initialiser API et shortcodes
 * - Gérer permissions et visibilité
 * - Réutiliser assets dashboard existants
 * - Dépendance user-dashboard pour renderer partagé
 * - Dépendance Sisme_User_Dashboard_Data_Manager pour données
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Profile_Loader {
    
    private static $instance = null;
    private static $modules_loaded = false;
    private $assets_loaded = false;
    
    /**
     * Singleton
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
     * Initialisation du module profil public
     */
    private function init() {
        $this->load_profile_modules();
        $this->register_hooks();
        $this->init_shortcodes();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Profile] Module profils publics utilisateur initialisé');
        }
    }
    
    /**
     * Charger tous les modules du profil public
     */
    private function load_profile_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $profile_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-profile/';
        
        $required_modules = [
            'user-profile-permissions.php',
            'user-profile-api.php'
        ];
        
        foreach ($required_modules as $module) {
            $file_path = $profile_dir . $module;
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Profile] Module chargé : {$module}");
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Profile] ERREUR - Module non trouvé : {$file_path}");
                }
            }
        }
        
        self::$modules_loaded = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Profile] Tous les modules profil public chargés');
        }
    }
    
    /**
     * Enregistrer les hooks WordPress
     */
    private function register_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }
    
    /**
     * Initialiser les shortcodes
     */
    private function init_shortcodes() {
        if (class_exists('Sisme_User_Profile_API')) {
            add_shortcode('sisme_user_profile', ['Sisme_User_Profile_API', 'render_profile']);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme User Profile] Shortcode [sisme_user_profile] enregistré');
            }
        }
    }
    
    /**
     * Charger les assets frontend (réutilise assets dashboard)
     */
    public function enqueue_frontend_assets() {
        if ($this->assets_loaded || is_admin()) {
            return;
        }
        
        if (!$this->should_load_assets()) {
            return;
        }
        
        $this->ensure_dashboard_renderer_loaded();
        $this->load_dashboard_assets();
        
        $this->assets_loaded = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Profile] Assets frontend chargés (réutilisation dashboard)');
        }
    }
    
    /**
     * Vérifier si les assets doivent être chargés
     */
    private function should_load_assets() {
        global $post;
        
        if (has_shortcode(get_post_field('post_content', $post), 'sisme_user_profile')) {
            return true;
        }
        
        if ($this->is_profile_page()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Détecter si on est sur une page de profil
     */
    private function is_profile_page() {
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $profile_pages = [
            '/profil/',
            '/profile/',
            '/user/',
            '/utilisateur/'
        ];
        
        foreach ($profile_pages as $page) {
            if (strpos($current_url, $page) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * S'assurer que le renderer dashboard est disponible
     */
    private function ensure_dashboard_renderer_loaded() {
        if (!class_exists('Sisme_User_Dashboard_Renderer')) {
            $renderer_file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-dashboard/user-dashboard-renderer.php';
            if (file_exists($renderer_file)) {
                require_once $renderer_file;
            }
        }
        
        if (!class_exists('Sisme_User_Dashboard_Data_Manager')) {
            $data_manager_file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-dashboard/user-dashboard-data-manager.php';
            if (file_exists($data_manager_file)) {
                require_once $data_manager_file;
            }
        }
    }
    
    /**
     * Charger les assets du dashboard (réutilisation)
     */
    private function load_dashboard_assets() {
        // Charger les tokens globaux en premier
        if (!wp_style_is('sisme-frontend-tokens', 'enqueued')) {
            wp_enqueue_style(
                'sisme-frontend-tokens',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }
        
        // CSS du dashboard
        if (!wp_style_is('sisme-user-dashboard', 'enqueued')) {
            wp_enqueue_style(
                'sisme-user-dashboard',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-dashboard/assets/user-dashboard.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
        }
        
        // JavaScript du dashboard
        if (!wp_script_is('sisme-user-dashboard', 'enqueued')) {
            wp_enqueue_script(
                'sisme-user-dashboard',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-dashboard/assets/user-dashboard.js',
                array('jquery'),
                SISME_GAMES_EDITOR_VERSION,
                true
            );
            
            // Configuration JavaScript adaptée pour profil public
            wp_localize_script('sisme-user-dashboard', 'sismeUserDashboard', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sisme_dashboard'),
                'currentUserId' => get_current_user_id(),
                'isPublicProfile' => true,
                'strings' => [
                    'loading' => 'Chargement...',
                    'error' => 'Une erreur est survenue'
                ],
                'config' => [
                    'autoRefresh' => false,
                    'animations' => true,
                    'notifications' => false
                ]
            ]);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Profile] Assets dashboard chargés pour profil public');
        }
    }
    
    /**
     * Vérifier si le module est prêt
     */
    public function is_module_ready() {
        $required_classes = [
            'Sisme_User_Dashboard_Renderer',
            'Sisme_User_Dashboard_Data_Manager',
            'Sisme_User_Profile_Permissions',
            'Sisme_User_Profile_API'
        ];
        
        foreach ($required_classes as $class) {
            if (!class_exists($class)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Forcer le chargement des assets
     */
    public function force_load_assets() {
        if (!$this->assets_loaded) {
            $this->enqueue_frontend_assets();
        }
    }
    
    /**
     * Vérifier si les assets sont chargés
     */
    public function are_assets_loaded() {
        return $this->assets_loaded;
    }
    
    /**
     * Obtenir la version du module
     */
    public function get_version() {
        return SISME_GAMES_EDITOR_VERSION;
    }
    
    /**
     * Obtenir les statistiques du module
     */
    public function get_module_stats() {
        return [
            'module_ready' => $this->is_module_ready(),
            'assets_loaded' => $this->assets_loaded,
            'required_classes_available' => [
                'renderer' => class_exists('Sisme_User_Dashboard_Renderer'),
                'data_manager' => class_exists('Sisme_User_Dashboard_Data_Manager'),
                'permissions' => class_exists('Sisme_User_Profile_Permissions'),
                'api' => class_exists('Sisme_User_Profile_API')
            ],
            'dashboard_integration' => class_exists('Sisme_User_Dashboard_Loader'),
            'version' => $this->get_version()
        ];
    }
    
    /**
     * Intégration avec le système de modules utilisateur
     */
    public function integrate_with_user_system() {
        $this->force_load_assets();
        
        if (!$this->is_module_ready()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme User Profile] ERREUR - Module non prêt pour intégration');
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Test de compatibilité avec les autres modules
     */
    public function test_compatibility() {
        $compatibility = [
            'dashboard_available' => class_exists('Sisme_User_Dashboard_Loader'),
            'utils_available' => class_exists('Sisme_Utils_Users'),
            'cards_available' => class_exists('Sisme_Cards_Functions'),
            'preferences_available' => class_exists('Sisme_User_Preferences_Data_Manager')
        ];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Profile] Test compatibilité : ' . json_encode($compatibility));
        }
        
        return $compatibility;
    }
}