<?php
/**
 * File: /sisme-games-editor/includes/user/user-auth/user-auth-api.php
 * API et shortcodes pour l'authentification utilisateur - VERSION CORRIGÉE
 * 
 * MODIFICATION: URLs de redirection en dur avec pages fixes
 * - sisme-user-profil
 * - sisme-user-tableau-de-bord  
 * - sisme-user-register
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Auth_API {
    
    // URLs de redirection en dur
    const PROFILE_URL = '/sisme-user-profil/';
    const DASHBOARD_URL = '/sisme-user-tableau-de-bord/';
    const REGISTER_URL = '/sisme-user-register/';
    const LOGIN_URL = '/sisme-user-login/';
    
    /**
     * Initialisation de l'API
     */
    public static function init() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Auth API] API d\'authentification initialisée');
        }
    }
    
    /**
     * Shortcode [sisme_user_login] - Formulaire de connexion
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML du formulaire de connexion
     */
    public static function render_login_form($atts = []) {
        $defaults = [
            'container_class' => 'sisme-user-auth-container',
            Sisme_Utils_Games::KEY_TITLE => 'Connexion',
            'subtitle' => 'Accédez à votre espace membre',
            'submit_text' => 'Se connecter',
            'show_register_link' => 'true', 
            'register_link_text' => 'Pas encore de compte ? Créer un compte',
            'redirect_to' => ''
        ];
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_login');
        if (is_user_logged_in()) {
            return self::render_already_logged_in();
        }
        if (class_exists('Sisme_User_Auth_Loader')) {
            $loader = Sisme_User_Auth_Loader::get_instance();
            if (method_exists($loader, 'force_load_assets')) {
                $loader->force_load_assets();
            }
        }
        $form_options = [
            'type' => 'login',
            'submit_text' => $atts['submit_text'],
            'redirect_to' => !empty($atts['redirect_to']) ? $atts['redirect_to'] : home_url(Sisme_Utils_Users::DASHBOARD_URL)
        ];
        $components = ['user_email', 'user_password', 'remember_me', 'redirect_to'];
        $form = new Sisme_User_Auth_Forms($components, $form_options);
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>">
            <?php echo self::render_auth_card('login', $atts, $form); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode [sisme_user_register] - Formulaire d'inscription
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML du formulaire d'inscription
     */
    public static function render_register_form($atts = []) {
        $defaults = [
            'container_class' => 'sisme-user-auth-container',
            Sisme_Utils_Games::KEY_TITLE => 'Inscription',
            'subtitle' => 'Créez votre compte gamer',
            'submit_text' => 'Créer mon compte',
            'show_login_link' => 'true',
            'login_link_text' => 'Déjà un compte ? Se connecter',
            'require_email_verification' => 'false'
        ];
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_register');
        if (is_user_logged_in()) {
            return self::render_already_logged_in();
        }
        if (class_exists('Sisme_User_Auth_Loader')) {
            $loader = Sisme_User_Auth_Loader::get_instance();
            if (method_exists($loader, 'force_load_assets')) {
                $loader->force_load_assets();
            }
        }
        $form_options = [
            'type' => 'register',
            'submit_text' => $atts['submit_text'],
            'redirect_to' => home_url(Sisme_Utils_Users::DASHBOARD_URL)
        ];
        $components = ['user_email', 'user_password', 'user_confirm_password', 'display_name', 'redirect_to'];
        $form = new Sisme_User_Auth_Forms($components, $form_options);
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>">
            <?php echo self::render_auth_card('register', $atts, $form); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu d'une card d'authentification (section entière)
     * 
     * @param string $type Type de card ('login', 'register', 'forgot_password', 'reset_password')
     * @param array $atts Attributs de configuration
     * @param object $form Instance du formulaire
     * @return string HTML de la card d'authentification
     */
    private static function render_auth_card($type, $atts, $form) {
        // Icônes selon le type
        $icons = [
            'login' => '🔐',
            'register' => '📝',
            'forgot_password' => '🔄',
            'reset_password' => '🔑'
        ];
        $icon = $icons[$type] ?? '🔐';
        
        // Gestion des liens selon le type
        $show_login_link = isset($atts['show_login_link']) && $atts['show_login_link'] === 'true';
        $show_register_link = isset($atts['show_register_link']) && $atts['show_register_link'] === 'true';
        
        $login_link_text = $atts['login_link_text'] ?? 'Se connecter';
        $register_link_text = $atts['register_link_text'] ?? 'S\'inscrire';
        
        $login_url = home_url(self::LOGIN_URL);
        $register_url = home_url(self::REGISTER_URL);
        
        ob_start();
        ?>
        <div class="sisme-auth-card">
            <header class="sisme-auth-header">
                <h2 class="sisme-auth-title">
                    <span class="sisme-auth-icon"><?php echo $icon; ?></span>
                    <?php echo esc_html($atts['title']); ?>
                </h2>
                <?php if (!empty($atts['subtitle'])): ?>
                    <p class="sisme-auth-subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
                <?php endif; ?>
            </header>
            
            <div class="sisme-auth-content">
                <?php $form->render(); ?>
            </div>
            
            <?php if ($show_login_link || $show_register_link): ?>
                <footer class="sisme-auth-footer">
                    <?php if ($show_login_link): ?>
                        <p class="sisme-auth-link">
                            <a href="<?php echo esc_url($login_url); ?>"><?php echo esc_html($login_link_text); ?></a>
                        </p>
                    <?php endif; ?>
                    <?php if ($show_register_link): ?>
                        <p class="sisme-auth-link">
                            <a href="<?php echo esc_url($register_url); ?>"><?php echo esc_html($register_link_text); ?></a>
                        </p>
                    <?php endif; ?>
                </footer>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Message pour utilisateur déjà connecté
     * 
     * @return string HTML du message pour utilisateur connecté
     */
    private static function render_already_logged_in() {
        $current_user = wp_get_current_user();
        ob_start();
        ?>
        <div class="sisme-auth-card sisme-auth-card--logged-in">
            <div class="sisme-auth-content">
                <div class="sisme-auth-message sisme-auth-message--info">
                    <span class="sisme-message-icon">✅</span>
                    <p>Vous êtes déjà connecté en tant que <strong><?php echo esc_html($current_user->display_name); ?></strong>.</p>
                </div>
                <div class="sisme-auth-actions">
                    <a href="<?php echo esc_url(home_url(Sisme_Utils_Users::DASHBOARD_URL)); ?>" class="sisme-button sisme-button-vert">
                        <span class="sisme-btn-icon">👤</span>
                        Mon tableau de bord
                    </a>
                    <a href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>" class="sisme-button sisme-button-orange">
                        <span class="sisme-btn-icon">🚪</span>
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode [sisme_user_forgot_password] - Demande de réinitialisation
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML du formulaire de réinitialisation
     */
    public static function render_forgot_password_form($atts = []) {
        $defaults = [
            'container_class' => 'sisme-user-auth-container',
            Sisme_Utils_Games::KEY_TITLE => 'Mot de passe oublié',
            'subtitle' => 'Recevez un lien de réinitialisation par email',
            'submit_text' => 'Envoyer le lien',
            'show_login_link' => 'true',
            'login_link_text' => 'Retour à la connexion',
            'show_register_link' => 'true',
            'register_link_text' => 'Pas encore de compte ? S\'inscrire'
        ];
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_forgot_password');
        
        // Si déjà connecté, rediriger vers dashboard
        if (is_user_logged_in()) {
            return self::render_already_logged_in();
        }
        
        // Charger les assets
        if (class_exists('Sisme_User_Auth_Loader')) {
            $loader = Sisme_User_Auth_Loader::get_instance();
            if (method_exists($loader, 'force_load_assets')) {
                $loader->force_load_assets();
            }
        }
        
        $form_options = [
            'type' => 'forgot_password',
            'submit_text' => $atts['submit_text'],
            'redirect_to' => home_url(Sisme_Utils_Users::FORGOT_PASSWORD_URL)
        ];
        
        $components = ['reset_email', 'redirect_to'];
        $form = new Sisme_User_Auth_Forms($components, $form_options);
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>">
            <?php echo self::render_auth_card('forgot_password', $atts, $form); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode [sisme_user_reset_password] - Nouveau mot de passe
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML du formulaire de nouveau mot de passe
     */
    public static function render_reset_password_form($atts = []) {
        $defaults = [
            'container_class' => 'sisme-user-auth-container',
            'title' => 'Nouveau mot de passe',
            'subtitle' => 'Choisissez votre nouveau mot de passe',
            'submit_text' => 'Modifier le mot de passe',
            'show_login_link' => 'true',
            'login_link_text' => 'Retour à la connexion',
            'show_register_link' => 'false',
            'register_link_text' => ''
        ];
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_reset_password');
        
        // Si déjà connecté, rediriger vers dashboard
        if (is_user_logged_in()) {
            return self::render_already_logged_in();
        }
        
        // Vérifier la présence du token
        $reset_token = sanitize_text_field($_GET['key'] ?? '');
        $login = sanitize_text_field($_GET['login'] ?? '');
        if (empty($reset_token) || empty($login)) {
            return self::render_invalid_token();
        }
        
        // Charger les assets
        if (class_exists('Sisme_User_Auth_Loader')) {
            $loader = Sisme_User_Auth_Loader::get_instance();
            if (method_exists($loader, 'force_load_assets')) {
                $loader->force_load_assets();
            }
        }
        
        $form_options = [
            'type' => 'reset_password',
            'submit_text' => $atts['submit_text'],
            'redirect_to' => home_url(Sisme_Utils_Users::LOGIN_URL)
        ];
        
        $components = ['reset_token', 'login', 'new_password', 'confirm_new_password', 'redirect_to'];
        $form = new Sisme_User_Auth_Forms($components, $form_options);

        // Pré-remplir le token et le login
        $form->set_component_value('reset_token', $reset_token);
        $form->set_component_value('login', $login);
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>">
            <?php echo self::render_auth_card('reset_password', $atts, $form); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Message d'erreur pour token invalide
     * 
     * @return string HTML du message d'erreur
     */
    private static function render_invalid_token() {
        ob_start();
        ?>
        <div class="sisme-auth-card sisme-auth-card--error">
            <div class="sisme-auth-content">
                <div class="sisme-auth-message sisme-auth-message--error">
                    <span class="sisme-message-icon">❌</span>
                    <p>Le lien de réinitialisation est invalide ou a expiré.</p>
                </div>
                <div class="sisme-auth-actions">
                    <a href="<?php echo esc_url(home_url(Sisme_Utils_Users::FORGOT_PASSWORD_URL)); ?>" class="sisme-button sisme-button-vert">
                        <span class="sisme-btn-icon">🔄</span>
                        Demander un nouveau lien
                    </a>
                    <a href="<?php echo esc_url(home_url(Sisme_Utils_Users::LOGIN_URL)); ?>" class="sisme-button sisme-button-bleu">
                        <span class="sisme-btn-icon">🔐</span>
                        Retour connexion
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
Sisme_User_Auth_API::init();