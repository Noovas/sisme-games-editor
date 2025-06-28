<?php
/**
 * File: /sisme-games-editor/includes/user/user-preferences/user-preferences-api.php
 * API publique pour le rendu des préférences utilisateur
 * 
 * RESPONSABILITÉ:
 * - Rendu des formulaires de préférences avec sections
 * - Shortcode [sisme_user_preferences]
 * - Composants réutilisables (toggles iOS, multi-select)
 * - Intégration avec le dashboard utilisateur
 * 
 * DÉPENDANCES:
 * - Sisme_User_Preferences_Data_Manager
 * - Assets CSS/JS du loader
 * - Design tokens frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Preferences_API {
    
    /**
     * Shortcode principal [sisme_user_preferences]
     */
    public static function render_preferences_shortcode($atts = []) {
        // Vérifier si l'utilisateur est connecté
        if (!is_user_logged_in()) {
            return self::render_login_required();
        }
        
        $defaults = [
            'sections' => 'gaming,notifications,privacy', // Sections à afficher
            'user_id' => get_current_user_id(),
            'container_class' => 'sisme-user-preferences',
            'title' => 'Mes préférences'
        ];
        
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_preferences');
        
        // ID utilisateur (utilisateur courant par défaut)
        $user_id = !empty($atts['user_id']) ? intval($atts['user_id']) : get_current_user_id();
        
        // Vérification permissions (seul l'utilisateur peut modifier ses préférences)
        if ($user_id !== get_current_user_id() && !current_user_can('manage_users')) {
            return self::render_access_denied();
        }
        
        // Forcer le chargement des assets
        if (class_exists('Sisme_User_Preferences_Loader')) {
            $loader = Sisme_User_Preferences_Loader::get_instance();
            $loader->force_load_assets();
        }
        
        // Vérifier que le Data Manager est disponible
        if (!class_exists('Sisme_User_Preferences_Data_Manager')) {
            return self::render_error('Module de données non disponible');
        }
        
        // Récupérer les préférences utilisateur
        $user_preferences = Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id);
        
        // Parser les sections à afficher
        $sections_to_show = array_map('trim', explode(',', $atts['sections']));
        
        // Rendu complet
        ob_start();
        ?>
        <div class="<?php echo esc_attr($atts['container_class']); ?>" data-user-id="<?php echo esc_attr($user_id); ?>">
            
            <?php if (!empty($atts['title'])): ?>
                <header class="sisme-preferences-header">
                    <h2 class="sisme-preferences-title">
                        <span class="sisme-title-icon">⚙️</span>
                        <?php echo esc_html($atts['title']); ?>
                    </h2>
                </header>
            <?php endif; ?>
            
            <div class="sisme-preferences-form" data-auto-save="true">
                
                <!-- Indicateur de sauvegarde -->
                <div class="sisme-save-indicator" style="display: none;">
                    <span class="sisme-save-text">Sauvegarde en cours...</span>
                </div>
                
                <?php foreach ($sections_to_show as $section): ?>
                    <?php echo self::render_section($section, $user_id, $user_preferences); ?>
                <?php endforeach; ?>
                
                <!-- Actions globales -->
                <div class="sisme-preferences-actions">
                    <button type="button" class="sisme-button sisme-button-bleu sisme-reset-preferences">
                        <span class="sisme-btn-icon">🔄</span>
                        Réinitialiser mes préférences
                    </button>
                </div>
                
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu d'une section spécifique de préférences
     */
    public static function render_section($section_name, $user_id, $user_preferences = null) {
        if (!$user_preferences) {
            $user_preferences = Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id);
        }
        
        switch ($section_name) {
            case 'gaming':
                return self::render_gaming_section($user_preferences);
                
            case 'notifications':
                return self::render_notifications_section($user_preferences);
                
            case 'privacy':
                return self::render_privacy_section($user_preferences);
                
            default:
                return '';
        }
    }
    
    /**
     * Rendu de la section préférences gaming
     */
    private static function render_gaming_section($preferences) {
        ob_start();
        ?>
        <section class="sisme-preferences-section" data-section="gaming">
            <h3 class="sisme-section-title">
                <span class="sisme-section-icon">🎮</span>
                Préférences de jeu
            </h3>
            
            <div class="sisme-section-content">
                
                <!-- Plateformes préférées -->
                <div class="sisme-preference-group">
                    <label class="sisme-preference-label">Plateformes préférées</label>
                    <p class="sisme-preference-description">Sélectionnez vos plateformes de jeu favorites</p>
                    <?php 
                    echo self::render_multi_select(
                        'platforms',
                        Sisme_User_Preferences_Data_Manager::get_available_platforms(),
                        $preferences['platforms'],
                        ['display_key' => 'name', 'value_key' => 'slug', 'columns' => 2]
                    ); 
                    ?>
                </div>
                
                <!-- Genres favoris -->
                <div class="sisme-preference-group">
                    <label class="sisme-preference-label">Genres favoris</label>
                    <p class="sisme-preference-description">Choisissez vos genres de jeux préférés</p>
                    <?php 
                    echo self::render_multi_select(
                        'genres',
                        Sisme_User_Preferences_Data_Manager::get_available_genres(),
                        $preferences['genres'],
                        ['display_key' => 'name', 'value_key' => 'id', 'columns' => 2]
                    ); 
                    ?>
                </div>
                
                <!-- Types de joueur -->
                <div class="sisme-preference-group">
                    <label class="sisme-preference-label">Types de jeu</label>
                    <p class="sisme-preference-description">Vos préférences de mode de jeu</p>
                    <?php 
                    echo self::render_multi_select(
                        'player_types',
                        Sisme_User_Preferences_Data_Manager::get_available_player_types(),
                        $preferences['player_types'],
                        ['display_key' => 'name', 'value_key' => 'slug', 'columns' => 2]
                    ); 
                    ?>
                </div>
                
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu de la section notifications
     */
    private static function render_notifications_section($preferences) {
        $notification_types = Sisme_User_Preferences_Data_Manager::get_notification_types();
        
        ob_start();
        ?>
        <section class="sisme-preferences-section" data-section="notifications">
            <h3 class="sisme-section-title">
                <span class="sisme-section-icon">🔔</span>
                Notifications
            </h3>
            
            <div class="sisme-section-content">
                
                <?php foreach ($notification_types as $key => $label): ?>
                    <div class="sisme-preference-group">
                        <?php 
                        echo self::render_toggle(
                            "notifications.{$key}",
                            $label,
                            isset($preferences['notifications'][$key]) ? $preferences['notifications'][$key] : false,
                            ['description' => self::get_notification_description($key)]
                        ); 
                        ?>
                    </div>
                <?php endforeach; ?>
                
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu de la section confidentialité
     */
    private static function render_privacy_section($preferences) {
        ob_start();
        ?>
        <section class="sisme-preferences-section" data-section="privacy">
            <h3 class="sisme-section-title">
                <span class="sisme-section-icon">🔒</span>
                Confidentialité
            </h3>
            
            <div class="sisme-section-content">
                
                <div class="sisme-preference-group">
                    <?php 
                    echo self::render_toggle(
                        'privacy_public',
                        'Profil public',
                        $preferences['privacy_public'],
                        [
                            'description' => 'Votre profil et vos collections seront visibles par les autres utilisateurs'
                        ]
                    ); 
                    ?>
                </div>
                
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu d'un toggle iOS style
     */
    public static function render_toggle($key, $label, $value, $options = []) {
        $defaults = [
            'classes' => '',
            'disabled' => false,
            'description' => ''
        ];
        
        $options = array_merge($defaults, $options);
        $toggle_id = 'sisme-toggle-' . str_replace('.', '-', $key);
        
        ob_start();
        ?>
        <div class="sisme-preference-toggle-group <?php echo esc_attr($options['classes']); ?>">
            <div class="sisme-toggle-container">
                <input type="checkbox" 
                       id="<?php echo esc_attr($toggle_id); ?>"
                       class="sisme-preference-toggle"
                       data-preference-key="<?php echo esc_attr($key); ?>"
                       <?php checked($value); ?>
                       <?php disabled($options['disabled']); ?>>
                <label for="<?php echo esc_attr($toggle_id); ?>" class="sisme-toggle-label">
                    <span class="sisme-toggle-slider"></span>
                </label>
                <span class="sisme-toggle-text"><?php echo esc_html($label); ?></span>
            </div>
            
            <?php if (!empty($options['description'])): ?>
                <p class="sisme-toggle-description"><?php echo esc_html($options['description']); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu d'une sélection multiple avec checkboxes
     */
    public static function render_multi_select($key, $options, $selected, $config = []) {
        $defaults = [
            'columns' => 3,
            'display_key' => 'name',
            'value_key' => 'slug',
            'allow_all' => false,
            'max_selections' => 0
        ];
        
        $config = array_merge($defaults, $config);
        $selected = is_array($selected) ? $selected : [];
        
        ob_start();
        ?>
        <div class="sisme-multi-select" 
             data-preference-key="<?php echo esc_attr($key); ?>"
             data-columns="<?php echo esc_attr($config['columns']); ?>">
            
            <div class="sisme-multi-select-grid" style="grid-template-columns: repeat(<?php echo esc_attr($config['columns']); ?>, 1fr);">
                
                <?php foreach ($options as $option): ?>
                    <?php
                    $option_value = $option[$config['value_key']];
                    $option_label = $option[$config['display_key']];
                    $is_selected = in_array($option_value, $selected);
                    $checkbox_id = 'sisme-multi-' . $key . '-' . $option_value;
                    ?>
                    
                    <label class="sisme-multi-select-item <?php echo $is_selected ? 'selected' : ''; ?>" 
                           for="<?php echo esc_attr($checkbox_id); ?>">
                        <input type="checkbox" 
                               id="<?php echo esc_attr($checkbox_id); ?>"
                               class="sisme-multi-select-checkbox"
                               value="<?php echo esc_attr($option_value); ?>"
                               <?php checked($is_selected); ?>>
                        <span class="sisme-multi-select-label"><?php echo esc_html($option_label); ?></span>
                    </label>
                    
                <?php endforeach; ?>
                
            </div>
            
            <?php if ($config['allow_all']): ?>
                <div class="sisme-multi-select-actions">
                    <button type="button" class="sisme-btn sisme-btn--small sisme-select-all">
                        Tout sélectionner
                    </button>
                    <button type="button" class="sisme-btn sisme-btn--small sisme-select-none">
                        Tout désélectionner
                    </button>
                </div>
            <?php endif; ?>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Message pour utilisateur non connecté
     */
    private static function render_login_required() {
        ob_start();
        ?>
        <div class="sisme-preferences-card sisme-preferences-card--login-required">
            <div class="sisme-preferences-content">
                <div class="sisme-preferences-message sisme-preferences-message--warning">
                    <span class="sisme-message-icon">🔒</span>
                    <p>Vous devez être connecté pour accéder à vos préférences.</p>
                </div>
                <div class="sisme-preferences-actions">
                    <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="sisme-btn sisme-btn--primary">
                        <span class="sisme-btn-icon">🔐</span>
                        Se connecter
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Message d'accès refusé
     */
    private static function render_access_denied() {
        ob_start();
        ?>
        <div class="sisme-preferences-card sisme-preferences-card--access-denied">
            <div class="sisme-preferences-content">
                <div class="sisme-preferences-message sisme-preferences-message--error">
                    <span class="sisme-message-icon">⛔</span>
                    <p>Vous n'avez pas les permissions pour modifier ces préférences.</p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Message d'erreur générique
     */
    private static function render_error($message) {
        ob_start();
        ?>
        <div class="sisme-preferences-card sisme-preferences-card--error">
            <div class="sisme-preferences-content">
                <div class="sisme-preferences-message sisme-preferences-message--error">
                    <span class="sisme-message-icon">❌</span>
                    <p><?php echo esc_html($message); ?></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtenir la description d'un type de notification
     */
    private static function get_notification_description($key) {
        $descriptions = [
            'new_games_in_genres' => 'Recevez une notification quand de nouveaux jeux sortent dans vos genres favoris',
            'favorite_games_updates' => 'Soyez informé des mises à jour de vos jeux favoris',
            'new_indie_releases' => 'Découvrez les dernières sorties de jeux indépendants',
            'newsletter' => 'Recevez notre newsletter hebdomadaire avec une sélection de jeux'
        ];
        
        return isset($descriptions[$key]) ? $descriptions[$key] : '';
    }
    
    /**
     * Initialiser les shortcodes
     */
    public static function init_shortcodes() {
        add_shortcode('sisme_user_preferences', [self::class, 'render_preferences_shortcode']);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Preferences API] Shortcode enregistré : sisme_user_preferences');
        }
    }
}

// Initialiser les shortcodes
add_action('init', ['Sisme_User_Preferences_API', 'init_shortcodes']);