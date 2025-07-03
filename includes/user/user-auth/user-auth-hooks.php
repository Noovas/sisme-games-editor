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
        
        // ❌ SUPPRIMÉ : Ces hooks empêchaient l'envoi des emails
        // add_action('lostpassword_post', [__CLASS__, 'handle_lost_password_request']);
        // add_action('resetpass_post', [__CLASS__, 'handle_reset_password_request']);
        
        // ✅ NOUVEAUX HOOKS : Intercepter APRÈS l'envoi d'email
        add_action('lostpassword_post', [__CLASS__, 'track_password_reset_request'], 1);
        add_filter('wp_mail', [__CLASS__, 'track_password_reset_email'], 10, 1);
        
        // Personnaliser les emails de réinitialisation
        add_filter('retrieve_password_message', [__CLASS__, 'custom_password_reset_email'], 10, 4);
        add_filter('retrieve_password_title', [__CLASS__, 'custom_password_reset_subject'], 10, 2);
        
        // Redirection après succès d'envoi d'email
        add_action('wp_redirect', [__CLASS__, 'handle_password_reset_redirect'], 10, 2);
        
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
        $redirect_url = home_url(Sisme_Utils_Users::RESET_PASSWORD_URL);
        
        // Préserver le token s'il existe
        if (isset($_GET['key']) && isset($_GET['login'])) {
            $redirect_url = add_query_arg([
                'key' => sanitize_text_field($_GET['key']),
                'login' => sanitize_text_field($_GET['login'])
            ], home_url(Sisme_Utils_Users::RESET_PASSWORD_URL));
        }
        
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Suivre les demandes de réinitialisation de mot de passe
     */
    public static function track_password_reset_request() {
        if (!session_id()) {
            session_start();
        }
        
        // Marquer qu'une demande est en cours
        $_SESSION['sisme_password_reset_requested'] = true;
        $_SESSION['sisme_password_reset_time'] = time();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Auth Hooks] Demande de réinitialisation trackée');
        }
    }
    
    /**
     * Suivre l'envoi des emails de réinitialisation
     */
    public static function track_password_reset_email($args) {
        if (!session_id()) {
            session_start();
        }
        
        // Vérifier si c'est un email de réinitialisation
        if (isset($_SESSION['sisme_password_reset_requested']) && 
            $_SESSION['sisme_password_reset_requested'] &&
            isset($args['subject']) && 
            strpos($args['subject'], 'réinitialisation') !== false) {
            
            // Marquer l'email comme envoyé
            $_SESSION['sisme_password_reset_email_sent'] = true;
            $_SESSION['sisme_password_reset_email_to'] = $args['to'];
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme User Auth Hooks] Email de réinitialisation envoyé à: ' . $args['to']);
            }
        }
        
        return $args;
    }
    
    /**
     * Gérer les redirections après reset password
     */
    public static function handle_password_reset_redirect($location, $status) {
        if (!session_id()) {
            session_start();
        }
        
        // Vérifier si c'est une redirection après reset password
        if (isset($_SESSION['sisme_password_reset_requested']) && 
            $_SESSION['sisme_password_reset_requested'] &&
            strpos($location, 'wp-login.php') !== false &&
            strpos($location, 'checkemail=confirm') !== false) {
            
            // Nettoyer les sessions
            $email_sent = isset($_SESSION['sisme_password_reset_email_sent']) && $_SESSION['sisme_password_reset_email_sent'];
            unset($_SESSION['sisme_password_reset_requested']);
            unset($_SESSION['sisme_password_reset_email_sent']);
            unset($_SESSION['sisme_password_reset_email_to']);
            unset($_SESSION['sisme_password_reset_time']);
            
            // Rediriger vers notre page avec le bon message
            $message = $email_sent ? 'email_sent' : 'email_error';
            $redirect_url = add_query_arg([
                'message' => $message
            ], home_url(Sisme_Utils_Users::FORGOT_PASSWORD_URL));
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme User Auth Hooks] Redirection vers: ' . $redirect_url);
            }
            
            return $redirect_url;
        }
        
        return $location;
    }
    
    /**
     * Personnaliser l'email de réinitialisation
     */
    public static function custom_password_reset_email($message, $key, $user_login, $user_data) {
        $reset_url = add_query_arg([
            'key' => $key,
            'login' => $user_login
        ], home_url(Sisme_Utils_Users::RESET_PASSWORD_URL));
        
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