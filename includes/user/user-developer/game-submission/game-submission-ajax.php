<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/game-submission/game-submission-ajax.php
 * Handlers AJAX pour les soumissions de jeux
 */

if (!defined('ABSPATH')) {
    exit;
}


if (!class_exists('Sisme_Utils_Users')) {
    require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/utils/utils-users.php';
}

if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
    require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
}

/**
 * Handler pour utilisateurs non connectés
 */
function sisme_ajax_not_logged() {
    wp_send_json_error(['message' => 'Vous devez être connecté pour effectuer cette action']);
}

/**
 * Initialiser les hooks AJAX pour les soumissions de jeux
 */
function sisme_init_game_submission_ajax() {
    // CRUD soumissions
    add_action('wp_ajax_sisme_create_game_submission', 'sisme_ajax_create_game_submission');
    add_action('wp_ajax_sisme_save_draft_submission', 'sisme_ajax_save_draft_submission');
    add_action('wp_ajax_sisme_update_game_submission', 'sisme_ajax_update_game_submission');
    add_action('wp_ajax_sisme_delete_game_submission', 'sisme_ajax_delete_game_submission');
    add_action('wp_ajax_sisme_get_game_submissions', 'sisme_ajax_get_game_submissions');

    // Workflow
    add_action('wp_ajax_sisme_submit_game_for_review', 'sisme_ajax_submit_game_for_review');
    add_action('wp_ajax_sisme_retry_rejected_submission', 'sisme_ajax_retry_rejected_submission');
    add_action('wp_ajax_sisme_create_game_revision', 'sisme_ajax_create_game_revision');

    // Détails
    add_action('wp_ajax_sisme_get_submission_details', 'sisme_ajax_get_submission_details');
    add_action('wp_ajax_sisme_get_developer_game_stats', 'sisme_ajax_get_developer_game_stats');
    add_action('wp_ajax_sisme_get_archive_details', 'sisme_ajax_get_archive_details');

    // Admin
    add_action('wp_ajax_sisme_admin_get_submissions', 'sisme_ajax_admin_get_submissions');
    add_action('wp_ajax_sisme_admin_delete_submission', 'sisme_ajax_admin_delete_submission');

    // Non-connectés
    add_action('wp_ajax_nopriv_sisme_create_game_submission', 'sisme_ajax_not_logged');
    add_action('wp_ajax_nopriv_sisme_save_draft_submission', 'sisme_ajax_not_logged');
    add_action('wp_ajax_nopriv_sisme_update_game_submission', 'sisme_ajax_not_logged');
    add_action('wp_ajax_nopriv_sisme_delete_game_submission', 'sisme_ajax_not_logged');
    add_action('wp_ajax_nopriv_sisme_get_game_submissions', 'sisme_ajax_not_logged');
    add_action('wp_ajax_nopriv_sisme_submit_game_for_review', 'sisme_ajax_not_logged');
    add_action('wp_ajax_nopriv_sisme_retry_rejected_submission', 'sisme_ajax_not_logged');
    add_action('wp_ajax_nopriv_sisme_get_submission_details', 'sisme_ajax_not_logged');
    add_action('wp_ajax_nopriv_sisme_get_developer_game_stats', 'sisme_ajax_not_logged');

    //Détails
    add_action('wp_ajax_sisme_convert_taxonomy_ids', 'sisme_ajax_convert_taxonomy_ids');
    add_action('wp_ajax_nopriv_sisme_convert_taxonomy_ids', 'sisme_ajax_not_logged');
}

/**
 * Créer une nouvelle soumission (brouillon)
 */
function sisme_ajax_create_game_submission() {
    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }

    $user_id = get_current_user_id();

    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }

    $game_data = sisme_collect_game_data_from_post();
    $submission_id = Sisme_Game_Submission_Data_Manager::create_submission($user_id, $game_data);

    if (is_wp_error($submission_id)) {
        wp_send_json_error(['message' => $submission_id->get_error_message()]);
        return;
    }

    wp_send_json_success([
        'submission_id' => $submission_id,
        'message' => 'Brouillon créé avec succès',
        'game_name' => $game_data[Sisme_Utils_Users::GAME_FIELD_NAME] ?? 'Nouveau jeu'
    ]);
}

