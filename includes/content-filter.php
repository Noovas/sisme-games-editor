<?php
/**
 * File: /sisme-games-editor/includes/content-filter.php
 */

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
     */
    private function load_game_data_from_meta($post_id) {
        // Récupérer les données stockées par le Game Page Creator
        $stored_data = get_post_meta($post_id, '_sisme_game_page_data', true);
        
        if (empty($stored_data)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Content Filter] Post {$post_id} : Aucune donnée _sisme_game_page_data trouvée");
            }
            return array();
        }
        
        // Les données sont déjà formatées pour le renderer
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Content Filter] Post {$post_id} : Données chargées - " . wp_json_encode(array_keys($stored_data)));
        }
        
        return $stored_data;
    }
}