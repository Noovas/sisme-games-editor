<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-game-data.php
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

$is_edit_mode = ($tag_data !== null);

$page_title = $is_edit_mode ? 'Modifier les données du jeu' : 'Ajouter un nouveau jeu';
$page_subtitle = $is_edit_mode ? 'Jeu : ' . $tag_data->name : 'Créer un nouveau jeu avec ses données';

$page = new Sisme_Admin_Page_Wrapper(
    $page_title,
    $page_subtitle,
    'database-alt',
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour à Game Data'
);

$success_message = '';
$form_was_submitted = false;

// Traitement formulaire
if (!empty($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'sisme_form_action')) {
    $temp_form = new Sisme_Game_Form_Module(['game_name', 'game_genres', 'game_modes', 'game_developers', 'game_publishers', 'description', 'game_platforms', 'release_date', 'trailer_link', 'external_links', 'cover_main', 'cover_news', 'cover_patch', 'cover_test', 'screenshots']);
    $data = $temp_form->get_submitted_data();
    
    if (!empty($data['game_name'])) {
        foreach ($data as $key => $value) {
            if ($key !== 'game_name') {
                $meta_key = ($key === 'description') ? 'game_description' : $key;
                update_term_meta($data['game_name'], $meta_key, $value);
            }
        }
        
        update_term_meta($data['game_name'], 'last_update', current_time('mysql'));
        $success_message = 'Données du jeu sauvegardées avec succès !';
        $form_was_submitted = true;
        $_POST = array();
    }
}

// Pré-remplir données
if ($is_edit_mode) {
    $_POST['game_name'] = $tag_id;
    $_POST['game_genres'] = get_term_meta($tag_id, 'game_genres', true) ?: array();
    $_POST['game_modes'] = get_term_meta($tag_id, 'game_modes', true) ?: array();
    $_POST['game_developers'] = get_term_meta($tag_id, 'game_developers', true) ?: array();
    $_POST['game_publishers'] = get_term_meta($tag_id, 'game_publishers', true) ?: array();
    $_POST['description'] = wp_specialchars_decode(get_term_meta($tag_id, 'game_description', true) ?: '', ENT_QUOTES);
    $_POST['cover_main'] = get_term_meta($tag_id, 'cover_main', true);
    $_POST['cover_news'] = get_term_meta($tag_id, 'cover_news', true);
    $_POST['cover_patch'] = get_term_meta($tag_id, 'cover_patch', true);
    $_POST['cover_test'] = get_term_meta($tag_id, 'cover_test', true);
    $_POST['game_platforms'] = get_term_meta($tag_id, 'game_platforms', true) ?: [];
    $_POST['release_date'] = get_term_meta($tag_id, 'release_date', true);
    $_POST['external_links'] = get_term_meta($tag_id, 'external_links', true) ?: ['steam' => '', 'epic_games' => '', 'gog' => ''];
    $_POST['trailer_link'] = get_term_meta($tag_id, 'trailer_link', true) ?: '';
    $_POST['screenshots'] = get_term_meta($tag_id, 'screenshots', true) ?: array();
}

$form = new Sisme_Game_Form_Module(['game_name', 'game_genres', 'game_modes', 'game_developers', 'game_publishers', 'description', 'game_platforms', 'release_date', 'trailer_link', 'external_links', 'cover_main', 'cover_news', 'cover_patch', 'cover_test', 'screenshots']);

$page->render_start();
?>

<?php if ($form_was_submitted && !empty($success_message)) : ?>
    <div class="sisme-notice sisme-notice--success">
        ✅ <?php echo esc_html($success_message); ?>
    </div>
<?php endif; ?>

<div class="sisme-card">
    <div class="sisme-card__header">
        <h3 class="sisme-heading-4">
            <?php echo $is_edit_mode ? '✏️ Modification du jeu' : '➕ Création d\'un nouveau jeu'; ?>
        </h3>
    </div>
    <div class="sisme-card__body">
        <?php $form->render(['context_help' => true]); ?>
    </div>
</div>

<?php 
$form->render_javascript(); 
$page->render_end();
?>