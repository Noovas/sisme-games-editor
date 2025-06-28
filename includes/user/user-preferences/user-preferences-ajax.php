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

class Sisme_User_Preferences_Ajax {
    
    /**
     * Initialiser les hooks AJAX
     */
    public static function init() {
        // Actions AJAX pour utilisateurs connectés
        add_action('wp_ajax_sisme_update_user_preference', [self::class, 'ajax_update_user_preference']);
        add_action('wp_ajax_sisme_reset_user_preferences', [self::class, 'ajax_reset_user_preferences']);
        
        // Actions AJAX pour utilisateurs non connectés (refus)
        add_action('wp_ajax_nopriv_sisme_update_user_preference', [self::class, 'ajax_not_logged_in']);
        add_action('wp_ajax_nopriv_sisme_reset_user_preferences', [self::class, 'ajax_not_logged_in']);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme User Preferences Ajax] Hooks AJAX enregistrés');
        }
    }
    
    /**
     * Handler AJAX pour mettre à jour une préférence utilisateur
     */
    public static function ajax_update_user_preference() {
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
        
        // Vérifier les paramètres requis
        if (!isset($_POST['preference_key']) || !isset($_POST['preference_value'])) {
            wp_send_json_error([
                'message' => __('Paramètres manquants', 'sisme-games-editor'),
                'code' => 'missing_params'
            ]);
        }
        
        // Récupérer et valider les paramètres
        $preference_key = sanitize_text_field($_POST['preference_key']);
        $preference_value = $_POST['preference_value'];
        
        // Traitement spécial selon le type de valeur
        $preference_value = self::process_preference_value($preference_key, $preference_value);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Preferences Ajax] Mise à jour préférence {$preference_key} pour utilisateur {$user_id}: " . print_r($preference_value, true));
        }
        
        // Vérifier que le Data Manager est disponible
        if (!class_exists('Sisme_User_Preferences_Data_Manager')) {
            wp_send_json_error([
                'message' => __('Module de données non disponible', 'sisme-games-editor'),
                'code' => 'data_manager_missing'
            ]);
        }
        
        // Mettre à jour la préférence
        $success = Sisme_User_Preferences_Data_Manager::update_user_preference(
            $user_id,
            $preference_key,
            $preference_value
        );
        
        if ($success) {
            // Récupérer la valeur sauvegardée pour confirmation
            $saved_value = Sisme_User_Preferences_Data_Manager::get_user_preference($user_id, $preference_key);
            
            wp_send_json_success([
                'message' => __('Préférence sauvegardée', 'sisme-games-editor'),
                'preference_key' => $preference_key,
                'preference_value' => $saved_value,
                'timestamp' => current_time('timestamp')
            ]);
        } else {
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
     * Traiter une valeur de préférence selon son type
     * 
     * @param string $key Clé de la préférence
     * @param mixed $value Valeur brute reçue
     * @return mixed Valeur traitée
     */
    private static function process_preference_value($key, $value) {
        switch ($key) {
            case 'platforms':
            case 'genres':
            case 'player_types':
                // Assurer que c'est un array
                if (!is_array($value)) {
                    return [];
                }
                return $value;
                
            case 'notifications':
                // Traiter les notifications (peut être un array partiel)
                if (!is_array($value)) {
                    return [];
                }
                
                // S'assurer que toutes les clés de notification existent
                $notification_types = Sisme_User_Preferences_Data_Manager::get_notification_types();
                $processed = [];
                
                foreach (array_keys($notification_types) as $notif_key) {
                    $processed[$notif_key] = isset($value[$notif_key]) ? (bool) $value[$notif_key] : false;
                }
                
                return $processed;
                
            case 'privacy_public':
                // Convertir en boolean
                if (is_string($value)) {
                    return in_array(strtolower($value), ['true', '1', 'on', 'yes']);
                }
                return (bool) $value;
                
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
            $log_message .= ' | Contexte: ' . json_encode($context);
        }
        
        error_log($log_message);
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
}