<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-meta-tags.php
 * Module SEO Meta Tags - Compatible système game-page-creator
 * 
 * RESPONSABILITÉ:
 * - Génération meta description optimisée depuis game-data-formatter
 * - Meta keywords intelligents basés sur les données de jeu
 * - Balises robots appropriées pour les jeux
 * - Canonical URLs et optimisations techniques
 * - Cache performant avec invalidation automatique
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_SEO_Meta_Tags {
    
    /**
     * Durée du cache pour les meta données (1 heure)
     */
    const CACHE_DURATION = 3600;
    
    /**
     * Longueurs optimales
     */
    const META_DESC_MAX_LENGTH = 160;
    const META_DESC_MIN_LENGTH = 120;
    const META_KEYWORDS_MAX = 10;
    
    /**
     * Templates de description
     */
    const DESC_TEMPLATE_FULL = "Découvrez %s, jeu %s (%s). %s Référencé sur Sisme Games.";
    const DESC_TEMPLATE_SIMPLE = "Découvrez %s, jeu indépendant disponible sur %s. %s Référencé sur Sisme Games.";
    const DESC_TEMPLATE_MINIMAL = "Découvrez %s sur Sisme Games. %s Jeu indépendant référencé.";
    
    public function __construct() {
        // Hooks WordPress pour les meta tags
        add_action('wp_head', array($this, 'add_meta_description'), 1);
        add_action('wp_head', array($this, 'add_meta_keywords'), 2);
        add_action('wp_head', array($this, 'add_canonical_url'), 3);
        add_action('wp_head', array($this, 'add_robots_meta'), 4);
        
        // Hook pour nettoyer le cache si le jeu est modifié
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('edited_term', array($this, 'clear_cache_on_term_edit'), 10, 3);
        add_action('sisme_seo_clear_cache', array($this, 'clear_cache_on_save'));
    }
    
    /**
     * Ajouter la meta description
     */
    public function add_meta_description() {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return;
        }
        
        $meta_description = $this->generate_meta_description();
        if ($meta_description) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '">' . "\n";
        }
    }
    
    /**
     * Ajouter les meta keywords
     */
    public function add_meta_keywords() {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return;
        }
        
        $keywords = $this->generate_meta_keywords();
        if ($keywords) {
            echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
        }
    }
    
    /**
     * Ajouter l'URL canonique
     */
    public function add_canonical_url() {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return;
        }
        
        $canonical_url = get_permalink();
        if ($canonical_url) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '">' . "\n";
        }
    }
    
    /**
     * Ajouter les balises robots
     */
    public function add_robots_meta() {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return;
        }
        
        // Robots optimisés pour les pages de jeu
        $robots_content = 'index, follow, max-image-preview:large, max-snippet:-1';
        
        // Ajouter max-video-preview si trailer présent
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if ($game_data && !empty($game_data['trailer_link'])) {
            $robots_content .= ', max-video-preview:-1';
        }
        
        echo '<meta name="robots" content="' . esc_attr($robots_content) . '">' . "\n";
    }
    
    /**
     * Générer la meta description optimisée
     */
    private function generate_meta_description() {
        global $post;
        
        // Vérifier le cache
        $cache_key = 'sisme_meta_desc_' . $post->ID;
        $cached_desc = get_transient($cache_key);
        if ($cached_desc !== false) {
            return $cached_desc;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return false;
        }
        
        $meta_description = $this->build_meta_description($game_data);
        
        // Sauvegarder en cache
        if ($meta_description) {
            set_transient($cache_key, $meta_description, self::CACHE_DURATION);
        }
        
        return $meta_description;
    }
    
    /**
     * Construire la meta description
     */
    private function build_meta_description($game_data) {
        $game_name = $game_data['name'] ?? '';
        $description = $game_data['description'] ?? '';
        $genres = $game_data['genres'] ?? array();
        $platforms = $game_data['platforms'] ?? array();
        $release_date = $game_data['release_date'] ?? '';
        
        // Extraire l'année de la date de sortie
        $year = '';
        if ($release_date && preg_match('/(\d{4})/', $release_date, $matches)) {
            $year = $matches[1];
        }
        
        // Formater les genres (max 2)
        $genre_text = '';
        if (!empty($genres)) {
            $genre_names = array_slice(array_map(function($genre) {
                return $genre['name'] ?? '';
            }, $genres), 0, 2);
            $genre_text = implode('/', $genre_names);
        }
        
        // Formater les plateformes principales
        $platform_text = '';
        if (!empty($platforms)) {
            $platform_names = array_slice(array_map(function($platform) {
                return $platform['label'] ?? '';
            }, $platforms), 0, 3);
            $platform_text = implode(', ', $platform_names);
        }
        
        // Raccourcir la description si trop longue
        $desc_excerpt = $this->create_description_excerpt($description);
        
        // Choisir le template selon les données disponibles
        if ($genre_text && $year && $desc_excerpt) {
            $meta_desc = sprintf(
                self::DESC_TEMPLATE_FULL,
                $game_name,
                $genre_text,
                $year,
                $desc_excerpt
            );
        } elseif ($platform_text && $desc_excerpt) {
            $meta_desc = sprintf(
                self::DESC_TEMPLATE_SIMPLE,
                $game_name,
                $platform_text,
                $desc_excerpt
            );
        } else {
            $meta_desc = sprintf(
                self::DESC_TEMPLATE_MINIMAL,
                $game_name,
                $desc_excerpt ?: 'Jeu indépendant à découvrir.'
            );
        }
        
        // Limiter à la longueur optimale
        if (strlen($meta_desc) > self::META_DESC_MAX_LENGTH) {
            $meta_desc = substr($meta_desc, 0, self::META_DESC_MAX_LENGTH - 3) . '...';
        }
        
        return $meta_desc;
    }
    
    /**
     * Créer un extrait de description
     */
    private function create_description_excerpt($description) {
        if (empty($description)) {
            return '';
        }
        
        // Nettoyer le HTML
        $description = wp_strip_all_tags($description);
        
        // Limiter la longueur pour laisser de la place au reste
        $max_length = 60;
        if (strlen($description) <= $max_length) {
            return $description . ' ';
        }
        
        // Couper au dernier espace complet
        $excerpt = substr($description, 0, $max_length);
        $last_space = strrpos($excerpt, ' ');
        if ($last_space !== false) {
            $excerpt = substr($excerpt, 0, $last_space);
        }
        
        return $excerpt . '... ';
    }
    
    /**
     * Générer les meta keywords
     */
    private function generate_meta_keywords() {
        global $post;
        
        // Vérifier le cache
        $cache_key = 'sisme_meta_keywords_' . $post->ID;
        $cached_keywords = get_transient($cache_key);
        if ($cached_keywords !== false) {
            return $cached_keywords;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return false;
        }
        
        $keywords = $this->build_meta_keywords($game_data);
        
        // Sauvegarder en cache
        if ($keywords) {
            set_transient($cache_key, $keywords, self::CACHE_DURATION);
        }
        
        return $keywords;
    }
    
    /**
     * Construire les meta keywords
     */
    private function build_meta_keywords($game_data) {
        $keywords_array = array();
        
        // Nom du jeu (priorité 1)
        if (!empty($game_data['name'])) {
            $keywords_array[] = $game_data['name'];
        }
        
        // Genres (priorité 2)
        if (!empty($game_data['genres'])) {
            foreach (array_slice($game_data['genres'], 0, 3) as $genre) {
                if (!empty($genre['name'])) {
                    $keywords_array[] = $genre['name'];
                }
            }
        }
        
        // Plateformes principales (priorité 3)
        if (!empty($game_data['platforms'])) {
            foreach (array_slice($game_data['platforms'], 0, 2) as $platform) {
                if (!empty($platform['key'])) {
                    $keywords_array[] = $platform['key'];
                }
            }
        }
        
        // Développeurs (priorité 4)
        if (!empty($game_data['developers'])) {
            foreach (array_slice($game_data['developers'], 0, 2) as $developer) {
                if (!empty($developer['name'])) {
                    $keywords_array[] = $developer['name'];
                }
            }
        }
        
        // Mots-clés génériques (priorité 5)
        $keywords_array[] = 'jeu indépendant';
        $keywords_array[] = 'indie game';
        $keywords_array[] = 'jeux indé';
        $keywords_array[] = 'découvrir jeux indies';
        
        // Nettoyer et limiter
        $keywords_array = array_filter($keywords_array);
        $keywords_array = array_unique($keywords_array);
        $keywords_array = array_slice($keywords_array, 0, self::META_KEYWORDS_MAX);
        
        return implode(', ', $keywords_array);
    }
    
    /**
     * Nettoyer le cache lors de la sauvegarde d'un post
     */
    public function clear_cache_on_save($post_id) {
        if (get_post_type($post_id) === 'post') {
            delete_transient('sisme_meta_desc_' . $post_id);
            delete_transient('sisme_meta_keywords_' . $post_id);
        }
    }
    
    /**
     * Nettoyer le cache lors de l'édition d'un terme
     */
    public function clear_cache_on_term_edit($term_id, $tt_id, $taxonomy) {
        if ($taxonomy === 'post_tag') {
            // Trouver tous les posts qui utilisent ce terme et nettoyer leur cache
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
     * Méthode publique statique pour récupérer la description meta générée
     * Utilisée par l'interface admin pour afficher les meta réellement générées
     */
    public static function get_generated_description($post_id = null) {
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
        $description = $instance->generate_meta_description();
        
        // Restaurer le post original
        $GLOBALS['post'] = $original_post;
        
        return $description;
    }
}