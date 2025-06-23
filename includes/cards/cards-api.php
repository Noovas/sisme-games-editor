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
require_once dirname(__FILE__) . '/cards-functions.php';
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
        $game_data = Sisme_Cards_Functions::get_game_data($game_id);
        if (!$game_data) {
            return self::render_error("Données du jeu incomplètes");
        }
        
        // Déléguer selon le type
        switch ($type) {
            case 'normal':
                return Sisme_Cards_Normal_Module::render($game_data, $options);
            
            case 'details':
                return self::render_todo("Carte Details - À venir");
            
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
            'cards_per_row' => 3,                  // Nombre de cartes par ligne
            'max_cards' => -1,                     // Nombre max (-1 = illimité)
            'genres' => array(),                   // Liste des genres
            'is_team_choice' => false,             // Choix équipe (à venir)
            'sort_by_date' => true,                // Tri par date de sortie
            'container_class' => '',               // Classe CSS personnalisée
            'debug' => false                       // Mode debug
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
        
        // 1. Récupérer les IDs des jeux via cards-functions
        $criteria = array(
            'genres' => $args['genres'],
            'is_team_choice' => $args['is_team_choice'],
            'sort_by_date' => $args['sort_by_date'],
            'max_results' => $args['max_cards'],
            'debug' => $args['debug']
        );
        
        $game_ids = Sisme_Cards_Functions::get_games_by_criteria($criteria);
        
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
        if (!is_array($args['genres'])) {
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
                'genres' => $args['genres'],
                'is_team_choice' => $args['is_team_choice'],
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
                'genres' => $args['genres'],
                'max_cards' => $args['max_cards'],
                'is_team_choice' => $args['is_team_choice']
            );
            $message .= ' (Critères: ' . wp_json_encode($debug_info) . ')';
        }
        
        return '<div class="sisme-cards-grid sisme-cards-grid--empty">' . 
               '<p class="sisme-cards-grid__empty-message">' . esc_html($message) . '</p>' .
               '</div>';
    }

    /**
     * 🔧 Fonction utilitaire pour débugger une grille
     * 
     * @param array $args Arguments de grille
     * @return string HTML avec informations de debug
     */
    public static function debug_grid($args = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return self::render_error('Debug mode non activé');
        }
        
        $args['debug'] = true;
        
        // Tester les critères
        $criteria = array(
            'genres' => $args['genres'] ?? array(),
            'is_team_choice' => $args['is_team_choice'] ?? false,
            'sort_by_date' => $args['sort_by_date'] ?? true,
            'max_results' => $args['max_cards'] ?? -1,
            'debug' => true
        );
        
        $test_result = Sisme_Cards_Functions::test_criteria($criteria);
        $stats = Sisme_Cards_Functions::get_games_stats_by_criteria($criteria);
        
        $debug_html = '<div class="sisme-cards-debug" style="background: #f8f9fa; padding: 1rem; margin: 1rem 0; border-left: 4px solid #007cba;">';
        $debug_html .= '<h4>🐛 Debug Grille de Cartes</h4>';
        $debug_html .= '<p><strong>Jeux trouvés:</strong> ' . $test_result['stats']['found_count'] . '</p>';
        $debug_html .= '<p><strong>Temps d\'exécution:</strong> ' . $test_result['execution_time'] . '</p>';
        $debug_html .= '<p><strong>Statistiques globales:</strong> ' . $stats['games_with_data'] . '/' . $stats['total_games'] . ' jeux avec données complètes</p>';
        
        if (!empty($test_result['sample_games'])) {
            $debug_html .= '<p><strong>Échantillon:</strong></p><ul>';
            foreach ($test_result['sample_games'] as $game) {
                $debug_html .= '<li>' . esc_html($game['name']) . ' (' . esc_html($game['release_date']) . ') - Genres: ' . implode(', ', $game['genres']) . '</li>';
            }
            $debug_html .= '</ul>';
        }
        
        $debug_html .= '</div>';
        
        return $debug_html . self::render_cards_grid($args);
    }

    /**
     * 🎠 Rendre un carrousel de cartes - NOUVEAU POINT D'ENTRÉE
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

// 🎯 SHORTCODE pour tester
add_shortcode('game_card', function($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
        'type' => 'normal',
        'show_description' => 'true',
        'show_genres' => 'true',
        'show_platforms' => 'false',
        'show_date' => 'true',
        'css_class' => '',
        'max_genres' => '3',
        'max_modes' => '4'
    ), $atts);
    
    $options = array(
        'show_description' => filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN),
        'show_genres' => filter_var($atts['show_genres'], FILTER_VALIDATE_BOOLEAN),
        'show_platforms' => filter_var($atts['show_platforms'], FILTER_VALIDATE_BOOLEAN),
        'show_date' => filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN),
        'css_class' => $atts['css_class'],
        'max_genres' => intval($atts['max_genres']),
        'max_modes' => intval($atts['max_modes'])
    );
    
    return Sisme_Cards_API::render_card(intval($atts['id']), $atts['type'], $options);
});

// 🎯 SHORTCODE principal pour la grille
add_shortcode('game_cards_grid', function($atts) {
    $atts = shortcode_atts(array(
        'type' => 'normal',
        'cards_per_row' => '3',
        'max_cards' => '-1',
        'genres' => '',
        'is_team_choice' => 'false',
        'sort_by_date' => 'true',
        'container_class' => '',
        'debug' => 'false'
    ), $atts);
    
    // Préparer les arguments
    $args = array(
        'type' => sanitize_text_field($atts['type']),
        'cards_per_row' => intval($atts['cards_per_row']),
        'max_cards' => intval($atts['max_cards']),
        'genres' => !empty($atts['genres']) ? array_map('trim', explode(',', $atts['genres'])) : array(),
        'is_team_choice' => filter_var($atts['is_team_choice'], FILTER_VALIDATE_BOOLEAN),
        'sort_by_date' => filter_var($atts['sort_by_date'], FILTER_VALIDATE_BOOLEAN),
        'container_class' => sanitize_html_class($atts['container_class']),
        'debug' => filter_var($atts['debug'], FILTER_VALIDATE_BOOLEAN)
    );
    
    return Sisme_Cards_API::render_cards_grid($args);
});

// 🔧 SHORTCODE de debug pour tester
add_shortcode('debug_cards_grid', function($atts) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return '<p style="color: #dc3232;">Shortcode debug disponible uniquement en mode WP_DEBUG</p>';
    }
    
    $atts = shortcode_atts(array(
        'type' => 'normal',
        'cards_per_row' => '3',
        'max_cards' => '6',
        'genres' => '',
        'is_team_choice' => 'false',
        'sort_by_date' => 'true',
        'container_class' => ''
    ), $atts);
    
    // Préparer les arguments
    $args = array(
        'type' => sanitize_text_field($atts['type']),
        'cards_per_row' => intval($atts['cards_per_row']),
        'max_cards' => intval($atts['max_cards']),
        'genres' => !empty($atts['genres']) ? array_map('trim', explode(',', $atts['genres'])) : array(),
        'is_team_choice' => filter_var($atts['is_team_choice'], FILTER_VALIDATE_BOOLEAN),
        'sort_by_date' => filter_var($atts['sort_by_date'], FILTER_VALIDATE_BOOLEAN),
        'container_class' => sanitize_html_class($atts['container_class'])
    );
    
    return Sisme_Cards_API::debug_grid($args);
});

// 📊 SHORTCODE pour afficher les statistiques
add_shortcode('cards_stats', function($atts) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return '<p style="color: #dc3232;">Shortcode stats disponible uniquement en mode WP_DEBUG</p>';
    }
    
    $atts = shortcode_atts(array(
        'genres' => '',
        'is_team_choice' => 'false'
    ), $atts);
    
    $criteria = array(
        'genres' => !empty($atts['genres']) ? array_map('trim', explode(',', $atts['genres'])) : array(),
        'is_team_choice' => filter_var($atts['is_team_choice'], FILTER_VALIDATE_BOOLEAN)
    );
    
    $stats = Sisme_Cards_Functions::get_games_stats_by_criteria($criteria);
    
    $output = '<div class="sisme-cards-stats" style="background: #f0f8ff; padding: 1rem; margin: 1rem 0; border-radius: 8px;">';
    $output .= '<h4>📊 Statistiques des Jeux</h4>';
    $output .= '<p><strong>Total jeux:</strong> ' . $stats['total_games'] . '</p>';
    $output .= '<p><strong>Jeux avec données complètes:</strong> ' . $stats['games_with_data'] . '</p>';
    
    if (!empty($stats['games_by_genre'])) {
        $output .= '<p><strong>Répartition par genre:</strong></p><ul>';
        arsort($stats['games_by_genre']);
        foreach (array_slice($stats['games_by_genre'], 0, 10) as $genre => $count) {
            $output .= '<li>' . esc_html($genre) . ': ' . $count . ' jeux</li>';
        }
        $output .= '</ul>';
    }
    
    if (!empty($stats['date_range']['oldest']) && !empty($stats['date_range']['newest'])) {
        $output .= '<p><strong>Période:</strong> ' . $stats['date_range']['oldest'] . ' → ' . $stats['date_range']['newest'] . '</p>';
    }
    
    $output .= '</div>';
    
    return $output;
});

// 🎠 SHORTCODE principal pour le carrousel
add_shortcode('game_cards_carousel', function($atts) {
    $atts = shortcode_atts(array(
        'cards_per_view' => '3',
        'total_cards' => '9',
        'type' => 'normal',
        'genres' => '',
        'is_team_choice' => 'false',
        'sort_by_date' => 'true',
        'infinite' => 'true',
        'navigation' => 'true',
        'pagination' => 'true',
        'debug' => 'false',
        'max_genres' => '3', 
        'max_modes' => '4'        
    ), $atts);
    
    // Préparer les arguments 
    $args = array(
        'cards_per_view' => intval($atts['cards_per_view']),
        'total_cards' => intval($atts['total_cards']),
        'type' => sanitize_text_field($atts['type']),
        'genres' => !empty($atts['genres']) ? array_map('trim', explode(',', $atts['genres'])) : array(),
        'is_team_choice' => filter_var($atts['is_team_choice'], FILTER_VALIDATE_BOOLEAN),
        'sort_by_date' => filter_var($atts['sort_by_date'], FILTER_VALIDATE_BOOLEAN),
        'infinite' => filter_var($atts['infinite'], FILTER_VALIDATE_BOOLEAN),
        'navigation' => filter_var($atts['navigation'], FILTER_VALIDATE_BOOLEAN),
        'pagination' => filter_var($atts['pagination'], FILTER_VALIDATE_BOOLEAN),
        'debug' => filter_var($atts['debug'], FILTER_VALIDATE_BOOLEAN),
        'max_genres' => intval($atts['max_genres']),
        'max_modes' => intval($atts['max_modes'])
    );
    
    return Sisme_Cards_API::render_cards_carousel($args);
});

// 🔧 SHORTCODE de debug pour le carrousel
add_shortcode('debug_cards_carousel', function($atts) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return '<p style="color: #dc3232;">Shortcode debug carrousel disponible uniquement en mode WP_DEBUG</p>';
    }
    
    $atts = shortcode_atts(array(
        'cards_per_view' => '3',
        'total_cards' => '6',
        'genres' => ''
    ), $atts);
    
    // Forcer le debug
    $args = array(
        'cards_per_view' => intval($atts['cards_per_view']),
        'total_cards' => intval($atts['total_cards']),
        'genres' => !empty($atts['genres']) ? array_map('trim', explode(',', $atts['genres'])) : array(),
        'debug' => true
    );
    
    return Sisme_Cards_API::render_cards_carousel($args);
});