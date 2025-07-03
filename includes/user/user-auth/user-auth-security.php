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
     * Valider un email avec règles strictes pour éviter les caractères problématiques
     * @param string $email Email à valider
     * @return WP_Error|true True si valide, WP_Error sinon
     */
    public static function validate_email_strict($email) {
        $email = trim($email);
        
        // Vérification de base WordPress
        if (!is_email($email)) {
            return new WP_Error('email_invalid', 'L\'adresse email n\'est pas valide.');
        }
        
        // Vérifier la longueur
        if (strlen($email) > 254) {
            return new WP_Error('email_too_long', 'L\'adresse email est trop longue (max 254 caractères).');
        }
        
        if (strlen($email) < 5) {
            return new WP_Error('email_too_short', 'L\'adresse email est trop courte.');
        }
        
        // Extraire les parties local et domaine
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return new WP_Error('email_format', 'Format d\'email invalide.');
        }
        
        $local_part = $parts[0];
        $domain_part = $parts[1];
        
        // Valider la partie locale (avant @)
        $local_validation = self::validate_email_local_part($local_part);
        if (is_wp_error($local_validation)) {
            return $local_validation;
        }
        
        // Valider le domaine
        $domain_validation = self::validate_email_domain_part($domain_part);
        if (is_wp_error($domain_validation)) {
            return $domain_validation;
        }
        
        // Vérifier la liste noire de domaines
        if (self::is_email_blacklisted($email)) {
            return new WP_Error('email_blacklisted', 'Cette adresse email n\'est pas autorisée.');
        }
        
        return true;
    }
    
    /**
     * Vérifier si un email est dans la liste noire - VERSION AMÉLIORÉE
     */
    private static function is_email_blacklisted($email) {
        // Domaines temporaires/jetables courants
        $blacklisted_domains = [
            // Domaines temporaires connus
            '10minutemail.com',
            'guerrillamail.com',
            'tempmail.org',
            'throwaway.email',
            'mailinator.com',
            'yopmail.com',
            'temp-mail.org',
            'getnada.com',
            'guerrillamail.de',
            '10minute.email',
            
            // Domaines suspects
            'example.com',
            'test.com',
            'localhost',
            
            // Domaines avec caractères problématiques (au cas où)
            'domain+test.com',
            'test@domain.com' // Pas un vrai domaine
        ];
        
        $email_domain = substr(strrchr($email, "@"), 1);
        $email_domain = strtolower($email_domain);
        
        // Vérification exacte
        if (in_array($email_domain, $blacklisted_domains)) {
            return true;
        }
        
        // Vérification de patterns suspects
        $suspicious_patterns = [
            'temp',
            'fake',
            'spam',
            'trash',
            'disposable',
            'throwaway'
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (strpos($email_domain, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Valider la partie locale d'un email (avant @)
     * @param string $local_part Partie locale
     * @return WP_Error|true True si valide, WP_Error sinon
     */
    private static function validate_email_local_part($local_part) {
        // Longueur max pour partie locale : 64 caractères
        if (strlen($local_part) > 64) {
            return new WP_Error('email_local_too_long', 'La partie avant @ est trop longue.');
        }
        
        if (strlen($local_part) < 1) {
            return new WP_Error('email_local_empty', 'La partie avant @ ne peut pas être vide.');
        }
        
        // Caractères interdits dans la partie locale
        $forbidden_chars = [
            '+', // Peut causer des problèmes d'URL
            '\'', '"', // Quotes problématiques
            '\\', '/', // Slashes
            '<', '>', // Chevrons
            '(', ')', // Parenthèses
            '[', ']', // Crochets
            ',', ';', // Séparateurs
            ':', '|', // Deux-points, pipe
            '?', '*', // Wildcards
            '=', '&', // Caractères URL
            '%', '#', // Caractères URL
            '!', '$', // Caractères spéciaux
            '^', '~', // Caractères spéciaux
            '`', '{', '}' // Autres caractères
        ];
        
        foreach ($forbidden_chars as $char) {
            if (strpos($local_part, $char) !== false) {
                return new WP_Error(
                    'email_forbidden_char', 
                    "Le caractère '{$char}' n'est pas autorisé dans l'adresse email."
                );
            }
        }
        
        // Ne peut pas commencer ou finir par un point
        if (strpos($local_part, '.') === 0 || substr($local_part, -1) === '.') {
            return new WP_Error('email_dot_position', 'L\'adresse email ne peut pas commencer ou finir par un point.');
        }
        
        // Pas de points consécutifs
        if (strpos($local_part, '..') !== false) {
            return new WP_Error('email_consecutive_dots', 'L\'adresse email ne peut pas contenir de points consécutifs.');
        }
        
        // Caractères autorisés : lettres, chiffres, points, tirets, underscores
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $local_part)) {
            return new WP_Error(
                'email_invalid_chars', 
                'L\'adresse email ne peut contenir que des lettres, chiffres, points, tirets et underscores.'
            );
        }
        
        return true;
    }

    /**
     * Valider la partie domaine d'un email (après @)
     * @param string $domain_part Partie domaine
     * @return WP_Error|true True si valide, WP_Error sinon
     */
    private static function validate_email_domain_part($domain_part) {
        // Longueur max pour domaine : 253 caractères
        if (strlen($domain_part) > 253) {
            return new WP_Error('email_domain_too_long', 'Le domaine email est trop long.');
        }
        
        if (strlen($domain_part) < 4) { // Ex: a.co
            return new WP_Error('email_domain_too_short', 'Le domaine email est trop court.');
        }
        
        // Doit contenir au moins un point
        if (strpos($domain_part, '.') === false) {
            return new WP_Error('email_domain_no_dot', 'Le domaine doit contenir au moins un point.');
        }
        
        // Ne peut pas commencer ou finir par un point ou tiret
        if (preg_match('/^[.-]|[.-]$/', $domain_part)) {
            return new WP_Error('email_domain_format', 'Le domaine ne peut pas commencer ou finir par un point ou tiret.');
        }
        
        // Validation basique du format domaine
        if (!preg_match('/^[a-zA-Z0-9.-]+$/', $domain_part)) {
            return new WP_Error('email_domain_chars', 'Le domaine contient des caractères non autorisés.');
        }
        
        // Vérifier que l'extension a au moins 2 caractères
        $domain_parts = explode('.', $domain_part);
        $tld = end($domain_parts);
        
        if (strlen($tld) < 2) {
            return new WP_Error('email_tld_short', 'L\'extension du domaine est trop courte.');
        }
        
        if (!preg_match('/^[a-zA-Z]+$/', $tld)) {
            return new WP_Error('email_tld_format', 'L\'extension du domaine ne peut contenir que des lettres.');
        }
        
        return true;
    }
    
    /**
     * Validation complète des données utilisateur
     */
    public static function validate_user_data($data, $context = 'register') {
        $errors = [];
        
        // Validation de l'email - VERSION STRICTE
        if (empty($data['user_email'])) {
            $errors['user_email'] = 'L\'adresse email est requise.';
        } else {
            // Utiliser la nouvelle validation stricte
            $email_validation = self::validate_email_strict($data['user_email']);
            if (is_wp_error($email_validation)) {
                $errors['user_email'] = $email_validation->get_error_message();
            }
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
            if (empty($data['display_name'])) {
                $errors['display_name'] = 'Le pseudo est obligatoire.';
            } else {
                $data['display_name'] = sanitize_text_field(trim($data['display_name']));
                
                // Validation du format
                $display_name_validation = self::validate_display_name($data['display_name']);
                if (is_wp_error($display_name_validation)) {
                    $errors['display_name'] = $display_name_validation->get_error_message();
                } else {
                    // Vérification unicité
                    if (self::display_name_exists($data['display_name'])) {
                        $errors['display_name'] = 'Ce pseudo est déjà utilisé. Veuillez en choisir un autre.';
                    }
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
     * Vérifier si un display_name est déjà utilisé
     * @param string $display_name Nom d'affichage à vérifier
     * @param int $exclude_user_id ID utilisateur à exclure (pour modification profil)
     * @return bool True si le display_name existe déjà
     */
    public static function display_name_exists($display_name, $exclude_user_id = 0) {
        global $wpdb;
        
        $display_name = sanitize_text_field(trim($display_name));
        
        if (empty($display_name)) {
            return false;
        }
        
        // Requête pour vérifier l'unicité
        $query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->users} 
            WHERE display_name = %s AND ID != %d 
            LIMIT 1",
            $display_name,
            intval($exclude_user_id)
        );
        
        $existing_user = $wpdb->get_var($query);
        
        if (defined('WP_DEBUG') && WP_DEBUG && $existing_user) {
            error_log("[Sisme Auth Security] Display name '{$display_name}' déjà utilisé par utilisateur ID: {$existing_user}");
        }
        
        return !empty($existing_user);
    }

    /**
     * Valider un display_name selon nos règles
     * @param string $display_name Nom d'affichage à valider
     * @return WP_Error|true True si valide, WP_Error sinon
     */
    public static function validate_display_name($display_name) {
        $display_name = sanitize_text_field(trim($display_name));
        
        // Vérifications de base
        if (empty($display_name)) {
            return new WP_Error('display_name_required', 'Le pseudo est obligatoire.');
        }
        
        if (strlen($display_name) < 3) {
            return new WP_Error('display_name_too_short', 'Le pseudo doit contenir au moins 3 caractères.');
        }
        
        if (strlen($display_name) > 30) {
            return new WP_Error('display_name_too_long', 'Le pseudo ne peut pas dépasser 30 caractères.');
        }
        
        // Caractères autorisés : lettres, chiffres, underscores
        if (!preg_match('/^[a-zA-Z0-9_]+$/u', $display_name)) {
            return new WP_Error('display_name_invalid_chars', 'Le pseudo ne peut contenir que des lettres, chiffres, underscores.');
        }
        
        // Interdire certains mots réservés
        $reserved_names = [
            'admin', 'administrator', 'root', 'system', 'sisme', 'moderator', 'mod',
            'support', 'help', 'test', 'demo', 'guest', 'user', 'null', 'undefined'
        ];
        
        if (in_array(strtolower($display_name), $reserved_names)) {
            return new WP_Error('display_name_reserved', 'Ce pseudo est réservé et ne peut pas être utilisé.');
        }
        
        // Interdire les pseudos qui commencent/finissent par des caractères spéciaux
        if (preg_match('/^[._-]|[._-]$/', $display_name)) {
            return new WP_Error('display_name_invalid_format', 'Le pseudo ne peut pas commencer ou finir par un point, tiret ou underscore.');
        }
        
        return true;
    }

    /**
     * Générer des suggestions de pseudos disponibles
     * @param string $base_name Nom de base pour les suggestions
     * @param int $max_suggestions Nombre maximum de suggestions
     * @return array Liste de pseudos disponibles
     */
    public static function suggest_available_display_names($base_name, $max_suggestions = 5) {
        $base_name = sanitize_text_field(trim($base_name));
        $suggestions = [];
        
        if (empty($base_name)) {
            return $suggestions;
        }
        
        // Nettoyer le nom de base
        $clean_base = preg_replace('/[^a-zA-Z0-9]/', '', $base_name);
        if (strlen($clean_base) < 3) {
            $clean_base = 'user' . $clean_base;
        }
        
        // Générer des variations
        for ($i = 1; $i <= $max_suggestions * 2; $i++) {
            $candidate = $clean_base . $i;
            
            // Valider le candidat
            if (!is_wp_error(self::validate_display_name($candidate)) && 
                !self::display_name_exists($candidate)) {
                $suggestions[] = $candidate;
                
                if (count($suggestions) >= $max_suggestions) {
                    break;
                }
            }
        }
        
        // Ajouter quelques variations créatives si pas assez de suggestions
        if (count($suggestions) < $max_suggestions) {
            $creative_suffixes = ['_gaming', '_player', '_gamer', '_pro'];
            foreach ($creative_suffixes as $suffix) {
                $candidate = $clean_base . $suffix;
                if (!is_wp_error(self::validate_display_name($candidate)) && 
                    !self::display_name_exists($candidate)) {
                    $suggestions[] = $candidate;
                    if (count($suggestions) >= $max_suggestions) {
                        break;
                    }
                }
            }
        }
        
        return $suggestions;
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
        
        if (isset($data['display_name'])) {
            $sanitized['display_name'] = sanitize_text_field($data['display_name']);
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