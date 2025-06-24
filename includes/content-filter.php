<?php
/**
 * File: /sisme-games-editor/includes/content-filter.php
 * SIMPLE - Uniquement hero-section pour les fiches
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