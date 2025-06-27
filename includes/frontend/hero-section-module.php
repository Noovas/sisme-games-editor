<?php
/**
 * File: /sisme-games-editor/includes/frontend/hero-section-module.php
 * Module de génération de la Hero Section pour les fiches de jeu
 * Génère uniquement le HTML, CSS dans fichier séparé
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-actions/user-actions-api.php';


class Sisme_Hero_Section_Module {
    
    /**
     * Générer la Hero Section HTML
     * 
     * @param array $game_data Données du jeu depuis Game Data
     * @return string HTML de la hero section
     */
    public static function render($game_data, $sections = array()) {
        $output = '<div class="sisme-game-hero">';
        
        // Colonne gauche - Média + Sections
        $output .= '<div class="sisme-game-media">';
        $output .= self::render_media_gallery($game_data);
        
        // SECTIONS SOUS LA GALERIE
        if (!empty($sections)) {
            $output .= self::render_game_sections($sections);
        }
        
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
                $output .= 'src="https://www.youtube.com/embed/' . esc_attr($youtube_id) . '?enablejsapi=1" ';
                $output .= 'allowfullscreen></iframe>';
            }
        }
        
        // Image de screenshot (cachée par défaut)
        $output .= '<img id="sismeScreenshotImg" src="" alt="Screenshot" style="display: none;">';
        
        $output .= '</div>';
        
        // Galerie de navigation
        $output .= '<div class="sisme-media-gallery">';
        
        // TRAILER THUMBNAIL EN PREMIER (si disponible)
        if (!empty($game_data['trailer_link'])) {
            $youtube_id = self::extract_youtube_id($game_data['trailer_link']);
            if ($youtube_id) {
                $thumbnail_url = "https://img.youtube.com/vi/{$youtube_id}/maxresdefault.jpg";
                $output .= '<div class="sisme-media-thumb trailer active" ';
                $output .= 'data-type="trailer" data-youtube="' . esc_attr($youtube_id) . '">';
                $output .= '<img src="' . esc_url($thumbnail_url) . '" alt="Trailer">';
                $output .= '</div>';
            }
        }
        
        // SCREENSHOTS (une seule fois !)
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

        // User-actions
        $output .= self::render_user_actions($game_data);
        
        // TITRE + MÉTADONNÉES avec ID pour le scroll
        $output .= '<div class="sisme-game-meta-container" id="sismeGameMeta">';
        $output .= '<h2 class="sisme-meta-title">Informations du jeu</h2>'; // NOUVEAU TITRE
        $output .= '<div class="sisme-game-meta">';
        $output .= self::render_genres($game_data['genres']);
        $output .= self::render_platforms($game_data['platforms']);
        $output .= self::render_modes($game_data['modes']);
        $output .= self::render_developers($game_data['developers']);
        $output .= self::render_publishers($game_data['publishers']);
        $output .= self::render_release_date($game_data['release_date']);
        $output .= self::render_store_links($game_data['external_links']);
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }


    /**
     * Générer les user-actions
     */
    private static function render_user_actions($game_data) {
        $output = '';
        $output .= '<div class="sisme-user-actions sisme-user-action-fiches">';
        
        
        $button_html = Sisme_User_Actions_API::render_action_button(
            $game_data['id'],
            'favorite',
            [
                'size' => 'medium',
                'show_text' => false,
                'show_count' => true
            ]
        );
        $output .= $button_html;

        $button_html = Sisme_User_Actions_API::render_action_button(
            $game_data['id'],
            'owned',
            [
                'size' => 'medium',
                'show_text' => false,
                'show_count' => true
            ]
        );
        $output .= $button_html;

        $output .= '</div>';
        return $output;
    }
    
    /**
     * Générer les genres
     */
    private static function render_genres($genres) {
        if (empty($genres)) return '';
        
        $output = '<div class="sisme-meta-row">';
        $output .= '<span class="sisme-meta-label">Genres</span>';
        $output .= '<div class="sisme-game-tags">';
        
        foreach ($genres as $genre_id) {
            $genre = get_category($genre_id);
            if ($genre) {
                // 🔗 LIEN VERS LA PAGE D'ARCHIVE DE LA CATÉGORIE
                $genre_url = get_category_link($genre_id);
                $genre_name = str_replace('jeux-', '', $genre->name); // Nettoyer le nom
                
                $output .= '<a href="' . esc_url($genre_url) . '" class="sisme-badge sisme-badge-genre" ';
                $output .= 'title="Voir tous les jeux de type ' . esc_attr($genre_name) . '">';
                $output .= esc_html(ucfirst($genre_name));
                $output .= '</a>';
            }
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer les plateformes groupées par catégorie avec icônes et tooltips détaillés
     */
    private static function render_platforms($platforms) {
        if (empty($platforms)) return '';
        
        // Groupement des plateformes par catégorie
        $pc_platforms = array('windows', 'mac');
        $console_platforms = array('xbox', 'playstation', 'switch');
        $mobile_platforms = array('ios', 'android');
        
        // Noms complets pour les tooltips
        $platform_names = array(
            'windows' => 'Windows',
            'mac' => 'Mac',
            'xbox' => 'Xbox',
            'playstation' => 'PlayStation',
            'switch' => 'Nintendo Switch',
            'ios' => 'iOS',
            'android' => 'Android',
            'web' => 'Navigateur Web'
        );
        
        $output = '<div class="sisme-meta-row">';
        $output .= '<span class="sisme-meta-label">Plateformes</span>';
        $output .= '<div class="sisme-platforms">';
        
        // Vérifier et afficher PC
        $pc_found = array_intersect($platforms, $pc_platforms);
        if (!empty($pc_found)) {
            $pc_details = array_map(function($p) use ($platform_names) {
                return $platform_names[$p];
            }, $pc_found);
            
            $output .= '<span class="sisme-badge-platform" title="' . esc_attr(implode(', ', $pc_details)) . '">';
            $output .= '💻';
            $output .= '</span>';
        }
        
        // Vérifier et afficher Console
        $console_found = array_intersect($platforms, $console_platforms);
        if (!empty($console_found)) {
            $console_details = array_map(function($p) use ($platform_names) {
                return $platform_names[$p];
            }, $console_found);
            
            $output .= '<span class="sisme-badge-platform" title="' . esc_attr(implode(', ', $console_details)) . '">';
            $output .= '🎮'; // Icône Console
            $output .= '</span>';
        }
        
        // Vérifier et afficher Mobile
        $mobile_found = array_intersect($platforms, $mobile_platforms);
        if (!empty($mobile_found)) {
            $mobile_details = array_map(function($p) use ($platform_names) {
                return $platform_names[$p];
            }, $mobile_found);
            
            $output .= '<span class="sisme-badge-platform" title="' . esc_attr(implode(', ', $mobile_details)) . '">';
            $output .= '📱'; 
            $output .= '</span>';
        }
        
        // Web à part (si présent)
        if (in_array('web', $platforms)) {
            $output .= '<span class="sisme-badge-platform" title="' . esc_attr($platform_names['web']) . '">';
            $output .= '🌐';
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
        if (empty($modes)) return '';
        
        $output = '<div class="sisme-meta-row">';
        $output .= '<span class="sisme-meta-label">Modes</span>';
        $output .= '<div class="sisme-game-modes">';
        
        foreach ($modes as $mode) {
            $mode_clean = strtolower(trim($mode));
            $output .= '<span class="sisme-badge sisme-badge-mode ' . esc_attr($mode_clean) . '">';
            $output .= esc_html(ucfirst($mode));
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
        if (empty($developers)) return '';
        
        $output = '<div class="sisme-meta-row sisme-dev-publish-div">';
        $output .= '<span class="sisme-meta-label">Développeur</span>';
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
     * Générer les éditeurs/publishers
     */
    private static function render_publishers($publishers) {
        if (empty($publishers)) return '';
        
        $output = '<div class="sisme-meta-row sisme-dev-publish-div">';
        $output .= '<span class="sisme-meta-label">Éditeur</span>';
        $output .= '<div class="sisme-publisher-info">';
        
        $publisher_links = array();
        foreach ($publishers as $pub_id) {
            $publisher = get_category($pub_id);
            if ($publisher) {
                $website = get_term_meta($pub_id, 'entity_website', true);
                if ($website) {
                    $publisher_links[] = '<a href="' . esc_url($website) . '" target="_blank">' . esc_html($publisher->name) . '</a>';
                } else {
                    $publisher_links[] = esc_html($publisher->name);
                }
            }
        }
        
        $output .= implode('', $publisher_links);
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Générer la date de sortie en français pour les métadonnées
     */
    private static function render_release_date($release_date) {
        if (empty($release_date)) return '';
        
        // Mois en français
        $mois = array(
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
        );
        
        $date = DateTime::createFromFormat('Y-m-d', $release_date);
        if ($date) {
            $jour = $date->format('j');
            $mois_num = (int)$date->format('n');
            $annee = $date->format('Y');
            
            $date_fr = $jour . ' ' . $mois[$mois_num] . ' ' . $annee;
            
            $output = '<div class="sisme-meta-row">';
            $output .= '<span class="sisme-meta-label">Date de sortie</span>';
            $output .= '<span class="sisme-meta-value">' . esc_html($date_fr) . '</span>';
            $output .= '</div>';
            
            return $output;
        }
        
        return '';
    }
    
    /**
     * Générer les liens boutiques
     */
    private static function render_store_links($external_links) {
        $output = '<div class="sisme-store-links">';
        
        $steam_url = !empty($external_links['steam']) ? $external_links['steam'] : '';
        $steam_class = $steam_url ? 'sisme-store-icon' : 'sisme-store-icon sisme-store-icon--disabled';
        $steam_title = $steam_url ? 'Steam' : 'Pas disponible';
        
        if ($steam_url) {
            $output .= '<a href="' . esc_url($steam_url) . '" class="' . $steam_class . '" target="_blank" title="' . $steam_title . '">';
        } else {
            $output .= '<div class="' . $steam_class . '" title="' . $steam_title . '">';
        }
        $output .= '<img src="https://games.sisme.fr/wp-content/uploads/2025/06/Logo-STEAM.webp" alt="Steam">';
        $output .= $steam_url ? '</a>' : '</div>';
        
        $epic_url = !empty($external_links['epic']) ? $external_links['epic'] : '';
        $epic_class = $epic_url ? 'sisme-store-icon' : 'sisme-store-icon sisme-store-icon--disabled';
        $epic_title = $epic_url ? 'Epic Games' : 'Pas disponible';
        
        if ($epic_url) {
            $output .= '<a href="' . esc_url($epic_url) . '" class="' . $epic_class . '" target="_blank" title="' . $epic_title . '">';
        } else {
            $output .= '<div class="' . $epic_class . '" title="' . $epic_title . '">';
        }
        $output .= '<img src="https://games.sisme.fr/wp-content/uploads/2025/06/Logo-EPIC.webp" alt="Epic Games">';
        $output .= $epic_url ? '</a>' : '</div>';
        
        $gog_url = !empty($external_links['gog']) ? $external_links['gog'] : '';
        $gog_class = $gog_url ? 'sisme-store-icon' : 'sisme-store-icon sisme-store-icon--disabled';
        $gog_title = $gog_url ? 'GOG' : 'Pas disponible';
        
        if ($gog_url) {
            $output .= '<a href="' . esc_url($gog_url) . '" class="' . $gog_class . '" target="_blank" title="' . $gog_title . '">';
        } else {
            $output .= '<div class="' . $gog_class . '" title="' . $gog_title . '">';
        }
        $output .= '<img src="https://games.sisme.fr/wp-content/uploads/2025/06/Logo-GOG.webp" alt="GOG">';
        $output .= $gog_url ? '</a>' : '</div>';
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Générer les Sections descriptives
     */
    private static function render_game_sections($sections) {
        if (empty($sections)) {
            return '';
        }
        
        $output = '<div class="sisme-game-sections">';
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
     * JavaScript pour la galerie interactive avec pause du trailer
     */
    private static function render_javascript() {
        return '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const mainDisplay = document.getElementById("sismeMainDisplay");
            const trailerFrame = document.getElementById("sismeTrailerFrame");
            const screenshotImg = document.getElementById("sismeScreenshotImg");
            const thumbs = document.querySelectorAll(".sisme-media-thumb");
            
            if (mainDisplay && thumbs.length > 0) {
                function pauseYouTube() {
                    if (trailerFrame && trailerFrame.contentWindow) {
                        trailerFrame.contentWindow.postMessage(
                            \'{"event":"command","func":"pauseVideo","args":""}\',
                            "*"
                        );
                    }
                }
                
                thumbs.forEach(thumb => {
                    thumb.addEventListener("click", function() {
                        thumbs.forEach(t => t.classList.remove("active"));
                        this.classList.add("active");
                        
                        const type = this.dataset.type;
                        
                        if (type === "trailer" && trailerFrame) {
                            if (screenshotImg && screenshotImg.style.display !== "none") {
                                screenshotImg.classList.add("sisme-fading");
                                
                                setTimeout(() => {
                                    screenshotImg.style.display = "none";
                                    screenshotImg.classList.remove("sisme-fading");
                                    
                                    trailerFrame.style.display = "block";
                                    trailerFrame.classList.add("sisme-appearing");
                                    
                                    setTimeout(() => {
                                        trailerFrame.classList.remove("sisme-appearing");
                                    }, 400);
                                }, 150);
                            } else {
                                trailerFrame.style.display = "block";
                            }
                            
                            const youtubeId = this.dataset.youtube;
                            if (youtubeId) {
                                const newSrc = `https://www.youtube.com/embed/${youtubeId}?enablejsapi=1`;
                                if (!trailerFrame.src.includes(youtubeId)) {
                                    trailerFrame.src = newSrc;
                                }
                            }
                            
                        } else if (type === "screenshot" && screenshotImg) {
                            pauseYouTube();
                            
                            if (trailerFrame && trailerFrame.style.display !== "none") {
                                trailerFrame.classList.add("sisme-fading");
                                
                                setTimeout(() => {
                                    trailerFrame.style.display = "none";
                                    trailerFrame.classList.remove("sisme-fading");
                                    
                                    screenshotImg.src = this.dataset.image;
                                    screenshotImg.style.display = "block";
                                    screenshotImg.classList.add("sisme-appearing");
                                    
                                    setTimeout(() => {
                                        screenshotImg.classList.remove("sisme-appearing");
                                    }, 400);
                                }, 150);
                            } else {
                                if (screenshotImg.src !== this.dataset.image) {
                                    screenshotImg.classList.add("sisme-fading");
                                    
                                    setTimeout(() => {
                                        screenshotImg.src = this.dataset.image;
                                        screenshotImg.style.display = "block";
                                        screenshotImg.classList.remove("sisme-fading");
                                        screenshotImg.classList.add("sisme-appearing");
                                        
                                        setTimeout(() => {
                                            screenshotImg.classList.remove("sisme-appearing");
                                        }, 400);
                                    }, 150);
                                } else {
                                    screenshotImg.style.display = "block";
                                }
                            }
                        }
                    });
                });
            }
            
            function createTooltipSystem() {
                if (document.getElementById("sismeTooltip")) return document.getElementById("sismeTooltip");
                
                const tooltip = document.createElement("div");
                tooltip.id = "sismeTooltip";
                tooltip.style.cssText = `
                    position: absolute;
                    background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
                    color: #e2e8f0;
                    padding: 8px 12px;
                    border-radius: 8px;
                    font-size: 0.85rem;
                    font-weight: 500;
                    line-height: 1.3;
                    pointer-events: none;
                    z-index: 9999;
                    opacity: 0;
                    transform: translateY(5px) scale(0.95);
                    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
                    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4), 0 4px 12px rgba(0, 0, 0, 0.2);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    backdrop-filter: blur(8px);
                    max-width: 250px;
                    word-wrap: break-word;
                `;
                
                document.body.appendChild(tooltip);
                return tooltip;
            }
            
            function showTooltip(element, text) {
                const tooltip = createTooltipSystem();
                if (!tooltip || !text) return;
                
                tooltip.textContent = text;
                
                const rect = element.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();
                
                let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
                let top = rect.top - tooltipRect.height - 8;
                
                const padding = 10;
                if (left < padding) left = padding;
                if (left + tooltipRect.width > window.innerWidth - padding) {
                    left = window.innerWidth - tooltipRect.width - padding;
                }
                if (top < padding) {
                    top = rect.bottom + 8;
                }
                
                tooltip.style.left = left + "px";
                tooltip.style.top = top + window.scrollY + "px";
                
                requestAnimationFrame(() => {
                    tooltip.style.opacity = "1";
                    tooltip.style.transform = "translateY(0) scale(1)";
                });
            }
            
            function hideTooltip() {
                const tooltip = document.getElementById("sismeTooltip");
                if (tooltip) {
                    tooltip.style.opacity = "0";
                    tooltip.style.transform = "translateY(5px) scale(0.95)";
                }
            }
            
            const platformIcons = document.querySelectorAll(".sisme-badge-platform");
            platformIcons.forEach(icon => {
                const tooltipText = icon.getAttribute("title");
                if (tooltipText) {
                    icon.removeAttribute("title");
                    
                    icon.addEventListener("mouseenter", () => {
                        showTooltip(icon, tooltipText);
                    });
                    
                    icon.addEventListener("mouseleave", hideTooltip);
                }
            });

            const storeIcons = document.querySelectorAll(".sisme-store-icon");
            storeIcons.forEach(icon => {
                const tooltipText = icon.getAttribute("title");
                if (tooltipText) {
                    icon.removeAttribute("title");
                    
                    icon.addEventListener("mouseenter", () => {
                        showTooltip(icon, tooltipText);
                    });
                    
                    icon.addEventListener("mouseleave", hideTooltip);
                }
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