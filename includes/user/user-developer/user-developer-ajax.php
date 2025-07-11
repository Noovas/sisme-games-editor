<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/user-developer-ajax.php
 * Handlers AJAX pour le module développeur utilisateur
 * 
 * RESPONSABILITÉ:
 * - Gestion des requêtes AJAX pour candidature développeur
 * - Validation sécurité et permissions utilisateur
 * - Sauvegarde des candidatures avec changement de statut
 * - Réponses JSON structurées pour le frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialiser les hooks AJAX pour le module développeur
 */
function sisme_init_developer_ajax() {
    // Actions AJAX pour utilisateurs connectés
    add_action('wp_ajax_sisme_developer_submit', 'sisme_ajax_developer_submit');
    add_action('wp_ajax_sisme_developer_reset_rejection', 'sisme_ajax_developer_reset_rejection'); 
    
    // ✅ NOUVEAUX HANDLERS POUR MES JEUX
    add_action('wp_ajax_sisme_create_submission', 'sisme_ajax_create_submission');
    add_action('wp_ajax_sisme_delete_submission', 'sisme_ajax_delete_submission');
    add_action('wp_ajax_sisme_get_submission_details', 'sisme_ajax_get_submission_details');
    add_action('wp_ajax_sisme_retry_submission', 'sisme_ajax_retry_submission');
    add_action('wp_ajax_sisme_get_developer_stats', 'sisme_ajax_get_developer_stats');
    
    // Actions AJAX pour utilisateurs non connectés
    add_action('wp_ajax_nopriv_sisme_developer_submit', 'sisme_ajax_not_logged_in');
    add_action('wp_ajax_nopriv_sisme_developer_reset_rejection', 'sisme_ajax_not_logged_in');
    
    // ✅ NOUVEAUX HANDLERS NON CONNECTÉS
    add_action('wp_ajax_nopriv_sisme_create_submission', 'sisme_ajax_not_logged_in');
    add_action('wp_ajax_nopriv_sisme_delete_submission', 'sisme_ajax_not_logged_in');
    add_action('wp_ajax_nopriv_sisme_get_submission_details', 'sisme_ajax_not_logged_in');
    add_action('wp_ajax_nopriv_sisme_retry_submission', 'sisme_ajax_not_logged_in');
    add_action('wp_ajax_nopriv_sisme_get_developer_stats', 'sisme_ajax_not_logged_in');
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Sisme User Developer Ajax] Hooks AJAX enregistrés (avec soumissions)');
    }
}

/**
 * Handler AJAX pour reset d'une candidature rejetée
 */
function sisme_ajax_developer_reset_rejection() {
    // Nettoyer le buffer de sortie
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Vérifier le nonce de sécurité
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'code' => 'invalid_nonce'
        ]);
    }
    
    // Vérifier que l'utilisateur est connecté
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Vous devez être connecté pour effectuer cette action.',
            'code' => 'not_logged_in'
        ]);
    }
    
    $user_id = get_current_user_id();
    
    // Vérifier que l'utilisateur a bien un statut 'rejected'
    $current_status = Sisme_User_Developer_Data_Manager::get_developer_status($user_id);
    if ($current_status !== Sisme_Utils_Users::DEVELOPER_STATUS_REJECTED) {
        wp_send_json_error([
            'message' => 'Vous ne pouvez pas refaire de candidature dans votre état actuel.',
            'code' => 'invalid_status',
            'current_status' => $current_status
        ]);
    }
    
    // Reset du statut vers 'none'
    $result = Sisme_User_Developer_Data_Manager::set_developer_status($user_id, Sisme_Utils_Users::DEVELOPER_STATUS_NONE);
    
    if (!$result) {
        wp_send_json_error([
            'message' => 'Erreur lors du reset de votre candidature. Veuillez réessayer.',
            'code' => 'reset_failed'
        ]);
    }
    
    // Optionnel : Supprimer les anciennes données de candidature pour un fresh start
    delete_user_meta($user_id, Sisme_Utils_Users::META_DEVELOPER_APPLICATION);
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Sisme User Developer Ajax] Reset candidature rejetée - User ID: $user_id");
    }
    
    // Retourner le succès
    wp_send_json_success([
        'message' => 'Vous pouvez maintenant refaire une candidature !',
        'code' => 'rejection_reset',
        'new_status' => Sisme_Utils_Users::DEVELOPER_STATUS_NONE,
        'reload_dashboard' => true
    ]);
}

