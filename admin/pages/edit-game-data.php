<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-game-data.php
 * Page de création/édition de jeu - VERSION ÉPURÉE CORRECTE
 * 
 * MODIFICATIONS APPORTÉES:
 * 1. Garde exactement le même fonctionnel que l'original
 * 2. Ajoute juste un header informatif pour le mode édition
 * 3. Le CSS admin-game-form.css stylera l'existant
 * 
 * LOGIQUE IDENTIQUE À 100% - seul l'affichage change via CSS
 */

if (!defined('ABSPATH')) {
    exit;
}

if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-formulaire.php')) {require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-formulaire.php';}
if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php')) {require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';}
if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/vedettes/vedettes-data-manager.php')) {require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/vedettes/vedettes-data-manager.php';}


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

$page_title = $is_edit_mode ? 'Modifier : ' . $tag_data->name : 'Ajouter un nouveau jeu';
$page_subtitle = $is_edit_mode ? 'Édition des données du jeu' : 'Créer un nouveau jeu avec toutes ses données';

$page = new Sisme_Admin_Page_Wrapper(
    $page_title,
    $page_subtitle,
    'create',
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour à Game Data'
);

$success_message = '';
$form_was_submitted = false;

if (!empty($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'sisme_form_action')) {
    $temp_form = new Sisme_Game_Form_Module([
        'game_name', Sisme_Utils_Games::META_TEAM_CHOICE, Sisme_Utils_Games::META_GENRES, Sisme_Utils_Games::META_MODES, Sisme_Utils_Games::META_DEVELOPERS, Sisme_Utils_Games::META_PUBLISHERS, 
        'description', Sisme_Utils_Games::META_PLATFORMS, Sisme_Utils_Games::META_RELEASE_DATE, Sisme_Utils_Games::META_TRAILER_LINK, Sisme_Utils_Games::META_EXTERNAL_LINKS, 
        Sisme_Utils_Games::META_COVER_MAIN, Sisme_Utils_Games::META_COVER_NEWS, Sisme_Utils_Games::META_COVER_PATCH, Sisme_Utils_Games::META_COVER_TEST, 'cover_vertical', Sisme_Utils_Games::META_SCREENSHOTS
    ]);
    $data = $temp_form->get_submitted_data();
    
    if (!empty($data['game_name'])) {
        foreach ($data as $key => $value) {
            if ($key !== 'game_name') {
                $meta_key = ($key === 'description') ? Sisme_Utils_Games::META_DESCRIPTION : $key;
                update_term_meta($data['game_name'], $meta_key, $value);
            }
        }
        
        update_term_meta($data['game_name'], Sisme_Utils_Games::META_LAST_UPDATE, current_time('mysql'));

        if (class_exists('Sisme_Vedettes_Data_Manager')) {
            Sisme_Vedettes_Data_Manager::initialize_new_game($data['game_name']);
        }

        $success_message = 'Données du jeu sauvegardées avec succès !';
        $form_was_submitted = true;
        $_POST = array();
    }
}

