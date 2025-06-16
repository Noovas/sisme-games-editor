<?php
/**
 * File: /sisme-games-editor/admin/pages/dashboard.php
 * Page Tableau de bord - Version fonctionnelle simple
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

<div class="wrap">
    <h1>Sisme Games Editor</h1>
    <p>Tableau de bord - Créez rapidement vos contenus gaming</p>
    
    <!-- Statistiques -->
    <div style="display: flex; gap: 20px; margin: 30px 0;">
        <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
            <h3 style="margin: 0; font-size: 32px; color: #0073aa;"><?php echo $stats['fiches']; ?></h3>
            <p style="margin: 5px 0 0 0;">Fiches de jeu</p>
        </div>
        <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
            <h3 style="margin: 0; font-size: 32px; color: #0073aa;"><?php echo $stats['news']; ?></h3>
            <p style="margin: 5px 0 0 0;">Patch & News</p>
        </div>
        <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
            <h3 style="margin: 0; font-size: 32px; color: #0073aa;"><?php echo $stats['tests']; ?></h3>
            <p style="margin: 5px 0 0 0;">Tests</p>
        </div>
        <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center; min-width: 150px;">
            <h3 style="margin: 0; font-size: 32px; color: #666;"><?php echo $stats['total']; ?></h3>
            <p style="margin: 5px 0 0 0;">Total articles</p>
        </div>
    </div>
    
    <!-- Introduction -->
    <div style="background: #f1f1f1; padding: 20px; border-left: 4px solid #0073aa; margin: 20px 0;">
        <h2>Bienvenue dans votre éditeur gaming !</h2>
        <p>
            Créez facilement et rapidement vos fiches de jeux, articles de patch & news, et tests détaillés pour games.sisme.fr. 
            Chaque type de contenu dispose de son propre template optimisé pour vous faire gagner du temps.
        </p>
    </div>
    
    <!-- Actions rapides -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 30px 0;">
        
        <!-- Fiches de jeu -->
        <div style="background: white; padding: 25px; border: 1px solid #ddd; border-radius: 5px;">
            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                <span class="dashicons dashicons-media-document" style="font-size: 24px; color: #0073aa; margin-right: 10px;"></span>
                <h3 style="margin: 0;">Fiches de jeu</h3>
            </div>
            <p style="color: #666; margin-bottom: 20px;">
                Créez des fiches détaillées pour présenter les jeux : informations principales, captures d'écran, 
                caractéristiques techniques et description complète.
            </p>
            <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="button">
                Gérer les fiches
            </a>
            <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche'); ?>" class="button button-primary">
                Nouvelle fiche
            </a>
        </div>
        
        <!-- Patch & News -->
        <div style="background: white; padding: 25px; border: 1px solid #ddd; border-radius: 5px;">
            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                <span class="dashicons dashicons-megaphone" style="font-size: 24px; color: #0073aa; margin-right: 10px;"></span>
                <h3 style="margin: 0;">Patch & News</h3>
            </div>
            <p style="color: #666; margin-bottom: 20px;">
                Rédigez rapidement des articles sur les dernières mises à jour, patches et actualités 
                du monde du gaming avec un template adapté.
            </p>
            <a href="<?php echo admin_url('admin.php?page=sisme-games-patch-news'); ?>" class="button">
                Gérer les news
            </a>
            <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-patch-news'); ?>" class="button button-primary">
                Nouveau
            </a>
        </div>
        
        <!-- Tests -->
        <div style="background: white; padding: 25px; border: 1px solid #ddd; border-radius: 5px;">
            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                <span class="dashicons dashicons-star-filled" style="font-size: 24px; color: #0073aa; margin-right: 10px;"></span>
                <h3 style="margin: 0;">Tests</h3>
            </div>
            <p style="color: #666; margin-bottom: 20px;">
                Créez des tests complets avec système de notation, points forts/faibles, 
                et analyse détaillée pour guider vos lecteurs.
            </p>
            <a href="<?php echo admin_url('admin.php?page=sisme-games-tests'); ?>" class="button">
                Gérer les tests
            </a>
            <span style="color: #666; font-style: italic;">Bientôt disponible</span>
        </div>
        
        <!-- Réglages -->
        <div style="background: white; padding: 25px; border: 1px solid #ddd; border-radius: 5px;">
            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #0073aa; margin-right: 10px;"></span>
                <h3 style="margin: 0;">Réglages</h3>
            </div>
            <p style="color: #666; margin-bottom: 20px;">
                Configurez les paramètres du plugin, personnalisez les templates 
                et ajustez les options selon vos besoins.
            </p>
            <a href="<?php echo admin_url('admin.php?page=sisme-games-settings'); ?>" class="button">
                Accéder aux réglages
            </a>
        </div>
    </div>
</div>