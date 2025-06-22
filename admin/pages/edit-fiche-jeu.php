<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-fiche-jeu.php
 * Page de création/édition de fiche - VERSION ÉPURÉE
 * 
 * MODIFICATIONS APPORTÉES:
 * 1. Structure HTML plus propre avec classes CSS gaming
 * 2. Affichage des infos du jeu en header élégant
 * 3. Organisation du formulaire en sections claires
 * 4. Préservation totale du fonctionnel existant
 * 
 * ATTENTION: Ce fichier remplace l'ancien edit-fiche-jeu.php
 * Tester en mode backup avant de remplacer !
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure les modules nécessaires
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/fiche-creator.php';

// Récupérer l'ID du tag (jeu) - TOUJOURS depuis $_GET
$tag_id = isset($_GET['tag_id']) ? intval($_GET['tag_id']) : 0;
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

// Vérifier que le tag existe
if (!$tag_id) {
    wp_die('Erreur : Aucun jeu spécifié.');
}

$tag_data = get_term($tag_id, 'post_tag');
if (is_wp_error($tag_data) || !$tag_data) {
    wp_die('Erreur : Jeu non trouvé.');
}

// Récupérer les Game Data pour affichage
$game_data = array(
    'title' => $tag_data->name,
    'description' => get_term_meta($tag_id, 'game_description', true),
    'genres' => get_term_meta($tag_id, 'game_genres', true) ?: array(),
    'modes' => get_term_meta($tag_id, 'game_modes', true) ?: array(),
    'platforms' => get_term_meta($tag_id, 'game_platforms', true) ?: array(),
    'release_date' => get_term_meta($tag_id, 'release_date', true),
);

// Récupérer les sections par défaut du jeu
$default_game_sections = get_term_meta($tag_id, 'game_sections', true) ?: array();

// Messages
$success_message = '';
$error_message = '';

// Traitement du formulaire EN PREMIER
if (isset($_POST['action']) && $_POST['action'] === 'create_fiche' && check_admin_referer('sisme_fiche_sections')) {
    
    // Récupérer et nettoyer les sections
    $sections = $_POST['sections'] ?? array();
    $clean_sections = array();
    
    if (is_array($sections)) {
        foreach ($sections as $section) {
            if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])) {
                $clean_sections[] = array(
                    'title' => sanitize_text_field($section['title'] ?? ''),
                    'content' => wp_kses_post($section['content'] ?? ''),
                    'image_id' => intval($section['image_id'] ?? 0)
                );
            }
        }
    }
    
    // Redéterminer le mode en fonction des paramètres actuels
    $current_is_edit_mode = $post_id > 0;
    
    if ($current_is_edit_mode) {
        // Mode édition : mettre à jour
        $result = Sisme_Fiche_Creator::update_fiche($post_id, $clean_sections);
        
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
        
    } else {
        // Mode création : créer nouvelle fiche
        $result = Sisme_Fiche_Creator::create_fiche($tag_id, $clean_sections);
        
        if ($result['success']) {
            $success_message = $result['message'];
            
            // Mettre à jour post_id et rediriger
            $post_id = $result['post_id'];
            $redirect_url = add_query_arg(array(
                'tag_id' => $tag_id,
                'post_id' => $post_id,
                'page' => 'sisme-games-edit-fiche-jeu'
            ), admin_url('admin.php'));
            
            wp_redirect($redirect_url);
            exit;
            
        } else {
            $error_message = $result['message'];
        }
    }
}

// MAINTENANT déterminer le mode pour l'affichage
$is_edit_mode = $post_id > 0;

// Si pas de post_id fourni, vérifier si une fiche existe déjà pour ce jeu
if (!$is_edit_mode) {
    $existing_fiche_id = Sisme_Fiche_Creator::find_existing_fiche($tag_id);
    
    // Si pas trouvé avec la fonction officielle, chercher manuellement
    if (!$existing_fiche_id) {
        $manual_search = get_posts(array(
            'tag_id' => $tag_id,
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => 1
        ));
        
        if (!empty($manual_search)) {
            $existing_fiche_id = $manual_search[0]->ID;
        }
    }
    
    if ($existing_fiche_id) {
        $post_id = $existing_fiche_id;
        $is_edit_mode = true;
        
        // Mettre à jour l'URL pour inclure post_id ET préserver tag_id
        $new_url = add_query_arg(array(
            'post_id' => $post_id,
            'tag_id' => $tag_id
        ));
        echo '<script>window.history.replaceState({}, "", "' . esc_js($new_url) . '");</script>';
    }
}

$existing_sections = array();

