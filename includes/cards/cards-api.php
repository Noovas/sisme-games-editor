<?php
/**
 * File: /sisme-games-editor/includes/cards/cards-api.php
 * API principale du système de cartes de jeux
 * 
 * RESPONSABILITÉ:
 * - Point d'entrée unique pour toutes les cartes
 * - Validation et gestion d'erreurs
 * - Délégation vers les modules spécialisés
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure les dépendances
require_once dirname(__FILE__) . '/cards-normal-module.php';

class Sisme_Cards_API {
    
    /**
     * Types de cartes supportés
     */
    const SUPPORTED_TYPES = array('normal', 'details', 'compact');
    
    /**
     * Rendre une carte de jeu - POINT D'ENTRÉE PRINCIPAL
     * 
     * @param int $game_id ID du jeu (term_id)
     * @param string $type Type de carte: 'normal', 'details', 'compact'
     * @param array $options Options supplémentaires
     * @return string HTML de la carte
     */
    public static function render_card($game_id, $type = 'normal', $options = array()) {
        
        // Validation du type
        if (!in_array($type, self::SUPPORTED_TYPES)) {
            return self::render_error("Type de carte non supporté: {$type}");
        }
        
        // Validation de l'ID
        if (empty($game_id) || !is_numeric($game_id)) {
            return self::render_error("ID de jeu invalide");
        }
        
        // Vérifier que le jeu existe
        $game_term = get_term($game_id);
        if (!$game_term || is_wp_error($game_term)) {
            return self::render_error("Jeu introuvable (ID: {$game_id})");
        }
        
        // Récupérer les données du jeu
        $game_data = Sisme_Utils_Games::get_game_data($game_id);
        if (!$game_data) {
            return self::render_error("Données du jeu incomplètes");
        }
        
        // Déléguer selon le type
        switch ($type) {
            case 'normal':
                return Sisme_Cards_Normal_Module::render($game_data, $options);
            
            case 'details':
                return Sisme_Cards_Details_Module::render($game_data, $options);
            
            case 'compact':
                return self::render_todo("Carte Compact - À venir");
            
            default:
                return Sisme_Cards_Normal_Module::render($game_data, $options);
        }
    }
    
    /**
     * Rendre plusieurs cartes
     * 
     * @param array $game_ids Liste d'IDs de jeux
     * @param string $type Type de carte
     * @param array $options Options
     * @return string HTML des cartes
     */
    public static function render_multiple_cards($game_ids, $type = 'normal', $options = array()) {
        if (empty($game_ids) || !is_array($game_ids)) {
            return self::render_error("Liste d'IDs invalide");
        }
        
        $output = '';
        foreach ($game_ids as $game_id) {
            $output .= self::render_card($game_id, $type, $options);
        }
        
        return $output;
    }
    
    /**
     * Rendre un état d'erreur
     */
    private static function render_error($message) {
        return '<div class="sisme-card-error">' . esc_html($message) . '</div>';
    }
    
    /**
     * Rendre un état TODO
     */
    private static function render_todo($message) {
        return '<div class="sisme-card-todo">' . esc_html($message) . '</div>';
    }

    /**
     * 🎯 Rendre une grille de cartes selon critères - POINT D'ENTRÉE PRINCIPAL
     * 
     * @param array $args Paramètres de configuration
     * @return string HTML de la grille ou message d'erreur
     */
    public static function render_cards_grid($args = array()) {
        
        // Paramètres par défaut
        $defaults = array(
            'type' => 'normal',                    // Type des cartes
            'cards_per_row' => 4,                  // Nombre de cartes par ligne
            'max_cards' => -1,                     // Nombre max (-1 = illimité)
            Sisme_Utils_Games::KEY_GENRES => array(),                   // Liste des genres
            Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => false,             // Choix équipe (à venir)
            'sort_by_date' => true,
            'container_class' => '',
            'debug' => false
        );
        
        // Fusionner et valider les paramètres
        $args = array_merge($defaults, $args);
        $validation = self::validate_grid_args($args);
        
        if (!$validation['valid']) {
            return self::render_error($validation['message']);
        }
        
        // Debug si activé
        if ($args['debug'] && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Cards API] Rendu grille avec args: ' . print_r($args, true));
        }
        
        $criteria = array(
            Sisme_Utils_Games::KEY_GENRES => $args[Sisme_Utils_Games::KEY_GENRES] ?? array(),
            Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => $args[Sisme_Utils_Games::KEY_IS_TEAM_CHOICE] ?? false,
            'sort_by_date' => $args['sort_by_date'] ?? true,
            'sort_order' => $args['sort_order'] ?? 'desc',  // ✅ NOUVEAU
            'max_results' => ($args['max_cards'] ?? -1) > 0 ? $args['max_cards'] : -1,
            'released' => $args['released'] ?? 0,
            'debug' => $args['debug'] ?? false
        );
        
        $game_ids = Sisme_Utils_Games::get_games_by_criteria($criteria);
        
        if (empty($game_ids)) {
            return self::render_grid_empty($args);
        }
        
        // Debug nombre de jeux trouvés
        if ($args['debug']) {
            error_log('[Sisme Cards API] Jeux trouvés: ' . count($game_ids));
        }
        
        // 2. Générer le HTML de la grille
        return self::render_grid_html($game_ids, $args);
    }

    /**
     * ✅ Valider les arguments de la grille
     * 
     * @param array $args Arguments à valider
     * @return array Résultat de validation
     */
    private static function validate_grid_args($args) {
        
        // Vérifier le type de carte
        if (!in_array($args['type'], self::SUPPORTED_TYPES)) {
            return array(
                'valid' => false,
                'message' => "Type de carte non supporté: {$args['type']}"
            );
        }
        
        // Vérifier cards_per_row
        if (!is_numeric($args['cards_per_row']) || $args['cards_per_row'] < 1 || $args['cards_per_row'] > 6) {
            return array(
                'valid' => false,
                'message' => "cards_per_row doit être entre 1 et 6"
            );
        }
        
        // Vérifier max_cards
        if (!is_numeric($args['max_cards']) || ($args['max_cards'] != -1 && $args['max_cards'] < 1)) {
            return array(
                'valid' => false,
                'message' => "max_cards doit être -1 ou un nombre positif"
            );
        }
        
        // Vérifier genres (doit être un tableau)
        if (!is_array($args[Sisme_Utils_Games::KEY_GENRES])) {
            return array(
                'valid' => false,
                'message' => "genres doit être un tableau"
            );
        }
        
        return array('valid' => true, 'message' => '');
    }

    /**
     * 🏗️ Générer le HTML de la grille de cartes
     * 
     * @param array $game_ids IDs des jeux à afficher
     * @param array $args Paramètres d'affichage
     * @return string HTML de la grille
     */
    private static function render_grid_html($game_ids, $args) {
        
        // Classes CSS de la grille
        $grid_classes = array(
            'sisme-cards-grid',
            'sisme-cards-grid--' . $args['type'],
            'sisme-cards-grid--cols-' . $args['cards_per_row']
        );
        
        if (!empty($args['container_class'])) {
            $grid_classes[] = $args['container_class'];
        }
        
        $grid_class = implode(' ', $grid_classes);
        
        // Variables CSS pour le nombre de colonnes
        $css_vars = '--cards-per-row: ' . $args['cards_per_row'] . ';';
        
        // Début du container
        $output = '<div class="' . esc_attr($grid_class) . '" style="' . esc_attr($css_vars) . '" data-cards-count="' . count($game_ids) . '">';
        
        // Générer chaque carte
        $card_options = array(
            'css_class' => 'sisme-cards-grid__item'
        );
        
        foreach ($game_ids as $game_id) {
            $output .= self::render_card($game_id, $args['type'], $card_options);
        }
        
        $output .= '</div>';
        
        // Ajouter les métadonnées pour le futur carrousel (JSON caché)
        $output .= self::render_grid_metadata($game_ids, $args);
        
        return $output;
    }

    /**
     * 📊 Générer les métadonnées de la grille (pour carrousel futur)
     * 
     * @param array $game_ids IDs des jeux
     * @param array $args Paramètres de la grille
     * @return string HTML avec données JSON
     */
    private static function render_grid_metadata($game_ids, $args) {
        $metadata = array(
            'total_cards' => count($game_ids),
            'cards_per_row' => $args['cards_per_row'],
            'type' => $args['type'],
            'criteria' => array(
                Sisme_Utils_Games::KEY_GENRES => $args[Sisme_Utils_Games::KEY_GENRES],
                Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => $args[Sisme_Utils_Games::KEY_IS_TEAM_CHOICE],
                'sort_by_date' => $args['sort_by_date'],
                'max_cards' => $args['max_cards']
            ),
            'game_ids' => $game_ids,
            'generated_at' => current_time('c')
        );
        
        return '<script type="application/json" class="sisme-cards-data" style="display: none;">' .
               wp_json_encode($metadata) .
               '</script>';
    }

    /**
     * 🚫 Rendu quand aucun jeu trouvé
     * 
     * @param array $args Paramètres pour debug
     * @return string HTML d'état vide
     */
    private static function render_grid_empty($args) {
        $message = 'Aucun jeu trouvé';
        
        // Ajouter des détails en mode debug
        if ($args['debug']) {
            $debug_info = array(
                Sisme_Utils_Games::KEY_GENRES => $args[Sisme_Utils_Games::KEY_GENRES],
                'max_cards' => $args['max_cards'],
                Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => $args[Sisme_Utils_Games::KEY_IS_TEAM_CHOICE]
            );
            $message .= ' (Critères: ' . wp_json_encode($debug_info) . ')';
        }
        
        return '<div class="sisme-cards-grid sisme-cards-grid--empty">' . 
               '<p class="sisme-cards-grid__empty-message">' . esc_html($message) . '</p>' .
               '</div>';
    }


    /**
     * 🎠 Rendre un carrousel de cartes 
     * 
     * @param array $args Paramètres du carrousel
     * @return string HTML du carrousel ou message d'erreur
     */
    public static function render_cards_carousel($args = array()) {
        
        // Vérifier que le module carrousel est disponible
        if (!class_exists('Sisme_Cards_Carousel_Module')) {
            return self::render_error('Module carrousel non chargé');
        }
        
        // Valider les arguments
        $validation = Sisme_Cards_Carousel_Module::validate_carousel_args($args);
        if (!$validation['valid']) {
            return self::render_error($validation['message']);
        }
        
        // Déléguer au module carrousel
        return Sisme_Cards_Carousel_Module::render_carousel($args);
    }
}

