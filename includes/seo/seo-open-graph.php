<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-open-graph.php
 * Module SEO - Open Graph et Twitter Cards
 * 
 * RESPONSABILITÉ:
 * - Balises Open Graph optimisées pour les jeux
 * - Twitter Cards enrichies
 * - Partage social intelligent
 * - Images optimisées pour réseaux sociaux
 * - Cache des métadonnées sociales
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_SEO_Open_Graph {
    
    /**
     * Durée du cache pour les données Open Graph (1 heure)
     */
    const CACHE_DURATION = 3600;
    
    /**
     * Templates de description sociale
     */
    const SOCIAL_DESC_TEMPLATE = "🎮 Découvrez %s ! %s 🎯 Jeu indépendant référencé !";
    const SOCIAL_DESC_SIMPLE = "🎮 Découvrez %s ! Jeu indépendant sur Sisme Games";
    const SOCIAL_DESC_MAX_LENGTH = 200;
    
    public function __construct() {
        add_action('wp_head', array($this, 'add_open_graph_tags'), 30);
        add_action('wp_head', array($this, 'add_twitter_cards'), 35);
        add_action('wp_head', array($this, 'add_additional_social_tags'), 40);
        
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('edited_term', array($this, 'clear_cache_on_term_edit'), 10, 3);
        add_action('sisme_seo_clear_cache', array($this, 'clear_cache_on_save'));
    }
    
    /**
     * Ajouter les balises Open Graph
     */
    public function add_open_graph_tags() {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return;
        }
        
        $og_data = $this->generate_open_graph_data();
        if ($og_data) {
            $this->render_og_tags($og_data);
        }
    }
    
    /**
     * Ajouter les Twitter Cards
     */
    public function add_twitter_cards() {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return;
        }
        
        $twitter_data = $this->generate_twitter_data();
        if ($twitter_data) {
            $this->render_twitter_tags($twitter_data);
        }
    }
    
    /**
     * Ajouter les balises sociales supplémentaires
     */
    public function add_additional_social_tags() {
        if (!Sisme_SEO_Loader::is_game_page()) {
            return;
        }
        
        $additional_data = $this->generate_additional_social_data();
        if ($additional_data) {
            $this->render_additional_tags($additional_data);
        }
    }
    
    /**
     * Générer les données Open Graph
     */
    private function generate_open_graph_data() {
        global $post;
        
        $cache_key = 'sisme_og_data_' . $post->ID;
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return false;
        }
        
        $og_data = array(
            'type' => 'article',
            'title' => $this->generate_social_title($game_data),
            'description' => $this->generate_social_description($game_data),
            'url' => get_permalink(),
            'site_name' => get_bloginfo('name'),
            'image' => $this->get_optimized_cover_image($game_data),
            'locale' => get_locale(),
            'article_author' => get_author_posts_url(get_post_field('post_author')),
            'article_published_time' => get_post_time('c'),
            'article_modified_time' => get_post_modified_time('c'),
            'article_section' => 'Jeux Indépendants',
            'article_tag' => $this->get_article_tags($game_data)
        );
        
        set_transient($cache_key, $og_data, self::CACHE_DURATION);
        
        return $og_data;
    }
    
    /**
     * Générer le titre social
     */
    private function generate_social_title($game_data) {
        $game_name = $game_data['name'] ?? '';
        $genres = $game_data['genres'] ?? array();
        $platforms = $game_data['platforms'] ?? array();
        
        $parts = array($game_name);
        
        if (!empty($genres)) {
            $main_genre = $genres[0]['name'] ?? '';
            if ($main_genre) {
                $parts[] = $main_genre;
            }
        }
        
        if (!empty($platforms)) {
            $main_platform = $platforms[0]['label'] ?? '';
            if ($main_platform && count($platforms) <= 2) {
                $parts[] = $main_platform;
            }
        }
        
        $parts[] = 'Sisme Games';
        
        $title = implode(' - ', array_filter($parts));
        
        if (strlen($title) > 60) {
            $title = $game_name . ' - Jeu Indé | Sisme Games';
        }
        
        return $title;
    }
    
    /**
     * Générer la description sociale
     */
    private function generate_social_description($game_data) {
        $game_name = $game_data['name'] ?? '';
        $description = $game_data['description'] ?? '';
        
        $desc_excerpt = '';
        if ($description) {
            $description = wp_strip_all_tags($description);
            $desc_excerpt = strlen($description) > 80 
                ? substr($description, 0, 80) . '...' 
                : $description;
        }
        
        if ($desc_excerpt) {
            $social_desc = sprintf(self::SOCIAL_DESC_TEMPLATE, $game_name, $desc_excerpt);
        } else {
            $social_desc = sprintf(self::SOCIAL_DESC_SIMPLE, $game_name);
        }
        
        if (strlen($social_desc) > self::SOCIAL_DESC_MAX_LENGTH) {
            $social_desc = substr($social_desc, 0, self::SOCIAL_DESC_MAX_LENGTH - 3) . '...';
        }
        
        return $social_desc;
    }
    
    /**
     * Obtenir l'image de couverture optimisée
     */
    private function get_optimized_cover_image($game_data) {
        $covers = $game_data['covers'] ?? array();
        
        $image_url = '';
        
        if (!empty($covers['main'])) {
            $image_url = $covers['main'];
        } elseif (!empty($covers['vertical'])) {
            $image_url = $covers['vertical'];
        } else {
            $featured_image_id = get_post_thumbnail_id();
            if ($featured_image_id) {
                $image_url = wp_get_attachment_url($featured_image_id);
            }
        }
        
        return $image_url;
    }
    
    /**
     * Obtenir les tags pour l'article
     */
    private function get_article_tags($game_data) {
        $tags = array();
        
        if (!empty($game_data['genres'])) {
            foreach ($game_data['genres'] as $genre) {
                if (!empty($genre['name'])) {
                    $tags[] = $genre['name'];
                }
            }
        }
        
        if (!empty($game_data['platforms'])) {
            foreach (array_slice($game_data['platforms'], 0, 3) as $platform) {
                if (!empty($platform['label'])) {
                    $tags[] = $platform['label'];
                }
            }
        }
        
        $tags[] = 'Jeu Indépendant';
        $tags[] = 'Indie Game';
        
        return array_unique($tags);
    }
    
    /**
     * Générer les données Twitter
     */
    private function generate_twitter_data() {
        global $post;
        
        $cache_key = 'sisme_twitter_data_' . $post->ID;
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return false;
        }
        
        $image_url = $this->get_optimized_cover_image($game_data);
        $has_trailer = !empty($game_data['trailer_link']);
        
        $twitter_data = array(
            'card' => $has_trailer ? 'player' : 'summary_large_image',
            'site' => '@SismeGames',
            'creator' => '@SismeGames',
            'title' => $this->generate_social_title($game_data),
            'description' => $this->generate_social_description($game_data),
            'image' => $image_url
        );
        
        if ($has_trailer) {
            $twitter_data['player'] = $game_data['trailer_link'];
            $twitter_data['player_width'] = '1280';
            $twitter_data['player_height'] = '720';
        }
        
        set_transient($cache_key, $twitter_data, self::CACHE_DURATION);
        
        return $twitter_data;
    }
    
    /**
     * Générer les données sociales supplémentaires
     */
    private function generate_additional_social_data() {
        return array(
            'theme_color' => '#1a1a1a',
            'msapplication_TileColor' => '#1a1a1a',
            'application_name' => 'Sisme Games'
        );
    }
    
    /**
     * Afficher les balises Open Graph
     */
    private function render_og_tags($og_data) {
        foreach ($og_data as $property => $content) {
            if (empty($content)) {
                continue;
            }
            
            if (is_array($content)) {
                foreach ($content as $item) {
                    echo '<meta property="og:' . esc_attr($property) . '" content="' . esc_attr($item) . '">' . "\n";
                }
            } else {
                echo '<meta property="og:' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
            }
        }
    }
    
    /**
     * Afficher les balises Twitter
     */
    private function render_twitter_tags($twitter_data) {
        foreach ($twitter_data as $name => $content) {
            if (empty($content)) {
                continue;
            }
            
            echo '<meta name="twitter:' . esc_attr($name) . '" content="' . esc_attr($content) . '">' . "\n";
        }
    }
    
    /**
     * Afficher les balises supplémentaires
     */
    private function render_additional_tags($additional_data) {
        foreach ($additional_data as $name => $content) {
            if (empty($content)) {
                continue;
            }
            
            echo '<meta name="' . esc_attr($name) . '" content="' . esc_attr($content) . '">' . "\n";
        }
    }
    
    /**
     * Nettoyer le cache lors de la sauvegarde
     */
    public function clear_cache_on_save($post_id) {
        if (get_post_type($post_id) === 'post') {
            delete_transient('sisme_og_data_' . $post_id);
            delete_transient('sisme_twitter_data_' . $post_id);
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
}