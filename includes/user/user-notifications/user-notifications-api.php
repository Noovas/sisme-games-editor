<?php
/**
 * File: /sisme-games-editor/includes/user/user-notifications/user-notifications-api.php
 * API publique pour le rendu des notifications utilisateur
 * 
 * RESPONSABILITÉ:
 * - Rendu du badge de notification avec compteur
 * - Rendu du panel latéral des notifications
 * - Shortcodes publics pour intégration
 * - Formatage et affichage des notifications individuelles
 * 
 * DÉPENDANCES:
 * - Sisme_User_Notifications_Data_Manager
 * - Assets CSS/JS du loader
 * - Module Cards pour données jeux
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Notifications_API {
    
    /**
     * Shortcode badge notifications [sisme_user_notifications_badge]
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML du badge
     */
    public static function render_badge_shortcode($atts = []) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $defaults = [
            'user_id' => get_current_user_id(),
            'show_zero' => false
        ];
        
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_notifications_badge');
        
        return self::render_notification_badge($atts['user_id'], $atts['show_zero']);
    }
    
    /**
     * Shortcode panel notifications [sisme_user_notifications_panel]
     * 
     * @param array $atts Attributs du shortcode
     * @return string HTML du panel complet
     */
    public static function render_panel_shortcode($atts = []) {
        if (!is_user_logged_in()) {
            return self::render_login_required();
        }
        
        $defaults = [
            'user_id' => get_current_user_id(),
            'limit' => 20
        ];
        
        $atts = shortcode_atts($defaults, $atts, 'sisme_user_notifications_panel');
        
        return self::render_notification_badge($atts['user_id']) . 
               self::render_notification_panel($atts['user_id'], $atts['limit']);
    }
    
    /**
     * Rendu du badge de notification avec compteur
     * 
     * @param int $user_id ID de l'utilisateur
     * @param bool $show_zero Afficher même si 0 notifications
     * @return string HTML du badge
     */
    public static function render_notification_badge($user_id, $show_zero = false) {
        $unread_count = Sisme_User_Notifications_Data_Manager::get_unread_count($user_id);
        
        if ($unread_count === 0 && !$show_zero) {
            $show_zero = true;
        }
        
        $badge_class = 'sisme-notifications-badge';
        if ($unread_count > 0) {
            $badge_class .= ' sisme-notifications-badge--active';
        }
        
        $display_count = $unread_count > 99 ? '99+' : $unread_count;
        
        $output = '<div class="' . $badge_class . '" data-count="' . $unread_count . '">';
        $output .= '<button type="button" class="sisme-notifications-toggle" aria-label="Notifications">';
        $output .= '<span class="sisme-notification-icon">🔔</span>';
        
        if ($unread_count > 0) {
            $output .= '<span class="sisme-notification-count">' . esc_html($display_count) . '</span>';
        }
        
        $output .= '</button>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Rendu du panel latéral des notifications
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $limit Limite de notifications à afficher
     * @return string HTML du panel
     */
    public static function render_notification_panel($user_id, $limit = 20) {
        $output = '<div class="sisme-notifications-panel" style="display: none;">';
        
        $output .= '<div class="sisme-notifications-header">';
        $output .= '<h3>Notifications</h3>';
        $output .= '<button type="button" class="sisme-notifications-close" aria-label="Fermer">×</button>';
        $output .= '</div>';
        
        $output .= '<div class="sisme-notifications-content">';
        $output .= self::render_notifications_list($user_id, $limit);
        $output .= '</div>';
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Rendu de la liste des notifications
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $limit Limite de notifications
     * @return string HTML de la liste
     */
    public static function render_notifications_list($user_id, $limit = 20) {
        $notifications = Sisme_User_Notifications_Data_Manager::get_enriched_notifications($user_id, $limit);
        
        if (empty($notifications)) {
            return self::render_empty_state();
        }
        
        $output = '<div class="sisme-notifications-list">';
        foreach ($notifications as $notification) {
            $output .= self::render_notification_item($notification);
        }
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Rendu d'une notification individuelle
     * 
     * @param array $notification Notification avec données enrichies
     * @return string HTML de la notification
     */
    public static function render_notification_item($notification) {
        $time_ago = self::format_time_ago($notification[Sisme_Utils_Games::KEY_TIMESTAMP]);
        $output = '<div class="sisme-notification-item sisme-notification-item--unread" data-index="' . $notification['index'] . '">';
        $output .= '<div class="sisme-notification-content">';
        $output .= '<div class="sisme-notification-game">';
        $output .= '<a href="' . esc_url($notification[Sisme_Utils_Games::KEY_GAME_URL]) . '" class="sisme-notification-game-link">';
        $output .= '<strong>' . esc_html($notification['game_name']) . '</strong>';
        $output .= '</a>';
        $output .= '</div>';
        $output .= '<div class="sisme-notification-message">';
        $output .= self::get_notification_message($notification['type']);
        $output .= '</div>';
        $output .= '<div class="sisme-notification-meta">';
        $output .= '<span class="sisme-notification-time">' . esc_html($time_ago) . '</span>';
        $output .= '</div>';
        $output .= '</div>';
        $output .= '<div class="sisme-notification-actions">';
        $output .= '<button type="button" class="sisme-notification-mark-read" data-index="' . $notification['index'] . '" aria-label="Marquer comme lue">';
        $output .= '✓';
        $output .= '</button>';
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Rendu de l'état vide
     * 
     * @return string HTML état vide
     */
    private static function render_empty_state() {
        $output = '<div class="sisme-notifications-empty">';
        $output .= '<div class="sisme-empty-icon">🔔</div>';
        $output .= '<h4>Aucune notification</h4>';
        $output .= '<p>Vous serez notifié des nouveaux jeux ici.</p>';
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Rendu message connexion requise
     * 
     * @return string HTML message connexion
     */
    private static function render_login_required() {
        $output = '<div class="sisme-notifications-login-required">';
        $output .= '<p>Vous devez être connecté pour voir vos notifications.</p>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Obtenir le message selon le type de notification
     * 
     * @param string $type Type de notification
     * @return string Message formaté
     */
    private static function get_notification_message($type) {
        $messages = [
            Sisme_User_Notifications_Data_Manager::TYPE_NEW_GAME => 'Nouveau jeu disponible'
        ];
        
        return $messages[$type] ?? 'Nouvelle notification';
    }
    
    /**
     * Formater le timestamp en "il y a X"
     * 
     * @param int $timestamp Timestamp Unix
     * @return string Temps formaté
     */
    private static function format_time_ago($timestamp) {
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'À l\'instant';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' min';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . 'h';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . 'j';
        } else {
            return date('d/m/Y', $timestamp);
        }
    }
    
    /**
     * Récupérer les notifications au format JSON pour AJAX
     * 
     * @param int $user_id ID de l'utilisateur
     * @param int $limit Limite de notifications
     * @return array Données pour réponse AJAX
     */
    public static function get_notifications_ajax_data($user_id, $limit = 20) {
        $notifications = Sisme_User_Notifications_Data_Manager::get_enriched_notifications($user_id, $limit);
        $unread_count = Sisme_User_Notifications_Data_Manager::get_unread_count($user_id);
        
        $html = self::render_notifications_list($user_id, $limit);
        
        return [
            'notifications' => $notifications,
            'unread_count' => $unread_count,
            'html' => $html
        ];
    }
}