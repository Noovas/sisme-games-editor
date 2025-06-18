<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-test.php
 * Page d'édition de test - Sisme Games Editor
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer l'ID de l'article
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$is_creation_mode = !$post_id;

// En mode édition : récupérer l'article
if (!$is_creation_mode) {
    $post = get_post($post_id);
    if (!$post) {
        wp_die('Article introuvable');
    }
} else {
    // En mode création : créer un objet post vide
    $post = (object) array(
        'post_title' => '',
        'post_excerpt' => '',
        'post_name' => ''
    );
}

// Inclure le wrapper admin
require_once plugin_dir_path(__FILE__) . '../../includes/module-admin-page-wrapper.php';
require_once plugin_dir_path(__FILE__) . '../../includes/module-admin-page-formulaire.php';

// Créer la page avec le wrapper
$page = new Sisme_Admin_Page_Wrapper(
    'Édition de Test',
    'Créez ou modifiez un test de jeu',
    'test',
    admin_url('admin.php?page=sisme-games-tests'),
    'Retour à la liste des tests'
);
$form = new Sisme_Game_Form_Module(['game_name', 'description']);
if ($form->is_submitted()) {
    $data = $form->get_submitted_data();
    $form->display_success('Données enregistrées avec succès !');
}

$page->render_start();
    $form->render();
    $form->render_javascript();
$page->render_end();