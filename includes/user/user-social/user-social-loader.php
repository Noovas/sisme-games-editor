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

        add_action('wp_ajax_sisme_search_users', [$this, 'handle_search_users']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);

        // Hook pour initialiser les métadonnées des nouveaux utilisateurs
        add_action('user_register', [$this, 'init_user_social_metadata']);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Social] Hooks AJAX enregistrés');
        }
    }

    /**
     * Charger les assets frontend
     */
    public function enqueue_frontend_assets() {
        // Charger seulement si on est sur les pages avec dashboard/profil
        if (!$this->should_load_assets()) {
            return;
        }
        
        // Localisation des scripts pour AJAX et sécurité
        wp_localize_script('sisme-user-dashboard', 'sismeUserSocial', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_user_social_nonce'),
            'user_id' => get_current_user_id(),
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ]);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Social] Variables JavaScript localisées');
        }
    }

    /**
     * AJOUTER CETTE NOUVELLE MÉTHODE - Vérifier si charger les assets
     */
    private function should_load_assets() {
        // Charger sur les pages dashboard et profil
        global $post;
        
        if (is_admin()) {
            return false;
        }
        
        // Vérifier les URLs spécifiques
        $current_url = $_SERVER['REQUEST_URI'] ?? '';
        $dashboard_pages = [
            Sisme_Utils_Users::DASHBOARD_URL,
            Sisme_Utils_Users::PROFILE_URL
        ];
        
        foreach ($dashboard_pages as $page_url) {
            if (strpos($current_url, $page_url) !== false) {
                return true;
            }
        }
        
        // Vérifier si on a le shortcode dans le contenu
        if ($post && (
            has_shortcode($post->post_content, 'sisme_user_dashboard') ||
            has_shortcode($post->post_content, 'sisme_user_profile')
        )) {
            return true;
        }
        
        return false;
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
     * Handler AJAX pour la recherche d'utilisateurs
     */
    public function handle_search_users() {
        // Vérification de sécurité
        if (!check_ajax_referer('sisme_user_social_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Token de sécurité invalide']);
            return;
        }
        
        // Vérification que l'utilisateur est connecté
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Connexion requise']);
            return;
        }
        
        // Récupération et validation du terme de recherche
        $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';
        
        if (strlen($search_term) < 2) {
            wp_send_json_success([
                'results' => [],
                'message' => 'Tapez au moins 2 caractères'
            ]);
            return;
        }
        
        // Recherche des utilisateurs
        if (!class_exists('Sisme_Utils_Users')) {
            wp_send_json_error(['message' => 'Service de recherche indisponible']);
            return;
        }
        
        $users = Sisme_Utils_Users::search_users_by_display_name($search_term, 10);
        
        // Formatage de la réponse
        if (empty($users)) {
            wp_send_json_success([
                'results' => [],
                'message' => 'Aucun résultat pour "' . esc_html($search_term) . '"'
            ]);
        } else {
            wp_send_json_success([
                'results' => $users,
                'message' => count($users) . ' utilisateur(s) trouvé(s)'
            ]);
        }
    }

    /**
     * HANDLER AJAX à ajouter côté PHP pour le compteur d'amis
     */
    public function handle_get_friends_count() {
        if (!wp_verify_nonce($_POST['nonce'], 'sisme_user_social_nonce')) {
            wp_die('Erreur de sécurité');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $user_id = get_current_user_id();
        $social_stats = Sisme_User_Social_API::get_user_social_stats($user_id);
        
        wp_send_json_success(['count' => $social_stats['friends_count']]);
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