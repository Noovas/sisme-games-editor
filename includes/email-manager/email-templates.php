<?php
/**
 * File: /sisme-games-editor/includes/email-manager/email-templates.php
 * Templates d'emails avec variables remplaçables
 * 
 * RESPONSABILITÉ:
 * - Définir les templates d'emails texte
 * - Variables remplaçables avec {placeholder}
 * - Templates pour soumissions et autres cas d'usage
 * - Contenu optimisé anti-spam
 * 
 * DÉPENDANCES:
 * - Aucune (fichier de constantes)
 */

if (!defined('ABSPATH')) {
    exit;
}



class Sisme_Email_Templates {

    const EDIT_LINK = Sisme_Utils_Users::DEVELOPER_URL;
    const DASHBOARD_LINK = Sisme_Utils_Users::DASHBOARD_URL;

    /**
     * Email de soumission rejetée
     * @param string $user_name Nom de l'utilisateur
     * @param string $game_name Nom du jeu
     * @param string $rejection_reason Motif du rejet
     * @return string Contenu email complet
     */
    public static function submission_rejected($user_name, $game_name, $rejection_reason) {
        $edit_link = home_url(self::EDIT_LINK);
        return "Bonjour {$user_name},

Nous avons examiné votre soumission de jeu \"{$game_name}\" et malheureusement, nous ne pouvons pas l'approuver dans son état actuel.

Motif du rejet :
{$rejection_reason}

Ne vous découragez pas ! Vous pouvez modifier votre soumission et la soumettre à nouveau en tenant compte de nos commentaires.

Pour modifier votre soumission, rendez-vous ici :
{$edit_link}

Nous encourageons les développeurs à améliorer leurs soumissions et à les resoumettre. Notre équipe sera ravie de réévaluer votre jeu une fois les modifications apportées.

Cordialement,
L'équipe Sisme Games

---
Si vous avez des questions, contactez-nous à sisme-games@sisme.fr";
    }
    
    /**
     * Email de soumission approuvée
     * @param string $user_name Nom de l'utilisateur
     * @param string $game_name Nom du jeu
     * @param string $game_link Lien vers fiche jeu publiée
     * @return string Contenu email complet
     */
    public static function submission_approved($user_name, $game_name) {
        $dashboard_link = home_url(self::DASHBOARD_LINK);
        return "Félicitations {$user_name} !

Nous avons le plaisir de vous informer que votre soumission de jeu \"{$game_name}\" a été approuvée et est maintenant publiée sur Sisme Games.

Votre jeu est désormais visible par notre communauté de joueurs passionnés. Nous vous remercions de contribuer à enrichir notre catalogue avec du contenu de qualité.

Gérer vos soumissions et accéder à votre jeu : {$dashboard_link}

Nous espérons que cette publication vous apportera la visibilité que mérite votre travail. N'hésitez pas à soumettre d'autres jeux !

Bravo et merci pour votre contribution !

L'équipe Sisme Games

---
Si vous avez des questions, contactez-nous à sisme-games@sisme.fr";
    }
}