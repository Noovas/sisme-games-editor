<?php
/**
 * Fichier: /sisme-games-editor/admin/components/nom_du_fichier.php
 * Classe pour 
 * 
 * RESPONSABILITÉ:
 * - 
 * - 
 * -
 * 
 * DÉPENDANCES:
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

if (current_user_can('manage_options')) {
    Sisme_Admin_Page_Template::init();
}

class Sisme_Admin_Page_Template {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_menu', array(__CLASS__, 'admin_include'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
    }

    public static function admin_include() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/assets/PHP-admin-page-wrapper.php';
    }

    public static function enqueue_admin_scripts() {
        wp_enqueue_script(
            'sisme-admin-search-bar',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'admin/assets/JS-admin-search-bar.js',
            array(),
            null,
            true
        );
    }

    public static function add_admin_menu() {
        add_submenu_page(
            null,
            'Titre_menu',
            'icon',
            'manage_options',
            'slug_de_la_page',
            array(__CLASS__, 'render')
        );
    }

    public static function render() {
        $page = new Sisme_Admin_Page_Wrapper(
            'Titre',
            'Sous_titre',
            'icon',
            admin_url('admin.php?page=slug_page_de_retour'),
            'Retour vers page_de_retour',
        );
        
        $page->render_start();
        self::render_stats();
        $page->render_end();    
    }

    public static function render_stats() {

        Sisme_Admin_Page_Wrapper::render_card_start(
            'Statistiques',
            'stats',
            '',
            'sisme-admin-grid sisme-admin-grid-4',
            false,
        );
            ?>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-info">
                <div class="sisme-admin-stat-number"><?php echo 'NC' ?></div>
                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('icon', 0, 12) ?> Stat_1</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-special">
                <div class="sisme-admin-stat-number"><?php echo 'NC' ?></div>
                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('icon', 0, 12) ?> Stat_2</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-danger">
                <div class="sisme-admin-stat-number"><?php echo 'NC' ?></div>
                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('icon', 0, 12) ?> Stat_3</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-warning">
                <div class="sisme-admin-stat-number"><?php echo 'NC' ?></div>
                <div class="sisme-admin-stat-label"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('icon', 0, 12) ?> Stat_4</div>
            </div>
            <?php
        Sisme_Admin_Page_Wrapper::render_card_end();
    }
}
