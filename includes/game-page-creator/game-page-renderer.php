<?php
/**
 * File: /sisme-games-editor/includes/game-page-creator/game-page-renderer.php
 * Rendu HTML exact de la page de jeu
 * 
 * RESPONSABILITÉ:
 * - Génération HTML strictement identique à la structure existante
 * - Utilisation des mêmes classes CSS
 * - Intégration des User Actions
 * - JavaScript pour galerie interactive
 * 
 * DÉPENDANCES:
 * - user-actions-api.php (pour les boutons favoris/possédé)
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-actions/user-actions-api.php';

class Sisme_Game_Page_Renderer {
    
    /**
     * Générer le HTML complet de la page de jeu
     * 
     * @param array $game_data Données formatées du jeu
     * @return string HTML de la page
     */
    public static function render($game_data) {
        $output = '<div class="sisme-game-hero">';
        
        // COLONNE GAUCHE - MÉDIA + SECTIONS
        $output .= '<div class="sisme-game-media">';
        $output .= self::render_media_gallery($game_data);
        
        // SECTIONS DESCRIPTIVES (Si sections disponibles)
        if (!empty($game_data['sections'])) {
            $output .= self::render_game_sections($game_data['sections']);
        }
        
        $output .= '</div>';
        
        // COLONNE DROITE - INFOS
        $output .= '<div class="sisme-game-info">';
        $output .= self::render_game_info($game_data);
        $output .= '</div>';
        
        $output .= '</div>';
        
        // JAVASCRIPT POUR GALERIE INTERACTIVE
        $output .= self::render_javascript();
        
        return $output;
    }
    
    /**
     * Générer la galerie média (trailer + screenshots)
     */
    private static function render_media_gallery($game_data) {
        $output = '';
        
        // Zone d'affichage principal
        $output .= '<div class="sisme-main-media-display" id="sismeMainDisplay">';
        
        // Trailer par défaut
        if (!empty($game_data['trailer_link'])) {
            $youtube_id = self::extract_youtube_id($game_data['trailer_link']);
            if ($youtube_id) {
                $output .= '<iframe id="sismeTrailerFrame" src="https://www.youtube.com/embed/' . esc_attr($youtube_id) . '?enablejsapi=1" allowfullscreen></iframe>';
            }
        }
        
        // Image de screenshot (cachée par défaut)
        $output .= '<img id="sismeScreenshotImg" src="" alt="Screenshot" style="display: none;">';
        
        $output .= '</div>';
        
        // Galerie de navigation
        $output .= '<div class="sisme-media-gallery">';
        
        // Thumbnail Trailer (Premier élément, actif par défaut)
        if (!empty($game_data['trailer_link'])) {
            $youtube_id = self::extract_youtube_id($game_data['trailer_link']);
            if ($youtube_id) {
                $output .= '<div class="sisme-media-thumb trailer active" data-type="trailer" data-youtube="' . esc_attr($youtube_id) . '">';
                $output .= '<img src="https://img.youtube.com/vi/' . esc_attr($youtube_id) . '/maxresdefault.jpg" alt="Trailer">';
                $output .= '</div>';
            }
        }
        
        // Thumbnails Screenshots (Multiples)
        if (!empty($game_data['screenshots'])) {
            foreach ($game_data['screenshots'] as $screenshot) {
                $output .= '<div class="sisme-media-thumb" data-type="screenshot" data-image="' . esc_attr($screenshot['url']) . '">';
                $output .= '<img src="' . esc_attr($screenshot['thumbnail']) . '" alt="' . esc_attr($screenshot['alt']) . '">';
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les sections descriptives
     */
    private static function render_game_sections($sections) {
        $output = '<div class="sisme-game-sections">';
        $output .= '<h2>Présentation complète du jeu</h2>';
        
        foreach ($sections as $section) {
            $output .= '<div class="sisme-game-section">';
            $output .= '<h3>' . esc_html($section['title']) . '</h3>';
            $output .= $section['content']; // Déjà formaté avec wpautop()
            
            if (!empty($section['image_url'])) {
                $output .= '<div class="sisme-game-section-image">';
                $output .= '<img class="sisme-section-image" src="' . esc_attr($section['image_url']) . '" alt="">';
                $output .= '</div>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les informations du jeu (colonne droite)
     */
    private static function render_game_info($game_data) {
        $output = '';
        
        // Titre
        $output .= '<h1 class="sisme-game-title">' . esc_html($game_data['name']) . '</h1>';
        
        // Description
        if (!empty($game_data['description'])) {
            $output .= '<p class="sisme-game-description">' . esc_html($game_data['description']) . '</p>';
        }
        
        // USER ACTIONS
        $output .= self::render_user_actions($game_data);
        
        // MÉTADONNÉES DU JEU
        $output .= '<div class="sisme-game-meta-container" id="sismeGameMeta">';
        $output .= '<h2 class="sisme-meta-title">Informations du jeu</h2>';
        $output .= '<div class="sisme-game-meta">';
        
        // GENRES
        if (!empty($game_data['genres'])) {
            $output .= self::render_genres($game_data['genres']);
        }
        
        // PLATEFORMES
        if (!empty($game_data['platforms'])) {
            $output .= self::render_platforms($game_data['platforms']);
        }
        
        // MODES DE JEU
        if (!empty($game_data['modes'])) {
            $output .= self::render_modes($game_data['modes']);
        }
        
        // DÉVELOPPEURS
        if (!empty($game_data['developers'])) {
            $output .= self::render_developers($game_data['developers']);
        }
        
        // ÉDITEURS
        if (!empty($game_data['publishers'])) {
            $output .= self::render_publishers($game_data['publishers']);
        }
        
        // DATE DE SORTIE
        if (!empty($game_data['release_date'])) {
            $output .= self::render_release_date($game_data['release_date']);
        }
        
        // LIENS BOUTIQUES
        if (!empty($game_data['external_links'])) {
            $output .= self::render_store_links($game_data['external_links']);
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les boutons d'actions utilisateur
     */
    private static function render_user_actions($game_data) {
        $output = '<div class="sisme-user-actions sisme-user-action-fiches">';
        
        if (class_exists('Sisme_User_Actions_API')) {
            $favorite_button = Sisme_User_Actions_API::render_action_button(
                $game_data['id'],
                'favorite',
                array(
                    'size' => 'medium',
                    'show_text' => false,
                    'show_count' => true
                )
            );
            
            $owned_button = Sisme_User_Actions_API::render_action_button(
                $game_data['id'],
                'owned',
                array(
                    'size' => 'medium',
                    'show_text' => false,
                    'show_count' => true
                )
            );
            
            $output .= $favorite_button;
            $output .= $owned_button;
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les genres
     */
    private static function render_genres($genres) {
        $output = '<div class="sisme-meta-row">';
        $output .= '<span class="sisme-meta-label">Genres</span>';
        $output .= '<div class="sisme-game-tags">';
        
        foreach ($genres as $genre) {
            $output .= '<a href="' . esc_url($genre['url']) . '" class="sisme-badge sisme-badge-genre" title="Voir tous les jeux de type ' . esc_attr($genre['name']) . '">';
            $output .= esc_html($genre['name']);
            $output .= '</a>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les plateformes
     */
    private static function render_platforms($platforms) {
        $output = '<div class="sisme-meta-row">';
        $output .= '<span class="sisme-meta-label">Plateformes</span>';
        $output .= '<div class="sisme-platforms">';
        
        foreach ($platforms as $platform) {
            $output .= '<span class="sisme-badge-platform" title="' . esc_attr($platform['tooltip']) . '">';
            $output .= $platform['icon'];
            $output .= '</span>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les modes de jeu
     */
    private static function render_modes($modes) {
        $output = '<div class="sisme-meta-row">';
        $output .= '<span class="sisme-meta-label">Modes</span>';
        $output .= '<div class="sisme-game-modes">';
        
        foreach ($modes as $mode) {
            $output .= '<span class="sisme-badge sisme-badge-mode ' . esc_attr($mode['key']) . '">';
            $output .= esc_html($mode['label']);
            $output .= '</span>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les développeurs
     */
    private static function render_developers($developers) {
        $output = '<div class="sisme-meta-row sisme-dev-publish-div">';
        $output .= '<span class="sisme-meta-label">Développeur</span>';
        $output .= '<div class="sisme-developer-info">';
        
        $dev_links = array();
        foreach ($developers as $developer) {
            if (!empty($developer['website'])) {
                $dev_links[] = '<a href="' . esc_url($developer['website']) . '" target="_blank">' . esc_html($developer['name']) . '</a>';
            } else {
                $dev_links[] = esc_html($developer['name']);
            }
        }
        
        $output .= implode(', ', $dev_links);
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les éditeurs
     */
    private static function render_publishers($publishers) {
        $output = '<div class="sisme-meta-row sisme-dev-publish-div">';
        $output .= '<span class="sisme-meta-label">Éditeur</span>';
        $output .= '<div class="sisme-publisher-info">';
        
        $pub_links = array();
        foreach ($publishers as $publisher) {
            if (!empty($publisher['website'])) {
                $pub_links[] = '<a href="' . esc_url($publisher['website']) . '" target="_blank">' . esc_html($publisher['name']) . '</a>';
            } else {
                $pub_links[] = esc_html($publisher['name']);
            }
        }
        
        $output .= implode(', ', $pub_links);
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer la date de sortie
     */
    private static function render_release_date($release_date) {
        $output = '<div class="sisme-meta-row">';
        $output .= '<span class="sisme-meta-label">Date de sortie</span>';
        $output .= '<span class="sisme-meta-value">' . esc_html($release_date) . '</span>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les liens boutiques
     */
    private static function render_store_links($external_links) {
        $output = '<div class="sisme-store-links">';
        
        foreach ($external_links as $platform => $link_data) {
            if ($link_data['available']) {
                $output .= '<a href="' . esc_url($link_data['url']) . '" class="sisme-store-icon" target="_blank" title="' . esc_attr($link_data['label']) . '">';
                $output .= '<img src="' . esc_attr($link_data['icon']) . '" alt="' . esc_attr($link_data['label']) . '">';
                $output .= '</a>';
            } else {
                $output .= '<div class="sisme-store-icon sisme-store-icon--disabled" title="Pas disponible">';
                $output .= '<img src="' . esc_attr($link_data['icon']) . '" alt="' . esc_attr($link_data['label']) . '">';
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Extraire l'ID YouTube depuis une URL
     */
    private static function extract_youtube_id($url) {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        preg_match($pattern, $url, $matches);
        return isset($matches[1]) ? $matches[1] : '';
    }
    
    /**
     * Générer le JavaScript pour la galerie interactive
     */
    private static function render_javascript() {
        return '<script>
document.addEventListener("DOMContentLoaded", function() {
    const mainDisplay = document.getElementById("sismeMainDisplay");
    const trailerFrame = document.getElementById("sismeTrailerFrame");
    const screenshotImg = document.getElementById("sismeScreenshotImg");
    const thumbs = document.querySelectorAll(".sisme-media-thumb");
    
    if (!mainDisplay || !thumbs.length) return;
    
    thumbs.forEach(function(thumb) {
        thumb.addEventListener("click", function() {
            // Retirer la classe active de tous les thumbnails
            thumbs.forEach(function(t) { t.classList.remove("active"); });
            
            // Ajouter la classe active au thumbnail cliqué
            this.classList.add("active");
            
            const type = this.getAttribute("data-type");
            
            if (type === "trailer") {
                const youtubeId = this.getAttribute("data-youtube");
                if (trailerFrame && youtubeId) {
                    trailerFrame.src = "https://www.youtube.com/embed/" + youtubeId + "?enablejsapi=1";
                    trailerFrame.style.display = "block";
                }
                if (screenshotImg) {
                    screenshotImg.style.display = "none";
                }
            } else if (type === "screenshot") {
                const imageUrl = this.getAttribute("data-image");
                if (screenshotImg && imageUrl) {
                    screenshotImg.src = imageUrl;
                    screenshotImg.style.display = "block";
                }
                if (trailerFrame) {
                    trailerFrame.style.display = "none";
                }
            }
        });
    });
});
</script>';
    }
}