<?php
/**
 * File: /sisme-games-editor/includes/game-page-creator/game-page-renderer.php
 * Rendu HTML exact de la page de jeu
 * 
 * RESPONSABILITÉ:
 * - Génération HTML strictement identique à la structure existante
 * - Utilisation des mêmes classes CSS
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
        $output .= '<div class="sisme-game-media">';
        $output .= self::render_media_gallery($game_data);
        if (!empty($game_data['sections'])) {
            $output .= self::render_game_sections($game_data['sections']);
        }
        $output .= '</div>';
        $output .= '<div class="sisme-game-info">';
        $output .= self::render_game_info($game_data);
        $output .= '</div>';
        $output .= '</div>';
        $output .= self::render_javascript();
        return $output;
    }
    
    /**
     * Générer la galerie média (trailer + screenshots)
     */
    private static function render_media_gallery($game_data) {
        $output = '';
        $output .= '<div class="sisme-main-media-display" id="sismeMainDisplay">';
        if (!empty($game_data['trailer_link'])) {
            $youtube_id = self::extract_youtube_id($game_data['trailer_link']);
            if ($youtube_id) {
                $output .= '<iframe id="sismeTrailerFrame" src="https://www.youtube.com/embed/' . esc_attr($youtube_id) . '?enablejsapi=1" allowfullscreen></iframe>';
            }
        }
        $output .= '<img id="sismeScreenshotImg" src="" alt="Screenshot" style="display: none;">';
        $output .= '</div>';
        $output .= '<div class="sisme-media-gallery">';
        if (!empty($game_data['trailer_link'])) {
            $youtube_id = self::extract_youtube_id($game_data['trailer_link']);
            if ($youtube_id) {
                $output .= '<div class="sisme-media-thumb trailer active" data-type="trailer" data-youtube="' . esc_attr($youtube_id) . '">';
                $output .= '<img src="https://img.youtube.com/vi/' . esc_attr($youtube_id) . '/maxresdefault.jpg" alt="Trailer">';
                $output .= '</div>';
            }
        }
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
            if (!empty($section['title'])) {
                $output .= '<h3>' . esc_html($section['title']) . '</h3>';
            }
            if (!empty($section['content'])) {
                $output .= wpautop(wp_kses_post($section['content']));
            }
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
        $output .= '<h1 class="sisme-game-title">' . esc_html($game_data['name']) . '</h1>';
        if (!empty($game_data['description'])) {
            $output .= '<p class="sisme-game-description">' . wp_kses_post($game_data['description']) . '</p>';
        }
        $output .= self::render_user_actions($game_data);
        $output .= '<div class="sisme-game-meta-container" id="sismeGameMeta">';
        $output .= '<h2 class="sisme-meta-title">Informations du jeu</h2>';
        $output .= '<div class="sisme-game-meta">';
        if (!empty($game_data['genres'])) {
            $output .= self::render_genres($game_data['genres']);
        }
        if (!empty($game_data['platforms'])) {
            $output .= self::render_platforms($game_data['platforms']);
        }
        if (!empty($game_data['modes'])) {
            $output .= self::render_modes($game_data['modes']);
        }
        if (!empty($game_data['developers'])) {
            $output .= self::render_developers($game_data['developers']);
        }
        if (!empty($game_data['publishers'])) {
            $output .= self::render_publishers($game_data['publishers']);
        }
        if (!empty($game_data['release_date'])) {
            $output .= self::render_release_date($game_data['release_date']);
        }
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
        $platform_mapping = [
            'windows' => ['group' => 'pc', 'icon' => '🖥️'],
            'mac' => ['group' => 'pc', 'icon' => '🖥️'],
            'macos' => ['group' => 'pc', 'icon' => '🖥️'],
            'linux' => ['group' => 'pc', 'icon' => '🖥️'],
            'xbox' => ['group' => 'console', 'icon' => '🎮'],
            'playstation' => ['group' => 'console', 'icon' => '🎮'],
            'switch' => ['group' => 'console', 'icon' => '🎮'],
            'ios' => ['group' => 'mobile', 'icon' => '📱'],
            'android' => ['group' => 'mobile', 'icon' => '📱'],
            'web' => ['group' => 'web', 'icon' => '�']
        ];
        $groups = [];
        foreach ($platforms as $platform) {
            if (!isset($platform['key'])) {
                continue;
            }
            $key = $platform['key'];
            $label = isset($platform['label']) ? $platform['label'] : ucfirst($key);
            if (isset($platform_mapping[$key])) {
                $group = $platform_mapping[$key]['group'];
                $icon = $platform_mapping[$key]['icon'];
                
                if (!isset($groups[$group])) {
                    $groups[$group] = [
                        'platforms' => [],
                        'icon' => $icon
                    ];
                }
                $groups[$group]['platforms'][] = $label;
            } else {
                if (!isset($groups['other'])) {
                    $groups['other'] = [
                        'platforms' => [],
                        'icon' => '🎲'
                    ];
                }
                $groups['other']['platforms'][] = $label;
            }
        }
        foreach ($groups as $group_key => $group) {
            $platforms_list = implode(', ', $group['platforms']);
            $output .= '<span class="sisme-badge-platform sisme-platform-' . esc_attr($group_key) . '" ';
            $output .= 'title="Disponible sur: ' . esc_attr($platforms_list) . '">';
            $output .= $group['icon'];
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
        $mode_labels = array(
            'solo' => 'Solo',
            'multijoueur' => 'Multijoueur',
            'coop' => 'Coopération',
            'competitif' => 'Compétitif',
            'online' => 'En ligne',
            'local' => 'Local'
        );
        if (is_array($modes)) {
            if (isset($modes[0]) && is_array($modes[0]) && isset($modes[0]['key'])) {
                foreach ($modes as $mode) {
                    $output .= '<span class="sisme-badge sisme-badge-mode ' . esc_attr($mode['key']) . '">';
                    $output .= esc_html($mode['label']);
                    $output .= '</span>';
                }
            } else {
                foreach ($modes as $mode) {
                    $mode_key = is_string($mode) ? $mode : strval($mode);
                    $mode_label = isset($mode_labels[$mode_key]) ? $mode_labels[$mode_key] : ucfirst($mode_key);
                    
                    $output .= '<span class="sisme-badge sisme-badge-mode ' . esc_attr($mode_key) . '">';
                    $output .= esc_html($mode_label);
                    $output .= '</span>';
                }
            }
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
        if (empty($external_links) || count($external_links) === 0) {
            $output .= '<div class="sisme-store-no-links">Aucun lien boutique disponible</div>';
        } else {
            foreach ($external_links as $platform => $link_data) {
                if (!empty($link_data['available'])) {
                    $output .= '<a href="' . esc_url($link_data['url']) . '" class="sisme-store-icon" target="_blank" title="' . esc_attr($link_data['label']) . '">';
                    $output .= '<img src="' . esc_attr($link_data['icon']) . '" alt="' . esc_attr($link_data['label']) . '">';
                    $output .= '</a>';
                } else {
                    $output .= '<div class="sisme-store-icon sisme-store-icon--disabled" title="Pas disponible">';
                    $output .= '<img src="' . esc_attr($link_data['icon']) . '" alt="' . esc_attr($link_data['label']) . '">';
                    $output .= '</div>';
                }
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
        const gallery = document.querySelector(".sisme-media-gallery");
        
        if (!mainDisplay || !thumbs.length) return;
        
        // === FONCTIONNALITÉ GALERIE EXISTANTE ===
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
        
        // === NOUVEAU : DÉFILEMENT HORIZONTAL ===
        if (gallery) {
            // Support molette horizontale
            gallery.addEventListener("wheel", function(e) {
                // Empêcher le scroll vertical de la page
                e.preventDefault();
                
                // Scroll horizontal avec la molette
                this.scrollLeft += e.deltaY;
            });
            
            // Ajouter boutons navigation si nécessaire
            if (thumbs.length > 5) {
                addNavigationButtons(gallery);
            }
        }
        
        // === FONCTION BOUTONS NAVIGATION ===
        function addNavigationButtons(gallery) {
            const container = gallery.parentElement;
            
            // Créer le wrapper avec boutons
            const wrapper = document.createElement("div");
            wrapper.className = "sisme-gallery-wrapper";
            wrapper.style.position = "relative";
            
            // Bouton gauche
            const leftBtn = document.createElement("button");
            leftBtn.className = "sisme-gallery-nav sisme-gallery-nav--left";
            leftBtn.innerHTML = "‹";
            leftBtn.style.cssText = `
                position: absolute;
                left: -15px;
                top: 50%;
                transform: translateY(-50%);
                z-index: 10;
                background: rgba(0, 0, 0, 0.7);
                color: white;
                border: none;
                width: 30px;
                height: 30px;
                border-radius: 50%;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
                font-size: 18px;
                font-weight: bold;
            `;
            
            // Bouton droite
            const rightBtn = document.createElement("button");
            rightBtn.className = "sisme-gallery-nav sisme-gallery-nav--right";
            rightBtn.innerHTML = "›";
            rightBtn.style.cssText = leftBtn.style.cssText;
            rightBtn.style.left = "auto";
            rightBtn.style.right = "-15px";
            
            // Hover effects
            [leftBtn, rightBtn].forEach(function(btn) {
                btn.addEventListener("mouseenter", function() {
                    this.style.background = "rgba(102, 192, 244, 0.8)";
                    this.style.transform = "translateY(-50%) scale(1.1)";
                });
                
                btn.addEventListener("mouseleave", function() {
                    this.style.background = "rgba(0, 0, 0, 0.7)";
                    this.style.transform = "translateY(-50%) scale(1)";
                });
            });
            
            // Fonctionnalité scroll
            leftBtn.addEventListener("click", function() {
                gallery.scrollBy({
                    left: -150,
                    behavior: "smooth"
                });
            });
            
            rightBtn.addEventListener("click", function() {
                gallery.scrollBy({
                    left: 150,
                    behavior: "smooth"
                });
            });
            
            // Insérer le wrapper
            container.insertBefore(wrapper, gallery);
            wrapper.appendChild(leftBtn);
            wrapper.appendChild(gallery);
            wrapper.appendChild(rightBtn);
            
            // Gérer la visibilité des boutons
            function updateButtonVisibility() {
                const isAtStart = gallery.scrollLeft <= 5;
                const isAtEnd = gallery.scrollLeft >= gallery.scrollWidth - gallery.clientWidth - 5;
                
                leftBtn.style.opacity = isAtStart ? "0.3" : "1";
                leftBtn.style.pointerEvents = isAtStart ? "none" : "auto";
                
                rightBtn.style.opacity = isAtEnd ? "0.3" : "1";
                rightBtn.style.pointerEvents = isAtEnd ? "none" : "auto";
            }
            
            gallery.addEventListener("scroll", updateButtonVisibility);
            updateButtonVisibility(); // Initial check
        }
    });

    // === CSS POUR LES BOUTONS (injecté automatiquement) ===
    if (!document.getElementById("sisme-gallery-scroll-css")) {
        const style = document.createElement("style");
        style.id = "sisme-gallery-scroll-css";
        style.textContent = `
            .sisme-media-gallery {
                scroll-behavior: smooth;
                -webkit-overflow-scrolling: touch;
            }
            
            .sisme-gallery-nav:focus {
                outline: 2px solid #66c0f4;
                outline-offset: 2px;
            }
            
            .sisme-gallery-nav:active {
                transform: translateY(-50%) scale(0.95) !important;
            }
            
            @media (max-width: 768px) {
                .sisme-gallery-nav {
                    display: none !important;
                }
            }
        `;
        document.head.appendChild(style);
    }

    </script>';
    }
}