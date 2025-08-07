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
            '👥 Utilisateurs',
            'manage_options',
            'sisme-games-users',
            array(__CLASS__, 'render')
        );
    }
    
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
        
        $page = new Sisme_Admin_Page_Wrapper(
            'Utilisateurs',
            'Gestion et administration des utilisateurs du plugin',
            'users',
            admin_url('admin.php?page=sisme-games-tableau-de-bord'),
            'Retour au tableau de bord'
        );
        self::render_users_content();
    }
    
    private static function render_users_content() {
        ?>
        <div class="sisme-admin-container">
            <h2 class="sisme-admin-title">👥 Gestion des utilisateurs</h2>
            <p class="sisme-admin-comment">Centre de gestion pour tous les aspects liés aux utilisateurs</p>
            
            <div class="sisme-admin-grid sisme-admin-grid-2">
                <!-- Tous les utilisateurs -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">👤</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">Tous les utilisateurs</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">Vue d'ensemble et gestion de tous les utilisateurs inscrits sur la plateforme</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-info">Général</span>
                        <button class="sisme-admin-btn sisme-admin-btn-secondary" disabled>
                            👤 Accéder (Bientôt disponible)
                        </button>
                    </div>
                </div>
                
                <!-- Développeurs -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">💻</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">Développeurs</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">Gestion spécifique des développeurs et de leurs privilèges de création de jeux</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-success">Développement</span>
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-developers'); ?>" class="sisme-admin-btn sisme-admin-btn-primary">
                            💻 Accéder
                        </a>
                    </div>
                </div>
                
                <!-- Utilisateurs Premium -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">💎</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">Utilisateurs Premium</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">Administration des abonnements premium et des privilèges associés</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-warning">Premium</span>
                        <button class="sisme-admin-btn sisme-admin-btn-secondary" disabled>
                            💎 Accéder (Bientôt disponible)
                        </button>
                    </div>
                </div>
                
                <!-- Statistiques -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">📊</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">Statistiques</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">Analyse et rapports détaillés sur l'activité et l'engagement des utilisateurs</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-info">Analytics</span>
                        <button class="sisme-admin-btn sisme-admin-btn-secondary" disabled>
                            📊 Accéder (Bientôt disponible)
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
