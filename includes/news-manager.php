<?php
/**
 * File: /sisme-games-editor/includes/news-manager.php
 * Plugin: Sisme Games Editor - VERSION SIMPLE
 * Author: Sisme Games
 * Description: Gestion automatique des pages news - CONTENU UNIQUEMENT
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_News_Manager {
    
    public function __construct() {        
        add_filter('the_content', array($this, 'enrich_news_page_content'), 5);
        add_action('init', array($this, 'init_hooks'));
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
        $existing_page = get_page_by_path($news_slug, OBJECT, 'post');
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
     * Enrichir le contenu des pages news (COMME LES FICHES)
     */
    public function enrich_news_page_content($content) {
        if (is_single()) {
            global $post;
        }
        
        if (!is_single() || !$this->is_news_page()) {
            return $content;
        }
        
        global $post;
        $post_id = $post->ID;
        $game_name = get_post_meta($post_id, '_sisme_game_name', true);
        $parent_fiche_id = get_post_meta($post_id, '_sisme_parent_fiche_id', true);
        if (!$game_name) {
            $game_name = str_replace('-news', '', $post->post_name);
            $game_name = str_replace('-', ' ', $game_name);
            $game_name = ucwords($game_name);
        }
        $enriched_content = '';

        // Section d'accroche SEO optimisée
        $enriched_content .= $this->render_seo_intro($game_name, $parent_fiche_id);
        // Titre de section
        $enriched_content .= '<h2>Dernières actualités et patches pour ' . esc_html($game_name) . '</h2>';

        $news_articles = $this->get_game_news_articles($game_name, $post_id);        
        if (!empty($news_articles)) {
            $enriched_content .= '<div class="sisme-news-grid">';
            
            foreach ($news_articles as $article) {
                $enriched_content .= $this->render_news_card($article, $game_name);
            }
            
            $enriched_content .= '</div>';
        } else {
            $enriched_content .= '<div class="sisme-news-empty">';
            $enriched_content .= '<h3>Actualités en préparation</h3>';
            $enriched_content .= '<p>Les premières actualités pour <strong>' . esc_html($game_name) . '</strong> arriveront bientôt !</p>';
            $enriched_content .= '</div>';
        }
        
        // Lien vers la fiche parent si elle existe
        if ($parent_fiche_id) {
            $fiche_url = get_permalink($parent_fiche_id);
            
            $enriched_content .= '<div class="sisme-fiche-link">';
            $enriched_content .= '<p><strong>💡 Découvrir le jeu :</strong> ';
            $enriched_content .= '<a href="' . esc_url($fiche_url) . '">Voir la fiche complète de ' . esc_html($game_name) . '</a></p>';
            $enriched_content .= '</div>';
        }

        // Liens boutiques
        $enriched_content .= $this->render_store_links($parent_fiche_id);
        
        return $enriched_content;
    }
    
    /**
     * Générer une carte d'article news/patch
     */
    private function render_news_card($article, $game_name) {
        $article_url = get_permalink($article->ID);
        $article_date = get_the_date('j M Y', $article->ID);
        $article_type = $this->get_article_type($article);
        $article_image = get_the_post_thumbnail_url($article->ID, 'medium');
        
        $card_html = '<div class="sisme-news-card sisme-card-' . strtolower($article_type) . '">';
        
        // Image de l'article si elle existe
        if ($article_image) {
            $card_html .= '<div class="sisme-card-image">';
            $card_html .= '<img src="' . esc_url($article_image) . '" alt="' . esc_attr($article->post_title) . '">';
            $card_html .= '</div>';
        }
        
        $card_html .= '<div class="sisme-card-content">';
        
        // Badge du type d'article
        $card_html .= '<div class="sisme-card-meta">';
        $card_html .= '<span class="sisme-article-badge sisme-badge-' . strtolower($article_type) . '">' . esc_html($article_type) . '</span>';
        $card_html .= '<span class="sisme-card-date">' . esc_html($article_date) . '</span>';
        $card_html .= '</div>';
        
        // Titre de l'article
        $card_html .= '<h3 class="sisme-card-title">';
        $card_html .= '<a href="' . esc_url($article_url) . '">' . esc_html($article->post_title) . '</a>';
        $card_html .= '</h3>';
        
        // Extrait de l'article
        if (!empty($article->post_excerpt)) {
            $card_html .= '<p class="sisme-card-excerpt">' . esc_html(wp_trim_words($article->post_excerpt, 25)) . '</p>';
        }
        
        // Nom du jeu concerné
        $card_html .= '<div class="sisme-card-game">🎮 ' . esc_html($game_name) . '</div>';
        
        $card_html .= '</div>'; // .sisme-card-content
        $card_html .= '</div>'; // .sisme-news-card
        
        return $card_html;
    }

    /**
     * Générer l'introduction SEO optimisée
     */
    private function render_seo_intro($game_name, $parent_fiche_id) {
        $intro_html = '<div class="sisme-news-intro">';
        
        // Récupérer les données de la fiche pour enrichir le SEO
        $game_modes = '';
        $platforms = '';
        $categories = '';
        
        if ($parent_fiche_id) {
            $modes = get_post_meta($parent_fiche_id, '_sisme_game_modes', true) ?: array();
            $plat = get_post_meta($parent_fiche_id, '_sisme_platforms', true) ?: array();
            $cats = get_the_category($parent_fiche_id);
            
            if (!empty($modes)) {
                $game_modes = implode(', ', array_map('ucfirst', $modes));
            }
            
            if (!empty($plat)) {
                $platform_names = array('pc' => 'PC', 'mac' => 'Mac', 'xbox' => 'Xbox', 'playstation' => 'PlayStation', 'switch' => 'Nintendo Switch');
                $platform_display = array();
                foreach ($plat as $p) {
                    $platform_display[] = $platform_names[$p] ?? ucfirst($p);
                }
                $platforms = implode(', ', $platform_display);
            }
            
            if (!empty($cats)) {
                $cat_names = array();
                foreach ($cats as $cat) {
                    if (strpos($cat->slug, 'jeux-') === 0) {
                        $cat_names[] = str_replace('jeux-', '', $cat->name);
                    }
                }
                $categories = implode(', ', $cat_names);
            }
        }
        
        $intro_html .= '<p><strong>Découvrez toute l\'actualité de ' . esc_html($game_name) . '</strong>';
        if ($categories) {
            $intro_html .= ', un jeu <strong>' . esc_html(strtolower($categories)) . '</strong>';
        }
        if ($platforms) {
            $intro_html .= ' disponible sur <strong>' . esc_html($platforms) . '</strong>';
        }
        $intro_html .= '. Chez <strong>Sisme Games</strong>, nous suivons de près l\'évolution de ce titre pour vous tenir informés des dernières <strong>mises à jour</strong>, <strong>patches</strong>, et <strong>actualités gaming</strong>.</p>';
        
        $intro_html .= '<p>Notre équipe teste régulièrement les nouvelles fonctionnalités et modifications apportées à <strong>' . esc_html($game_name) . '</strong> pour vous offrir des <strong>guides détaillés</strong> et des <strong>analyses approfondies</strong>. ';
        if ($game_modes) {
            $intro_html .= 'Ce jeu <strong>' . esc_html(strtolower($game_modes)) . '</strong> ';
        }
        $intro_html .= 'mérite votre attention, que vous soyez un joueur occasionnel ou un passionné de jeux indépendants.</p>';
        
        // Lien optimisé vers la fiche
        if ($parent_fiche_id) {
            $fiche_url = get_permalink($parent_fiche_id);
            $fiche_title = get_the_title($parent_fiche_id);
            
            $intro_html .= '<p class="sisme-fiche-link"><strong>💡 Nouveau sur ' . esc_html($game_name) . ' ?</strong> ';
            $intro_html .= 'Consultez d\'abord notre <a href="' . esc_url($fiche_url) . '" ';
            $intro_html .= 'title="Fiche complète de ' . esc_attr($fiche_title) . ' - Gameplay, caractéristiques et notre avis" ';
            $intro_html .= 'alt="Guide complet du jeu ' . esc_attr($game_name) . '">';
            $intro_html .= '<strong>fiche complète du jeu</strong></a> pour découvrir ses principales caractéristiques, son gameplay et nos premières impressions.</p>';
        }
        
        $intro_html .= '</div>';
        
        return $intro_html;
    }

    /**
     * Rendu des liens boutiques
     */
    private function render_store_links($parent_fiche_id) {
        if (!$parent_fiche_id) {
            return '';
        }
        
        // Récupérer les URLs depuis la fiche parente
        $steam_url = get_post_meta($parent_fiche_id, '_sisme_steam_url', true);
        $epic_url = get_post_meta($parent_fiche_id, '_sisme_epic_url', true);
        $gog_url = get_post_meta($parent_fiche_id, '_sisme_gog_url', true);
        
        // Si aucun lien, ne pas afficher la section
        if (empty($steam_url) && empty($epic_url) && empty($gog_url)) {
            return '';
        }
        
        $output = '<div class="sisme-fiche-stores">';
        $output .= '<h2>Où l\'acheter</h2>';
        $output .= '<div class="sisme-store-links">';
        
        if (!empty($steam_url)) {
            $output .= '<a href="' . esc_url($steam_url) . '" target="_blank">';
            $output .= '<img src="https://games.sisme.fr/wp-content/uploads/2025/04/GetItOnSteam.webp" alt="Disponible sur Steam">';
            $output .= '</a>';
        }
        
        if (!empty($epic_url)) {
            $output .= '<a href="' . esc_url($epic_url) . '" target="_blank">';
            $output .= '<img src="https://games.sisme.fr/wp-content/uploads/2025/05/get-on-epic.webp" alt="Disponible sur Epic Games">';
            $output .= '</a>';
        }
        
        if (!empty($gog_url)) {
            $output .= '<a href="' . esc_url($gog_url) . '" target="_blank">';
            $output .= '<img src="https://games.sisme.fr/wp-content/uploads/2025/06/get-on-Gog.webp" alt="Disponible sur GOG">';
            $output .= '</a>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Vérifier si c'est une page news
     */
    private function is_news_page() {
        global $post;
        
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
     * Récupérer tous les articles news d'un jeu
     */
    public function get_game_news_articles($game_name, $exclude_post_id = 0) {
        global $post;
        
        // Récupérer l'étiquette principale depuis la fiche parente
        $parent_fiche_id = get_post_meta($post->ID, '_sisme_parent_fiche_id', true);
        
        if (!$parent_fiche_id) {
            return array();
        }
        
        // Récupérer l'étiquette principale de la fiche
        $main_tag_id = get_post_meta($parent_fiche_id, '_sisme_main_tag', true);
        
        if (!$main_tag_id) {
            return array();
        }
        
        // Récupérer la catégorie "news"
        $news_category = get_category_by_slug('news');
        if (!$news_category) {
            return array();
        }
        
        // Récupérer les articles avec : catégorie "news" + étiquette principale du jeu
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'category__in' => array($news_category->term_id),
            'tag_id' => $main_tag_id,
            'post__not_in' => array($exclude_post_id),
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
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