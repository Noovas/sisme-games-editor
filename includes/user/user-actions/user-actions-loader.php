<?php
/**
* File: user-actions-loader.php
* Module: Chargeur pour le module User Actions
* 
* Singleton responsable du chargement du module User Actions
* et de l'initialisation des sous-modules.
*/

if (!defined('ABSPATH')) {
   exit;
}

class Sisme_User_Actions_Loader {
   
   private static $instance = null;
   private $modules_loaded = false;
   private $assets_loaded = false;
   
   /**
    * Obtenir l'instance unique du singleton
    */
   public static function get_instance() {
       if (null === self::$instance) {
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
    * Initialisation du module
    */
   private function init() {
       // Charger les fichiers du module
       $this->load_module_files();
       
       // Enregistrer les hooks WordPress
       $this->register_hooks();
       
       if (defined('WP_DEBUG') && WP_DEBUG) {
           error_log('[Sisme User Actions] Module initialisé avec succès');
       }
   }
   
   /**
    * Charger les fichiers du module
    */
   private function load_module_files() {
       if ($this->modules_loaded) {
           return;
       }
       
       $actions_dir = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-actions/';
       
       // Modules requis dans l'ordre
       $required_modules = [
           'user-actions-data-manager.php',  // Gestion données en premier
           'user-actions-api.php',           // API publique
           'user-actions-ajax.php'           // Handlers AJAX
       ];
       
       foreach ($required_modules as $module) {
           $file_path = $actions_dir . $module;
           
           if (file_exists($file_path)) {
               require_once $file_path;
               
               if (defined('WP_DEBUG') && WP_DEBUG) {
                   error_log("[Sisme User Actions] Module chargé : $module");
               }
           } else {
               if (defined('WP_DEBUG') && WP_DEBUG) {
                   error_log("[Sisme User Actions] ERREUR - Module manquant : $file_path");
               }
           }
       }
       
       $this->modules_loaded = true;
   }
   
   /**
    * Enregistrer les hooks WordPress
    */
   private function register_hooks() {
       // Assets frontend
       add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
       
       // Initialiser les handlers AJAX si disponibles
       if (class_exists('Sisme_User_Actions_Ajax')) {
           Sisme_User_Actions_Ajax::init();
       }
   }
   
   /**
    * Charger les assets frontend
    */
   public function enqueue_frontend_assets() {
       if ($this->assets_loaded || is_admin()) {
           return;
       }
       
       // Vérifier si on doit charger les assets (utilisateur connecté ou page spécifique)
       if (!$this->should_load_assets()) {
           return;
       }
       
       // Base CSS communes à toutes les actions
       wp_enqueue_style(
           'sisme-user-actions',
           SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-actions/assets/user-actions.css',
           array('sisme-frontend-tokens'),
           SISME_GAMES_EDITOR_VERSION
       );
       
       // JavaScript commun
       wp_enqueue_script(
           'sisme-user-actions',
           SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-actions/assets/user-actions.js',
           array('jquery'),
           SISME_GAMES_EDITOR_VERSION,
           true
       );
       
       // Configuration JS pour AJAX
       wp_localize_script('sisme-user-actions', 'sismeUserActions', array(
           'ajax_url' => admin_url('admin-ajax.php'),
           'security' => wp_create_nonce('sisme_user_actions_nonce'),
           'is_logged_in' => is_user_logged_in(),
           'login_url' => wp_login_url(get_permalink()),
           'i18n' => array(
               'error' => __('Une erreur est survenue', 'sisme-games-editor'),
               'login_required' => __('Vous devez être connecté', 'sisme-games-editor')
           )
       ));
       
       // Chargement des CSS spécifiques aux actions
       $this->load_action_specific_assets();
       
       $this->assets_loaded = true;
   }
   
   /**
    * Charger les assets spécifiques aux différentes actions
    */
   private function load_action_specific_assets() {
       // Favoris - Toujours chargé
       wp_enqueue_style(
           'sisme-user-actions-favorites',
           SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-actions/assets/user-actions-favorites.css',
           array('sisme-user-actions'),
           SISME_GAMES_EDITOR_VERSION
       );
       
       wp_enqueue_script(
           'sisme-user-actions-favorites',
           SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-actions/assets/user-actions-favorites.js',
           array('sisme-user-actions'),
           SISME_GAMES_EDITOR_VERSION,
           true
       );
   }
   
   /**
    * Vérifier si on doit charger les assets
    */
   private function should_load_assets() {
       // Cas 1: Utilisateur connecté
       if (is_user_logged_in()) {
           return true;
       }
       
       // Cas 2: Page qui contient des fiches de jeu
       if (is_singular('post') || is_archive() || is_tax()) {
           return true;
       }
       
       // Cas 3: Shortcode présent dans le contenu
       global $post;
       if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'sisme_game_card')) {
           return true;
       }
       
       return false;
   }
}

// Initialisation lors du chargement du fichier
Sisme_User_Actions_Loader::get_instance();