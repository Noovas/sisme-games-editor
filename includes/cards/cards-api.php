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
        'css_class' => ''
    ), $atts);
    
    // Convertir les strings en boolean
    $options = array(
        'show_description' => filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN),
        'show_genres' => filter_var($atts['show_genres'], FILTER_VALIDATE_BOOLEAN),
        'show_platforms' => filter_var($atts['show_platforms'], FILTER_VALIDATE_BOOLEAN),
        'show_date' => filter_var($atts['show_date'], FILTER_VALIDATE_BOOLEAN),
        'css_class' => $atts['css_class']
    );
    
    return Sisme_Cards_API::render_card(intval($atts['id']), $atts['type'], $options);
});

?>