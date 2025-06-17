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
?>

<div class="wrap">
    <h1>
        Patch & News
        <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-patch-news'); ?>" class="page-title-action">
            Ajouter un nouvel article
        </a>
    </h1>
    
    <?php if (!empty($search)) : ?>
        <div class="notice notice-info">
            <p>Résultats de recherche pour : <strong><?php echo esc_html($search); ?></strong> 
            (<a href="<?php echo admin_url('admin.php?page=sisme-games-patch-news'); ?>">voir tous les articles</a>)</p>
        </div>
    <?php endif; ?>
    
    <!-- Barre de recherche -->
    <form method="get" class="search-form">
        <input type="hidden" name="page" value="sisme-games-patch-news">
        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input">Rechercher des articles :</label>
            <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($search); ?>">
            <input type="submit" id="search-submit" class="button" value="Rechercher">
        </p>
    </form>
    
    <?php if ($articles_query->have_posts()) : ?>
        
        <div class="tablenav top">
            <div class="alignleft">
                <span class="displaying-num"><?php echo $total_posts; ?> élément<?php echo $total_posts > 1 ? 's' : ''; ?></span>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th scope="col" class="manage-column">Titre</th>
                    <th scope="col" class="manage-column">Type</th>
                    <th scope="col" class="manage-column">Jeu associé</th>
                    <th scope="col" class="manage-column">Statut</th>
                    <th scope="col" class="manage-column">Date</th>
                    <th scope="col" class="manage-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($articles_query->have_posts()) : $articles_query->the_post(); 
                    $post_id = get_the_ID();
                    $categories = get_the_category();
                    $status = get_post_status();
                    
                    // Déterminer le type d'article
                    $article_type = 'News';
                    foreach ($categories as $category) {
                        if (strpos(strtolower($category->slug), 'patch') !== false) {
                            $article_type = 'Patch';
                            break;
                        }
                    }
                    
                    // Récupérer le jeu associé via les tags
                    $tags = get_the_tags();
                    $associated_game = '';
                    if ($tags) {
                        $associated_game = $tags[0]->name; // Premier tag = jeu associé généralement
                    }
                    
                    $status_labels = array(
                        'publish' => 'Publié',
                        'draft' => 'Brouillon',
                        'private' => 'Privé'
                    );
                ?>
                    <tr>
                        <td class="title column-title">
                            <strong>
                                <a href="<?php echo get_edit_post_link($post_id); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </strong>
                            <?php if (has_post_thumbnail()) : ?>
                                <span style="color: #666;"> — 📷 Image</span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="type column-type">
                            <span style="background: <?php echo $article_type === 'Patch' ? '#D4A373' : '#A1B78D'; ?>; color: white; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                                <?php echo esc_html($article_type); ?>
                            </span>
                        </td>
                        
                        <td class="game column-game">
                            <?php if ($associated_game) : ?>
                                <span style="background: #f1f1f1; padding: 2px 6px; border-radius: 3px; margin-right: 5px; font-size: 12px;">
                                    🎮 <?php echo esc_html($associated_game); ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #999; font-style: italic;">Aucun jeu associé</span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="status column-status">
                            <span style="color: <?php echo $status === 'publish' ? 'green' : ($status === 'draft' ? 'orange' : 'blue'); ?>;">
                                <?php echo $status_labels[$status] ?? ucfirst($status); ?>
                            </span>
                        </td>
                        
                        <td class="date column-date">
                            <?php echo get_the_date('j M Y'); ?><br>
                            <small><?php echo get_the_date('H:i'); ?></small>
                        </td>
                        
                        <td class="actions column-actions">
                            <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id); ?>" class="button button-small">
                                Modifier
                            </a>
                            
                            <!-- Boutons fonctionnels -->
                            <?php if ($status === 'draft') : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-patch-news&action=publish&post_id=' . $post_id), 'publish_post_' . $post_id); ?>" 
                                   class="button button-small button-primary" 
                                   onclick="return confirm('Publier cet article ?')"
                                   style="background: #00a32a; border-color: #00a32a; margin-left: 5px;">
                                    📢 Publier
                                </a>
                            <?php elseif ($status === 'publish') : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-patch-news&action=unpublish&post_id=' . $post_id), 'unpublish_post_' . $post_id); ?>" 
                                   class="button button-small" 
                                   onclick="return confirm('Remettre en brouillon ?')"
                                   style="background: #dba617; border-color: #dba617; color: white; margin-left: 5px;">
                                    📝 Brouillon
                                </a>
                            <?php endif; ?>
                            
                            <!-- Bouton Site (fonctionnel) -->
                            <a href="<?php echo get_permalink($post_id); ?>" target="_blank" class="button button-small" style="margin-left: 5px;">
                                Site
                            </a>
                            
                            <!-- Bouton Supprimer (fonctionnel) -->
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-patch-news&action=delete&post_id=' . $post_id), 'delete_post_' . $post_id); ?>" 
                               onclick="return confirm('⚠️ Supprimer définitivement cet article ?')" 
                               class="button button-small" 
                               style="color: #a00; border-color: #a00; margin-left: 5px;">
                                🗑️ Supprimer
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($articles_query->max_num_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
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
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        
        <div style="text-align: center; padding: 50px 0;">
            <h2>Aucun article Patch & News trouvé</h2>
            
            <?php if (!empty($search)) : ?>
                <p>Aucun résultat pour "<?php echo esc_html($search); ?>". Essayez avec d'autres mots-clés.</p>
                <a href="<?php echo admin_url('admin.php?page=sisme-games-patch-news'); ?>" class="button">
                    Voir tous les articles
                </a>
            <?php elseif (empty($category_ids)) : ?>
                <p>Aucune catégorie "news" ou "patch" trouvée.</p>
                <p>Créez d'abord ces catégories dans <a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>">Articles > Catégories</a></p>
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 20px; border-radius: 5px; margin: 30px auto; max-width: 600px;">
                    <h3 style="margin-top: 0;">💡 Configuration nécessaire</h3>
                    <p>Pour utiliser cette section, créez les catégories suivantes :</p>
                    <ul style="text-align: left;">
                        <li><strong>news</strong> - pour les actualités gaming</li>
                        <li><strong>patch</strong> - pour les notes de patch</li>
                    </ul>
                </div>
            <?php else : ?>
                <p>Commencez par créer vos premiers articles de patch et news !</p>
                <p style="color: #666; margin-top: 20px;">
                    <em>La fonctionnalité de création sera bientôt disponible dans cette interface.</em>
                </p>
                
                <div style="background: #f0f8ff; border: 1px solid #b3d9ff; padding: 20px; border-radius: 5px; margin: 30px auto; max-width: 600px;">
                    <h3 style="margin-top: 0;">🔄 En attendant</h3>
                    <p>Vous pouvez créer vos articles de patch et news via l'éditeur WordPress classique :</p>
                    <ol style="text-align: left;">
                        <li>Allez dans <a href="<?php echo admin_url('post-new.php'); ?>">Articles > Ajouter</a></li>
                        <li>Rédigez votre article</li>
                        <li>Assignez la catégorie "news" ou "patch"</li>
                        <li>Ajoutez l'étiquette du jeu concerné</li>
                    </ol>
                </div>
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
    
    <!-- Statistiques en bas -->
    <div style="background: #f8f9fa; padding: 20px; margin: 30px 0; border: 1px solid #dee2e6; border-radius: 5px;">
        <h3 style="margin-top: 0; color: #495057;">
            📊 Statistiques Patch & News
        </h3>
        <div style="display: flex; gap: 20px;">
            <div style="background: white; padding: 15px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 120px;">
                <h4 style="margin: 0; font-size: 24px; color: #A1B78D;"><?php echo count_news_patch_articles(); ?></h4>
                <p style="margin: 5px 0 0 0; font-size: 14px;">Total articles</p>
            </div>
            <div style="background: white; padding: 15px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 120px;">
                <h4 style="margin: 0; font-size: 24px; color: #D4A373;"><?php echo $articles_query->found_posts; ?></h4>
                <p style="margin: 5px 0 0 0; font-size: 14px;">Affichés</p>
            </div>
        </div>
    </div>
</div>

<?php wp_reset_postdata(); ?>