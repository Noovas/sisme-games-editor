<?php
/**
 * File: /sisme-games-editor/admin/pages/all-articles.php
 * Page Tous les articles - Avec scroll infini
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
    foreach (get_categories(array('hide_empty' => false)) as $category) {
        if (strpos($category->slug, 'jeux-') === 0) {
            $fiches_categories[] = $category->term_id;
        }
    }
    
    $fiches_query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => array('publish', 'draft', 'private'),
        'category__in' => $fiches_categories,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    // Patch & News
    $patch_news_cats = array();
    $news_cat = get_category_by_slug('news');
    $patch_cat = get_category_by_slug('patch');
    if ($news_cat) $patch_news_cats[] = $news_cat->term_id;
    if ($patch_cat) $patch_news_cats[] = $patch_cat->term_id;
    
    $patch_news_query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => array('publish', 'draft', 'private'),
        'category__in' => $patch_news_cats,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    // Tests
    $tests_cat = get_category_by_slug('tests');
    $tests_query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => array('publish', 'draft', 'private'),
        'category__in' => $tests_cat ? array($tests_cat->term_id) : array(),
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
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
        'fiches' => $fiches_query->found_posts,
        'patch_news' => $patch_news_query->found_posts,
        'tests' => $tests_query->found_posts,
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
                'icon' => '🎮',
                'label' => 'Fiche',
                'edit_url' => admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id)
            );
        } elseif ($category->slug === 'news') {
            return array(
                'type' => 'news',
                'icon' => '📰',
                'label' => 'News',
                'edit_url' => admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id)
            );
        } elseif ($category->slug === 'patch') {
            return array(
                'type' => 'patch',
                'icon' => '🔧',
                'label' => 'Patch',
                'edit_url' => admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id)
            );
        } elseif ($category->slug === 'tests') {
            return array(
                'type' => 'tests',
                'icon' => '⭐',
                'label' => 'Test',
                'edit_url' => admin_url('admin.php?page=sisme-games-tests&post_id=' . $post_id)
            );
        }
    }
    
    return array(
        'type' => 'other',
        'icon' => '📄',
        'label' => 'Article',
        'edit_url' => get_edit_post_link($post_id)
    );
}

// Récupérer les statistiques
$stats = count_articles_by_type();

// Paramètres de recherche et filtre
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';

// Configuration de la requête pour le premier chargement (10 articles)
$sisme_categories = get_all_sisme_categories();
$args = array(
    'post_type' => 'post',
    'post_status' => array('publish', 'draft', 'private'),
    'posts_per_page' => 10, // Premier chargement : 10 articles
    'paged' => 1,
    'category__in' => $sisme_categories,
    'orderby' => 'date',
    'order' => 'DESC'
);

// Appliquer les filtres
if (!empty($search)) {
    $args['s'] = $search;
}

if (!empty($filter_type)) {
    switch ($filter_type) {
        case 'fiches':
            $fiches_categories = array();
            foreach (get_categories(array('hide_empty' => false)) as $category) {
                if (strpos($category->slug, 'jeux-') === 0) {
                    $fiches_categories[] = $category->term_id;
                }
            }
            if (!empty($fiches_categories)) {
                $args['category__in'] = $fiches_categories;
            }
            break;
        case 'news':
            $news_cat = get_category_by_slug('news');
            if ($news_cat) {
                $args['cat'] = $news_cat->term_id;
            }
            break;
        case 'patch':
            $patch_cat = get_category_by_slug('patch');
            if ($patch_cat) {
                $args['cat'] = $patch_cat->term_id;
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
$max_pages = $articles_query->max_num_pages;
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
    
    <!-- Section recherche et filtres -->
    <div class="search-filters-section">
        <h3>🔍 Recherche et filtres</h3>
        <form method="GET" id="articles-filter-form">
            <input type="hidden" name="page" value="sisme-games-all-articles">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="search-input">Rechercher :</label>
                    <input type="text" 
                           id="search-input" 
                           name="s" 
                           value="<?php echo esc_attr($search); ?>" 
                           placeholder="Titre, contenu...">
                </div>
                
                <div class="filter-group">
                    <label for="filter-type">Type :</label>
                    <select name="filter_type" id="filter-type">
                        <option value="">Tous les types</option>
                        <option value="fiches" <?php selected($filter_type, 'fiches'); ?>>Fiches de jeu</option>
                        <option value="news" <?php selected($filter_type, 'news'); ?>>News</option>
                        <option value="patch" <?php selected($filter_type, 'patch'); ?>>Patch</option>
                        <option value="tests" <?php selected($filter_type, 'tests'); ?>>Tests</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filter-status">Statut :</label>
                    <select name="filter_status" id="filter-status">
                        <option value="">Tous les statuts</option>
                        <option value="publish" <?php selected($filter_status, 'publish'); ?>>Publié</option>
                        <option value="draft" <?php selected($filter_status, 'draft'); ?>>Brouillon</option>
                        <option value="private" <?php selected($filter_status, 'private'); ?>>Privé</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">🔍 Filtrer</button>
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-all-articles'); ?>" 
                       class="btn btn-secondary">🔄 Réinitialiser</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Section des articles -->
    <div class="articles-table-section">
        <h3>📋 Articles 
            <span class="results-count"><?php echo $total_posts; ?> résultat<?php echo $total_posts > 1 ? 's' : ''; ?></span>
        </h3>
        
        <?php if ($articles_query->have_posts()) : ?>
            
            <!-- En-têtes du tableau -->
            <div class="table-header">
                <div class="header-row">
                    <div class="header-image">Image</div>
                    <div class="header-content">
                        <div class="header-col title-col">Titre</div>
                        <div class="header-col type-col">Type</div>
                        <div class="header-col status-col">Statut</div>
                        <div class="header-col date-col">Date</div>
                    </div>
                </div>
            </div>
            
            <!-- Container des articles -->
            <div class="articles-container" id="articles-container">
                <?php 
                while ($articles_query->have_posts()) : 
                    $articles_query->the_post(); 
                    $post_id = get_the_ID();
                    $status = get_post_status();
                    $article_info = get_article_type_info($post_id);
                    
                    $status_labels = array(
                        'publish' => 'Publié',
                        'draft' => 'Brouillon',
                        'private' => 'Privé'
                    );
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
            
            <!-- Indicateur de chargement et scroll infini -->
            <?php if ($max_pages > 1) : ?>
                <div class="infinite-scroll-loader" id="infinite-loader" style="display: none;">
                    <div class="loader-spinner">🔄</div>
                    <p>Chargement des articles suivants...</p>
                </div>
                
                <div class="infinite-scroll-end" id="infinite-end" style="display: none;">
                    <p>📋 Tous les articles ont été chargés</p>
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
</div>

<!-- JavaScript pour le scroll infini -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentPage = 1;
    const maxPages = <?php echo $max_pages; ?>;
    let loading = false;
    const container = document.getElementById('articles-container');
    const loader = document.getElementById('infinite-loader');
    const endIndicator = document.getElementById('infinite-end');
    
    // Paramètres de filtrage actuels
    const filterParams = {
        search: '<?php echo esc_js($search); ?>',
        filter_type: '<?php echo esc_js($filter_type); ?>',
        filter_status: '<?php echo esc_js($filter_status); ?>'
    };
    
    function loadMoreArticles() {
        if (loading || currentPage >= maxPages) return;
        
        loading = true;
        currentPage++;
        loader.style.display = 'block';
        
        // Construire les paramètres
        const params = new URLSearchParams({
            action: 'sisme_load_more_articles',
            page_num: currentPage,
            search: filterParams.search,
            filter_type: filterParams.filter_type,
            filter_status: filterParams.filter_status,
            nonce: '<?php echo wp_create_nonce('sisme_load_more'); ?>'
        });
        
        // Utiliser admin-ajax.php directement
        const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
        
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.html) {
                container.insertAdjacentHTML('beforeend', data.data.html);
            }
            
            loader.style.display = 'none';
            loading = false;
            
            // Si on a atteint la fin
            if (currentPage >= maxPages) {
                endIndicator.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Erreur de chargement:', error);
            loader.style.display = 'none';
            loading = false;
        });
    }
    
    // Détecter le scroll
    function handleScroll() {
        if (loading || currentPage >= maxPages) return;
        
        const scrollPosition = window.innerHeight + window.scrollY;
        const documentHeight = document.documentElement.offsetHeight;
        
        // Charger quand on est à 200px du bas
        if (scrollPosition >= documentHeight - 200) {
            loadMoreArticles();
        }
    }
    
    // Écouter le scroll seulement s'il y a plus d'une page
    if (maxPages > 1) {
        window.addEventListener('scroll', handleScroll);
        
        // Debugging : afficher les informations de scroll
        console.log('Scroll infini activé - Pages totales:', maxPages);
    }
});
</script>

<?php wp_reset_postdata(); ?>