<?php
/**
 * File: /sisme-games-editor/includes/assets-loader.php
 * Gestion du chargement des assets CSS/JS - Mis à jour avec news-cards
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
            // CSS des composants dans l'ordre d'affichage
            wp_enqueue_style(
                'sisme-description-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/description.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            wp_enqueue_style(
                'sisme-trailer-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/trailer.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            wp_enqueue_style(
                'sisme-informations-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/informations.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // CSS des sections de contenu personnalisées
            wp_enqueue_style(
                'sisme-sections-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/sections.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            wp_enqueue_style(
                'sisme-blocks-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/blocks.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // CSS des liens boutiques
            wp_enqueue_style(
                'sisme-stores-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/stores.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }

        // CSS pour les contenus patch/news
        if (is_single() && $this->is_patch_news_content()) {
            wp_enqueue_style(
                'sisme-patch-news-content-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/patch-news-content.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }

        // CSS pour les pages news
        if (is_single() && $this->is_news_page()) {
            // CSS de la grille/liste des news (container principal)
            wp_enqueue_style(
                'sisme-news-list-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/news-list.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // CSS des cartes de news/patch individuelles
            wp_enqueue_style(
                'sisme-news-cards-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/news-cards.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // CSS des liens boutiques (réutilisé)
            wp_enqueue_style(
                'sisme-stores-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/stores.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }
    }

    /**
     * Vérifier si l'article actuel est un contenu patch/news
     */
    private function is_patch_news_content() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Vérification par métadonnée
        if (get_post_meta($post->ID, '_sisme_is_patch_news', true)) {
            return true;
        }
        
        // Vérification par catégorie
        $categories = get_the_category($post->ID);
        foreach ($categories as $category) {
            if (in_array($category->slug, array('patch', 'news'))) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Vérifier si l'article actuel est une page news
     */
    private function is_news_page() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Vérification par métadonnée
        if (get_post_meta($post->ID, '_sisme_is_news_page', true)) {
            return true;
        }
        
        // Vérification par slug pattern
        if (preg_match('/-news$/', $post->post_name)) {
            return true;
        }
        
        // Vérification par catégorie
        $categories = get_the_category($post->ID);
        foreach ($categories as $category) {
            if ($category->slug === 'page-news') {
                return true;
            }
        }
        
        return false;
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
            
            // CSS spécifique pour le dashboard
            if ($hook === 'toplevel_page_sisme-games-editor') {
                wp_enqueue_style(
                    'sisme-dashboard-styles',
                    SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/admin/dashboard.css',
                    array(),
                    SISME_GAMES_EDITOR_VERSION
                );
            }
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