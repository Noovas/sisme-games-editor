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
            '🎮 Jeux',
            'manage_options',
            'sisme-games-jeux',
            array(__CLASS__, 'render')
        );
    }
    
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
        
        $page = new Sisme_Admin_Page_Wrapper(
            'Jeux',
            'Hub des outils de gestion des jeux et catalogue',
            'games',
            admin_url('admin.php?page=sisme-games-tableau-de-bord'),
            'Retour au tableau de bord'
        );
        self::render_games_hub();
    }
    
    private static function render_games_hub() {
        ?>
        <div class="sisme-admin-container">
            <h2 class="sisme-admin-title">🎮 HUB pour les jeux</h2>
            <p class="sisme-admin-comment">Tous les outils de gestion des jeux en un seul endroit</p>

            <div class="sisme-admin-grid sisme-admin-grid-2">
                <!-- Tous les jeux -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">🎮</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">Tous les jeux</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">Gestion complète du catalogue de jeux, édition et administration</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-info">Catalogue</span>
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-all'); ?>" class="sisme-admin-btn sisme-admin-btn-primary">
                            🎮 Accéder
                        </a>
                    </div>
                </div>
                
                <!-- Jeux vedettes -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">⭐</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">Jeux vedettes</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">Configuration et gestion des jeux mis en avant sur le site</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-warning">Vedettes</span>
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-vedettes'); ?>" class="sisme-admin-btn sisme-admin-btn-primary">
                            ⭐ Accéder
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialiser seulement si on est en admin
if (is_admin()) {
    Sisme_Admin_Games::init();
}
