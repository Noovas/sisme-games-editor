<?php
/**
 * File: /sisme-games-editor/includes/cards/cards-loader.php
 * Chargeur principal du système de cartes
 * 
 * RESPONSABILITÉ:
 * - Inclure tous les modules du système de cartes
 * - Initialiser les hooks WordPress
 * - Enregistrer les shortcodes
 * - Charger les assets CSS/JS de manière conditionnelle
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Cards_Loader {
    private static $instance = null;
    private static $modules_loaded = false;

    private function __construct() {
        $this->load_modules();
        $this->init_hooks();
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 📦 Charger tous les modules du système de cartes
     */
    private function load_modules() {
        if (self::$modules_loaded) {
            return;
        }
        $cards_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/cards/';
        
        // 1. Fonctions utilitaires (doit être chargé en premier)
        require_once $cards_dir . 'cards-functions.php';
        
        // 2. Modules de rendu
        require_once $cards_dir . 'cards-normal-module.php';
        require_once $cards_dir . 'cards-carousel-module.php';

        // require_once $cards_dir . 'cards-details-module.php';   // À venir
        // require_once $cards_dir . 'cards-compact-module.php';   // À venir
        
        // 3. API principale (doit être chargée après les modules)
        require_once $cards_dir . 'cards-api.php';
        
        self::$modules_loaded = true;
        
        // Log pour debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Cards] Modules chargés avec succès');
        }
    }
    
    /**
     * 🎣 Initialiser les hooks WordPress
     */
    private function init_hooks() {
        // Hooks pour le chargement des assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'), 15);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'), 15);
    }
    
    /**
     * 🎨 Charger les assets frontend PARTOUT
     */
    public function enqueue_assets() {
        $this->load_cards_assets();
    }
    
    /**
     * 📦 Charger les assets CSS/JS des cartes
     */
    private function load_cards_assets($is_admin = false) {
        // 1. Design tokens (base)
        if (!$is_admin) {
            wp_enqueue_style(
                'sisme-frontend-tokens',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // 2. Système de tooltip
            wp_enqueue_script(
                'sisme-frontend-tooltip',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/frontend-tooltip.js',
                array(),
                SISME_GAMES_EDITOR_VERSION,
                true
            );
        }
        
        // 3. CSS principal des cartes
        wp_enqueue_style(
            'sisme-cards',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/cards/assets/cards.css',
            $is_admin ? array() : array('sisme-frontend-tokens'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // 4. CSS grilles et carrousels
        wp_enqueue_style(
            'sisme-cards-grid',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/cards/assets/cards-grid.css',
            array('sisme-cards'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // 5. 🆕 JavaScript carrousel (si le fichier existe)
        if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/cards/assets/cards-carousel.js')) {
            wp_enqueue_script(
                'sisme-cards-carousel-js',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/cards/assets/cards-carousel.js',
                $is_admin ? array('jquery') : array('jquery', 'sisme-frontend-tooltip'),
                SISME_GAMES_EDITOR_VERSION,
                true
            );
            
            // Variables pour JavaScript carrousel
            wp_localize_script('sisme-cards-carousel-js', 'sismeCarousel', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sisme_carousel_nonce'),
                'loadingText' => __('Chargement...', 'sisme-games-editor'),
                'errorText' => __('Erreur lors du chargement', 'sisme-games-editor'),
                'prevText' => __('Précédent', 'sisme-games-editor'),
                'nextText' => __('Suivant', 'sisme-games-editor'),
                'pageText' => __('Page', 'sisme-games-editor'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            ));
        }
        
        // 6. JavaScript des cartes (existant)
        if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/cards/assets/cards.js')) {
            wp_enqueue_script(
                'sisme-cards-js',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/cards/assets/cards.js',
                $is_admin ? array('jquery') : array('jquery', 'sisme-frontend-tooltip'),
                SISME_GAMES_EDITOR_VERSION,
                true
            );
            
            // Variables pour JavaScript (existant)
            wp_localize_script('sisme-cards-js', 'sismeCards', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sisme_cards_nonce'),
                'loadingText' => __('Chargement...', 'sisme-games-editor'),
                'errorText' => __('Erreur lors du chargement', 'sisme-games-editor')
            ));
        }
    }
    
}