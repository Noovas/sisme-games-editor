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
        $game_name = $game_tags[0]->name;
        $game_data = self::get_game_data_from_tag($tag_id);
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
     * Récupérer les Game Data depuis un tag
     */
    private static function get_game_data_from_tag($tag_id) {
        return array(
            'title' => get_term($tag_id)->name,
            'description' => get_term_meta($tag_id, 'game_description', true),
            'genres' => get_term_meta($tag_id, 'game_genres', true) ?: array(),
            'modes' => get_term_meta($tag_id, 'game_modes', true) ?: array(),
            'developers' => get_term_meta($tag_id, 'game_developers', true) ?: array(),
            'publishers' => get_term_meta($tag_id, 'game_publishers', true) ?: array(),
            'platforms' => get_term_meta($tag_id, 'game_platforms', true) ?: array(),
            'release_date' => get_term_meta($tag_id, 'release_date', true),
            'external_links' => get_term_meta($tag_id, 'external_links', true) ?: array(),
            'trailer_link' => get_term_meta($tag_id, 'trailer_link', true),
            'screenshots' => get_term_meta($tag_id, 'screenshots', true) ?: array(),
            'covers' => array(
                'main' => get_term_meta($tag_id, 'cover_main', true),
                'news' => get_term_meta($tag_id, 'cover_news', true),
                'patch' => get_term_meta($tag_id, 'cover_patch', true),
                'test' => get_term_meta($tag_id, 'cover_test', true)
            )
        );
    }
    
    /**
     * Générer les sections de présentation personnalisées
     */
    private static function render_game_sections($sections) {
        if (empty($sections)) {
            return '';
        }
        
        $output = '<div class="sisme-fiche-presentation">';
        $output .= '<h2>Présentation complète du jeu</h2>';
        
        foreach ($sections as $section) {
            if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])) {
                $output .= '<div class="sisme-game-section">';
                
                if (!empty($section['title'])) {
                    $output .= '<h3>' . esc_html($section['title']) . '</h3>';
                }
                
                if (!empty($section['content'])) {
                    $output .= wpautop(wp_kses_post($section['content']));
                }
                
                if (!empty($section['image_id'])) {
                    $image = wp_get_attachment_image($section['image_id'], 'large', false, array(
                        'class' => 'sisme-section-image'
                    ));
                    if ($image) {
                        $output .= '<div class="sisme-game-section-image">' . $image . '</div>';
                    }
                }
                
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les blocs de navigation croisée
     */
    private static function render_navigation_blocks($tag_id, $game_name, $game_data) {
        $game_slug = get_term($tag_id)->slug;
        
        // URLs des différents types de contenu
        $news_url = home_url('/tag/' . $game_slug . '/');
        $test_url = home_url('/tag/' . $game_slug . '/');
        
        // Images depuis les covers
        $news_image = !empty($game_data['covers']['news']) ? 
            wp_get_attachment_image_url($game_data['covers']['news'], 'medium') : '';
        $test_image = !empty($game_data['covers']['test']) ? 
            wp_get_attachment_image_url($game_data['covers']['test'], 'medium') : $news_image;
        
        // Si aucune image, ne pas afficher les blocs
        if (!$news_image && !$test_image) {
            return '';
        }
        
        $output = '<div class="sisme-navigation-blocks">';
        $output .= '<h2>Découvrir ' . esc_html($game_name) . '</h2>';
        $output .= '<div class="sisme-blocks-grid">';
        
        // Bloc News
        if ($news_image) {
            $output .= '<div class="sisme-nav-block sisme-news-block">';
            $output .= '<a href="' . esc_url($news_url) . '" class="sisme-block-link">';
            $output .= '<div class="sisme-block-image">';
            $output .= '<img src="' . esc_url($news_image) . '" alt="Actualités ' . esc_attr($game_name) . '">';
            $output .= '<div class="sisme-block-overlay">';
            $output .= '<span class="sisme-block-icon">📰</span>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '<div class="sisme-block-content">';
            $output .= '<h4 class="sisme-block-title">Actualités</h4>';
            $output .= '<p class="sisme-block-description">Suivez toutes les news, patches et mises à jour du jeu.</p>';
            $output .= '</div>';
            $output .= '</a>';
            $output .= '</div>';
        }
        
        // Bloc Test
        if ($test_image) {
            $output .= '<div class="sisme-nav-block sisme-test-block">';
            $output .= '<a href="' . esc_url($test_url) . '" class="sisme-block-link">';
            $output .= '<div class="sisme-block-image">';
            $output .= '<img src="' . esc_url($test_image) . '" alt="Test ' . esc_attr($game_name) . '">';
            $output .= '<div class="sisme-block-overlay">';
            $output .= '<span class="sisme-block-icon">⭐</span>';
            $output .= '</div>';
            $output .= '</div>';
            $output .= '<div class="sisme-block-content">';
            $output .= '<h4 class="sisme-block-title">Test complet</h4>';
            $output .= '<p class="sisme-block-description">Découvrez notre analyse détaillée et notre verdict.</p>';
            $output .= '</div>';
            $output .= '</a>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Mettre à jour le contenu d'un article avec le template
     */
    public static function update_post_content($post_id) {
        $content = self::generate_fiche_content($post_id);
        
        if (!empty($content)) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $content
            ));
        }
        
        return $content;
    }
}