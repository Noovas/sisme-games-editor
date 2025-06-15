<?php
/**
 * Plugin Name: Sisme Games Editor
 * Description: Plugin pour la création rapide d'articles gaming (Fiches de jeu, Patch/News, Tests)
 * Version: 1.0.0
 * Author: Sisme Games
 * Text Domain: sisme-games-editor
 * 
 * File: /sisme-games-editor/sisme-games-editor.php
 * VERSION FONCTIONNELLE PURE - Sans CSS/JS
 */

// Sécurité : Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes du plugin
define('SISME_GAMES_EDITOR_VERSION', '1.0.0');
define('SISME_GAMES_EDITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SISME_GAMES_EDITOR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Classe principale du plugin Sisme Games Editor
 */
class SismeGamesEditor {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_form_submission'));
        add_action('wp_ajax_sisme_create_fiche', array($this, 'ajax_create_fiche'));
        
        // Inclure les fichiers nécessaires
        $this->include_files();
    }
    
    private function include_files() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/form-handler.php';
    }
    
    /**
     * Ajouter le menu admin
     */
    public function add_admin_menu() {
        // Page principale
        add_menu_page(
            'Sisme Games Editor',
            'Games Editor',
            'manage_options',
            'sisme-games-editor',
            array($this, 'dashboard_page'),
            'dashicons-games',
            30
        );
        
        // Sous-pages
        add_submenu_page(
            'sisme-games-editor',
            'Tableau de bord',
            'Tableau de bord',
            'manage_options',
            'sisme-games-editor',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Fiches de jeu',
            'Fiches de jeu',
            'manage_options',
            'sisme-games-fiches',
            array($this, 'fiches_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Patch & News',
            'Patch & News',
            'manage_options',
            'sisme-games-patch-news',
            array($this, 'patch_news_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Tests',
            'Tests',
            'manage_options',
            'sisme-games-tests',
            array($this, 'tests_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Réglages',
            'Réglages',
            'manage_options',
            'sisme-games-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Gestion de la soumission des formulaires
     */
    public function handle_form_submission() {
        if (!isset($_POST['sisme_form_action'])) {
            return;
        }
        
        // Vérification du nonce
        if (!wp_verify_nonce($_POST['sisme_nonce'], 'sisme_form')) {
            wp_die('Erreur de sécurité');
        }
        
        $form_handler = new Sisme_Form_Handler();
        
        switch ($_POST['sisme_form_action']) {
            case 'create_fiche_step1':
                $form_handler->handle_fiche_step1();
                break;
            case 'create_fiche_step2':
                $form_handler->handle_fiche_step2();
                break;
        }
    }
    
    /**
     * AJAX pour création de fiche
     */
    public function ajax_create_fiche() {
        check_ajax_referer('sisme_form', 'nonce');
        
        $form_handler = new Sisme_Form_Handler();
        $result = $form_handler->create_complete_fiche($_POST);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Pages du plugin
     */
    public function dashboard_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/dashboard.php';
    }
    
    public function fiches_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/fiches.php';
    }
    
    public function patch_news_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/patch-news.php';
    }
    
    public function tests_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/tests.php';
    }
    
    public function settings_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/settings.php';
    }
}

// Initialiser le plugin
function sisme_games_editor_init() {
    if (is_admin()) {
        SismeGamesEditor::get_instance();
    }
}
add_action('init', 'sisme_games_editor_init');