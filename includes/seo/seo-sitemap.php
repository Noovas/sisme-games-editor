<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-sitemap.php
 * Module SEO - Sitemap XML pour référencement de jeux
 * 
 * RESPONSABILITÉ:
 * - Sitemap XML principal (/sitemap.xml)
 * - Sitemap pages de jeu (priorité 0.8)
 * - Sitemap pages normales (priorité 0.6)
 * - Cache performant avec invalidation
 * - URLs de réecriture propres
 * 
 * DÉPENDANCES:
 * - seo-game-detector.php
 * - WordPress Rewrite API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_SEO_Sitemap {
    
    /**
     * Durée du cache pour les sitemaps (30 minutes)
     */
    const CACHE_DURATION = 1800;
    
    /**
     * Priorités pour les différents types de contenu
     */
    const PRIORITY_HOMEPAGE = '1.0';
    const PRIORITY_GAMES = '0.8';
    const PRIORITY_PAGES = '0.6';
    const PRIORITY_POSTS = '0.5';
    
    /**
     * Fréquences de changement
     */
    const FREQ_HOMEPAGE = 'daily';
    const FREQ_GAMES = 'weekly';
    const FREQ_PAGES = 'monthly';
    const FREQ_POSTS = 'weekly';
    
    public function __construct() {
        add_action('init', array($this, 'add_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_sitemap_request'));
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('edited_term', array($this, 'clear_cache_on_term_edit'), 10, 3);
        add_action('sisme_seo_clear_cache', array($this, 'clear_sitemap_cache'));
        add_action('sisme_seo_clear_all_cache', array($this, 'clear_sitemap_cache'));
    }
    
    /**
     * Ajouter les règles de réécriture pour les sitemaps
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^sitemap\.xml$', 'index.php?sisme_sitemap=main', 'top');
        add_rewrite_rule('^sitemap-pages\.xml$', 'index.php?sisme_sitemap=pages', 'top');
        add_rewrite_rule('^sitemap-games\.xml$', 'index.php?sisme_sitemap=games', 'top');
        
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        if (get_option('sisme_sitemap_rules_flushed') !== '1') {
            flush_rewrite_rules();
            update_option('sisme_sitemap_rules_flushed', '1');
        }
    }
    
    /**
     * Ajouter les variables de requête personnalisées
     */
    public function add_query_vars($vars) {
        $vars[] = 'sisme_sitemap';
        return $vars;
    }
    
    /**
     * Gérer les requêtes de sitemap
     */
    public function handle_sitemap_request() {
        $sitemap_type = get_query_var('sisme_sitemap');
        
        if (!$sitemap_type) {
            return;
        }
        
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Robots-Tag: noindex, follow');
        
        switch ($sitemap_type) {
            case 'main':
                $this->output_main_sitemap();
                break;
                
            case 'pages':
                $this->output_pages_sitemap();
                break;
                
            case 'games':
                $this->output_games_sitemap();
                break;
                
            default:
                status_header(404);
                exit;
        }
        
        exit;
    }
    
    /**
     * Sitemap principal (index)
     */
    private function output_main_sitemap() {
        $cache_key = 'sisme_sitemap_main';
        $cached_sitemap = get_transient($cache_key);
        
        if ($cached_sitemap !== false) {
            echo $cached_sitemap;
            return;
        }
        
        $sitemap_xml = $this->generate_main_sitemap();
        
        set_transient($cache_key, $sitemap_xml, self::CACHE_DURATION);
        
        echo $sitemap_xml;
    }
    
    /**
     * Générer le sitemap principal (index)
     */
    private function generate_main_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $base_url = home_url();
        $current_time = current_time('c');
        
        $xml .= $this->add_sitemap_entry($base_url . '/sitemap-pages.xml', $current_time);
        
        $games_count = $this->count_game_pages();
        if ($games_count > 0) {
            $xml .= $this->add_sitemap_entry($base_url . '/sitemap-games.xml', $current_time);
        }
        
        $xml .= '</sitemapindex>';
        
        return $xml;
    }
    
    /**
     * Sitemap des pages normales
     */
    private function output_pages_sitemap() {
        $cache_key = 'sisme_sitemap_pages';
        $cached_sitemap = get_transient($cache_key);
        
        if ($cached_sitemap !== false) {
            echo $cached_sitemap;
            return;
        }
        
        $sitemap_xml = $this->generate_pages_sitemap();
        
        set_transient($cache_key, $sitemap_xml, self::CACHE_DURATION);
        
        echo $sitemap_xml;
    }
    
    /**
     * Générer le sitemap des pages normales
     */
    private function generate_pages_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $xml .= $this->add_url_entry(
            home_url(),
            current_time('c'),
            self::FREQ_HOMEPAGE,
            self::PRIORITY_HOMEPAGE
        );
        
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        foreach ($pages as $page) {
            if ($page->post_status !== 'publish' || !empty($page->post_password)) {
                continue;
            }
            
            $xml .= $this->add_url_entry(
                get_permalink($page->ID),
                get_the_modified_date('c', $page->ID),
                self::FREQ_PAGES,
                self::PRIORITY_PAGES
            );
        }
        
        $posts = $this->get_non_game_posts();
        foreach ($posts as $post) {
            if ($post->post_status !== 'publish' || !empty($post->post_password)) {
                continue;
            }
            
            $xml .= $this->add_url_entry(
                get_permalink($post->ID),
                get_the_modified_date('c', $post->ID),
                self::FREQ_POSTS,
                self::PRIORITY_POSTS
            );
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * Sitemap des pages de jeu
     */
    private function output_games_sitemap() {
        $cache_key = 'sisme_sitemap_games';
        $cached_sitemap = get_transient($cache_key);
        
        if ($cached_sitemap !== false) {
            echo $cached_sitemap;
            return;
        }
        
        $sitemap_xml = $this->generate_games_sitemap();
        
        set_transient($cache_key, $sitemap_xml, self::CACHE_DURATION);
        
        echo $sitemap_xml;
    }
    
    /**
     * Générer le sitemap des jeux
     */
    private function generate_games_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $game_posts = $this->get_game_posts();
        
        foreach ($game_posts as $post) {
            if ($post->post_status !== 'publish' || !empty($post->post_password)) {
                continue;
            }
            
            $xml .= $this->add_url_entry(
                get_permalink($post->ID),
                get_the_modified_date('c', $post->ID),
                self::FREQ_GAMES,
                self::PRIORITY_GAMES
            );
        }
        
        $xml .= '</urlset>';
        
        return $xml;
    }
    
    /**
     * Récupérer tous les posts de jeu via le nouveau système
     */
    private function get_game_posts() {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $game_posts = array();
        foreach ($posts as $post) {
            if (Sisme_SEO_Game_Detector::is_game_page($post->ID)) {
                $game_posts[] = $post;
            }
        }
        
        return $game_posts;
    }
    
    /**
     * Récupérer les posts non-gaming (articles de blog)
     */
    private function get_non_game_posts() {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 50,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $non_game_posts = array();
        foreach ($posts as $post) {
            if (!Sisme_SEO_Game_Detector::is_game_page($post->ID)) {
                $non_game_posts[] = $post;
            }
        }
        
        return $non_game_posts;
    }
    
    /**
     * Compter les pages de jeu via le nouveau système
     */
    private function count_game_pages() {
        $cache_key = 'sisme_game_pages_count';
        $cached_count = get_transient($cache_key);
        
        if ($cached_count !== false) {
            return (int)$cached_count;
        }
        
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ));
        
        $game_count = 0;
        foreach ($posts as $post_id) {
            if (Sisme_SEO_Game_Detector::is_game_page($post_id)) {
                $game_count++;
            }
        }
        
        set_transient($cache_key, $game_count, 3600);
        
        return $game_count;
    }
    
    /**
     * Ajouter une entrée de sitemap (pour l'index)
     */
    private function add_sitemap_entry($loc, $lastmod) {
        $xml = "\t<sitemap>\n";
        $xml .= "\t\t<loc>" . esc_url($loc) . "</loc>\n";
        $xml .= "\t\t<lastmod>" . esc_xml($lastmod) . "</lastmod>\n";
        $xml .= "\t</sitemap>\n";
        
        return $xml;
    }
    
    /**
     * Ajouter une entrée d'URL
     */
    private function add_url_entry($loc, $lastmod, $changefreq, $priority) {
        $xml = "\t<url>\n";
        $xml .= "\t\t<loc>" . esc_url($loc) . "</loc>\n";
        $xml .= "\t\t<lastmod>" . esc_xml($lastmod) . "</lastmod>\n";
        $xml .= "\t\t<changefreq>" . esc_xml($changefreq) . "</changefreq>\n";
        $xml .= "\t\t<priority>" . esc_xml($priority) . "</priority>\n";
        $xml .= "\t</url>\n";
        
        return $xml;
    }
    
    /**
     * Nettoyer le cache lors de la sauvegarde
     */
    public function clear_cache_on_save($post_id) {
        if (get_post_type($post_id) === 'post') {
            $this->clear_sitemap_cache();
        }
    }
    
    /**
     * Nettoyer le cache lors de l'édition d'un terme
     */
    public function clear_cache_on_term_edit($term_id, $tt_id, $taxonomy) {
        if ($taxonomy === 'post_tag') {
            $this->clear_sitemap_cache();
        }
    }
    
    /**
     * Nettoyer le cache du sitemap
     */
    public function clear_sitemap_cache() {
        delete_transient('sisme_sitemap_main');
        delete_transient('sisme_sitemap_pages');
        delete_transient('sisme_sitemap_games');
        delete_transient('sisme_game_pages_count');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme SEO] Cache sitemap nettoyé');
        }
    }
    
    /**
     * Forcer la régénération du sitemap
     */
    public function regenerate_sitemap() {
        $this->clear_sitemap_cache();
        
        $this->generate_main_sitemap();
        $this->generate_pages_sitemap();
        $this->generate_games_sitemap();
        
        return true;
    }
    
    /**
     * Obtenir les statistiques du sitemap
     */
    public function get_sitemap_stats() {
        $game_pages = $this->count_game_pages();
        $normal_pages_query = get_posts(array(
            'post_type' => array('page', 'post'),
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ));
        
        $normal_pages = 0;
        foreach ($normal_pages_query as $post_id) {
            if (!Sisme_SEO_Game_Detector::is_game_page($post_id)) {
                $normal_pages++;
            }
        }
        
        $main_cache = get_transient('sisme_sitemap_main');
        
        return array(
            'total_pages' => $game_pages + $normal_pages + 1,
            'game_pages' => $game_pages,
            'normal_pages' => $normal_pages,
            'cache_status' => $main_cache !== false ? 'active' : 'expired',
            'last_generated' => $main_cache !== false ? 'Moins de 30min' : 'Expiré'
        );
    }
    
    /**
     * Obtenir les URLs du sitemap
     */
    public function get_sitemap_urls() {
        return array(
            'main' => home_url('/sitemap.xml'),
            'pages' => home_url('/sitemap-pages.xml'),
            'games' => home_url('/sitemap-games.xml')
        );
    }
    
    /**
     * Valider l'accès aux sitemaps
     */
    public function validate_sitemap_access() {
        $urls = $this->get_sitemap_urls();
        $results = array();
        
        foreach ($urls as $type => $url) {
            $response = wp_remote_get($url, array('timeout' => 10));
            
            if (is_wp_error($response)) {
                $results[$type] = array(
                    'status' => 'error',
                    'message' => $response->get_error_message()
                );
            } else {
                $status_code = wp_remote_retrieve_response_code($response);
                $content_type = wp_remote_retrieve_header($response, 'content-type');
                
                $results[$type] = array(
                    'status' => $status_code === 200 ? 'success' : 'error',
                    'status_code' => $status_code,
                    'content_type' => $content_type,
                    'is_xml' => strpos($content_type, 'xml') !== false
                );
            }
        }
        
        return $results;
    }
}