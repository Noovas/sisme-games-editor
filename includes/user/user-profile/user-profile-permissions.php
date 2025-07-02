<?php
/**
 * File: /sisme-games-editor/includes/user/user-profile/user-profile-permissions.php
 * Gestion des permissions et visibilité des profils publics
 * 
 * RESPONSABILITÉ:
 * - Définir niveaux de visibilité (public/privé/amis)
 * - Vérifier permissions d'accès aux profils
 * - Filtrer données selon niveau d'accès
 * - Cache des permissions par session
 * - Dépendance Sisme_Utils_Users pour validation
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Profile_Permissions {
    
    /**
     * Niveaux de visibilité
     */
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_PRIVATE = 'private';
    const VISIBILITY_FRIENDS = 'friends';
    
    /**
     * Meta key pour stocker la visibilité du profil
     */
    const META_PROFILE_VISIBILITY = 'sisme_user_profile_visibility';
    
    /**
     * Sections disponibles dans un profil
     */
    const SECTION_OVERVIEW = 'overview';
    const SECTION_FAVORITES = 'favorites';
    const SECTION_LIBRARY = 'library';
    const SECTION_ACTIVITY = 'activity';
    const SECTION_SETTINGS = 'settings';
    
    /**
     * Vérifier si un utilisateur peut voir le profil d'un autre
     * @param int $viewer_id ID de l'utilisateur qui consulte (0 si non connecté)
     * @param int $profile_user_id ID du propriétaire du profil
     * @return bool Peut voir le profil
     */
    public static function can_view_profile($viewer_id, $profile_user_id) {
        if (!Sisme_Utils_Users::validate_user_id($profile_user_id, 'can_view_profile')) {
            return false;
        }
        if ($viewer_id === $profile_user_id) {
            return true;
        }
        if (current_user_can('manage_users')) {
            return true;
        }
        $visibility = self::get_user_profile_visibility($profile_user_id);
        switch ($visibility) {
            case self::VISIBILITY_PUBLIC:
                return true;
            case self::VISIBILITY_PRIVATE:
                return false;
            case self::VISIBILITY_FRIENDS:
                return self::are_users_friends($viewer_id, $profile_user_id);
            default:
                return true;
        }
    }
    
    /**
     * Obtenir le niveau de visibilité d'un profil utilisateur
     * @param int $user_id ID utilisateur
     * @return string Niveau de visibilité
     */
    public static function get_user_profile_visibility($user_id) {
        $visibility = get_user_meta($user_id, self::META_PROFILE_VISIBILITY, true);
        if (empty($visibility) || !in_array($visibility, self::get_available_visibilities())) {
            return self::VISIBILITY_PUBLIC;
        }
        return $visibility;
    }
    
    /**
     * Définir le niveau de visibilité d'un profil utilisateur
     * @param int $user_id ID utilisateur
     * @param string $visibility Niveau de visibilité
     * @return bool Succès
     */
    public static function set_user_profile_visibility($user_id, $visibility) {
        if (!Sisme_Utils_Users::validate_user_id($user_id, 'set_profile_visibility')) {
            return false;
        }
        if (!in_array($visibility, self::get_available_visibilities())) {
            return false;
        }
        $success = update_user_meta($user_id, self::META_PROFILE_VISIBILITY, $visibility);
        if ($success && defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Profile Permissions] Visibilité mise à jour pour utilisateur {$user_id} : {$visibility}");
        }
        return $success;
    }
    
    /**
     * Vérifier si deux utilisateurs sont amis
     * @param int $user1_id Premier utilisateur
     * @param int $user2_id Deuxième utilisateur
     * @return bool Sont amis
     */
    public static function are_users_friends($user1_id, $user2_id) {
        if (!$user1_id || !$user2_id) {
            return false;
        }
        return false;
    }
    
    /**
     * Obtenir les sections accessibles pour un utilisateur
     * @param int $viewer_id ID utilisateur qui consulte
     * @param int $profile_user_id ID propriétaire du profil
     * @return array Sections accessibles
     */
    public static function get_accessible_sections($viewer_id, $profile_user_id) {
        $all_sections = self::get_available_sections();
        if ($viewer_id === $profile_user_id) {
            return $all_sections;
        }
        if (!self::can_view_profile($viewer_id, $profile_user_id)) {
            return [];
        }
        $public_sections = [
            self::SECTION_OVERVIEW,
            self::SECTION_FAVORITES,
            self::SECTION_LIBRARY,
            self::SECTION_ACTIVITY
        ];
        return $public_sections;
    }
    
    /**
     * Vérifier si une section est accessible
     * @param string $section Section à vérifier
     * @param int $viewer_id ID utilisateur qui consulte
     * @param int $profile_user_id ID propriétaire du profil
     * @return bool Section accessible
     */
    public static function can_access_section($section, $viewer_id, $profile_user_id) {
        $accessible_sections = self::get_accessible_sections($viewer_id, $profile_user_id);
        return in_array($section, $accessible_sections);
    }
    
    /**
     * Filtrer les données du profil selon les permissions
     * @param array $dashboard_data Données complètes du dashboard
     * @param int $viewer_id ID utilisateur qui consulte
     * @param int $profile_user_id ID propriétaire du profil
     * @return array Données filtrées
     */
    public static function filter_profile_data($dashboard_data, $viewer_id, $profile_user_id) {
        if (!self::can_view_profile($viewer_id, $profile_user_id)) {
            return null;
        }
        $filtered_data = $dashboard_data;
        if ($viewer_id !== $profile_user_id) {
            $filtered_data['user_info'] = self::filter_user_info($dashboard_data['user_info'], $viewer_id, $profile_user_id);
            $filtered_data['gaming_stats'] = self::filter_gaming_stats($dashboard_data['gaming_stats'], $viewer_id, $profile_user_id);
            $filtered_data['activity_feed'] = self::filter_activity_feed($dashboard_data['activity_feed'], $viewer_id, $profile_user_id);
        }
        return $filtered_data;
    }
    
    /**
     * Filtrer les informations utilisateur
     * @param array $user_info Informations utilisateur
     * @param int $viewer_id ID utilisateur qui consulte
     * @param int $profile_user_id ID propriétaire du profil
     * @return array Informations filtrées
     */
    public static function filter_user_info($user_info, $viewer_id, $profile_user_id) {
        $filtered = $user_info;
        $visibility = self::get_user_profile_visibility($profile_user_id);
        if ($visibility === self::VISIBILITY_PRIVATE && $viewer_id !== $profile_user_id) {
            $filtered['bio'] = '';
            $filtered['last_login'] = '';
        }
        return $filtered;
    }
    
    /**
     * Filtrer les statistiques gaming
     * @param array $gaming_stats Statistiques gaming
     * @param int $viewer_id ID utilisateur qui consulte
     * @param int $profile_user_id ID propriétaire du profil
     * @return array Statistiques filtrées
     */
    public static function filter_gaming_stats($gaming_stats, $viewer_id, $profile_user_id) {
        $user_preferences = get_user_meta($profile_user_id, 'sisme_user_privacy_show_stats', true);
        if ($user_preferences === 'no' && $viewer_id !== $profile_user_id) {
            return [
                'owned_games' => 0,
                'favorite_games' => 0,
                'level' => 'Privé'
            ];
        }
        return $gaming_stats;
    }
    
    /**
     * Filtrer le feed d'activité
     * @param array $activity_feed Feed d'activité
     * @param int $viewer_id ID utilisateur qui consulte
     * @param int $profile_user_id ID propriétaire du profil
     * @return array Feed filtré
     */
    public static function filter_activity_feed($activity_feed, $viewer_id, $profile_user_id) {
        if ($viewer_id === $profile_user_id) {
            return $activity_feed;
        }
        $filtered_activities = [];
        foreach ($activity_feed as $activity) {
            if (!isset($activity['private']) || !$activity['private']) {
                $filtered_activities[] = $activity;
            }
        }
        return $filtered_activities;
    }
    
    /**
     * Créer le contexte de rendu pour le renderer
     * @param int $viewer_id ID utilisateur qui consulte
     * @param int $profile_user_id ID propriétaire du profil
     * @return array Contexte de rendu
     */
    public static function create_render_context($viewer_id, $profile_user_id) {
        $is_own_profile = ($viewer_id === $profile_user_id);
        $can_view = self::can_view_profile($viewer_id, $profile_user_id);
        $accessible_sections = self::get_accessible_sections($viewer_id, $profile_user_id);
        $hidden_sections = array_diff(self::get_available_sections(), $accessible_sections);
        return [
            'is_public' => !$is_own_profile,
            'viewer_id' => $viewer_id,
            'profile_user_id' => $profile_user_id,
            'can_edit' => $is_own_profile,
            'can_view' => $can_view,
            'show_private_data' => $is_own_profile,
            'accessible_sections' => $accessible_sections,
            'hide_sections' => $hidden_sections,
            'visibility_level' => self::get_user_profile_visibility($profile_user_id)
        ];
    }
    
    /**
     * Obtenir les niveaux de visibilité disponibles
     * @return array Niveaux disponibles
     */
    public static function get_available_visibilities() {
        return [
            self::VISIBILITY_PUBLIC,
            self::VISIBILITY_PRIVATE,
            self::VISIBILITY_FRIENDS
        ];
    }
    
    /**
     * Obtenir les sections disponibles
     * @return array Sections disponibles
     */
    public static function get_available_sections() {
        return [
            self::SECTION_OVERVIEW,
            self::SECTION_FAVORITES,
            self::SECTION_LIBRARY,
            self::SECTION_ACTIVITY,
            self::SECTION_SETTINGS
        ];
    }
    
    /**
     * Obtenir les labels des niveaux de visibilité
     * @return array Labels des niveaux
     */
    public static function get_visibility_labels() {
        return [
            self::VISIBILITY_PUBLIC => 'Public - Visible par tous',
            self::VISIBILITY_PRIVATE => 'Privé - Visible uniquement par moi',
            self::VISIBILITY_FRIENDS => 'Amis - Visible par mes amis uniquement'
        ];
    }
    
    /**
     * Vérifier si l'utilisateur a configuré ses permissions
     * @param int $user_id ID utilisateur
     * @return bool Permissions configurées
     */
    public static function user_has_configured_permissions($user_id) {
        $visibility = get_user_meta($user_id, self::META_PROFILE_VISIBILITY, true);
        return !empty($visibility);
    }
    
    /**
     * Initialiser les permissions par défaut pour un nouvel utilisateur
     * @param int $user_id ID utilisateur
     * @return bool Succès
     */
    public static function init_default_permissions($user_id) {
        if (!Sisme_Utils_Users::validate_user_id($user_id, 'init_permissions')) {
            return false;
        }
        $default_permissions = [
            self::META_PROFILE_VISIBILITY => self::VISIBILITY_PUBLIC,
            'sisme_user_privacy_show_stats' => 'yes',
            'sisme_user_privacy_allow_friend_requests' => 'yes'
        ];
        foreach ($default_permissions as $meta_key => $default_value) {
            if (!get_user_meta($user_id, $meta_key, true)) {
                update_user_meta($user_id, $meta_key, $default_value);
            }
        }
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Profile Permissions] Permissions par défaut initialisées pour utilisateur {$user_id}");
        }
        return true;
    }
    
    /**
     * Obtenir un message d'erreur d'accès personnalisé
     * @param int $viewer_id ID utilisateur qui consulte
     * @param int $profile_user_id ID propriétaire du profil
     * @return string Message d'erreur
     */
    public static function get_access_denied_message($viewer_id, $profile_user_id) {
        if (!get_userdata($profile_user_id)) {
            return 'Cet utilisateur n\'existe pas.';
        }
        $visibility = self::get_user_profile_visibility($profile_user_id);
        $profile_user = get_userdata($profile_user_id);
        $display_name = $profile_user->display_name ?: 'Cet utilisateur';
        switch ($visibility) {
            case self::VISIBILITY_PRIVATE:
                return "{$display_name} a configuré son profil en mode privé.";
            case self::VISIBILITY_FRIENDS:
                if (!$viewer_id) {
                    return "Vous devez être connecté pour voir ce profil.";
                }
                return "{$display_name} ne partage son profil qu'avec ses amis.";
            default:
                return "Vous n'avez pas l'autorisation de voir ce profil.";
        }
    }
    
    /**
     * Obtenir les statistiques des permissions du système
     * @return array Statistiques permissions
     *
    public static function get_permissions_stats() {
        global $wpdb;
        $total_users = wp_count_users()['total_users'];
        $public_profiles = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} 
                 WHERE meta_key = %s AND meta_value = %s",
                self::META_PROFILE_VISIBILITY,
                self::VISIBILITY_PUBLIC
            )
        );
        $private_profiles = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} 
                 WHERE meta_key = %s AND meta_value = %s",
                self::META_PROFILE_VISIBILITY,
                self::VISIBILITY_PRIVATE
            )
        );
        $friends_profiles = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} 
                 WHERE meta_key = %s AND meta_value = %s",
                self::META_PROFILE_VISIBILITY,
                self::VISIBILITY_FRIENDS
            )
        );
        $default_profiles = $total_users - ($public_profiles + $private_profiles + $friends_profiles);
        return [
            'total_users' => $total_users,
            'public_profiles' => $public_profiles,
            'private_profiles' => $private_profiles,
            'friends_profiles' => $friends_profiles,
            'default_profiles' => $default_profiles,
            'configured_percentage' => $total_users > 0 ? round((($total_users - $default_profiles) / $total_users) * 100, 1) : 0
        ];
    }*/
}