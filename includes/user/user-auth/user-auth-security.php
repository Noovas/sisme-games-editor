<?php
/**
 * File: /sisme-games-editor/includes/user/user-auth/user-auth-security.php
 * Module de sécurité pour l'authentification utilisateur
 * 
 * RESPONSABILITÉ:
 * - Rate limiting (limitation tentatives de connexion)
 * - Validation des données utilisateur
 * - Sécurisation des formulaires
 * - Protection contre les attaques
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Auth_Security {
    
    // Configuration de sécurité
    private static $max_attempts = 5;           // Max 5 tentatives
    private static $lockout_time = 900;         // 15 minutes de blocage
    private static $cleanup_interval = 3600;    // Nettoyage chaque heure
    
    /**
     * Initialisation des hooks de sécurité
     */
    public static function init() {
        // Nettoyage périodique des tentatives expirées
        add_action('wp_loaded', [__CLASS__, 'schedule_cleanup']);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Auth Security] Module de sécurité initialisé');
        }
    }
    
    /**
     * Valider une tentative de connexion (rate limiting)
     */
    public static function validate_login_attempt($email) {
        $attempts_key = self::get_attempts_key($email);
        $attempts_data = get_transient($attempts_key);
        
        if ($attempts_data === false) {
            return true; // Aucune tentative précédente
        }
        
        $attempts_count = isset($attempts_data['count']) ? intval($attempts_data['count']) : 0;
        $last_attempt = isset($attempts_data['last_attempt']) ? $attempts_data['last_attempt'] : 0;
        
        if ($attempts_count >= self::$max_attempts) {
            $time_remaining = self::$lockout_time - (time() - $last_attempt);
            
            if ($time_remaining > 0) {
                $minutes_remaining = ceil($time_remaining / 60);
                
                return new WP_Error(
                    'too_many_attempts', 
                    sprintf(
                        'Trop de tentatives de connexion. Veuillez réessayer dans %d minute(s).',
                        $minutes_remaining
                    )
                );
            } else {
                // Le délai est expiré, réinitialiser
                delete_transient($attempts_key);
                return true;
            }
        }
        
        return true;
    }
    
    /**
     * Enregistrer un échec de connexion
     */
    public static function record_failed_attempt($email) {
        $attempts_key = self::get_attempts_key($email);
        $attempts_data = get_transient($attempts_key);
        
        if ($attempts_data === false) {
            $attempts_data = [
                'count' => 1,
                'first_attempt' => time(),
                'last_attempt' => time(),
                'ip' => self::get_client_ip()
            ];
        } else {
            $attempts_data['count'] = intval($attempts_data['count']) + 1;
            $attempts_data['last_attempt'] = time();
        }
        
        // Sauvegarder pour la durée du lockout
        set_transient($attempts_key, $attempts_data, self::$lockout_time);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Auth Security] Tentative échouée #{$attempts_data['count']} pour : $email");
        }
    }
    
    /**
     * Nettoyer les tentatives après une connexion réussie
     */
    public static function clear_failed_attempts($email) {
        $attempts_key = self::get_attempts_key($email);
        delete_transient($attempts_key);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Auth Security] Tentatives nettoyées pour : $email");
        }
    }
    
    /**
     * Validation complète des données utilisateur
     */
    public static function validate_user_data($data, $context = 'register') {
        $errors = [];
        
        // Validation de l'email
        if (empty($data['user_email'])) {
            $errors['user_email'] = 'L\'adresse email est requise.';
        } elseif (!is_email($data['user_email'])) {
            $errors['user_email'] = 'L\'adresse email n\'est pas valide.';
        } elseif (self::is_email_blacklisted($data['user_email'])) {
            $errors['user_email'] = 'Cette adresse email n\'est pas autorisée.';
        }
        
        // Validation du mot de passe
        if (empty($data['user_password'])) {
            $errors['user_password'] = 'Le mot de passe est requis.';
        } else {
            $password_validation = self::validate_password_strength($data['user_password']);
            if (is_wp_error($password_validation)) {
                $errors['user_password'] = $password_validation->get_error_message();
            }
        }
        
        // Validations spécifiques à l'inscription
        if ($context === 'register') {
            // Vérifier si l'email existe déjà
            if (!empty($data['user_email'])) {
                if (email_exists($data['user_email'])) {
                    $errors['user_email'] = 'Cette adresse email est déjà utilisée.';
                }
            }
            
            // Confirmation du mot de passe
            if (empty($data['user_confirm_password'])) {
                $errors['user_confirm_password'] = 'La confirmation du mot de passe est requise.';
            } elseif ($data['user_password'] !== $data['user_confirm_password']) {
                $errors['user_confirm_password'] = 'Les mots de passe ne correspondent pas.';
            }
            
            // Nom d'affichage
            if (!empty($data['user_display_name'])) {
                $data['user_display_name'] = sanitize_text_field($data['user_display_name']);
                if (strlen($data['user_display_name']) < 2) {
                    $errors['user_display_name'] = 'Le nom d\'affichage doit contenir au moins 2 caractères.';
                } elseif (strlen($data['user_display_name']) > 50) {
                    $errors['user_display_name'] = 'Le nom d\'affichage ne peut pas dépasser 50 caractères.';
                }
            }
        }
        
        // Validation du nonce
        if (!empty($data['_wpnonce'])) {
            $nonce_action = $context === 'register' ? 'sisme_user_register' : 'sisme_user_login';
            if (!wp_verify_nonce($data['_wpnonce'], $nonce_action)) {
                $errors['security'] = 'Erreur de sécurité. Veuillez recharger la page.';
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Valider la force du mot de passe
     */
    private static function validate_password_strength($password) {
        $min_length = 8;
        $errors = [];
        
        // Longueur minimale
        if (strlen($password) < $min_length) {
            $errors[] = "au moins {$min_length} caractères";
        }
        
        // Au moins une lettre
        if (!preg_match('/[a-zA-Z]/', $password)) {
            $errors[] = "au moins une lettre";
        }
        
        // Au moins un chiffre
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "au moins un chiffre";
        }
        
        // Mots de passe faibles communs
        $weak_passwords = ['password', '123456', 'qwerty', 'azerty', 'admin', 'test'];
        if (in_array(strtolower($password), $weak_passwords)) {
            $errors[] = "ne pas être un mot de passe trop commun";
        }
        
        if (!empty($errors)) {
            return new WP_Error(
                'weak_password',
                'Le mot de passe doit contenir : ' . implode(', ', $errors) . '.'
            );
        }
        
        return true;
    }
    
    /**
     * Vérifier si un email est dans la liste noire
     */
    private static function is_email_blacklisted($email) {
        // Domaines temporaires/jetables courants
        $blacklisted_domains = [
            '10minutemail.com',
            'guerrilla mail.com',
            'tempmail.org',
            'throwaway.email'
        ];
        
        $email_domain = substr(strrchr($email, "@"), 1);
        return in_array(strtolower($email_domain), $blacklisted_domains);
    }
    
    /**
     * Sanitiser les données utilisateur
     */
    public static function sanitize_user_data($data) {
        $sanitized = [];
        
        if (isset($data['user_email'])) {
            $sanitized['user_email'] = sanitize_email($data['user_email']);
        }
        
        if (isset($data['user_password'])) {
            $sanitized['user_password'] = $data['user_password']; // Ne pas modifier les mots de passe
        }
        
        if (isset($data['user_confirm_password'])) {
            $sanitized['user_confirm_password'] = $data['user_confirm_password'];
        }
        
        if (isset($data['user_display_name'])) {
            $sanitized['user_display_name'] = sanitize_text_field($data['user_display_name']);
        }
        
        if (isset($data['remember_me'])) {
            $sanitized['remember_me'] = !empty($data['remember_me']);
        }
        
        if (isset($data['redirect_to'])) {
            $sanitized['redirect_to'] = esc_url_raw($data['redirect_to']);
        }
        
        if (isset($data['_wpnonce'])) {
            $sanitized['_wpnonce'] = sanitize_text_field($data['_wpnonce']);
        }
        
        return $sanitized;
    }
    
    /**
     * Générer une clé unique pour les tentatives de connexion
     */
    private static function get_attempts_key($email) {
        $ip = self::get_client_ip();
        return 'sisme_login_attempts_' . md5($email . $ip);
    }
    
    /**
     * Obtenir l'IP du client de manière sécurisée
     */
    private static function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Programmer le nettoyage automatique
     */
    public static function schedule_cleanup() {
        if (!wp_next_scheduled('sisme_auth_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'sisme_auth_cleanup');
        }
    }
    
    /**
     * Nettoyage des données expirées
     */
    public static function cleanup_expired_data() {
        // Les transients WordPress se nettoient automatiquement
        // Cette méthode peut être étendue pour d'autres nettoyages
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Auth Security] Nettoyage des données expirées effectué');
        }
    }
    
    /**
     * Obtenir les statistiques de sécurité
     */
    public static function get_security_stats() {
        global $wpdb;
        
        // Compter les tentatives actives (approximatif via options)
        $active_attempts = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_sisme_login_attempts_%'"
        );
        
        return [
            'max_attempts' => self::$max_attempts,
            'lockout_time_minutes' => self::$lockout_time / 60,
            'active_lockouts' => intval($active_attempts),
            'cleanup_interval_hours' => self::$cleanup_interval / 3600
        ];
    }
}

// Initialiser le module de sécurité
Sisme_User_Auth_Security::init();

// Hook pour le nettoyage automatique
add_action('sisme_auth_cleanup', ['Sisme_User_Auth_Security', 'cleanup_expired_data']);