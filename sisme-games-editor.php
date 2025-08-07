<?php
/**
 * Plugin Name: Sisme Games Editor
 * Description: Plugin pour la création rapide d'articles gaming (Fiches de jeu, Patch/News, Tests)
 * Version: 1.0.0
 * Author: Sisme Games
 * Text Domain: sisme-games-editor
 * 
 * File: /sisme-games-editor/sisme-games-editor.php
 */
if (!defined('ABSPATH')) {
    exit;
}

// Définir TOUTES les constantes AVANT de charger les fichiers
define('SISME_GAMES_EDITOR_VERSION', '1.0.0');
define('SISME_GAMES_EDITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SISME_GAMES_EDITOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SISME_GAMES_MODULES', array(
    "utils",
    "vedettes", 
    "cards",
    "search",
    "team-choice",
    "user",
    "seo",
    "email-manager",
    "dev-editor-manager",
    "game-data-creator",
    "game-page-creator",
    "migration"
));

// Charger les fichiers
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/sisme-constants.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/assets-loader.php';

/**
 * Charger automatiquement tous les fichiers utilitaires
 */
function sisme_load_utils() {
    $utils_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/utils/';
    
    // Vérifier que le dossier existe
    if (!is_dir($utils_dir)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Games Editor] Dossier utils non trouvé: ' . $utils_dir);
        }
        return;
    }
    
    // Scanner le dossier pour les fichiers PHP
    $files = glob($utils_dir . '*.php');
    
    if (empty($files)) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Games Editor] Aucun fichier PHP trouvé dans utils/');
        }
        return;
    }
    
    $loaded_count = 0;
    
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Exclure les fichiers commençant par un point ou underscore (convention fichiers privés)
        if (strpos($filename, '.') === 0 || strpos($filename, '_') === 0) {
            continue;
        }
        
        require_once $file;
        $loaded_count++;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Games Editor] Utilitaire chargé: {$filename}");
        }
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Sisme Games Editor] {$loaded_count} utilitaires chargés depuis utils/");
    }
}


