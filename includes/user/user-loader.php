<?php
/**
 * File: /sisme-games-editor/includes/user/user-loader.php
 * Master Loader du système utilisateur - Sisme Games Editor
 * 
 * RESPONSABILITÉ:
 * - Point d'entrée principal appelé par le système de modules
 * - Charger tous les sous-modules utilisateur
 * - Initialiser les hooks généraux
 * - Coordonner les différents aspects utilisateur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Loader {
    
    private static $instance = null;
    private static $modules_loaded = false;
    
    /**
     * Singleton - Instance unique
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
     * Initialisation du système utilisateur
     */
    private function init() {
        $this->load_modules();
        $this->register_hooks();
        
        // Log d'initialisation en mode debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User] Master loader initialisé avec succès');
        }
    }
    
    /**
     * Charger tous les sous-modules utilisateur
     */
    private function load_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        // Modules disponibles (par ordre de priorité)
        $available_modules = [
            'user-auth'     => 'Authentification',
            'user-preferences'  => 'Preferences utilisateur', 
            'user-actions'  => 'Actions utilisateur',
            'user-dashboard' => 'Dashboard utilisateur',
            'user-library'  => 'Ludothèque personnelle'
        ];
        
        foreach ($available_modules as $module_name => $module_description) {
            $this->load_user_module($module_name, $module_description);
        }
        
        self::$modules_loaded = true;
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User] Tous les modules utilisateur chargés');
        }
    }
    
    /**
     * Charger un module utilisateur spécifique
     */
    private function load_user_module($module_name, $description) {
        $loader_file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/' . $module_name . '/' . $module_name . '-loader.php';
        
        if (file_exists($loader_file)) {
            require_once $loader_file;
            
            // Construire le nom de classe selon vos conventions
            $class_name = 'Sisme_User_' . ucfirst(str_replace('-', '_', str_replace('user-', '', $module_name))) . '_Loader';
            
            // Initialiser le module s'il a la bonne structure
            if (class_exists($class_name) && method_exists($class_name, 'get_instance')) {
                $class_name::get_instance();
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User] Module '$description' initialisé : $class_name");
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User] ERREUR - Classe '$class_name' introuvable pour le module '$module_name'");
                }
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User] Module '$module_name' non trouvé : $loader_file");
            }
        }
    }
    
    /**
     * Enregistrer les hooks généraux du système utilisateur
     */
    private function register_hooks() {
        // Hook pour initialisation tardive si nécessaire
        add_action('wp_loaded', [$this, 'late_init']);
        
        // Hooks globaux utilisateur (si nécessaires)
        add_action('user_register', [$this, 'on_user_register']);
        add_action('wp_login', [$this, 'on_user_login'], 10, 2);
    }
    
    /**
     * Initialisation tardive
     */
    public function late_init() {
        // Vérifications et initialisations tardives si nécessaires
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User] Initialisation tardive terminée');
        }
    }
    
    /**
     * Hook appelé lors de l'inscription d'un utilisateur
     */
    public function on_user_register($user_id) {
        // Actions globales lors d'une inscription
        // Les modules spécifiques géreront leurs propres actions
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User] Nouvel utilisateur inscrit : $user_id");
        }
    }
    
    /**
     * Hook appelé lors de la connexion d'un utilisateur
     */
    public function on_user_login($user_login, $user) {
        // Actions globales lors d'une connexion
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User] Utilisateur connecté : $user_login (ID: {$user->ID})");
        }
    }
    
    /**
     * Méthodes utilitaires publiques
     */
    
    /**
     * Vérifier si un module utilisateur est chargé
     */
    public function is_module_loaded($module_name) {
        $class_name = 'Sisme_User_' . ucfirst(str_replace('-', '_', str_replace('user-', '', $module_name))) . '_Loader';
        return class_exists($class_name);
    }
    
    /**
     * Obtenir la liste des modules utilisateur actifs
     */
    public function get_active_modules() {
        $modules = [];
        $available_modules = ['user-auth', 'user-profile', 'user-library'];
        
        foreach ($available_modules as $module_name) {
            if ($this->is_module_loaded($module_name)) {
                $modules[] = $module_name;
            }
        }
        
        return $modules;
    }
}

// Le système principal appellera automatiquement Sisme_User_Loader::get_instance()