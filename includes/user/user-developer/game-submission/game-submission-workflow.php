<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/game-submission/game-submission-workflow.php
 * NOUVEAU FICHIER : Handlers pour approbation/rejet des soumissions
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_Submission_Workflow {
    
    /**
     * Approuver une soumission (Pending → Published)
     */
    public static function approve_submission($submission_id, $user_id, $admin_notes = '') {
        // Charger le data manager
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
        }
        
        // Vérifier que la soumission existe et est en pending
        $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
        
        if (!$submission) {
            return new WP_Error('submission_not_found', 'Soumission introuvable');
        }
        
        if ($submission['status'] !== Sisme_Utils_Users::GAME_STATUS_PENDING) {
            return new WP_Error('invalid_status', 'Cette soumission ne peut pas être approuvée (statut: ' . $submission['status'] . ')');
        }
        
        // Modifier le statut et les métadonnées
        $submission['status'] = Sisme_Utils_Users::GAME_STATUS_PUBLISHED;
        $submission['metadata']['published_at'] = current_time('mysql');
        $submission['metadata']['updated_at'] = current_time('mysql');
        
        // Ajouter les données admin
        $submission['admin_data'] = [
            'admin_user_id' => get_current_user_id(),
            'admin_notes' => sanitize_textarea_field($admin_notes),
            'reviewed_at' => current_time('mysql'),
            'action' => 'approved'
        ];
        
        // Sauvegarder la soumission modifiée
        $result = Sisme_Game_Submission_Data_Manager::update_submission_in_user_data($user_id, $submission);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Recalculer les statistiques
        Sisme_Game_Submission_Data_Manager::update_user_stats($user_id);
        
        // TODO: Créer le post WordPress pour publier le jeu
        // self::create_game_post($submission, $user_id);
        
        // TODO: Envoyer email de notification au développeur
        // self::send_approval_notification($user_id, $submission, $admin_notes);
        
        return true;
    }
    
    /**
     * Rejeter une soumission (Pending → Rejected)
     */
    public static function reject_submission($submission_id, $user_id, $admin_notes = '') {
        // Charger le data manager
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
        }
        
        // Vérifier que la soumission existe et est en pending
        $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
        
        if (!$submission) {
            return new WP_Error('submission_not_found', 'Soumission introuvable');
        }
        
        if ($submission['status'] !== Sisme_Utils_Users::GAME_STATUS_PENDING) {
            return new WP_Error('invalid_status', 'Cette soumission ne peut pas être rejetée (statut: ' . $submission['status'] . ')');
        }
        
        // Modifier le statut et les métadonnées
        $submission['status'] = Sisme_Utils_Users::GAME_STATUS_REJECTED;
        $submission['metadata']['updated_at'] = current_time('mysql');
        
        // Ajouter les données admin
        $submission['admin_data'] = [
            'admin_user_id' => get_current_user_id(),
            'admin_notes' => sanitize_textarea_field($admin_notes),
            'reviewed_at' => current_time('mysql'),
            'action' => 'rejected'
        ];
        
        // Sauvegarder la soumission modifiée
        $result = Sisme_Game_Submission_Data_Manager::update_submission_in_user_data($user_id, $submission);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Recalculer les statistiques
        Sisme_Game_Submission_Data_Manager::update_user_stats($user_id);
        
        // TODO: Envoyer email de notification au développeur
        // self::send_rejection_notification($user_id, $submission, $admin_notes);
        
        return true;
    }
    
    /**
     * Supprimer une soumission (admin uniquement - brouillons)
     */
    public static function delete_submission_admin($submission_id, $user_id) {
        // Charger le data manager
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
        }
        
        // Vérifier que la soumission existe
        $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
        
        if (!$submission) {
            return new WP_Error('submission_not_found', 'Soumission introuvable');
        }
        
        // Seuls les brouillons peuvent être supprimés par l'admin
        if ($submission['status'] !== Sisme_Utils_Users::GAME_STATUS_DRAFT) {
            return new WP_Error('invalid_status', 'Seuls les brouillons peuvent être supprimés (statut actuel: ' . $submission['status'] . ')');
        }
        
        // Supprimer la soumission
        return Sisme_Game_Submission_Data_Manager::delete_submission($user_id, $submission_id);
    }
    
    /**
     * Mettre une soumission en révision (Published/Rejected → Revision)
     */
    public static function request_revision($submission_id, $user_id, $admin_notes = '') {
        // Charger le data manager
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
        }
        
        // Vérifier que la soumission existe
        $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
        
        if (!$submission) {
            return new WP_Error('submission_not_found', 'Soumission introuvable');
        }
        
        $allowed_statuses = [
            Sisme_Utils_Users::GAME_STATUS_PUBLISHED,
            Sisme_Utils_Users::GAME_STATUS_REJECTED
        ];
        
        if (!in_array($submission['status'], $allowed_statuses)) {
            return new WP_Error('invalid_status', 'Cette soumission ne peut pas être mise en révision (statut: ' . $submission['status'] . ')');
        }
        
        // Modifier le statut vers Revision
        $submission['status'] = Sisme_Utils_Users::GAME_STATUS_REVISION;
        $submission['metadata']['updated_at'] = current_time('mysql');
        
        // Ajouter les données admin
        $submission['admin_data'] = [
            'admin_user_id' => get_current_user_id(),
            'admin_notes' => sanitize_textarea_field($admin_notes),
            'reviewed_at' => current_time('mysql'),
            'action' => 'revision_requested'
        ];
        
        // Sauvegarder la soumission modifiée
        $result = Sisme_Game_Submission_Data_Manager::update_submission_in_user_data($user_id, $submission);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Recalculer les statistiques
        Sisme_Game_Submission_Data_Manager::update_user_stats($user_id);
        
        // TODO: Envoyer email de notification au développeur
        // self::send_revision_notification($user_id, $submission, $admin_notes);
        
        return true;
    }
    
    /**
     * Créer un post WordPress pour le jeu approuvé (TODO)
     */
    private static function create_game_post($submission, $user_id) {
        // TODO: Implémenter la création du post WordPress
        // - Créer le post avec les données du jeu
        // - Assigner les catégories appropriées
        // - Gérer les images (covers)
        // - Définir l'auteur comme le développeur
        
        return false;
    }
    
    /**
     * Envoyer notification d'approbation (TODO)
     */
    private static function send_approval_notification($user_id, $submission, $admin_notes) {
        // TODO: Implémenter l'envoi d'email
        // - Template email d'approbation
        // - Inclure les notes admin si présentes
        // - Lien vers le jeu publié
        
        return false;
    }
    
    /**
     * Envoyer notification de rejet (TODO)
     */
    private static function send_rejection_notification($user_id, $submission, $admin_notes) {
        // TODO: Implémenter l'envoi d'email
        // - Template email de rejet
        // - Inclure les raisons du rejet
        // - Lien pour créer une nouvelle version
        
        return false;
    }
    
    /**
     * Envoyer notification de révision (TODO)
     */
    private static function send_revision_notification($user_id, $submission, $admin_notes) {
        // TODO: Implémenter l'envoi d'email
        // - Template email de demande de révision
        // - Inclure les modifications demandées
        // - Lien pour modifier la soumission
        
        return false;
    }
}