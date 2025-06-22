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
     * Initialiser le système vedettes
     */
    public static function init() {
        // Enregistrer le shortcode
        add_shortcode('sisme_vedettes', array('Sisme_Vedettes_API', 'vedettes_shortcode'));
        
        // Hook pour vider le cache quand une vedette est modifiée
        add_action('updated_term_meta', array(self::class, 'clear_cache_on_update'), 10, 4);
        
        // ✅ NOUVEAUX HOOKS pour l'auto-initialisation
        add_action('added_term_meta', array(self::class, 'auto_initialize_vedettes'), 10, 4);
        add_action('updated_term_meta', array(self::class, 'auto_initialize_vedettes'), 10, 4);
        
        error_log("Sisme Vedettes: Système initialisé avec auto-initialisation");
    }
    
    /**
     * Vider le cache automatiquement quand une vedette est modifiée
     * 
     * @param int $meta_id ID du meta
     * @param int $object_id ID de l'objet (term_id)
     * @param string $meta_key Clé meta
     * @param mixed $meta_value Valeur meta
     */
    public static function clear_cache_on_update($meta_id, $object_id, $meta_key, $meta_value) {
        // Vérifier si c'est une méta vedette
        $vedette_meta_keys = array_values(Sisme_Vedettes_Data_Manager::META_KEYS);
        
        if (in_array($meta_key, $vedette_meta_keys)) {
            Sisme_Vedettes_API::clear_cache();
            error_log("Sisme Vedettes: Cache vidé après modification de $meta_key pour terme $object_id");
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
    public static function auto_initialize_vedettes($meta_id, $term_id, $meta_key, $meta_value) {
        // Déclencher uniquement pour game_description
        if ($meta_key === 'game_description') {
            error_log("Sisme Hook: Détection game_description pour terme $term_id");
            
            // Forcer l'initialisation (même si déjà initialisé)
            Sisme_Vedettes_Data_Manager::force_initialize_game($term_id);
        }
    }
}
?>