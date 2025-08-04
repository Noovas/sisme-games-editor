<?php
/**
 * File: /sisme-games-editor/includes/game-page-creator/game-page-creator.php
 * API principale du module Game Page Creator
 * 
 * RESPONSABILITÉ:
 * - Point d'entrée unique pour la génération de pages de jeu
 * - Orchestration des modules internes
 * - Interface publique simplifiée
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

class Sisme_Game_Page_Creator {
    
    /**
     * Créer la page HTML complète d'un jeu
     * 
     * @param int $term_id ID du terme jeu (post_tag)
     * @return string|false HTML de la page ou false si erreur
     */
    public static function create_page($term_id) {
        if (!is_numeric($term_id) || $term_id <= 0) {
            return false;
        }
        
        $game_data = Sisme_Game_Data_Formatter::format_game_data($term_id);
        if (!$game_data) {
            return false;
        }
        
        return Sisme_Game_Page_Renderer::render($game_data);
    }
    
    /**
     * Vérifier si un jeu existe et peut générer une page
     * 
     * @param int $term_id ID du terme jeu
     * @return bool Jeu existe et valide
     */
    public static function can_create_page($term_id) {
        if (!is_numeric($term_id) || $term_id <= 0) {
            return false;
        }
        
        $term = get_term($term_id, 'post_tag');
        return $term && !is_wp_error($term);
    }
    
    /**
     * Créer une page avec données pré-formatées (usage avancé)
     * 
     * @param array $game_data Données formatées du jeu
     * @return string HTML de la page
     */
    public static function create_page_from_data($game_data) {
        return Sisme_Game_Page_Renderer::render($game_data);
    }
    
    /**
     * Obtenir les données formatées d'un jeu (sans rendu)
     * 
     * @param int $term_id ID du terme jeu
     * @return array|false Données formatées ou false si erreur
     */
    public static function get_formatted_data($term_id) {
        return Sisme_Game_Data_Formatter::format_game_data($term_id);
    }
}