/**
 * Handler AJAX pour soumission candidature développeur
 */
function sisme_ajax_developer_submit() {
    // Nettoyer le buffer de sortie
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Vérifier le nonce de sécurité
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'code' => 'invalid_nonce'
        ]);
    }
    
    // Vérifier que l'utilisateur est connecté
    if (!is_user_logged_in()) {
        wp_send_json_error([
            'message' => 'Vous devez être connecté pour soumettre une candidature.',
            'code' => 'not_logged_in'
        ]);
    }
    
    $user_id = get_current_user_id();
    
    // Vérifier que l'utilisateur peut candidater
    if (!Sisme_Utils_Users::can_apply_as_developer($user_id)) {
        $current_status = Sisme_Utils_Users::get_user_dev_data($user_id, 'status');
        wp_send_json_error([
            'message' => 'Vous ne pouvez pas soumettre de candidature en ce moment.',
            'code' => 'cannot_apply',
            'current_status' => $current_status
        ]);
    }
    
    // Sanitiser et valider les données du formulaire
    $application_data = sisme_sanitize_developer_form_data($_POST);
    $validation_errors = sisme_validate_developer_form_data($application_data);
    
    if (!empty($validation_errors)) {
        wp_send_json_error([
            'message' => 'Veuillez corriger les erreurs dans le formulaire.',
            'code' => 'validation_errors',
            'errors' => $validation_errors
        ]);
    }
    
    // Sauvegarder la candidature
    $result = Sisme_User_Developer_Data_Manager::save_developer_application($user_id, $application_data);
    
    if (!$result) {
        wp_send_json_error([
            'message' => 'Erreur lors de la sauvegarde de votre candidature. Veuillez réessayer.',
            'code' => 'save_failed'
        ]);
    }
    
    do_action('sisme_developer_application_submitted', $user_id);
    
    // Vérifier le changement de statut
    $new_status = Sisme_Utils_Users::get_user_dev_data($user_id, 'status');
    
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Sisme User Developer Ajax] Candidature soumise avec succès - User ID: $user_id, Nouveau statut: $new_status");
    }
    
    // Retourner le succès
    wp_send_json_success([
        'message' => 'Votre candidature a été soumise avec succès ! Vous recevrez une notification dès qu\'elle sera examinée.',
        'code' => 'application_submitted',
        'new_status' => $new_status,
        'reload_dashboard' => true
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
 * 
 * @param array $raw_data Données brutes du formulaire
 * @return array Données sanitisées
 */
function sisme_sanitize_developer_form_data($raw_data) {
    $sanitized = [];
    
    // Informations studio
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME] = sanitize_text_field($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME] ?? '');
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION] = sanitize_textarea_field($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION] ?? '');
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_WEBSITE] = esc_url_raw($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_WEBSITE] ?? '');
    
    // Liens sociaux
    $social_links = [];
    if (isset($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS]) && is_array($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS])) {
        foreach ($raw_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS] as $platform => $value) {
            if (!empty($value)) {
                // Tous les réseaux sociaux sont traités comme des URLs
                $social_links[sanitize_key($platform)] = esc_url_raw($value);
            }
        }
    }
    $sanitized[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS] = $social_links;
    
    // Informations représentant
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
 * 
 * @param array $data Données sanitisées
 * @return array Erreurs de validation
 */
