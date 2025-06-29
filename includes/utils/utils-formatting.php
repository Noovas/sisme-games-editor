<?php
/**
 * File: /sisme-games-editor/includes/utils/utils-formatting.php
 * Fonctions utilitaires de formatage et présentation
 * 
 * RESPONSABILITÉ:
 * - Formatage de textes (troncature intelligente)
 * - Formatage de dates (release dates, timestamps)
 * - Génération de classes CSS
 * - Icônes et symboles (plateformes, badges)
 * - Formatage de données pour affichage
 * 
 * DÉPENDANCES:
 * - WordPress core functions
 * - Constantes système (DAY_IN_SECONDS)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Utils_Formatting {
    
    /**
     * Constantes pour le formatage
     */
    const DEFAULT_TRUNCATE_LENGTH = 150;
    const DEFAULT_DATE_FORMAT = 'j F Y';
    
    /**
     * ✂️ Tronquer intelligemment un texte sur les mots
     * Migration depuis: Sisme_Cards_Functions::truncate_smart()
     * 
     * @param string $text Texte à tronquer
     * @param int $max_length Longueur maximale (défaut: 150)
     * @return string Texte tronqué avec points de suspension
     */
    public static function truncate_smart($text, $max_length = self::DEFAULT_TRUNCATE_LENGTH) {
        if (empty($text) || !is_string($text)) {
            return '';
        }
        if (!is_numeric($max_length) || $max_length <= 0) {
            $max_length = self::DEFAULT_TRUNCATE_LENGTH;
        }
        if (strlen($text) <= $max_length) {
            return $text;
        }
        $truncated = substr($text, 0, $max_length);
        $last_space = strrpos($truncated, ' ');
        
        if ($last_space !== false) {
            $truncated = substr($truncated, 0, $last_space);
        }
        return rtrim($truncated, '.,;:!?') . '...';
    }
}

// TODO: Prochaines fonctions à migrer
// - get_platform_icon()
// - build_css_class()  
// - format_release_date()
// - format_release_date_long()
// - format_release_date_with_status()