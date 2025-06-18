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

// Traitement du formulaire
$form_options = [
    'submit_text' => $is_edit_mode ? 'Mettre à jour les données' : 'Créer le jeu et ses données'
];

// Pré-remplir les données si en mode édition
if ($is_edit_mode) {
    // Récupérer les métadonnées existantes
    $existing_description = get_term_meta($tag_id, 'game_description', true);
    
    // Simuler une soumission pour pré-remplir le formulaire
    if (empty($_POST)) {
        $_POST['game_name'] = $tag_id;
        $_POST['description'] = $existing_description;
        $_POST['_wpnonce'] = wp_create_nonce('sisme_form_action'); // Nonce factice pour éviter les erreurs
    }
}

$form = new Sisme_Game_Form_Module(['game_name', 'description'], $form_options);

if ($form->is_submitted() && !empty($_POST['submit'])) { // Ajouter la condition submit pour éviter le pré-remplissage
    $data = $form->get_submitted_data();
    
    if (!empty($data['game_name'])) {
        // Sauvegarder la description dans term_meta
        if (!empty($data['description'])) {
            update_term_meta($data['game_name'], 'game_description', $data['description']);
        }
        
        // Mettre à jour la date de dernière modification
        update_term_meta($data['game_name'], 'last_update', current_time('mysql'));
        
        $form->display_success('Données du jeu sauvegardées avec succès !');
    }
}

$page->render_start();
?>

<div style="background: white; padding: 30px; border-radius: 8px; margin: 20px 0;">
    
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