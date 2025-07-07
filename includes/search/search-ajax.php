<?php
/**
 * File: /sisme-games-editor/includes/search/search-ajax.php
 * MODULE SEARCH REFAIT - Handlers AJAX
 * 
 * FONCTIONNALITÉS:
 * - Handler unique pour la recherche
 * - Intégration avec Cards API
 * - Gestion des erreurs
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Search_Ajax {
    
    /**
     * Initialiser les handlers AJAX
     */
    public static function init() {
        // Handler principal pour la recherche
        add_action('wp_ajax_sisme_search_games', array(__CLASS__, 'handle_search_games'));
        add_action('wp_ajax_nopriv_sisme_search_games', array(__CLASS__, 'handle_search_games'));
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Search AJAX] Handlers enregistrés');
        }
    }
    
    /**
     * Handler principal pour la recherche de jeux
     */
    public static function handle_search_games() {
        try {
            // Vérification de sécurité
            if (!self::verify_nonce()) {
                self::send_error('Erreur de sécurité', 403);
                return;
            }
            
            // Récupérer et valider les paramètres
            $params = self::extract_search_params();
            
            // Vérifier les dépendances
            if (!self::check_dependencies()) {
                self::send_error('Modules requis non disponibles', 500);
                return;
            }
            
            // Effectuer la recherche
            $results = self::perform_search($params);
            
            if (!$results['success']) {
                self::send_error($results['message'], 400);
                return;
            }
            
            // Envoyer les résultats
            self::send_success($results);
            
        } catch (Exception $e) {
            error_log('[Sisme Search AJAX] Erreur: ' . $e->getMessage());
            self::send_error('Erreur interne du serveur', 500);
        }
    }
    
    /**
     * Vérifier le nonce de sécurité
     * 
     * @return bool True si valide
     */
    private static function verify_nonce() {
        $nonce = $_POST['nonce'] ?? '';
        return wp_verify_nonce($nonce, 'sisme_search_nonce');
    }
    
    /**
     * Extraire les paramètres de recherche
     * 
     * @return array Paramètres nettoyés
     */
    private static function extract_search_params() {
        return array(
            'query' => sanitize_text_field($_POST['query'] ?? ''),
            'genre' => sanitize_text_field($_POST['genre'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? ''),
            'columns' => max(1, min(6, intval($_POST['columns'] ?? 4))),
            'max_results' => max(1, min(50, intval($_POST['max_results'] ?? 12)))
        );
    }
    
    /**
     * Vérifier les dépendances requises
     * 
     * @return bool True si toutes les dépendances sont disponibles
     */
    private static function check_dependencies() {
        return class_exists('Sisme_Utils_Games') && 
               class_exists('Sisme_Cards_API') &&
               class_exists('Sisme_Search_API');
    }
    
    /**
     * Effectuer la recherche
     * 
     * @param array $params Paramètres de recherche
     * @return array Résultats de la recherche
     */
    private static function perform_search($params) {
        // Valider les paramètres
        $validation = self::validate_search_params($params);
        
        if (!$validation['valid']) {
            return array(
                'success' => false,
                'message' => $validation['message']
            );
        }
        
        // Construire les critères pour utils-games
        $criteria = self::build_search_criteria($validation['params']);
        
        // 1. Récupérer tous les jeux selon les critères (genre, statut)
        $game_ids = Sisme_Utils_Games::get_games_by_criteria($criteria);
        
        // 2. Appliquer la recherche textuelle avec utils-filters
        if (!empty($validation['params']['query'])) {
            if (class_exists('Sisme_Utils_Filters')) {
                $game_ids = Sisme_Utils_Filters::filter_by_search_term($game_ids, $validation['params']['query']);
            }
        }
        
        // 3. Limiter au nombre de résultats demandés
        if ($validation['params']['max_results'] > 0 && count($game_ids) > $validation['params']['max_results']) {
            $game_ids = array_slice($game_ids, 0, $validation['params']['max_results']);
        }
        
        // Générer le HTML des résultats
        $html = self::generate_results_html($game_ids, $validation['params']);
        
        // Préparer les métadonnées
        $metadata = self::prepare_metadata($game_ids, $validation['params']);
        
        return array(
            'success' => true,
            'html' => $html,
            'total' => count($game_ids),
            'params' => $validation['params'],
            'metadata' => $metadata
        );
    }
    
    /**
     * Valider les paramètres de recherche
     * 
     * @param array $params Paramètres bruts
     * @return array Résultat de validation
     */
    private static function validate_search_params($params) {
        $validated = array(
            'query' => trim($params['query']),
            'genre' => $params['genre'],
            'status' => $params['status'],
            'columns' => $params['columns'],
            'max_results' => $params['max_results']
        );
        
        // Vérifier qu'au moins un critère est fourni
        if (empty($validated['query']) && empty($validated['genre']) && empty($validated['status'])) {
            return array(
                'valid' => false,
                'message' => 'Veuillez saisir au moins un critère de recherche'
            );
        }
        
        // Vérifier la longueur minimum de la recherche textuelle
        if (!empty($validated['query']) && strlen($validated['query']) < 2) {
            return array(
                'valid' => false,
                'message' => 'Le terme de recherche doit contenir au moins 2 caractères'
            );
        }
        
        return array(
            'valid' => true,
            'params' => $validated
        );
    }
    
    /**
     * Construire les critères pour utils-games
     * 
     * @param array $params Paramètres validés
     * @return array Critères pour get_games_by_criteria
     */
    private static function build_search_criteria($params) {
        $criteria = array(
            'sort_by_date' => true,
            'sort_order' => 'desc',
            'max_results' => -1, // Récupérer tous pour filtrer après
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        );
        
        // NOTE: utils-games ne gère PAS le paramètre 'search'
        // La recherche textuelle sera faite après avec Sisme_Utils_Filters::filter_by_search_term
        
        // Filtre par genre (utiliser le slug directement)
        if (!empty($params['genre'])) {
            // Trouver l'ID de la catégorie depuis le slug
            $genre_category = get_category_by_slug($params['genre']);
            if ($genre_category) {
                $criteria[Sisme_Utils_Games::KEY_GENRES] = array($genre_category->term_id);
            }
        }
        
        // Filtre par statut
        if (!empty($params['status'])) {
            if ($params['status'] === 'released') {
                $criteria['released'] = 1;
            } elseif ($params['status'] === 'upcoming') {
                $criteria['released'] = -1;
            }
        }
        
        return $criteria;
    }
    
    /**
     * Générer le HTML des résultats
     * 
     * @param array $game_ids IDs des jeux trouvés
     * @param array $params Paramètres de recherche
     * @return string HTML des résultats
     */
    private static function generate_results_html($game_ids, $params) {
        if (empty($game_ids)) {
            return self::render_no_results($params);
        }
        
        // Utiliser Cards API pour générer la grille
        $grid_args = array(
            'type' => 'normal',
            'cards_per_row' => $params['columns'],
            'max_cards' => count($game_ids),
            'container_class' => 'sisme-search-results-grid'
        );
        
        // Créer une grille avec les IDs spécifiques
        return self::render_cards_grid_with_ids($game_ids, $grid_args);
    }
    
    /**
     * Rendu d'une grille de cartes avec IDs spécifiques
     * 
     * @param array $game_ids IDs des jeux
     * @param array $grid_args Arguments de la grille
     * @return string HTML de la grille
     */
    private static function render_cards_grid_with_ids($game_ids, $grid_args) {
        // Classes CSS de la grille
        $grid_classes = array(
            'sisme-cards-grid',
            'sisme-cards-grid--' . $grid_args['type'],
            'sisme-cards-grid--cols-' . $grid_args['cards_per_row']
        );
        
        if (!empty($grid_args['container_class'])) {
            $grid_classes[] = $grid_args['container_class'];
        }
        
        $grid_class = implode(' ', $grid_classes);
        
        // Variables CSS
        $css_vars = '--cards-per-row: ' . $grid_args['cards_per_row'] . ';';
        
        // Début du container
        $output = '<div class="' . esc_attr($grid_class) . '" style="' . esc_attr($css_vars) . '" data-cards-count="' . count($game_ids) . '">';
        
        // Générer chaque carte
        foreach ($game_ids as $game_id) {
            $card_options = array(
                'css_class' => 'sisme-cards-grid__item'
            );
            
            $output .= Sisme_Cards_API::render_card($game_id, $grid_args['type'], $card_options);
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Rendu du message "aucun résultat"
     * 
     * @param array $params Paramètres de recherche
     * @return string HTML du message
     */
    private static function render_no_results($params) {
        $message = 'Aucun jeu trouvé';
        
        // Personnaliser le message selon les critères
        $criteria = array();
        
        if (!empty($params['query'])) {
            $criteria[] = 'pour "' . esc_html($params['query']) . '"';
        }
        
        if (!empty($params['genre'])) {
            $genre_name = str_replace('jeux-', '', $params['genre']);
            $criteria[] = 'dans le genre ' . esc_html($genre_name);
        }
        
        if (!empty($params['status'])) {
            $status_text = $params['status'] === 'released' ? 'sortis' : 'à venir';
            $criteria[] = 'parmi les jeux ' . $status_text;
        }
        
        if (!empty($criteria)) {
            $message .= ' ' . implode(' ', $criteria);
        }
        
        return '<div class="sisme-search-no-results">' .
               '<div class="sisme-search-no-results-icon">🔍</div>' .
               '<h3 class="sisme-search-no-results-title">' . esc_html($message) . '</h3>' .
               '<p class="sisme-search-no-results-text">Essayez avec d\'autres critères de recherche.</p>' .
               '</div>';
    }
    
    /**
     * Préparer les métadonnées de la recherche
     * 
     * @param array $game_ids IDs des jeux trouvés
     * @param array $params Paramètres de recherche
     * @return array Métadonnées
     */
    private static function prepare_metadata($game_ids, $params) {
        return array(
            'total_results' => count($game_ids),
            'search_query' => $params['query'],
            'genre_filter' => $params['genre'],
            'status_filter' => $params['status'],
            'columns' => $params['columns'],
            'max_results' => $params['max_results'],
            'generated_at' => current_time('c')
        );
    }
    
    /**
     * Envoyer une réponse de succès
     * 
     * @param array $data Données à envoyer
     */
    private static function send_success($data) {
        wp_send_json_success($data);
    }
    
    /**
     * Envoyer une réponse d'erreur
     * 
     * @param string $message Message d'erreur
     * @param int $code Code d'erreur HTTP
     */
    private static function send_error($message, $code = 400) {
        // Définir le code de statut HTTP
        if ($code !== 200) {
            status_header($code);
        }
        
        wp_send_json_error(array(
            'message' => $message,
            'code' => $code
        ));
    }
}