if ($is_edit_mode) {
    $_POST['game_name'] = $tag_id;
    $_POST['game_genres'] = get_term_meta($tag_id, Sisme_Utils_Games::META_GENRES, true) ?: array();
    $_POST['game_modes'] = get_term_meta($tag_id, Sisme_Utils_Games::META_MODES, true) ?: array();
    $_POST['game_developers'] = get_term_meta($tag_id, Sisme_Utils_Games::META_DEVELOPERS, true) ?: array();
    $_POST['game_publishers'] = get_term_meta($tag_id, Sisme_Utils_Games::META_PUBLISHERS, true) ?: array();
    $_POST[Sisme_Utils_Games::KEY_DESCRIPTION] = wp_specialchars_decode(get_term_meta($tag_id, Sisme_Utils_Games::META_DESCRIPTION, true) ?: '', ENT_QUOTES);
    $_POST[Sisme_Utils_Games::META_COVER_MAIN] = get_term_meta($tag_id, Sisme_Utils_Games::META_COVER_MAIN, true);
    $_POST['cover_news'] = get_term_meta($tag_id, Sisme_Utils_Games::META_COVER_NEWS, true);
    $_POST['cover_patch'] = get_term_meta($tag_id, Sisme_Utils_Games::META_COVER_PATCH, true);
    $_POST['cover_test'] = get_term_meta($tag_id, Sisme_Utils_Games::META_COVER_TEST, true);
    $_POST['cover_vertical'] = get_term_meta($tag_id, 'cover_vertical', true);
    $_POST['game_platforms'] = get_term_meta($tag_id, Sisme_Utils_Games::META_PLATFORMS, true) ?: array();
    $_POST[Sisme_Utils_Games::KEY_RELEASE_DATE] = get_term_meta($tag_id, Sisme_Utils_Games::META_RELEASE_DATE, true);
    $_POST[Sisme_Utils_Games::KEY_TRAILER_LINK] = get_term_meta($tag_id, Sisme_Utils_Games::META_TRAILER_LINK, true);
    $_POST[Sisme_Utils_Games::KEY_EXTERNAL_LINKS] = get_term_meta($tag_id, Sisme_Utils_Games::META_EXTERNAL_LINKS, true) ?: array();
    $_POST[Sisme_Utils_Games::KEY_SCREENSHOTS] = get_term_meta($tag_id, Sisme_Utils_Games::META_SCREENSHOTS, true);
    $_POST[Sisme_Utils_Games::KEY_IS_TEAM_CHOICE] = get_term_meta($tag_id, Sisme_Utils_Games::META_TEAM_CHOICE, true);
}

$page->render_start();
?>

<!-- Messages de feedback -->
<?php if (!empty($success_message)): ?>
    <div class="sisme-game-form-notices">
        <div class="sisme-notice sisme-notice--success">
            ✅ <?php echo esc_html($success_message); ?>
        </div>
    </div>
<?php endif; ?>

<!-- Header informations contextuelles (seulement en mode édition) -->
<?php if ($is_edit_mode): ?>
    <div class="sisme-game-form-context">
        <div class="sisme-game-form-context__header">
            <h2 class="sisme-game-form-context__title">
                🎮 <?php echo esc_html($tag_data->name); ?>
            </h2>
            <div class="sisme-game-form-context__meta">
                <span>Tag ID: <?php echo $tag_id; ?></span>
                <span>Mode: Édition</span>
                <?php 
                $last_update = get_term_meta($tag_id, Sisme_Utils_Games::META_LAST_UPDATE, true);
                if ($last_update): 
                ?>
                    <span>Modifié: <?php echo date('d/m/Y H:i', strtotime($last_update)); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$form = new Sisme_Game_Form_Module([
    'game_name', Sisme_Utils_Games::META_TEAM_CHOICE, Sisme_Utils_Games::META_GENRES, Sisme_Utils_Games::META_MODES, Sisme_Utils_Games::META_DEVELOPERS, Sisme_Utils_Games::META_PUBLISHERS, 
    'description', Sisme_Utils_Games::META_PLATFORMS, Sisme_Utils_Games::META_RELEASE_DATE, Sisme_Utils_Games::META_TRAILER_LINK, Sisme_Utils_Games::META_EXTERNAL_LINKS, 
    Sisme_Utils_Games::META_COVER_MAIN, Sisme_Utils_Games::META_COVER_NEWS, Sisme_Utils_Games::META_COVER_PATCH, Sisme_Utils_Games::META_COVER_TEST, 'cover_vertical', Sisme_Utils_Games::META_SCREENSHOTS
], [
    'submit_text' => $is_edit_mode ? 'Mettre à jour' : 'Créer le jeu',
    'nonce_action' => 'sisme_form_action'
]);

$form->render();
$form->render_javascript();
$page->render_end();
?>