<?php
/**
 * File: /sisme-games-editor/admin/components/PHP-admin-submission-functions.php
 * Interface admin moderne pour les soumissions de jeux
 * 
 * RESPONSABILITÉ:
 * - Tableau condensé avec miniatures média
 * - Actions admin (approuver/rejeter/supprimer/voir)
 * - Expansion des détails avec le système front
 * - Styles admin isolés
 * 
 * DÉPENDANCES:
 * - game-submission système existant (aucune modification)
 * - JS-admin-submissions.js (comportements admin)
 */

if (!defined('ABSPATH')) {
    exit;
}


add_action('wp_ajax_sisme_admin_get_submission_details', ['Sisme_Admin_Submission_Functions', 'ajax_get_submission_details']);
add_action('wp_ajax_sisme_admin_reject_submission', ['Sisme_Admin_Submission_Functions', 'ajax_reject_submission']);
add_action('wp_ajax_sisme_admin_approve_submission', ['Sisme_Admin_Submission_Functions', 'ajax_approve_submission']);
add_action('wp_ajax_sisme_admin_delete_submission', ['Sisme_Admin_Submission_Functions', 'ajax_delete_submission']);

/**
 * Classe pour l'onglet admin des soumissions
 */
class Sisme_Admin_Submission_Functions {    
    /**
     * Convertir les IDs de genre en noms lisibles
     */
    private static function format_genres($genre_ids) {
        if (empty($genre_ids) || !is_array($genre_ids)) {
            return [];
        }
        
        $formatted_genres = [];
        foreach ($genre_ids as $genre_id) {
            $term = get_term($genre_id);
            if ($term && !is_wp_error($term)) {
                $formatted_genres[] = $term->name;
            }
        }
        
        return $formatted_genres;
    }
    
