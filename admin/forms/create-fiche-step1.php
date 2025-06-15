<?php
/**
 * File: /sisme-games-editor/admin/forms/create-fiche-step1.php
 * Formulaire de création d'une fiche de jeu - Étape 1 : Métadonnées
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les catégories jeux existantes
$jeux_categories = get_categories(array(
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
));

$available_jeux_categories = array();
foreach ($jeux_categories as $category) {
    if (strpos($category->slug, 'jeux-') === 0) {
        $available_jeux_categories[] = $category;
    }
}
?>

<div class="wrap">
    <h1>Créer une fiche de jeu - Étape 1/2</h1>
    <p><strong>Étape 1 :</strong> Informations principales | <em>Étape 2 : Contenu détaillé</em></p>
    
    <form method="post" action="">
        <?php wp_nonce_field('sisme_form', 'sisme_nonce'); ?>
        <input type="hidden" name="sisme_form_action" value="create_fiche_step1">
        
        <table class="form-table">
            <!-- Titre du jeu -->
            <tr>
                <th scope="row"><label for="game_title">Titre du jeu *</label></th>
                <td>
                    <input type="text" 
                           id="game_title" 
                           name="game_title" 
                           class="regular-text" 
                           placeholder="Ex: Lost in Random: The Eternal Die"
                           required>
                    <p class="description">Ce sera le titre de la page et de l'article</p>
                </td>
            </tr>
            
            <!-- Description -->
            <tr>
                <th scope="row"><label for="game_description">Description du jeu *</label></th>
                <td>
                    <textarea id="game_description" 
                              name="game_description" 
                              rows="6" 
                              cols="50" 
                              class="large-text"
                              placeholder="Décrivez le jeu, son gameplay, son univers..."
                              required></textarea>
                    <p class="description">Cette description sera utilisée dans le template de la fiche</p>
                </td>
            </tr>
            
            <!-- Image de mise en avant -->
            <tr>
                <th scope="row"><label>Image de mise en avant</label></th>
                <td>
                    <div id="featured-image-preview" style="margin-bottom: 10px;">
                        <em>Aucune image sélectionnée</em>
                    </div>
                    <button type="button" id="select-featured-image" class="button">
                        Choisir une image
                    </button>
                    <input type="hidden" id="featured_image_id" name="featured_image_id" value="">
                </td>
            </tr>
            
            <!-- Catégories de jeux -->
            <tr>
                <th scope="row">Catégories de jeux *</th>
                <td>
                    <fieldset>
                        <?php foreach ($available_jeux_categories as $category) : ?>
                            <label>
                                <input type="checkbox" name="game_categories[]" value="<?php echo $category->term_id; ?>">
                                <?php echo esc_html(str_replace('jeux-', '', $category->name)); ?>
                            </label><br>
                        <?php endforeach; ?>
                        
                        <?php if (empty($available_jeux_categories)) : ?>
                            <p><em>Aucune catégorie "jeux-*" trouvée. Créez d'abord des catégories dans Articles > Catégories</em></p>
                        <?php endif; ?>
                    </fieldset>
                </td>
            </tr>
            
            <!-- Mode de jeu -->
            <tr>
                <th scope="row">Mode de jeu *</th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="game_modes[]" value="solo">
                            Solo
                        </label><br>
                        <label>
                            <input type="checkbox" name="game_modes[]" value="multijoueur">
                            Multijoueur
                        </label><br>
                        <label>
                            <input type="checkbox" name="game_modes[]" value="cooperatif">
                            Coopératif
                        </label><br>
                        <label>
                            <input type="checkbox" name="game_modes[]" value="competitif">
                            Compétitif
                        </label>
                    </fieldset>
                </td>
            </tr>
            
            <!-- Date de sortie -->
            <tr>
                <th scope="row"><label for="release_date">Date de sortie</label></th>
                <td>
                    <input type="date" id="release_date" name="release_date">
                </td>
            </tr>
            
            <!-- Plateformes -->
            <tr>
                <th scope="row">Plateformes *</th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="platforms[]" value="pc">
                            PC
                        </label><br>
                        <label>
                            <input type="checkbox" name="platforms[]" value="mac">
                            Mac
                        </label><br>
                        <label>
                            <input type="checkbox" name="platforms[]" value="xbox">
                            Xbox
                        </label><br>
                        <label>
                            <input type="checkbox" name="platforms[]" value="playstation">
                            PlayStation
                        </label><br>
                        <label>
                            <input type="checkbox" name="platforms[]" value="switch">
                            Nintendo Switch
                        </label>
                    </fieldset>
                </td>
            </tr>
            
            <!-- Développeurs -->
            <tr>
                <th scope="row">Développeur(s)</th>
                <td>
                    <div id="developers-list" style="margin-bottom: 15px;">
                        <!-- Les développeurs ajoutés apparaîtront ici -->
                    </div>
                    
                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #f9f9f9;">
                        <h4 style="margin-top: 0;">Ajouter un développeur</h4>
                        <p>
                            <label for="dev_name">Nom du développeur :</label><br>
                            <input type="text" id="dev_name" class="regular-text" placeholder="Ex: Studio XYZ">
                        </p>
                        <p>
                            <label for="dev_url">Site web (optionnel) :</label><br>
                            <input type="url" id="dev_url" class="regular-text" placeholder="https://www.studio-xyz.com">
                        </p>
                        <button type="button" id="add-developer" class="button">Ajouter ce développeur</button>
                    </div>
                </td>
            </tr>
            
            <!-- Éditeurs -->
            <tr>
                <th scope="row">Éditeur(s)</th>
                <td>
                    <div id="editors-list" style="margin-bottom: 15px;">
                        <!-- Les éditeurs ajoutés apparaîtront ici -->
                    </div>
                    
                    <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; background: #f9f9f9;">
                        <h4 style="margin-top: 0;">Ajouter un éditeur</h4>
                        <p>
                            <label for="editor_name">Nom de l'éditeur :</label><br>
                            <input type="text" id="editor_name" class="regular-text" placeholder="Ex: Publisher ABC">
                        </p>
                        <p>
                            <label for="editor_url">Site web (optionnel) :</label><br>
                            <input type="url" id="editor_url" class="regular-text" placeholder="https://www.publisher-abc.com">
                        </p>
                        <button type="button" id="add-editor" class="button">Ajouter cet éditeur</button>
                    </div>
                </td>
            </tr>
            
            <!-- Étiquette principale -->
            <tr>
                <th scope="row"><label for="main_tag">Étiquette principale *</label></th>
                <td>
                    <select id="main_tag" name="main_tag" class="regular-text" required>
                        <option value="">Sélectionner une étiquette...</option>
                        <?php
                        $existing_tags = get_tags(array(
                            'hide_empty' => false,
                            'orderby' => 'name',
                            'order' => 'ASC'
                        ));
                        
                        foreach ($existing_tags as $tag) {
                            echo '<option value="' . esc_attr($tag->term_id) . '">' . esc_html($tag->name) . '</option>';
                        }
                        ?>
                    </select>
                    <p class="description">
                        Choisissez l'étiquette principale qui représente ce jeu. 
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=post_tag'); ?>" target="_blank">Gérer les étiquettes</a>
                    </p>
                </td>
            </tr>
            
            <!-- Trailer -->
            <tr>
                <th scope="row"><label for="trailer_url">Lien du trailer</label></th>
                <td>
                    <input type="url" 
                           id="trailer_url" 
                           name="trailer_url" 
                           class="regular-text"
                           placeholder="https://www.youtube.com/watch?v=...">
                </td>
            </tr>
            
            <!-- Liens boutiques -->
            <tr>
                <th scope="row">Liens boutiques</th>
                <td>
                    <p>
                        <label for="steam_url">Steam :</label><br>
                        <input type="url" id="steam_url" name="steam_url" class="regular-text" placeholder="https://store.steampowered.com/app/...">
                    </p>
                    <p>
                        <label for="epic_url">Epic Games :</label><br>
                        <input type="url" id="epic_url" name="epic_url" class="regular-text" placeholder="https://www.epicgames.com/store/...">
                    </p>
                    <p>
                        <label for="gog_url">GOG :</label><br>
                        <input type="url" id="gog_url" name="gog_url" class="regular-text" placeholder="https://www.gog.com/game/...">
                    </p>
                </td>
            </tr>
        </table>
        
        <!-- NOUVELLE SECTION : Images des blocs Test/News -->
        <h2>Images pour les blocs Test & Actualités</h2>
        <p>Sélectionnez des images personnalisées pour activer les liens vers les pages test et news. Si aucune image n'est sélectionnée, l'image par défaut sera utilisée et les blocs seront inactifs.</p>
        
        <table class="form-table">
            <!-- Image du bloc Test -->
            <tr>
                <th scope="row"><label>Image du bloc Test</label></th>
                <td>
                    <div id="test-image-preview" style="margin-bottom: 10px;">
                        <em>Aucune image sélectionnée - Image par défaut sera utilisée (bloc inactif)</em>
                    </div>
                    <button type="button" id="select-test-image" class="button">
                        Choisir une image pour le test
                    </button>
                    <button type="button" id="remove-test-image" class="button" style="margin-left: 10px; display: none;">
                        Supprimer l'image
                    </button>
                    <input type="hidden" id="test_image_id" name="test_image_id" value="">
                    <p class="description">
                        <strong>Important :</strong> Si vous sélectionnez une image, le bloc Test deviendra cliquable et redirigera vers 
                        <code>https://games.sisme.fr/[nom-du-jeu]-test/</code>
                    </p>
                </td>
            </tr>
            
            <!-- Image du bloc News -->
            <tr>
                <th scope="row"><label>Image du bloc News</label></th>
                <td>
                    <div id="news-image-preview" style="margin-bottom: 10px;">
                        <em>Aucune image sélectionnée - Image par défaut sera utilisée (bloc inactif)</em>
                    </div>
                    <button type="button" id="select-news-image" class="button">
                        Choisir une image pour les news
                    </button>
                    <button type="button" id="remove-news-image" class="button" style="margin-left: 10px; display: none;">
                        Supprimer l'image
                    </button>
                    <input type="hidden" id="news_image_id" name="news_image_id" value="">
                    <p class="description">
                        <strong>Important :</strong> Si vous sélectionnez une image, le bloc News deviendra cliquable et redirigera vers 
                        <code>https://games.sisme.fr/[nom-du-jeu]-news/</code>
                    </p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="button">
                Annuler
            </a>
            <input type="submit" value="Continuer vers l'étape 2" class="button-primary">
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var developers = [];
    var editors = [];
    
    // Sélecteur d'image media uploader - Image mise en avant
    $('#select-featured-image').click(function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: 'Sélectionner une image mise en avant',
            button: {
                text: 'Utiliser cette image'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#featured_image_id').val(attachment.id);
            $('#featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;">');
            $('#select-featured-image').text('Changer l\'image');
        });
        
        mediaUploader.open();
    });
    
    // Sélecteur d'image pour le bloc Test
    $('#select-test-image').click(function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: 'Sélectionner une image pour le bloc Test',
            button: {
                text: 'Utiliser cette image'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#test_image_id').val(attachment.id);
            $('#test-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;"><br><strong>Bloc Test activé !</strong> Le lien sera : <code>https://games.sisme.fr/[nom-du-jeu]-test/</code>');
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
            button: {
                text: 'Utiliser cette image'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#news_image_id').val(attachment.id);
            $('#news-image-preview').html('<img src="' + attachment.url + '" style="max-width: 300px; height: auto;"><br><strong>Bloc News activé !</strong> Le lien sera : <code>https://games.sisme.fr/[nom-du-jeu]-news/</code>');
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
    
    // Ajouter un développeur
    $('#add-developer').click(function() {
        var name = $('#dev_name').val().trim();
        var url = $('#dev_url').val().trim();
        
        if (!name) {
            alert('Veuillez saisir le nom du développeur');
            return;
        }
        
        var developer = {
            name: name,
            url: url
        };
        
        developers.push(developer);
        updateDevelopersList();
        
        // Reset form
        $('#dev_name').val('');
        $('#dev_url').val('');
    });
    
    // Ajouter un éditeur
    $('#add-editor').click(function() {
        var name = $('#editor_name').val().trim();
        var url = $('#editor_url').val().trim();
        
        if (!name) {
            alert('Veuillez saisir le nom de l\'éditeur');
            return;
        }
        
        var editor = {
            name: name,
            url: url
        };
        
        editors.push(editor);
        updateEditorsList();
        
        // Reset form
        $('#editor_name').val('');
        $('#editor_url').val('');
    });
    
    // Supprimer un développeur
    $(document).on('click', '.remove-dev', function() {
        var index = $(this).data('index');
        developers.splice(index, 1);
        updateDevelopersList();
    });
    
    // Supprimer un éditeur
    $(document).on('click', '.remove-editor', function() {
        var index = $(this).data('index');
        editors.splice(index, 1);
        updateEditorsList();
    });
    
    // Mettre à jour la liste des développeurs
    function updateDevelopersList() {
        var html = '';
        
        if (developers.length === 0) {
            html = '<em>Aucun développeur ajouté</em>';
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
    
    // Mettre à jour la liste des éditeurs
    function updateEditorsList() {
        var html = '';
        
        if (editors.length === 0) {
            html = '<em>Aucun éditeur ajouté</em>';
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
    
    // Fonction utilitaire pour échapper le HTML
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Initialiser les listes vides
    updateDevelopersList();
    updateEditorsList();
});
</script>