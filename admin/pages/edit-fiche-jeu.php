<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-fiche-jeu.php
 * Version Simple - Juste formulaire sections + données jeu + DEBUG
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

// Récupérer les Game Data
$game_data = array(
    'title' => $tag_data->name,
    'description' => get_term_meta($tag_id, 'game_description', true),
    'genres' => get_term_meta($tag_id, 'game_genres', true) ?: array(),
    'modes' => get_term_meta($tag_id, 'game_modes', true) ?: array(),
    'platforms' => get_term_meta($tag_id, 'game_platforms', true) ?: array(),
    'release_date' => get_term_meta($tag_id, 'release_date', true),
);

// DEBUG: Récupérer aussi les sections par défaut du jeu
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

// Messages
$success_message = '';
$error_message = '';

// Traitement du formulaire
if (isset($_POST['action']) && $_POST['action'] === 'create_fiche' && check_admin_referer('sisme_fiche_sections')) {
    
    echo '<div style="background: yellow; padding: 10px; margin: 10px 0;">DEBUG: Traitement formulaire - Mode édition: ' . ($is_edit_mode ? 'OUI' : 'NON') . ' - Post ID: ' . $post_id . ' - Tag ID: ' . $tag_id . '</div>';
    
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
    
    if ($is_edit_mode) {
        // Mode édition : mettre à jour
        $result = Sisme_Fiche_Creator::update_fiche($post_id, $clean_sections);
        
        if ($result['success']) {
            $success_message = $result['message'];
            // Recharger les sections
            $existing_sections = get_post_meta($post_id, '_sisme_game_sections', true) ?: array();
        } else {
            $error_message = $result['message'];
        }
        
    } else {
        // Mode création : créer nouvelle fiche
        $result = Sisme_Fiche_Creator::create_fiche($tag_id, $clean_sections);
        
        if ($result['success']) {
            $success_message = $result['message'];
            
            // Basculer en mode édition
            $is_edit_mode = true;
            $post_id = $result['post_id'];
            $existing_sections = get_post_meta($post_id, '_sisme_game_sections', true) ?: array();
            
            // Mettre à jour l'URL pour inclure post_id ET préserver tag_id
            $new_url = add_query_arg(array(
                'post_id' => $post_id,
                'tag_id' => $tag_id
            ), remove_query_arg('post_id'));
            echo '<script>window.history.replaceState({}, "", "' . esc_js($new_url) . '");</script>';
            
        } else {
            $error_message = $result['message'];
        }
    }
}

// Configuration de la page
$page = new Sisme_Admin_Page_Wrapper(
    $is_edit_mode ? 'Modifier la fiche : ' . $tag_data->name : 'Créer la fiche : ' . $tag_data->name,
    'Édition des sections de présentation',
    'edit-pages',
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour à Game Data'
);

$page->render_start();
?>

<!-- Messages -->
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