function sisme_validate_developer_form_data($data) {
    $errors = [];
    
    // Validation informations studio
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME])) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME] = 'Le nom du studio est requis.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME]) < 2) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME] = 'Le nom du studio doit contenir au moins 2 caractères.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME]) > 100) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME] = 'Le nom du studio ne peut pas dépasser 100 caractères.';
    }
    
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION])) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION] = 'La description du studio est requise.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION]) < 10) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION] = 'La description du studio doit contenir au moins 10 caractères.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION]) > 1000) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_DESCRIPTION] = 'La description du studio ne peut pas dépasser 1000 caractères.';
    }
    
    // Validation site web (optionnel)
    if (!empty($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_WEBSITE])) {
        if (!filter_var($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_WEBSITE], FILTER_VALIDATE_URL)) {
            $errors[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_WEBSITE] = 'L\'URL du site web n\'est pas valide.';
        }
    }
    
    // Validation liens sociaux (tous doivent être des URLs)
    if (!empty($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS])) {
        $platform_domains = [
            'twitter' => ['twitter.com', 'x.com'],
            'discord' => ['discord.gg', 'discord.com', 'discordapp.com'],
            'instagram' => ['instagram.com'],
            'youtube' => ['youtube.com', 'youtu.be'],
            'twitch' => ['twitch.tv'],
            'facebook' => ['facebook.com', 'fb.com'],
            'linkedin' => ['linkedin.com']
        ];
        
        foreach ($data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_SOCIAL_LINKS] as $platform => $value) {
            if (!empty($value)) {
                // Vérifier que c'est une URL valide
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors["social_{$platform}"] = "Le lien {$platform} doit être une URL valide.";
                    continue;
                }
                
                // Vérifier le domaine spécifique à la plateforme
                if (isset($platform_domains[$platform])) {
                    $valid_domain = false;
                    foreach ($platform_domains[$platform] as $domain) {
                        if (str_contains(strtolower($value), $domain)) {
                            $valid_domain = true;
                            break;
                        }
                    }
                    
                    if (!$valid_domain) {
                        $allowed_domains = implode(', ', $platform_domains[$platform]);
                        $errors["social_{$platform}"] = "Le lien {$platform} doit contenir un domaine valide ({$allowed_domains}).";
                    }
                }
            }
        }
    }
    
    // Validation informations représentant
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME])) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME] = 'Le prénom est requis.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME]) < 2) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME] = 'Le prénom doit contenir au moins 2 caractères.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME]) > 50) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_FIRSTNAME] = 'Le prénom ne peut pas dépasser 50 caractères.';
    }
    
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME])) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME] = 'Le nom est requis.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME]) < 2) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME] = 'Le nom doit contenir au moins 2 caractères.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME]) > 50) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_LASTNAME] = 'Le nom ne peut pas dépasser 50 caractères.';
    }
    
    // Validation date de naissance
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE])) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE] = 'La date de naissance est requise.';
    } else {
        $birthdate = DateTime::createFromFormat('Y-m-d', $data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE]);
        if (!$birthdate || $birthdate->format('Y-m-d') !== $data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE]) {
            $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE] = 'La date de naissance n\'est pas valide.';
        } else {
            $age = $birthdate->diff(new DateTime())->y;
            if ($age < 18) {
                $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_BIRTHDATE] = 'Vous devez avoir au moins 18 ans pour candidater.';
            }
        }
    }
    
    // Validation adresse
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS])) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS] = 'L\'adresse est requise.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS]) < 5) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS] = 'L\'adresse doit contenir au moins 5 caractères.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS]) > 200) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_ADDRESS] = 'L\'adresse ne peut pas dépasser 200 caractères.';
    }
    
    // Validation ville
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY])) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY] = 'La ville est requise.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY]) < 2) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY] = 'La ville doit contenir au moins 2 caractères.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY]) > 100) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_CITY] = 'La ville ne peut pas dépasser 100 caractères.';
    }
    
    // Validation pays
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY])) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY] = 'Le pays est requis.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY]) < 2) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY] = 'Le pays doit contenir au moins 2 caractères.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY]) > 100) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_COUNTRY] = 'Le pays ne peut pas dépasser 100 caractères.';
    }
    
    // Validation email
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_EMAIL])) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_EMAIL] = 'L\'email est requis.';
    } elseif (!is_email($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_EMAIL])) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_EMAIL] = 'L\'email n\'est pas valide.';
    }
    
    // Validation téléphone
    if (empty($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_PHONE])) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_PHONE] = 'Le téléphone est requis.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_PHONE]) < 8) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_PHONE] = 'Le numéro de téléphone doit contenir au moins 8 caractères.';
    } elseif (strlen($data[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_PHONE]) > 20) {
        $errors[Sisme_Utils_Users::APPLICATION_FIELD_REPRESENTATIVE_PHONE] = 'Le numéro de téléphone ne peut pas dépasser 20 caractères.';
    }
    
    return $errors;
}

