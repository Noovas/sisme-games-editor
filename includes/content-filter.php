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
        
        if ($this->is_game_fiche($post_id)) {
            // NOUVEAU : Vérifier si créé avec le nouveau système
            $created_with_new_system = get_post_meta($post_id, '_sisme_created_with_game_page_creator', true);
            
            if ($created_with_new_system) {
                // ✅ Nouveau système : Laisser passer le contenu déjà généré
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Content Filter] Post {$post_id} : Nouveau système détecté, contenu préservé");
                }
                return $content;
            } else {
                // ✅ Ancien système : Utiliser l'ancien template
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Content Filter] Post {$post_id} : Ancien système, génération template");
                }
                
                if (!class_exists('Sisme_Fiche_Template')) {
                    require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/template-fiche.php';
                }
                return Sisme_Fiche_Template::generate_fiche_content($post_id);
            }
        }
        
        return $content;
    }
    
    private function is_game_fiche($post_id) {
        $game_sections = get_post_meta($post_id, '_sisme_game_sections', true);
        return !empty($game_sections);
    }
}