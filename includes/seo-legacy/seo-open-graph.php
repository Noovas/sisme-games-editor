<?php
/**
 * File: /sisme-games-editor/includes/seo/seo-open-graph.php
 * Module SEO - Open Graph et Twitter Cards
 * 
 * RESPONSABILITÉ:
 * - Balises Open Graph optimisées
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
     * Dimensions optimales pour les images sociales
     */
    const OG_IMAGE_WIDTH = 1200;
    const OG_IMAGE_HEIGHT = 630;
    const TWITTER_IMAGE_WIDTH = 1200;
    const TWITTER_IMAGE_HEIGHT = 675;
    
    public function __construct() {
        // Hooks pour Open Graph
        add_action('wp_head', array($this, 'add_open_graph_tags'), 30);
        add_action('wp_head', array($this, 'add_twitter_cards'), 35);
        add_action('wp_head', array($this, 'add_additional_social_tags'), 40);
        
        // Nettoyage cache
        add_action('save_post', array($this, 'clear_cache_on_save'));
        add_action('edited_term', array($this, 'clear_cache_on_term_edit'), 10, 3);
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
        
        // Vérifier le cache
        $cache_key = 'sisme_og_data_' . $post->ID;
        $cached_data = get_transient($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return false;
        }
        
        $og_data = $this->build_open_graph_data($game_data, $post);
        
        // Mettre en cache
        set_transient($cache_key, $og_data, self::CACHE_DURATION);
        
        return $og_data;
    }
    
    /**
     * Construire les données Open Graph
     */
    private function build_open_graph_data($game_data, $post) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        if (empty($game_name)) {
            return false;
        }
        
        $og_data = array(
            'type' => 'website',
            'locale' => get_locale(),
            'site_name' => get_bloginfo('name'),
            'url' => get_permalink($post->ID),
            'updated_time' => get_the_modified_date('c', $post->ID)
        );
        
        // Titre optimisé pour le partage
        $og_data['title'] = $this->generate_social_title($game_data);
        
        // Description optimisée
        $og_data['description'] = $this->generate_social_description($game_data);
        
        // Image principale
        $image_data = $this->get_optimized_social_image($game_data);
        if ($image_data) {
            $og_data = array_merge($og_data, $image_data);
        }
        
        // Données de jeu spécifiques
        $game_meta = $this->extract_game_metadata($game_data);
        $og_data = array_merge($og_data, $game_meta);
        
        return $og_data;
    }
    
    /**
     * Générer les données Twitter
     */
    private function generate_twitter_data() {
        global $post;
        
        // Utiliser les données OG comme base
        $og_data = $this->generate_open_graph_data();
        if (!$og_data) {
            return false;
        }
        
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        
        $twitter_data = array(
            'card' => $this->determine_twitter_card_type($game_data),
            'title' => $og_data['title'],
            'description' => $this->truncate_for_twitter($og_data['description']),
            'url' => $og_data['url']
        );
        
        // Image pour Twitter (peut être différente d'OG)
        $twitter_image = $this->get_twitter_optimized_image($game_data, $og_data);
        if ($twitter_image) {
            $twitter_data['image'] = $twitter_image['url'];
            $twitter_data['image:alt'] = $twitter_image['alt'];
        }
        
        // Créateur/Site Twitter (si configuré)
        $twitter_handle = get_option('sisme_twitter_handle', '');
        if ($twitter_handle) {
            $twitter_data['site'] = '@' . ltrim($twitter_handle, '@');
            $twitter_data['creator'] = '@' . ltrim($twitter_handle, '@');
        }
        
        return $twitter_data;
    }
    
    /**
     * Générer les données sociales supplémentaires
     */
    private function generate_additional_social_data() {
        $game_data = Sisme_SEO_Loader::get_current_game_data();
        if (!$game_data) {
            return false;
        }
        
        $additional_data = array();
        
        // LinkedIn spécifique
        $additional_data['linkedin'] = array(
            'author' => get_bloginfo('name')
        );
        
        // Pinterest spécifique
        $pinterest_desc = $this->generate_pinterest_description($game_data);
        if ($pinterest_desc) {
            $additional_data['pinterest'] = array(
                'description' => $pinterest_desc
            );
        }
        
        // WhatsApp/Telegram optimisé
        $additional_data['messaging'] = array(
            'title' => $this->generate_messaging_title($game_data)
        );
        
        return $additional_data;
    }
    
    /**
     * Générer un titre optimisé pour le partage social
     */
    private function generate_social_title($game_data) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        $genres = $this->extract_primary_genres($game_data, 2);
        
        // Format: "Nom du Jeu - Genre1 Genre2 | Site"
        $title = $game_name;
        
        if (!empty($genres)) {
            $title .= ' - ' . implode(' ', $genres);
        }
        
        // Ajouter indicateur "jeu indé" si pas déjà dans les genres
        if (!$this->contains_indie_indicator($title)) {
            $title .= ' - Jeu Indépendant';
        }
        
        return $title;
    }
    
    /**
     * Générer une description optimisée pour le partage social
     */
    private function generate_social_description($game_data) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        $description = $game_data[Sisme_Utils_Games::KEY_DESCRIPTION] ?? '';
        $release_date = $game_data[Sisme_Utils_Games::KEY_RELEASE_DATE] ?? '';
        
        // Template attractif pour le partage
        $social_desc = "🎮 Découvrez {$game_name}";
        
        // Ajouter l'année
        if ($release_date) {
            $year = date('Y', strtotime($release_date));
            if ($year && $year !== '1970') {
                $social_desc .= " ({$year})";
            }
        }
        
        $social_desc .= " ! ";
        
        // Ajouter description courte
        if ($description) {
            $clean_desc = $this->clean_description($description);
            $truncated = $this->smart_truncate($clean_desc, 200);
            $social_desc .= $truncated . ' ';
        }
        
        // CTA adapté au partage
        $social_desc .= "Voir le jeu sur Sisme Games !";
        
        return $this->smart_truncate($social_desc, 300);
    }
    
    /**
     * Obtenir l'image optimisée pour les réseaux sociaux
     */
    private function get_optimized_social_image($game_data) {
        $covers = $game_data[Sisme_Utils_Games::KEY_COVERS] ?? array();
        $cover_id = $covers[Sisme_Utils_Games::KEY_COVER_MAIN] ?? '';
        
        if (!$cover_id) {
            return $this->get_fallback_social_image($game_data);
        }
        
        $image_url = wp_get_attachment_image_url($cover_id, 'large');
        if (!$image_url) {
            return $this->get_fallback_social_image($game_data);
        }
        
        $image_meta = wp_get_attachment_metadata($cover_id);
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        
        return array(
            'image' => $image_url,
            'image:url' => $image_url,
            'image:secure_url' => str_replace('http://', 'https://', $image_url),
            'image:width' => $image_meta['width'] ?? self::OG_IMAGE_WIDTH,
            'image:height' => $image_meta['height'] ?? self::OG_IMAGE_HEIGHT,
            'image:alt' => "Cover du jeu {$game_name} - Jeu indépendant à découvrir",
            'image:type' => 'image/jpeg'
        );
    }
    
    /**
     * Obtenir l'image optimisée pour Twitter
     */
    private function get_twitter_optimized_image($game_data, $og_data) {
        // Utiliser la même image qu'OG mais avec métadonnées Twitter
        if (isset($og_data['image'])) {
            return array(
                'url' => $og_data['image'],
                'alt' => $og_data['image:alt'] ?? ''
            );
        }
        
        return false;
    }
    
    /**
     * Image de fallback si pas de cover
     */
    private function get_fallback_social_image($game_data) {
        // Utiliser une image par défaut du site ou générer dynamiquement
        $default_image = get_option('sisme_default_social_image', '');
        
        if ($default_image) {
            $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? 'Jeu';
            return array(
                'image' => $default_image,
                'image:url' => $default_image,
                'image:secure_url' => str_replace('http://', 'https://', $default_image),
                'image:width' => self::OG_IMAGE_WIDTH,
                'image:height' => self::OG_IMAGE_HEIGHT,
                'image:alt' => "Découvrez {$game_name} sur Sisme Games",
                'image:type' => 'image/jpeg'
            );
        }
        
        return false;
    }
    
    /**
     * Extraire les métadonnées de jeu pour OG
     */
    private function extract_game_metadata($game_data) {
        $meta = array();
        
        // Type de contenu spécifique
        $meta['type'] = 'video.game'; // Type OG spécifique aux jeux
        
        // Tags de jeu
        $genres = $this->extract_primary_genres($game_data, 5);
        if (!empty($genres)) {
            $meta['video:tag'] = $genres; // Propriété OG pour les tags
        }
        
        // Développeur
        $developers = $this->extract_developer_names($game_data);
        if (!empty($developers)) {
            $meta['video:director'] = $developers[0]; // Utiliser comme "réalisateur"
        }
        
        // Date de sortie
        $release_date = $game_data[Sisme_Utils_Games::KEY_RELEASE_DATE] ?? '';
        if ($release_date) {
            $meta['video:release_date'] = $release_date;
        }
        
        return $meta;
    }
    
    /**
     * Déterminer le type de carte Twitter
     */
    private function determine_twitter_card_type($game_data) {
        // Utiliser summary_large_image pour les jeux (plus visuel)
        $trailer = $game_data[Sisme_Utils_Games::KEY_TRAILER_LINK] ?? '';
        
        if ($trailer) {
            // Si trailer disponible, utiliser player pour l'intégration vidéo
            return 'player';
        }
        
        return 'summary_large_image';
    }
    
    /**
     * Tronquer pour Twitter (limite différente)
     */
    private function truncate_for_twitter($text) {
        return $this->smart_truncate($text, 200);
    }
    
    /**
     * Générer description pour Pinterest
     */
    private function generate_pinterest_description($game_data) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        $genres = $this->extract_primary_genres($game_data, 3);
        
        $pinterest_desc = "📌 {$game_name}";
        
        if (!empty($genres)) {
            $pinterest_desc .= " - " . implode(', ', $genres);
        }
        
        $pinterest_desc .= " | Jeu indépendant à découvrir ! 🎮 #IndieGame #Gaming";
        
        return $pinterest_desc;
    }
    
    /**
     * Générer titre pour applications de messagerie
     */
    private function generate_messaging_title($game_data) {
        $game_name = $game_data[Sisme_Utils_Games::KEY_NAME] ?? '';
        return "🎮 {$game_name} - Nouveau jeu sur Sisme Games !";
    }
    
    /**
     * Extraire les genres principaux
     */
    private function extract_primary_genres($game_data, $limit = 3) {
        $genres = $game_data[Sisme_Utils_Games::KEY_GENRES] ?? array();
        $genre_names = array();
        
        foreach ($genres as $genre) {
            if (isset($genre['name']) && !empty($genre['name'])) {
                $genre_names[] = ucfirst($genre['name']);
            }
        }
        
        return array_slice($genre_names, 0, $limit);
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
     * Vérifier si le titre contient déjà un indicateur "indé"
     */
    private function contains_indie_indicator($title) {
        $indie_words = array('indépendant', 'indie', 'indé');
        $title_lower = strtolower($title);
        
        foreach ($indie_words as $word) {
            if (strpos($title_lower, $word) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Nettoyer la description
     */
    private function clean_description($description) {
        $clean = strip_tags($description);
        $clean = preg_replace('/[\r\n\t]+/', ' ', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return trim($clean);
    }
    
    /**
     * Troncature intelligente
     */
    private function smart_truncate($text, $max_length) {
        if (strlen($text) <= $max_length) {
            return $text;
        }
        
        $truncated = substr($text, 0, $max_length - 3);
        $last_space = strrpos($truncated, ' ');
        
        if ($last_space !== false && $last_space > ($max_length * 0.7)) {
            $truncated = substr($truncated, 0, $last_space);
        }
        
        return $truncated . '...';
    }
    
    /**
     * Rendu des balises Open Graph
     */
    private function render_og_tags($og_data) {
        foreach ($og_data as $property => $content) {
            if (is_array($content)) {
                foreach ($content as $value) {
                    echo '<meta property="og:' . esc_attr($property) . '" content="' . esc_attr($value) . '">' . "\n";
                }
            } else {
                echo '<meta property="og:' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
            }
        }
    }
    
    /**
     * Rendu des balises Twitter
     */
    private function render_twitter_tags($twitter_data) {
        foreach ($twitter_data as $name => $content) {
            echo '<meta name="twitter:' . esc_attr($name) . '" content="' . esc_attr($content) . '">' . "\n";
        }
    }
    
    /**
     * Rendu des balises sociales supplémentaires
     */
    private function render_additional_tags($additional_data) {
        // LinkedIn
        if (isset($additional_data['linkedin'])) {
            foreach ($additional_data['linkedin'] as $property => $content) {
                echo '<meta property="linkedin:' . esc_attr($property) . '" content="' . esc_attr($content) . '">' . "\n";
            }
        }
        
        // Pinterest
        if (isset($additional_data['pinterest'])) {
            foreach ($additional_data['pinterest'] as $name => $content) {
                echo '<meta name="pinterest:' . esc_attr($name) . '" content="' . esc_attr($content) . '">' . "\n";
            }
        }
    }
    
    /**
     * Nettoyer le cache
     */
    public function clear_cache_on_save($post_id) {
        if (Sisme_SEO_Loader::is_game_page($post_id)) {
            delete_transient('sisme_og_data_' . $post_id);
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
                delete_transient('sisme_og_data_' . $post_id);
            }
        }
    }
    
    /**
     * Obtenir les statistiques du module
     */
    public function get_stats() {
        global $wpdb;
        
        $cache_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_sisme_og_data_%'"
        );
        
        return array(
            'module' => 'Open Graph',
            'active_caches' => intval($cache_count),
            'cache_duration' => self::CACHE_DURATION,
            'og_image_size' => self::OG_IMAGE_WIDTH . 'x' . self::OG_IMAGE_HEIGHT,
            'twitter_image_size' => self::TWITTER_IMAGE_WIDTH . 'x' . self::TWITTER_IMAGE_HEIGHT
        );
    }
}