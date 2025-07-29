<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/user-developer-email-notifications.php
 * Système de notifications email SIMPLIFIÉ pour le module développeur
 * 
 * ANTI-SPAM:
 * - Emails texte simple (pas de HTML complexe)
 * - Headers corrects
 * - Contenu simple et direct
 * - Encodage UTF-8 propre
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Developer_Email_Notifications {
    
    /**
     * Types de notifications email développeur
     */
    const TYPE_APPLICATION_SUBMITTED = 'developer_application_submitted';
    const TYPE_APPLICATION_APPROVED = 'developer_application_approved';
    const TYPE_APPLICATION_REJECTED = 'developer_application_rejected';
    
    /**
     * Initialiser les hooks pour l'envoi automatique d'emails
     */
    public static function init_hooks() {
        // Hooks pour envoi automatique lors des changements de statut
        add_action('sisme_developer_application_submitted', [__CLASS__, 'send_application_submitted_email'], 10, 1);
        add_action('sisme_developer_application_approved', [__CLASS__, 'send_application_approved_email'], 10, 2);
        add_action('sisme_developer_application_rejected', [__CLASS__, 'send_application_rejected_email'], 10, 2);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme Developer Email] Hooks email notifications initialisés');
        }
    }
    
    /**
     * Envoyer email de confirmation de candidature soumise
     */
    public static function send_application_submitted_email($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $application_data = Sisme_User_Developer_Data_Manager::get_developer_application($user_id);
        if (!$application_data) {
            return false;
        }
        
        $studio_name = $application_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME];
        $site_name = get_bloginfo('name');
        
        $subject = 'Candidature développeur reçue - ' . $site_name;
        
        $message = "Bonjour " . $user->display_name . ",

Nous avons bien reçu votre candidature pour devenir développeur sur " . $site_name . ".

Studio : " . $studio_name . "
Date : " . current_time('d/m/Y H:i') . "

Votre candidature est maintenant en cours d'examen par notre équipe.
Vous recevrez un email dès qu'elle aura été traitée.

Délai d'examen : 2 jours ouvrés maximum

Vous pouvez suivre l'état de votre candidature sur votre dashboard :
" . home_url(Sisme_Utils_Users::DASHBOARD_URL) . "

Merci pour votre intérêt !

L'équipe " . $site_name;
        
        return self::send_simple_email($user->user_email, $subject, $message);
    }
    
    /**
     * Envoyer email d'approbation de candidature
     */
    public static function send_application_approved_email($user_id, $admin_notes = '') {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $application_data = Sisme_User_Developer_Data_Manager::get_developer_application($user_id);
        if (!$application_data) {
            return false;
        }
        
        $studio_name = $application_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME];
        $site_name = get_bloginfo('name');
        
        $subject = 'Félicitations ! Candidature développeur approuvée - ' . $site_name;
        
        $message = "Bonjour " . $user->display_name . ",

Excellente nouvelle ! Votre candidature pour devenir développeur a été APPROUVÉE.

Studio : " . $studio_name . "
Date d'approbation : " . current_time('d/m/Y H:i') . "

Vous pouvez maintenant :
- Soumettre vos jeux sur notre plateforme
- Gérer vos publications
- Accéder aux statistiques développeur

";

        if (!empty($admin_notes)) {
            $message .= "Message de notre équipe :
" . strip_tags($admin_notes) . "

";
        }

        $message .= "Accédez dès maintenant à votre espace développeur :
" . home_url(Sisme_Utils_Users::DASHBOARD_URL) . "

Bienvenue dans la communauté des développeurs " . $site_name . " !

L'équipe " . $site_name;
        
        return self::send_simple_email($user->user_email, $subject, $message);
    }
    
    /**
     * Envoyer email de rejet de candidature
     */
    public static function send_application_rejected_email($user_id, $admin_notes = '') {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        $application_data = Sisme_User_Developer_Data_Manager::get_developer_application($user_id);
        if (!$application_data) {
            return false;
        }
        
        $studio_name = $application_data[Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME];
        $site_name = get_bloginfo('name');
        
        $subject = 'Mise à jour candidature développeur - ' . $site_name;
        
        $message = "Bonjour " . $user->display_name . ",

Nous avons examiné votre candidature pour devenir développeur.

Studio : " . $studio_name . "
Date d'examen : " . current_time('d/m/Y H:i') . "

Malheureusement, nous ne pouvons pas l'approuver à ce stade.

";

        if (!empty($admin_notes)) {
            $message .= "Commentaires de notre équipe :
" . strip_tags($admin_notes) . "

";
        }

        $message .= "Conseils pour une prochaine candidature :
- Présentez des projets terminés et jouables
- Détaillez votre expérience en développement
- Montrez votre motivation pour notre communauté
- Vérifiez que vos liens portfolio sont accessibles

Bonne nouvelle : vous pouvez refaire une candidature quand vous le souhaitez !

" . home_url(Sisme_Utils_Users::DASHBOARD_URL) . "

Merci pour votre compréhension.

L'équipe " . $site_name;
        
        return self::send_simple_email($user->user_email, $subject, $message);
    }
    
    /**
     * Fonction d'envoi d'email simple et propre
     */
    private static function send_simple_email($to, $subject, $message) {
        // Headers simples pour éviter le spam
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        );
        
        // Nettoyer le message
        $clean_message = strip_tags($message);
        $clean_message = html_entity_decode($clean_message, ENT_QUOTES, 'UTF-8');
        
        $success = wp_mail($to, $subject, $clean_message, $headers);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $status = $success ? 'SUCCÈS' : 'ÉCHEC';
            error_log("[Sisme Developer Email] {$status} envoi email - Destinataire: {$to}");
        }
        
        return $success;
    }
}

// Initialiser les hooks automatiquement
add_action('init', function() {
    Sisme_User_Developer_Email_Notifications::init_hooks();
}, 20);