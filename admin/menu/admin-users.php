<?php
/**
 * File: /sisme-games-editor/admin/menu/admin-users.php
 * Classe pour gérer le sous-menu Utilisateurs et ses pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Users {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            'sisme-games-tableau-de-bord',
            'Utilisateurs',
            '└► 👥 Utilisateurs',
            'manage_options',
            'sisme-games-users',
            array(__CLASS__, 'render')
        );
    }

    /**
     * Affiche la page principale du hub de jeux
     */
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/assets/PHP-admin-page-wrapper.php';
        
        $page = new Sisme_Admin_Page_Wrapper(
            'Utilisateurs',
            'Centre d\'outils et utilitaires pour la gestion des utilisateurs',
            'users',
            admin_url('admin.php?page=sisme-games-tableau-de-bord'),
            'Retour au tableau de bord',
            true,
            'folder',
            '',
        );

        $page->render_start();
        self::render_menu();
        $page->render_end();
    }

    /**
     * Affiche le contenu du hub de jeux
     */
    private static function render_menu() {
        Sisme_Admin_Page_Wrapper::render_menu_card(
            'Utilisateurs',
            'users',
            'Gestion des utilisateurs',
            admin_url('admin.php?page=sisme-games-users-page')
        );

        Sisme_Admin_Page_Wrapper::render_menu_card(
            'Utilisateurs Premium',
            'premium',
            'Gestion des utilisateurs premium',
            admin_url('admin.php?page=sisme-games-users-premium')
        );

        Sisme_Admin_Page_Wrapper::render_menu_card(
            'Développeurs',
            'devs',
            'Gestion des développeurs',
            admin_url('admin.php?page=sisme-games-developers')
        );
        
        Sisme_Admin_Page_Wrapper::render_menu_card(
            'Statistiques',
            'stats',
            'Analyse et rapports sur l\'activité des utilisateurs',
            admin_url('admin.php?page=sisme-games-users-stats')
        );
    }
}
