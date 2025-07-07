<?php
/**
 * File: /sisme-games-editor/includes/search/search-loader.php
 * MODULE SEARCH REFAIT - Loader pour le système de modules
 * 
 * RESPONSABILITÉ:
 * - Chargement des modules PHP search
 * - Enregistrement des assets CSS/JS
 * - Initialisation des shortcodes
 * - Gestion des dépendances
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Search_Loader {
    
    private static $instance = null;
    private static $modules_loaded = false;
    
    /**
     * Singleton - Instance unique
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
        $this->load_modules();
        $this->init_hooks();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Search] Module search initialisé');
        }
    }
    
    /**
     * Charger tous les modules PHP
     */
    private function load_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $search_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/search/';
        
        $modules = array(
            'search-api.php',
            'search-ajax.php'
        );
        
        foreach ($modules as $module) {
            $file_path = $search_dir . $module;
            if (file_exists($file_path)) {
                require_once $file_path;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme Search] Module chargé: {$module}");
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme Search] Module manquant: {$module}");
                }
            }
        }
        
        // Initialiser les APIs après chargement des modules
        if (class_exists('Sisme_Search_API')) {
            Sisme_Search_API::init();
        }
        
        if (class_exists('Sisme_Search_Ajax')) {
            Sisme_Search_Ajax::init();
        }
        
        self::$modules_loaded = true;
    }
    
    /**
     * Initialiser les hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_enqueue_scripts', array($this, 'localize_scripts'), 20);
    }
    
    /**
     * Charger les assets CSS/JS
     */
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }
        
        wp_enqueue_style(
            'sisme-search-style',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/search/assets/search.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_script(
            'sisme-search-script',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/search/assets/search.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
    }
    
    /**
     * Localiser les scripts
     */
    public function localize_scripts() {
        if (wp_script_is('sisme-search-script', 'enqueued')) {
            wp_localize_script('sisme-search-script', 'sismeSearch', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sisme_search_nonce'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG,
                'messages' => array(
                    'searching' => 'Recherche en cours...',
                    'no_results' => 'Aucun jeu trouvé',
                    'error' => 'Erreur lors de la recherche'
                )
            ));
        }
    }
    
    /**
     * Vérifier si les assets doivent être chargés
     */
    private function should_load_assets() {
        if (is_admin()) {
            return false;
        }
        
        global $post;
        if ($post && has_shortcode($post->post_content, 'sisme_search')) {
            return true;
        }
        
        if (is_page('recherche') || is_search()) {
            return true;
        }
        
        return false;
    }
}