class SismeGamesEditor {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
        Sisme_Beta_Indicator::init();
    }
    
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'load_admin_css'));
        add_action('init', array($this, 'init_modules_system'));

        $this->include_files();
        sisme_load_utils();

        new Sisme_Assets_Loader();
    }
    
    public function load_admin_css($hook) {
        // Charger uniquement sur les pages du plugin
        if (strpos($hook, 'sisme-games') !== false) {
            wp_enqueue_style(
                'sisme-admin-shared',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'admin/assets/admin-shared.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }
    }
    
    private function include_files() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/assets-loader.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/vedettes/vedettes-loader.php';
        
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/assets/php-admin-submission-functions.php';
        
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/components/admin-data-inspector.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/components/admin-developers.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/components/admin-vedettes.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/components/admin-migration.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/components/admin-notifications.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/components/admin-email.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/components/admin-all-games.php';

        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/menu/admin-communication.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/menu/admin-outils.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/menu/admin-games.php';
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/menu/admin-users.php';
        
        // Initialiser les classes admin
        Sisme_Admin_Data_Inspector::init();
        Sisme_Admin_Developers::init();
        Sisme_Admin_Vedettes::init();
        Sisme_Admin_Outils::init();
        Sisme_Admin_Communication::init();
        Sisme_Admin_Games::init();
        Sisme_Admin_All_Games::init();
        Sisme_Admin_Notifications::init();
        Sisme_Admin_Email::init();
        Sisme_Admin_Users::init();
    }

    public function add_admin_menu() {
        // Menu principal - Tableau de Bord comme page d'accueil
        add_menu_page(
            'Sisme Games',
            'Sisme Games',
            'manage_options',
            'sisme-games-tableau-de-bord',
            array(__CLASS__, 'render'),
            'dashicons-games',
            30
        );
        
        // Sous-menu Tableau de Bord (page d'accueil)
        add_submenu_page(
            'sisme-games-tableau-de-bord',
            'Tableau de Bord',
            '💻 Tableau de Bord',
            'manage_options',
            'sisme-games-tableau-de-bord',
            array(__CLASS__, 'render')
        );
    }

    // ===================================
    // PAGES ADMIN ESSENTIELLES
    // ===================================

    /**
     * Affiche la page principale du hub de jeux
     */
    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
        
        $page = new Sisme_Admin_Page_Wrapper(
            'Tableau de Bord',
            'Gestion de Sisme Games',
            'pc',
            '',
            '',
            true,
            'lib',
            'Catégorie d\'outils'
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
            'Les Jeux',
            'games',
            'Gestion des jeux : Tous les jeux, jeux en vedette',
            admin_url('admin.php?page=sisme-games-jeux')
        );

        Sisme_Admin_Page_Wrapper::render_menu_card(
            'La communication',
            'communication',
            'Centre de communication : Emails, Notifications, Statistiques',
            admin_url('admin.php?page=sisme-games-communication')
        );

        Sisme_Admin_Page_Wrapper::render_menu_card(
            'Utilisateurs',
            'users',
            'Gestion des utilisateurs : Utilisateurs, Premium, Développeurs',
            admin_url('admin.php?page=sisme-games-users')
        );

        Sisme_Admin_Page_Wrapper::render_menu_card(
            'Les outils',
            'outils',
            'Tous les outils : Outils techniques, Migration, SEO, etc.',
            admin_url('admin.php?page=sisme-games-outils')
        );
    }

    public function init_modules_system() {
        foreach (SISME_GAMES_MODULES as $module_name) {
            $loader_file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/' . $module_name . '/' . $module_name . '-loader.php';
            if (file_exists($loader_file)) {
                require_once $loader_file;
                $class_name = 'Sisme_' . ucfirst(str_replace('-', '_', $module_name)) . '_Loader';
                if (class_exists($class_name) && method_exists($class_name, 'get_instance')) {
                    $class_name::get_instance();
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[Sisme] Module initialisé : ' . $module_name . ' (' . str_replace('-', '_', $class_name) . ')');
                    }
                } else {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[Sisme] Classe introuvable : ' . $class_name);
                    }
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[Sisme] Module introuvable : ' . $module_name . ' (' . $loader_file . ')');
                }
            }
        }
    }
}

SismeGamesEditor::get_instance();


class Sisme_Beta_Indicator {
    
    const FEATURES_COMPLETED = [
        '(WIP) Espace devs',
        'Genres de jeux préférés',
        'Vue profil utilisateur',
        'Système d\'amis',
    ];
    
    const FEATURES_UPCOMING = [
        '(WIP) Espace devs',
        'Espace testeurs/guides',
        'Publication utilisateur',
        'Et plein d\'autres bricoles'
    ];
    
    /**
     * Initialiser l'indicateur béta
     */
    public static function init() {
        // Seulement sur le frontend
        if (!is_admin()) {
            add_action('wp_footer', [self::class, 'inject_beta_indicator']);
        }
    }
    
