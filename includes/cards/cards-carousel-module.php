<?php
/**
 * File: /sisme-games-editor/includes/cards/cards-carousel-module.php
 * Module spécialisé pour les carrousels de cartes
 * 
 * RESPONSABILITÉ:
 * - Rendu HTML des carrousels de cartes
 * - Configuration JavaScript
 * - Intégration avec le système de grilles existant
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Cards_Carousel_Module {
    
    /**
     * Options par défaut pour les carrousels
     */
    private static $default_options = array(
        'cards_per_view' => 3,        
        'total_cards' => 10,           
        'infinite' => false,          
        'autoplay' => false,
        'navigation' => true,
        'pagination' => true,
        'smooth_animation' => true,
        'touch_enabled' => true
    );
    
    /**
     * 🎠 Rendre un carrousel de cartes
     * 
     * @param array $args Arguments du carrousel
     * @return string HTML du carrousel
     */
    public static function render_carousel($args = array()) {
        
        // Fusionner avec les options par défaut
        $carousel_options = array_merge(self::$default_options, $args);
        
        // Préparer les arguments pour la grille de cartes (MODIFIÉ)
        $grid_args = array(
            'type' => $args['type'] ?? 'normal',
            'max_cards' => $carousel_options['total_cards'],
            Sisme_Utils_Games::KEY_GENRES => $args[Sisme_Utils_Games::KEY_GENRES] ?? array(),
            Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => $args[Sisme_Utils_Games::KEY_IS_TEAM_CHOICE] ?? false,
            'sort_by_date' => $args['sort_by_date'] ?? true,
            'sort_order' => $args['sort_order'] ?? 'desc', 
            'debug' => $args['debug'] ?? false,
            'max_genres' => $args['max_genres'] ?? -1,
            'max_modes' => $args['max_modes'] ?? -1,
            Sisme_Utils_Games::KEY_TITLE => $args['title'] ?? '',
            'released' => $args['released'] ?? 0
        );
        
        // Debug
        if ($grid_args['debug'] && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Carousel] Configuration: ' . print_r($carousel_options, true));
            error_log('[Sisme Carousel] Sort order: ' . $grid_args['sort_order']);
        }
        
        $criteria = array(
            Sisme_Utils_Games::KEY_GENRES => $grid_args[Sisme_Utils_Games::KEY_GENRES],
            Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => $grid_args[Sisme_Utils_Games::KEY_IS_TEAM_CHOICE],
            'sort_by_date' => $grid_args['sort_by_date'],
            'sort_order' => $grid_args['sort_order'],  //
            'max_results' => $grid_args['max_cards'],
            'released' => $grid_args['released'],
            'debug' => $grid_args['debug']
        );
        
        $game_ids = Sisme_Utils_Games::get_games_by_criteria($criteria);
        
        if (empty($game_ids)) {
            return self::render_empty_carousel($carousel_options);
        }
        
        // Générer le HTML du carrousel
        return self::render_carousel_html($game_ids, $carousel_options, $grid_args);
    }
    
    /**
     * 🏗️ Générer le HTML du carrousel
     * 
     * @param array $game_ids IDs des jeux
     * @param array $carousel_options Options carrousel
     * @param array $grid_args Arguments grille
     * @return string HTML du carrousel
     */
    private static function render_carousel_html($game_ids, $carousel_options, $grid_args) {
        
        // Calculer le nombre de pages
        $total_cards = count($game_ids);
        $cards_per_view = $carousel_options['cards_per_view'];
        $total_pages = ceil($total_cards / $cards_per_view);
        $is_infinite = $carousel_options['infinite'] && $total_cards > $cards_per_view;
        
        // Configuration JavaScript
        $js_config = array(
            'cardsPerView' => $cards_per_view,
            'totalCards' => $total_cards,
            'totalPages' => $total_pages,
            'infinite' => $is_infinite,
            'autoplay' => $carousel_options['autoplay'],
            'navigation' => $carousel_options['navigation'],
            'pagination' => $carousel_options['pagination'],
            'smoothAnimation' => $carousel_options['smooth_animation'],
            'touchEnabled' => $carousel_options['touch_enabled']
        );
        
        // Container principal
        $css_class = 'sisme-cards-carousel';
        if (!empty($grid_args['debug'])) {
            $css_class .= ' sisme-cards-carousel--debug';
        }
        if ($is_infinite) {
            $css_class .= ' sisme-cards-carousel--infinite';
        }

        // Initialiser $output vide
        $output = '';
        $output .= '<div class="sisme-carrousel-contener">';
        
        // Ajouter le titre EN PREMIER si présent
        if (!empty($grid_args['title'])) {
            $output .= self::render_section_title($grid_args['title']);
        }
        
        // ENSUITE ajouter le carrousel
        $output .= '<div class="' . esc_attr($css_class) . '" ';
        $output .= 'data-carousel-config="' . esc_attr(wp_json_encode($js_config)) . '" ';
        $output .= 'data-cards-count="' . $total_cards . '" ';
        $output .= 'data-cards-per-view="' . $cards_per_view . '"';
        if ($is_infinite) {
            $output .= ' data-infinite="true"';
        }
        $output .= '>';
        
        // Container du carrousel
        $output .= '<div class="sisme-carousel__container">';
        $output .= '<div class="sisme-carousel__track" style="--cards-per-view: ' . $cards_per_view . ';">';
        
        // GÉNÉRATION AVEC CLONES POUR INFINITE LOOP
        if ($is_infinite) {
            $output .= self::render_infinite_slides($game_ids, $cards_per_view, $grid_args);
        } else {
            $output .= self::render_normal_slides($game_ids, $grid_args);
        }
        
        $output .= '</div>'; // fin track
        $output .= '</div>'; // fin container
        
        // Navigation (boutons)
        if ($carousel_options['navigation']) {
            $output .= self::render_navigation_buttons();
        }
        
        // Pagination (dots) - PAS d'infinite pour la pagination
        if ($carousel_options['pagination'] && !$is_infinite) {
            $output .= self::render_pagination_dots($total_pages);
        }
        
        $output .= '</div>'; // fin carrousel
        $output .= '</div>';

        return $output;
    }

    /**
     * Rendre le titre de section
     */
    private static function render_section_title($title) {
        $output = '';
        $output .= '<div class="sisme-carousel-section-header">';
        $output .= '<h2 class="sisme-carousel-section-title">' . esc_html($title) . '</h2>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * 🔄 Générer les slides avec clones pour infinite loop
     */
    private static function render_infinite_slides($game_ids, $cards_per_view, $grid_args) {
        $output = '';
        $total_cards = count($game_ids);
        foreach ($game_ids as $game_id) {
            $output .= '<div class="sisme-carousel__slide sisme-carousel__slide--original">';
            $output .= self::render_single_card($game_id, $grid_args);
            $output .= '</div>';
        }
        $start_clones = array_slice($game_ids, 0, $cards_per_view);
        foreach ($start_clones as $game_id) {
            $output .= '<div class="sisme-carousel__slide sisme-carousel__slide--clone-start">';
            $output .= self::render_single_card($game_id, $grid_args);
            $output .= '</div>';
        }
        $end_clones = array_slice($game_ids, -$cards_per_view);
        foreach ($end_clones as $game_id) {
            $output .= '<div class="sisme-carousel__slide sisme-carousel__slide--clone-end">';
            $output .= self::render_single_card($game_id, $grid_args);
            $output .= '</div>';
        }
        return $output;
    }

    /**
     * 📄 Générer les slides normaux (sans clones)
     */
    private static function render_normal_slides($game_ids, $grid_args) {
        $output = '';
        
        foreach ($game_ids as $game_id) {
            $output .= '<div class="sisme-carousel__slide">';
            $output .= self::render_single_card($game_id, $grid_args);
            $output .= '</div>';
        }
        
        return $output;
    }

    /**
     * 🎴 Rendre une carte individuelle
     */
    private static function render_single_card($game_id, $grid_args) {
        if (class_exists('Sisme_Cards_API')) {
            $card_options = array(
                'css_class' => '',
                'max_genres' => isset($grid_args['max_genres']) ? $grid_args['max_genres'] : -1,
                'max_modes' => isset($grid_args['max_modes']) ? $grid_args['max_modes'] : -1
            );
            return Sisme_Cards_API::render_card($game_id, $grid_args['type'], $card_options);
        } else {
            return '<div class="sisme-card-error">Erreur: API Cards non disponible</div>';
        }
    }
    
    /**
     * ⬅️➡️ Générer les boutons de navigation
     */
    private static function render_navigation_buttons() {
        $output = '<button class="sisme-carousel__btn sisme-carousel__btn--prev" ';
        $output .= 'aria-label="Carte précédente" ';
        $output .= 'type="button">';
        $output .= '<span class="sisme-carousel__btn-icon">‹</span>';
        $output .= '</button>';
        
        $output .= '<button class="sisme-carousel__btn sisme-carousel__btn--next" ';
        $output .= 'aria-label="Carte suivante" ';
        $output .= 'type="button">';
        $output .= '<span class="sisme-carousel__btn-icon">›</span>';
        $output .= '</button>';
        
        return $output;
    }
    
    /**
     * ⚫⚪ Générer les dots de pagination
     * 
     * @param int $total_pages Nombre total de pages
     */
    private static function render_pagination_dots($total_pages) {
        if ($total_pages <= 1) {
            return '';
        }
        
        $output = '<div class="sisme-carousel__pagination" role="tablist" aria-label="Pages du carrousel">';
        
        for ($i = 0; $i < $total_pages; $i++) {
            $is_active = $i === 0 ? 'active' : '';
            $output .= '<button class="sisme-carousel__dot ' . $is_active . '" ';
            $output .= 'role="tab" ';
            $output .= 'aria-selected="' . ($i === 0 ? 'true' : 'false') . '" ';
            $output .= 'aria-label="Page ' . ($i + 1) . '" ';
            $output .= 'data-slide="' . $i . '" ';
            $output .= 'type="button">';
            $output .= '<span class="sisme-carousel__dot-inner"></span>';
            $output .= '</button>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 🚫 Rendu carrousel vide
     */
    private static function render_empty_carousel($options) {
        $output = '<div class="sisme-cards-carousel sisme-cards-carousel--empty">';
        $output .= '<div class="sisme-carousel__empty">';
        $output .= '<p class="sisme-carousel__empty-message">Aucune carte à afficher dans le carrousel</p>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * ❌ Rendu erreur
     */
    private static function render_error($message) {
        return '<div class="sisme-cards-carousel sisme-cards-carousel--error">' .
               '<p class="sisme-carousel__error">' . esc_html($message) . '</p>' .
               '</div>';
    }
    
    /**
     * 🔧 Valider les arguments du carrousel
     * 
     * @param array $args Arguments à valider
     * @return array Résultat de validation
     */
    public static function validate_carousel_args($args) {
    
        // Valider cards_per_view
        if (isset($args['cards_per_view'])) {
            $cards_per_view = intval($args['cards_per_view']);
            if ($cards_per_view < 2 || $cards_per_view > 5) {
                return array(
                    'valid' => false,
                    'message' => 'cards_per_view doit être entre 2 et 5'
                );
            }
        }
        
        // Valider total_cards
        if (isset($args['total_cards'])) {
            $total_cards = intval($args['total_cards']);
            if ($total_cards < 1 || $total_cards > 50) {
                return array(
                    'valid' => false,
                    'message' => 'total_cards doit être entre 1 et 50'
                );
            }
        }
        
        // Valider genres
        if (isset($args[Sisme_Utils_Games::KEY_GENRES]) && !is_array($args[Sisme_Utils_Games::KEY_GENRES])) {
            return array(
                'valid' => false,
                'message' => 'genres doit être un tableau'
            );
        }
        
        return array('valid' => true, 'message' => '');
    }
}

?>