<?php
/**
 * Page principale Jeux - Hub des outils de gestion des jeux
 * 
 * Cette page centralise tous les outils et fonctionnalités liées à la gestion des jeux :
 * - Gestion des jeux et catalogue
 * - Configuration des jeux vedettes
 * - Statistiques des jeux
 * - Outils d'administration
 */

if (!defined('ABSPATH')) {
    die('Accès direct interdit');
}

class Sisme_Admin_Games {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            'sisme-games-tableau-de-bord',
            'Jeux',
            '└► 🎮 Jeux',
            'manage_options',
            'sisme-games-jeux',
            array(__CLASS__, 'render')
        );
    }

    /**
     * Affiche la page principale du hub de jeux
     */
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
        
        $page = new Sisme_Admin_Page_Wrapper(
            'Jeux',
            'Centre de commande pour tous les outils de gestion des jeux',
            'jeu',
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
            'Tous les jeux',
            'jeu',
            'Gestion complète du catalogue de jeux, édition et administration',
            admin_url('admin.php?page=sisme-games-all-games')
        );

        Sisme_Admin_Page_Wrapper::render_menu_card(
            'Jeux vedettes',
            'featured',
            'Configuration et gestion des jeux mis en avant sur le site',
            admin_url('admin.php?page=sisme-games-vedettes')
        );
    }
}
