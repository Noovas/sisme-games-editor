<?php
/**
 * File: /sisme-games-editor/includes/assets-loader.php
 * Gestion du chargement des assets CSS/JS - VERSION DARK ADMIN
 * 
 * Charge le nouveau style gaming dark pour l'admin
 * et conserve le frontend existant
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Assets_Loader {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    /**
     * Charger les styles admin - DARK GAMING STYLE
     */
    public function enqueue_admin_styles($hook) {
        // Vérifier si on est sur une page admin du plugin
        if (strpos($hook, 'sisme-games') === false) {
            return;
        }
        
        // NOUVEAU CSS Dark Gaming (remplace admin.css)
        wp_enqueue_style(
            'sisme-admin-dark',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/admin-dark.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // JavaScript pour les tooltips
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
     * Charger les styles frontend - INCHANGÉ
     * Garde le système existant pour les fiches de jeu
     */
    public function enqueue_frontend_styles() {
        // SEULEMENT pour les fiches de jeu modernes
        if (is_single() && $this->is_game_fiche()) {
            
            // 1. Variables CSS frontend
            wp_enqueue_style(
                'sisme-frontend-tokens',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // 2. Hero Section (layout principal)
            wp_enqueue_style(
                'sisme-hero-section',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/hero-section.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // 3. Sections personnalisées
            wp_enqueue_style(
                'sisme-sections-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/sections.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // 4. JavaScript galerie interactive (si screenshots présents)
            $this->enqueue_gallery_script();
        }
    }
    
    /**
     * Charger le script de galerie si nécessaire
     */
    private function enqueue_gallery_script() {
        global $post;
        
        if (!$post) {
            return;
        }
        
        // Récupérer le tag du jeu
        $game_tags = wp_get_post_tags($post->ID);
        if (empty($game_tags)) {
            return;
        }
        
        // Vérifier si le jeu a des screenshots
        $tag_id = $game_tags[0]->term_id;
        $screenshots = get_term_meta($tag_id, 'screenshots', true);
        
        if (!empty($screenshots)) {
            wp_enqueue_script(
                'sisme-gallery-interactive',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/gallery-interactive.js',
                array('jquery'),
                SISME_GAMES_EDITOR_VERSION,
                true
            );
        }
    }
    
    /**
     * Détecter si l'article est une fiche de jeu moderne
     * 
     * @param int $post_id ID de l'article (optionnel)
     * @return bool True si c'est une fiche de jeu
     */
    private function is_game_fiche($post_id = null) {
        if (!$post_id) {
            global $post;
            if (!$post) {
                return false;
            }
            $post_id = $post->ID;
        }
        
        // Détecter via la métadonnée des sections de jeu
        $game_sections = get_post_meta($post_id, '_sisme_game_sections', true);
        return !empty($game_sections);
    }
}