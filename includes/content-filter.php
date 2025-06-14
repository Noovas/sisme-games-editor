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
     * Rendu des blocs par défaut avec gestion intelligente des liens
     */
    private function render_default_blocks() {
        global $post;
        $game_title = get_the_title();
        $game_slug = $post->post_name;
        
        // Récupérer les catégories pour le SEO
        $categories = get_the_category();
        $main_category = '';
        foreach ($categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0) {
                $main_category = str_replace('jeux-', '', $category->slug);
                break;
            }
        }
        
        // URLs optimisées SEO selon les règles Sisme Games
        $test_url = 'https://games.sisme.fr/' . $game_slug . '-test/';
        $news_url = 'https://games.sisme.fr/' . $game_slug . '-news/';
        
        // Images par défaut (à comparer pour savoir si griser)
        $default_test_image = 'https://games.sisme.fr/wp-content/uploads/2025/06/Sisme-Games-default-test.webp';
        $default_news_image = 'https://games.sisme.fr/wp-content/uploads/2025/06/Sisme-Games-default-news.webp';
        
        // Récupérer les images personnalisées depuis les métadonnées
        $test_image_id = get_post_meta($post->ID, '_sisme_test_image_id', true);
        $news_image_id = get_post_meta($post->ID, '_sisme_news_image_id', true);
        
        // Déterminer les images à utiliser et si les liens sont actifs
        $test_image = '';
        $news_image = '';
        $test_link_active = false;
        $news_link_active = false;
        
        if (!empty($test_image_id)) {
            $test_image_data = wp_get_attachment_image_src($test_image_id, 'medium_large');
            if ($test_image_data) {
                $test_image = $test_image_data[0];
                $test_link_active = true;
            }
        }
        
        if (!empty($news_image_id)) {
            $news_image_data = wp_get_attachment_image_src($news_image_id, 'medium_large');
            if ($news_image_data) {
                $news_image = $news_image_data[0];
                $news_link_active = true;
            }
        }
        
        // Fallback vers les images par défaut si aucune image personnalisée
        if (empty($test_image)) {
            $test_image = $default_test_image;
        }
        
        if (empty($news_image)) {
            $news_image = $default_news_image;
        }
        
        // Container principal avec la MÊME structure que les informations
        $output = '<div class="sisme-fiche-blocks" itemscope itemtype="https://schema.org/ItemList">';
        
        // Titre avec icône - IDENTIQUE aux informations
        $output .= '<h3 itemprop="name">Test & Actualités</h3>';
        
        // Meta pour la liste
        $output .= '<meta itemprop="numberOfItems" content="2">';
        $output .= '<meta itemprop="description" content="Tests et actualités pour ' . esc_attr($game_title) . '">';
        
        // Grille des blocs
        $output .= '<div class="sisme-blocks-grid">';
        
        // ========== BLOC TEST ==========
        $test_block_class = 'sisme-block sisme-test-block';
        if (!$test_link_active) {
            $test_block_class .= ' sisme-block-inactive';
        }
        
        $output .= '<div class="' . $test_block_class . '" itemprop="itemListElement" itemscope itemtype="https://schema.org/Review">';
        
        // Position dans la liste
        $output .= '<meta itemprop="position" content="1">';
        
        // Wrapper du bloc avec ou sans lien
        if ($test_link_active) {
            $output .= '<a href="' . esc_url($test_url) . '" class="sisme-block-wrapper" ';
            $output .= 'title="Lire notre test complet et détaillé de ' . esc_attr($game_title) . ' - Gameplay, graphismes, verdict" ';
            $output .= 'aria-label="Test complet du jeu ' . esc_attr($game_title) . '">';
        } else {
            $output .= '<div class="sisme-block-wrapper sisme-block-disabled" ';
            $output .= 'title="Test de ' . esc_attr($game_title) . ' bientôt disponible" ';
            $output .= 'aria-label="Test du jeu ' . esc_attr($game_title) . ' à venir">';
        }
        
        // Image du bloc test
        $output .= '<div class="sisme-block-image">';
        $output .= '<img src="' . esc_url($test_image) . '" ';
        $output .= 'alt="' . ($test_link_active ? 'Test complet de ' . esc_attr($game_title) : 'Test de ' . esc_attr($game_title) . ' bientôt disponible') . '" ';
        $output .= 'width="400" height="200" ';
        $output .= 'loading="lazy" ';
        $output .= 'decoding="async">';
        $output .= '</div>';
        
        // Contenu du bloc test
        $output .= '<div class="sisme-block-content">';
        $output .= '<h4 class="sisme-block-title" itemprop="name">Test de ' . esc_html($game_title) . '</h4>';
        $output .= '<p class="sisme-block-description" itemprop="description">';
        if ($test_link_active) {
            $output .= 'Découvrez notre test complet avec analyse détaillée du gameplay, des graphismes, ';
            $output .= 'de la bande sonore et notre verdict final avec note.';
        } else {
            $output .= 'Notre test complet de ' . esc_html($game_title) . ' sera bientôt disponible. ';
            $output .= 'Revenez prochainement pour découvrir notre analyse détaillée.';
        }
        $output .= '</p>';
        
        // Meta tags
        $output .= '<div class="sisme-block-meta">';
        if ($test_link_active) {
            $output .= '<span class="sisme-block-tag sisme-tag-active">Test Complet</span>';
            $output .= '<span class="sisme-block-tag sisme-tag-active">Verdict & Note</span>';
            $output .= '<span class="sisme-block-tag sisme-tag-active">Analyse Pro</span>';
        } else {
            $output .= '<span class="sisme-block-tag sisme-tag-inactive">Bientôt</span>';
            $output .= '<span class="sisme-block-tag sisme-tag-inactive">Test à venir</span>';
        }
        $output .= '</div>';
        
        // CTA ou status
        if ($test_link_active) {
            $output .= '<span class="sisme-block-cta">Lire le test complet</span>';
        } else {
            $output .= '<span class="sisme-block-status">Test en préparation</span>';
        }
        
        $output .= '</div>'; // Fin block-content
        
        // Fermer le wrapper (lien ou div)
        $output .= $test_link_active ? '</a>' : '</div>';
        
        // Meta SEO pour le test
        if ($test_link_active) {
            $output .= '<meta itemprop="author" content="Sisme Games">';
            $output .= '<meta itemprop="datePublished" content="' . get_the_date('c') . '">';
            $output .= '<meta itemprop="reviewRating" itemscope itemtype="https://schema.org/Rating">';
            $output .= '<meta itemprop="ratingValue" content="4.5">';
            $output .= '<meta itemprop="bestRating" content="5">';
            $output .= '<meta itemprop="worstRating" content="1">';
            $output .= '<meta itemprop="url" content="' . esc_url($test_url) . '">';
        }
        
        $output .= '</div>'; // Fin sisme-test-block
        
        // ========== BLOC NEWS ==========
        $news_block_class = 'sisme-block sisme-news-block';
        if (!$news_link_active) {
            $news_block_class .= ' sisme-block-inactive';
        }
        
        $output .= '<div class="' . $news_block_class . '" itemprop="itemListElement" itemscope itemtype="https://schema.org/NewsArticle">';
        
        // Position dans la liste
        $output .= '<meta itemprop="position" content="2">';
        
        // Wrapper du bloc avec ou sans lien
        if ($news_link_active) {
            $output .= '<a href="' . esc_url($news_url) . '" class="sisme-block-wrapper" ';
            $output .= 'title="Toutes les actualités de ' . esc_attr($game_title) . ' - Patches, DLC, mises à jour, événements" ';
            $output .= 'aria-label="Actualités du jeu ' . esc_attr($game_title) . '">';
        } else {
            $output .= '<div class="sisme-block-wrapper sisme-block-disabled" ';
            $output .= 'title="Actualités de ' . esc_attr($game_title) . ' bientôt disponibles" ';
            $output .= 'aria-label="Actualités du jeu ' . esc_attr($game_title) . ' à venir">';
        }
        
        // Image du bloc news
        $output .= '<div class="sisme-block-image">';
        $output .= '<img src="' . esc_url($news_image) . '" ';
        $output .= 'alt="' . ($news_link_active ? 'Actualités et news de ' . esc_attr($game_title) : 'Actualités de ' . esc_attr($game_title) . ' bientôt disponibles') . '" ';
        $output .= 'width="400" height="200" ';
        $output .= 'loading="lazy" ';
        $output .= 'decoding="async">';
        $output .= '</div>';
        
        // Contenu du bloc news
        $output .= '<div class="sisme-block-content">';
        $output .= '<h4 class="sisme-block-title" itemprop="headline">News ' . esc_html($game_title) . '</h4>';
        $output .= '<p class="sisme-block-description" itemprop="description">';
        if ($news_link_active) {
            $output .= 'Suivez toute l\'actualité : patches, mises à jour, DLC, événements spéciaux, ';
            $output .= 'annonces officielles et dernières nouvelles du développeur.';
        } else {
            $output .= 'Les actualités pour ' . esc_html($game_title) . ' seront bientôt disponibles. ';
            $output .= 'Patches, DLC et news du développeur à suivre ici.';
        }
        $output .= '</p>';
        
        // Meta tags
        $output .= '<div class="sisme-block-meta">';
        if ($news_link_active) {
            $output .= '<span class="sisme-block-tag sisme-tag-active">Actualités</span>';
            $output .= '<span class="sisme-block-tag sisme-tag-active">Patches & DLC</span>';
            $output .= '<span class="sisme-block-tag sisme-tag-active">Communauté</span>';
        } else {
            $output .= '<span class="sisme-block-tag sisme-tag-inactive">Bientôt</span>';
            $output .= '<span class="sisme-block-tag sisme-tag-inactive">News à venir</span>';
        }
        $output .= '</div>';
        
        // CTA ou status
        if ($news_link_active) {
            $output .= '<span class="sisme-block-cta">Voir toutes les news</span>';
        } else {
            $output .= '<span class="sisme-block-status">Actualités en préparation</span>';
        }
        
        $output .= '</div>'; // Fin block-content
        
        // Fermer le wrapper (lien ou div)
        $output .= $news_link_active ? '</a>' : '</div>';
        
        // Meta SEO pour les news
        if ($news_link_active) {
            $output .= '<meta itemprop="author" content="Sisme Games">';
            $output .= '<meta itemprop="datePublished" content="' . get_the_date('c') . '">';
            $output .= '<meta itemprop="publisher" itemscope itemtype="https://schema.org/Organization">';
            $output .= '<meta itemprop="name" content="Sisme Games">';
            $output .= '<meta itemprop="url" content="https://games.sisme.fr">';
            $output .= '<meta itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">';
            $output .= '<meta itemprop="url" content="' . (get_site_icon_url(512) ?: 'https://games.sisme.fr/logo.png') . '">';
            $output .= '<meta itemprop="mainEntityOfPage" content="' . esc_url($news_url) . '">';
        }
        
        $output .= '</div>'; // Fin sisme-news-block
        
        $output .= '</div>'; // Fin sisme-blocks-grid
        
        // JSON-LD structuré pour la navigation interne
        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'Test et Actualités - ' . $game_title,
            'description' => 'Tests complets et actualités pour le jeu ' . $game_title,
            'numberOfItems' => 2,
            'itemListElement' => array()
        );
        
        if ($test_link_active) {
            $structured_data['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Test de ' . $game_title,
                'description' => 'Test complet et analyse détaillée du jeu ' . $game_title,
                'url' => $test_url,
                'image' => $test_image
            );
        }
        
        if ($news_link_active) {
            $structured_data['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Actualités ' . $game_title,
                'description' => 'Toutes les actualités et news pour ' . $game_title,
                'url' => $news_url,
                'image' => $news_image
            );
        }
        
        $output .= '<script type="application/ld+json">' . wp_json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        
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