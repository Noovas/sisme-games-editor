<?php
/**
 * File: /sisme-games-editor/includes/utils/utils-developer-roles.php
 * Gestion des rôles et permissions développeur
 * 
 * CORRECTION : Multi-rôles avec ajout/suppression plutôt que remplacement
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Utils_Developer_Roles {
    
    const ROLE_DEVELOPER = 'sisme-dev';
    const CAPABILITY_SUBMIT_GAMES = 'submit_games';
    const CAPABILITY_MANAGE_OWN_GAMES = 'manage_own_games';
    
    /**
     * Créer le rôle développeur lors de l'activation du plugin
     */
    public static function create_developer_role() {
        // Récupérer les capacités de base d'un subscriber
        $subscriber = get_role('subscriber');
        $capabilities = $subscriber ? $subscriber->capabilities : [];
        
        // Ajouter les capacités spécifiques aux développeurs
        $developer_caps = array_merge($capabilities, [
            'read' => true,
            self::CAPABILITY_SUBMIT_GAMES => true,
            self::CAPABILITY_MANAGE_OWN_GAMES => true,
        ]);
        
        // Créer le rôle
        add_role(
            self::ROLE_DEVELOPER,
            'Développeur Sisme',
            $developer_caps
        );
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Developer Roles] Rôle développeur créé avec succès');
        }
    }
    
    /**
     * Supprimer le rôle développeur lors de la désactivation
     */
    public static function remove_developer_role() {
        remove_role(self::ROLE_DEVELOPER);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Developer Roles] Rôle développeur supprimé');
        }
    }
    
    /**
     * Promouvoir un utilisateur au rôle développeur
     * CORRECTION : AJOUTER le rôle au lieu de le remplacer
     */
    public static function promote_to_developer($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // CORRECTION: AJOUTER le rôle développeur (ne pas remplacer)
        $user->add_role(self::ROLE_DEVELOPER);
        
        // Mettre à jour le statut développeur
        update_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_STATUS, Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Developer Roles] Utilisateur $user_id promu développeur (rôle ajouté)");
        }
        
        return true;
    }
    
    /**
     * Révoquer le statut développeur
     * Supprimer SEULEMENT le rôle développeur, garder les autres
     */
    public static function revoke_developer($user_id, $admin_notes = '') {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Supprimer SEULEMENT le rôle développeur (garder admin/subscriber)
        $user->remove_role(self::ROLE_DEVELOPER);
        
        // Si l'utilisateur n'a plus de rôles, lui donner subscriber par défaut
        if (empty($user->roles)) {
            $user->add_role('subscriber');
        }
        
        // Mettre à jour le statut développeur vers "none"
        update_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_STATUS, Sisme_Utils_Users::DEVELOPER_STATUS_REVOKED);
        
        // Mettre à jour les données de candidature
        $application = get_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_APPLICATION, true);
        if ($application) {
            $application[Sisme_Utils_Users::APPLICATION_FIELD_REVIEWED_DATE] = current_time('Y-m-d H:i:s');
            $application[Sisme_Utils_Users::APPLICATION_FIELD_ADMIN_NOTES] = sanitize_textarea_field($admin_notes);
            update_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_APPLICATION, $application);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Developer Roles] Rôle développeur supprimé pour l'utilisateur $user_id (autres rôles conservés)");
        }
        
        return true;
    }

    /**
     * Réactiver un développeur révoqué
     */
    public static function reactivate_developer($user_id, $admin_notes = '') {
        // Vérifier que le statut est bien 'revoked'
        $status = get_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_STATUS, true);
        if ($status !== Sisme_Utils_Users::DEVELOPER_STATUS_REVOKED) {
            return false;
        }
        
        // Réactiver directement (même logique que approve_application)
        return self::approve_application($user_id, $admin_notes);
    }
    
    /**
     * Vérifier si un utilisateur a le rôle développeur
     */
    public static function is_developer($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        return in_array(self::ROLE_DEVELOPER, $user->roles);
    }
    
    /**
     * Vérifier si un utilisateur peut soumettre des jeux
     */
    public static function can_submit_games($user_id) {
        // Vérifier ET le rôle ET le statut
        $has_role = user_can($user_id, self::CAPABILITY_SUBMIT_GAMES);
        $status = get_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_STATUS, true);
        
        return $has_role && ($status === Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED);
    }
    
    /**
     * Approuver une candidature développeur
     */
    public static function approve_application($user_id, $admin_notes = '') {
        // Promouvoir au rôle développeur
        $promoted = self::promote_to_developer($user_id);
        
        if ($promoted) {
            // Mettre à jour les données de candidature
            $application = get_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_APPLICATION, true);
            if ($application) {
                $application[Sisme_Utils_Users::APPLICATION_FIELD_REVIEWED_DATE] = current_time('Y-m-d H:i:s');
                $application[Sisme_Utils_Users::APPLICATION_FIELD_ADMIN_NOTES] = sanitize_textarea_field($admin_notes);
                update_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_APPLICATION, $application);
            }
            
            do_action('sisme_developer_application_approved', $user_id, $admin_notes);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Rejeter une candidature développeur
     */
    public static function reject_application($user_id, $admin_notes = '') {
        // Mettre à jour le statut (SANS toucher au rôle)
        update_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_STATUS, Sisme_Utils_Users::DEVELOPER_STATUS_REJECTED);
        
        // Mettre à jour les données de candidature
        $application = get_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_APPLICATION, true);
        if ($application) {
            $application[Sisme_Utils_Users::APPLICATION_FIELD_REVIEWED_DATE] = current_time('Y-m-d H:i:s');
            $application[Sisme_Utils_Users::APPLICATION_FIELD_ADMIN_NOTES] = sanitize_textarea_field($admin_notes);
            update_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_APPLICATION, $application);
        }
        
        do_action('sisme_developer_application_rejected', $user_id, $admin_notes);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Developer Roles] Candidature $user_id rejetée (rôle inchangé)");
        }
        
        return true;
    }
}

// Fonction d'activation du plugin
function sisme_activate_developer_roles() {
    Sisme_Utils_Developer_Roles::create_developer_role();
}

// Fonction de désactivation du plugin
function sisme_deactivate_developer_roles() {
    Sisme_Utils_Developer_Roles::remove_developer_role();
}

// Enregistrer les hooks d'activation/désactivation
add_action('init', function() {
    // Créer le rôle si il n'existe pas
    if (!get_role(Sisme_Utils_Developer_Roles::ROLE_DEVELOPER)) {
        Sisme_Utils_Developer_Roles::create_developer_role();
    }
});