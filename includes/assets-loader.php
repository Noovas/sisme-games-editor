<?php
/**
 * File: /sisme-games-editor/includes/assets-loader.php
 * Gestion du chargement des assets CSS/JS - VERSION MISE À JOUR
 * 
 * MODIFICATION: Ajout du CSS épuré pour la page de création de fiche
 * 
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Assets_Loader {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_global_frontend'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
        //add_action('wp_enqueue_scripts', array($this, 'enqueue_carousel_assets'));
        //add_action('admin_enqueue_scripts', array($this, 'enqueue_carousel_assets'));
        //add_action('wp_enqueue_scripts', array($this, 'enqueue_homepage_assets'));
    }

    public function enqueue_global_frontend() {
        if (is_admin()) {
            return;
        }
        
        wp_enqueue_style(
            'sisme-frontend-tokens-global',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_style(
            'sisme-frontend-global',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/front-global.css',
            array('sisme-frontend-tokens-global'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_style(
            'sisme-hero-section',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/hero-section.css',
            array('sisme-frontend-tokens-global'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_style(
            'sisme-carousel-universal',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/carousel.css',
            array('sisme-frontend-tokens-global'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_style(
            'sisme-sections-styles',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/sections.css',
            array('sisme-frontend-tokens-global'),
            SISME_GAMES_EDITOR_VERSION
        );

        wp_enqueue_style(
            'sisme-homepage-universal',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/homepage.css',
            array('sisme-frontend-tokens-global'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_script(
            'sisme-frontend-tooltip',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/frontend-tooltip.js',
            array(),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
    }

    /*public function enqueue_homepage_assets() {
        global $post;
        
        // Vérifier si la page contient le shortcode homepage
        $has_homepage_shortcode = false;
        
        if (is_object($post)) {
            $has_homepage_shortcode = has_shortcode($post->post_content, 'sisme_homepage');
        }
        
        // Charger aussi sur la page d'accueil (front_page)
        $is_front_page = is_front_page() || is_home();
        
        // Charger si shortcode présent ou page d'accueil
        if ($has_homepage_shortcode || $is_front_page) {
            
            // 1. Design tokens (base)
            wp_enqueue_style(
                'sisme-frontend-tokens',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // 2. Styles homepage spécifiques
            wp_enqueue_style(
                'sisme-homepage',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/homepage.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // 3. Carrousel
            wp_enqueue_style(
                'sisme-carousel',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/carousel.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // 4. JavaScript homepage (si nécessaire)
            if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'assets/js/homepage.js')) {
                wp_enqueue_script(
                    'sisme-homepage-js',
                    SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/homepage.js',
                    array('jquery'),
                    SISME_GAMES_EDITOR_VERSION,
                    true
                );
                
                // Passer des variables PHP vers JavaScript
                wp_localize_script('sisme-homepage-js', 'sismeHomepage', array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('sisme_homepage_nonce'),
                    'loadingText' => __('Chargement...', 'sisme-games-editor'),
                    'errorText' => __('Erreur lors du chargement', 'sisme-games-editor')
                ));
            }
            
            // Log pour debug
            error_log("Sisme: Assets homepage chargés - Shortcode: " . ($has_homepage_shortcode ? 'oui' : 'non') . " - Front page: " . ($is_front_page ? 'oui' : 'non'));
        }
    }*/

    /**
     * Charger les assets carrousel quand nécessaire
     */
    /*public function enqueue_carousel_assets() {
        // Vérifier si la page contient le shortcode vedettes
        global $post;
        $has_carousel_shortcode = false;
        
        if (is_object($post)) {
            $has_carousel_shortcode = has_shortcode($post->post_content, 'sisme_vedettes_carousel');
        }
        
        // Charger aussi dans l'admin sur la page vedettes
        $is_vedettes_admin = is_admin() && isset($_GET['page']) && $_GET['page'] === 'sisme-games-vedettes';
        
        // Charger si shortcode présent ou page admin vedettes
        if ($has_carousel_shortcode || $is_vedettes_admin) {
            wp_enqueue_style(
                'sisme-carousel',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/carousel.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // Log pour debug
            error_log("Sisme: CSS carrousel chargé - Shortcode: " . ($has_carousel_shortcode ? 'oui' : 'non') . " - Admin vedettes: " . ($is_vedettes_admin ? 'oui' : 'non'));
        }
    }*/
    
    /**
     * Charger les styles admin - DARK GAMING STYLE + FICHE FORM
     * 
     * MODIFICATION: Ajout du chargement conditionnel pour edit-fiche-jeu
     */
    public function enqueue_admin_styles($hook) {
        // Vérifier si on est sur une page admin du plugin
        if (strpos($hook, 'sisme-games') === false) {
            return;
        }
        
        // CSS Dark Gaming de base (pour toutes les pages admin du plugin)
        wp_enqueue_style(
            'sisme-admin-dark',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/admin-dark.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );


        
        // Page création/édition de fiche
        if (strpos($hook, 'sisme-games-edit-fiche-jeu') !== false) {
            wp_enqueue_style(
                'sisme-fiche-form',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/admin-fiche-form.css',
                array('sisme-admin-dark'),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // JavaScript optionnel pour améliorer l'UX
            if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'assets/js/fiche-form.js')) {
                wp_enqueue_script(
                    'sisme-fiche-form-js',
                    SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/fiche-form.js',
                    array('jquery'),
                    SISME_GAMES_EDITOR_VERSION,
                    true
                );
            }
        }
        
        // Page création/édition de jeu
        if (strpos($hook, 'sisme-games-edit-game-data') !== false) {
            wp_enqueue_style(
                'sisme-game-form',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/admin-game-form.css',
                array('sisme-admin-dark'),
                SISME_GAMES_EDITOR_VERSION
            );
        }

        //Page vedettes
        if (strpos($hook, 'vedettes') !== false) {
            wp_enqueue_style(
                'sisme-admin-vedettes',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/admin-vedettes.css',
                array('sisme-admin-dark'),
                SISME_GAMES_EDITOR_VERSION
            );
        }
        
        // JavaScript pour les tooltips (toutes les pages admin)
        wp_enqueue_script(
            'sisme-tooltip',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/tooltip.js',
            array(),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        // JavaScript admin si nécessaire
        if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'assets/js/admin.js')) {
            wp_enqueue_script(
                'sisme-admin-js',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'sisme-tooltip'),
                SISME_GAMES_EDITOR_VERSION,
                true
            );
        }
    }
    
    /**
     * Vérifier si la fiche a des screenshots
     */
    private function has_screenshots() {
        if (!is_single()) return false;
        
        global $post;
        $game_tags = wp_get_post_tags($post->ID);
        if (empty($game_tags)) return false;
        
        $tag_id = $game_tags[0]->term_id;
        $screenshots = get_term_meta($tag_id, Sisme_Utils_Games::META_SCREENSHOTS, true);
        
        return !empty($screenshots);
    }
}

/*add_action('wp_loaded', function() {
    // Vérifier que le fichier existe avant de l'inclure
    $homepage_module_file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/frontend/homepage-module.php';
    
    if (file_exists($homepage_module_file)) {
        require_once $homepage_module_file;
        
        // Log de réussite
        error_log("Sisme: Module homepage chargé avec succès");
    } else {
        // Log d'erreur
        error_log("Sisme: ERREUR - Fichier homepage-module.php introuvable: " . $homepage_module_file);
    }
});*/