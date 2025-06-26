<?php
/**
 * File: /sisme-games-editor/includes/user/user-dashboard/user-dashboard-data-manager.php
 * Gestionnaire de données pour le dashboard utilisateur
 * 
 * RESPONSABILITÉ:
 * - Récupérer les données utilisateur pour le dashboard
 * - Intégration avec le module Cards pour les jeux
 * - Cache simple des données
 * - Statistiques gaming de base
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
            'recent_games' => self::get_recent_games($user_id),
            'favorite_games' => self::get_favorite_games($user_id),
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
     * Statistiques gaming de l'utilisateur
     * 
     * @param int $user_id ID utilisateur
     * @return array Stats gaming
     */
    public static function get_gaming_stats($user_id) {
        // Récupérer les jeux favoris
        $favorite_games = get_user_meta($user_id, 'sisme_user_favorite_games', true) ?: [];
        $favorite_count = is_array($favorite_games) ? count($favorite_games) : 0;
        
        // Compter les articles (fiches) de jeux créés par l'utilisateur (si applicable)
        $user_posts = count_user_posts($user_id, 'post');
        
        // Stats basiques
        return [
            'total_games' => $favorite_count, // Pour l'instant = favoris
            'favorite_games' => $favorite_count,
            'user_posts' => $user_posts,
            'completion_rate' => 0, // À implémenter plus tard
            'playtime_hours' => 0,  // À implémenter plus tard
            'level' => self::calculate_user_level($favorite_count, $user_posts)
        ];
    }
    
    /**
     * Calculer le niveau de l'utilisateur basé sur son activité
     * 
     * @param int $favorite_count Nombre de favoris
     * @param int $posts_count Nombre de posts
     * @return string Niveau utilisateur
     */
    private static function calculate_user_level($favorite_count, $posts_count) {
        $score = $favorite_count + ($posts_count * 2);
        
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
                error_log("[Sisme Dashboard Data] Module Cards non disponible");
            }
            return [];
        }
        
        // Récupérer les jeux récents via le module Cards
        $recent_game_ids = Sisme_Cards_Functions::get_games_by_criteria([
            'sort_by_date' => true,
            'max_results' => $limit,
            'released' => 0 // Tous les jeux
        ]);
        
        if (empty($recent_game_ids)) {
            return [];
        }
        
        $recent_games = [];
        foreach ($recent_game_ids as $term_id) {
            $game_data = Sisme_Cards_Functions::get_game_data($term_id);
            if ($game_data) {
                $recent_games[] = [
                    'id' => $term_id,
                    'name' => $game_data['name'],
                    'slug' => $game_data['slug'],
                    'cover_url' => $game_data['cover_url'],
                    'game_url' => $game_data['game_url'],
                    'release_date' => $game_data['release_date'],
                    'genres' => array_slice($game_data['genres'], 0, 2) // Max 2 genres
                ];
            }
        }
        
        return $recent_games;
    }
    
    /**
     * Récupérer les jeux favoris de l'utilisateur
     * 
     * @param int $user_id ID utilisateur
     * @param int $limit Nombre de favoris à récupérer
     * @return array Jeux favoris
     */
    public static function get_favorite_games($user_id, $limit = 12) {
        $favorite_game_ids = get_user_meta($user_id, 'sisme_user_favorite_games', true);
        
        if (!is_array($favorite_game_ids) || empty($favorite_game_ids)) {
            return [];
        }
        
        // Limiter le nombre de favoris
        $favorite_game_ids = array_slice($favorite_game_ids, 0, $limit);
        
        $favorite_games = [];
        foreach ($favorite_game_ids as $term_id) {
            $term = get_term($term_id, 'post_tag');
            if ($term && !is_wp_error($term)) {
                // Récupérer les données via Cards si disponible
                if (class_exists('Sisme_Cards_Functions')) {
                    $game_data = Sisme_Cards_Functions::get_game_data($term_id);
                    if ($game_data) {
                        $favorite_games[] = [
                            'id' => $term_id,
                            'name' => $game_data['name'],
                            'slug' => $game_data['slug'],
                            'cover_url' => $game_data['cover_url'],
                            'game_url' => $game_data['game_url']
                        ];
                    }
                } else {
                    // Fallback simple sans Cards
                    $favorite_games[] = [
                        'id' => $term_id,
                        'name' => $term->name,
                        'slug' => $term->slug,
                        'cover_url' => '',
                        'game_url' => get_term_link($term)
                    ];
                }
            }
        }
        
        return $favorite_games;
    }
    
    /**
     * Feed d'activité simple de l'utilisateur
     * 
     * @param int $user_id ID utilisateur
     * @param int $limit Nombre d'éléments
     * @return array Feed d'activité
     */
    public static function get_activity_feed($user_id, $limit = 10) {
        $activities = [];
        
        // Activité 1: Inscription
        $user = get_userdata($user_id);
        if ($user) {
            $activities[] = [
                'type' => 'register',
                'icon' => '🎮',
                'message' => 'Vous avez créé votre compte gaming',
                'date' => $user->user_registered,
                'timestamp' => strtotime($user->user_registered)
            ];
        }
        
        // Activité 2: Dernière connexion
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
        
        // Activité 3: Favoris récents
        $favorite_games = get_user_meta($user_id, 'sisme_user_favorite_games', true);
        if (!empty($favorite_games) && is_array($favorite_games)) {
            $last_favorite = end($favorite_games);
            $term = get_term($last_favorite, 'post_tag');
            if ($term && !is_wp_error($term)) {
                $activities[] = [
                    'type' => 'favorite',
                    'icon' => '⭐',
                    'message' => 'Ajout de "' . $term->name . '" aux favoris',
                    'date' => current_time('mysql'), // Approximatif
                    'timestamp' => current_time('timestamp') - 3600 // Il y a 1h
                ];
            }
        }
        
        // Trier par timestamp décroissant
        usort($activities, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
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
     * Nettoyer le cache dashboard d'un utilisateur
     * 
     * @param int $user_id ID utilisateur
     * @return bool Succès
     */
    public static function clear_user_dashboard_cache($user_id) {
        $cache_key = "sisme_dashboard_data_{$user_id}";
        $success = delete_transient($cache_key);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Dashboard Data] Cache nettoyé pour utilisateur {$user_id}");
        }
        
        return $success;
    }
    
    /**
     * Nettoyer tous les caches dashboard (maintenance)
     * 
     * @return int Nombre de caches nettoyés
     */
    public static function clear_all_dashboard_caches() {
        global $wpdb;
        
        $deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_sisme_dashboard_data_%' 
             OR option_name LIKE '_transient_timeout_sisme_dashboard_data_%'"
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Dashboard Data] {$deleted} caches dashboard nettoyés");
        }
        
        return $deleted;
    }
    
    /**
     * Initialiser les données de base pour un nouvel utilisateur
     * 
     * @param int $user_id ID utilisateur
     * @return bool Succès
     */
    public static function init_user_dashboard_data($user_id) {
        $default_data = [
            'sisme_user_favorite_games' => [],
            'sisme_user_last_dashboard_visit' => current_time('mysql'),
            'sisme_user_dashboard_created' => current_time('mysql')
        ];
        
        $success = true;
        foreach ($default_data as $meta_key => $meta_value) {
            if (!get_user_meta($user_id, $meta_key, true)) {
                $result = update_user_meta($user_id, $meta_key, $meta_value);
                if (!$result) {
                    $success = false;
                }
            }
        }
        
        if ($success && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Dashboard Data] Données dashboard initialisées pour utilisateur {$user_id}");
        }
        
        return $success;
    }
    
    /**
     * Obtenir des statistiques globales pour debug
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
        
        // Compter le total de favoris
        $total_favorites = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE meta_key = 'sisme_user_favorite_games'"
        );
        
        return [
            'users_with_favorites' => intval($users_with_favorites),
            'total_favorite_entries' => intval($total_favorites),
            'cache_duration_minutes' => self::CACHE_DURATION / 60,
            'cards_module_available' => class_exists('Sisme_Cards_Functions')
        ];
    }
}