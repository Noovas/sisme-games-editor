<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/submission-game/submission-game-ajax.php
 * Handlers AJAX pour le module d'édition de soumissions de jeux
 * 
 * RESPONSABILITÉ:
 * - Sauvegarde des soumissions (draft/pending)
 * - Validation des données soumises
 * - Chargement des données d'édition
 * - Sécurité et permissions
 */

if (!defined('ABSPATH')) {
    exit;
}

function sisme_init_submission_game_ajax() {
    add_action('wp_ajax_sisme_save_submission_game', 'sisme_ajax_save_submission_game');
    add_action('wp_ajax_sisme_submit_submission_game', 'sisme_ajax_submit_submission_game');
    add_action('wp_ajax_sisme_load_submission_game', 'sisme_ajax_load_submission_game');
    
    add_action('wp_ajax_nopriv_sisme_save_submission_game', 'sisme_ajax_not_logged_in_submission');
    add_action('wp_ajax_nopriv_sisme_submit_submission_game', 'sisme_ajax_not_logged_in_submission');
    add_action('wp_ajax_nopriv_sisme_load_submission_game', 'sisme_ajax_not_logged_in_submission');
    
    add_action('wp_ajax_sisme_render_submission_editor', 'sisme_ajax_render_submission_editor');
    add_action('wp_ajax_nopriv_sisme_render_submission_editor', 'sisme_ajax_not_logged_in_submission');

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Sisme Submission Game] Hooks AJAX enregistrés');
    }
}

function sisme_ajax_not_logged_in_submission() {
    wp_send_json_error([
        'message' => 'Vous devez être connecté pour effectuer cette action.',
        'code' => 'not_logged_in'
    ]);
}

function sisme_ajax_save_submission_game() {
    if (ob_get_length()) {
        ob_clean();
    }
    
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'code' => 'invalid_nonce'
        ]);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Vous devez être connecté.',
            'code' => 'not_logged_in'
        ]);
    }
    
    $user_id = get_current_user_id();
    
    if (!class_exists('Sisme_User_Developer_Data_Manager')) {
        wp_send_json_error([
            'message' => 'Module développeur non disponible.',
            'code' => 'module_unavailable'
        ]);
    }
    
    if (!Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
        wp_send_json_error([
            'message' => 'Vous n\'êtes pas autorisé à soumettre des jeux.',
            'code' => 'permission_denied'
        ]);
    }
    
    $submission_id = intval($_POST['submission_id'] ?? 0);
    $game_name = sanitize_text_field($_POST['game_name'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    
    $validation_result = sisme_validate_submission_game_data($game_name, $description, false);
    if ($validation_result !== true) {
        wp_send_json_error([
            'message' => 'Données invalides',
            'code' => 'validation_failed',
            'validation_errors' => $validation_result
        ]);
    }
    
    if (!class_exists('Sisme_Submission_Database')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
    }
    
    $game_data = [
        'game_name' => $game_name,
        'description' => $description,
        'metadata' => [
            'completion_percentage' => sisme_calculate_completion_percentage($game_name, $description),
            'last_step_completed' => 'basic',
            'last_saved' => current_time('mysql')
        ]
    ];
    
    if ($submission_id) {
        $result = Sisme_Submission_Database::update_submission($submission_id, $game_data, $user_id);
        $action = 'updated';
    } else {
        $result = Sisme_Submission_Database::create_submission($user_id, $game_data);
        $action = 'created';
        $submission_id = $result;
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error([
            'message' => 'Erreur lors de la sauvegarde: ' . $result->get_error_message(),
            'code' => 'save_failed'
        ]);
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Sisme Submission Game] Soumission $action: ID $submission_id");
    }
    
    wp_send_json_success([
        'message' => 'Brouillon sauvegardé avec succès',
        'submission_id' => $submission_id,
        'action' => $action,
        'completion_percentage' => $game_data['metadata']['completion_percentage']
    ]);
}

function sisme_ajax_submit_submission_game() {
    if (ob_get_length()) {
        ob_clean();
    }
    
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'code' => 'invalid_nonce'
        ]);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Vous devez être connecté.',
            'code' => 'not_logged_in'
        ]);
    }
    
    $user_id = get_current_user_id();
    
    if (!Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
        wp_send_json_error([
            'message' => 'Vous n\'êtes pas autorisé à soumettre des jeux.',
            'code' => 'permission_denied'
        ]);
    }
    
    $submission_id = intval($_POST['submission_id'] ?? 0);
    $game_name = sanitize_text_field($_POST['game_name'] ?? '');
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    
    $validation_result = sisme_validate_submission_game_data($game_name, $description, true);
    if ($validation_result !== true) {
        wp_send_json_error([
            'message' => 'Validation échouée',
            'code' => 'validation_failed',
            'validation_errors' => $validation_result
        ]);
    }
    
    if (!class_exists('Sisme_Submission_Database')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
    }
    
    $game_data = [
        'game_name' => $game_name,
        'description' => $description,
        'metadata' => [
            'completion_percentage' => 100,
            'last_step_completed' => 'submitted',
            'submitted_at' => current_time('mysql')
        ]
    ];
    
    if ($submission_id) {
        $result = Sisme_Submission_Database::update_submission($submission_id, $game_data, $user_id);
        if (!is_wp_error($result)) {
            $result = Sisme_Submission_Database::update_submission_status($submission_id, 'pending', $user_id);
        }
    } else {
        $result = Sisme_Submission_Database::create_submission($user_id, $game_data);
        if (!is_wp_error($result)) {
            $submission_id = $result;
            $result = Sisme_Submission_Database::update_submission_status($submission_id, 'pending', $user_id);
        }
    }
    
    if (is_wp_error($result)) {
        wp_send_json_error([
            'message' => 'Erreur lors de la soumission: ' . $result->get_error_message(),
            'code' => 'submit_failed'
        ]);
    }
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Sisme Submission Game] Soumission envoyée pour validation: ID $submission_id");
    }
    
    wp_send_json_success([
        'message' => 'Soumission envoyée avec succès ! Elle sera examinée par notre équipe.',
        'submission_id' => $submission_id,
        'status' => 'pending'
    ]);
}

