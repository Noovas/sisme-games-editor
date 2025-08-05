<?php
/**
 * File: /sisme-games-editor/includes/game-page-creator/game-page-content-filter.php
 * Filtre de contenu pour le système Game Page Creator
 * 
 * RESPONSABILITÉ:
 * - Remplacer le contenu des fiches de jeu par le rendu dynamique
 * - Détection automatique des pages créées avec le nouveau système
 * - Chargement et formatage des données depuis term_meta
 * 
 * DÉPENDANCES:
 * - game-data-formatter.php
 * - game-page-renderer.php
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/game-data-formatter.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/game-page-renderer.php';

class Sisme_Game_Page_Content_Filter {
    
    /**
     * Initialiser le filtre de contenu
     */
    public static function init() {
        add_filter('the_content', array(__CLASS__, 'process_content'));
    }
    
    /**
     * Traiter le contenu des pages
     * 
     * @param string $content Contenu original
     * @return string Contenu modifié ou original
     */
    public static function process_content($content) {
        global $post;
        if (!$post || !is_single()) {
            return $content;
        }
        $post_id = $post->ID; 
        return self::render_game_page($post_id);
    }
    
    /**
     * Générer le rendu d'une page de jeu
     * 
     * @param int $post_id ID du post
     * @return string HTML généré
     */
    private static function render_game_page($post_id) {
        $term_id = self::get_game_term_id($post_id);
        if (!$term_id) {
            return '<p>Erreur : Impossible de charger les données du jeu.</p>';
        }
        $game_data = Sisme_Game_Data_Formatter::format_game_data($term_id);
        if (!$game_data) {
            return '<p>Erreur : Impossible de formater les données du jeu.</p>';
        }
        $html = Sisme_Game_Page_Renderer::render($game_data);        
        return $html;
    }
    
    /**
     * Récupérer le term_id du jeu depuis les tags du post
     * 
     * @param int $post_id ID du post
     * @return int|false Term ID ou false si non trouvé
     */
    private static function get_game_term_id($post_id) {
        $tags = wp_get_post_tags($post_id);
        if (empty($tags)) {
            return false;
        }
        return $tags[0]->term_id;
    }
}
