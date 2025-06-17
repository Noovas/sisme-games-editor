<?php
/**
 * File: /sisme-games-editor/admin/pages/dashboard.php
 * Page Tableau de bord - Version cohérente avec le design system
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

// Récupérer les statistiques
$stats = array(
    'fiches' => count_posts_by_category_prefix('jeux-'),
    'news' => count_posts_by_category_prefix('news'),
    'tests' => count_posts_by_category_prefix('tests')
);
$stats['total'] = $stats['fiches'] + $stats['news'] + $stats['tests'];
?>

<div class="sisme-dashboard-container">
    <!-- En-tête du dashboard -->
    <div class="sisme-dashboard-header">
        <h1>Sisme Games Editor</h1>
        <p class="dashboard-subtitle">
            Créez rapidement et facilement vos contenus gaming avec des templates optimisés pour une expérience professionnelle et cohérente.
        </p>
    </div>
    
    <!-- Section statistiques -->
    <div class="sisme-dashboard-stats">
        <h2>Aperçu de votre contenu</h2>
        <div class="sisme-stats-grid">
            <div class="sisme-stat-card stat-fiches">
                <div class="sisme-stat-number"><?php echo $stats['fiches']; ?></div>
                <div class="sisme-stat-label">Fiches de jeu</div>
            </div>
            <div class="sisme-stat-card stat-news">
                <div class="sisme-stat-number"><?php echo $stats['news']; ?></div>
                <div class="sisme-stat-label">Patch & News</div>
            </div>
            <div class="sisme-stat-card stat-tests">
                <div class="sisme-stat-number"><?php echo $stats['tests']; ?></div>
                <div class="sisme-stat-label">Tests</div>
            </div>
            <div class="sisme-stat-card stat-total">
                <div class="sisme-stat-number"><?php echo $stats['total']; ?></div>
                <div class="sisme-stat-label">Total articles</div>
            </div>
        </div>
    </div>
    
    <!-- Section actions rapides -->
    <div class="sisme-dashboard-actions">
        <h2>Actions rapides</h2>
        <div class="sisme-actions-grid">
            
            <!-- Fiches de jeu -->
            <div class="sisme-action-card card-fiches">
                <div class="sisme-action-header">
                    <div class="sisme-action-icon">📄</div>
                    <h3 class="sisme-action-title">Fiches de jeu</h3>
                </div>
                <p class="sisme-action-description">
                    Créez des fiches détaillées pour présenter les jeux : informations principales, captures d'écran, 
                    caractéristiques techniques et description complète avec sections personnalisées.
                </p>
                <div class="sisme-action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche'); ?>" class="sisme-btn sisme-btn-primary">
                        ✨ Nouvelle fiche
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="sisme-btn sisme-btn-secondary">
                        📋 Gérer les fiches
                    </a>
                </div>
            </div>
            
            <!-- Patch & News -->
            <div class="sisme-action-card card-news">
                <div class="sisme-action-header">
                    <div class="sisme-action-icon">📰</div>
                    <h3 class="sisme-action-title">Patch & News</h3>
                </div>
                <p class="sisme-action-description">
                    Rédigez rapidement des articles sur les dernières mises à jour, patches et actualités 
                    du monde du gaming avec un template adapté et des sections structurées.
                </p>
                <div class="sisme-action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-patch-news'); ?>" class="sisme-btn sisme-btn-primary">
                        ⚡ Nouveau patch/news
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-patch-news'); ?>" class="sisme-btn sisme-btn-secondary">
                        📊 Gérer les articles
                    </a>
                </div>
            </div>
            
            <!-- Tests -->
            <div class="sisme-action-card card-tests">
                <div class="sisme-action-header">
                    <div class="sisme-action-icon">⭐</div>
                    <h3 class="sisme-action-title">Tests</h3>
                </div>
                <p class="sisme-action-description">
                    Créez des tests complets avec analyse détaillée, points forts/faibles, 
                    et verdict final pour guider vos lecteurs dans leurs choix gaming.
                </p>
                <div class="sisme-action-buttons">
                    <span class="sisme-btn sisme-btn-secondary" style="opacity: 0.6; cursor: not-allowed;">
                        🚧 Bientôt disponible
                    </span>
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-tests'); ?>" class="sisme-btn sisme-btn-secondary">
                        👀 Voir la page
                    </a>
                </div>
            </div>
            
            <!-- Réglages -->
            <div class="sisme-action-card card-settings">
                <div class="sisme-action-header">
                    <div class="sisme-action-icon">⚙️</div>
                    <h3 class="sisme-action-title">Configuration</h3>
                </div>
                <p class="sisme-action-description">
                    Configurez les paramètres du plugin, personnalisez les templates, 
                    ajustez les options SEO et optimisez votre workflow selon vos besoins.
                </p>
                <div class="sisme-action-buttons">
                    <a href="<?php echo admin_url('admin.php?page=sisme-games-settings'); ?>" class="sisme-btn sisme-btn-primary">
                        🔧 Réglages
                    </a>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=category'); ?>" class="sisme-btn sisme-btn-secondary">
                        🏷️ Catégories
                    </a>
                </div>
            </div>
            
        </div>
    </div>
</div>