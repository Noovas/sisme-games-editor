<?php
/**
 * File: /sisme-games-editor/admin/forms/create-fiche-step2.php
 * Formulaire de création d'une fiche de jeu - Étape 2 : Contenu détaillé
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Vérifier que l'étape 1 a été complétée
if (!session_id()) {
    session_start();
}

if (!isset($_SESSION['sisme_form_step1'])) {
    wp_redirect(admin_url('admin.php?page=sisme-games-fiches&action=create'));
    exit;
}

$step1_data = $_SESSION['sisme_form_step1'];
?>

<div class="wrap">
    <h1>Créer une fiche de jeu - Étape 2/2</h1>
    <p><em>Étape 1 : Informations principales</em> | <strong>Étape 2 :</strong> Contenu détaillé</p>
    
    <div style="background: #f1f1f1; padding: 15px; margin: 20px 0; border-left: 4px solid #0073aa;">
        <h3>Récapitulatif de l'étape 1 :</h3>
        <p><strong>Titre :</strong> <?php echo esc_html($step1_data['game_title']); ?></p>
        <p><strong>Description :</strong> <?php echo esc_html(wp_trim_words($step1_data['game_description'], 20)); ?>...</p>
    </div>
    
    <form method="post" action="">
        <?php wp_nonce_field('sisme_form', 'sisme_nonce'); ?>
        <input type="hidden" name="sisme_form_action" value="create_fiche_step2">
        
        <h2>Construction du contenu</h2>
        <p>Ajoutez des sections pour structurer votre fiche de jeu. Chaque section peut avoir un titre et du contenu.</p>
        
        <div id="content-sections">
            <!-- Section 1 par défaut -->
            <div class="content-section" data-section="1">
                <h3>Section 1</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="section_1_title">Titre de la section</label></th>
                        <td>
                            <input type="text" 
                                   id="section_1_title" 
                                   name="sections[1][title]" 
                                   class="regular-text"
                                   placeholder="Ex: Gameplay et mécaniques">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="section_1_content">Contenu</label></th>
                        <td>
                            <?php 
                            wp_editor('', 'section_1_content', array(
                                'textarea_name' => 'sections[1][content]',
                                'textarea_rows' => 8,
                                'media_buttons' => true,
                                'teeny' => false,
                                'tinymce' => true
                            )); 
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Image de section</th>
                        <td>
                            <div id="section-1-image-preview">
                                <em>Aucune image sélectionnée</em>
                            </div>
                            <button type="button" class="button select-section-image" data-section="1">
                                Choisir une image
                            </button>
                            <input type="hidden" name="sections[1][image_id]" id="section_1_image_id" value="">
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button type="button" class="button remove-section" data-section="1">
                        Supprimer cette section
                    </button>
                </p>
                <hr>
            </div>
            
            <!-- Sections supplémentaires pré-générées -->
            <?php for ($i = 2; $i <= 10; $i++) : ?>
            <div class="content-section" data-section="<?php echo $i; ?>" style="display: none;">
                <h3>Section <?php echo $i; ?></h3>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="section_<?php echo $i; ?>_title">Titre de la section</label></th>
                        <td>
                            <input type="text" 
                                   id="section_<?php echo $i; ?>_title" 
                                   name="sections[<?php echo $i; ?>][title]" 
                                   class="regular-text"
                                   placeholder="Ex: Points forts et faibles">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="section_<?php echo $i; ?>_content">Contenu</label></th>
                        <td>
                            <?php 
                            wp_editor('', 'section_' . $i . '_content', array(
                                'textarea_name' => 'sections[' . $i . '][content]',
                                'textarea_rows' => 8,
                                'media_buttons' => true,
                                'teeny' => false,
                                'tinymce' => true
                            )); 
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Image de section</th>
                        <td>
                            <div id="section-<?php echo $i; ?>-image-preview">
                                <em>Aucune image sélectionnée</em>
                            </div>
                            <button type="button" class="button select-section-image" data-section="<?php echo $i; ?>">
                                Choisir une image
                            </button>
                            <input type="hidden" name="sections[<?php echo $i; ?>][image_id]" id="section_<?php echo $i; ?>_image_id" value="">
                        </td>
                    </tr>
                </table>
                
                <p>
                    <button type="button" class="button remove-section" data-section="<?php echo $i; ?>">
                        Supprimer cette section
                    </button>
                </p>
                <hr>
            </div>
            <?php endfor; ?>
        </div>
        
        <p>
            <button type="button" id="add-section" class="button">
                + Ajouter une section
            </button>
        </p>
        
        <h2>Finalisation</h2>
        <table class="form-table">
            <tr>
                <th scope="row">Statut de publication</th>
                <td>
                    <select name="post_status">
                        <option value="draft">Brouillon</option>
                        <option value="publish">Publié</option>
                        <option value="private">Privé</option>
                    </select>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches&action=create'); ?>" class="button">
                ← Retour à l'étape 1
            </a>
            <input type="submit" value="Créer la fiche de jeu" class="button-primary">
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var sectionCounter = 1;
    var maxSections = 10;
    
    // Ajouter une nouvelle section
    $('#add-section').click(function() {
        if (sectionCounter >= maxSections) {
            alert('Maximum ' + maxSections + ' sections autorisées');
            return;
        }
        
        sectionCounter++;
        $('[data-section="' + sectionCounter + '"]').show();
        
        // Mettre à jour le texte du bouton si on approche de la limite
        if (sectionCounter >= maxSections) {
            $(this).text('Maximum de sections atteint').prop('disabled', true);
        }
    });
    
    // Supprimer une section
    $(document).on('click', '.remove-section', function() {
        var section = $(this).data('section');
        if (confirm('Êtes-vous sûr de vouloir supprimer cette section ?')) {
            $('[data-section="' + section + '"]').hide();
            
            // Reset des champs de cette section
            $('[data-section="' + section + '"] input[type="text"]').val('');
            $('[data-section="' + section + '"] input[type="hidden"]').val('');
            $('[data-section="' + section + '"] textarea').val('');
            
            // Réactiver le bouton d'ajout
            $('#add-section').text('+ Ajouter une section').prop('disabled', false);
        }
    });
    
    // Sélecteur d'image pour les sections
    $(document).on('click', '.select-section-image', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var section = button.data('section');
        
        var mediaUploader = wp.media({
            title: 'Sélectionner une image pour cette section',
            button: {
                text: 'Utiliser cette image'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#section_' + section + '_image_id').val(attachment.id);
            $('#section-' + section + '-image-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto;">');
            button.text('Changer l\'image');
        });
        
        mediaUploader.open();
    });
});
</script>