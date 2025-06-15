<?php
/**
 * File: /sisme-games-editor/includes/assets-loader.php
 * Gestion du chargement des assets CSS/JS
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Assets_Loader {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    }
    
    /**
     * Charger les styles front-end
     */
    public function enqueue_frontend_styles() {
        // Vérifier si on est sur une fiche de jeu
        if (is_single() && $this->is_game_fiche()) {
            // CSS des composants
            wp_enqueue_style(
                'sisme-description-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/description.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // CSS du trailer
            wp_enqueue_style(
                'sisme-trailer-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/trailer.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // CSS des informations
            wp_enqueue_style(
                'sisme-informations-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/informations.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // CSS des blocs test/news
            wp_enqueue_style(
                'sisme-blocks-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/blocks.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }
    }
    
    /**
     * Charger les styles admin
     */
    public function enqueue_admin_styles($hook) {
        // Vérifier si on est sur les pages du plugin
        if (strpos($hook, 'sisme-games') !== false) {
            wp_enqueue_style(
                'sisme-admin-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/admin/admin.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }
    }
    
    /**
     * Vérifier si l'article actuel est une fiche de jeu
     */
    private function is_game_fiche() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Vérifier si l'article a des métadonnées de jeu
        $game_modes = get_post_meta($post->ID, '_sisme_game_modes', true);
        return !empty($game_modes);
    }
}