<?php
/**
 * File: /sisme-games-editor/admin/lists/fiches-list.php
 * Liste des fiches de jeu - Structure adaptée avec flexbox
 */

if (!defined('ABSPATH')) {
    exit;
}

// Traitement des actions de publication (même code qu'avant)
if (isset($_GET['action']) && isset($_GET['post_id'])) {
    $action = sanitize_text_field($_GET['action']);
    $post_id = intval($_GET['post_id']);
    
    if ($action === 'publish' && wp_verify_nonce($_GET['_wpnonce'], 'publish_post_' . $post_id)) {
        $result = wp_update_post(array('ID' => $post_id, 'post_status' => 'publish'));
        
        if (!is_wp_error($result)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Fiche publiée avec succès !</p></div>';
            });
        }
        wp_redirect(admin_url('admin.php?page=sisme-games-fiches'));
        exit;
        
    } elseif ($action === 'unpublish' && wp_verify_nonce($_GET['_wpnonce'], 'unpublish_post_' . $post_id)) {
        $result = wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
        
        if (!is_wp_error($result)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Fiche remise en brouillon !</p></div>';
            });
        }
        wp_redirect(admin_url('admin.php?page=sisme-games-fiches'));
        exit;
    }
}

// Calcul des statistiques
function count_fiches_by_status() {
    $category_ids = sisme_get_jeux_category_ids();
    
    if (empty($category_ids)) {
        return array('total' => 0, 'published' => 0, 'draft' => 0);
    }
    
    $published = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'category__in' => $category_ids,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    $draft = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'draft',
        'category__in' => $category_ids,
        'posts_per_page' => -1,
        'fields' => 'ids'
    ));
    
    return array(
        'total' => $published->found_posts + $draft->found_posts,
        'published' => $published->found_posts,
        'draft' => $draft->found_posts
    );
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
$stats = count_fiches_by_status();

if (empty($category_ids)) {
    $fiches_query = new WP_Query(array('post__in' => array(0)));
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
    <!-- En-tête -->
    <div class="page-header">
        <h1>
            🎮 Fiches de jeu
            <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche'); ?>" class="page-title-action">
                ➕ Ajouter une nouvelle fiche
            </a>
        </h1>
    </div>
    
    <!-- Section statistiques -->
    <div class="stats-section">
        <h3>📊 Statistiques Fiches de jeu</h3>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['total']; ?></span>
                <span class="stat-label">Total</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['published']; ?></span>
                <span class="stat-label">Publiées</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['draft']; ?></span>
                <span class="stat-label">Brouillons</span>
            </div>
        </div>
    </div>
    
    <!-- Barre de recherche -->
    <div class="search-section">
        <form method="get" class="search-form">
            <input type="hidden" name="page" value="sisme-games-fiches">
            <div class="search-container">
                <input type="search" 
                       name="s" 
                       value="<?php echo esc_attr($search); ?>" 
                       placeholder="Rechercher une fiche..."
                       class="search-input">
                <button type="submit" class="search-button">Rechercher</button>
            </div>
        </form>
    </div>
    
    <?php if (!empty($search)) : ?>
        <div class="search-notice">
            <p>Résultats pour : <strong><?php echo esc_html($search); ?></strong> 
            (<a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>">voir toutes</a>)</p>
        </div>
    <?php endif; ?>
    
    <?php if ($fiches_query->have_posts()) : ?>
        
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
                    <div class="header-col platforms-col">Plateformes</div>
                    <div class="header-col status-col">Statut</div>
                    <div class="header-col date-col">Date</div>
                </div>
            </div>
        </div>
        
        <!-- Articles groupés avec flexbox -->
        <div class="articles-container">
            <?php while ($fiches_query->have_posts()) : $fiches_query->the_post(); 
                $post_id = get_the_ID();
                $categories = get_the_category();
                $status = get_post_status();
                $platforms = get_post_meta($post_id, '_sisme_platforms', true);
                
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
                            <div class="no-image">🎮</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contenu à droite (données + actions) -->
                    <div class="article-content">
                        <!-- Ligne de données -->
                        <div class="article-data">
                            <div class="data-col title-col">
                                <h4 class="article-title">
                                    <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id); ?>">
                                        <?php the_title(); ?>
                                    </a>
                                </h4>
                            </div>
                            
                            <div class="data-col platforms-col">
                                <?php if (!empty($platforms) && is_array($platforms)) : ?>
                                    <div class="platforms-list">
                                        <?php 
                                        $has_pc = false;
                                        $has_console = false;
                                        
                                        // Vérifier quels types sont présents
                                        foreach ($platforms as $platform) {
                                            $platform_lower = strtolower($platform);
                                            
                                            if (in_array($platform_lower, array('windows', 'mac', 'linux', 'steam', 'pc'))) {
                                                $has_pc = true;
                                            } elseif (in_array($platform_lower, array('switch', 'playstation', 'xbox', 'ps4', 'ps5', 'nintendo'))) {
                                                $has_console = true;
                                            }
                                        }
                                        
                                        // Afficher une seule icône (priorité PC > Console)
                                        if ($has_pc) {
                                            echo '<span class="platform-icon" title="PC">🖥️</span>';
                                        } elseif ($has_console) {
                                            echo '<span class="platform-icon" title="Console">🎮</span>';
                                        }
                                        ?>
                                    </div>
                                <?php else : ?>
                                    <span class="no-platforms">—</span>
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
                            <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id); ?>" 
                               class="action-btn edit-btn">✏️ Modifier</a>
                            
                            <?php if ($status === 'publish') : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-fiches&action=unpublish&post_id=' . $post_id), 'unpublish_post_' . $post_id); ?>" 
                                   class="action-btn draft-btn">📝 Brouillon</a>
                            <?php else : ?>
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=sisme-games-fiches&action=publish&post_id=' . $post_id), 'publish_post_' . $post_id); ?>" 
                                   class="action-btn publish-btn">✅ Publier</a>
                            <?php endif; ?>
                            
                            <a href="<?php echo get_permalink($post_id); ?>" 
                               target="_blank" 
                               class="action-btn view-btn">👁️ Voir</a>
                            
                            <a href="<?php echo get_delete_post_link($post_id); ?>" 
                               onclick="return confirm('Supprimer cette fiche ?');"
                               class="action-btn delete-btn">🗑️ Supprimer</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($fiches_query->max_num_pages > 1) : ?>
            <div class="pagination-section">
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
        <?php endif; ?>
        
    <?php else : ?>
        
        <!-- État vide -->
        <div class="empty-state">
            <div class="empty-icon">🎮</div>
            <h3>Aucune fiche trouvée</h3>
            
            <?php if (!empty($search)) : ?>
                <p>Aucun résultat pour "<?php echo esc_html($search); ?>"</p>
                <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="button">
                    Voir toutes les fiches
                </a>
            <?php elseif (empty($category_ids)) : ?>
                <p>Créez d'abord des catégories "jeux-*"</p>
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>" class="button">
                    Gérer les catégories
                </a>
            <?php else : ?>
                <p>Commencez par créer votre première fiche !</p>
                <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche'); ?>" class="button button-primary">
                    Créer une fiche
                </a>
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
</div>

<?php wp_reset_postdata(); ?>