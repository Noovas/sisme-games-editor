<?php
/**
 * File: /sisme-games-editor/includes/utils/utils-media-cleanup.php
 * Gestionnaire de nettoyage des médias orphelins
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Utils_Media_Cleanup {
    /**
     * Initialiser les hooks
     */
    public static function init() {
        // Nettoyage lorsqu'une soumission est supprimée
        add_action('sisme_before_submission_delete', array(__CLASS__, 'cleanup_submission_media'));
        
        // Nettoyage programmé des médias orphelins
        add_action('sisme_cleanup_orphaned_media', array(__CLASS__, 'cleanup_orphaned_media'));
        
        // Planifier le nettoyage quotidien
        if (!wp_next_scheduled('sisme_cleanup_orphaned_media')) {
            wp_schedule_event(time(), 'daily', 'sisme_cleanup_orphaned_media');
        }
    }
    
    /**
     * Nettoyer les médias d'une soumission avant sa suppression
     */
    public static function cleanup_submission_media($submission_id) {
        // Le submission_id est en fait le user_id et l'id de soumission
        list($user_id, $submission_id) = explode('_', $submission_id);
        $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
        if (!$submission || empty($submission['game_data'])) return;
        $game_data = $submission['game_data'];
        $media_ids = [];

        // Covers
        if (!empty($game_data['covers'])) {
            foreach ($game_data['covers'] as $cover_id) {
                if ($cover_id && is_numeric($cover_id)) {
                    $media_ids[] = intval($cover_id);
                }
            }
        }

        // Screenshots
        if (!empty($game_data['screenshots'])) {
            foreach ($game_data['screenshots'] as $screenshot_id) {
                if ($screenshot_id && is_numeric($screenshot_id)) {
                    $media_ids[] = intval($screenshot_id);
                }
            }
        }

        // Images de sections
        if (!empty($game_data[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION_SECTIONS])) {
            foreach ($game_data[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION_SECTIONS] as $section) {
                $img_id = $section['image_attachment_id'] ?? null;
                if ($img_id && is_numeric($img_id)) {
                    $media_ids[] = intval($img_id);
                }
            }
        }

        // Suppression en une seule boucle
        foreach (array_unique($media_ids) as $media_id) {
            wp_delete_attachment($media_id, true);
        }
    }
    
    /**
     * Nettoyer les médias orphelins (plus utilisés dans aucune soumission)
     */
    public static function cleanup_orphaned_media() {
        global $wpdb;
        
        // Récupérer tous les IDs des médias avec les métadonnées du ratio
        $media_with_ratio = $wpdb->get_col("
            SELECT DISTINCT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_sisme_ratio_type'
        ");
        
        if (empty($media_with_ratio)) return;
        
        // Récupérer tous les utilisateurs qui ont des soumissions
        $users = get_users([
            'meta_key' => Sisme_Utils_Users::META_GAME_SUBMISSIONS,
            'meta_compare' => 'EXISTS'
        ]);
        
        $used_media_ids = array();
        
        // Pour chaque utilisateur, récupérer ses soumissions
        foreach ($users as $user) {
            $submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user->ID);
            
            // Collecter tous les IDs de médias utilisés
            foreach ($submissions as $submission) {
                if (empty($submission['game_data'])) continue;
                
                // Ajouter les covers
                if (!empty($submission['game_data']['covers'])) {
                    $used_media_ids = array_merge($used_media_ids, array_values($submission['game_data']['covers']));
                }
                
                // Ajouter les screenshots
                if (!empty($submission['game_data']['screenshots'])) {
                    $used_media_ids = array_merge($used_media_ids, $submission['game_data']['screenshots']);
                }
            }
        }
        
        // Nettoyer les doublons
        $used_media_ids = array_unique(array_filter($used_media_ids));
        
        // Trouver les médias orphelins
        $orphaned_media = array_diff($media_with_ratio, $used_media_ids);
        
        // Supprimer les médias orphelins
        foreach ($orphaned_media as $media_id) {
            wp_delete_attachment($media_id, true);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[Sisme Media Cleanup] Nettoyage terminé : %d médias orphelins supprimés',
                count($orphaned_media)
            ));
        }
    }
}