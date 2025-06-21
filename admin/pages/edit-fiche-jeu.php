<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-fiche-jeu.php
 * Version Simple - Juste formulaire sections + données jeu
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure les modules nécessaires
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/fiche-creator.php';

// Récupérer l'ID du tag (jeu)
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

// Mode création ou édition
$is_edit_mode = $post_id > 0;
$existing_sections = array();

if ($is_edit_mode) {
    $post_data = get_post($post_id);
    if ($post_data) {
        $existing_sections = get_post_meta($post_id, '_sisme_game_sections', true) ?: array();
    }
}

// Messages
$success_message = '';
$error_message = '';

// Traitement du formulaire
if ($_POST['action'] === 'create_fiche' && check_admin_referer('sisme_fiche_sections')) {
    
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
            
            // Mettre à jour l'URL pour inclure post_id
            $new_url = add_query_arg('post_id', $post_id, remove_query_arg('post_id'));
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

<!-- Données du jeu (lecture seule) -->
<div class="sisme-card">
    <div class="sisme-card__header sisme-card__header--flex">
        <h3 class="sisme-card__title">🎮 Données du jeu</h3>
        <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-game-data&tag_id=' . $tag_id); ?>" 
           class="sisme-btn sisme-btn--small sisme-btn--secondary">
            ✏️ Modifier
        </a>
    </div>
    <div class="sisme-card__body">
        <div class="sisme-readonly-grid">
            <div class="sisme-readonly-field">
                <label class="sisme-readonly-label">Titre</label>
                <div class="sisme-readonly-value">
                    <strong><?php echo esc_html($game_data['title']); ?></strong>
                </div>
            </div>
            
            <?php if (!empty($game_data['description'])): ?>
            <div class="sisme-readonly-field">
                <label class="sisme-readonly-label">Description</label>
                <div class="sisme-readonly-value">
                    <?php echo wp_trim_words(wp_kses_post($game_data['description']), 20); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($game_data['release_date'])): ?>
            <div class="sisme-readonly-field">
                <label class="sisme-readonly-label">Date de sortie</label>
                <div class="sisme-readonly-value">
                    <?php echo esc_html(date('d/m/Y', strtotime($game_data['release_date']))); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Formulaire des sections -->
