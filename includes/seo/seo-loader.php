<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-loader.php
 * Loader du module SEO - Suit l'architecture modulaire du projet
 * 
 * RESPONSABILITÉ:
 * - Chargement  du module SEO
 * - Intégration avec seo-game-detector.php
 * - Vérification des dépendances système
 * - Logging debug et monitoring de santé
 * 
 * DÉPENDANCES:
 * - game-page-creator (game-data-formatter.php)
 * - seo-game-detector.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure la couche d'abstraction
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/seo/seo-game-detector.php';

class Sisme_SEO_Loader {

    private static $instance = null;
    private static $modules_loaded = false;
    private $loaded_modules = array();
    
    /**
     * Modules SEO disponibles dans l'ordre de chargement
     */
    private $available_modules = array(
        'seo-meta-tags.php' => 'Meta tags et descriptions automatiques',
        'seo-structured-data.php' => 'Données structurées Schema.org',
        'seo-open-graph.php' => 'Balises Open Graph et Twitter Cards',
        'seo-title-optimizer.php' => 'Optimisation des titres de page',
        'seo-sitemap.php' => 'Sitemap XML complet',
        'seo-images.php' => 'Optimisation SEO des images'
    );
    
    /**
     * Singleton - Récupérer l'instance unique
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
     * Initialisation du module SEO
     */
    private function init() {
        if (!$this->check_system_dependencies()) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme SEO] Dépendances système manquantes. SEO non chargé.');
            }
            return;
        }
        
        if (!$this->should_load_seo()) {
            return;
        }
        
        $this->load_seo_modules();
        $this->init_hooks();
        $this->init_admin();
        $this->log_initialization();
    }
    
    /**
     * Vérifier les dépendances système
     */
    private function check_system_dependencies() {
        if (!class_exists('Sisme_Game_Data_Formatter')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-page-creator/game-data-formatter.php';
            
            if (!class_exists('Sisme_Game_Data_Formatter')) {
                return false;
            }
        }
        
        if (!class_exists('Sisme_SEO_Game_Detector')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Déterminer si le SEO doit être chargé
     */
    private function should_load_seo() {
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }
        
        if (defined('DOING_CRON') && DOING_CRON) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Charger tous les modules SEO
     */
    private function load_seo_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $seo_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/seo/';
        
        foreach ($this->available_modules as $module_file => $module_description) {
            $this->load_seo_module($seo_dir, $module_file, $module_description);
        }
        
        self::$modules_loaded = true;
    }
    
    /**
     * Charger un module SEO spécifique
     */
    private function load_seo_module($seo_dir, $module_file, $module_description) {
        $module_path = $seo_dir . $module_file;
        
        if (!file_exists($module_path)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme SEO] Fichier introuvable : ' . $module_file);
            }
            return false;
        }
        
        require_once $module_path;
        
        $this->loaded_modules[$module_file] = $module_description;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme SEO] Module chargé : ' . $module_description . ' (' . $module_file . ')');
        }
        
        return true;
    }
    
    /**
     * Initialiser les hooks WordPress pour les modules chargés
     */
    private function init_hooks() {
        $module_classes = array(
            'seo-meta-tags.php' => 'Sisme_SEO_Meta_Tags',
            'seo-structured-data.php' => 'Sisme_SEO_Structured_Data',
            'seo-open-graph.php' => 'Sisme_SEO_Open_Graph',
            'seo-title-optimizer.php' => 'Sisme_SEO_Title_Optimizer',
            'seo-sitemap.php' => 'Sisme_SEO_Sitemap',
            'seo-images.php' => 'Sisme_SEO_Images'
        );
        
        foreach ($this->loaded_modules as $module_file => $description) {
            if (isset($module_classes[$module_file])) {
                $class_name = $module_classes[$module_file];
                if (class_exists($class_name)) {
                    new $class_name();
                }
            }
        }
    }
    
    /**
     * Initialiser la partie admin si nécessaire
     */
    private function init_admin() {
        if (!is_admin()) {
            return;
        }
        
        $admin_file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/components/admin-seo.php';
        if (file_exists($admin_file)) {
            require_once $admin_file;
            Sisme_SEO_Admin::init();
            
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
    }
    
    /**
     * Charger les assets admin SEO
     */
    public function enqueue_admin_assets($hook) {
        if ($hook !== 'sisme-games_page_sisme-games-seo') {
            return;
        }
        
        // Utiliser le système CSS admin unifié uniquement
        wp_enqueue_style(
            'sisme-admin-shared',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'admin/assets/CSS-admin-shared.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );
    }
    private function log_initialization() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $loaded_count = count($this->loaded_modules);
            $total_count = count($this->available_modules);
            
            error_log(sprintf(
                '[Sisme SEO] Système SEO initialisé - %d/%d modules chargés',
                $loaded_count,
                $total_count
            ));
        }
    }
    
    /**
     * API PUBLIQUE - Méthodes pour les modules SEO
     */
    
    /**
     * Détecter si on est sur une page de jeu
     * Utilise Sisme_SEO_Game_Detector
     * 
     * @param int|null $post_id ID du post (null = post actuel)
     * @return bool True si c'est une page de jeu
     */
    public static function is_game_page($post_id = null) {
        return Sisme_SEO_Game_Detector::is_game_page($post_id);
    }
    
    /**
     * Récupérer les données du jeu pour la page actuelle
     * Utilise Sisme_SEO_Game_Detector
     * 
     * @param int|null $post_id ID du post (null = post actuel)
     * @return array|false Données formatées ou false
     */
    public static function get_current_game_data($post_id = null) {
        return Sisme_SEO_Game_Detector::get_game_data($post_id);
    }
    
    /**
     * Récupérer le term_id du jeu
     * 
     * @param int|null $post_id ID du post (null = post actuel)
     * @return int|false Term ID ou false
     */
    public static function get_game_term_id($post_id = null) {
        if (!$post_id) {
            global $post;
            if (!$post || !is_single()) {
                return false;
            }
            $post_id = $post->ID;
        }
        
        return Sisme_SEO_Game_Detector::get_game_term_id($post_id);
    }
    
    /**
     * MÉTHODES DE MONITORING ET DEBUG
     */
    
    /**
     * Obtenir la liste des modules chargés
     */
    public static function get_loaded_modules() {
        return self::get_instance()->loaded_modules;
    }
    
    /**
     * Obtenir le statut de santé du module SEO
     */
    public static function get_health_status() {
        $instance = self::get_instance();
        $total_modules = count($instance->available_modules);
        $loaded_count = count($instance->loaded_modules);
        
        return array(
            'total_modules' => $total_modules,
            'loaded_count' => $loaded_count,
            'health_percentage' => $total_modules > 0 ? round(($loaded_count / $total_modules) * 100) : 0,
            'status' => $loaded_count === $total_modules ? 'healthy' : ($loaded_count > 0 ? 'partial' : 'critical'),
            'loaded_modules' => array_keys($instance->loaded_modules),
            'system_dependencies' => $instance->check_system_dependencies()
        );
    }
    
    /**
     * Nettoyer le cache SEO pour un post
     * 
     * @param int $post_id ID du post
     */
    public static function clear_cache($post_id) {
        Sisme_SEO_Game_Detector::clear_cache($post_id);
        do_action('sisme_seo_clear_cache', $post_id);
    }
    
    /**
     * Nettoyer tout le cache SEO
     */
    public static function clear_all_cache() {
        Sisme_SEO_Game_Detector::clear_all_cache();
        do_action('sisme_seo_clear_all_cache');
    }
    
    /**
     * Debug complet d'une page
     * 
     * @param int|null $post_id ID du post
     * @return array Informations de debug
     */
    public static function debug_page($post_id = null) {
        if (!$post_id) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        $instance = self::get_instance();
        $health = self::get_health_status();
        $detector_debug = Sisme_SEO_Game_Detector::debug_detection($post_id);
        $cache_stats = Sisme_SEO_Game_Detector::get_cache_stats();
        
        return array(
            'seo_health' => $health,
            'page_detection' => $detector_debug,
            'cache_stats' => $cache_stats,
            'current_post' => array(
                'ID' => $post_id,
                'post_type' => get_post_type($post_id),
                'is_single' => is_single(),
                'tags_count' => count(wp_get_post_tags($post_id))
            )
        );
    }
}