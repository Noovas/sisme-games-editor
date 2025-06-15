<?php
/**
 * File: /sisme-games-editor/templates/single-fiche.php
 * Template front-end pour l'affichage des fiches de jeu
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="sisme-fiche-container">
    <?php while (have_posts()) : the_post(); 
        $post_id = get_the_ID();
        
        // Récupérer les métadonnées
        $game_modes = get_post_meta($post_id, '_sisme_game_modes', true) ?: array();
        $platforms = get_post_meta($post_id, '_sisme_platforms', true) ?: array();
        $release_date = get_post_meta($post_id, '_sisme_release_date', true);
        $developers = get_post_meta($post_id, '_sisme_developers', true) ?: array();
        $editors = get_post_meta($post_id, '_sisme_editors', true) ?: array();
        $trailer_url = get_post_meta($post_id, '_sisme_trailer_url', true);
        $steam_url = get_post_meta($post_id, '_sisme_steam_url', true);
        $epic_url = get_post_meta($post_id, '_sisme_epic_url', true);
        
        // Récupérer les catégories
        $categories = get_the_category($post_id);
        $game_categories = array();
        foreach ($categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0) {
                $game_categories[] = $category;
            }
        }
        
        // Fonction pour formater la date
        function format_french_date_frontend($date_string) {
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
        
        // Fonction pour formater les entreprises
        function format_companies_frontend($companies) {
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
    ?>
    
    <article class="sisme-fiche-article">
        
        <!-- Image mise en avant -->
        <?php if (has_post_thumbnail()) : ?>
            <div class="sisme-fiche-header">
                <?php the_post_thumbnail('large'); ?>
            </div>
        <?php endif; ?>
        
        <!-- Titre -->
        <h1 class="sisme-fiche-title"><?php the_title(); ?></h1>
        
        <!-- Catégories/Tags -->
        <?php if (!empty($game_categories)) : ?>
            <div class="sisme-fiche-tags">
                <?php foreach ($game_categories as $category) : ?>
                    <span class="sisme-tag">
                        <?php echo esc_html(str_replace('jeux-', '', $category->name)); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Description principale -->
        <div class="sisme-fiche-description">
            <?php echo wpautop(get_the_excerpt()); ?>
        </div>
        
        <!-- Vidéo YouTube -->
        <?php if (!empty($trailer_url)) : ?>
            <div class="sisme-fiche-trailer">
                <h3>Trailer</h3>
                <?php 
                // Convertir l'URL YouTube en embed
                $video_id = '';
                if (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $trailer_url, $matches)) {
                    $video_id = $matches[1];
                } elseif (preg_match('/youtu\.be\/([^?]+)/', $trailer_url, $matches)) {
                    $video_id = $matches[1];
                }
                
                if ($video_id) : ?>
                    <div class="sisme-video-container">
                        <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>" 
                                frameborder="0" allowfullscreen></iframe>
                    </div>
                <?php else : ?>
                    <p><a href="<?php echo esc_url($trailer_url); ?>" target="_blank">Voir le trailer</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Informations -->
        <div class="sisme-fiche-informations">
            <h3>Informations</h3>
            <ul>
                <?php if (!empty($game_categories)) : ?>
                    <li><strong>Genre</strong> : 
                        <?php 
                        $genre_links = array();
                        foreach ($game_categories as $category) {
                            $genre_links[] = '<a href="' . get_category_link($category->term_id) . '">' . 
                                           esc_html(str_replace('jeux-', '', $category->name)) . '</a>';
                        }
                        echo implode(', ', $genre_links);
                        ?>
                    </li>
                <?php endif; ?>
                
                <?php if (!empty($game_modes)) : ?>
                    <li><strong>Mode de jeu</strong> : <?php echo esc_html(implode(', ', $game_modes)); ?></li>
                <?php endif; ?>
                
                <li><strong>Date de sortie</strong> : <?php echo format_french_date_frontend($release_date); ?></li>
                
                <?php if (!empty($platforms)) : ?>
                    <li><strong>Plateformes</strong> : 
                        <?php 
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
                        echo esc_html(implode(', ', $platform_display));
                        ?>
                    </li>
                <?php endif; ?>
                
                <?php if (!empty($developers)) : ?>
                    <li><strong>Développeur(s)</strong> : <?php echo format_companies_frontend($developers); ?></li>
                <?php endif; ?>
                
                <?php if (!empty($editors)) : ?>
                    <li><strong>Éditeur(s)</strong> : <?php echo format_companies_frontend($editors); ?></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Bloc Test/News par défaut -->
        <div class="sisme-fiche-blocks">
            <a href="#" class="sisme-block-link">
                <img src="https://games.sisme.fr/wp-content/uploads/2025/06/Sisme-Games-default-test.webp" 
                     alt="Lien vers le test">
            </a>
            <a href="#" class="sisme-block-link">
                <img src="https://games.sisme.fr/wp-content/uploads/2025/06/Sisme-Games-default-news.webp" 
                     alt="Lien vers les news">
            </a>
        </div>
        
        <!-- Contenu de l'étape 2 -->
        <div class="sisme-fiche-content">
            <?php the_content(); ?>
        </div>
        
        <!-- Liens boutiques -->
        <?php if (!empty($steam_url) || !empty($epic_url)) : ?>
            <div class="sisme-fiche-stores">
                <h3>Où l'acheter</h3>
                <div class="sisme-store-links">
                    <?php if (!empty($steam_url)) : ?>
                        <a href="<?php echo esc_url($steam_url); ?>" target="_blank">
                            <img src="https://games.sisme.fr/wp-content/uploads/2025/04/GetItOnSteam.webp" 
                                 alt="Disponible sur Steam">
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($epic_url)) : ?>
                        <a href="<?php echo esc_url($epic_url); ?>" target="_blank">
                            <img src="https://games.sisme.fr/wp-content/uploads/2025/05/get-on-epic.webp" 
                                 alt="Disponible sur Epic Games">
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
    </article>
    
    <?php endwhile; ?>
</div>

<style>
.sisme-fiche-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.sisme-fiche-title {
    font-size: 2.5em;
    margin-bottom: 15px;
    color: #2c3e50;
}

.sisme-fiche-tags {
    margin-bottom: 20px;
}

.sisme-tag {
    background: #e1f5fe;
    padding: 4px 12px;
    border-radius: 4px;
    margin-right: 8px;
    font-size: 14px;
    display: inline-block;
}

.sisme-fiche-description {
    font-size: 1.1em;
    line-height: 1.6;
    margin-bottom: 30px;
}

.sisme-video-container {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
    margin-bottom: 30px;
}

.sisme-video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.sisme-fiche-informations ul {
    list-style: disc;
    margin-left: 20px;
    line-height: 1.8;
}

.sisme-fiche-blocks {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 30px 0;
}

.sisme-block-link img {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.sisme-store-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.sisme-store-links img {
    height: 60px;
    width: auto;
}
</style>

<?php get_footer(); ?>