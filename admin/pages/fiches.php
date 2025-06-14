<?php
/**
 * File: /sisme-games-editor/admin/pages/fiches.php
 * Page Fiches de jeu du plugin Sisme Games Editor
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier si on est en mode création
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

if ($action === 'create') {
    // Afficher le formulaire de création
    include SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/forms/create-fiche.php';
    return;
}

// Mode liste (code existant)
// Récupération des paramètres de tri
$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Inclure les fonctions utilitaires
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/debug-helper.php';

// Récupération des catégories "jeux-*" via fonction utilitaire
$category_ids = sisme_get_jeux_category_ids();

// Debug temporaire (à supprimer une fois que ça marche)
// sisme_debug_categories();

// Si aucune catégorie trouvée, ne pas faire de requête
if (empty($category_ids)) {
    $fiches_query = new WP_Query(array('post__in' => array(0))); // Requête vide
    $total_posts = 0;
} else {
    // Requête pour récupérer les articles des catégories "jeux-*"
    $args = array(
        'post_type' => 'post',
        'post_status' => array('publish', 'draft', 'private'),
        'posts_per_page' => 20,
        'paged' => max(1, get_query_var('paged')),
        'category__in' => $category_ids, // Toutes les catégories "jeux-*"
        'orderby' => ($orderby === 'title') ? 'title' : 'date',
        'order' => $order
    );

    if (!empty($search)) {
        $args['s'] = $search;
    }

    $fiches_query = new WP_Query($args);
    $total_posts = $fiches_query->found_posts;
}
?>

<div class="sisme-games-container">
    <div class="sisme-games-header">
        <h1 class="sisme-games-title">
            <span class="dashicons dashicons-media-document" style="margin-right: 12px; font-size: 28px; vertical-align: middle;"></span>
            Fiches de jeu
            <span class="sisme-count-badge"><?php echo $total_posts; ?></span>
        </h1>
        <p class="sisme-games-subtitle">Gérez vos fiches de jeux • <?php echo $total_posts; ?> fiche<?php echo $total_posts > 1 ? 's' : ''; ?> trouvée<?php echo $total_posts > 1 ? 's' : ''; ?></p>
    </div>
    
    <div class="sisme-games-content">
        <!-- Barre d'outils -->
        <div class="sisme-toolbar">
            <div class="sisme-toolbar-left">
                <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches&action=create'); ?>" class="sisme-btn">
                    <span class="dashicons dashicons-plus-alt"></span>
                    Nouvelle fiche de jeu
                </a>
            </div>
            
            <div class="sisme-toolbar-right">
                <!-- Recherche -->
                <form method="get" class="sisme-search-form">
                    <input type="hidden" name="page" value="sisme-games-fiches">
                    <input type="hidden" name="orderby" value="<?php echo esc_attr($orderby); ?>">
                    <input type="hidden" name="order" value="<?php echo esc_attr($order); ?>">
                    <div class="sisme-search-box">
                        <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Rechercher une fiche..." class="sisme-search-input">
                        <button type="submit" class="sisme-search-btn">
                            <span class="dashicons dashicons-search"></span>
                        </button>
                    </div>
                </form>
                
                <!-- Tri -->
                <div class="sisme-sort-dropdown">
                    <button class="sisme-sort-trigger">
                        <span class="dashicons dashicons-sort"></span>
                        Trier par
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div class="sisme-sort-menu">
                        <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'date', 'order' => 'DESC', 's' => $search))); ?>" 
                           class="<?php echo ($orderby === 'date' && $order === 'DESC') ? 'active' : ''; ?>">
                            Plus récents d'abord
                        </a>
                        <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'date', 'order' => 'ASC', 's' => $search))); ?>" 
                           class="<?php echo ($orderby === 'date' && $order === 'ASC') ? 'active' : ''; ?>">
                            Plus anciens d'abord
                        </a>
                        <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'title', 'order' => 'ASC', 's' => $search))); ?>" 
                           class="<?php echo ($orderby === 'title' && $order === 'ASC') ? 'active' : ''; ?>">
                            Alphabétique A-Z
                        </a>
                        <a href="<?php echo esc_url(add_query_arg(array('orderby' => 'title', 'order' => 'DESC', 's' => $search))); ?>" 
                           class="<?php echo ($orderby === 'title' && $order === 'DESC') ? 'active' : ''; ?>">
                            Alphabétique Z-A
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($fiches_query->have_posts()) : ?>
            <!-- Liste des fiches -->
            <div class="sisme-posts-grid">
                <?php while ($fiches_query->have_posts()) : $fiches_query->the_post(); 
                    $post_id = get_the_ID();
                    $categories = get_the_category();
                    $tags = get_the_tags();
                    $thumbnail = get_the_post_thumbnail_url($post_id, 'medium');
                    $status = get_post_status();
                    $status_labels = array(
                        'publish' => 'Publié',
                        'draft' => 'Brouillon',
                        'private' => 'Privé'
                    );
                ?>
                    <div class="sisme-post-card" data-post-id="<?php echo $post_id; ?>">
                        <div class="sisme-post-thumbnail">
                            <?php if ($thumbnail) : ?>
                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                            <?php else : ?>
                                <div class="sisme-post-no-image">
                                    <span class="dashicons dashicons-games"></span>
                                </div>
                            <?php endif; ?>
                            <div class="sisme-post-status sisme-status-<?php echo $status; ?>">
                                <?php echo $status_labels[$status] ?? ucfirst($status); ?>
                            </div>
                        </div>
                        
                        <div class="sisme-post-content">
                            <h3 class="sisme-post-title">
                                <a href="<?php echo get_edit_post_link($post_id); ?>"><?php the_title(); ?></a>
                            </h3>
                            
                            <div class="sisme-post-meta">
                                <span class="sisme-post-date">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php echo get_the_date('j M Y'); ?>
                                </span>
                                
                                <?php if ($categories) : ?>
                                    <span class="sisme-post-category">
                                        <span class="dashicons dashicons-category"></span>
                                        <?php echo esc_html($categories[0]->name); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($tags) : ?>
                                <div class="sisme-post-tags">
                                    <?php foreach (array_slice($tags, 0, 3) as $tag) : ?>
                                        <span class="sisme-tag"><?php echo esc_html($tag->name); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($tags) > 3) : ?>
                                        <span class="sisme-tag-more">+<?php echo count($tags) - 3; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="sisme-post-excerpt">
                                <?php echo wp_trim_words(get_the_excerpt(), 15, '...'); ?>
                            </div>
                        </div>
                        
                        <div class="sisme-post-actions">
                            <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches&action=edit&post=' . $post_id); ?>" class="sisme-action-btn" title="Modifier">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <a href="<?php echo get_permalink($post_id); ?>" target="_blank" class="sisme-action-btn" title="Voir">
                                <span class="dashicons dashicons-visibility"></span>
                            </a>
                            <button class="sisme-action-btn sisme-duplicate-btn" data-post-id="<?php echo $post_id; ?>" title="Dupliquer">
                                <span class="dashicons dashicons-admin-page"></span>
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($fiches_query->max_num_pages > 1) : ?>
                <div class="sisme-pagination">
                    <?php
                    $pagination_args = array(
                        'total' => $fiches_query->max_num_pages,
                        'current' => max(1, get_query_var('paged')),
                        'format' => '?paged=%#%&orderby=' . $orderby . '&order=' . $order . '&s=' . urlencode($search),
                        'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> Précédent',
                        'next_text' => 'Suivant <span class="dashicons dashicons-arrow-right-alt2"></span>'
                    );
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else : ?>
            <!-- Aucun résultat -->
            <div class="sisme-no-results">
                <div class="sisme-card-icon" style="margin: 0 auto 20px;">
                    <span class="dashicons dashicons-media-document"></span>
                </div>
                <h3>Aucune fiche de jeu trouvée</h3>
                <?php if (!empty($search)) : ?>
                    <p>Aucun résultat pour "<?php echo esc_html($search); ?>". Essayez avec d'autres mots-clés.</p>
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="sisme-btn-secondary">
                        Voir toutes les fiches
                    </a>
                <?php else : ?>
                    <p>Commencez par créer votre première fiche de jeu !</p>
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches&action=create'); ?>" class="sisme-btn">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Créer une fiche
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php wp_reset_postdata(); ?>