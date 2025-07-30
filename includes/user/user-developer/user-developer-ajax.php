<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/user-developer-ajax.php
 * Handlers AJAX pour le module développeur utilisateur (NETTOYÉ)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialiser les hooks AJAX pour le module développeur
 */
function sisme_init_developer_ajax() {
    // Actions AJAX pour candidatures développeur
    add_action('wp_ajax_sisme_developer_submit', 'sisme_ajax_developer_submit');
    add_action('wp_ajax_sisme_developer_reset_rejection', 'sisme_ajax_developer_reset_rejection');
    
    // Upload d'images (système de crop)
    add_action('wp_ajax_sisme_simple_crop_upload', 'sisme_handle_simple_crop_upload');

    // Actions AJAX pour utilisateurs non connectés
    add_action('wp_ajax_nopriv_sisme_developer_submit', 'sisme_ajax_not_logged_in');
    add_action('wp_ajax_nopriv_sisme_developer_reset_rejection', 'sisme_ajax_not_logged_in');

    // Initialiser les handlers AJAX des soumissions de jeux
    if (function_exists('sisme_init_game_submission_ajax')) {
        sisme_init_game_submission_ajax();
    }
}

/**
 * Handler AJAX pour soumission candidature développeur
 */
function sisme_ajax_developer_submit() {
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
            'message' => 'Vous devez être connecté pour effectuer cette action.',
            'code' => 'not_logged_in'
        ]);
    }
    
    $user_id = get_current_user_id();
    
    if (!Sisme_Utils_Users::can_apply_as_developer($user_id)) {
        $current_status = Sisme_Utils_Users::get_user_dev_data($user_id, 'status');
        wp_send_json_error([
            'message' => 'Vous ne pouvez pas soumettre de candidature en ce moment.',
            'code' => 'cannot_apply',
            'current_status' => $current_status
        ]);
    }
    
    $application_data = sisme_sanitize_developer_form_data($_POST);
    $validation_errors = sisme_validate_developer_form_data($application_data);
    
    if (!empty($validation_errors)) {
        wp_send_json_error([
            'message' => 'Veuillez corriger les erreurs dans le formulaire.',
            'code' => 'validation_errors',
            'errors' => $validation_errors
        ]);
    }
    
    $result = Sisme_User_Developer_Data_Manager::save_developer_application($user_id, $application_data);
    
    if (!$result) {
        wp_send_json_error([
            'message' => 'Erreur lors de la sauvegarde de votre candidature. Veuillez réessayer.',
            'code' => 'save_failed'
        ]);
    }
    
    do_action('sisme_developer_application_submitted', $user_id);
    
    $new_status = Sisme_Utils_Users::get_user_dev_data($user_id, 'status');
    
    wp_send_json_success([
        'message' => 'Votre candidature a été soumise avec succès ! Vous recevrez une notification dès qu\'elle sera examinée.',
        'code' => 'application_submitted',
        'new_status' => $new_status,
        'reload_dashboard' => true
    ]);
}

/**
 * Handler AJAX pour reset d'une candidature rejetée
 */
function sisme_ajax_developer_reset_rejection() {
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
            'message' => 'Vous devez être connecté pour effectuer cette action.',
            'code' => 'not_logged_in'
        ]);
    }
    
    $user_id = get_current_user_id();
    
    $current_status = Sisme_User_Developer_Data_Manager::get_developer_status($user_id);
    if ($current_status !== Sisme_Utils_Users::DEVELOPER_STATUS_REJECTED) {
        wp_send_json_error([
            'message' => 'Vous ne pouvez pas refaire de candidature dans votre état actuel.',
            'code' => 'invalid_status',
            'current_status' => $current_status
        ]);
    }
    
    Sisme_User_Developer_Data_Manager::set_developer_status($user_id, Sisme_Utils_Users::DEVELOPER_STATUS_NONE);
    
    delete_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_APPLICATION);
    
    wp_send_json_success([
        'message' => 'Vous pouvez maintenant refaire une candidature.',
        'code' => 'reset_successful',
        'new_status' => Sisme_Utils_Users::DEVELOPER_STATUS_NONE,
        'reload_dashboard' => true
    ]);
}

