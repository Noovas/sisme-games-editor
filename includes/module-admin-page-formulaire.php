<?php
/**
 * File: /sisme-games-editor/includes/module-admin-page-formulaire.php
 * Module: Formulaire avec Composants Fixes - Sisme Games Editor
 * 
 * Ce module fournit des composants de formulaire prédéfinis et réutilisables.
 * Chaque composant a un nom, label et comportement fixes.
 * 
 * Composants disponibles:
 * - game_name: Sélection/création de tag de jeu (toujours "Nom du jeu")
 * - description: Champ description riche (toujours "Description du jeu")
 * 
 * Utilisation:
 * 1. Inclure ce fichier
 * 2. Initialiser la classe avec la liste des composants souhaités
 * 3. Appeler render() pour afficher le formulaire
 * 4. Utiliser get_submitted_data() pour récupérer les données
 * 
 * Exemples:
 * // Formulaire avec les deux composants
 * $form = new Sisme_Game_Form_Module(['game_name', 'description']);
 * $form->render();
 * 
 * // Formulaire avec un seul composant
 * $form = new Sisme_Game_Form_Module(['game_name']);
 * 
 * // Avec options personnalisées
 * $form = new Sisme_Game_Form_Module(['game_name', 'description'], [
 *     'submit_text' => 'Créer le jeu',
 *     'action' => admin_url('admin-post.php')
 * ]);
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_Form_Module {
    
    private $components = [];
    private $form_data = [];
    private $module_id;
    private $form_action = '';
    private $form_method = 'POST';
    private $submit_button_text = 'Enregistrer';
    private $show_nonce = true;
    private $nonce_action = 'sisme_form_action';
    private static $instance_counter = 0;
    
    // Définition des composants fixes disponibles
    private $available_components = [
        'game_name' => [
            'label' => 'Nom du jeu',
            'description' => 'Sélectionnez un jeu existant ou créez un nouveau jeu.',
            'required' => true,
            'output_var' => 'game_name'
        ],
        'description' => [
            'label' => 'Description du jeu',
            'description' => 'Description détaillée acceptant les balises de mise en forme.',
            'required' => false,
            'output_var' => 'description',
            'allowed_tags' => ['strong', 'em', 'br'],
            'rows' => 6
        ],
        'cover_main' => [
            'label' => 'Cover principale',
            'description' => 'Image principale du jeu (médiathèque WordPress).',
            'required' => false,
            'output_var' => 'cover_main'
        ],
        'cover_news' => [
            'label' => 'Cover news',
            'description' => 'Image pour les articles news du jeu.',
            'required' => false,
            'output_var' => 'cover_news'
        ],
        'cover_patch' => [
            'label' => 'Cover patch',
            'description' => 'Image pour les articles patch du jeu.',
            'required' => false,
            'output_var' => 'cover_patch'
        ],
        'cover_test' => [
            'label' => 'Cover test',
            'description' => 'Image pour les articles test du jeu.',
            'required' => false,
            'output_var' => 'cover_test'
        ]
    ];
    
    /**
     * Constructeur
     * 
     * @param array $components Liste des noms de composants à inclure
     * @param array $options Options du formulaire (action, method, submit_text, etc.)
     */
    public function __construct($components = [], $options = []) {
        // Valider et définir les composants
        $this->set_components($components);
        
        // Générer un ID unique pour chaque instance du module
        self::$instance_counter++;
        $this->module_id = 'game-form-' . self::$instance_counter;
        
        // Traiter les options du formulaire
        $this->process_form_options($options);
        
        // Récupérer les données soumises si présentes
        $this->process_submitted_data();
    }
    
    /**
     * Valider et définir les composants à utiliser
     */
    private function set_components($components) {
        if (empty($components)) {
            $components = ['game_name', 'description']; // Par défaut
        }
        
        foreach ($components as $component_name) {
            if (isset($this->available_components[$component_name])) {
                $this->components[$component_name] = $this->available_components[$component_name];
            } else {
                // Log d'erreur pour composant non reconnu
                error_log("SISME Form Module: Composant '$component_name' non reconnu. Composants disponibles: " . implode(', ', array_keys($this->available_components)));
            }
        }
    }
    
    /**
     * Traiter les options du formulaire
     */
    private function process_form_options($options) {
        $this->form_action = isset($options['action']) ? $options['action'] : '';
        $this->form_method = isset($options['method']) ? strtoupper($options['method']) : 'POST';
        $this->submit_button_text = isset($options['submit_text']) ? $options['submit_text'] : 'Enregistrer';
        $this->show_nonce = isset($options['nonce']) ? $options['nonce'] : true;
        $this->nonce_action = isset($options['nonce_action']) ? $options['nonce_action'] : 'sisme_form_action';
    }
    
    /**
     * Traiter les données soumises
     */
    private function process_submitted_data() {
        if ($this->form_method === 'POST' && !empty($_POST)) {
            $submitted_data = $_POST;
        } elseif ($this->form_method === 'GET' && !empty($_GET)) {
            $submitted_data = $_GET;
        } else {
            return;
        }
        
        // Nettoyer et valider les données pour chaque composant
        foreach ($this->components as $component_name => $component_config) {
            $output_var = $component_config['output_var'];
            if (isset($submitted_data[$output_var])) {
                $this->form_data[$output_var] = $this->sanitize_component_value(
                    $submitted_data[$output_var], 
                    $component_name
                );
            }
        }
    }
    
    /**
     * Nettoyer la valeur d'un composant selon son type
     */
    private function sanitize_component_value($value, $component_name) {
        switch ($component_name) {
            case 'game_name':
                // Pour game_name, on attend un ID de tag (nombre)
                return intval($value);
                
            case 'description':
                // Pour description, on utilise les balises autorisées
                $component_config = $this->components[$component_name];
                $allowed_tags = $component_config['allowed_tags'];
                $allowed_html = [];
                foreach ($allowed_tags as $tag) {
                    $allowed_html[$tag] = [];
                }
                return wp_kses($value, $allowed_html);

            case 'cover_main':
            case 'cover_news':
            case 'cover_patch':
            case 'cover_test':
                return intval($value);
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Afficher un composant cover (sélecteur d'image)
     */
    private function render_cover_component($component_name) {
        $component = $this->components[$component_name];
        $value = isset($this->form_data[$component['output_var']]) ? $this->form_data[$component['output_var']] : '';
        $field_id = $this->module_id . '_' . $component_name;
        $required_attr = $component['required'] ? 'required' : '';
        $required_label = $component['required'] ? ' *' : '';
        
        // Récupérer l'image actuelle si elle existe
        $current_image_url = '';
        $current_image_title = '';
        if (!empty($value)) {
            $image = wp_get_attachment_image_src($value, 'thumbnail');
            if ($image) {
                $current_image_url = $image[0];
                $current_image_title = get_the_title($value);
            }
        }
        ?>
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($component['label'] . $required_label); ?>
                </label>
            </th>
            <td>
                <div class="sisme-media-component">
                    <!-- Champ caché pour stocker l'ID -->
                    <input type="hidden" 
                           id="<?php echo esc_attr($field_id); ?>" 
                           name="<?php echo esc_attr($component['output_var']); ?>" 
                           value="<?php echo esc_attr($value); ?>"
                           <?php echo $required_attr; ?>>
                    
                    <!-- Aperçu de l'image -->
                    <div class="media-preview" style="margin-bottom: 10px;">
                        <?php if (!empty($current_image_url)): ?>
                            <div class="current-image" style="display: flex; align-items: center; gap: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px;">
                                <img src="<?php echo esc_url($current_image_url); ?>" 
                                     style="max-width: 80px; max-height: 80px; border-radius: 4px;">
                                <div>
                                    <strong><?php echo esc_html($current_image_title); ?></strong>
                                    <div style="font-size: 12px; color: #666;">ID: <?php echo $value; ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-image" style="padding: 20px; background: #f0f0f0; border-radius: 4px; text-align: center; color: #666;">
                                Aucune image sélectionnée
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Boutons d'action -->
                    <div class="media-actions" style="display: flex; gap: 10px;">
                        <button type="button" 
                                class="button button-secondary sisme-select-media-btn"
                                data-field-id="<?php echo esc_attr($field_id); ?>"
                                data-component="<?php echo esc_attr($component_name); ?>">
                            🖼️ <?php echo !empty($value) ? 'Changer l\'image' : 'Sélectionner une image'; ?>
                        </button>
                        
                        <?php if (!empty($value)): ?>
                            <button type="button" 
                                    class="button sisme-remove-media-btn"
                                    data-field-id="<?php echo esc_attr($field_id); ?>"
                                    data-component="<?php echo esc_attr($component_name); ?>">
                                🗑️ Supprimer
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <p class="description">
                    <?php echo esc_html($component['description']); ?>
                </p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Obtenir les données soumises du formulaire
     * 
     * @return array Données du formulaire nettoyées
     */
    public function get_submitted_data() {
        return $this->form_data;
    }
    
    /**
     * Vérifier si le formulaire a été soumis
     * 
     * @return bool True si le formulaire a été soumis
     */
    public function is_submitted() {
        if ($this->show_nonce) {
            return !empty($this->form_data) && wp_verify_nonce($_REQUEST['_wpnonce'] ?? '', $this->nonce_action);
        }
        return !empty($this->form_data);
    }
    
    /**
     * Obtenir la liste des tags/étiquettes existants
     * 
     * @return array Liste des tags triés par nom
     */
    private function get_existing_tags() {
        $tags = get_tags(array(
            'hide_empty' => false, 
            'orderby' => 'name', 
            'order' => 'ASC',
            'number' => 200
        ));
        
        // Trier par ordre alphabétique (insensible à la casse)
        usort($tags, function($a, $b) {
            return strcasecmp($a->name, $b->name);
        });
        
        return $tags;
    }
    
    /**
     * Afficher le composant game_name
     */
    private function render_game_name_component() {
        $component = $this->components['game_name'];
        $value = isset($this->form_data['game_name']) ? $this->form_data['game_name'] : '';
        $field_id = $this->module_id . '_game_name';
        $tags = $this->get_existing_tags();
        ?>
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($component['label']); ?> *
                </label>
            </th>
            <td>
                <div class="sisme-game-name-component">
                    <!-- Sélecteur de tags existants -->
                    <select id="<?php echo esc_attr($field_id); ?>" 
                            name="game_name" 
                            class="regular-text sisme-tag-select" 
                            style="width: 100%; margin-bottom: 10px;"
                            required>
                        <option value="">Sélectionner un jeu existant...</option>
                        <?php foreach ($tags as $tag): ?>
                            <option value="<?php echo esc_attr($tag->term_id); ?>" 
                                    <?php selected($value, $tag->term_id); ?>>
                                <?php echo esc_html($tag->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <!-- Section de création de nouveau tag -->
                    <div class="sisme-create-tag-section" style="display: flex; align-items: center; gap: 10px; margin-top: 10px; padding: 12px; background: #f9f9f9; border-radius: 4px; border: 1px solid #ddd;">
                        <label for="<?php echo esc_attr($field_id . '_new_tag'); ?>" style="margin: 0; font-weight: 500; color: #555;">Ou créer un nouveau jeu :</label>
                        <input type="text" 
                           id="<?php echo esc_attr($field_id . '_new_tag'); ?>"
                           name="new_tag_input"
                           class="sisme-new-tag-input regular-text" 
                           placeholder="Nom du nouveau jeu..."
                           style="flex: 1;">
                        <button type="button" 
                                class="button button-secondary sisme-create-tag-btn"
                                title="Créer le tag">
                            +
                        </button>
                    </div>
                </div>
                
                <p class="description">
                    <?php echo esc_html($component['description']); ?>
                </p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Afficher le composant description
     */
    private function render_description_component() {
        $component = $this->components['description'];
        $value = isset($this->form_data['description']) ? $this->form_data['description'] : '';
        $field_id = $this->module_id . '_description';
        $required_attr = $component['required'] ? 'required' : '';
        $required_label = $component['required'] ? ' *' : '';
        ?>
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($component['label'] . $required_label); ?>
                </label>
            </th>
            <td>
                <textarea id="<?php echo esc_attr($field_id); ?>" 
                          name="description" 
                          rows="<?php echo esc_attr($component['rows']); ?>"
                          placeholder="Décrivez le jeu, son gameplay, son univers..."
                          class="large-text sisme-rich-textarea"
                          style="width: 100%;"
                          <?php echo $required_attr; ?>><?php echo esc_textarea($value); ?></textarea>
                
                <p class="description">
                    <strong>Balises autorisées :</strong> 
                    <?php echo '&lt;' . implode('&gt; &lt;', $component['allowed_tags']) . '&gt;'; ?>
                    <br><?php echo esc_html($component['description']); ?>
                </p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Afficher un composant selon son nom
     * 
     * @param string $component_name Nom du composant
     */
    private function render_component($component_name) {
        switch ($component_name) {
            case 'game_name':
                $this->render_game_name_component();
                break;
                
            case 'description':
                $this->render_description_component();
                break;

            case 'cover_main':
            case 'cover_news':
            case 'cover_patch':
            case 'cover_test':
                $this->render_cover_component($component_name);
                break;
                
            default:
                // Pour les composants non reconnus, afficher un message d'erreur
                ?>
                <tr>
                    <td colspan="2">
                        <div class="notice notice-error">
                            <p><strong>Erreur :</strong> Le composant "<?php echo esc_html($component_name); ?>" n'est pas supporté.</p>
                            <p>Composants disponibles : <?php echo implode(', ', array_keys($this->available_components)); ?></p>
                        </div>
                    </td>
                </tr>
                <?php
                break;
        }
    }
    
    /**
     * Afficher le formulaire complet
     * 
     * @param array $extra_options Options supplémentaires pour l'affichage
     */
    public function render($extra_options = []) {
        $show_table = isset($extra_options['table']) ? $extra_options['table'] : true;
        $form_class = isset($extra_options['class']) ? $extra_options['class'] : 'sisme-game-form-module';
        ?>
        
        <div class="<?php echo esc_attr($form_class); ?>" id="<?php echo esc_attr($this->module_id); ?>">
            <form method="<?php echo esc_attr($this->form_method); ?>" 
                  action="<?php echo esc_attr($this->form_action); ?>"
                  enctype="multipart/form-data">
                
                <?php
                // Nonce de sécurité
                if ($this->show_nonce) {
                    wp_nonce_field($this->nonce_action);
                }
                
                // Préserver les paramètres GET importants si méthode POST
                if ($this->form_method === 'POST') {
                    $preserve_params = ['page', 'post_id', 'action'];
                    foreach ($preserve_params as $param) {
                        if (isset($_GET[$param])) {
                            echo '<input type="hidden" name="' . esc_attr($param) . '" value="' . esc_attr($_GET[$param]) . '">';
                        }
                    }
                }
                ?>
                
                <?php if ($show_table): ?>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <?php foreach (array_keys($this->components) as $component_name): ?>
                                <?php $this->render_component($component_name); ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" 
                               name="submit" 
                               class="button-primary" 
                               value="<?php echo esc_attr($this->submit_button_text); ?>">
                    </p>
                    
                <?php else: ?>
                    <div class="sisme-form-fields">
                        <?php foreach (array_keys($this->components) as $component_name): ?>
                            <div class="sisme-form-field">
                                <?php $this->render_component($component_name); ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="sisme-form-submit">
                            <input type="submit" 
                                   name="submit" 
                                   class="button-primary" 
                                   value="<?php echo esc_attr($this->submit_button_text); ?>">
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Valider les données du formulaire
     * 
     * @return array|bool Tableau d'erreurs ou true si valide
     */
    public function validate() {
        $errors = [];
        
        foreach ($this->components as $component_name => $component_config) {
            $required = $component_config['required'];
            $output_var = $component_config['output_var'];
            $value = isset($this->form_data[$output_var]) ? $this->form_data[$output_var] : '';
            
            // Vérifier les champs requis
            if ($required && empty($value)) {
                $errors[$output_var] = sprintf('Le champ "%s" est obligatoire.', $component_config['label']);
                continue;
            }
            
            // Validation spécifique par composant
            switch ($component_name) {
                case 'game_name':
                    if (!empty($value) && !is_numeric($value)) {
                        $errors[$output_var] = 'ID de tag invalide.';
                    }
                    break;
                    
                case 'description':
                    // Pas de validation spéciale pour la description
                    break;
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Afficher les erreurs de validation
     * 
     * @param array $errors Tableau d'erreurs
     */
    public function display_errors($errors) {
        if (empty($errors) || !is_array($errors)) {
            return;
        }
        
        echo '<div class="notice notice-error"><ul>';
        foreach ($errors as $field => $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul></div>';
    }
    
    /**
     * Afficher un message de succès
     * 
     * @param string $message Message à afficher
     */
    public function display_success($message) {
        if (empty($message)) {
            return;
        }
        
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . esc_html($message) . '</p>';
        echo '</div>';
    }
    
    /**
     * Obtenir la liste des composants disponibles
     * 
     * @return array Liste des composants disponibles
     */
    public function get_available_components() {
        return array_keys($this->available_components);
    }

    /**
     * Générer le script JavaScript pour cette instance
     */
    public function render_javascript() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-create-tag-btn', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var container = button.closest('.sisme-game-name-component');
                var input = container.find('.sisme-new-tag-input');
                var select = container.find('.sisme-tag-select');
                var tagName = input.val().trim();
                
                if (!tagName) {
                    alert('Veuillez saisir un nom de jeu.');
                    return;
                }
                
                button.prop('disabled', true).text('Création...');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'sisme_create_tag',
                        tag_name: tagName,
                        nonce: '<?php echo wp_create_nonce('sisme_create_tag'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            var newOption = $('<option></option>')
                                .attr('value', response.data.term_id)
                                .text(response.data.name)
                                .prop('selected', true);
                            
                            select.append(newOption);
                            input.val('');
                            button.text('Créé !');
                            setTimeout(function() {
                                button.text('+').prop('disabled', false);
                            }, 1500);
                        } else {
                            alert('Erreur: ' + (response.data || 'Problème inconnu'));
                            button.text('+').prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Erreur AJAX');
                        button.text('+').prop('disabled', false);
                    }
                });
            });
            // JavaScript pour la sélection d'images
            wp.media.frames.selectMedia = wp.media({
                title: 'Sélectionner une image',
                multiple: false,
                library: { type: 'image' }
            });

            wp.media.frames.selectMedia.on('select', function() {
                var attachment = wp.media.frames.selectMedia.state().get('selection').first().toJSON();
                var fieldId = jQuery('.sisme-select-media-btn.active').data('field-id');
                
                if (fieldId && attachment.id) {
                    // Mettre à jour le champ caché
                    jQuery('#' + fieldId).val(attachment.id);
                    
                    // Mettre à jour l'aperçu sans recharger
                    var preview = jQuery('#' + fieldId).closest('.sisme-media-component').find('.media-preview');
                    var newPreview = '<div class="current-image" style="display: flex; align-items: center; gap: 10px; padding: 10px; background: #f9f9f9; border-radius: 4px;">' +
                                    '<img src="' + attachment.sizes.thumbnail.url + '" style="max-width: 80px; max-height: 80px; border-radius: 4px;">' +
                                    '<div><strong>' + attachment.title + '</strong><div style="font-size: 12px; color: #666;">ID: ' + attachment.id + '</div></div></div>';
                    
                    preview.html(newPreview);
                    
                    // Mettre à jour les boutons
                    var button = jQuery('.sisme-select-media-btn.active');
                    button.text('🖼️ Changer l\'image');
                    
                    // Ajouter le bouton supprimer si pas présent
                    if (!button.siblings('.sisme-remove-media-btn').length) {
                        button.after('<button type="button" class="button sisme-remove-media-btn" data-field-id="' + fieldId + '">🗑️ Supprimer</button>');
                    }
                }
                
                jQuery('.sisme-select-media-btn').removeClass('active');
            });

            // Gestion des boutons de sélection
            $(document).on('click', '.sisme-select-media-btn', function(e) {
                e.preventDefault();
                
                // Marquer ce bouton comme actif
                $('.sisme-select-media-btn').removeClass('active');
                $(this).addClass('active');
                
                // Ouvrir la médiathèque
                wp.media.frames.selectMedia.open();
            });

            // Gestion des boutons de suppression
            $(document).on('click', '.sisme-remove-media-btn', function(e) {
                e.preventDefault();
                
                var fieldId = $(this).data('field-id');
                
                if (confirm('Supprimer cette image ?')) {
                    $('#' + fieldId).val('');
                    location.reload();
                }
            });
        });
        </script>
        <?php
    }
    
}