    /**
     * Injecter l'indicateur béta dans le header
     */
    public static function inject_beta_indicator() {
        $completed = json_encode(self::FEATURES_COMPLETED);
        $upcoming = json_encode(self::FEATURES_UPCOMING);
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const siteBranding = document.querySelector('.site-branding[data-id="logo"]');
            if (siteBranding) {
                // Données features
                const featuresCompleted = <?php echo $completed; ?>;
                const featuresUpcoming = <?php echo $upcoming; ?>;
                
                // Créer l'indicateur béta
                const betaIndicator = document.createElement('div');
                betaIndicator.className = 'sisme-beta-indicator';
                betaIndicator.innerHTML = `
                    <div class="sisme-beta-content">
                        <span class="sisme-beta-icon">🚧</span>
                        <span class="sisme-beta-text">ALPHA</span>
                    </div>
                    <div class="sisme-beta-tooltip">
                        <div class="sisme-beta-tooltip-content">
                            <div class="sisme-beta-columns">
                                <div class="sisme-beta-column">
                                    <h4>✅ Terminé</h4>
                                    <ul>
                                        ${featuresCompleted.map(f => `<li>${f}</li>`).join('')}
                                    </ul>
                                </div>
                                <div class="sisme-beta-column">
                                    <h4>🚧 À venir</h4>
                                    <ul>
                                        ${featuresUpcoming.map(f => `<li>${f}</li>`).join('')}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Event listeners
                const betaContent = betaIndicator.querySelector('.sisme-beta-content');
                const tooltip = betaIndicator.querySelector('.sisme-beta-tooltip');
                
                betaContent.addEventListener('mouseenter', () => {
                    tooltip.classList.add('sisme-beta-tooltip--visible');
                });
                
                betaContent.addEventListener('mouseleave', () => {
                    tooltip.classList.remove('sisme-beta-tooltip--visible');
                });
                
                // Insérer après le logo
                siteBranding.appendChild(betaIndicator);
            }
        });
        </script>
        
        <style>
        /* ===🚧 INDICATEUR BETA STYLÉ =============== */
        .sisme-beta-indicator {
            position: absolute;
            top: -8px;
            right: -15px;
            z-index: 1000;
        }
        
        .sisme-beta-content {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            color: #ffffff;
            padding: 4px 8px;
            border-radius: 12px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: 
                0 2px 8px rgba(26, 26, 46, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.2),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 3px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .sisme-beta-content:hover {
            transform: scale(1.05);
            box-shadow: 
                0 4px 12px rgba(26, 26, 46, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.3),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }
        
        .sisme-beta-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: sisme-beta-shine 2s infinite;
        }
        
        .sisme-beta-icon {
            font-size: 9px;
            filter: drop-shadow(0 1px 1px rgba(0, 0, 0, 0.5));
            color: #ffffff;
        }
        
        .sisme-beta-text {
            font-size: 9px;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.5);
            color: #ffffff;
        }
        
        /* ===💬 TOOLTIP FEATURES ==================== */
        .sisme-beta-tooltip {
            position: absolute;
            top: 100%;
            left: 0;
            margin-top: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            pointer-events: none;
        }
        
        .sisme-beta-tooltip--visible {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .sisme-beta-tooltip-content {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
            min-width: 400px;
            position: relative;
        }
        
        .sisme-beta-tooltip-content::before {
            content: '';
            position: absolute;
            top: -6px;
            left: 20px;
            width: 12px;
            height: 12px;
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-bottom: none;
            border-right: none;
            transform: rotate(45deg);
        }
        
        .sisme-beta-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .sisme-beta-column h4 {
            margin: 0 0 8px 0;
            font-size: 12px;
            font-weight: 600;
            color: #ffffff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .sisme-beta-column ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .sisme-beta-column li {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 4px;
            line-height: 1.3;
            position: relative;
            padding-left: 12px;
        }
        
        .sisme-beta-column li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: rgba(255, 255, 255, 0.5);
        }
        
        /* Animation shine */
        @keyframes sisme-beta-shine {
            0% { left: -100%; }
            50% { left: 100%; }
            100% { left: 100%; }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sisme-beta-indicator {
                top: -6px;
                right: -10px;
            }
            
            .sisme-beta-content {
                padding: 3px 6px;
                border-radius: 10px;
            }
            
            .sisme-beta-icon,
            .sisme-beta-text {
                font-size: 8px;
            }
            
            .sisme-beta-tooltip-content {
                min-width: 280px;
                padding: 12px;
            }
            
            .sisme-beta-columns {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
        
        /* S'assurer que le logo container est relatif */
        .site-branding[data-id="logo"] {
            position: relative !important;
        }
        </style>
        <?php
    }
}