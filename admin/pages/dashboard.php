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

<div class="wrap">
    <!-- En-tête -->
    <div class="page-header">
        <h1>
            🎮 Sisme Games Editor
        </h1>
        <p class="page-subtitle">
            Créez rapidement vos contenus gaming avec des templates optimisés pour une expérience professionnelle et cohérente.
        </p>
    </div>
    
    <!-- Section statistiques principales -->
    <div class="stats-section">
        <h3>📊 Aperçu de votre contenu</h3>
        <div class="stats-grid">
            <div class="stat-card stat-total">
                <span class="stat-number"><?php echo $stats['total']; ?></span>
                <span class="stat-label">Total articles</span>
            </div>
            <div class="stat-card stat-fiches">
                <span class="stat-number"><?php echo $stats['fiches']; ?></span>
                <span class="stat-label">Fiches de jeu</span>
            </div>
            <div class="stat-card stat-news">
                <span class="stat-number"><?php echo $stats['news'] + $stats['patch']; ?></span>
                <span class="stat-label">Patch & News</span>
            </div>
            <div class="stat-card stat-tests">
                <span class="stat-number"><?php echo $stats['tests']; ?></span>
                <span class="stat-label">Tests</span>
            </div>
        </div>
    </div>
    
    <!-- Section brouillons (si il y en a) -->
    <?php if ($drafts['total'] > 0) : ?>
    <div class="drafts-section">
        <h3>📝 Brouillons en attente</h3>
        <div class="stats-grid">
            <div class="stat-card draft-total">
                <span class="stat-number"><?php echo $drafts['total']; ?></span>
                <span class="stat-label">Total brouillons</span>
            </div>
            <?php if ($drafts['fiches'] > 0) : ?>
            <div class="stat-card draft-fiches">
                <span class="stat-number"><?php echo $drafts['fiches']; ?></span>
                <span class="stat-label">Fiches</span>
            </div>
            <?php endif; ?>
            <?php if ($drafts['news'] + $drafts['patch'] > 0) : ?>
            <div class="stat-card draft-news">
                <span class="stat-number"><?php echo $drafts['news'] + $drafts['patch']; ?></span>
                <span class="stat-label">Patch & News</span>
            </div>
            <?php endif; ?>
            <?php if ($drafts['tests'] > 0) : ?>
            <div class="stat-card draft-tests">
                <span class="stat-number"><?php echo $drafts['tests']; ?></span>
                <span class="stat-label">Tests</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Section actions rapides -->
    <div class="actions-section">
        <h3>⚡ Actions rapides</h3>
        <div class="actions-grid">
            
            <!-- Fiches de jeu -->
            <div class="action-card card-fiches">
                <div class="action-header">
                    <div class="action-icon">🎮</div>
                    <h4 class="action-title">Fiches de jeu</h4>
                </div>
                <p class="action-description">
                    Créez des fiches détaillées pour présenter les jeux : informations principales, captures d'écran, 
                    caractéristiques techniques et description complète avec sections personnalisées.
                </p>
                <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche'); ?>" class="btn btn-primary">
                        ✨ Nouvelle fiche
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="btn btn-secondary">
                        📋 Gérer les fiches
                    </a>
                </div>
            </div>
            
            <!-- Patch & News -->
            <div class="action-card card-news">
                <div class="action-header">
                    <div class="action-icon">📰</div>
                    <h4 class="action-title">Patch & News</h4>
                </div>
                <p class="action-description">
                    Rédigez rapidement des articles sur les dernières mises à jour, patches et actualités 
                    du monde du gaming avec un template adapté et des sections structurées.
                </p>
                <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-patch-news'); ?>" class="btn btn-primary">
                        ✨ Nouveau patch/news
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-patch-news'); ?>" class="btn btn-secondary">
                        📋 Gérer les articles
                    </a>
                </div>
            </div>
            
            <!-- Tests -->
            <div class="action-card card-tests">
                <div class="action-header">
                    <div class="action-icon">⭐</div>
                    <h4 class="action-title">Tests</h4>
                </div>
                <p class="action-description">
                    Créez des tests complets avec analyse détaillée, points forts/faibles, et verdict 
                    final pour guider vos lecteurs dans leurs choix gaming.
                </p>
                <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-tests'); ?>" class="btn btn-primary">
                        ✨ Nouveau test
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-tests'); ?>" class="btn btn-secondary">
                        📋 Voir la page
                    </a>
                </div>
            </div>
            
            <!-- Configuration -->
            <div class="action-card card-settings">
                <div class="action-header">
                    <div class="action-icon">⚙️</div>
                    <h4 class="action-title">Configuration</h4>
                </div>
                <p class="action-description">
                    Configurez les paramètres SEO, les templates par défaut et les options d'affichage 
                    pour optimiser votre workflow et vos contenus.
                </p>
                <div class="action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-settings'); ?>" class="btn btn-primary">
                        ⚙️ Réglages
                    </a>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>" class="btn btn-secondary">
                        🏷️ Catégories
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Section activité récente -->
    <?php if ($recent_activity->have_posts()) : ?>
    <div class="activity-section">
        <h3>🕒 Activité récente</h3>
        <div class="activity-list">
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
            <div class="activity-item">
                <div class="activity-icon"><?php echo $post_icon; ?></div>
                <div class="activity-content">
                    <div class="activity-main">
                        <h5 class="activity-title">
                            <a href="<?php 
                                if (strpos($post_type, 'Fiche') !== false) {
                                    echo admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id);
                                } elseif (in_array($post_type, array('News', 'Patch'))) {
                                    echo admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id);
                                } else {
                                    echo get_edit_post_link($post_id);
                                }
                            ?>">
                                <?php the_title(); ?>
                            </a>
                        </h5>
                        <div class="activity-meta">
                            <span class="activity-type"><?php echo $post_type; ?></span>
                            <span class="activity-status status-<?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                            <span class="activity-date"><?php echo human_time_diff(get_the_modified_time('U'), current_time('U')) . ' ago'; ?></span>
                        </div>
                    </div>
                    <div class="activity-actions">
                        <a href="<?php 
                            if (strpos($post_type, 'Fiche') !== false) {
                                echo admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id);
                            } elseif (in_array($post_type, array('News', 'Patch'))) {
                                echo admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id);
                            } else {
                                echo get_edit_post_link($post_id);
                            }
                        ?>" class="activity-btn">✏️ Modifier</a>
                        <a href="<?php echo get_permalink($post_id); ?>" target="_blank" class="activity-btn">👁️ Voir</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div class="activity-footer">
            <a href="<?php echo admin_url('admin.php?page=sisme-games-all-articles'); ?>">
                📋 Voir tous les articles
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Section liens utiles -->
    <div class="links-section">
        <h3>🔗 Liens utiles</h3>
        <div class="links-grid">
            <div class="link-group">
                <h5>WordPress</h5>
                <ul>
                    <li><a href="<?php echo admin_url('admin.php?page=sisme-games-all-articles'); ?>">📄 Tous les articles</a></li>
                    <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>">🏷️ Catégories</a></li>
                    <li><a href="<?php echo admin_url('edit-tags.php?taxonomy=post_tag'); ?>">🏷️ Étiquettes</a></li>
                    <li><a href="<?php echo admin_url('upload.php'); ?>">📸 Médiathèque</a></li>
                </ul>
            </div>
            <div class="link-group">
                <h5>Sisme Games</h5>
                <ul>
                    <li><a href="https://games.sisme.fr" target="_blank">🌐 Site games.sisme.fr</a></li>
                    <li><a href="https://sisme.fr" target="_blank">🌐 Site principal</a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=sisme-games-settings'); ?>">⚙️ Réglages du plugin</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php wp_reset_postdata(); ?>