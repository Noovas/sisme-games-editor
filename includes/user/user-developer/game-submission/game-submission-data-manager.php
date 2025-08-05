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
     * Changer le statut d'une soumission
     * @param int $user_id ID utilisateur
     * @param string $submission_id ID soumission
     * @param string $status Nouveau statut
     * @param array $metadata Métadonnées additionnelles
     * @return bool Succès de l'opération
     */
    public static function change_submission_status($user_id, $submission_id, $status, $metadata = []) {
        $submission = self::get_submission_by_id($user_id, $submission_id);
        if (!$submission) {
            return false;
        }
        
        // Valider le statut
        $valid_statuses = [
            Sisme_Utils_Users::GAME_STATUS_DRAFT,
            Sisme_Utils_Users::GAME_STATUS_PENDING,
            Sisme_Utils_Users::GAME_STATUS_PUBLISHED,
            Sisme_Utils_Users::GAME_STATUS_REJECTED,
            Sisme_Utils_Users::GAME_STATUS_ARCHIVED
        ];
        
        if (!in_array($status, $valid_statuses)) {
            return false;
        }
        
        // Changer le statut
        $submission['status'] = $status;
        $submission['metadata']['updated_at'] = current_time('mysql');
        
        // Ajouter métadonnées supplémentaires
        foreach ($metadata as $key => $value) {
            $submission['metadata'][$key] = $value;
        }
        
        // Sauvegarder
        if (self::update_submission_in_user_data($user_id, $submission)) {
            self::update_user_stats($user_id);
            return true;
        }
        
        return false;
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

        $new_submission = [
            'id' => $submission_id,
            'status' => Sisme_Utils_Users::GAME_STATUS_DRAFT,
            'game_data' => $clean_game_data,
            'metadata' => [
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'submitted_at' => null,
                'published_at' => null,
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
            return new WP_Error('not_draft', 'Seuls les brouillons peuvent être sauvegardés ou soumis pour validation');
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

        $submission['game_data'] = $merged_data;
        $submission['metadata']['updated_at'] = current_time('mysql');
        
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
     * @param int $user_id ID de l'utilisateur
     * @param string $submission_id ID de la soumission
     * @param bool $delete_media Forcer la suppression des médias (optionnel)
     * @return bool|WP_Error Résultat de la suppression
     */
    public static function delete_submission($user_id, $submission_id, $delete_media = null) {
        if (!self::can_delete_submission($user_id, $submission_id)) {
            return new WP_Error('cannot_delete', 'Suppression non autorisée');
        }
        
        // Récupérer la soumission avant suppression
        $submission = self::get_submission_by_id($user_id, $submission_id);
        if (!$submission) {
            return new WP_Error('submission_not_found', 'Soumission introuvable');
        }
        
        // Déterminer si on doit supprimer les médias
        $is_revision = isset($submission['metadata']['is_revision']) && $submission['metadata']['is_revision'];
        
        // Si delete_media n'est pas explicitement défini, utiliser la logique par défaut
        if ($delete_media === null) {
            $delete_media = !$is_revision; // Supprimer les médias SAUF pour les révisions
        }
        
        // Déclencher un hook avant suppression pour permettre le nettoyage des médias
        if ($submission && $delete_media) {
            do_action('sisme_before_submission_delete', $user_id . '_' . $submission_id, $submission);
        }

        if (self::remove_submission_from_user_data($user_id, $submission_id)) {
            self::update_user_stats($user_id);
            
            // Log pour traçabilité
            $type = $is_revision ? 'révision' : 'soumission';
            $media_action = $delete_media ? 'avec suppression des médias' : 'sans suppression des médias';
            error_log("Suppression de {$type} {$submission_id} {$media_action}");
            
            return true;
        }
        
        return new WP_Error('delete_failed', 'Erreur lors de la suppression');
    }
    
    /**
     * Soumettre un brouillon pour validation (draft → pending)
     */
    public static function submit_for_review($user_id, $submission_id) {
        $submission = self::get_submission_by_id($user_id, $submission_id);
        if (!$submission) {
            return new WP_Error('not_found', 'Soumission introuvable');
        }
        
        if (!self::can_edit_submission($user_id, $submission_id)) {
            return new WP_Error('cannot_submit', 'Soumission pour validation non autorisée');
        }
        
        $validation_errors = self::validate_required_fields($submission['game_data']);
        if (!empty($validation_errors)) {
            return new WP_Error('validation_failed', 'Champs obligatoires manquants: ' . implode(', ', $validation_errors));
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
     * Vérifier si un utilisateur peut créer une soumission
     */
    public static function can_create_submission($user_id, $is_revision = false) {
        if (!Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
            return false;
        }
        
        $drafts = self::get_user_submissions($user_id, Sisme_Utils_Users::GAME_STATUS_DRAFT);
        
        if (!$is_revision) {
            // Logique existante pour nouveaux jeux - compter seulement les non-révisions
            $new_game_drafts = array_filter($drafts, function($draft) {
                return !($draft['metadata']['is_revision'] ?? false);
            });
            
            if (count($new_game_drafts) >= Sisme_Utils_Users::GAME_MAX_DRAFTS_PER_USER) {
                return false;
            }
        } else {
            // Nouvelle logique pour révisions - compter seulement les révisions
            $revision_drafts = array_filter($drafts, function($draft) {
                return ($draft['metadata']['is_revision'] ?? false);
            });
            
            if (count($revision_drafts) >= 3) { // Max 3 révisions simultanées
                return false;
            }
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
            Sisme_Utils_Users::GAME_STATUS_DRAFT
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

        if (current_user_can('manage_options')) {
            return true;
        }
        
        // Autoriser la suppression des brouillons et des révisions
        $deletable_statuses = [
            Sisme_Utils_Users::GAME_STATUS_DRAFT,
            Sisme_Utils_Users::GAME_STATUS_REJECTED
        ];
        
        // Autoriser aussi la suppression des révisions en attente
        if (isset($submission['metadata']['is_revision']) && $submission['metadata']['is_revision']) {
            $deletable_statuses[] = Sisme_Utils_Users::GAME_STATUS_PENDING;
        }
        
        return in_array($submission['status'], $deletable_statuses);
    }
    
    /**
     * Vérifier si une soumission peut être envoyée pour validation
     */
    public static function can_submit_for_review($user_id, $submission_id) {
        if (!self::can_edit_submission($user_id, $submission_id)) {
            return false;
        }
        
        $submission = self::get_submission_by_id($user_id, $submission_id);
        if (!$submission || $submission['status'] !== Sisme_Utils_Users::GAME_STATUS_DRAFT) {
            return false;
        }
        
        $validation_errors = self::validate_required_fields($submission['game_data']);
        return empty($validation_errors);
    }
    
    public static function validate_required_fields($game_data) {
        $errors = [];
        
        $required_fields = [
            'game_name',
            'game_description',
            'game_release_date',
            'game_trailer',
            'game_studio_name',
            'game_publisher_name',
            'game_genres',
            'game_platforms',
            'game_modes',
            'screenshots'
        ];
        
        foreach ($required_fields as $field) {
            if (!isset($game_data[$field]) || empty($game_data[$field])) {
                $errors[] = $field;
            }
        }
        
        $has_external_link = false;
        if (isset($game_data['external_links']) && is_array($game_data['external_links'])) {
            foreach (['steam', 'epic', 'gog'] as $platform) {
                if (!empty($game_data['external_links'][$platform])) {
                    $has_external_link = true;
                    break;
                }
            }
        }
        if (!$has_external_link) {
            $errors[] = 'external_links';
        }
        
        if (!isset($game_data['covers']) || 
            empty($game_data['covers']['horizontal']) || 
            empty($game_data['covers']['vertical'])) {
            $errors[] = 'covers';
        }
        
        $has_section = false;
        if (isset($game_data['sections']) && is_array($game_data['sections'])) {
            foreach ($game_data['sections'] as $section) {
                if (!empty($section['title']) && !empty($section['content'])) {
                    $has_section = true;
                    break;
                }
            }
        }
        if (!$has_section) {
            $errors[] = 'sections';
        }
        
        return $errors;
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
        
        $complex_fields = ['covers', 'screenshots', 'external_links', 'sections'];
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
        
        // Filtrer les soumissions archivées pour le calcul des statistiques côté front
        $active_submissions = array_filter($submissions, function($submission) {
            return ($submission['status'] ?? '') !== Sisme_Utils_Users::GAME_STATUS_ARCHIVED;
        });
        
        $stats = [
            'total_submissions' => count($active_submissions), // Seulement les soumissions actives
            'draft_count' => 0,
            'pending_count' => 0,
            'published_count' => 0,
            'rejected_count' => 0,
            'archived_count' => 0, // Toujours calculé mais pas affiché côté front
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
                case Sisme_Utils_Users::GAME_STATUS_ARCHIVED:
                    $stats['archived_count']++; // Comptabilisé mais pas dans le total
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

    /**
     * Vérifier si une soumission est une révision
     */
    public static function is_revision_submission($submission) {
        return $submission['metadata']['is_revision'] ?? false;
    }
    
    /**
     * Récupérer les données de la soumission originale d'une révision
     */
    public static function get_original_submission_data($revision_submission) {
        if (!self::is_revision_submission($revision_submission)) {
            return null;
        }
        
        $original_id = $revision_submission['metadata']['original_published_id'] ?? null;
        if (!$original_id) {
            return null;
        }
        
        // Récupérer l'utilisateur propriétaire de la révision
        $user_data = $revision_submission['user_data'] ?? null;
        if (!$user_data || !isset($user_data['user_id'])) {
            return null;
        }
        
        $user_id = $user_data['user_id'];
        
        // Chercher la soumission originale
        return self::get_submission_by_id($user_id, $original_id);
    }
    
    /**
     * Créer une révision d'une soumission publiée
     */
    public static function create_revision($user_id, $published_submission_id, $revision_type = 'major') {
        // Vérifier que l'utilisateur peut créer une révision
        if (!self::can_create_submission($user_id, true)) {
            return new WP_Error('revision_limit', 'Limite de révisions atteinte (3 maximum)');
        }

        // Récupérer les brouillons existants pour vérifier les révisions en cours
        $drafts = self::get_user_submissions($user_id, Sisme_Utils_Users::GAME_STATUS_DRAFT);
        
        // Récupérer la soumission originale
        $original_submission = self::get_submission_by_id($user_id, $published_submission_id);
        if (!$original_submission) {
            return new WP_Error('original_not_found', 'Soumission originale introuvable');
        }
        
        // Vérifier que c'est bien une soumission publiée
        if ($original_submission['status'] !== Sisme_Utils_Users::GAME_STATUS_PUBLISHED) {
            return new WP_Error('not_published', 'Seuls les jeux publiés peuvent être révisés');
        }

        // Vérifier qu'il n'y a pas déjà une révision en cours pour ce jeu
        // Récupérer toutes les soumissions de l'utilisateur (pas seulement les brouillons)
        $all_user_submissions = self::get_user_submissions($user_id);

        // Filtrer pour trouver les révisions en cours (brouillons ou en attente)
        $existing_revisions = array_filter($all_user_submissions, function($submission) use ($published_submission_id) {
            $is_revision = $submission['metadata']['is_revision'] ?? false;
            $original_id = $submission['metadata']['original_published_id'] ?? null;
            $status = $submission['status'] ?? '';
            
            // Vérifier si c'est une révision du même jeu et si elle est en brouillon ou en attente
            return $is_revision && 
                $original_id === $published_submission_id && 
                ($status === Sisme_Utils_Users::GAME_STATUS_DRAFT || 
                    $status === Sisme_Utils_Users::GAME_STATUS_PENDING);
        });

        if (!empty($existing_revisions)) {
            return new WP_Error('revision_exists', 'Une révision de ce jeu est déjà en cours de traitement. Attendez qu\'elle soit approuvée ou rejetée avant d\'en créer une nouvelle.');
        }
        
        // Générer nouvel ID pour la révision
        $revision_id = self::generate_submission_id();
        $clean_game_data = self::sanitize_game_data($original_submission['game_data']);
        
        // Créer le brouillon de révision
        $revision_submission = [
            'id' => $revision_id,
            'status' => Sisme_Utils_Users::GAME_STATUS_DRAFT,
            'game_data' => $clean_game_data,
            'metadata' => [
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'submitted_at' => null,
                'published_at' => null,
                'retry_count' => 0,
                'original_submission_id' => null,
                'auto_save_enabled' => true,
                'last_auto_save' => null,
                'is_revision' => true,
                'original_published_id' => $published_submission_id,
                'revision_type' => $revision_type,
                'revision_reason' => ''
            ],
            'admin_data' => [
                'admin_user_id' => null,
                'admin_notes' => '',
                'reviewed_at' => null
            ]
        ];
        
        // Sauvegarder la révision
        if (self::add_submission_to_user_data($user_id, $revision_submission)) {
            self::update_user_stats($user_id);
            return $revision_id;
        }
        
        return new WP_Error('create_failed', 'Erreur lors de la création de la révision');
    }
    
    /**
     * Approuver une révision : remplacer les données de la soumission principale et archiver la révision
     * @param int $user_id ID de l'utilisateur
     * @param string $revision_id ID de la révision à approuver
     * @return bool|WP_Error Succès ou erreur
     */
    public static function approve_revision($user_id, $revision_id) {
        if (!self::validate_developer_permissions($user_id)) {
            return new WP_Error('no_permission', 'Permissions insuffisantes');
        }
        
        // Récupérer la révision
        $revision_submission = self::get_submission_by_id($user_id, $revision_id);
        if (!$revision_submission) {
            return new WP_Error('revision_not_found', 'Révision introuvable');
        }
        
        // Vérifier que c'est bien une révision
        if (!isset($revision_submission['metadata']['is_revision']) || !$revision_submission['metadata']['is_revision']) {
            return new WP_Error('not_revision', 'Cette soumission n\'est pas une révision');
        }
        
        // Récupérer l'ID de la soumission principale
        $original_id = $revision_submission['metadata']['original_published_id'] ?? null;
        if (!$original_id) {
            return new WP_Error('no_original', 'Impossible de trouver la soumission principale');
        }
        
        // Récupérer la soumission principale
        $original_submission = self::get_submission_by_id($user_id, $original_id);
        if (!$original_submission) {
            return new WP_Error('original_not_found', 'Soumission principale introuvable');
        }
        
        // Effectuer le remplacement des données
        $replacement_result = self::replace_submission_data($user_id, $original_id, $revision_submission['game_data'], $revision_id);
        if (is_wp_error($replacement_result)) {
            return $replacement_result;
        }
        
        // Archiver la révision avec le motif original de l'utilisateur
        $original_revision_reason = $revision_submission['metadata']['revision_reason'] ?? '';
        $archive_reason = !empty($original_revision_reason) 
            ? "Révision approuvée - Motif : " . $original_revision_reason
            : 'Révision approuvée et fusionnée dans la soumission principale';
            
        $archive_result = self::change_submission_status($user_id, $revision_id, Sisme_Utils_Users::GAME_STATUS_ARCHIVED, [
            'archived_at' => current_time('mysql'),
            'archived_reason' => $archive_reason
        ]);
        
        if (!$archive_result) {
            return new WP_Error('archive_failed', 'Erreur lors de l\'archivage de la révision');
        }
        
        return true;
    }
    
    /**
     * Remplacer les données d'une soumission par celles d'une révision
     * @param int $user_id ID de l'utilisateur
     * @param string $submission_id ID de la soumission à modifier
     * @param array $new_game_data Nouvelles données de jeu
     * @param string $source_revision_id ID de la révision source
     * @return bool|WP_Error Succès ou erreur
     */
    private static function replace_submission_data($user_id, $submission_id, $new_game_data, $source_revision_id) {
        $user_data = get_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, true);
        
        if (empty($user_data['submissions'])) {
            return new WP_Error('no_submissions', 'Aucune soumission trouvée');
        }
        
        // Trouver et modifier la soumission
        foreach ($user_data['submissions'] as &$submission) {
            if ($submission['id'] === $submission_id) {
                $submission['game_data'] = $new_game_data;
                $submission['metadata']['updated_at'] = current_time('mysql');
                $submission['metadata']['last_revision_approved_at'] = current_time('mysql');
                $submission['metadata']['approved_revision_id'] = $source_revision_id;
                break;
            }
        }
        
        // Sauvegarder
        $save_result = update_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, $user_data);
        if (!$save_result) {
            return new WP_Error('save_failed', 'Erreur lors de la sauvegarde');
        }
        
        return true;
    }
}