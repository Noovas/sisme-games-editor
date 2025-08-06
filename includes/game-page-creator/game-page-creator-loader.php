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
        add_action('init', array($this, 'add_game_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_game_page_request'));
        
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
     * Ajouter les règles de réécriture pour les pages de jeu
     */
    public function add_game_rewrite_rules() {
        // Forcer la suppression de l'option pour debug
        delete_option('sisme_game_page_rules_flushed');
        
        // Règle pour capturer les URLs de jeu : /nom-du-jeu/
        add_rewrite_rule(
            '^([^/]+)/?$',
            'index.php?sisme_game_page=$matches[1]',
            'top'
        );
        
        error_log('[Game Page Creator] Rewrite rule added: ^([^/]+)/?$ -> index.php?sisme_game_page=$matches[1]');
        
        // Ajouter la query var
        add_filter('query_vars', function($vars) {
            $vars[] = 'sisme_game_page';
            error_log('[Game Page Creator] Query var sisme_game_page added');
            return $vars;
        });
        
        // Flush immédiatement
        flush_rewrite_rules(false);
        error_log('[Game Page Creator] Rewrite rules flushed immediately');
    }
    
    /**
     * Flush les règles de réécriture si nécessaire
     */
    private function maybe_flush_rewrite_rules() {
        // Forcer le flush pour debug
        delete_option('sisme_game_page_rules_flushed');
        
        if (!get_option('sisme_game_page_rules_flushed')) {
            add_action('wp_loaded', function() {
                flush_rewrite_rules(false);
                update_option('sisme_game_page_rules_flushed', true);
                error_log('[Game Page Creator] Rewrite rules flushed - home_url: ' . home_url());
            });
        }
    }
    
    /**
     * Intercepter les requêtes de pages de jeu
     */
    public function handle_game_page_request() {
        $game_slug = get_query_var('sisme_game_page');
        
        // Debug: log de toutes les query vars
        error_log('[Game Page Creator] Debug - URL: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
        error_log('[Game Page Creator] Debug - game_slug: ' . ($game_slug ?: 'EMPTY'));
        
        if (empty($game_slug)) {
            return; // Pas une page de jeu
        }
        
        error_log('[Game Page Creator] GAME PAGE DETECTED: ' . $game_slug);
        
        // Chercher le terme correspondant au slug
        $term = get_term_by('slug', $game_slug, 'post_tag');
        
        if (!$term || is_wp_error($term)) {
            error_log('[Game Page Creator] Term not found: ' . $game_slug);
            return; // Terme non trouvé, laisser WordPress gérer la 404
        }
        
        // Vérifier que c'est bien un jeu (a des données de jeu)
        $game_data = get_term_meta($term->term_id, 'sisme_game_data', true);
        if (empty($game_data)) {
            error_log('[Game Page Creator] No game data for term: ' . $term->term_id);
            return; // Pas un jeu, laisser WordPress gérer
        }
        
        error_log('[Game Page Creator] Rendering game page for: ' . $game_slug);
        
        // Afficher la page de jeu
        $this->render_game_page($term->term_id);
        exit;
    }
    
    /**
     * Rendre une page de jeu complète
     */
    private function render_game_page($term_id) {
        // Inclure les fichiers nécessaires
        if (!class_exists('Sisme_Game_Data_Formatter')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/game-data-formatter.php';
        }
        if (!class_exists('Sisme_Game_Page_Renderer')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/game-page-renderer.php';
        }
        
        // Formater les données du jeu
        $game_data = Sisme_Game_Data_Formatter::format_game_data($term_id);
        
        if (!$game_data) {
            wp_die('Erreur : Impossible de charger les données du jeu.', 'Erreur de jeu', array('response' => 500));
        }
        
        // Générer le HTML
        $content = Sisme_Game_Page_Renderer::render($game_data);
        
        // Obtenir le header et footer du thème
        get_header();
        
        echo '<main class="sisme-game-page-wrapper">';
        echo $content;
        echo '</main>';
        
        get_footer();
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