/**
 * Handler AJAX pour upload et crop d'images
 */
function sisme_handle_simple_crop_upload() {
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error(['message' => 'Erreur de sécurité']);
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
        return;
    }
    
    if (!class_exists('Sisme_Simple_Image_Cropper')) {
        $cropper_file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/simple-image-cropper.php';
        if (file_exists($cropper_file)) {
            require_once $cropper_file;
        } else {
            wp_send_json_error(['message' => 'Système de crop non disponible']);
            return;
        }
    }
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(['message' => 'Erreur lors de l\'upload du fichier']);
        return;
    }
    
    $result = Sisme_Simple_Image_Cropper::process_upload($_FILES['image']);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
        return;
    }
    
    $attachment_url = wp_get_attachment_url($result);
    
    wp_send_json_success([
        'attachment_id' => $result,
        'url' => $attachment_url,
        'message' => 'Image uploadée avec succès'
    ]);
}

/**
 * Handler AJAX pour utilisateurs non connectés
 */
function sisme_ajax_not_logged_in() {
    wp_send_json_error([
        'message' => 'Vous devez être connecté pour effectuer cette action.',
        'code' => 'not_logged_in'
    ]);
}

/**
 * Sanitiser les données du formulaire développeur
 */
function sisme_sanitize_developer_form_data($raw_data) {
    $sanitized = [];
    
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME] = sanitize_text_field($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME] ?? '');
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION] = sanitize_textarea_field($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION] ?? '');
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_WEBSITE] = esc_url_raw($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_WEBSITE] ?? '');
    
    if (isset($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS]) && is_array($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS])) {
        $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS] = array_map('esc_url_raw', $raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS]);
    } else {
        $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS] = [];
    }
    
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME] = sanitize_text_field($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME] ?? '');
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME] = sanitize_text_field($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME] ?? '');
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE] = sanitize_text_field($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE] ?? '');
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS] = sanitize_textarea_field($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS] ?? '');
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY] = sanitize_text_field($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY] ?? '');
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY] = sanitize_text_field($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY] ?? '');
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_EMAIL] = sanitize_email($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_EMAIL] ?? '');
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_PHONE] = sanitize_text_field($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_PHONE] ?? '');
    
    return $sanitized;
}

/**
 * Valider les données du formulaire développeur
 */
function sisme_validate_developer_form_data($data) {
    $errors = [];
    
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME])) {
        $errors['studio_name'] = 'Le nom du studio est obligatoire';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME]) < 2) {
        $errors['studio_name'] = 'Le nom du studio doit faire au moins 2 caractères';
    }
    
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION])) {
        $errors['studio_description'] = 'La description du studio est obligatoire';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION]) < 10) {
        $errors['studio_description'] = 'La description doit faire au moins 10 caractères';
    }
    
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME])) {
        $errors['representative_firstname'] = 'Le prénom du représentant est obligatoire';
    }
    
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME])) {
        $errors['representative_lastname'] = 'Le nom du représentant est obligatoire';
    }
    
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE])) {
        $errors['representative_birthdate'] = 'La date de naissance est obligatoire';
    } else {
        $birthdate = strtotime($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE]);
        $age = floor((time() - $birthdate) / (365.25 * 24 * 60 * 60));
        if ($age < 18) {
            $errors['representative_birthdate'] = 'Vous devez être majeur pour candidater';
        }
    }
    
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_EMAIL])) {
        $errors['representative_email'] = 'L\'email du représentant est obligatoire';
    } elseif (!is_email($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_EMAIL])) {
        $errors['representative_email'] = 'Format d\'email invalide';
    }
    
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS])) {
        $errors['representative_address'] = 'L\'adresse est obligatoire';
    }
    
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY])) {
        $errors['representative_city'] = 'La ville est obligatoire';
    }
    
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY])) {
        $errors['representative_country'] = 'Le pays est obligatoire';
    }
    
    return $errors;
}