<?php
/**
 * File: /sisme-games-editor/includes/user/user-preferences/user-preferences-ajax.php
 * Handlers AJAX pour les préférences utilisateur
 * 
 * RESPONSABILITÉ:
 * - Gestion des requêtes AJAX pour sauvegarde auto des préférences
 * - Validation sécurité et permissions utilisateur
 * - Reset des préférences utilisateur
 * - Réponses JSON structurées pour le frontend
 * 
 * DÉPENDANCES:
 * - Sisme_User_Preferences_Data_Manager
 * - WordPress AJAX system
 * - Nonces WordPress pour sécurité
 */

if (!defined('ABSPATH')) {
    exit;
}

if (defined('WP_DEBUG')) {
    ini_set('display_errors', 0);
    error_reporting(0);
}

class Sisme_User_Preferences_Ajax {
    
    /**
     * Initialiser les hooks AJAX
     */
    public static function init() {
        // Actions AJAX pour utilisateurs connectés
        add_action('wp_ajax_sisme_update_user_preference', [self::class, 'ajax_update_user_preference']);
        add_action('wp_ajax_sisme_reset_user_preferences', [self::class, 'ajax_reset_user_preferences']);
        
        // Actions AJAX pour utilisateurs non connectés
        add_action('wp_ajax_nopriv_sisme_update_user_preference', [self::class, 'ajax_not_logged_in']);
        add_action('wp_ajax_nopriv_sisme_reset_user_preferences', [self::class, 'ajax_not_logged_in']);

        add_action('wp_ajax_sisme_select_user_avatar', [self::class, 'ajax_select_avatar']);
        add_action('wp_ajax_nopriv_sisme_select_user_avatar', [self::class, 'ajax_not_logged_in']);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Preferences Ajax] Hooks AJAX enregistrés');
        }
    }
    
    /**
     * Handler AJAX pour mettre à jour une préférence utilisateur
     */
    public static function ajax_update_user_preference() {
        if (ob_get_length()) {
            ob_clean();
        }
        // Vérifier le nonce de sécurité
        if (!check_ajax_referer('sisme_user_preferences_nonce', 'security', false)) {
            self::log_ajax_error('update_preference', 'invalid_nonce', [
                'received_nonce' => $_POST['security'] ?? 'missing',
                'expected_action' => 'sisme_user_preferences_nonce'
            ]);
            
            wp_send_json_error([
                'message' => __('Erreur de sécurité', 'sisme-games-editor'),
                'code' => 'invalid_nonce'
            ]);
        }
        
        // Vérifier que l'utilisateur est connecté
        $user_id = get_current_user_id();
        if (!$user_id) {
            self::log_ajax_error('update_preference', 'not_logged_in');
            
            wp_send_json_error([
                'message' => __('Vous devez être connecté', 'sisme-games-editor'),
                'code' => 'not_logged_in'
            ]);
        }
        
        // Vérifier les paramètres requis
        if (!isset($_POST['preference_key']) || !isset($_POST['preference_value'])) {
            self::log_ajax_error('update_preference', 'missing_params', [
                'has_key' => isset($_POST['preference_key']),
                'has_value' => isset($_POST['preference_value']),
                'post_keys' => array_keys($_POST)
            ]);
            
            wp_send_json_error([
                'message' => __('Paramètres manquants', 'sisme-games-editor'),
                'code' => 'missing_params'
            ]);
        }
        
        // Récupérer et valider les paramètres
        $preference_key = sanitize_text_field($_POST['preference_key']);
        $preference_value = $_POST['preference_value'];
        
        // Log des données reçues
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences Ajax] Données brutes reçues - Key: {$preference_key}, Value type: " . gettype($preference_value) . ", Value: " . print_r($preference_value, true));
        }
        
        // Traitement spécial selon le type de valeur
        try {
            $processed_value = self::process_preference_value($preference_key, $preference_value);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Preferences Ajax] Valeur après traitement - Type: " . gettype($processed_value) . ", Value: " . print_r($processed_value, true));
            }
            
        } catch (Exception $e) {
            self::log_ajax_error('update_preference', 'processing_failed', [
                'exception' => $e->getMessage(),
                'key' => $preference_key,
                'raw_value' => $preference_value
            ]);
            
            wp_send_json_error([
                'message' => __('Erreur lors du traitement des données', 'sisme-games-editor'),
                'code' => 'processing_failed'
            ]);
        }
        
        // Vérifier que le Data Manager est disponible
        if (!class_exists('Sisme_User_Preferences_Data_Manager')) {
            self::log_ajax_error('update_preference', 'data_manager_missing');
            
            wp_send_json_error([
                'message' => __('Module de données non disponible', 'sisme-games-editor'),
                'code' => 'data_manager_missing'
            ]);
        }
        
        // Validation supplémentaire des données
        if (!Sisme_User_Preferences_Data_Manager::validate_preference_key($preference_key)) {
            self::log_ajax_error('update_preference', 'invalid_key', [
                'key' => $preference_key
            ]);
            
            wp_send_json_error([
                'message' => __('Clé de préférence invalide', 'sisme-games-editor'),
                'code' => 'invalid_key',
                'preference_key' => $preference_key
            ]);
        }
        
        if (!Sisme_User_Preferences_Data_Manager::validate_preference_value($preference_key, $processed_value)) {
            self::log_ajax_error('update_preference', 'invalid_value', [
                'key' => $preference_key,
                'value' => $processed_value,
                'value_type' => gettype($processed_value)
            ]);
            
            wp_send_json_error([
                'message' => __('Valeur de préférence invalide', 'sisme-games-editor'),
                'code' => 'invalid_value',
                'preference_key' => $preference_key
            ]);
        }
        
        // Mettre à jour la préférence
        try {
            $success = Sisme_User_Preferences_Data_Manager::update_user_preference(
                $user_id,
                $preference_key,
                $processed_value
            );
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Preferences Ajax] Résultat update_user_preference: " . ($success ? 'TRUE' : 'FALSE'));
            }
            
        } catch (Exception $e) {
            self::log_ajax_error('update_preference', 'save_exception', [
                'exception' => $e->getMessage(),
                'user_id' => $user_id,
                'key' => $preference_key,
                'value' => $processed_value
            ]);
            
            wp_send_json_error([
                'message' => __('Exception lors de la sauvegarde', 'sisme-games-editor'),
                'code' => 'save_exception'
            ]);
        }
        
        if ($success) {
            // Récupérer la valeur sauvegardée pour confirmation
            $saved_value = Sisme_User_Preferences_Data_Manager::get_user_preference($user_id, $preference_key);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Preferences Ajax] Valeur confirmée depuis DB: " . print_r($saved_value, true));
            }
            
            wp_send_json_success([
                'message' => __('Préférence sauvegardée', 'sisme-games-editor'),
                'preference_key' => $preference_key,
                'preference_value' => $saved_value,
                'processed_value' => $processed_value,
                'timestamp' => current_time('timestamp')
            ]);
            
        } else {
            self::log_ajax_error('update_preference', 'save_failed', [
                'user_id' => $user_id,
                'key' => $preference_key,
                'processed_value' => $processed_value,
                'user_exists' => (bool) get_userdata($user_id)
            ]);
            
            wp_send_json_error([
                'message' => __('Erreur lors de la sauvegarde', 'sisme-games-editor'),
                'code' => 'save_failed',
                'preference_key' => $preference_key
            ]);
        }
    }
    
    /**
     * Handler AJAX pour réinitialiser toutes les préférences utilisateur
     */
    public static function ajax_reset_user_preferences() {
        // Vérifier le nonce de sécurité
        if (!check_ajax_referer('sisme_user_preferences_nonce', 'security', false)) {
            wp_send_json_error([
                'message' => __('Erreur de sécurité', 'sisme-games-editor'),
                'code' => 'invalid_nonce'
            ]);
        }
        
        // Vérifier que l'utilisateur est connecté
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error([
                'message' => __('Vous devez être connecté', 'sisme-games-editor'),
                'code' => 'not_logged_in'
            ]);
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences Ajax] Reset préférences pour utilisateur {$user_id}");
        }
        
        // Vérifier que le Data Manager est disponible
        if (!class_exists('Sisme_User_Preferences_Data_Manager')) {
            wp_send_json_error([
                'message' => __('Module de données non disponible', 'sisme-games-editor'),
                'code' => 'data_manager_missing'
            ]);
        }
        
        // Réinitialiser les préférences
        $success = Sisme_User_Preferences_Data_Manager::reset_user_preferences($user_id);
        
        if ($success) {
            // Récupérer les nouvelles préférences pour les renvoyer
            $new_preferences = Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id);
            
            wp_send_json_success([
                'message' => __('Préférences réinitialisées', 'sisme-games-editor'),
                'preferences' => $new_preferences,
                'timestamp' => current_time('timestamp')
            ]);
        } else {
            wp_send_json_error([
                'message' => __('Erreur lors de la réinitialisation', 'sisme-games-editor'),
                'code' => 'reset_failed'
            ]);
        }
    }
    
    /**
     * Handler AJAX pour les utilisateurs non connectés
     */
    public static function ajax_not_logged_in() {
        wp_send_json_error([
            'message' => __('Vous devez être connecté pour modifier vos préférences', 'sisme-games-editor'),
            'code' => 'not_logged_in',
            'login_url' => wp_login_url(get_permalink())
        ]);
    }

    /**
     * Convertir une valeur en boolean de manière robuste
     * 
     * @param mixed $value Valeur à convertir
     * @return bool Valeur boolean
     */
    private static function convert_to_boolean($value) {
        // Si c'est déjà un boolean
        if (is_bool($value)) {
            return $value;
        }
        
        // Si c'est un string
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', '1', 'on', 'yes'], true);
        }
        
        // Si c'est un nombre
        if (is_numeric($value)) {
            return (bool) intval($value);
        }
        
        // Par défaut, convertir normalement
        return (bool) $value;
    }
        
    /**
     * Traiter une valeur de préférence selon son type
     * 
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur brute reçue
     * @return mixed Valeur traitée
     */
    private static function process_preference_value($key, $value) {
        // Log pour debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences Ajax] Processing {$key} with value: " . print_r($value, true));
        }
        
        switch ($key) {
            case 'platforms':
            case 'genres':
            case 'player_types':
                // Assurer que c'est un array, même si vide
                if (is_null($value) || $value === '' || $value === false) {
                    return [];
                }
                
                if (!is_array($value)) {
                    // Si c'est une string vide ou "null", retourner tableau vide
                    if (empty($value) || $value === 'null' || $value === '[]') {
                        return [];
                    }
                    
                    // Tenter de decoder si c'est du JSON
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }
                    
                    // Fallback: transformer en array
                    return [$value];
                }
                
                // Nettoyer le tableau : supprimer les valeurs vides/nulles
                $cleaned = array_filter($value, function($item) {
                    return !is_null($item) && $item !== '' && $item !== false;
                });
                
                // Re-indexer le tableau pour éviter les indices vides
                return array_values($cleaned);
                
            case 'notifications':
                // Traiter les notifications (peut être un array partiel ou complet)
                if (!is_array($value)) {
                    // Si ce n'est pas un array, retourner config par défaut
                    return Sisme_User_Preferences_Data_Manager::get_default_preferences()['notifications'];
                }
                
                // S'assurer que toutes les clés de notification existent
                $notification_types = Sisme_User_Preferences_Data_Manager::get_notification_types();
                $processed = [];
                
                foreach (array_keys($notification_types) as $notif_key) {
                    if (isset($value[$notif_key])) {
                        // Convertir en boolean de manière robuste
                        $processed[$notif_key] = self::convert_to_boolean($value[$notif_key]);
                    } else {
                        // Valeur par défaut si clé manquante
                        $defaults = Sisme_User_Preferences_Data_Manager::get_default_preferences();
                        $processed[$notif_key] = $defaults['notifications'][$notif_key] ?? false;
                    }
                }
                
                return $processed;
                
            case 'privacy_public':
                // Convertir en boolean de manière robuste
                return self::convert_to_boolean($value);
                
            default:
                return $value;
        }
    }
    
    /**
     * Valider les données reçues par AJAX
     * 
     * @param string $key Clé de préférence
     * @param mixed $value Valeur à valider
     * @return array Résultat de validation [valid => bool, message => string]
     */
    private static function validate_ajax_data($key, $value) {
        // Vérifier que le Data Manager est disponible pour la validation
        if (!class_exists('Sisme_User_Preferences_Data_Manager')) {
            return [
                'valid' => false,
                'message' => 'Module de validation non disponible'
            ];
        }
        
        // Valider la clé
        if (!Sisme_User_Preferences_Data_Manager::validate_preference_key($key)) {
            return [
                'valid' => false,
                'message' => "Clé de préférence invalide: {$key}"
            ];
        }
        
        // Valider la valeur
        if (!Sisme_User_Preferences_Data_Manager::validate_preference_value($key, $value)) {
            return [
                'valid' => false,
                'message' => "Valeur invalide pour la préférence {$key}"
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Validation réussie'
        ];
    }
    
    /**
     * Logger les erreurs AJAX avec contexte
     * 
     * @param string $action Action AJAX
     * @param string $error_code Code d'erreur
     * @param array $context Contexte additionnel
     */
    private static function log_ajax_error($action, $error_code, $context = []) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_message = "[Sisme User Preferences Ajax] ERREUR {$action}: {$error_code}";
        
        if (!empty($context)) {
            $log_message .= ' | Contexte: ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        }
        
        error_log($log_message);
        ?> <?php
    }
    
    /**
     * Créer une réponse d'erreur standardisée
     * 
     * @param string $message Message d'erreur
     * @param string $code Code d'erreur
     * @param array $additional_data Données supplémentaires
     */
    private static function send_error_response($message, $code, $additional_data = []) {
        $error_data = array_merge([
            'message' => $message,
            'code' => $code,
            'timestamp' => current_time('timestamp')
        ], $additional_data);
        
        wp_send_json_error($error_data);
    }
    
    /**
     * Créer une réponse de succès standardisée
     * 
     * @param string $message Message de succès
     * @param array $data Données de succès
     */
    private static function send_success_response($message, $data = []) {
        $success_data = array_merge([
            'message' => $message,
            'timestamp' => current_time('timestamp')
        ], $data);
        
        wp_send_json_success($success_data);
    }
    
    /**
     * Obtenir des statistiques sur les appels AJAX (debug)
     * 
     * @return array Statistiques d'utilisation
     */
    public static function get_ajax_stats() {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return [];
        }
        
        return [
            'handlers_registered' => [
                'update_preference' => has_action('wp_ajax_sisme_update_user_preference'),
                'reset_preferences' => has_action('wp_ajax_sisme_reset_user_preferences')
            ],
            'data_manager_available' => class_exists('Sisme_User_Preferences_Data_Manager'),
            'current_user_id' => get_current_user_id()
        ];
    }

    /**
     * Handler AJAX pour sélection d'avatar dans la librairie
     */
    // Dans ajax_select_avatar(), ajouter du debug console :
    public static function ajax_select_avatar() {
        if (ob_get_length()) {
            ob_clean();
        }
        
        
        if (!check_ajax_referer('sisme_user_preferences_nonce', 'security', false)) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'Connexion requise']);
        }

        $avatar_key = isset($_POST['avatar_key']) ? sanitize_text_field($_POST['avatar_key']) : '';
        
        if (empty($avatar_key)) {
            wp_send_json_error(['message' => 'Clé d\'avatar manquante']);
        }

        if (!Sisme_Constants::is_valid_avatar($avatar_key)) {
            wp_send_json_error(['message' => 'Avatar invalide: ' . $avatar_key]); // Debug dans le message
        }

        $success = Sisme_User_Preferences_Data_Manager::update_user_avatar($user_id, $avatar_key);
        
        if ($success) {
            $new_avatar_url = Sisme_Constants::get_avatar_url($avatar_key);
            wp_send_json_success([
                'avatar_key' => $avatar_key,
                'url' => $new_avatar_url,
                'message' => 'Avatar mis à jour',
                'debug_saved_value' => get_user_meta($user_id, 'sisme_user_avatar', true) // Debug dans la réponse
            ]);
        } else {
            wp_send_json_error(['message' => 'Erreur lors de la sauvegarde']);
        }
    }

    /**
     * Obtenir la liste des avatars disponibles
     */
    public static function get_available_avatars() {
        if (!class_exists('Sisme_Constants')) {
            return [];
        }
        
        $avatars = Sisme_Constants::get_avatars();
        $formatted_avatars = [];
        
        foreach ($avatars as $key => $url) {
            $filename = basename($url, '.png');
            $display_name = ucfirst(str_replace(['avatar user ', '-', '_'], ['', ' ', ' '], $filename));
            
            $formatted_avatars[] = [
                'key' => $key,
                'url' => $url,
                Sisme_Utils_Games::KEY_NAME => $display_name,
                'filename' => $filename
            ];
        }
        
        return $formatted_avatars;
    }
}