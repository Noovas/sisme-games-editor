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

?>

<!-- Informations du jeu (lecture seule) -->
<div class="sisme-card sisme-card--primary">
    <div class="sisme-card__header">
        <h2>🎮 Informations du jeu (basées sur Game Data)</h2>
    </div>
    <div class="sisme-card__body">
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem; align-items: start;">
            
            <!-- Image principale -->
            <div>
                <?php if ($game_data['cover_main']): ?>
                    <?php echo wp_get_attachment_image($game_data['cover_main'], 'medium', false, array(
                        'style' => 'width: 100%; height: auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'
                    )); ?>
                <?php else: ?>
                    <div style="background: #f5f5f5; padding: 3rem; text-align: center; border-radius: 8px; color: #666;">
                        🎮<br>Aucune image
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Métadonnées -->
            <div>
                <table class="sisme-game-info-table" style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee; font-weight: 600; color: #555;">Nom</td>
                        <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee;"><?php echo esc_html($tag_data->name); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee; font-weight: 600; color: #555;">Description</td>
                        <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee;"><?php echo esc_html($game_data['description'] ?: 'Non renseignée'); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee; font-weight: 600; color: #555;">Plateformes</td>
                        <td style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">
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
                        <td style="padding: 0.5rem 0; font-weight: 600; color: #555;">Date de sortie</td>
                        <td style="padding: 0.5rem 0;">
                            <?php echo esc_html($game_data['release_date'] ?: 'Non renseignée'); ?>
                        </td>
                    </tr>
                </table>
                
                <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                    <small style="color: #666;">
                        ℹ️ Ces informations proviennent des <strong>Game Data</strong>. 
                        Pour les modifier, utilisez la 
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-game-data&tag_id=' . $tag_id); ?>">
                            page d'édition du jeu
                        </a>.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de présentation complète -->
