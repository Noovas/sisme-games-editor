<?php
/**
 * File: /sisme-games-editor/includes/utils/utils-filters.php
 * Fonctions utilitaires de filtrage et recherche de jeux
 * 
 * RESPONSABILITÉ:
 * - Recherche textuelle dans les noms de jeux
 * - Tri des jeux par différents critères
 * - Conversion entre IDs et noms de genres
 * - Formatage des résumés de recherche
 * 
 * DÉPENDANCES:
 * - WordPress core functions (get_term, get_term_meta)
 * - Sisme_Utils_Games (constantes META)
 * - WordPress i18n functions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Utils_Filters {
    /**
     * Filtrer les jeux par terme de recherche textuelle
     * Recherche dans les noms des jeux (tags WordPress)
     * 
     * @param array $game_ids IDs des jeux à filtrer
     * @param string $search_term Terme de recherche
     * @return array IDs des jeux correspondants
     */
    public static function filter_by_search_term($game_ids, $search_term) {
        if (empty($search_term) || empty($game_ids)) {
            return $game_ids;
        }
        $matching_ids = array();
        $search_term_lower = strtolower(trim($search_term));
        foreach ($game_ids as $game_id) {
            $game_term = get_term($game_id);
            if (!$game_term || is_wp_error($game_term)) {
                continue;
            }
            $game_name = strtolower($game_term->name);
            if (strpos($game_name, $search_term_lower) !== false) {
                $matching_ids[] = $game_id;
                continue;
            }
        }
        return $matching_ids;
    }
    
    /**
     * Appliquer le tri aux résultats
     * 
     * @param array $games Liste des IDs de jeux
     * @param string $sort_type Type de tri
     * @return array IDs des jeux triés
     */
    public static function apply_sorting($games, $sort_type) {
        if (empty($games)) {
            return $games;
        }
        if (empty($sort_type) || $sort_type === 'relevance') {
            return $games;
        }
        switch ($sort_type) {
            case 'name_asc':
                usort($games, function($id_a, $id_b) {
                    $name_a = get_term($id_a)->name ?? '';
                    $name_b = get_term($id_b)->name ?? '';
                    return strcasecmp($name_a, $name_b);
                });
                break;
            case 'name_desc':
                usort($games, function($id_a, $id_b) {
                    $name_a = get_term($id_a)->name ?? '';
                    $name_b = get_term($id_b)->name ?? '';
                    return strcasecmp($name_b, $name_a);
                });
                break;
            case 'date_desc':
                usort($games, function($id_a, $id_b) {
                    $date_a = get_term_meta($id_a, Sisme_Utils_Games::META_RELEASE_DATE, true) ?: '1970-01-01';
                    $date_b = get_term_meta($id_b, Sisme_Utils_Games::META_RELEASE_DATE, true) ?: '1970-01-01';
                    return strcmp($date_b, $date_a);
                });
                break;
            case 'date_asc':
                usort($games, function($id_a, $id_b) {
                    $date_a = get_term_meta($id_a, Sisme_Utils_Games::META_RELEASE_DATE, true) ?: '1970-01-01';
                    $date_b = get_term_meta($id_b, Sisme_Utils_Games::META_RELEASE_DATE, true) ?: '1970-01-01';
                    return strcmp($date_a, $date_b);
                });
                break;
                
            default:
                break;
        }
        return $games;
    }
    
    /**
     * Générer un résumé de recherche lisible
     * 
     * @param array $params Paramètres de recherche
     * @param int $total_results Nombre total de résultats
     * @return string Résumé lisible
     */
    public static function get_search_summary($params, $total_results) {
        $summary_parts = array();
        $summary_parts[] = sprintf(
            _n('%d jeu trouvé', '%d jeux trouvés', $total_results, 'sisme-games-editor'),
            $total_results
        );
        if (!empty($params['query'])) {
            $summary_parts[] = sprintf(
                __('pour "%s"', 'sisme-games-editor'),
                esc_html($params['query'])
            );
        }
        $active_filters = array();
        if (!empty($params[Sisme_Utils_Games::KEY_GENRES])) {
            $active_filters[] = sprintf(
                _n('%d genre', '%d genres', count($params[Sisme_Utils_Games::KEY_GENRES]), 'sisme-games-editor'),
                count($params[Sisme_Utils_Games::KEY_GENRES])
            );
        }
        if (!empty($params['platforms'])) {
            $active_filters[] = sprintf(
                _n('%d plateforme', '%d plateformes', count($params['platforms']), 'sisme-games-editor'),
                count($params['platforms'])
            );
        }
        if (!empty($params['status'])) {
            $status_text = ($params['status'] === 'released') ? __('sortis', 'sisme-games-editor') : __('à venir', 'sisme-games-editor');
            $active_filters[] = $status_text;
        }
        if (!empty($active_filters)) {
            $summary_parts[] = __('avec filtres:', 'sisme-games-editor') . ' ' . implode(', ', $active_filters);
        }
        return implode(' ', $summary_parts);
    }
}