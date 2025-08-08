<?php
/**
 * File: PHP-admin-games-actions.php
 * Actions administratives pour la gestion des jeux (dépublication, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Games_Actions {
    
    /**
     * Initialiser les hooks AJAX
     */
    public static function init() {
        add_action('wp_ajax_sisme_admin_unpublish_game', [__CLASS__, 'ajax_unpublish_game']);
        add_action('wp_ajax_sisme_admin_republish_game', [__CLASS__, 'ajax_republish_game']);
        add_action('wp_ajax_sisme_admin_set_team_choice', [__CLASS__, 'ajax_set_team_choice']);
        add_action('wp_ajax_sisme_admin_unset_team_choice', [__CLASS__, 'ajax_unset_team_choice']);
    }
    
    /**
     * AJAX : Dépublier un jeu
     */
    public static function ajax_unpublish_game() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_admin_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        $game_term_id = intval($_POST['game_term_id'] ?? 0);
        
        if (!$game_term_id) {
            wp_send_json_error(['message' => 'ID de jeu invalide']);
        }
        
        // Vérifier que le terme existe
        $term = get_term($game_term_id);
        if (is_wp_error($term) || !$term) {
            wp_send_json_error(['message' => 'Jeu non trouvé']);
        }
        
        try {
            // Marquer le jeu comme dépublié
            update_term_meta($game_term_id, 'game_is_unpublished', 'true');
            update_term_meta($game_term_id, 'game_unpublished_at', current_time('mysql'));
            
            // Optionnel : Mettre le post WordPress en brouillon si trouvé
            self::set_wordpress_post_status($game_term_id, 'draft');
            
            wp_send_json_success([
                'message' => 'Jeu dépublié avec succès',
                'new_status' => 'unpublished'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Erreur lors de la dépublication : ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX : Republier un jeu
     */
    public static function ajax_republish_game() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_admin_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        $game_term_id = intval($_POST['game_term_id'] ?? 0);
        
        if (!$game_term_id) {
            wp_send_json_error(['message' => 'ID de jeu invalide']);
        }
        
        try {
            // Retirer le marquage de dépublication
            delete_term_meta($game_term_id, 'game_is_unpublished');
            delete_term_meta($game_term_id, 'game_unpublished_at');
            
            // Optionnel : Remettre le post WordPress en ligne
            self::set_wordpress_post_status($game_term_id, 'publish');
            
            wp_send_json_success([
                'message' => 'Jeu republié avec succès',
                'new_status' => 'published'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Erreur lors de la republication : ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX : Marquer un jeu comme "team choice"
     */
    public static function ajax_set_team_choice() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_admin_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        $game_term_id = intval($_POST['game_term_id'] ?? 0);
        
        if (!$game_term_id) {
            wp_send_json_error(['message' => 'ID de jeu invalide']);
        }
        
        // Vérifier que le terme existe
        $term = get_term($game_term_id);
        if (is_wp_error($term) || !$term) {
            wp_send_json_error(['message' => 'Jeu non trouvé']);
        }
        
        try {
            // Marquer le jeu comme team choice
            update_term_meta($game_term_id, 'is_team_choice', 'true');
            update_term_meta($game_term_id, 'team_choice_added_at', current_time('mysql'));
            
            wp_send_json_success([
                'message' => 'Jeu ajouté aux coups de cœur de l\'équipe',
                'new_status' => 'team_choice'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Erreur lors de l\'ajout aux coups de cœur : ' . $e->getMessage()]);
        }
    }
    
    /**
     * AJAX : Retirer un jeu des "team choice"
     */
    public static function ajax_unset_team_choice() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_admin_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        $game_term_id = intval($_POST['game_term_id'] ?? 0);
        
        if (!$game_term_id) {
            wp_send_json_error(['message' => 'ID de jeu invalide']);
        }
        
        try {
            // Retirer le marquage team choice
            delete_term_meta($game_term_id, 'is_team_choice');
            delete_term_meta($game_term_id, 'team_choice_added_at');
            
            wp_send_json_success([
                'message' => 'Jeu retiré des coups de cœur de l\'équipe',
                'new_status' => 'not_team_choice'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => 'Erreur lors du retrait des coups de cœur : ' . $e->getMessage()]);
        }
    }
    
    /**
     * Changer le statut du post WordPress associé
     */
    private static function set_wordpress_post_status($game_term_id, $status) {
        // Trouver le post associé via les tags
        $posts = get_posts([
            'tag__in' => [$game_term_id],
            'post_type' => 'post',
            'post_status' => ['publish', 'draft'],
            'numberposts' => 1
        ]);
        
        if (!empty($posts)) {
            wp_update_post([
                'ID' => $posts[0]->ID,
                'post_status' => $status
            ]);
        }
    }
    
    /**
     * Vérifier si un jeu est dépublié
     */
    public static function is_game_unpublished($game_term_id) {
        $is_unpublished = get_term_meta($game_term_id, 'game_is_unpublished', true);
        return $is_unpublished === 'true' || $is_unpublished === true;
    }
    
    /**
     * Vérifier si un jeu est un coup de cœur de l'équipe
     */
    public static function is_team_choice($game_term_id) {
        $is_team_choice = get_term_meta($game_term_id, 'is_team_choice', true);
        return $is_team_choice === 'true' || $is_team_choice === true;
    }
}
