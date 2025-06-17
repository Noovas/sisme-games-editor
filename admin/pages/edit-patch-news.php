<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-patch-news.php
 * Page de création/édition Patch & News avec split-screen redimensionnable
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer l'ID de l'article (optionnel pour la création)
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
$is_creation_mode = !$post_id;

// En mode édition : récupérer l'article
if (!$is_creation_mode) {
    $post = get_post($post_id);
    if (!$post) {
        wp_die('Article introuvable');
    }
} else {
    // En mode création : créer un objet post vide
    $post = (object) array(
        'post_title' => '',
        'post_excerpt' => '',
        'post_name' => ''
    );
}

// Récupérer les métadonnées existantes
$article_type = '';
$custom_date = '';
$current_game_tag = '';
$existing_sections = array();
$is_just_created = isset($_GET['created']) && $_GET['created'] == '1';

if (!$is_creation_mode) {
    // Déterminer le type via les catégories
    $categories = get_the_category($post_id);
    foreach ($categories as $category) {
        if ($category->slug === 'patch') {
            $article_type = 'patch';
            break;
        } elseif ($category->slug === 'news') {
            $article_type = 'news';
            break;
        }
    }
    
    $custom_date = get_post_meta($post_id, '_sisme_custom_date', true) ?: get_the_date('Y-m-d', $post_id);
    $current_game_tag = get_post_meta($post_id, '_sisme_game_tag', true);
    $existing_sections = get_post_meta($post_id, '_sisme_article_sections', true) ?: array();
}

// Récupérer l'image mise en avant
$featured_image_id = get_post_thumbnail_id($post_id);
$fiche_block_image_id = get_post_meta($post_id, '_sisme_fiche_block_image_id', true);
$news_block_image_id = get_post_meta($post_id, '_sisme_news_block_image_id', true);

$post_url = $is_creation_mode ? '#' : get_permalink($post_id);
?>

