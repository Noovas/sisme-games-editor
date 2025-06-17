<?php
/**
 * File: /sisme-games-editor/admin/pages/all-articles.php
 * Page Tous les articles - Structure cohérente avec les autres listes
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Traitement des actions
if (isset($_GET['action']) && isset($_GET['post_id'])) {
    $action = sanitize_text_field($_GET['action']);
    $post_id = intval($_GET['post_id']);
    
    if ($action === 'publish' && wp_verify_nonce($_GET['_wpnonce'], 'publish_post_' . $post_id)) {
        $result = wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
        
        if (!is_wp_error($result)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Article publié avec succès !</p></div>';
            });
        }
        wp_redirect(admin_url('admin.php?page=sisme-games-all-articles'));
        exit;
        
    } elseif ($action === 'unpublish' && wp_verify_nonce($_GET['_wpnonce'], 'unpublish_post_' . $post_id)) {
        $result = wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
        
        if (!is_wp_error($result)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Article remis en brouillon !</p></div>';
            });
        }
        wp_redirect(admin_url('admin.php?page=sisme-games-all-articles'));
        exit;
        
    } elseif ($action === 'delete' && wp_verify_nonce($_GET['_wpnonce'], 'delete_post_' . $post_id)) {
        $result = wp_delete_post($post_id, true);
        
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Article supprimé !</p></div>';
            });
        }
        wp_redirect(admin_url('admin.php?page=sisme-games-all-articles'));
        exit;
    }
}

// Fonctions utilitaires
function get_all_sisme_categories() {
    $all_categories = get_categories(array('hide_empty' => false));
    $sisme_categories = array();
    
    foreach ($all_categories as $category) {
        if (strpos($category->slug, 'jeux-') === 0 || 
            in_array($category->slug, array('news', 'patch', 'tests'))) {
            $sisme_categories[] = $category->term_id;
        }
    }
    
    return $sisme_categories;
}

function count_articles_by_type() {
    $sisme_categories = get_all_sisme_categories();
    
    if (empty($sisme_categories)) {
        return array('total' => 0, 'fiches' => 0, 'patch_news' => 0, 'tests' => 0, 'published' => 0, 'draft' => 0);
    }
    
    // Total
    $total_query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => array('publish', 'draft', 'private'),
        'category__in' => $sisme_categories,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    // Fiches (catégories jeux-*)
    $fiches_categories = array();
    foreach (get_categories(array('hide_empty' => false)) as $cat) {
        if (strpos($cat->slug, 'jeux-') === 0) {
            $fiches_categories[] = $cat->term_id;
        }
    }
    $fiches_count = 0;
    if (!empty($fiches_categories)) {
        $fiches_query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'category__in' => $fiches_categories,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $fiches_count = $fiches_query->found_posts;
    }
    
    // Patch & News
    $patch_news_categories = array();
    $news_cat = get_category_by_slug('news');
    $patch_cat = get_category_by_slug('patch');
    if ($news_cat) $patch_news_categories[] = $news_cat->term_id;
    if ($patch_cat) $patch_news_categories[] = $patch_cat->term_id;
    
    $patch_news_count = 0;
    if (!empty($patch_news_categories)) {
        $patch_news_query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'category__in' => $patch_news_categories,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $patch_news_count = $patch_news_query->found_posts;
    }
    
    // Tests
    $tests_cat = get_category_by_slug('tests');
    $tests_count = 0;
    if ($tests_cat) {
        $tests_query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'cat' => $tests_cat->term_id,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $tests_count = $tests_query->found_posts;
    }
    
    // Publiés
    $published_query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'category__in' => $sisme_categories,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    // Brouillons
    $draft_query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'draft',
        'category__in' => $sisme_categories,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    return array(
        'total' => $total_query->found_posts,
        'fiches' => $fiches_count,
        'patch_news' => $patch_news_count,
        'tests' => $tests_count,
        'published' => $published_query->found_posts,
        'draft' => $draft_query->found_posts
    );
}

function get_article_type_info($post_id) {
    $categories = get_the_category($post_id);
    
    foreach ($categories as $category) {
        if (strpos($category->slug, 'jeux-') === 0) {
            return array(
                'type' => 'fiche',
                'label' => 'Fiche de jeu',
                'icon' => '🎮',
                'edit_url' => admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id)
            );
        } elseif ($category->slug === 'news') {
            return array(
                'type' => 'news',
                'label' => 'News',
                'icon' => '📰',
                'edit_url' => admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id)
            );
        } elseif ($category->slug === 'patch') {
            return array(
                'type' => 'patch',
                'label' => 'Patch',
                'icon' => '🔧',
                'edit_url' => admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id)
            );
        } elseif ($category->slug === 'tests') {
            return array(
                'type' => 'test',
                'label' => 'Test',
                'icon' => '⭐',
                'edit_url' => admin_url('admin.php?page=sisme-games-tests&post_id=' . $post_id)
            );
        }
    }
    
    return array(
        'type' => 'other',
        'label' => 'Autre',
        'icon' => '📄',
        'edit_url' => get_edit_post_link($post_id)
    );
}

// Récupération des paramètres
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';

// Statistiques
$stats = count_articles_by_type();

// Récupérer les articles
$sisme_categories = get_all_sisme_categories();

if (empty($sisme_categories)) {
    $articles_query = new WP_Query(array('post__in' => array(0)));
    $total_posts = 0;
} else {
    $args = array(
        'post_type' => 'post',
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => 20,
        'paged' => max(1, get_query_var('paged')),
        'category__in' => $sisme_categories,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    // Filtres
    if (!empty($search)) {
        $args['s'] = $search;
    }
    
    if (!empty($filter_type)) {
        switch ($filter_type) {
            case 'fiches':
                $fiches_cats = array();
                foreach (get_categories(array('hide_empty' => false)) as $cat) {
                    if (strpos($cat->slug, 'jeux-') === 0) {
                        $fiches_cats[] = $cat->term_id;
                    }
                }
                if (!empty($fiches_cats)) {
                    $args['category__in'] = $fiches_cats;
                }
                break;
            case 'patch_news':
                $patch_news_cats = array();
                $news_cat = get_category_by_slug('news');
                $patch_cat = get_category_by_slug('patch');
                if ($news_cat) $patch_news_cats[] = $news_cat->term_id;
                if ($patch_cat) $patch_news_cats[] = $patch_cat->term_id;
                if (!empty($patch_news_cats)) {
                    $args['category__in'] = $patch_news_cats;
                }
                break;
            case 'tests':
                $tests_cat = get_category_by_slug('tests');
                if ($tests_cat) {
                    $args['cat'] = $tests_cat->term_id;
                }
                break;
        }
    }
    
    if (!empty($filter_status)) {
        $args['post_status'] = array($filter_status);
    }

    $articles_query = new WP_Query($args);
    $total_posts = $articles_query->found_posts;
}
?>

<div class="wrap">
    <!-- En-tête -->
    <div class="page-header">
        <h1>
            📋 Tous les articles
        </h1>
        <p class="page-subtitle">
            Vue d'ensemble de tous vos contenus gaming créés avec Sisme Games Editor
        </p>
    </div>
    
    <!-- Section statistiques -->
    <div class="stats-section">
        <h3>📊 Statistiques globales</h3>
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
                <span class="stat-number"><?php echo $stats['patch_news']; ?></span>
                <span class="stat-label">Patch & News</span>
            </div>
            <div class="stat-card stat-tests">
                <span class="stat-number"><?php echo $stats['tests']; ?></span>
                <span class="stat-label">Tests</span>
            </div>
            <div class="stat-card stat-published">
                <span class="stat-number"><?php echo $stats['published']; ?></span>
                <span class="stat-label">Publiés</span>
            </div>
            <div class="stat-card stat-draft">
                <span class="stat-number"><?php echo $stats['draft']; ?></span>
                <span class="stat-label">Brouillons</span>
            </div>
        </div>
    </div>
    
    <!-- Section filtres et recherche -->
    <div class="filters-section">
        <form method="get" class="filters-form">
            <input type="hidden" name="page" value="sisme-games-all-articles">
            
            <div class="filters-row">
                <!-- Recherche -->
                <div class="search-container">
                    <input type="search" 
                           name="s" 
                           value="<?php echo esc_attr($search); ?>" 
                           placeholder="Rechercher un article..."
                           class="search-input">
                    <button type="submit" class="search-button">Rechercher</button>
                </div>
                
                <!-- Filtres -->
                <div class="filters-container">
                    <select name="filter_type" class="filter-select">
                        <option value="">Tous les types</option>
                        <option value="fiches" <?php selected($filter_type, 'fiches'); ?>>Fiches de jeu</option>
                        <option value="patch_news" <?php selected($filter_type, 'patch_news'); ?>>Patch & News</option>
                        <option value="tests" <?php selected($filter_type, 'tests'); ?>>Tests</option>
                    </select>
                    
                    <select name="filter_status" class="filter-select">
                        <option value="">Tous les statuts</option>
                        <option value="publish" <?php selected($filter_status, 'publish'); ?>>Publiés</option>
                        <option value="draft" <?php selected($filter_status, 'draft'); ?>>Brouillons</option>
                        <option value="private" <?php selected($filter_status, 'private'); ?>>Privés</option>
                    </select>
                    
                    <button type="submit" class="filter-button">Filtrer</button>
                    
                    <?php if (!empty($search) || !empty($filter_type) || !empty($filter_status)) : ?>
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-all-articles'); ?>" class="reset-filters">
                            ❌ Reset
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
    
    <?php if (!empty($search) || !empty($filter_type) || !empty($filter_status)) : ?>
        <div class="search-notice">
            <p>
                <?php if (!empty($search)) : ?>
                    Résultats pour : <strong><?php echo esc_html($search); ?></strong>
                <?php endif; ?>
                <?php if (!empty($filter_type) || !empty($filter_status)) : ?>
                    <?php if (!empty($search)) echo ' • '; ?>
                    Filtres actifs
                <?php endif; ?>
                (<a href="<?php echo admin_url('admin.php?page=sisme-games-all-articles'); ?>">voir tous</a>)
            </p>
        </div>
    <?php endif; ?>
    
    <?php if ($articles_query->have_posts()) : ?>
        
        <!-- Info nombre d'éléments -->
        <div class="items-count">
            <span><?php echo $total_posts; ?> élément<?php echo $total_posts > 1 ? 's' : ''; ?></span>
        </div>
        
        <!-- En-têtes du tableau -->
        <div class="table-header">
            <div class="header-row">
                <div class="header-image">Image</div>
                <div class="header-content">
                    <div class="header-col title-col">Titre</div>
                    <div class="header-col type-col">Type</div>
                    <div class="header-col category-col">Catégorie/Jeu</div>
                    <div class="header-col status-col">Statut</div>
                    <div class="header-col date-col">Date</div>
                </div>
            </div>
        </div>
        
        <!-- Articles groupés avec flexbox -->
        <div class="articles-container">
            <?php while ($articles_query->have_posts()) : $articles_query->the_post(); 
                $post_id = get_the_ID();
                $status = get_post_status();
                $article_info = get_article_type_info($post_id);
                
                $status_labels = array(
                    'publish' => 'Publié',
                    'draft' => 'Brouillon',
                    'private' => 'Privé'
                );
                
                // Catégorie ou jeu associé
                $category_info = '';
                if ($article_info['type'] === 'fiche') {
                    $categories = get_the_category($post_id);
                    foreach ($categories as $category) {
                        if (strpos($category->slug, 'jeux-') === 0) {
                            $category_info = str_replace('jeux-', '', $category->name);
                            break;
                        }
                    }
                } else {
                    // Pour patch/news/tests, récupérer le jeu via les tags
                    $tags = get_the_tags($post_id);
                    if ($tags) {
                        $category_info = '🎮 ' . $tags[0]->name;
                    }
                }
            ?>
                <!-- Article avec image à gauche -->
                <div class="article-item">
                    <!-- Image à gauche -->
                    <div class="article-image">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php echo get_the_post_thumbnail($post_id, 'medium', array('class' => 'article-thumb')); ?>
                        <?php else : ?>
                            <div class="no-image"><?php echo $article_info['icon']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contenu à droite -->
                    <div class="article-content">
                        <!-- Ligne de données -->
                        <div class="article-data">
                            <div class="data-col title-col">
                                <h4 class="article-title">
                                    <a href="<?php echo $article_info['edit_url']; ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h4>
                            </div>
                            
                            <div class="data-col type-col">
                                <span class="type-badge <?php echo $article_info['type']; ?>-badge">
                                    <?php echo $article_info['icon']; ?> <?php echo $article_info['label']; ?>
                                </span>
                            </div>
                            
                            <div class="data-col category-col">
                                <?php if ($category_info) : ?>
                                    <span class="category-info"><?php echo esc_html($category_info); ?></span>
                                <?php else : ?>
                                    <span class="no-category">—</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="data-col status-col">
                                <span class="status-badge status-<?php echo $status; ?>">
                                    <?php echo isset($status_labels[$status]) ? $status_labels[$status] : ucfirst($status); ?>
                                </span>
                            </div>
                            
                            <div class="data-col date-col">
                                <div class="date-info">
                                    <span class="date"><?php echo get_the_date('j M Y'); ?></span>
                                    <span class="time"><?php echo get_the_date('H:i'); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Ligne d'actions -->
                        <div class="article-actions">
                            <a href="<?php echo $article_info['edit_url']; ?>" 
                               class="action-btn edit-btn">✏️ Modifier</a>
                            
                            <?php if ($status === 'publish') : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-all-articles&action=unpublish&post_id=' . $post_id), 'unpublish_post_' . $post_id); ?>" 
                                   class="action-btn draft-btn">📝 Brouillon</a>
                            <?php else : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-all-articles&action=publish&post_id=' . $post_id), 'publish_post_' . $post_id); ?>" 
                                   class="action-btn publish-btn">✅ Publier</a>
                            <?php endif; ?>
                            
                            <a href="<?php echo get_permalink($post_id); ?>" 
                               target="_blank" 
                               class="action-btn view-btn">👁️ Voir</a>
                            
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-all-articles&action=delete&post_id=' . $post_id), 'delete_post_' . $post_id); ?>" 
                               onclick="return confirm('Supprimer cet article ?');"
                               class="action-btn delete-btn">🗑️ Supprimer</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($articles_query->max_num_pages > 1) : ?>
            <div class="pagination-section">
                <?php
                $pagination_args = array(
                    'total' => $articles_query->max_num_pages,
                    'current' => max(1, get_query_var('paged')),
                    'format' => '?paged=%#%&s=' . urlencode($search) . '&filter_type=' . urlencode($filter_type) . '&filter_status=' . urlencode($filter_status),
                    'prev_text' => '‹ Précédent',
                    'next_text' => 'Suivant ›'
                );
                echo paginate_links($pagination_args);
                ?>
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        
        <!-- État vide -->
        <div class="empty-state">
            <div class="empty-icon">📋</div>
            <h3>Aucun article trouvé</h3>
            
            <?php if (!empty($search) || !empty($filter_type) || !empty($filter_status)) : ?>
                <p>Aucun résultat avec les critères actuels</p>
                <a href="<?php echo admin_url('admin.php?page=sisme-games-all-articles'); ?>" class="button">
                    Voir tous les articles
                </a>
            <?php elseif (empty($sisme_categories)) : ?>
                <p>Aucune catégorie Sisme Games trouvée</p>
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>" class="button">
                    Gérer les catégories
                </a>
            <?php else : ?>
                <p>Commencez par créer votre premier contenu !</p>
                <a href="<?php echo admin_url('admin.php?page=sisme-games-editor'); ?>" class="button button-primary">
                    Retour au dashboard
                </a>
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
</div>

<?php wp_reset_postdata(); ?>