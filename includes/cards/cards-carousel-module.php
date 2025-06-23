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
        'cards_per_view' => 3,        // 2-5
        'total_cards' => 9,           
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
        
        // Préparer les arguments pour la grille de cartes
        $grid_args = array(
            'type' => $args['type'] ?? 'normal',
            'max_cards' => $carousel_options['total_cards'],
            'genres' => $args['genres'] ?? array(),
            'is_team_choice' => $args['is_team_choice'] ?? false,
            'sort_by_date' => $args['sort_by_date'] ?? true,
            'debug' => $args['debug'] ?? false,
            'max_genres' => $args['max_genres'] ?? -1,      
            'max_modes' => $args['max_modes'] ?? -1   
        );
        
        // Debug
        if ($grid_args['debug'] && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Carousel] Configuration: ' . print_r($carousel_options, true));
        }
        
        // Récupérer les IDs des jeux
        if (!class_exists('Sisme_Cards_Functions')) {
            return self::render_error('Module Cards Functions non disponible');
        }
        
        $criteria = array(
            'genres' => $grid_args['genres'],
            'is_team_choice' => $grid_args['is_team_choice'],
            'sort_by_date' => $grid_args['sort_by_date'],
            'max_results' => $grid_args['max_cards'],
            'debug' => $grid_args['debug']
        );
        
        $game_ids = Sisme_Cards_Functions::get_games_by_criteria($criteria);
        
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
        
        // Configuration JavaScript
        $js_config = array(
            'cardsPerView' => $cards_per_view,
            'totalCards' => $total_cards,
            'totalPages' => $total_pages,
            'infinite' => $carousel_options['infinite'],
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
        
        $output = '<div class="' . esc_attr($css_class) . '" ';
        $output .= 'data-carousel-config="' . esc_attr(wp_json_encode($js_config)) . '" ';
        $output .= 'data-cards-count="' . $total_cards . '" ';
        $output .= 'data-cards-per-view="' . $cards_per_view . '">';
        
        // Container du carrousel
        $output .= '<div class="sisme-carousel__container">';
        $output .= '<div class="sisme-carousel__track" style="--cards-per-view: ' . $cards_per_view . ';">';
        
        // Générer les cartes via l'API existante
        foreach ($game_ids as $game_id) {
            // WRAPPER SLIDE qui gère l'espacement
            $output .= '<div class="sisme-carousel__slide">';
            
            if (class_exists('Sisme_Cards_API')) {
                $card_options = array(
                    'css_class' => '',
                    'max_genres' => isset($grid_args['max_genres']) ? $grid_args['max_genres'] : -1,
                    'max_modes' => isset($grid_args['max_modes']) ? $grid_args['max_modes'] : -1
                );
                $card_html = Sisme_Cards_API::render_card($game_id, $grid_args['type'], $card_options);
                $output .= $card_html;
            } else {
                $output .= '<div class="sisme-card-error">Erreur: API Cards non disponible</div>';
            }
            
            $output .= '</div>'; // Fin wrapper slide
        }
        
        $output .= '</div>'; // fin track
        $output .= '</div>'; // fin container
        
        // Navigation (boutons)
        if ($carousel_options['navigation']) {
            $output .= self::render_navigation_buttons();
        }
        
        // Pagination (dots)
        if ($carousel_options['pagination']) {
            $output .= self::render_pagination_dots($total_pages);
        }
        
        $output .= '</div>'; // fin carrousel

        return $output;
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
        if (isset($args['genres']) && !is_array($args['genres'])) {
            return array(
                'valid' => false,
                'message' => 'genres doit être un tableau'
            );
        }
        
        return array('valid' => true, 'message' => '');
    }
}

?>