// 🎯 SHORTCODE
add_shortcode('game_card', function($atts) {
    $atts = shortcode_atts(array(
        Sisme_Utils_Games::KEY_ID => 0,
        'type' => 'normal',
        'show_description' => 'true',
        'show_genres' => 'true',
        'show_platforms' => 'false',        
        'show_date' => 'true',
        'date_format' => 'short',
        'css_class' => '',
        'max_genres' => '3',
        'max_modes' => '4'
    ), $atts);
    
    $options = array(
        'show_description' => filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN),
        'show_genres' => filter_var($atts['show_genres'], FILTER_VALIDATE_BOOLEAN),
        'show_platforms' => filter_var($atts['show_platforms'], FILTER_VALIDATE_BOOLEAN),
        'show_date' => filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN),
        'date_format' => sanitize_text_field($atts['date_format']),
        'css_class' => $atts['css_class'],
        'max_genres' => intval($atts['max_genres']),
        'max_modes' => intval($atts['max_modes'])
    );
    
    return Sisme_Cards_API::render_card(intval($atts['id']), $atts['type'], $options);
});

// 🎯 SHORTCODE grille
add_shortcode('game_cards_grid', function($atts) {
    $atts = shortcode_atts(array(
        'type' => 'normal',
        'cards_per_row' => '4',
        'max_cards' => '-1',
        Sisme_Utils_Games::KEY_GENRES => '',
        Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => 'false',
        'sort_by_date' => 'true',
        'sort_order' => 'desc',
        'container_class' => '',
        'debug' => 'false',
        'max_genres' => '3',
        'max_modes' => '4',
        Sisme_Utils_Games::KEY_TITLE => '',
        'released' => '0'
    ), $atts);
    
    // Préparer les arguments
    $args = array(
        'type' => sanitize_text_field($atts['type']),
        'cards_per_row' => intval($atts['cards_per_row']),
        'max_cards' => intval($atts['max_cards']),
        Sisme_Utils_Games::KEY_GENRES => !empty($atts[Sisme_Utils_Games::KEY_GENRES]) ? array_map('trim', explode(',', $atts[Sisme_Utils_Games::KEY_GENRES])) : array(),
        Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => filter_var($atts[Sisme_Utils_Games::KEY_IS_TEAM_CHOICE], FILTER_VALIDATE_BOOLEAN),
        'sort_by_date' => filter_var($atts['sort_by_date'], FILTER_VALIDATE_BOOLEAN),
        'sort_order' => in_array($atts['sort_order'], ['asc', 'desc']) ? $atts['sort_order'] : 'desc', // ✅ VALIDATION
        'container_class' => sanitize_text_field($atts['container_class']),
        'debug' => filter_var($atts['debug'], FILTER_VALIDATE_BOOLEAN),
        'max_genres' => intval($atts['max_genres']),
        'max_modes' => intval($atts['max_modes']),
        Sisme_Utils_Games::KEY_TITLE => sanitize_text_field($atts['title']),
        'released' => intval($atts['released'])
    );
    
    return Sisme_Cards_API::render_cards_grid($args);
});