<div class="sisme-card">
    <div class="sisme-card__header">
        <h2>📝 Présentation complète du jeu</h2>
        <p>Ajoutez des sections personnalisées pour structurer le contenu détaillé de votre fiche.</p>
        
        <?php if (!$is_edit_mode && !empty($existing_sections)): ?>
            <div style="background: #d1ecf1; padding: 1rem; border-radius: 6px; margin-top: 1rem;">
                <strong>ℹ️ Sections du jeu chargées</strong><br>
                <small><?php echo count($existing_sections); ?> section(s) trouvée(s) dans Game Data</small>
            </div>
        <?php endif; ?>
    </div>
    <div class="sisme-card__body">
        
        <form method="post" action="">
            <?php wp_nonce_field('sisme_fiche_jeu_form', 'sisme_fiche_jeu_nonce'); ?>
            <input type="hidden" name="sisme_fiche_jeu_action" value="<?php echo $is_edit_mode ? 'update_fiche' : 'create_fiche'; ?>">
            <input type="hidden" name="tag_id" value="<?php echo $tag_id; ?>">
            <?php if ($is_edit_mode): ?>
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
            <?php endif; ?>
            
            <!-- Container des sections dynamiques -->
            <div id="sections-container">
                <?php if (!empty($existing_sections)): ?>
                    <?php foreach ($existing_sections as $index => $section): ?>
                        <?php if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])): ?>
                            <div class="section-item" data-section="<?php echo $index; ?>" style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #fafafa;">
                                <h3 style="margin-top: 0; color: #A1B78D;">Section <?php echo ($index + 1); ?></h3>
                                
                                <table class="form-table">
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
                                    
                                    <tr>
                                        <th scope="row"><label for="section_<?php echo $index; ?>_content">Contenu</label></th>
                                        <td>
                                            <textarea id="section_<?php echo $index; ?>_content" 
                                                      name="sections[<?php echo $index; ?>][content]" 
                                                      rows="8" 
                                                      cols="50" 
                                                      class="large-text"><?php echo esc_textarea($section['content']); ?></textarea>
                                            <p class="description"><strong>Balises autorisées :</strong> &lt;em&gt; &lt;strong&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt;</p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><label>Image de la section</label></th>
                                        <td>
                                            <div id="section-<?php echo $index; ?>-image-preview" style="margin-bottom: 10px;">
                                                <?php if (!empty($section['image_id'])): ?>
                                                    <?php echo wp_get_attachment_image($section['image_id'], 'medium'); ?>
                                                <?php else: ?>
                                                    <em>Aucune image sélectionnée</em>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" class="button select-section-image" data-section="<?php echo $index; ?>">
                                                <?php echo !empty($section['image_id']) ? 'Changer l\'image' : 'Choisir une image'; ?>
                                            </button>
                                            <?php if (!empty($section['image_id'])): ?>
                                                <button type="button" class="button remove-section-image" data-section="<?php echo $index; ?>" style="margin-left: 10px;">
                                                    Supprimer l'image
                                                </button>
                                            <?php endif; ?>
                                            <input type="hidden" id="section_<?php echo $index; ?>_image_id" name="sections[<?php echo $index; ?>][image_id]" value="<?php echo esc_attr($section['image_id'] ?? ''); ?>">
                                        </td>
                                    </tr>
                                </table>
                                
                                <button type="button" class="button button-secondary remove-section" data-section="<?php echo $index; ?>" style="margin-top: 10px;">
                                    🗑️ Supprimer cette section
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Bouton ajouter section -->
            <button type="button" class="sisme-btn sisme-btn--secondary" id="add-section">
                ➕ Ajouter une section
            </button>
            
            <!-- Actions -->
            <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                <p class="submit">
                    <input type="submit" name="submit" class="sisme-btn sisme-btn--primary" 
                           value="<?php echo $is_edit_mode ? 'Mettre à jour la fiche' : 'Créer la fiche'; ?>">
                    <a href="<?php echo $back_url; ?>" class="sisme-btn sisme-btn--secondary" style="margin-left: 10px;">
                        Annuler
                    </a>
                    <?php if ($is_edit_mode && $post_data): ?>
                        <a href="<?php echo get_permalink($post_data->ID); ?>" class="sisme-btn sisme-btn--secondary" target="_blank" style="margin-left: 10px;">
                            👁️ Voir l'article
                        </a>
                    <?php endif; ?>
                </p>
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
            e.target.closest('.section-item').remove();
        }
    });
    
    // Fonction pour créer le HTML d'une nouvelle section
    function createSectionHTML(index) {
        return `
            <div class="section-item" data-section="${index}" style="border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; background: #fafafa;">
                <h3 style="margin-top: 0; color: #A1B78D;">Section ${index + 1}</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="section_${index}_title">Titre</label></th>
                        <td>
                            <input type="text" 
                                   id="section_${index}_title" 
                                   name="sections[${index}][title]" 
                                   class="large-text"
                                   placeholder="Titre de la section">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="section_${index}_content">Contenu</label></th>
                        <td>
                            <textarea id="section_${index}_content" 
                                      name="sections[${index}][content]" 
                                      rows="8" 
                                      cols="50" 
                                      class="large-text"
                                      placeholder="Contenu de la section..."></textarea>
                            <p class="description"><strong>Balises autorisées :</strong> &lt;em&gt; &lt;strong&gt; &lt;ul&gt; &lt;ol&gt; &lt;li&gt;</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label>Image de la section</label></th>
                        <td>
                            <div id="section-${index}-image-preview" style="margin-bottom: 10px;">
                                <em>Aucune image sélectionnée</em>
                            </div>
                            <button type="button" class="button select-section-image" data-section="${index}">
                                Choisir une image
                            </button>
                            <button type="button" class="button remove-section-image" data-section="${index}" style="margin-left: 10px; display: none;">
                                Supprimer l'image
                            </button>
                            <input type="hidden" id="section_${index}_image_id" name="sections[${index}][image_id]" value="">
                        </td>
                    </tr>
                </table>
                
                <button type="button" class="button button-secondary remove-section" data-section="${index}" style="margin-top: 10px;">
                    🗑️ Supprimer cette section
                </button>
            </div>
        `;
    }
});
</script>

<?php

// Terminer le rendu de la page
$page_wrapper->render_end();

?>