/**
 * Sauvegarder un brouillon (auto-save)
 */
function sisme_ajax_save_draft_submission() {
    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }

    $user_id = get_current_user_id();
    $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');

    if (empty($submission_id)) {
        wp_send_json_error(['message' => 'ID de soumission manquant']);
        return;
    }

    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }


    $game_data = sisme_collect_game_data_from_post();

    
    // Récupérer la soumission actuelle pour comparer les images avant/après
    $current_submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
    // Refuser si la soumission n'est pas un brouillon
    if (!$current_submission || $current_submission['status'] !== Sisme_Utils_Users::GAME_STATUS_DRAFT) {
        wp_send_json_error(['message' => 'Seuls les brouillons peuvent être sauvegardés.']);
        return;
    }
    // Sauvegarder le brouillon
    $result = Sisme_Game_Submission_Data_Manager::save_draft($user_id, $submission_id, $game_data);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
        return;
    }
    
    // Nettoyer les médias remplacés (covers)
    if (!empty($current_submission) && !empty($current_submission['game_data']['covers'])) {
        $old_covers = $current_submission['game_data']['covers'];
        $new_covers = $game_data['covers'] ?? [];
        if (!empty($old_covers['horizontal']) && (!isset($new_covers['horizontal']) || $old_covers['horizontal'] != $new_covers['horizontal'])) {
            wp_delete_attachment(intval($old_covers['horizontal']), true);
        }
        if (!empty($old_covers['vertical']) && (!isset($new_covers['vertical']) || $old_covers['vertical'] != $new_covers['vertical'])) {
            wp_delete_attachment(intval($old_covers['vertical']), true);
        }
    }

    // Nettoyer les screenshots remplacés
    if (!empty($current_submission) && !empty($current_submission['game_data']['screenshots'])) {
        $old_screenshots = $current_submission['game_data']['screenshots'];
        $new_screenshots = $game_data['screenshots'] ?? [];
        $removed_screenshots = array_diff($old_screenshots, $new_screenshots);
        foreach ($removed_screenshots as $screenshot_id) {
            if (!empty($screenshot_id)) {
                wp_delete_attachment(intval($screenshot_id), true);
            }
        }
    }

    // Nettoyer les images de sections supprimées ou remplacées
    if (!empty($current_submission) && !empty($current_submission['game_data'][Sisme_Utils_Users::GAME_FIELD_DESCRIPTION_SECTIONS])) {
        $old_sections = $current_submission['game_data'][Sisme_Utils_Users::GAME_FIELD_DESCRIPTION_SECTIONS];
        $new_sections = $game_data[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION_SECTIONS] ?? [];
        
        // Récupérer les IDs d'images (ignorer les nulls et les temp_)
        $old_ids = array_filter(array_map(function($s) { 
            $id = $s['image_attachment_id'] ?? null;
            return ($id && !is_string($id)) ? intval($id) : null;
        }, $old_sections));
        
        $new_ids = array_filter(array_map(function($s) { 
            $id = $s['image_attachment_id'] ?? null;
            return ($id && !is_string($id)) ? intval($id) : null;
        }, $new_sections));
        
        // Supprimer les images qui ne sont plus utilisées
        $removed_section_images = array_diff($old_ids, $new_ids);
        foreach ($removed_section_images as $img_id) {
            if (!empty($img_id) && is_numeric($img_id)) {
                wp_delete_attachment(intval($img_id), true);
            }
        }
    }

    wp_send_json_success([
        'message' => 'Brouillon sauvegardé',
        'last_auto_save' => current_time('H:i:s')
    ]);
}

/**
 * Mettre à jour une soumission existante
 */
