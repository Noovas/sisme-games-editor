<?php
/**
 * File: /sisme-games-editor/includes/assets-loader.php
 * Gestion du chargement des assets CSS/JS - VERSION ÉPURÉE
 * 
 * Charge seulement les assets nécessaires selon le contexte :
 * - Admin : 1 seul CSS (admin.css avec imports)
 * - Frontend : CSS pour les fiches de jeu uniquement
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
     * Charger les styles admin - SIMPLIFIÉ
     */
    public function enqueue_admin_styles($hook) {
        // Vérifier si on est sur une page admin du plugin
        if (strpos($hook, 'sisme-games') === false) {
            return;
        }
        
        // CSS Admin principal avec tous les imports
        wp_enqueue_style(
            'sisme-admin',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // JavaScript admin si nécessaire
        wp_enqueue_script(
            'sisme-admin-js',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
    }
    
    /**
     * Charger les styles frontend - ÉPURÉ
     * Charge seulement pour les fiches de jeu
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