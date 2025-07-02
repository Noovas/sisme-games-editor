<?php
/**
 * File: user-actions-data-manager.php
 * Module: Gestionnaire de données User Actions
 * 
 * Gestion CRUD des interactions utilisateur avec les jeux
 * (favoris, jeux possédés, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Actions_Data_Manager {
    
    // Types de collection
    const COLLECTION_FAVORITE = 'favorite';
    const COLLECTION_OWNED = 'owned';
    
    // Mapping des types vers les meta_keys
    private static $collection_meta_keys = [
        self::COLLECTION_FAVORITE => 'sisme_user_favorite_games',
        self::COLLECTION_OWNED => 'sisme_user_owned_games'
    ];

    /**
     * Migrer les collections utilisateur vers le nouveau format avec timestamps
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $collection_type Type de collection
     */
    private static function migrate_user_collection_to_timestamped($user_id, $collection_type) {
        if (!isset(self::$collection_meta_keys[$collection_type])) {
            return false;
        }
        
        $meta_key = self::$collection_meta_keys[$collection_type];
        $collection = get_user_meta($user_id, $meta_key, true);
        
        // Si déjà au nouveau format (array associatif avec timestamps), ne rien faire
        if (is_array($collection) && !empty($collection)) {
            $first_key = array_key_first($collection);
            if (is_array($collection[$first_key]) && isset($collection[$first_key]['added_at'])) {
                return true;
            }
        }
        
        // Migration : transformer [123, 456] en [123 => ['added_at' => date], 456 => [...]]
        if (is_array($collection) && !empty($collection)) {
            $migrated_collection = [];
            $base_date = time() - (count($collection) * 86400);
            
            foreach ($collection as $index => $game_id) {
                $migrated_collection[$game_id] = [
                    'added_at' => date('Y-m-d H:i:s', $base_date + ($index * 86400))
                ];
            }
            
            update_user_meta($user_id, $meta_key, $migrated_collection);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Actions] Collection {$collection_type} migrée pour utilisateur {$user_id}");
            }
        }
        
        return true;
    }
    
    /**
     * Vérifier si un jeu est dans une collection utilisateur (MODIFIÉE)
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $game_id ID du jeu (term_id)
     * @param string $collection_type Type de collection (favorite, owned)
     * @return bool
     */
    public static function is_game_in_user_collection($user_id, $game_id, $collection_type) {
        if (!isset(self::$collection_meta_keys[$collection_type])) {
            return false;
        }
        
        // Migration automatique si nécessaire
        self::migrate_user_collection_to_timestamped($user_id, $collection_type);
        
        $meta_key = self::$collection_meta_keys[$collection_type];
        $collection = get_user_meta($user_id, $meta_key, true);
        
        if (empty($collection) || !is_array($collection)) {
            return false;
        }
        
        // Nouveau format : vérifier la clé dans l'array associatif
        return isset($collection[$game_id]);
    }
    
    /**
     * Ajouter un jeu à une collection utilisateur (MODIFIÉE)
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $game_id ID du jeu (term_id)
     * @param string $collection_type Type de collection (favorite, owned)
     * @return bool Succès de l'opération
     */
    public static function add_game_to_user_collection($user_id, $game_id, $collection_type) {
        if (!isset(self::$collection_meta_keys[$collection_type])) {
            return false;
        }
        
        // Migration automatique si nécessaire
        self::migrate_user_collection_to_timestamped($user_id, $collection_type);
        
        $meta_key = self::$collection_meta_keys[$collection_type];
        $collection = get_user_meta($user_id, $meta_key, true);
        
        // Initialiser collection vide si nécessaire
        if (empty($collection) || !is_array($collection)) {
            $collection = [];
        }
        
        // Si le jeu est déjà dans la collection, ne rien faire
        if (isset($collection[$game_id])) {
            return true;
        }
        
        // Ajouter le jeu avec timestamp actuel
        $collection[$game_id] = [
            'added_at' => current_time('mysql')
        ];
        
        $success = update_user_meta($user_id, $meta_key, $collection);
        
        // Actions personnalisées après l'ajout
        if ($success) {
            do_action("sisme_user_{$collection_type}_game_added", $user_id, $game_id);
            self::invalidate_user_cache($user_id);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Actions] Jeu {$game_id} ajouté à {$collection_type} pour utilisateur {$user_id}");
            }
        }
        
        return $success;
    }
    
    /**
     * Supprimer un jeu d'une collection utilisateur (MODIFIÉE)
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $game_id ID du jeu (term_id)
     * @param string $collection_type Type de collection (favorite, owned)
     * @return bool Succès de l'opération
     */
    public static function remove_game_from_user_collection($user_id, $game_id, $collection_type) {
        if (!isset(self::$collection_meta_keys[$collection_type])) {
            return false;
        }
        
        // Migration automatique si nécessaire
        self::migrate_user_collection_to_timestamped($user_id, $collection_type);
        
        $meta_key = self::$collection_meta_keys[$collection_type];
        $collection = get_user_meta($user_id, $meta_key, true);
        
        if (empty($collection) || !is_array($collection)) {
            return true;
        }
        
        // Si le jeu n'est pas dans la collection, ne rien faire
        if (!isset($collection[$game_id])) {
            return true;
        }
        
        // Supprimer le jeu
        unset($collection[$game_id]);
        $success = update_user_meta($user_id, $meta_key, $collection);
        
        // Actions personnalisées après la suppression
        if ($success) {
            do_action("sisme_user_{$collection_type}_game_removed", $user_id, $game_id);
            self::invalidate_user_cache($user_id);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Actions] Jeu {$game_id} retiré de {$collection_type} pour utilisateur {$user_id}");
            }
        }
        
        return $success;
    }
    
    /**
     * Récupérer les jeux d'une collection utilisateur (MODIFIÉE)
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $collection_type Type de collection (favorite, owned)
     * @param int $limit Nombre maximum de jeux à récupérer (-1 pour tous)
     * @return array Liste des IDs de jeux dans la collection
     */
    public static function get_user_collection($user_id, $collection_type, $limit = -1) {
        if (!isset(self::$collection_meta_keys[$collection_type])) {
            return [];
        }
        
        // Migration automatique si nécessaire
        self::migrate_user_collection_to_timestamped($user_id, $collection_type);
        
        $meta_key = self::$collection_meta_keys[$collection_type];
        $collection = get_user_meta($user_id, $meta_key, true);
        
        if (empty($collection) || !is_array($collection)) {
            return [];
        }
        
        // Extraire les IDs (clés de l'array)
        $game_ids = array_keys($collection);
        
        // Limiter si nécessaire
        if ($limit > 0 && count($game_ids) > $limit) {
            // Trier par date d'ajout (plus récent d'abord)
            uasort($collection, function($a, $b) {
                return strtotime($b['added_at']) - strtotime($a['added_at']);
            });
            
            $game_ids = array_slice(array_keys($collection), 0, $limit);
        }
        
        return $game_ids;
    }

    /**
     * Récupérer une collection avec métadonnées complètes
     * 
     * @param int $user_id ID de l'utilisateur
     * @param string $collection_type Type de collection
     * @param int $limit Limite (-1 pour tous)
     * @return array Collection avec metadata [game_id => ['added_at' => date]]
     */
    public static function get_user_collection_with_metadata($user_id, $collection_type, $limit = -1) {
        if (!isset(self::$collection_meta_keys[$collection_type])) {
            return [];
        }
        
        // Migration automatique si nécessaire
        self::migrate_user_collection_to_timestamped($user_id, $collection_type);
        
        $meta_key = self::$collection_meta_keys[$collection_type];
        $collection = get_user_meta($user_id, $meta_key, true);
        
        if (empty($collection) || !is_array($collection)) {
            return [];
        }
        
        // Trier par date d'ajout (plus récent d'abord)
        uasort($collection, function($a, $b) {
            return strtotime($b['added_at']) - strtotime($a['added_at']);
        });
        
        // Limiter si nécessaire
        if ($limit > 0) {
            $collection = array_slice($collection, 0, $limit, true);
        }
        
        return $collection;
    }

    /**
     * NOUVELLE : Obtenir la date d'ajout d'un jeu dans une collection
     * 
     * @param int $user_id ID utilisateur
     * @param int $game_id ID jeu
     * @param string $collection_type Type collection
     * @return string|false Date MySQL ou false si pas trouvé
     */
    public static function get_game_added_date($user_id, $game_id, $collection_type) {
        if (!isset(self::$collection_meta_keys[$collection_type])) {
            return false;
        }
        
        // Migration automatique si nécessaire
        self::migrate_user_collection_to_timestamped($user_id, $collection_type);
        
        $meta_key = self::$collection_meta_keys[$collection_type];
        $collection = get_user_meta($user_id, $meta_key, true);
        
        if (empty($collection) || !is_array($collection) || !isset($collection[$game_id])) {
            return false;
        }
        
        return $collection[$game_id]['added_at'];
    }
    
    /**
     * Basculer le statut d'un jeu dans une collection utilisateur
     * (ajouter s'il n'est pas présent, supprimer s'il l'est)
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $game_id ID du jeu (term_id)
     * @param string $collection_type Type de collection (favorite, owned)
     * @return array État après opération (success, status, message)
     */
    public static function toggle_game_in_user_collection($user_id, $game_id, $collection_type) {
        $is_in_collection = self::is_game_in_user_collection($user_id, $game_id, $collection_type);
        
        if ($is_in_collection) {
            // Supprimer de la collection
            $success = self::remove_game_from_user_collection($user_id, $game_id, $collection_type);
            
            if ($success) {
                return [
                    'success' => true,
                    'status' => 'removed',
                    'message' => __('Jeu retiré de votre collection', 'sisme-games-editor'),
                    'is_active' => false
                ];
            } else {
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => __('Erreur lors de la suppression', 'sisme-games-editor'),
                    'is_active' => $is_in_collection
                ];
            }
        } else {
            // Ajouter à la collection
            $success = self::add_game_to_user_collection($user_id, $game_id, $collection_type);
            
            if ($success) {
                return [
                    'success' => true,
                    'status' => 'added',
                    'message' => __('Jeu ajouté à votre collection', 'sisme-games-editor'),
                    'is_active' => true
                ];
            } else {
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => __('Erreur lors de l\'ajout', 'sisme-games-editor'),
                    'is_active' => $is_in_collection
                ];
            }
        }
    }
    
    /**
     * Invalider le cache utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     */
    private static function invalidate_user_cache($user_id) {
        // Supprimer les transients utilisateur si le module dashboard est présent
        if (class_exists('Sisme_User_Dashboard_Data_Manager')) {
            if (method_exists('Sisme_User_Dashboard_Data_Manager', 'clear_user_dashboard_cache')) {
                Sisme_User_Dashboard_Data_Manager::clear_user_dashboard_cache($user_id);
            }
        }
    }
    
    /**
     * Récupérer les statistiques d'une collection pour un jeu
     * 
     * @param int $game_id ID du jeu
     * @param string $collection_type Type de collection
     * @return int Nombre d'utilisateurs ayant ce jeu dans leur collection
     */
    public static function get_game_collection_stats($game_id, $collection_type) {
        global $wpdb;
        
        if (!isset(self::$collection_meta_keys[$collection_type])) {
            return 0;
        }
        
        $meta_key = self::$collection_meta_keys[$collection_type];
        
        // Requête pour compter les utilisateurs ayant ce jeu dans leur collection
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE meta_key = %s 
             AND meta_value LIKE %s",
            $meta_key,
            '%"' . $game_id . '"%'
        ));
        
        return intval($count);
    }
}