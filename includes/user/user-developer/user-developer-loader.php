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
            'user-developer-renderer.php'
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
            array('jquery', 'sisme-user-dashboard'), // Dépendances
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Developer] Assets chargés');
        }
    }
    
    /**
     * Vérifier si les assets doivent être chargés
     */
    private function should_load_assets() {
        // Charger sur la page dashboard
        if (is_page('sisme-user-tableau-de-bord')) {
            return true;
        }
        
        // Charger si shortcode dashboard présent
        global $post;
        if ($post && has_shortcode($post->post_content, 'sisme_user_dashboard')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Ajouter la section développeur aux sections accessibles
     */
    public function add_developer_section($accessible_sections, $user_id) {
        // Toujours ajouter la section développeur (la visibilité sera gérée par l'état)
        $accessible_sections[] = 'developer';
        
        return $accessible_sections;
    }
    
    /**
     * Ajouter l'item de navigation développeur
     */
    public function add_developer_nav_item($nav_items, $user_id) {
        $developer_status = $this->get_developer_status($user_id);
        
        // Déterminer l'icône et le texte selon le statut
        $icon_map = [
            'none' => '📝',
            'pending' => '⏳',
            'approved' => '🎮',
            'rejected' => '❌'
        ];
        
        $text_map = [
            'none' => 'Devenir Développeur',
            'pending' => 'Candidature en cours',
            'approved' => 'Mes Jeux',
            'rejected' => 'Candidature rejetée'
        ];
        
        $icon = $icon_map[$developer_status] ?? $icon_map['none'];
        $text = $text_map[$developer_status] ?? $text_map['none'];
        
        $nav_items[] = [
            'section' => 'developer',
            'icon' => $icon,
            'text' => $text,
            'badge' => $developer_status === 'pending' ? '1' : null,
            'class' => 'sisme-nav-developer-' . $developer_status
        ];
        
        return $nav_items;
    }
    
    /**
     * Rendre la section développeur
     */
    public function render_developer_section($content, $section, $dashboard_data) {
        if ($section !== 'developer') {
            return $content;
        }
        
        if (!class_exists('Sisme_User_Developer_Renderer')) {
            return '<div class="sisme-error">Module développeur non disponible</div>';
        }
        
        $user_id = $dashboard_data['user_info']['id'];
        $developer_status = $this->get_developer_status($user_id);
        
        return Sisme_User_Developer_Renderer::render_developer_section($user_id, $developer_status, $dashboard_data);
    }
    
    /**
     * Ajouter la section développeur aux sections JavaScript valides
     */
    public function add_developer_valid_section($valid_sections) {
        $valid_sections[] = 'developer';
        return $valid_sections;
    }
    
    /**
     * Récupérer le statut développeur d'un utilisateur
     */
    private function get_developer_status($user_id) {
        $status = get_user_meta($user_id, 'sisme_user_developer_status', true);
        return $status ?: 'none';
    }
    
    /**
     * Vérifier si l'utilisateur peut soumettre des jeux
     */
    public static function can_submit_games($user_id) {
        $status = get_user_meta($user_id, 'sisme_user_developer_status', true);
        return $status === 'approved';
    }
    
    /**
     * Récupérer les données développeur
     */
    public static function get_developer_data($user_id) {
        if (!class_exists('Sisme_User_Developer_Data_Manager')) {
            return null;
        }
        
        return Sisme_User_Developer_Data_Manager::get_developer_data($user_id);
    }
}