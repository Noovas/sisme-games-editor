<?php
/**
 * File: /sisme-games-editor/admin/components/admin-email.php
 * Page admin pour la gestion des emails
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Email {

    private static $debug = false;

    public static function init() {
        if (self::$debug) { error_log('[ADMIN EMAIL] init'); }
        add_action('admin_menu', array(__CLASS__, 'add_hidden_page'));
    }

    /**
     * Ajouter comme page cachée
     */
    public static function add_hidden_page() {
        if (self::$debug) { error_log('[ADMIN EMAIL] add_hidden_page start'); }
        add_submenu_page(
            null,
            'Gestion Email',
            'Gestion Email',
            'manage_options',
            'sisme-games-email',
            array(__CLASS__, 'render')
        );
        if (self::$debug) { error_log('[ADMIN EMAIL] add_hidden_page end'); }
    }
    
    public static function render() {
        if (self::$debug) { error_log('[ADMIN EMAIL] render start'); }
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';


        $page = new Sisme_Admin_Page_Wrapper(
            'Mail',
            'Gestion complète des emails',
            'email',
            admin_url('admin.php?page=sisme-games-communication'),
            'Retour au menu Communication',
        );
        
        $page->render_start();

        $page->render_end();
        if (self::$debug) { error_log('[ADMIN EMAIL] render end'); }
    }


}