// ======================================================
// 🎮 HANDLERS SOUMISSIONS DE JEUX
// ======================================================

/**
 * Créer une nouvelle soumission
 */
function sisme_ajax_create_submission() {
    // Sécurité - MÊME NONCE que votre système existant
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'code' => 'invalid_nonce'
        ]);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
    }
    
    $user_id = get_current_user_id();
    
    // Vérifier que l'utilisateur est développeur approuvé
    if (!class_exists('Sisme_User_Developer_Data_Manager')) {
        wp_send_json_error(['message' => 'Module développeur non disponible']);
    }
    
    if (!Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
        wp_send_json_error(['message' => 'Vous n\'êtes pas autorisé à soumettre des jeux']);
    }
    
    // Vérifier les limites de soumissions
    if (!class_exists('Sisme_Submission_Database')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
    }
    
    // Créer la soumission avec données par défaut
    $default_game_data = [
        'game_name' => '',
        'description' => '',
        'genres' => [],
        'platforms' => [],
        'covers' => ['horizontal' => '', 'vertical' => ''],
        'screenshots' => '',
        'metadata' => [
            'completion_percentage' => 0,
            'last_step_completed' => 'basic'
        ]
    ];
    
    $submission_id = Sisme_Submission_Database::create_submission($user_id, $default_game_data);
    
    if (is_wp_error($submission_id)) {
        $error_message = $submission_id->get_error_message();
        
        // ✅ Messages d'erreur plus clairs pour les limites
        if ($submission_id->get_error_code() === 'limit_exceeded') {
            $error_message = 'Vous avez atteint la limite de brouillons (3 maximum) ou de soumissions par jour (1 maximum). Supprimez un brouillon existant ou attendez demain.';
        }
        
        wp_send_json_error([
            'message' => 'Erreur lors de la création: ' . $error_message
        ]);
    }
    
    // Log pour debug
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Sisme Developer] Nouvelle soumission créée: ID $submission_id pour user $user_id");
    }
    
    wp_send_json_success([
        'submission_id' => $submission_id,
        'message' => 'Soumission créée avec succès'
    ]);
}
add_action('wp_ajax_sisme_create_submission', 'sisme_ajax_create_submission');

/**
 * Supprimer une soumission
 */
function sisme_ajax_delete_submission() {
    // Sécurité - MÊME PATTERN que votre système existant
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'code' => 'invalid_nonce'
        ]);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
    }
    
    $submission_id = intval($_POST['submission_id'] ?? 0);
    $user_id = get_current_user_id();
    
    if (!$submission_id) {
        wp_send_json_error(['message' => 'ID de soumission manquant']);
    }
    
    // Charger la classe si nécessaire
    if (!class_exists('Sisme_Submission_Database')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
    }
    
    // Vérifier que la soumission existe et appartient à l'utilisateur
    $submission = Sisme_Submission_Database::get_submission($submission_id);
    
    if (!$submission) {
        wp_send_json_error(['message' => 'Soumission introuvable']);
    }
    
    if ($submission->user_id != $user_id) {
        wp_send_json_error(['message' => 'Vous n\'avez pas le droit de supprimer cette soumission']);
    }
    
    // Vérifier que la soumission peut être supprimée (draft ou revision uniquement)
    if (!in_array($submission->status, ['draft', 'revision'])) {
        wp_send_json_error(['message' => 'Cette soumission ne peut pas être supprimée']);
    }
    
    // Supprimer la soumission
    $result = Sisme_Submission_Database::delete_submission($submission_id, $user_id);
    
    if (is_wp_error($result)) {
        wp_send_json_error([
            'message' => 'Erreur lors de la suppression: ' . $result->get_error_message()
        ]);
    }
    
    // Log pour debug
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Sisme Developer] Soumission supprimée: ID $submission_id par user $user_id");
    }
    
    wp_send_json_success(['message' => 'Soumission supprimée avec succès']);
}
add_action('wp_ajax_sisme_delete_submission', 'sisme_ajax_delete_submission');

