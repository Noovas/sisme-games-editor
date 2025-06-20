<?php
/**
 * File: /sisme-games-editor/admin/pages/edit-fiche-jeu.php
 * Page: Création de Fiche de Jeu basée sur Game Data
 * 
 * Cette page permet de créer un article de fiche de jeu en utilisant
 * les métadonnées Game Data existantes. Seule la présentation complète
 * (sections) est éditable, le reste vient des Game Data.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure les modules nécessaires
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';

// Récupérer l'ID du tag (jeu) - OBLIGATOIRE
$tag_id = isset($_GET['tag_id']) ? intval($_GET['tag_id']) : 0;
$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;

// Vérifier que le tag existe
if (!$tag_id) {
    wp_die('Erreur : Aucun jeu spécifié. Cette page nécessite un tag_id.');
}

$tag_data = get_term($tag_id, 'post_tag');
if (is_wp_error($tag_data) || !$tag_data) {
    wp_die('Erreur : Jeu non trouvé.');
}

// Récupérer les métadonnées Game Data
$game_data = array(
    'description' => get_term_meta($tag_id, 'game_description', true),
    'genres' => get_term_meta($tag_id, 'game_genres', true),
    'modes' => get_term_meta($tag_id, 'game_modes', true),
    'developers' => get_term_meta($tag_id, 'game_developers', true),
    'publishers' => get_term_meta($tag_id, 'game_publishers', true),
    'platforms' => get_term_meta($tag_id, 'game_platforms', true),
    'release_date' => get_term_meta($tag_id, 'release_date', true),
    'external_links' => get_term_meta($tag_id, 'external_links', true),
    'cover_main' => get_term_meta($tag_id, 'cover_main', true),
);

// Déterminer si on est en mode création ou édition d'article
$is_edit_mode = $post_id > 0;
$post_data = null;
$existing_sections = array();

if ($is_edit_mode) {
    $post_data = get_post($post_id);
    if (!$post_data || $post_data->post_type !== 'post') {
        wp_die('Article non trouvé ou type invalide.');
    }
    $existing_sections = get_post_meta($post_id, '_sisme_game_sections', true) ?: array();
} else {
    // En mode création : récupérer les sections depuis Game Data (term_meta)
    $existing_sections = get_term_meta($tag_id, 'game_sections', true) ?: array();
}

// Variables pour le message de succès
$success_message = '';
$error_message = '';

// TRAITEMENT DU FORMULAIRE
if (!empty($_POST['submit_fiche_jeu']) && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'sisme_fiche_jeu_action')) {
    
    // Récupérer et nettoyer seulement les sections
    $sections = $_POST['sections'] ?? array();
    
    // Nettoyer les sections
    $clean_sections = array();
    if (is_array($sections)) {
        foreach ($sections as $index => $section) {
            if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])) {
                $clean_sections[] = array(
                    'title' => sanitize_text_field($section['title'] ?? ''),
                    'content' => wp_kses_post($section['content'] ?? ''),
                    'image_id' => intval($section['image_id'] ?? 0)
                );
            }
        }
    }
    
    // Titre automatique basé sur le nom du jeu
    $article_title = 'Fiche de ' . $tag_data->name;
    
    // Préparer les données de l'article
    $post_data_to_save = array(
        'post_title' => $article_title,
        'post_content' => '', // Pas de contenu d'intro
        'post_status' => 'draft', // En brouillon par défaut
        'post_type' => 'post',
        'meta_input' => array(
            '_sisme_game_sections' => $clean_sections
        )
    );
    
    // Créer ou mettre à jour l'article
    if ($is_edit_mode && $post_data) {
        // Mode édition
        $post_data_to_save['ID'] = $post_id;
        $result = wp_update_post($post_data_to_save);
        
        if (!is_wp_error($result)) {
            // Assigner l'étiquette du jeu à l'article
            wp_set_post_terms($post_id, array($tag_id), 'post_tag');
            $success_message = 'Fiche mise à jour avec succès !';
            
            // Recharger les données
            $post_data = get_post($post_id);
            $existing_sections = get_post_meta($post_id, '_sisme_game_sections', true) ?: array();
        } else {
            $error_message = 'Erreur lors de la mise à jour : ' . $result->get_error_message();
        }
        
    } else {
        // Mode création
        $new_post_id = wp_insert_post($post_data_to_save);
        
        if (!is_wp_error($new_post_id)) {
            // Assigner l'étiquette du jeu à l'article
            wp_set_post_terms($new_post_id, array($tag_id), 'post_tag');
            $success_message = 'Fiche créée avec succès ! <a href="' . get_edit_post_link($new_post_id) . '">Modifier dans WordPress</a>';
            
            // Basculer en mode édition
            $is_edit_mode = true;
            $post_id = $new_post_id;
            $post_data = get_post($new_post_id);
            $existing_sections = get_post_meta($new_post_id, '_sisme_game_sections', true) ?: array();
            
            // Mettre à jour les titres et sous-titres
            $page_title = 'Modifier la fiche : ' . $tag_data->name;
            $page_subtitle = 'Modifiez la présentation complète de la fiche de jeu';
            
        } else {
            $error_message = 'Erreur lors de la création : ' . $new_post_id->get_error_message();
        }
    }
}

// Configuration de la page
$page_title = $is_edit_mode 
    ? 'Modifier la fiche : ' . $tag_data->name
    : 'Créer une fiche pour : ' . $tag_data->name;
    
$page_subtitle = $is_edit_mode 
    ? 'Modifiez la présentation complète de la fiche de jeu'
    : 'Créez un article détaillé basé sur les données du jeu';

$back_url = admin_url('admin.php?page=sisme-games-game-data');

// Initialiser le wrapper de page
$page_wrapper = new Sisme_Admin_Page_Wrapper(
    $page_title,
    $page_subtitle,
    'controller', // Icône gaming
    $back_url,
    'Retour au Game Data'
);

// Démarrer le rendu de la page
$page_wrapper->render_start();

// Afficher les messages de succès/erreur
if (!empty($success_message)) {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><strong>Succès !</strong> <?php echo $success_message; ?></p>
    </div>
    <?php
}

if (!empty($error_message)) {
    ?>
    <div class="notice notice-error is-dismissible">
        <p><strong>Erreur !</strong> <?php echo esc_html($error_message); ?></p>
    </div>
    <?php
}

?>

<!-- Informations du jeu (lecture seule) -->
<div class="sisme-card sisme-card--primary">
    <div class="sisme-card__header">
        <h2>🎮 Informations du jeu (basées sur Game Data)</h2>
    </div>
    <div class="sisme-card__body">
        <div class="sisme-game-info-grid">
            
            <!-- Image principale -->
            <div class="sisme-game-info-image">
                <?php if ($game_data['cover_main']): ?>
                    <?php echo wp_get_attachment_image($game_data['cover_main'], 'medium', false, array(
                        'class' => 'sisme-game-cover-image'
                    )); ?>
                <?php else: ?>
                    <div class="sisme-game-cover-placeholder">
                        🎮<br>Aucune image
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Métadonnées -->
            <div class="sisme-game-info-details">
                <table class="sisme-game-info-table">
                    <tr>
                        <td class="sisme-game-info-label">Nom</td>
                        <td class="sisme-game-info-value"><?php echo esc_html($tag_data->name); ?></td>
                    </tr>
                    <tr>
                        <td class="sisme-game-info-label">Description</td>
                        <td class="sisme-game-info-value"><?php echo esc_html($game_data['description'] ?: 'Non renseignée'); ?></td>
                    </tr>
                    <tr>
                        <td class="sisme-game-info-label">Plateformes</td>
                        <td class="sisme-game-info-value">
                            <?php 
                            if (!empty($game_data['platforms']) && is_array($game_data['platforms'])) {
                                echo esc_html(implode(', ', $game_data['platforms']));
                            } else {
                                echo 'Non renseignées';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="sisme-game-info-label">Date de sortie</td>
                        <td class="sisme-game-info-value">
                            <?php echo esc_html($game_data['release_date'] ?: 'Non renseignée'); ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de présentation complète -->
<div class="sisme-card">
    <div class="sisme-card__header">
        <h2>📝 Présentation complète du jeu</h2>        
    </div>
    <div class="sisme-card__body">
        <form method="post" action="" class="sisme-fiche-form">
            <?php wp_nonce_field('sisme_fiche_jeu_action'); ?>
            
            <!-- Container des sections dynamiques -->
            <div class="sisme-sections-container" id="sections-container">
                <?php if (!empty($existing_sections)): ?>
                    <?php foreach ($existing_sections as $index => $section): ?>
                        <?php if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])): ?>
                            <div class="sisme-section-item" data-section="<?php echo $index; ?>">
                                <div class="sisme-section-header">
                                    <h3 class="sisme-section-title">Section <?php echo ($index + 1); ?></h3>
                                </div>
                                
                                <table class="sisme-form-table">
                                    <tr>
                                        <th scope="row"><label for="section_<?php echo $index; ?>_title">Titre</label></th>
                                        <td>
                                            <input type="text" 
                                                   id="section_<?php echo $index; ?>_title" 
                                                   name="sections[<?php echo $index; ?>][title]" 
                                                   class="sisme-form-input sisme-form-input--large"
                                                   value="<?php echo esc_attr($section['title']); ?>"
                                                   placeholder="Titre de la section">
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><label for="section_<?php echo $index; ?>_content">Contenu</label></th>
                                        <td>
                                            <textarea id="section_<?php echo $index; ?>_content" 
                                                      name="sections[<?php echo $index; ?>][content]" 
                                                      rows="8" 
                                                      class="sisme-form-input sisme-form-input--large sisme-section-textarea"><?php echo esc_textarea($section['content']); ?></textarea>
                                            <p class="sisme-form-description">
                                                <strong>Balises autorisées :</strong> &lt;em&gt; &lt;strong&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt;
                                            </p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><label>Image de la section</label></th>
                                        <td>
                                            <div class="sisme-section-image-container">
                                                <div id="section-<?php echo $index; ?>-image-preview" class="sisme-section-image-preview">
                                                    <?php if (!empty($section['image_id'])): ?>
                                                        <?php echo wp_get_attachment_image($section['image_id'], 'medium', false, array('class' => 'sisme-section-image')); ?>
                                                    <?php else: ?>
                                                        <em class="sisme-no-image-text">Aucune image sélectionnée</em>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="sisme-section-image-actions">
                                                    <button type="button" class="sisme-btn sisme-btn--secondary select-section-image" data-section="<?php echo $index; ?>">
                                                        <?php echo !empty($section['image_id']) ? 'Changer l\'image' : 'Choisir une image'; ?>
                                                    </button>
                                                    <?php if (!empty($section['image_id'])): ?>
                                                        <button type="button" class="sisme-btn sisme-btn--delete remove-section-image" data-section="<?php echo $index; ?>">
                                                            Supprimer l'image
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                <input type="hidden" id="section_<?php echo $index; ?>_image_id" name="sections[<?php echo $index; ?>][image_id]" value="<?php echo esc_attr($section['image_id'] ?? ''); ?>">
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                                
                                <div class="sisme-section-footer">
                                    <button type="button" class="sisme-btn sisme-btn--delete remove-section" data-section="<?php echo $index; ?>">
                                        🗑️ Supprimer cette section
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Bouton ajouter section -->
            <div class="sisme-add-section-container">
                <button type="button" class="sisme-btn sisme-btn--secondary" id="add-section">
                    ➕ Ajouter une section
                </button>
            </div>
            
            <!-- Actions -->
            <div class="sisme-form-actions">
                <input type="submit" 
                       name="submit_fiche_jeu" 
                       class="sisme-btn sisme-btn--primary sisme-btn--lg" 
                       value="<?php echo $is_edit_mode ? 'Mettre à jour la fiche' : 'Créer la fiche'; ?>">
                <a href="<?php echo $back_url; ?>" class="sisme-btn sisme-btn--secondary">
                    Annuler
                </a>
                <?php if ($is_edit_mode && isset($post_data) && $post_data): ?>
                    <a href="<?php echo get_permalink($post_data->ID); ?>" class="sisme-btn sisme-btn--info" target="_blank">
                        👁️ Voir l'article
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript pour la gestion des sections -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let sectionCounter = <?php echo count($existing_sections); ?>;
    
    // Ajouter une nouvelle section
    document.getElementById('add-section').addEventListener('click', function() {
        const container = document.getElementById('sections-container');
        const newSection = createSectionHTML(sectionCounter);
        container.insertAdjacentHTML('beforeend', newSection);
        sectionCounter++;
    });
    
    // Supprimer une section
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-section')) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette section ?')) {
                e.target.closest('.sisme-section-item').remove();
            }
        }
    });
    
    // Gestion des images des sections - Sélection
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('select-section-image')) {
            e.preventDefault();
            const button = e.target;
            const section = button.getAttribute('data-section');
            
            // Créer l'instance de la médiathèque
            const mediaUploader = wp.media({
                title: 'Sélectionner une image pour cette section',
                button: { text: 'Utiliser cette image' },
                multiple: false,
                library: { type: 'image' }
            });
            
            // Quand une image est sélectionnée
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                
                // Mettre à jour le champ caché
                document.getElementById('section_' + section + '_image_id').value = attachment.id;
                
                // Mettre à jour l'aperçu
                const preview = document.getElementById('section-' + section + '-image-preview');
                const imageUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                preview.innerHTML = '<img src="' + imageUrl + '" alt="Image de section" class="sisme-section-image">';
                
                // Mettre à jour le texte du bouton
                button.textContent = 'Changer l\'image';
                
                // Afficher le bouton supprimer
                const removeButton = button.parentNode.querySelector('.remove-section-image[data-section="' + section + '"]');
                if (removeButton) {
                    removeButton.style.display = 'inline-block';
                }
            });
            
            // Ouvrir la médiathèque
            mediaUploader.open();
        }
    });
    
    // Gestion des images des sections - Suppression
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-section-image')) {
            e.preventDefault();
            const section = e.target.getAttribute('data-section');
            
            if (confirm('Supprimer cette image ?')) {
                // Vider le champ caché
                document.getElementById('section_' + section + '_image_id').value = '';
                
                // Mettre à jour l'aperçu
                const preview = document.getElementById('section-' + section + '-image-preview');
                preview.innerHTML = '<em class="sisme-no-image-text">Aucune image sélectionnée</em>';
                
                // Mettre à jour le texte du bouton sélection
                const selectButton = e.target.parentNode.querySelector('.select-section-image[data-section="' + section + '"]');
                if (selectButton) {
                    selectButton.textContent = 'Choisir une image';
                }
                
                // Cacher le bouton supprimer
                e.target.style.display = 'none';
            }
        }
    });
    
    // Fonction pour créer le HTML d'une nouvelle section
    function createSectionHTML(index) {
        return `
            <div class="sisme-section-item" data-section="${index}">
                <div class="sisme-section-header">
                    <h3 class="sisme-section-title">Section ${index + 1}</h3>
                </div>
                
                <table class="sisme-form-table">
                    <tr>
                        <th scope="row"><label for="section_${index}_title">Titre</label></th>
                        <td>
                            <input type="text" 
                                   id="section_${index}_title" 
                                   name="sections[${index}][title]" 
                                   class="sisme-form-input sisme-form-input--large"
                                   placeholder="Titre de la section">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="section_${index}_content">Contenu</label></th>
                        <td>
                            <textarea id="section_${index}_content" 
                                      name="sections[${index}][content]" 
                                      rows="8" 
                                      class="sisme-form-input sisme-form-input--large sisme-section-textarea"
                                      placeholder="Contenu de la section..."></textarea>
                            <p class="sisme-form-description">
                                <strong>Balises autorisées :</strong> &lt;em&gt; &lt;strong&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt;
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label>Image de la section</label></th>
                        <td>
                            <div class="sisme-section-image-container">
                                <div id="section-${index}-image-preview" class="sisme-section-image-preview">
                                    <em class="sisme-no-image-text">Aucune image sélectionnée</em>
                                </div>
                                <div class="sisme-section-image-actions">
                                    <button type="button" class="sisme-btn sisme-btn--secondary select-section-image" data-section="${index}">
                                        Choisir une image
                                    </button>
                                    <button type="button" class="sisme-btn sisme-btn--delete remove-section-image" data-section="${index}" style="display: none;">
                                        Supprimer l'image
                                    </button>
                                </div>
                                <input type="hidden" id="section_${index}_image_id" name="sections[${index}][image_id]" value="">
                            </div>
                        </td>
                    </tr>
                </table>
                
                <div class="sisme-section-footer">
                    <button type="button" class="sisme-btn sisme-btn--delete remove-section" data-section="${index}">
                        🗑️ Supprimer cette section
                    </button>
                </div>
            </div>
        `;
    }
});
</script>

<?php

// Terminer le rendu de la page
$page_wrapper->render_end();

?>