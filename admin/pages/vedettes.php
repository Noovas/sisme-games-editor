<?php
/**
 * File: /sisme-games-editor/admin/pages/vedettes.php
 * Page de gestion des jeux vedettes pour la page d'accueil
 * 
 * OBJECTIF:
 * - Gérer les jeux mis en vedette pour le carrousel frontend
 * - Interface simple et épurée pour sélectionner/ordonner les jeux
 * - Cohérence avec le design admin existant
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure les modules nécessaires
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';

// Créer l'instance de wrapper avec le bon design
$page = new Sisme_Admin_Page_Wrapper(
    'Vedettes',
    '',
    'star', // Icône ⭐ pour "vedette"
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour à Game Data'
);

// Messages de feedback
$success_message = '';
$error_message = '';

// TODO: Traitement des formulaires (dans les prochaines étapes)
// if (isset($_POST['action']) && $_POST['action'] === 'update_vedettes') {
//     // Logique de sauvegarde
// }

// Début du rendu de la page
$page->render_start();

// Afficher les messages si présents
if (!empty($success_message)) {
    $page->add_notice($success_message, 'success');
}
if (!empty($error_message)) {
    $page->add_notice($error_message, 'error');
}

?>

<?php

// Fin du rendu de la page
$page->render_end();

?>