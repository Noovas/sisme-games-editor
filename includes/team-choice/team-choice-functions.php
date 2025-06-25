<?php
/**
 * File: /sisme-games-editor/includes/team-choice/team-choice-functions.php
 */

if (!defined('ABSPATH')) {
    exit;
}
class Sisme_Team_Choice_Functions {
    
    /**
     * Marquer un jeu comme choix de l'équipe
     * 
     * @param int $term_id ID du terme/jeu
     * @param bool $is_choice True pour marquer, false pour dé-marquer
     * @return bool Succès de l'opération
     */
    public static function set_team_choice($term_id, $is_choice = true) {
        if (!term_exists($term_id, 'post_tag')) {
            return false;
        }
        
        $value = $is_choice ? '1' : '0';
        $result = update_term_meta($term_id, 'is_team_choice', $value);
        
        // Log pour debug
        error_log("Jeu $term_id marqué comme choix équipe: $value");
        
        return $result !== false;
    }
    
    /**
     * Vérifier si un jeu est un choix de l'équipe
     * 
     * @param int $term_id ID du terme/jeu
     * @return bool True si c'est un choix de l'équipe
     */
    public static function is_team_choice($term_id) {
        $value = get_term_meta($term_id, 'is_team_choice', true);
        return $value === '1';
    }
    
    /**
     * Obtenir tous les jeux marqués comme choix de l'équipe
     * 
     * @param array $args Arguments supplémentaires pour get_terms
     * @return array Liste des jeux choix de l'équipe
     */
    public static function get_team_choice_games($args = array()) {
        $default_args = array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'is_team_choice',
                    'value' => '1',
                    'compare' => '='
                ),
                array(
                    'key' => 'game_description',
                    'compare' => 'EXISTS'
                )
            )
        );
        
        $args = wp_parse_args($args, $default_args);
        return get_terms($args);
    }
    
    /**
     * Obtenir le nombre de jeux choix de l'équipe
     * 
     * @return int Nombre de jeux
     */
    public static function count_team_choice_games() {
        $games = self::get_team_choice_games(array('fields' => 'ids'));
        return count($games);
    }
    
    /**
     * Actions en lot pour plusieurs jeux
     * 
     * @param array $term_ids Liste des IDs de termes
     * @param bool $is_choice True pour marquer, false pour dé-marquer
     * @return array Résultat avec succès et erreurs
     */
    public static function bulk_set_team_choice($term_ids, $is_choice = true) {
        $results = array(
            'success' => array(),
            'errors' => array()
        );
        
        foreach ($term_ids as $term_id) {
            if (self::set_team_choice($term_id, $is_choice)) {
                $results['success'][] = $term_id;
            } else {
                $results['errors'][] = $term_id;
            }
        }
        
        return $results;
    }
}