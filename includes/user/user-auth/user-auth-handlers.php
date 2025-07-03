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

        // Traitement mot de passe oublié
        if (isset($_POST['sisme_user_forgot_password_submit'])) {
            self::process_forgot_password_form();
        }

        // Traitement nouveau mot de passe
        if (isset($_POST['sisme_user_reset_password_submit'])) {
            self::process_reset_password_form();
        }
    }

    /**
     * Traiter le formulaire de mot de passe oublié
     */
    private static function process_forgot_password_form() {
        // Sécurité : vérifier le nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'sisme_user_forgot_password')) {
            wp_die('Erreur de sécurité. Veuillez recharger la page.');
        }
        
        // Sanitiser les données
        $email = sanitize_email($_POST['reset_email'] ?? '');
        
        if (empty($email)) {
            self::set_auth_message('Veuillez saisir une adresse email.', 'error');
            return;
        }
        
        if (!is_email($email)) {
            self::set_auth_message('Veuillez saisir une adresse email valide.', 'error');
            return;
        }
        
        // Vérifier si l'utilisateur existe
        $user = get_user_by('email', $email);
        if (!$user) {
            // Email inexistant - Message de sécurité (ne pas révéler si l'email existe)
            self::set_auth_message('Si cette adresse email existe, vous recevrez un lien de réinitialisation.', 'success');
            return;
        }
        
        // Email existant - Envoyer l'email de réinitialisation
        $result = retrieve_password($user->user_login);
        
        if (is_wp_error($result)) {
            self::set_auth_message('Erreur lors de l\'envoi de l\'email. Veuillez réessayer.', 'error');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme User Auth] Erreur retrieve_password: ' . $result->get_error_message());
            }
        } else {
            // Succès - Même message que pour email inexistant (sécurité)
            self::set_auth_message('Si cette adresse email existe, vous recevrez un lien de réinitialisation.', 'success');
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme User Auth] Email de réinitialisation envoyé pour: ' . $email);
            }
        }
    }
    
    /**
     * Traiter le formulaire de nouveau mot de passe
     */
    private static function process_reset_password_form() {
        // Sécurité : vérifier le nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'sisme_user_reset_password')) {
            wp_die('Erreur de sécurité. Veuillez recharger la page.');
        }
        
        // Récupérer les données
        $token = sanitize_text_field($_POST['reset_token'] ?? '');
        $login = sanitize_text_field($_POST['login'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_new_password'] ?? '';
        
        // Validations
        if (empty($token)) {
            self::set_auth_message('Token de réinitialisation manquant.', 'error');
            return;
        }
        
        if (empty($new_password) || empty($confirm_password)) {
            self::set_auth_message('Veuillez remplir tous les champs.', 'error');
            return;
        }
        
        if ($new_password !== $confirm_password) {
            self::set_auth_message('Les mots de passe ne correspondent pas.', 'error');
            return;
        }
        
        if (strlen($new_password) < 8) {
            self::set_auth_message('Le mot de passe doit contenir au moins 8 caractères.', 'error');
            return;
        }
        
        // Vérifier et traiter le token
        $result = self::handle_password_reset($token, $login, $new_password);
        
        if (is_wp_error($result)) {
            self::set_auth_message($result->get_error_message(), 'error');
        } else {
            self::set_auth_message('Votre mot de passe a été modifié avec succès.', 'success');
            
            // Rediriger vers la page de connexion après succès
            wp_safe_redirect(add_query_arg(['message' => 'password_changed'], home_url(Sisme_Utils_Users::LOGIN_URL)));
            exit;
        }
    }
    
    /**
     * Gérer la réinitialisation du mot de passe avec token
     */
    private static function handle_password_reset($token, $login, $new_password) {
        // Utiliser la fonction WordPress pour vérifier le token
        $user = check_password_reset_key($token, $login);
        
        if (is_wp_error($user)) {
            return new WP_Error('invalid_token', 'Le lien de réinitialisation est invalide ou a expiré.');
        }
        
        // Changer le mot de passe
        reset_password($user, $new_password);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Auth] Mot de passe réinitialisé pour utilisateur: ' . $user->ID);
        }
        
        return true;
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
        
        // Préparer le display_name - OBLIGATOIRE maintenant
        $display_name = !empty($data['display_name']) ? 
            sanitize_text_field(trim($data['display_name'])) : 
            $data['user_email'];
        
        // FORCER le user_nicename basé sur le display_name (pas l'email)
        $user_nicename = sanitize_title($display_name);
        
        // S'assurer que le slug est unique
        $user_nicename = self::make_user_nicename_unique($user_nicename);
        
        // Préparer les données utilisateur complètes
        $user_data = [
            'user_login' => $data['user_email'],         // Email pour login
            'user_pass' => $data['user_password'],       // Mot de passe
            'user_email' => $data['user_email'],         // Email
            'user_nicename' => $user_nicename,           // SLUG basé sur pseudo
            'display_name' => $display_name,             // Pseudo visible
            'nickname' => $display_name,                 // Surnom (cohérence)
            'first_name' => '',                          // Prénom vide par défaut
            'last_name' => '',                           // Nom vide par défaut
            'role' => 'subscriber'                       // Rôle par défaut
        ];
        
        // Créer l'utilisateur avec wp_insert_user - MÉTHODE SAFE
        $user_id = wp_insert_user($user_data);
        
        if (is_wp_error($user_id)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Auth] Erreur wp_insert_user: " . $user_id->get_error_message());
            }
            return $user_id;
        }
        
        // Vérification de sécurité - S'assurer que tout est correct
        $created_user = get_userdata($user_id);
        if (!$created_user || 
            $created_user->display_name !== $display_name ||
            $created_user->user_nicename !== $user_nicename) {
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Auth] ALERTE: Données utilisateur incorrectes après création");
                error_log("Display attendu: {$display_name}, reçu: " . ($created_user ? $created_user->display_name : 'NULL'));
                error_log("Nicename attendu: {$user_nicename}, reçu: " . ($created_user ? $created_user->user_nicename : 'NULL'));
            }
            
            // Correction de sécurité si nécessaire
            wp_update_user([
                'ID' => $user_id,
                'display_name' => $display_name,
                'user_nicename' => $user_nicename
            ]);
        }
        
        // Initialiser les métadonnées gaming
        self::init_user_gaming_meta($user_id);
        
        // Connecter automatiquement l'utilisateur
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, !empty($data['remember_me']));
        
        // Hook pour extensions
        do_action('sisme_user_register_success', $user_id, $data);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Auth] ✅ Inscription SAFE réussie - Email: {$data['user_email']}, ID: {$user_id}, Display: {$display_name}, Slug: {$user_nicename}");
        }
        
        return [
            'success' => true,
            'user_id' => $user_id,
            'message' => 'Inscription réussie ! Bienvenue ' . $display_name . ' !',
            'redirect_to' => home_url(Sisme_Utils_Users::DASHBOARD_URL)
        ];
    }

    /**
     * Rendre un user_nicename unique
     * @param string $nicename Nicename de base
     * @return string Nicename unique
     */
    private static function make_user_nicename_unique($nicename) {
        if (empty($nicename)) {
            $nicename = 'user' . wp_rand(1000, 9999);
        }
        
        global $wpdb;
        $original_nicename = $nicename;
        $counter = 1;
        
        // Vérifier l'unicité
        while (true) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->users} WHERE user_nicename = %s LIMIT 1",
                $nicename
            ));
            
            if (!$existing) {
                break; // Nicename disponible
            }
            
            $nicename = $original_nicename . '-' . $counter;
            $counter++;
            
            // Sécurité anti-boucle infinie
            if ($counter > 999) {
                $nicename = $original_nicename . '-' . wp_rand(1000, 9999);
                break;
            }
        }
        
        return $nicename;
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
     * Nettoyer les sessions lors de la déconnexion
     */
    public static function cleanup_auth_session() {
        if (!session_id()) {
            session_start();
        }        
        unset($_SESSION['sisme_auth_message']);
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