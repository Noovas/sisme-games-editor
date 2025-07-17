<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/user-developer-data-manager.php
 * Gestionnaire de données pour le module développeur
 * 
 * RESPONSABILITÉ:
 * - Gérer les métadonnées développeur
 * - Statuts et candidatures
 * - Données profil développeur
 * - Liaison avec les jeux soumis
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Developer_Data_Manager {
    
    /**
     * Récupérer le statut développeur d'un utilisateur
     */
    public static function get_developer_status($user_id) {
        $status = get_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_STATUS, true);
        return $status ?: Sisme_Utils_Users::DEVELOPER_STATUS_NONE;
    }
    
    /**
     * Définir le statut développeur
     */
    public static function set_developer_status($user_id, $status) {
        $valid_statuses = [
            Sisme_Utils_Users::DEVELOPER_STATUS_NONE, 
            Sisme_Utils_Users::DEVELOPER_STATUS_PENDING, 
            Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED, 
            Sisme_Utils_Users::DEVELOPER_STATUS_REJECTED,
            Sisme_Utils_Users::DEVELOPER_STATUS_REVOKED
        ];
        
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        return update_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_STATUS, $status);
    }
    
    /**
     * Récupérer toutes les données développeur
     */
    public static function get_developer_data($user_id) {
        return [
            'status' => self::get_developer_status($user_id),
            'application' => self::get_developer_application($user_id),
            'profile' => self::get_developer_profile($user_id),
            'submitted_games' => self::get_submitted_games($user_id),
            'stats' => self::get_developer_stats($user_id)
        ];
    }
    
    /**
     * Récupérer les données de candidature
     */
    public static function get_developer_application($user_id) {
        $application = get_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_APPLICATION, true);
        
        if (!$application) {
            return null;
        }
        
        // Structure par défaut avec les nouvelles constantes
        return wp_parse_args($application, [
            Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME => '',
            Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION => '',
            Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_WEBSITE => '',
            Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS => [],
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME => '',
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME => '',
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE => '',
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS => '',
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY => '',
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY => '',
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_EMAIL => '',
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_PHONE => '',
            Sisme_Utils_Users::APPLICATION_FIELD_SUBMITTED_DATE => '',
            Sisme_Utils_Users::APPLICATION_FIELD_REVIEWED_DATE => '',
            Sisme_Utils_Users::APPLICATION_FIELD_ADMIN_NOTES => ''
        ]);
    }
    
    /**
     * Récupérer le profil développeur public
     */
    public static function get_developer_profile($user_id) {
        $profile = get_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_PROFILE, true);
        
        if (!$profile) {
            return null;
        }
        
        // Structure par défaut
        return wp_parse_args($profile, [
            Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME => '',
            Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_WEBSITE => '',
            'bio' => '',
            'avatar_studio' => '',
            'verified' => false,
            'public_contact' => ''
        ]);
    }
    
    /**
     * Récupérer les jeux soumis par le développeur
     */
    public static function get_submitted_games($user_id) {
        // Pour l'instant, retourner un tableau vide
        // Sera implémenté quand on aura le système de soumission
        return [];
    }
    
    /**
     * Récupérer les statistiques développeur
     */
    public static function get_developer_stats($user_id) {
        $submitted_games = self::get_submitted_games($user_id);
        
        return [
            'total_games' => count($submitted_games),
            'approved_games' => 0, // À calculer
            'pending_games' => 0, // À calculer
            'total_views' => 0, // À implémenter
            'total_downloads' => 0, // À implémenter
            'join_date' => self::get_developer_join_date($user_id)
        ];
    }
    
    /**
     * Récupérer la date d'approbation développeur
     */
    private static function get_developer_join_date($user_id) {
        $application = self::get_developer_application($user_id);
        
        if ($application && !empty($application['reviewed_date'])) {
            return $application['reviewed_date'];
        }
        
        return null;
    }
    
    /**
     * Sauvegarder une candidature développeur
     */
    public static function save_developer_application($user_id, $application_data) {
        // Nettoyer et valider les données avec les nouvelles constantes
        $clean_data = [
            Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME => sanitize_text_field($application_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME] ?? ''),
            Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION => sanitize_textarea_field($application_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION] ?? ''),
            Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_WEBSITE => esc_url_raw($application_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_WEBSITE] ?? ''),
            Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS => self::sanitize_social_links($application_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS] ?? []),
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME => sanitize_text_field($application_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME] ?? ''),
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME => sanitize_text_field($application_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME] ?? ''),
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE => sanitize_text_field($application_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE] ?? ''),
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS => sanitize_textarea_field($application_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS] ?? ''),
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY => sanitize_text_field($application_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY] ?? ''),
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY => sanitize_text_field($application_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY] ?? ''),
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_EMAIL => sanitize_email($application_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_EMAIL] ?? ''),
            Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_PHONE => sanitize_text_field($application_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_PHONE] ?? ''),
            Sisme_Utils_Users::APPLICATION_FIELD_SUBMITTED_DATE => current_time('Y-m-d H:i:s'),
            Sisme_Utils_Users::APPLICATION_FIELD_REVIEWED_DATE => '',
            Sisme_Utils_Users::APPLICATION_FIELD_ADMIN_NOTES => ''
        ];
        
        // Sauvegarder les données
        $result = update_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_APPLICATION, $clean_data);
        
        if ($result) {
            // Changer le statut vers 'pending'
            self::set_developer_status($user_id, Sisme_Utils_Users::DEVELOPER_STATUS_PENDING);
            
            // Log pour debug
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Developer] Candidature sauvegardée pour l'utilisateur $user_id");
            }
        }
        
        return $result;
    }
    
    /**
     * Nettoyer les liens portfolio
     */
    private static function sanitize_portfolio_links($links) {
        if (!is_array($links)) {
            return [];
        }
        
        $clean_links = [];
        foreach ($links as $link) {
            $clean_link = esc_url_raw($link);
            if (!empty($clean_link)) {
                $clean_links[] = $clean_link;
            }
        }
        
        return $clean_links;
    }
    
    /**
     * Nettoyer les liens sociaux
     */
    private static function sanitize_social_links($social_links) {
        if (!is_array($social_links)) {
            return [];
        }
        
        $clean_social = [];
        $allowed_platforms = ['twitter', 'discord', 'instagram', 'youtube', 'twitch', 'facebook', 'linkedin'];
        
        foreach ($social_links as $platform => $handle) {
            if (in_array($platform, $allowed_platforms) && !empty($handle)) {
                $clean_social[$platform] = sanitize_text_field($handle);
            }
        }
        
        return $clean_social;
    }
    
    /**
     * Vérifier si un utilisateur peut candidater
     */
    public static function can_apply($user_id) {
        $status = self::get_developer_status($user_id);
        
        // Peut candidater seulement si statut 'none' ou 'rejected'
        return in_array($status, [Sisme_Utils_Users::DEVELOPER_STATUS_NONE, Sisme_Utils_Users::DEVELOPER_STATUS_REJECTED]);
    }
    
    /**
     * Vérifier si un utilisateur est développeur approuvé
     */
    public static function is_approved_developer($user_id) {
        return self::get_developer_status($user_id) === Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED;
    }
}