// 🎠 SHORTCODE CARROUSEL
add_shortcode('game_cards_carousel', function($atts) {
    $atts = shortcode_atts(array(
        'cards_per_view' => '3',
        'total_cards' => '9',
        Sisme_Utils_Games::KEY_GENRES => '',
        Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => 'false',
        'sort_by_date' => 'true',
        'sort_order' => 'desc',
        'navigation' => 'true',
        'pagination' => 'true',
        'infinite' => 'true',
        'autoplay' => 'false',
        'debug' => 'false',
        'max_genres' => '3',
        'max_modes' => '4',
        Sisme_Utils_Games::KEY_TITLE => '',
        'released' => '0'
    ), $atts);
    
    // Préparer les arguments
    $args = array(
        'cards_per_view' => intval($atts['cards_per_view']),
        'total_cards' => intval($atts['total_cards']),
        Sisme_Utils_Games::KEY_GENRES => !empty($atts[Sisme_Utils_Games::KEY_GENRES]) ? array_map('trim', explode(',', $atts[Sisme_Utils_Games::KEY_GENRES])) : array(),
        Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => filter_var($atts[Sisme_Utils_Games::KEY_IS_TEAM_CHOICE], FILTER_VALIDATE_BOOLEAN),
        'sort_by_date' => filter_var($atts['sort_by_date'], FILTER_VALIDATE_BOOLEAN),
        'sort_order' => in_array($atts['sort_order'], ['asc', 'desc']) ? $atts['sort_order'] : 'desc', // ✅ VALIDATION
        'navigation' => filter_var($atts['navigation'], FILTER_VALIDATE_BOOLEAN),
        'pagination' => filter_var($atts['pagination'], FILTER_VALIDATE_BOOLEAN),
        'infinite' => filter_var($atts['infinite'], FILTER_VALIDATE_BOOLEAN),
        'autoplay' => filter_var($atts['autoplay'], FILTER_VALIDATE_BOOLEAN),
        'debug' => filter_var($atts['debug'], FILTER_VALIDATE_BOOLEAN),
        'max_genres' => intval($atts['max_genres']),
        'max_modes' => intval($atts['max_modes']),
        Sisme_Utils_Games::KEY_TITLE => sanitize_text_field($atts['title']),
        'released' => intval($atts['released'])
    );
    
    return Sisme_Cards_API::render_cards_carousel($args);
});