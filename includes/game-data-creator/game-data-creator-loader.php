<?php
/**
 * File: /sisme-games-editor/includes/game-data-creator/game-data-creator-loader.php
 * Loader pour le module Game Creator - Suit l'architecture modulaire du projet
 * 
 * RESPONSABILITÉ:
 * - Chargement des composants du module game-data-creator
 * - Initialisation du singleton
 * - Vérification des permissions admin
 * - Logging debug des modules chargés
 * 
 * DÉPENDANCES:
 * - WordPress term/meta API
 * - Système de permissions WordPress
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_data_creator_Loader {

    private static $instance = null;
    private static $modules_loaded = false;
    private $loaded_modules = array();
    
    /**
     * Modules game-data-creator disponibles
     */
    private $available_modules = array(
        'game-data-creator-constants.php' => 'Constantes centralisées',
        'game-data-creator-validator.php' => 'Validation et sanitisation',
        'game-data-creator-data-manager.php' => 'CRUD WordPress',
        'game-data-creator.php' => 'API publique principale'
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
     * Initialisation du module game-data-creator
     */
    private function init() {
        $this->load_game_creator_modules();
        $this->register_hooks();
        $this->log_initialization();
    }
    
    /**
     * Charger tous les modules game-data-creator
     */
    private function load_game_creator_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $game_creator_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/game-data-creator/';
        
        foreach ($this->available_modules as $module_file => $module_description) {
            $this->load_module($game_creator_dir, $module_file, $module_description);
        }
        
        self::$modules_loaded = true;
    }
    
    /**
     * Charger un module game-data-creator spécifique
     */
    private function load_module($game_creator_dir, $module_file, $module_description) {
        $module_path = $game_creator_dir . $module_file;
        
        if (file_exists($module_path)) {
            require_once $module_path;
            $this->loaded_modules[$module_file] = $module_description;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme Game Creator] Module '{$module_description}' chargé : {$module_file}");
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme Game Creator] Module manquant : {$module_file}");
            }
        }
    }
    
    /**
     * Enregistrer les hooks WordPress
     */
    private function register_hooks() {
        // Hook pour nettoyage lors de suppression de terme
        add_action('delete_term', array($this, 'on_term_deleted'), 10, 4);
    }
    
    /**
     * Hook appelé lors de suppression d'un terme
     */
    public function on_term_deleted($term_id, $tt_id, $taxonomy, $deleted_term) {
        if ($taxonomy === 'post_tag') {
            do_action('sisme_game_creator_term_deleted', $term_id, $deleted_term);
        }
    }
    
    /**
     * Vérifier si un utilisateur peut créer des jeux
     */
    public static function can_create_games($user_id = null) {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }
        return user_can($user_id, 'manage_options');
    }
    
    /**
     * Obtenir les statistiques du module
     */
    public function get_module_stats() {
        return array(
            'modules_loaded' => count($this->loaded_modules),
            'total_modules' => count($this->available_modules),
            'loaded_modules' => $this->loaded_modules,
            'system_ready' => self::$modules_loaded
        );
    }
    
    /**
     * Logger l'initialisation
     */
    private function log_initialization() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $loaded_count = count($this->loaded_modules);
            $total_count = count($this->available_modules);
            error_log("[Sisme Game Creator] Module initialisé - {$loaded_count}/{$total_count} composants chargés");
        }
    }
    
    /**
     * Vérifier si le module est prêt
     */
    public function is_ready() {
        return self::$modules_loaded && count($this->loaded_modules) >= 3;
    }
}