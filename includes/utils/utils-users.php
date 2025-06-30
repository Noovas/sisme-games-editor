<?php
/**
 * File: /sisme-games-editor/includes/utils/utils-users.php
 * Utilitaires partagés pour la gestion des utilisateurs
 * 
 * RESPONSABILITÉ:
 * - Validation des utilisateurs
 * - Gestion des métadonnées utilisateur
 * - Collections de jeux utilisateur
 * - Utilitaires logging utilisateur
 * 
 * DÉPENDANCES:
 * - WordPress User API
 * - WordPress Meta API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Utils_Users {
    
    /**
     * Types de collections de jeux supportés
     */
    const COLLECTION_FAVORITE = 'favorite';
    const COLLECTION_OWNED = 'owned';
    const COLLECTION_WISHLIST = 'wishlist';
    const COLLECTION_COMPLETED = 'completed';
    
    /**
     * Préfixes des clés meta utilisateur
     */
    const META_PREFIX = 'sisme_user_';
    
    /**
     * Valider qu'un ID utilisateur est valide et que l'utilisateur existe
     * 
     * @param int $user_id ID de l'utilisateur à valider
     * @param string $context Contexte pour les logs (optionnel)
     * @return bool True si l'utilisateur est valide, false sinon
     */
    public static function validate_user_id($user_id, $context = '') {
        if (empty($user_id) || !is_numeric($user_id) || $user_id <= 0) {
            if (defined('WP_DEBUG') && WP_DEBUG && $context) {
                error_log("[Sisme Utils Users] {$context}: ID utilisateur invalide: {$user_id}");
            }
            return false;
        }
        $user = get_userdata($user_id);
        if (!$user || is_wp_error($user)) {
            if (defined('WP_DEBUG') && WP_DEBUG && $context) {
                error_log("[Sisme Utils Users] {$context}: Utilisateur inexistant: {$user_id}");
            }
            return false;
        }
        return true;
    }

    /**
     * Récupérer une métadonnée utilisateur avec valeur par défaut
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $meta_key Clé de la métadonnée
     * @param mixed $default Valeur par défaut si la métadonnée n'existe pas
     * @param string $context Contexte pour les logs (optionnel)
     * @return mixed Valeur de la métadonnée ou valeur par défaut
     */
    public static function get_user_meta_with_default($user_id, $meta_key, $default = null, $context = '') {
        if (!self::validate_user_id($user_id, $context ?: 'get_meta')) {
            return $default;
        }
        if (empty($meta_key) || !is_string($meta_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme Utils Users] " . ($context ?: 'get_meta') . ": Clé meta invalide: " . var_export($meta_key, true));
            }
            return $default;
        }
        $value = get_user_meta($user_id, $meta_key, true);
        return !empty($value) ? $value : $default;
    }
}