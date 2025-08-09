<?php
/**
 * File: /sisme-games-editor/includes/dev-editor-manager/dev-editor-manager-loader.php
 * Loader du module gestionnaire de développeurs/éditeurs
 * 
 * RESPONSABILITÉ:
 * - Charger les composants du module dev-editor-manager
 * - Initialiser l'interface admin et les APIs
 * - Point d'entrée unique du module via système principal
 * - Gestion des hooks WordPress
 * 
 * DÉPENDANCES:
 * - WordPress Category API
 * - WordPress Meta API
 * - module-admin-page-wrapper.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Dev_Editor_Manager_Loader {
    
    private static $instance = null;
    private static $modules_loaded = false;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        $this->load_modules();
        $this->init_hooks();
    }

    private function load_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $module_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/dev-editor-manager/';
        $required_modules = [ 'dev-editor-manager.php' ];
        
        foreach ($required_modules as $module) {
            $file_path = $module_dir . $module;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        self::$modules_loaded = true;
    }
    
    private function init_hooks() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        }
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'sisme-games-dev-editors') === false) {
            return;
        }
        
        wp_enqueue_script(
            'sisme-dev-editor-manager',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/dev-editor-manager/assets/dev-editor-manager.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        wp_localize_script('sisme-dev-editor-manager', 'sismeDevEditorAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_dev_editor_nonce')
        ]);
    }
    
    public function is_module_ready() {
        return class_exists('Sisme_Dev_Editor_Manager') && self::$modules_loaded;
    }
}