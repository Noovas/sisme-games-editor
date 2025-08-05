<?php
/**
 * File: /sisme-games-editor/includes/game-page-creator/game-page-creator-loader.php
 * Loader du module Game Page Creator - Suit l'architecture modulaire du projet
 * 
 * RESPONSABILITÉ:
 * - Chargement des composants du module game-page-creator
 * - Initialisation selon le pattern du projet
 * - Gestion des assets CSS depuis assets/ du module
 * - Logging debug des modules chargés
 * 
 * DÉPENDANCES:
 * - Aucune (module autonome)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_Page_Creator_Loader {
    
    private static $instance = null;
    private static $modules_loaded = false;
    private $loaded_modules = array();
    
    /**
     * Modules game-page-creator disponibles
     */
    private $available_modules = array(
        'game-data-formatter.php' => 'Formatage données terme',
        'game-media-handler.php' => 'Gestion médias YouTube et images',
        'game-sections-builder.php' => 'Construction sections personnalisées',
        'game-page-renderer.php' => 'Rendu HTML page complète',
        'game-page-creator.php' => 'API principale du module',
        'game-page-creator-publisher.php' => 'Publisher et intégration WordPress',
        'game-page-content-filter.php' => 'Filtre de contenu pour rendu dynamique'
    );
    
    /**
     * Singleton - Obtenir l'instance unique
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialisation du module game-page-creator
     */
    private function init() {
        $this->load_game_page_creator_modules();
        $this->register_hooks();
        $this->log_initialization();
    }
    
    /**
     * Charger tous les modules game-page-creator
     */
    private function load_game_page_creator_modules() {
        if (self::$modules_loaded) {
            return;
        }
        $game_page_creator_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/';
        foreach ($this->available_modules as $module_file => $module_description) {
            $this->load_module($game_page_creator_dir, $module_file, $module_description);
        }
        self::$modules_loaded = true;
    }
    
    /**
     * Charger un module game-page-creator spécifique
     */
    private function load_module($game_page_creator_dir, $module_file, $module_description) {
        $module_path = $game_page_creator_dir . $module_file;
        if (file_exists($module_path)) {
            require_once $module_path;
            $this->loaded_modules[] = $module_file;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Game Page Creator] Module chargé: {$module_description} ({$module_file})");
            }
        } else {
            error_log("[Game Page Creator] ERREUR - Module manquant: {$module_file}");
        }
    }
    
    /**
     * Enregistrer les hooks WordPress
     */
    private function register_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        if (class_exists('Sisme_Game_Page_Content_Filter')) {
            Sisme_Game_Page_Content_Filter::init();
        }
        if (class_exists('Sisme_Game_Page_Creator_Publisher')) {
            Sisme_Game_Page_Creator_Publisher::init();
        }
    }
    
    /**
     * Charger les assets frontend depuis le dossier assets/ du module
     */
    public function enqueue_frontend_assets() {
        if (is_admin()) {
            return;
        }
        if ($this->should_load_assets()) {
            wp_enqueue_style(
                'sisme-game-page',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/game-page-creator/assets/game-page.css',
                array('sisme-frontend-tokens-global'),
                SISME_GAMES_EDITOR_VERSION
            );
        }
    }
    
    /**
     * Déterminer si les assets doivent être chargés
     */
    private function should_load_assets() {
        global $post;
        if (is_single() && $post) {
            $game_sections = get_post_meta($post->ID, '_sisme_game_sections', true);
            if (!empty($game_sections)) {
                return true;
            }
        }
        if (is_tag()) {
            return true;
        }
        return false;
    }
    
    /**
     * Log d'initialisation du module
     */
    private function log_initialization() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $loaded_count = count($this->loaded_modules);
            $total_count = count($this->available_modules);
            error_log("[Game Page Creator] Module initialisé - {$loaded_count}/{$total_count} composants chargés");
        }
    }
    
    /**
     * Vérifier si le module est correctement chargé
     */
    public function is_loaded() {
        return self::$modules_loaded && count($this->loaded_modules) === count($this->available_modules);
    }
    
    /**
     * Obtenir les statistiques du module
     */
    public function get_module_stats() {
        return array(
            'loaded' => self::$modules_loaded,
            'modules_count' => count($this->loaded_modules),
            'total_modules' => count($this->available_modules),
            'modules_loaded' => $this->loaded_modules,
            'classes_available' => array(
                'Sisme_Game_Page_Creator' => class_exists('Sisme_Game_Page_Creator'),
                'Sisme_Game_Data_Formatter' => class_exists('Sisme_Game_Data_Formatter'),
                'Sisme_Game_Page_Renderer' => class_exists('Sisme_Game_Page_Renderer'),
                'Sisme_Game_Media_Handler' => class_exists('Sisme_Game_Media_Handler'),
                'Sisme_Game_Sections_Builder' => class_exists('Sisme_Game_Sections_Builder')
            )
        );
    }
}