function sisme_ajax_update_game_submission() {
    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }

    $user_id = get_current_user_id();
    $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');

    if (empty($submission_id)) {
        wp_send_json_error(['message' => 'ID de soumission manquant']);
        return;
    }

    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }

    $game_data = sisme_collect_game_data_from_post();
    $result = Sisme_Game_Submission_Data_Manager::update_submission($user_id, $submission_id, $game_data);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
        return;
    }

    $can_submit = Sisme_Game_Submission_Data_Manager::can_submit_for_review($user_id, $submission_id);

    wp_send_json_success([
        'message' => 'Soumission mise à jour',
        'can_submit' => $can_submit
    ]);
}

function sisme_ajax_validate_submission_for_review() {
    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }

    $user_id = get_current_user_id();
    $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');

    if (empty($submission_id)) {
        wp_send_json_error(['message' => 'ID de soumission manquant']);
        return;
    }

    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }

    $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
    if (!$submission) {
        wp_send_json_error(['message' => 'Soumission introuvable']);
        return;
    }

    $validation_errors = Sisme_Game_Submission_Data_Manager::validate_required_fields($submission['game_data']);
    
    if (!empty($validation_errors)) {
        wp_send_json_error([
            'message' => 'Champs obligatoires manquants',
            'errors' => $validation_errors
        ]);
        return;
    }

    wp_send_json_success(['message' => 'Validation réussie']);
}
add_action('wp_ajax_sisme_validate_submission_for_review', 'sisme_ajax_validate_submission_for_review');

/**
 * Supprimer une soumission (brouillons uniquement)
 */
function sisme_ajax_delete_game_submission() {
    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }

    $user_id = get_current_user_id();
    $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');

    if (empty($submission_id)) {
        wp_send_json_error(['message' => 'ID de soumission manquant']);
        return;
    }

    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }

    $result = Sisme_Game_Submission_Data_Manager::delete_submission($user_id, $submission_id);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
        return;
    }

    wp_send_json_success(['message' => 'Soumission supprimée']);
}

/**
 * Récupérer les soumissions d'un utilisateur
 */
function sisme_ajax_get_game_submissions() {
    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }

    $user_id = get_current_user_id();

    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }

    $status = sanitize_text_field($_POST['status'] ?? '');
    $status = empty($status) ? null : $status;

    $submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user_id, $status);
    $stats = Sisme_Game_Submission_Data_Manager::get_user_stats($user_id);

    wp_send_json_success([
        'submissions' => $submissions,
        'stats' => $stats,
        'total_count' => count($submissions)
    ]);
}

/**
 * Soumettre une soumission pour validation (draft → pending)
 */
function sisme_ajax_submit_game_for_review() {
    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }

    $user_id = get_current_user_id();
    $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');

    if (empty($submission_id)) {
        wp_send_json_error(['message' => 'ID de soumission manquant']);
        return;
    }

    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }

    $current_submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
    // Refuser si la soumission n'est pas un brouillon
    if (!$current_submission || $current_submission['status'] !== Sisme_Utils_Users::GAME_STATUS_DRAFT) {
        wp_send_json_error(['message' => 'Seuls les brouillons peuvent être soumis pour validation.']);
        return;
    }
    $result = Sisme_Game_Submission_Data_Manager::submit_for_review($user_id, $submission_id);

    if (is_wp_error($result)) {
        $error_code = $result->get_error_code();
        if ($error_code === 'validation_failed') {
            wp_send_json_error([
                'message' => $result->get_error_message(),
                'validation_error' => true
            ]);
        } else {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        return;
    }

    wp_send_json_success(['message' => 'Soumission envoyée pour validation']);
}

/**
 * Récupérer les détails d'une soumission
 */
