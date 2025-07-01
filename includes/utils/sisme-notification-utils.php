<?php
/**
 * File: /sisme-games-editor/includes/utils/sisme-notification-utils.php
 * Utilitaires globaux pour les notifications automatiques
 * 
 * RESPONSABILITÉ:
 * - Récupérer users avec préférences notifications spécifiques
 * - Envoyer notifications en masse
 * - Hooks automatiques publication jeux
 * - Filtrage utilisateurs par préférences
 * 
 * DÉPENDANCES:
 * - Module user-notifications
 * - Module user-preferences
 * - WordPress hooks
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Notification_Utils {
    
    /**
     * Récupérer la liste des utilisateurs avec une préférence notification activée
     * 
     * @param string $notification_type Type de notification ('new_indie_releases', 'new_games_in_genres', etc.)
     * @return array IDs des utilisateurs avec cette préférence activée
     */
    public static function get_users_with_notification_preference($notification_type) {
        global $wpdb;
        
        // Utiliser une requête SQL optimisée pour éviter de charger tous les users
        $meta_key = 'sisme_user_notifications';
        
        $query = $wpdb->prepare("
            SELECT user_id, meta_value 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = %s
        ", $meta_key);
        
        $results = $wpdb->get_results($query);
        $user_ids = [];
        
        foreach ($results as $result) {
            $notifications_prefs = maybe_unserialize($result->meta_value);
            
            if (is_array($notifications_prefs) && 
                isset($notifications_prefs[$notification_type]) && 
                $notifications_prefs[$notification_type] === true) {
                $user_ids[] = intval($result->user_id);
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Notification Utils] Trouvé " . count($user_ids) . " utilisateurs avec préférence '{$notification_type}' activée");
        }
        
        return $user_ids;
    }
    
    /**
     * Envoyer une notification à une liste d'utilisateurs
     * 
     * @param array $user_ids Liste des IDs utilisateurs
     * @param int $game_id ID du jeu
     * @param string $notification_type Type de notification
     * @return array Résultat avec statistiques
     */
    public static function send_notification_to_users($user_ids, $game_id, $notification_type = 'new_game') {
        if (empty($user_ids) || !$game_id) {
            return [
                'success' => false,
                'message' => 'Paramètres invalides',
                'stats' => ['total' => 0, 'sent' => 0, 'failed' => 0]
            ];
        }
        
        if (!class_exists('Sisme_User_Notifications_Data_Manager')) {
            return [
                'success' => false,
                'message' => 'Module notifications non disponible',
                'stats' => ['total' => 0, 'sent' => 0, 'failed' => 0]
            ];
        }
        
        $stats = [
            'total' => count($user_ids),
            'sent' => 0,
            'failed' => 0
        ];
        
        foreach ($user_ids as $user_id) {
            $success = Sisme_User_Notifications_Data_Manager::add_notification(
                $user_id, 
                $game_id, 
                $notification_type
            );
            
            if ($success) {
                $stats['sent']++;
            } else {
                $stats['failed']++;
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Notification Utils] Notifications envoyées: {$stats['sent']}/{$stats['total']} pour jeu {$game_id}");
        }
        
        return [
            'success' => true,
            'message' => "Notifications envoyées à {$stats['sent']} utilisateurs",
            'stats' => $stats
        ];
    }
    
    /**
     * Envoyer notification "nouvelles sorties indépendantes" à tous les users concernés
     * 
     * @param int $game_id ID du jeu publié
     * @return array Résultat de l'envoi
     */
    public static function send_indie_release_notification($game_id) {
        $user_ids = self::get_users_with_notification_preference('new_indie_releases');
        
        if (empty($user_ids)) {
            return [
                'success' => true,
                'message' => 'Aucun utilisateur avec cette préférence',
                'stats' => ['total' => 0, 'sent' => 0, 'failed' => 0]
            ];
        }
        
        return self::send_notification_to_users($user_ids, $game_id, 'new_game');
    }
    
    /**
     * Hook automatique lors de la publication d'un jeu
     * 
     * @param string $new_status Nouveau statut
     * @param string $old_status Ancien statut  
     * @param WP_Term $term Term du jeu
     */
    public static function on_game_published($new_status, $old_status, $term) {
        // Vérifier que c'est bien une publication (draft/pending -> publish)
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return;
        }
        
        // Vérifier que c'est bien un jeu (avec meta game_description)
        $game_description = get_term_meta($term->term_id, Sisme_Utils_Games::META_DESCRIPTION, true);
        if (empty($game_description)) {
            return;
        }
        
        // Envoyer les notifications
        $result = self::send_indie_release_notification($term->term_id);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Notification Utils] Publication jeu {$term->term_id} ({$term->name}): {$result['message']}");
        }
        
        // Hook personnalisé pour extensions futures
        do_action('sisme_game_published_notification_sent', $term->term_id, $result);
    }
    
    /**
     * Initialiser les hooks automatiques
     */
    public static function init_hooks() {
        add_action('save_post', [self::class, 'on_post_saved'], 99, 1);
    }

    /**
     * Hook après sauvegarde complète (tags inclus)
     */
    public static function on_post_saved($post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'post' || $post->post_status !== 'publish') {
            return;
        }
        
        // Vérifier si c'est une nouvelle publication (pas une mise à jour)
        $is_new_post = get_post_meta($post_id, '_sisme_notification_sent', true) !== '1';
        
        if (!$is_new_post) {
            return; // Déjà traité
        }
        
        // Vérifier les tags maintenant disponibles
        $post_tags = wp_get_post_tags($post_id);
        if (empty($post_tags)) {
            error_log("HOOK SAVE: Post {$post_id} sans tags ignoré");
            return;
        }
        
        $game_tag_id = $post_tags[0]->term_id;
        $game_description = get_term_meta($game_tag_id, Sisme_Utils_Games::META_DESCRIPTION, true);
        
        if (empty($game_description)) {
            error_log("HOOK SAVE: Tag {$game_tag_id} sans game_description ignoré");
            return;
        }
        
        // Envoyer notifications
        $result = self::send_indie_release_notification($game_tag_id);
        
        // Marquer comme traité
        update_post_meta($post_id, '_sisme_notification_sent', '1');
        
        error_log("HOOK SAVE: Notifications envoyées pour jeu {$game_tag_id}: {$result['message']}");
    }
    
    /**
     * Hook pour publication de posts (si les jeux sont des posts)
     * 
     * @param string $new_status Nouveau statut
     * @param string $old_status Ancien statut
     * @param WP_Post $post Post object
     */
    public static function on_post_published($new_status, $old_status, $post) {
        // ✅ DEBUG temporaire
        error_log("HOOK: transition_post_status - Post {$post->ID}, type: {$post->post_type}, {$old_status} → {$new_status}");
        
        // ✅ Tes jeux sont des posts WordPress classiques
        if ($post->post_type !== 'post') {
            error_log("HOOK: Post type '{$post->post_type}' ignoré");
            return;
        }
        
        if ($new_status !== 'publish' || $old_status === 'publish') {
            error_log("HOOK: Statut ignoré ({$old_status} → {$new_status})");
            return;
        }
        
        // ✅ Vérifier que c'est bien un jeu (avec tag de jeu)
        $post_tags = wp_get_post_tags($post->ID);
        if (empty($post_tags)) {
            error_log("HOOK: Post sans tags ignoré");
            return;
        }
        
        // ✅ Utiliser le premier tag comme game_id
        $game_tag_id = $post_tags[0]->term_id;
        
        // ✅ Vérifier que ce tag a des game_data
        $game_description = get_term_meta($game_tag_id, Sisme_Utils_Games::META_DESCRIPTION, true);
        if (empty($game_description)) {
            error_log("HOOK: Tag {$game_tag_id} sans game_description ignoré");
            return;
        }
        
        // ✅ Envoyer notifications avec l'ID du tag (pas du post)
        $result = self::send_indie_release_notification($game_tag_id);
        error_log("HOOK: Notifications envoyées pour jeu {$game_tag_id}: {$result['message']}");
    }
    
    /**
     * Fonction utilitaire pour tester l'envoi de notifications
     * 
     * @param int $game_id ID du jeu pour test
     * @return array Résultat du test
     */
    public static function test_notification_system($game_id) {
        $user_ids = self::get_users_with_notification_preference('new_indie_releases');
        
        return [
            'users_found' => count($user_ids),
            'user_ids' => $user_ids,
            'game_exists' => Sisme_Utils_Games::get_game_data($game_id),
            'notification_module_loaded' => class_exists('Sisme_User_Notifications_Data_Manager')
        ];
    }
}

// Initialiser automatiquement les hooks si les modules sont disponibles
add_action('init', function() {
    if (class_exists('Sisme_User_Notifications_Data_Manager') && 
        class_exists('Sisme_User_Preferences_Data_Manager')) {
        Sisme_Notification_Utils::init_hooks();
    }
}, 20); // Priorité 20 pour être sûr que les modules user sont chargés