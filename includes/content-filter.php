<?php
/**
 * File: /sisme-games-editor/includes/content-filter.php
 * Enrichissement du contenu des fiches de jeu dans les articles existants
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Content_Filter {
    
    public function __construct() {
        add_filter('the_content', array($this, 'enrich_game_fiche_content'));
    }
    
    /**
     * Enrichir le contenu des fiches de jeu
     */
    public function enrich_game_fiche_content($content) {
        // Vérifier si on est sur un article single et que c'est une fiche de jeu
        if (!is_single() || !$this->is_game_fiche()) {
            return $content;
        }
        
        global $post;
        $post_id = $post->ID;
        
        // Récupérer les métadonnées
        $game_modes = get_post_meta($post_id, '_sisme_game_modes', true) ?: array();
        $platforms = get_post_meta($post_id, '_sisme_platforms', true) ?: array();
        $release_date = get_post_meta($post_id, '_sisme_release_date', true);
        $developers = get_post_meta($post_id, '_sisme_developers', true) ?: array();
        $editors = get_post_meta($post_id, '_sisme_editors', true) ?: array();
        $trailer_url = get_post_meta($post_id, '_sisme_trailer_url', true);
        $steam_url = get_post_meta($post_id, '_sisme_steam_url', true);
        $epic_url = get_post_meta($post_id, '_sisme_epic_url', true);
        
        // Construire le contenu enrichi
        $enriched_content = '';
        
        // Description mise en avant
        if (has_excerpt()) {
            $enriched_content .= '<div class="sisme-fiche-description">';
            $enriched_content .= wpautop(get_the_excerpt());
            $enriched_content .= '</div>';
        }
        
        // Vidéo trailer
        if (!empty($trailer_url)) {
            $enriched_content .= $this->render_trailer_section($trailer_url);
        }
        
        // Informations du jeu
        $enriched_content .= $this->render_game_info_section($game_modes, $platforms, $release_date, $developers, $editors);
        
        // Blocs test/news par défaut
        $enriched_content .= $this->render_default_blocks();
        
        // Contenu original de l'article (sections étape 2)
        $enriched_content .= '<div class="sisme-fiche-content">' . $content . '</div>';
        
        // Liens boutiques
        if (!empty($steam_url) || !empty($epic_url)) {
            $enriched_content .= $this->render_store_links($steam_url, $epic_url);
        }
        
        return $enriched_content;
    }
    
    /**
     * Vérifier si l'article actuel est une fiche de jeu
     */
    private function is_game_fiche() {
        global $post;
        
        if (!$post) {
            return false;
        }
        
        // Vérifier si l'article a des métadonnées de jeu
        $game_modes = get_post_meta($post->ID, '_sisme_game_modes', true);
        return !empty($game_modes);
    }
    
    /**
     * Rendu de la section trailer
     */
    private function render_trailer_section($trailer_url) {
        $output = '<div class="sisme-fiche-trailer">';
        $output .= '<h3>Trailer</h3>';
        
        // Convertir l'URL YouTube en embed
        $video_id = '';
        if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $trailer_url, $matches)) {
            $video_id = $matches[1];
        } elseif (preg_match('/youtu\.be\/([^?]+)/', $trailer_url, $matches)) {
            $video_id = $matches[1];
        }
        
        if ($video_id) {
            $output .= '<div class="sisme-video-container">';
            $output .= '<iframe src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></iframe>';
            $output .= '</div>';
        } else {
            $output .= '<p><a href="' . esc_url($trailer_url) . '" target="_blank">Voir le trailer</a></p>';
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Rendu de la section informations
     */
    private function render_game_info_section($game_modes, $platforms, $release_date, $developers, $editors) {
        $output = '<div class="sisme-fiche-informations">';
        $output .= '<h3>Informations</h3>';
        $output .= '<ul>';
        
        // Genres (catégories)
        $categories = get_the_category();
        $game_categories = array();
        foreach ($categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0) {
                $game_categories[] = $category;
            }
        }
        
        if (!empty($game_categories)) {
            $output .= '<li><strong>Genre</strong> : ';
            $genre_links = array();
            foreach ($game_categories as $category) {
                $genre_links[] = '<a href="' . get_category_link($category->term_id) . '">' . 
                               esc_html(str_replace('jeux-', '', $category->name)) . '</a>';
            }
            $output .= implode(', ', $genre_links);
            $output .= '</li>';
        }
        
        // Mode de jeu avec liens vers les catégories correspondantes
        if (!empty($game_modes)) {
            $mode_links = array();
            foreach ($game_modes as $mode) {
                // Capitaliser le mode
                $mode_capitalized = ucfirst($mode);
                
                // Chercher la catégorie correspondante (jeux-[mode])
                $category_slug = 'jeux-' . strtolower($mode);
                $category = get_category_by_slug($category_slug);
                
                if ($category) {
                    // Si la catégorie existe, créer un lien
                    $mode_links[] = '<a href="' . get_category_link($category->term_id) . '">' . esc_html($mode_capitalized) . '</a>';
                } else {
                    // Sinon, juste afficher le texte
                    $mode_links[] = esc_html($mode_capitalized);
                }
            }
            $output .= '<li><strong>Mode de jeu</strong> : ' . implode(', ', $mode_links) . '</li>';
        }
        
        // Date de sortie
        $output .= '<li><strong>Date de sortie</strong> : ' . $this->format_french_date($release_date) . '</li>';
        
        // Plateformes
        if (!empty($platforms)) {
            $platform_names = array(
                'pc' => 'PC',
                'mac' => 'Mac',
                'xbox' => 'Xbox',
                'playstation' => 'PlayStation',
                'switch' => 'Nintendo Switch'
            );
            $platform_display = array();
            foreach ($platforms as $platform) {
                $platform_display[] = $platform_names[$platform] ?? ucfirst($platform);
            }
            $output .= '<li><strong>Plateformes</strong> : ' . esc_html(implode(', ', $platform_display)) . '</li>';
        }
        
        // Développeurs
        if (!empty($developers)) {
            $output .= '<li><strong>Développeur(s)</strong> : ' . $this->format_companies($developers) . '</li>';
        }
        
        // Éditeurs
        if (!empty($editors)) {
            $output .= '<li><strong>Éditeur(s)</strong> : ' . $this->format_companies($editors) . '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Rendu des blocs par défaut avec SEO amélioré
     */
    private function render_default_blocks() {
        global $post;
        $game_title = get_the_title();
        $game_slug = $post->post_name;
        
        // Récupérer les catégories pour les liens
        $categories = get_the_category();
        $main_category = '';
        foreach ($categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0) {
                $main_category = str_replace('jeux-', '', $category->slug);
                break;
            }
        }
        
        // URLs optimisées SEO (à adapter selon votre structure)
        $test_url = home_url("/test-{$game_slug}/");
        $news_url = home_url("/actualites-{$game_slug}/");
        
        $output = '<div class="sisme-fiche-blocks">';
        
        // Titre de la section
        $output .= '<h3 class="sisme-blocks-title">Test & Actualités</h3>';
        
        $output .= '<div class="sisme-blocks-grid">';
        
        // Bloc TEST avec contenu SEO riche et background image
        $output .= '<a href="' . esc_url($test_url) . '" class="sisme-block-link sisme-test-block" ';
        $output .= 'itemscope itemtype="https://schema.org/Review" ';
        $output .= 'title="Test complet et avis détaillé de ' . esc_attr($game_title) . ' - Gameplay, graphismes, verdict">';
        
        // Background image
        $output .= '<div class="sisme-block-bg" style="background-image: url(\'https://games.sisme.fr/wp-content/uploads/2025/06/Sisme-Games-default-test.webp\');"></div>';
        
        $output .= '<div class="sisme-block-content">';
        $output .= '<div class="sisme-block-icon">🎯</div>';
        $output .= '<h4 class="sisme-block-title" itemprop="name">Test de ' . esc_html($game_title) . '</h4>';
        $output .= '<p class="sisme-block-description" itemprop="description">';
        $output .= 'Découvrez notre test complet avec analyse détaillée du gameplay, des graphismes, ';
        $output .= 'de la bande sonore et notre verdict final sur ce jeu ' . esc_html($main_category ?: 'indépendant') . '.';
        $output .= '</p>';
        $output .= '<div class="sisme-block-meta">';
        $output .= '<span class="sisme-block-tag">Test Complet</span>';
        $output .= '<span class="sisme-block-tag">Avis Gaming</span>';
        $output .= '<span class="sisme-block-tag">Verdict</span>'; // SUPPRIMÉ "Note &"
        $output .= '</div>';
        $output .= '<div class="sisme-block-cta">Lire le test →</div>';
        $output .= '</div>';
        
        $output .= '<meta itemprop="author" content="Sisme Games">';
        $output .= '<meta itemprop="datePublished" content="' . get_the_date('c') . '">';
        $output .= '<meta itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">';
        $output .= '<meta itemprop="ratingValue" content="4.5">';
        $output .= '<meta itemprop="bestRating" content="5">';
        $output .= '</a>';
        
        // Bloc NEWS avec contenu SEO riche et background image
        $output .= '<a href="' . esc_url($news_url) . '" class="sisme-block-link sisme-news-block" ';
        $output .= 'itemscope itemtype="https://schema.org/NewsArticle" ';
        $output .= 'title="Actualités, mises à jour et news de ' . esc_attr($game_title) . ' - Patches, DLC, événements">';
        
        // Background image
        $output .= '<div class="sisme-block-bg" style="background-image: url(\'https://games.sisme.fr/wp-content/uploads/2025/06/Sisme-Games-default-news.webp\');"></div>';
        
        $output .= '<div class="sisme-block-content">';
        $output .= '<div class="sisme-block-icon">📰</div>';
        $output .= '<h4 class="sisme-block-title" itemprop="headline">News ' . esc_html($game_title) . '</h4>';
        $output .= '<p class="sisme-block-description" itemprop="description">';
        $output .= 'Suivez toute l\'actualité : patches, mises à jour, DLC, événements spéciaux, ';
        $output .= 'annonces officielles et dernières nouvelles du développeur.';
        $output .= '</p>';
        $output .= '<div class="sisme-block-meta">';
        $output .= '<span class="sisme-block-tag">Actualités</span>';
        $output .= '<span class="sisme-block-tag">Mises à jour</span>';
        $output .= '<span class="sisme-block-tag">Patches</span>';
        $output .= '</div>';
        $output .= '<div class="sisme-block-cta">Voir les news →</div>';
        $output .= '</div>';
        
        $output .= '<meta itemprop="author" content="Sisme Games">';
        $output .= '<meta itemprop="datePublished" content="' . get_the_date('c') . '">';
        $output .= '<meta itemprop="publisher" itemscope itemtype="https://schema.org/Organization">';
        $output .= '<meta itemprop="name" content="Sisme Games">';
        $output .= '</a>';
        
        $output .= '</div>'; // Fin sisme-blocks-grid
        $output .= '</div>'; // Fin sisme-fiche-blocks
        
        return $output;
    }
    
    /**
     * Rendu des liens boutiques
     */
    private function render_store_links($steam_url, $epic_url) {
        $output = '<div class="sisme-fiche-stores">';
        $output .= '<h3>Où l\'acheter</h3>';
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
        
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Formater la date en français
     */
    private function format_french_date($date_string) {
        if (empty($date_string)) return 'Non spécifiée';
        
        $months = array(
            '01' => 'janvier', '02' => 'février', '03' => 'mars', '04' => 'avril',
            '05' => 'mai', '06' => 'juin', '07' => 'juillet', '08' => 'août',
            '09' => 'septembre', '10' => 'octobre', '11' => 'novembre', '12' => 'décembre'
        );
        
        $date = DateTime::createFromFormat('Y-m-d', $date_string);
        if ($date) {
            return $date->format('j') . ' ' . $months[$date->format('m')] . ' ' . $date->format('Y');
        }
        
        return $date_string;
    }
    
    /**
     * Formater les entreprises
     */
    private function format_companies($companies) {
        if (empty($companies)) return 'Non spécifié';
        
        $formatted = array();
        foreach ($companies as $company) {
            if (is_array($company)) {
                if (!empty($company['url'])) {
                    $formatted[] = '<a href="' . esc_url($company['url']) . '" target="_blank">' . esc_html($company['name']) . '</a>';
                } else {
                    $formatted[] = esc_html($company['name']);
                }
            } elseif (is_string($company)) {
                $formatted[] = esc_html($company);
            }
        }
        
        return implode(', ', $formatted);
    }
}