<div class="wrap">
    <h1>
        <?php if ($is_creation_mode) : ?>
            Créer un article Patch & News
        <?php else : ?>
            Éditer : <?php echo esc_html($post->post_title); ?>
        <?php endif; ?>
        <a href="<?php echo admin_url('admin.php?page=sisme-games-patch-news'); ?>" class="page-title-action">
            ← Retour à la liste
        </a>
    </h1>
    
    <!-- Contrôles du split -->
    <div class="split-controls" style="margin: 20px 0; text-align: center;">
        <span style="color: #666; margin-right: 15px;">Taille du split :</span>
        <button type="button" class="button split-btn" data-split="30-70">30% / 70%</button>
        <button type="button" class="button split-btn" data-split="40-60">40% / 60%</button>
        <button type="button" class="button split-btn button-primary" data-split="50-50">50% / 50%</button>
        <button type="button" class="button split-btn" data-split="60-40">60% / 40%</button>
        <button type="button" class="button split-btn" data-split="70-30">70% / 30%</button>
        <span style="color: #999; margin-left: 15px; font-size: 11px;">(Formulaire / Aperçu)</span>
    </div>
    
    <!-- Layout split-screen redimensionnable -->
    <div id="split-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; min-height: 80vh;">
        
        <!-- COLONNE GAUCHE : FORMULAIRE D'ÉDITION -->
        <div id="form-panel" style="background: white; padding: 20px; border: 1px solid #ddd; border-radius: 8px; height: 80vh; overflow-y: auto;">
            
            <form method="post" action="">
                <?php wp_nonce_field('sisme_edit_patch_news_form', 'sisme_edit_patch_news_nonce'); ?>
                <input type="hidden" name="sisme_patch_news_action" value="<?php echo $is_creation_mode ? 'create_article' : 'update_article'; ?>">
                <?php if (!$is_creation_mode) : ?>
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <?php endif; ?>
                <input type="hidden" name="split_screen_mode" value="1">
                
                <h2>Informations principales</h2>
                
                <table class="form-table">
                    <!-- Type d'article -->
                    <tr>
                        <th scope="row">Type d'article *</th>
                        <td>
                            <fieldset>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="radio" 
                                           name="article_type" 
                                           value="patch"
                                           <?php checked($article_type, 'patch'); ?>
                                           <?php echo $is_creation_mode ? '' : 'disabled'; ?>
                                           style="margin-right: 8px;">
                                    <span style="background: #D4A373; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px; margin-right: 5px;">PATCH</span>
                                    Notes de patch, correctifs et mises à jour
                                </label>
                                <label style="display: block;">
                                    <input type="radio" 
                                           name="article_type" 
                                           value="news"
                                           <?php checked($article_type, 'news'); ?>
                                           <?php echo $is_creation_mode ? '' : 'disabled'; ?>
                                           style="margin-right: 8px;">
                                    <span style="background: #A1B78D; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px; margin-right: 5px;">NEWS</span>
                                    Actualités, annonces et nouveautés
                                </label>
                            </fieldset>
                            <?php if (!$is_creation_mode) : ?>
                                <p class="description" style="color: #666; margin-top: 8px;">
                                    <em>Le type ne peut pas être modifié après création</em>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- Titre -->
                    <tr>
                        <th scope="row"><label for="article_title">Titre de l'article *</label></th>
                        <td>
                            <input type="text" 
                                   id="article_title" 
                                   name="article_title" 
                                   class="large-text" 
                                   value="<?php echo esc_attr($post->post_title); ?>"
                                   required
                                   placeholder="Ex: Tinkerlands v0.1.5.0 - Nouvelles îles et corrections">
                            <p class="description">Titre principal qui apparaîtra sur le site</p>
                        </td>
                    </tr>
                    
                    <!-- Description -->
                    <tr>
                        <th scope="row"><label for="article_description">Description succincte *</label></th>
                        <td>
                            <textarea id="article_description" 
                                      name="article_description" 
                                      rows="4" 
                                      cols="50" 
                                      class="large-text"
                                      required
                                      placeholder="Résumé de l'article en quelques phrases..."><?php echo esc_textarea($post->post_excerpt); ?></textarea>
                            <p class="description">
                                Description courte pour le référencement et les aperçus. 
                                <strong>Balises autorisées :</strong> &lt;em&gt; &lt;strong&gt;
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Date de publication -->
                    <tr>
                        <th scope="row"><label for="custom_date">Date de publication</label></th>
                        <td>
                            <input type="date" 
                                   id="custom_date" 
                                   name="custom_date" 
                                   value="<?php echo esc_attr($custom_date ?: date('Y-m-d')); ?>">
                            <p class="description">
                                Date qui sera affichée sur le site (remplace la date WordPress native)
                            </p>
                        </td>
                    </tr>
                    <!-- Étiquette du jeu -->
                    <tr>
                        <th scope="row"><label for="game_tag">Jeu associé *</label></th>
                        <td>
                            <?php
                                // Utiliser la métadonnée sauvegardée
                                $current_tag_id = $current_game_tag;
                            ?>
                            <select name="game_tag" id="game_tag" class="regular-text" style="width: 100%;" required>
                                <option value="">Sélectionner un jeu...</option>
                                <?php
                                $existing_tags = get_tags(array(
                                    'hide_empty' => false, 
                                    'orderby' => 'name', 
                                    'order' => 'ASC',
                                    'number' => 200
                                ));
                                usort($existing_tags, function($a, $b) {
                                    return strcasecmp($a->name, $b->name);
                                });
                                foreach ($existing_tags as $tag) {
                                    $selected = selected($current_tag_id, $tag->term_id, false);
                                    echo '<option value="' . esc_attr($tag->term_id) . '"' . $selected . '>' . esc_html($tag->name) . '</option>';
                                }
                                ?>
                            </select>
                            <p class="description">
                                <strong>Obligatoire :</strong> Sélectionnez le jeu concerné par cet article.
                            </p>
                        </td>
                    </tr>
                    <!-- Image mise en avant -->
                    <tr>
                        <th scope="row">Image mise en avant</th>
                        <td>
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
                            <p class="description">
                                Image principale de l'article. <strong>Formats supportés :</strong> JPG, PNG, WebP, GIF
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Image bloc "Fiche du jeu" -->
                <tr>
                    <th scope="row">Image "Découvrir le jeu"</th>
                    <td>
                        <input type="hidden" id="fiche_block_image_id" name="fiche_block_image_id" value="<?php echo $fiche_block_image_id; ?>">
                        <div id="fiche-block-image-preview" style="margin-bottom: 10px;">
                            <?php if ($fiche_block_image_id) : ?>
                                <?php echo wp_get_attachment_image($fiche_block_image_id, 'medium', false, array('style' => 'max-width: 250px; height: auto; border-radius: 4px; border: 2px solid #A1B78D;')); ?>
                            <?php else : ?>
                                <em>Aucune image sélectionnée - Bloc inactif</em>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="select-fiche-block-image" class="button">
                            <?php echo $fiche_block_image_id ? 'Changer l\'image pour la fiche' : 'Choisir une image pour la fiche'; ?>
                        </button>
                        <?php if ($fiche_block_image_id) : ?>
                            <button type="button" id="remove-fiche-block-image" class="button" style="margin-left: 8px;">Supprimer</button>
                        <?php endif; ?>
                        <p class="description">
                            Image pour le bloc redirigeant vers la <strong>fiche principale du jeu</strong>
                            <br><em>Conseil : Image représentative du jeu (logo, artwork, capture)</em>
                        </p>
                    </td>
                </tr>

                <!-- Image bloc "Page de news" -->
                <tr>
                    <th scope="row">Image "Suivre l'actualité"</th>
                    <td>
                        <input type="hidden" id="news_block_image_id" name="news_block_image_id" value="<?php echo $news_block_image_id; ?>">
                        <div id="news-block-image-preview" style="margin-bottom: 10px;">
                            <?php if ($news_block_image_id) : ?>
                                <?php echo wp_get_attachment_image($news_block_image_id, 'medium', false, array('style' => 'max-width: 250px; height: auto; border-radius: 4px; border: 2px solid #D4A373;')); ?>
                            <?php else : ?>
                                <em>Aucune image sélectionnée - Bloc inactif</em>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="select-news-block-image" class="button">
                            <?php echo $news_block_image_id ? 'Changer l\'image pour les news' : 'Choisir une image pour les news'; ?>
                        </button>
                        <?php if ($news_block_image_id) : ?>
                            <button type="button" id="remove-news-block-image" class="button" style="margin-left: 8px;">Supprimer</button>
                        <?php endif; ?>
                        <p class="description">
                            Image pour le bloc redirigeant vers la <strong>page news du jeu</strong>
                            <br><em>Conseil : Image dynamique liée aux actualités (icône news, badge "actu")</em>
                        </p>
                    </td>
                </tr>
                
                <!-- Sections de contenu -->
                <h2>Contenu de l'article</h2>
                <p>Structurez votre article en sections pour une meilleure lisibilité.</p>
                
                <div id="sections-container">
                    <?php foreach ($existing_sections as $index => $section) : ?>
                        <?php if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])) : ?>
                            <div class="section-item" data-section="<?php echo $index; ?>" style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 5px; background: #f9f9f9;">
                                <h3 style="margin-top: 0;">Section <?php echo ($index + 1); ?></h3>
                                
                                <table class="form-table">
                                    <!-- Titre de la section -->
                                    <tr>
                                        <th scope="row"><label for="section_<?php echo $index; ?>_title">Titre de la section</label></th>
                                        <td>
                                            <input type="text" 
                                                   id="section_<?php echo $index; ?>_title" 
                                                   name="sections[<?php echo $index; ?>][title]" 
                                                   class="large-text"
                                                   value="<?php echo esc_attr($section['title']); ?>"
                                                   placeholder="Ex: Nouveautés principales, Corrections de bugs...">
                                            <p class="description">
                                                <strong>Balises autorisées :</strong> &lt;em&gt; &lt;strong&gt;
                                            </p>
                                        </td>
                                    </tr>
                                    
                                    <!-- Contenu de la section -->
                                    <tr>
                                        <th scope="row"><label for="section_<?php echo $index; ?>_content">Contenu</label></th>
                                        <td>
                                            <textarea id="section_<?php echo $index; ?>_content" 
                                                      name="sections[<?php echo $index; ?>][content]" 
                                                      rows="8" 
                                                      cols="50" 
                                                      class="large-text"
                                                      placeholder="Décrivez le contenu de cette section..."><?php echo esc_textarea($section['content']); ?></textarea>
                                            <p class="description">
                                                <strong>Balises autorisées :</strong> &lt;em&gt; &lt;strong&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt;<br>
                                                Utilisez des listes pour structurer les informations importantes.
                                            </p>
                                        </td>
                                    </tr>
                                    
                                    <!-- Image de la section -->
                                    <tr>
                                        <th scope="row"><label>Image de la section</label></th>
                                        <td>
                                            <div id="section-<?php echo $index; ?>-image-preview" style="margin-bottom: 10px;">
                                                <?php if (!empty($section['image_id'])) : ?>
                                                    <?php 
                                                    $section_image = wp_get_attachment_image_src($section['image_id'], 'medium');
                                                    if ($section_image) : ?>
                                                        <img src="<?php echo esc_url($section_image[0]); ?>" style="max-width: 200px; height: auto;">
                                                    <?php endif; ?>
                                                <?php else : ?>
                                                    <em>Aucune image sélectionnée</em>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="button select-section-image" data-section="<?php echo $index; ?>">
                                                <?php echo !empty($section['image_id']) ? 'Changer l\'image' : 'Choisir une image'; ?>
                                            </button>
                                            <?php if (!empty($section['image_id'])) : ?>
                                                <button type="button" class="button remove-section-image" data-section="<?php echo $index; ?>" style="margin-left: 10px;">
                                                    Supprimer l'image
                                                </button>
                                            <?php else : ?>
                                                <button type="button" class="button remove-section-image" data-section="<?php echo $index; ?>" style="margin-left: 10px; display: none;">
                                                    Supprimer l'image
                                                </button>
                                            <?php endif; ?>
                                            <input type="hidden" id="section_<?php echo $index; ?>_image_id" name="sections[<?php echo $index; ?>][image_id]" value="<?php echo esc_attr($section['image_id']); ?>">
                                            <p class="description">
                                                Image optionnelle pour illustrer cette section. <strong>Formats supportés :</strong> JPG, PNG, WebP, GIF
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p>
                                    <button type="button" class="button button-secondary remove-section" data-section="<?php echo $index; ?>">
                                        Supprimer cette section
                                    </button>
                                </p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <p>
                    <button type="button" id="add-new-section" class="button">
                        + Ajouter une nouvelle section
                    </button>
                </p>
                
                <p class="submit" style="border-top: 1px solid #ddd; padding-top: 20px; margin-top: 30px;">
                    <?php if ($is_creation_mode) : ?>
                        <input type="submit" value="🚀 Créer l'article" class="button-primary" id="create-article">
                    <?php else : ?>
                        <input type="submit" value="💾 Sauvegarder et actualiser" class="button-primary" id="save-and-refresh">
                        <button type="button" id="refresh-preview-only" class="button" style="margin-left: 10px;">
                            🔄 Actualiser aperçu
                        </button>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        
        <!-- COLONNE DROITE : PRÉVISUALISATION -->
        <div id="preview-panel" style="background: white; padding: 10px; border: 1px solid #ddd; border-radius: 8px;">
            <?php if ($is_creation_mode) : ?>
                <div style="text-align: center; padding: 50px 20px; color: #666;">
                    <h3 style="margin: 0 0 16px; color: #444;">Aperçu en temps réel</h3>
                    <p style="margin: 0 0 20px;">L'aperçu sera disponible après la création de l'article</p>
                    <div style="font-size: 48px; opacity: 0.3;">📄</div>
                </div>
            <?php else : ?>
                <div style="margin-bottom: 10px; text-align: center; padding: 10px; background: #f7f7f7; border-radius: 5px;">
                    <strong>Prévisualisation en temps réel</strong>
                    <button type="button" id="refresh-preview" class="button" style="margin-left: 10px;">
                        🔄 Actualiser
                    </button>
                    <button type="button" id="open-in-new-tab" class="button" style="margin-left: 10px;">
                        🔗 Ouvrir dans un nouvel onglet
                    </button>
                </div>
                
                <iframe id="article-preview" 
                        src="<?php echo $is_just_created ? '#' : esc_url($post_url); ?>" 
                        style="width: 100%; height: 75vh; border: 1px solid #ccc; border-radius: 4px;"
                        frameborder="0">
                </iframe>

                <?php if ($is_just_created) : ?>
                <script>
                // Charger l'iframe après 3 secondes si c'est une création récente
                setTimeout(function() {
                    $('#article-preview').attr('src', '<?php echo esc_url($post_url); ?>');
                }, 3000);
                </script>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var sectionCounter = <?php 
    $actual_count = 0;
    foreach ($existing_sections as $section) {
        if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])) {
            $actual_count++;
        }
    }
    echo $actual_count;
    ?>;
    
    // Gestion du redimensionnement du split (copié de edit-fiche.php)
    $('.split-btn').click(function() {
        var split = $(this).data('split');
        var parts = split.split('-');
        var leftSize = parts[0] + '%';
        var rightSize = parts[1] + '%';
        
        $('#split-container').css('grid-template-columns', leftSize + ' ' + rightSize);
        $('.split-btn').removeClass('button-primary');
        $(this).addClass('button-primary');
        
        try {
            localStorage.setItem('sisme_patch_news_split_preference', split);
        } catch(e) {}
    });
    
    // Restaurer la préférence de split
    try {
        var savedSplit = localStorage.getItem('sisme_patch_news_split_preference');
        if (savedSplit) {
            var $btn = $('.split-btn[data-split="' + savedSplit + '"]');
            if ($btn.length) {
                $btn.click();
            }
        }
    } catch(e) {}
    
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
            $('#featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
            $('#select-featured-image').text('Changer l\'image');
        });
        
        mediaUploader.open();
    });

    // Gestion de la médiathèque pour l'image bloc "Fiche du jeu"
    $('#select-fiche-block-image').click(function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: 'Choisir une image pour le bloc "Découvrir le jeu"',
            button: { text: 'Utiliser cette image' },
            multiple: false,
            library: { type: 'image' }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#fiche_block_image_id').val(attachment.id);
            $('#fiche-block-image-preview').html('<img src="' + attachment.sizes.medium.url + '" style="max-width: 250px; height: auto; border-radius: 4px; border: 2px solid #A1B78D;">');
            $('#select-fiche-block-image').text('Changer l\'image pour la fiche');
            $('#remove-fiche-block-image').show();
        });
        
        mediaUploader.open();
    });

    // Supprimer l'image bloc "Fiche du jeu"
    $('#remove-fiche-block-image').click(function(e) {
        e.preventDefault();
        $('#fiche_block_image_id').val('');
        $('#fiche-block-image-preview').html('<em>Aucune image sélectionnée - Bloc inactif</em>');
        $('#select-fiche-block-image').text('Choisir une image pour la fiche');
        $(this).hide();
    });

    // Gestion de la médiathèque pour l'image bloc "Page de news"
    $('#select-news-block-image').click(function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: 'Choisir une image pour le bloc "Suivre l\'actualité"',
            button: { text: 'Utiliser cette image' },
            multiple: false,
            library: { type: 'image' }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#news_block_image_id').val(attachment.id);
            $('#news-block-image-preview').html('<img src="' + attachment.sizes.medium.url + '" style="max-width: 250px; height: auto; border-radius: 4px; border: 2px solid #D4A373;">');
            $('#select-news-block-image').text('Changer l\'image pour les news');
            $('#remove-news-block-image').show();
        });
        
        mediaUploader.open();
    });

    // Supprimer l'image bloc "Page de news"
    $('#remove-news-block-image').click(function(e) {
        e.preventDefault();
        $('#news_block_image_id').val('');
        $('#news-block-image-preview').html('<em>Aucune image sélectionnée - Bloc inactif</em>');
        $('#select-news-block-image').text('Choisir une image pour les news');
        $(this).hide();
    });
    
    // Gestion des images des sections
    $(document).on('click', '.select-section-image', function(e) {
        e.preventDefault();
        var button = $(this);
        var section = button.data('section');
        
        var mediaUploader = wp.media({
            title: 'Sélectionner une image pour cette section',
            button: { text: 'Utiliser cette image' },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#section_' + section + '_image_id').val(attachment.id);
            $('#section-' + section + '-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
            button.text('Changer l\'image');
            $('.remove-section-image[data-section="' + section + '"]').show();
        });
        
        mediaUploader.open();
    });

    // Supprimer image de section
    $(document).on('click', '.remove-section-image', function(e) {
        e.preventDefault();
        var section = $(this).data('section');
        $('#section_' + section + '_image_id').val('');
        $('#section-' + section + '-image-preview').html('<em>Aucune image sélectionnée</em>');
        $('.select-section-image[data-section="' + section + '"]').text('Choisir une image');
        $(this).hide();
    });

    // Supprimer section
    $(document).on('click', '.remove-section', function(e) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette section ?')) {
            $(this).closest('.section-item').remove();
        }
    });

    // Ajouter nouvelle section
    $('#add-new-section').click(function() {
        var newSectionHtml = `
            <div class="section-item" data-section="${sectionCounter}" style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 5px; background: #f9f9f9;">
                <h3 style="margin-top: 0;">Section ${sectionCounter + 1}</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="section_${sectionCounter}_title">Titre de la section</label></th>
                        <td>
                            <input type="text" 
                                   id="section_${sectionCounter}_title" 
                                   name="sections[${sectionCounter}][title]" 
                                   class="large-text"
                                   value=""
                                   placeholder="Ex: Nouveautés principales, Corrections de bugs...">
                            <p class="description"><strong>Balises autorisées :</strong> &lt;em&gt; &lt;strong&gt;</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="section_${sectionCounter}_content">Contenu</label></th>
                        <td>
                            <textarea id="section_${sectionCounter}_content" 
                                      name="sections[${sectionCounter}][content]" 
                                      rows="8" 
                                      cols="50" 
                                      class="large-text"
                                      placeholder="Décrivez le contenu de cette section..."></textarea>
                            <p class="description"><strong>Balises autorisées :</strong> &lt;em&gt; &lt;strong&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt;</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label>Image de la section</label></th>
                        <td>
                            <div id="section-${sectionCounter}-image-preview" style="margin-bottom: 10px;">
                                <em>Aucune image sélectionnée</em>
                            </div>
                            <button type="button" class="button select-section-image" data-section="${sectionCounter}">
                                Choisir une image
                            </button>
                            <button type="button" class="button remove-section-image" data-section="${sectionCounter}" style="margin-left: 10px; display: none;">
                                Supprimer l'image
                            </button>
                            <input type="hidden" id="section_${sectionCounter}_image_id" name="sections[${sectionCounter}][image_id]" value="">
                            <p class="description">Image optionnelle. <strong>Formats supportés :</strong> JPG, PNG, WebP, GIF</p>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button type="button" class="button button-secondary remove-section" data-section="${sectionCounter}">
                        Supprimer cette section
                    </button>
                </p>
            </div>
        `;
        $('#sections-container').append(newSectionHtml);
        sectionCounter++;
        
        // Scroll vers la nouvelle section
        $('html, body').animate({
            scrollTop: $('#sections-container .section-item:last').offset().top - 100
        }, 500);
    });
    
    // Actualisation iframe (pour le mode édition)
    $('#refresh-preview, #save-and-refresh, #refresh-preview-only').click(function() {
        var isFormSubmit = $(this).attr('id') === 'save-and-refresh';
        
        if (isFormSubmit) {
            // Pour la sauvegarde, attendre plus longtemps
            setTimeout(function() {
                refreshIframe();
            }, 2000); // 2 secondes au lieu de 1
        } else {
            // Pour l'actualisation simple, vérifier d'abord que l'article existe
            var currentUrl = $('#article-preview').attr('src');
            if (currentUrl && currentUrl !== '#') {
                refreshIframe();
            } else {
                alert('Article pas encore créé. Sauvegardez d\'abord.');
            }
        }
    });
    
    function refreshIframe() {
        var iframe = $('#article-preview');
        if (iframe.length) {
            var currentSrc = iframe.attr('src');
            
            // Vérifier que l'URL n'est pas '#' (mode création)
            if (currentSrc === '#' || currentSrc.includes('#')) {
                console.log('Article pas encore créé, refresh ignoré');
                return;
            }
            
            // Ajouter un timestamp pour forcer le refresh
            var separator = currentSrc.indexOf('?') > -1 ? '&' : '?';
            var baseUrl = currentSrc.split('?')[0]; // Enlever les paramètres existants
            var newSrc = baseUrl + separator + 'preview_refresh=' + Date.now();
            
            // Feedback visuel
            $('#refresh-preview, #refresh-preview-only').text('🔄 Actualisation...');
            
            // Gérer les erreurs de chargement
            iframe.off('load error').on('load', function() {
                setTimeout(function() {
                    $('#refresh-preview, #refresh-preview-only').text('🔄 Actualiser');
                }, 500);
            }).on('error', function() {
                $('#refresh-preview, #refresh-preview-only').text('❌ Erreur');
                setTimeout(function() {
                    $('#refresh-preview, #refresh-preview-only').text('🔄 Actualiser');
                }, 2000);
            });
            
            iframe.attr('src', newSrc);
        }
    }
    
    // Ouvrir dans un nouvel onglet
    $('#open-in-new-tab').click(function() {
        var iframe = $('#article-preview');
        if (iframe.length) {
            var iframeSrc = iframe.attr('src');
            window.open(iframeSrc, '_blank');
        }
    });
    
    // Auto-actualisation après sauvegarde
    $('form').on('submit', function() {
        $('#save-and-refresh').val('💾 Sauvegarde...');
    });
});
</script>

<style>
/* Responsive pour le split-screen (copié de edit-fiche.php) */
@media (max-width: 1200px) {
    #split-container {
        grid-template-columns: 1fr !important;
        gap: 10px;
    }
    
    #form-panel {
        height: auto !important;
        max-height: 60vh;
    }
    
    #article-preview {
        height: 50vh !important;
    }
    
    .split-controls {
        display: none;
    }
}

.split-btn {
    margin: 0 2px;
    font-size: 12px;
    padding: 4px 8px;
}

.split-controls {
    background: #f9f9f9;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

#article-preview {
    transition: opacity 0.3s ease;
}

#article-preview.loading {
    opacity: 0.7;
}

#form-panel, #preview-panel {
    transition: all 0.3s ease;
}

/* Styles pour les types d'article */
input[type="radio"]:checked + span {
    box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}
</style>