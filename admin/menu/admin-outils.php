<?php
/**
 * File: /sisme-games-editor/admin/components/admin-outils.php
 * Classe pour gérer le sous-menu Outils et ses pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Outils {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            'sisme-games-tableau-de-bord',
            'Outils',
            '└► 🛠️ Outils',
            'manage_options',
            'sisme-games-outils',
            array(__CLASS__, 'render')
        );
    }

    /**
     * Affiche la page principale du hub de jeux
     */
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/assets/PHP-admin-page-wrapper.php';
        
        $page = new Sisme_Admin_Page_Wrapper(
            'Outils',
            'Centre d\'outils et utilitaires pour la gestion du plugin',
            'settings',
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
            'Inspecteur de données',
            'datas',
            'Examine la structure des données des jeux pour le développement et le débogage',
            admin_url('admin.php?page=sisme-games-data-inspector'),
        );

        Sisme_Admin_Page_Wrapper::render_menu_card(
            'SEO des Jeux',
            'seo',
            'Monitoring et diagnostic du référencement, analyse des performances SEO',
            admin_url('admin.php?page=sisme-games-seo'),
        );

        Sisme_Admin_Page_Wrapper::render_menu_card(
            'Migration',
            'migration',
            'Outils de migration et transfert de données entre environnements',
            admin_url('admin.php?page=sisme-games-migration'),
        );
    }
}
