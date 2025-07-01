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