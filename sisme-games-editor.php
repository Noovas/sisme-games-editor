<?php
/**
 * Plugin Name: Sisme Games Editor
 * Description: Plugin pour la création rapide d'articles gaming (Fiches de jeu, Patch/News, Tests)
 * Version: 1.0.0
 * Author: Sisme Games
 * Text Domain: sisme-games-editor
 * Domain Path: /languages
 * 
 * File: /sisme-games-editor/sisme-games-editor.php
 */

// Sécurité : Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Définition des constantes du plugin
define('SISME_GAMES_EDITOR_VERSION', '1.0.0');
define('SISME_GAMES_EDITOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SISME_GAMES_EDITOR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SISME_GAMES_EDITOR_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale du plugin Sisme Games Editor
 */
class SismeGamesEditor {
    
    /**
     * Instance singleton
     */
    private static $instance = null;
    
    /**
     * Obtenir l'instance singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé pour le singleton
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialiser les hooks WordPress
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'fix_admin_urls'));
        
        // Hook d'activation et désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Inclure les fichiers nécessaires
        $this->include_files();
    }
    
    /**
     * Corriger les URLs admin malformées
     */
    public function fix_admin_urls() {
        // Rediriger les URLs malformées vers les bonnes pages
        if (isset($_SERVER['REQUEST_URI'])) {
            $request_uri = $_SERVER['REQUEST_URI'];
            
            // Corriger admin.php/edit.php vers edit.php
            if (strpos($request_uri, 'admin.php/edit.php') !== false) {
                wp_redirect(admin_url('edit.php'));
                exit;
            }
            
            // Corriger admin.php/edit-tags.php vers edit-tags.php
            if (strpos($request_uri, 'admin.php/edit-tags.php') !== false) {
                wp_redirect(admin_url('edit-tags.php?taxonomy=category'));
                exit;
            }
        }
    }
    
    /**
     * Inclure les fichiers du plugin
     */
    private function include_files() {
        // Inclure le gestionnaire AJAX
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/ajax-handler.php';
        // Inclure les fonctions utilitaires
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/debug-helper.php';
        // Ne plus inclure les meta boxes car on utilise notre propre interface
        // require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/meta-boxes.php';
    }
    
    /**
     * Ajouter le menu admin
     */
    public function add_admin_menu() {
        // Page principale (Tableau de bord)
        add_menu_page(
            'Sisme Games Editor',
            'Games Editor',
            'manage_options',
            'sisme-games-editor',
            array($this, 'dashboard_page'),
            'dashicons-games',
            30
        );
        
        // Sous-pages
        add_submenu_page(
            'sisme-games-editor',
            'Tableau de bord',
            'Tableau de bord',
            'manage_options',
            'sisme-games-editor',
            array($this, 'dashboard_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Fiches de jeu',
            'Fiches de jeu',
            'manage_options',
            'sisme-games-fiches',
            array($this, 'fiches_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Patch & News',
            'Patch & News',
            'manage_options',
            'sisme-games-patch-news',
            array($this, 'patch_news_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Tests',
            'Tests',
            'manage_options',
            'sisme-games-tests',
            array($this, 'tests_page')
        );
        
        add_submenu_page(
            'sisme-games-editor',
            'Réglages',
            'Réglages',
            'manage_options',
            'sisme-games-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Charger les assets CSS et JS
     */
    public function enqueue_admin_assets($hook) {
        // Vérifier si on est sur une page du plugin
        if (strpos($hook, 'sisme-games') === false) {
            return;
        }
        
        // Charger automatiquement tous les fichiers CSS du dossier assets/css/
        $this->auto_enqueue_css_files();
        
        // Charger les styles des formulaires si nécessaire
        if (isset($_GET['action']) && $_GET['action'] === 'create') {
            wp_enqueue_script(
                'sisme-games-editor-forms',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/forms.js',
                array('jquery', 'media-upload'),
                SISME_GAMES_EDITOR_VERSION,
                true
            );
            
            wp_enqueue_script(
                'sisme-games-editor-content-builder',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/content-builder.js',
                array('jquery', 'sisme-games-editor-forms', 'media-upload'),
                SISME_GAMES_EDITOR_VERSION,
                true
            );
        }
        
        wp_enqueue_script(
            'sisme-games-editor-admin',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        // Localisation des données JavaScript
        wp_localize_script('sisme-games-editor-admin', 'sismeGamesEditor', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_games_nonce'),
            'ficheListUrl' => admin_url('admin.php?page=sisme-games-fiches'),
            'strings' => array(
                'confirmDelete' => 'Êtes-vous sûr de vouloir supprimer cet élément ?',
                'confirmDuplicate' => 'Voulez-vous vraiment dupliquer cet article ?',
                'loading' => 'Chargement...',
                'error' => 'Une erreur est survenue',
                'success' => 'Opération réussie'
            )
        ));
        
        // Enqueue des scripts WordPress nécessaires
        wp_enqueue_media();
    }
    
    /**
     * Charger automatiquement tous les fichiers CSS du dossier assets/css/
     */
    private function auto_enqueue_css_files() {
        $css_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'assets/css/';
        $css_url = SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/';
        
        // Vérifier si le dossier existe
        if (!is_dir($css_dir)) {
            return;
        }
        
        // Scanner le dossier pour les fichiers CSS
        $css_files = glob($css_dir . '*.css');
        
        if (empty($css_files)) {
            return;
        }
        
        // Trier les fichiers pour avoir un ordre de chargement prévisible
        sort($css_files);
        
        // Fichiers prioritaires (chargés en premier)
        $priority_files = array('admin.css', 'forms.css');
        $loaded_files = array();
        
        // Charger d'abord les fichiers prioritaires
        foreach ($priority_files as $priority_file) {
            $priority_path = $css_dir . $priority_file;
            if (file_exists($priority_path)) {
                $handle = 'sisme-games-editor-' . str_replace('.css', '', $priority_file);
                
                wp_enqueue_style(
                    $handle,
                    $css_url . $priority_file,
                    array(), // Pas de dépendances pour les fichiers prioritaires
                    $this->get_file_version($priority_path)
                );
                
                $loaded_files[] = $priority_file;
            }
        }
        
        // Charger ensuite tous les autres fichiers CSS
        foreach ($css_files as $css_file) {
            $filename = basename($css_file);
            
            // Ignorer si déjà chargé
            if (in_array($filename, $loaded_files)) {
                continue;
            }
            
            // Créer un handle unique basé sur le nom du fichier
            $handle = 'sisme-games-editor-' . str_replace('.css', '', $filename);
            
            // Les fichiers non-prioritaires dépendent des fichiers de base
            $dependencies = array();
            if (in_array('admin.css', $loaded_files)) {
                $dependencies[] = 'sisme-games-editor-admin';
            }
            if (in_array('forms.css', $loaded_files)) {
                $dependencies[] = 'sisme-games-editor-forms';
            }
            
            wp_enqueue_style(
                $handle,
                $css_url . $filename,
                $dependencies,
                $this->get_file_version($css_file)
            );
            
            $loaded_files[] = $filename;
        }
        
        // Log des fichiers chargés (pour debug en mode développement)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Games Editor - CSS files loaded: ' . implode(', ', $loaded_files));
        }
    }
    
    /**
     * Obtenir la version d'un fichier basée sur sa date de modification
     * Utile pour le cache busting pendant le développement
     */
    private function get_file_version($file_path) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // En mode debug, utiliser la date de modification pour forcer le rechargement
            return filemtime($file_path);
        }
        
        // En production, utiliser la version du plugin
        return SISME_GAMES_EDITOR_VERSION;
    }
    
    /**
     * Page Tableau de bord
     */
    public function dashboard_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/dashboard.php';
    }
    
    /**
     * Page Fiches de jeu
     */
    public function fiches_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/fiches.php';
    }
    
    /**
     * Page Patch & News
     */
    public function patch_news_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/patch-news.php';
    }
    
    /**
     * Page Tests
     */
    public function tests_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/tests.php';
    }
    
    /**
     * Page Réglages
     */
    public function settings_page() {
        include_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/pages/settings.php';
    }
    
    /**
     * Activation du plugin
     */
    public function activate() {
        // Actions à effectuer lors de l'activation
        flush_rewrite_rules();
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Actions à effectuer lors de la désactivation
        flush_rewrite_rules();
    }
}

// Initialiser le plugin
function sisme_games_editor() {
    return SismeGamesEditor::get_instance();
}

// Lancer le plugin
sisme_games_editor();