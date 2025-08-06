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
            '🛠️ Outils',
            'manage_options',
            'sisme-games-outils',
            array(__CLASS__, 'render')
        );
    }
    
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
        
        $page = new Sisme_Admin_Page_Wrapper(
            'Outils',
            'Centre d\'outils et utilitaires pour la gestion du plugin',
            'settings',
            admin_url('admin.php?page=sisme-games-tableau-de-bord'),
            'Retour au tableau de bord'
        );
        self::render_outils_content();
    }
    
    private static function render_outils_content() {
        ?>
        <div class="sisme-admin-container">
            <h2 class="sisme-admin-title">🛠️ Outils disponibles</h2>
            <p class="sisme-admin-comment">Centre de commande pour tous les outils d'administration et de maintenance</p>
            
            <div class="sisme-admin-grid sisme-admin-grid-2">
                <!-- Inspecteur de données -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">🛢️</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">Inspecteur de données</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">Examiner la structure des données des jeux pour le développement et le débogage</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-info">Développement</span>
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-data-inspector'); ?>" class="sisme-admin-btn sisme-admin-btn-primary">
                            🔍 Accéder
                        </a>
                    </div>
                </div>
                
                <!-- SEO des Jeux -->
                <?php if (class_exists('Sisme_SEO_Admin')): ?>
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">🔍</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">SEO des Jeux</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">Monitoring et diagnostic du référencement, analyse des performances SEO</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-success">SEO</span>
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-seo'); ?>" class="sisme-admin-btn sisme-admin-btn-primary">
                            📊 Accéder
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Migration -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">📦</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">Migration</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">Outils de migration et transfert de données entre environnements</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-warning">Migration</span>
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-migration'); ?>" class="sisme-admin-btn sisme-admin-btn-primary">
                            📦 Accéder
                        </a>
                    </div>
                </div>
                
                <!-- Placeholder pour futurs outils -->
                <div class="sisme-admin-card sisme-admin-opacity-75">
                    <div class="sisme-admin-card-header">
                        <div class="sisme-admin-flex-center">
                            <span style="font-size: 24px;">➕</span>
                            <h3 class="sisme-admin-heading sisme-admin-m-0">Autres outils</h3>
                        </div>
                    </div>
                    <p class="sisme-admin-comment">D'autres outils d'administration seront ajoutés au fur et à mesure des besoins</p>
                    <div class="sisme-admin-flex-between sisme-admin-mt-md">
                        <span class="sisme-admin-badge sisme-admin-badge-secondary">À venir</span>
                        <button class="sisme-admin-btn sisme-admin-btn-secondary" disabled>
                            🚀 Bientôt disponible
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
