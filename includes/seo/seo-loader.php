<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-loader.php
 * Loader pour le module SEO - Suit l'architecture modulaire du projet
 * 
 * RESPONSABILITÉ:
 * - Chargement conditionnel des composants SEO
 * - Vérification des dépendances
 * - Initialisation des hooks WordPress
 * - Logging debug des modules chargés
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Seo_Loader {

    private static $instance = null;
    
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
        self::init();
    }
    
    /**
     * Modules SEO disponibles avec leurs dépendances
     */
    private static $seo_modules = array(
        'meta-tags' => array(
            'file' => 'seo-meta-tags.php',
            'class' => 'Sisme_SEO_Meta_Tags',
            'description' => 'Meta tags et descriptions automatiques',
            'dependencies' => array('Sisme_Utils_Games'),
            'hooks' => array('wp_head')
        ),
        'structured-data' => array(
            'file' => 'seo-structured-data.php',
            'class' => 'Sisme_SEO_Structured_Data',
            'description' => 'Données structurées Schema.org',
            'dependencies' => array('Sisme_Utils_Games'),
            'hooks' => array('wp_head')
        ),
        'open-graph' => array(
            'file' => 'seo-open-graph.php',
            'class' => 'Sisme_SEO_Open_Graph',
            'description' => 'Balises Open Graph et Twitter Cards',
            'dependencies' => array('Sisme_Utils_Games'),
            'hooks' => array('wp_head')
        ),
        'title-optimizer' => array(
            'file' => 'seo-title-optimizer.php',
            'class' => 'Sisme_SEO_Title_Optimizer',
            'description' => 'Optimisation des titres de page',
            'dependencies' => array('Sisme_Utils_Games'),
            'hooks' => array('wp_title', 'document_title_parts')
        ),
        'sitemap' => array(
            'file' => 'seo-sitemap.php',
            'class' => 'Sisme_SEO_Sitemap',
            'description' => 'Sitemap XML complet',
            'dependencies' => array(),
            'hooks' => array('init', 'template_redirect')
        ),
        'images-seo' => array(
            'file' => 'seo-images.php',
            'class' => 'Sisme_SEO_Images',
            'description' => 'Optimisation SEO des images',
            'dependencies' => array(),
            'hooks' => array()
        )
    );
    
    private static $loaded_modules = array();
    private static $failed_modules = array();
    
    /**
     * Initialiser le chargement des modules SEO
     */
    public static function init() {
        // Vérifier si on est dans un contexte où le SEO est nécessaire
        if (!self::should_load_seo()) {
            return false;
        }
        
        // Charger les modules SEO
        self::load_modules();
        
        // Initialiser les hooks si des modules sont chargés
        if (!empty(self::$loaded_modules)) {
            self::init_hooks();
        }
        
        // Debug logging si activé
        if (defined('WP_DEBUG') && WP_DEBUG) {
            self::log_loading_summary();
        }
        
        return count(self::$loaded_modules);
    }
    
    /**
     * Déterminer si le SEO doit être chargé
     */
    private static function should_load_seo() {
        // Ne pas charger en admin (sauf si nécessaire)
        if (is_admin() && !wp_doing_ajax()) {
            return false;
        }
        
        // Ne pas charger pour les API REST
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }
        
        // Ne pas charger pour les crons
        if (defined('DOING_CRON') && DOING_CRON) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Charger tous les modules SEO
     */
    private static function load_modules() {
        foreach (self::$seo_modules as $module_key => $module_config) {
            self::load_single_module($module_key, $module_config);
        }
    }
    
    /**
     * Charger un module SEO individuel
     */
    private static function load_single_module($module_key, $module_config) {
        // Vérifier les dépendances
        if (!self::check_dependencies($module_config['dependencies'])) {
            self::$failed_modules[$module_key] = 'Dépendances manquantes: ' . implode(', ', $module_config['dependencies']);
            return false;
        }
        
        // Chemin du fichier
        $module_file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/seo/' . $module_config['file'];
        
        // Vérifier que le fichier existe
        if (!file_exists($module_file)) {
            self::$failed_modules[$module_key] = 'Fichier introuvable: ' . $module_config['file'];
            return false;
        }
        
        // Inclure le fichier
        require_once $module_file;
        
        // Vérifier que la classe existe
        if (!class_exists($module_config['class'])) {
            self::$failed_modules[$module_key] = 'Classe introuvable: ' . $module_config['class'];
            return false;
        }
        
        // Marquer comme chargé
        self::$loaded_modules[$module_key] = $module_config;
        
        return true;
    }
    
    /**
     * Vérifier les dépendances d'un module
     */
    private static function check_dependencies($dependencies) {
        foreach ($dependencies as $dependency) {
            if (!class_exists($dependency)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Initialiser les hooks WordPress pour les modules chargés
     */
    private static function init_hooks() {
        foreach (self::$loaded_modules as $module_key => $module_config) {
            // Initialiser la classe du module
            if (class_exists($module_config['class'])) {
                new $module_config['class']();
            }
        }
    }
    
    /**
     * Obtenir la liste des modules chargés
     */
    public static function get_loaded_modules() {
        return self::$loaded_modules;
    }
    
    /**
     * Obtenir la liste des modules échoués
     */
    public static function get_failed_modules() {
        return self::$failed_modules;
    }
    
    /**
     * Vérifier si un module spécifique est chargé
     */
    public static function is_module_loaded($module_key) {
        return isset(self::$loaded_modules[$module_key]);
    }
    
    /**
     * Obtenir le statut de santé du module SEO
     */
    public static function get_health_status() {
        $total_modules = count(self::$seo_modules);
        $loaded_count = count(self::$loaded_modules);
        $failed_count = count(self::$failed_modules);
        
        return array(
            'total_modules' => $total_modules,
            'loaded_count' => $loaded_count,
            'failed_count' => $failed_count,
            'health_percentage' => $total_modules > 0 ? round(($loaded_count / $total_modules) * 100) : 0,
            'status' => $failed_count === 0 ? 'healthy' : ($loaded_count > 0 ? 'partial' : 'critical'),
            'missing_modules' => array_keys(self::$failed_modules)
        );
    }
    
    /**
     * Logging debug du chargement
     */
    private static function log_loading_summary() {
        $health = self::get_health_status();
        
        error_log(sprintf(
            '[Sisme SEO] Modules chargés : %d/%d (%d%%) - Status: %s',
            $health['loaded_count'],
            $health['total_modules'],
            $health['health_percentage'],
            $health['status']
        ));
        
        // Log des modules chargés
        foreach (self::$loaded_modules as $module_key => $module_config) {
            error_log("[Sisme SEO] ✓ Module '{$module_config['description']}' chargé : {$module_config['file']}");
        }
        
        // Log des échecs
        foreach (self::$failed_modules as $module_key => $error) {
            error_log("[Sisme SEO] ✗ Module '{$module_key}' échoué : {$error}");
        }
    }
    
    /**
     * Détecter si on est sur une page de jeu (utilitaire partagé)
     */
    public static function is_game_page($post_id = null) {
        if (!$post_id) {
            global $post;
            if (!$post || !is_single()) {
                return false;
            }
            $post_id = $post->ID;
        }
        
        $game_sections = get_post_meta($post_id, '_sisme_game_sections', true);
        return !empty($game_sections);
    }
    
    /**
     * Récupérer les données du jeu pour la page actuelle (utilitaire partagé)
     */
    public static function get_current_game_data($post_id = null) {
        if (!self::is_game_page($post_id)) {
            return false;
        }
        
        if (!$post_id) {
            global $post;
            $post_id = $post->ID;
        }
        
        $game_tags = wp_get_post_tags($post_id);
        if (empty($game_tags)) {
            return false;
        }
        
        $tag_id = $game_tags[0]->term_id;
        
        // Vérifier que Sisme_Utils_Games est disponible
        if (!class_exists('Sisme_Utils_Games')) {
            return false;
        }
        
        return Sisme_Utils_Games::get_game_data($tag_id);
    }
}