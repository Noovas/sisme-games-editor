<?php
/**
 * File: /sisme-games-editor/includes/search/search-loader.php
 * Module de recherche gaming - Singleton et chargement des assets
 * 
 * Responsabilités :
 * - Singleton pattern pour initialisation unique
 * - Chargement conditionnel des assets CSS/JS
 * - Enregistrement des hooks WordPress
 * - Chargement des autres modules du système
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Search_Loader {
    
    private static $instance = null;
    private $assets_loaded = false;
    
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
     * Constructeur privé (singleton)
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialisation du module
     */
    private function init() {
        // Charger les autres modules du système
        $this->load_modules();
        
        // Enregistrer les hooks WordPress
        $this->register_hooks();
        
        // Log d'initialisation en mode debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search Module: Initialized successfully');
        }
    }
    
    /**
     * Charger tous les modules du système de recherche
     */
    private function load_modules() {
        $modules = array(
            'search-api.php',           // API principale et shortcodes
            'search-filters.php',        // Logique des filtres
            'search-suggestions.php',    // Suggestions et historique
            'search-ajax.php'           // Handlers AJAX
        );
        
        foreach ($modules as $module) {
            $file_path = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/search/' . $module;
            
            if (file_exists($file_path)) {
                require_once $file_path;
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Sisme Search: Loaded module {$module}");
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Sisme Search: WARNING - Module {$module} not found at {$file_path}");
                }
            }
        }
    }
    
    /**
     * Enregistrer les hooks WordPress
     */
    private function register_hooks() {
        // Chargement des assets frontend
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Chargement des assets admin (si nécessaire dans le futur)
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Override du template de recherche WordPress
        add_filter('template_include', array($this, 'override_search_template'), 99);
        
        // Modification de la requête de recherche principale
        add_action('pre_get_posts', array($this, 'modify_main_search_query'));
        
        // Hook d'initialisation tardive pour autres plugins
        add_action('wp_loaded', array($this, 'late_init'));
    }
    
    /**
     * Chargement des assets frontend
     */
    public function enqueue_frontend_assets() {
        // Ne pas charger dans l'admin
        if (is_admin()) {
            return;
        }
        
        // Vérifier si on doit charger les assets
        if (!$this->should_load_assets()) {
            return;
        }
        
        // Charger les CSS
        $this->enqueue_styles();
        
        // Charger les JavaScript
        $this->enqueue_scripts();
        
        // Marquer comme chargé
        $this->assets_loaded = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search: Frontend assets loaded');
        }
    }
    
    /**
     * Déterminer si on doit charger les assets
     */
    private function should_load_assets() {
        global $post;
        
        // Toujours charger sur les pages de recherche
        if (is_search()) {
            return true;
        }
        
        // Charger si la page contient le shortcode de recherche
        if (is_object($post)) {
            $has_search_shortcode = (
                has_shortcode($post->post_content, 'sisme_search') ||
                has_shortcode($post->post_content, 'sisme_search_bar') ||
                has_shortcode($post->post_content, 'sisme_search_filters') ||
                has_shortcode($post->post_content, 'sisme_search_results')
            );
            
            if ($has_search_shortcode) {
                return true;
            }
        }
        
        // Ne pas charger par défaut
        return false;
    }
    
    /**
     * Charger les styles CSS
     */
    private function enqueue_styles() {
        // S'assurer que les tokens frontend sont chargés (dépendance)
        if (!wp_style_is('sisme-frontend-tokens-global', 'enqueued')) {
            wp_enqueue_style(
                'sisme-frontend-tokens-global',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
                array(),
                SISME_GAMES_EDITOR_VERSION
            );
        }
        
        // CSS principal du module de recherche
        wp_enqueue_style(
            'sisme-search',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/search/assets/search.css',
            array('sisme-frontend-tokens-global'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // S'assurer que les CSS Cards sont chargés (pour l'affichage des résultats)
        if (!wp_style_is('sisme-cards', 'enqueued')) {
            wp_enqueue_style(
                'sisme-cards',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/cards/assets/cards.css',
                array('sisme-frontend-tokens-global'),
                SISME_GAMES_EDITOR_VERSION
            );
            
            wp_enqueue_style(
                'sisme-cards-grid',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/cards/assets/cards-grid.css',
                array('sisme-cards'),
                SISME_GAMES_EDITOR_VERSION
            );
        }
    }
    
    /**
     * Charger les scripts JavaScript
     */
    private function enqueue_scripts() {
        // JavaScript principal du module
        wp_enqueue_script(
            'sisme-search-js',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/search/assets/search.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        // Variables pour JavaScript
        wp_localize_script('sisme-search-js', 'sismeSearch', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_search_nonce'),
            'restUrl' => rest_url('sisme/v1/'),
            'restNonce' => wp_create_nonce('wp_rest'),
            
            // Textes traduisibles
            'i18n' => array(
                'loadingText' => __('Recherche en cours...', 'sisme-games-editor'),
                'errorText' => __('Aucun résultat', 'sisme-games-editor'),
                'noResultsText' => __('Aucun jeu trouvé', 'sisme-games-editor'),
                'searchPlaceholder' => __('Rechercher un jeu...', 'sisme-games-editor'),
                'loadMoreText' => __('📚 Charger plus de jeux', 'sisme-games-editor'),
                'applyFiltersText' => __('✅ Appliquer', 'sisme-games-editor'),
                'resetFiltersText' => __('🔄 Réinitialiser', 'sisme-games-editor'),
                'clearHistoryText' => __('Vider l\'historique', 'sisme-games-editor'),
                'recentSearchesText' => __('🕒 Recherches récentes', 'sisme-games-editor')
            ),
            
            // Configuration
            'config' => array(
                'debounceDelay' => 500,        // Délai de frappe en ms
                'resultsPerPage' => 12,        // Résultats par page
                'maxHistoryItems' => 5,        // Nb max d'historique
                'cacheTimeout' => 300000,      // Cache timeout en ms (5min)
                'animationDuration' => 300     // Durée animations en ms
            ),
            
            // Debug mode
            'debug' => defined('WP_DEBUG') && WP_DEBUG
        ));
        
        // S'assurer que le JS tooltip est chargé (pour les interactions)
        if (!wp_script_is('sisme-frontend-tooltip', 'enqueued')) {
            wp_enqueue_script(
                'sisme-frontend-tooltip',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/js/frontend-tooltip.js',
                array(),
                SISME_GAMES_EDITOR_VERSION,
                true
            );
        }
    }
    
    /**
     * Chargement des assets admin (pour le futur)
     */
    public function enqueue_admin_assets($hook) {
        // Pour l'instant, pas d'assets admin spécifiques
        // Mais on prépare la structure pour des pages d'admin futures
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Sisme Search: Admin assets hook called on {$hook}");
        }
    }
    
    /**
     * Override du template de recherche WordPress
     */
    public function override_search_template($template) {
        // Seulement sur les pages de recherche
        if (!is_search()) {
            return $template;
        }
        
        // Chemin vers notre template personnalisé
        $custom_template = SISME_GAMES_EDITOR_PLUGIN_DIR . 'templates/search.php';
        
        // Utiliser notre template s'il existe
        if (file_exists($custom_template)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Sisme Search: Using custom search template');
            }
            return $custom_template;
        }
        
        // Sinon, utiliser le template par défaut
        return $template;
    }
    
    /**
     * Modifier la requête de recherche principale de WordPress
     */
    public function modify_main_search_query($query) {
        // Seulement pour la requête principale, pas dans l'admin
        if (is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Seulement sur les pages de recherche
        if (!$query->is_search()) {
            return;
        }
        
        // Modifier les paramètres de la requête pour inclure plus de contenu
        $query->set('post_type', array('post'));
        $query->set('posts_per_page', -1); // On gérera la pagination via AJAX
        
        // Améliorer la recherche en incluant les métadonnées
        $meta_query = array(
            'relation' => 'OR',
            array(
                'key' => Sisme_Utils_Games::META_DESCRIPTION,
                'value' => $query->get('s'),
                'compare' => 'LIKE'
            )
        );
        
        $query->set('meta_query', $meta_query);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search: Modified main search query for: ' . $query->get('s'));
        }
    }
    
    /**
     * Initialisation tardive (après chargement des autres plugins)
     */
    public function late_init() {
        // Vérifier que les dépendances sont présentes
        $this->check_dependencies();
        
        // Initialiser les autres modules s'ils ont une méthode init
        $this->init_other_modules();
    }
    
    /**
     * Vérifier les dépendances
     */
    private function check_dependencies() {
        $dependencies = array(
            'Sisme_Cards_API' => 'Module Cards API requis'
        );
        
        foreach ($dependencies as $class => $message) {
            if (!class_exists($class)) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("Sisme Search: WARNING - {$message}. Classe {$class} non trouvée.");
                }
                
                // Ajouter une notice d'admin si nécessaire
                add_action('admin_notices', function() use ($message) {
                    echo '<div class="notice notice-warning"><p><strong>Sisme Search:</strong> ' . esc_html($message) . '</p></div>';
                });
            }
        }
    }
    
    /**
     * Initialiser les autres modules s'ils ont une méthode d'init
     */
    private function init_other_modules() {
        // Les autres modules s'initialiseront via leurs propres hooks
        // On peut ajouter des initialisations spécifiques ici si besoin
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Sisme Search: Late initialization completed');
        }
    }
    
    /**
     * Méthodes utilitaires publiques
     */
    
    /**
     * Vérifier si les assets sont chargés
     */
    public function are_assets_loaded() {
        return $this->assets_loaded;
    }
    
    /**
     * Forcer le chargement des assets (utile pour les shortcodes)
     */
    public function force_load_assets() {
        if (!$this->assets_loaded) {
            $this->enqueue_styles();
            $this->enqueue_scripts();
            $this->assets_loaded = true;
        }
    }
    
    /**
     * Obtenir la version du module
     */
    public function get_version() {
        return SISME_GAMES_EDITOR_VERSION;
    }
    
    /**
     * Vérifier si on est en mode debug
     */
    public function is_debug_mode() {
        return defined('WP_DEBUG') && WP_DEBUG;
    }
}

// Initialisation automatique du module
add_action('plugins_loaded', function() {
    Sisme_Search_Loader::get_instance();
}, 10);