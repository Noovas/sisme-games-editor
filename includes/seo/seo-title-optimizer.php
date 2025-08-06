<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-title-optimizer.php
 * Module SEO - Optimisation des titres de page
 * 
 * RESPONSABILITÉ:
 * - Titres SEO optimisés pour la découverte de jeux indépendants
 * - Gestion des mots-clés dans les titres
 * - Formatage intelligent selon les guidelines SEO
 * - Titres adaptatifs selon le contexte et contenu
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
     * Longueurs optimales pour les titres SEO
     */
    const TITLE_MAX_LENGTH = 60;
    const TITLE_IDEAL_LENGTH = 55;
    
    /**
     * Séparateurs et formats
     */
    const SEPARATOR_PRIMARY = ' - ';
    const SEPARATOR_BRAND = ' | ';
    const BRAND_NAME = 'Sisme Games';
    
    /**
     * Templates de titre selon le contenu disponible
     */
    const TEMPLATE_FULL = '%s - %s (%s) | Jeu Indé';
    const TEMPLATE_GENRE_YEAR = '%s - %s (%s) | Sisme Games';
    const TEMPLATE_GENRE_PLATFORM = '%s - %s | Sisme Games';
    const TEMPLATE_SIMPLE = '%s - Jeu Indépendant | Sisme Games';
    const TEMPLATE_MINIMAL = '%s | Sisme Games';
    
    /**
     * Raccourcis pour genres courants
     */
    private static $genre_shortcuts = array(
        'Role Playing Game' => 'RPG',
        'Role-Playing Game' => 'RPG',
        'First Person Shooter' => 'FPS',
        'Real Time Strategy' => 'RTS',
        'Massively Multiplayer Online' => 'MMO',
        'Turn Based Strategy' => 'TBS',
        'Action Role Playing' => 'Action-RPG',
        'Metroidvania' => 'Metroidvania'
    );
    
    public function __construct() {
        // Hooks WordPress pour l'optimisation des titres
        add_filter('wp_title', array($this, 'optimize_wp_title'), 10, 2);
        add_filter('document_title_parts', array($this, 'optimize_document_title_parts'), 10, 1);
        add_filter('pre_get_document_title', array($this, 'override_document_title'), 10, 1);
        
        // Nettoyage cache
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('edited_term', array($this, 'clear_cache_on_term_edit'), 10, 3);
        add_action('sisme_seo_clear_cache', array($this, 'clear_cache_on_save'));
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
        
        // Reconstruire complètement le titre
        $optimized_title = $this->generate_optimized_title();
        if ($optimized_title) {
            return array('title' => $optimized_title);
        }
        
        return $title_parts;
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
        
        // Sauvegarder en cache
        if ($optimized_title) {
            set_transient($cache_key, $optimized_title, self::CACHE_DURATION);
        }
        
        return $optimized_title;
    }
    
    /**
     * Construire le titre optimisé
     */
    private function build_optimized_title($game_data) {
        $game_name = $game_data['name'] ?? '';
        $genres = $game_data['genres'] ?? array();
        $platforms = $game_data['platforms'] ?? array();
        $release_date = $game_data['release_date'] ?? '';
        
        if (empty($game_name)) {
            return false;
        }
        
        // Extraire l'année
        $year = $this->extract_year_from_date($release_date);
        
        // Formater le genre principal avec raccourcis
        $main_genre = $this->format_main_genre($genres);
        
        // Formater la plateforme principale
        $main_platform = $this->format_main_platform($platforms);
        
        // Choisir le template optimal selon les données disponibles
        $title = $this->select_optimal_template($game_name, $main_genre, $main_platform, $year);
        
        // Vérifier et ajuster la longueur
        if (strlen($title) > self::TITLE_MAX_LENGTH) {
            $title = $this->create_shortened_title($game_name, $main_genre, $year);
        }
        
        return $title;
    }
    
    /**
     * Extraire l'année de la date de sortie
     */
    private function extract_year_from_date($release_date) {
        if (empty($release_date)) {
            return '';
        }
        
        // Extraire l'année si c'est récent (±2 ans)
        if (preg_match('/(\d{4})/', $release_date, $matches)) {
            $year = intval($matches[1]);
            $current_year = intval(date('Y'));
            
            // Inclure l'année si c'est récent ou futur
            if ($year >= ($current_year - 2) && $year <= ($current_year + 1)) {
                return (string)$year;
            }
        }
        
        return '';
    }
    
    /**
     * Formater le genre principal avec raccourcis
     */
    private function format_main_genre($genres) {
        if (empty($genres)) {
            return '';
        }
        
        $main_genre = $genres[0]['name'] ?? '';
        
        // Appliquer les raccourcis si disponibles
        foreach (self::$genre_shortcuts as $full_name => $shortcut) {
            if (stripos($main_genre, $full_name) !== false) {
                return $shortcut;
            }
        }
        
        // Nettoyer le nom du genre
        $main_genre = str_replace(['jeux-', 'Jeux '], '', $main_genre);
        
        return $main_genre;
    }
    
    /**
     * Formater la plateforme principale
     */
    private function format_main_platform($platforms) {
        if (empty($platforms)) {
            return '';
        }
        
        // Priorité aux plateformes principales
        $priority_platforms = array('PC', 'Windows', 'Steam', 'Console');
        
        foreach ($platforms as $platform) {
            $platform_name = $platform['label'] ?? '';
            
            foreach ($priority_platforms as $priority) {
                if (stripos($platform_name, $priority) !== false) {
                    return $platform_name;
                }
            }
        }
        
        // Fallback sur la première plateforme
        return $platforms[0]['label'] ?? '';
    }
    
    /**
     * Sélectionner le template optimal
     */
    private function select_optimal_template($game_name, $main_genre, $main_platform, $year) {
        // Template genre + année (priorité 1)
        if ($main_genre && $year) {
            $title = sprintf(self::TEMPLATE_GENRE_YEAR, $game_name, $main_genre, $year);
            if (strlen($title) <= self::TITLE_MAX_LENGTH) {
                return $title;
            }
        }
        
        // Template genre seul (priorité 2)
        if ($main_genre) {
            $title = sprintf(self::TEMPLATE_GENRE_PLATFORM, $game_name, $main_genre);
            if (strlen($title) <= self::TITLE_MAX_LENGTH) {
                return $title;
            }
        }
        
        // Template simple (priorité 3)
        $title = sprintf(self::TEMPLATE_SIMPLE, $game_name);
        if (strlen($title) <= self::TITLE_MAX_LENGTH) {
            return $title;
        }
        
        // Template minimal
        return sprintf(self::TEMPLATE_MINIMAL, $game_name);
    }
    
    /**
     * Créer un titre raccourci si nécessaire
     */
    private function create_shortened_title($game_name, $main_genre, $year) {
        $max_game_name_length = 30;
        if (strlen($game_name) > $max_game_name_length) {
            $game_name = substr($game_name, 0, $max_game_name_length - 3) . '...';
        }
        
        // Template court avec genre et année si disponibles
        if ($main_genre && $year) {
            return sprintf('%s - %s %s | Sisme', $game_name, $main_genre, $year);
        }
        
        // Template court avec genre seulement
        if ($main_genre) {
            return sprintf('%s - %s | Sisme Games', $game_name, $main_genre);
        }
        
        // Template minimal
        return sprintf('%s | Sisme Games', $game_name);
    }
    
    /**
     * Nettoyer le cache lors de la sauvegarde
     */
    public function clear_cache_on_save($post_id) {
        if (get_post_type($post_id) === 'post') {
            delete_transient('sisme_optimized_title_' . $post_id);
        }
    }
    
    /**
     * Nettoyer le cache lors de l'édition d'un terme
     */
    public function clear_cache_on_term_edit($term_id, $tt_id, $taxonomy) {
        if ($taxonomy === 'post_tag') {
            $posts = get_posts(array(
                'post_type' => 'post',
                'tag__in' => array($term_id),
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            
            foreach ($posts as $post_id) {
                $this->clear_cache_on_save($post_id);
            }
        }
    }
    
    /**
     * Debug : obtenir les variations de titre possibles
     */
    public function debug_title_variations($post_id = null) {
        if (!$post_id) {
            global $post;
            $post_id = $post->ID;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data($post_id);
        if (!$game_data) {
            return array('error' => 'Pas de données de jeu trouvées');
        }
        
        $game_name = $game_data['name'] ?? '';
        $main_genre = $this->format_main_genre($game_data['genres'] ?? array());
        $main_platform = $this->format_main_platform($game_data['platforms'] ?? array());
        $year = $this->extract_year_from_date($game_data['release_date'] ?? '');
        
        $variations = array(
            'current' => $this->build_optimized_title($game_data),
            'full' => sprintf(self::TEMPLATE_FULL, $game_name, $main_genre, $main_platform, $year),
            'genre_year' => sprintf(self::TEMPLATE_GENRE_YEAR, $game_name, $main_genre, $year),
            'genre_platform' => sprintf(self::TEMPLATE_GENRE_PLATFORM, $game_name, $main_genre, $main_platform),
            'simple' => sprintf(self::TEMPLATE_SIMPLE, $game_name),
            'minimal' => sprintf(self::TEMPLATE_MINIMAL, $game_name)
        );
        
        // Ajouter les longueurs
        foreach ($variations as $key => $title) {
            $variations[$key] = array(
                'title' => $title,
                'length' => strlen($title),
                'optimal' => strlen($title) <= self::TITLE_IDEAL_LENGTH,
                'acceptable' => strlen($title) <= self::TITLE_MAX_LENGTH
            );
        }
        
        return $variations;
    }
    
    /**
     * Méthode publique statique pour récupérer le titre optimisé généré
     * Utilisée par l'interface admin pour afficher les titres réellement générés
     */
    public static function get_generated_title($post_id = null) {
        if (!$post_id) {
            global $post;
            $post_id = $post ? $post->ID : 0;
        }
        
        if (!$post_id) {
            return false;
        }
        
        // Temporairement définir le post global si nécessaire
        $original_post = $GLOBALS['post'] ?? null;
        $GLOBALS['post'] = get_post($post_id);
        
        // Créer une instance temporaire pour accéder à la méthode privée
        $instance = new self();
        $title = $instance->generate_optimized_title();
        
        // Restaurer le post original
        $GLOBALS['post'] = $original_post;
        
        return $title;
    }
}