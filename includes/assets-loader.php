<?php
/**
 * File: /sisme-games-editor/includes/assets-loader.php
 * Gestion du chargement des assets CSS/JS - MISE À JOUR avec Hero Section
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
     * Charger les styles admin
     */
    public function enqueue_admin_styles($hook) {
        // Vérifier si on est sur une page admin du plugin
        if (strpos($hook, 'sisme-games') === false) {
            return;
        }
        
        // CSS Admin principal (avec imports)
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
     * Charger les styles front-end - NOUVEAU SYSTÈME
     */
    public function enqueue_frontend_styles() {
        // ========================================
        // NOUVEAU : FICHES DE JEU AVEC HERO SECTION
        // ========================================
        if (is_single() && $this->is_game_fiche()) {
            
            // 1. Tokens CSS en premier (variables partagées)
            wp_enqueue_style(
                'sisme-frontend-tokens',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // 2. Hero Section (remplace description + trailer + informations)
            wp_enqueue_style(
                'sisme-hero-section',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/hero-section.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // 3. Sections personnalisées (garde l'existant mais en dépendance)
            wp_enqueue_style(
                'sisme-sections-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/sections.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // 4. Navigation blocks (nouveau, remplace blocks.css)
            wp_enqueue_style(
                'sisme-navigation-blocks',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/navigation-blocks.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // 5. Styles de base fiche (garde l'existant)
            wp_enqueue_style(
                'sisme-fiche-base',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/fiche.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
        }
        
        // ========================================
        // ANCIEN SYSTÈME MAINTENU pour les autres pages
        // ========================================
        
        // CSS pour les contenus patch/news
        if (is_single() && $this->is_patch_news_content()) {
            wp_enqueue_style(
                'sisme-frontend-tokens',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            wp_enqueue_style(
                'sisme-patch-news-content-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/patch-news-content.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
        }

        // CSS pour les articles patch/news avec blocs croisés
        if (is_single() && (has_category('patch') || has_category('news'))) {
            wp_enqueue_style(
                'sisme-frontend-tokens',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // ANCIEN : garde blocks.css pour compatibilité
            wp_enqueue_style(
                'sisme-blocks-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/blocks.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
        }

        // CSS pour les pages news
        if (is_single() && $this->is_news_page()) {
            wp_enqueue_style(
                'sisme-frontend-tokens',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // CSS de la grille/liste des news (container principal)
            wp_enqueue_style(
                'sisme-news-list-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/news-list.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
            
            // CSS des cartes de news/patch individuelles
            wp_enqueue_style(
                'sisme-news-cards-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/components/news-cards.css',
                array('sisme-frontend-tokens'),
                SISME_GAMES_EDITOR_VERSION
            );
        }
    }
    
    /**
     * Vérifier si l'article actuel est une fiche de jeu (NOUVEAU CRITÈRE)
     */
    private function is_game_fiche() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // NOUVEAU : Vérifier si l'article a des métadonnées _sisme_game_sections
        $game_sections = get_post_meta($post->ID, '_sisme_game_sections', true);
        return !empty($game_sections);
    }
    
    /**
     * Vérifier si c'est un contenu patch/news
     */
    private function is_patch_news_content() {
        return has_category('patch') || has_category('news');
    }
    
    /**
     * Vérifier si c'est une page news
     */
    private function is_news_page() {
        return is_category('news') || is_tag() || is_archive();
    }
}