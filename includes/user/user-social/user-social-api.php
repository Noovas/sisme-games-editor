<?php
/**
 * File: /sisme-games-editor/includes/user/user-social/user-social-api.php
 * API du module user-social - Gestion des relations sociales
 * 
 * RESPONSABILITÉ:
 * - Ajout et suppression d'amis
 * - Gestion des demandes d'ami (envoi, acceptation, refus)
 * - Vérification des relations d'amitié
 * - Récupération des listes d'amis et statistiques
 * - Utilise la structure META_FRIENDS_LIST de utils-users.php
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Social_API {
    
    /**
     * États possibles des relations d'amitié
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    
    /**
     * Envoyer une demande d'ami
     * 
     * @param int $sender_id ID de l'utilisateur qui envoie
     * @param int $receiver_id ID de l'utilisateur qui reçoit
     * @return array Résultat avec success et message
     */
    public static function send_friend_request($sender_id, $receiver_id) {
        // Validation des utilisateurs
        if (!Sisme_Utils_Users::validate_user_id($sender_id, 'send_friend_request_sender') ||
            !Sisme_Utils_Users::validate_user_id($receiver_id, 'send_friend_request_receiver')) {
            return ['success' => false, 'message' => 'Utilisateur invalide'];
        }
        
        // Éviter l'auto-ajout
        if ($sender_id === $receiver_id) {
            return ['success' => false, 'message' => 'Impossible de s\'ajouter soi-même'];
        }
        
        // Vérifier si déjà amis
        if (self::are_friends($sender_id, $receiver_id)) {
            return ['success' => false, 'message' => 'Vous êtes déjà amis'];
        }
        
        // Vérifier si une demande existe déjà
        if (self::has_pending_request($sender_id, $receiver_id)) {
            return ['success' => false, 'message' => 'Demande déjà envoyée'];
        }
        
        // Récupérer la liste d'amis du destinataire
        $receiver_friends = get_user_meta($receiver_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
        if (!is_array($receiver_friends)) {
            $receiver_friends = [];
        }
        
        // Ajouter la demande dans la liste du destinataire
        $receiver_friends[$sender_id] = [
            'status' => self::STATUS_PENDING,
            'date' => current_time('mysql')
        ];
        
        // Sauvegarder
        $success = update_user_meta($receiver_id, Sisme_Utils_Users::META_FRIENDS_LIST, $receiver_friends);
        
        if ($success) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Social] Demande d'ami envoyée de {$sender_id} vers {$receiver_id}");
            }
            return ['success' => true, 'message' => 'Demande d\'ami envoyée'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de l\'envoi de la demande'];
        }
    }
    
    /**
     * Accepter une demande d'ami
     * 
     * @param int $sender_id ID de l'utilisateur qui a envoyé la demande
     * @param int $receiver_id ID de l'utilisateur qui accepte
     * @return array Résultat avec success et message
     */
    public static function accept_friend_request($sender_id, $receiver_id) {
        // Validation
        if (!Sisme_Utils_Users::validate_user_id($sender_id, 'accept_friend_request_sender') ||
            !Sisme_Utils_Users::validate_user_id($receiver_id, 'accept_friend_request_receiver')) {
            return ['success' => false, 'message' => 'Utilisateur invalide'];
        }
        
        // Vérifier qu'il y a bien une demande en attente
        if (!self::has_pending_request($sender_id, $receiver_id)) {
            return ['success' => false, 'message' => 'Aucune demande en attente'];
        }
        
        $acceptance_date = current_time('mysql');
        
        // Mettre à jour la liste du destinataire (accepter la demande)
        $receiver_friends = get_user_meta($receiver_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
        if (!is_array($receiver_friends)) {
            $receiver_friends = [];
        }
        
        $receiver_friends[$sender_id] = [
            'status' => self::STATUS_ACCEPTED,
            'date' => $acceptance_date
        ];
        
        // Ajouter la relation réciproque dans la liste de l'expéditeur
        $sender_friends = get_user_meta($sender_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
        if (!is_array($sender_friends)) {
            $sender_friends = [];
        }
        
        $sender_friends[$receiver_id] = [
            'status' => self::STATUS_ACCEPTED,
            'date' => $acceptance_date
        ];
        
        // Sauvegarder les deux relations
        $success1 = update_user_meta($receiver_id, Sisme_Utils_Users::META_FRIENDS_LIST, $receiver_friends);
        $success2 = update_user_meta($sender_id, Sisme_Utils_Users::META_FRIENDS_LIST, $sender_friends);
        
        if ($success1 && $success2) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Social] Amitié acceptée entre {$sender_id} et {$receiver_id}");
            }
            return ['success' => true, 'message' => 'Demande d\'ami acceptée'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de l\'acceptation'];
        }
    }
    
    /**
     * Refuser une demande d'ami
     * 
     * @param int $sender_id ID de l'utilisateur qui a envoyé la demande
     * @param int $receiver_id ID de l'utilisateur qui refuse
     * @return array Résultat avec success et message
     */
    public static function decline_friend_request($sender_id, $receiver_id) {
        // Validation
        if (!Sisme_Utils_Users::validate_user_id($sender_id, 'decline_friend_request_sender') ||
            !Sisme_Utils_Users::validate_user_id($receiver_id, 'decline_friend_request_receiver')) {
            return ['success' => false, 'message' => 'Utilisateur invalide'];
        }
        
        // Vérifier qu'il y a bien une demande en attente
        if (!self::has_pending_request($sender_id, $receiver_id)) {
            return ['success' => false, 'message' => 'Aucune demande en attente'];
        }
        
        // Supprimer la demande de la liste du destinataire
        $receiver_friends = get_user_meta($receiver_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
        if (is_array($receiver_friends) && isset($receiver_friends[$sender_id])) {
            unset($receiver_friends[$sender_id]);
            $success = update_user_meta($receiver_id, Sisme_Utils_Users::META_FRIENDS_LIST, $receiver_friends);
            
            if ($success) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Social] Demande d'ami refusée de {$sender_id} par {$receiver_id}");
                }
                return ['success' => true, 'message' => 'Demande d\'ami refusée'];
            }
        }
        
        return ['success' => false, 'message' => 'Erreur lors du refus'];
    }
    
    /**
     * Annuler une demande d'ami envoyée
     * 
     * @param int $sender_id ID de l'utilisateur qui annule
     * @param int $receiver_id ID de l'utilisateur destinataire
     * @return array Résultat avec success et message
     */
    public static function cancel_friend_request($sender_id, $receiver_id) {
        // Validation
        if (!Sisme_Utils_Users::validate_user_id($sender_id, 'cancel_friend_request_sender') ||
            !Sisme_Utils_Users::validate_user_id($receiver_id, 'cancel_friend_request_receiver')) {
            return ['success' => false, 'message' => 'Utilisateur invalide'];
        }
        
        // Vérifier qu'il y a bien une demande en attente
        if (!self::has_pending_request($sender_id, $receiver_id)) {
            return ['success' => false, 'message' => 'Aucune demande en attente'];
        }
        
        // Supprimer la demande de la liste du destinataire
        $receiver_friends = get_user_meta($receiver_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
        if (is_array($receiver_friends) && isset($receiver_friends[$sender_id])) {
            unset($receiver_friends[$sender_id]);
            $success = update_user_meta($receiver_id, Sisme_Utils_Users::META_FRIENDS_LIST, $receiver_friends);
            
            if ($success) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Social] Demande d'ami annulée par {$sender_id} vers {$receiver_id}");
                }
                return ['success' => true, 'message' => 'Demande annulée'];
            }
        }
        
        return ['success' => false, 'message' => 'Erreur lors de l\'annulation'];
    }
    
    /**
     * Supprimer un ami
     * 
     * @param int $user1_id Premier utilisateur
     * @param int $user2_id Deuxième utilisateur (ami à supprimer)
     * @return array Résultat avec success et message
     */
    public static function remove_friend($user1_id, $user2_id) {
        // Validation
        if (!Sisme_Utils_Users::validate_user_id($user1_id, 'remove_friend_user1') ||
            !Sisme_Utils_Users::validate_user_id($user2_id, 'remove_friend_user2')) {
            return ['success' => false, 'message' => 'Utilisateur invalide'];
        }
        
        // Vérifier qu'ils sont bien amis
        if (!self::are_friends($user1_id, $user2_id)) {
            return ['success' => false, 'message' => 'Vous n\'êtes pas amis'];
        }
        
        // Supprimer la relation des deux côtés
        $user1_friends = get_user_meta($user1_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
        $user2_friends = get_user_meta($user2_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
        
        if (!is_array($user1_friends)) $user1_friends = [];
        if (!is_array($user2_friends)) $user2_friends = [];
        
        // Supprimer les relations
        unset($user1_friends[$user2_id]);
        unset($user2_friends[$user1_id]);
        
        // Sauvegarder
        $success1 = update_user_meta($user1_id, Sisme_Utils_Users::META_FRIENDS_LIST, $user1_friends);
        $success2 = update_user_meta($user2_id, Sisme_Utils_Users::META_FRIENDS_LIST, $user2_friends);
        
        if ($success1 && $success2) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("[Sisme User Social] Amitié supprimée entre {$user1_id} et {$user2_id}");
            }
            return ['success' => true, 'message' => 'Ami supprimé'];
        } else {
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
    }
    
    /**
     * Vérifier si deux utilisateurs sont amis
     * 
     * @param int $user1_id Premier utilisateur
     * @param int $user2_id Deuxième utilisateur
     * @return bool Sont amis
     */
    public static function are_friends($user1_id, $user2_id) {
        if (!$user1_id || !$user2_id || $user1_id === $user2_id) {
            return false;
        }
        
        // Vérifier dans la liste d'amis du premier utilisateur
        $user1_friends = get_user_meta($user1_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
        if (!is_array($user1_friends)) {
            return false;
        }
        
        // Vérifier si user2 est dans la liste de user1 avec le statut "accepted"
        if (isset($user1_friends[$user2_id]) && $user1_friends[$user2_id]['status'] === self::STATUS_ACCEPTED) {
            // Vérification réciproque pour s'assurer de la cohérence
            $user2_friends = get_user_meta($user2_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
            if (is_array($user2_friends) && 
                isset($user2_friends[$user1_id]) && 
                $user2_friends[$user1_id]['status'] === self::STATUS_ACCEPTED) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Vérifier s'il y a une demande d'ami en attente
     * 
     * @param int $sender_id ID de l'expéditeur
     * @param int $receiver_id ID du destinataire
     * @return bool Demande en attente
     */
    public static function has_pending_request($sender_id, $receiver_id) {
        if (!$sender_id || !$receiver_id) {
            return false;
        }
        
        // Vérifier dans la liste du destinataire
        $receiver_friends = get_user_meta($receiver_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
        if (!is_array($receiver_friends)) {
            return false;
        }
        
        return isset($receiver_friends[$sender_id]) && 
               $receiver_friends[$sender_id]['status'] === self::STATUS_PENDING;
    }
    
    /**
     * Obtenir la liste des amis d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Liste des amis [user_id => metadata]
     */
    public static function get_user_friends($user_id) {
        if (!Sisme_Utils_Users::validate_user_id($user_id, 'get_user_friends')) {
            return [];
        }
        
        $friends_list = get_user_meta($user_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
        if (!is_array($friends_list)) {
            return [];
        }
        
        // Filtrer pour ne garder que les amis acceptés
        $accepted_friends = [];
        foreach ($friends_list as $friend_id => $metadata) {
            if ($metadata['status'] === self::STATUS_ACCEPTED) {
                // Valider que l'ami existe encore
                if (Sisme_Utils_Users::validate_user_id($friend_id)) {
                    $accepted_friends[$friend_id] = $metadata;
                }
            }
        }
        
        return $accepted_friends;
    }
    
    /**
     * Obtenir les demandes d'ami reçues
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Demandes reçues [sender_id => metadata]
     */
    public static function get_pending_friend_requests($user_id) {
        if (!Sisme_Utils_Users::validate_user_id($user_id, 'get_pending_friend_requests')) {
            return [];
        }
        
        $friends_list = get_user_meta($user_id, Sisme_Utils_Users::META_FRIENDS_LIST, true);
        if (!is_array($friends_list)) {
            return [];
        }
        
        // Filtrer pour ne garder que les demandes en attente
        $pending_requests = [];
        foreach ($friends_list as $sender_id => $metadata) {
            if ($metadata['status'] === self::STATUS_PENDING) {
                // Valider que l'expéditeur existe encore
                if (Sisme_Utils_Users::validate_user_id($sender_id)) {
                    $pending_requests[$sender_id] = $metadata;
                }
            }
        }
        
        return $pending_requests;
    }
    
    /**
     * Obtenir les statistiques sociales d'un utilisateur
     * 
     * @param int $user_id ID de l'utilisateur
     * @return array Statistiques [friends_count, pending_requests]
     */
    public static function get_user_social_stats($user_id) {
        if (!Sisme_Utils_Users::validate_user_id($user_id, 'get_user_social_stats')) {
            return [
                'friends_count' => 0,
                'pending_requests' => 0
            ];
        }
        
        $friends = self::get_user_friends($user_id);
        $pending = self::get_pending_friend_requests($user_id);
        
        return [
            'friends_count' => count($friends),
            'pending_requests' => count($pending)
        ];
    }
    
    /**
     * Obtenir le statut de relation entre deux utilisateurs
     * 
     * @param int $user1_id Premier utilisateur
     * @param int $user2_id Deuxième utilisateur
     * @return string 'friends'|'pending_from_user1'|'pending_from_user2'|'none'
     */
    public static function get_relationship_status($user1_id, $user2_id) {
        if (!$user1_id || !$user2_id || $user1_id === $user2_id) {
            return 'none';
        }
        
        // Vérifier s'ils sont amis
        if (self::are_friends($user1_id, $user2_id)) {
            return 'friends';
        }
        
        // Vérifier s'il y a une demande en attente de user1 vers user2
        if (self::has_pending_request($user1_id, $user2_id)) {
            return 'pending_from_user1';
        }
        
        // Vérifier s'il y a une demande en attente de user2 vers user1
        if (self::has_pending_request($user2_id, $user1_id)) {
            return 'pending_from_user2';
        }
        
        return 'none';
    }
}