/**
 * Récupérer les détails d'une soumission
 */
function sisme_ajax_get_submission_details() {
    // Sécurité - MÊME PATTERN que votre système existant  
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'code' => 'invalid_nonce'
        ]);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
    }
    
    $submission_id = intval($_POST['submission_id'] ?? 0);
    $user_id = get_current_user_id();
    
    if (!$submission_id) {
        wp_send_json_error(['message' => 'ID de soumission manquant']);
    }
    
    // Charger la classe si nécessaire
    if (!class_exists('Sisme_Submission_Database')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
    }
    
    $submission = Sisme_Submission_Database::get_submission($submission_id);
    
    if (!$submission) {
        wp_send_json_error(['message' => 'Soumission introuvable']);
    }
    
    if ($submission->user_id != $user_id) {
        wp_send_json_error(['message' => 'Accès non autorisé']);
    }
    
    $game_name = $submission->game_data_decoded['game_name'] ?? 'Jeu sans titre';
    $admin_notes = $submission->admin_notes ?? '';
    
    wp_send_json_success([
        'submission_id' => $submission->id,
        'game_name' => $game_name,
        'admin_notes' => $admin_notes,
        'status' => $submission->status,
        'created_at' => $submission->created_at,
        'updated_at' => $submission->updated_at,
        'submitted_at' => $submission->submitted_at,
        'completion_percentage' => $submission->game_data_decoded['metadata']['completion_percentage'] ?? 0
    ]);
}
add_action('wp_ajax_sisme_get_submission_details', 'sisme_ajax_get_submission_details');

/**
 * Réessayer une soumission rejetée (créer nouvelle version)
 */
function sisme_ajax_retry_submission() {
    // Sécurité - MÊME PATTERN que votre système existant
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'code' => 'invalid_nonce'
        ]);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
    }
    
    $submission_id = intval($_POST['submission_id'] ?? 0);
    $user_id = get_current_user_id();
    
    if (!$submission_id) {
        wp_send_json_error(['message' => 'ID de soumission manquant']);
    }
    
    // Charger la classe si nécessaire
    if (!class_exists('Sisme_Submission_Database')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
    }
    
    // Récupérer la soumission rejetée
    $original_submission = Sisme_Submission_Database::get_submission($submission_id);
    
    if (!$original_submission) {
        wp_send_json_error(['message' => 'Soumission originale introuvable']);
    }
    
    if ($original_submission->user_id != $user_id) {
        wp_send_json_error(['message' => 'Accès non autorisé']);
    }
    
    if ($original_submission->status !== 'rejected') {
        wp_send_json_error(['message' => 'Seules les soumissions rejetées peuvent être retentées']);
    }
    
    // Vérifier que l'utilisateur peut encore soumettre
    if (!Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
        wp_send_json_error(['message' => 'Vous n\'êtes plus autorisé à soumettre des jeux']);
    }
    
    // Copier les données de l'original pour la nouvelle soumission
    $original_game_data = $original_submission->game_data_decoded;
    
    // Réinitialiser les métadonnées pour une nouvelle soumission
    $original_game_data['metadata'] = [
        'completion_percentage' => $original_game_data['metadata']['completion_percentage'] ?? 0,
        'last_step_completed' => $original_game_data['metadata']['last_step_completed'] ?? 'basic',
        'original_submission_id' => $submission_id, // Référence à l'original
        'retry_count' => ($original_game_data['metadata']['retry_count'] ?? 0) + 1
    ];
    
    // Créer la nouvelle soumission
    $new_submission_id = Sisme_Submission_Database::create_submission($user_id, $original_game_data);
    
    if (is_wp_error($new_submission_id)) {
        wp_send_json_error([
            'message' => 'Erreur lors de la création de la nouvelle version: ' . $new_submission_id->get_error_message()
        ]);
    }
    
    // Log pour debug
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("[Sisme Developer] Nouvelle tentative soumission: nouveau ID $new_submission_id depuis original ID $submission_id");
    }
    
    wp_send_json_success([
        'new_submission_id' => $new_submission_id,
        'original_submission_id' => $submission_id,
        'message' => 'Nouvelle version créée avec succès'
    ]);
}
add_action('wp_ajax_sisme_retry_submission', 'sisme_ajax_retry_submission');