<!-- Formulaire des sections -->
<form method="post">
    <?php wp_nonce_field('sisme_fiche_sections'); ?>
    <input type="hidden" name="action" value="create_fiche">
    
    <h3>Sections de présentation</h3>
    
    <div id="sections-container">
    <?php if (!empty($existing_sections)): ?>
        <?php foreach ($existing_sections as $index => $section): ?>
            <div style="border: 1px solid #ccc; margin: 10px 0; padding: 15px; background: #fff; border-radius: 5px;">
                <h4>Section <?php echo ($index + 1); ?></h4>
                
                <p>
                    <label><strong>Titre de la section:</strong></label><br>
                    <input type="text" name="sections[<?php echo $index; ?>][title]" 
                           value="<?php echo esc_attr($section['title'] ?? ''); ?>" 
                           style="width: 100%; padding: 8px;"
                           placeholder="Ex: Gameplay, Histoire, Graphismes...">
                </p>
                
                <p>
                    <label><strong>Contenu:</strong></label><br>
                    <textarea name="sections[<?php echo $index; ?>][content]" 
                              rows="6" 
                              style="width: 100%; padding: 8px;"
                              placeholder="Décrivez cette section du jeu..."><?php echo esc_textarea($section['content'] ?? ''); ?></textarea>
                </p>
                
                <p>
                    <label><strong>Image de la section:</strong></label><br>
                    <div class="section-image-container">
                        <div id="section-<?php echo $index; ?>-image-preview" style="margin-bottom: 10px; padding: 20px; border: 2px dashed #ddd; text-align: center; background: #f9f9f9; border-radius: 4px;">
                            <?php 
                            $image_id = intval($section['image_id'] ?? 0);
                            if ($image_id > 0) {
                                $image = wp_get_attachment_image_src($image_id, 'medium');
                                $image_meta = wp_get_attachment_metadata($image_id);
                                $filename = basename(get_attached_file($image_id));
                                
                                if ($image) {
                                    echo '<img src="' . esc_url($image[0]) . '" style="max-width: 200px; height: auto; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" alt="Aperçu">';
                                    echo '<div style="margin-top: 8px; font-size: 12px; color: #666;">';
                                    echo '<strong>' . esc_html($filename) . '</strong><br>';
                                    echo $image[1] . ' × ' . $image[2] . ' px';
                                    echo '</div>';
                                } else {
                                    echo '<em style="color: #666;">Image introuvable (ID: ' . $image_id . ')</em>';
                                }
                            } else {
                                echo '<em style="color: #666;">Aucune image sélectionnée</em>';
                            }
                            ?>
                        </div>
                        <button type="button" class="button select-section-image" data-section="<?php echo $index; ?>">
                            <?php echo ($image_id > 0) ? '🔄 Changer l\'image' : '🖼️ Choisir une image'; ?>
                        </button>
                        <button type="button" class="button remove-section-image" data-section="<?php echo $index; ?>" style="margin-left: 10px; <?php echo ($image_id > 0) ? '' : 'display: none;'; ?> background: #dc3232; color: white;">
                            🗑️ Supprimer l'image
                        </button>
                        <input type="hidden" id="section_<?php echo $index; ?>_image_id" name="sections[<?php echo $index; ?>][image_id]" value="<?php echo $image_id; ?>">
                    </div>
                </p>
            </div>
        <?php endforeach; ?>
        
        <!-- Bouton d'ajout de section -->
        <div style="text-align: center; padding: 20px; margin: 20px 0; border: 2px dashed #ccc; border-radius: 8px; background: #f9f9f9;">
            <button type="button" id="add-new-section" class="button button-secondary" style="font-size: 16px; padding: 10px 20px;">
                ➕ Ajouter une nouvelle section
            </button>
            <p style="margin: 10px 0 0; color: #666; font-size: 14px;">
                Cliquez pour ajouter une section de contenu personnalisée
            </p>
        </div>
        
    <?php else: ?>
        <!-- Section vide par défaut -->
        <div style="border: 1px solid #ccc; margin: 10px 0; padding: 15px; background: #fff; border-radius: 5px;">
            <h4>Section 1</h4>
            
            <p>
                <label><strong>Titre de la section:</strong></label><br>
                <input type="text" name="sections[0][title]" value="" style="width: 100%; padding: 8px;" placeholder="Ex: Gameplay, Histoire, Graphismes...">
            </p>
            
            <p>
                <label><strong>Contenu:</strong></label><br>
                <textarea name="sections[0][content]" rows="6" style="width: 100%; padding: 8px;" placeholder="Décrivez cette section du jeu..."></textarea>
            </p>
            
            <p>
                <label><strong>Image de la section:</strong></label><br>
                <div class="section-image-container">
                    <div id="section-0-image-preview" style="margin-bottom: 10px; padding: 20px; border: 2px dashed #ddd; text-align: center; background: #f9f9f9; border-radius: 4px;">
                        <em style="color: #666;">Aucune image sélectionnée</em>
                    </div>
                    <button type="button" class="button select-section-image" data-section="0">
                        🖼️ Choisir une image
                    </button>
                    <button type="button" class="button remove-section-image" data-section="0" style="margin-left: 10px; display: none; background: #dc3232; color: white;">
                        🗑️ Supprimer l'image
                    </button>
                    <input type="hidden" id="section_0_image_id" name="sections[0][image_id]" value="0">
                </div>
            </p>
        </div>
        
        <!-- Bouton d'ajout de section -->
        <div style="text-align: center; padding: 20px; margin: 20px 0; border: 2px dashed #ccc; border-radius: 8px; background: #f9f9f9;">
            <button type="button" id="add-new-section" class="button button-secondary" style="font-size: 16px; padding: 10px 20px;">
                ➕ Ajouter une nouvelle section
            </button>
            <p style="margin: 10px 0 0; color: #666; font-size: 14px;">
                Cliquez pour ajouter une section de contenu personnalisée
            </p>
        </div>
    <?php endif; ?>