    /**
     * Récupérer une soumission par son ID parmi tous les développeurs (admin)
     */
    public static function get_submission_for_admin($submission_id) {
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            $file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
        $developer_users = get_users([
            'meta_key' => Sisme_Utils_Users::META_DEVELOPER_STATUS,
            'meta_value' => Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED,
            'fields' => ['ID', 'display_name', 'user_email']
        ]);
        foreach ($developer_users as $user) {
            $user_submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user->ID);
            foreach ($user_submissions as $submission) {
                if ($submission['id'] === $submission_id) {
                    $submission['user_data'] = [
                        'user_id' => $user->ID,
                        'display_name' => $user->display_name,
                        'user_email' => $user->user_email
                    ];
                    return $submission;
                }
            }
        }
        return null;
    }
    /**
     * AJAX : Récupérer les détails d'une soumission
     */
    public static function ajax_get_submission_details() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
            return;
        }
        
        // Vérification nonce
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_admin_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
            return;
        }
        
        $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');
        $developer_user_id = intval($_POST['user_id'] ?? 0);
        error_log('DEBUG: submission_id=' . $submission_id . ' developer_user_id=' . $developer_user_id);
        error_log('AJAX APPELÉ !');
        if (empty($submission_id) || !$developer_user_id) {
            wp_send_json_error(['message' => 'Paramètres manquants']);
            return;
        }
        
        // Charger le module data manager
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            $file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
            if (!file_exists($file)) {
                wp_send_json_error(['message' => 'Module soumission non disponible']);
                return;
            }
            require_once $file;
        }

        $submission = self::get_submission_for_admin($submission_id);
        error_log('DEBUG: submission=' . print_r($submission, true));
        if (!$submission) {
            wp_send_json_error(['message' => 'Soumission introuvable pour ce développeur']);
            return;
        }
        
        // Enrichir les données avec des infos développeur
        $developer_info = get_userdata($developer_user_id);
        
        $game_data = $submission['game_data'] ?? [];
        // Covers
        $covers = [
            'horizontal' => !empty($game_data['covers']['horizontal']) ? [
                'id' => $game_data['covers']['horizontal'],
                'url' => wp_get_attachment_url($game_data['covers']['horizontal']),
                'thumb' => wp_get_attachment_image_url($game_data['covers']['horizontal'], 'thumbnail')
            ] : null,
            'vertical' => !empty($game_data['covers']['vertical']) ? [
                'id' => $game_data['covers']['vertical'],
                'url' => wp_get_attachment_url($game_data['covers']['vertical']),
                'thumb' => wp_get_attachment_image_url($game_data['covers']['vertical'], 'thumbnail')
            ] : null
        ];
        // Screenshots
        $screenshots = [];
        if (!empty($game_data['screenshots']) && is_array($game_data['screenshots'])) {
            foreach ($game_data['screenshots'] as $i => $sid) {
                $screenshots[] = [
                    'id' => $sid,
                    'url' => wp_get_attachment_url($sid),
                    'thumb' => wp_get_attachment_image_url($sid, 'thumbnail')
                ];
            }
        }
        // Sections détaillées
        $sections = [];
        if (!empty($game_data['sections']) && is_array($game_data['sections'])) {
            foreach ($game_data['sections'] as $section) {
                $sections[] = [
                    'title' => $section['title'] ?? '',
                    'content' => $section['content'] ?? '',
                    'image' => !empty($section['image_attachment_id']) ? [
                        'id' => $section['image_attachment_id'],
                        'url' => wp_get_attachment_url($section['image_attachment_id']),
                        'thumb' => wp_get_attachment_image_url($section['image_attachment_id'], 'thumbnail')
                    ] : null
                ];
            }
        }
        // Liens externes
        $external_links = $game_data['external_links'] ?? [];
        
        // Convertir les genres d'IDs en noms
        $genres_formatted = self::format_genres($game_data['game_genres'] ?? []);

        // Réponse complète
        wp_send_json_success([
            'submission_id' => $submission_id,
            'developer' => [
                'user_id' => $developer_user_id,
                'display_name' => $developer_info ? $developer_info->display_name : 'Inconnu',
                'user_email' => $developer_info ? $developer_info->user_email : ''
            ],
            'game_data' => $game_data,
            'genres_formatted' => $genres_formatted,
            'covers' => $covers,
            'screenshots' => $screenshots,
            'sections' => $sections,
            'external_links' => $external_links,
            'metadata' => $submission['metadata'] ?? [],
            'admin_data' => $submission['admin_data'] ?? [],
            'status' => $submission['status'] ?? 'unknown',
            'is_admin_access' => true        
        ]);
    }
    
    /**
     * Méthode publique pour récupérer les statistiques des soumissions
     * Utilisée par d'autres classes admin
     */
    public static function get_submissions_statistics() {
        $submissions_data = self::get_submissions_data();
        $archived_data = self::get_archived_submissions_data();
        $all_submissions = array_merge($submissions_data, $archived_data);
        
        return self::calculate_stats($all_submissions);
    }
    
    /**
     * Récupérer les données des soumissions
     * @param string|null $status_filter Statut à filtrer (optionnel) - ex: 'pending', 'draft', 'published', 'rejected', 'archived'
     * @param string|null $revision_filter Filtre pour les révisions ('only_revisions', 'exclude_revisions', null pour tout)
     * @return array Tableau des soumissions filtrées
     */
    public static function get_submissions_data($status_filter = null, $revision_filter = null) {
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            $file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
        
        $all_submissions = [];
        
        if (class_exists('Sisme_Game_Submission_Data_Manager')) {
            $developer_users = get_users([
                'meta_key' => Sisme_Utils_Users::META_DEVELOPER_STATUS,
                'meta_value' => Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED,
                'fields' => ['ID', 'display_name', 'user_email']
            ]);
            
            foreach ($developer_users as $user) {
                // Cas spécial pour les archives : utiliser la méthode dédiée du data manager
                if ($status_filter === 'archived') {
                    $user_submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user->ID, 'archived');
                } else {
                    $user_submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user->ID);
                }
                
                foreach ($user_submissions as $submission) {
                    $submission_status = $submission['status'] ?? 'draft';
                    $is_revision = isset($submission['metadata']['is_revision']) && $submission['metadata']['is_revision'];
                    
                    // Filtrage par type de révision (nouveau système à 2 paramètres)
                    if ($revision_filter === 'only_revisions' && !$is_revision) {
                        continue; // Ne garder que les révisions
                    }
                    if ($revision_filter === 'exclude_revisions' && $is_revision) {
                        continue; // Exclure les révisions
                    }
                    
                    // Filtrage par statut
                    if ($status_filter === 'archived') {
                        // Déjà filtré par le data manager, on garde tout
                    }
                    elseif ($status_filter !== null) {
                        if ($submission_status !== $status_filter) {
                            continue;
                        }
                    }
                    // Comportement par défaut : exclure les archives
                    elseif ($status_filter === null && $submission_status === 'archived') {
                        continue;
                    }
                    
                    $submission['user_data'] = [
                        'user_id' => $user->ID,
                        'display_name' => $user->display_name,
                        'user_email' => $user->user_email
                    ];
                    $all_submissions[] = $submission;
                }
            }
            
            // Trier par date appropriée
            usort($all_submissions, function($a, $b) use ($status_filter) {
                if ($status_filter === 'archived') {
                    // Pour les archives, trier par date d'archivage
                    $date_a = $a['metadata']['archived_at'] ?? $a['metadata']['updated_at'];
                    $date_b = $b['metadata']['archived_at'] ?? $b['metadata']['updated_at'];
                } else {
                    // Pour les autres, trier par date de soumission
                    $date_a = $a['metadata']['submitted_at'] ?? $a['metadata']['updated_at'];
                    $date_b = $b['metadata']['submitted_at'] ?? $b['metadata']['updated_at'];
                }
                return strtotime($date_b) - strtotime($date_a);
            });
        }
        
        return $all_submissions;
    }
    
    /**
     * Récupérer les données des archives
     */
    private static function get_archived_submissions_data() {
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            $file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
        
        $archived_submissions = [];
        
        if (class_exists('Sisme_Game_Submission_Data_Manager')) {
            $developer_users = get_users([
                'meta_key' => Sisme_Utils_Users::META_DEVELOPER_STATUS,
                'meta_value' => Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED,
                'fields' => ['ID', 'display_name', 'user_email']
            ]);
            
            foreach ($developer_users as $user) {
                $user_submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user->ID, 'archived');
                
                foreach ($user_submissions as $submission) {
                    $submission['user_data'] = [
                        'user_id' => $user->ID,
                        'display_name' => $user->display_name,
                        'user_email' => $user->user_email
                    ];
                    $archived_submissions[] = $submission;
                }
            }
            
            // Trier par date d'archivage
            usort($archived_submissions, function($a, $b) {
                $date_a = $a['metadata']['archived_at'] ?? $a['metadata']['updated_at'];
                $date_b = $b['metadata']['archived_at'] ?? $b['metadata']['updated_at'];
                return strtotime($date_b) - strtotime($date_a);
            });
        }
        
        return $archived_submissions;
    }
    
    /**
     * Calculer les statistiques
     */
    private static function calculate_stats($submissions) {
        $stats = [
            'draft' => 0, 'pending' => 0, 'published' => 0,
            'rejected' => 0, 'archived_count' => 0, 'total' => 0
        ];
        
        foreach ($submissions as $submission) {
            $status = $submission['status'] ?? 'draft';
            if ($status === 'archived') {
                $stats['archived_count']++;
            } elseif (isset($stats[$status])) {
                $stats[$status]++;
                $stats['total']++; // Only count non-archived submissions in total
            }
        }
        
        return $stats;
    }
    

    

    

    

    

    
    /**
     * AJAX : Rejeter une soumission
     */
    public static function ajax_reject_submission() {
        error_log('DEBUG: Début ajax_reject_submission');
        error_log('DEBUG: POST data: ' . print_r($_POST, true));
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_admin_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);
        $reason = wp_unslash(sanitize_textarea_field($_POST['rejection_reason'] ?? ''));
        
        error_log("DEBUG: submission_id='$submission_id', user_id=$user_id, reason='$reason'");
        
        if (!$submission_id || !$user_id || !$reason) {
            error_log('DEBUG: Paramètre manquant détecté');
            wp_send_json_error(['message' => "Paramètres manquants - ID:$submission_id, User:$user_id, Reason:$reason"]);
        }
        
        // Charger le data manager
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
        }
        
        // Changer le statut vers draft
        $result = Sisme_Game_Submission_Data_Manager::change_submission_status(
            $user_id, 
            $submission_id, 
            Sisme_Utils_Users::GAME_STATUS_DRAFT,
            [
                'rejected_at' => current_time('mysql'),
                'rejection_reason' => $reason
            ]
        );
        
        if ($result) {
            $user = get_userdata($user_id);
            $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
            $game_name = $submission['game_data']['game_name'] ?? 'Votre jeu';
            
            $email_content = Sisme_Email_Templates::submission_rejected(
                $user->display_name,
                $game_name,
                $reason
            );
            
            Sisme_Email_Manager::send_email(
                [$user_id],
                "Soumission rejetée : {$game_name}",
                $email_content
            );
            
            wp_send_json_success(['message' => 'Soumission rejetée avec succès']);
        } else {
            wp_send_json_error(['message' => 'Erreur lors du rejet']);
        }
    }

    /**
     * AJAX : Approuver une soumission
     */
    public static function ajax_approve_submission() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_admin_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if (!$submission_id || !$user_id) {
            wp_send_json_error(['message' => "Paramètres manquants - ID:$submission_id, User:$user_id"]);
        }
        
        // Charger le data manager
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
        }
        
        // Vérifier que la soumission existe et est en attente
        $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
        if (!$submission) {
            wp_send_json_error(['message' => 'Soumission introuvable']);
        }
        
        if ($submission['status'] !== Sisme_Utils_Users::GAME_STATUS_PENDING) {
            wp_send_json_error(['message' => 'Seules les soumissions en attente peuvent être approuvées']);
        }
        
        // AJOUT ICI : Détecter si c'est une révision
        $is_revision = $submission['metadata']['is_revision'] ?? false;
        
        if ($is_revision) {
            // Traitement spécial pour les révisions
            $result = self::approve_revision($submission, $user_id);
            
            if ($result) {
                wp_send_json_success([
                    'message' => 'Révision approuvée avec succès',
                    'is_revision' => true
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Erreur lors de l\'approbation de la révision'
                ]);
            }
            return; // Important : arrêter l'exécution ici pour les révisions
        }
        
        // Changer le statut vers published
        $result = Sisme_Game_Submission_Data_Manager::change_submission_status(
            $user_id, 
            $submission_id, 
            Sisme_Utils_Users::GAME_STATUS_PENDING,
            [
                'approval_timestamp' => current_time('mysql')
            ]
        );
        
        if ($result) {
            if (class_exists('Sisme_Game_Creator')) {
                
                $game_id = Sisme_Game_Creator::create_from_submission_data($submission);
                
                if (!is_wp_error($game_id)) {
                    // Mettre à jour la soumission avec l'ID du jeu créé
                    Sisme_Game_Submission_Data_Manager::change_submission_status(
                        $user_id,
                        $submission_id, 
                        Sisme_Utils_Users::GAME_STATUS_PUBLISHED,
                        [
                            'published_at' => current_time('mysql'),
                            'admin_user_id' => get_current_user_id(),
                            'published_game_id' => $game_id
                        ]
                    );
                    
                    // Email de confirmation
                    $user = get_userdata($user_id);
                    $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
                    $game_name = $submission['game_data']['game_name'] ?? 'Votre jeu';
                    $game_link = Sisme_Game_Creator_Data_Manager::get_game_url($submission['metadata']['published_game_id'] ?? 'Erreur de génération du lien');

                    $email_content = Sisme_Email_Templates::submission_approved(
                        $user->display_name,
                        $game_name
                    );
                    
                    Sisme_Email_Manager::send_email(
                        [$user_id],
                        "Soumission approuvée : {$game_name}",
                        $email_content
                    );
                    
                    wp_send_json_success([
                        'message' => 'Jeu approuvé et créé avec succès',
                        'game_id' => $game_id
                    ]);
                } else {
                    // Rollback - remettre en pending si création échoue
                    Sisme_Game_Submission_Data_Manager::change_submission_status(
                        $user_id,
                        $submission_id, 
                        Sisme_Utils_Users::GAME_STATUS_PENDING
                    );
                    
                    wp_send_json_error([
                        'message' => 'Erreur lors de la création du jeu: ' . $game_id->get_error_message()
                    ]);
                }
            } else {
                wp_send_json_error(['message' => 'Module de création de jeu non disponible']);
            }
        } else {
            wp_send_json_error(['message' => 'Erreur lors de l\'approbation']);
        }
    }

    /**
     * Approuver une révision et mettre à jour le jeu original
     * 
     * @param array $revision_submission Données de la révision
     * @param int $user_id ID du développeur
     * @return bool Succès de l'opération
     */
    private static function approve_revision($revision_submission, $user_id) {
        error_log('DEBUG: Début approve_revision pour user_id=' . $user_id . ', revision_id=' . $revision_submission['id']);
        
        // Récupérer l'ID de la soumission originale
        $original_id = $revision_submission['metadata']['original_published_id'] ?? null;
        if (!$original_id) {
            error_log('DEBUG: Échec - original_id manquant dans metadata');
            return false;
        }
        
        // Récupérer la soumission originale
        $original_submission = self::get_submission_for_admin($original_id);
        if (!$original_submission) {
            error_log('DEBUG: Échec - soumission originale introuvable pour ID=' . $original_id);
            return false;
        }
        
        // Récupérer l'ID du jeu publié
        $game_id = $original_submission['metadata']['published_game_id'] ?? null;
        if (!$game_id) {
            error_log('DEBUG: Échec - published_game_id manquant dans la soumission originale');
            return false;
        }
        
        // Mettre à jour le jeu avec les données de la révision
        if (class_exists('Sisme_Game_Creator_Data_Manager')) {
            // Utiliser la même logique que pour les nouvelles soumissions
            if (class_exists('Sisme_Game_Creator')) {
                $updated_game_data = Sisme_Game_Creator::extract_game_data_from_submission($revision_submission);
                
                if (is_wp_error($updated_game_data)) {
                    error_log('DEBUG: Échec - Erreur lors de l\'extraction des données: ' . $updated_game_data->get_error_message());
                    return false;
                }
            } else {
                error_log('DEBUG: Échec - Classe Sisme_Game_Creator non trouvée');
                return false;
            }
            
            // Tentative de mise à jour du jeu
            error_log('DEBUG: Tentative de mise à jour du jeu game_id=' . $game_id);
            $update_result = Sisme_Game_Creator_Data_Manager::update_game($game_id, $updated_game_data);
            
            if (is_wp_error($update_result)) {
                error_log('DEBUG: Échec - Erreur lors de la mise à jour du jeu: ' . $update_result->get_error_message());
                return false;
            }
            
            error_log('DEBUG: Jeu mis à jour avec succès');
        } else {
            error_log('DEBUG: Échec - Classe Sisme_Game_Creator_Data_Manager introuvable');
            return false;
        }
        
        // 2. Gérer les soumissions via le module dédié
        if (class_exists('Sisme_Game_Submission_Data_Manager')) {
            error_log('DEBUG: Début de l\'approbation de révision via le module de soumissions');
            
            $approval_result = Sisme_Game_Submission_Data_Manager::approve_revision($user_id, $revision_submission['id']);
            
            if (is_wp_error($approval_result)) {
                error_log('DEBUG: Échec - Erreur lors de l\'approbation de révision: ' . $approval_result->get_error_message());
                return false;
            }
            
            error_log('DEBUG: Révision approuvée dans le système de soumissions');
        } else {
            error_log('DEBUG: Échec - Classe Sisme_Game_Submission_Data_Manager introuvable');
            return false;
        }
        
        // 3. Envoyer l'email de confirmation
        $user = get_userdata($user_id);
        if ($user && class_exists('Sisme_Email_Templates') && class_exists('Sisme_Email_Manager')) {
            $game_name = $updated_game_data['name'] ?? 'Votre jeu';
            
            $email_content = Sisme_Email_Templates::revision_approved(
                $user->display_name,
                $game_name
            );
            
            $email_result = Sisme_Email_Manager::send_email(
                [$user_id],
                "Révision approuvée : {$game_name}",
                $email_content
            );
            
            if (!$email_result) {
                error_log('DEBUG: Avertissement - Échec de l\'envoi de l\'email');
            }
        } else {
            error_log('DEBUG: Avertissement - Classes d\'email manquantes ou utilisateur introuvable');
        }
        
        error_log('DEBUG: Révision approuvée avec succès');
        return true;
    }
    
    /**
     * Handler AJAX pour supprimer une soumission (révision)
     */
    public static function ajax_delete_submission() {
        // Vérification de sécurité
        if (!check_ajax_referer('sisme_admin_nonce', 'security', false)) {
            wp_send_json_error(['message' => 'Token de sécurité invalide']);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
            return;
        }
        
        $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if (empty($submission_id) || empty($user_id)) {
            wp_send_json_error(['message' => 'Paramètres manquants']);
            return;
        }
        
        // Vérifier que la soumission existe
        $submission = self::get_submission_for_admin($submission_id);
        if (!$submission) {
            wp_send_json_error(['message' => 'Soumission introuvable']);
            return;
        }
        
        // Information sur le type pour le log
        $is_revision = isset($submission['metadata']['is_revision']) && $submission['metadata']['is_revision'];
        $submission_type = $is_revision ? 'révision' : 'soumission';
        
        // Supprimer via le module de soumissions (sans supprimer les médias)
        if (class_exists('Sisme_Game_Submission_Data_Manager')) {
            $delete_result = Sisme_Game_Submission_Data_Manager::delete_submission($user_id, $submission_id);
            
            if (is_wp_error($delete_result)) {
                wp_send_json_error(['message' => 'Erreur lors de la suppression: ' . $delete_result->get_error_message()]);
                return;
            }
            
            if ($delete_result) {
                // Log de l'action admin
                error_log("ADMIN: Suppression de {$submission_type} {$submission_id} par admin " . get_current_user_id());
                
                wp_send_json_success(['message' => ucfirst($submission_type) . ' supprimée avec succès']);
            } else {
                wp_send_json_error(['message' => 'Échec de la suppression']);
            }
        } else {
            wp_send_json_error(['message' => 'Module de gestion des soumissions introuvable']);
        }
    }
}