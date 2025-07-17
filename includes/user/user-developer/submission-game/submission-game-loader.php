<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/submission-game/submission-game-loader.php
 * Loader pour le module d'édition de soumissions de jeux
 * 
 * RESPONSABILITÉ:
 * - Chargement du module submission-game
 * - Initialisation des hooks AJAX
 * - Gestion des assets CSS/JS
 * - Intégration avec le système développeur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Submission_Game_Loader {
    
    private static $instance = null;
    private $is_initialized = false;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
    
    public function init() {
        if ($this->is_initialized) {
            return;
        }
        
        $this->load_dependencies();
        $this->register_hooks();
        $this->is_initialized = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Submission Game] Module initialisé');
        }
    }
    
    private function load_dependencies() {
        $base_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission-game/';
        
        $dependencies = [
            'submission-game-renderer.php',
            'submission-game-ajax.php'
        ];
        
        foreach ($dependencies as $file) {
            $file_path = $base_dir . $file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    private function register_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        if (function_exists('sisme_init_submission_game_ajax')) {
            sisme_init_submission_game_ajax();
        }
    }
    
    public function enqueue_assets() {
        if (!$this->should_load_assets()) {
            return;
        }
        
        $assets_url = SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-developer/submission-game/assets/';
        $assets_path = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission-game/assets/';
        
        if (file_exists($assets_path . 'submission-game.css')) {
            wp_enqueue_style(
                'sisme-submission-game',
                $assets_url . 'submission-game.css',
                ['sisme-user-developer'],
                filemtime($assets_path . 'submission-game.css')
            );
        }
        
        if (file_exists($assets_path . 'submission-game.js')) {
            wp_enqueue_script(
                'sisme-submission-game',
                $assets_url . 'submission-game.js',
                ['jquery', 'sisme-user-developer-ajax'],
                filemtime($assets_path . 'submission-game.js'),
                true
            );
        }
    }
    
    private function should_load_assets() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        if (is_admin()) {
            return false;
        }
        
        if (!class_exists('Sisme_User_Developer_Data_Manager')) {
            return false;
        }
        
        return Sisme_User_Developer_Data_Manager::is_approved_developer(get_current_user_id());
    }
    
    public static function render_submission_editor($submission_id = null) {
        if (!class_exists('Sisme_Submission_Game_Renderer')) {
            return '<p>Erreur: Module non disponible</p>';
        }
        
        return Sisme_Submission_Game_Renderer::render_editor($submission_id);
    }
}

function sisme_init_submission_game_loader() {
    Sisme_Submission_Game_Loader::get_instance();
}
add_action('init', 'sisme_init_submission_game_loader');