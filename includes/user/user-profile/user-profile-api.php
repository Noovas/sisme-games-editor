<?php
/**
 * File: /sisme-games-editor/includes/user/user-profile/user-profile-api.php
 * API et shortcodes pour la gestion de profil utilisateur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Profile_API {
    
    /**
     * Shortcode [sisme_user_profile_edit] - Formulaire d'édition de profil
     * @param array $atts Attributs du shortcode
     * @return string HTML du formulaire
     */
    public static function render_profile_edit_form($atts = []) {
        $defaults = [
            'sections' => 'basic,gaming,privacy',
            'title' => 'Modifier mon profil',
            'show_avatar' => 'true',
            'show_banner' => 'true',
            'redirect_to' => '',
            'container_class' => 'sisme-profile-edit-container'
        ];
        
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_profile_edit');
        
        if (!is_user_logged_in()) {
            return self::render_login_required();
        }
        
        self::ensure_assets_loaded();
        
        $sections = array_map('trim', explode(',', $atts['sections']));
        $components = self::get_components_for_sections($sections);
        
        $form_options = [
            'type' => 'profile',
            'redirect_to' => $atts['redirect_to']
        ];
        
        $form = new Sisme_User_Profile_Forms($components, $form_options);
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>">
            
            <?php if (!empty($atts['title'])): ?>
                <div class="sisme-profile-edit-header">
                    <h2 class="sisme-profile-edit-title">
                        <span class="sisme-title-icon">✏️</span>
                        <?php echo esc_html($atts['title']); ?>
                    </h2>
                </div>
            <?php endif; ?>
            
            <div class="sisme-profile-edit-content">
                
                <?php if ($atts['show_banner'] === 'true' || $atts['show_avatar'] === 'true'): ?>
                    <div class="sisme-profile-images-section">
                        
                        <?php if ($atts['show_banner'] === 'true'): ?>
                            <div class="sisme-profile-banner-section">
                                <h3 class="sisme-section-subtitle">
                                    <span class="sisme-subtitle-icon">🖼️</span>
                                    Bannière de profil
                                </h3>
                                <?php echo self::render_banner_uploader(); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($atts['show_avatar'] === 'true'): ?>
                            <div class="sisme-profile-avatar-section">
                                <h3 class="sisme-section-subtitle">
                                    <span class="sisme-subtitle-icon">👤</span>
                                    Photo de profil
                                </h3>
                                <?php echo self::render_avatar_uploader(); ?>
                            </div>
                        <?php endif; ?>
                        
                    </div>
                <?php endif; ?>
                
                <div class="sisme-profile-form-section">
                    <?php echo $form->render(); ?>
                </div>
                
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode [sisme_user_avatar_uploader] - Interface d'upload d'avatar
     * @param array $atts Attributs du shortcode
     * @return string HTML de l'interface
     */
    public static function render_avatar_uploader($atts = []) {
        $defaults = [
            'size' => 'large',
            'show_delete' => 'true',
            'show_info' => 'false',
            'container_class' => 'sisme-avatar-uploader-container'
        ];
        
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_avatar_uploader');
        
        if (!is_user_logged_in()) {
            return self::render_login_required();
        }
        
        self::ensure_assets_loaded();
        
        $options = [
            'size' => $atts['size'],
            'show_delete' => $atts['show_delete'] === 'true',
            'show_info' => $atts['show_info'] === 'true',
            'css_class' => 'sisme-avatar-uploader'
        ];
        
        $uploader_html = Sisme_User_Profile_Avatar::render_avatar_uploader(null, $options);
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>">
            <?php echo $uploader_html; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode [sisme_user_banner_uploader] - Interface d'upload de bannière
     * @param array $atts Attributs du shortcode
     * @return string HTML de l'interface
     */
    public static function render_banner_uploader($atts = []) {
        $defaults = [
            'size' => 'large',
            'show_delete' => 'true',
            'show_info' => 'false',
            'container_class' => 'sisme-banner-uploader-container'
        ];
        
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_banner_uploader');
        
        if (!is_user_logged_in()) {
            return self::render_login_required();
        }
        
        self::ensure_assets_loaded();
        
        $options = [
            'type' => 'banner',
            'size' => $atts['size'],
            'show_delete' => $atts['show_delete'] === 'true',
            'show_info' => $atts['show_info'] === 'true',
            'css_class' => 'sisme-banner-uploader'
        ];
        
        $uploader_html = Sisme_User_Profile_Avatar::render_banner_uploader(null, $options);
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>">
            <?php echo $uploader_html; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode [sisme_user_preferences] - Section préférences uniquement
     * @param array $atts Attributs du shortcode
     * @return string HTML des préférences
     */
    public static function render_preferences($atts = []) {
        $defaults = [
            'title' => 'Mes préférences',
            'compact' => 'false',
            'container_class' => 'sisme-preferences-container'
        ];
        
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_preferences');
        
        if (!is_user_logged_in()) {
            return self::render_login_required();
        }
        
        self::ensure_assets_loaded();
        
        $components = [
            'favorite_game_genres', 
            'skill_level',
            'favorite_games_display',
            'privacy_profile_public',
            'privacy_show_stats',
            'privacy_allow_friend_requests'
        ];
        
        $form_options = ['type' => 'preferences'];
        $form = new Sisme_User_Profile_Forms($components, $form_options);
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>">
            
            <?php if (!empty($atts['title'])): ?>
                <div class="sisme-preferences-header">
                    <h3 class="sisme-preferences-title">
                        <span class="sisme-title-icon">⚙️</span>
                        <?php echo esc_html($atts['title']); ?>
                    </h3>
                </div>
            <?php endif; ?>
            
            <div class="sisme-preferences-content">
                <?php echo $form->render(); ?>
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Shortcode [sisme_user_profile_display] - Affichage public du profil
     * @param array $atts Attributs du shortcode
     * @return string HTML du profil public
     */
    public static function render_profile_display($atts = []) {
        $defaults = [
            'user_id' => '',
            'sections' => 'basic,gaming',
            'show_avatar' => 'true',
            'show_banner' => 'true',
            'show_stats' => 'true',
            'container_class' => 'sisme-profile-display-container'
        ];
        
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_profile_display');
        
        $user_id = !empty($atts['user_id']) ? intval($atts['user_id']) : get_current_user_id();
        
        if (!$user_id) {
            return '<div class="sisme-profile-error">Utilisateur non trouvé.</div>';
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return '<div class="sisme-profile-error">Utilisateur non trouvé.</div>';
        }
        
        $is_own_profile = (get_current_user_id() === $user_id);
        $is_public = get_user_meta($user_id, 'sisme_user_privacy_profile_public', true);
        
        if (!$is_own_profile && !$is_public) {
            return '<div class="sisme-profile-error">Ce profil est privé.</div>';
        }
        
        self::ensure_assets_loaded();
        
        $sections = array_map('trim', explode(',', $atts['sections']));
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>">
            
            <div class="sisme-profile-display">
                
                <?php if ($atts['show_banner'] === 'true'): ?>
                    <div class="sisme-profile-display-banner">
                        <?php
                        $banner_url = Sisme_User_Profile_Avatar::get_user_banner_url($user_id, 'large');
                        if ($banner_url):
                        ?>
                            <img src="<?php echo esc_url($banner_url); ?>" alt="Bannière de <?php echo esc_attr($user->display_name); ?>" class="sisme-banner-large">
                        <?php else: ?>
                            <div class="sisme-banner-placeholder">
                                <span class="sisme-banner-icon">🖼️</span>
                                <p>Aucune bannière</p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Header du profil -->
                <header class="sisme-profile-display-header">
                    
                    <?php if ($atts['show_avatar'] === 'true'): ?>
                        <div class="sisme-profile-display-avatar">
                            <?php
                            $avatar_url = Sisme_User_Profile_Avatar::get_final_avatar_url($user_id, 'large');
                            ?>
                            <img src="<?php echo esc_url($avatar_url); ?>" alt="Avatar de <?php echo esc_attr($user->display_name); ?>" class="sisme-avatar-large">
                        </div>
                    <?php endif; ?>
                    
                    <div class="sisme-profile-display-info">
                        <h2 class="sisme-profile-display-name">
                            <?php echo esc_html($user->display_name); ?>
                        </h2>
                        
                        <?php if ($user->user_url): ?>
                            <div class="sisme-profile-display-website">
                                <span class="sisme-profile-icon">🌐</span>
                                <a href="<?php echo esc_url($user->user_url); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html(parse_url($user->user_url, PHP_URL_HOST)); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php
                        $last_login = get_user_meta($user_id, 'sisme_user_last_login', true);
                        if ($last_login):
                        ?>
                            <div class="sisme-profile-display-activity">
                                <span class="sisme-profile-icon">⏰</span>
                                Dernière connexion : <?php echo esc_html(date('d/m/Y', strtotime($last_login))); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                </header>
                
                <!-- Sections du profil -->
                <div class="sisme-profile-display-sections">
                    
                    <?php if (in_array('basic', $sections)): ?>
                        <?php echo self::render_basic_info_section($user_id); ?>
                    <?php endif; ?>
                    
                    <?php if (in_array('gaming', $sections)): ?>
                        <?php echo self::render_gaming_section($user_id, $atts['show_stats'] === 'true'); ?>
                    <?php endif; ?>
                    
                </div>
                
            </div>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu de la section informations de base
     * @param int $user_id ID de l'utilisateur
     * @return string HTML de la section
     */
    private static function render_basic_info_section($user_id) {
        $bio = get_user_meta($user_id, 'sisme_user_bio', true);
        
        if (empty($bio)) {
            return '';
        }
        
        ob_start();
        ?>
        <section class="sisme-profile-section sisme-profile-section--basic">
            <h3 class="sisme-profile-section-title">
                <span class="sisme-section-icon">📋</span>
                À propos
            </h3>
            <div class="sisme-profile-section-content">
                <div class="sisme-profile-bio">
                    <?php echo wp_kses_post(wpautop($bio)); ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu de la section gaming
     * @param int $user_id ID de l'utilisateur
     * @param bool $show_stats Afficher les statistiques
     * @return string HTML de la section
     */
    private static function render_gaming_section($user_id, $show_stats = true) {
        $show_gaming_stats = get_user_meta($user_id, 'sisme_user_privacy_show_stats', true);
        
        if (!$show_stats || !$show_gaming_stats) {
            return '';
        }
        
        $skill_level = get_user_meta($user_id, 'sisme_user_skill_level', true);
        $favorite_genres = get_user_meta($user_id, 'sisme_user_favorite_game_genres', true);
        $favorite_games = get_user_meta($user_id, 'sisme_user_favorite_games', true);
        
        ob_start();
        ?>
        <section class="sisme-profile-section sisme-profile-section--gaming">
            <h3 class="sisme-profile-section-title">
                <span class="sisme-section-icon">🎮</span>
                Préférences Gaming
            </h3>
            <div class="sisme-profile-section-content">
                
                <div class="sisme-profile-gaming-grid">
                    
                    <?php if ($skill_level): ?>
                        <div class="sisme-profile-gaming-item">
                            <h4 class="sisme-gaming-item-title">Niveau de jeu</h4>
                            <div class="sisme-gaming-item-value">
                                <?php echo esc_html(self::get_skill_level_label($skill_level)); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($favorite_genres) && is_array($favorite_genres)): ?>
                        <div class="sisme-profile-gaming-item sisme-profile-gaming-item--full">
                            <h4 class="sisme-gaming-item-title">Genres préférés</h4>
                            <div class="sisme-gaming-item-value">
                                <div class="sisme-genres-list">
                                    <?php
                                    $genres = get_terms([
                                        'taxonomy' => 'game_genre',
                                        'include' => $favorite_genres,
                                        'hide_empty' => false
                                    ]);
                                    
                                    if (!is_wp_error($genres)):
                                        foreach ($genres as $genre):
                                    ?>
                                        <span class="sisme-genre-tag"><?php echo esc_html($genre->name); ?></span>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($favorite_games) && is_array($favorite_games)): ?>
                        <div class="sisme-profile-gaming-item sisme-profile-gaming-item--full">
                            <h4 class="sisme-gaming-item-title">Jeux favoris</h4>
                            <div class="sisme-gaming-item-value">
                                <div class="sisme-games-list">
                                    <?php
                                    $games = get_terms([
                                        'taxonomy' => 'post_tag',
                                        'include' => array_slice($favorite_games, 0, 10),
                                        'hide_empty' => false
                                    ]);
                                    
                                    if (!is_wp_error($games)):
                                        foreach ($games as $game):
                                    ?>
                                        <span class="sisme-game-tag"><?php echo esc_html($game->name); ?></span>
                                    <?php
                                        endforeach;
                                    endif;
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                </div>
                
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtenir les composants pour des sections données
     * @param array $sections Sections demandées
     * @return array Composants correspondants
     */
    private static function get_components_for_sections($sections) {
        $all_components = [
            'basic' => ['user_display_name', 'user_bio', 'user_website'],
            'gaming' => ['favorite_game_genres', 'skill_level', 'favorite_games_display'],
            'privacy' => ['privacy_profile_public', 'privacy_show_stats', 'privacy_allow_friend_requests']
        ];
        
        $components = [];
        
        foreach ($sections as $section) {
            if (isset($all_components[$section])) {
                $components = array_merge($components, $all_components[$section]);
            }
        }
        
        return $components;
    }
    
    /**
     * Obtenir le label d'un niveau de compétence
     * @param string $level Code du niveau
     * @return string Label du niveau
     */
    private static function get_skill_level_label($level) {
        $labels = [
            'beginner' => 'Débutant',
            'casual' => 'Casual',
            'experienced' => 'Expérimenté',
            'hardcore' => 'Hardcore',
            'professional' => 'Professionnel'
        ];
        
        return $labels[$level] ?? $level;
    }
    
    /**
     * Rendu du message de connexion requise
     * @return string HTML du message
     */
    private static function render_login_required() {
        ob_start();
        ?>
        <div class="sisme-profile-login-required">
            <div class="sisme-message sisme-message--info">
                <span class="sisme-message-icon">🔐</span>
                <p>Vous devez être connecté pour accéder à cette fonctionnalité.</p>
                <div class="sisme-message-actions">
                    <a href="<?php echo esc_url(wp_login_url()); ?>" class="sisme-button sisme-button-primary">
                        Se connecter
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * S'assurer que les assets sont chargés
     * @return void
     */
    private static function ensure_assets_loaded() {
        if (class_exists('Sisme_User_Profile_Loader')) {
            $loader = Sisme_User_Profile_Loader::get_instance();
            if (method_exists($loader, 'force_load_assets')) {
                $loader->force_load_assets();
            }
        }
    }
}