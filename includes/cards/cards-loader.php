<?php
/**
 * File: /sisme-games-editor/includes/cards/cards-loader.php
 * NOUVEAU CHARGEUR ORGANISÉ POUR LE SYSTÈME DE CARTES
 * 
 * RESPONSABILITÉ:
 * - Chargement des modules PHP
 * - Enregistrement des assets CSS/JS
 * - Initialisation des shortcodes
 * - Gestion des dépendances
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Cards_Loader {
    private static $instance = null;
    private static $modules_loaded = false;
    private static $assets_loaded = false;

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
     * 📦 Charger tous les modules PHP
     */
    private function load_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $cards_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/cards/';
        
        try {
            // 1. Modules de rendu (ordre important)
            $modules = [
                'cards-normal-module.php',
                'cards-details-module.php', 
                'cards-carousel-module.php',
                // 'cards-compact-module.php', // À venir
            ];
            
            foreach ($modules as $module) {
                $file_path = $cards_dir . $module;
                if (file_exists($file_path)) {
                    require_once $file_path;
                    $this->debug_log("Module chargé: {$module}");
                } else {
                    $this->debug_log("Module manquant: {$module}", 'error');
                }
            }
            
            // 2. API principale (doit être chargée après les modules)
            require_once $cards_dir . 'cards-api.php';
            $this->debug_log("API principale chargée");
            
            self::$modules_loaded = true;
            $this->debug_log("Tous les modules chargés avec succès");
            
        } catch (Exception $e) {
            $this->debug_log("Erreur chargement modules: " . $e->getMessage(), 'error');
        }
    }
    
    /**
     * 🎣 Initialiser les hooks WordPress
     */
    private function init_hooks() {
        // Chargement des assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets'], 15);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets'], 15);
        
        // Hook pour AJAX/contenu dynamique
        add_action('wp_ajax_reload_cards', [$this, 'handle_ajax_reload']);
        add_action('wp_ajax_nopriv_reload_cards', [$this, 'handle_ajax_reload']);
    }
    
    /**
     * 🎨 Charger les assets frontend
     */
    public function enqueue_frontend_assets() {
        if (self::$assets_loaded) {
            return;
        }
        
        $this->enqueue_base_assets();
        $this->enqueue_cards_assets();
        $this->enqueue_carousel_assets();
        
        self::$assets_loaded = true;
        $this->debug_log("Assets frontend chargés");
    }
    
    /**
     * 🛠️ Charger les assets admin
     */
    public function enqueue_admin_assets() {
        // Pour l'admin, on charge juste les assets de base
        $this->enqueue_base_assets();
        $this->debug_log("Assets admin chargés");
    }
    
    /**
     * 🎯 Assets de base (tokens, utilities)
     */
    private function enqueue_base_assets() {
        // 1. Design tokens
        wp_enqueue_style(
            'sisme-frontend-tokens',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
            [],
            SISME_GAMES_EDITOR_VERSION
        );
        
        // 2. Système de tooltip
        wp_enqueue_script(
            'sisme-frontend-tooltip',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/frontend-tooltip.js',
            [],
            SISME_GAMES_EDITOR_VERSION,
            true
        );
    }
    
    /**
     * 🎴 Assets des cartes
     */
    private function enqueue_cards_assets() {
        // 1. CSS de base des cartes
        wp_enqueue_style(
            'sisme-cards-base',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/cards/assets/cards.css',
            ['sisme-frontend-tokens'],
            SISME_GAMES_EDITOR_VERSION
        );
        
        // 2. CSS des grilles
        wp_enqueue_style(
            'sisme-cards-grid',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/cards/assets/cards-grid.css',
            ['sisme-cards-base'],
            SISME_GAMES_EDITOR_VERSION
        );
        
        // 3. JavaScript des cartes (s'il existe)
        $cards_js = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/cards/assets/cards.js';
        if (file_exists($cards_js)) {
            wp_enqueue_script(
                'sisme-cards-js',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/cards/assets/cards.js',
                ['jquery', 'sisme-frontend-tooltip'],
                SISME_GAMES_EDITOR_VERSION,
                true
            );
        }
    }
    
    /**
     * 🎠 Assets des carrousels
     */
    private function enqueue_carousel_assets() {
        // 1. CSS du carrousel
        wp_enqueue_style(
            'sisme-cards-carousel-css',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/cards/assets/cards-carousel.css',
            ['sisme-cards-base'],
            SISME_GAMES_EDITOR_VERSION
        );
        
        // 2. JavaScript du carrousel
        $carousel_js = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/cards/assets/cards-carousel.js';
        if (file_exists($carousel_js)) {
            wp_enqueue_script(
                'sisme-cards-carousel-js',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/cards/assets/cards-carousel.js',
                ['jquery', 'sisme-frontend-tooltip'],
                SISME_GAMES_EDITOR_VERSION,
                true
            );
            
            // 3. Variables pour le JavaScript
            wp_localize_script('sisme-cards-carousel-js', 'sismeCarousel', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sisme_carousel_nonce'),
                'loadingText' => __('Chargement...', 'sisme-games-editor'),
                'errorText' => __('Erreur lors du chargement', 'sisme-games-editor'),
                'prevText' => __('Précédent', 'sisme-games-editor'),
                'nextText' => __('Suivant', 'sisme-games-editor'),
                'pageText' => __('Page', 'sisme-games-editor'),
                'debug' => defined('WP_DEBUG') && WP_DEBUG
            ]);
        }
    }
    
    /**
     * 🔄 Gérer le rechargement AJAX
     */
    public function handle_ajax_reload() {
        check_ajax_referer('sisme_carousel_nonce', 'nonce');
        
        // Logique de rechargement si nécessaire
        wp_die();
    }
    
    /**
     * 📊 Statistiques de chargement
     */
    public function get_load_stats() {
        return [
            'modules_loaded' => self::$modules_loaded,
            'assets_loaded' => self::$assets_loaded,
            'version' => SISME_GAMES_EDITOR_VERSION,
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ];
    }
    
    /**
     * 🐛 Logging debug
     */
    private function debug_log($message, $type = 'info') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $prefix = $type === 'error' ? '❌' : '✅';
            error_log("[Sisme Cards Loader] {$prefix} {$message}");
        }
    }
    
    /**
     * 🧹 Nettoyer les assets (pour les tests)
     */
    public function cleanup_assets() {
        // Désenregistrer les styles
        wp_deregister_style('sisme-cards-base');
        wp_deregister_style('sisme-cards-grid');
        wp_deregister_style('sisme-cards-carousel-css');
        
        // Désenregistrer les scripts
        wp_deregister_script('sisme-cards-js');
        wp_deregister_script('sisme-cards-carousel-js');
        
        self::$assets_loaded = false;
        $this->debug_log("Assets nettoyés");
    }
    
    /**
     * 🔍 Vérifier l'intégrité des fichiers
     */
    public function check_file_integrity() {
        $required_files = [
            'includes/cards/assets/cards.css',
            'includes/cards/assets/cards-grid.css', 
            'includes/cards/assets/cards-carousel.css',
            'includes/cards/assets/cards-carousel.js',
            'includes/cards/cards-api.php',
            'includes/cards/cards-normal-module.php',
            'includes/cards/cards-carousel-module.php',
            'includes/cards/cards-details-module.php'
        ];
        
        $missing_files = [];
        $existing_files = [];
        
        foreach ($required_files as $file) {
            $full_path = SISME_GAMES_EDITOR_PLUGIN_DIR . $file;
            if (file_exists($full_path)) {
                $existing_files[] = $file;
            } else {
                $missing_files[] = $file;
            }
        }
        
        return [
            'status' => empty($missing_files) ? 'ok' : 'error',
            'existing' => $existing_files,
            'missing' => $missing_files,
            'total' => count($required_files),
            'existing_count' => count($existing_files)
        ];
    }
}

// Initialisation automatique
Sisme_Cards_Loader::get_instance();

// Fonction utilitaire pour debug
function sisme_cards_debug_info() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $loader = Sisme_Cards_Loader::get_instance();
        return [
            'loader_stats' => $loader->get_load_stats(),
            'file_integrity' => $loader->check_file_integrity(),
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'plugin_version' => SISME_GAMES_EDITOR_VERSION
        ];
    }
    return null;
}

// Shortcut pour nettoyer les assets (utile pour les tests)
function sisme_cards_cleanup() {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $loader = Sisme_Cards_Loader::get_instance();
        $loader->cleanup_assets();
        return true;
    }
    return false;
}