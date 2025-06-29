<?php
/**
 * File: /sisme-games-editor/includes/user/user-notifications/user-notifications-ajax.php
 * Handlers AJAX pour les notifications utilisateur
 * 
 * RESPONSABILITÉ:
 * - Gestion requêtes AJAX marquer comme lue
 * - Récupération notifications via AJAX
 * - Validation sécurité et paramètres
 * - Réponses JSON structurées
 * 
 * DÉPENDANCES:
 * - Sisme_User_Notifications_Data_Manager
 * - Sisme_User_Notifications_API
 * - WordPress AJAX API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Notifications_Ajax {
    
    /**
     * Initialiser les hooks AJAX
     */
    public static function init() {
        add_action('wp_ajax_sisme_mark_notification_read', [self::class, 'ajax_mark_as_read']);
        add_action('wp_ajax_sisme_get_user_notifications', [self::class, 'ajax_get_notifications']);
        
        add_action('wp_ajax_nopriv_sisme_mark_notification_read', [self::class, 'ajax_not_logged_in']);
        add_action('wp_ajax_nopriv_sisme_get_user_notifications', [self::class, 'ajax_not_logged_in']);
    }
    
    /**
     * Handler AJAX marquer notification comme lue
     */
    public static function ajax_mark_as_read() {
        if (!check_ajax_referer('sisme_user_notifications_nonce', 'security', false)) {
            wp_send_json_error([
                'message' => __('Erreur de sécurité', 'sisme-games-editor'),
                'code' => 'invalid_nonce'
            ]);
        }
        
        if (!isset($_POST['notification_index'])) {
            wp_send_json_error([
                'message' => __('Index de notification manquant', 'sisme-games-editor'),
                'code' => 'missing_index'
            ]);
        }
        
        $notification_index = intval($_POST['notification_index']);
        $user_id = get_current_user_id();
        
        if ($notification_index < 0) {
            wp_send_json_error([
                'message' => __('Index de notification invalide', 'sisme-games-editor'),
                'code' => 'invalid_index'
            ]);
        }
        
        $success = Sisme_User_Notifications_Data_Manager::mark_as_read($user_id, $notification_index);
        
        if (!$success) {
            wp_send_json_error([
                'message' => __('Erreur lors du marquage', 'sisme-games-editor'),
                'code' => 'mark_failed'
            ]);
        }
        
        $new_unread_count = Sisme_User_Notifications_Data_Manager::get_unread_count($user_id);
        
        wp_send_json_success([
            'message' => __('Notification marquée comme lue', 'sisme-games-editor'),
            'unread_count' => $new_unread_count,
            'notification_index' => $notification_index
        ]);
    }
    
    /**
     * Handler AJAX récupérer notifications
     */
    public static function ajax_get_notifications() {
        if (!check_ajax_referer('sisme_user_notifications_nonce', 'security', false)) {
            wp_send_json_error([
                'message' => __('Erreur de sécurité', 'sisme-games-editor'),
                'code' => 'invalid_nonce'
            ]);
        }
        
        $user_id = get_current_user_id();
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 20;
        
        if ($limit <= 0 || $limit > 50) {
            $limit = 20;
        }
        
        try {
            $data = Sisme_User_Notifications_API::get_notifications_ajax_data($user_id, $limit);
            
            wp_send_json_success($data);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => __('Erreur lors de la récupération', 'sisme-games-editor'),
                'code' => 'fetch_failed',
                'debug' => defined('WP_DEBUG') && WP_DEBUG ? $e->getMessage() : null
            ]);
        }
    }
    
    /**
     * Handler AJAX utilisateur non connecté
     */
    public static function ajax_not_logged_in() {
        wp_send_json_error([
            'message' => __('Vous devez être connecté', 'sisme-games-editor'),
            'code' => 'not_logged_in',
            'redirect' => wp_login_url()
        ]);
    }
}