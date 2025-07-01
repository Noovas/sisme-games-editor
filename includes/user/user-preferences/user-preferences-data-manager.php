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
    const META_AVATAR = 'sisme_user_avatar';

    
    // Clés de préférences valides
    private static $valid_preference_keys = [
        'platforms',
        'genres', 
        'player_types',
        'notifications',
        'privacy_public',
        'avatar'
    ];
    
    /**
     * Récupérer toutes les préférences d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Préférences complètes avec valeurs par défaut si nécessaire
     */
    public static function get_user_preferences($user_id) {
        if (!Sisme_Utils_Users::validate_user_id($user_id, 'get_user_preferences')) {
            return [];
        }
        $defaults = self::get_default_preferences();
        $preferences = [
            Sisme_Utils_Games::KEY_PLATFORMS => get_user_meta($user_id, self::META_PLATFORMS, true) ?: $defaults['platforms'],
            Sisme_Utils_Games::KEY_GENRES => get_user_meta($user_id, self::META_GENRES, true) ?: $defaults[Sisme_Utils_Games::KEY_GENRES],
            'player_types' => get_user_meta($user_id, self::META_PLAYER_TYPES, true) ?: $defaults['player_types'],
            'notifications' => get_user_meta($user_id, self::META_NOTIFICATIONS, true) ?: $defaults['notifications'],
            'avatar' => get_user_meta($user_id, self::META_AVATAR, true) ?: $defaults['avatar'],
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
        if (!Sisme_Utils_Users::validate_user_id($user_id, 'update_user_preference')) {
            return false;
        }
        
        if (!self::validate_preference_key($key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Preferences] ERREUR - Clé de préférence invalide: {$key}");
            }
            return false;
        }
        
        // Debug: Log des données avant validation
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences] Validation pour {$key} - Type: " . gettype($value) . ", Valeur: " . print_r($value, true));
        }
        
        // Valider la valeur AVANT de nettoyer
        if (!self::validate_preference_value($key, $value)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Preferences] ERREUR - Valeur invalide pour {$key}: " . print_r($value, true));
                
                // Debug supplémentaire pour les tableaux
                if (is_array($value)) {
                    error_log("[Sisme User Preferences] DEBUG - Tableau de taille: " . count($value) . ", Empty: " . (empty($value) ? 'OUI' : 'NON'));
                }
            }
            return false;
        }
        
        // Nettoyer la valeur
        $clean_value = self::sanitize_preference_value($key, $value);
        
        // Debug: Log des données après nettoyage
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences] Valeur après nettoyage - Type: " . gettype($clean_value) . ", Valeur: " . print_r($clean_value, true));
        }
        
        // Obtenir la meta_key WordPress
        $meta_key = self::get_meta_key_for_preference($key);
        if (empty($meta_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Preferences] ERREUR - Meta key introuvable pour: {$key}");
            }
            return false;
        }
        
        // Sauvegarder
        $success = update_user_meta($user_id, $meta_key, $clean_value);
        
        // Debug: Log du résultat
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences] Sauvegarde {$key} pour utilisateur {$user_id}: " . ($success !== false ? 'SUCCESS' : 'FAILED'));
            
            // Vérifier que la sauvegarde a bien fonctionné
            $saved_value = get_user_meta($user_id, $meta_key, true);
            error_log("[Sisme User Preferences] Valeur vérifiée depuis DB: " . print_r($saved_value, true));
        }
        
        if ($success !== false) {
            // Déclencher un hook pour d'autres modules
            do_action('sisme_user_preference_updated', $user_id, $key, $clean_value);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Preferences] Préférence {$key} mise à jour avec succès pour utilisateur {$user_id}");
            }
            
            return true;
        }
        
        return false;
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
        if (!Sisme_Utils_Users::validate_user_id($user_id, 'reset_user_preferences')) {
            return false;
        }
        $defaults = self::get_default_preferences();
        self::update_multiple_preferences($user_id, $defaults);
        return true;
    }
    
    /**
     * Récupérer les plateformes disponibles (liste fixe)
     * 
     * @return array Plateformes avec slug et nom
     */
    public static function get_available_platforms() {
        return [
            ['slug' => 'pc', Sisme_Utils_Games::KEY_NAME => 'PC'],
            ['slug' => 'console', Sisme_Utils_Games::KEY_NAME => 'Console'],
            ['slug' => 'mobile', Sisme_Utils_Games::KEY_NAME => 'Mobile']
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
                    Sisme_Utils_Games::KEY_ID => $category->term_id,
                    Sisme_Utils_Games::KEY_NAME => str_replace('jeux-', '', $category->name), // Nettoyer le nom
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
            'solo' => [Sisme_Utils_Games::KEY_NAME => 'Solo', 'category_slug' => 'jeux-solo'],
            'multijoueur' => [Sisme_Utils_Games::KEY_NAME => 'Multijoueur', 'category_slug' => 'jeux-multijoueur'],
            'cooperatif' => [Sisme_Utils_Games::KEY_NAME => 'Coopératif', 'category_slug' => 'jeux-cooperatif']
        ];
        
        foreach ($type_definitions as $slug => $definition) {
            // Vérifier que la catégorie existe
            $category = get_category_by_slug($definition['category_slug']);
            
            if ($category) {
                $player_types[] = [
                    'slug' => $slug,
                    Sisme_Utils_Games::KEY_NAME => $definition[Sisme_Utils_Games::KEY_NAME],
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
        // Récupérer TOUTES les plateformes disponibles
        $all_platforms = array_column(self::get_available_platforms(), 'slug');
        
        // Récupérer TOUS les genres disponibles  
        $all_genres = array_column(self::get_available_genres(), 'id');
        
        // Récupérer TOUS les types de joueur disponibles
        $all_player_types = array_column(self::get_available_player_types(), 'slug');
        
        return [
            Sisme_Utils_Games::KEY_PLATFORMS => $all_platforms,        // TOUTES les plateformes sélectionnées
            Sisme_Utils_Games::KEY_GENRES => $all_genres,              // TOUS les genres sélectionnés  
            'player_types' => $all_player_types,  // TOUS les types sélectionnés
            'notifications' => [
                'new_games_in_genres' => true,     // Toutes activées par défaut
                'favorite_games_updates' => true,
                'new_indie_releases' => true,
                'newsletter' => true
            ],
            'privacy_public' => true,
            'avatar' => 'default'
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
                // CORRECTION: Vérifier que c'est un array ET valider son contenu (même si vide)
                return is_array($value) && self::validate_platforms($value);
                
            case 'genres':
                // CORRECTION: Vérifier que c'est un array ET valider son contenu (même si vide)
                return is_array($value) && self::validate_genre_ids($value);
                
            case 'player_types':
                // CORRECTION: Vérifier que c'est un array ET valider son contenu (même si vide)
                return is_array($value) && self::validate_player_types($value);
                
            case 'notifications':
                // CORRECTION: Vérifier que c'est un array ET valider son contenu (même si vide)
                return is_array($value) && self::validate_notifications($value);
                
            case 'privacy_public':
                return is_bool($value) || in_array($value, ['0', '1', 0, 1, true, false]);

            case 'avatar':
                if (empty($value)) return true;
                if (is_numeric($value)) return true; // Ancien système
                if (is_string($value)) {
                    if ($value === 'default') return true;
                    if (class_exists('Sisme_Constants')) {
                        return Sisme_Constants::is_valid_avatar($value); // ← Ça doit valider 'borne-arcade' !
                    }
                    return true;
                }
                return false;
                
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

            case 'avatar':
                if (empty($value)) return 'default';
                if (is_numeric($value)) return intval($value);
                $clean_value = sanitize_text_field($value);
                if (class_exists('Sisme_Constants') && !Sisme_Constants::is_valid_avatar($clean_value)) {
                    return 'default';
                }
                return $clean_value;
                
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
            Sisme_Utils_Games::KEY_PLATFORMS => self::META_PLATFORMS,
            Sisme_Utils_Games::KEY_GENRES => self::META_GENRES,
            'player_types' => self::META_PLAYER_TYPES,
            'notifications' => self::META_NOTIFICATIONS,
            'privacy_public' => self::META_PRIVACY_PUBLIC,
            'avatar' => self::META_AVATAR
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
        // Tableau vide = OK (aucune sélection)
        if (empty($platforms)) {
            return true;
        }
        
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
        if (empty($genre_ids)) {
            return true;
        }
        
        // Vérifier que chaque ID est numérique et > 0
        foreach ($genre_ids as $genre_id) {
            if (!is_numeric($genre_id) || intval($genre_id) <= 0) {
                return false;
            }
        }
        
        // On ne vérifie PLUS get_category() car ça peut faire planter
        return true;
    }
    
    /**
     * Valider les types de joueur sélectionnés
     * 
     * @param array $player_types Types à valider
     * @return bool True si valides
     */
    private static function validate_player_types($player_types) {
    // Tableau vide = OK (aucune sélection)
        if (empty($player_types)) {
            return true;
        }
        
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
        if (empty($notifications)) {
            return true;
        }

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

    /**
     * Mettre à jour l'avatar utilisateur (clé de librairie)
     * 
     * @param int $user_id ID utilisateur
     * @param string $avatar_key Clé d'avatar de la librairie ('borne-arcade', 'cd-rom', etc.)
     * @return bool Succès de la sauvegarde
     */
    public static function update_user_avatar($user_id, $avatar_key) {
        // Valider que la clé d'avatar existe dans la librairie
        if (!class_exists('Sisme_Constants') || !Sisme_Constants::is_valid_avatar($avatar_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Preferences] ERREUR - Clé d'avatar invalide: {$avatar_key}");
            }
            return false;
        }
        
        // Sauvegarder la clé d'avatar (STRING, pas intval !)
        $success = update_user_meta($user_id, self::META_AVATAR, sanitize_text_field($avatar_key));
        
        if ($success && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences] Avatar mis à jour - Utilisateur {$user_id} - Clé: {$avatar_key}");
        }
        
        return $success !== false;
    }

    /**
     * Récupérer l'URL de l'avatar utilisateur
     */
    public static function get_user_avatar_url($user_id, $size = 'thumbnail') {
        $avatar_key = get_user_meta($user_id, self::META_AVATAR, true);
        if (!$avatar_key) {
            $avatar_key = 'default';
        }
        if (is_numeric($avatar_key)) {
            $attachment_url = wp_get_attachment_image_url($avatar_key, $size);
            if ($attachment_url) {
                return $attachment_url;
            } else {
                // Attachment n'existe plus, migrer vers default
                self::update_user_avatar($user_id, 'default');
                $avatar_key = 'default';
            }
        }
        if (class_exists('Sisme_Constants')) {
            $avatar_url = Sisme_Constants::get_avatar_url($avatar_key);        
            return $avatar_url;
        }    
        return false;
    }
}