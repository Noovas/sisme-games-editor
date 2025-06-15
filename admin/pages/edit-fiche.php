<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-fiche.php
 * Page d'édition/visualisation d'une fiche de jeu
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer l'ID de l'article
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

if (!$post_id) {
    wp_die('ID d\'article manquant');
}

// Récupérer l'article
$post = get_post($post_id);
if (!$post) {
    wp_die('Article introuvable');
}

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

// Récupérer l'image mise en avant
$featured_image = get_the_post_thumbnail($post_id, 'large');

// Fonction pour formater la date
function format_french_date($date_string) {
    if (empty($date_string)) return 'Non spécifiée';
    
    $months = array(
        '01' => 'janvier', '02' => 'février', '03' => 'mars', '04' => 'avril',
        '05' => 'mai', '06' => 'juin', '07' => 'juillet', '08' => 'août',
        '09' => 'septembre', '10' => 'octobre', '11' => 'novembre', '12' => 'décembre'
    );
    
    $date = DateTime::createFromFormat('Y-m-d', $date_string);
    if ($date) {
        $day = $date->format('j');
        $month = $months[$date->format('m')];
        $year = $date->format('Y');
        return $day . ' ' . $month . ' ' . $year;
    }
    
    return $date_string;
}

// Fonction pour formater les entreprises avec liens
function format_companies_display($companies) {
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

<div class="wrap">
    <h1>
        <?php echo esc_html($post->post_title); ?>
        <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="page-title-action">
            ← Retour à la liste
        </a>
    </h1>
    
    <div style="background: #f1f1f1; padding: 15px; margin: 20px 0; border-left: 4px solid #0073aa;">
        <p><strong>Statut :</strong> <?php echo get_post_status($post_id) === 'publish' ? 'Publié' : 'Brouillon'; ?></p>
        <p>
            <a href="<?php echo get_edit_post_link($post_id); ?>" class="button">Éditer dans WordPress</a>
            <a href="<?php echo get_permalink($post_id); ?>" target="_blank" class="button">Voir sur le site</a>
        </p>
    </div>
    
    <!-- Aperçu de la fiche comme elle apparaîtra -->
    <div style="background: white; padding: 30px; border: 1px solid #ddd; max-width: 800px;">
        
        <!-- Titre -->
        <h1 style="color: #2c3e50; margin-bottom: 10px;"><?php echo esc_html($post->post_title); ?></h1>
        
        <!-- Catégories/Tags -->
        <?php if (!empty($game_categories)) : ?>
            <div style="margin-bottom: 20px;">
                <?php foreach ($game_categories as $category) : ?>
                    <span style="background: #e1f5fe; padding: 3px 8px; border-radius: 3px; margin-right: 5px; font-size: 12px;">
                        <?php echo esc_html(str_replace('jeux-', '', $category->name)); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Description principale -->
        <div style="margin-bottom: 30px; line-height: 1.6;">
            <?php echo wpautop(esc_html($post->post_excerpt)); ?>
        </div>
        
        <!-- Vidéo YouTube -->
        <?php if (!empty($trailer_url)) : ?>
            <div style="margin-bottom: 30px;">
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
                    <div style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden;">
                        <iframe src="https://www.youtube.com/embed/<?php echo esc_attr($video_id); ?>" 
                                style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" 
                                frameborder="0" allowfullscreen></iframe>
                    </div>
                <?php else : ?>
                    <p><a href="<?php echo esc_url($trailer_url); ?>" target="_blank">Voir le trailer</a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Informations -->
        <div style="margin-bottom: 30px;">
            <h3>Informations</h3>
            <ul style="list-style: disc; margin-left: 20px; line-height: 1.8;">
                <?php if (!empty($game_categories)) : ?>
                    <li><strong>Genre :</strong> 
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
                    <li><strong>Mode de jeu :</strong> <?php echo esc_html(implode(', ', $game_modes)); ?></li>
                <?php endif; ?>
                
                <li><strong>Date de sortie :</strong> <?php echo format_french_date($release_date); ?></li>
                
                <?php if (!empty($platforms)) : ?>
                    <li><strong>Plateformes :</strong> 
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
                    <li><strong>Développeur :</strong> <?php echo format_companies_display($developers); ?></li>
                <?php endif; ?>
                
                <?php if (!empty($editors)) : ?>
                    <li><strong>Éditeur :</strong> <?php echo format_companies_display($editors); ?></li>
                <?php endif; ?>
            </ul>
        </div>
        
        <!-- Bloc Test/News par défaut -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <a href="#" style="display: block; text-decoration: none;">
                <img src="https://games.sisme.fr/wp-content/uploads/2025/06/Sisme-Games-default-test.webp" 
                     alt="Lien vers le test" style="width: 100%; height: auto; border-radius: 5px;">
            </a>
            <a href="#" style="display: block; text-decoration: none;">
                <img src="https://games.sisme.fr/wp-content/uploads/2025/06/Sisme-Games-default-news.webp" 
                     alt="Lien vers les news" style="width: 100%; height: auto; border-radius: 5px;">
            </a>
        </div>
        
        <!-- Contenu de l'étape 2 -->
        <div style="margin-bottom: 30px;">
            <?php echo $post->post_content; ?>
        </div>
        
        <!-- Liens boutiques -->
        <?php if (!empty($steam_url) || !empty($epic_url)) : ?>
            <div style="text-align: center; margin-bottom: 30px;">
                <h3>Où l'acheter</h3>
                <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                    <?php if (!empty($steam_url)) : ?>
                        <a href="<?php echo esc_url($steam_url); ?>" target="_blank">
                            <img src="https://games.sisme.fr/wp-content/uploads/2025/04/GetItOnSteam.webp" 
                                 alt="Disponible sur Steam" style="height: 60px; width: auto;">
                        </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($epic_url)) : ?>
                        <a href="<?php echo esc_url($epic_url); ?>" target="_blank">
                            <img src="https://games.sisme.fr/wp-content/uploads/2025/05/get-on-epic.webp" 
                                 alt="Disponible sur Epic Games" style="height: 60px; width: auto;">
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Actions -->
    <div style="margin-top: 30px;">
        <p>
            <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="button">
                ← Retour à la liste
            </a>
            <a href="<?php echo get_edit_post_link($post_id); ?>" class="button button-primary">
                Éditer cette fiche
            </a>
            <span style="color: #666; margin-left: 20px; font-style: italic;">
                Fonctionnalités d'édition à venir dans la prochaine étape
            </span>
        </p>
    </div>
</div>