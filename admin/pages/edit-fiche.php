<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-fiche.php
 * Page de prévisualisation avec éditeur intégré (split-screen)
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer l'ID de l'article
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

if (!$post_id) {
    wp_die('ID d\'article manquant');
}

// Récupérer l'article
$post = get_post($post_id);
if (!$post) {
    wp_die('Article introuvable');
}

// ============= COPIER TOUT LE CODE DE internal-editor.php =============
// Récupérer toutes les métadonnées (copié de internal-editor.php)
$game_modes = get_post_meta($post_id, '_sisme_game_modes', true) ?: array();
$platforms = get_post_meta($post_id, '_sisme_platforms', true) ?: array();
$release_date = get_post_meta($post_id, '_sisme_release_date', true);
$developers = get_post_meta($post_id, '_sisme_developers', true) ?: array();
$editors = get_post_meta($post_id, '_sisme_editors', true) ?: array();
$trailer_url = get_post_meta($post_id, '_sisme_trailer_url', true);
$steam_url = get_post_meta($post_id, '_sisme_steam_url', true);
$epic_url = get_post_meta($post_id, '_sisme_epic_url', true);
$gog_url = get_post_meta($post_id, '_sisme_gog_url', true);
$main_tag = get_post_meta($post_id, '_sisme_main_tag', true);
$test_image_id = get_post_meta($post_id, '_sisme_test_image_id', true);
$news_image_id = get_post_meta($post_id, '_sisme_news_image_id', true);
$existing_sections = get_post_meta($post_id, '_sisme_sections', true) ?: array();

// Récupérer les catégories (copié de internal-editor.php)
$categories = get_the_category($post_id);
$selected_categories = array();
foreach ($categories as $category) {
    if (strpos($category->slug, 'jeux-') === 0) {
        $selected_categories[] = $category->term_id;
    }
}

// Récupérer toutes les catégories jeux disponibles (copié de internal-editor.php)
$jeux_categories = get_categories(array('hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC'));
$available_jeux_categories = array();
foreach ($jeux_categories as $category) {
    if (strpos($category->slug, 'jeux-') === 0) {
        $available_jeux_categories[] = $category;
    }
}

// Récupérer l'image mise en avant (copié de internal-editor.php)
$featured_image_id = get_post_thumbnail_id($post_id);

$post_url = get_permalink($post_id);
?>

<div class="wrap">
    <h1>
        Éditer : <?php echo esc_html($post->post_title); ?>
        <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="page-title-action">
            ← Retour à la liste
        </a>
    </h1>
    
    <!-- Layout split-screen -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        
        <!-- COLONNE GAUCHE : FORMULAIRE D'ÉDITION -->
        <div style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; height: 80vh; overflow-y: auto;">
            
            <!-- Formulaire - COPIER EXACTEMENT de internal-editor.php -->
            <form method="post" action="">
                <?php wp_nonce_field('sisme_edit_form', 'sisme_edit_nonce'); ?>
                <input type="hidden" name="sisme_edit_action" value="update_fiche">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <input type="hidden" name="split_screen_mode" value="1">
                
                <h2>Informations principales</h2>
                
                <table class="form-table">
                    <!-- Titre -->
                    <tr>
                        <th scope="row"><label for="game_title">Titre du jeu *</label></th>
                        <td>
                            <input type="text" 
                                   id="game_title" 
                                   name="game_title" 
                                   class="regular-text" 
                                   value="<?php echo esc_attr($post->post_title); ?>"
                                   required>
                        </td>
                    </tr>
                    
                    <!-- Description -->
                    <tr>
                        <th scope="row"><label for="game_description">Description *</label></th>
                        <td>
                            <textarea id="game_description" 
                                      name="game_description" 
                                      rows="4" 
                                      cols="50" 
                                      class="large-text"
                                      required><?php echo esc_textarea($post->post_excerpt); ?></textarea>
                        </td>
                    </tr>
                </table>
                
                <!-- 
                    ici on peut ajouter PROGRESSIVEMENT les autres sections :
                    - Images test/news
                    - Sections de contenu
                    - etc.
                    
                    Pour l'instant on garde simple avec juste titre/description
                -->
                
                <p class="submit">
                    <input type="submit" value="💾 Sauvegarder et actualiser" class="button-primary" id="save-and-refresh">
                </p>
            </form>
        </div>
        
        <!-- COLONNE DROITE : PRÉVISUALISATION -->
        <div style="background: white; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            <div style="margin-bottom: 10px; text-align: center;">
                <strong>Prévisualisation en temps réel</strong>
                <button type="button" id="refresh-preview" class="button" style="margin-left: 10px;">
                    🔄 Actualiser
                </button>
            </div>
            
            <iframe id="fiche-preview" 
                    src="<?php echo esc_url($post_url); ?>" 
                    style="width: 100%; height: 75vh; border: 1px solid #ccc; border-radius: 4px;"
                    frameborder="0">
            </iframe>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Actualiser l'iframe
    $('#refresh-preview, #save-and-refresh').click(function() {
        setTimeout(function() {
            var iframe = $('#fiche-preview');
            var currentSrc = iframe.attr('src');
            var separator = currentSrc.indexOf('?') > -1 ? '&' : '?';
            var newSrc = currentSrc.split('?')[0] + separator + 'preview_refresh=' + Date.now();
            iframe.attr('src', newSrc);
        }, 1000); // Délai pour laisser la sauvegarde se faire
    });
    
    // Auto-actualisation après sauvegarde réussie
    $('form').on('submit', function() {
        $('#save-and-refresh').val('💾 Sauvegarde...');
    });
});
</script>

<style>
/* Responsive */
@media (max-width: 1200px) {
    .wrap > div:last-child {
        grid-template-columns: 1fr !important;
    }
    
    .wrap > div:last-child > div:first-child {
        height: auto !important;
        max-height: 60vh;
    }
    
    #fiche-preview {
        height: 50vh !important;
    }
}
</style>