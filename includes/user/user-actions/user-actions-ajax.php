<?php
/**
 * File: user-actions-ajax.php
 * Module: Handlers AJAX pour User Actions
 * 
 * Gère les requêtes AJAX pour les interactions utilisateur-jeux.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Actions_Ajax {
    
    /**
     * Initialiser les hooks AJAX
     */
    public static function init() {
        // Actions AJAX (utilisateurs connectés)
        add_action('wp_ajax_sisme_toggle_game_collection', [self::class, 'ajax_toggle_game_collection']);
        
        // Actions AJAX (non connectés) - Redirection vers connexion
        add_action('wp_ajax_nopriv_sisme_toggle_game_collection', [self::class, 'ajax_not_logged_in']);
    }
    
    /**
     * Handler AJAX pour basculer un jeu dans une collection
     */
    public static function ajax_toggle_game_collection() {
        // Vérifier le nonce de sécurité
        if (!check_ajax_referer('sisme_user_actions_nonce', 'security', false)) {
            wp_send_json_error([
                'message' => __('Erreur de sécurité', 'sisme-games-editor'),
                'code' => 'invalid_nonce'
            ]);
        }
        
        // Vérifier les paramètres requis
        if (!isset($_POST['game_id']) || !isset($_POST['collection_type'])) {
            wp_send_json_error([
                'message' => __('Paramètres manquants', 'sisme-games-editor'),
                'code' => 'missing_params'
            ]);
        }
        
        // Récupérer et valider les paramètres
        $game_id = intval($_POST['game_id']);
        $collection_type = sanitize_text_field($_POST['collection_type']);
        $user_id = get_current_user_id();
        
        // Vérifier que le jeu existe
        $term = get_term($game_id, 'post_tag');
        if (!$term || is_wp_error($term)) {
            wp_send_json_error([
                'message' => __('Jeu non trouvé', 'sisme-games-editor'),
                'code' => 'game_not_found'
            ]);
        }
        
        // Vérifier que le type de collection est valide
        $valid_collections = [
            Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE,
            Sisme_User_Actions_Data_Manager::COLLECTION_OWNED
        ];
        
        if (!in_array($collection_type, $valid_collections)) {
            wp_send_json_error([
                'message' => __('Type de collection invalide', 'sisme-games-editor'),
                'code' => 'invalid_collection'
            ]);
        }
        
        // Basculer le jeu dans la collection
        $result = Sisme_User_Actions_Data_Manager::toggle_game_in_user_collection(
            $user_id,
            $game_id,
            $collection_type
        );
        
        if (!$result['success']) {
            wp_send_json_error([
                'message' => __('Erreur lors de la mise à jour de la collection', 'sisme-games-editor'),
                'code' => 'update_failed'
            ]);
        }
        
        // Préparation de la réponse
        $response = [
            'message' => $result['message'],
            'status' => $result['status'],
            'game_id' => $game_id,
            'collection_type' => $collection_type,
            'html' => Sisme_User_Actions_API::render_action_button($game_id, $collection_type, ['update' => true])
        ];
        
        // Ajouter les statistiques pour ce jeu
        $response['stats'] = [
            'count' => Sisme_User_Actions_Data_Manager::get_game_collection_stats($game_id, $collection_type)
        ];
        
        wp_send_json_success($response);
    }
    
    /**
     * Handler AJAX pour les utilisateurs non connectés
     */
    public static function ajax_not_logged_in() {
        wp_send_json_error([
            'message' => __('Vous devez être connecté pour effectuer cette action', 'sisme-games-editor'),
            'code' => 'not_logged_in',
            'login_url' => wp_login_url(get_permalink())
        ]);
    }
}