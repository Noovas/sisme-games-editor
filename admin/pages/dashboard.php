<?php
/**
 * File: /sisme-games-editor/admin/pages/dashboard.php
 * Dashboard - Structure cohérente avec les listes
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Fonction pour compter les articles par type
function count_posts_by_category_prefix($prefix) {
    $all_categories = get_categories(array('hide_empty' => false));
    $category_ids = array();
    
    if ($prefix === 'jeux-') {
        foreach ($all_categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0) {
                $category_ids[] = $category->term_id;
            }
        }
    } else {
        $category = get_category_by_slug($prefix);
        if ($category) {
            $category_ids[] = $category->term_id;
        }
    }
    
    if (empty($category_ids)) {
        return 0;
    }
    
    $query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => array('publish', 'draft', 'private'),
        'category__in' => $category_ids,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    return $query->found_posts;
}

// Fonction pour compter les brouillons
function count_drafts_by_category_prefix($prefix) {
    $all_categories = get_categories(array('hide_empty' => false));
    $category_ids = array();
    
    if ($prefix === 'jeux-') {
        foreach ($all_categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0) {
                $category_ids[] = $category->term_id;
            }
        }
    } else {
        $category = get_category_by_slug($prefix);
        if ($category) {
            $category_ids[] = $category->term_id;
        }
    }
    
    if (empty($category_ids)) {
        return 0;
    }
    
    $query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'draft',
        'category__in' => $category_ids,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    return $query->found_posts;
}

// Récupérer les statistiques

$game_data_stats = array();
$all_tags = get_terms(['taxonomy' => 'post_tag', 'hide_empty' => false]);
$tags_with_data = 0;
if (!is_wp_error($all_tags)) {
    foreach ($all_tags as $tag) {
        $meta = get_term_meta($tag->term_id);
        if (!empty($meta)) {
            $tags_with_data++;
        }
    }
}

$game_data_stats = array(
    'total_tags' => is_wp_error($all_tags) ? 0 : count($all_tags),
    'tags_with_data' => $tags_with_data
);

$stats = array(
    'fiches' => count_posts_by_category_prefix('jeux-'),
    'news' => count_posts_by_category_prefix('news'),
    'patch' => count_posts_by_category_prefix('patch'),
    'tests' => count_posts_by_category_prefix('tests')
);
$stats['total'] = $stats['fiches'] + $stats['news'] + $stats['patch'] + $stats['tests'];

// Statistiques des brouillons
$drafts = array(
    'fiches' => count_drafts_by_category_prefix('jeux-'),
    'news' => count_drafts_by_category_prefix('news'),
    'patch' => count_drafts_by_category_prefix('patch'),
    'tests' => count_drafts_by_category_prefix('tests')
);
$drafts['total'] = $drafts['fiches'] + $drafts['news'] + $drafts['patch'] + $drafts['tests'];

// Activité récente (derniers articles créés/modifiés)
$recent_activity = new WP_Query(array(
    'post_type' => 'post',
    'post_status' => array('publish', 'draft'),
    'posts_per_page' => 5,
    'orderby' => 'modified',
    'order' => 'DESC',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => '_sisme_game_modes',
            'compare' => 'EXISTS'
        ),
        array(
            'key' => '_sisme_article_type', 
            'compare' => 'EXISTS'
        )
    )
));
?>

<div class="sisme-admin-page">
    <header class="sisme-admin-header">
        <h1 class="sisme-admin-title">
            <span class="sisme-admin-title-icon">🏠</span>
            Tableau de bord Sisme Games
        </h1>
        <p class="sisme-admin-subtitle">
            Vue d'ensemble du système de gestion de contenu Gaming
        </p>
    </header>

    <div class="sisme-admin-layout">
        <!-- Statistique Game Data principale -->
        <div class="sisme-card sisme-card--primary sisme-mb-lg">
            <div class="sisme-card__header">
                <h2 class="sisme-heading-3">🎮 Game Data Overview</h2>
            </div>
            <div class="sisme-card__body">
                <div class="sisme-flex sisme-flex-between sisme-flex-center">
                    <div class="sisme-flex sisme-flex-center">
                        <div class="sisme-text-4xl sisme-mr-md">🎮</div>
                        <div>
                            <div class="sisme-heading-2 sisme-mb-0">
                                <?php echo $game_data_stats['tags_with_data']; ?> / <?php echo $game_data_stats['total_tags']; ?>
                            </div>
                            <p class="sisme-text-muted sisme-mb-0">Jeux avec données</p>
                        </div>
                    </div>
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-game-data'); ?>" 
                       class="sisme-btn sisme-btn--primary">
                        Gérer →
                    </a>
                </div>
            </div>
        </div>

        <!-- Section statistiques principales -->
        <div class="sisme-card sisme-mb-lg">
            <div class="sisme-card__header">
                <h2 class="sisme-heading-3">📊 Aperçu de votre contenu</h2>
            </div>
            <div class="sisme-card__body">
                <div class="sisme-grid sisme-grid-4">
                    <div class="sisme-card sisme-card--secondary">
                        <div class="sisme-card__body sisme-text-center">
                            <div class="sisme-text-4xl sisme-color-primary sisme-mb-sm">
                                <?php echo $stats['total']; ?>
                            </div>
                            <div class="sisme-text-sm sisme-text-muted">Total articles</div>
                        </div>
                    </div>
                    <div class="sisme-card sisme-card--secondary">
                        <div class="sisme-card__body sisme-text-center">
                            <div class="sisme-text-4xl sisme-color-primary sisme-mb-sm">
                                <?php echo $stats['fiches']; ?>
                            </div>
                            <div class="sisme-text-sm sisme-text-muted">Fiches de jeu</div>
                        </div>
                    </div>
                    <div class="sisme-card sisme-card--secondary">
                        <div class="sisme-card__body sisme-text-center">
                            <div class="sisme-text-4xl sisme-color-primary sisme-mb-sm">
                                <?php echo $stats['news'] + $stats['patch']; ?>
                            </div>
                            <div class="sisme-text-sm sisme-text-muted">Patch & News</div>
                        </div>
                    </div>
                    <div class="sisme-card sisme-card--secondary">
                        <div class="sisme-card__body sisme-text-center">
                            <div class="sisme-text-4xl sisme-color-primary sisme-mb-sm">
                                <?php echo $stats['tests']; ?>
                            </div>
                            <div class="sisme-text-sm sisme-text-muted">Tests</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section brouillons (si il y en a) -->
        <?php if ($drafts['total'] > 0) : ?>
        <div class="sisme-card sisme-mb-lg">
            <div class="sisme-card__header">
                <h2 class="sisme-heading-3">📝 Brouillons en attente</h2>
            </div>
            <div class="sisme-card__body">
                <div class="sisme-grid sisme-grid-4">
                    <div class="sisme-card sisme-card--warning">
                        <div class="sisme-card__body sisme-text-center">
                            <div class="sisme-text-4xl sisme-color-warning sisme-mb-sm">
                                <?php echo $drafts['total']; ?>
                            </div>
                            <div class="sisme-text-sm sisme-text-muted">Total brouillons</div>
                        </div>
                    </div>
                    <?php if ($drafts['fiches'] > 0) : ?>
                    <div class="sisme-card sisme-card--warning">
                        <div class="sisme-card__body sisme-text-center">
                            <div class="sisme-text-4xl sisme-color-warning sisme-mb-sm">
                                <?php echo $drafts['fiches']; ?>
                            </div>
                            <div class="sisme-text-sm sisme-text-muted">Fiches</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($drafts['news'] + $drafts['patch'] > 0) : ?>
                    <div class="sisme-card sisme-card--warning">
                        <div class="sisme-card__body sisme-text-center">
                            <div class="sisme-text-4xl sisme-color-warning sisme-mb-sm">
                                <?php echo $drafts['news'] + $drafts['patch']; ?>
                            </div>
                            <div class="sisme-text-sm sisme-text-muted">Patch & News</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($drafts['tests'] > 0) : ?>
                    <div class="sisme-card sisme-card--warning">
                        <div class="sisme-card__body sisme-text-center">
                            <div class="sisme-text-4xl sisme-color-warning sisme-mb-sm">
                                <?php echo $drafts['tests']; ?>
                            </div>
                            <div class="sisme-text-sm sisme-text-muted">Tests</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Section actions rapides -->
        <div class="sisme-card sisme-mb-lg">
            <div class="sisme-card__header">
                <h2 class="sisme-heading-3">⚡ Actions rapides</h2>
            </div>
            <div class="sisme-card__body">
                <div class="sisme-grid sisme-grid-2">
                    <!-- Game Data -->
                    <div class="sisme-card sisme-card--highlight">
                        <div class="sisme-card__header">
                            <h3 class="sisme-heading-4">🎮 Game Data</h3>
                        </div>
                        <div class="sisme-card__body">
                            <p class="sisme-text sisme-mb-md">
                                Gérez les données des jeux (étiquettes) et leurs métadonnées.
                            </p>
                            <div class="sisme-flex sisme-flex-wrap sisme-gap-sm sisme-flex-center">
                                <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-game-data'); ?>" 
                                   class="sisme-btn sisme-btn--primary">➕
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=sisme-games-game-data'); ?>" 
                                   class="sisme-btn sisme-btn--secondary">📊
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fiches de jeu -->
                    <div class="sisme-card sisme-card--highlight">
                        <div class="sisme-card__header">
                            <h3 class="sisme-heading-4">🎮 Fiches de jeu</h3>
                        </div>
                        <div class="sisme-card__body">
                            <p class="sisme-text sisme-mb-md">
                                Créez des fiches détaillées pour présenter les jeux : informations principales, 
                                captures d'écran, caractéristiques techniques et description complète.
                            </p>
                            <div class="sisme-flex sisme-flex-wrap sisme-gap-sm sisme-flex-center">
                                <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche'); ?>" 
                                   class="sisme-btn sisme-btn--primary">➕
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" 
                                   class="sisme-btn sisme-btn--secondary">📋
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Patch & News -->
                    <div class="sisme-card sisme-card--highlight">
                        <div class="sisme-card__header">
                            <h3 class="sisme-heading-4">📰 Patch & News</h3>
                        </div>
                        <div class="sisme-card__body">
                            <p class="sisme-text sisme-mb-md">
                                Rédigez rapidement des articles sur les dernières mises à jour, patches 
                                et actualités du monde du gaming.
                            </p>
                            <div class="sisme-flex sisme-flex-wrap sisme-gap-sm sisme-flex-center">
                                <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-patch-news'); ?>" 
                                   class="sisme-btn sisme-btn--primary">➕
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=sisme-games-patch-news'); ?>" 
                                   class="sisme-btn sisme-btn--secondary">📋
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tests -->
                    <div class="sisme-card sisme-card--highlight">
                        <div class="sisme-card__header">
                            <h3 class="sisme-heading-4">⭐ Tests</h3>
                        </div>
                        <div class="sisme-card__body">
                            <p class="sisme-text sisme-mb-md">
                                Créez des tests complets avec analyse détaillée, points forts/faibles, 
                                et verdict final pour guider vos lecteurs.
                            </p>
                            <div class="sisme-flex sisme-flex-wrap sisme-gap-sm sisme-flex-center">
                                <a href="<?php echo admin_url('admin.php?page=sisme-games-tests'); ?>" 
                                   class="sisme-btn sisme-btn--primary">➕
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=sisme-games-tests'); ?>" 
                                   class="sisme-btn sisme-btn--secondary">📋
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Configuration -->
                    <div class="sisme-card sisme-card--highlight">
                        <div class="sisme-card__header">
                            <h3 class="sisme-heading-4">⚙️ Configuration</h3>
                        </div>
                        <div class="sisme-card__body">
                            <p class="sisme-text sisme-mb-md">
                                Configurez les paramètres SEO, les templates par défaut et les options 
                                d'affichage pour optimiser votre workflow.
                            </p>
                            <div class="sisme-flex sisme-flex-wrap sisme-gap-sm sisme-flex-center">
                                <a href="<?php echo admin_url('admin.php?page=sisme-games-settings'); ?>" 
                                   class="sisme-btn sisme-btn--primary">⚙️
                                </a>
                                <a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>" 
                                   class="sisme-btn sisme-btn--secondary">🏷️
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section activité récente -->
        <?php if ($recent_activity->have_posts()) : ?>
        <div class="sisme-card sisme-mb-lg">
            <div class="sisme-card__header">
                <h2 class="sisme-heading-3">🕒 Activité récente</h2>
            </div>
            <div class="sisme-card__body">
                <div class="sisme-activity-list">
                    <?php while ($recent_activity->have_posts()) : $recent_activity->the_post(); 
                        $post_id = get_the_ID();
                        $post_type = 'Article';
                        $post_icon = '📄';
                        
                        // Déterminer le type d'article
                        $categories = get_the_category($post_id);
                        foreach ($categories as $category) {
                            if (strpos($category->slug, 'jeux-') === 0) {
                                $post_type = 'Fiche de jeu';
                                $post_icon = '🎮';
                                break;
                            } elseif ($category->slug === 'news') {
                                $post_type = 'News';
                                $post_icon = '📰';
                                break;
                            } elseif ($category->slug === 'patch') {
                                $post_type = 'Patch';
                                $post_icon = '🔧';
                                break;
                            } elseif ($category->slug === 'tests') {
                                $post_type = 'Test';
                                $post_icon = '⭐';
                                break;
                            }
                        }
                        
                        $status = get_post_status();
                        $status_label = $status === 'publish' ? 'Publié' : 'Brouillon';
                        $status_class = $status === 'publish' ? 'published' : 'draft';
                    ?>
                    <div class="sisme-activity-item">
                        <div class="sisme-flex sisme-flex-center sisme-gap-md">
                            <div class="sisme-activity-icon"><?php echo $post_icon; ?></div>
                            <div class="sisme-flex-1">
                                <h4 class="sisme-heading-4 sisme-mb-xs">
                                    <a href="<?php 
                                        if (strpos($post_type, 'Fiche') !== false) {
                                            echo admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id);
                                        } elseif (in_array($post_type, array('News', 'Patch'))) {
                                            echo admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id);
                                        } else {
                                            echo get_edit_post_link($post_id);
                                        }
                                    ?>" class="sisme-link">
                                        <?php the_title(); ?>
                                    </a>
                                </h4>
                                <div class="sisme-flex sisme-flex-center sisme-gap-sm sisme-text-sm sisme-text-muted">
                                    <span class="sisme-tag sisme-tag--type"><?php echo $post_type; ?></span>
                                    <span class="sisme-tag sisme-tag--status sisme-tag--status-<?php echo $status_class; ?>">
                                        <?php echo $status_label; ?>
                                    </span>
                                    <span><?php echo human_time_diff(get_the_modified_time('U'), current_time('U')) . ' ago'; ?></span>
                                </div>
                            </div>
                            <div class="sisme-flex sisme-gap-xs">
                                <a href="<?php 
                                    if (strpos($post_type, 'Fiche') !== false) {
                                        echo admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id);
                                    } elseif (in_array($post_type, array('News', 'Patch'))) {
                                        echo admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id);
                                    } else {
                                        echo get_edit_post_link($post_id);
                                    }
                                ?>" class="sisme-btn sisme-btn--sm sisme-btn--secondary">✏️
                                </a>
                                <a href="<?php echo get_permalink($post_id); ?>" target="_blank" 
                                   class="sisme-btn sisme-btn--sm sisme-btn--secondary">👁️
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="sisme-text-center sisme-mt-md">
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-all-articles'); ?>" 
                       class="sisme-btn sisme-btn--secondary">
                        📋 Voir tous les articles
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Section liens utiles -->
        <div class="sisme-card">
            <div class="sisme-card__header">
                <h2 class="sisme-heading-3">🔗 Liens utiles</h2>
            </div>
            <div class="sisme-card__body">
                <div class="sisme-grid sisme-grid-3">
                    <div class="sisme-links-group">
                        <h4 class="sisme-heading-4 sisme-mb-sm">Game Data</h4>
                        <ul class="sisme-links-list">
                            <li><a href="<?php echo admin_url('admin.php?page=sisme-games-game-data'); ?>" class="sisme-link">📊 Tous les jeux</a></li>
                            <li><a href="<?php echo admin_url('admin.php?page=sisme-games-edit-game-data'); ?>" class="sisme-link">➕ Créer un jeu</a></li>
                            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=post_tag'); ?>" class="sisme-link">🏷️ Étiquettes WordPress</a></li>
                        </ul>
                    </div>
                    <div class="sisme-links-group">
                        <h4 class="sisme-heading-4 sisme-mb-sm">WordPress</h4>
                        <ul class="sisme-links-list">
                            <li><a href="<?php echo admin_url('admin.php?page=sisme-games-all-articles'); ?>" class="sisme-link">📄 Tous les articles</a></li>
                            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>" class="sisme-link">🏷️ Catégories</a></li>
                            <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=post_tag'); ?>" class="sisme-link">🏷️ Étiquettes</a></li>
                            <li><a href="<?php echo admin_url('upload.php'); ?>" class="sisme-link">📸 Médiathèque</a></li>
                        </ul>
                    </div>
                    <div class="sisme-links-group">
                        <h4 class="sisme-heading-4 sisme-mb-sm">Sisme Games</h4>
                        <ul class="sisme-links-list">
                            <li><a href="https://games.sisme.fr" target="_blank" class="sisme-link">🌐 Site games.sisme.fr</a></li>
                            <li><a href="https://sisme.fr" target="_blank" class="sisme-link">🌐 Site principal</a></li>
                            <li><a href="<?php echo admin_url('admin.php?page=sisme-games-settings'); ?>" class="sisme-link">⚙️ Réglages du plugin</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php wp_reset_postdata(); ?>