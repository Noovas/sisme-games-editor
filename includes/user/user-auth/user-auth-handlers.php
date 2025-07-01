<?php
/**
 * File: /sisme-games-editor/includes/user/user-auth/user-auth-handlers.php
 * Gestionnaire de traitement pour l'authentification utilisateur
 * 
 * RESPONSABILITÉ:
 * - Traitement des connexions
 * - Traitement des inscriptions
 * - Gestion des sessions
 * - Handlers AJAX
 * - Initialisation des métadonnées utilisateur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Auth_Handlers {
    
    /**
     * Initialiser la gestion des requêtes
     */
    public static function init_request_handling() {
        // Traitement des formulaires POST
        //add_action('init', [__CLASS__, 'process_auth_forms'], 1);
        self::process_auth_forms();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Auth Handlers] Gestionnaire de traitement initialisé');
        }
    }
    
    /**
     * Traiter les formulaires d'authentification
     */
    public static function process_auth_forms() {
        // Traitement connexion
        if (isset($_POST['sisme_user_login_submit'])) {
            self::process_login_form();
        }
        
        // Traitement inscription
        if (isset($_POST['sisme_user_register_submit'])) {
            self::process_register_form();
        }
    }
    
    /**
     * Traiter le formulaire de connexion
     */
    private static function process_login_form() {
        // Sécurité : vérifier le nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'sisme_user_login')) {
            wp_die('Erreur de sécurité. Veuillez recharger la page.');
        }
        
        // Sanitiser les données
        $data = Sisme_User_Auth_Security::sanitize_user_data($_POST);
        
        // Traiter la connexion
        $result = self::handle_login($data);
        
        if (is_wp_error($result)) {
            // Stocker l'erreur dans la session
            self::set_auth_message($result->get_error_message(), 'error');
        } else {
            // Redirection après connexion réussie
            $redirect_url = !empty($data['redirect_to']) ? $data['redirect_to'] : home_url('/mon-profil/');
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Traiter le formulaire d'inscription
     */
    private static function process_register_form() {
        // Sécurité : vérifier le nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'sisme_user_register')) {
            wp_die('Erreur de sécurité. Veuillez recharger la page.');
        }
        
        // Sanitiser les données
        $data = Sisme_User_Auth_Security::sanitize_user_data($_POST);
        
        // Traiter l'inscription
        $result = self::handle_register($data);
        
        if (is_wp_error($result)) {
            // Stocker l'erreur dans la session
            self::set_auth_message($result->get_error_message(), 'error');
        } else {
            // Message de succès et redirection
            self::set_auth_message('Inscription réussie ! Vous êtes maintenant connecté.', 'success');
            $redirect_url = !empty($data['redirect_to']) ? $data['redirect_to'] : home_url('/mon-profil/');
            wp_safe_redirect($redirect_url);
            exit;
        }
    }
    
    /**
     * Traiter une connexion utilisateur
     */
    public static function handle_login($data) {
        // Validation sécurité préalable
        $security_check = Sisme_User_Auth_Security::validate_login_attempt($data['user_email']);
        if (is_wp_error($security_check)) {
            return $security_check;
        }
        
        // Validation des données
        $validation = Sisme_User_Auth_Security::validate_user_data($data, 'login');
        if (is_array($validation)) {
            $error_message = 'Erreurs de validation : ' . implode(', ', $validation);
            return new WP_Error('validation_failed', $error_message);
        }
        
        // Tentative de connexion WordPress
        $creds = [
            'user_login' => $data['user_email'],
            'user_password' => $data['user_password'],
            'remember' => !empty($data['remember_me'])
        ];
        
        $user = wp_signon($creds, false);
        
        if (is_wp_error($user)) {
            // Enregistrer l'échec
            Sisme_User_Auth_Security::record_failed_attempt($data['user_email']);
            
            // Retourner une erreur générique pour éviter le fishing
            return new WP_Error('login_failed', 'Email ou mot de passe incorrect.');
        }
        
        // Succès : nettoyer les tentatives échouées
        Sisme_User_Auth_Security::clear_failed_attempts($data['user_email']);
        
        // Définir l'utilisateur courant
        wp_set_current_user($user->ID);
        
        // Mettre à jour la dernière connexion
        update_user_meta($user->ID, 'sisme_last_login', current_time('mysql'));
        
        // Hook pour extensions
        do_action('sisme_user_login_success', $user->ID, $data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Auth Handlers] Connexion réussie pour : {$data['user_email']} (ID: {$user->ID})");
        }
        
        return [
            'success' => true,
            'user_id' => $user->ID,
            'redirect_to' => !empty($data['redirect_to']) ? $data['redirect_to'] : home_url('/mon-profil/')
        ];
    }
    
    /**
     * Traiter une inscription utilisateur
     */
    public static function handle_register($data) {
        // Validation des données
        $validation = Sisme_User_Auth_Security::validate_user_data($data, 'register');
        if (is_array($validation)) {
            $error_message = 'Erreurs de validation : ' . implode(', ', $validation);
            return new WP_Error('validation_failed', $error_message);
        }
        
        // Créer l'utilisateur WordPress
        $user_id = wp_create_user(
            $data['user_email'], 
            $data['user_password'], 
            $data['user_email']
        );
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Mettre à jour le profil utilisateur
        $user_data = [
            Sisme_Utils_Games::KEY_ID => $user_id,
            'display_name' => !empty($data['user_display_name']) ? $data['user_display_name'] : $data['user_email']
        ];
        
        $update_result = wp_update_user($user_data);
        if (is_wp_error($update_result)) {
            // L'utilisateur est créé mais on n'a pas pu mettre à jour le profil
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Auth Handlers] Erreur mise à jour profil pour user ID $user_id : " . $update_result->get_error_message());
            }
        }
        
        // Initialiser les métadonnées gaming
        self::init_user_gaming_meta($user_id);
        
        // Connecter automatiquement l'utilisateur
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, !empty($data['remember_me']));
        
        // Hook pour extensions
        do_action('sisme_user_register_success', $user_id, $data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Auth Handlers] Inscription réussie pour : {$data['user_email']} (ID: $user_id)");
        }
        
        return [
            'success' => true,
            'user_id' => $user_id,
            'message' => 'Inscription réussie ! Vous êtes maintenant connecté.'
        ];
    }
    
    /**
     * Initialiser les métadonnées gaming pour un nouvel utilisateur
     */
    private static function init_user_gaming_meta($user_id) {
        $default_meta = [
            // Ludothèque
            'sisme_favorite_games' => [],
            'sisme_wishlist_games' => [],
            'sisme_completed_games' => [],
            'sisme_user_reviews' => [],
            
            // Préférences gaming
            'sisme_gaming_platforms' => [],
            'sisme_favorite_genres' => [],
            
            // Paramètres
            'sisme_notifications_email' => true,
            'sisme_privacy_level' => 'public',
            
            // Métadonnées système
            'sisme_profile_created' => current_time('mysql'),
            'sisme_last_login' => current_time('mysql'),
            'sisme_profile_version' => '1.0'
        ];
        
        foreach ($default_meta as $key => $value) {
            update_user_meta($user_id, $key, $value);
        }
        
        // Hook pour permettre l'extension des métadonnées par défaut
        do_action('sisme_user_init_meta', $user_id, $default_meta);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Auth Handlers] Métadonnées gaming initialisées pour user ID: $user_id");
        }
    }
    
    /**
     * Handler AJAX pour connexion
     */
    public static function ajax_login() {
        // Vérifier le nonce
        if (!check_ajax_referer('sisme_user_auth_nonce', 'nonce', false)) {
            wp_send_json_error('Erreur de sécurité.');
        }
        
        // Sanitiser les données
        $data = Sisme_User_Auth_Security::sanitize_user_data($_POST);
        
        // Traiter la connexion
        $result = self::handle_login($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success([
                'message' => 'Connexion réussie !',
                'redirect_to' => $result['redirect_to']
            ]);
        }
    }
    
    /**
     * Handler AJAX pour inscription
     */
    public static function ajax_register() {
        // Vérifier le nonce
        if (!check_ajax_referer('sisme_user_auth_nonce', 'nonce', false)) {
            wp_send_json_error('Erreur de sécurité.');
        }
        
        // Sanitiser les données
        $data = Sisme_User_Auth_Security::sanitize_user_data($_POST);
        
        // Traiter l'inscription
        $result = self::handle_register($data);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success([
                'message' => $result['message'],
                'redirect_to' => !empty($data['redirect_to']) ? $data['redirect_to'] : home_url('/mon-profil/')
            ]);
        }
    }
    
    /**
     * Définir un message d'authentification
     */
    private static function set_auth_message($message, $type = 'info') {
        if (!session_id()) {
            session_start();
        }
        
        $_SESSION['sisme_auth_message'] = [
            'text' => $message,
            'type' => $type
        ];
    }
    
    /**
     * Récupérer et supprimer un message d'authentification
     */
    public static function get_auth_message() {
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['sisme_auth_message'])) {
            $message = $_SESSION['sisme_auth_message'];
            unset($_SESSION['sisme_auth_message']);
            return $message;
        }
        
        return null;
    }
    
    /**
     * Obtenir les métadonnées utilisateur par défaut
     */
    public static function get_default_user_meta() {
        return [
            'sisme_favorite_games' => [],
            'sisme_wishlist_games' => [],
            'sisme_completed_games' => [],
            'sisme_user_reviews' => [],
            'sisme_gaming_platforms' => [],
            'sisme_favorite_genres' => [],
            'sisme_notifications_email' => true,
            'sisme_privacy_level' => 'public'
        ];
    }
    
    /**
     * Traitement de déconnexion personnalisé
     */
    public static function handle_logout($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id) {
            // Mettre à jour la dernière activité
            update_user_meta($user_id, 'sisme_last_logout', current_time('mysql'));
            
            // Hook pour extensions
            do_action('sisme_user_logout', $user_id);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Auth Handlers] Déconnexion pour user ID: $user_id");
            }
        }
        
        // Déconnexion WordPress standard
        wp_logout();
    }
}