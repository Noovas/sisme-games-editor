<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-fiche.php
 * Page de prévisualisation avec éditeur intégré (split-screen redimensionnable)
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

// Initialiser les valeurs par défaut en mode création
if ($is_creation_mode) {
    $game_modes = array();
    $platforms = array();
    $release_date = '';
    $developers = array();
    $editors = array();
    $trailer_url = '';
    $steam_url = '';
    $epic_url = '';
    $gog_url = '';
    $main_tag = '';
    $test_image_id = '';
    $news_image_id = '';
    $existing_sections = array();
    $selected_categories = array();
    $featured_image_id = 0;
    $post_url = '#'; // URL temporaire
}

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
        <?php if ($is_creation_mode) : ?>
            Créer une nouvelle fiche de jeu
        <?php else : ?>
            Éditer : <?php echo esc_html($post->post_title); ?>
        <?php endif; ?>
        <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="page-title-action">
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
            
            <!-- Formulaire - COPIER EXACTEMENT de internal-editor.php -->
            <form method="post" action="">
                <?php wp_nonce_field('sisme_edit_form', 'sisme_edit_nonce'); ?>
                <input type="hidden" name="sisme_edit_action" value="<?php echo $is_creation_mode ? 'create_fiche' : 'update_fiche'; ?>">
                <?php if (!$is_creation_mode) : ?>
                    <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <?php endif; ?>
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
                                   class="large-text" 
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
                    
                    <!-- Catégories -->
                    <tr>
                        <th scope="row">Catégories de jeux *</th>
                        <td>
                            <fieldset style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px;">
                                <?php 
                                // Trier les catégories par ordre alphabétique
                                usort($available_jeux_categories, function($a, $b) {
                                    $nameA = str_replace('jeux-', '', $a->name);
                                    $nameB = str_replace('jeux-', '', $b->name);
                                    return strcasecmp($nameA, $nameB);
                                });
                                
                                foreach ($available_jeux_categories as $category) : ?>
                                    <label style="display: flex; align-items: center; margin: 0; padding: 4px 0;">
                                        <input type="checkbox" 
                                               name="game_categories[]" 
                                               value="<?php echo $category->term_id; ?>"
                                               <?php checked(in_array($category->term_id, $selected_categories)); ?>
                                               style="margin-right: 8px;">
                                        <?php echo esc_html(str_replace('jeux-', '', $category->name)); ?>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Mode de jeu *</th>
                        <td>
                            <fieldset style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px;">
                                <?php 
                                $available_modes = array('solo' => 'Solo', 'multijoueur' => 'Multijoueur', 'cooperatif' => 'Coopératif', 'competitif' => 'Compétitif');
                                foreach ($available_modes as $value => $label) : ?>
                                    <label style="display: flex; align-items: center; margin: 0; padding: 4px 0;">
                                        <input type="checkbox" 
                                               name="game_modes[]" 
                                               value="<?php echo $value; ?>"
                                               <?php checked(in_array($value, $game_modes)); ?>
                                               style="margin-right: 8px;">
                                        <?php echo $label; ?>
                                    </label>
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
                            <fieldset style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px;">
                                <?php 
                                $available_platforms = array('pc' => 'PC', 'mac' => 'Mac', 'xbox' => 'Xbox', 'playstation' => 'PlayStation', 'switch' => 'Nintendo Switch');
                                foreach ($available_platforms as $value => $label) : ?>
                                    <label style="display: flex; align-items: center; margin: 0; padding: 4px 0;">
                                        <input type="checkbox" 
                                               name="platforms[]" 
                                               value="<?php echo $value; ?>"
                                               <?php checked(in_array($value, $platforms)); ?>
                                               style="margin-right: 8px;">
                                        <?php echo $label; ?>
                                    </label>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>

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
                                    <input type="text" id="dev_name" class="large-text" placeholder="Nom du développeur">
                                </p>
                                <p>
                                    <input type="url" id="dev_url" class="large-text" placeholder="Site web (optionnel)">
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
                                    <input type="text" id="editor_name" class="large-text" placeholder="Nom de l'éditeur">
                                </p>
                                <p>
                                    <input type="url" id="editor_url" class="large-text" placeholder="Site web (optionnel)">
                                </p>
                                <button type="button" id="add-editor" class="button">Ajouter</button>
                            </div>
                        </td>
                    </tr>
                    
                    <!-- Trailer -->
                    <tr>
                        <th scope="row"><label for="trailer_url">Lien du trailer</label></th>
                        <td>
                            <input type="url" 
                                   id="trailer_url" 
                                   name="trailer_url" 
                                   class="large-text"
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
                                   class="large-text"
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
                                   class="large-text"
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
                                   class="large-text"
                                   value="<?php echo esc_attr($gog_url); ?>"
                                   placeholder="https://www.gog.com/game/...">
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
                        </td>
                    </tr>
                    
                    <!-- Étiquette principale -->
                    <tr>
                        <th scope="row">Étiquette principale</th>
                        <td>
                            <select name="main_tag" class="large-text" style="width: 100%;">
                                <option value="">Sélectionner...</option>
                                <?php
                                $existing_tags = get_tags(array('hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC'));
                                foreach ($existing_tags as $tag) {
                                    echo '<option value="' . esc_attr($tag->term_id) . '"' . selected($main_tag, $tag->term_id, false) . '>' . esc_html($tag->name) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    
                    <!-- Images des blocs Test/News -->
                    <tr>
                        <th scope="row"><label>Image du bloc Test</label></th>
                        <td>
                            <div id="test-image-preview" style="margin-bottom: 10px;">
                                <?php if (!empty($test_image_id)) : ?>
                                    <?php 
                                    $test_image_data = wp_get_attachment_image_src($test_image_id, 'medium');
                                    if ($test_image_data) : ?>
                                        <img src="<?php echo esc_url($test_image_data[0]); ?>" style="max-width: 200px; height: auto;">
                                        <br><strong>Bloc Test activé !</strong>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <em>Aucune image sélectionnée - Bloc inactif</em>
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
                                        <img src="<?php echo esc_url($news_image_data[0]); ?>" style="max-width: 200px; height: auto;">
                                        <br><strong>Bloc News activé !</strong>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <em>Aucune image sélectionnée - Bloc inactif</em>
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
                        </td>
                    </tr>

                </table>
                
                <!-- Présentation complète du jeu -->
                <h2>Présentation complète du jeu</h2>
                <p>Ajoutez des sections personnalisées pour structurer le contenu détaillé de votre fiche.</p>
                
                <div id="sections-container">
                    <?php foreach ($existing_sections as $index => $section) : ?>
                        <?php if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])) : ?>
                            <div class="section-item" data-section="<?php echo $index; ?>" style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 5px; background: #f9f9f9;">
                                <h3 style="margin-top: 0;">Section <?php echo ($index + 1); ?></h3>
                                
                                <table class="form-table">
                                    <!-- Titre de la section -->
                                    <tr>
                                        <th scope="row"><label for="section_<?php echo $index; ?>_title">Titre</label></th>
                                        <td>
                                            <input type="text" 
                                                   id="section_<?php echo $index; ?>_title" 
                                                   name="sections[<?php echo $index; ?>][title]" 
                                                   class="large-text"
                                                   value="<?php echo esc_attr($section['title']); ?>"
                                                   placeholder="Titre de la section">
                                        </td>
                                    </tr>
                                    
                                    <!-- Contenu de la section -->
                                    <tr>
                                        <th scope="row"><label for="section_<?php echo $index; ?>_content">Contenu</label></th>
                                        <td>
                                            <textarea id="section_<?php echo $index; ?>_content" 
                                                      name="sections[<?php echo $index; ?>][content]" 
                                                      rows="6" 
                                                      cols="50" 
                                                      class="large-text"><?php echo esc_textarea($section['content']); ?></textarea>
                                            <p class="description">Contenu texte de la section</p>
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
                <p class="submit">
                    <?php if ($is_creation_mode) : ?>
                        <input type="submit" value="🚀 Créer la fiche" class="button-primary" id="create-fiche">
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
                    <p style="margin: 0 0 20px;">L'aperçu sera disponible après la création de la fiche</p>
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
                
                <iframe id="fiche-preview" 
                        src="<?php echo esc_url($post_url); ?>" 
                        style="width: 100%; height: 75vh; border: 1px solid #ccc; border-radius: 4px;"
                        frameborder="0">
                </iframe>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Gestion du redimensionnement du split
    $('.split-btn').click(function() {
        var split = $(this).data('split');
        var parts = split.split('-');
        var leftSize = parts[0] + '%';
        var rightSize = parts[1] + '%';
        
        // Mettre à jour la grille CSS
        $('#split-container').css('grid-template-columns', leftSize + ' ' + rightSize);
        
        // Mettre à jour les boutons actifs
        $('.split-btn').removeClass('button-primary');
        $(this).addClass('button-primary');
        
        // Sauvegarder la préférence dans localStorage
        try {
            localStorage.setItem('sisme_split_preference', split);
        } catch(e) {
            // Ignore si localStorage n'est pas disponible
        }
    });
    
    // Restaurer la préférence de split au chargement
    try {
        var savedSplit = localStorage.getItem('sisme_split_preference');
        if (savedSplit) {
            var $btn = $('.split-btn[data-split="' + savedSplit + '"]');
            if ($btn.length) {
                $btn.click();
            }
        }
    } catch(e) {
        // Ignore si localStorage n'est pas disponible
    }
    
    // Actualiser l'iframe
    $('#refresh-preview, #save-and-refresh, #refresh-preview-only').click(function() {
        var isFormSubmit = $(this).attr('id') === 'save-and-refresh';
        
        if (isFormSubmit) {
            // Pour la sauvegarde, attendre un peu avant d'actualiser
            setTimeout(function() {
                refreshIframe();
            }, 1000);
        } else {
            // Pour l'actualisation simple, faire immédiatement
            refreshIframe();
        }
    });
    
    function refreshIframe() {
        var iframe = $('#fiche-preview');
        var currentSrc = iframe.attr('src');
        var separator = currentSrc.indexOf('?') > -1 ? '&' : '?';
        var newSrc = currentSrc.split('?')[0] + separator + 'preview_refresh=' + Date.now();
        iframe.attr('src', newSrc);
        
        // Feedback visuel
        $('#refresh-preview, #refresh-preview-only').text('🔄 Actualisation...');
        setTimeout(function() {
            $('#refresh-preview, #refresh-preview-only').text('🔄 Actualiser');
        }, 1500);
    }
    
    // Ouvrir dans un nouvel onglet
    $('#open-in-new-tab').click(function() {
        var iframe = $('#fiche-preview');
        var iframeSrc = iframe.attr('src');
        window.open(iframeSrc, '_blank');
    });
    
    // Auto-actualisation après sauvegarde réussie
    $('form').on('submit', function() {
        $('#save-and-refresh').val('💾 Sauvegarde...');
    });

    // Variables pour développeurs et éditeurs
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
            $('#featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
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
            $('#test-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;"><br><strong>Bloc Test activé !</strong>');
            $('#select-test-image').text('Changer l\'image du test');
            $('#remove-test-image').show();
        });
        
        mediaUploader.open();
    });
    
    // Supprimer l'image du bloc Test
    $('#remove-test-image').click(function(e) {
        e.preventDefault();
        $('#test_image_id').val('');
        $('#test-image-preview').html('<em>Aucune image sélectionnée - Bloc inactif</em>');
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
            $('#news-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;"><br><strong>Bloc News activé !</strong>');
            $('#select-news-image').text('Changer l\'image des news');
            $('#remove-news-image').show();
        });
        
        mediaUploader.open();
    });
    
    // Supprimer l'image du bloc News
    $('#remove-news-image').click(function(e) {
        e.preventDefault();
        $('#news_image_id').val('');
        $('#news-image-preview').html('<em>Aucune image sélectionnée - Bloc inactif</em>');
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

    // AJOUTER APRÈS LES AUTRES FONCTIONS JAVASCRIPT
    
    // Gestion des sections personnalisées
    var sectionCounter = <?php 
    $actual_count = 0;
    foreach ($existing_sections as $section) {
        if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])) {
            $actual_count++;
        }
    }
    echo $actual_count;
    ?>;

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
                        <th scope="row"><label for="section_${sectionCounter}_title">Titre</label></th>
                        <td>
                            <input type="text" 
                                   id="section_${sectionCounter}_title" 
                                   name="sections[${sectionCounter}][title]" 
                                   class="large-text"
                                   value=""
                                   placeholder="Titre de la section">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="section_${sectionCounter}_content">Contenu</label></th>
                        <td>
                            <textarea id="section_${sectionCounter}_content" 
                                      name="sections[${sectionCounter}][content]" 
                                      rows="6" 
                                      cols="50" 
                                      class="large-text"></textarea>
                            <p class="description">Contenu texte de la section</p>
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

<style>
/* Responsive pour le split-screen */
@media (max-width: 1200px) {
    #split-container {
        grid-template-columns: 1fr !important;
        gap: 10px;
    }
    
    #form-panel {
        height: auto !important;
        max-height: 60vh;
    }
    
    #fiche-preview {
        height: 50vh !important;
    }
    
    .split-controls {
        display: none;
    }
}

/* Styles pour les boutons de split */
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

/* Amélioration de l'iframe */
#fiche-preview {
    transition: opacity 0.3s ease;
}

#fiche-preview.loading {
    opacity: 0.7;
}

/* Amélioration des panels */
#form-panel, #preview-panel {
    transition: all 0.3s ease;
}
</style>