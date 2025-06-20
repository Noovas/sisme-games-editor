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
        'cover_main' => [
            'label' => 'Cover principale',
            'description' => '',
            'required' => false,
            'output_var' => 'cover_main'
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
        'external_links' => [
            'label' => 'Liens de vente',
            'description' => '',
            'required' => false,
            'output_var' => 'external_links'
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
                
                // CORRECTION : Décoder d'abord les entités HTML pour éviter le double échappement
                $value = wp_specialchars_decode($value, ENT_QUOTES);
                
                // Puis appliquer wp_kses pour nettoyer les balises non autorisées
                return wp_kses($value, $allowed_html);

            case 'cover_main':
            case 'cover_news':
            case 'cover_patch':
            case 'cover_test':
                return intval($value);

            case 'game_platforms':
                return is_array($value) ? array_map('sanitize_text_field', $value) : [];
                
            case 'release_date':
                return sanitize_text_field($value);
                
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
            <th scope="row">
                <label for="<?php echo esc_attr($field_id); ?>" class="sisme-entity-label">
                    <?php echo esc_html($component['label'] . $required_label); ?>
                </label>
            </th>
            <td class="sisme-entity-component-cell">
                <div class="sisme-entity-component sisme-entity-<?php echo esc_attr($component['entity_type']); ?>">
                    
                    <!-- Liste des entités sélectionnées -->
                    <div class="sisme-selected-entities">
                        <label class="sisme-selected-entities-label"><?php echo esc_html($component['label']); ?> sélectionnés :</label>
                        <div class="sisme-selected-entities-list" id="<?php echo esc_attr($field_id . '_selected'); ?>">
                            <?php if (empty($value)): ?>
                                <span class="sisme-no-entities-selected">Aucun <?php echo esc_html(strtolower($component['label'])); ?> sélectionné</span>
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
                        <label class="sisme-entity-suggestions-label"><?php echo esc_html($component['label']); ?> disponibles :</label>
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
     */
    private function render_game_publishers_component() {
        $this->render_modern_entity_component('game_publishers');
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
        $entity_type = $component['entity_type'] === 'developer' ? 'développeur' : 'éditeur';
        $entity_type_plural = $component['entity_type'] === 'developer' ? 'développeurs' : 'éditeurs';
        ?>
        <tr>
            <th scope="row">
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($component['label'] . $required_label); ?>
                </label>
            </th>
            <td>
                <div class="sisme-game-entities-component">
                    
                    <!-- Liste des entités sélectionnées -->
                    <div class="sisme-selected-entities" style="margin-bottom: 15px;">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;"><?php echo ucfirst($entity_type_plural); ?> sélectionné(s) :</label>
                        <div class="sisme-selected-entities-list" id="<?php echo esc_attr($field_id . '_selected'); ?>" 
                             style="min-height: 40px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                            <?php if (empty($value)): ?>
                                <span class="no-entities-selected" style="color: #666; font-style: italic;">Aucun <?php echo $entity_type; ?> sélectionné</span>
                            <?php else: ?>
                                <?php foreach ($value as $entity_id): ?>
                                    <?php $entity = get_category($entity_id); ?>
                                    <?php if ($entity): ?>
                                        <?php $entity_website = get_term_meta($entity_id, 'entity_website', true); ?>
                                        <span class="selected-entity-tag" data-entity-id="<?php echo esc_attr($entity_id); ?>" 
                                              style="display: inline-block; background: var(--theme-palette-color-2, #007cba); color: white; padding: 4px 8px; margin: 2px; border-radius: 3px; font-size: 12px;">
                                            <?php echo esc_html($entity->name); ?>
                                            <?php if (!empty($entity_website)): ?>
                                                <span style="margin-left: 3px;">🌐</span>
                                            <?php endif; ?>
                                            <span class="remove-entity" style="margin-left: 5px; cursor: pointer; font-weight: bold;">&times;</span>
                                            <input type="hidden" name="<?php echo esc_attr($component['output_var']); ?>[]" value="<?php echo esc_attr($entity_id); ?>">
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Champ de recherche/création -->
                    <div class="sisme-entity-search" style="margin-bottom: 15px;">
                        <input type="text" 
                               id="<?php echo esc_attr($field_id . '_search'); ?>" 
                               class="sisme-entity-search-input regular-text" 
                               placeholder="Rechercher ou créer un <?php echo $entity_type; ?>..."
                               style="width: 70%; margin-right: 10px;">
                        <button type="button" 
                                class="button button-secondary sisme-create-entity-btn" 
                                data-entity-type="<?php echo esc_attr($component['entity_type']); ?>"
                                data-component="<?php echo esc_attr($component_name); ?>">
                            + Créer
                        </button>
                    </div>
                    
                    <!-- Champ URL pour nouveau développeur/éditeur -->
                    <div class="sisme-entity-url" style="margin-bottom: 15px; display: none;">
                        <input type="url" 
                               id="<?php echo esc_attr($field_id . '_url'); ?>" 
                               class="sisme-entity-url-input regular-text" 
                               placeholder="Site web (optionnel)"
                               style="width: 70%; margin-right: 10px;">
                        <button type="button" class="button button-primary sisme-confirm-create-entity-btn">
                            Confirmer création
                        </button>
                        <button type="button" class="button sisme-cancel-create-entity-btn">
                            Annuler
                        </button>
                    </div>
                    
                    <!-- Suggestions d'entités existantes -->
                    <div class="sisme-entity-suggestions" style="margin-bottom: 15px;">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;"><?php echo ucfirst($entity_type_plural); ?> disponibles :</label>
                        <div class="sisme-suggestions-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; background: white;">
                            <?php foreach ($entities as $entity): ?>
                                <?php 
                                $entity_website = get_term_meta($entity->term_id, 'entity_website', true);
                                // CORRECTION : Compter les jeux réels via term_meta
                                $games_count = $this->count_games_for_entity($entity->term_id);
                                ?>
                                <div class="suggestion-item" 
                                     data-entity-id="<?php echo esc_attr($entity->term_id); ?>" 
                                     data-entity-name="<?php echo esc_attr($entity->name); ?>"
                                     style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <strong><?php echo esc_html($entity->name); ?></strong>
                                        <?php if (!empty($entity_website)): ?>
                                            <span style="color: #666; font-size: 11px; margin-left: 5px;">🌐 Site web</span>
                                        <?php endif; ?>
                                    </div>
                                    <span style="color: #999; font-size: 11px;"><?php echo $games_count; ?> jeu(x)</span>
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
            <th scope="row">
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($component['label'] . $required_label); ?>
                </label>
            </th>
            <td>
                <div class="sisme-game-genres-component">
                    
                    <!-- Genres sélectionnés -->
                    <div class="sisme-selected-genres">
                        <label class="sisme-form-label">Genres sélectionnés :</label>
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
                        <label class="sisme-form-label">Genres disponibles :</label>
                        <div class="sisme-genre-suggestions-list sisme-suggestions-container-base">
                            <?php foreach ($genres as $genre): ?>
                                <div class="suggestion-item" 
                                     data-genre-id="<?php echo esc_attr($genre->term_id); ?>" 
                                     data-genre-name="<?php echo esc_attr($genre->name); ?>">
                                    <div>
                                        <strong><?php echo esc_html(str_replace('jeux-', '', $genre->name)); ?></strong>
                                    </div>
                                    <span><?php echo $genre->count; ?> jeu(x)</span>
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
            <th scope="row">
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($component['label'] . $required_label); ?>
                </label>
            </th>
            <td>
                <div class="sisme-game-modes-component">
                    
                    <!-- Liste des modes sélectionnés - EN PREMIER -->
                    <div class="sisme-selected-modes" style="margin-bottom: 15px;">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">Modes sélectionnés :</label>
                        <div class="sisme-selected-modes-list" id="<?php echo esc_attr($field_id . '_selected'); ?>" 
                             style="min-height: 40px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
                            <?php if (empty($value)): ?>
                                <span class="no-modes-selected" style="color: #666; font-style: italic;">Aucun mode sélectionné</span>
                            <?php else: ?>
                                <?php foreach ($value as $mode_key): ?>
                                    <?php if (isset($available_modes[$mode_key])): ?>
                                        <span class="selected-mode-tag" data-mode-key="<?php echo esc_attr($mode_key); ?>" 
                                              style="display: inline-block; background: var(--theme-palette-color-7); color: white; padding: 4px 8px; margin: 2px; border-radius: 3px; font-size: 12px;">
                                            <?php echo esc_html($available_modes[$mode_key]); ?>
                                            <span class="remove-mode" style="margin-left: 5px; cursor: pointer; font-weight: bold;">&times;</span>
                                            <input type="hidden" name="game_modes[]" value="<?php echo esc_attr($mode_key); ?>">
                                        </span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Modes disponibles pour sélection -->
                    <div class="sisme-mode-options">
                        <label style="font-weight: 600; margin-bottom: 8px; display: block;">Modes disponibles :</label>
                        <div class="sisme-modes-list" style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; border: 1px solid #ddd; border-radius: 4px; padding: 12px;">
                            <?php foreach ($available_modes as $mode_key => $mode_label): ?>
                                <div class="mode-option" data-mode-key="<?php echo esc_attr($mode_key); ?>" 
                                     style="padding: 8px 12px; background: #fff; border-radius: 3px; cursor: pointer; border: 1px solid #e0e0e0; text-align: center; transition: all 0.2s;">
                                    <strong><?php echo esc_html($mode_label); ?></strong>
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
            <th scope="row">
                <label><?php echo esc_html($component['label'] . $required_label); ?></label>
            </th>
            <td>
                <div class="sisme-platforms-selector">
                    <?php foreach ($platforms as $category => $category_platforms): ?>
                        <div class="platform-category">
                            <h4><?php echo esc_html($category); ?></h4>
                            <?php foreach ($category_platforms as $platform_key => $platform_name): ?>
                                <label class="platform-option">
                                    <input type="checkbox" 
                                           name="game_platforms[]" 
                                           value="<?php echo esc_attr($platform_key); ?>"
                                           <?php checked(in_array($platform_key, $value)); ?>>
                                    <?php echo esc_html($platform_name); ?>
                                </label>
                            <?php endforeach; ?>
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
            <th scope="row">
                <label for="<?php echo esc_attr($field_id); ?>">
                    <?php echo esc_html($component['label'] . $required_label); ?>
                </label>
            </th>
            <td>
                <input type="date" 
                       id="<?php echo esc_attr($field_id); ?>" 
                       name="release_date" 
                       value="<?php echo esc_attr($value); ?>"
                       class="regular-text"
                       <?php echo $required_attr; ?>>
                <p class="description"><?php echo esc_html($component['description']); ?></p>
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
            <th scope="row">
                <label><?php echo esc_html($component['label'] . $required_label); ?></label>
            </th>
            <td>
                <div class="sisme-external-links">
                    <?php foreach ($platforms as $platform_key => $platform_name): ?>
                        <div class="link-field">
                            <label for="<?php echo esc_attr($this->module_id . '_' . $platform_key); ?>">
                                <?php echo esc_html($platform_name); ?>
                            </label>
                            <input type="url" 
                                   id="<?php echo esc_attr($this->module_id . '_' . $platform_key); ?>" 
                                   name="external_links[<?php echo esc_attr($platform_key); ?>]" 
                                   value="<?php echo esc_attr($value[$platform_key] ?? ''); ?>"
                                   placeholder="https://..."
                                   class="regular-text">
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="description"><?php echo esc_html($component['description']); ?></p>
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
            <th scope="row" class="sisme-form-label-cell">
                <label for="<?php echo esc_attr($field_id); ?>" class="sisme-form-label sisme-form-label--required">
                    <?php echo esc_html($component['label']); ?> *
                </label>
            </th>
            <td class="sisme-form-field-cell">
                <div class="sisme-game-name-component">
                    
                    <!-- Jeu sélectionné -->
                    <div class="sisme-selected-game">
                        <label class="sisme-form-label">Jeu sélectionné :</label>
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
                        <label class="sisme-form-label">Jeux disponibles :</label>
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

            case 'cover_main':
            case 'cover_news':
            case 'cover_patch':
            case 'cover_test':
                $this->render_cover_component($component_name);
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
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.mode-option', function(e) {
                e.preventDefault();
                
                var modeKey = $(this).data('mode-key');
                var modeLabel = $(this).find('strong').text();
                var container = $(this).closest('.sisme-game-modes-component');
                
                // Vérifier si déjà sélectionné
                if (container.find('.selected-mode-tag[data-mode-key="' + modeKey + '"]').length > 0) {
                    alert('Ce mode est déjà sélectionné.');
                    return;
                }
                
                addModeToSelection(container, modeKey, modeLabel);
                
                // Effet visuel sur le bouton cliqué
                $(this).css('background-color', '#e7f3e7');
                setTimeout(function() {
                    $(this).css('background-color', '#fff');
                }.bind(this), 300);
            });

            // Suppression d'un mode sélectionné
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.remove-mode', function(e) {
                e.preventDefault();
                $(this).closest('.selected-mode-tag').remove();
                
                // Vérifier s'il reste des modes sélectionnés
                var container = $(this).closest('.sisme-game-modes-component');
                var selectedList = container.find('.sisme-selected-modes-list');
                if (selectedList.find('.selected-mode-tag').length === 0) {
                    selectedList.html('<span class="no-modes-selected" style="color: #666; font-style: italic;">Aucun mode sélectionné</span>');
                }
            });

            // Fonction pour ajouter un mode à la sélection
            function addModeToSelection(container, modeKey, modeLabel) {
                var selectedList = container.find('.sisme-selected-modes-list');
                
                // Supprimer le message "aucun mode sélectionné"
                selectedList.find('.no-modes-selected').remove();
                
                // Créer le tag de mode sélectionné
                var modeTag = $('<span class="selected-mode-tag" data-mode-key="' + modeKey + '" ' +
                               'style="display: inline-block; background: var(--theme-palette-color-7); color: white; padding: 4px 8px; margin: 2px; border-radius: 3px; font-size: 12px;">' +
                               modeLabel +
                               '<span class="remove-mode" style="margin-left: 5px; cursor: pointer; font-weight: bold;">&times;</span>' +
                               '<input type="hidden" name="game_modes[]" value="' + modeKey + '">' +
                               '</span>');
                
                selectedList.append(modeTag);
            }

            // === GESTION DES ENTITÉS (DÉVELOPPEURS/ÉDITEURS) ===
            // Affichage du champ URL lors de la création
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-create-entity-btn', function(e) {
                e.preventDefault();
                
                var container = $(this).closest('.sisme-game-entities-component');
                var searchInput = container.find('.sisme-entity-search-input');
                var entityName = searchInput.val().trim();
                
                if (entityName === '') {
                    alert('Veuillez saisir un nom d\'entité.');
                    return;
                }
                
                // Vérifier si l'entité existe déjà
                var existingEntity = container.find('.suggestion-item').filter(function() {
                    return $(this).data('entity-name').toLowerCase() === entityName.toLowerCase();
                });
                
                if (existingEntity.length > 0) {
                    // L'entité existe déjà, la sélectionner directement
                    var entityId = existingEntity.data('entity-id');
                    var entityDisplayName = existingEntity.data('entity-name');
                    addEntityToSelection(container, entityId, entityDisplayName, $(this).data('component'));
                    searchInput.val('');
                    return;
                }
                
                // Entité nouvelle, afficher le champ URL
                container.find('.sisme-entity-search').hide();
                container.find('.sisme-entity-url').show();
                container.find('.sisme-entity-url-input').focus();
                
                // Stocker le nom pour la création
                container.data('pending-entity-name', entityName);
                container.data('pending-entity-type', $(this).data('entity-type'));
                container.data('pending-component', $(this).data('component'));
            });

            // Annulation de la création
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-cancel-create-entity-btn', function(e) {
                e.preventDefault();
                
                var container = $(this).closest('.sisme-game-entities-component');
                container.find('.sisme-entity-url').hide();
                container.find('.sisme-entity-search').show();
                container.find('.sisme-entity-search-input').val('').focus();
            });

            // Confirmation de la création avec AJAX
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.sisme-confirm-create-entity-btn', function(e) {
                e.preventDefault();
                
                var button = $(this);
                var container = button.closest('.sisme-game-entities-component');
                var entityName = container.data('pending-entity-name');
                var entityType = container.data('pending-entity-type');
                var component = container.data('pending-component');
                var entityUrl = container.find('.sisme-entity-url-input').val().trim();
                
                if (!entityName) {
                    alert('Erreur: nom d\'entité manquant.');
                    return;
                }
                
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
                            // Ajouter l'entité à la sélection
                            addEntityToSelection(container, response.data.term_id, response.data.name, component);
                            
                            // Ajouter à la liste des suggestions si c'est une nouvelle entité
                            if (!response.data.existed) {
                                addEntityToSuggestions(container, response.data);
                            }
                            
                            // Réinitialiser l'interface
                            container.find('.sisme-entity-url').hide();
                            container.find('.sisme-entity-search').show();
                            container.find('.sisme-entity-search-input').val('');
                            container.find('.sisme-entity-url-input').val('');
                            
                            button.text(response.data.existed ? 'Existait déjà !' : 'Créé !');
                            setTimeout(function() {
                                button.text('Confirmer création').prop('disabled', false);
                            }, 1500);
                        } else {
                            alert('Erreur: ' + (response.data || 'Problème inconnu'));
                            button.text('Confirmer création').prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Erreur AJAX');
                        button.text('Confirmer création').prop('disabled', false);
                    }
                });
            });

            // Sélection d'une entité depuis les suggestions
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.suggestion-item', function(e) {
                e.preventDefault();
                
                var entityId = $(this).data('entity-id');
                var entityName = $(this).data('entity-name');
                var container = $(this).closest('.sisme-game-entities-component');
                var component = container.closest('tr').find('.sisme-create-entity-btn').data('component');
                
                // Vérifier si déjà sélectionné
                if (container.find('.selected-entity-tag[data-entity-id="' + entityId + '"]').length > 0) {
                    alert('Cette entité est déjà sélectionnée.');
                    return;
                }
                
                addEntityToSelection(container, entityId, entityName, component);
            });

            // Suppression d'une entité sélectionnée
            $('#<?php echo esc_js($this->module_id); ?>').on('click', '.remove-entity', function(e) {
                e.preventDefault();
                $(this).closest('.selected-entity-tag').remove();
                
                // Vérifier s'il reste des entités sélectionnées
                var container = $(this).closest('.sisme-game-entities-component');
                var selectedList = container.find('.sisme-selected-entities-list');
                if (selectedList.find('.selected-entity-tag').length === 0) {
                    var entityType = container.closest('tr').find('.sisme-create-entity-btn').data('entity-type');
                    var entityLabel = entityType === 'developer' ? 'développeur' : 'éditeur';
                    selectedList.html('<span class="no-entities-selected" style="color: #666; font-style: italic;">Aucun ' + entityLabel + ' sélectionné</span>');
                }
            });

            // Recherche en temps réel dans les suggestions
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

            // Fonctions utilitaires pour les entités
            function addEntityToSelection(container, entityId, entityName, component) {
                var selectedList = container.find('.sisme-selected-entities-list');
                var outputVar = component; // 'game_developers' ou 'game_publishers'
                
                // Supprimer le message "aucune entité sélectionnée"
                selectedList.find('.no-entities-selected').remove();
                
                // Vérifier si l'entité a un site web (sera mis à jour par un futur refresh si nécessaire)
                var websiteIcon = ''; // Sera géré côté serveur lors du prochain chargement
                
                // Créer le tag d'entité sélectionnée
                var entityTag = $('<span class="selected-entity-tag" data-entity-id="' + entityId + '" ' +
                                 'style="display: inline-block; background: var(--theme-palette-color-2, #007cba); color: white; padding: 4px 8px; margin: 2px; border-radius: 3px; font-size: 12px;">' +
                                 entityName + websiteIcon +
                                 '<span class="remove-entity" style="margin-left: 5px; cursor: pointer; font-weight: bold;">&times;</span>' +
                                 '<input type="hidden" name="' + outputVar + '[]" value="' + entityId + '">' +
                                 '</span>');
                
                selectedList.append(entityTag);
            }

            function addEntityToSuggestions(container, entityData) {
                var suggestionsList = container.find('.sisme-suggestions-list');
                var websiteIcon = entityData.website ? '<span style="color: #666; font-size: 11px; margin-left: 5px;">🌐 Site web</span>' : '';
                
                var suggestion = $('<div class="suggestion-item" ' +
                                  'data-entity-id="' + entityData.term_id + '" ' +
                                  'data-entity-name="' + entityData.name + '" ' +
                                  'style="padding: 8px 12px; border-bottom: 1px solid #f0f0f0; cursor: pointer; display: flex; justify-content: space-between; align-items: center;">' +
                                  '<div><strong>' + entityData.name + '</strong>' + websiteIcon + '</div>' +
                                  '<span style="color: #999; font-size: 11px;">0 jeu(x)</span>' +
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

        });
        
        </script>
        <?php
    }
    
}