<?php
/**
 * File: /sisme-games-editor/includes/user/user-profile/user-profile-handlers.php
 * Gestionnaire de traitement pour la gestion de profil utilisateur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Profile_Handlers {
    
    /**
     * Initialiser la gestion des requêtes
     * @return void
     */
    public static function init_request_handling() {
        self::process_profile_forms();
    }
    
    /**
     * Traiter les formulaires de profil
     * @return void
     */
    public static function process_profile_forms() {
        if (isset($_POST['sisme_user_profile_submit'])) {
            self::process_profile_form();
        }
        
        if (isset($_POST['sisme_user_preferences_submit'])) {
            self::process_preferences_form();
        }
    }
    
    /**
     * Traiter le formulaire de profil
     * @return void
     */
    private static function process_profile_form() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'sisme_user_profile_update')) {
            wp_die('Erreur de sécurité. Veuillez recharger la page.');
        }
        
        if (!is_user_logged_in()) {
            wp_die('Vous devez être connecté.');
        }
        
        $result = self::handle_profile_update($_POST);
        
        if (is_wp_error($result)) {
            self::set_profile_message($result->get_error_message(), 'error');
        } else {
            self::set_profile_message('Profil mis à jour avec succès !', 'success');
            
            if (!empty($_POST['redirect_to'])) {
                wp_safe_redirect(esc_url($_POST['redirect_to']));
                exit;
            }
        }
    }
    
    /**
     * Traiter le formulaire de préférences
     * @return void
     */
    private static function process_preferences_form() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'sisme_user_profile_update')) {
            wp_die('Erreur de sécurité. Veuillez recharger la page.');
        }
        
        if (!is_user_logged_in()) {
            wp_die('Vous devez être connecté.');
        }
        
        $result = self::handle_preferences_update($_POST);
        
        if (is_wp_error($result)) {
            self::set_profile_message($result->get_error_message(), 'error');
        } else {
            self::set_profile_message('Préférences mises à jour !', 'success');
        }
    }
    
    /**
     * Traiter une mise à jour de profil
     * @param array $data Données du formulaire
     * @return array|WP_Error Résultat ou erreur
     */
    public static function handle_profile_update($data) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error('not_logged_in', 'Vous devez être connecté.');
        }
        
        $sanitized_data = self::sanitize_profile_data($data);
        $validation = self::validate_profile_data($sanitized_data);
        
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $updated_fields = [];
        
        // Mise à jour données WordPress de base
        if (isset($sanitized_data['user_display_name'])) {
            $user_data = [
                'ID' => $user_id,
                'display_name' => $sanitized_data['user_display_name']
            ];
            
            $result = wp_update_user($user_data);
            if (is_wp_error($result)) {
                return $result;
            }
            $updated_fields['display_name'] = $sanitized_data['user_display_name'];
        }
        
        if (isset($sanitized_data['user_website'])) {
            $user_data = [
                'ID' => $user_id,
                'user_url' => $sanitized_data['user_website']
            ];
            
            wp_update_user($user_data);
            $updated_fields['website'] = $sanitized_data['user_website'];
        }
        
        // Mise à jour métadonnées custom
        $meta_fields = [
            'user_bio' => 'sisme_user_bio'
        ];
        
        foreach ($meta_fields as $form_key => $meta_key) {
            if (isset($sanitized_data[$form_key])) {
                update_user_meta($user_id, $meta_key, $sanitized_data[$form_key]);
                $updated_fields[$form_key] = $sanitized_data[$form_key];
            }
        }
        
        // Mise à jour timestamp
        update_user_meta($user_id, 'sisme_user_profile_updated', current_time('mysql'));
        
        do_action('sisme_profile_updated', $user_id, $updated_fields);
        
        return [
            'success' => true,
            'user_id' => $user_id,
            'updated_fields' => $updated_fields,
            'message' => 'Profil mis à jour avec succès'
        ];
    }
    
    /**
     * Traiter une mise à jour de préférences
     * @param array $data Données du formulaire
     * @return array|WP_Error Résultat ou erreur
     */
    public static function handle_preferences_update($data) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return new WP_Error('not_logged_in', 'Vous devez être connecté.');
        }
        
        $sanitized_data = self::sanitize_preferences_data($data);
        $validation = self::validate_preferences_data($sanitized_data);
        
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $updated_preferences = [];
        
        // Préférences gaming
        $preference_fields = [
            'favorite_game_genres' => 'sisme_user_favorite_game_genres',
            'skill_level' => 'sisme_user_skill_level'
        ];
        
        foreach ($preference_fields as $form_key => $meta_key) {
            if (isset($sanitized_data[$form_key])) {
                update_user_meta($user_id, $meta_key, $sanitized_data[$form_key]);
                $updated_preferences[$form_key] = $sanitized_data[$form_key];
            }
        }
        
        // Préférences de confidentialité
        $privacy_fields = [
            'privacy_profile_public' => 'sisme_user_privacy_profile_public',
            'privacy_show_stats' => 'sisme_user_privacy_show_stats',
            'privacy_allow_friend_requests' => 'sisme_user_privacy_allow_friend_requests'
        ];
        
        foreach ($privacy_fields as $form_key => $meta_key) {
            if (isset($sanitized_data[$form_key])) {
                update_user_meta($user_id, $meta_key, $sanitized_data[$form_key]);
                $updated_preferences[$form_key] = $sanitized_data[$form_key];
            }
        }
        
        do_action('sisme_preferences_updated', $user_id, $updated_preferences);
        
        return [
            'success' => true,
            'user_id' => $user_id,
            'updated_preferences' => $updated_preferences,
            'message' => 'Préférences mises à jour'
        ];
    }
    
    /**
     * Sanitiser les données de profil
     * @param array $data Données brutes
     * @return array Données sanitisées
     */
    private static function sanitize_profile_data($data) {
        $sanitized = [];
        
        if (isset($data['user_display_name'])) {
            $sanitized['user_display_name'] = sanitize_text_field($data['user_display_name']);
        }
        
        if (isset($data['user_bio'])) {
            $sanitized['user_bio'] = sanitize_textarea_field($data['user_bio']);
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitiser les données de préférences
     * @param array $data Données brutes
     * @return array Données sanitisées
     */
    private static function sanitize_preferences_data($data) {
        $sanitized = [];
        
        if (isset($data['favorite_game_genres'])) {
            $sanitized['favorite_game_genres'] = is_array($data['favorite_game_genres']) 
                ? array_map('intval', $data['favorite_game_genres']) 
                : [];
        }
        
        if (isset($data['skill_level'])) {
            $sanitized['skill_level'] = sanitize_text_field($data['skill_level']);
        }
        
        if (isset($data['privacy_profile_public'])) {
            $sanitized['privacy_profile_public'] = !empty($data['privacy_profile_public']);
        }
        
        if (isset($data['privacy_show_stats'])) {
            $sanitized['privacy_show_stats'] = !empty($data['privacy_show_stats']);
        }
        
        if (isset($data['privacy_allow_friend_requests'])) {
            $sanitized['privacy_allow_friend_requests'] = !empty($data['privacy_allow_friend_requests']);
        }
        
        return $sanitized;
    }
    
    /**
     * Valider les données de profil
     * @param array $data Données sanitisées
     * @return true|WP_Error Validation ou erreur
     */
    private static function validate_profile_data($data) {
        $errors = [];
        
        if (isset($data['user_display_name'])) {
            if (strlen($data['user_display_name']) < 2) {
                $errors['user_display_name'] = 'Le nom d\'affichage doit contenir au moins 2 caractères.';
            } elseif (strlen($data['user_display_name']) > 50) {
                $errors['user_display_name'] = 'Le nom d\'affichage ne peut pas dépasser 50 caractères.';
            }
        }
        
        if (isset($data['user_bio'])) {
            if (strlen($data['user_bio']) > 500) {
                $errors['user_bio'] = 'La biographie ne peut pas dépasser 500 caractères.';
            }
        }
        
        if (isset($data['user_website'])) {
            if (!empty($data['user_website']) && !filter_var($data['user_website'], FILTER_VALIDATE_URL)) {
                $errors['user_website'] = 'L\'URL du site web n\'est pas valide.';
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error('validation_failed', 'Erreurs de validation', $errors);
        }
        
        return true;
    }
    
    /**
     * Valider les données de préférences
     * @param array $data Données sanitisées
     * @return true|WP_Error Validation ou erreur
     */
    private static function validate_preferences_data($data) {
        $errors = [];
        
        if (isset($data['skill_level'])) {
            $valid_levels = ['beginner', 'casual', 'experienced', 'hardcore', 'professional'];
            if (!empty($data['skill_level']) && !in_array($data['skill_level'], $valid_levels)) {
                $errors['skill_level'] = 'Niveau de compétence non valide.';
            }
        }
        
        if (isset($data['favorite_game_genres'])) {
            if (count($data['favorite_game_genres']) > 10) {
                $errors['favorite_game_genres'] = 'Vous ne pouvez pas sélectionner plus de 10 genres.';
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error('validation_failed', 'Erreurs de validation', $errors);
        }
        
        return true;
    }
    
    /**
     * Stocker un message de profil dans la session
     * @param string $message Message à stocker
     * @param string $type Type de message (success, error, info)
     * @return void
     */
    public static function set_profile_message($message, $type = 'info') {
        if (!session_id()) {
            session_start();
        }
        
        $_SESSION['sisme_profile_message'] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => time()
        ];
    }
    
    /**
     * Récupérer et supprimer un message de profil
     * @return array|null Message ou null
     */
    public static function get_profile_message() {
        if (!session_id()) {
            session_start();
        }
        
        if (isset($_SESSION['sisme_profile_message'])) {
            $message = $_SESSION['sisme_profile_message'];
            unset($_SESSION['sisme_profile_message']);
            return $message;
        }
        
        return null;
    }
    
    /**
     * Obtenir les métadonnées par défaut pour un nouvel utilisateur
     * @return array Métadonnées par défaut
     */
    public static function get_default_user_meta() {
        return [
            'sisme_user_bio' => '',
            'sisme_user_favorite_game_genres' => [],
            'sisme_user_skill_level' => '',
            'sisme_user_favorite_games' => [],
            'sisme_user_avatar' => '',
            'sisme_user_banner' => '',
            'sisme_user_privacy_profile_public' => false,
            'sisme_user_privacy_show_stats' => true,
            'sisme_user_privacy_allow_friend_requests' => true,
            'sisme_user_profile_updated' => current_time('mysql')
        ];
    }
    
    /**
     * Initialiser les métadonnées pour un utilisateur
     * @param int $user_id ID de l'utilisateur
     * @return void
     */
    public static function init_user_profile_meta($user_id) {
        $default_meta = self::get_default_user_meta();
        
        foreach ($default_meta as $meta_key => $meta_value) {
            if (!get_user_meta($user_id, $meta_key, true)) {
                update_user_meta($user_id, $meta_key, $meta_value);
            }
        }
        
        do_action('sisme_user_profile_meta_initialized', $user_id);
    }
}