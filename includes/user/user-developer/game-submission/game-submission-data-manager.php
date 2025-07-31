<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/game-submission/game-submission-data-manager.php
 * Data Manager pour les soumissions de jeux via user meta
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_Submission_Data_Manager {
    
    /**
     * Récupérer toutes les soumissions d'un utilisateur
     */
    public static function get_user_submissions($user_id, $status = null) {
        if (!self::validate_developer_permissions($user_id)) {
            return [];
        }
        
        $user_data = get_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, true);
        
        if (empty($user_data['submissions'])) {
            return [];
        }
        
        $submissions = $user_data['submissions'];
        
        if ($status !== null) {
            $submissions = array_filter($submissions, function($submission) use ($status) {
                return $submission['status'] === $status;
            });
        }
        
        usort($submissions, function($a, $b) {
            return strtotime($b['metadata']['updated_at']) - strtotime($a['metadata']['updated_at']);
        });
        
        return $submissions;
    }
    
    /**
     * Récupérer une soumission spécifique par son ID
     */
    public static function get_submission_by_id($user_id, $submission_id) {
        $submissions = self::get_user_submissions($user_id);
        
        foreach ($submissions as $submission) {
            if ($submission['id'] === $submission_id) {
                return $submission;
            }
        }
        
        return null;
    }
    
    /**
     * Récupérer les statistiques d'un utilisateur
     */
    public static function get_user_stats($user_id) {
        $user_data = get_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, true);
        
        if (empty($user_data['stats'])) {
            return self::recalculate_user_stats($user_id);
        }
        
        return $user_data['stats'];
    }
    
    /**
     * Créer une nouvelle soumission (brouillon)
     */
    public static function create_submission($user_id, $game_data = []) {
        if (!self::can_create_submission($user_id)) {
            return new WP_Error('cannot_create', 'Impossible de créer une nouvelle soumission');
        }
        
        $submission_id = self::generate_submission_id();
        $clean_game_data = self::sanitize_game_data($game_data);
        $completion = self::calculate_completion_percentage($clean_game_data);
        
        $new_submission = [
            'id' => $submission_id,
            'status' => Sisme_Utils_Users::GAME_STATUS_DRAFT,
            'game_data' => $clean_game_data,
            'metadata' => [
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'submitted_at' => null,
                'published_at' => null,
                'completion_percentage' => $completion,
                'retry_count' => 0,
                'original_submission_id' => null,
                'auto_save_enabled' => true,
                'last_auto_save' => null
            ],
            'admin_data' => [
                'admin_user_id' => null,
                'admin_notes' => '',
                'reviewed_at' => null
            ]
        ];
        
        if (self::add_submission_to_user_data($user_id, $new_submission)) {
            self::update_user_stats($user_id);
            return $submission_id;
        }
        
        return new WP_Error('create_failed', 'Erreur lors de la création de la soumission');
    }
    
    /**
     * Sauvegarder un brouillon (auto-save)
     */
    public static function save_draft($user_id, $submission_id, $game_data) {
        $submission = self::get_submission_by_id($user_id, $submission_id);
        
        if (!$submission) {
            return new WP_Error('not_found', 'Soumission introuvable');
        }
        
        if ($submission['status'] !== Sisme_Utils_Users::GAME_STATUS_DRAFT) {
            return new WP_Error('not_draft', 'Seuls les brouillons peuvent être auto-sauvegardés');
        }
        
        return self::update_submission($user_id, $submission_id, $game_data);
    }
    
    /**
     * Mettre à jour une soumission existante
     */
    public static function update_submission($user_id, $submission_id, $game_data) {
        if (!self::can_edit_submission($user_id, $submission_id)) {
            return new WP_Error('cannot_edit', 'Modification non autorisée');
        }
        
        $submission = self::get_submission_by_id($user_id, $submission_id);
        if (!$submission) {
            return new WP_Error('not_found', 'Soumission introuvable');
        }
        
        $clean_game_data = self::sanitize_game_data($game_data);
        $merged_data = self::merge_game_data($submission['game_data'], $clean_game_data);
        $completion = self::calculate_completion_percentage($merged_data);
        
        $submission['game_data'] = $merged_data;
        $submission['metadata']['updated_at'] = current_time('mysql');
        $submission['metadata']['completion_percentage'] = $completion;
        
        if ($submission['status'] === Sisme_Utils_Users::GAME_STATUS_DRAFT) {
            $submission['metadata']['last_auto_save'] = current_time('mysql');
        }
        
        if (self::update_submission_in_user_data($user_id, $submission)) {
            self::update_user_stats($user_id);
            return true;
        }
        
        return new WP_Error('update_failed', 'Erreur lors de la mise à jour');
    }
    
    /**
     * Supprimer une soumission
     */
    public static function delete_submission($user_id, $submission_id) {
        if (!self::can_delete_submission($user_id, $submission_id)) {
            return new WP_Error('cannot_delete', 'Suppression non autorisée');
        }
        
        if (self::remove_submission_from_user_data($user_id, $submission_id)) {
            self::update_user_stats($user_id);
            return true;
        }
        
        return new WP_Error('delete_failed', 'Erreur lors de la suppression');
    }
    
    /**
     * Soumettre un brouillon pour validation (draft → pending)
     */
    public static function submit_for_review($user_id, $submission_id) {
        if (!self::can_submit_for_review($user_id, $submission_id)) {
            return new WP_Error('cannot_submit', 'Soumission pour validation non autorisée');
        }
        
        $submission = self::get_submission_by_id($user_id, $submission_id);
        if (!$submission) {
            return new WP_Error('not_found', 'Soumission introuvable');
        }
        
        $submission['status'] = Sisme_Utils_Users::GAME_STATUS_PENDING;
        $submission['metadata']['submitted_at'] = current_time('mysql');
        $submission['metadata']['updated_at'] = current_time('mysql');
        $submission['metadata']['auto_save_enabled'] = false;
        
        if (self::update_submission_in_user_data($user_id, $submission)) {
            self::update_user_stats($user_id);
            do_action('sisme_game_submission_submitted', $user_id, $submission_id, $submission);
            return true;
        }
        
        return new WP_Error('submit_failed', 'Erreur lors de la soumission');
    }
    
    /**
     * Créer une nouvelle version après rejet (rejected → revision)
     */
    public static function create_retry_submission($user_id, $original_id) {
        $original = self::get_submission_by_id($user_id, $original_id);
        
        if (!$original || $original['status'] !== Sisme_Utils_Users::GAME_STATUS_REJECTED) {
            return new WP_Error('invalid_original', 'Soumission originale invalide');
        }
        
        $retry_data = $original['game_data'];
        $new_submission_id = self::create_submission($user_id, $retry_data);
        
        if (is_wp_error($new_submission_id)) {
            return $new_submission_id;
        }
        
        $new_submission = self::get_submission_by_id($user_id, $new_submission_id);
        $new_submission['status'] = Sisme_Utils_Users::GAME_STATUS_REVISION;
        $new_submission['metadata']['retry_count'] = ($original['metadata']['retry_count'] ?? 0) + 1;
        $new_submission['metadata']['original_submission_id'] = $original_id;
        
        self::update_submission_in_user_data($user_id, $new_submission);
        self::update_user_stats($user_id);
        
        return $new_submission_id;
    }
    
    /**
     * Vérifier si un utilisateur peut créer une soumission
     */
    public static function can_create_submission($user_id) {
        if (!Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
            return false;
        }
        
        $drafts = self::get_user_submissions($user_id, Sisme_Utils_Users::GAME_STATUS_DRAFT);
        if (count($drafts) >= Sisme_Utils_Users::GAME_MAX_DRAFTS_PER_USER) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Vérifier si un utilisateur peut modifier une soumission
     */
    public static function can_edit_submission($user_id, $submission_id) {
        $submission = self::get_submission_by_id($user_id, $submission_id);
        
        if (!$submission) {
            return false;
        }
        
        $editable_statuses = [
            Sisme_Utils_Users::GAME_STATUS_DRAFT,
            Sisme_Utils_Users::GAME_STATUS_REVISION
        ];
        
        return in_array($submission['status'], $editable_statuses);
    }
    
    /**
     * Vérifier si un utilisateur peut supprimer une soumission
     */
    public static function can_delete_submission($user_id, $submission_id) {
        $submission = self::get_submission_by_id($user_id, $submission_id);
        
        if (!$submission) {
            return false;
        }
        
        return $submission['status'] === Sisme_Utils_Users::GAME_STATUS_DRAFT;
    }
    
    /**
     * Vérifier si une soumission peut être envoyée pour validation
     */
    public static function can_submit_for_review($user_id, $submission_id) {
        $submission = self::get_submission_by_id($user_id, $submission_id);
        
        if (!$submission) {
            return false;
        }
        
        $submittable_statuses = [
            Sisme_Utils_Users::GAME_STATUS_DRAFT,
            Sisme_Utils_Users::GAME_STATUS_REVISION
        ];
        
        if (!in_array($submission['status'], $submittable_statuses)) {
            return false;
        }
        
        $completion = $submission['metadata']['completion_percentage'] ?? 0;
        return $completion >= Sisme_Utils_Users::GAME_MIN_COMPLETION_TO_SUBMIT;
    }
    
    /**
     * Calculer le pourcentage de completion d'une soumission
     */
    public static function calculate_completion_percentage($game_data) {
        $required_fields = [
            Sisme_Utils_Users::GAME_FIELD_NAME,
            Sisme_Utils_Users::GAME_FIELD_DESCRIPTION,
            Sisme_Utils_Users::GAME_FIELD_RELEASE_DATE,
            Sisme_Utils_Users::GAME_FIELD_TRAILER,
            Sisme_Utils_Users::GAME_FIELD_STUDIO_NAME,
            Sisme_Utils_Users::GAME_FIELD_PUBLISHER_NAME,
                    Sisme_Utils_Users::GAME_FIELD_PLATFORMS,
            Sisme_Utils_Users::GAME_FIELD_COVER_HORIZONTAL,
            Sisme_Utils_Users::GAME_FIELD_COVER_VERTICAL
        ];
        
        $completed_fields = 0;
        $total_fields = count($required_fields);
        
        foreach ($required_fields as $field) {
            if (isset($game_data[$field]) && !empty($game_data[$field])) {
                if (is_array($game_data[$field]) && count($game_data[$field]) > 0) {
                    $completed_fields++;
                } elseif (!is_array($game_data[$field])) {
                    $completed_fields++;
                }
            }
        }
        
        return round(($completed_fields / $total_fields) * 100);
    }
    
    /**
     * Récupérer toutes les soumissions pour l'admin
     */
    public static function get_all_submissions_for_admin($status = null, $limit = 50, $offset = 0) {
        $developer_users = get_users([
            'meta_key' => Sisme_Utils_Users::META_DEVELOPER_STATUS,
            'meta_value' => Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED,
            'fields' => ['ID', 'display_name', 'user_email']
        ]);
        
        $all_submissions = [];
        
        foreach ($developer_users as $user) {
            $user_submissions = self::get_user_submissions($user->ID, $status);
            
            foreach ($user_submissions as $submission) {
                $submission['user_data'] = [
                    'user_id' => $user->ID,
                    'display_name' => $user->display_name,
                    'user_email' => $user->user_email
                ];
                $all_submissions[] = $submission;
            }
        }
        
        usort($all_submissions, function($a, $b) {
            $date_a = $a['metadata']['submitted_at'] ?? $a['metadata']['updated_at'];
            $date_b = $b['metadata']['submitted_at'] ?? $b['metadata']['updated_at'];
            return strtotime($date_b) - strtotime($date_a);
        });
        
        return array_slice($all_submissions, $offset, $limit);
    }
    
    /**
     * Supprimer une soumission (action admin)
     */
    public static function delete_submission_admin($submission_id, $user_id) {
        $submission = self::get_submission_by_id($user_id, $submission_id);
        
        if (!$submission) {
            return new WP_Error('not_found', 'Soumission introuvable');
        }
        
        if (self::remove_submission_from_user_data($user_id, $submission_id)) {
            self::update_user_stats($user_id);
            return true;
        }
        
        return new WP_Error('delete_failed', 'Erreur lors de la suppression');
    }
    
    private static function generate_submission_id() {
        return 'sub_' . uniqid() . '_' . wp_generate_password(8, false);
    }
    
    private static function validate_developer_permissions($user_id) {
        if (!$user_id || !is_numeric($user_id)) {
            return false;
        }
        
        return Sisme_User_Developer_Data_Manager::is_approved_developer($user_id);
    }
    
    private static function sanitize_game_data($game_data) {
        $clean_data = [];
        
        if (!is_array($game_data)) {
            return $clean_data;
        }
        
        $text_fields = [
            Sisme_Utils_Users::GAME_FIELD_NAME,
            Sisme_Utils_Users::GAME_FIELD_STUDIO_NAME,
            Sisme_Utils_Users::GAME_FIELD_PUBLISHER_NAME
        ];
        
        foreach ($text_fields as $field) {
            if (isset($game_data[$field])) {
                $clean_data[$field] = sanitize_text_field($game_data[$field]);
            }
        }
        
        if (isset($game_data[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION])) {
            $clean_data[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION] = sanitize_textarea_field($game_data[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION]);
        }
        
        $url_fields = [
            Sisme_Utils_Users::GAME_FIELD_TRAILER,
            Sisme_Utils_Users::GAME_FIELD_STUDIO_URL,
            Sisme_Utils_Users::GAME_FIELD_PUBLISHER_URL
        ];
        
        foreach ($url_fields as $field) {
            if (isset($game_data[$field])) {
                $clean_data[$field] = esc_url_raw($game_data[$field]);
            }
        }
        
        if (isset($game_data[Sisme_Utils_Users::GAME_FIELD_RELEASE_DATE])) {
            $clean_data[Sisme_Utils_Users::GAME_FIELD_RELEASE_DATE] = sanitize_text_field($game_data[Sisme_Utils_Users::GAME_FIELD_RELEASE_DATE]);
        }
        
        $array_fields = [
            Sisme_Utils_Users::GAME_FIELD_GENRES,
            Sisme_Utils_Users::GAME_FIELD_PLATFORMS,
            Sisme_Utils_Users::GAME_FIELD_MODES
        ];
        
        foreach ($array_fields as $field) {
            if (isset($game_data[$field]) && is_array($game_data[$field])) {
                $clean_data[$field] = array_map('sanitize_text_field', $game_data[$field]);
            }
        }
        
        $complex_fields = ['covers', 'screenshots', 'external_links'];
        foreach ($complex_fields as $field) {
            if (isset($game_data[$field])) {
                $clean_data[$field] = $game_data[$field];
            }
        }
        
        return $clean_data;
    }
    
    private static function merge_game_data($existing, $new_data) {
        if (empty($existing)) {
            return $new_data;
        }
        
        return array_merge($existing, $new_data);
    }
    
    private static function add_submission_to_user_data($user_id, $submission) {
        $user_data = get_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, true);
        
        if (empty($user_data)) {
            $user_data = [
                'submissions' => [],
                'stats' => [],
                'settings' => self::get_default_settings()
            ];
        }
        
        $user_data['submissions'][] = $submission;
        
        return update_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, $user_data);
    }
    
    private static function update_submission_in_user_data($user_id, $updated_submission) {
        $user_data = get_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, true);
        
        if (empty($user_data['submissions'])) {
            return false;
        }
        
        foreach ($user_data['submissions'] as $index => $submission) {
            if ($submission['id'] === $updated_submission['id']) {
                $user_data['submissions'][$index] = $updated_submission;
                return update_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, $user_data);
            }
        }
        
        return false;
    }
    
    private static function remove_submission_from_user_data($user_id, $submission_id) {
        $user_data = get_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, true);
        
        if (empty($user_data['submissions'])) {
            return false;
        }
        
        $user_data['submissions'] = array_filter($user_data['submissions'], function($submission) use ($submission_id) {
            return $submission['id'] !== $submission_id;
        });
        
        $user_data['submissions'] = array_values($user_data['submissions']);
        
        return update_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, $user_data);
    }
    
    private static function update_user_stats($user_id) {
        $stats = self::recalculate_user_stats($user_id);
        
        $user_data = get_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, true);
        if (empty($user_data)) {
            $user_data = ['submissions' => [], 'settings' => self::get_default_settings()];
        }
        
        $user_data['stats'] = $stats;
        
        return update_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, $user_data);
    }
    
    private static function recalculate_user_stats($user_id) {
        $submissions = self::get_user_submissions($user_id);
        
        $stats = [
            'total_submissions' => count($submissions),
            'draft_count' => 0,
            'pending_count' => 0,
            'published_count' => 0,
            'rejected_count' => 0,
            'revision_count' => 0,
            'last_updated' => current_time('mysql')
        ];
        
        foreach ($submissions as $submission) {
            switch ($submission['status']) {
                case Sisme_Utils_Users::GAME_STATUS_DRAFT:
                    $stats['draft_count']++;
                    break;
                case Sisme_Utils_Users::GAME_STATUS_PENDING:
                    $stats['pending_count']++;
                    break;
                case Sisme_Utils_Users::GAME_STATUS_PUBLISHED:
                    $stats['published_count']++;
                    break;
                case Sisme_Utils_Users::GAME_STATUS_REJECTED:
                    $stats['rejected_count']++;
                    break;
                case Sisme_Utils_Users::GAME_STATUS_REVISION:
                    $stats['revision_count']++;
                    break;
            }
        }
        
        return $stats;
    }
    
    private static function get_default_settings() {
        return [
            'auto_save_interval' => 30,
            'auto_save_enabled' => true,
            'email_notifications' => true
        ];
    }

    /**
     * Compter toutes les soumissions pour l'admin par statut
     */
    public static function get_all_submissions_count($status = null) {
        $developer_users = get_users([
            'meta_key' => Sisme_Utils_Users::META_DEVELOPER_STATUS,
            'meta_value' => Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED,
            'fields' => ['ID']
        ]);
        
        $total_count = 0;
        
        foreach ($developer_users as $user) {
            $user_submissions = self::get_user_submissions($user->ID, $status);
            $total_count += count($user_submissions);
        }
        
        return $total_count;
    }
}