<?php
/**
 * File: /sisme-games-editor/includes/user/user-auth/user-auth-hooks.php
 * Hooks et redirections pour l'authentification utilisateur
 * 
 * RESPONSABILITÉ:
 * - Rediriger les pages WP vers les nôtres
 * - Hooks de connexion/déconnexion
 * - Personnalisation des messages d'erreur
 * - Intégration avec le système WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Auth_Hooks {
    
    /**
     * Initialiser les hooks
     */
    public static function init() {
        // Redirections des pages WordPress
        add_action('login_form_lostpassword', [__CLASS__, 'redirect_to_custom_forgot_password']);
        add_action('login_form_retrievepassword', [__CLASS__, 'redirect_to_custom_forgot_password']);
        add_action('login_form_resetpass', [__CLASS__, 'redirect_to_custom_reset_password']);
        add_action('login_form_rp', [__CLASS__, 'redirect_to_custom_reset_password']);
        
        // Intercepter les formulaires de perte de mot de passe
        add_action('lostpassword_post', [__CLASS__, 'handle_lost_password_request']);
        add_action('resetpass_post', [__CLASS__, 'handle_reset_password_request']);
        
        // Personnaliser les emails de réinitialisation
        add_filter('retrieve_password_message', [__CLASS__, 'custom_password_reset_email'], 10, 4);
        add_filter('retrieve_password_title', [__CLASS__, 'custom_password_reset_subject'], 10, 2);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Auth Hooks] Hooks d\'authentification initialisés');
        }
    }
    
    /**
     * Rediriger vers notre page de mot de passe oublié
     */
    public static function redirect_to_custom_forgot_password() {
        $redirect_url = home_url(Sisme_Utils_Users::FORGOT_PASSWORD_URL);
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Rediriger vers notre page de réinitialisation
     */
    public static function redirect_to_custom_reset_password() {
        $redirect_url = home_url(Sisme_Utils_Users::FORGOT_PASSWORD_URL);
        
        // Préserver le token s'il existe
        if (isset($_GET['key']) && isset($_GET['login'])) {
            $redirect_url = add_query_arg([
                'token' => sanitize_text_field($_GET['key']),
                'login' => sanitize_text_field($_GET['login'])
            ], home_url(Sisme_Utils_Users::FORGOT_PASSWORD_URL));
        }
        
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Traiter la demande de réinitialisation de mot de passe
     */
    public static function handle_lost_password_request() {
        // Laisser WordPress traiter la demande normalement
        // Mais rediriger vers notre page après
        add_action('lostpassword_post', function() {
            $redirect_url = add_query_arg([
                'message' => 'email_sent'
            ], home_url(Sisme_Utils_Users::FORGOT_PASSWORD_URL));
            
            wp_safe_redirect($redirect_url);
            exit;
        }, 999);
    }
    
    /**
     * Traiter la réinitialisation du mot de passe
     */
    public static function handle_reset_password_request() {
        // Laisser WordPress traiter la réinitialisation
        // Rediriger vers login après succès
        add_action('password_reset', function() {
            $redirect_url = add_query_arg([
                'message' => 'password_reset'
            ], home_url(Sisme_Utils_Users::LOGIN_URL));
            
            wp_safe_redirect($redirect_url);
            exit;
        }, 999);
    }
    
    /**
     * Personnaliser l'email de réinitialisation
     */
    public static function custom_password_reset_email($message, $key, $user_login, $user_data) {
        $reset_url = add_query_arg([
            'token' => $key,
            'login' => $user_login
        ], home_url(Sisme_Utils_Users::FORGOT_PASSWORD_URL));
        
        $site_name = get_bloginfo('name');
        $site_url = home_url();
        
        $message = "Bonjour,\n\n";
        $message .= "Une demande de réinitialisation de mot de passe a été effectuée pour votre compte sur {$site_name}.\n\n";
        $message .= "Pour choisir un nouveau mot de passe, cliquez sur le lien suivant :\n";
        $message .= "{$reset_url}\n\n";
        $message .= "Ce lien est valable pendant 24 heures.\n\n";
        $message .= "Si vous n'avez pas demandé cette réinitialisation, ignorez simplement cet email.\n\n";
        $message .= "Cordialement,\n";
        $message .= "L'équipe {$site_name}\n";
        $message .= "{$site_url}";
        
        return $message;
    }
    
    /**
     * Personnaliser le sujet de l'email
     */
    public static function custom_password_reset_subject($title, $user_login) {
        $site_name = get_bloginfo('name');
        return "[{$site_name}] Réinitialisation de votre mot de passe";
    }
}

// Initialiser les hooks
Sisme_User_Auth_Hooks::init();