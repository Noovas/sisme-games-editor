<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-sitemap.php
 * Module SEO - Sitemap XML simple et efficace
 * 
 * RESPONSABILITÉ:
 * - Sitemap XML principal (/sitemap.xml)
 * - Pages de jeu (priorité 0.8)
 * - Pages normales (accueil, contact, etc.)
 * - Cache intelligent avec invalidation
 * - Remplacement de SEO AIOAIO
 */

if (!defined('ABSPATH')) {
    exit;
}



class Sisme_SEO_Sitemap {
    
    /**
     * Durée du cache du sitemap (30 minutes)
     */
    const CACHE_DURATION = 1800;
    
    /**
     * Priorités SEO
     */
    const PRIORITY_HOMEPAGE = '1.0';
    const PRIORITY_GAMES = '0.8';
    const PRIORITY_PAGES = '0.6';
    const PRIORITY_POSTS = '0.5';
    
    /**
     * Fréquences de mise à jour
     */
    const FREQ_HOMEPAGE = 'daily';
    const FREQ_GAMES = 'weekly';
    const FREQ_PAGES = 'monthly';
    const FREQ_POSTS = 'weekly';
    
    public function __construct() {
        // Hooks pour le sitemap
        add_action('init', array($this, 'add_sitemap_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_sitemap_request'));
        
        // Nettoyage cache
        add_action('save_post', array($this, 'clear_sitemap_cache'));
        add_action('created_term', array($this, 'clear_sitemap_cache'));
        add_action('edited_term', array($this, 'clear_sitemap_cache'));
        add_action('deleted_term', array($this, 'clear_sitemap_cache'));

        // Handler sitemap
        add_action('template_redirect', function() {
            $sitemap_type = get_query_var('sisme_sitemap');
            
            if (!$sitemap_type) return;
            
            header('Content-Type: application/xml; charset=UTF-8');
            
            if (class_exists('Sisme_SEO_Sitemap')) {
                $sitemap = new Sisme_SEO_Sitemap();
                $sitemap->handle_sitemap_request();
            }
        }, 1);
        
        // Désactiver le sitemap WordPress natif pour éviter les conflits
        add_filter('wp_sitemaps_enabled', '__return_false');
    }
    
    /**
     * Ajouter les règles de réécriture pour le sitemap
     */
    public function add_sitemap_rewrite_rules() {
        add_rewrite_rule(
            '^sitemap\.xml$',
            'index.php?sisme_sitemap=main',
            'top'
        );
        
        add_rewrite_rule(
            '^sitemap-([^/]+)\.xml$',
            'index.php?sisme_sitemap=$matches[1]',
            'top'
        );
        
        // Ajouter les query vars
        add_filter('query_vars', function($vars) {
            $vars[] = 'sisme_sitemap';
            return $vars;
        });
    }
    
    /**
     * Gérer les requêtes de sitemap
     */
    public function handle_sitemap_request() {
        $sitemap_type = get_query_var('sisme_sitemap');
        
        if (!$sitemap_type) {
            return;
        }
        
        // Headers XML
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
        
        // Mettre en cache
        set_transient($cache_key, $sitemap_xml, self::CACHE_DURATION);
        
        echo $sitemap_xml;
    }
    
    /**
     * Générer le sitemap principal
     */
    private function generate_main_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $base_url = home_url();
        $current_time = current_time('c');
        
        // Sitemap des pages
        $xml .= $this->add_sitemap_entry($base_url . '/sitemap-pages.xml', $current_time);
        
        // Sitemap des jeux (si il y en a)
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
        
        // Mettre en cache
        set_transient($cache_key, $sitemap_xml, self::CACHE_DURATION);
        
        echo $sitemap_xml;
    }
    
    /**
     * Générer le sitemap des pages
     */
    private function generate_pages_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Page d'accueil
        $xml .= $this->add_url_entry(
            home_url(),
            current_time('c'),
            self::FREQ_HOMEPAGE,
            self::PRIORITY_HOMEPAGE
        );
        
        // Pages WordPress
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        foreach ($pages as $page) {
            // Exclure les pages privées ou avec mot de passe
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
        
        // Articles de blog (non-gaming)
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 50, // Limiter pour les performances
            'meta_query' => array(
                array(
                    'key' => '_sisme_game_sections',
                    'compare' => 'NOT EXISTS' // Exclure les fiches de jeu
                )
            )
        ));
        
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
        
        // Mettre en cache
        set_transient($cache_key, $sitemap_xml, self::CACHE_DURATION);
        
        echo $sitemap_xml;
    }
    
    /**
     * Générer le sitemap des jeux
     */
    private function generate_games_sitemap() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Fiches de jeu
        $game_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_sisme_game_sections',
                    'compare' => 'EXISTS' // Seulement les fiches de jeu
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
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
     * Compter les pages de jeu
     */
    private function count_game_pages() {
        $count = wp_count_posts();
        
        // Compter seulement les fiches de jeu
        global $wpdb;
        $game_count = $wpdb->get_var(
            "SELECT COUNT(p.ID) 
             FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
             WHERE p.post_status = 'publish' 
             AND p.post_type = 'post' 
             AND pm.meta_key = '_sisme_game_sections'"
        );
        
        return intval($game_count);
    }
    
    /**
     * Nettoyer le cache du sitemap
     */
    public function clear_sitemap_cache() {
        delete_transient('sisme_sitemap_main');
        delete_transient('sisme_sitemap_pages');
        delete_transient('sisme_sitemap_games');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Sisme SEO] Cache sitemap nettoyé');
        }
    }
    
    /**
     * Forcer la régénération du sitemap
     */
    public function regenerate_sitemap() {
        $this->clear_sitemap_cache();
        
        // Générer les nouveaux sitemaps
        $this->generate_main_sitemap();
        $this->generate_pages_sitemap();
        $this->generate_games_sitemap();
        
        return true;
    }
    
    /**
     * Obtenir les statistiques du sitemap
     */
    public function get_sitemap_stats() {
        $stats = array(
            'total_pages' => 0,
            'game_pages' => 0,
            'normal_pages' => 0,
            'last_generated' => '',
            'cache_status' => 'unknown'
        );
        
        // Compter les pages de jeu
        $stats['game_pages'] = $this->count_game_pages();
        
        // Compter les pages normales
        $normal_pages = get_posts(array(
            'post_type' => array('page', 'post'),
            'post_status' => 'publish',
            'numberposts' => -1,
            'meta_query' => array(
                array(
                    'key' => '_sisme_game_sections',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'fields' => 'ids'
        ));
        
        $stats['normal_pages'] = count($normal_pages);
        $stats['total_pages'] = $stats['game_pages'] + $stats['normal_pages'];
        
        // Statut du cache
        $main_cache = get_transient('sisme_sitemap_main');
        $stats['cache_status'] = $main_cache !== false ? 'active' : 'expired';
        
        // Dernière génération (approximative)
        $stats['last_generated'] = $main_cache !== false ? 
            date('Y-m-d H:i:s', time() - (self::CACHE_DURATION - wp_cache_get_last_changed('transients'))) : 
            'Non généré';
        
        return $stats;
    }
    
    /**
     * Obtenir les URLs du sitemap pour test
     */
    public function get_sitemap_urls() {
        return array(
            'main' => home_url('/sitemap.xml'),
            'pages' => home_url('/sitemap-pages.xml'),
            'games' => home_url('/sitemap-games.xml')
        );
    }
    
    /**
     * Valider que les URLs du sitemap sont accessibles
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