<?php
/**
 * File: /sisme-games-editor/includes/content-filter.php
 */

if (!defined('ABSPATH')) {
    exit;
}
/*class Sisme_Content_Filter {
    
    public function __construct() {
    }
    
}*/


class Sisme_Content_Filter {
    
    public function __construct() {
        add_filter('the_content', array($this, 'process_content'));
    }
    
    public function process_content($content) {
        global $post;
        $post_id = $post->ID;
        
        // EXCLUSION : Si créé avec le nouveau système, on ignore complètement
        $created_with_new_system = get_post_meta($post_id, '_sisme_created_with_game_page_creator', true);
        if ($created_with_new_system) {
            // Le nouveau système gère tout, on ne touche à rien
            return $content;
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
}