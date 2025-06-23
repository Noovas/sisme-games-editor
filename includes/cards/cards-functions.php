<?php
/**
 * File: /sisme-games-editor/includes/cards/cards-functions.php
 * Fonctions utilitaires partagées pour tous les types de cartes
 * 
 * RESPONSABILITÉ:
 * - Récupération des données de jeux
 * - Fonctions de formatage
 * - Utilitaires de calcul (badges, dates, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Cards_Functions {
    
    /**
     * 📊 Récupérer les données complètes d'un jeu
     * 
     * @param int $term_id ID du jeu
     * @return array|false Données du jeu ou false si incomplet
     */
    public static function get_game_data($term_id) {
        
        // Vérifications de base
        $description = get_term_meta($term_id, 'game_description', true);
        $cover_id = get_term_meta($term_id, 'cover_main', true);
        
        if (empty($description) || empty($cover_id)) {
            return false;
        }
        
        // URL de la cover
        $cover_url = wp_get_attachment_image_url($cover_id, 'full');
        if (!$cover_url) {
            return false;
        }
        
        // Récupérer les infos de base
        $term = get_term($term_id);
        
        // Construire les données
        $game_data = array(
            'term_id' => $term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => wp_strip_all_tags($description),
            'cover_url' => $cover_url,
            'game_url' => home_url('/tag/' . $term->slug . '/'),
            'genres' => self::get_game_genres($term_id),
            'platforms' => self::get_game_platforms($term_id),
            'release_date' => get_term_meta($term_id, 'release_date', true),
            'last_update' => get_term_meta($term_id, 'last_update', true),
            'timestamp' => self::get_game_timestamp($term_id)
        );
        
        return $game_data;
    }
    
    /**
     * 🏷️ Récupérer les genres du jeu
     */
    public static function get_game_genres($term_id) {
        $genre_ids = get_term_meta($term_id, 'game_genres', true) ?: array();
        $genres = array();
        
        foreach ($genre_ids as $genre_id) {
            $genre = get_category($genre_id);
            if ($genre) {
                $genres[] = array(
                    'id' => $genre_id,
                    'name' => str_replace('jeux-', '', $genre->name), // Nettoyer le préfixe
                    'slug' => $genre->slug
                );
            }
        }
        
        return $genres;
    }
    
    /**
     * 🎮 Récupérer les plateformes du jeu
     */
    public static function get_game_platforms($term_id) {
        return get_term_meta($term_id, 'game_platforms', true) ?: array();
    }
    
    /**
     * ⏰ Déterminer le timestamp de référence du jeu
     */
    public static function get_game_timestamp($term_id) {
        $last_update = get_term_meta($term_id, 'last_update', true);
        $release_date = get_term_meta($term_id, 'release_date', true);
        
        // Priorité : last_update > release_date > maintenant
        if ($last_update) {
            return strtotime($last_update);
        } elseif ($release_date) {
            return strtotime($release_date);
        } else {
            return time();
        }
    }
    
    /**
     * 🏷️ Déterminer le badge du jeu selon sa fraîcheur
     */
    public static function get_game_badge($game_data) {
        $now = time();
        $game_time = $game_data['timestamp'];
        $diff_days = ($now - $game_time) / DAY_IN_SECONDS;
        
        if ($diff_days <= 7) {
            return array(
                'class' => 'sisme-badge-new',
                'text' => 'NOUVEAU'
            );
        } elseif ($diff_days <= 30 && !empty($game_data['last_update'])) {
            return array(
                'class' => 'sisme-badge-updated',
                'text' => 'MIS À JOUR'
            );
        }
        
        return null; // Pas de badge
    }
    
    /**
     * 🕒 Formater une date en format relatif
     */
    public static function format_relative_date($timestamp) {
        $diff = time() - $timestamp;
        
        if ($diff < HOUR_IN_SECONDS) {
            $minutes = floor($diff / MINUTE_IN_SECONDS);
            return $minutes <= 1 ? 'Il y a 1 minute' : "Il y a {$minutes} minutes";
        } elseif ($diff < DAY_IN_SECONDS) {
            $hours = floor($diff / HOUR_IN_SECONDS);
            return $hours <= 1 ? 'Il y a 1 heure' : "Il y a {$hours} heures";
        } elseif ($diff < WEEK_IN_SECONDS) {
            $days = floor($diff / DAY_IN_SECONDS);
            return $days <= 1 ? 'Il y a 1 jour' : "Il y a {$days} jours";
        } elseif ($diff < MONTH_IN_SECONDS) {
            $weeks = floor($diff / WEEK_IN_SECONDS);
            return $weeks <= 1 ? 'Il y a 1 semaine' : "Il y a {$weeks} semaines";
        } else {
            $months = floor($diff / MONTH_IN_SECONDS);
            return $months <= 1 ? 'Il y a 1 mois' : "Il y a {$months} mois";
        }
    }
    
    /**
     * ✂️ Tronquer intelligemment un texte sur les mots
     */
    public static function truncate_smart($text, $max_length) {
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
     */
    public static function get_platform_icon($platform) {
        $icons = array(
            'windows' => '🖥️',
            'mac' => '🖥️',
            'linux' => '🖥️',
            'playstation' => '🎮',
            'xbox' => '🎮',
            'nintendo-switch' => '🎮',
            'ios' => '📱',
            'android' => '📱',
            'web' => '🌐'
        );
        
        return isset($icons[$platform]) ? $icons[$platform] : '💻';
    }
    
    /**
     * 🎨 Générer une classe CSS avec options
     */
    public static function build_css_class($base_class, $modifiers = array(), $custom_class = '') {
        $classes = array($base_class);
        
        // Ajouter les modificateurs
        foreach ($modifiers as $modifier) {
            if (!empty($modifier)) {
                $classes[] = $base_class . '--' . $modifier;
            }
        }
        
        // Ajouter la classe personnalisée
        if (!empty($custom_class)) {
            $classes[] = $custom_class;
        }
        
        return implode(' ', $classes);
    }
}

?>