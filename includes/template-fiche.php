<?php
/**
 * File: /sisme-games-editor/includes/template-fiche.php
 * Template de génération de contenu pour les fiches de jeu
 * Version modulaire avec fichiers CSS séparés
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure les modules frontend
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/frontend/hero-section-module.php';


class Sisme_Fiche_Template {
    
    /**
     * Générer le contenu complet d'une fiche de jeu
     * 
     * @param int $post_id ID de l'article
     * @return string Contenu HTML de la fiche
     */
    public static function generate_fiche_content($post_id) {
        $game_tags = wp_get_post_tags($post_id);
        if (empty($game_tags)) {
            return '';
        }
        $tag_id = $game_tags[0]->term_id;
        $game_data = Sisme_Utils_Games::get_game_data($tag_id);
        $sections = get_post_meta($post_id, '_sisme_game_sections', true) ?: array();
        self::enqueue_frontend_styles();
        $content = Sisme_Hero_Section_Module::render($game_data, $sections);
        return $content;
    }
    
    /**
     * Charger les styles CSS frontend
     * NOTE: Cette fonction est maintenant gérée par assets-loader.php
     * mais on la garde pour la rétrocompatibilité
     */
    private static function enqueue_frontend_styles() {
        // Les CSS sont maintenant chargés automatiquement par assets-loader.php
        // selon le contexte (is_game_fiche() détecte _sisme_game_sections)
        
        // Cette fonction peut être utilisée pour forcer le chargement si nécessaire
        if (!wp_style_is('sisme-frontend-tokens', 'enqueued')) {
            wp_enqueue_style(
                'sisme-frontend-tokens',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }
    }
    
    /**
     * Mettre à jour le contenu d'un article avec le template
     */
    public static function update_post_content($post_id) {
        $content = self::generate_fiche_content($post_id);
        
        if (!empty($content)) {
            wp_update_post(array(
                Sisme_Utils_Games::KEY_ID => $post_id,
                'post_content' => $content
            ));
        }
        
        return $content;
    }
}