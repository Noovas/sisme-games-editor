<?php
/**
 * Page principale Communication - Hub des outils de communication
 * 
 * Cette page centralise tous les outils et fonctionnalités liées à la communication :
 * - Gestion des emails et notifications
 * - Statistiques d'engagement
 * - Templates de communication
 * - Configuration des notifications
 */

if (!defined('ABSPATH')) {
    die('Accès direct interdit');
}

class Sisme_Admin_Communication {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            'sisme-games-tableau-de-bord',
            'Communication',
            '└► 📢 La Com.',
            'manage_options',
            'sisme-games-communication',
            array(__CLASS__, 'render')
        );
    }
    
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/assets/PHP-admin-page-wrapper.php';
        
        $page = new Sisme_Admin_Page_Wrapper(
            'Communication',
            'Centre de commande pour tous les outils de communication et engagement',
            'communication',
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
     * Affiche le contenu du hub de communication
     */
    private static function render_menu() {
        Sisme_Admin_Page_Wrapper::render_menu_card(
            'Gestion Email',
            'email',
            'Gérez les emails automatiques, templates et statistiques d\'envoi',
            admin_url('admin.php?page=sisme-games-email')
        );

        Sisme_Admin_Page_Wrapper::render_menu_card(
            'Notifications',
            'notifications',
            'Système de notifications et alertes utilisateur, configuration et historique',
            admin_url('admin.php?page=sisme-games-notifications')
        );
    }
}
