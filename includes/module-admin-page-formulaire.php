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
            'description' => '',
            'required' => true,
            'output_var' => 'game_name'
        ],
        'is_team_choice' => [
            'label' => 'Choix de l\'équipe',
            'description' => '',
            'required' => false,
            'output_var' => 'is_team_choice',
            'type' => 'checkbox'
        ],
        'trailer_link' => [
            'label' => 'Trailer',
            'description' => '',
            'required' => false,
            'output_var' => 'trailer_link'
        ],
        'description' => [
            'label' => 'Description du jeu',
            'description' => '',
            'required' => false,
            'output_var' => 'description',
            'allowed_tags' => ['strong', 'em', 'br'],
            'rows' => 6
        ],
        'game_genres' => [
            'label' => 'Genres du jeu',
            'description' => '',
            'required' => false,
            'output_var' => 'game_genres',
            'parent_category' => 'jeux',
            'exclude_modes' => ['jeux-solo', 'jeux-multijoueur', 'jeux-cooperatif', 'jeux-competitif']
        ],
        'game_modes' => [
            'label' => 'Modes de jeu',
            'description' => '',
            'required' => false,
            'output_var' => 'game_modes',
            'available_modes' => ['solo', 'multijoueur', 'cooperatif', 'competitif']
        ],
        'game_developers' => [
            'label' => 'Développeurs',
            'description' => '',
            'required' => false,
            'output_var' => 'game_developers',
            'parent_category' => 'editeurs-developpeurs',
            'entity_type' => 'developer'
        ],
        'game_publishers' => [
            'label' => 'Éditeurs',
            'description' => '',
            'required' => false,
            'output_var' => 'game_publishers',
            'parent_category' => 'editeurs-developpeurs',
            'entity_type' => 'publisher'
        ],
        Sisme_Utils_Games::META_COVER_MAIN => [
            'label' => 'Cover principale',
            'description' => '',
            'required' => false,
            'output_var' => Sisme_Utils_Games::META_COVER_MAIN
        ],
        'cover_news' => [
            'label' => 'Cover news',
            'description' => '',
            'required' => false,
            'output_var' => 'cover_news'
        ],
        'cover_patch' => [
            'label' => 'Cover patch',
            'description' => '',
            'required' => false,
            'output_var' => 'cover_patch'
        ],
        'cover_test' => [
            'label' => 'Cover test',
            'description' => '',
            'required' => false,
            'output_var' => 'cover_test'
        ],
        'cover_vertical' => [
            'label' => 'Cover verticale',
            'description' => '',
            'required' => false,
            'output_var' => 'cover_vertical'
        ],
        'game_platforms' => [
            'label' => 'Plateformes',
            'description' => '',
            'required' => false,
            'output_var' => 'game_platforms'
        ],
        'release_date' => [
            'label' => 'Date de sortie',
            'description' => '',
            'required' => false,
            'output_var' => 'release_date'
        ],
        'screenshots' => [
            'label' => 'Screenshots du jeu',
            'description' => '',
            'required' => false,
            'output_var' => 'screenshots',
            'component_type' => 'media_gallery'
        ],
        'external_links' => [
            'label' => 'Liens de vente',
            'description' => '',
            'required' => false,
            'output_var' => 'external_links'
        ],
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

        // Traitement spécial pour game_genres (tableau d'IDs)
        if (isset($_POST['game_genres']) && is_array($_POST['game_genres'])) {
            $this->form_data['game_genres'] = array_map('intval', $_POST['game_genres']);
        } else {
            $this->form_data['game_genres'] = array();
        }

        // Traitement spécial pour game_modes (tableau de clés)
        if (isset($_POST['game_modes']) && is_array($_POST['game_modes'])) {
            $this->form_data['game_modes'] = array_map('sanitize_text_field', $_POST['game_modes']);
        } else {
            $this->form_data['game_modes'] = array();
        }

        // Traitement des développeurs
        if (isset($_POST['game_developers']) && is_array($_POST['game_developers'])) {
            $this->form_data['game_developers'] = array_map('intval', $_POST['game_developers']);
        } else {
            $this->form_data['game_developers'] = array();
        }

        // Traitement des éditeurs
        if (isset($_POST['game_publishers']) && is_array($_POST['game_publishers'])) {
            $this->form_data['game_publishers'] = array_map('intval', $_POST['game_publishers']);
        } else {
            $this->form_data['game_publishers'] = array();
        }

        // Traitement du trailer_link
        if (isset($_POST['trailer_link'])) {
            $this->form_data['trailer_link'] = sanitize_text_field($_POST['trailer_link']);
        } else {
            $this->form_data['trailer_link'] = '';
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

                $value = wp_specialchars_decode($value, ENT_QUOTES);
                return wp_kses($value, $allowed_html);

            case Sisme_Utils_Games::META_COVER_MAIN:
            case 'cover_news':
            case 'cover_patch':
            case 'cover_test':
                return intval($value);

            case 'game_platforms':
                return is_array($value) ? array_map('sanitize_text_field', $value) : [];
                
            case 'release_date':
                return sanitize_text_field($value);

            case 'trailer_link':
                return esc_url_raw($value);
                
            case 'external_links':
                if (is_array($value)) {
                    $sanitized = [];
                    foreach ($value as $platform => $url) {
                        if (!empty($url)) {
                            $sanitized[sanitize_key($platform)] = esc_url_raw($url);
                        }
                    }
                    return $sanitized;
                }
                return [];
            case 'is_team_choice':
                return ($value === '1' || $value === 1 || $value === true) ? '1' : '0';

                
            default:
                return sanitize_text_field($value);
        }
    }

    /**
     * Méthode générique pour afficher un composant entité
     */
    private function render_entity_component($component_name) {
        $component = $this->components[$component_name];
        $value = isset($this->form_data[$component['output_var']]) ? $this->form_data[$component['output_var']] : array();
        $field_id = $this->module_id . '_' . $component_name;
        $required_attr = $component['required'] ? 'required' : '';
        $required_label = $component['required'] ? ' *' : '';
        
        // Récupérer les entités existantes
        $entities = $this->get_existing_entities();
        ?>
        <tr class="sisme-entity-component-row">
            <td class="sisme-entity-component-cell">
                <div class="sisme-entity-component sisme-entity-<?php echo esc_attr($component['entity_type']); ?>">
                    
                    <!-- Liste des entités sélectionnées -->
                    <div class="sisme-selected-entities">
                        <label class="sisme-selected-entities-label"><?php echo esc_html($component['label']); ?></label>
                        <div class="sisme-selected-entities-list" id="<?php echo esc_attr($field_id . '_selected'); ?>">
                            <?php if (empty($value)): ?>
                                <span class="sisme-no-entities-selected">Aucun <?php echo esc_html(strtolower($component['label'])); ?></span>
                            <?php else: ?>
                                <?php foreach ($value as $entity_id): ?>
                                    <?php $entity = get_category($entity_id); ?>
                                    <?php if ($entity): ?>
                                        <?php
                                        $entity_website = get_term_meta($entity_id, 'entity_website', true);
                                        ?>
                                        <span class="sisme-selected-entity-tag" data-entity-id="<?php echo esc_attr($entity_id); ?>">
                                            <?php if (!empty($entity_website)): ?>
                                                <a href="<?php echo esc_url($entity_website); ?>" 
                                                   target="_blank" 
                                                   class="sisme-entity-link"
                                                   title="Site web de <?php echo esc_attr($entity->name); ?>"
                                                   alt="<?php echo esc_attr($entity->name); ?>">
                                                    <?php echo esc_html($entity->name); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="sisme-entity-name"><?php echo esc_html($entity->name); ?></span>
                                            <?php endif; ?>
                                            <span class="sisme-remove-entity">&times;</span>
                                            <input type="hidden" name="<?php echo esc_attr($component['output_var']); ?>[]" value="<?php echo esc_attr($entity_id); ?>">
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Recherche/Création d'entités -->
                    <div class="sisme-entity-search-section">
                        <div class="sisme-entity-search-controls">
                            <input type="text" 
                                   id="<?php echo esc_attr($field_id . '_search'); ?>"
                                   class="sisme-entity-search-input" 
                                   placeholder="Rechercher ou créer <?php echo esc_html(strtolower($component['label'])); ?>...">
                            <input type="url" 
                                   id="<?php echo esc_attr($field_id . '_website'); ?>"
                                   class="sisme-entity-website-input" 
                                   placeholder="Site web (optionnel)">
                            <button type="button" 
                                    class="button button-secondary sisme-create-entity-btn"
                                    data-entity-type="<?php echo esc_attr($component['entity_type']); ?>">
                                + Créer
                            </button>
                        </div>
                    </div>
                    
                    <!-- Suggestions d'entités existantes -->
                    <div class="sisme-entity-suggestions">
                        <div class="sisme-entity-suggestions-list">
                            <?php if (empty($entities)): ?>
                                <p class="sisme-no-entities-available">Aucun <?php echo esc_html(strtolower($component['label'])); ?> disponible. Créez le premier !</p>
                            <?php else: ?>
                                <?php foreach ($entities as $entity): ?>
                                    <?php
                                    $entity_website = get_term_meta($entity->term_id, 'entity_website', true);
                                    ?>
                                    <div class="sisme-entity-suggestion-item" data-entity-id="<?php echo esc_attr($entity->term_id); ?>">
                                        <div class="sisme-entity-suggestion-content">
                                            <strong class="sisme-entity-suggestion-name"><?php echo esc_html($entity->name); ?></strong>
                                            <?php if (!empty($entity_website)): ?>
                                                <div class="sisme-entity-suggestion-website">
                                                    <a href="<?php echo esc_url($entity_website); ?>" 
                                                       target="_blank" 
                                                       class="sisme-entity-website-link"
                                                       title="Site web de <?php echo esc_attr($entity->name); ?>">
                                                        <?php echo esc_html($entity_website); ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Récupérer les entités existantes (enfants de editeurs-developpeurs)
     */
    private function get_existing_entities() {
        $parent_category = get_category_by_slug('editeurs-developpeurs');
        if (!$parent_category) {
            error_log('ERREUR: Catégorie parent "editeurs-developpeurs" introuvable');
            return array();
        }
        
        $entities = get_categories(array(
            'parent' => $parent_category->term_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        return $entities;
    }

    /**
     * Afficher le composant développeurs avec interface moderne
     */
    private function render_game_developers_component() {
        $this->render_modern_entity_component('game_developers');
    }

    /**
     * Afficher le composant éditeurs avec interface moderne
     * Rendu du composant screenshots
     */
    private function render_game_publishers_component() {
        $this->render_modern_entity_component('game_publishers');
    }

    /**
     * Rendu du composant screenshots avec fonctionnalités de modification
     */
    private function render_screenshots_component($component_name = 'screenshots') {
        // Récupérer le composant depuis available_components
        $component = $this->available_components[$component_name];
        
        $field_id = $this->module_id . '_screenshots';
        $value = isset($this->form_data['screenshots']) ? $this->form_data['screenshots'] : array();
        
        // Si $value est une chaîne, la convertir en array
        if (is_string($value) && !empty($value)) {
            $value = explode(',', $value);
        }
        
        // S'assurer que $value est toujours un array
        if (!is_array($value)) {
            $value = array();
        }
        
        ?>
        <tr>
            <td>
                <div class="sisme-screenshots-selector">
                    <label class="sisme-form-label" for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($component['label']); ?></label>
                    
                    <?php if (!empty($component['description'])): ?>
                        <p class="sisme-form-description"><?php echo esc_html($component['description']); ?></p>
                    <?php endif; ?>
                    
                    <div class="sisme-screenshots-controls" style="margin-bottom: 15px;">
                        <button type="button" class="sisme-btn sisme-btn--secondary" id="select-screenshots">
                            📷 Ajouter des screenshots
                        </button>
                        <?php if (!empty($value)): ?>
                            <button type="button" class="sisme-btn sisme-btn--danger" id="clear-all-screenshots">
                                🗑️ Tout supprimer
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div id="screenshots-preview" class="sisme-screenshots-gallery" style="margin-top: 15px;">
                        <?php if (!empty($value)): ?>
                            <?php foreach ($value as $index => $screenshot_id): ?>
                                <?php if (!empty($screenshot_id) && is_numeric($screenshot_id)): ?>
                                    <?php $image = wp_get_attachment_image_src($screenshot_id, 'thumbnail'); ?>
                                    <?php if ($image): ?>
                                        <div class="sisme-screenshot-item" data-id="<?php echo esc_attr($screenshot_id); ?>">
                                            <img src="<?php echo esc_url($image[0]); ?>" alt="Screenshot">
                                            <div class="sisme-screenshot-overlay">
                                                <button type="button" class="sisme-screenshot-remove" title="Supprimer">❌</button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="sisme-no-screenshots">
                                <p>Aucun screenshot ajouté</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <input type="hidden" name="screenshots" id="<?php echo esc_attr($field_id); ?>" value="<?php echo esc_attr(implode(',', $value)); ?>">
                </div>
                
                <style>
                .sisme-screenshots-gallery {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                    gap: 10px;
                    max-height: 300px;
                    overflow-y: auto;
                    border: 2px dashed #ddd;
                    padding: 15px;
                    border-radius: 8px;
                    background: #fafafa;
                }
                
                .sisme-screenshot-item {
                    position: relative;
                    border-radius: 6px;
                    overflow: hidden;
                    transition: transform 0.2s;
                }
                
                .sisme-screenshot-item:hover {
                    transform: scale(1.05);
                }
                
                .sisme-screenshot-item img {
                    width: 80px;
                    height: 80px;
                    object-fit: cover;
                    display: block;
                    border-radius: 6px;
                }
                
                .sisme-screenshot-overlay {
                    position: absolute;
                    top: 0;
                    right: 0;
                    background: rgba(0,0,0,0.7);
                    border-radius: 0 6px 0 6px;
                    opacity: 0;
                    transition: opacity 0.2s;
                }
                
                .sisme-screenshot-item:hover .sisme-screenshot-overlay {
                    opacity: 1;
                }
                
                .sisme-screenshot-remove {
                    background: none;
                    border: none;
                    color: white;
                    cursor: pointer;
                    padding: 4px;
                    font-size: 12px;
                    line-height: 1;
                }
                
                .sisme-no-screenshots {
                    grid-column: 1 / -1;
                    text-align: center;
                    color: #666;
                    font-style: italic;
                    padding: 40px 20px;
                }
                
                .sisme-screenshots-controls {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                }
                </style>
            </td>
        </tr>
        <?php
    }

    /**
     * Compter le nombre de jeux associés à une entité (développeur/éditeur)
     */
    private function count_games_for_entity($entity_id) {
        // Récupérer toutes les étiquettes (jeux)
        $all_tags = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'fields' => 'ids'
        ]);
        
        if (is_wp_error($all_tags) || empty($all_tags)) {
            return 0;
        }
        
        $games_count = 0;
        
        foreach ($all_tags as $tag_id) {
            // Vérifier si cette entité est dans les développeurs
            $developers = get_term_meta($tag_id, 'game_developers', true);
            if (is_array($developers) && in_array($entity_id, $developers)) {
                $games_count++;
                continue; // Éviter de compter 2 fois si l'entité est à la fois dev et éditeur
            }
            
            // Vérifier si cette entité est dans les éditeurs
            $publishers = get_term_meta($tag_id, 'game_publishers', true);
            if (is_array($publishers) && in_array($entity_id, $publishers)) {
                $games_count++;
            }
        }
        
        return $games_count;
    }

    /**
     * Interface moderne pour développeurs/éditeurs
     */
    private function render_modern_entity_component($component_name) {
        $component = $this->components[$component_name];
        $value = isset($this->form_data[$component['output_var']]) ? $this->form_data[$component['output_var']] : array();
        $field_id = $this->module_id . '_' . $component_name;
        $required_attr = $component['required'] ? 'required' : '';
        $required_label = $component['required'] ? ' *' : '';
        
        // Récupérer les entités existantes
        $entities = $this->get_existing_entities();
        
        // Déterminer le type d'entité pour les labels
        $entity_type = $component['entity_type'] === 'developer' ? 'développeur' : 'Éditeur';
        $entity_type_plural = $component['entity_type'] === 'developer' ? 'développeurs' : 'Éditeurs';
        ?>
        <tr>
            
            
            <td>
                <div class="sisme-game-entities-component" data-component="<?php echo esc_attr($component_name); ?>">
                    <!-- Entités sélectionnées -->
                    <div class="sisme-selected-entities">
                        <label class="sisme-form-label"><?php echo ucfirst($entity_type_plural); ?></label>
                        <div class="sisme-selected-entities-display sisme-selected-display-base sisme-tags-list" id="<?php echo esc_attr($field_id . '_selected'); ?>">
                            <?php if (empty($value)): ?>
                                <span class="sisme-no-selection">Aucun <?php echo $entity_type; ?> sélectionné</span>
                            <?php else: ?>
                                <?php foreach ($value as $entity_id): ?>
                                    <?php $entity = get_category($entity_id); ?>
                                    <?php if ($entity): ?>
                                        <?php $entity_website = get_term_meta($entity->term_id, 'entity_website', true); ?>
                                        <span class="sisme-tag sisme-tag--selected sisme-tag--entity" data-entity-id="<?php echo esc_attr($entity_id); ?>">
                                            <?php echo esc_html($entity->name); ?>
                                            <?php if (!empty($entity_website)): ?>
                                                <span class="sisme-entity-website-icon" title="Site web disponible"></span>
                                            <?php endif; ?>
                                            <span class="sisme-tag__remove remove-entity" title="Retirer cet <?php echo $entity_type; ?>">&times;</span>
                                            <input type="hidden" name="<?php echo esc_attr($component['output_var']); ?>[]" value="<?php echo esc_attr($entity_id); ?>">
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Champ de recherche/création -->
                    <div class="sisme-entity-search-controls sisme-search-controls-base">
                        <input type="text" 
                               id="<?php echo esc_attr($field_id . '_search'); ?>"
                               class="sisme-form-input sisme-entity-search-input" 
                               placeholder="Rechercher ou créer un <?php echo $entity_type; ?>...">
                        
                        <button type="button" class="sisme-btn sisme-btn--secondary sisme-create-entity-btn">
                            + Créer
                        </button>
                    </div>
                    
                    <!-- Champ URL pour création (masqué par défaut) -->
                    <div class="sisme-entity-url-field" style="display: none;">
                        <label class="sisme-form-label">Site web (optionnel) :</label>
                        <input type="url" 
                               class="sisme-form-input sisme-entity-url-input" 
                               placeholder="https://exemple.com">
                    </div>
                    
                    <!-- Liste des suggestions -->
                    <div class="sisme-entity-suggestions sisme-suggestions-parent-base">
                        <div class="sisme-entity-suggestions-list sisme-suggestions-container-base">
                            <?php foreach ($entities as $entity): ?>
                                <?php 
                                $entity_website = get_term_meta($entity->term_id, 'entity_website', true);
                                $games_count = $this->count_games_for_entity($entity->term_id);
                                ?>
                                <div class="suggestion-item" 
                                     data-entity-id="<?php echo esc_attr($entity->term_id); ?>" 
                                     data-entity-name="<?php echo esc_attr($entity->name); ?>">
                                    <div>
                                        <strong><?php echo esc_html($entity->name); ?></strong>
                                        <?php if (!empty($entity_website)): ?>
                                            <span class="sisme-entity-website-icon" title="Site web disponible"></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                </div>
                <p class="description"><?php echo esc_html($component['description']); ?></p>
            </td>

        </tr>
        <?php
    }

    /**
     * Afficher le composant genres de jeu
     */
    private function render_game_genres_component() {
        $component = $this->components['game_genres'];
        $value = isset($this->form_data['game_genres']) ? $this->form_data['game_genres'] : array();
        $field_id = $this->module_id . '_game_genres';
        $required_attr = $component['required'] ? 'required' : '';
        $required_label = $component['required'] ? ' *' : '';
        
        // Récupérer les genres existants (catégories enfants de "Jeux" sauf les modes)
        $genres = $this->get_existing_genres();
        ?>
        <tr>
            
            <td>
                <div class="sisme-game-genres-component">
                    
                    <!-- Genres sélectionnés -->
                    <div class="sisme-selected-genres">
                        <label class="sisme-form-label">Genres</label>
                        <div class="sisme-selected-genres-display sisme-selected-display-base sisme-tags-list" id="<?php echo esc_attr($field_id . '_selected'); ?>">
                            <?php if (empty($value)): ?>
                                <span class="sisme-no-selection">Aucun genre sélectionné</span>
                            <?php else: ?>
                                <?php foreach ($value as $genre_id): ?>
                                    <?php $genre = get_category($genre_id); ?>
                                    <?php if ($genre): ?>
                                        <span class="sisme-tag sisme-tag--selected sisme-tag--genre" data-genre-id="<?php echo esc_attr($genre_id); ?>">
                                            <?php echo esc_html(str_replace('jeux-', '', $genre->name ?? '')); ?>
                                            <span class="sisme-tag__remove remove-genre" title="Retirer ce genre">&times;</span>
                                            <input type="hidden" name="game_genres[]" value="<?php echo esc_attr($genre_id); ?>">
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Champ de recherche/création -->
                    <div class="sisme-genre-search-controls sisme-search-controls-base">
                        <input type="text" 
                               id="<?php echo esc_attr($field_id . '_search'); ?>"
                               class="sisme-form-input sisme-genre-search-input" 
                               placeholder="Rechercher ou créer un genre...">
                        
                        <button type="button" class="sisme-btn sisme-btn--secondary sisme-create-genre-btn">
                            + Créer
                        </button>
                    </div>
                    
                    <!-- Liste des suggestions -->
                    <div class="sisme-genre-suggestions sisme-suggestions-parent-base">
                        <div class="sisme-genre-suggestions-list sisme-suggestions-container-base">
                            <?php foreach ($genres as $genre): ?>
                                <div class="suggestion-item" 
                                     data-genre-id="<?php echo esc_attr($genre->term_id); ?>" 
                                     data-genre-name="<?php echo esc_attr($genre->name); ?>">
                                    <div>
                                        <strong><?php echo esc_html(str_replace('jeux-', '', $genre->name)); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                </div>
                <p class="description"><?php echo esc_html($component['description']); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Récupérer les genres existants
     */
    private function get_existing_genres() {
        // Chercher la catégorie parent "Jeux" avec le bon slug ou nom
        $parent_category = get_category_by_slug('jeux-video'); // CORRECTION ICI
        
        if (!$parent_category) {
            // Essayer par nom si le slug ne fonctionne pas
            $parent_category = get_term_by('name', 'Jeux', 'category');
        }
        
        if (!$parent_category) {
            error_log('ERREUR: Catégorie parent "Jeux" introuvable. Slugs testés: jeux-video, nom: Jeux');
            return array();
        }
        
        $excluded_slugs = ['jeux-solo', 'jeux-multijoueur', 'jeux-cooperatif', 'jeux-competitif'];
        
        $categories = get_categories(array(
            'parent' => $parent_category->term_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        // Filtrer les modes de jeu
        $genres = array();
        foreach ($categories as $category) {
            if (!in_array($category->slug, $excluded_slugs)) {
                $genres[] = $category;
            }
        }
        
        return $genres;
    }

    /**
     * Afficher le composant modes de jeu
     */
    private function render_game_modes_component() {
        $component = $this->components['game_modes'];
        $value = isset($this->form_data['game_modes']) ? $this->form_data['game_modes'] : array();
        $field_id = $this->module_id . '_game_modes';
        $required_attr = $component['required'] ? 'required' : '';
        $required_label = $component['required'] ? ' *' : '';
        
        // Modes de jeu fixes
        $available_modes = [
            'solo' => 'Solo',
            'multijoueur' => 'Multijoueur', 
            'cooperatif' => 'Coopératif',
            'competitif' => 'Compétitif'
        ];
        ?>
        <tr>
            
            <td>
                <div class="sisme-game-modes-component">
                    
                    <!-- Modes sélectionnés -->
                    <div class="sisme-selected-modes">
                        <label class="sisme-form-label">Modes</label>
                        <div class="sisme-selected-modes-display sisme-selected-display-base sisme-tags-list" id="<?php echo esc_attr($field_id . '_selected'); ?>">
                            <?php if (empty($value)): ?>
                                <span class="sisme-no-selection">Aucun mode sélectionné</span>
                            <?php else: ?>
                                <?php foreach ($value as $mode_key): ?>
                                    <?php if (isset($available_modes[$mode_key])): ?>
                                        <span class="sisme-tag sisme-tag--selected sisme-tag--mode" data-mode-key="<?php echo esc_attr($mode_key); ?>">
                                            <?php echo esc_html($available_modes[$mode_key]); ?>
                                            <span class="sisme-tag__remove remove-mode" title="Retirer ce mode">&times;</span>
                                            <input type="hidden" name="game_modes[]" value="<?php echo esc_attr($mode_key); ?>">
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Modes disponibles pour sélection -->
                    <div class="sisme-mode-options sisme-suggestions-parent-base">
                        <div class="sisme-modes-grid">
                            <?php foreach ($available_modes as $mode_key => $mode_label): ?>
                                <div class="mode-option sisme-mode-option-card" data-mode-key="<?php echo esc_attr($mode_key); ?>">
                                    <strong><?php echo esc_html($mode_label); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <p class="description"><?php echo esc_html($component['description']); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Afficher tous les covers
     */
    private function render_all_covers_component() {
        $covers = [Sisme_Utils_Games::META_COVER_MAIN, 'cover_news', 'cover_patch', 'cover_test', 'cover_vertical'];
        $cover_labels = [
            Sisme_Utils_Games::META_COVER_MAIN => 'Cover Principale',
            'cover_news' => 'Cover News', 
            'cover_patch' => 'Cover Patch',
            'cover_test' => 'Cover Test',
            'cover_vertical' => 'Cover Verticale'
        ];
        ?>
        <tr>
            <td>
                <div class="sisme-covers-component">
                    <div class="sisme-covers-grid">
                        <?php foreach ($covers as $cover_name): ?>
                            <?php 
                            $component = $this->available_components[$cover_name];
                            $value = isset($this->form_data[$component['output_var']]) ? $this->form_data[$component['output_var']] : '';
                            $field_id = $this->module_id . '_' . $cover_name;
                            $cover_label = $cover_labels[$cover_name];
                            ?>
                            <div class="sisme-cover-item">
                                <label class="sisme-cover-label" for="<?php echo esc_attr($field_id); ?>">
                                    <?php echo esc_html($cover_label); ?>
                                </label>
                                <div class="sisme-media-selector" data-cover-type="<?php echo esc_attr($cover_name); ?>">
                                    <?php if (!empty($value)): ?>
                                        <?php $image_url = wp_get_attachment_image_url($value, 'medium'); ?>
                                        <?php if ($image_url): ?>
                                            <img src="<?php echo esc_url($image_url); ?>" 
                                                 alt="<?php echo esc_attr($cover_label); ?>" 
                                                 class="sisme-cover-preview sisme-form-cover-preview">
                                        <?php else: ?>
                                            <div class="sisme-cover-placeholder sisme-cover-error">❌</div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="sisme-cover-placeholder sisme-cover-empty">📷</div>
                                    <?php endif; ?>
                                    
                                    <div class="sisme-media-buttons">
                                        <button type="button" class="sisme-btn sisme-btn--secondary sisme-btn--sm sisme-select-media-btn" 
                                                data-field-id="<?php echo esc_attr($field_id); ?>">
                                            <?php echo !empty($value) ? 'Modifier' : 'Sélectionner'; ?>
                                        </button>
                                        <?php if (!empty($value)): ?>
                                            <button type="button" class="sisme-button-no-margin sisme-btn sisme-btn--secondary sisme-btn--sm sisme-remove-media-btn"
                                                data-field-id="<?php echo esc_attr($field_id); ?>"
                                                onclick="event.preventDefault(); return false;">
                                            Supprimer
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <input type="hidden" 
                                           id="<?php echo esc_attr($field_id); ?>" 
                                           name="<?php echo esc_attr($component['output_var']); ?>" 
                                           value="<?php echo esc_attr($value); ?>">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Afficher le composant plateformes
     */
    private function render_game_platforms_component() {
        $component = $this->components['game_platforms'];
        $value = isset($this->form_data['game_platforms']) ? $this->form_data['game_platforms'] : [];
        $field_id = $this->module_id . '_game_platforms';
        $required_attr = $component['required'] ? 'required' : '';
        $required_label = $component['required'] ? ' *' : '';
        
        // Structure hiérarchique des plateformes
        $platforms = [
            'Mobile' => [
                'ios' => 'iOS',
                'android' => 'Android'
            ],
            'Console' => [
                'xbox' => 'Xbox',
                'playstation' => 'PlayStation',
                'switch' => 'Switch'
            ],
            'PC' => [
                'web' => 'Web',
                'mac' => 'Mac',
                'windows' => 'Windows'
            ]
        ];
        ?>
        <tr>
            <td>
                <div class="sisme-game-platforms-component">
                    
                    <!-- Plateformes sélectionnées -->
                    <div class="sisme-selected-platforms">
                        <label class="sisme-form-label">Plateformes</label>
                        <div class="sisme-selected-platforms-display sisme-selected-display-base sisme-tags-list" id="<?php echo esc_attr($field_id . '_selected'); ?>">
                            <?php if (empty($value)): ?>
                                <span class="sisme-no-selection">Aucune plateforme sélectionnée</span>
                            <?php else: ?>
                                <?php foreach ($value as $platform_key): ?>
                                    <?php 
                                    // Trouver le nom de la plateforme
                                    $platform_name = '';
                                    foreach ($platforms as $category => $category_platforms) {
                                        if (isset($category_platforms[$platform_key])) {
                                            $platform_name = $category_platforms[$platform_key];
                                            break;
                                        }
                                    }
                                    ?>
                                    <?php if ($platform_name): ?>
                                        <span class="sisme-tag sisme-tag--selected sisme-tag--platform" data-platform-key="<?php echo esc_attr($platform_key); ?>">
                                            <?php echo esc_html($platform_name); ?>
                                            <span class="sisme-tag__remove remove-platform" title="Retirer cette plateforme">&times;</span>
                                            <input type="hidden" name="game_platforms[]" value="<?php echo esc_attr($platform_key); ?>">
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Plateformes disponibles par catégorie -->
                    <?php foreach ($platforms as $category => $category_platforms): ?>
                        <div class="sisme-platform-category sisme-suggestions-parent-base">
                            <label class="sisme-form-label sisme-platform-category-label">
                                <span class="sisme-category-icon"><?php echo $category === 'Mobile' ? '📱' : ($category === 'Console' ? '🎮' : '💻'); ?></span>
                                <?php echo esc_html($category); ?>
                            </label>
                            <div class="sisme-platforms-grid">
                                <?php foreach ($category_platforms as $platform_key => $platform_name): ?>
                                    <div class="platform-option sisme-platform-option-card" 
                                         data-platform-key="<?php echo esc_attr($platform_key); ?>"
                                         data-category="<?php echo esc_attr(strtolower($category)); ?>">
                                        <span class="sisme-platform-icon"><?php 
                                            // Icônes par plateforme
                                            $icons = [
                                                'ios' => '🍎', 'android' => '🤖',
                                                'xbox' => '🎮', 'playstation' => '🎮', 'switch' => '🕹️',
                                                'web' => '🌐', 'mac' => '🍎', 'windows' => '🪟'
                                            ];
                                            echo $icons[$platform_key] ?? '💻';
                                        ?></span>
                                        <strong><?php echo esc_html($platform_name); ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                </div>
                <p class="description"><?php echo esc_html($component['description']); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Afficher le composant date de sortie
     */
    private function render_release_date_component() {
        $component = $this->components['release_date'];
        $value = isset($this->form_data['release_date']) ? $this->form_data['release_date'] : '';
        $field_id = $this->module_id . '_release_date';
        $required_attr = $component['required'] ? 'required' : '';
        $required_label = $component['required'] ? ' *' : '';
        ?>
        <tr>
            
            <td>
                <div class="sisme-date-component">
                    <input type="date" 
                           id="<?php echo esc_attr($field_id); ?>" 
                           name="release_date" 
                           value="<?php echo esc_attr($value); ?>"
                           class="sisme-form-input sisme-form-input--date"
                           <?php echo $required_attr; ?>>
                    <?php if (!empty($component['description'])): ?>
                        <p class="sisme-form-description"><?php echo esc_html($component['description']); ?></p>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Afficher le composant lien trailer
     */
    private function render_trailer_link_component() {
        $component = $this->components['trailer_link'];
        $value = isset($this->form_data['trailer_link']) ? $this->form_data['trailer_link'] : '';
        $field_id = $this->module_id . '_trailer_link';
        $required_attr = $component['required'] ? 'required' : '';
        $required_label = $component['required'] ? ' *' : '';
        ?>
        <tr>
            <td>
                <div class="sisme-trailer-link-component sisme-flex-row-center-gap">
                    <span class="sisme-platform-icon">
                        <span class="sisme-store-icon">
                            <img src="https://games.sisme.fr/wp-content/uploads/2025/06/Logo-YT.webp" alt="Youtube logo" class="sisme-store-logo">
                        </span>
                    </span>
                    <input type="url" 
                           id="<?php echo esc_attr($field_id); ?>" 
                           name="trailer_link" 
                           value="<?php echo esc_attr($value); ?>"
                           placeholder="https://www.youtube.com/watch?v=..."
                           class="sisme-form-input sisme-form-input--url"
                           <?php echo $required_attr; ?>>
                    <?php if (!empty($component['description'])): ?>
                        <p class="sisme-form-description"><?php echo esc_html($component['description']); ?></p>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Afficher le composant liens externes
     */
    private function render_external_links_component() {
        $component = $this->components['external_links'];
        $value = isset($this->form_data['external_links']) ? $this->form_data['external_links'] : [];
        $required_label = $component['required'] ? ' *' : '';
        
        $platforms = [
            'steam' => 'Steam',
            'epic' => 'Epic Games',
            'gog' => 'GOG'
        ];
        ?>
        <tr>
            <td>
                <div class="sisme-external-links-component">
                    <div class="sisme-external-links-grid">
                        <?php foreach ($platforms as $platform_key => $platform_name): ?>
                            <div class="sisme-external-link-field">
                                <label for="<?php echo esc_attr($this->module_id . '_' . $platform_key); ?>" 
                                       class="sisme-external-link-label sisme-external-link-label--<?php echo esc_attr($platform_key); ?>">
                                    <span class="sisme-platform-icon">
                                        <span class="sisme-store-icon">
                                            <?php 
                                            $store_logos = [
                                                'steam' => 'Logo-STEAM.webp',
                                                'epic' => 'Logo-EPIC.webp', 
                                                'gog' => 'Logo-GOG.webp'
                                            ];
                                            
                                            if (isset($store_logos[$platform_key])): ?>
                                                <img src="<?php echo esc_url("https://games.sisme.fr/wp-content/uploads/2025/06/" . $store_logos[$platform_key]); ?>" 
                                                     alt="<?php echo esc_attr($platform_name); ?>" 
                                                     class="sisme-store-logo">
                                            <?php else: ?>
                                                🔗
                                            <?php endif; ?>
                                        </span>
                                    </span>
                                </label>
                                <input type="url" 
                                       id="<?php echo esc_attr($this->module_id . '_' . $platform_key); ?>" 
                                       name="external_links[<?php echo esc_attr($platform_key); ?>]" 
                                       value="<?php echo esc_attr($value[$platform_key] ?? ''); ?>"
                                       placeholder="https://..."
                                       class="sisme-form-input sisme-form-input--url">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($component['description'])): ?>
                        <p class="sisme-form-description"><?php echo esc_html($component['description']); ?></p>
                    <?php endif; ?>
                </div>
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
        <tr class="sisme-form-row">
            <td class="sisme-form-field-cell">
                <div class="sisme-game-name-component">
                    
                    <!-- Jeu sélectionné -->
                    <div class="sisme-selected-game">
                        <label class="sisme-form-label">Jeu</label>
                        <div class="sisme-selected-game-display sisme-selected-display-base sisme-tags-list" id="<?php echo esc_attr($field_id . '_selected'); ?>">
                            <?php if (empty($value)): ?>
                                <span class="sisme-no-selection">Aucun jeu sélectionné</span>
                            <?php else: ?>
                                <?php $selected_tag = get_tag($value); ?>
                                <?php if ($selected_tag): ?>
                                    <span class="sisme-tag sisme-tag--selected sisme-tag--game" data-game-id="<?php echo esc_attr($value); ?>">
                                        <?php echo esc_html($selected_tag->name); ?>
                                        <span class="sisme-tag__remove remove-game" title="Retirer ce jeu">&times;</span>
                                        <input type="hidden" name="game_name" value="<?php echo esc_attr($value); ?>">
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Champ de recherche/création -->
                    <div class="sisme-game-search-controls sisme-search-controls-base">
                        <input type="text" 
                               id="<?php echo esc_attr($field_id . '_search'); ?>"
                               class="sisme-form-input sisme-game-search-input" 
                               placeholder="Rechercher ou créer un jeu...">
                        
                        <button type="button" class="sisme-btn sisme-btn--secondary sisme-create-game-btn">
                            + Créer
                        </button>
                    </div>
                    
                    <!-- Liste des suggestions -->
                    <div class="sisme-game-suggestions sisme-suggestions-parent-base">
                        <div class="sisme-suggestions-list sisme-suggestions-container-base">
                            <?php foreach ($tags as $tag): ?>
                                <div class="suggestion-item" 
                                     data-game-id="<?php echo esc_attr($tag->term_id); ?>" 
                                     data-game-name="<?php echo esc_attr($tag->name); ?>">
                                    <div><strong><?php echo esc_html($tag->name); ?></strong></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                </div>
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
            
            <td>
                <div class="sisme-description-component">
                    
                    <!-- Textarea avec styling amélioré -->
                    <div class="sisme-description-field">
                        <textarea id="<?php echo esc_attr($field_id); ?>" 
                                  name="description" 
                                  rows="<?php echo esc_attr($component['rows']); ?>"
                                  placeholder="Décrivez le jeu, son gameplay, son univers, ses mécaniques principales..."
                                  class="sisme-form-textarea sisme-rich-textarea sisme-description-textarea"
                                  <?php echo $required_attr; ?>><?php echo esc_textarea($value); ?></textarea>
                    </div>
                    
                    <!-- Aide contextuelle -->
                    <div class="sisme-description-help">                        
                        <div class="sisme-help-section">
                            <span class="sisme-help-label">🏷️ Balises autorisées :</span>
                            <div class="sisme-allowed-tags">
                                <?php foreach ($component['allowed_tags'] as $tag): ?>
                                    <code class="sisme-tag-example" title="Cliquer pour insérer">&lt;<?php echo esc_html($tag); ?>&gt;</code>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                </div>
                <p class="description"><?php echo esc_html($component['description']); ?></p>
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

            case 'trailer_link':
                $this->render_trailer_link_component();
                break;

            case 'game_genres':
                $this->render_game_genres_component();
                break;

            case 'game_modes':
                $this->render_game_modes_component();
                break;

            case 'game_developers':
                $this->render_game_developers_component();
                break;
                
            case 'game_publishers':
                $this->render_game_publishers_component();
                break;

            case Sisme_Utils_Games::META_COVER_MAIN:
            case 'cover_news':
            case 'cover_patch':
            case 'cover_test':
            case 'cover_vertical':
                // Les covers sont maintenant gérés groupés
                static $covers_rendered = false;
                if (!$covers_rendered) {
                    $this->render_all_covers_component();
                    $covers_rendered = true;
                }
                break;

            case 'game_platforms':
                $this->render_game_platforms_component();
                break;
                
            case 'release_date':
                $this->render_release_date_component();
                break;
                
            case 'external_links':
                $this->render_external_links_component();
                break;

            case 'screenshots':
                $this->render_screenshots_component('screenshots');
                break;

            case 'is_team_choice':
                $this->render_team_choice_component();
                break;
                
            default:
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
     * Rendre le composant "Choix de l'équipe"
     */
    private function render_team_choice_component() {
        $config = $this->components['is_team_choice'];
        $output_var = $config['output_var'];
        $field_id = $this->module_id . '_' . $output_var;
        $use_table = $this->form_options['table'] ?? true;
        
        $current_value = isset($this->form_data[$output_var]) ? $this->form_data[$output_var] : false;
        $is_checked = $current_value === '1' || $current_value === true;
        
        if ($use_table) {
            echo '<tr>';
            echo '<th scope="row">';
            echo '<label for="' . esc_attr($field_id) . '">' . esc_html($config['label']) . '</label>';
            echo '</th>';
            echo '<td>';
        } else {
            echo '<label class="sisme-field-label" for="' . esc_attr($field_id) . '">';
            echo '<strong>' . esc_html($config['label']) . '</strong>';
            echo '</label>';
        }
        
        echo '<div class="sisme-team-choice-container">';
        echo '<label class="sisme-team-choice-checkbox">';
        echo '<input type="checkbox" ';
        echo 'id="' . esc_attr($field_id) . '" ';
        echo 'name="' . esc_attr($output_var) . '" ';
        echo 'value="1" ';
        if ($is_checked) echo 'checked="checked" ';
        echo 'class="sisme-checkbox-input">';
        echo '<span class="sisme-checkbox-label">💖 Ce jeu est un choix spécial de l\'équipe</span>';
        echo '</label>';
        
        if (!empty($config['description'])) {
            echo '<p class="description">' . esc_html($config['description']) . '</p>';
        }
        
        echo '<div class="sisme-team-choice-info">';
        echo '<p><em>💡 Les jeux marqués comme "choix de l\'équipe" peuvent être filtrés spécifiquement dans les shortcodes et widgets.</em></p>';
        echo '</div>';
        echo '</div>';
        
        if ($use_table) {
            echo '</td>';
            echo '</tr>';
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

                case 'game_platforms':
                    if (!empty($value) && !is_array($value)) {
                        $errors[$output_var] = 'Format de plateformes invalide.';
                    }
                    break;
                    
                case 'release_date':
                    if (!empty($value) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        $errors[$output_var] = 'Format de date invalide (YYYY-MM-DD attendu).';
                    }
                    break;
                    
                case 'external_links':
                    if (!empty($value) && is_array($value)) {
                        foreach ($value as $platform => $url) {
                            if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
                                $errors[$output_var] = sprintf('URL invalide pour %s.', $platform);
                                break;
                            }
                        }
                    }
                    break;

                case 'game_genres':
                    if (!empty($value)) {
                        // Vérifier que c'est un tableau
                        if (!is_array($value)) {
                            $errors[$output_var] = 'Format de données invalide pour les genres.';
                            break;
                        }
                        
                        // Vérifier que chaque ID de genre est valide
                        foreach ($value as $genre_id) {
                            if (!is_numeric($genre_id) || !get_category($genre_id)) {
                                $errors[$output_var] = 'Un ou plusieurs genres sélectionnés sont invalides.';
                                break 2; // Sortir des deux boucles
                            }
                        }
                    }
                    break;

                case 'game_modes':
                    if (!empty($value)) {
                        // Vérifier que c'est un tableau
                        if (!is_array($value)) {
                            $errors[$output_var] = 'Format de données invalide pour les modes.';
                            break;
                        }
                        
                        // Modes valides
                        $valid_modes = ['solo', 'multijoueur', 'cooperatif', 'competitif'];
                        
                        // Vérifier que chaque mode est valide
                        foreach ($value as $mode) {
                            if (!in_array($mode, $valid_modes)) {
                                $errors[$output_var] = 'Un ou plusieurs modes sélectionnés sont invalides.';
                                break 2; // Sortir des deux boucles
                            }
                        }
                    }
                    break;

                case 'game_developers':
                case 'game_publishers':
                    if (!empty($value)) {
                        if (!is_array($value)) {
                            $errors[$output_var] = 'Format de données invalide pour les entités.';
                            break;
                        }
                        
                        foreach ($value as $entity_id) {
                            if (!is_numeric($entity_id) || !get_category($entity_id)) {
                                $errors[$output_var] = 'Une ou plusieurs entités sélectionnées sont invalides.';
                                break 2;
                            }
                        }
                    }
                    break;

                case 'description':
                case 'is_team_choice':
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

            // === GESTION DES SCREENSHOTS AMÉLIORÉE ===
            // Sélecteur de screenshots multiple
            $('#select-screenshots').on('click', function(e) {
                e.preventDefault();
                
                var frame = wp.media({
                    title: 'Ajouter des screenshots',
                    button: { text: 'Ajouter ces images' },
                    multiple: true
                });
                
                frame.on('select', function() {
                    var attachments = frame.state().get('selection').toJSON();
                    var currentIds = getScreenshotIds();
                    
                    // Supprimer le message "aucun screenshot"
                    $('#screenshots-preview').find('.sisme-no-screenshots').remove();
                    
                    // Ajouter les nouvelles images
                    attachments.forEach(function(attachment) {
                        if (currentIds.indexOf(attachment.id.toString()) === -1) {
                            addScreenshotToGallery(attachment);
                        }
                    });
                    
                    updateScreenshotsField();
                    updateClearButton();
                });
                
                frame.open();
            });

            // Supprimer tous les screenshots
            $(document).on('click', '#clear-all-screenshots', function(e) {
                e.preventDefault();
                
                if (confirm('Êtes-vous sûr de vouloir supprimer tous les screenshots ?')) {
                    clearAllScreenshots();
                }
            });

            // Supprimer un screenshot individuel
            $(document).on('click', '.sisme-screenshot-remove', function(e) {
                e.preventDefault();
                
                var screenshotItem = $(this).closest('.sisme-screenshot-item');
                removeScreenshot(screenshotItem);
            });

            // Fonction pour récupérer les IDs actuels
            function getScreenshotIds() {
                var value = $('input[name="screenshots"]').val();
                return value ? value.split(',').filter(function(id) { return id.trim() !== ''; }) : [];
            }

            // Fonction pour ajouter un screenshot à la galerie
            function addScreenshotToGallery(attachment) {
                var imageUrl = attachment.sizes && attachment.sizes.thumbnail ? 
                               attachment.sizes.thumbnail.url : attachment.url;
                
                var screenshotHtml = 
                    '<div class="sisme-screenshot-item" data-id="' + attachment.id + '">' +
                        '<img src="' + imageUrl + '" alt="Screenshot">' +
                        '<div class="sisme-screenshot-overlay">' +
                            '<button type="button" class="sisme-screenshot-remove" title="Supprimer">❌</button>' +
                        '</div>' +
                    '</div>';
                
                $('#screenshots-preview').append(screenshotHtml);
            }

            // Fonction pour supprimer un screenshot
            function removeScreenshot(screenshotItem) {
                screenshotItem.fadeOut(300, function() {
                    $(this).remove();
                    updateScreenshotsField();
                    
                    // Si plus de screenshots, afficher le message
                    if ($('.sisme-screenshot-item').length === 0) {
                        showNoScreenshotsMessage();
                    }
                    
                    updateClearButton();
                });
            }

            // Fonction pour supprimer tous les screenshots
            function clearAllScreenshots() {
                $('.sisme-screenshot-item').fadeOut(300, function() {
                    $(this).remove();
                    updateScreenshotsField();
                    showNoScreenshotsMessage();
                    updateClearButton();
                });
            }

            // Fonction pour afficher le message "aucun screenshot"
            function showNoScreenshotsMessage() {
                var gallery = $('#screenshots-preview');
                if (gallery.find('.sisme-screenshot-item').length === 0) {
                    gallery.html(
                        '<div class="sisme-no-screenshots">' +
                            '<p>Aucun screenshot ajouté</p>' +
                        '</div>'
                    );
                }
            }

            // Fonction pour mettre à jour le champ caché
            function updateScreenshotsField() {
                var ids = [];
                $('.sisme-screenshot-item').each(function() {
                    var id = $(this).data('id');
                    if (id) {
                        ids.push(id);
                    }
                });
                
                $('input[name="screenshots"]').val(ids.join(','));
            }

            // Fonction pour mettre à jour le bouton "Tout supprimer"
            function updateClearButton() {
                var clearButton = $('#clear-all-screenshots');
                var hasScreenshots = $('.sisme-screenshot-item').length > 0;
                
                if (hasScreenshots && clearButton.length === 0) {
                    $('#select-screenshots').after(
                        '<button type="button" class="sisme-btn sisme-btn--danger" id="clear-all-screenshots" style="margin-left: 10px;">' +
                            '🗑️ Tout supprimer' +
                        '</button>'
                    );
                } else if (!hasScreenshots) {
                    clearButton.remove();
                }
            }

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

            // === GESTION DE LA DESCRIPTION ===
            // Insertion de balises au clic
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-tag-example', function() {
                var tag = $(this).text().replace(/[<>]/g, '');
                var textarea = $(this).closest('.sisme-description-component').find('.sisme-description-textarea')[0];
                
                if (textarea) {
                    var start = textarea.selectionStart;
                    var end = textarea.selectionEnd;
                    var text = textarea.value;
                    var before = text.substring(0, start);
                    var after = text.substring(end, text.length);
                    
                    // Insérer les balises ouvrante et fermante
                    var newText = before + '<' + tag + '></' + tag + '>' + after;
                    textarea.value = newText;
                    
                    // Repositionner le curseur entre les balises
                    var newPos = start + tag.length + 2;
                    textarea.setSelectionRange(newPos, newPos);
                    textarea.focus();
                    
                    // Effet visuel
                    $(this).css('background', 'var(--sisme-color-primary)').css('color', 'white');
                    setTimeout(function() {
                        $(this).css('background', '').css('color', '');
                    }.bind(this), 200);
                }
            });

            wp.media.frames.selectMedia.on('select', function() {
                var attachment = wp.media.frames.selectMedia.state().get('selection').first().toJSON();
                var fieldId = jQuery('.sisme-select-media-btn.active').data('field-id');
                
                if (fieldId && attachment.id) {
                    // Mettre à jour le champ caché
                    jQuery('#' + fieldId).val(attachment.id);
                    
                    // Trouver le container de la cover correspondante
                    var container = jQuery('.sisme-select-media-btn.active').closest('.sisme-media-selector');
                    
                    // Supprimer l'ancien placeholder/image
                    container.find('.sisme-cover-placeholder, .sisme-form-cover-preview').remove();
                    
                    // Ajouter la nouvelle image avant les boutons
                    var buttonsDiv = container.find('.sisme-media-buttons');
                    buttonsDiv.before('<img src="' + attachment.sizes.medium.url + '" alt="Cover" class="sisme-form-cover-preview">');
                    
                    // Mettre à jour le texte du bouton
                    var button = jQuery('.sisme-select-media-btn.active');
                    button.text('Modifier');
                    
                    // Afficher le bouton supprimer s'il est caché
                    button.siblings('.sisme-remove-media-btn').show();
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
                    // Vider le champ caché
                    $('#' + fieldId).val('');
                    
                    // Mettre à jour l'affichage visuel
                    var container = $(this).closest('.sisme-media-selector');
                    container.find('img, .sisme-cover-preview').remove();
                    container.find('.sisme-cover-placeholder').remove();
                    
                    // Ajouter le placeholder vide
                    var buttonsDiv = container.find('.sisme-media-buttons');
                    buttonsDiv.before('<div class="sisme-cover-placeholder sisme-cover-empty">📷</div>');
                    
                    // Mettre à jour les boutons
                    $(this).siblings('.sisme-select-media-btn').text('Sélectionner');
                    $(this).hide(); // Cacher le bouton supprimer
                }
            });

            // === GESTION DES GENRES (MULTI-SÉLECTION) ===
            // Sélection d'un genre depuis les suggestions
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-genre-suggestions-list .suggestion-item', function() {
                var genreId = $(this).data('genre-id');
                var genreName = $(this).data('genre-name');
                var container = $(this).closest('.sisme-game-genres-component');
                var selectedList = container.find('.sisme-selected-genres-display');
                
                // Vérifier si le genre n'est pas déjà sélectionné
                if (selectedList.find('[data-genre-id="' + genreId + '"]').length > 0) {
                    return; // Genre déjà sélectionné
                }
                
                addGenreToSelection(container, genreId, genreName);
            });

            // Suppression d'un genre sélectionné
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.remove-genre', function(e) {
                e.preventDefault();
                $(this).closest('.sisme-tag--genre').remove();
                
                // Vérifier s'il reste des genres sélectionnés
                var container = $(this).closest('.sisme-game-genres-component');
                var selectedDisplay = container.find('.sisme-selected-genres-display');
                if (selectedDisplay.find('.sisme-tag--genre').length === 0) {
                    selectedDisplay.html('<span class="sisme-no-selection">Aucun genre sélectionné</span>');
                }
            });

            // Création d'un nouveau genre
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-create-genre-btn', function(e) {
                e.preventDefault();
                
                var container = $(this).closest('.sisme-game-genres-component');
                var searchInput = container.find('.sisme-genre-search-input');
                var genreName = searchInput.val().trim();
                
                if (!genreName) {
                    alert('Veuillez saisir un nom de genre.');
                    return;
                }
                
                $(this).prop('disabled', true).text('Création...');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'sisme_create_category',
                        category_name: genreName,
                        parent_category: 'jeux',
                        nonce: '<?php echo wp_create_nonce('sisme_create_category'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            addGenreToSelection(container, response.data.term_id, response.data.name);
                            searchInput.val('');
                            addGenreToSuggestions(container, response.data);
                        }
                    },
                    complete: function() {
                        $(this).prop('disabled', false).text('+ Créer');
                    }.bind(this)
                });
            });

            // Recherche en temps réel dans les suggestions de genres
            $('#<?php echo esc_js($this->module_id); ?>').on('keyup', '.sisme-genre-search-input', function() {
                var searchTerm = $(this).val().toLowerCase();
                var container = $(this).closest('.sisme-game-genres-component');
                var suggestions = container.find('.suggestion-item');
                
                suggestions.each(function() {
                    var genreName = $(this).data('genre-name').toLowerCase();
                    if (genreName.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Fonction pour ajouter un genre à la sélection
            function addGenreToSelection(container, genreId, genreName) {
                var selectedDisplay = container.find('.sisme-selected-genres-display');
                
                // Supprimer le message "aucun genre sélectionné"
                selectedDisplay.find('.sisme-no-selection').remove();
                
                // Nettoyer le nom du genre (enlever "jeux-")
                var cleanGenreName = genreName.replace('jeux-', '');
                
                // Créer le tag de genre sélectionné
                var genreTag = $('<span class="sisme-tag sisme-tag--selected sisme-tag--genre" data-genre-id="' + genreId + '">' +
                                cleanGenreName +
                                '<span class="sisme-tag__remove remove-genre" title="Retirer ce genre">&times;</span>' +
                                '<input type="hidden" name="game_genres[]" value="' + genreId + '">' +
                                '</span>');
                
                selectedDisplay.append(genreTag);
            }

            // Fonction pour ajouter un genre aux suggestions
            function addGenreToSuggestions(container, genreData) {
                var suggestionsList = container.find('.sisme-genre-suggestions-list');
                var cleanGenreName = genreData.name.replace('jeux-', '');
                
                var suggestion = $('<div class="suggestion-item" ' +
                                  'data-genre-id="' + genreData.term_id + '" ' +
                                  'data-genre-name="' + genreData.name + '">' +
                                  '<div><strong>' + cleanGenreName + '</strong></div>' +
                                  '<span>0 jeu(x)</span>' +
                                  '</div>');
                
                suggestionsList.prepend(suggestion);
            }

            // === GESTION DES MODES DE JEU ===
            // Sélection d'un mode depuis les options disponibles

            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-mode-option-card', function(e) {
                e.preventDefault();
                
                var modeKey = $(this).data('mode-key');
                var modeLabel = $(this).find('strong').text();
                var container = $(this).closest('.sisme-game-modes-component');
                
                // Vérifier si déjà sélectionné
                if (container.find('.sisme-tag--mode[data-mode-key="' + modeKey + '"]').length > 0) {
                    alert('Ce mode est déjà sélectionné.');
                    return;
                }
                
                addModeToSelection(container, modeKey, modeLabel);
                
                // Effet visuel sur le bouton cliqué
                $(this).css('background-color', 'rgba(161, 183, 141, 0.1)');
                setTimeout(function() {
                    $(this).css('background-color', 'white');
                }.bind(this), 300);
            });

            // Suppression d'un mode sélectionné
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.remove-mode', function(e) {
                e.preventDefault();
                $(this).closest('.sisme-tag--mode').remove();
                
                // Vérifier s'il reste des modes sélectionnés
                var container = $(this).closest('.sisme-game-modes-component');
                var selectedDisplay = container.find('.sisme-selected-modes-display');
                if (selectedDisplay.find('.sisme-tag--mode').length === 0) {
                    selectedDisplay.html('<span class="sisme-no-selection">Aucun mode sélectionné</span>');
                }
            });

            // Fonction pour ajouter un mode à la sélection
            function addModeToSelection(container, modeKey, modeLabel) {
                var selectedDisplay = container.find('.sisme-selected-modes-display');
                
                // Supprimer le message "aucun mode sélectionné"
                selectedDisplay.find('.sisme-no-selection').remove();
                
                // Créer le tag de mode sélectionné
                var modeTag = $('<span class="sisme-tag sisme-tag--selected sisme-tag--mode" data-mode-key="' + modeKey + '">' +
                               modeLabel +
                               '<span class="sisme-tag__remove remove-mode" title="Retirer ce mode">&times;</span>' +
                               '<input type="hidden" name="game_modes[]" value="' + modeKey + '">' +
                               '</span>');
                
                selectedDisplay.append(modeTag);
            }

            // === GESTION DES ENTITÉS (DÉVELOPPEURS/ÉDITEURS) ===
            // Affichage du champ URL lors de la création
        
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-create-entity-btn', function(e) {
                e.preventDefault();
                
                var container = $(this).closest('.sisme-game-entities-component');
                var searchInput = container.find('.sisme-entity-search-input');
                var entityName = searchInput.val().trim();
                var urlField = container.find('.sisme-entity-url-field');
                
                if (entityName === '') {
                    alert('Veuillez saisir un nom d\'entité.');
                    return;
                }
                
                // Afficher le champ URL
                urlField.addClass('sisme-entity-url-visible').show();
                container.find('.sisme-entity-url-input').focus();
                
                // Changer le bouton
                $(this).text('✓ Confirmer').removeClass('sisme-btn--secondary').addClass('sisme-btn--primary');
                $(this).off('click').on('click', function(e) {
                    e.preventDefault();
                    createEntityWithUrl(container, entityName);
                });
            });

            // Sélection d'une entité depuis les suggestions
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-entity-suggestions-list .suggestion-item', function() {
                var entityId = $(this).data('entity-id');
                var entityName = $(this).data('entity-name');
                var container = $(this).closest('.sisme-game-entities-component');
                var selectedList = container.find('.sisme-selected-entities-display');
                
                // Vérifier si l'entité n'est pas déjà sélectionnée
                if (selectedList.find('[data-entity-id="' + entityId + '"]').length > 0) {
                    return; // Entité déjà sélectionnée
                }
                
                addEntityToSelection(container, entityId, entityName);
            });

            // Suppression d'une entité sélectionnée
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.remove-entity', function(e) {
                e.preventDefault();
                var entityElement = $(this).closest('.sisme-tag--entity');
                var container = entityElement.closest('.sisme-game-entities-component');
                
                // Supprimer l'élément
                entityElement.remove();
                
                // Mettre à jour le champ caché
                updateHiddenField(container);
                
                // Vérifier s'il reste des entités sélectionnées pour afficher le message par défaut
                var selectedDisplay = container.find('.sisme-selected-entities-display');
                if (selectedDisplay.find('.sisme-tag--entity').length === 0) {
                    var entityType = container.find('.sisme-form-label').first().text().toLowerCase();
                    var noSelectionMessage = 'Aucun ' + entityType.replace(':', '').trim() + ' sélectionné';
                    selectedDisplay.html('<span class="sisme-no-selection">' + noSelectionMessage + '</span>');
                }
            });

            // Recherche en temps réel dans les suggestions d'entités
            $('#<?php echo esc_js($this->module_id); ?>').on('keyup', '.sisme-entity-search-input', function() {
                var searchTerm = $(this).val().toLowerCase();
                var container = $(this).closest('.sisme-game-entities-component');
                var suggestions = container.find('.suggestion-item');
                
                suggestions.each(function() {
                    var entityName = $(this).data('entity-name').toLowerCase();
                    if (entityName.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Fonction pour créer une entité avec URL
            function createEntityWithUrl(container, entityName) {
                var urlInput = container.find('.sisme-entity-url-input');
                var entityUrl = urlInput.val().trim();
                var button = container.find('.sisme-create-entity-btn');
                
                button.prop('disabled', true).text('Création...');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'sisme_create_entity',
                        entity_name: entityName,
                        entity_website: entityUrl,
                        nonce: '<?php echo wp_create_nonce('sisme_create_entity'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            addEntityToSelection(container, response.data.term_id, response.data.name, entityUrl);
                            container.find('.sisme-entity-search-input').val('');
                            urlInput.val('');
                            container.find('.sisme-entity-url-field').removeClass('sisme-entity-url-visible').hide();
                            addEntityToSuggestions(container, response.data);
                        }
                    },
                    complete: function() {
                        button.prop('disabled', false).text('+ Créer').removeClass('sisme-btn--primary').addClass('sisme-btn--secondary');
                        // Remettre l'événement original
                        button.off('click').on('click', function(e) {
                            e.preventDefault();
                            var container = $(this).closest('.sisme-game-entities-component');
                            var searchInput = container.find('.sisme-entity-search-input');
                            var entityName = searchInput.val().trim();
                            var urlField = container.find('.sisme-entity-url-field');
                            
                            if (entityName === '') {
                                alert('Veuillez saisir un nom d\'entité.');
                                return;
                            }
                            
                            urlField.addClass('sisme-entity-url-visible').show();
                            container.find('.sisme-entity-url-input').focus();
                            $(this).text('✓ Confirmer').removeClass('sisme-btn--secondary').addClass('sisme-btn--primary');
                        });
                    }
                });
            }

            function updateHiddenField(container) {
                var hiddenInput = container.find('input[type="hidden"]');
                var selectedIds = [];
                
                container.find('.sisme-tag--entity').each(function() {
                    var entityId = $(this).data('entity-id');
                    if (entityId) {
                        selectedIds.push(entityId);
                    }
                });
                
                // Mettre à jour la valeur du champ caché
                if (selectedIds.length > 0) {
                    hiddenInput.val(JSON.stringify(selectedIds));
                } else {
                    hiddenInput.val('');
                }
            }

            // Fonction pour ajouter une entité à la sélection
            function addEntityToSelection(container, entityId, entityName, entityUrl) {
                var selectedDisplay = container.find('.sisme-selected-entities-display');
                
                // ✅ CORRECTION : Déterminer le nom du champ de manière plus robuste
                var fieldName = null;
                
                // Méthode 1 : Chercher un input existant
                var existingInput = selectedDisplay.find('input[type="hidden"]').first();
                if (existingInput.length > 0) {
                    fieldName = existingInput.attr('name');
                } else {
                    // Méthode 2 : Déterminer selon l'ID du composant ou le data-attribute
                    var componentDiv = container.closest('.sisme-game-entities-component');
                    
                    // Chercher un attribut data-component si il existe
                    var componentName = componentDiv.attr('data-component');
                    if (componentName) {
                        fieldName = componentName + '[]';
                    } else {
                        // Méthode 3 : Déterminer selon le label visible
                        var labelText = container.find('.sisme-form-label').text().toLowerCase();
                        if (labelText.includes('développeur')) {
                            fieldName = 'game_developers[]';
                        } else if (labelText.includes('éditeur')) {
                            fieldName = 'game_publishers[]';
                        } else {
                            console.error('ERREUR: Impossible de déterminer le type de champ');
                            return;
                        }
                    }
                }
                
                console.log('Champ détecté:', fieldName); // Pour debug
                
                // Supprimer le message "aucune entité sélectionnée"
                selectedDisplay.find('.sisme-no-selection').remove();
                
                // Icône site web si URL présente
                var websiteIcon = entityUrl ? 
                    '<span class="sisme-entity-website-icon" title="Site web disponible"></span>' : '';
                
                // Créer l'élément entité sélectionnée (même structure que le PHP)
                var entityElement = $('<span class="sisme-tag sisme-tag--selected sisme-tag--entity" data-entity-id="' + entityId + '">' +
                    entityName + websiteIcon +
                    '<span class="sisme-tag__remove remove-entity" title="Retirer cet élément">&times;</span>' +
                    '<input type="hidden" name="' + fieldName + '" value="' + entityId + '">' +
                    '</span>');
                
                selectedDisplay.append(entityElement);
                
                // Effacer le champ de recherche
                container.find('.sisme-entity-search-input').val('');
            }

            // Fonction pour ajouter une entité aux suggestions
            function addEntityToSuggestions(container, entityData) {
                var suggestionsList = container.find('.sisme-entity-suggestions-list');
                var websiteIcon = entityData.website ? '<span class="sisme-entity-website-icon" title="Site web disponible"></span>' : '';
                
                var suggestion = $('<div class="suggestion-item" ' +
                                  'data-entity-id="' + entityData.term_id + '" ' +
                                  'data-entity-name="' + entityData.name + '">' +
                                  '<div><strong>' + entityData.name + '</strong>' + websiteIcon + '</div>' +
                                  '<span>0 jeu(x)</span>' +
                                  '</div>');
                
                suggestionsList.prepend(suggestion);
            }


            // === GESTION DU NOM DU JEU (SÉLECTION UNIQUE) ===
            // Sélection d'un jeu depuis les suggestions
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-game-suggestions .suggestion-item', function() {
                var gameId = $(this).data('game-id');
                var gameName = $(this).data('game-name');
                var container = $(this).closest('.sisme-game-name-component');
                
                selectGame(container, gameId, gameName);
            });

            // Suppression du jeu sélectionné
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.remove-game', function(e) {
                e.preventDefault();
                var container = $(this).closest('.sisme-game-name-component');
                var selectedDisplay = container.find('.sisme-selected-game-display');
                
                selectedDisplay.html('<span class="no-game-selected" style="color: #666; font-style: italic;">Aucun jeu sélectionné</span>');
            });

            // Création d'un nouveau jeu
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-create-game-btn', function(e) {
                e.preventDefault();
                
                var container = $(this).closest('.sisme-game-name-component');
                var searchInput = container.find('.sisme-game-search-input');
                var gameName = searchInput.val().trim();
                
                if (!gameName) {
                    alert('Veuillez saisir un nom de jeu.');
                    return;
                }
                
                $(this).prop('disabled', true).text('Création...');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'sisme_create_tag',
                        tag_name: gameName,
                        nonce: '<?php echo wp_create_nonce('sisme_create_tag'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            selectGame(container, response.data.term_id, response.data.name);
                            searchInput.val('');
                            addGameToSuggestions(container, response.data);
                        }
                    },
                    complete: function() {
                        $(this).prop('disabled', false).text('+ Créer');
                    }.bind(this)
                });
            });

            // Recherche en temps réel dans les suggestions de jeux
            $('#<?php echo esc_js($this->module_id); ?>').on('keyup', '.sisme-game-search-input', function() {
                var searchTerm = $(this).val().toLowerCase();
                var container = $(this).closest('.sisme-game-name-component');
                var suggestions = container.find('.suggestion-item');
                
                suggestions.each(function() {
                    var gameName = $(this).data('game-name').toLowerCase();
                    if (gameName.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            });

            // Fonction pour sélectionner un jeu
            function selectGame(container, gameId, gameName) {
                var selectedDisplay = container.find('.sisme-selected-game-display');
                
                var gameTag = $('<span class="sisme-tag sisme-tag--selected sisme-tag--game" data-game-id="' + gameId + '">' +
                               gameName +
                               '<span class="sisme-tag__remove remove-game" title="Retirer ce jeu">&times;</span>' +
                               '<input type="hidden" name="game_name" value="' + gameId + '">' +
                               '</span>');
                
                selectedDisplay.html(gameTag);
            }

            // Fonction pour ajouter un jeu aux suggestions
            function addGameToSuggestions(container, gameData) {
                var suggestionsList = container.find('.sisme-suggestions-list');
                
                var suggestion = $('<div class="suggestion-item" ' +
                                  'data-game-id="' + gameData.term_id + '" ' +
                                  'data-game-name="' + gameData.name + '" ' +
                                  'style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">' +
                                  '<div><strong>' + gameData.name + '</strong></div>' +
                                  '<span style="color: #999; font-size: 11px;">0 article(s)</span>' +
                                  '</div>');
                
                suggestionsList.prepend(suggestion);
            }

            // === GESTION DES PLATEFORMES ===
            // Sélection d'une plateforme depuis les options disponibles
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-platform-option-card', function(e) {
                e.preventDefault();
                
                var platformKey = $(this).data('platform-key');
                var platformLabel = $(this).find('strong').text();
                var container = $(this).closest('.sisme-game-platforms-component');
                
                // Vérifier si déjà sélectionnée
                if (container.find('.sisme-tag--platform[data-platform-key="' + platformKey + '"]').length > 0) {
                    alert('Cette plateforme est déjà sélectionnée.');
                    return;
                }
                
                addPlatformToSelection(container, platformKey, platformLabel);
                
                // Effet visuel sur le bouton cliqué
                var category = $(this).data('category');
                var colors = {
                    'mobile': 'rgba(16, 185, 129, 0.1)',
                    'console': 'rgba(245, 158, 11, 0.1)',
                    'pc': 'rgba(59, 130, 246, 0.1)'
                };
                
                $(this).css('background-color', colors[category] || 'rgba(161, 183, 141, 0.1)');
                setTimeout(function() {
                    $(this).css('background-color', 'white');
                }.bind(this), 300);
            });

            // Suppression d'une plateforme sélectionnée
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.remove-platform', function(e) {
                e.preventDefault();
                $(this).closest('.sisme-tag--platform').remove();
                
                // Vérifier s'il reste des plateformes sélectionnées
                var container = $(this).closest('.sisme-game-platforms-component');
                var selectedDisplay = container.find('.sisme-selected-platforms-display');
                if (selectedDisplay.find('.sisme-tag--platform').length === 0) {
                    selectedDisplay.html('<span class="sisme-no-selection">Aucune plateforme sélectionnée</span>');
                }
            });

            // Fonction pour ajouter une plateforme à la sélection
            function addPlatformToSelection(container, platformKey, platformLabel) {
                var selectedDisplay = container.find('.sisme-selected-platforms-display');
                
                // Supprimer le message "aucune plateforme sélectionnée"
                selectedDisplay.find('.sisme-no-selection').remove();
                
                // Créer le tag de plateforme sélectionnée
                var platformTag = $('<span class="sisme-tag sisme-tag--selected sisme-tag--platform" data-platform-key="' + platformKey + '">' +
                                   platformLabel +
                                   '<span class="sisme-tag__remove remove-platform" title="Retirer cette plateforme">&times;</span>' +
                                   '<input type="hidden" name="game_platforms[]" value="' + platformKey + '">' +
                                   '</span>');
                
                selectedDisplay.append(platformTag);
            }

        });
        
        </script>
        <?php
    }
    
}