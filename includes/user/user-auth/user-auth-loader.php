<?php
/**
 * File: /sisme-games-editor/includes/user/user-auth/user-auth-loader.php
 * Loader du module d'authentification utilisateur
 * 
 * RESPONSABILITÉ:
 * - Charger tous les composants d'authentification
 * - Initialiser les shortcodes d'auth
 * - Gérer les assets CSS/JS
 * - Hooks de sécurité
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Auth_Loader {
    
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
     * Initialisation du module auth
     */
    private function init() {
        $this->load_auth_modules();
        $this->register_hooks();
        $this->init_shortcodes();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Auth] Module d\'authentification initialisé');
        }
    }
    
    /**
     * Charger tous les modules d'authentification
     */
    private function load_auth_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $auth_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-auth/';
        
        // Modules requis dans l'ordre
        $required_modules = [
            'user-auth-security.php',    // Sécurité en premier
            'user-auth-handlers.php',    // Logique métier
            'user-auth-forms.php',       // Formulaires
            'user-auth-api.php'          // API et shortcodes
        ];
        
        foreach ($required_modules as $module) {
            $file_path = $auth_dir . $module;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Auth] Module chargé : $module");
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Auth] ERREUR - Module manquant : $file_path");
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
        
        // Traitement des requêtes d'authentification
        add_action('wp_loaded', [$this, 'handle_auth_requests']);
        
        // Nettoyage lors de la déconnexion
        add_action('wp_logout', [$this, 'handle_logout_cleanup']);
        
        // AJAX handlers
        add_action('wp_ajax_nopriv_sisme_user_login', [$this, 'ajax_handle_login']);
        add_action('wp_ajax_nopriv_sisme_user_register', [$this, 'ajax_handle_register']);
    }
    
    /**
     * Initialiser les shortcodes
     */
    private function init_shortcodes() {
        if (class_exists('Sisme_User_Auth_API')) {
            add_shortcode('sisme_user_login', ['Sisme_User_Auth_API', 'render_login_form']);
            add_shortcode('sisme_user_register', ['Sisme_User_Auth_API', 'render_register_form']);
            add_shortcode('sisme_user_profile', ['Sisme_User_Auth_API', 'render_profile_dashboard']);
            add_shortcode('sisme_user_menu', ['Sisme_User_Auth_API', 'render_user_menu']);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme User Auth] Shortcodes enregistrés : sisme_user_login, sisme_user_register, sisme_user_profile, sisme_user_menu');
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
        
        // CSS d'authentification
        wp_enqueue_style(
            'sisme-user-auth',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-auth/assets/user-auth.css',
            ['sisme-frontend-tokens-global'], // Dépend de vos tokens CSS
            SISME_GAMES_EDITOR_VERSION
        );
        
        // JavaScript d'authentification
        wp_enqueue_script(
            'sisme-user-auth',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-auth/assets/user-auth.js',
            ['jquery'],
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        // Configuration JavaScript
        wp_localize_script('sisme-user-auth', 'sismeUserAuth', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_user_auth_nonce'),
            'messages' => [
                'loading' => 'Connexion en cours...',
                'error' => 'Une erreur est survenue',
                'success' => 'Connexion réussie !',
                'register_success' => 'Inscription réussie !',
                'logout_success' => 'Déconnexion réussie'
            ],
            'config' => [
                'redirect_delay' => 2000, // 2 secondes avant redirection
                'show_messages' => true,   // Afficher les messages
                'auto_hide_messages' => 5000 // Masquer après 5 secondes
            ],
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);
        
        $this->assets_loaded = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Auth] Assets frontend chargés');
        }
    }
    
    /**
     * Traiter les requêtes d'authentification
     */
    public function handle_auth_requests() {
        // Déléguer aux handlers spécifiques
        if (class_exists('Sisme_User_Auth_Handlers')) {
            Sisme_User_Auth_Handlers::init_request_handling();
        }
    }
    
    /**
     * Nettoyage lors de la déconnexion
     */
    public function handle_logout_cleanup() {
        // Actions de nettoyage lors de la déconnexion
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Auth] Nettoyage session de déconnexion');
        }
    }
    
    /**
     * Handler AJAX pour connexion
     */
    public function ajax_handle_login() {
        if (class_exists('Sisme_User_Auth_Handlers')) {
            Sisme_User_Auth_Handlers::ajax_login();
        } else {
            wp_send_json_error('Module de traitement non disponible');
        }
    }
    
    /**
     * Handler AJAX pour inscription
     */
    public function ajax_handle_register() {
        if (class_exists('Sisme_User_Auth_Handlers')) {
            Sisme_User_Auth_Handlers::ajax_register();
        } else {
            wp_send_json_error('Module de traitement non disponible');
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
}