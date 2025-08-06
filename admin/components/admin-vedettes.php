<?php
/**
 * File: /sisme-games-editor/admin/components/admin-vedettes.php
 * Classe pour gérer le menu admin des vedettes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Vedettes {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            'sisme-games-tableau-de-bord',
            'Vedettes',
            '⭐ Vedettes',
            'manage_options',
            'sisme-games-vedettes',
            array(__CLASS__, 'render')
        );
    }
    
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/vedettes.php';
    }
}
