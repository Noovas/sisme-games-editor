<?php
/**
 * File: /sisme-games-editor/includes/frontend/hero-section-module.php
 * Module de génération de la Hero Section pour les fiches de jeu
 * Génère uniquement le HTML, CSS dans fichier séparé
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Hero_Section_Module {
    
    /**
     * Générer la Hero Section HTML
     * 
     * @param array $game_data Données du jeu depuis Game Data
     * @return string HTML de la hero section
     */
    public static function render($game_data) {
        $output = '<div class="sisme-game-hero">';
        
        // Colonne gauche - Média
        $output .= '<div class="sisme-game-media">';
        $output .= self::render_media_gallery($game_data);
        $output .= '</div>';
        
        // Colonne droite - Infos
        $output .= '<div class="sisme-game-info">';
        $output .= self::render_game_info($game_data);
        $output .= '</div>';
        
        $output .= '</div>';
        
        // JavaScript pour la galerie interactive
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
                $output .= '<iframe id="sismeTrailerFrame" ';
                $output .= 'src="https://www.youtube.com/embed/' . esc_attr($youtube_id) . '" ';
                $output .= 'allowfullscreen></iframe>';
            }
        }
        
        // Image de screenshot (cachée par défaut)
        $output .= '<img id="sismeScreenshotImg" src="" alt="Screenshot" style="display: none;">';
        
        $output .= '</div>';
        
        // Galerie de navigation
        $output .= '<div class="sisme-media-gallery">';
        
        // Trailer en premier (si disponible)
        if (!empty($game_data['screenshots'])) {
            $screenshots_array = is_array($game_data['screenshots']) ? 
                $game_data['screenshots'] : 
                explode(',', $game_data['screenshots']);
            
            foreach ($screenshots_array as $screenshot_id) {
                $screenshot_id = intval(trim($screenshot_id)); // Nettoyer l'ID
                
                $screenshot_url = wp_get_attachment_image_url($screenshot_id, 'large');
                $thumbnail_url = wp_get_attachment_image_url($screenshot_id, 'thumbnail');
                
                if ($screenshot_url && $thumbnail_url) {
                    $output .= '<div class="sisme-media-thumb" ';
                    $output .= 'data-type="screenshot" data-image="' . esc_url($screenshot_url) . '">';
                    $output .= '<img src="' . esc_url($thumbnail_url) . '" alt="Screenshot">';
                    $output .= '</div>';
                }
            }
        }
        
        // Screenshots
        if (!empty($game_data['screenshots'])) {
            foreach ($game_data['screenshots'] as $screenshot_id) {
                $screenshot_url = wp_get_attachment_image_url($screenshot_id, 'large');
                $thumbnail_url = wp_get_attachment_image_url($screenshot_id, 'thumbnail');
                
                if ($screenshot_url && $thumbnail_url) {
                    $output .= '<div class="sisme-media-thumb" ';
                    $output .= 'data-type="screenshot" data-image="' . esc_url($screenshot_url) . '">';
                    $output .= '<img src="' . esc_url($thumbnail_url) . '" alt="Screenshot">';
                    $output .= '</div>';
                }
            }
        }
        
        $output .= '</div>';

         
        
        return $output;
    }
    
    /**
     * Générer les informations du jeu
     */
    private static function render_game_info($game_data) {
        $output = '';
        
        // Titre
        $output .= '<h1 class="sisme-game-title">' . esc_html($game_data['title']) . '</h1>';
        
        // Description
        if (!empty($game_data['description'])) {
            $output .= '<p class="sisme-game-description">' . wp_kses_post($game_data['description']) . '</p>';
        }
        
        // Métadonnées
        $output .= '<div class="sisme-game-meta">';
        $output .= self::render_genres($game_data['genres']);
        $output .= self::render_platforms($game_data['platforms']);
        $output .= self::render_modes($game_data['modes']);
        $output .= self::render_developers($game_data['developers']);
        $output .= '</div>';
        
        // Actions et liens boutiques
        $output .= '<div class="sisme-game-actions">';
        $output .= '<div class="sisme-price-section">';
        $output .= self::render_release_date($game_data['release_date']);
        $output .= self::render_store_links($game_data['external_links']);
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les genres
     */
    private static function render_genres($genres) {
        if (empty($genres)) return '';
        
        $output = '<div class="sisme-meta-row">';
        $output .= '<span class="sisme-meta-label">Genres :</span>';
        $output .= '<div class="sisme-game-tags">';
        
        foreach ($genres as $genre_id) {
            $genre = get_category($genre_id);
            if ($genre) {
                $output .= '<span class="sisme-tag">' . esc_html($genre->name) . '</span>';
            }
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les plateformes
     */
    private static function render_platforms($platforms) {
        if (empty($platforms)) return '';
        
        $platform_icons = array(
            'windows' => 'W',
            'mac' => 'M',
            'xbox' => 'X',
            'playstation' => 'P',
            'switch' => 'S',
            'ios' => 'i',
            'android' => 'A',
            'web' => '@'
        );
        
        $output = '<div class="sisme-meta-row">';
        $output .= '<span class="sisme-meta-label">Plateformes :</span>';
        $output .= '<div class="sisme-platforms">';
        
        foreach ($platforms as $platform) {
            if (isset($platform_icons[$platform])) {
                $output .= '<div class="sisme-platform-icon" title="' . esc_attr(ucfirst($platform)) . '">';
                $output .= $platform_icons[$platform];
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les modes de jeu
     */
    private static function render_modes($modes) {
        if (empty($modes)) return '';
        
        $output = '<div class="sisme-meta-row">';
        $output .= '<span class="sisme-meta-label">Modes :</span>';
        $output .= '<span class="sisme-meta-value">' . esc_html(implode(', ', $modes)) . '</span>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les développeurs
     */
    private static function render_developers($developers) {
        if (empty($developers)) return '';
        
        $output = '<div class="sisme-meta-row">';
        $output .= '<span class="sisme-meta-label">Développeur :</span>';
        $output .= '<div class="sisme-developer-info">';
        
        foreach ($developers as $dev_id) {
            $developer = get_category($dev_id);
            if ($developer) {
                $website = get_term_meta($dev_id, 'entity_website', true);
                if ($website) {
                    $output .= '<a href="' . esc_url($website) . '" target="_blank">' . esc_html($developer->name) . '</a>';
                } else {
                    $output .= esc_html($developer->name);
                }
            }
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer la date de sortie
     */
    private static function render_release_date($release_date) {
        if (empty($release_date)) return '';
        
        $formatted_date = date('j F Y', strtotime($release_date));
        return '<div class="sisme-release-date">Sorti le ' . esc_html($formatted_date) . '</div>';
    }
    
    /**
     * Générer les liens boutiques
     */
    private static function render_store_links($external_links) {
        if (empty($external_links)) return '';
        
        $output = '<div class="sisme-store-links">';
        
        if (!empty($external_links['steam'])) {
            $output .= '<a href="' . esc_url($external_links['steam']) . '" ';
            $output .= 'class="sisme-store-btn steam" target="_blank">';
            $output .= '<span>🎮</span> Voir sur Steam';
            $output .= '</a>';
        }
        
        if (!empty($external_links['epic_games'])) {
            $output .= '<a href="' . esc_url($external_links['epic_games']) . '" ';
            $output .= 'class="sisme-store-btn epic" target="_blank">';
            $output .= '<span>🏪</span> Epic Games Store';
            $output .= '</a>';
        }
        
        if (!empty($external_links['gog'])) {
            $output .= '<a href="' . esc_url($external_links['gog']) . '" ';
            $output .= 'class="sisme-store-btn gog" target="_blank">';
            $output .= '<span>🎯</span> GOG.com';
            $output .= '</a>';
        }
        
        $output .= '</div>';
        
        return $output;
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
            
            if (!mainDisplay || thumbs.length === 0) return;
            
            thumbs.forEach(thumb => {
                thumb.addEventListener("click", function() {
                    thumbs.forEach(t => t.classList.remove("active"));
                    this.classList.add("active");
                    
                    const type = this.dataset.type;
                    
                    if (type === "trailer" && trailerFrame) {
                        trailerFrame.style.display = "block";
                        if (screenshotImg) screenshotImg.style.display = "none";
                        
                        const youtubeId = this.dataset.youtube;
                        if (youtubeId) {
                            trailerFrame.src = `https://www.youtube.com/embed/${youtubeId}`;
                        }
                    } else if (type === "screenshot" && screenshotImg) {
                        if (trailerFrame) trailerFrame.style.display = "none";
                        screenshotImg.style.display = "block";
                        screenshotImg.src = this.dataset.image;
                    }
                });
            });
        });
        </script>';
    }
    
    /**
     * Extraire l'ID YouTube depuis une URL
     */
    private static function extract_youtube_id($url) {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        preg_match($pattern, $url, $matches);
        return isset($matches[1]) ? $matches[1] : null;
    }
}