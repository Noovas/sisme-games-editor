<?php
/**
 * File: /sisme-games-editor/admin/lists/fiches-list.php
 * Liste des fiches de jeu
 */

if (!defined('ABSPATH')) {
    exit;
}

// Traitement des actions de publication
if (isset($_GET['action']) && isset($_GET['post_id'])) {
    $action = sanitize_text_field($_GET['action']);
    $post_id = intval($_GET['post_id']);
    
    if ($action === 'publish' && wp_verify_nonce($_GET['_wpnonce'], 'publish_post_' . $post_id)) {
        // Publier l'article
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'publish'
        ));
        
        if (!is_wp_error($result)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Fiche publiée avec succès !</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error is-dismissible"><p>Erreur lors de la publication : ' . esc_html($result->get_error_message()) . '</p></div>';
            });
        }
        
        // Rediriger pour éviter la double soumission
        wp_redirect(admin_url('admin.php?page=sisme-games-fiches'));
        exit;
        
    } elseif ($action === 'unpublish' && wp_verify_nonce($_GET['_wpnonce'], 'unpublish_post_' . $post_id)) {
        // Remettre en brouillon
        $result = wp_update_post(array(
            'ID' => $post_id,
            'post_status' => 'draft'
        ));
        
        if (!is_wp_error($result)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Fiche remise en brouillon avec succès !</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error is-dismissible"><p>Erreur lors de la modification : ' . esc_html($result->get_error_message()) . '</p></div>';
            });
        }
        
        // Rediriger pour éviter la double soumission
        wp_redirect(admin_url('admin.php?page=sisme-games-fiches'));
        exit;
    }
}

// Récupération des paramètres
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Fonction pour obtenir les catégories jeux
function sisme_get_jeux_category_ids() {
    $all_categories = get_categories(array('hide_empty' => false));
    $category_ids = array();
    
    foreach ($all_categories as $category) {
        if (strpos($category->slug, 'jeux-') === 0) {
            $category_ids[] = $category->term_id;
        }
    }
    
    return $category_ids;
}

// Récupérer les fiches de jeux
$category_ids = sisme_get_jeux_category_ids();

if (empty($category_ids)) {
    $fiches_query = new WP_Query(array('post__in' => array(0))); // Requête vide
    $total_posts = 0;
} else {
    $args = array(
        'post_type' => 'post',
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => 20,
        'paged' => max(1, get_query_var('paged')),
        'category__in' => $category_ids,
        'orderby' => 'date',
        'order' => 'DESC'
    );

    if (!empty($search)) {
        $args['s'] = $search;
    }

    $fiches_query = new WP_Query($args);
    $total_posts = $fiches_query->found_posts;
}
?>

<div class="wrap">
    <h1>
        Fiches de jeu
        <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche'); ?>" class="page-title-action">
            Ajouter une nouvelle fiche
        </a>
    </h1>
    
    <?php if (!empty($search)) : ?>
        <div class="notice notice-info">
            <p>Résultats de recherche pour : <strong><?php echo esc_html($search); ?></strong> 
            (<a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>">voir toutes les fiches</a>)</p>
        </div>
    <?php endif; ?>
    
    <!-- Barre de recherche -->
    <form method="get" class="search-form">
        <input type="hidden" name="page" value="sisme-games-fiches">
        <p class="search-box">
            <label class="screen-reader-text" for="post-search-input">Rechercher des fiches :</label>
            <input type="search" id="post-search-input" name="s" value="<?php echo esc_attr($search); ?>">
            <input type="submit" id="search-submit" class="button" value="Rechercher">
        </p>
    </form>
    
    <?php if ($fiches_query->have_posts()) : ?>
        
        <div class="tablenav top">
            <div class="alignleft">
                <span class="displaying-num"><?php echo $total_posts; ?> élément<?php echo $total_posts > 1 ? 's' : ''; ?></span>
            </div>
        </div>
        
        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th scope="col" class="manage-column">Titre</th>
                    <th scope="col" class="manage-column">Catégorie</th>
                    <th scope="col" class="manage-column">Statut</th>
                    <th scope="col" class="manage-column">Date</th>
                    <th scope="col" class="manage-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($fiches_query->have_posts()) : $fiches_query->the_post(); 
                    $post_id = get_the_ID();
                    $categories = get_the_category();
                    $status = get_post_status();
                    
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
                        
                        <td class="categories column-categories">
                            <?php if ($categories) : ?>
                                <?php foreach ($categories as $category) : ?>
                                    <span style="background: #f1f1f1; padding: 2px 6px; border-radius: 3px; margin-right: 5px; font-size: 12px;">
                                        <?php echo esc_html(str_replace('jeux-', '', $category->name)); ?>
                                    </span>
                                <?php endforeach; ?>
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
                            <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id); ?>" class="button button-small">
                                Modifier
                            </a>
                            
                            <?php if ($status === 'draft') : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-fiches&action=publish&post_id=' . $post_id), 'publish_post_' . $post_id); ?>" 
                                   class="button button-small button-primary" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir publier cette fiche ?')"
                                   style="background: #00a32a; border-color: #00a32a;">
                                    📢 Publier
                                </a>
                            <?php elseif ($status === 'publish') : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-fiches&action=unpublish&post_id=' . $post_id), 'unpublish_post_' . $post_id); ?>" 
                                   class="button button-small" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir remettre cette fiche en brouillon ?')"
                                   style="background: #dba617; border-color: #dba617; color: white;">
                                    📝 Brouillon
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?php echo get_permalink($post_id); ?>" target="_blank" class="button button-small">
                                Site
                            </a>
                            <a href="<?php echo get_delete_post_link($post_id); ?>" 
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette fiche ?')" 
                               class="button button-small" style="color: #a00;">
                                Supprimer
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <?php if ($fiches_query->max_num_pages > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $pagination_args = array(
                        'total' => $fiches_query->max_num_pages,
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
            <h2>Aucune fiche de jeu trouvée</h2>
            
            <?php if (!empty($search)) : ?>
                <p>Aucun résultat pour "<?php echo esc_html($search); ?>". Essayez avec d'autres mots-clés.</p>
                <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="button">
                    Voir toutes les fiches
                </a>
            <?php elseif (empty($category_ids)) : ?>
                <p>Aucune catégorie "jeux-*" trouvée.</p>
                <p>Créez d'abord des catégories commençant par "jeux-" dans <a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>">Articles > Catégories</a></p>
            <?php else : ?>
                <p>Commencez par créer votre première fiche de jeu !</p>
                <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche'); ?>" class="button button-primary">
                    Créer une fiche
                </a>
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
</div>

<?php wp_reset_postdata(); ?>