if ($is_edit_mode) {
    $post_data = get_post($post_id);
    if ($post_data) {
        $existing_sections = get_post_meta($post_id, '_sisme_game_sections', true) ?: array();
    }
}

// Si pas de sections dans l'article (création ou article sans sections), 
// utiliser les sections par défaut du jeu
if (empty($existing_sections) && !empty($default_game_sections)) {
    $existing_sections = $default_game_sections;
}

// Configuration de la page avec wrapper épuré
$page = new Sisme_Admin_Page_Wrapper(
    $is_edit_mode ? 'Modifier la fiche : ' . $tag_data->name : 'Créer la fiche : ' . $tag_data->name,
    'Édition des sections de présentation du jeu',
    'edit', // Icône 📝
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour à Game Data'
);

$page->render_start();
?>

<!-- Messages de feedback -->
<?php if (!empty($success_message) || !empty($error_message)): ?>
    <div class="sisme-fiche-notices">
        <?php if (!empty($success_message)): ?>
            <div class="sisme-notice sisme-notice--success">
                ✅ <?php echo esc_html($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error_message)): ?>
            <div class="sisme-notice sisme-notice--error">
                ❌ <?php echo esc_html($error_message); ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Informations du jeu - Header élégant -->
<div class="sisme-fiche-game-info">
    <div class="sisme-fiche-game-info__header">
        <h2 class="sisme-fiche-game-info__title">
            🎮 <?php echo esc_html($game_data['title']); ?>
        </h2>
        <div class="sisme-fiche-game-info__meta">
            <span>Tag ID: <?php echo $tag_id; ?></span>
            <?php if ($is_edit_mode): ?>
                <span>Article ID: <?php echo $post_id; ?></span>
                <span>Mode: Édition</span>
            <?php else: ?>
                <span>Mode: Création</span>
            <?php endif; ?>
            <?php if (!empty($game_data['release_date'])): ?>
                <span>Sortie: <?php echo esc_html($game_data['release_date']); ?></span>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($game_data['description'])): ?>
        <div class="sisme-fiche-game-info__body">
            <div class="sisme-fiche-game-description">
                <?php echo wp_kses_post($game_data['description']); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Formulaire des sections - Structure épurée -->
