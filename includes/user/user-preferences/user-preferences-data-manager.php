<?php
/**
 * File: /sisme-games-editor/includes/user/user-preferences/user-preferences-data-manager.php
 * Gestionnaire de données pour les préférences utilisateur
 * 
 * RESPONSABILITÉ:
 * - CRUD des préférences utilisateur (plateformes, genres, notifications...)
 * - Récupération des données taxonomies (genres, types joueur)
 * - Validation et sanitisation des données
 * - Gestion des valeurs par défaut
 * 
 * DÉPENDANCES:
 * - WordPress user_meta
 * - Taxonomie categories (jeux-vidéo, jeux-solo, jeux-multijoueur, jeux-cooperatif)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Preferences_Data_Manager {
    
    // Meta keys pour les préférences utilisateur
    const META_PLATFORMS = 'sisme_user_platforms';
    const META_GENRES = 'sisme_user_genres';
    const META_PLAYER_TYPES = 'sisme_user_player_types';
    const META_NOTIFICATIONS = 'sisme_user_notifications';
    const META_PRIVACY_PUBLIC = 'sisme_user_privacy_public';
    
    // Clés de préférences valides
    private static $valid_preference_keys = [
        'platforms',
        'genres', 
        'player_types',
        'notifications',
        'privacy_public'
    ];
    
    /**
     * Récupérer toutes les préférences d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Préférences complètes avec valeurs par défaut si nécessaire
     */
    public static function get_user_preferences($user_id) {
        if (!$user_id || !get_userdata($user_id)) {
            return [];
        }
        
        $defaults = self::get_default_preferences();
        
        $preferences = [
            'platforms' => get_user_meta($user_id, self::META_PLATFORMS, true) ?: $defaults['platforms'],
            'genres' => get_user_meta($user_id, self::META_GENRES, true) ?: $defaults['genres'],
            'player_types' => get_user_meta($user_id, self::META_PLAYER_TYPES, true) ?: $defaults['player_types'],
            'notifications' => get_user_meta($user_id, self::META_NOTIFICATIONS, true) ?: $defaults['notifications'],
            'privacy_public' => get_user_meta($user_id, self::META_PRIVACY_PUBLIC, true) !== '' ? 
                               (bool) get_user_meta($user_id, self::META_PRIVACY_PUBLIC, true) : 
                               $defaults['privacy_public']
        ];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences] Préférences récupérées pour utilisateur {$user_id}");
        }
        
        return $preferences;
    }
    
    /**
     * Récupérer une préférence spécifique d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $key Clé de la préférence
     * @return mixed Valeur de la préférence ou valeur par défaut
     */
    public static function get_user_preference($user_id, $key) {
        if (!self::validate_preference_key($key)) {
            return null;
        }
        
        $all_preferences = self::get_user_preferences($user_id);
        return isset($all_preferences[$key]) ? $all_preferences[$key] : null;
    }
    
    /**
     * Mettre à jour une préférence utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $key Clé de la préférence
     * @param mixed $value Nouvelle valeur
     * @return bool Succès de la sauvegarde
     */
    public static function update_user_preference($user_id, $key, $value) {
        if (!$user_id || !get_userdata($user_id)) {
            return false;
        }
        
        if (!self::validate_preference_key($key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Preferences] Clé de préférence invalide: {$key}");
            }
            return false;
        }
        
        // Valider et nettoyer la valeur
        if (!self::validate_preference_value($key, $value)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Preferences] Valeur invalide pour {$key}: " . print_r($value, true));
            }
            return false;
        }
        
        $sanitized_value = self::sanitize_preference_value($key, $value);
        $meta_key = self::get_meta_key_for_preference($key);
        
        $success = update_user_meta($user_id, $meta_key, $sanitized_value);
        
        if ($success && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences] Préférence {$key} mise à jour pour utilisateur {$user_id}");
        }
        
        return $success;
    }
    
    /**
     * Mettre à jour plusieurs préférences en une fois
     * 
     * @param int $user_id ID de l'utilisateur
     * @param array $preferences Tableau clé => valeur des préférences
     * @return bool Succès de toutes les sauvegardes
     */
    public static function update_multiple_preferences($user_id, $preferences) {
        if (!$user_id || !is_array($preferences)) {
            return false;
        }
        
        $all_success = true;
        
        foreach ($preferences as $key => $value) {
            $success = self::update_user_preference($user_id, $key, $value);
            if (!$success) {
                $all_success = false;
            }
        }
        
        return $all_success;
    }
    
    /**
     * Réinitialiser les préférences d'un utilisateur aux valeurs par défaut
     * 
     * @param int $user_id ID de l'utilisateur
     * @return bool Succès de la réinitialisation
     */
    public static function reset_user_preferences($user_id) {
        if (!$user_id || !get_userdata($user_id)) {
            return false;
        }
        
        $defaults = self::get_default_preferences();
        $success = self::update_multiple_preferences($user_id, $defaults);
        
        if ($success && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences] Préférences réinitialisées pour utilisateur {$user_id}");
        }
        
        return $success;
    }
    
    /**
     * Récupérer les plateformes disponibles (liste fixe)
     * 
     * @return array Plateformes avec slug et nom
     */
    public static function get_available_platforms() {
        return [
            ['slug' => 'pc', 'name' => 'PC'],
            ['slug' => 'console', 'name' => 'Console'],
            ['slug' => 'mobile', 'name' => 'Mobile']
        ];
    }
    
    /**
     * Récupérer les genres disponibles depuis la taxonomie
     * (enfants de "jeux-vidéo", excluant les modes de jeu)
     * 
     * @return array Genres avec id, name et slug
     */
    public static function get_available_genres() {
        // Récupérer la catégorie parent "jeux-vidéo"
        $parent_category = get_category_by_slug('jeux-vidéo');
        
        if (!$parent_category) {
            // Essayer par nom si le slug ne fonctionne pas
            $parent_category = get_term_by('name', 'Jeux', 'category');
        }
        
        if (!$parent_category) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme User Preferences] Catégorie parent "jeux-vidéo" introuvable');
            }
            return [];
        }
        
        // Récupérer les catégories enfants
        $categories = get_categories([
            'parent' => $parent_category->term_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ]);
        
        // Exclure les modes de jeu
        $excluded_slugs = ['jeux-solo', 'jeux-multijoueur', 'jeux-cooperatif'];
        $genres = [];
        
        foreach ($categories as $category) {
            if (!in_array($category->slug, $excluded_slugs)) {
                $genres[] = [
                    'id' => $category->term_id,
                    'name' => str_replace('jeux-', '', $category->name), // Nettoyer le nom
                    'slug' => $category->slug
                ];
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Preferences] ' . count($genres) . ' genres récupérés');
        }
        
        return $genres;
    }
    
    /**
     * Récupérer les types de joueur disponibles depuis la taxonomie
     * 
     * @return array Types de joueur avec slug, name et category_slug
     */
    public static function get_available_player_types() {
        $player_types = [];
        $type_definitions = [
            'solo' => ['name' => 'Solo', 'category_slug' => 'jeux-solo'],
            'multijoueur' => ['name' => 'Multijoueur', 'category_slug' => 'jeux-multijoueur'],
            'cooperatif' => ['name' => 'Coopératif', 'category_slug' => 'jeux-cooperatif']
        ];
        
        foreach ($type_definitions as $slug => $definition) {
            // Vérifier que la catégorie existe
            $category = get_category_by_slug($definition['category_slug']);
            
            if ($category) {
                $player_types[] = [
                    'slug' => $slug,
                    'name' => $definition['name'],
                    'category_slug' => $definition['category_slug']
                ];
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Preferences] Catégorie {$definition['category_slug']} introuvable");
                }
            }
        }
        
        return $player_types;
    }
    
    /**
     * Récupérer les types de notifications disponibles
     * 
     * @return array Types de notifications avec clés et labels
     */
    public static function get_notification_types() {
        return [
            'new_games_in_genres' => 'Nouveaux jeux dans mes genres favoris',
            'favorite_games_updates' => 'Mises à jour des jeux en favoris',
            'new_indie_releases' => 'Nouvelles sorties indépendantes',
            'newsletter' => 'Newsletter hebdomadaire'
        ];
    }
    
    /**
     * Récupérer les valeurs par défaut des préférences
     * 
     * @return array Préférences par défaut
     */
    public static function get_default_preferences() {
        return [
            'platforms' => [], // Aucune plateforme sélectionnée par défaut
            'genres' => [], // Aucun genre sélectionné par défaut
            'player_types' => [], // Aucun type sélectionné par défaut
            'notifications' => [
                'new_games_in_genres' => false,
                'favorite_games_updates' => true, // Activé par défaut
                'new_indie_releases' => false,
                'newsletter' => false
            ],
            'privacy_public' => true // Profil public par défaut
        ];
    }
    
    /**
     * Valider une clé de préférence
     * 
     * @param string $key Clé à valider
     * @return bool True si valide
     */
    public static function validate_preference_key($key) {
        return in_array($key, self::$valid_preference_keys);
    }
    
    /**
     * Valider une valeur de préférence
     * 
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur à valider
     * @return bool True si valide
     */
    public static function validate_preference_value($key, $value) {
        switch ($key) {
            case 'platforms':
                return is_array($value) && self::validate_platforms($value);
                
            case 'genres':
                return is_array($value) && self::validate_genre_ids($value);
                
            case 'player_types':
                return is_array($value) && self::validate_player_types($value);
                
            case 'notifications':
                return is_array($value) && self::validate_notifications($value);
                
            case 'privacy_public':
                return is_bool($value) || in_array($value, ['0', '1', 0, 1, true, false]);
                
            default:
                return false;
        }
    }
    
    /**
     * Nettoyer une valeur de préférence
     * 
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur à nettoyer
     * @return mixed Valeur nettoyée
     */
    public static function sanitize_preference_value($key, $value) {
        switch ($key) {
            case 'platforms':
                return array_map('sanitize_text_field', (array) $value);
                
            case 'genres':
                return array_map('intval', (array) $value);
                
            case 'player_types':
                return array_map('sanitize_text_field', (array) $value);
                
            case 'notifications':
                $sanitized = [];
                $valid_keys = array_keys(self::get_notification_types());
                foreach ($valid_keys as $notif_key) {
                    $sanitized[$notif_key] = isset($value[$notif_key]) ? (bool) $value[$notif_key] : false;
                }
                return $sanitized;
                
            case 'privacy_public':
                return (bool) $value;
                
            default:
                return $value;
        }
    }
    
    /**
     * Convertir une clé de préférence en meta_key WordPress
     * 
     * @param string $key Clé de préférence
     * @return string Meta key correspondant
     */
    private static function get_meta_key_for_preference($key) {
        $mapping = [
            'platforms' => self::META_PLATFORMS,
            'genres' => self::META_GENRES,
            'player_types' => self::META_PLAYER_TYPES,
            'notifications' => self::META_NOTIFICATIONS,
            'privacy_public' => self::META_PRIVACY_PUBLIC
        ];
        
        return isset($mapping[$key]) ? $mapping[$key] : '';
    }
    
    /**
     * Valider les plateformes sélectionnées
     * 
     * @param array $platforms Plateformes à valider
     * @return bool True si valides
     */
    private static function validate_platforms($platforms) {
        $available_platforms = array_column(self::get_available_platforms(), 'slug');
        
        foreach ($platforms as $platform) {
            if (!in_array($platform, $available_platforms)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valider les IDs de genres sélectionnés
     * 
     * @param array $genre_ids IDs de genres à valider
     * @return bool True si valides
     */
    private static function validate_genre_ids($genre_ids) {
        foreach ($genre_ids as $genre_id) {
            if (!is_numeric($genre_id) || intval($genre_id) <= 0) {
                return false;
            }
            
            // Vérifier que le genre existe
            $category = get_category(intval($genre_id));
            if (!$category || is_wp_error($category)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valider les types de joueur sélectionnés
     * 
     * @param array $player_types Types à valider
     * @return bool True si valides
     */
    private static function validate_player_types($player_types) {
        $available_types = array_column(self::get_available_player_types(), 'slug');
        
        foreach ($player_types as $type) {
            if (!in_array($type, $available_types)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valider les préférences de notifications
     * 
     * @param array $notifications Notifications à valider
     * @return bool True si valides
     */
    private static function validate_notifications($notifications) {
        $valid_keys = array_keys(self::get_notification_types());
        
        foreach ($notifications as $key => $value) {
            if (!in_array($key, $valid_keys)) {
                return false;
            }
            if (!is_bool($value) && !in_array($value, ['0', '1', 0, 1])) {
                return false;
            }
        }
        
        return true;
    }
}