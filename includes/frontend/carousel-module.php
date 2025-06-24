<?php
/**
 * FICHIER : includes/frontend/carousel-module.php
 * Module carrousel générique réutilisable
 * 
 * Usage simple:
 * $carousel = new Sisme_Carousel_Module();
 * echo $carousel->render($images_ids, $options);
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Carousel_Module {
    
    private static $instance_counter = 0;
    
    /**
     * Rendre le carrousel
     * 
     * @param array $items Array d'IDs d'images OU array d'objets avec données
     * @param array $options Options de configuration
     * @return string HTML du carrousel
     */
    public function render($items = array(), $options = array()) {
        
        if (empty($items)) {
            return $this->render_empty_state();
        }
        
        // Options par défaut
        $defaults = array(
            'height' => '600px',
            'show_arrows' => true,
            'show_dots' => true,
            'autoplay' => false,
            'autoplay_delay' => 5000,
            'item_type' => 'image', // 'image' ou 'custom'
            'css_class' => '',
            'id' => null
        );
        
        $options = array_merge($defaults, $options);
        
        // Générer un ID unique si pas fourni
        if (!$options['id']) {
            self::$instance_counter++;
            $options['id'] = 'sisme-carousel-' . self::$instance_counter;
        }
        
        // Traiter les items selon le type
        $processed_items = $this->process_items($items, $options['item_type']);
        
        if (empty($processed_items)) {
            return $this->render_empty_state();
        }
        
        // Commencer le HTML
        $output = $this->render_container_start($options);
        $output .= $this->render_navigation($options, count($processed_items));
        $output .= $this->render_slides($processed_items, $options);
        $output .= $this->render_dots($processed_items, $options);
        $output .= $this->render_container_end();
        
        // Ajouter le JavaScript inline pour ce carrousel
        $output .= $this->render_javascript($options);
        
        return $output;
    }
    
    /**
     * Traiter les items selon leur type
     */
    private function process_items($items, $type) {
        $processed = array();
        
        foreach ($items as $index => $item) {
            if ($type === 'image') {
                // Si c'est juste un ID numérique
                if (is_numeric($item)) {
                    $image_data = $this->get_image_data($item);
                    if ($image_data) {
                        $processed[] = array(
                            'type' => 'image',
                            'id' => $item,
                            'url' => $image_data['url'],
                            'alt' => $image_data['alt'],
                            'title' => $image_data['title'],
                            'caption' => $image_data['caption']
                        );
                    }
                }
                // Si c'est déjà un objet avec les données (pour vedettes)
                elseif (is_array($item) && isset($item['url'])) {
                    $processed[] = array_merge(array(
                        'type' => 'image',
                        'id' => isset($item['id']) ? $item['id'] : 0,
                        'url' => '',
                        'alt' => '',
                        'title' => '',
                        'caption' => '',
                        'game_info' => null
                    ), $item);
                }
            } elseif ($type === 'custom') {
                if (is_array($item)) {
                    $processed[] = array_merge(array(
                        'type' => 'custom',
                        'html' => '',
                        'title' => '',
                        'content' => ''
                    ), $item);
                }
            }
        }
        
        return $processed;
    }
    
    /**
     * Récupérer les données d'une image
     */
    private function get_image_data($image_id) {
        $image_url = wp_get_attachment_image_url($image_id, 'large');
        if (!$image_url) {
            return false;
        }
        
        return array(
            'url' => $image_url,
            'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: '',
            'title' => get_the_title($image_id),
            'caption' => wp_get_attachment_caption($image_id)
        );
    }
    
    /**
     * Début du container
     */
    private function render_container_start($options) {
        //$css_classes = 'sisme-carousel-container';
        $css_classes = '';
        if (!empty($options['css_class'])) {
            $css_classes .= ' ' . esc_attr($options['css_class']);
        }
        
        $output = '<div class="' . $css_classes . '" id="' . esc_attr($options['id']) . '" ';
        $output .= 'data-options="' . esc_attr(json_encode($options)) . '">';
        
        return $output;
    }

    private function render_slides($items, $options) {
        $output = '';
        
        foreach ($items as $index => $item) {
            // Classes pour position 3D
            $slide_class = 'sisme-ultra-slide';
            if ($index === 0) {
                $slide_class .= ' active';
            } elseif ($index === 1) {
                $slide_class .= ' next';
            } elseif ($index === count($items) - 1) {
                $slide_class .= ' prev';
            } else {
                $slide_class .= ' far';
            }
            
            $output .= '<div class="' . $slide_class . '" data-index="' . $index . '">';
            
            if ($item['type'] === 'image') {
                $output .= $this->render_image_slide($item);
            } elseif ($item['type'] === 'custom') {
                $output .= $this->render_custom_slide($item);
            }
            
            $output .= '</div>';
        }
        
        return $output;
    }

    /**
     * Wrapper complet pour carrousel vedettes
     */
    public function render_vedettes_carousel($items = array(), $options = array()) {
        if (empty($items)) {
            return $this->render_vedettes_empty_state();
        }
        
        // Options par défaut avec métadonnées SEO
        $defaults = array(
            'height' => '500px',
            'show_arrows' => true,
            'show_dots' => true,
            'autoplay' => true,
            'autoplay_delay' => 5000,
            'item_type' => 'image',
            'css_class' => '',
            'id' => null,
            'show_title' => true,
            'title' => 'Jeux à la Une'
        );
        
        $options = array_merge($defaults, $options);
        
        // Générer un ID unique
        if (!$options['id']) {
            self::$instance_counter++;
            $options['id'] = 'sisme-carousel-' . self::$instance_counter;
        }
        
        // Traiter les items
        $processed_items = $this->process_items($items, $options['item_type']);
        
        if (empty($processed_items)) {
            return $this->render_vedettes_empty_state();
        }
        
        // STRUCTURE HTML SÉMANTIQUE
        //$output = '<section class="sisme-ultra-carousel" role="region" aria-label="' . esc_attr($options['title']) . '">';
        $output = '<section class="" role="region" aria-label="' . esc_attr($options['title']) . '">';
        
        // Données structurées JSON-LD
        $output .= $this->render_structured_data($processed_items, $options);
        
        // Effet de particules
        $output .= '<div class="sisme-particles" id="particles-' . $options['id'] . '" aria-hidden="true"></div>';
        
        // Titre sémantique
        if ($options['show_title'] && !empty($options['title'])) {
            $output .= '<header class="sisme-ultra-title">';
            $output .= '<h2>' . esc_html($options['title']) . '</h2>';
            $output .= '</header>';
        }
        
        // Carrousel avec ARIA
        $output .= '<div class="sisme-ultra-wrapper" role="group" aria-label="Carrousel de jeux">';
        $output .= '<div class="sisme-ultra-container" id="' . esc_attr($options['id']) . '">';
        
        // Slides avec structure sémantique
        $output .= $this->render_slides($processed_items, $options);
        
        $output .= '</div>'; // fin ultra-container
        
        // Navigation avec ARIA
        $output .= $this->render_navigation($options, count($processed_items));
        
        $output .= '</div>'; // fin ultra-wrapper
        
        // Dots avec ARIA
        $output .= $this->render_dots($processed_items, $options);
        
        // JavaScript
        $output .= $this->render_javascript($options);
        
        $output .= '</section>'; // fin ultra-carousel
        
        return $output;
    }
    
    /**
     * Navigation (flèches)
     */
    private function render_navigation($options, $total_items) {
        if (!$options['show_arrows'] || $total_items <= 1) {
            return '';
        }
        
        $output = '<button class="sisme-ultra-nav prev" ';
        $output .= 'aria-label="Jeu précédent" ';
        $output .= 'title="Voir le jeu précédent">';
        $output .= '<div class="sisme-ultra-btn"></div>';
        $output .= '<span class="sr-only">Jeu précédent</span>';
        $output .= '</button>';
        
        $output .= '<button class="sisme-ultra-nav next" ';
        $output .= 'aria-label="Jeu suivant" ';
        $output .= 'title="Voir le jeu suivant">';
        $output .= '<div class="sisme-ultra-btn"></div>';
        $output .= '<span class="sr-only">Jeu suivant</span>';
        $output .= '</button>';
        
        return $output;
    }
    
    /**
     * Slides
     */
    private function render_image_slide($item) {
        // Préparer les données SEO
        $game_name = !empty($item['game_info']['name']) ? $item['game_info']['name'] : 'Jeu';
        $game_description = !empty($item['game_info']['description']) ? 
            strip_tags($item['game_info']['description']) : '';
        
        // ALT optimisé : descriptif et riche en mots-clés
        $optimized_alt = "Cover du jeu " . $game_name . " - Jeu indépendant à découvrir";
        
        // TITLE optimisé : call-to-action + description
        $optimized_title = "Découvrez " . $game_name;
        if (!empty($game_description)) {
            $short_desc = self::truncate_on_word($game_description, 60);
            $optimized_title .= " - " . $short_desc;
        }
        
        // URL de redirection vers la fiche du jeu
        $game_url = !empty($item['game_info']['slug']) ? 
            home_url($item['game_info']['slug'] . '/') : '#';
        
        $output = '';
        
        // 🎯 LIEN WRAPPER qui englobe TOUT le slide
        $output .= '<a href="' . esc_url($game_url) . '" ';
        $output .= 'class="sisme-slide-link" ';
        $output .= 'title="' . esc_attr($optimized_title) . '" ';
        $output .= 'aria-label="Découvrir le jeu ' . esc_attr($game_name) . '">';
        
        // Image container
        $output .= '<div class="sisme-ultra-image">';
        $output .= '<img src="' . esc_url($item['url']) . '" ';
        $output .= 'alt="' . esc_attr($optimized_alt) . '" ';
        $output .= 'loading="lazy" ';
        $output .= 'decoding="async">';
        $output .= '</div>';
        
        // Overlay avec informations (SANS lien dedans)
        $output .= '<div class="sisme-ultra-overlay">';
        
        if (!empty($item['game_info']['name'])) {
            $output .= '<h3 class="sisme-slide-title" itemprop="name">';
            $output .= esc_html($item['game_info']['name']);
            $output .= '</h3>';
        }
        
        // Description tronquée intelligente
        if (!empty($item['game_info']['description'])) {
            $description = strip_tags($item['game_info']['description']);
            $truncated_description = self::truncate_on_word($description, 120);
            
            $output .= '<p class="sisme-slide-description" itemprop="description">';
            $output .= esc_html($truncated_description);
            $output .= '</p>';
        } elseif (!empty($item['game_info']['sponsor'])) {
            // Fallback : afficher le sponsor si pas de description
            $output .= '<p class="sisme-slide-sponsor">';
            $output .= '🎯 Sponsorisé par ' . esc_html($item['game_info']['sponsor']);
            $output .= '</p>';
        } else {
            // Fallback ultime
            $output .= '<p class="sisme-slide-default">';
            $output .= 'Jeu à la Une';
            $output .= '</p>';
        }
        
        $output .= '</div>'; // fin overlay
        $output .= '</a>'; // fin lien wrapper
        
        return $output;
    }


    private function render_structured_data($processed_items, $options) {
        if (empty($processed_items)) {
            return '';
        }
        
        $games_data = array();
        
        foreach ($processed_items as $item) {
            if (!empty($item['game_info'])) {
                $game_data = array(
                    "@type" => "VideoGame",
                    "name" => $item['game_info']['name'],
                    "image" => $item['url'],
                    "url" => home_url('/tag/' . $item['game_info']['slug'] . '/'),
                );
                
                if (!empty($item['game_info']['description'])) {
                    $game_data['description'] = self::truncate_on_word(
                        strip_tags($item['game_info']['description']), 
                        160
                    );
                }
                
                $games_data[] = $game_data;
            }
        }
        
        if (empty($games_data)) {
            return '';
        }
        
        $structured_data = array(
            "@context" => "https://schema.org",
            "@type" => "ItemList",
            "name" => $options['title'] ?: "Jeux à la Une",
            "description" => "Découvrez notre sélection de jeux indépendants mis en avant",
            "numberOfItems" => count($games_data),
            "itemListElement" => array()
        );
        
        foreach ($games_data as $index => $game) {
            $structured_data['itemListElement'][] = array(
                "@type" => "ListItem",
                "position" => $index + 1,
                "item" => $game
            );
        }
        
        return '<script type="application/ld+json">' . wp_json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    private static function truncate_on_word($text, $max_length = 150) {
        // Si le texte est déjà assez court, le retourner tel quel
        if (mb_strlen($text) <= $max_length) {
            return $text;
        }
        
        // Tronquer au nombre de caractères maximum
        $truncated = mb_substr($text, 0, $max_length);
        
        // Trouver la dernière position d'un espace pour couper sur un mot
        $last_space = mb_strrpos($truncated, ' ');
        
        // Si on trouve un espace et qu'il n'est pas trop proche du début
        if ($last_space !== false && $last_space > ($max_length * 0.6)) {
            $truncated = mb_substr($truncated, 0, $last_space);
        }
        
        // Nettoyer les signes de ponctuation en fin si nécessaire
        $truncated = rtrim($truncated, '.,;:!?');
        
        return $truncated . '...';
    }

    /**
     * État vide pour carrousel vedettes
     */
    private function render_vedettes_empty_state() {
        return '<div class="sisme-ultra-carousel">
                    <div class="sisme-ultra-title">
                        <h2>Jeux à la Une</h2>
                    </div>
                    <div class="sisme-ultra-wrapper">
                        <div class="sisme-ultra-empty">
                            <div class="sisme-empty-icon">🌟</div>
                            <h3>Aucun jeu vedette</h3>
                            <p>Configurez des jeux en vedette pour voir le carrousel ici.</p>
                            <small>Rendez-vous dans l\'administration pour ajouter des jeux à la une.</small>
                        </div>
                    </div>
                </div>';
    }

    /**
     * Méthode statique spécifique aux vedettes
     */
    public static function quick_render_vedettes($items, $options = array()) {
        $instance = new self();
        return $instance->render_vedettes_carousel($items, $options);
    }
    
    /**
     * Slide custom
     */
    private function render_custom_slide($item) {
        $output = '<div class="sisme-slide-custom">';
        
        if (!empty($item['html'])) {
            $output .= $item['html']; // HTML custom (assumé sécurisé)
        } else {
            if (!empty($item['title'])) {
                $output .= '<h3 class="sisme-slide-title">' . esc_html($item['title']) . '</h3>';
            }
            if (!empty($item['content'])) {
                $output .= '<div class="sisme-slide-content">' . wp_kses_post($item['content']) . '</div>';
            }
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Dots de navigation
     */
    private function render_dots($items, $options) {
        if (!$options['show_dots'] || count($items) <= 1) {
            return '';
        }
        
        $output = '<nav class="sisme-ultra-dots" role="tablist" aria-label="Navigation du carrousel">';
        
        foreach ($items as $index => $item) {
            $active_class = $index === 0 ? ' active' : '';
            $game_name = !empty($item['game_info']['name']) ? $item['game_info']['name'] : 'Jeu ' . ($index + 1);
            
            $output .= '<button class="sisme-ultra-dot' . $active_class . '" ';
            $output .= 'role="tab" ';
            $output .= 'aria-label="Aller au jeu ' . esc_attr($game_name) . '" ';
            $output .= 'aria-selected="' . ($index === 0 ? 'true' : 'false') . '" ';
            $output .= 'title="' . esc_attr($game_name) . '" ';
            $output .= 'data-index="' . $index . '">';
            $output .= '<span class="sr-only">' . esc_html($game_name) . '</span>';
            $output .= '</button>';
        }
        
        $output .= '</nav>';
        
        return $output;
    }
    
    /**
     * Fin du container
     */
    private function render_container_end() {
        return '</div>'; // .sisme-carousel-container
    }
    
    /**
     * État vide
     */
    private function render_empty_state() {
        return '<div class="sisme-carousel-empty">
                    <div class="sisme-empty-icon">🖼️</div>
                    <p>Aucun élément à afficher</p>
                </div>';
    }
    
    /**
     * JavaScript inline pour ce carrousel
     */
    private function render_javascript($options) {
        $carousel_id = $options['id'];
        $autoplay = $options['autoplay'] ? 'true' : 'false';
        $delay = $options['autoplay_delay'];
        
        $script = "
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const carousel = document.getElementById('{$carousel_id}');
            if (!carousel) return;
            
            let currentSlide = 0;
            const slides = carousel.querySelectorAll('.sisme-ultra-slide');
            
            // Recherche robuste des boutons (plusieurs méthodes)
            const prevBtn = document.querySelector('.sisme-ultra-nav.prev') || 
                           carousel.parentElement.querySelector('.sisme-ultra-nav.prev') ||
                           document.querySelector('[class*=\"prev\"]');
            const nextBtn = document.querySelector('.sisme-ultra-nav.next') || 
                           carousel.parentElement.querySelector('.sisme-ultra-nav.next') ||
                           document.querySelector('[class*=\"next\"]');
            
            // Recherche des dots
            const dotsContainer = document.querySelector('.sisme-ultra-dots');
            const dots = dotsContainer ? dotsContainer.querySelectorAll('.sisme-ultra-dot') : [];
            
            const totalSlides = slides.length;
            let autoplayTimer = null;
            let isAnimating = false;
            let autoplayEnabled = {$autoplay};

            function updateSlides() {
                if (isAnimating) return;
                isAnimating = true;
                
                slides.forEach((slide, index) => {
                    slide.className = 'sisme-ultra-slide';
                    
                    if (index === currentSlide) {
                        slide.classList.add('active');
                    } else if (index === (currentSlide - 1 + totalSlides) % totalSlides) {
                        slide.classList.add('prev');
                    } else if (index === (currentSlide + 1) % totalSlides) {
                        slide.classList.add('next');
                    } else {
                        slide.classList.add('far');
                    }
                });

                if (dots && dots.length > 0) {
                    dots.forEach((dot, index) => {
                        dot.classList.toggle('active', index === currentSlide);
                    });
                }
                
                setTimeout(() => { isAnimating = false; }, 800);
            }

            function nextSlide() {
                if (isAnimating) return;
                currentSlide = (currentSlide + 1) % totalSlides;
                updateSlides();
            }

            function prevSlide() {
                if (isAnimating) return;
                currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
                updateSlides();
            }

            function goToSlide(index) {
                if (isAnimating || index === currentSlide) return;
                currentSlide = index;
                updateSlides();
            }
            
            function startAutoplay() {
                stopAutoplay();
                if (autoplayEnabled) {
                    autoplayTimer = setInterval(function() {
                        if (!isAnimating) nextSlide();
                    }, {$delay});
                }
            }
            
            function stopAutoplay() {
                if (autoplayTimer) {
                    clearInterval(autoplayTimer);
                    autoplayTimer = null;
                }
            }
            
            let clickDebounce = false;
            
            function handleClick(callback) {
                if (clickDebounce || isAnimating) return;
                clickDebounce = true;
                stopAutoplay();
                callback();
                setTimeout(() => {
                    clickDebounce = false;
                    startAutoplay();
                }, 1000);
            }

            // Event listeners avec vérification d'existence
            if (nextBtn) {
                nextBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    handleClick(nextSlide);
                });
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    handleClick(prevSlide);
                });
            }

            if (dots && dots.length > 0) {
                dots.forEach((dot, index) => {
                    dot.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        handleClick(() => goToSlide(index));
                    });
                });
            }

            // Gestion du hover
            const carouselWrapper = carousel.parentElement;
            if (carouselWrapper) {
                carouselWrapper.addEventListener('mouseenter', () => {
                    autoplayEnabled = false;
                    stopAutoplay();
                });
                
                carouselWrapper.addEventListener('mouseleave', () => {
                    autoplayEnabled = {$autoplay};
                    startAutoplay();
                });
            }

            // Particles (optionnel)
            function createParticle() {
                const particlesContainer = document.getElementById('particles-{$carousel_id}');
                if (!particlesContainer || particlesContainer.children.length > 10) return;
                
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                particle.style.animationDelay = Math.random() * 2 + 's';
                
                particlesContainer.appendChild(particle);
                setTimeout(() => particle.remove(), 8000);
            }

            const particleInterval = setInterval(createParticle, 500);
            
            window.addEventListener('beforeunload', () => {
                stopAutoplay();
                clearInterval(particleInterval);
            });

            // Initialisation
            updateSlides();
            startAutoplay();
        });
        </script>";
        
        return $script;
    }
    
    /**
     * Méthode statique pour usage rapide
     */
    public static function quick_render($items, $options = array()) {
        $instance = new self();
        return $instance->render($items, $options);
    }
}

// Shortcode pour usage dans le contenu
add_shortcode('sisme_carousel', function($atts, $content = '') {
    $atts = shortcode_atts(array(
        'images' => '', // IDs séparés par virgules
        'height' => '400px',
        'autoplay' => 'false',
        'show_arrows' => 'true',
        'show_dots' => 'true'
    ), $atts);
    
    if (empty($atts['images'])) {
        return '';
    }
    
    $image_ids = array_map('intval', explode(',', $atts['images']));
    $image_ids = array_filter($image_ids); // Enlever les valeurs vides
    
    $options = array(
        'height' => $atts['height'],
        'autoplay' => filter_var($atts['autoplay'], FILTER_VALIDATE_BOOLEAN),
        'show_arrows' => filter_var($atts['show_arrows'], FILTER_VALIDATE_BOOLEAN),
        'show_dots' => filter_var($atts['show_dots'], FILTER_VALIDATE_BOOLEAN),
        'item_type' => 'image'
    );
    
    return Sisme_Carousel_Module::quick_render($image_ids, $options);
});