function sisme_ajax_get_submission_details() {
    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }
    $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');
    $admin_user_id = intval($_POST['admin_user_id'] ?? 0);
    if (empty($submission_id)) {
        wp_send_json_error(['message' => 'ID de soumission manquant']);
        return;
    }
    if ($admin_user_id && $admin_user_id !== get_current_user_id()) {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes pour accéder aux soumissions d\'autres utilisateurs']);
            return;
        }
        $user_id = $admin_user_id;
    } else {
        $user_id = get_current_user_id();
    }
    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }
    $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
    if (!$submission) {
        wp_send_json_error(['message' => 'Soumission introuvable']);
        return;
    }
    if ($submission['metadata']['user_id'] ?? null && $submission['metadata']['user_id'] != $user_id) {
        wp_send_json_error(['message' => 'Soumission non autorisée']);
        return;
    }
    wp_send_json_success([
        'submission_id' => $submission_id,
        'user_id' => $user_id,
        'game_data' => $submission['game_data'],
        'metadata' => $submission['metadata'],
        'admin_data' => current_user_can('manage_options') ? ($submission['admin_data'] ?? []) : []
    ]);
}

/**
 * Récupérer les statistiques développeur
 */
function sisme_ajax_get_developer_game_stats() {
    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }

    $user_id = get_current_user_id();

    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }

    $stats = Sisme_Game_Submission_Data_Manager::get_user_stats($user_id);

    wp_send_json_success(['stats' => $stats]);
}

/**
 * Récupérer toutes les soumissions (admin)
 */
function sisme_ajax_admin_get_submissions() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permissions insuffisantes']);
        return;
    }

    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }

    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }

    $status = sanitize_text_field($_POST['status'] ?? '');
    $limit = intval($_POST['limit'] ?? 50);
    $offset = intval($_POST['offset'] ?? 0);

    $status = empty($status) ? null : $status;

    $submissions = Sisme_Game_Submission_Data_Manager::get_all_submissions_for_admin($status, $limit, $offset);

    wp_send_json_success([
        'submissions' => $submissions,
        'total_count' => count($submissions)
    ]);
}

/**
 * Vérifier le nonce de sécurité
 */
function sisme_verify_submission_nonce() {
    return wp_verify_nonce($_POST['security'] ?? '', 'sisme_developer_nonce');
}

/**
 * Charger le data manager si nécessaire
 */
function sisme_load_submission_data_manager() {
    if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
        $file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
        if (file_exists($file)) {
            require_once $file;
        } else {
            return false;
        }
    }
    
    return true;
}

/**
 * Collecter les données de jeu depuis $_POST
 */