</div>
    
    <p>
        <input type="submit" value="<?php echo $is_edit_mode ? 'Mettre à jour la fiche' : 'Créer la fiche'; ?>" class="button-primary">
    </p>
</form>
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
            <div style="border: 1px solid #ccc; margin: 10px 0; padding: 15px; background: #fff; border-radius: 5px;">
                <h4>Section ${sectionCounter + 1} 
                    <button type="button" class="button button-small remove-section" style="float: right; background: #dc3232; color: white;">
                        🗑️ Supprimer
                    </button>
                </h4>
                
                <p>
                    <label><strong>Titre de la section:</strong></label><br>
                    <input type="text" name="sections[${sectionCounter}][title]" 
                           value="" 
                           style="width: 100%; padding: 8px;" 
                           placeholder="Ex: Gameplay, Histoire, Graphismes...">
                </p>
                
                <p>
                    <label><strong>Contenu:</strong></label><br>
                    <textarea name="sections[${sectionCounter}][content]" 
                              rows="6" 
                              style="width: 100%; padding: 8px;" 
                              placeholder="Décrivez cette section du jeu..."></textarea>
                </p>
                
                <p>
                    <label><strong>Image de la section:</strong></label><br>
                    <div class="section-image-container">
                        <div id="section-${sectionCounter}-image-preview" style="margin-bottom: 10px; padding: 20px; border: 2px dashed #ddd; text-align: center; background: #f9f9f9; border-radius: 4px;">
                            <em style="color: #666;">Aucune image sélectionnée</em>
                        </div>
                        <button type="button" class="button select-section-image" data-section="${sectionCounter}">
                            🖼️ Choisir une image
                        </button>
                        <button type="button" class="button remove-section-image" data-section="${sectionCounter}" style="margin-left: 10px; display: none; background: #dc3232; color: white;">
                            🗑️ Supprimer l'image
                        </button>
                        <input type="hidden" id="section_${sectionCounter}_image_id" name="sections[${sectionCounter}][image_id]" value="0">
                    </div>
                </p>
            </div>
        `;
        
        // Insérer la nouvelle section juste avant le bouton d'ajout
        var $newSection = $(newSectionHtml).insertBefore($(this).closest('div'));
        
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
            
            // Si le media uploader existe déjà, le réutiliser
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
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
                $(`.select-section-image[data-section="${section}"]`).text('🔄 Changer l\'image');
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
        $(`.select-section-image[data-section="${section}"]`).text('🖼️ Choisir une image');
        $(this).hide();
    });
    
    // Supprimer section (délégation d'événement pour les éléments dynamiques)
    $(document).on('click', '.remove-section', function() {
        if (confirm('Supprimer cette section ?')) {
            $(this).closest('div').remove();
        }
    });
});
</script>
<?php $page->render_end(); ?>