<?php
/**
 * File: /sisme-games-editor/includes/email-manager/email-manager-loader.php
 * Loader du module gestionnaire d'emails
 * 
 * RESPONSABILITÉ:
 * - Charger les composants du module email
 * - Initialiser le gestionnaire d'emails
 * - Préparer l'infrastructure pour templates futurs
 * - Point d'entrée unique du module
 * 
 * DÉPENDANCES:
 * - WordPress wp_mail()
 * - WordPress User API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Email_Manager_Loader {
    
    private static $instance = null;
    private static $modules_loaded = false;
    
    /**
     * Singleton
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
     * Initialisation du module email
     */
    private function init() {
        $this->load_email_modules();
    }
    
    /**
     * Charger tous les modules du gestionnaire d'emails
     */
    private function load_email_modules() {
        if (self::$modules_loaded) {
            return;
        }
        
        $email_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/email-manager/';
        
        $required_modules = [
            'email-manager.php',
            'email-templates.php'
        ];
        
        foreach ($required_modules as $module) {
            $file_path = $email_dir . $module;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
        
        self::$modules_loaded = true;
    }
    
    /**
     * Vérifier si le module est prêt
     */
    public function is_module_ready() {
        return class_exists('Sisme_Email_Manager') && self::$modules_loaded;
    }
    
    /**
     * Obtenir la version du module
     */
    public function get_version() {
        return SISME_GAMES_EDITOR_VERSION;
    }
}