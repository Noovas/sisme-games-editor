<?php
/**
 * File: /sisme-games-editor/includes/vedettes/vedettes-loader.php
 * Chargeur principal des modules vedettes
 * 
 * RESPONSABILITÉ:
 * - Inclure tous les modules vedettes
 * - Initialiser les hooks WordPress
 * - Enregistrer les shortcodes
 */
if (!defined('ABSPATH')) {
    exit;
}

// Inclure tous les modules vedettes
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/vedettes/vedettes-data-manager.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/vedettes/vedettes-migration.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/vedettes/vedettes-api.php';

class Sisme_Vedettes_Loader {
    
    /**
     * Instance unique du loader
     */
    private static $instance = null;
    
    /**
     * Constructeur privé (Singleton)
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Obtenir l'instance unique du loader
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 🎣 Initialiser les hooks WordPress
     */
    private function init_hooks() {
        // Enregistrer le shortcode
        add_shortcode('sisme_vedettes_carousel', array('Sisme_Vedettes_API', 'vedettes_carousel_shortcode'));
        
        // Hooks pour les mises à jour automatiques
        add_action('updated_term_meta', array($this, 'clear_cache_on_update'), 10, 4);
        add_action('added_term_meta', array($this, 'auto_initialize_vedettes'), 10, 4);
        add_action('updated_term_meta', array($this, 'auto_initialize_vedettes'), 10, 4);
        
        // Log pour debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Vedettes] Loader initialisé avec succès');
        }
    }
    
    /**
     * Vider le cache automatiquement quand une vedette est modifiée
     * 
     * @param int $meta_id ID du meta
     * @param int $object_id ID de l'objet (term_id)
     * @param string $meta_key Clé meta
     * @param mixed $meta_value Valeur meta
     */
    public function clear_cache_on_update($meta_id, $object_id, $meta_key, $meta_value) {
        // Vérifier si c'est une méta vedette
        $vedette_meta_keys = array_values(Sisme_Vedettes_Data_Manager::META_KEYS);
        
        if (in_array($meta_key, $vedette_meta_keys)) {
            Sisme_Vedettes_API::clear_cache();
        }
    }
    
    /**
     * ✅ Hook automatique pour initialiser les données vedettes
     * Déclenché chaque fois qu'on ajoute/met à jour game_description
     * 
     * @param int $meta_id ID du meta
     * @param int $term_id ID du terme
     * @param string $meta_key Clé meta
     * @param mixed $meta_value Valeur meta
     */
    public function auto_initialize_vedettes($meta_id, $term_id, $meta_key, $meta_value) {
        // Déclencher uniquement pour game_description
        if ($meta_key === 'game_description') {
            error_log("Sisme Hook: Détection game_description pour terme $term_id");
            
            // Forcer l'initialisation (même si déjà initialisé)
            Sisme_Vedettes_Data_Manager::force_initialize_game($term_id);
        }
    }

    /**
     * 🎠 Shortcode pour carrousel d'images (legacy - à supprimer plus tard)
     */
    public function carousel_shortcode($atts, $content = '') {
        $atts = shortcode_atts(array(
            'images' => '', // IDs séparés par virgules
            'height' => '600px',
            'autoplay' => 'false',
            'show_arrows' => 'true',
            'show_dots' => 'true'
        ), $atts);
        
        if (empty($atts['images'])) {
            return '';
        }
        
        $image_ids = array_map('intval', explode(',', $atts['images']));
        $image_ids = array_filter($image_ids);
        
        if (empty($image_ids)) {
            return '';
        }
        
        if (!class_exists('Sisme_Carousel_Module')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/frontend/carousel-module.php';
        }
        
        $options = array(
            'height' => $atts['height'],
            'autoplay' => filter_var($atts['autoplay'], FILTER_VALIDATE_BOOLEAN),
            'show_arrows' => filter_var($atts['show_arrows'], FILTER_VALIDATE_BOOLEAN),
            'show_dots' => filter_var($atts['show_dots'], FILTER_VALIDATE_BOOLEAN),
            'item_type' => 'image'
        );
        
        return Sisme_Carousel_Module::quick_render($image_ids, $options);
    }
}
