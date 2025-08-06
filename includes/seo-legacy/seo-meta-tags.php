<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-meta-tags.php
 * Module SEO - Meta Tags et descriptions automatiques
 * 
 * RESPONSABILITÉ:
 * - Génération meta description optimisée
 * - Meta keywords intelligents
 * - Balises robots appropriées
 * - Canonical URLs
 * - Cache des meta données
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
     * Longueur optimale meta description
     */
    const META_DESC_MAX_LENGTH = 160;
    
    public function __construct() {
        // Hooks WordPress pour les meta tags
        add_action('wp_head', array($this, 'add_meta_description'), 1);
        add_action('wp_head', array($this, 'add_meta_keywords'), 2);
        add_action('wp_head', array($this, 'add_canonical_url'), 3);
        add_action('wp_head', array($this, 'add_robots_meta'), 4);
        
        // Hook pour nettoyer le cache si le jeu est modifié
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('edited_term', array($this, 'clear_cache_on_term_edit'), 10, 3);
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
        if ($game_data && !empty($game_data[Sisme_Utils_Games::KEY_TRAILER_LINK])) {
            $robots_content .= ', max-video-preview:-1';
        }
        
        echo '<meta name="robots" content="' . esc_attr($robots_content) . '">' . "\n";
    }
    
    /**
     * Générer une meta description optimisée
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
            return '';
        }
        
        $meta_desc = $this->build_meta_description($game_data);
        
        // Mettre en cache
        set_transient($cache_key, $meta_desc, self::CACHE_DURATION);
        
        return $meta_desc;
    }
    
    /**
     * Construire la meta description
     */
    private function build_meta_description($game_data) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        $description = $game_data[Sisme_Utils_Games::KEY_DESCRIPTION] ?? '';
        $release_date = $game_data[Sisme_Utils_Games::KEY_RELEASE_DATE] ?? '';
        
        if (empty($game_name)) {
            return '';
        }
        
        // Template de base
        $meta_desc = "Découvrez {$game_name}";
        
        // Ajouter les genres
        $genres = $this->extract_genres_names($game_data);
        if (!empty($genres)) {
            $genre_text = strtolower(implode(' ', array_slice($genres, 0, 2)));
            $meta_desc .= ", jeu {$genre_text}";
        }
        
        // Ajouter l'année de sortie
        if ($release_date) {
            $year = date('Y', strtotime($release_date));
            if ($year && $year !== '1970') { // Éviter les dates invalides
                $meta_desc .= " ({$year})";
            }
        }
        
        $meta_desc .= ". ";
        
        // Ajouter description tronquée
        if ($description) {
            $clean_desc = $this->clean_description($description);
            $remaining_length = self::META_DESC_MAX_LENGTH - strlen($meta_desc) - 25; // 25 chars pour la fin
            
            if ($remaining_length > 20) {
                $truncated = $this->smart_truncate($clean_desc, $remaining_length);
                $meta_desc .= $truncated . ' ';
            }
        }
        
        // Ajouter CTA final
        $meta_desc .= "Voir ce jeu indépendant sur Sisme Games.";
        
        // Limiter à la longueur maximale
        return $this->smart_truncate($meta_desc, self::META_DESC_MAX_LENGTH);
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
            return '';
        }
        
        $keywords = $this->build_meta_keywords($game_data);
        
        // Mettre en cache
        set_transient($cache_key, $keywords, self::CACHE_DURATION);
        
        return $keywords;
    }
    
    /**
     * Construire les meta keywords
     */
    private function build_meta_keywords($game_data) {
        $keywords = array();
        
        // Nom du jeu (priorité 1)
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        if ($game_name) {
            $keywords[] = $game_name;
            
            // Variations du nom (sans caractères spéciaux)
            $clean_name = preg_replace('/[^\w\s]/', '', $game_name);
            if ($clean_name !== $game_name) {
                $keywords[] = $clean_name;
            }
        }
        
        // Genres (priorité 2)
        $genres = $this->extract_genres_names($game_data);
        $keywords = array_merge($keywords, array_slice($genres, 0, 3));
        
        // Plateformes (priorité 3)
        $platforms = $this->extract_platform_names($game_data);
        $keywords = array_merge($keywords, array_slice($platforms, 0, 3));
        
        // Modes de jeu (priorité 4)
        $modes = $this->extract_mode_names($game_data);
        $keywords = array_merge($keywords, array_slice($modes, 0, 2));
        
        // Mots-clés génériques gaming (priorité 5)
        $generic_keywords = array(
            'jeu indépendant',
            'indie game',
            'jeu vidéo',
            'télécharger jeu',
            'gaming'
        );
        $keywords = array_merge($keywords, $generic_keywords);
        
        // Développeur (si connu)
        $developers = $this->extract_developer_names($game_data);
        if (!empty($developers)) {
            $keywords = array_merge($keywords, array_slice($developers, 0, 2));
        }
        
        // Nettoyer et limiter
        $keywords = array_unique(array_filter($keywords));
        $keywords = array_slice($keywords, 0, 15); // Max 15 keywords
        
        return implode(', ', $keywords);
    }
    
    /**
     * Extraire les noms de genres
     */
    private function extract_genres_names($game_data) {
        $genres = $game_data[Sisme_Utils_Games::KEY_GENRES] ?? array();
        $genre_names = array();
        
        foreach ($genres as $genre) {
            if (isset($genre['name']) && !empty($genre['name'])) {
                $genre_names[] = ucfirst($genre['name']);
            }
        }
        
        return $genre_names;
    }
    
    /**
     * Extraire les noms de plateformes
     */
    private function extract_platform_names($game_data) {
        $platforms = $game_data[Sisme_Utils_Games::KEY_PLATFORMS] ?? array();
        $platform_names = array();
        
        foreach ($platforms as $platform_group) {
            if (isset($platform_group['label']) && !empty($platform_group['label'])) {
                $platform_names[] = $platform_group['label'];
            }
        }
        
        return array_unique($platform_names);
    }
    
    /**
     * Extraire les noms de modes
     */
    private function extract_mode_names($game_data) {
        $modes = $game_data[Sisme_Utils_Games::KEY_MODES] ?? array();
        $mode_names = array();
        
        foreach ($modes as $mode) {
            if (isset($mode['label']) && !empty($mode['label'])) {
                $mode_names[] = $mode['label'];
            }
        }
        
        return $mode_names;
    }
    
    /**
     * Extraire les noms de développeurs
     */
    private function extract_developer_names($game_data) {
        $developers = $game_data[Sisme_Utils_Games::KEY_DEVELOPERS] ?? array();
        $dev_names = array();
        
        foreach ($developers as $dev_id) {
            $developer = get_category($dev_id);
            if ($developer && !is_wp_error($developer)) {
                $dev_names[] = $developer->name;
            }
        }
        
        return $dev_names;
    }
    
    /**
     * Nettoyer la description pour meta
     */
    private function clean_description($description) {
        // Supprimer les balises HTML
        $clean = strip_tags($description);
        
        // Supprimer les caractères de contrôle et espaces multiples
        $clean = preg_replace('/[\r\n\t]+/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        
        // Supprimer les caractères spéciaux problématiques
        $clean = str_replace(array('"', "'", '&'), array('', '', 'et'), $clean);
        
        return trim($clean);
    }
    
    /**
     * Troncature intelligente qui préserve les mots
     */
    private function smart_truncate($text, $max_length) {
        if (strlen($text) <= $max_length) {
            return $text;
        }
        
        $truncated = substr($text, 0, $max_length - 3); // -3 pour les "..."
        $last_space = strrpos($truncated, ' ');
        
        if ($last_space !== false && $last_space > ($max_length * 0.7)) {
            $truncated = substr($truncated, 0, $last_space);
        }
        
        return $truncated . '...';
    }
    
    /**
     * Nettoyer le cache lors de la sauvegarde d'un post
     */
    public function clear_cache_on_save($post_id) {
        if (Sisme_SEO_Loader::is_game_page($post_id)) {
            delete_transient('sisme_meta_desc_' . $post_id);
            delete_transient('sisme_meta_keywords_' . $post_id);
        }
    }
    
    /**
     * Nettoyer le cache lors de la modification d'un terme (jeu)
     */
    public function clear_cache_on_term_edit($term_id, $tt_id, $taxonomy) {
        if ($taxonomy === 'post_tag') {
            // Trouver tous les posts associés à ce tag et nettoyer leur cache
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
                delete_transient('sisme_meta_desc_' . $post_id);
                delete_transient('sisme_meta_keywords_' . $post_id);
            }
        }
    }
    
    /**
     * Obtenir les statistiques du module
     */
    public function get_stats() {
        global $wpdb;
        
        // Compter les caches actifs
        $cache_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_sisme_meta_%'"
        );
        
        return array(
            'module' => 'Meta Tags',
            'active_caches' => intval($cache_count),
            'cache_duration' => self::CACHE_DURATION,
            'max_description_length' => self::META_DESC_MAX_LENGTH
        );
    }
}