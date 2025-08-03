<?php
/**
 * File: /sisme-games-editor/includes/email-manager/email-manager.php
 * Gestionnaire d'envoi d'emails simple anti-spam
 * 
 * RESPONSABILITÉ:
 * - Envoi emails text uniquement (anti-spam)
 * - Headers optimisés pour éviter spam
 * - API simple send_email(user_ids, subject, content)
 * - Logging des erreurs et succès
 * 
 * DÉPENDANCES:
 * - WordPress wp_mail()
 * - WordPress User API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Email_Manager {
    
    /**
     * Envoyer un email à une liste d'utilisateurs
     * @param array $user_ids Liste des IDs utilisateurs destinataires
     * @param string $subject Sujet de l'email
     * @param string $content Contenu text de l'email
     * @return array Résultat avec success/error count
     */
    public static function send_email($user_ids, $subject, $content) {
        if (empty($user_ids) || empty($subject) || empty($content)) {
            return ['success' => 0, 'errors' => 1, 'message' => 'Paramètres manquants'];
        }
        
        if (!is_array($user_ids)) {
            $user_ids = [$user_ids];
        }
        
        $success_count = 0;
        $error_count = 0;
        $errors = [];
        
        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if (!$user || !$user->user_email) {
                $error_count++;
                $errors[] = "Utilisateur {$user_id} invalide";
                continue;
            }
            
            $result = self::send_single_email($user->user_email, $subject, $content);
            if ($result) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = "Échec envoi pour {$user->user_email}";
            }
        }
        
        return [
            'success' => $success_count,
            'errors' => $error_count,
            'details' => $errors
        ];
    }
    
    /**
     * Envoyer un email à une adresse unique
     * @param string $to Adresse email destinataire
     * @param string $subject Sujet de l'email
     * @param string $content Contenu text de l'email
     * @return bool Succès de l'envoi
     */
    private static function send_single_email($to, $subject, $content) {
        $clean_content = self::clean_content($content);
        $clean_subject = self::clean_subject($subject);
        $result = wp_mail($to, $clean_subject, $clean_content);        
        return $result;
    }
    
    /**
     * Nettoyer le contenu de l'email
     * @param string $content Contenu brut
     * @return string Contenu nettoyé
     */
    private static function clean_content($content) {
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        $content = strip_tags($content);
        $content = trim($content);
        return $content;
    }
    
    /**
     * Nettoyer le sujet de l'email
     * @param string $subject Sujet brut
     * @return string Sujet nettoyé
     */
    private static function clean_subject($subject) {
        $subject = html_entity_decode($subject, ENT_QUOTES, 'UTF-8');
        $subject = strip_tags($subject);
        $subject = trim($subject);
        $subject = substr($subject, 0, 100);
        return $subject;
    }
}