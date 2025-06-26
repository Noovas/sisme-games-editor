<?php
/**
 * File: /sisme-games-editor/includes/user/user-profile/user-profile-loader.php
 * Loader du module de gestion de profil utilisateur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Profile_Loader {
    
    private static $instance = null;
    private static $modules_loaded = false;
    private $assets_loaded = false;
    
    /**
     * Singleton
     * @return Sisme_User_Profile_Loader Instance unique
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    private function init() {
        $this->load_profile_modules();
        $this->register_hooks();
        $this->init_shortcodes();
    }
    
    private function load_profile_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $profile_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-profile/';
        
        $required_modules = [
            'user-profile-handlers.php',
            'user-profile-forms.php',
            'user-profile-avatar.php',
            'user-profile-api.php'
        ];
        
        foreach ($required_modules as $module) {
            $file_path = $profile_dir . $module;
            
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        self::$modules_loaded = true;
    }
    
    private function register_hooks() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_loaded', [$this, 'handle_profile_requests']);
        
        add_action('wp_ajax_sisme_update_profile', [$this, 'ajax_update_profile']);
        add_action('wp_ajax_sisme_upload_avatar', [$this, 'ajax_upload_avatar']);
        add_action('wp_ajax_sisme_delete_avatar', [$this, 'ajax_delete_avatar']);
        add_action('wp_ajax_sisme_upload_banner', [$this, 'ajax_upload_banner']);
        add_action('wp_ajax_sisme_delete_banner', [$this, 'ajax_delete_banner']);
        add_action('wp_ajax_sisme_update_preferences', [$this, 'ajax_update_preferences']);
    }
    
    private function init_shortcodes() {
        if (class_exists('Sisme_User_Profile_API')) {
            add_shortcode('sisme_user_profile_edit', ['Sisme_User_Profile_API', 'render_profile_edit_form']);
            add_shortcode('sisme_user_avatar_uploader', ['Sisme_User_Profile_API', 'render_avatar_uploader']);
            add_shortcode('sisme_user_banner_uploader', ['Sisme_User_Profile_API', 'render_banner_uploader']);
            add_shortcode('sisme_user_preferences', ['Sisme_User_Profile_API', 'render_preferences']);
            add_shortcode('sisme_user_profile_display', ['Sisme_User_Profile_API', 'render_profile_display']);
        }
    }
    
    /**
     * Charger assets CSS/JS frontend
     * @return void
     */
    public function enqueue_frontend_assets() {
        if ($this->assets_loaded || is_admin()) {
            return;
        }
        
        wp_enqueue_style(
            'sisme-user-profile',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-profile/assets/user-profile.css',
            ['sisme-user-auth'],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'sisme-user-profile',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-profile/assets/user-profile.js',
            ['jquery', 'sisme-user-auth'],
            '1.0.0',
            true
        );
        
        wp_localize_script('sisme-user-profile', 'sismeUserProfile', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_user_profile'),
            'messages' => [
                'profile_updated' => 'Profil mis à jour avec succès !',
                'avatar_updated' => 'Avatar mis à jour !',
                'avatar_deleted' => 'Avatar supprimé',
                'banner_updated' => 'Bannière mise à jour !',
                'banner_deleted' => 'Bannière supprimée',
                'error_upload' => 'Erreur lors de l\'upload',
                'error_format' => 'Format de fichier non supporté',
                'error_size' => 'Fichier trop volumineux (max 2Mo)'
            ],
            'config' => [
                'max_file_size' => 2097152,
                'allowed_types' => ['image/jpeg', 'image/png', 'image/gif'],
                'avatar_sizes' => [
                    'thumbnail' => 150,
                    'medium' => 300,
                    'large' => 600
                ],
                'banner_sizes' => [
                    'medium' => [800, 200],
                    'large' => [1200, 300],
                    'full' => [1920, 480]
                ]
            ]
        ]);
        
        $this->assets_loaded = true;
    }
    
    /**
     * Traiter les requêtes de profil
     * @return void
     */
    public function handle_profile_requests() {
        if (class_exists('Sisme_User_Profile_Handlers')) {
            Sisme_User_Profile_Handlers::init_request_handling();
        }
    }
    
    /**
     * AJAX: Mettre à jour le profil
     * @return void JSON response
     */
    public function ajax_update_profile() {
        check_ajax_referer('sisme_user_profile', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $result = Sisme_User_Profile_Handlers::handle_profile_update($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    /**
     * AJAX: Upload avatar
     * @return void JSON response
     */
    public function ajax_upload_avatar() {
        check_ajax_referer('sisme_user_profile', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $result = Sisme_User_Profile_Avatar::handle_avatar_upload($_FILES);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    /**
     * AJAX: Supprimer avatar
     * @return void JSON response
     */
    public function ajax_delete_avatar() {
        check_ajax_referer('sisme_user_profile', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $result = Sisme_User_Profile_Avatar::delete_user_avatar(get_current_user_id());
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('Avatar supprimé avec succès');
        }
    }
    
    /**
     * AJAX: Upload bannière
     * @return void JSON response
     */
    public function ajax_upload_banner() {
        check_ajax_referer('sisme_user_profile', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $result = Sisme_User_Profile_Avatar::handle_banner_upload($_FILES);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    /**
     * AJAX: Supprimer bannière
     * @return void JSON response
     */
    public function ajax_delete_banner() {
        check_ajax_referer('sisme_user_profile', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $result = Sisme_User_Profile_Avatar::delete_user_banner(get_current_user_id());
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success('Bannière supprimée avec succès');
        }
    }
    
    /**
     * AJAX: Mettre à jour les préférences
     * @return void JSON response
     */
    public function ajax_update_preferences() {
        check_ajax_referer('sisme_user_profile', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Vous devez être connecté');
        }
        
        $result = Sisme_User_Profile_Handlers::handle_preferences_update($_POST);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }
    
    /**
     * Forcer le chargement des assets
     * @return void
     */
    public function force_load_assets() {
        if (!$this->assets_loaded) {
            $this->enqueue_frontend_assets();
        }
    }
    
    /**
     * Vérifier si les assets sont chargés
     * @return bool Status des assets
     */
    public function are_assets_loaded() {
        return $this->assets_loaded;
    }
    
    /**
     * Obtenir la version du module
     * @return string Version
     */
    public function get_version() {
        return '1.0.0';
    }
}