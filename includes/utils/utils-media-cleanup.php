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
    }
    
    /**
     * Nettoyer les médias d'une soumission avant sa suppression
     */
    public static function cleanup_submission_media($submission_id) {
        // Le submission_id est en fait le user_id et l'id de soumission
        list($user_id, $submission_id) = explode('_', $submission_id, 2);
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

        error_log('[MEDIA CLEANUP] DEBUT LOG =============');
        error_log('[MEDIA CLEANUP] Suppression médias pour la soumission ID: ' . $submission_id);
        $loged_media_ids = "[ ";
        foreach (array_unique($media_ids) as $media_id) {
            $loged_media_ids .= $media_id . " ";
            wp_delete_attachment($media_id, true);
        }
        $loged_media_ids .= "]";
        error_log('[MEDIA CLEANUP] Médias supprimés: ' . $loged_media_ids);
        error_log('[MEDIA CLEANUP] FIN LOG =============');
    }
}

Sisme_Utils_Media_Cleanup::init();