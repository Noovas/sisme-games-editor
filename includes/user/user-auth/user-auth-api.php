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
            'title' => 'Connexion',
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
            'redirect_to' => !empty($atts['redirect_to']) ? $atts['redirect_to'] : home_url(self::DASHBOARD_URL)
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
            'title' => 'Inscription',
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
            'redirect_to' => home_url(self::DASHBOARD_URL)
        ];
        $components = ['user_email', 'user_password', 'user_confirm_password', 'user_display_name', 'redirect_to'];
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
     * Rendu d'une card d'authentification
     */
    private static function render_auth_card($type, $atts, $form) {
        $icon = ($type === 'login') ? '🔐' : '📝';
        
        // URLs fixes selon le type
        $show_link = ($type === 'login') ? $atts['show_register_link'] : $atts['show_login_link'];
        $link_text = ($type === 'login') ? $atts['register_link_text'] : $atts['login_link_text'];
        $link_url = ($type === 'login') ? home_url(self::REGISTER_URL) : home_url(self::LOGIN_URL);
        
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
            
            <?php if ($show_link === 'true'): ?>
                <footer class="sisme-auth-footer">
                    <p class="sisme-auth-link">
                        <a href="<?php echo esc_url($link_url); ?>"><?php echo esc_html($link_text); ?></a>
                    </p>
                </footer>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Message pour utilisateur déjà connecté
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
                    <a href="<?php echo esc_url(home_url(self::DASHBOARD_URL)); ?>" class="sisme-button sisme-button-vert">
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
     * Message pour connexion requise
     */
    private static function render_login_required() {
        ob_start();
        ?>
        <div class="sisme-auth-card sisme-auth-card--login-required">
            <div class="sisme-auth-content">
                <div class="sisme-auth-message sisme-auth-message--warning">
                    <span class="sisme-message-icon">🔒</span>
                    <p>Vous devez être connecté pour accéder à cette page.</p>
                </div>
                <div class="sisme-auth-actions">
                    <a href="<?php echo esc_url(home_url(self::LOGIN_URL)); ?>" class="sisme-button sisme-button-vert">
                        <span class="sisme-btn-icon">🔐</span>
                        Se connecter
                    </a>
                    <a href="<?php echo esc_url(home_url(self::REGISTER_URL)); ?>" class="sisme-button sisme-button-bleu">
                        <span class="sisme-btn-icon">📝</span>
                        S'inscrire
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();



    }
    
    /**
     * Rendu des jeux favoris de l'utilisateur
     */
    private static function render_user_favorites($user_id) {
        $favorite_games = get_user_meta($user_id, 'sisme_favorite_games', true) ?: [];
        
        if (empty($favorite_games)) {
            return '<p class="sisme-profile-empty">Aucun jeu favori pour le moment. Explorez notre catalogue pour découvrir de nouveaux jeux !</p>';
        }
        
        ob_start();
        ?>
        <div class="sisme-favorites-grid">
            <?php 
            $count = 0;
            foreach ($favorite_games as $game_id): 
                if ($count >= 6) break; // Limiter à 6 jeux
                $game = get_term($game_id, 'post_tag');
                if ($game && !is_wp_error($game)):
                    $count++;
            ?>
                <div class="sisme-favorite-item">
                    <a href="<?php echo esc_url(get_term_link($game)); ?>" class="sisme-favorite-link">
                        <?php echo esc_html($game->name); ?>
                    </a>
                </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
        <?php if (count($favorite_games) > 6): ?>
            <p class="sisme-favorites-more">
                <a href="<?php echo esc_url(home_url(self::PROFILE_URL)); ?>">
                    Voir tous mes favoris (<?php echo count($favorite_games); ?>)
                </a>
            </p>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu de l'activité récente de l'utilisateur
     */
    private static function render_user_activity($user_id) {
        $last_login = get_user_meta($user_id, 'sisme_last_login', true);
        $profile_created = get_user_meta($user_id, 'sisme_profile_created', true);
        
        ob_start();
        ?>
        <div class="sisme-activity-list">
            <?php if ($last_login): ?>
                <div class="sisme-activity-item">
                    <span class="sisme-activity-icon">🕐</span>
                    <span class="sisme-activity-text">
                        Dernière connexion : <?php echo esc_html(date_i18n('j F Y à H\hi', strtotime($last_login))); ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <?php if ($profile_created): ?>
                <div class="sisme-activity-item">
                    <span class="sisme-activity-icon">📅</span>
                    <span class="sisme-activity-text">
                        Profil créé le <?php echo esc_html(date_i18n('j F Y', strtotime($profile_created))); ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <div class="sisme-activity-item">
                <span class="sisme-activity-icon">⭐</span>
                <span class="sisme-activity-text">
                    <?php 
                    $favorites_count = count(get_user_meta($user_id, 'sisme_favorite_games', true) ?: []);
                    echo $favorites_count . ' jeu' . ($favorites_count > 1 ? 'x' : '') . ' en favoris';
                    ?>
                </span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu des recommandations pour l'utilisateur
     */
    private static function render_user_recommendations($user_id) {
        // TODO: Implémenter la logique de recommandations basée sur les favoris
        // Pour l'instant, message placeholder
        
        ob_start();
        ?>
        <div class="sisme-recommendations-placeholder">
            <p class="sisme-profile-empty">
                <span class="sisme-placeholder-icon">🎯</span>
                Les recommandations personnalisées arriveront bientôt ! 
                Ajoutez des jeux à vos favoris pour que nous puissions vous suggérer de nouveaux titres.
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialiser l'API
Sisme_User_Auth_API::init();