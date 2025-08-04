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
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Game Page Content Filter] Filtre de contenu initialisé');
        }
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
        
        // Vérifier si c'est une fiche de jeu créée avec le nouveau système
        $created_with_new_system = get_post_meta($post_id, '_sisme_created_with_game_page_creator', true);
        
        if ($created_with_new_system) {
            return self::render_game_page($post_id);
        }
        
        // Fallback : détecter les anciennes fiches et les convertir (temporaire)
        if (self::is_legacy_game_fiche($post_id)) {
            return self::render_game_page($post_id);
        }
        
        return $content;
    }
    
    /**
     * Générer le rendu d'une page de jeu
     * 
     * @param int $post_id ID du post
     * @return string HTML généré
     */
    private static function render_game_page($post_id) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Game Page Content Filter] Rendu dynamique pour post {$post_id}");
        }
        
        // Récupérer le term_id depuis les tags du post
        $term_id = self::get_game_term_id($post_id);
        if (!$term_id) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Game Page Content Filter] Post {$post_id} : Aucun term_id trouvé");
            }
            return '<p>Erreur : Impossible de charger les données du jeu.</p>';
        }
        
        // Formater les données du jeu
        $game_data = Sisme_Game_Data_Formatter::format_game_data($term_id);
        if (!$game_data) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Game Page Content Filter] Post {$post_id} : Échec formatage données term_id {$term_id}");
            }
            return '<p>Erreur : Impossible de formater les données du jeu.</p>';
        }
        
        // Générer le HTML avec le renderer
        $html = Sisme_Game_Page_Renderer::render($game_data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Game Page Content Filter] Post {$post_id} : HTML généré avec succès");
        }
        
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
        
        // Pour l'instant on prend le premier tag
        // TODO: Améliorer la logique de détection du bon tag de jeu
        return $tags[0]->term_id;
    }
    
    /**
     * Détecter si c'est une ancienne fiche de jeu
     * 
     * @param int $post_id ID du post
     * @return bool Est une ancienne fiche de jeu
     */
    private static function is_legacy_game_fiche($post_id) {
        $game_sections = get_post_meta($post_id, '_sisme_game_sections', true);
        return !empty($game_sections);
    }
}