function sisme_collect_game_data_from_post() {
    $game_data = [];

    $text_fields = [
        Sisme_Utils_Users::GAME_FIELD_NAME,
        Sisme_Utils_Users::GAME_FIELD_STUDIO_NAME,
        Sisme_Utils_Users::GAME_FIELD_PUBLISHER_NAME,
        Sisme_Utils_Users::GAME_FIELD_RELEASE_DATE
    ];

    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            $game_data[$field] = sanitize_text_field($_POST[$field]);
        }
    }

    if (isset($_POST[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION])) {
        $game_data[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION] = sanitize_textarea_field($_POST[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION]);
    }

    $url_fields = [
        Sisme_Utils_Users::GAME_FIELD_TRAILER,
        Sisme_Utils_Users::GAME_FIELD_STUDIO_URL,
        Sisme_Utils_Users::GAME_FIELD_PUBLISHER_URL
    ];

    foreach ($url_fields as $field) {
        if (isset($_POST[$field])) {
            $game_data[$field] = esc_url_raw($_POST[$field]);
        }
    }

    $array_fields = [
        Sisme_Utils_Users::GAME_FIELD_GENRES,
        Sisme_Utils_Users::GAME_FIELD_PLATFORMS,
        Sisme_Utils_Users::GAME_FIELD_MODES
    ];

    foreach ($array_fields as $field) {
        if (isset($_POST[$field]) && is_array($_POST[$field])) {
            $game_data[$field] = array_map('sanitize_text_field', $_POST[$field]);
        }
    }

    if (isset($_POST['external_links']) && is_array($_POST['external_links'])) {
        $external_links = [];
        foreach ($_POST['external_links'] as $platform => $url) {
            $platform = sanitize_text_field($platform);
            $url = esc_url_raw(trim($url));
            
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                $external_links[$platform] = $url;
            }
        }
        $game_data[Sisme_Utils_Users::GAME_FIELD_EXTERNAL_LINKS] = $external_links;
    }

    // Toujours envoyer covers même si vide
    $covers = [
        'horizontal' => isset($_POST['covers']['horizontal']) ? intval($_POST['covers']['horizontal']) : '',
        'vertical'   => isset($_POST['covers']['vertical']) ? intval($_POST['covers']['vertical']) : ''
    ];
    // Si les deux sont vides, on envoie quand même un tableau vide
    if (empty($covers['horizontal']) && empty($covers['vertical'])) {
        $game_data['covers'] = [];
    } else {
        $game_data['covers'] = $covers;
    }
    
    // Traitement des screenshots
    if (isset($_POST['screenshots'])) {
        $raw_screenshots = $_POST['screenshots'];
        
        if (is_array($raw_screenshots)) {
            // Format array : screenshots[] = [4222, 4223, 4224]
            $game_data['screenshots'] = array_map('intval', array_filter($raw_screenshots));
        } elseif (is_string($raw_screenshots) && !empty($raw_screenshots)) {
            // Format string : screenshots = "4222,4223,4224"
            $screenshots_ids = array_map('intval', explode(',', $raw_screenshots));
            $game_data['screenshots'] = array_filter($screenshots_ids);
        } else {
            $game_data['screenshots'] = [];
        }
    } else {
        $game_data['screenshots'] = [];
    }

    // Traitement des sections de description longue
    if (isset($_POST['sections']) && is_array($_POST['sections'])) {
        $sections = [];
        foreach ($_POST['sections'] as $section_data) {
            if (!empty($section_data['title']) && !empty($section_data['content'])) {
                $image_id = $section_data['image_id'] ?? '';
                $final_image_id = null;
                
                // Ne garder que les IDs numériques valides (ignorer les temp_)
                if (!empty($image_id) && !str_starts_with($image_id, 'temp_') && is_numeric($image_id)) {
                    $final_image_id = intval($image_id);
                }
                
                $sections[] = [
                    'title' => sanitize_text_field($section_data['title']),
                    'content' => sanitize_textarea_field($section_data['content']),
                    'image_attachment_id' => $final_image_id
                ];
            }
        }
        if (!empty($sections)) {
            $game_data[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION_SECTIONS] = $sections;
        }
    }

    return $game_data;
}

/**
 * Récupérer l'URL d'une image attachée
 */
add_action('wp_ajax_get_attachment_url', 'sisme_ajax_get_attachment_url');
function sisme_ajax_get_attachment_url() {
    if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_developer_nonce')) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
    }
    
    $attachment_id = intval($_POST['attachment_id'] ?? 0);
    if (!$attachment_id) {
        wp_send_json_error(['message' => 'ID attachment manquant']);
    }
    
    $url = wp_get_attachment_url($attachment_id);
    if (!$url) {
        wp_send_json_error(['message' => 'Image introuvable']);
    }
    
    wp_send_json_success([
        'url' => add_query_arg('ver', time(), $url),
        'attachment_id' => $attachment_id
    ]);
}

/**
 * Suppression d'une image (attachment) via AJAX pour le cropper
 */
add_action('wp_ajax_sisme_simple_crop_delete', function() {
    if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_developer_nonce')) {
        wp_send_json_error(['message' => 'Erreur de sécurité (nonce)']);
    }

    $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;
    if (!$attachment_id) {
        wp_send_json_error(['message' => 'ID d\'image manquant']);
    }

    $user_id = get_current_user_id();
    $can_delete = current_user_can('delete_post', $attachment_id);

    if (!$can_delete && $user_id) {
        if (!function_exists('sisme_load_submission_data_manager')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-ajax.php';
        }
        if (sisme_load_submission_data_manager()) {
            $submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user_id);
            foreach ($submissions as $submission) {
                $covers = $submission['game_data']['covers'] ?? [];
                if (in_array($attachment_id, $covers, true)) {
                    $can_delete = true;
                    break;
                }
            }
        }
    }

    if (!$can_delete) {
        wp_send_json_error(['message' => 'Droits insuffisants pour supprimer cette image']);
    }

    $deleted = wp_delete_attachment($attachment_id, true);
    if ($deleted) {
        wp_send_json_success(['message' => 'Image supprimée']);
    } else {
        wp_send_json_error(['message' => 'Erreur lors de la suppression']);
    }
});

