<?php
/**
 * File: /sisme-games-editor/admin/pages/tests.php
 * Page Tests minimaliste du plugin Sisme Games Editor - Avec nouveau wrapper
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le wrapper admin
require_once plugin_dir_path(__FILE__) . '../../includes/module-admin-page-liste-article.php';
require_once plugin_dir_path(__FILE__) . '../../includes/module-admin-page-statistiques.php';
require_once plugin_dir_path(__FILE__) . '../../includes/module-admin-page-wrapper.php';


// Créer la page avec le wrapper
$page = new Sisme_Admin_Page_Wrapper(
    'Tests',
    'Analyses détaillées et tests complets de jeux',
    'test',
    admin_url('admin.php?page=sisme-games-editor'),
    'Retour au tableau de bord'
);

$page->render_start();
    ?><a href="<?php echo admin_url('admin.php?page=sisme-games-edit-test'); ?>" class="sisme-admin-create">Créer un article</a><?php
    $stats = new Sisme_Stats_Module('🧪 Statistiques Tests');
    $stats->add_stat('tests', 'Tests publiés', '✅');
    $stats->add_stat('drafts_tests', 'Tests en cours', '📝');
    $stats->render();


    // Créer une liste de tous les contenus Sisme avec toutes les options de filtre
    $all_list = new Sisme_Article_List_Module('tests', -1, [
        'search' => true,
        'status' => true,
        'categories' => false,
        'tags' => true,
        'author' => false
    ]);
    $all_list->render();

$page->render_end();