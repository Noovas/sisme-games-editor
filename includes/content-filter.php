<?php
/*
 * File: /sisme-games-editor/includes/content-filter.php

if (!defined('ABSPATH')) {
    exit;
}


class Sisme_Content_Filter {
    
    public function __construct() {
        add_filter('the_content', array($this, 'process_content'));
    }
    
    public function process_content($content) {
        global $post;
        $post_id = $post->ID;
        
        // NOUVEAU SYSTÈME : Rendu dynamique avec le Game Page Renderer
        $created_with_new_system = get_post_meta($post_id, '_sisme_created_with_game_page_creator', true);
        
        // FORCE TEST - Temporaire pour débugger (supprimer après test)
        if ($this->is_game_fiche($post_id)) {
            $created_with_new_system = true;
        }
        
        if ($created_with_new_system) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Content Filter] Post {$post_id} : Nouveau système, rendu dynamique");
            }
            
            // Charger les données du jeu depuis les meta
            $game_data = $this->load_game_data_from_meta($post_id);
            
            if (!empty($game_data)) {
                // Charger le renderer
                if (!class_exists('Sisme_Game_Page_Renderer')) {
                    require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/game-page-renderer.php';
                }
                
                // Générer le HTML dynamiquement
                return Sisme_Game_Page_Renderer::render($game_data);
            }
        }
        // ANCIEN SYSTÈME : Traitement normal pour les anciennes fiches
        if ($this->is_game_fiche($post_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Content Filter] Post {$post_id} : Ancien système, génération template");
            }
            
            if (!class_exists('Sisme_Fiche_Template')) {
                require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/template-fiche.php';
            }
            return Sisme_Fiche_Template::generate_fiche_content($post_id);
        }
        
        return $content;
    }
    
    private function is_game_fiche($post_id) {
        $game_sections = get_post_meta($post_id, '_sisme_game_sections', true);
        return !empty($game_sections);
    }
    
    /**
     * Charger les données du jeu depuis les meta pour le nouveau système
     
    private function load_game_data_from_meta($post_id) {
        // POUR TEST : Récupérer le term_id depuis les tags du post
        $tags = wp_get_post_tags($post_id);
        if (empty($tags)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Content Filter] Post {$post_id} : Aucun tag trouvé");
            }
            return array();
        }
        
        // Prendre le premier tag comme term_id du jeu
        $term_id = $tags[0]->term_id;
        
        // Utiliser le formatter pour récupérer les données
        if (!class_exists('Sisme_Game_Data_Formatter')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/game-data-formatter.php';
        }
        
        $game_data = Sisme_Game_Data_Formatter::format_game_data($term_id);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Content Filter] Post {$post_id} : Données formatées depuis term_id {$term_id}");
        }
        
        return $game_data;
    }
}*/