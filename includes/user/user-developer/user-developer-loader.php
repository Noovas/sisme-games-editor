<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/user-developer-loader.php
 * Loader du module développeur utilisateur
 * 
 * RESPONSABILITÉ:
 * - Charger les composants du module développeur
 * - Initialiser les hooks et intégrations
 * - Étendre le dashboard avec l'onglet développeur
 * - Gérer les permissions et statuts développeur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Developer_Loader {
    
    private static $instance = null;
    private static $modules_loaded = false;
    
    /**
     * Singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialisation du module développeur
     */
    private function init() {
        $this->ensure_database_ready();
        $this->load_developer_modules();
        $this->register_hooks();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Developer] Module développeur utilisateur initialisé');
        }
    }
    
    /**
     * Charger tous les modules du développeur
     */
    private function load_developer_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $developer_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/';
        
        $required_modules = [
            'user-developer-data-manager.php',
            'user-developer-renderer.php',
            'user-developer-ajax.php',
            'user-developer-email-notifications.php' 
        ];
        
        foreach ($required_modules as $module) {
            $file_path = $developer_dir . $module;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Developer] Module chargé : $module");
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Developer] ERREUR - Module manquant : $file_path");
                }
            }
        }
        
        self::$modules_loaded = true;
    }
    
    /**
     * Enregistrer les hooks WordPress
     */
    private function register_hooks() {
        // Hook pour étendre le dashboard avec l'onglet développeur
        add_filter('sisme_dashboard_accessible_sections', [$this, 'add_developer_section'], 10, 2);
        add_filter('sisme_dashboard_navigation_items', [$this, 'add_developer_nav_item'], 10, 2);
        add_filter('sisme_dashboard_render_section', [$this, 'render_developer_section'], 10, 3);
        
        // Hook pour étendre le JavaScript avec les nouvelles sections
        add_filter('sisme_dashboard_valid_sections', [$this, 'add_developer_valid_section'], 10, 1);
        
        // Hook pour charger les assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_developer_assets']);
        
        // Initialiser les hooks AJAX
        add_action('init', function() {
            if (function_exists('sisme_init_developer_ajax')) {
                sisme_init_developer_ajax();
            }
        });
    }
    
    /**
     * Charger les assets CSS et JS du module développeur
     */
    public function enqueue_developer_assets() {
        // Vérifier si on doit charger les assets
        if (!$this->should_load_assets()) {
            return;
        }
        
        // CSS du module développeur
        wp_enqueue_style(
            'sisme-user-developer',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-developer/assets/user-developer.css',
            array('sisme-user-dashboard'), // Dépendance dashboard
            SISME_GAMES_EDITOR_VERSION
        );
        
        // JavaScript du module développeur
        wp_enqueue_script(
            'sisme-user-developer',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-developer/assets/user-developer.js',
            array('jquery', 'sisme-user-dashboard'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        // JavaScript AJAX du module développeur
        wp_enqueue_script(
            'sisme-user-developer-ajax',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-developer/assets/user-developer-ajax.js',
            array('jquery', 'sisme-user-developer'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        // Localisation AJAX
        wp_localize_script('sisme-user-developer-ajax', 'sismeAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_developer_nonce'),
            'currentUserId' => get_current_user_id()
        ));
    }
    
    /**
     * Vérifier si on doit charger les assets
     */
    private function should_load_assets() {
        // Charger seulement sur les pages avec le dashboard
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Vérifier si c'est une page dashboard
        return $post->post_name === 'sisme-user-tableau-de-bord' || 
               strpos($post->post_content, '[sisme_user_dashboard]') !== false;
    }
    
    /**
     * Ajouter la section développeur aux sections accessibles
     */
    public function add_developer_section($accessible_sections, $user_id) {
        if (is_user_logged_in()) {
            $accessible_sections[] = 'developer';
            
            if (Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
                $accessible_sections[] = 'submit-game';
            }
        }
        return $accessible_sections;
    }
    
    /**
     * Ajouter l'item de navigation développeur
     */
    public function add_developer_nav_item($nav_items, $user_id) {
        if (!is_user_logged_in()) {
            return $nav_items;
        }
        
        $developer_status = Sisme_User_Developer_Data_Manager::get_developer_status($user_id);
        
        // Configuration selon le statut
        $nav_config = $this->get_nav_config_by_status($developer_status);
        
        $nav_items[] = [
            'section' => 'developer',
            'icon' => $nav_config['icon'],
            'text' => $nav_config['text'],
            'badge' => $nav_config['badge'],
            'class' => 'sisme-nav-developer-' . $developer_status
        ];
        
        return $nav_items;
    }
    
    /**
     * Rendu de la section développeur
     */
    public function render_developer_section($content, $section, $dashboard_data) {
        if ($section !== 'developer' && $section !== 'submit-game') {
            return $content;
        }
        
        if (!class_exists('Sisme_User_Developer_Renderer')) {
            return '<p>Erreur: Module développeur non disponible.</p>';
        }
        
        $user_id = get_current_user_id();
        $developer_status = Sisme_User_Developer_Data_Manager::get_developer_status($user_id);
        
        if ($section === 'submit-game') {
            return Sisme_User_Developer_Renderer::render_submit_game_section($user_id, $developer_status, $dashboard_data);
        }

        return Sisme_User_Developer_Renderer::render_developer_section($user_id, $developer_status, $dashboard_data);
    }
    
    /**
     * Ajouter 'developer' aux sections JavaScript valides
     */
    public function add_developer_valid_section($valid_sections) {
        $valid_sections[] = 'developer';
        $valid_sections[] = 'submit-game';
        return $valid_sections;
    }
    
    /**
     * Configuration navigation selon le statut développeur
     */
    private function get_nav_config_by_status($status) {
        $configs = [
            Sisme_Utils_Users::DEVELOPER_STATUS_NONE => [
                'icon' => '📝',
                'text' => 'Devenir Développeur',
                'badge' => null
            ],
            Sisme_Utils_Users::DEVELOPER_STATUS_PENDING => [
                'icon' => '⏳',
                'text' => 'Candidature en cours',
                'badge' => '1'
            ],
            Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED => [
                'icon' => '🎮',
                'text' => 'Mes Jeux',
                'badge' => null
            ],
            Sisme_Utils_Users::DEVELOPER_STATUS_REJECTED => [
                'icon' => '❌',
                'text' => 'Candidature rejetée',
                'badge' => null
            ]
        ];
        
        return $configs[$status] ?? $configs[Sisme_Utils_Users::DEVELOPER_STATUS_NONE];
    }
    
    /**
     * Méthodes utilitaires publiques
     */
    
    /**
     * Vérifier si un utilisateur peut soumettre des jeux
     */
    public static function can_submit_games($user_id) {
        return Sisme_User_Developer_Data_Manager::is_approved_developer($user_id);
    }
    
    /**
     * Récupérer les données développeur d'un utilisateur
     */
    public static function get_developer_data($user_id) {
        if (!class_exists('Sisme_User_Developer_Data_Manager')) {
            return null;
        }
        
        return Sisme_User_Developer_Data_Manager::get_developer_data($user_id);
    }

    private function ensure_database_ready() {
    require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
    
    if (!Sisme_Submission_Database::table_exists()) {
        Sisme_Submission_Database::create_table();
    }
}
}