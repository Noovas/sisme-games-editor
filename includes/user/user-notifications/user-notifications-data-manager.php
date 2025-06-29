<?php
/**
 * File: /sisme-games-editor/includes/user/user-notifications/user-notifications-data-manager.php
 * Gestionnaire de données notifications utilisateur
 * 
 * RESPONSABILITÉ:
 * - Gestion CRUD des notifications utilisateur
 * - Système FIFO avec limite de 99 notifications
 * - Marquage notifications comme lues
 * - Cache et optimisation requêtes
 * 
 * DÉPENDANCES:
 * - WordPress User Meta API
 * - Module Cards pour données jeux
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Notifications_Data_Manager {
    
    const TYPE_NEW_GAME = 'new_game';
    const MAX_NOTIFICATIONS = 99;
    const META_KEY = 'sisme_user_notifications_list';
    
    /**
     * Ajouter une notification à un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $game_id ID du jeu (term_id)
     * @param string $type Type de notification
     * @return bool Succès de l'opération
     */
    public static function add_notification($user_id, $game_id, $type = self::TYPE_NEW_GAME) {
        if (!$user_id || !$game_id) {
            return false;
        }
        
        if (!class_exists('Sisme_Cards_Functions')) {
            return false;
        }
        
        $game_data = Sisme_Cards_Functions::get_game_data($game_id);
        if (!$game_data) {
            return false;
        }
        
        $notifications = self::get_user_notifications($user_id, false);
        
        foreach ($notifications as $notification) {
            if ($notification['game_id'] == $game_id && $notification['type'] == $type) {
                return true;
            }
        }
        
        $new_notification = [
            'game_id' => intval($game_id),
            'timestamp' => time(),
            'type' => $type
        ];
        
        array_unshift($notifications, $new_notification);
        
        self::cleanup_old_notifications_array($notifications);
        
        $success = update_user_meta($user_id, self::META_KEY, $notifications);
        
        if ($success) {
            do_action('sisme_notification_added', $user_id, $game_id, $type);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Notifications] Notification ajoutée pour utilisateur {$user_id}, jeu {$game_id}");
            }
        }
        
        return $success;
    }
    
    /**
     * Marquer une notification comme lue (la supprimer)
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $notification_index Index de la notification
     * @return bool Succès de l'opération
     */
    public static function mark_as_read($user_id, $notification_index) {
        if (!$user_id || $notification_index < 0) {
            return false;
        }
        
        $notifications = self::get_user_notifications($user_id, false);
        
        if (!isset($notifications[$notification_index])) {
            return false;
        }
        
        unset($notifications[$notification_index]);
        $notifications = array_values($notifications);
        
        $success = update_user_meta($user_id, self::META_KEY, $notifications);
        
        if ($success) {
            do_action('sisme_notification_read', $user_id, $notification_index);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Notifications] Notification marquée lue: index {$notification_index}, utilisateur {$user_id}");
            }
        }
        
        return $success;
    }
    
    /**
     * Récupérer les notifications d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param bool $unread_only Seulement les non lues (toutes pour les notifications)
     * @return array Liste des notifications
     */
    public static function get_user_notifications($user_id, $unread_only = false) {
        if (!$user_id) {
            return [];
        }
        
        $notifications = get_user_meta($user_id, self::META_KEY, true);
        
        if (empty($notifications) || !is_array($notifications)) {
            return [];
        }
        
        return $notifications;
    }
    
    /**
     * Récupérer le nombre de notifications non lues
     * 
     * @param int $user_id ID de l'utilisateur
     * @return int Nombre de notifications non lues
     */
    public static function get_unread_count($user_id) {
        $notifications = self::get_user_notifications($user_id, false);
        return count($notifications);
    }
    
    /**
     * Vider toutes les notifications d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return bool Succès de l'opération
     */
    public static function clear_all_notifications($user_id) {
        if (!$user_id) {
            return false;
        }
        
        $success = update_user_meta($user_id, self::META_KEY, []);
        
        if ($success) {
            do_action('sisme_notifications_cleared', $user_id);
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Notifications] Toutes notifications vidées pour utilisateur {$user_id}");
            }
        }
        
        return $success;
    }
    
    /**
     * Nettoyer les anciennes notifications pour maintenir la limite FIFO
     * 
     * @param int $user_id ID de l'utilisateur
     */
    public static function cleanup_old_notifications($user_id) {
        if (!$user_id) {
            return;
        }
        
        $notifications = self::get_user_notifications($user_id, false);
        
        if (count($notifications) <= self::MAX_NOTIFICATIONS) {
            return;
        }
        
        self::cleanup_old_notifications_array($notifications);
        update_user_meta($user_id, self::META_KEY, $notifications);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Notifications] Nettoyage automatique: " . count($notifications) . " notifications restantes pour utilisateur {$user_id}");
        }
    }
    
    /**
     * Nettoyer un tableau de notifications (utilisé en interne)
     * 
     * @param array &$notifications Référence au tableau de notifications
     */
    private static function cleanup_old_notifications_array(&$notifications) {
        if (count($notifications) > self::MAX_NOTIFICATIONS) {
            $notifications = array_slice($notifications, 0, self::MAX_NOTIFICATIONS);
        }
    }
    
    /**
     * Récupérer les données enrichies d'une notification
     * 
     * @param array $notification Notification brute
     * @return array|false Notification avec données du jeu ou false
     */
    public static function get_notification_with_game_data($notification) {
        if (!isset($notification['game_id'])) {
            return false;
        }
        
        if (!class_exists('Sisme_Cards_Functions')) {
            return false;
        }
        
        $game_data = Sisme_Cards_Functions::get_game_data($notification['game_id']);
        if (!$game_data) {
            return false;
        }
        
        return array_merge($notification, [
            'game_name' => $game_data['name'],
            'game_slug' => $game_data['slug'],
            'game_url' => $game_data['game_url'],
            'game_cover_url' => $game_data['cover_url'],
            'game_description' => $game_data['description']
        ]);
    }
    
    /**
     * Obtenir les notifications avec données enrichies
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $limit Limite de notifications à récupérer (-1 pour toutes)
     * @return array Notifications avec données des jeux
     */
    public static function get_enriched_notifications($user_id, $limit = -1) {
        $notifications = self::get_user_notifications($user_id, false);
        
        if ($limit > 0) {
            $notifications = array_slice($notifications, 0, $limit);
        }
        
        $enriched = [];
        
        foreach ($notifications as $index => $notification) {
            $enriched_notification = self::get_notification_with_game_data($notification);
            if ($enriched_notification) {
                $enriched_notification['index'] = $index;
                $enriched[] = $enriched_notification;
            }
        }
        
        return $enriched;
    }
    
    /**
     * Ajouter des notifications pour tous les utilisateurs (usage admin)
     * 
     * @param int $game_id ID du jeu
     * @param string $type Type de notification
     * @return int Nombre d'utilisateurs notifiés
     */
    public static function add_notification_for_all_users($game_id, $type = self::TYPE_NEW_GAME) {
        $users = get_users(['fields' => 'ID']);
        $count = 0;
        
        foreach ($users as $user_id) {
            if (self::add_notification($user_id, $game_id, $type)) {
                $count++;
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Notifications] Notification envoyée à {$count} utilisateurs pour jeu {$game_id}");
        }
        
        return $count;
    }
}