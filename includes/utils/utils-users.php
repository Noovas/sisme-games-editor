<?php
/**
 * File: /sisme-games-editor/includes/utils/utils-users.php
 * Utilitaires partagés pour la gestion des utilisateurs
 * 
 * RESPONSABILITÉ:
 * - Validation des utilisateurs
 * - Gestion des métadonnées utilisateur
 * - Collections de jeux utilisateur
 * - Utilitaires logging utilisateur
 * 
 * DÉPENDANCES:
 * - WordPress User API
 * - WordPress Meta API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Utils_Users {
    
    /**
     * Types de collections de jeux supportés
     */
    const COLLECTION_FAVORITE = 'favorite';
    const COLLECTION_OWNED = 'owned';
    const COLLECTION_WISHLIST = 'wishlist';
    const COLLECTION_COMPLETED = 'completed';
    
    /**
     * Préfixes des clés meta utilisateur
     */
    const META_PREFIX = 'sisme_user_';
    const META_FRIENDS_LIST = 'sisme_user_friends_list'; //user_id => ['status' => 'pending'|'accepted','date' => '2025-01-15 14:30:25']

    /**
     * URLs des pages utilisateur
     */
    const LOGIN_URL = '/sisme-user-login/';
    const REGISTER_URL = '/sisme-user-register/';
    const PROFILE_URL = '/sisme-user-profil/';
    const DASHBOARD_URL = '/sisme-user-tableau-de-bord/';
    const FORGOT_PASSWORD_URL = '/sisme-user-forgot-password/';
    const RESET_PASSWORD_URL = '/sisme-user-reset-password/';
    
    /**
     * Messages par défaut
     */
    const DEFAULT_LOGIN_REQUIRED_MESSAGE = 'Vous devez être connecté pour accéder à cette page.';
    const DEFAULT_DASHBOARD_LOGIN_MESSAGE = 'Vous devez être connecté pour accéder à votre dashboard.';

    /**
     * Obtenir un utilisateur par son slug (user_nicename)
     * 
     * @param string $slug Slug de l'utilisateur
     * @return WP_User|false Utilisateur ou false
     */
    public static function get_user_by_slug($slug) {
        if (empty($slug)) {
            return false;
        }
        $slug = sanitize_title($slug);
        if (empty($slug)) {
            return false;
        }
        $user = get_user_by('slug', $slug);
        
        if (!$user) {
            if (is_numeric($slug)) {
                $user = get_user_by('ID', intval($slug));
            }
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if ($user) {
                error_log("[Sisme User Auth] User trouvé par slug '{$slug}': ID {$user->ID}, Display: {$user->display_name}");
            } else {
                error_log("[Sisme User Auth] Aucun user trouvé pour le slug: '{$slug}'");
            }
        }
        return $user;
    }

    /**
     * Obtenir l'URL du profil d'un utilisateur basée sur son slug
     * @param int|WP_User $user ID utilisateur ou objet WP_User
     * @return string URL du profil
     */
    public static function get_user_profile_url($user) {
        if (is_numeric($user)) {
            $user = get_userdata($user);
        }
        if (!$user || !($user instanceof WP_User)) {
            return '';
        }
        return home_url('/sisme-user-profil/?user=' . $user->user_nicename);
    }

    /**
     * Obtenir l'URL du profil de l'utilisateur courant
     * @return string URL du profil ou chaîne vide si non connecté
     */
    public static function get_current_user_profile_url() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '';
        }
        
        return self::get_user_profile_url($user_id);
    }
    
    /**
     * Valider qu'un ID utilisateur est valide et que l'utilisateur existe
     * 
     * @param int $user_id ID de l'utilisateur à valider
     * @param string $context Contexte pour les logs (optionnel)
     * @return bool True si l'utilisateur est valide, false sinon
     */
    public static function validate_user_id($user_id, $context = '') {
        if (empty($user_id) || !is_numeric($user_id) || $user_id <= 0) {
            if (defined('WP_DEBUG') && WP_DEBUG && $context) {
                error_log("[Sisme Utils Users] {$context}: ID utilisateur invalide: {$user_id}");
            }
            return false;
        }
        $user = get_userdata($user_id);
        if (!$user || is_wp_error($user)) {
            if (defined('WP_DEBUG') && WP_DEBUG && $context) {
                error_log("[Sisme Utils Users] {$context}: Utilisateur inexistant: {$user_id}");
            }
            return false;
        }
        return true;
    }

    /**
     * Récupérer une métadonnée utilisateur avec valeur par défaut
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $meta_key Clé de la métadonnée
     * @param mixed $default Valeur par défaut si la métadonnée n'existe pas
     * @param string $context Contexte pour les logs (optionnel)
     * @return mixed Valeur de la métadonnée ou valeur par défaut
     */
    public static function get_user_meta_with_default($user_id, $meta_key, $default = null, $context = '') {
        if (!self::validate_user_id($user_id, $context ?: 'get_meta')) {
            return $default;
        }
        if (empty($meta_key) || !is_string($meta_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme Utils Users] " . ($context ?: 'get_meta') . ": Clé meta invalide: " . var_export($meta_key, true));
            }
            return $default;
        }
        $value = get_user_meta($user_id, $meta_key, true);
        return !empty($value) ? $value : $default;
    }

    /**
     * Rendu du message demandant une connexion utilisateur
     * 
     * @param string $message Message personnalisé (optionnel)
     * @param string $login_url URL de connexion personnalisée (optionnel)
     * @param string $register_url URL d'inscription personnalisée (optionnel)
     * @return string HTML du message de connexion requise
     */
    public static function render_login_required($message = '', $login_url = '', $register_url = '') {
        $message = !empty($message) ? $message : self::DEFAULT_LOGIN_REQUIRED_MESSAGE;
        $login_url = !empty($login_url) ? $login_url : home_url(self::LOGIN_URL);
        $register_url = !empty($register_url) ? $register_url : home_url(self::REGISTER_URL);
        ob_start();
        ?>
        <div class="sisme-auth-card sisme-auth-card--login-required">
            <div class="sisme-auth-content">
                <div class="sisme-auth-message sisme-auth-message--warning">
                    <span class="sisme-message-icon">🔒</span>
                    <p><?php echo esc_html($message); ?></p>
                </div>
                <div class="sisme-auth-actions">
                    <a href="<?php echo esc_url($login_url); ?>" class="sisme-button sisme-button-vert">
                        <span class="sisme-btn-icon">🔐</span>
                        Se connecter
                    </a>
                    <a href="<?php echo esc_url($register_url); ?>" class="sisme-button sisme-button-bleu">
                        <span class="sisme-btn-icon">📝</span>
                        S'inscrire
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}