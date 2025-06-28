<?php
/**
 * File: /sisme-games-editor/includes/user/user-dashboard/user-dashboard-data-manager.php
 * Gestionnaire de données pour le dashboard utilisateur avec support onglets
 * 
 * RESPONSABILITÉ:
 * - Récupérer les données utilisateur pour le dashboard
 * - Support complet des collections favorites et owned
 * - Intégration avec le module Cards pour les jeux
 * - Cache intelligent par section
 * - Statistiques gaming complètes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Dashboard_Data_Manager {
    
    /**
     * Durée du cache en secondes (5 minutes)
     */
    const CACHE_DURATION = 300;
    
    /**
     * Obtenir toutes les données nécessaires pour le dashboard
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array|false Données complètes ou false si erreur
     */
    public static function get_dashboard_data($user_id) {
        if (!$user_id || !get_userdata($user_id)) {
            return false;
        }
        
        // Vérifier le cache d'abord
        $cache_key = "sisme_dashboard_data_{$user_id}";
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme Dashboard Data] Cache hit pour utilisateur {$user_id}");
            }
            return $cached_data;
        }
        
        // Construire les données dashboard
        $dashboard_data = [
            'user_info' => self::get_user_info($user_id),
            'gaming_stats' => self::get_gaming_stats($user_id),
            'recent_games' => self::get_recent_games_filtered($user_id),
            'favorite_games' => self::get_favorite_games($user_id),
            'owned_games' => self::get_owned_games($user_id),
            'activity_feed' => self::get_activity_feed($user_id),
            'last_updated' => current_time('timestamp')
        ];
        
        // Mettre en cache
        set_transient($cache_key, $dashboard_data, self::CACHE_DURATION);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Dashboard Data] Données générées et mises en cache pour utilisateur {$user_id}");
        }
        
        return $dashboard_data;
    }
    
    /**
     * Informations de base de l'utilisateur
     * 
     * @param int $user_id ID utilisateur
     * @return array Infos utilisateur
     */
    public static function get_user_info($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return [];
        }
        
        return [
            'id' => $user_id,
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'avatar_url' => get_avatar_url($user_id, ['size' => 80]),
            'member_since' => date_i18n('j F Y', strtotime($user->user_registered)),
            'last_login' => get_user_meta($user_id, 'sisme_user_last_login', true),
            'profile_created' => get_user_meta($user_id, 'sisme_user_profile_created', true)
        ];
    }
    
    /**
     * Statistiques gaming complètes avec support favorites + owned
     * 
     * @param int $user_id ID utilisateur
     * @return array Stats gaming
     */
    public static function get_gaming_stats($user_id) {
        // Récupérer les collections
        $favorite_games = get_user_meta($user_id, 'sisme_user_favorite_games', true) ?: [];
        $owned_games = get_user_meta($user_id, 'sisme_user_owned_games', true) ?: [];
        
        $favorite_count = is_array($favorite_games) ? count($favorite_games) : 0;
        $owned_count = is_array($owned_games) ? count($owned_games) : 0;
        
        // Calculer le nombre total de jeux uniques (éviter doublons)
        $all_unique_games = array_unique(array_merge($favorite_games, $owned_games));
        $total_unique_count = count($all_unique_games);
        
        // Compter les articles créés par l'utilisateur
        $user_posts = count_user_posts($user_id, 'post');
        
        return [
            'total_games' => $total_unique_count,
            'favorite_games' => $favorite_count,
            'owned_games' => $owned_count,
            'user_posts' => $user_posts,
            'completion_rate' => 0, // Future feature
            'playtime_hours' => 0,  // Future feature
            'level' => self::calculate_user_level($total_unique_count, $user_posts)
        ];
    }
    
    /**
     * Calculer le niveau de l'utilisateur basé sur son activité
     * 
     * @param int $total_games_count Nombre total de jeux
     * @param int $posts_count Nombre de posts
     * @return string Niveau utilisateur
     */
    private static function calculate_user_level($total_games_count, $posts_count) {
        $score = $total_games_count + ($posts_count * 2);
        
        if ($score >= 50) return 'Expert';
        if ($score >= 20) return 'Expérimenté';
        if ($score >= 10) return 'Intermédiaire';
        if ($score >= 5) return 'Débutant';
        return 'Nouveau';
    }
    
    /**
     * Récupérer les jeux récents (via module Cards)
     * 
     * @param int $user_id ID utilisateur
     * @param int $limit Nombre de jeux à récupérer
     * @return array Jeux récents
     */
    public static function get_recent_games($user_id, $limit = 6) {
        // Vérifier si le module Cards est disponible
        if (!class_exists('Sisme_Cards_Functions')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme Dashboard Data] Module Cards non disponible pour recent_games');
            }
            return [];
        }
        
        // Utiliser Cards pour récupérer les jeux récents
        try {
            $criteria = [
                'limit' => $limit,
                'orderby' => 'last_update',
                'order' => 'DESC'
            ];
            
            $games = Sisme_Cards_Functions::get_games_by_criteria($criteria);
            return is_array($games) ? $games : [];
            
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[Sisme Dashboard Data] Erreur récupération recent_games: ' . $e->getMessage());
            }
            return [];
        }
    }
    
    /**
     * Récupérer les jeux favoris avec données complètes
     * 
     * @param int $user_id ID utilisateur
     * @param int $limit Nombre de favoris à récupérer
     * @return array Jeux favoris
     */
    public static function get_favorite_games($user_id, $limit = 20) {
        $favorite_game_ids = get_user_meta($user_id, 'sisme_user_favorite_games', true);
        
        if (!is_array($favorite_game_ids) || empty($favorite_game_ids)) {
            return [];
        }
        
        // Limiter et récupérer les plus récents
        $favorite_game_ids = array_slice(array_reverse($favorite_game_ids), 0, $limit);
        
        return self::build_games_array($favorite_game_ids);
    }
    
    /**
     * Récupérer les jeux possédés de l'utilisateur
     * 
     * @param int $user_id ID utilisateur
     * @param int $limit Nombre de jeux owned à récupérer
     * @return array Jeux possédés
     */
    public static function get_owned_games($user_id, $limit = 20) {
        $owned_game_ids = get_user_meta($user_id, 'sisme_user_owned_games', true);
        
        if (!is_array($owned_game_ids) || empty($owned_game_ids)) {
            return [];
        }
        
        // Limiter et récupérer les plus récents
        $owned_game_ids = array_slice(array_reverse($owned_game_ids), 0, $limit);
        
        return self::build_games_array($owned_game_ids);
    }
    
    /**
     * Méthode utilitaire pour construire un tableau de jeux à partir d'IDs
     * 
     * @param array $game_ids Liste des IDs de jeux
     * @return array Tableau de jeux avec données complètes
     */
    private static function build_games_array($game_ids) {
        $games = [];
        
        foreach ($game_ids as $term_id) {
            $term = get_term($term_id, 'post_tag');
            if ($term && !is_wp_error($term)) {
                // Récupérer les données via Cards si disponible
                if (class_exists('Sisme_Cards_Functions')) {
                    $game_data = Sisme_Cards_Functions::get_game_data($term_id);
                    if ($game_data) {
                        $games[] = [
                            'id' => $term_id,
                            'name' => $game_data['name'],
                            'slug' => $game_data['slug'],
                            'cover_url' => $game_data['cover_url'] ?? '',
                            'game_url' => $game_data['game_url'] ?? get_term_link($term),
                            'genres' => $game_data['genres'] ?? []
                        ];
                    }
                } else {
                    // Fallback simple sans Cards
                    $games[] = [
                        'id' => $term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'cover_url' => '',
                        'game_url' => get_term_link($term),
                        'genres' => []
                    ];
                }
            }
        }
        
        return $games;
    }
    
    /**
     * Feed d'activité complet pour la section Activity
     * 
     * @param int $user_id ID utilisateur
     * @param int $limit Nombre d'éléments max
     * @return array Feed d'activité détaillé
     */
    public static function get_activity_feed($user_id, $limit = 20) {
        $activities = [];
        
        // Activité 1: Inscription
        $user = get_userdata($user_id);
        if ($user) {
            $activities[] = [
                'type' => 'register',
                'icon' => '🎮',
                'message' => 'Vous avez créé votre compte Sisme Games',
                'date' => $user->user_registered,
                'timestamp' => strtotime($user->user_registered)
            ];
        }
        
        // Activité 2: Création du profil
        $profile_created = get_user_meta($user_id, 'sisme_user_profile_created', true);
        if ($profile_created) {
            $activities[] = [
                'type' => 'profile_created',
                'icon' => '👤',
                'message' => 'Profil initialisé',
                'date' => $profile_created,
                'timestamp' => strtotime($profile_created)
            ];
        }
        
        // Activité 3: Dernière connexion
        $last_login = get_user_meta($user_id, 'sisme_user_last_login', true);
        if ($last_login) {
            $activities[] = [
                'type' => 'login',
                'icon' => '🔐',
                'message' => 'Dernière connexion au dashboard',
                'date' => $last_login,
                'timestamp' => strtotime($last_login)
            ];
        }
        
        // Activité 4: Favoris récents (simulé - en attente du vrai système de tracking)
        $favorite_games = get_user_meta($user_id, 'sisme_user_favorite_games', true);
        if (!empty($favorite_games) && is_array($favorite_games)) {
            // Prendre les 3 derniers favoris
            $recent_favorites = array_slice(array_reverse($favorite_games), 0, 3);
            foreach ($recent_favorites as $index => $game_id) {
                $term = get_term($game_id, 'post_tag');
                if ($term && !is_wp_error($term)) {
                    $activities[] = [
                        'type' => 'favorite_added',
                        'icon' => '❤️',
                        'message' => 'Ajout de "' . $term->name . '" aux favoris',
                        'date' => current_time('mysql'),
                        'timestamp' => current_time('timestamp') - (3600 * ($index + 1)) // Échelonner sur plusieurs heures
                    ];
                }
            }
        }
        
        // Activité 5: Jeux owned récents (simulé)
        $owned_games = get_user_meta($user_id, 'sisme_user_owned_games', true);
        if (!empty($owned_games) && is_array($owned_games)) {
            // Prendre les 3 derniers owned
            $recent_owned = array_slice(array_reverse($owned_games), 0, 3);
            foreach ($recent_owned as $index => $game_id) {
                $term = get_term($game_id, 'post_tag');
                if ($term && !is_wp_error($term)) {
                    $activities[] = [
                        'type' => 'game_owned',
                        'icon' => '📚',
                        'message' => 'Ajout de "' . $term->name . '" à la Sismothèque',
                        'date' => current_time('mysql'),
                        'timestamp' => current_time('timestamp') - (7200 * ($index + 1)) // Échelonner sur plusieurs heures
                    ];
                }
            }
        }
        
        // Activité 6: Articles créés par l'utilisateur
        $user_posts = get_posts([
            'author' => $user_id,
            'post_type' => 'post',
            'posts_per_page' => 3,
            'post_status' => 'publish'
        ]);
        
        foreach ($user_posts as $post) {
            $activities[] = [
                'type' => 'post_published',
                'icon' => '📝',
                'message' => 'Publication de l\'article "' . $post->post_title . '"',
                'date' => $post->post_date,
                'timestamp' => strtotime($post->post_date)
            ];
        }
        
        // Trier par timestamp décroissant (plus récent en premier)
        usort($activities, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        // Limiter le nombre d'activités
        return array_slice($activities, 0, $limit);
    }
    
    /**
     * Ajouter un jeu aux favoris
     * 
     * @param int $user_id ID utilisateur
     * @param int $game_id ID du jeu (term_id)
     * @return bool Succès
     */
    public static function add_favorite_game($user_id, $game_id) {
        $current_favorites = get_user_meta($user_id, 'sisme_user_favorite_games', true) ?: [];
        
        if (!is_array($current_favorites)) {
            $current_favorites = [];
        }
        
        // Vérifier si pas déjà en favoris
        if (!in_array($game_id, $current_favorites)) {
            $current_favorites[] = intval($game_id);
            $success = update_user_meta($user_id, 'sisme_user_favorite_games', $current_favorites);
            
            if ($success) {
                // Nettoyer le cache
                self::clear_user_dashboard_cache($user_id);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme Dashboard Data] Jeu {$game_id} ajouté aux favoris de l'utilisateur {$user_id}");
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Retirer un jeu des favoris
     * 
     * @param int $user_id ID utilisateur
     * @param int $game_id ID du jeu
     * @return bool Succès
     */
    public static function remove_favorite_game($user_id, $game_id) {
        $current_favorites = get_user_meta($user_id, 'sisme_user_favorite_games', true) ?: [];
        
        if (!is_array($current_favorites)) {
            return false;
        }
        
        $key = array_search($game_id, $current_favorites);
        if ($key !== false) {
            unset($current_favorites[$key]);
            $current_favorites = array_values($current_favorites); // Réindexer
            
            $success = update_user_meta($user_id, 'sisme_user_favorite_games', $current_favorites);
            
            if ($success) {
                // Nettoyer le cache
                self::clear_user_dashboard_cache($user_id);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme Dashboard Data] Jeu {$game_id} retiré des favoris de l'utilisateur {$user_id}");
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Ajouter un jeu à la collection owned
     * 
     * @param int $user_id ID utilisateur
     * @param int $game_id ID du jeu (term_id)
     * @return bool Succès
     */
    public static function add_owned_game($user_id, $game_id) {
        $current_owned = get_user_meta($user_id, 'sisme_user_owned_games', true) ?: [];
        
        if (!is_array($current_owned)) {
            $current_owned = [];
        }
        
        // Vérifier si pas déjà en owned
        if (!in_array($game_id, $current_owned)) {
            $current_owned[] = intval($game_id);
            $success = update_user_meta($user_id, 'sisme_user_owned_games', $current_owned);
            
            if ($success) {
                // Nettoyer le cache
                self::clear_user_dashboard_cache($user_id);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme Dashboard Data] Jeu {$game_id} ajouté à la collection owned de l'utilisateur {$user_id}");
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Retirer un jeu de la collection owned
     * 
     * @param int $user_id ID utilisateur
     * @param int $game_id ID du jeu
     * @return bool Succès
     */
    public static function remove_owned_game($user_id, $game_id) {
        $current_owned = get_user_meta($user_id, 'sisme_user_owned_games', true) ?: [];
        
        if (!is_array($current_owned)) {
            return false;
        }
        
        $key = array_search($game_id, $current_owned);
        if ($key !== false) {
            unset($current_owned[$key]);
            $current_owned = array_values($current_owned); // Réindexer
            
            $success = update_user_meta($user_id, 'sisme_user_owned_games', $current_owned);
            
            if ($success) {
                // Nettoyer le cache
                self::clear_user_dashboard_cache($user_id);
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme Dashboard Data] Jeu {$game_id} retiré de la collection owned de l'utilisateur {$user_id}");
                }
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Mettre à jour la dernière visite du dashboard
     * 
     * @param int $user_id ID utilisateur
     * @return bool Succès
     */
    public static function update_last_dashboard_visit($user_id) {
        $success = update_user_meta($user_id, 'sisme_user_last_dashboard_visit', current_time('mysql'));
        
        if ($success && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Dashboard Data] Dernière visite dashboard mise à jour pour utilisateur {$user_id}");
        }
        
        return $success;
    }
    
    /**
     * Initialiser les données dashboard pour un nouvel utilisateur
     * 
     * @param int $user_id ID utilisateur
     * @return bool Succès
     */
    public static function init_user_dashboard_data($user_id) {
        $defaults = [
            'sisme_user_favorite_games' => [],
            'sisme_user_owned_games' => [],
            'sisme_user_dashboard_created' => current_time('mysql')
        ];
        
        foreach ($defaults as $meta_key => $default_value) {
            if (!get_user_meta($user_id, $meta_key, true)) {
                update_user_meta($user_id, $meta_key, $default_value);
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Dashboard Data] Données dashboard initialisées pour utilisateur {$user_id}");
        }
        
        return true;
    }
    
    /**
     * Nettoyer le cache dashboard d'un utilisateur
     * 
     * @param int $user_id ID utilisateur
     * @return bool Succès
     */
    public static function clear_user_dashboard_cache($user_id) {
        $cache_key = "sisme_dashboard_data_{$user_id}";
        $success = delete_transient($cache_key);
        
        if ($success && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Dashboard Data] Cache dashboard nettoyé pour utilisateur {$user_id}");
        }
        
        return $success;
    }
    
    /**
     * Nettoyer tous les caches dashboard
     * 
     * @return bool Succès
     */
    public static function clear_all_dashboard_caches() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_sisme_dashboard_data_%' 
             OR option_name LIKE '_transient_timeout_sisme_dashboard_data_%'"
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Dashboard Data] Tous les caches dashboard nettoyés");
        }
        
        return true;
    }
    
    /**
     * Obtenir des statistiques globales du système
     * 
     * @return array Stats système
     */
    public static function get_system_stats() {
        global $wpdb;
        
        // Compter les utilisateurs avec des favoris
        $users_with_favorites = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'sisme_user_favorite_games' 
             AND meta_value != '' AND meta_value != 'a:0:{}'"
        );
        
        // Compter les utilisateurs avec des jeux owned
        $users_with_owned = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'sisme_user_owned_games' 
             AND meta_value != '' AND meta_value != 'a:0:{}'"
        );
        
        // Compter le total de favoris
        $total_favorites = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'sisme_user_favorite_games'"
        );
        
        // Compter le total de owned
        $total_owned = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'sisme_user_owned_games'"
        );
        
        return [
            'users_with_favorites' => intval($users_with_favorites),
            'users_with_owned' => intval($users_with_owned),
            'total_favorite_entries' => intval($total_favorites),
            'total_owned_entries' => intval($total_owned),
            'cache_duration_minutes' => self::CACHE_DURATION / 60,
            'cards_module_available' => class_exists('Sisme_Cards_Functions')
        ];
    }

    /**
     * 🎯 Récupérer les jeux récents filtrés par genres favoris
     */
    public static function get_recent_games_filtered($user_id, $limit = 12) {
        self::clear_user_dashboard_cache($user_id);
        // Récupérer préférences
        if (!class_exists('Sisme_User_Preferences_Data_Manager')) {
            return self::get_recent_games_fallback($limit);
        }
        
        $preferences = Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id);
        
        if (empty($preferences['genres'])) {
            return self::get_recent_games_fallback($limit);
        }
        
        // Utiliser Cards avec critères
        if (class_exists('Sisme_Cards_Functions')) {
            $criteria = [
                'genres' => $preferences['genres'],
                'max_results' => $limit,
                'sort_by_date' => true
            ];
            
            $filtered_game_ids = Sisme_Cards_Functions::get_games_by_criteria($criteria);
            
            // CONVERTIR LES IDS EN DONNÉES COMPLÈTES
            return self::build_games_array($filtered_game_ids);
        }
        
        return self::get_recent_games_fallback($limit);
    }

    /**
     * 🔄 Fallback jeux récents sans filtre
     */
    private static function get_recent_games_fallback($limit = 12) {
        if (class_exists('Sisme_Cards_Functions')) {
            $fallback_ids = Sisme_Cards_Functions::get_games_by_criteria([
                'max_results' => $limit,
                'sort_by_date' => true
            ]);
            
            return self::build_games_array($fallback_ids);
        }
        
        return self::get_recent_games($limit); // Méthode existante
    }

    /**
     * ⚡ Vérifier si préférences personnalisées
     */
    public static function user_has_personalized_preferences($user_id) {
        if (!class_exists('Sisme_User_Preferences_Data_Manager')) {
            return false;
        }
        
        $preferences = Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id);
        $defaults = Sisme_User_Preferences_Data_Manager::get_default_preferences();
        
        return !empty($preferences['genres']) && $preferences['genres'] !== $defaults['genres'];
    }
}