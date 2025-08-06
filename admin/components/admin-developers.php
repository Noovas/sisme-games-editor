<?php
/**
 * File: /sisme-games-editor/admin/components/admin-developers.php
 * Classe pour gérer le menu admin des développeurs
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Developers {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            'sisme-games-tableau-de-bord',
            'Développeurs',
            '👥 Développeurs',
            'manage_options',
            'sisme-games-developers',
            array(__CLASS__, 'render')
        );
    }
    
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/developers.php';
    }
}
