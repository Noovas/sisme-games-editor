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
        $css_classes = 'sisme-carousel-container';
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
        
        // Options par défaut spécifiques aux vedettes
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
        
        // Générer un ID unique si pas fourni
        if (!$options['id']) {
            self::$instance_counter++;
            $options['id'] = 'sisme-carousel-' . self::$instance_counter;
        }
        
        // Traiter les items selon le type
        $processed_items = $this->process_items($items, $options['item_type']);
        
        if (empty($processed_items)) {
            return $this->render_vedettes_empty_state();
        }
        
        // STRUCTURE ULTRA-STYLÉE
        $output = '<div class="sisme-ultra-carousel">';
        
        // Effet de particules
        $output .= '<div class="sisme-particles" id="particles-' . $options['id'] . '"></div>';
        
        // Titre
        if ($options['show_title'] && !empty($options['title'])) {
            $output .= '<div class="sisme-ultra-title">';
            $output .= '<h2>' . esc_html($options['title']) . '</h2>';
            $output .= '</div>';
        }
        
        // Carrousel 3D
        $output .= '<div class="sisme-ultra-wrapper">';
        $output .= '<div class="sisme-ultra-container" id="' . esc_attr($options['id']) . '">';
        
        // Slides 3D
        $output .= $this->render_slides($processed_items, $options);
        
        $output .= '</div>'; // fin ultra-container
        
        // Navigation
        $output .= $this->render_navigation($options, count($processed_items));
        
        $output .= '</div>'; // fin ultra-wrapper
        
        // Dots
        $output .= $this->render_dots($processed_items, $options);
        
        // JavaScript
        $output .= $this->render_javascript($options);
        
        $output .= '</div>'; // fin ultra-carousel
        
        return $output;
    }
    
    /**
     * Navigation (flèches)
     */
    private function render_navigation($options, $total_items) {
        if (!$options['show_arrows'] || $total_items <= 1) {
            return '';
        }
        
        $output = '<button class="sisme-ultra-nav prev">';
        $output .= '<div class="sisme-ultra-btn"></div>';
        $output .= '</button>';
        
        $output .= '<button class="sisme-ultra-nav next">';
        $output .= '<div class="sisme-ultra-btn"></div>';
        $output .= '</button>';
        
        return $output;
    }
    
    /**
     * Slides
     */
    private function render_image_slide($item) {
        $output = '<div class="sisme-ultra-image">';
        $output .= '<img src="' . esc_url($item['url']) . '" ';
        $output .= 'alt="' . esc_attr($item['alt']) . '" ';
        $output .= 'loading="lazy">';
        
        // Overlay avec infos du jeu
        if (!empty($item['game_info'])) {
            $output .= '<div class="sisme-ultra-overlay">';
            $output .= '<div class="sisme-ultra-game-title">' . esc_html($item['game_info']['name']) . '</div>';
            
            if (!empty($item['game_info']['sponsor'])) {
                $output .= '<div class="sisme-ultra-game-meta">Sponsorisé par ' . esc_html($item['game_info']['sponsor']) . '</div>';
            } else {
                $output .= '<div class="sisme-ultra-game-meta">Jeu à la Une</div>';
            }
            
            $output .= '</div>';
        }
        
        $output .= '</div>';
        return $output;
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
        
        $output = '<div class="sisme-ultra-dots">';
        
        foreach ($items as $index => $item) {
            $active_class = $index === 0 ? ' active' : '';
            $output .= '<button class="sisme-ultra-dot' . $active_class . '" data-slide="' . $index . '"></button>';
        }
        
        $output .= '</div>';
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

// ============================================================================
// EXEMPLE DE STRUCTURE HTML GÉNÉRÉE
// ============================================================================

/**
 * NOUVELLE STRUCTURE pour vedettes:
 * 
 * <div class="sisme-vedettes-carousel-container">
 *   <h2 class="sisme-vedettes-carousel-title">Jeux à la Une</h2>
 *   <div class="sisme-carousel-wrapper">
 *     <div class="sisme-carousel-container" id="sisme-carousel-1">
 *       <div class="sisme-carousel-slides">
 *         <div class="sisme-carousel-slide active">...</div>
 *         <div class="sisme-carousel-slide">...</div>
 *       </div>
 *     </div>
 *     <div class="sisme-carousel-nav">
 *       <button class="sisme-carousel-btn sisme-carousel-btn--prev">‹</button>
 *       <button class="sisme-carousel-btn sisme-carousel-btn--next">›</button>
 *     </div>
 *     <div class="sisme-carousel-dots">
 *       <button class="sisme-carousel-dot active"></button>
 *       <button class="sisme-carousel-dot"></button>
 *     </div>
 *   </div>
 *   <script>...</script>
 * </div>
 * 
 * STRUCTURE standard inchangée:
 * 
 * <div class="sisme-carousel-container" id="sisme-carousel-1">
 *   <div class="sisme-carousel-nav">...</div>
 *   <div class="sisme-carousel-slides">...</div>
 *   <div class="sisme-carousel-dots">...</div>
 *   <script>...</script>
 * </div>
 */
?>


