<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-title-optimizer.php
 * Module SEO - Optimisation des titres de page
 * 
 * RESPONSABILITÉ:
 * - Titres SEO optimisés pour les pages de jeu
 * - Gestion des mots-clés dans les titres
 * - Formatage intelligent selon les guidelines SEO
 * - Titres adaptatifs selon le contexte
 * - Cache des titres optimisés
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_SEO_Title_Optimizer {
    
    /**
     * Durée du cache pour les titres (30 minutes)
     */
    const CACHE_DURATION = 1800;
    
    /**
     * Longueur optimale pour les titres SEO
     */
    const TITLE_MAX_LENGTH = 60;
    const TITLE_IDEAL_LENGTH = 55;
    
    /**
     * Séparateurs de titre par contexte
     */
    const SEPARATOR_SEO = ' - ';
    const SEPARATOR_BRAND = ' | ';
    
    public function __construct() {
        // Hooks WordPress pour l'optimisation des titres
        add_filter('wp_title', array($this, 'optimize_wp_title'), 10, 2);
        add_filter('document_title_parts', array($this, 'optimize_document_title_parts'), 10, 1);
        add_filter('pre_get_document_title', array($this, 'override_document_title'), 10, 1);
        
        // Nettoyage cache
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('edited_term', array($this, 'clear_cache_on_term_edit'), 10, 3);
    }
    
    /**
     * Optimiser le titre WordPress legacy (wp_title)
     */
    public function optimize_wp_title($title, $sep) {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return $title;
        }
        
        $optimized_title = $this->generate_optimized_title();
        return $optimized_title ? $optimized_title : $title;
    }
    
    /**
     * Optimiser les parties du titre de document (WordPress 4.4+)
     */
    public function optimize_document_title_parts($title_parts) {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return $title_parts;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return $title_parts;
        }
        
        // Reconstruire toutes les parties du titre
        $optimized_parts = $this->build_title_parts($game_data);
        
        return array_merge($title_parts, $optimized_parts);
    }
    
    /**
     * Override complet du titre si nécessaire
     */
    public function override_document_title($title) {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return $title;
        }
        
        $optimized_title = $this->generate_optimized_title();
        return $optimized_title ? $optimized_title : $title;
    }
    
    /**
     * Générer le titre optimisé principal
     */
    private function generate_optimized_title() {
        global $post;
        
        // Vérifier le cache
        $cache_key = 'sisme_optimized_title_' . $post->ID;
        $cached_title = get_transient($cache_key);
        if ($cached_title !== false) {
            return $cached_title;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return false;
        }
        
        $optimized_title = $this->build_optimized_title($game_data);
        
        // Mettre en cache
        set_transient($cache_key, $optimized_title, self::CACHE_DURATION);
        
        return $optimized_title;
    }
    
    /**
     * Construire le titre optimisé
     */
    private function build_optimized_title($game_data) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        if (empty($game_name)) {
            return false;
        }
        
        // Stratégie de titre selon la longueur du nom
        $base_length = strlen($game_name);
        
        if ($base_length > 40) {
            // Nom très long : format minimal
            return $this->build_minimal_title($game_data);
        } elseif ($base_length > 25) {
            // Nom moyen : format compact
            return $this->build_compact_title($game_data);
        } else {
            // Nom court : format complet
            return $this->build_complete_title($game_data);
        }
    }
    
    /**
     * Format minimal pour jeux aux noms très longs
     */
    private function build_minimal_title($game_data) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME];
        $site_name = get_bloginfo('name');
        
        // Format: "Nom du Jeu | Site"
        $title = $game_name . self::SEPARATOR_BRAND . $site_name;
        
        return $this->ensure_title_length($title);
    }
    
    /**
     * Format compact pour jeux aux noms moyens
     */
    private function build_compact_title($game_data) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME];
        $site_name = get_bloginfo('name');
        
        // Ajouter le genre principal si la place le permet
        $primary_genre = $this->get_primary_genre($game_data);
        
        if ($primary_genre) {
            $title = $game_name . self::SEPARATOR_SEO . $primary_genre . self::SEPARATOR_BRAND . $site_name;
            
            // Vérifier si ça rentre
            if (strlen($title) <= self::TITLE_MAX_LENGTH) {
                return $title;
            }
        }
        
        // Fallback : format minimal
        return $this->build_minimal_title($game_data);
    }
    
    /**
     * Format complet pour jeux aux noms courts
     */
    private function build_complete_title($game_data) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME];
        $site_name = get_bloginfo('name');
        
        // Éléments à ajouter par ordre de priorité
        $elements = array();
        
        // 1. Genres (priorité haute)
        $genres = $this->get_title_genres($game_data, 2);
        if (!empty($genres)) {
            $elements[] = implode(' ', $genres);
        }
        
        // 2. Plateforme principale (si PC/Console)
        $main_platform = $this->get_main_platform($game_data);
        if ($main_platform && $this->is_relevant_platform($main_platform)) {
            $elements[] = $main_platform;
        }
        
        // 3. Année de sortie (si récente)
        $year = $this->get_relevant_year($game_data);
        if ($year) {
            $elements[] = $year;
        }
        
        // 4. Indicateur "indé" si pas déjà présent
        if (!$this->contains_indie_indicator($game_name, $elements)) {
            $elements[] = 'Jeu Indépendant';
        }
        
        // Construire le titre en ajoutant des éléments tant que ça rentre
        $title_base = $game_name;
        $title_suffix = self::SEPARATOR_BRAND . $site_name;
        
        $working_title = $title_base;
        
        foreach ($elements as $element) {
            $test_title = $working_title . self::SEPARATOR_SEO . $element . $title_suffix;
            
            if (strlen($test_title) <= self::TITLE_MAX_LENGTH) {
                $working_title .= self::SEPARATOR_SEO . $element;
            } else {
                break; // Plus de place
            }
        }
        
        $final_title = $working_title . $title_suffix;
        
        return $this->ensure_title_length($final_title);
    }
    
    /**
     * Construire les parties du titre pour document_title_parts
     */
    private function build_title_parts($game_data) {
        $optimized_title = $this->build_optimized_title($game_data);
        
        if (!$optimized_title) {
            return array();
        }
        
        // Séparer le titre optimisé en parties
        $parts = array();
        
        // Détecter les séparateurs et découper
        if (strpos($optimized_title, self::SEPARATOR_BRAND) !== false) {
            $title_parts = explode(self::SEPARATOR_BRAND, $optimized_title);
            $parts['title'] = trim($title_parts[0]);
            if (isset($title_parts[1])) {
                $parts['site'] = trim($title_parts[1]);
            }
        } elseif (strpos($optimized_title, self::SEPARATOR_SEO) !== false) {
            $title_parts = explode(self::SEPARATOR_SEO, $optimized_title);
            $parts['title'] = trim($title_parts[0]);
            if (isset($title_parts[1])) {
                $parts['tagline'] = trim($title_parts[1]);
            }
        } else {
            $parts['title'] = $optimized_title;
        }
        
        return $parts;
    }
    
    /**
     * Obtenir le genre principal pour le titre
     */
    private function get_primary_genre($game_data) {
        $genres = $game_data[Sisme_Utils_Games::KEY_GENRES] ?? array();
        
        if (empty($genres)) {
            return false;
        }
        
        $first_genre = $genres[0];
        return isset($first_genre['name']) ? ucfirst($first_genre['name']) : false;
    }
    
    /**
     * Obtenir les genres optimisés pour le titre
     */
    private function get_title_genres($game_data, $max_count = 2) {
        $genres = $game_data[Sisme_Utils_Games::KEY_GENRES] ?? array();
        $genre_names = array();
        
        foreach ($genres as $genre) {
            if (isset($genre['name']) && !empty($genre['name'])) {
                $genre_name = ucfirst($genre['name']);
                
                // Raccourcir certains genres pour le titre
                $genre_name = $this->shorten_genre_name($genre_name);
                $genre_names[] = $genre_name;
            }
            
            if (count($genre_names) >= $max_count) {
                break;
            }
        }
        
        return $genre_names;
    }
    
    /**
     * Raccourcir les noms de genres pour économiser l'espace
     */
    private function shorten_genre_name($genre_name) {
        $shortcuts = array(
            'Role-Playing' => 'RPG',
            'First-Person Shooter' => 'FPS',
            'Real-Time Strategy' => 'RTS',
            'Massively Multiplayer Online' => 'MMO',
            'Battle Royale' => 'BR'
        );
        
        return $shortcuts[$genre_name] ?? $genre_name;
    }
    
    /**
     * Obtenir la plateforme principale
     */
    private function get_main_platform($game_data) {
        $platforms = $game_data[Sisme_Utils_Games::KEY_PLATFORMS] ?? array();
        
        // Prioriser PC, puis Console, puis Mobile
        $priority_order = array('pc', 'console', 'mobile', 'web');
        
        foreach ($priority_order as $priority_group) {
            foreach ($platforms as $platform_group) {
                if (($platform_group['group'] ?? '') === $priority_group) {
                    return $platform_group['label'] ?? false;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Vérifier si la plateforme est pertinente pour le titre
     */
    private function is_relevant_platform($platform) {
        // Exclure "Web Browser" et autres plateformes trop génériques
        $irrelevant_platforms = array('Web Browser', 'Navigateur');
        
        return !in_array($platform, $irrelevant_platforms);
    }
    
    /**
     * Obtenir l'année si pertinente pour le titre
     */
    private function get_relevant_year($game_data) {
        $release_date = $game_data[Sisme_Utils_Games::KEY_RELEASE_DATE] ?? '';
        
        if (!$release_date) {
            return false;
        }
        
        $year = date('Y', strtotime($release_date));
        $current_year = date('Y');
        
        // Inclure l'année si c'est cette année ou l'année prochaine
        if ($year >= ($current_year - 1) && $year <= ($current_year + 1)) {
            return $year;
        }
        
        return false;
    }
    
    /**
     * Vérifier si "indé" est déjà mentionné
     */
    private function contains_indie_indicator($game_name, $elements = array()) {
        $text_to_check = strtolower($game_name . ' ' . implode(' ', $elements));
        $indie_indicators = array('indépendant', 'indie', 'indé', 'independent');
        
        foreach ($indie_indicators as $indicator) {
            if (strpos($text_to_check, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * S'assurer que le titre respecte la longueur maximale
     */
    private function ensure_title_length($title) {
        if (strlen($title) <= self::TITLE_MAX_LENGTH) {
            return $title;
        }
        
        // Tronquer intelligemment en gardant le nom du jeu et le site
        $parts = explode(self::SEPARATOR_BRAND, $title);
        
        if (count($parts) === 2) {
            $game_part = $parts[0];
            $site_part = $parts[1];
            
            // Calculer l'espace disponible pour la partie jeu
            $available_length = self::TITLE_MAX_LENGTH - strlen(self::SEPARATOR_BRAND . $site_part);
            
            if (strlen($game_part) > $available_length) {
                // Tronquer la partie jeu en gardant le nom de base
                $game_parts = explode(self::SEPARATOR_SEO, $game_part);
                $game_name = $game_parts[0];
                
                if (strlen($game_name) <= $available_length) {
                    return $game_name . self::SEPARATOR_BRAND . $site_part;
                } else {
                    // Le nom lui-même est trop long
                    $truncated_name = substr($game_name, 0, $available_length - 3) . '...';
                    return $truncated_name . self::SEPARATOR_BRAND . $site_part;
                }
            }
        }
        
        // Fallback : tronquer brutalement
        return substr($title, 0, self::TITLE_MAX_LENGTH - 3) . '...';
    }
    
    /**
     * Nettoyer le cache lors de la sauvegarde
     */
    public function clear_cache_on_save($post_id) {
        if (Sisme_SEO_Loader::is_game_page($post_id)) {
            delete_transient('sisme_optimized_title_' . $post_id);
        }
    }
    
    /**
     * Nettoyer le cache lors de modification de terme
     */
    public function clear_cache_on_term_edit($term_id, $tt_id, $taxonomy) {
        if ($taxonomy === 'post_tag') {
            $posts = get_posts(array(
                'tag__in' => array($term_id),
                'post_type' => 'post',
                'meta_query' => array(
                    array(
                        'key' => '_sisme_game_sections',
                        'compare' => 'EXISTS'
                    )
                ),
                'fields' => 'ids',
                'numberposts' => -1
            ));
            
            foreach ($posts as $post_id) {
                delete_transient('sisme_optimized_title_' . $post_id);
            }
        }
    }
    
    /**
     * Analyser un titre pour les métriques SEO
     */
    public function analyze_title($title) {
        $length = strlen($title);
        
        $analysis = array(
            'length' => $length,
            'status' => 'good',
            'recommendations' => array()
        );
        
        // Analyse de la longueur
        if ($length > self::TITLE_MAX_LENGTH) {
            $analysis['status'] = 'warning';
            $analysis['recommendations'][] = 'Titre trop long (>' . self::TITLE_MAX_LENGTH . ' caractères)';
        } elseif ($length < 30) {
            $analysis['status'] = 'warning';
            $analysis['recommendations'][] = 'Titre trop court (<30 caractères)';
        } elseif ($length > self::TITLE_IDEAL_LENGTH) {
            $analysis['recommendations'][] = 'Idéalement, réduire à ' . self::TITLE_IDEAL_LENGTH . ' caractères';
        }
        
        // Vérifier la présence de mots-clés gaming
        $gaming_keywords = array('jeu', 'game', 'indépendant', 'indie');
        $has_gaming_keyword = false;
        
        foreach ($gaming_keywords as $keyword) {
            if (stripos($title, $keyword) !== false) {
                $has_gaming_keyword = true;
                break;
            }
        }
        
        if (!$has_gaming_keyword) {
            $analysis['recommendations'][] = 'Ajouter un mot-clé gaming (jeu, indépendant, etc.)';
        }
        
        return $analysis;
    }
    
    /**
     * Obtenir les statistiques du module
     */
    public function get_stats() {
        global $wpdb;
        
        $cache_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_sisme_optimized_title_%'"
        );
        
        return array(
            'module' => 'Title Optimizer',
            'active_caches' => intval($cache_count),
            'cache_duration' => self::CACHE_DURATION,
            'max_title_length' => self::TITLE_MAX_LENGTH,
            'ideal_title_length' => self::TITLE_IDEAL_LENGTH
        );
    }
    
    /**
     * Obtenir des exemples de titres optimisés
     */
    public function get_title_examples() {
        return array(
            'short_game' => array(
                'input' => 'Celeste',
                'output' => 'Celeste - Platformer Indie 2018 | Sisme Games',
                'strategy' => 'Format complet (nom court)'
            ),
            'medium_game' => array(
                'input' => 'Hollow Knight: Silksong',
                'output' => 'Hollow Knight: Silksong - Metroidvania | Sisme Games',
                'strategy' => 'Format compact (nom moyen)'
            ),
            'long_game' => array(
                'input' => 'The Elder Scrolls V: Skyrim Special Edition',
                'output' => 'The Elder Scrolls V: Skyrim Special Edition | Sisme Games',
                'strategy' => 'Format minimal (nom long)'
            )
        );
    }
}