<?php
/**
 * Plugin Name: Sisme Games Editor
 * Description: Plugin pour la création rapide d'articles gaming (Fiches de jeu, Patch/News, Tests)
 * Version: 1.0.0
 * Author: Sisme Games
 * Text Domain: sisme-games-editor
 * 
 * File: /sisme-games-editor/sisme-games-editor.php
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SISME_GAMES_EDITOR_VERSION', '1.0.0');
define('SISME_GAMES_EDITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SISME_GAMES_EDITOR_PLUGIN_URL', plugin_dir_url(__FILE__));

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
        add_action('wp_ajax_sisme_add_editor', array($this, 'ajax_add_editor'));

        $this->include_files();

        new Sisme_Assets_Loader();
        new Sisme_Content_Filter();
        new Sisme_SEO_Enhancements();
        new Sisme_News_Manager();
    }
    
    private function include_files() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/form-handler.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/assets-loader.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/content-filter.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/seo-enhancements.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/news-manager.php';
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Sisme Games Editor',
            'Games Editor',
            'manage_options',
            'sisme-games-editor',
            array($this, 'dashboard_page'),
            'dashicons-games',
            30
        );
        
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

        add_submenu_page(
            null, 
            'Éditer une fiche',
            'Éditer une fiche',
            'manage_options',
            'sisme-games-edit-fiche',
            array($this, 'edit_fiche_page')
        );

        add_submenu_page(
            null,
            'Éditeur interne Sisme Games',
            'Éditeur interne',
            'manage_options',
            'sisme-games-internal-editor',
            array($this, 'internal_editor_page')
        );
    }
    
    public function handle_form_submission() {
        if (!isset($_POST['sisme_form_action']) && !isset($_POST['sisme_edit_action'])) {
            return;
        }
        
        if (isset($_POST['sisme_form_action'])) {
            if (!wp_verify_nonce($_POST['sisme_nonce'], 'sisme_form')) {
                wp_die('Erreur de sécurité');
            }
        } elseif (isset($_POST['sisme_edit_action'])) {
            if (!wp_verify_nonce($_POST['sisme_edit_nonce'], 'sisme_edit_form')) {
                wp_die('Erreur de sécurité');
            }
        }
        
        $form_handler = new Sisme_Form_Handler();
        
        if (isset($_POST['sisme_form_action'])) {
            switch ($_POST['sisme_form_action']) {
                case 'create_fiche_step1':
                    $form_handler->handle_fiche_step1();
                    break;
                case 'create_fiche_step2':
                    $form_handler->handle_fiche_step2();
                    break;
            }
        } elseif (isset($_POST['sisme_edit_action'])) {
            switch ($_POST['sisme_edit_action']) {
                case 'create_fiche':
                    $form_handler->handle_fiche_creation();
                    break;
                case 'update_fiche':
                    $form_handler->handle_fiche_update();
                    break;
            }
        }
    }
    
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

    public function ajax_add_editor() {
        check_ajax_referer('sisme_editor', 'nonce');
        
        $editor_id = sanitize_text_field($_POST['editor_id']);
        $section_number = intval($_POST['section_number']);
        
        ob_start();
        wp_editor('', $editor_id, array(
            'textarea_name' => 'sections[' . $section_number . '][content]',
            'textarea_rows' => 8,
            'media_buttons' => true,
            'teeny' => false,
            'tinymce' => true
        ));
        $editor_html = ob_get_clean();
        
        wp_send_json_success($editor_html);
    }
    
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

    public function edit_fiche_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/edit-fiche.php';
    }

    public function internal_editor_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/internal-editor.php';
    }
}

function sisme_games_editor_init() {
    SismeGamesEditor::get_instance();
}
add_action('init', 'sisme_games_editor_init');