<div class="sisme-sections-form">
    <div class="sisme-sections-form__header">
        <h3 class="sisme-sections-form__title">
            📝 Sections de présentation
        </h3>
    </div>
    
    <div class="sisme-sections-form__body">
        <form method="post">
            <?php wp_nonce_field('sisme_fiche_sections'); ?>
            <input type="hidden" name="action" value="create_fiche">
            
            <div id="sections-container">
                <?php if (!empty($existing_sections)): ?>
                    <?php foreach ($existing_sections as $index => $section): ?>
                        <div class="sisme-section-item">
                            <div class="sisme-section-item__header">
                                <h4 class="sisme-section-item__title">
                                    Section <?php echo ($index + 1); ?>
                                </h4>
                                <?php if ($index > 0): // Pas de suppression pour la première section ?>
                                    <button type="button" class="sisme-btn sisme-btn--danger sisme-btn--icon remove-section" 
                                            data-sisme-tooltip="Supprimer cette section">
                                        🗑️
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="sisme-section-item__body">
                                <div class="sisme-field-group">
                                    <label class="sisme-field-label">
                                        <strong>Titre de la section:</strong>
                                    </label>
                                    <input type="text" 
                                           name="sections[<?php echo $index; ?>][title]" 
                                           value="<?php echo esc_attr($section['title'] ?? ''); ?>" 
                                           class="sisme-field-input"
                                           placeholder="Ex: Gameplay, Histoire, Graphismes...">
                                </div>
                                
                                <div class="sisme-field-group">
                                    <label class="sisme-field-label">
                                        <strong>Contenu:</strong>
                                    </label>
                                    <textarea name="sections[<?php echo $index; ?>][content]" 
                                              rows="6" 
                                              class="sisme-field-textarea"
                                              placeholder="Décrivez cette section du jeu..."><?php echo esc_textarea($section['content'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="sisme-field-group">
                                    <label class="sisme-field-label">
                                        <strong>Image de la section:</strong>
                                    </label>
                                    <div class="section-image-container">
                                        <?php 
                                        $image_id = intval($section['image_id'] ?? 0);
                                        ?>
                                        <div id="section-<?php echo $index; ?>-image-preview" class="sisme-image-preview">
                                            <?php if ($image_id > 0): ?>
                                                <?php 
                                                $image = wp_get_attachment_image_src($image_id, 'medium');
                                                $attachment = get_post($image_id);
                                                ?>
                                                <img src="<?php echo esc_url($image[0]); ?>" style="max-width: 200px; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" alt="Aperçu">
                                                <div style="margin-top: 8px; font-size: 12px; color: #666;">
                                                    <strong><?php echo esc_html($attachment->post_title); ?></strong><br>
                                                    <?php echo $image[1]; ?> × <?php echo $image[2]; ?> px
                                                </div>
                                            <?php else: ?>
                                                <em style="color: #666;">Aucune image sélectionnée</em>
                                            <?php endif; ?>
                                        </div>
                                        <div class="sisme-image-buttons">
                                            <button type="button" class="sisme-btn sisme-btn--secondary sisme-btn--icon select-section-image" 
                                                    data-section="<?php echo $index; ?>"
                                                    data-sisme-tooltip="<?php echo ($image_id > 0) ? 'Changer l\'image' : 'Choisir une image'; ?>">
                                                <?php echo ($image_id > 0) ? '🔄' : '🖼️'; ?>
                                            </button>
                                            <button type="button" class="sisme-btn sisme-btn--danger sisme-btn--icon remove-section-image" 
                                                    data-section="<?php echo $index; ?>" 
                                                    data-sisme-tooltip="Supprimer l'image"
                                                    style="<?php echo ($image_id > 0) ? '' : 'display: none;'; ?>">
                                                🗑️
                                            </button>
                                        </div>
                                        <input type="hidden" id="section_<?php echo $index; ?>_image_id" name="sections[<?php echo $index; ?>][image_id]" value="<?php echo $image_id; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Section par défaut si aucune section existante -->
                    <div class="sisme-section-item">
                        <div class="sisme-section-item__header">
                            <h4 class="sisme-section-item__title">
                                Section 1
                            </h4>
                        </div>
                        
                        <div class="sisme-section-item__body">
                            <div class="sisme-field-group">
                                <label class="sisme-field-label">
                                    <strong>Titre de la section:</strong>
                                </label>
                                <input type="text" 
                                       name="sections[0][title]" 
                                       value="" 
                                       class="sisme-field-input"
                                       placeholder="Ex: Gameplay, Histoire, Graphismes...">
                            </div>
                            
                            <div class="sisme-field-group">
                                <label class="sisme-field-label">
                                    <strong>Contenu:</strong>
                                </label>
                                <textarea name="sections[0][content]" 
                                          rows="6" 
                                          class="sisme-field-textarea"
                                          placeholder="Décrivez cette section du jeu..."></textarea>
                            </div>
                            
                            <div class="sisme-field-group">
                                <label class="sisme-field-label">
                                    <strong>Image de la section:</strong>
                                </label>
                                <div class="section-image-container">
                                    <div id="section-0-image-preview" class="sisme-image-preview">
                                        <em style="color: #666;">Aucune image sélectionnée</em>
                                    </div>
                                    <div class="sisme-image-buttons">
                                        <button type="button" class="sisme-btn sisme-btn--secondary sisme-btn--icon select-section-image" 
                                                data-section="0"
                                                data-sisme-tooltip="Choisir une image">
                                            🖼️
                                        </button>
                                        <button type="button" class="sisme-btn sisme-btn--danger sisme-btn--icon remove-section-image" 
                                                data-section="0" 
                                                data-sisme-tooltip="Supprimer l'image"
                                                style="display: none;">
                                            🗑️
                                        </button>
                                    </div>
                                    <input type="hidden" id="section_0_image_id" name="sections[0][image_id]" value="0">
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Zone d'ajout de nouvelle section -->
                <div class="sisme-add-section-zone">
                    <button type="button" id="add-new-section" class="sisme-btn sisme-btn--primary sisme-btn--large sisme-btn--icon"
                            data-sisme-tooltip="Ajouter une nouvelle section">
                        ➕
                    </button>
                    <p class="sisme-add-section-help">
                        Ajouter une section personnalisée
                    </p>
                </div>
            </div>
            
            <!-- Actions du formulaire -->
            <div class="sisme-fiche-form-actions">
                <input type="submit" 
                       name="submit" 
                       class="button-primary" 
                       value="<?php echo $is_edit_mode ? 'Mettre à jour la fiche' : 'Créer la fiche'; ?>">
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var sectionCounter = <?php echo count($existing_sections); ?>;
    
    // Initialiser le sélecteur de médias pour les sections existantes
    $('.select-section-image').each(function() {
        initMediaUploader($(this));
    });
    
    // Ajouter nouvelle section
    $('#add-new-section').click(function() {
        var newSectionHtml = `
            <div class="sisme-section-item" style="animation: sismeSectionFadeIn 0.3s ease-out;">
                <div class="sisme-section-item__header">
                    <h4 class="sisme-section-item__title">
                        Section ${sectionCounter + 1}
                    </h4>
                    <button type="button" class="sisme-btn sisme-btn--danger sisme-btn--icon remove-section" 
                            data-sisme-tooltip="Supprimer cette section">
                        🗑️
                    </button>
                </div>
                
                <div class="sisme-section-item__body">
                    <div class="sisme-field-group">
                        <label class="sisme-field-label">
                            <strong>Titre de la section:</strong>
                        </label>
                        <input type="text" 
                               name="sections[${sectionCounter}][title]" 
                               value="" 
                               class="sisme-field-input"
                               placeholder="Ex: Gameplay, Histoire, Graphismes...">
                    </div>
                    
                    <div class="sisme-field-group">
                        <label class="sisme-field-label">
                            <strong>Contenu:</strong>
                        </label>
                        <textarea name="sections[${sectionCounter}][content]" 
                                  rows="6" 
                                  class="sisme-field-textarea"
                                  placeholder="Décrivez cette section du jeu..."></textarea>
                    </div>
                    
                    <div class="sisme-field-group">
                        <label class="sisme-field-label">
                            <strong>Image de la section:</strong>
                        </label>
                        <div class="section-image-container">
                            <div id="section-${sectionCounter}-image-preview" class="sisme-image-preview">
                                <em style="color: #666;">Aucune image sélectionnée</em>
                            </div>
                            <div class="sisme-image-buttons">
                                <button type="button" class="sisme-btn sisme-btn--secondary sisme-btn--icon select-section-image" 
                                        data-section="${sectionCounter}"
                                        data-sisme-tooltip="Choisir une image">
                                    🖼️
                                </button>
                                <button type="button" class="sisme-btn sisme-btn--danger sisme-btn--icon remove-section-image" 
                                        data-section="${sectionCounter}" 
                                        data-sisme-tooltip="Supprimer l'image"
                                        style="display: none;">
                                    🗑️
                                </button>
                            </div>
                            <input type="hidden" id="section_${sectionCounter}_image_id" name="sections[${sectionCounter}][image_id]" value="0">
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Insérer la nouvelle section juste avant la zone d'ajout
        var $newSection = $(newSectionHtml).insertBefore('.sisme-add-section-zone');
        
        // Initialiser le sélecteur de médias pour la nouvelle section
        $newSection.find('.select-section-image').each(function() {
            initMediaUploader($(this));
        });
        
        sectionCounter++;
        
        // Scroll vers la nouvelle section
        $('html, body').animate({
            scrollTop: $newSection.offset().top - 100
        }, 500);
    });
    
    // Fonction pour initialiser le sélecteur de médias
    function initMediaUploader(button) {
        button.click(function(e) {
            e.preventDefault();
            
            var section = $(this).data('section');
            var mediaUploader;
            
            // Créer le media uploader
            mediaUploader = wp.media({
                title: 'Sélectionner une image pour cette section',
                button: {
                    text: 'Utiliser cette image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // Quand une image est sélectionnée
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                
                // Mettre à jour l'ID caché
                $('#section_' + section + '_image_id').val(attachment.id);
                
                // Afficher l'aperçu
                var previewHtml = `
                    <img src="${attachment.url}" style="max-width: 200px; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" alt="Aperçu">
                    <div style="margin-top: 8px; font-size: 12px; color: #666;">
                        <strong>${attachment.filename}</strong><br>
                        ${attachment.width} × ${attachment.height} px
                    </div>
                `;
                $('#section-' + section + '-image-preview').html(previewHtml);
                
                // Mettre à jour les boutons
                $(`.select-section-image[data-section="${section}"]`)
                    .html('🔄')
                    .attr('data-sisme-tooltip', 'Changer l\'image');
                $(`.remove-section-image[data-section="${section}"]`).show();
            });
            
            mediaUploader.open();
        });
    }
    
    // Supprimer image de section
    $(document).on('click', '.remove-section-image', function(e) {
        e.preventDefault();
        var section = $(this).data('section');
        
        $('#section_' + section + '_image_id').val('0');
        $('#section-' + section + '-image-preview').html('<em style="color: #666;">Aucune image sélectionnée</em>');
        $(`.select-section-image[data-section="${section}"]`)
            .html('🖼️')
            .attr('data-sisme-tooltip', 'Choisir une image');
        $(this).hide();
    });
    
    // Supprimer section (délégation d'événement pour les éléments dynamiques)
    $(document).on('click', '.remove-section', function() {
        if (confirm('Supprimer cette section ?')) {
            $(this).closest('.sisme-section-item').fadeOut(300, function() {
                $(this).remove();
            });
        }
    });
});
</script>

<?php
$page->render_end();
?>