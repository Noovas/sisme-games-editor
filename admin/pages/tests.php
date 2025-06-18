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
require_once plugin_dir_path(__FILE__) . '../../includes/admin-page-wrapper.php';
require_once plugin_dir_path(__FILE__) . '../../includes/admin-page-statistiques.php';

// Créer la page avec le wrapper
$page = new Sisme_Admin_Page_Wrapper(
    'Tests',
    'Analyses détaillées et tests complets de jeux',
    'test',
    admin_url('admin.php?page=sisme-games-editor'),
    'Retour au tableau de bord'
);



$page->render_start();
    $stats = new Sisme_Stats_Module('🧪 Statistiques Tests');
    $stats->add_stat('tests', 'Tests publiés', '✅');
    $stats->add_stat('drafts_tests', 'Tests en cours', '📝');
    $stats->render();
$page->render_end();