<?php
/**
 * File: /sisme-games-editor/includes/debug-helper.php
 * Fonctions utilitaires de debug pour le plugin Sisme Games Editor
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug des catégories - à utiliser temporairement pour vérifier la structure
 * Ajouter cette fonction dans une page admin pour voir toutes les catégories
 */
function sisme_debug_categories() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    echo '<div style="background: #fff; padding: 20px; margin: 20px; border: 1px solid #ccc;">';
    echo '<h3>🔍 Debug des catégories</h3>';
    
    $all_categories = get_categories(array(
        'hide_empty' => false,
        'fields' => 'all'
    ));
    
    echo '<h4>Toutes les catégories :</h4>';
    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>ID</th><th>Nom</th><th>Slug</th><th>Parent</th><th>Articles</th></tr>';
    
    foreach ($all_categories as $category) {
        $match_jeux = strpos($category->slug, 'jeux-') === 0 ? '✅' : '';
        echo '<tr>';
        echo '<td>' . $category->term_id . '</td>';
        echo '<td>' . $category->name . ' ' . $match_jeux . '</td>';
        echo '<td>' . $category->slug . '</td>';
        echo '<td>' . $category->parent . '</td>';
        echo '<td>' . $category->count . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
    // Afficher les catégories qui correspondent au filtre
    $jeux_categories = array();
    foreach ($all_categories as $category) {
        if (strpos($category->slug, 'jeux-') === 0) {
            $jeux_categories[] = $category;
        }
    }
    
    echo '<h4>Catégories "jeux-*" trouvées (' . count($jeux_categories) . ') :</h4>';
    echo '<ul>';
    foreach ($jeux_categories as $category) {
        echo '<li><strong>' . $category->name . '</strong> (slug: ' . $category->slug . ', ' . $category->count . ' articles)</li>';
    }
    echo '</ul>';
    
    echo '</div>';
}

/**
 * Fonction pour obtenir les IDs des catégories jeux
 * (fonction réutilisable dans tout le plugin)
 */
function sisme_get_jeux_category_ids() {
    static $category_ids = null;
    
    if ($category_ids === null) {
        $all_categories = get_categories(array(
            'hide_empty' => false,
            'fields' => 'all'
        ));
        
        $category_ids = array();
        foreach ($all_categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0) {
                $category_ids[] = $category->term_id;
            }
        }
    }
    
    return $category_ids;
}

/**
 * Fonction utilitaire pour afficher les statistiques de catégories
 */
function sisme_get_category_stats() {
    $stats = array();
    
    // Fiches de jeux (catégories jeux-*)
    $jeux_ids = sisme_get_jeux_category_ids();
    if (!empty($jeux_ids)) {
        $jeux_query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'category__in' => $jeux_ids,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $stats['fiches'] = $jeux_query->found_posts;
    } else {
        $stats['fiches'] = 0;
    }
    
    // News
    $news_category = get_category_by_slug('news');
    if ($news_category) {
        $news_query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'category__in' => array($news_category->term_id),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $stats['news'] = $news_query->found_posts;
    } else {
        $stats['news'] = 0;
    }
    
    // Tests
    $tests_category = get_category_by_slug('tests');
    if ($tests_category) {
        $tests_query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'category__in' => array($tests_category->term_id),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $stats['tests'] = $tests_query->found_posts;
    } else {
        $stats['tests'] = 0;
    }
    
    $stats['total'] = $stats['fiches'] + $stats['news'] + $stats['tests'];
    
    return $stats;
}