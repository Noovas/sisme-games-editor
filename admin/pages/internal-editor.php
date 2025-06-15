<?php
/**
 * File: /sisme-games-editor/admin/pages/internal-editor.php
 * Éditeur interne Sisme Games Editor - Mis à jour avec images test/news
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

// Récupérer l'article et ses métadonnées
$post = get_post($post_id);
if (!$post) {
    wp_die('Article introuvable');
}

// Récupérer toutes les métadonnées
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

// NOUVELLES MÉTADONNÉES : Images des blocs test/news
$test_image_id = get_post_meta($post_id, '_sisme_test_image_id', true);
$news_image_id = get_post_meta($post_id, '_sisme_news_image_id', true);

// Récupérer les catégories
$categories = get_the_category($post_id);
$selected_categories = array();
foreach ($categories as $category) {
    if (strpos($category->slug, 'jeux-') === 0) {
        $selected_categories[] = $category->term_id;
    }
}

// Récupérer toutes les catégories jeux disponibles
$jeux_categories = get_categories(array('hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC'));
$available_jeux_categories = array();
foreach ($jeux_categories as $category) {
    if (strpos($category->slug, 'jeux-') === 0) {
        $available_jeux_categories[] = $category;
    }
}

// Récupérer l'image mise en avant
$featured_image_id = get_post_thumbnail_id($post_id);
?>

<div class="wrap">
    <h1>
        Éditer : <?php echo esc_html($post->post_title); ?>
        <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id); ?>" class="page-title-action">
            ← Retour à la visualisation
        </a>
    </h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('sisme_edit_form', 'sisme_edit_nonce'); ?>
        <input type="hidden" name="sisme_edit_action" value="update_fiche">
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            
            <!-- Colonne principale -->
            <div>
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
                                      rows="6" 
                                      cols="50" 
                                      class="large-text"
                                      required><?php echo esc_textarea($post->post_excerpt); ?></textarea>
                        </td>
                    </tr>
                    
                    <!-- Catégories -->
                    <tr>
                        <th scope="row">Catégories de jeux *</th>
                        <td>
                            <fieldset>
                                <?php foreach ($available_jeux_categories as $category) : ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="game_categories[]" 
                                               value="<?php echo $category->term_id; ?>"
                                               <?php checked(in_array($category->term_id, $selected_categories)); ?>>
                                        <?php echo esc_html(str_replace('jeux-', '', $category->name)); ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <!-- Modes de jeu -->
                    <tr>
                        <th scope="row">Mode de jeu *</th>
                        <td>
                            <fieldset>
                                <?php 
                                $available_modes = array('solo' => 'Solo', 'multijoueur' => 'Multijoueur', 'cooperatif' => 'Coopératif', 'competitif' => 'Compétitif');
                                foreach ($available_modes as $value => $label) : ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="game_modes[]" 
                                               value="<?php echo $value; ?>"
                                               <?php checked(in_array($value, $game_modes)); ?>>
                                        <?php echo $label; ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <!-- Date de sortie -->
                    <tr>
                        <th scope="row"><label for="release_date">Date de sortie</label></th>
                        <td>
                            <input type="date" 
                                   id="release_date" 
                                   name="release_date" 
                                   value="<?php echo esc_attr($release_date); ?>">
                        </td>
                    </tr>
                    
                    <!-- Plateformes -->
                    <tr>
                        <th scope="row">Plateformes *</th>
                        <td>
                            <fieldset>
                                <?php 
                                $available_platforms = array('pc' => 'PC', 'mac' => 'Mac', 'xbox' => 'Xbox', 'playstation' => 'PlayStation', 'switch' => 'Nintendo Switch');
                                foreach ($available_platforms as $value => $label) : ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="platforms[]" 
                                               value="<?php echo $value; ?>"
                                               <?php checked(in_array($value, $platforms)); ?>>
                                        <?php echo $label; ?>
                                    </label><br>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <h2>Développeurs et Éditeurs</h2>
                
                <table class="form-table">
                    <!-- Développeurs -->
                    <tr>
                        <th scope="row">Développeur(s)</th>
                        <td>
                            <div id="developers-list" style="margin-bottom: 15px;">
                                <!-- Les développeurs apparaîtront ici -->
                            </div>
                            
                            <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #f9f9f9;">
                                <h4 style="margin-top: 0;">Ajouter un développeur</h4>
                                <p>
                                    <input type="text" id="dev_name" class="regular-text" placeholder="Nom du développeur">
                                </p>
                                <p>
                                    <input type="url" id="dev_url" class="regular-text" placeholder="Site web (optionnel)">
                                </p>
                                <button type="button" id="add-developer" class="button">Ajouter</button>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Éditeurs -->
                    <tr>
                        <th scope="row">Éditeur(s)</th>
                        <td>
                            <div id="editors-list" style="margin-bottom: 15px;">
                                <!-- Les éditeurs apparaîtront ici -->
                            </div>
                            
                            <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #f9f9f9;">
                                <h4 style="margin-top: 0;">Ajouter un éditeur</h4>
                                <p>
                                    <input type="text" id="editor_name" class="regular-text" placeholder="Nom de l'éditeur">
                                </p>
                                <p>
                                    <input type="url" id="editor_url" class="regular-text" placeholder="Site web (optionnel)">
                                </p>
                                <button type="button" id="add-editor" class="button">Ajouter</button>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <h2>Liens et médias</h2>
                
                <table class="form-table">
                    <!-- Trailer -->
                    <tr>
                        <th scope="row"><label for="trailer_url">Lien du trailer</label></th>
                        <td>
                            <input type="url" 
                                   id="trailer_url" 
                                   name="trailer_url" 
                                   class="regular-text"
                                   value="<?php echo esc_attr($trailer_url); ?>"
                                   placeholder="https://www.youtube.com/watch?v=...">
                        </td>
                    </tr>
                    
                    <!-- Steam -->
                    <tr>
                        <th scope="row"><label for="steam_url">Steam</label></th>
                        <td>
                            <input type="url" 
                                   id="steam_url" 
                                   name="steam_url" 
                                   class="regular-text"
                                   value="<?php echo esc_attr($steam_url); ?>"
                                   placeholder="https://store.steampowered.com/app/...">
                        </td>
                    </tr>
                    
                    <!-- Epic Games -->
                    <tr>
                        <th scope="row"><label for="epic_url">Epic Games</label></th>
                        <td>
                            <input type="url" 
                                   id="epic_url" 
                                   name="epic_url" 
                                   class="regular-text"
                                   value="<?php echo esc_attr($epic_url); ?>"
                                   placeholder="https://www.epicgames.com/store/...">
                        </td>
                    </tr>
                    
                    <!-- GOG -->
                    <tr>
                        <th scope="row"><label for="gog_url">GOG</label></th>
                        <td>
                            <input type="url" 
                                   id="gog_url" 
                                   name="gog_url" 
                                   class="regular-text"
                                   value="<?php echo esc_attr($gog_url); ?>"
                                   placeholder="https://www.gog.com/game/...">
                        </td>
                    </tr>
                </table>
                
                <!-- NOUVELLE SECTION : Images des blocs Test/News -->
                <h2>Images des blocs Test & Actualités</h2>
                
                <table class="form-table">
                    <!-- Image du bloc Test -->
                    <tr>
                        <th scope="row"><label>Image du bloc Test</label></th>
                        <td>
                            <div id="test-image-preview" style="margin-bottom: 10px;">
                                <?php if (!empty($test_image_id)) : ?>
                                    <?php 
                                    $test_image_data = wp_get_attachment_image_src($test_image_id, 'medium');
                                    if ($test_image_data) : ?>
                                        <img src="<?php echo esc_url($test_image_data[0]); ?>" style="max-width: 300px; height: auto;">
                                        <br><strong>Bloc Test activé !</strong> Le lien sera : <code>https://games.sisme.fr/<?php echo esc_html($post->post_name); ?>-test/</code>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <em>Aucune image sélectionnée - Image par défaut sera utilisée (bloc inactif)</em>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="select-test-image" class="button">
                                <?php echo !empty($test_image_id) ? 'Changer l\'image du test' : 'Choisir une image pour le test'; ?>
                            </button>
                            <?php if (!empty($test_image_id)) : ?>
                                <button type="button" id="remove-test-image" class="button" style="margin-left: 10px;">
                                    Supprimer l'image
                                </button>
                            <?php else : ?>
                                <button type="button" id="remove-test-image" class="button" style="margin-left: 10px; display: none;">
                                    Supprimer l'image
                                </button>
                            <?php endif; ?>
                            <input type="hidden" id="test_image_id" name="test_image_id" value="<?php echo esc_attr($test_image_id); ?>">
                            <p class="description">
                                <strong>Important :</strong> Si vous sélectionnez une image, le bloc Test deviendra cliquable et redirigera vers 
                                <code>https://games.sisme.fr/<?php echo esc_html($post->post_name); ?>-test/</code>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Image du bloc News -->
                    <tr>
                        <th scope="row"><label>Image du bloc News</label></th>
                        <td>
                            <div id="news-image-preview" style="margin-bottom: 10px;">
                                <?php if (!empty($news_image_id)) : ?>
                                    <?php 
                                    $news_image_data = wp_get_attachment_image_src($news_image_id, 'medium');
                                    if ($news_image_data) : ?>
                                        <img src="<?php echo esc_url($news_image_data[0]); ?>" style="max-width: 300px; height: auto;">
                                        <br><strong>Bloc News activé !</strong> Le lien sera : <code>https://games.sisme.fr/<?php echo esc_html($post->post_name); ?>-news/</code>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <em>Aucune image sélectionnée - Image par défaut sera utilisée (bloc inactif)</em>
                                <?php endif; ?>
                            </div>
                            <button type="button" id="select-news-image" class="button">
                                <?php echo !empty($news_image_id) ? 'Changer l\'image des news' : 'Choisir une image pour les news'; ?>
                            </button>
                            <?php if (!empty($news_image_id)) : ?>
                                <button type="button" id="remove-news-image" class="button" style="margin-left: 10px;">
                                    Supprimer l'image
                                </button>
                            <?php else : ?>
                                <button type="button" id="remove-news-image" class="button" style="margin-left: 10px; display: none;">
                                    Supprimer l'image
                                </button>
                            <?php endif; ?>
                            <input type="hidden" id="news_image_id" name="news_image_id" value="<?php echo esc_attr($news_image_id); ?>">
                            <p class="description">
                                <strong>Important :</strong> Si vous sélectionnez une image, le bloc News deviendra cliquable et redirigera vers 
                                <code>https://games.sisme.fr/<?php echo esc_html($post->post_name); ?>-news/</code>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Colonne latérale -->
            <div>
                <h2>Actions</h2>
                <div style="background: #f1f1f1; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <p><strong>Statut :</strong> <?php echo get_post_status($post_id) === 'publish' ? 'Publié' : 'Brouillon'; ?></p>
                    <p>
                        <a href="<?php echo get_edit_post_link($post_id); ?>" class="button" target="_blank">
                            Éditer dans WordPress
                        </a>
                    </p>
                    <p>
                        <a href="<?php echo get_permalink($post_id); ?>" class="button" target="_blank">
                            Voir sur le site
                        </a>
                    </p>
                </div>
                
                <h3>Image mise en avant</h3>
                <div style="margin-bottom: 20px;">
                    <div id="featured-image-preview" style="margin-bottom: 10px;">
                        <?php if ($featured_image_id) : ?>
                            <?php echo wp_get_attachment_image($featured_image_id, 'medium'); ?>
                        <?php else : ?>
                            <em>Aucune image sélectionnée</em>
                        <?php endif; ?>
                    </div>
                    <button type="button" id="select-featured-image" class="button">
                        <?php echo $featured_image_id ? 'Changer l\'image' : 'Choisir une image'; ?>
                    </button>
                    <input type="hidden" id="featured_image_id" name="featured_image_id" value="<?php echo $featured_image_id; ?>">
                </div>
                
                <h3>Étiquette principale</h3>
                <select name="main_tag" class="regular-text" style="width: 100%;">
                    <option value="">Sélectionner...</option>
                    <?php
                    $existing_tags = get_tags(array('hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC'));
                    foreach ($existing_tags as $tag) {
                        echo '<option value="' . esc_attr($tag->term_id) . '"' . selected($main_tag, $tag->term_id, false) . '>' . esc_html($tag->name) . '</option>';
                    }
                    ?>
                </select>
            </div>
        </div>
        
        <h2>Contenu de l'article</h2>
        <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin: 20px 0;">
            <p><strong>Note :</strong> Pour éditer le contenu détaillé de l'article (sections), utilisez l'éditeur WordPress.</p>
            <a href="<?php echo get_edit_post_link($post_id); ?>" class="button button-primary" target="_blank">
                Éditer le contenu dans WordPress
            </a>
        </div>
        
        <p class="submit">
            <input type="submit" value="Enregistrer les modifications" class="button-primary">
            <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id); ?>" class="button">
                Annuler
            </a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var developers = <?php echo json_encode($developers); ?>;
    var editors = <?php echo json_encode($editors); ?>;
    
    // Sélecteur d'image mise en avant
    $('#select-featured-image').click(function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: 'Sélectionner une image',
            button: { text: 'Utiliser cette image' },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#featured_image_id').val(attachment.id);
            $('#featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto;">');
            $('#select-featured-image').text('Changer l\'image');
        });
        
        mediaUploader.open();
    });
    
    // Sélecteur d'image pour le bloc Test
    $('#select-test-image').click(function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: 'Sélectionner une image pour le bloc Test',
            button: { text: 'Utiliser cette image' },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#test_image_id').val(attachment.id);
            $('#test-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;"><br><strong>Bloc Test activé !</strong> Le lien sera : <code>https://games.sisme.fr/<?php echo esc_js($post->post_name); ?>-test/</code>');
            $('#select-test-image').text('Changer l\'image du test');
            $('#remove-test-image').show();
        });
        
        mediaUploader.open();
    });
    
    // Supprimer l'image du bloc Test
    $('#remove-test-image').click(function(e) {
        e.preventDefault();
        $('#test_image_id').val('');
        $('#test-image-preview').html('<em>Aucune image sélectionnée - Image par défaut sera utilisée (bloc inactif)</em>');
        $('#select-test-image').text('Choisir une image pour le test');
        $(this).hide();
    });
    
    // Sélecteur d'image pour le bloc News
    $('#select-news-image').click(function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: 'Sélectionner une image pour le bloc News',
            button: { text: 'Utiliser cette image' },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#news_image_id').val(attachment.id);
            $('#news-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;"><br><strong>Bloc News activé !</strong> Le lien sera : <code>https://games.sisme.fr/<?php echo esc_js($post->post_name); ?>-news/</code>');
            $('#select-news-image').text('Changer l\'image des news');
            $('#remove-news-image').show();
        });
        
        mediaUploader.open();
    });
    
    // Supprimer l'image du bloc News
    $('#remove-news-image').click(function(e) {
        e.preventDefault();
        $('#news_image_id').val('');
        $('#news-image-preview').html('<em>Aucune image sélectionnée - Image par défaut sera utilisée (bloc inactif)</em>');
        $('#select-news-image').text('Choisir une image pour les news');
        $(this).hide();
    });
    
    // Gestion dynamique développeurs/éditeurs
    $('#add-developer').click(function() {
        var name = $('#dev_name').val().trim();
        var url = $('#dev_url').val().trim();
        
        if (!name) {
            alert('Veuillez saisir le nom du développeur');
            return;
        }
        
        developers.push({name: name, url: url});
        updateDevelopersList();
        $('#dev_name, #dev_url').val('');
    });
    
    $('#add-editor').click(function() {
        var name = $('#editor_name').val().trim();
        var url = $('#editor_url').val().trim();
        
        if (!name) {
            alert('Veuillez saisir le nom de l\'éditeur');
            return;
        }
        
        editors.push({name: name, url: url});
        updateEditorsList();
        $('#editor_name, #editor_url').val('');
    });
    
    $(document).on('click', '.remove-dev', function() {
        var index = $(this).data('index');
        developers.splice(index, 1);
        updateDevelopersList();
    });
    
    $(document).on('click', '.remove-editor', function() {
        var index = $(this).data('index');
        editors.splice(index, 1);
        updateEditorsList();
    });
    
    function updateDevelopersList() {
        var html = '';
        if (developers.length === 0) {
            html = '<em>Aucun développeur</em>';
        } else {
            developers.forEach(function(dev, index) {
                html += '<div style="background: white; border: 1px solid #ddd; padding: 10px; margin-bottom: 5px; border-radius: 3px;">';
                html += '<strong>' + escapeHtml(dev.name) + '</strong>';
                if (dev.url) {
                    html += ' - <a href="' + escapeHtml(dev.url) + '" target="_blank">' + escapeHtml(dev.url) + '</a>';
                }
                html += ' <button type="button" class="button-link remove-dev" data-index="' + index + '" style="color: red; margin-left: 10px;">Supprimer</button>';
                html += '<input type="hidden" name="developers[' + index + '][name]" value="' + escapeHtml(dev.name) + '">';
                html += '<input type="hidden" name="developers[' + index + '][url]" value="' + escapeHtml(dev.url) + '">';
                html += '</div>';
            });
        }
        $('#developers-list').html(html);
    }
    
    function updateEditorsList() {
        var html = '';
        if (editors.length === 0) {
            html = '<em>Aucun éditeur</em>';
        } else {
            editors.forEach(function(editor, index) {
                html += '<div style="background: white; border: 1px solid #ddd; padding: 10px; margin-bottom: 5px; border-radius: 3px;">';
                html += '<strong>' + escapeHtml(editor.name) + '</strong>';
                if (editor.url) {
                    html += ' - <a href="' + escapeHtml(editor.url) + '" target="_blank">' + escapeHtml(editor.url) + '</a>';
                }
                html += ' <button type="button" class="button-link remove-editor" data-index="' + index + '" style="color: red; margin-left: 10px;">Supprimer</button>';
                html += '<input type="hidden" name="editors[' + index + '][name]" value="' + escapeHtml(editor.name) + '">';
                html += '<input type="hidden" name="editors[' + index + '][url]" value="' + escapeHtml(editor.url) + '">';
                html += '</div>';
            });
        }
        $('#editors-list').html(html);
    }
    
    function escapeHtml(text) {
        if (!text) return '';
        var map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Initialiser les listes
    updateDevelopersList();
    updateEditorsList();
});
</script>