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
            '📢 Communication',
            'manage_options',
            'sisme-games-communication',
            array(__CLASS__, 'render')
        );
    }
    
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
        
        $page = new Sisme_Admin_Page_Wrapper(
            'Communication',
            'Hub des outils de communication, notifications et engagement utilisateur',
            'email-alt',
            admin_url('admin.php?page=sisme-games-tableau-de-bord'),
            'Retour au tableau de bord'
        );
        self::render_communication_hub();
    }
    
    private static function render_communication_hub() {
        ?>
        <div class="sisme-admin-container">
            <h2 class="sisme-admin-title">📢 Outils disponibles</h2>
            <p class="sisme-admin-comment">Centre de commande pour tous les outils de communication et engagement</p>
            
            <div class="sisme-admin-grid sisme-admin-grid-2">
                <!-- Gestion Email -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">📧</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">Gestion Email</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">Gestion des emails automatiques, templates et statistiques d'envoi</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-info">Communication</span>
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-email'); ?>" class="sisme-admin-btn sisme-admin-btn-primary">
                            📧 Accéder
                        </a>
                    </div>
                </div>
                
                <!-- Notifications -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">🔔</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">Notifications</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">Système de notifications et alertes utilisateur, configuration et historique</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-success">Engagement</span>
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-notifications'); ?>" class="sisme-admin-btn sisme-admin-btn-primary">
                            🔔 Accéder
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
    Sisme_Admin_Communication::init();
}
