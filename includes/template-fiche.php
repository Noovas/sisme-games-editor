<?php
/**
 * File: /sisme-games-editor/includes/template-fiche.php
 * Template de génération de contenu pour les fiches de jeu
 * Version adaptée aux nouvelles Game Data
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Fiche_Template {
    
    /**
     * Générer le contenu complet d'une fiche de jeu
     * 
     * @param int $post_id ID de l'article
     * @return string Contenu HTML de la fiche
     */
    public static function generate_fiche_content($post_id) {
        // Récupérer les tags du jeu
        $game_tags = wp_get_post_tags($post_id);
        if (empty($game_tags)) {
            return '';
        }
        
        $tag_id = $game_tags[0]->term_id;
        $game_name = $game_tags[0]->name;
        
        // Récupérer les Game Data depuis term_meta
        $game_data = self::get_game_data_from_tag($tag_id);
        
        // Récupérer les sections depuis post_meta
        $sections = get_post_meta($post_id, '_sisme_game_sections', true) ?: array();
        
        // Générer le contenu
        $content = '';
        
        // 1. Informations du jeu
        $content .= self::render_game_info($game_data);
        
        // 2. Sections de présentation
        if (!empty($sections)) {
            $content .= self::render_game_sections($sections);
        }
        
        // 3. Liens boutiques
        $content .= self::render_store_links($game_data['external_links']);
        
        // 4. Blocs de navigation croisée
        $content .= self::render_navigation_blocks($tag_id, $game_name, $game_data['covers']);
        
        return $content;
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
            'covers' => array(
                'main' => get_term_meta($tag_id, 'cover_main', true),
                'news' => get_term_meta($tag_id, 'cover_news', true),
                'patch' => get_term_meta($tag_id, 'cover_patch', true),
                'test' => get_term_meta($tag_id, 'cover_test', true),
            )
        );
    }
    
    /**
     * Afficher les informations du jeu
     */
    private static function render_game_info($game_data) {
        $output = '<div class="sisme-fiche-informations">';
        $output .= '<h2>Informations sur le jeu</h2>';
        $output .= '<div class="informations-content">';
        
        // Description
        if (!empty($game_data['description'])) {
            $output .= '<div class="game-description">';
            $output .= wp_kses_post($game_data['description']);
            $output .= '</div>';
        }
        
        // Grille d'informations
        $output .= '<div class="game-info-grid">';
        
        // Genres
        if (!empty($game_data['genres'])) {
            $output .= '<div class="info-item">';
            $output .= '<strong>Genres :</strong> ';
            $genre_names = array();
            foreach ($game_data['genres'] as $genre_id) {
                $genre = get_category($genre_id);
                if ($genre && !is_wp_error($genre)) {
                    $genre_names[] = str_replace('jeux-', '', $genre->name);
                }
            }
            $output .= esc_html(implode(', ', $genre_names));
            $output .= '</div>';
        }
        
        // Modes de jeu
        if (!empty($game_data['modes'])) {
            $output .= '<div class="info-item">';
            $output .= '<strong>Modes de jeu :</strong> ';
            $mode_labels = array(
                'solo' => 'Solo',
                'multijoueur' => 'Multijoueur',
                'cooperatif' => 'Coopératif',
                'competitif' => 'Compétitif'
            );
            $mode_names = array();
            foreach ($game_data['modes'] as $mode) {
                $mode_names[] = $mode_labels[$mode] ?? $mode;
            }
            $output .= esc_html(implode(', ', $mode_names));
            $output .= '</div>';
        }
        
        // Plateformes
        if (!empty($game_data['platforms'])) {
            $output .= '<div class="info-item">';
            $output .= '<strong>Plateformes :</strong> ';
            $platform_labels = array(
                'windows' => 'Windows',
                'mac' => 'Mac',
                'xbox' => 'Xbox',
                'playstation' => 'PlayStation',
                'switch' => 'Nintendo Switch',
                'ios' => 'iOS',
                'android' => 'Android',
                'web' => 'Navigateur'
            );
            $platform_names = array();
            foreach ($game_data['platforms'] as $platform) {
                $platform_names[] = $platform_labels[$platform] ?? $platform;
            }
            $output .= esc_html(implode(', ', $platform_names));
            $output .= '</div>';
        }
        
        // Date de sortie
        if (!empty($game_data['release_date'])) {
            $output .= '<div class="info-item">';
            $output .= '<strong>Date de sortie :</strong> ';
            $output .= esc_html(date('d/m/Y', strtotime($game_data['release_date'])));
            $output .= '</div>';
        }
        
        // Développeurs
        if (!empty($game_data['developers'])) {
            $output .= '<div class="info-item">';
            $output .= '<strong>Développeur(s) :</strong> ';
            $dev_names = array();
            foreach ($game_data['developers'] as $dev_id) {
                $dev = get_category($dev_id);
                if ($dev && !is_wp_error($dev)) {
                    $dev_names[] = $dev->name;
                }
            }
            $output .= esc_html(implode(', ', $dev_names));
            $output .= '</div>';
        }
        
        // Éditeurs
        if (!empty($game_data['publishers'])) {
            $output .= '<div class="info-item">';
            $output .= '<strong>Éditeur(s) :</strong> ';
            $pub_names = array();
            foreach ($game_data['publishers'] as $pub_id) {
                $pub = get_category($pub_id);
                if ($pub && !is_wp_error($pub)) {
                    $pub_names[] = $pub->name;
                }
            }
            $output .= esc_html(implode(', ', $pub_names));
            $output .= '</div>';
        }
        
        $output .= '</div>'; // .game-info-grid
        $output .= '</div>'; // .informations-content
        $output .= '</div>'; // .sisme-fiche-informations
        
        return $output;
    }
    
    /**
     * Afficher les sections de présentation
     */
    private static function render_game_sections($sections) {
        $output = '<div class="sisme-fiche-presentation">';
        $output .= '<h2>Présentation complète du jeu</h2>';
        $output .= '<div class="presentation-content">';
        
        foreach ($sections as $section) {
            if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])) {
                $output .= '<div class="game-section">';
                
                if (!empty($section['title'])) {
                    $output .= '<h3>' . esc_html($section['title']) . '</h3>';
                }
                
                if (!empty($section['content'])) {
                    $output .= wpautop($section['content']);
                }
                
                if (!empty($section['image_id'])) {
                    $image = wp_get_attachment_image($section['image_id'], 'large');
                    if ($image) {
                        $output .= '<div class="game-section-image">' . $image . '</div>';
                    }
                }
                
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Afficher les liens boutiques
     */
    private static function render_store_links($external_links) {
        if (empty($external_links)) {
            return '';
        }
        
        $output = '<div class="sisme-fiche-stores">';
        $output .= '<h2>Où l\'acheter</h2>';
        $output .= '<div class="sisme-store-links">';
        
        if (!empty($external_links['steam'])) {
            $output .= '<a href="' . esc_url($external_links['steam']) . '" target="_blank" rel="noopener">';
            $output .= '<img src="' . SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/images/steam-logo.png" alt="Disponible sur Steam">';
            $output .= '</a>';
        }
        
        if (!empty($external_links['epic_games'])) {
            $output .= '<a href="' . esc_url($external_links['epic_games']) . '" target="_blank" rel="noopener">';
            $output .= '<img src="' . SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/images/epic-logo.png" alt="Disponible sur Epic Games">';
            $output .= '</a>';
        }
        
        if (!empty($external_links['gog'])) {
            $output .= '<a href="' . esc_url($external_links['gog']) . '" target="_blank" rel="noopener">';
            $output .= '<img src="' . SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/images/gog-logo.png" alt="Disponible sur GOG">';
            $output .= '</a>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Afficher les blocs de navigation croisée
     */
    private static function render_navigation_blocks($tag_id, $game_name, $covers) {
        // Vérifier qu'on a les images nécessaires
        if (empty($covers['main']) || empty($covers['news'])) {
            return '';
        }
        
        $game_slug = get_term($tag_id)->slug;
        
        // URLs des liens
        $fiche_url = home_url('/' . $game_slug . '/');
        $news_url = home_url('/' . $game_slug . '-news/');
        $test_url = home_url('/' . $game_slug . '-test/');
        
        // Images
        $fiche_image = wp_get_attachment_image_url($covers['main'], 'medium');
        $news_image = wp_get_attachment_image_url($covers['news'], 'medium');
        $test_image = !empty($covers['test']) ? wp_get_attachment_image_url($covers['test'], 'medium') : $news_image;
        
        $output = '<div class="sisme-fiche-blocks">';
        $output .= '<h2>Découvrir ' . esc_html($game_name) . '</h2>';
        $output .= '<div class="sisme-blocks-grid">';
        
        // Bloc News
        $output .= '<div class="sisme-block sisme-news-block">';
        $output .= '<a href="' . esc_url($news_url) . '" class="sisme-block-wrapper">';
        $output .= '<div class="sisme-block-image">';
        $output .= '<img src="' . esc_url($news_image) . '" alt="Actualités ' . esc_attr($game_name) . '">';
        $output .= '</div>';
        $output .= '<div class="sisme-block-content">';
        $output .= '<h4 class="sisme-block-title">Actualités de ' . esc_html($game_name) . '</h4>';
        $output .= '<p class="sisme-block-description">Suivez toutes les news, patches et mises à jour du jeu.</p>';
        $output .= '</div>';
        $output .= '</a>';
        $output .= '</div>';
        
        // Bloc Test
        $output .= '<div class="sisme-block sisme-test-block">';
        $output .= '<a href="' . esc_url($test_url) . '" class="sisme-block-wrapper">';
        $output .= '<div class="sisme-block-image">';
        $output .= '<img src="' . esc_url($test_image) . '" alt="Test ' . esc_attr($game_name) . '">';
        $output .= '</div>';
        $output .= '<div class="sisme-block-content">';
        $output .= '<h4 class="sisme-block-title">Test de ' . esc_html($game_name) . '</h4>';
        $output .= '<p class="sisme-block-description">Découvrez notre test complet avec analyse détaillée.</p>';
        $output .= '</div>';
        $output .= '</a>';
        $output .= '</div>';
        
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