function sisme_ajax_render_submission_editor() {
    if (ob_get_length()) {
        ob_clean();
    }
    
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'code' => 'invalid_nonce'
        ]);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Vous devez être connecté.',
            'code' => 'not_logged_in'
        ]);
    }
    
    $user_id = get_current_user_id();
    
    if (!Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
        wp_send_json_error([
            'message' => 'Vous n\'êtes pas autorisé à accéder à cette fonctionnalité.',
            'code' => 'permission_denied'
        ]);
    }
    
    $submission_id = intval($_POST['submission_id'] ?? 0);
    
    // Vérifier que la soumission appartient à l'utilisateur si un ID est fourni
    if ($submission_id) {
        if (!class_exists('Sisme_Submission_Database')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
        }
        
        $submission = Sisme_Submission_Database::get_submission($submission_id);
        
        if (!$submission || $submission->user_id != $user_id) {
            wp_send_json_error([
                'message' => 'Soumission introuvable ou accès refusé',
                'code' => 'access_denied'
            ]);
        }
        
        // Vérifier que la soumission est éditable
        if (!in_array($submission->status, ['draft', 'revision'])) {
            wp_send_json_error([
                'message' => 'Cette soumission n\'est plus éditable',
                'code' => 'not_editable'
            ]);
        }
    }
    
    // Charger la classe renderer directement
    if (!class_exists('Sisme_Submission_Game_Renderer')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission-game/submission-game-renderer.php';
    }

    // Générer le HTML de l'éditeur
    $html = Sisme_Submission_Game_Renderer::render_editor($submission_id);

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Sisme Submission Game] Éditeur rendu pour soumission: " . ($submission_id ?: 'nouvelle'));
    }
    
    wp_send_json_success([
        'html' => $html,
        'submission_id' => $submission_id
    ]);
}

function sisme_ajax_load_submission_game() {
    if (ob_get_length()) {
        ob_clean();
    }
    
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'code' => 'invalid_nonce'
        ]);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Vous devez être connecté.',
            'code' => 'not_logged_in'
        ]);
    }
    
    $user_id = get_current_user_id();
    $submission_id = intval($_POST['submission_id'] ?? 0);
    
    if (!$submission_id) {
        wp_send_json_error([
            'message' => 'ID de soumission manquant',
            'code' => 'missing_id'
        ]);
    }
    
    if (!class_exists('Sisme_Submission_Database')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
    }
    
    $submission = Sisme_Submission_Database::get_submission($submission_id);
    
    if (!$submission || $submission->user_id != $user_id) {
        wp_send_json_error([
            'message' => 'Soumission introuvable ou accès refusé',
            'code' => 'not_found'
        ]);
    }
    
    $game_data = $submission->game_data_decoded ?: [];
    
    wp_send_json_success([
        'submission_id' => $submission->id,
        'game_name' => $game_data['game_name'] ?? '',
        'description' => $game_data['description'] ?? '',
        'status' => $submission->status,
        'completion_percentage' => $game_data['metadata']['completion_percentage'] ?? 0,
        'last_updated' => $submission->updated_at
    ]);
}

function sisme_validate_submission_game_data($game_name, $description, $strict = false) {
    $errors = [];
    
    if (empty($game_name)) {
        $errors['game_name'] = 'Le nom du jeu est obligatoire';
    } elseif (strlen($game_name) < 3) {
        $errors['game_name'] = 'Le nom du jeu doit faire au moins 3 caractères';
    } elseif (strlen($game_name) > 100) {
        $errors['game_name'] = 'Le nom du jeu ne peut pas dépasser 100 caractères';
    }
    
    if (empty($description)) {
        $errors['description'] = 'La description est obligatoire';
    } elseif ($strict && strlen($description) < 140) {
        $errors['description'] = 'La description doit faire au moins 140 caractères';
    } elseif (strlen($description) > 180) {
        $errors['description'] = 'La description ne peut pas dépasser 180 caractères';
    }
    
    return empty($errors) ? true : $errors;
}

function sisme_calculate_completion_percentage($game_name, $description) {
    $score = 0;
    
    if (!empty($game_name)) {
        $score += 50;
    }
    
    if (!empty($description)) {
        if (strlen($description) >= 140) {
            $score += 50;
        } else {
            $score += 25;
        }
    }
    
    return $score;
}