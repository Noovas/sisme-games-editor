<?php
/**
 * File: /sisme-games-editor/includes/utils/utils-loader.php
 * Système de chargement des utilitaires partagés
 * 
 * RESPONSABILITÉ:
 * - Chargement automatique de tous les modules utils
 * - Singleton pattern pour éviter les doublons
 * - Vérification des dépendances
 * - Logging et debug centralisé
 * - Point d'entrée unique pour tout le système utils
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Utils_Loader {
    
    private static $instance = null;
    private static $modules_loaded = false;
    private $loaded_modules = array();
    
    /**
     * Modules utils disponibles dans l'ordre de chargement
     */
    private $available_modules = array(
        'utils-validation.php'   => 'Validation et sanitisation',
        'utils-formatting.php'   => 'Formatage dates et textes',
        'utils-cache.php'        => 'Gestion cache WordPress',
        'utils-wp.php'           => 'Helpers WordPress',
        'utils-debug.php'        => 'Logging et debug',
        'utils-games.php'        => 'Données et métier jeux',
        'utils-filter.php'        => 'filtres et recherches de jeux',
        'utils-users.php'        => 'Données et métier utilisateurs'
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
     * Constructeur privé - Pattern singleton
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialisation du système utils
     */
    private function init() {
        $this->load_utils_modules();
        $this->log_initialization();
    }
    
    /**
     * Charger tous les modules utils disponibles
     */
    private function load_utils_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $utils_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/utils/';
        
        foreach ($this->available_modules as $module_file => $module_description) {
            $this->load_utils_module($utils_dir, $module_file, $module_description);
        }
        
        self::$modules_loaded = true;
    }
    
    /**
     * Charger un module utils spécifique
     * 
     * @param string $utils_dir Répertoire utils
     * @param string $module_file Nom du fichier module
     * @param string $module_description Description du module
     */
    private function load_utils_module($utils_dir, $module_file, $module_description) {
        $module_path = $utils_dir . $module_file;
        
        if (file_exists($module_path)) {
            require_once $module_path;
            $this->loaded_modules[$module_file] = $module_description;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme Utils] Module '{$module_description}' chargé : {$module_file}");
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme Utils] Module '{$module_description}' non trouvé : {$module_path}");
            }
        }
    }
    
    /**
     * Vérifier si un module utils est chargé
     * 
     * @param string $module_name Nom du module (avec ou sans extension)
     * @return bool Module chargé ou non
     */
    public function is_module_loaded($module_name) {
        // Normaliser le nom (ajouter .php si nécessaire)
        if (!str_ends_with($module_name, '.php')) {
            $module_name .= '.php';
        }
        
        return isset($this->loaded_modules[$module_name]);
    }
    
    /**
     * Obtenir la liste des modules utils chargés
     * 
     * @return array Liste des modules avec descriptions
     */
    public function get_loaded_modules() {
        return $this->loaded_modules;
    }
    
    /**
     * Obtenir le nombre de modules chargés
     * 
     * @return int Nombre de modules
     */
    public function get_loaded_modules_count() {
        return count($this->loaded_modules);
    }
    
    /**
     * Vérifier la santé du système utils
     * 
     * @return array Informations de diagnostic
     */
    public function get_system_health() {
        $total_modules = count($this->available_modules);
        $loaded_modules = count($this->loaded_modules);
        
        return array(
            'total_available' => $total_modules,
            'loaded_count' => $loaded_modules,
            'loading_success_rate' => $total_modules > 0 ? ($loaded_modules / $total_modules) * 100 : 0,
            'missing_modules' => array_diff(array_keys($this->available_modules), array_keys($this->loaded_modules)),
            'loaded_modules' => $this->loaded_modules,
            'system_ready' => $loaded_modules >= 3 // Au minimum validation, formatting, debug
        );
    }
    
    /**
     * Forcer le rechargement de tous les modules
     */
    public function reload_modules() {
        self::$modules_loaded = false;
        $this->loaded_modules = array();
        $this->load_utils_modules();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Utils] Modules rechargés - " . count($this->loaded_modules) . " modules actifs");
        }
    }
    
    /**
     * Obtenir les stats détaillées pour debug
     * 
     * @return array Statistiques complètes
     */
    public function get_debug_stats() {
        $health = $this->get_system_health();
        
        return array(
            'loader_initialized' => true,
            'modules_loaded_flag' => self::$modules_loaded,
            'health_check' => $health,
            'memory_usage' => memory_get_usage(true),
            'wp_debug_enabled' => defined('WP_DEBUG') && WP_DEBUG,
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version')
        );
    }
    
    /**
     * Logger l'initialisation du système
     */
    private function log_initialization() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $loaded_count = count($this->loaded_modules);
            $total_count = count($this->available_modules);
            error_log("[Sisme Utils] Système utils initialisé - {$loaded_count}/{$total_count} modules chargés");
        }
    }
    
    /**
     * Méthode publique pour les autres modules
     * Permet de vérifier facilement si le système utils est prêt
     * 
     * @return bool Système prêt à l'usage
     */
    public function is_ready() {
        $health = $this->get_system_health();
        return $health['system_ready'];
    }
}

// Auto-initialisation du système utils
// Appelé automatiquement lors de l'inclusion de ce fichier
add_action('plugins_loaded', function() {
    Sisme_Utils_Loader::get_instance();
}, 5); // Priorité 5 pour être chargé tôt