<?php
/**
 * File: /sisme-games-editor/admin/assets/PHP-admin-user-getter.php
 * Gestionnaire centralisé pour la récupération des données utilisateur dans l'admin
 * 
 * RESPONSABILITÉ:
 * - Fonctions centralisées pour récupérer les données utilisateur
 * - Indépendant de tous les autres modules existants
 * - Formatage des données pour l'interface d'administration
 * - Gestion des fallbacks et vérifications de sécurité
 * - Support des filtres et recherches avancées
 * 
 * DÉPENDANCES:
 * Utilise uniquement les APIs WordPress natives
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_User_Getter {
    /**
     * Constantes pour les clés de métadonnées utilisateur
     */
    // Préférences utilisateur
    const META_PLATFORMS = 'sisme_user_platforms';
    const META_GENRES = 'sisme_user_genres';
    const META_PLAYER_TYPES = 'sisme_user_player_types';
    const META_NOTIFICATIONS = 'sisme_user_notifications';
    const META_PRIVACY_PUBLIC = 'sisme_user_privacy_public';
    const META_AVATAR = 'sisme_user_avatar';
    
    // Données développeur
    const META_DEVELOPER_STATUS = 'sisme_user_developer_status';
    const META_DEVELOPER_APPLICATION = 'sisme_user_developer_application';
    const META_DEVELOPER_PROFILE = 'sisme_user_developer_profile';
    
    // Collections de jeux
    const META_FAVORITE_GAMES = 'sisme_user_favorite_games';
    const META_OWNED_GAMES = 'sisme_user_owned_games';
    const META_WISHLIST_GAMES = 'sisme_user_wishlist_games';
    const META_COMPLETED_GAMES = 'sisme_user_completed_games';
    
    // Données sociales
    const META_FRIENDS_LIST = 'sisme_user_friends_list';
    
    // Données dashboard
    const META_LAST_LOGIN = 'sisme_user_last_login';
    const META_PROFILE_CREATED = 'sisme_user_profile_created';
    const META_PROFILE_VISIBILITY = 'sisme_user_profile_visibility';

    
    
    // ===== AVATARS DISPONIBLES =====
    const SISME_ROOT_URL = 'https://games.sisme.fr/preprod/';  
    const AVATARS_URL = self::SISME_ROOT_URL . 'images/avatar/avatar-user-';
    const AVATARS_USER_URL = [
        'default' => self::AVATARS_URL . 'borne-arcade.png',
        'borne-arcade' => self::AVATARS_URL . 'borne-arcade.png',
        'cd-rom' => self::AVATARS_URL . 'cd-rom.png',
        'clavier' => self::AVATARS_URL . 'clavier.png',
        'flipper' => self::AVATARS_URL . 'flipper.png',
        'gameboy' => self::AVATARS_URL . 'gameboy.png',
        'joystick' => self::AVATARS_URL . 'joystick.png',
        'manette' => self::AVATARS_URL . 'manette.png',
        'tourne-disque' => self::AVATARS_URL . 'tourne-disque.png'
    ];

    /**
     * Vérifier les permissions d'accès admin
     * 
     * @return bool True si autorisé
     */
    private static function check_admin_permissions() {
        // Vérifier que l'utilisateur est connecté
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Vérifier les capacités admin
        if (!current_user_can('manage_options') && !current_user_can('list_users')) {
            return false;
        }
        
        // Vérifier le contexte admin
        if (!is_admin()) {
            return false;
        }
        
        return true;
    }

    /**
     * Vérifier si l'utilisateur peut voir les données sensibles
     * 
     * @param int $target_user_id ID de l'utilisateur cible
     * @return bool True si autorisé
     */
    private static function can_view_user_data($target_user_id) {
        $current_user_id = get_current_user_id();
        
        // L'utilisateur peut voir ses propres données
        if ($current_user_id === $target_user_id) {
            return true;
        }
        
        // Les admins peuvent voir toutes les données
        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Les utilisateurs avec capacité 'edit_users' peuvent voir les données de base
        if (current_user_can('edit_users')) {
            return true;
        }
        
        return false;
    }

    /**
     * Nettoyer les données sensibles selon les permissions
     * 
     * @param array $user_data Données utilisateur
     * @param int $target_user_id ID utilisateur cible
     * @return array Données nettoyées
     */
    private static function sanitize_user_data_for_permissions($user_data, $target_user_id) {
        $current_user_id = get_current_user_id();
        $is_admin = current_user_can('manage_options');
        $is_self = ($current_user_id === $target_user_id);
        
        // Si admin ou soi-même, retourner toutes les données
        if ($is_admin || $is_self) {
            return $user_data;
        }
        
        // Sinon, supprimer les données sensibles
        $safe_data = $user_data;
        
        // Masquer l'email si pas admin
        if (isset($safe_data['user_email'])) {
            $safe_data['user_email'] = '***@***.***';
        }
        
        // Supprimer les données développeur sensibles
        if (isset($safe_data['developer']['application'])) {
            unset($safe_data['developer']['application']);
        }
        
        // Supprimer les détails des collections de jeux
        if (isset($safe_data['gaming_stats']['collections'])) {
            unset($safe_data['gaming_stats']['collections']);
        }
        
        // Supprimer la liste d'amis détaillée
        if (isset($safe_data['social_stats']['friends_list'])) {
            unset($safe_data['social_stats']['friends_list']);
        }
        
        // Supprimer les préférences personnelles
        if (isset($safe_data['preferences'])) {
            unset($safe_data['preferences']);
        }
        
        return $safe_data;
    }
    
    /**
     * Récupérer une liste d'utilisateurs avec filtres et pagination
     * 
     * @param array $args Arguments de filtrage
     * @return array Liste des utilisateurs avec métadonnées de base
     * Structure complète du retour (user_data et autres):
     * [
     *   'users' => [
     *     [
     *       'ID' => int ID de l'utilisateur
     *       'display_name' => string Nom affiché de l'utilisateur
     *       'user_login' => string Identifiant de connexion de l'utilisateur
     *       'user_email' => string Email de l'utilisateur
     *       'user_nicename' => string Nom convivial de l'utilisateur
     *       'user_registered' => string Date d'enregistrement de l'utilisateur
     *       'roles' => array Rôles de l'utilisateur
     *       'meta' => array Liste des métadonnées de l'utilisateur
     *         Keys possibles :
     *         - user_email: Email de l'utilisateur
     *         - user_registered: Date d'enregistrement
     *         - roles: Rôles de l'utilisateur
     *     ]
     *   ]
     */
    public static function sisme_admin_get_users($args = []) {
        // Vérification des permissions
        if (!self::check_admin_permissions()) {
            return [
                'error' => 'Permissions insuffisantes',
                'users' => [],
                'total' => 0,
                'found' => 0
            ];
        }
        $defaults = [
            'role' => 'all',
            'number' => 9999,
            'offset' => 0,
            'search' => '',
            'orderby' => 'registered',
            'order' => 'DESC',
            'meta_query' => [],
            'include_meta' => true
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        // Construire les arguments pour WP_User_Query
        $user_args = [
            'number' => $args['number'],
            'offset' => $args['offset'],
            'orderby' => $args['orderby'],
            'order' => $args['order']
        ];
        
        // Filtrer par rôle si spécifié
        if ($args['role'] !== 'all') {
            $user_args['role'] = $args['role'];
        }
        
        // Recherche par nom/email
        if (!empty($args['search'])) {
            $user_args['search'] = '*' . esc_attr($args['search']) . '*';
            $user_args['search_columns'] = ['display_name', 'user_email', 'user_login'];
        }
        
        // Meta query personnalisée
        if (!empty($args['meta_query'])) {
            $user_args['meta_query'] = $args['meta_query'];
        }
        
        $user_query = new WP_User_Query($user_args);
        $users = $user_query->get_results();
        
        $result = [
            'users' => [],
            'total' => $user_query->get_total(),
            'found' => count($users)
        ];
        
        foreach ($users as $user) {
            $user_data = [
                'ID' => $user->ID,
                'display_name' => $user->display_name,
                'user_login' => $user->user_login,
                'user_email' => $user->user_email,
                'user_nicename' => $user->user_nicename,
                'user_registered' => $user->user_registered,
                'roles' => $user->roles
            ];
            
            // Ajouter les métadonnées de base si demandé
            if ($args['include_meta']) {
                $user_data['meta'] = self::get_user_basic_meta($user->ID);
            }
            
            $result['users'][] = $user_data;
        }
        
        return $result;
    }

    /**
     * Récupérer toutes les données d'un utilisateur (base + métadonnées personnalisées)
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array|null Données complètes de l'utilisateur ou null si non trouvé
     */
    public static function sisme_admin_get_user_data($user_id) {
        // Vérification des permissions
        if (!self::can_view_user_data($user_id)) {
            return [
                'error' => 'Vous n\'avez pas l\'autorisation de voir ces données',
                'user_id' => $user_id
            ];
        }
        
        if (!self::validate_user_id($user_id)) {
            return null;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return null;
        }
        
        // Données de base WordPress
        $user_data = [
            'ID' => $user->ID,
            'display_name' => $user->display_name,
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'user_nicename' => $user->user_nicename,
            'user_registered' => $user->user_registered,
            'user_status' => $user->user_status,
            'roles' => $user->roles,
            'capabilities' => array_keys($user->allcaps)
        ];
        
        // Métadonnées personnalisées
        $user_data['preferences'] = self::sisme_admin_get_user_preferences($user_id);
        $user_data['developer'] = self::get_user_developer_data($user_id);
        $user_data['gaming_stats'] = self::get_user_gaming_stats($user_id);
        $user_data['social_stats'] = self::get_user_social_stats($user_id);
        $user_data['profile_settings'] = self::get_user_profile_settings($user_id);
        $user_data['activity'] = self::get_user_activity_summary($user_id);
        
        // Nettoyer les données selon les permissions
        $user_data = self::sanitize_user_data_for_permissions($user_data, $user_id);
        
        return $user_data;
    }

    /**
     * Récupérer les données développeur d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Données développeur
     */
    public static function get_user_developer_data($user_id) {
        if (!self::validate_user_id($user_id)) {
            return [];
        }
        
        // Vérifier si on peut voir les données développeur sensibles
        $can_view_sensitive = self::can_view_user_data($user_id);
        
        $status = get_user_meta($user_id, self::META_DEVELOPER_STATUS, true) ?: 'none';
        $application = $can_view_sensitive ? (get_user_meta($user_id, self::META_DEVELOPER_APPLICATION, true) ?: []) : [];
        $profile = get_user_meta($user_id, self::META_DEVELOPER_PROFILE, true) ?: [];
        
        // Vérifier si l'utilisateur a le rôle développeur
        $user = get_userdata($user_id);
        $has_role = $user ? in_array('sisme_developer', $user->roles) : false;
        
        $developer_data = [
            'status' => $status,
            'has_role' => $has_role,
            'profile' => $profile,
            'is_active' => ($status === 'approved' && $has_role)
        ];
        
        // Ajouter les données sensibles seulement si autorisé
        if ($can_view_sensitive) {
            $developer_data['application'] = $application;
            $developer_data['submitted_date'] = isset($application['submitted_date']) ? $application['submitted_date'] : null;
            $developer_data['reviewed_date'] = isset($application['reviewed_date']) ? $application['reviewed_date'] : null;
            $developer_data['admin_notes'] = isset($application['admin_notes']) ? $application['admin_notes'] : '';
        }
        
        return $developer_data;
    }

    /**
     * Récupérer les statistiques gaming d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Statistiques gaming
     * Structure de retour :
     * /*[
     *     'favorite_games' => int,
     *     'owned_games' => int,
     *     'wishlist_games' => int,
     *     'completed_games' => int,
     *     'total_unique_games' => int,
     *     'collections' => [
     *         'favorites' => array,
     *         'owned' => array,
     *         'wishlist' => array,
     *         'completed' => array
     *     ],
     *     'last_activity' => array
     * ]
     */
    public static function get_user_gaming_stats($user_id) {
        if (!self::validate_user_id($user_id)) {
            return [];
        }
        
        // Récupérer les collections de jeux
        $favorite_games = get_user_meta($user_id, self::META_FAVORITE_GAMES, true) ?: [];
        $owned_games = get_user_meta($user_id, self::META_OWNED_GAMES, true) ?: [];
        $wishlist_games = get_user_meta($user_id, self::META_WISHLIST_GAMES, true) ?: [];
        $completed_games = get_user_meta($user_id, self::META_COMPLETED_GAMES, true) ?: [];
        
        // Traitement des données selon le format (ancien vs nouveau système avec timestamps)
        $favorite_count = self::count_game_collection($favorite_games);
        $owned_count = self::count_game_collection($owned_games);
        $wishlist_count = self::count_game_collection($wishlist_games);
        $completed_count = self::count_game_collection($completed_games);
        
        // Calcul des jeux uniques
        $all_games = array_merge(
            self::extract_game_ids($favorite_games),
            self::extract_game_ids($owned_games),
            self::extract_game_ids($wishlist_games),
            self::extract_game_ids($completed_games)
        );
        $unique_games = array_unique($all_games);
        
        return [
            'favorite_games' => $favorite_count,
            'owned_games' => $owned_count,
            'wishlist_games' => $wishlist_count,
            'completed_games' => $completed_count,
            'total_unique_games' => count($unique_games),
            'collections' => [
                'favorites' => $favorite_games,
                'owned' => $owned_games,
                'wishlist' => $wishlist_games,
                'completed' => $completed_games
            ],
            'last_activity' => self::get_last_gaming_activity($user_id)
        ];
    }

    /**
     * Récupérer les statistiques sociales d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Statistiques sociales
     */
    public static function get_user_social_stats($user_id) {
        if (!self::validate_user_id($user_id)) {
            return [];
        }
        
        $friends_list = get_user_meta($user_id, self::META_FRIENDS_LIST, true) ?: [];
        
        if (!is_array($friends_list)) {
            $friends_list = [];
        }
        
        $friends_count = 0;
        $pending_requests = 0;
        $sent_requests = 0;
        
        foreach ($friends_list as $friend_id => $data) {
            if (is_array($data) && isset($data['status'])) {
                if ($data['status'] === 'accepted') {
                    $friends_count++;
                } elseif ($data['status'] === 'pending') {
                    $pending_requests++;
                }
            } elseif (is_numeric($friend_id)) {
                // Format ancien (simple array d'IDs)
                $friends_count++;
            }
        }
        
        // Compter les demandes envoyées (chercher cet utilisateur dans les listes des autres)
        global $wpdb;
        $sent_requests_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE meta_key = %s 
             AND meta_value LIKE %s",
            self::META_FRIENDS_LIST,
            '%"' . $user_id . '"%pending%'
        );
        $sent_requests = $wpdb->get_var($sent_requests_query) ?: 0;
        
        return [
            'friends_count' => $friends_count,
            'pending_requests' => $pending_requests,
            'sent_requests' => $sent_requests,
            'friends_list' => $friends_list,
            'profile_visibility' => get_user_meta($user_id, self::META_PROFILE_VISIBILITY, true) ?: 'public'
        ];
    }

    /**
     * Récupérer les préférences utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Préférences utilisateur
     * 
     * Format de sortie :
     * [
     *   'platforms' => [],
     *   'genres' => [],
     *   'player_types' => [],
     *   'notifications' => [],
     *   'privacy_public' => true,
     *   'avatar' => url
     * ]
     */
    public static function sisme_admin_get_user_preferences($user_id) {
        if (!self::validate_user_id($user_id)) {
            return [];
        }
        
        return [
            'platforms' => get_user_meta($user_id, self::META_PLATFORMS, true) ?: [],
            'genres' => get_user_meta($user_id, self::META_GENRES, true) ?: [],
            'player_types' => get_user_meta($user_id, self::META_PLAYER_TYPES, true) ?: [],
            'notifications' => get_user_meta($user_id, self::META_NOTIFICATIONS, true) ?: [],
            'privacy_public' => get_user_meta($user_id, self::META_PRIVACY_PUBLIC, true) !== '' ? 
                               (bool) get_user_meta($user_id, self::META_PRIVACY_PUBLIC, true) : true,
            'avatar' => self::AVATARS_USER_URL[get_user_meta($user_id, self::META_AVATAR, true)] ?: self::AVATARS_USER_URL['default']
        ];
    }

    /**
     * Récupérer les paramètres de profil
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Paramètres de profil
     */
    public static function get_user_profile_settings($user_id) {
        if (!self::validate_user_id($user_id)) {
            return [];
        }
        
        return [
            'profile_visibility' => get_user_meta($user_id, self::META_PROFILE_VISIBILITY, true) ?: 'public',
            'show_stats' => get_user_meta($user_id, 'sisme_user_privacy_show_stats', true) !== 'no',
            'allow_friend_requests' => get_user_meta($user_id, 'sisme_user_privacy_allow_friend_requests', true) !== 'no',
            'last_login' => get_user_meta($user_id, self::META_LAST_LOGIN, true),
            'profile_created' => get_user_meta($user_id, self::META_PROFILE_CREATED, true)
        ];
    }

    /**
     * Récupérer un résumé de l'activité utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Résumé d'activité
     */
    public static function get_user_activity_summary($user_id) {
        if (!self::validate_user_id($user_id)) {
            return [];
        }
        
        $user = get_userdata($user_id);
        
        return [
            'registration_date' => $user->user_registered,
            'last_login' => get_user_meta($user_id, self::META_LAST_LOGIN, true),
            'profile_created' => get_user_meta($user_id, self::META_PROFILE_CREATED, true),
            'total_posts' => count_user_posts($user_id),
            'total_comments' => get_comments(['user_id' => $user_id, 'count' => true]),
            'is_active' => self::is_user_recently_active($user_id)
        ];
    }

    /**
     * Récupérer les métadonnées de base d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Métadonnées de base
     */
    private static function get_user_basic_meta($user_id) {
        return [
            'last_login' => get_user_meta($user_id, self::META_LAST_LOGIN, true),
            'profile_visibility' => get_user_meta($user_id, self::META_PROFILE_VISIBILITY, true) ?: 'public',
            'developer_status' => get_user_meta($user_id, self::META_DEVELOPER_STATUS, true) ?: 'none',
            'total_games' => self::count_total_user_games($user_id)
        ];
    }

    /**
     * Compter le total de jeux d'un utilisateur (toutes collections)
     * 
     * @param int $user_id ID de l'utilisateur
     * @return int Nombre total de jeux
     */
    private static function count_total_user_games($user_id) {
        $favorite_games = get_user_meta($user_id, self::META_FAVORITE_GAMES, true) ?: [];
        $owned_games = get_user_meta($user_id, self::META_OWNED_GAMES, true) ?: [];
        $wishlist_games = get_user_meta($user_id, self::META_WISHLIST_GAMES, true) ?: [];
        $completed_games = get_user_meta($user_id, self::META_COMPLETED_GAMES, true) ?: [];
        
        $all_games = array_merge(
            self::extract_game_ids($favorite_games),
            self::extract_game_ids($owned_games),
            self::extract_game_ids($wishlist_games),
            self::extract_game_ids($completed_games)
        );
        
        return count(array_unique($all_games));
    }

    /**
     * Compter les éléments d'une collection (gère ancien et nouveau format)
     * 
     * @param mixed $collection Collection de jeux
     * @return int Nombre d'éléments
     */
    private static function count_game_collection($collection) {
        if (empty($collection) || !is_array($collection)) {
            return 0;
        }
        
        // Nouveau format avec timestamps : [game_id => ['added_at' => date]]
        // Ancien format : [game_id1, game_id2, ...]
        return count($collection);
    }

    /**
     * Extraire les IDs de jeux d'une collection (gère ancien et nouveau format)
     * 
     * @param mixed $collection Collection de jeux
     * @return array IDs des jeux
     */
    private static function extract_game_ids($collection) {
        if (empty($collection) || !is_array($collection)) {
            return [];
        }
        
        // Détecter le format
        $first_key = array_key_first($collection);
        $first_value = $collection[$first_key];
        
        if (is_array($first_value) && isset($first_value['added_at'])) {
            // Nouveau format : clés = IDs, valeurs = metadata
            return array_keys($collection);
        } else {
            // Ancien format : valeurs = IDs
            return array_values($collection);
        }
    }

    /**
     * Récupérer la dernière activité gaming
     * 
     * @param int $user_id ID de l'utilisateur
     * @return string|null Date de dernière activité
     */
    private static function get_last_gaming_activity($user_id) {
        $collections = [
            self::META_FAVORITE_GAMES,
            self::META_OWNED_GAMES,
            self::META_WISHLIST_GAMES,
            self::META_COMPLETED_GAMES
        ];
        
        $latest_date = null;
        
        foreach ($collections as $meta_key) {
            $collection = get_user_meta($user_id, $meta_key, true);
            if (is_array($collection)) {
                foreach ($collection as $game_data) {
                    if (is_array($game_data) && isset($game_data['added_at'])) {
                        if (!$latest_date || strtotime($game_data['added_at']) > strtotime($latest_date)) {
                            $latest_date = $game_data['added_at'];
                        }
                    }
                }
            }
        }
        
        return $latest_date;
    }

    /**
     * Vérifier si un utilisateur est récemment actif
     * 
     * @param int $user_id ID de l'utilisateur
     * @return bool True si actif récemment
     */
    private static function is_user_recently_active($user_id) {
        $last_login = get_user_meta($user_id, self::META_LAST_LOGIN, true);
        if ($last_login) {
            $last_login_timestamp = strtotime($last_login);
            $thirty_days_ago = strtotime('-30 days');
            return $last_login_timestamp > $thirty_days_ago;
        }
        return false;
    }

    /**
     * Valider qu'un ID utilisateur est valide
     * 
     * @param int $user_id ID de l'utilisateur
     * @return bool True si valide
     */
    private static function validate_user_id($user_id) {
        return !empty($user_id) && is_numeric($user_id) && $user_id > 0 && get_userdata($user_id);
    }

    /**
     * Rechercher des utilisateurs par critères avancés
     * 
     * @param array $search_args Critères de recherche
     * @return array Résultats de recherche
     */
    public static function search_users_advanced($search_args = []) {
        // Vérification des permissions
        if (!self::check_admin_permissions()) {
            return [
                'error' => 'Permissions insuffisantes pour la recherche avancée',
                'users' => [],
                'total_found' => 0,
                'total_possible' => 0
            ];
        }
        $defaults = [
            'search_term' => '',
            'search_in' => ['display_name', 'user_email'],
            'role' => '',
            'developer_status' => '',
            'has_games' => false,
            'is_active' => false,
            'limit' => 20
        ];
        
        $args = wp_parse_args($search_args, $defaults);
        
        $user_query_args = [
            'number' => $args['limit'],
            'orderby' => 'display_name',
            'order' => 'ASC'
        ];
        
        // Recherche textuelle
        if (!empty($args['search_term'])) {
            $user_query_args['search'] = '*' . esc_attr($args['search_term']) . '*';
            $user_query_args['search_columns'] = $args['search_in'];
        }
        
        // Filtrage par rôle
        if (!empty($args['role'])) {
            $user_query_args['role'] = $args['role'];
        }
        
        // Meta queries
        $meta_query = [];
        
        if (!empty($args['developer_status'])) {
            $meta_query[] = [
                'key' => self::META_DEVELOPER_STATUS,
                'value' => $args['developer_status'],
                'compare' => '='
            ];
        }
        
        if ($args['is_active']) {
            $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
            $meta_query[] = [
                'key' => self::META_LAST_LOGIN,
                'value' => $thirty_days_ago,
                'compare' => '>',
                'type' => 'DATETIME'
            ];
        }
        
        if (!empty($meta_query)) {
            $user_query_args['meta_query'] = $meta_query;
        }
        
        $user_query = new WP_User_Query($user_query_args);
        $users = $user_query->get_results();
        
        $results = [];
        foreach ($users as $user) {
            $user_data = [
                'ID' => $user->ID,
                'display_name' => $user->display_name,
                'user_email' => $user->user_email,
                'roles' => $user->roles,
                'developer_status' => get_user_meta($user->ID, self::META_DEVELOPER_STATUS, true) ?: 'none',
                'total_games' => self::count_total_user_games($user->ID),
                'last_login' => get_user_meta($user->ID, self::META_LAST_LOGIN, true),
                'is_active' => self::is_user_recently_active($user->ID)
            ];
            
            // Filtrer par présence de jeux si demandé
            if ($args['has_games'] && $user_data['total_games'] == 0) {
                continue;
            }
            
            $results[] = $user_data;
        }
        
        return [
            'users' => $results,
            'total_found' => count($results),
            'total_possible' => $user_query->get_total()
        ];
    }

    /**
     * Obtenir des statistiques globales sur les utilisateurs
     * 
     * @return array Statistiques globales
     */
    public static function get_users_global_stats() {
        // Vérification des permissions - seuls les admins peuvent voir les stats globales
        if (!current_user_can('manage_options')) {
            return [
                'error' => 'Permissions insuffisantes pour voir les statistiques globales'
            ];
        }
        
        global $wpdb;
        
        // Statistiques de base
        $total_users = count_users();
        
        // Développeurs
        $developers_approved = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
            self::META_DEVELOPER_STATUS, 'approved'
        ));
        
        $developers_pending = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
            self::META_DEVELOPER_STATUS, 'pending'
        ));
        
        // Utilisateurs avec des jeux
        $users_with_games = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} 
             WHERE meta_key IN (%s, %s, %s, %s) 
             AND meta_value != '' AND meta_value != 'a:0:{}'",
            self::META_FAVORITE_GAMES, self::META_OWNED_GAMES, 
            self::META_WISHLIST_GAMES, self::META_COMPLETED_GAMES
        ));
        
        // Utilisateurs actifs (connectés dans les 30 derniers jours)
        $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
        $active_users = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->usermeta} 
             WHERE meta_key = %s AND meta_value > %s",
            self::META_LAST_LOGIN, $thirty_days_ago
        ));

        // Nombre total de jeux favoris (non distincts)
        $favoris = 0;
        $fav_results = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
            self::META_FAVORITE_GAMES
        ));
        foreach ($fav_results as $meta_value) {
            $games = maybe_unserialize($meta_value);
            if (is_array($games)) {
                $favoris += count($games);
            }
        }

        // Nombre total de jeux possédés (non distincts)
        $owned = 0;
        $owned_results = $wpdb->get_col($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
            self::META_OWNED_GAMES
        ));
        foreach ($owned_results as $meta_value) {
            $games = maybe_unserialize($meta_value);
            if (is_array($games)) {
                $owned += count($games);
            }
        }

        // Nombre de liens sociaux (paires d'amis uniques)
        $pairs = [];
        $friends_results = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
            self::META_FRIENDS_LIST
        ));
        foreach ($friends_results as $row) {
            $user_id = $row->user_id;
            $friends = maybe_unserialize($row->meta_value);
            if (is_array($friends)) {
                foreach ($friends as $friend_id => $data) {
                    if (is_numeric($friend_id) && isset($data['status']) && $data['status'] === 'accepted') {
                        $pair = [$user_id, $friend_id];
                        sort($pair);
                        $pairs[implode('-', $pair)] = true;
                    }
                }
            }
        }
        $social_links = count($pairs);
        
        return [
            'total_users' => $total_users['total_users'],
            'users_by_role' => $total_users['avail_roles'],
            'developers_approved' => (int) $developers_approved,
            'developers_pending' => (int) $developers_pending,
            'users_with_games' => (int) $users_with_games,
            'active_users_30_days' => (int) $active_users,
            'registration_trend' => self::get_registration_trend(),
            'users_favoris_games' => $favoris,
            'users_owned_games' => $owned,
            'users_social_links' => $social_links,
        ];
    }

    /**
     * Obtenir la tendance d'inscription (derniers 30 jours)
     * 
     * @return array Données de tendance
     */
    private static function get_registration_trend() {
        global $wpdb;
        
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        
        $registrations = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(user_registered) as date, COUNT(*) as count 
             FROM {$wpdb->users} 
             WHERE user_registered >= %s 
             GROUP BY DATE(user_registered) 
             ORDER BY date ASC",
            $thirty_days_ago
        ));
        
        $trend = [];
        foreach ($registrations as $reg) {
            $trend[$reg->date] = (int) $reg->count;
        }
        
        return $trend;
    }

    /**
     * Compter tous les jeux favoris
     * 
     * @return int Nombre total de jeux favoris
     */
    public static function count_all_favorite_games() {
        global $wpdb;
        $meta_key = self::META_FAVORITE_GAMES;
        $total = 0;

        // Récupérer toutes les valeurs de la meta favorite games
        $results = $wpdb->get_col( $wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s",
            $meta_key
        ));

        foreach ($results as $meta_value) {
            $games = maybe_unserialize($meta_value);
            if (is_array($games)) {
                $total += count($games);
            }
        }
        return $total;
    }
}