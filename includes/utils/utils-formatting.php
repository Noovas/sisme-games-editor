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

    const DEFAULT_PLATEFORM_PC = '🖥️';
    const DEFAULT_PLATEFORM_CONSOLE = '🎮';
    const DEFAULT_PLATEFORM_MOBILE = '📱';
    const DEFAULT_PLATEFORM_WEB = '🌐';
    
    /**
     * ✂️ Tronquer intelligemment un texte sur les mots
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

    /**
     * 🎮 Obtenir l'icône d'une plateforme
     * 
     * @param string $platform Nom de la plateforme
     * @return string Icône emoji de la plateforme
     */
    public static function get_platform_icon($platform) {
        $icons = array(
            'windows' => self::DEFAULT_PLATEFORM_PC,
            'mac' => self::DEFAULT_PLATEFORM_PC,
            'linux' => self::DEFAULT_PLATEFORM_PC,
            'playstation' => self::DEFAULT_PLATEFORM_CONSOLE,
            'xbox' => self::DEFAULT_PLATEFORM_CONSOLE,
            'nintendo-switch' => self::DEFAULT_PLATEFORM_CONSOLE,
            'ios' => self::DEFAULT_PLATEFORM_MOBILE,
            'android' => self::DEFAULT_PLATEFORM_MOBILE,
            'web' => self::DEFAULT_PLATEFORM_WEB
        );
        $platform = strtolower(trim($platform));
        return isset($icons[$platform]) ? $icons[$platform] : self::DEFAULT_PLATEFORM_PC;
    }

    /**
     * Convertir les IDs de genres en slugs
     * 
     * @param array $genre_ids Liste des IDs de genres
     * @return array Liste des slugs de genres (les noms en fait)
     */
    public static function convert_genre_ids_to_slugs($genre_ids) {
        $genre_names = array();
        
        foreach ($genre_ids as $genre_id) {
            $genre_id = intval($genre_id);
            if ($genre_id <= 0) {
                continue;
            }
            $category = get_category($genre_id);
            
            if ($category && !is_wp_error($category)) {
                $name = $category->name;
                $clean_name = preg_replace('/^jeux-/i', '', $name);
                $genre_names[] = $clean_name;
            }
        }
        return array_unique($genre_names);
    }

    /**
     * 🎨 Générer une classe CSS avec modificateurs et classe personnalisée
     * 
     * @param string $base_class Classe CSS de base
     * @param array $modifiers Modificateurs BEM (optionnel)
     * @param string $custom_class Classe CSS personnalisée (optionnel)
     * @return string Classes CSS assemblées
     */
    public static function build_css_class($base_class, $modifiers = array(), $custom_class = '') {
        if (empty($base_class) || !is_string($base_class)) {
            return '';
        } 
        $classes = array($base_class);
        if (is_array($modifiers)) {
            foreach ($modifiers as $modifier) {
                if (!empty($modifier) && is_string($modifier)) {
                    $classes[] = $base_class . '--' . $modifier;
                }
            }
        }
        if (!empty($custom_class) && is_string($custom_class)) {
            $classes[] = $custom_class;
        } 
        return implode(' ', $classes);
    }

    /**
     * 📅 Formater une date de sortie - Format court
     * 
     * @param string $release_date Date au format YYYY-MM-DD
     * @return string Date formatée (ex: "15 déc 2024") ou chaîne vide
     */
    public static function format_release_date($release_date) {
        if (empty($release_date)) {
            return '';
        }
        $date = DateTime::createFromFormat('Y-m-d', $release_date);
        if (!$date) {
            return '';
        }
        $mois_fr = array(
            1 => 'janv', 2 => 'févr', 3 => 'mars', 4 => 'avr',
            5 => 'mai', 6 => 'juin', 7 => 'juil', 8 => 'août',
            9 => 'sept', 10 => 'oct', 11 => 'nov', 12 => 'déc'
        );
        $jour = $date->format('j');
        $mois_num = (int)$date->format('n');
        $annee = $date->format('Y');
        return $jour . ' ' . $mois_fr[$mois_num] . ' ' . $annee;
    }
    
    /**
     * 📅 Formater une date de sortie - Format long
     * 
     * @param string $release_date Date au format YYYY-MM-DD
     * @return string Date formatée (ex: "15 décembre 2024") ou chaîne vide
     */
    public static function format_release_date_long($release_date) {
        if (empty($release_date)) {
            return '';
        }
        $date = DateTime::createFromFormat('Y-m-d', $release_date);
        if (!$date) {
            return '';
        }
        $mois_complets = array(
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
        );
        $jour = $date->format('j');
        $mois_num = (int)$date->format('n');
        $annee = $date->format('Y');
        return $jour . ' ' . $mois_complets[$mois_num] . ' ' . $annee;
    }
    
    /**
     * 📅 Formater une date avec statut (sorti/à venir)
     * 
     * @param string $release_date Date au format YYYY-MM-DD
     * @param bool $show_status Afficher le statut avec icône
     * @return string Date formatée avec statut optionnel
     */
    public static function format_release_date_with_status($release_date, $show_status = false) {
        if (empty($release_date)) {
            return '';
        }
        $date = DateTime::createFromFormat('Y-m-d', $release_date);
        if (!$date) {
            return '';
        }
        $formatted_date = self::format_release_date($release_date);      
        if (!$show_status) {
            return $formatted_date;
        }
        $release_timestamp = strtotime($release_date);
        $current_timestamp = time();
        $is_released = $current_timestamp >= $release_timestamp;
        
        if ($is_released) {
            return '✅ ' . $formatted_date;
        } else {
            return '📅 ' . $formatted_date;
        }
    }
}