// Handler AJAX pour recharger dynamiquement la liste des soumissions du dashboard développeur
add_action('wp_ajax_sisme_reload_submissions_list', 'sisme_reload_submissions_list_callback');
function sisme_reload_submissions_list_callback() {
    $nonce_valid = false;
    if (isset($_POST['security'])) {
        if (wp_verify_nonce($_POST['security'], 'sisme_submit_game_nonce')) {
            $nonce_valid = true;
        } elseif (wp_verify_nonce($_POST['security'], 'sisme_developer_nonce')) {
            $nonce_valid = true;
        }
    }
    if (!$nonce_valid) {
        wp_send_json_error('Accès non autorisé (nonce)');
    }
    if (!is_user_logged_in()) {
        wp_send_json_error('Vous devez être connecté.');
    }
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('Utilisateur non trouvé.');
    }
    // Inclure le renderer si besoin
    if (!class_exists('Sisme_Game_Submission_Renderer')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-renderer.php';
    }
    // Retourner le HTML complet du bloc Mes Soumissions
    echo Sisme_Game_Submission_Renderer::render_submissions_list($user_id);
    wp_die();
}

/**
 * Convertir les IDs de taxonomies en noms via AJAX
 */
function sisme_ajax_convert_taxonomy_ids() {
    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }

    $ids = $_POST['ids'] ?? [];
    $taxonomy = sanitize_text_field($_POST['taxonomy'] ?? '');

    if (empty($ids) || !is_array($ids)) {
        wp_send_json_success(['names' => []]);
        return;
    }

    $names = [];

    switch ($taxonomy) {
        case 'genre':
            // Utiliser la fonction existante de utils-formatting
            if (class_exists('Sisme_Utils_Formatting')) {
                $names = Sisme_Utils_Formatting::convert_genre_ids_to_slugs($ids);
            } else {
                // Fallback manuel
                foreach ($ids as $id) {
                    $category = get_category(intval($id));
                    if ($category && !is_wp_error($category)) {
                        $clean_name = preg_replace('/^jeux-/i', '', $category->name);
                        $names[] = $clean_name;
                    }
                }
            }
            break;

        case 'plateforme':
            // Mapper les slugs de plateformes
            $platform_names = [
                'windows' => 'Windows',
                'mac' => 'Mac', 
                'linux' => 'Linux',
                'playstation' => 'PlayStation',
                'xbox' => 'Xbox',
                'nintendo' => 'Nintendo Switch',
                'android' => 'Android',
                'ios' => 'iOS'
            ];
            
            foreach ($ids as $id) {
                $names[] = $platform_names[$id] ?? ucfirst($id);
            }
            break;

        case 'mode-de-jeu':
            // Mapper les modes de jeu
            $mode_names = [
                'solo' => 'Solo',
                'multijoueur' => 'Multijoueur', 
                'coop' => 'Coopératif',
                'pvp' => 'Joueur vs Joueur',
                'mmo' => 'MMO'
            ];
            
            foreach ($ids as $id) {
                $names[] = $mode_names[$id] ?? ucfirst($id);
            }
            break;

        default:
            // Taxonomie générique WordPress
            foreach ($ids as $id) {
                $term = get_term(intval($id), $taxonomy);
                if ($term && !is_wp_error($term)) {
                    $names[] = $term->name;
                }
            }
            break;
    }

    wp_send_json_success(['names' => array_unique($names)]);
}

