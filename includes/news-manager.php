<?php
/**
 * File: /sisme-games-editor/includes/news-manager.php
 * Plugin: Sisme Games Editor
 * Author: Sisme Games
 * Description: Gestion automatique des pages news de jeux
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_News_Manager {
    
    public function __construct() {
        add_action('init', array($this, 'init_hooks'));
        add_filter('template_include', array($this, 'load_news_template'));
    }
    
    public function init_hooks() {
        // Hook pour intercepter les pages news
        add_action('wp', array($this, 'handle_news_page_display'));
    }
    
    /**
     * Créer automatiquement une page news pour une fiche de jeu
     */
    public function create_news_page($fiche_post_id, $game_data) {
        if (!$fiche_post_id || !$game_data) {
            return false;
        }
        
        $game_title = $game_data['game_title'];
        $game_slug = sanitize_title($game_title);
        $news_slug = $game_slug . '-news';
        
        // Vérifier si la page news existe déjà
        $existing_page = get_page_by_path($news_slug);
        if ($existing_page) {
            return $existing_page->ID;
        }
        
        // Créer la page news
        $news_page_data = array(
            'post_title' => $game_title . ' : News',
            'post_name' => $news_slug,
            'post_content' => $this->generate_news_content($game_title, $fiche_post_id),
            'post_status' => 'publish',
            'post_type' => 'post',
            'post_author' => get_current_user_id()
        );
        
        $news_page_id = wp_insert_post($news_page_data);
        
        if (is_wp_error($news_page_id)) {
            return false;
        }
        
        // Assigner la catégorie "page-news"
        $news_category = $this->get_or_create_news_category();
        if ($news_category) {
            wp_set_post_categories($news_page_id, array($news_category));
        }
        
        // Assigner l'étiquette du jeu
        $main_tag_id = $game_data['main_tag'] ?? 0;
        if ($main_tag_id) {
            wp_set_post_tags($news_page_id, array($main_tag_id));
        }
        
        // Sauvegarder les métadonnées de liaison
        update_post_meta($news_page_id, '_sisme_parent_fiche_id', $fiche_post_id);
        update_post_meta($news_page_id, '_sisme_game_name', $game_title);
        update_post_meta($news_page_id, '_sisme_is_news_page', true);
        
        // Copier l'image mise en avant depuis la fiche
        $featured_image_id = get_post_thumbnail_id($fiche_post_id);
        if ($featured_image_id) {
            set_post_thumbnail($news_page_id, $featured_image_id);
        }
        
        return $news_page_id;
    }
    
    /**
     * Générer le contenu de base de la page news
     */
    private function generate_news_content($game_title, $fiche_post_id) {
        $fiche_url = get_permalink($fiche_post_id);
        
        $content = "Explorez les news de {$game_title}. Patch note, guides, tests et plus encore. ";
        $content .= "On prend le temps de vous informer en temps et en heure sur tout ce qu'il se passe sur le jeu. ";
        $content .= "Détails sur les patchs note après les avoir testés et cela est possible. ";
        $content .= "Annonces sur l'avancement des guides et du test. ";
        $content .= "Mise à jour régulières du contenu en adéquation avec l'évolution du jeu autant que possible.\n\n";
        $content .= "Pour voir la fiche du jeu vous pouvez voir <a href=\"{$fiche_url}\">cet article</a>.";
        
        return $content;
    }
    
    /**
     * Obtenir ou créer la catégorie "page-news"
     */
    private function get_or_create_news_category() {
        $category = get_category_by_slug('page-news');
        
        if (!$category) {
            $category_id = wp_insert_category(array(
                'cat_name' => 'Page News',
                'category_nicename' => 'page-news',
                'category_description' => 'Pages automatiques de news de jeux'
            ));
            
            return is_wp_error($category_id) ? false : $category_id;
        }
        
        return $category->term_id;
    }
    
    /**
     * Gérer l'affichage des pages news
     */
    public function handle_news_page_display() {
        if (!is_single()) {
            return;
        }
        
        global $post;
        
        // Vérifier si c'est une page news
        if (!$this->is_news_page($post)) {
            return;
        }
        
        // Enrichir les données pour le template
        $this->prepare_news_data($post);
    }
    
    /**
     * Vérifier si c'est une page news
     */
    public function is_news_page($post) {
        if (!$post) {
            return false;
        }
        
        // Vérification par métadonnée
        if (get_post_meta($post->ID, '_sisme_is_news_page', true)) {
            return true;
        }
        
        // Vérification par slug pattern
        if (preg_match('/-news$/', $post->post_name)) {
            return true;
        }
        
        // Vérification par catégorie
        $categories = get_the_category($post->ID);
        foreach ($categories as $category) {
            if ($category->slug === 'page-news') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Préparer les données pour l'affichage news
     */
    private function prepare_news_data($post) {
        // Récupérer le nom du jeu depuis le slug ou les métadonnées
        $game_name = get_post_meta($post->ID, '_sisme_game_name', true);
        
        if (!$game_name) {
            // Extraire depuis le slug
            $game_name = str_replace('-news', '', $post->post_name);
            $game_name = str_replace('-', ' ', $game_name);
            $game_name = ucwords($game_name);
        }
        
        // Récupérer la fiche parent
        $parent_fiche_id = get_post_meta($post->ID, '_sisme_parent_fiche_id', true);
        $parent_fiche = $parent_fiche_id ? get_post($parent_fiche_id) : null;
        
        // Récupérer les articles news pour ce jeu
        $news_articles = $this->get_game_news_articles($game_name, $post->ID);
        
        // Stocker dans des globals pour le template
        global $sisme_news_data;
        $sisme_news_data = array(
            'game_name' => $game_name,
            'parent_fiche' => $parent_fiche,
            'news_articles' => $news_articles,
            'current_page' => $post
        );
    }
    
    /**
     * Récupérer tous les articles news d'un jeu
     */
    public function get_game_news_articles($game_name, $exclude_post_id = 0) {
        // Chercher l'étiquette correspondant au nom du jeu
        $tag = get_term_by('name', $game_name, 'post_tag');
        
        if (!$tag) {
            // Essayer avec différentes variations
            $tag = get_term_by('slug', sanitize_title($game_name), 'post_tag');
        }
        
        if (!$tag) {
            return array();
        }
        
        // Récupérer les articles avec cette étiquette
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'tag_id' => $tag->term_id,
            'post__not_in' => array($exclude_post_id),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Exclure les catégories indésirables
        $excluded_categories = array();
        
        // Catégorie "Non classé"
        $uncategorized = get_category_by_slug('uncategorized');
        if ($uncategorized) {
            $excluded_categories[] = $uncategorized->term_id;
        }
        
        // Catégorie "Page News"
        $page_news_cat = get_category_by_slug('page-news');
        if ($page_news_cat) {
            $excluded_categories[] = $page_news_cat->term_id;
        }
        
        if (!empty($excluded_categories)) {
            $args['category__not_in'] = $excluded_categories;
        }
        
        $query = new WP_Query($args);
        
        return $query->posts;
    }
    
    /**
     * Déterminer le type d'article (NEWS/PATCH/TEST)
     */
    public function get_article_type($post) {
        $categories = get_the_category($post->ID);
        
        foreach ($categories as $category) {
            $slug = strtolower($category->slug);
            
            if (strpos($slug, 'patch') !== false) {
                return 'PATCH';
            }
            
            if (strpos($slug, 'test') !== false) {
                return 'TEST';
            }
            
            if (strpos($slug, 'news') !== false || strpos($slug, 'actualit') !== false) {
                return 'NEWS';
            }
        }
        
        // Par défaut
        return 'NEWS';
    }
    
    /**
     * Charger le template pour les pages news
     */
    public function load_news_template($template) {
        if (is_single() && $this->is_news_page(get_post())) {
            $news_template = SISME_GAMES_EDITOR_PLUGIN_DIR . 'templates/single-page-news.php';
            
            if (file_exists($news_template)) {
                return $news_template;
            }
        }
        
        return $template;
    }
    
    /**
     * Mettre à jour une page news existante
     */
    public function update_news_page($fiche_post_id, $new_game_data) {
        // Trouver la page news associée
        $news_pages = get_posts(array(
            'post_type' => 'post',
            'meta_key' => '_sisme_parent_fiche_id',
            'meta_value' => $fiche_post_id,
            'post_status' => 'any',
            'posts_per_page' => 1
        ));
        
        if (empty($news_pages)) {
            // Créer la page si elle n'existe pas
            return $this->create_news_page($fiche_post_id, $new_game_data);
        }
        
        $news_page = $news_pages[0];
        $new_title = $new_game_data['game_title'] . ' : News';
        $new_slug = sanitize_title($new_game_data['game_title']) . '-news';
        
        // Mettre à jour si nécessaire
        if ($news_page->post_title !== $new_title || $news_page->post_name !== $new_slug) {
            wp_update_post(array(
                'ID' => $news_page->ID,
                'post_title' => $new_title,
                'post_name' => $new_slug,
                'post_content' => $this->generate_news_content($new_game_data['game_title'], $fiche_post_id)
            ));
            
            update_post_meta($news_page->ID, '_sisme_game_name', $new_game_data['game_title']);
        }
        
        return $news_page->ID;
    }
}