<div class="sisme-card">
    <div class="sisme-card__header">
        <h3 class="sisme-card__title">📝 Sections de présentation</h3>
    </div>
    <div class="sisme-card__body">
        <form method="post" action="">
            <?php wp_nonce_field('sisme_fiche_sections'); ?>
            <input type="hidden" name="action" value="create_fiche">
            
            <div id="sections-container" class="sisme-sections-container">
                <?php if (!empty($existing_sections)): ?>
                    <?php foreach ($existing_sections as $index => $section): ?>
                        <div class="sisme-card section-item" data-index="<?php echo $index; ?>">
                            <div class="sisme-card__header sisme-card__header--flex">
                                <h4 class="sisme-card__title">Section <?php echo ($index + 1); ?></h4>
                                <button type="button" class="sisme-btn sisme-btn--danger sisme-btn--small remove-section">
                                    🗑️ Supprimer
                                </button>
                            </div>
                            
                            <div class="sisme-card__body">
                                <div class="sisme-form-group">
                                    <label class="sisme-form-label">Titre de la section</label>
                                    <input type="text" 
                                           name="sections[<?php echo $index; ?>][title]" 
                                           value="<?php echo esc_attr($section['title'] ?? ''); ?>" 
                                           class="sisme-form-input"
                                           placeholder="Ex: Gameplay, Histoire, Graphismes...">
                                </div>
                                
                                <div class="sisme-form-group">
                                    <label class="sisme-form-label">Contenu</label>
                                    <textarea name="sections[<?php echo $index; ?>][content]" 
                                              rows="6" 
                                              class="sisme-form-textarea"
                                              placeholder="Décrivez cette section du jeu..."><?php echo esc_textarea($section['content'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="sisme-form-group">
                                    <label class="sisme-form-label">Image de la section</label>
                                    <div class="sisme-media-selector">
                                        <input type="hidden" 
                                               name="sections[<?php echo $index; ?>][image_id]" 
                                               value="<?php echo esc_attr($section['image_id'] ?? 0); ?>"
                                               class="section-image-id">
                                        
                                        <div class="sisme-media-preview">
                                            <?php if (!empty($section['image_id'])): ?>
                                                <?php echo wp_get_attachment_image($section['image_id'], 'medium', false, array('class' => 'sisme-media-preview__image')); ?>
                                            <?php else: ?>
                                                <div class="sisme-media-preview__empty">
                                                    📷 Cliquez pour choisir une image
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="sisme-media-actions">
                                            <button type="button" class="sisme-btn sisme-btn--secondary select-image">
                                                📷 Choisir une image
                                            </button>
                                            <button type="button" class="sisme-btn sisme-btn--danger remove-image" 
                                                    style="<?php echo !empty($section['image_id']) ? '' : 'display:none;'; ?>">
                                                🗑️ Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="sisme-card section-item" data-index="0">
                        <div class="sisme-card__header sisme-card__header--flex">
                            <h4 class="sisme-card__title">Section 1</h4>
                            <button type="button" class="sisme-btn sisme-btn--danger sisme-btn--small remove-section">
                                🗑️ Supprimer
                            </button>
                        </div>
                        
                        <div class="sisme-card__body">
                            <div class="sisme-form-group">
                                <label class="sisme-form-label">Titre de la section</label>
                                <input type="text" 
                                       name="sections[0][title]" 
                                       value="" 
                                       class="sisme-form-input"
                                       placeholder="Ex: Gameplay, Histoire, Graphismes...">
                            </div>
                            
                            <div class="sisme-form-group">
                                <label class="sisme-form-label">Contenu</label>
                                <textarea name="sections[0][content]" 
                                          rows="6" 
                                          class="sisme-form-textarea"
                                          placeholder="Décrivez cette section du jeu..."></textarea>
                            </div>
                            
                            <div class="sisme-form-group">
                                <label class="sisme-form-label">Image de la section</label>
                                <div class="sisme-media-selector">
                                    <input type="hidden" 
                                           name="sections[0][image_id]" 
                                           value=""
                                           class="section-image-id">
                                    
                                    <div class="sisme-media-preview">
                                        <div class="sisme-media-preview__empty">
                                            📷 Cliquez pour choisir une image
                                        </div>
                                    </div>
                                    
                                    <div class="sisme-media-actions">
                                        <button type="button" class="sisme-btn sisme-btn--secondary select-image">
                                            📷 Choisir une image
                                        </button>
                                        <button type="button" class="sisme-btn sisme-btn--danger remove-image" style="display:none;">
                                            🗑️ Supprimer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sisme-form-actions">
                <button type="button" id="add-section" class="sisme-btn sisme-btn--secondary">
                    ➕ Ajouter une section
                </button>
            </div>
            
            <div class="sisme-form-actions">
                <button type="submit" name="submit_sections" class="sisme-btn sisme-btn--primary">
                    <?php echo $is_edit_mode ? '💾 Mettre à jour' : '✨ Créer la fiche'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    let sectionIndex = <?php echo count($existing_sections) ?: 1; ?>;
    
    // Ajouter une section
    $('#add-section').click(function() {
        const newSection = `
            <div class="sisme-card section-item" data-index="${sectionIndex}">
                <div class="sisme-card__header sisme-card__header--flex">
                    <h4 class="sisme-card__title">Section ${sectionIndex + 1}</h4>
                    <button type="button" class="sisme-btn sisme-btn--danger sisme-btn--small remove-section">
                        🗑️ Supprimer
                    </button>
                </div>
                
                <div class="sisme-card__body">
                    <div class="sisme-form-group">
                        <label class="sisme-form-label">Titre de la section</label>
                        <input type="text" 
                               name="sections[${sectionIndex}][title]" 
                               value="" 
                               class="sisme-form-input"
                               placeholder="Ex: Gameplay, Histoire, Graphismes...">
                    </div>
                    
                    <div class="sisme-form-group">
                        <label class="sisme-form-label">Contenu</label>
                        <textarea name="sections[${sectionIndex}][content]" 
                                  rows="6" 
                                  class="sisme-form-textarea"
                                  placeholder="Décrivez cette section du jeu..."></textarea>
                    </div>
                    
                    <div class="sisme-form-group">
                        <label class="sisme-form-label">Image de la section</label>
                        <div class="sisme-media-selector">
                            <input type="hidden" 
                                   name="sections[${sectionIndex}][image_id]" 
                                   value=""
                                   class="section-image-id">
                            
                            <div class="sisme-media-preview">
                                <div class="sisme-media-preview__empty">📷 Cliquez pour choisir une image</div>
                            </div>
                            
                            <div class="sisme-media-actions">
                                <button type="button" class="sisme-btn sisme-btn--secondary select-image">
                                    📷 Choisir une image
                                </button>
                                <button type="button" class="sisme-btn sisme-btn--danger remove-image" style="display:none;">
                                    🗑️ Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#sections-container').append(newSection);
        sectionIndex++;
        updateSectionNumbers();
    });
    
    // Supprimer une section
    $(document).on('click', '.remove-section', function() {
        if ($('.section-item').length > 1) {
            $(this).closest('.section-item').remove();
            updateSectionNumbers();
        } else {
            alert('Il faut au moins une section.');
        }
    });
    
    // Mettre à jour la numérotation
    function updateSectionNumbers() {
        $('.section-item').each(function(index) {
            $(this).find('.sisme-card__title').text('Section ' + (index + 1));
        });
    }
    
    // Sélecteur d'image WordPress
    $(document).on('click', '.select-image', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const imageSelector = button.closest('.sisme-media-selector');
        
        const mediaUploader = wp.media({
            title: 'Choisir une image pour la section',
            button: {
                text: 'Sélectionner cette image'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            
            imageSelector.find('.section-image-id').val(attachment.id);
            imageSelector.find('.sisme-media-preview').html(
                `<img src="${attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url}" 
                      alt="${attachment.alt}" class="sisme-media-preview__image">`
            );
            imageSelector.find('.remove-image').show();
        });
        
        mediaUploader.open();
    });
    
    // Supprimer l'image
    $(document).on('click', '.remove-image', function() {
        const imageSelector = $(this).closest('.sisme-media-selector');
        imageSelector.find('.section-image-id').val('');
        imageSelector.find('.sisme-media-preview').html('<div class="sisme-media-preview__empty">📷 Cliquez pour choisir une image</div>');
        $(this).hide();
    });
});
</script>

<?php
$page->render_end();
?>