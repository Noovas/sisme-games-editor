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
    const DEVELOPER_URL = '/sisme-user-tableau-de-bord/#developer';

    /**
     * Constantes pour le module développeur
     */
    const META_DEVELOPER_STATUS = 'sisme_user_developer_status';
    const META_DEVELOPER_APPLICATION = 'sisme_user_developer_application';
    const META_DEVELOPER_PROFILE = 'sisme_user_developer_profile';

    /**
     * Statuts développeur
     */
    const DEVELOPER_STATUS_NONE = 'none';
    const DEVELOPER_STATUS_PENDING = 'pending';
    const DEVELOPER_STATUS_APPROVED = 'approved';
    const DEVELOPER_STATUS_REJECTED = 'rejected';
    const DEVELOPER_STATUS_REVOKED = 'revoked';

    /**
     * Champs de candidature développeur
     */
    const APPLICATION_FIELD_STUDIO_NAME = 'studio_name';
    const APPLICATION_FIELD_STUDIO_DESCRIPTION = 'studio_description';
    const APPLICATION_FIELD_STUDIO_WEBSITE = 'studio_website';
    const APPLICATION_FIELD_STUDIO_SOCIAL_LINKS = 'studio_social_links';
    const APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME = 'representative_firstname';
    const APPLICATION_FIELD_REPRESENTATIVE_LASTNAME = 'representative_lastname';
    const APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE = 'representative_birthdate';
    const APPLICATION_FIELD_REPRESENTATIVE_ADDRESS = 'representative_address';
    const APPLICATION_FIELD_REPRESENTATIVE_CITY = 'representative_city';
    const APPLICATION_FIELD_REPRESENTATIVE_COUNTRY = 'representative_country';
    const APPLICATION_FIELD_REPRESENTATIVE_EMAIL = 'representative_email';
    const APPLICATION_FIELD_REPRESENTATIVE_PHONE = 'representative_phone';
    const APPLICATION_FIELD_SUBMITTED_DATE = 'submitted_date';
    const APPLICATION_FIELD_REVIEWED_DATE = 'reviewed_date';
    const APPLICATION_FIELD_ADMIN_NOTES = 'admin_notes';
    
    /**
     * Messages par défaut
     */
    const DEFAULT_LOGIN_REQUIRED_MESSAGE = 'Vous devez être connecté pour accéder à cette page.';
    const DEFAULT_DASHBOARD_LOGIN_MESSAGE = 'Vous devez être connecté pour accéder à votre dashboard.';


    /**
     * ========================================
     * 🎮 CONSTANTES GAME SUBMISSION
     * ========================================
     */
    
    /**
     * Métadonnée principale pour les soumissions de jeux
     */
    const META_GAME_SUBMISSIONS = 'sisme_user_game_submissions';
    
    /**
     * Champs du formulaire de soumission de jeu
     */
    const GAME_FIELD_NAME = 'game_name';
    const GAME_FIELD_DESCRIPTION = 'game_description';
    const GAME_FIELD_RELEASE_DATE = 'game_release_date';
    const GAME_FIELD_TRAILER = 'game_trailer';
    
    // Studio et éditeur (avec URLs optionnelles)
    const GAME_FIELD_STUDIO_NAME = 'game_studio_name';
    const GAME_FIELD_STUDIO_URL = 'game_studio_url';
    const GAME_FIELD_PUBLISHER_NAME = 'game_publisher_name';
    const GAME_FIELD_PUBLISHER_URL = 'game_publisher_url';
    
    // Catégories et plateformes
    const GAME_FIELD_GENRES = 'game_genres';
    const GAME_FIELD_PLATFORMS = 'game_platforms';
    const GAME_FIELD_MODES = 'game_modes';
    
    // Médias
    const GAME_FIELD_COVERS = 'covers';
    const GAME_FIELD_COVER_HORIZONTAL = 'cover_horizontal';
    const GAME_FIELD_COVER_VERTICAL = 'cover_vertical';
    const GAME_FIELD_SCREENSHOTS = 'screenshots';
    
    // Liens externes
    const GAME_FIELD_EXTERNAL_LINKS = 'external_links';
    const GAME_FIELD_EXTERNAL_LINKS_STEAM = 'steam';
    const GAME_FIELD_EXTERNAL_LINKS_GOG = 'gog';
    const GAME_FIELD_EXTERNAL_LINKS_EPIC = 'epic';

    // Sections de description longue
    const GAME_FIELD_DESCRIPTION_SECTIONS = 'sections';
    
    /**
     * Statuts des soumissions de jeux
     */
    const GAME_STATUS_DRAFT = 'draft';           // Brouillon (modifiable, supprimable)
    const GAME_STATUS_PENDING = 'pending';       // En attente validation admin
    const GAME_STATUS_PUBLISHED = 'published';   // Publié (plus modifiable)
    const GAME_STATUS_REJECTED = 'rejected';     // Rejeté par admin
    const GAME_STATUS_ARCHIVED = 'archived';     // Archivé (révision approuvée)
    
    /**
     * Limites et contraintes
     */
    const GAME_MAX_DRAFTS_PER_USER = 3;         // Max 3 brouillons simultanés
    const GAME_MAX_SCREENSHOTS = 5;             // Max 5 screenshots par jeu
    const GAME_MAX_SECTIONS_DESCRIPTION = 3;    // Max 3 sections de description longue par jeu
    
    /**
     * Champs metadata des soumissions
     */
    const GAME_META_CREATED_AT = 'created_at';
    const GAME_META_UPDATED_AT = 'updated_at';
    const GAME_META_SUBMITTED_AT = 'submitted_at';
    const GAME_META_PUBLISHED_AT = 'published_at';
    const GAME_META_COMPLETION_PERCENTAGE = 'completion_percentage';
    const GAME_META_RETRY_COUNT = 'retry_count';
    const GAME_META_ORIGINAL_SUBMISSION_ID = 'original_submission_id';
    
    /**
     * Champs admin des soumissions
     */
    const GAME_ADMIN_USER_ID = 'admin_user_id';
    const GAME_ADMIN_NOTES = 'admin_notes';
    const GAME_ADMIN_REVIEWED_AT = 'reviewed_at';


    /**
     * Récupérer les données développeur d'un utilisateur
     * 
     * @param int $user_id ID utilisateur (optionnel, par défaut utilisateur connecté)
     * @return array|null Données développeur complètes ou null
     */
    public static function get_user_dev_data($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return null;
        }
        
        // Vérifier que le module développeur est disponible
        if (!class_exists('Sisme_User_Developer_Data_Manager')) {
            return null;
        }
        
        return Sisme_User_Developer_Data_Manager::get_developer_data($user_id);
    }
    
    /**
     * Vérifier si un utilisateur peut candidater comme développeur
     * 
     * @param int $user_id ID utilisateur (optionnel, par défaut utilisateur connecté)
     * @return bool Peut candidater
     */
    public static function can_apply_as_developer($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id || !class_exists('Sisme_User_Developer_Data_Manager')) {
            return false;
        }
        
        return Sisme_User_Developer_Data_Manager::can_apply($user_id);
    }
    
    /**
     * Vérifier si un utilisateur est développeur approuvé
     * 
     * @param int $user_id ID utilisateur (optionnel, par défaut utilisateur connecté)
     * @return bool Est développeur approuvé
     */
    public static function is_approved_developer($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id || !class_exists('Sisme_User_Developer_Data_Manager')) {
            return false;
        }
        
        // Vérifier ET le statut ET le rôle (double sécurité)
        $status = Sisme_User_Developer_Data_Manager::get_developer_status($user_id);
        $has_role = class_exists('Sisme_Utils_Developer_Roles') ? 
                   Sisme_Utils_Developer_Roles::is_developer($user_id) : false;
        
        return ($status === self::DEVELOPER_STATUS_APPROVED) && $has_role;
    }

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
        return $user;
    }

    /**
     * Rechercher des utilisateurs par display_name
     * 
     * @param string $search_term Terme de recherche
     * @param int $max_results Nombre maximum de résultats (défaut: 10)
     * @return array Liste des utilisateurs trouvés [id, display_name, user_nicename]
     */
    public static function search_users_by_display_name($search_term, $max_results = 10) {
        if (empty($search_term) || !is_string($search_term)) {
            return [];
        }
        $search_term = trim($search_term);
        if (strlen($search_term) < 2) {
            return [];
        }
        $max_results = max(1, min(50, intval($max_results)));
        $user_query = new WP_User_Query([
            'search' => '*' . esc_attr($search_term) . '*',
            'search_columns' => ['display_name'],
            'number' => $max_results,
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => ['ID', 'display_name', 'user_nicename']
        ]);
        $results = [];
        foreach ($user_query->get_results() as $user) {
            $results[] = [
                'id' => intval($user->ID),
                'display_name' => esc_html($user->display_name),
                'user_nicename' => esc_attr($user->user_nicename),
                'profile_url' => self::get_user_profile_url($user->ID, 'overview')
            ];
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Utils Users] Recherche '{$search_term}' : " . count($results) . " résultats");
        }
        return $results;
    }

    /**
     * Obtenir l'URL du profil d'un utilisateur basée sur son slug
     * @param int|WP_User $user ID utilisateur ou objet WP_User
     * @return string URL du profil
     */
    public static function get_user_profile_url($user, $section = 'overview') {
        if (is_numeric($user)) {
            $user = get_userdata($user);
        }
        if (!$user || !($user instanceof WP_User)) {
            return '';
        }
        $base_url = home_url('/sisme-user-profil/?user=' . $user->user_nicename);
        $base_url .= '#' . sanitize_title($section);        
        return $base_url;
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