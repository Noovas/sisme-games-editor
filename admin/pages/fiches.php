<?php
/**
 * File: /sisme-games-editor/admin/pages/fiches.php
 * Page Fiches de jeu - Liste uniquement
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Afficher uniquement la liste
include SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/lists/fiches-list.php';