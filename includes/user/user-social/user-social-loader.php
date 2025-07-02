<?php
/**
 * File: /sisme-games-editor/includes/user/user-social/user-social-loader.php
 * Loader du module user-social - Gestion des relations sociales
 * 
 * RESPONSABILITÉ:
 * - Chargement du module social (amis, demandes)
 * - Initialisation des composants sociaux
 * - Gestion des hooks AJAX pour les interactions sociales
 * - Point d'entrée singleton pour les fonctionnalités sociales
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Social_Loader {
    
    private static $instance = null;
    
    /**
     * Singleton - Instance unique
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé (singleton)
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialisation du module social
     */
    private function init() {
        $this->load_dependencies();
        $this->register_hooks();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Social] Module social initialisé');
        }
    }
    
    /**
     * Charger les dépendances du module
     */
    private function load_dependencies() {
        $module_dir = plugin_dir_path(__FILE__);
        
        // Charger l'API sociale
        $api_file = $module_dir . 'user-social-api.php';
        if (file_exists($api_file)) {
            require_once $api_file;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Social] Dépendances chargées');
        }
    }
    
    /**
     * Enregistrer les hooks du module social
     */
    private function register_hooks() {
        // Hooks AJAX pour utilisateurs connectés
        add_action('wp_ajax_sisme_send_friend_request', [$this, 'handle_send_friend_request']);
        add_action('wp_ajax_sisme_accept_friend_request', [$this, 'handle_accept_friend_request']);
        add_action('wp_ajax_sisme_decline_friend_request', [$this, 'handle_decline_friend_request']);
        add_action('wp_ajax_sisme_remove_friend', [$this, 'handle_remove_friend']);
        add_action('wp_ajax_sisme_cancel_friend_request', [$this, 'handle_cancel_friend_request']);
        
        // Hook pour initialiser les métadonnées des nouveaux utilisateurs
        add_action('user_register', [$this, 'init_user_social_metadata']);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Social] Hooks AJAX enregistrés');
        }
    }
    
    /**
     * Gérer l'envoi d'une demande d'ami via AJAX
     */
    public function handle_send_friend_request() {
        // Vérifier le nonce pour la sécurité
        if (!wp_verify_nonce($_POST['nonce'], 'sisme_social_nonce')) {
            wp_die('Erreur de sécurité');
        }
        
        // Vérifier que l'utilisateur est connecté
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $sender_id = get_current_user_id();
        $receiver_id = intval($_POST['user_id']);
        
        // Valider l'ID du destinataire
        if (!Sisme_Utils_Users::validate_user_id($receiver_id, 'send_friend_request')) {
            wp_send_json_error('Utilisateur destinataire invalide');
        }
        
        // Éviter l'auto-ajout
        if ($sender_id === $receiver_id) {
            wp_send_json_error('Vous ne pouvez pas vous ajouter vous-même');
        }
        
        // Envoyer la demande via l'API
        $result = Sisme_User_Social_API::send_friend_request($sender_id, $receiver_id);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Gérer l'acceptation d'une demande d'ami via AJAX
     */
    public function handle_accept_friend_request() {
        if (!wp_verify_nonce($_POST['nonce'], 'sisme_social_nonce')) {
            wp_die('Erreur de sécurité');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $receiver_id = get_current_user_id();
        $sender_id = intval($_POST['user_id']);
        
        if (!Sisme_Utils_Users::validate_user_id($sender_id, 'accept_friend_request')) {
            wp_send_json_error('Utilisateur expéditeur invalide');
        }
        
        $result = Sisme_User_Social_API::accept_friend_request($sender_id, $receiver_id);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Gérer le refus d'une demande d'ami via AJAX
     */
    public function handle_decline_friend_request() {
        if (!wp_verify_nonce($_POST['nonce'], 'sisme_social_nonce')) {
            wp_die('Erreur de sécurité');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $receiver_id = get_current_user_id();
        $sender_id = intval($_POST['user_id']);
        
        if (!Sisme_Utils_Users::validate_user_id($sender_id, 'decline_friend_request')) {
            wp_send_json_error('Utilisateur expéditeur invalide');
        }
        
        $result = Sisme_User_Social_API::decline_friend_request($sender_id, $receiver_id);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Gérer la suppression d'un ami via AJAX
     */
    public function handle_remove_friend() {
        if (!wp_verify_nonce($_POST['nonce'], 'sisme_social_nonce')) {
            wp_die('Erreur de sécurité');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $user_id = get_current_user_id();
        $friend_id = intval($_POST['user_id']);
        
        if (!Sisme_Utils_Users::validate_user_id($friend_id, 'remove_friend')) {
            wp_send_json_error('Utilisateur ami invalide');
        }
        
        $result = Sisme_User_Social_API::remove_friend($user_id, $friend_id);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Gérer l'annulation d'une demande d'ami via AJAX
     */
    public function handle_cancel_friend_request() {
        if (!wp_verify_nonce($_POST['nonce'], 'sisme_social_nonce')) {
            wp_die('Erreur de sécurité');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $sender_id = get_current_user_id();
        $receiver_id = intval($_POST['user_id']);
        
        if (!Sisme_Utils_Users::validate_user_id($receiver_id, 'cancel_friend_request')) {
            wp_send_json_error('Utilisateur destinataire invalide');
        }
        
        $result = Sisme_User_Social_API::cancel_friend_request($sender_id, $receiver_id);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Initialiser les métadonnées sociales pour un nouvel utilisateur
     */
    public function init_user_social_metadata($user_id) {
        if (!Sisme_Utils_Users::validate_user_id($user_id, 'init_social_metadata')) {
            return;
        }
        
        // Initialiser la liste d'amis vide
        $empty_friends_list = [];
        update_user_meta($user_id, Sisme_Utils_Users::META_FRIENDS_LIST, $empty_friends_list);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Social] Métadonnées sociales initialisées pour utilisateur {$user_id}");
        }
    }
}