/**
 * Créer une révision d'un jeu publié
 */
function sisme_ajax_create_game_revision() {
    if (!sisme_verify_submission_nonce()) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }

    $user_id = get_current_user_id();
    $original_submission_id = sanitize_text_field($_POST['original_submission_id'] ?? '');
    $revision_reason = sanitize_textarea_field($_POST['revision_reason'] ?? '');

    if (empty($original_submission_id)) {
        wp_send_json_error(['message' => 'ID de soumission originale manquant']);
        return;
    }

    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }

    // Créer la révision
    $revision_id = Sisme_Game_Submission_Data_Manager::create_revision($user_id, $original_submission_id, 'major');

    if (is_wp_error($revision_id)) {
        wp_send_json_error(['message' => $revision_id->get_error_message()]);
        return;
    }

    // Ajouter la raison si fournie
    if (!empty($revision_reason)) {
        $revision_submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $revision_id);
        if ($revision_submission) {
            $revision_submission['metadata']['revision_reason'] = $revision_reason;
            
            // Mettre à jour directement dans les données utilisateur pour préserver les métadonnées
            $user_data = get_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, true);
            if (!empty($user_data['submissions'])) {
                foreach ($user_data['submissions'] as $index => $submission) {
                    if ($submission['id'] === $revision_id) {
                        $user_data['submissions'][$index]['metadata']['revision_reason'] = $revision_reason;
                        update_user_meta($user_id, Sisme_Utils_Users::META_GAME_SUBMISSIONS, $user_data);
                        break;
                    }
                }
            }
        }
    }

    wp_send_json_success([
        'revision_id' => $revision_id,
        'message' => 'Brouillon de révision créé avec succès'
    ]);
}

/**
 * AJAX: Récupérer les détails d'une archive
 */
function sisme_ajax_get_archive_details() {
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Token de sécurité invalide']);
        return;
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }

    $user_id = get_current_user_id();
    $archive_id = sanitize_text_field($_POST['archive_id'] ?? '');

    if (empty($archive_id)) {
        wp_send_json_error(['message' => 'ID d\'archive manquant']);
        return;
    }

    if (!sisme_load_submission_data_manager()) {
        wp_send_json_error(['message' => 'Système de soumission non disponible']);
        return;
    }

    // Récupérer l'archive
    $archive = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $archive_id);
    
    if (!$archive || $archive['status'] !== 'archived') {
        wp_send_json_error(['message' => 'Archive introuvable']);
        return;
    }

    // Générer le HTML des détails
    $html = sisme_render_archive_details_html($archive);

    wp_send_json_success([
        'html' => $html,
        'archive' => $archive
    ]);
}

/**
 * Générer le HTML des détails d'une archive
 * @param array $archive Données de l'archive
 * @return string HTML des détails
 */
function sisme_render_archive_details_html($archive) {
    $game_data = $archive['game_data'] ?? [];
    $metadata = $archive['metadata'] ?? [];
    
    ob_start();
    ?>
    <div class="sisme-archive-details-content">
        <div class="sisme-archive-info">
            <?php if (!empty($game_data[Sisme_Utils_Users::GAME_FIELD_NAME])): ?>
                <p><strong>Nom du jeu :</strong> <?php echo esc_html($game_data[Sisme_Utils_Users::GAME_FIELD_NAME]); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($game_data[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION])): ?>
                <p><strong>Description :</strong></p>
                <div class="sisme-archive-description">
                    <?php echo wp_kses_post(nl2br($game_data[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION])); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($metadata['revision_reason'])): ?>
                <p><strong>Raison de la révision :</strong> <?php echo esc_html($metadata['revision_reason']); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($metadata['created_at'])): ?>
                <p><strong>Créée le :</strong> <?php echo date('d/m/Y H:i', strtotime($metadata['created_at'])); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($metadata['archived_at'])): ?>
                <p><strong>Archivée le :</strong> <?php echo date('d/m/Y H:i', strtotime($metadata['archived_at'])); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}