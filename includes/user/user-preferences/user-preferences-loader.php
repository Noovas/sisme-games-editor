<?php
/**
 * File: /sisme-games-editor/includes/user/user-preferences/user-preferences-loader.php
 * Loader du module préférences utilisateur
 * 
 * RESPONSABILITÉ:
 * - Charger tous les composants du module préférences
 * - Initialiser les hooks et assets CSS/JS
 * - Gérer le chargement conditionnel des assets
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

class Sisme_User_Preferences_Loader {
    
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
     * Initialisation du module préférences
     */
    private function init() {
        $this->load_preferences_modules();
        $this->register_hooks();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Preferences] Module préférences utilisateur initialisé');
        }
    }
    
    /**
     * Charger tous les modules des préférences
     */
    private function load_preferences_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $preferences_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-preferences/';
        
        // Modules requis dans l'ordre
        $required_modules = [
            'user-preferences-data-manager.php',  // Gestion données en premier
            'user-preferences-api.php',           // API et rendu
            'user-preferences-ajax.php'           // Handlers AJAX
        ];
        
        foreach ($required_modules as $module) {
            $file_path = $preferences_dir . $module;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Preferences] Module chargé : $module");
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Preferences] ERREUR - Module manquant : $file_path");
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
        
        // Hooks AJAX
        if (class_exists('Sisme_User_Preferences_Ajax')) {
            Sisme_User_Preferences_Ajax::init();
        }
        
        // Hook d'initialisation utilisateur (si nécessaire)
        add_action('user_register', [$this, 'on_user_register'], 10, 1);
    }
    
    /**
     * Charger les assets frontend (CSS/JS)
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
        if (!wp_style_is('sisme-frontend-tokens-global', 'enqueued')) {
            wp_enqueue_style(
                'sisme-frontend-tokens-global',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }
        
        wp_enqueue_style(
            'sisme-user-preferences-css',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-preferences/assets/user-preferences.css',
            array('sisme-frontend-tokens-global'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // JavaScript des préférences avec auto-save
        wp_enqueue_script(
            'sisme-user-preferences-js',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-preferences/assets/user-preferences.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        // Configuration JavaScript
        wp_localize_script('sisme-user-preferences-js', 'sismeUserPreferences', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('sisme_user_preferences_nonce'),
            'auto_save' => true,
            'save_delay' => 1000,
            'user_id' => get_current_user_id(),
            'i18n' => [
                'saving' => 'Sauvegarde en cours...',
                'saved' => 'Sauvegardé !',
                'error' => 'Erreur lors de la sauvegarde',
                'reset_confirm' => 'Êtes-vous sûr de vouloir réinitialiser vos préférences ?'
            ]
        ]);
        
        $this->assets_loaded = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Preferences] Assets frontend chargés');
        }
    }
    
    /**
     * Déterminer si les assets doivent être chargés
     */
    private function should_load_assets() {
        global $post;

        return true;
        
        // Charger si on est sur une page avec shortcode préférences
        if ($post && has_shortcode($post->post_content, 'sisme_user_preferences')) {
            return true;
        }
        
        // Charger si on est sur le dashboard (qui peut contenir les préférences)
        if ($post && has_shortcode($post->post_content, 'sisme_user_dashboard')) {
            return true;
        }
        
        // Charger sur les pages spécifiques de préférences/profil
        $current_url = home_url(add_query_arg(array(), $GLOBALS['wp']->request));
        $preferences_pages = [
            '/sisme-user-preferences/',
            '/mes-preferences/',
            '/preferences/',
            '/mon-profil/',
            '/dashboard/',
            '/tableau-de-bord/'
        ];
        
        foreach ($preferences_pages as $page) {
            if (strpos($current_url, $page) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Hook appelé lors de l'inscription d'un utilisateur
     * Initialise les préférences par défaut
     */
    public function on_user_register($user_id) {
        if (!class_exists('Sisme_User_Preferences_Data_Manager')) {
            return;
        }
        
        // Initialiser les préférences avec les valeurs par défaut
        $defaults = Sisme_User_Preferences_Data_Manager::get_default_preferences();
        $success = Sisme_User_Preferences_Data_Manager::update_multiple_preferences($user_id, $defaults);
        
        if ($success && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences] Préférences par défaut initialisées pour utilisateur {$user_id}");
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
     * Vérifier si le module est correctement initialisé
     */
    public function is_module_ready() {
        return self::$modules_loaded && 
               class_exists('Sisme_User_Preferences_Data_Manager') &&
               class_exists('Sisme_User_Preferences_API') &&
               class_exists('Sisme_User_Preferences_Ajax');
    }
    
    /**
     * Obtenir des statistiques sur le module (debug)
     */
    public function get_module_stats() {
        return [
            'modules_loaded' => self::$modules_loaded,
            'assets_loaded' => $this->assets_loaded,
            'classes_available' => [
                'data_manager' => class_exists('Sisme_User_Preferences_Data_Manager'),
                'api' => class_exists('Sisme_User_Preferences_API'),
                'ajax' => class_exists('Sisme_User_Preferences_Ajax')
            ],
            'should_load_assets' => $this->should_load_assets(),
            'version' => $this->get_version()
        ];
    }
    
    /**
     * Nettoyer les assets lors de la déconnexion (si nécessaire)
     */
    public function cleanup_on_logout() {
        // Actions de nettoyage si nécessaire
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Preferences] Nettoyage session préférences');
        }
    }
    
    /**
     * Intégration avec le dashboard (méthode appelée par le dashboard)
     */
    public function integrate_with_dashboard() {
        // S'assurer que les assets sont chargés si le dashboard les utilise
        $this->force_load_assets();
        
        // Vérifier que les dépendances sont disponibles
        if (!$this->is_module_ready()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme User Preferences] ERREUR - Module non prêt pour intégration dashboard');
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtenir les données de préférences pour intégration externe
     */
    public function get_preferences_for_user($user_id) {
        if (!$this->is_module_ready()) {
            return [];
        }
        
        return Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id);
    }
}