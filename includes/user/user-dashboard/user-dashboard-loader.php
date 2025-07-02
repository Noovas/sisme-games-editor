<?php
/**
 * File: /sisme-games-editor/includes/user/user-dashboard/user-dashboard-loader.php
 * Loader du module dashboard utilisateur
 * 
 * RESPONSABILITÉ:
 * - Charger tous les composants du dashboard
 * - Initialiser le shortcode dashboard
 * - Gérer les assets CSS/JS
 * - Hooks d'intégration avec user-auth
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Dashboard_Loader {
    
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
     * Initialisation du module dashboard
     */
    private function init() {
        $this->load_dashboard_modules();
        $this->register_hooks();
        $this->init_shortcodes();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Dashboard] Module dashboard utilisateur initialisé');
        }
    }
    
    /**
     * Charger tous les modules du dashboard
     */
    private function load_dashboard_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $dashboard_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-dashboard/';
        
        // Modules requis dans l'ordre (version simplifiée)
        $required_modules = [
            'user-dashboard-renderer.php',
            'user-dashboard-data-manager.php',
            'user-dashboard-api.php'
        ];
        
        foreach ($required_modules as $module) {
            $file_path = $dashboard_dir . $module;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Dashboard] Module chargé : $module");
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Dashboard] ERREUR - Module manquant : $file_path");
                }
            }
        }
        
        self::$modules_loaded = true;
    }
    
    /**
     * Enregistrer les hooks WordPress
     */
    private function register_hooks() {
        // Assets frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Hook d'initialisation utilisateur (synchronisation avec user-auth)
        add_action('wp_login', [$this, 'on_user_login'], 20, 2);
        
        // Hook de mise à jour des données utilisateur
        add_action('profile_update', [$this, 'on_profile_update'], 10, 1);
    }
    
    /**
     * Initialiser les shortcodes
     */
    private function init_shortcodes() {
        if (class_exists('Sisme_User_Dashboard_API')) {
            add_shortcode('sisme_user_dashboard', ['Sisme_User_Dashboard_API', 'render_dashboard']);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme User Dashboard] Shortcode enregistré : sisme_user_dashboard');
            }
        }
    }
    
    /**
     * Charger les assets frontend
     */
    public function enqueue_frontend_assets() {
        if ($this->assets_loaded || is_admin()) {
            return;
        }
        
        // Vérifier si on doit charger les assets
        if (!$this->should_load_assets()) {
            return;
        }
        
        // Design tokens (base) - Réutilise les tokens frontend existants
        wp_enqueue_style(
            'sisme-frontend-tokens',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // CSS du dashboard
        wp_enqueue_style(
            'sisme-user-dashboard',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-dashboard/assets/user-dashboard.css',
            array('sisme-frontend-tokens'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // JavaScript du dashboard
        wp_enqueue_script(
            'sisme-user-dashboard',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-dashboard/assets/user-dashboard.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        // Configuration JavaScript
        wp_localize_script('sisme-user-dashboard', 'sismeUserDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_dashboard'),
            'currentUserId' => get_current_user_id(),
            'strings' => [
                'loading' => 'Chargement...',
                'error' => 'Une erreur est survenue',
                'success' => 'Action réussie',
                'confirm' => 'Êtes-vous sûr ?'
            ],
            'config' => [
                'autoRefresh' => false,  // Pas d'auto-refresh dans la version simple
                'animations' => true,
                'notifications' => true
            ],
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);
        
        $this->assets_loaded = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Dashboard] Assets frontend chargés');
        }
    }
    
    /**
     * Vérifier si on doit charger les assets dashboard
     */
    private function should_load_assets() {
        // Charger sur les pages contenant le shortcode dashboard
        global $post;
        
        if (is_object($post) && has_shortcode($post->post_content, 'sisme_user_dashboard')) {
            return true;
        }
        
        // Charger sur les pages dédiées au dashboard (URLs connues)
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $dashboard_pages = [
            '/sisme-user-tableau-de-bord/',
            '/mon-profil/',
            '/dashboard/',
            '/tableau-de-bord/'
        ];
        
        foreach ($dashboard_pages as $page) {
            if (strpos($current_url, $page) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Hook appelé lors de la connexion utilisateur
     */
    public function on_user_login($user_login, $user) {
        // Mettre à jour la dernière visite du dashboard
        if (class_exists('Sisme_User_Dashboard_Data_Manager')) {
            Sisme_User_Dashboard_Data_Manager::update_last_dashboard_visit($user->ID);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Dashboard] Utilisateur connecté au dashboard : $user_login (ID: {$user->ID})");
        }
    }
    
    /**
     * Hook appelé lors de la mise à jour du profil
     */
    public function on_profile_update($user_id) {
        // Nettoyer le cache des données dashboard pour cet utilisateur
        if (class_exists('Sisme_User_Dashboard_Data_Manager')) {
            Sisme_User_Dashboard_Data_Manager::clear_user_dashboard_cache($user_id);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Dashboard] Cache dashboard nettoyé pour utilisateur : $user_id");
        }
    }
    
    /**
     * Forcer le chargement des assets (utile pour shortcodes dynamiques)
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
     * Vérifier si le module dashboard est compatible avec l'environnement
     */
    public function check_requirements() {
        $requirements = [];
        
        // Vérifier que user-auth est chargé
        if (!class_exists('Sisme_User_Auth_Loader')) {
            $requirements[] = 'Sisme_User_Auth_Loader requis mais non trouvé';
        }
        
        // Vérifier que le module cards est disponible
        if (!class_exists('Sisme_Utils_Games')) {
            $requirements[] = 'Sisme_Utils_Games requis mais non trouvé';
        }
        
        // Vérifier WordPress version
        global $wp_version;
        if (version_compare($wp_version, '5.0', '<')) {
            $requirements[] = 'WordPress 5.0+ requis';
        }
        
        // Vérifier PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            $requirements[] = 'PHP 7.4+ requis';
        }
        
        if (!empty($requirements)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme User Dashboard] Prérequis manquants : ' . implode(', ', $requirements));
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtenir les statistiques du module (pour debug)
     */
    public function get_module_stats() {
        return [
            'version' => $this->get_version(),
            'assets_loaded' => $this->assets_loaded,
            'modules_loaded' => self::$modules_loaded,
            'requirements_ok' => $this->check_requirements(),
            'shortcodes_registered' => shortcode_exists('sisme_user_dashboard'),
            'hooks_registered' => has_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'])
        ];
    }
}