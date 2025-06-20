<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-game-data.php
 * Page d'édition des données de jeux
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-formulaire.php';

// Récupérer l'ID du tag si en mode édition
$tag_id = isset($_GET['tag_id']) ? intval($_GET['tag_id']) : 0;
$tag_data = null;

if ($tag_id > 0) {
    $tag_data = get_term($tag_id, 'post_tag');
    if (is_wp_error($tag_data)) {
        $tag_data = null;
    }
}

// Déterminer le mode (création ou édition)
$is_edit_mode = ($tag_data !== null);
$page_title = $is_edit_mode ? 'Modifier les données du jeu' : 'Ajouter un nouveau jeu';
$page_subtitle = $is_edit_mode ? 'Jeu : ' . $tag_data->name : 'Créer un nouveau jeu avec ses données';

// Créer la page avec le wrapper
$page = new Sisme_Admin_Page_Wrapper(
    $page_title,
    $page_subtitle,
    'database-alt',
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour à Game Data'
);

// Variables pour gérer l'affichage du succès
$success_message = '';
$form_was_submitted = false;

// Traitement du formulaire AVANT de créer l'instance
if (!empty($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'sisme_form_action')) {
    // Créer temporairement le formulaire pour traiter les données
    $temp_form = new Sisme_Game_Form_Module(['game_name', 'game_genres', 'game_modes', 'game_developers', 'game_publishers', 'description', 'game_platforms', 'release_date', 'external_links', 'cover_main', 'cover_news', 'cover_patch', 'cover_test']);
    $data = $temp_form->get_submitted_data();
    
    if (!empty($data['game_name'])) {
        // Sauvegarder toutes les données automatiquement
        foreach ($data as $key => $value) {
            if ($key !== 'game_name') {
                // Mapper les noms de variables vers les clés meta
                $meta_key = $key;
                if ($key === 'description') {
                    $meta_key = 'game_description';
                }
                
                // Sauvegarder même si vide (pour pouvoir supprimer)
                update_term_meta($data['game_name'], $meta_key, $value);
            }
        }
        
        // Date de mise à jour
        update_term_meta($data['game_name'], 'last_update', current_time('mysql'));
        
        $success_message = 'Données du jeu sauvegardées avec succès !';
        $form_was_submitted = true;
        
        // Vider $_POST pour forcer le préchargement propre
        $_POST = array();
    }
}

// Pré-remplir les données si en mode édition
if ($is_edit_mode) {
    // Récupérer les métadonnées existantes
    $existing_game_genres = get_term_meta($tag_id, 'game_genres', true) ?: array();
    $existing_game_modes = get_term_meta($tag_id, 'game_modes', true) ?: array();
    $existing_game_developers = get_term_meta($tag_id, 'game_developers', true) ?: array();
    $existing_game_publishers = get_term_meta($tag_id, 'game_publishers', true) ?: array();
    $existing_description = get_term_meta($tag_id, 'game_description', true);
    $existing_cover_main = get_term_meta($tag_id, 'cover_main', true);
    $existing_cover_news = get_term_meta($tag_id, 'cover_news', true);
    $existing_cover_patch = get_term_meta($tag_id, 'cover_patch', true);
    $existing_cover_test = get_term_meta($tag_id, 'cover_test', true);
    $existing_platforms = get_term_meta($tag_id, 'game_platforms', true);
    $existing_release_date = get_term_meta($tag_id, 'release_date', true);
    $existing_external_links = get_term_meta($tag_id, 'external_links', true);
    
    // Simuler une soumission pour pré-remplir le formulaire
    $_POST['game_name'] = $tag_id;
    $_POST['game_genres'] = $existing_game_genres;
    $_POST['game_modes'] = $existing_game_modes;
    $_POST['game_developers'] = $existing_game_developers;
    $_POST['game_publishers'] = $existing_game_publishers;
    
    // CORRECTION : Décoder la description pour éviter l'affichage d'échappements
    $_POST['description'] = !empty($existing_description) 
        ? wp_specialchars_decode($existing_description, ENT_QUOTES) 
        : '';
        
    $_POST['cover_main'] = $existing_cover_main;
    $_POST['cover_news'] = $existing_cover_news;
    $_POST['cover_patch'] = $existing_cover_patch;
    $_POST['cover_test'] = $existing_cover_test;
    $_POST['game_platforms'] = $existing_platforms ?: [];
    $_POST['release_date'] = $existing_release_date;
    $_POST['external_links'] = $existing_external_links ?: [];
    $_POST['_wpnonce'] = wp_create_nonce('sisme_form_action');
}

// Créer le formulaire APRÈS le préchargement
$form = new Sisme_Game_Form_Module(['game_name', 'game_genres', 'game_modes', 'game_developers', 'game_publishers', 'description', 'game_platforms', 'release_date', 'external_links', 'cover_main', 'cover_news', 'cover_patch', 'cover_test']);

$page->render_start();
?>

<div style="background: white; padding: 30px; border-radius: 8px; margin: 20px 0;">
    
    <?php if ($form_was_submitted && !empty($success_message)): ?>
        <div style="background: #d1edff; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #0073aa;">
            <p style="margin: 0; color: #0073aa;"><strong>✅ <?php echo esc_html($success_message); ?></strong></p>
        </div>
    <?php endif; ?>
    
    <?php if ($is_edit_mode): ?>
        <div style="background: #e7f3e7; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin: 0 0 10px;">🎮 Jeu en cours d'édition</h3>
            <p style="margin: 0;"><strong><?php echo esc_html($tag_data->name); ?></strong> (ID: <?php echo $tag_id; ?>)</p>
        </div>
    <?php endif; ?>
    
    <?php 
    $form->render();
    $form->render_javascript();
    ?>
</div>

<?php
$page->render_end();