/**
 * Mettre à jour les statistiques du dashboard développeur
 */
function sisme_ajax_get_developer_stats() {
    // Sécurité - MÊME PATTERN que votre système existant
    if (!check_ajax_referer('sisme_developer_nonce', 'security', false)) {
        wp_send_json_error([
            'message' => 'Erreur de sécurité. Veuillez recharger la page.',
            'code' => 'invalid_nonce'
        ]);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Utilisateur non connecté']);
    }
    
    $user_id = get_current_user_id();
    
    // Vérifier que l'utilisateur est développeur approuvé
    if (!Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
        wp_send_json_error(['message' => 'Accès non autorisé']);
    }
    
    // Charger la classe si nécessaire
    if (!class_exists('Sisme_Submission_Database')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
    }
    
    // Récupérer toutes les soumissions de l'utilisateur
    $user_submissions = Sisme_Submission_Database::get_user_submissions($user_id);
    
    // Calculer les statistiques
    $stats = [
        'published' => 0,
        'pending' => 0,
        'draft' => 0,
        'revision' => 0,
        'rejected' => 0,
        'total_views' => 0 // TODO: Implémenter le système de vues
    ];
    
    foreach ($user_submissions as $submission) {
        if (isset($stats[$submission->status])) {
            $stats[$submission->status]++;
        }
        
        // TODO: Ajouter les vraies statistiques de vues
        if ($submission->status === 'published') {
            // $stats['total_views'] += get_submission_views($submission->id);
        }
    }
    
    wp_send_json_success([
        'stats' => $stats,
        'submissions_count' => count($user_submissions),
        'last_updated' => current_time('mysql')
    ]);
}
add_action('wp_ajax_sisme_get_developer_stats', 'sisme_ajax_get_developer_stats');

// ======================================================
// 🔧 FONCTIONS UTILITAIRES
// ======================================================

/**
 * Valider les permissions développeur pour AJAX
 */
function sisme_validate_developer_ajax_permissions($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!is_user_logged_in()) {
        return new WP_Error('not_logged_in', 'Utilisateur non connecté');
    }
    
    if (!class_exists('Sisme_User_Developer_Data_Manager')) {
        return new WP_Error('module_missing', 'Module développeur non disponible');
    }
    
    if (!Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
        return new WP_Error('not_approved', 'Développeur non approuvé');
    }
    
    return true;
}

/**
 * Formater les données de soumission pour le frontend
 */
function sisme_format_submission_for_frontend($submission) {
    if (!$submission) {
        return null;
    }
    
    $game_data = $submission->game_data_decoded ?? [];
    
    return [
        'id' => $submission->id,
        'status' => $submission->status,
        'game_name' => $game_data['game_name'] ?? 'Jeu sans titre',
        'completion_percentage' => $game_data['metadata']['completion_percentage'] ?? 0,
        'last_step_completed' => $game_data['metadata']['last_step_completed'] ?? 'basic',
        'created_at' => $submission->created_at,
        'updated_at' => $submission->updated_at,
        'submitted_at' => $submission->submitted_at,
        'published_at' => $submission->published_at,
        'admin_notes' => $submission->admin_notes ?? '',
        'submission_version' => $submission->submission_version ?? 1
    ];
}