<?php
/**
 * File: /sisme-games-editor/admin/pages/fiches.php
 * Page Fiches de jeu - Version fonctionnelle pure
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Déterminer l'action
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;

// Afficher le bon template selon l'action
if ($action === 'create') {
    if ($step === 1) {
        include SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/forms/create-fiche-step1.php';
    } else {
        include SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/forms/create-fiche-step2.php';
    }
} else {
    // Mode liste
    include SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/lists/fiches-list.php';
}