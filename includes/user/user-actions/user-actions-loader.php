<?php
/**
 * File: user-actions-loader.php
 * VERSION ULTRA SIMPLE - Charge PARTOUT sur le frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Actions_Loader {
    
    private static $instance = null;
    private $modules_loaded = false;
    private $assets_loaded = false;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        $this->load_module_files();
        $this->register_hooks();
    }
    
    private function load_module_files() {
        if ($this->modules_loaded) {
            return;
        }
        
        $actions_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-actions/';
        
        $required_modules = [
            'user-actions-data-manager.php',
            'user-actions-api.php',
            'user-actions-ajax.php'
        ];
        
        foreach ($required_modules as $module) {
            $file_path = $actions_dir . $module;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        $this->modules_loaded = true;
    }
    
    private function register_hooks() {
        // SIMPLE : Charger PARTOUT sur le frontend
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        if (class_exists('Sisme_User_Actions_Ajax')) {
            Sisme_User_Actions_Ajax::init();
        }
    }
    
    /**
     * ✅ VERSION ULTRA SIMPLE : Charge TOUJOURS sur le frontend
     */
    public function enqueue_frontend_assets() {
        // ❌ Pas d'admin
        if (is_admin()) {
            return;
        }
        
        // ❌ Pas de double chargement
        if ($this->assets_loaded) {
            return;
        }
        
        // ✅ CHARGER PARTOUT - POINT FINAL !
        
        // Tokens frontend (dépendance)
        if (!wp_style_is('sisme-frontend-tokens-global', 'enqueued')) {
            wp_enqueue_style(
                'sisme-frontend-tokens-global',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }
        
        // CSS principal
        wp_enqueue_style(
            'sisme-user-actions',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-actions/assets/user-actions.css',
            array('sisme-frontend-tokens-global'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // CSS favoris
        wp_enqueue_style(
            'sisme-user-actions-favorites',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-actions/assets/user-actions-favorites.css',
            array('sisme-user-actions'),
            SISME_GAMES_EDITOR_VERSION
        );

        // CSS owned
        wp_enqueue_style(
            'sisme-user-actions-owned',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-actions/assets/user-actions-owned.css',
            array('sisme-user-actions'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // JavaScript principal
        wp_enqueue_script(
            'sisme-user-actions',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-actions/assets/user-actions.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        // JavaScript favoris
        wp_enqueue_script(
            'sisme-user-actions-favorites',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-actions/assets/user-actions-favorites.js',
            array('sisme-user-actions'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        // Configuration AJAX
        wp_localize_script('sisme-user-actions', 'sismeUserActions', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'security' => wp_create_nonce('sisme_user_actions_nonce'),
            'is_logged_in' => is_user_logged_in(),
            'login_url' => wp_login_url(get_permalink()),
            'i18n' => array(
                'error' => __('Une erreur est survenue', 'sisme-games-editor'),
                'login_required' => __('Vous devez être connecté', 'sisme-games-editor')
            )
        ));
        
        $this->assets_loaded = true;
    }
}

// Initialiser
Sisme_User_Actions_Loader::get_instance();