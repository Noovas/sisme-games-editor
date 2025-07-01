<?php
/**
 * File: /sisme-games-editor/includes/utils/utils-notifications.php
 * Utilitaires globaux pour les notifications automatiques
 * 
 * RESPONSABILITÉ:
 * - Récupérer users avec préférences notifications spécifiques
 * - Envoyer notifications en masse
 *  - Hooks automatiques publication jeux
 * - Filtrage utilisateurs par préférences et genres
 * 
 * DÉPENDANCES:
 * - WordPress Core (wpdb, hooks, meta API)
 * - Module user-notifications (Sisme_User_Notifications_Data_Manager)
 * - Module user-preferences (Sisme_User_Preferences_Data_Manager)
 * - Utils games (Sisme_Utils_Games)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Utils_Notification {
    /**
     * Récupérer la liste des utilisateurs avec une préférence notification activée
     * 
     * @param string $notification_type Type de notification ('new_indie_releases', 'new_games_in_genres', etc.)
     * @return array IDs des utilisateurs avec cette préférence activée
     */
    public static function get_users_with_notification_preference($notification_type) {
        global $wpdb;
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
     * Envoyer notification pour nouveau jeu avec logique de filtrage par genres
     * 
     * @param int $game_id ID du jeu publié
     * @return array Résultat de l'envoi
     */
    public static function send_new_game_notification($game_id) {
        global $wpdb;
        $users_to_notify = [];
        $notification_meta_key = 'sisme_user_notifications';
        $genres_meta_key = 'sisme_user_genres';
        $query = $wpdb->prepare("
            SELECT DISTINCT n.user_id, n.meta_value as notification_prefs, g.meta_value as user_genres
            FROM {$wpdb->usermeta} n
            LEFT JOIN {$wpdb->usermeta} g ON n.user_id = g.user_id AND g.meta_key = %s
            WHERE n.meta_key = %s
        ", $genres_meta_key, $notification_meta_key);
        $results = $wpdb->get_results($query);
        foreach ($results as $result) {
            $notification_prefs = maybe_unserialize($result->notification_prefs);
            if (!is_array($notification_prefs) || 
                !isset($notification_prefs['new_indie_releases']) || 
                $notification_prefs['new_indie_releases'] !== true) {
                continue;
            }
            $has_genre_filter = isset($notification_prefs['new_games_in_genres']) && 
                            $notification_prefs['new_games_in_genres'] === true;
            if ($has_genre_filter) {
                $user_genres = maybe_unserialize($result->user_genres);
                if (!is_array($user_genres) || empty($user_genres)) {
                    continue;
                }
                $game_genres = Sisme_Utils_Games::get_game_genres($game_id);
                if (empty($game_genres)) {
                    continue;
                }
                $game_genre_ids = array_map('intval', array_column($game_genres, 'id'));
                $user_genre_ids = array_map('intval', $user_genres);
                $matching_genres = array_intersect($user_genre_ids, $game_genre_ids);
                if (empty($matching_genres)) {
                    continue;
                }
            }
            $users_to_notify[] = intval($result->user_id);
        }
        if (empty($users_to_notify)) {
            return [
                'success' => true,
                'message' => 'Aucun utilisateur avec ces préférences',
                'stats' => ['total' => 0, 'sent' => 0, 'failed' => 0]
            ];
        }
        return self::send_notification_to_users($users_to_notify, $game_id, 'new_game');
    }
    
    /**
     * Hook automatique lors de la publication d'un jeu
     * 
     * @param string $new_status Nouveau statut
     * @param string $old_status Ancien statut  
     * @param WP_Term $term Term du jeu
     */
    public static function on_game_published($new_status, $old_status, $term) {
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return;
        }
        $game_description = get_term_meta($term->term_id, Sisme_Utils_Games::META_DESCRIPTION, true);
        if (empty($game_description)) {
            return;
        }
        $result = self::send_new_game_notification($term->term_id);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme Notification Utils] Publication jeu {$term->term_id} ({$term->name}): {$result['message']}");
        }
        do_action('sisme_game_published_notification_sent', $term->term_id, $result);
    }
    
    /**
     * Initialiser les hooks automatiques
     */
    public static function init_hooks() {
        add_action('save_post', [self::class, 'on_post_saved'], 99, 1);
    }

    /**
    * Hook après sauvegarde complète (tags inclus) - Envoie notifications unifiées
    * 
    * @param int $post_id ID du post publié
    */
    public static function on_post_saved($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'post' || $post->post_status !== 'publish') {
            return;
        }
        $is_new_post = get_post_meta($post_id, '_sisme_notification_sent', true) !== '1';
        if (!$is_new_post) {
            return;
        }
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
        $result = self::send_new_game_notification($game_tag_id);
        update_post_meta($post_id, '_sisme_notification_sent', '1');
        error_log("HOOK SAVE: Notifications envoyées pour jeu {$game_tag_id}: {$result['message']}");
    }
}

// Initialiser automatiquement les hooks si les modules sont disponibles
add_action('init', function() {
    if (class_exists('Sisme_User_Notifications_Data_Manager') && 
        class_exists('Sisme_User_Preferences_Data_Manager')) {
        Sisme_Utils_Notification::init_hooks();
    }
}, 20); // Priorité 20 pour être sûr que les modules user sont chargés