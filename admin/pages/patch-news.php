<?php
/**
 * File: /sisme-games-editor/admin/pages/patch-news.php
 * Page Patch & News - Version avec liste des articles
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Traitement des actions de publication/suppression
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
        wp_redirect(admin_url('admin.php?page=sisme-games-patch-news'));
        exit;
        
    } elseif ($action === 'unpublish' && wp_verify_nonce($_GET['_wpnonce'], 'unpublish_post_' . $post_id)) {
        $result = wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
        
        if (!is_wp_error($result)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Article remis en brouillon !</p></div>';
            });
        }
        wp_redirect(admin_url('admin.php?page=sisme-games-patch-news'));
        exit;
        
    } elseif ($action === 'delete' && wp_verify_nonce($_GET['_wpnonce'], 'delete_post_' . $post_id)) {
        $result = wp_delete_post($post_id, true);
        
        if ($result) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Article supprimé !</p></div>';
            });
        }
        wp_redirect(admin_url('admin.php?page=sisme-games-patch-news'));
        exit;
    }
}

// Fonction pour compter les articles news/patch
function count_news_patch_articles() {
    $news_category = get_category_by_slug('news');
    $patch_category = get_category_by_slug('patch');
    
    $category_ids = array();
    if ($news_category) $category_ids[] = $news_category->term_id;
    if ($patch_category) $category_ids[] = $patch_category->term_id;
    
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

// Récupération des paramètres
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Récupérer les articles Patch & News
$news_category = get_category_by_slug('news');
$patch_category = get_category_by_slug('patch');

$category_ids = array();
if ($news_category) $category_ids[] = $news_category->term_id;
if ($patch_category) $category_ids[] = $patch_category->term_id;

if (empty($category_ids)) {
    $articles_query = new WP_Query(array('post__in' => array(0))); // Requête vide
    $total_posts = 0;
} else {
    $args = array(
        'post_type' => 'post',
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => -1,
        'paged' => max(1, get_query_var('paged')),
        'category__in' => $category_ids,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    if (!empty($search)) {
        $args['s'] = $search;
    }

    $articles_query = new WP_Query($args);
    $total_posts = $articles_query->found_posts;
}

$news_count = 0;
$patch_count = 0;

if (!empty($category_ids)) {
    $news_category = get_category_by_slug('news');
    $patch_category = get_category_by_slug('patch');
    
    if ($news_category) {
        $news_query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => -1,
            'cat' => $news_category->term_id,
            'fields' => 'ids'
        ));
        $news_count = $news_query->found_posts;
    }
    
    if ($patch_category) {
        $patch_query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => -1,
            'cat' => $patch_category->term_id,
            'fields' => 'ids'
        ));
        $patch_count = $patch_query->found_posts;
    }
}
?>

<div class="wrap">
    <!-- En-tête -->
    <div class="page-header">
        <h1>
            📰 Patch & News
            <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-patch-news'); ?>" class="page-title-action">
                ➕ Ajouter un nouvel article
            </a>
        </h1>
    </div>
    
    <!-- Section statistiques -->
    <div class="stats-section">
        <h3>📊 Statistiques Patch & News</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo count_news_patch_articles(); ?></span>
                <span class="stat-label">Total</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $news_count; ?></span>
                <span class="stat-label">News</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $patch_count; ?></span>
                <span class="stat-label">Patches</span>
            </div>
        </div>
    </div>
    
    <!-- Barre de recherche -->
    <div class="search-section">
        <form method="get" class="search-form">
            <input type="hidden" name="page" value="sisme-games-patch-news">
            <div class="search-container">
                <input type="search" 
                       name="s" 
                       value="<?php echo esc_attr($search); ?>" 
                       placeholder="Rechercher un article..."
                       class="search-input">
                <button type="submit" class="search-button">Rechercher</button>
            </div>
        </form>
    </div>
    
    <?php if (!empty($search)) : ?>
        <div class="search-notice">
            <p>Résultats pour : <strong><?php echo esc_html($search); ?></strong> 
            (<a href="<?php echo admin_url('admin.php?page=sisme-games-patch-news'); ?>">voir tous</a>)</p>
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
                    <div class="header-col game-col">Jeu associé</div>
                    <div class="header-col status-col">Statut</div>
                    <div class="header-col date-col">Date</div>
                </div>
            </div>
        </div>
        
        <!-- Articles groupés avec flexbox -->
        <div class="articles-container">
            <?php while ($articles_query->have_posts()) : $articles_query->the_post(); 
                $post_id = get_the_ID();
                $categories = get_the_category();
                $status = get_post_status();
                
                // Déterminer le type
                $article_type = 'news';
                $type_label = 'News';
                foreach ($categories as $category) {
                    if (strpos(strtolower($category->slug), 'patch') !== false) {
                        $article_type = 'patch';
                        $type_label = 'Patch';
                        break;
                    }
                }
                
                // Jeu associé
                $tags = get_the_tags();
                $associated_game = $tags ? $tags[0]->name : '';
                
                $status_labels = array(
                    'publish' => 'Publié',
                    'draft' => 'Brouillon',
                    'private' => 'Privé'
                );
            ?>
                <!-- Article avec image à gauche -->
                <div class="article-item">
                    <!-- Image à gauche (enjambe les 2 lignes) -->
                    <div class="article-image">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php echo get_the_post_thumbnail($post_id, 'medium', array('class' => 'article-thumb')); ?>
                        <?php else : ?>
                            <div class="no-image">📰</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contenu à droite (données + actions) -->
                    <div class="article-content">
                        <!-- Ligne de données -->
                        <div class="article-data">
                            <div class="data-col title-col">
                                <h4 class="article-title">
                                    <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h4>
                            </div>
                            
                            <div class="data-col type-col">
                                <span class="type-badge <?php echo $article_type; ?>-badge">
                                    <?php echo $type_label; ?>
                                </span>
                            </div>
                            
                            <div class="data-col game-col">
                                <?php if ($associated_game) : ?>
                                    <span class="game-name">🎮 <?php echo esc_html($associated_game); ?></span>
                                <?php else : ?>
                                    <span class="no-game">—</span>
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
                            <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id); ?>" 
                               class="action-btn edit-btn">✏️ Modifier</a>
                            
                            <?php if ($status === 'publish') : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-patch-news&action=unpublish&post_id=' . $post_id), 'unpublish_post_' . $post_id); ?>" 
                                   class="action-btn draft-btn">📝 Brouillon</a>
                            <?php else : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-patch-news&action=publish&post_id=' . $post_id), 'publish_post_' . $post_id); ?>" 
                                   class="action-btn publish-btn">✅ Publier</a>
                            <?php endif; ?>
                            
                            <a href="<?php echo get_permalink($post_id); ?>" 
                               target="_blank" 
                               class="action-btn view-btn">👁️ Voir</a>
                            
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-patch-news&action=delete&post_id=' . $post_id), 'delete_post_' . $post_id); ?>" 
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
                    'format' => '?paged=%#%&s=' . urlencode($search),
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
            <div class="empty-icon">📰</div>
            <h3>Aucun article trouvé</h3>
            
            <?php if (!empty($search)) : ?>
                <p>Aucun résultat pour "<?php echo esc_html($search); ?>"</p>
                <a href="<?php echo admin_url('admin.php?page=sisme-games-patch-news'); ?>" class="button">
                    Voir tous les articles
                </a>
            <?php elseif (empty($category_ids)) : ?>
                <p>Créez d'abord les catégories "news" et "patch"</p>
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>" class="button">
                    Gérer les catégories
                </a>
            <?php else : ?>
                <p>Commencez par créer votre premier article !</p>
                <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-patch-news'); ?>" class="button button-primary">
                    Créer un article
                </a>
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
</div>

<?php wp_reset_postdata(); ?>