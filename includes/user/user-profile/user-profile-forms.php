<?php
/**
 * File: /sisme-games-editor/includes/user/user-profile/user-profile-forms.php
 * Générateur de formulaires pour la gestion de profil utilisateur
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Profile_Forms {
    
    private $components = [];
    private $available_components = [];
    private $form_data = [];
    private $form_type = 'profile';
    private $user_id;
    private $module_id = '';
    private static $instance_counter = 0;
    
    /**
     * Constructeur
     * @param array $components Liste des composants à utiliser
     * @param array $options Options du formulaire
     */
    public function __construct($components = [], $options = []) {
        $this->user_id = get_current_user_id();
        
        self::$instance_counter++;
        $this->module_id = 'user-profile-form-' . self::$instance_counter;
        
        $this->init_available_components();
        $this->load_game_data();
        $this->set_components($components);
        $this->load_user_data();
        $this->process_options($options);
    }
    
    /**
     * Initialiser les composants disponibles
     * @return void
     */
    private function init_available_components() {
        $this->available_components = [
            'user_display_name' => [
                'type' => 'text',
                'label' => 'Nom d\'affichage',
                'placeholder' => 'Votre nom public',
                'required' => true,
                'output_var' => 'user_display_name',
                'icon' => '👤',
                'section' => 'basic'
            ],
            'user_bio' => [
                'type' => 'textarea',
                'label' => 'Biographie',
                'placeholder' => 'Parlez-nous de vous...',
                'required' => false,
                'output_var' => 'user_bio',
                'rows' => 4,
                'maxlength' => 500,
                'icon' => '📝',
                'section' => 'basic'
            ],
            'user_website' => [
                'type' => 'url',
                'label' => 'Site web',
                'placeholder' => 'https://votre-site.com',
                'required' => false,
                'output_var' => 'user_website',
                'icon' => '🌐',
                'section' => 'basic'
            ],
            'user_location' => [
                'type' => 'text',
                'label' => 'Localisation',
                'placeholder' => 'Votre ville, pays',
                'required' => false,
                'output_var' => 'user_location',
                'icon' => '📍',
                'section' => 'basic'
            ],
            'platform_preference' => [
                'type' => 'select',
                'label' => 'Plateforme préférée',
                'required' => false,
                'output_var' => 'platform_preference',
                'icon' => '🎮',
                'section' => 'gaming',
                'options' => [
                    '' => 'Sélectionnez...',
                    'pc' => 'PC',
                    'playstation' => 'PlayStation',
                    'xbox' => 'Xbox',
                    'nintendo' => 'Nintendo',
                    'mobile' => 'Mobile',
                    'multiple' => 'Plusieurs plateformes'
                ]
            ],
            'favorite_game_genres' => [
                'type' => 'checkbox_group',
                'label' => 'Genres préférés',
                'required' => false,
                'output_var' => 'favorite_game_genres',
                'icon' => '🎯',
                'section' => 'gaming',
                'options' => []
            ],
            'skill_level' => [
                'type' => 'select',
                'label' => 'Niveau de jeu',
                'required' => false,
                'output_var' => 'skill_level',
                'icon' => '⭐',
                'section' => 'gaming',
                'options' => [
                    '' => 'Sélectionnez...',
                    'beginner' => 'Débutant',
                    'casual' => 'Casual',
                    'experienced' => 'Expérimenté',
                    'hardcore' => 'Hardcore',
                    'professional' => 'Professionnel'
                ]
            ],
            'favorite_games' => [
                'type' => 'select_multiple',
                'label' => 'Jeux favoris',
                'required' => false,
                'output_var' => 'favorite_games',
                'icon' => '🎲',
                'section' => 'gaming',
                'options' => []
            ],
            'privacy_profile_public' => [
                'type' => 'checkbox',
                'label' => 'Profil public',
                'description' => 'Autoriser les autres utilisateurs à voir votre profil',
                'required' => false,
                'output_var' => 'privacy_profile_public',
                'icon' => '👁️',
                'section' => 'privacy'
            ],
            'privacy_show_stats' => [
                'type' => 'checkbox',
                'label' => 'Afficher les statistiques',
                'description' => 'Partager vos statistiques et achievements',
                'required' => false,
                'output_var' => 'privacy_show_stats',
                'icon' => '📊',
                'section' => 'privacy'
            ],
            'privacy_allow_friend_requests' => [
                'type' => 'checkbox',
                'label' => 'Demandes d\'amis',
                'description' => 'Autoriser les demandes d\'amitié',
                'required' => false,
                'output_var' => 'privacy_allow_friend_requests',
                'icon' => '👥',
                'section' => 'privacy'
            ]
        ];
    }
    
    /**
     * Charger les données de jeux depuis les taxonomies
     * @return void
     */
    private function load_game_data() {
        $genres = get_terms([
            'taxonomy' => 'game_genre',
            'hide_empty' => false,
            'orderby' => 'name'
        ]);
        
        if (!is_wp_error($genres)) {
            $genre_options = [];
            foreach ($genres as $genre) {
                $genre_options[$genre->term_id] = $genre->name;
            }
            $this->available_components['favorite_game_genres']['options'] = $genre_options;
        }
        
        $games = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'meta_query' => [
                [
                    'key' => 'game_name',
                    'compare' => 'EXISTS'
                ]
            ],
            'orderby' => 'name',
            'number' => 100
        ]);
        
        if (!is_wp_error($games)) {
            $game_options = [];
            foreach ($games as $game) {
                $game_options[$game->term_id] = $game->name;
            }
            $this->available_components['favorite_games']['options'] = $game_options;
        }
    }
    
    /**
     * Définir les composants à utiliser
     * @param array $components Liste des composants
     * @return void
     */
    private function set_components($components) {
        if (empty($components)) {
            $this->components = $this->available_components;
        } else {
            foreach ($components as $component_name) {
                if (isset($this->available_components[$component_name])) {
                    $this->components[$component_name] = $this->available_components[$component_name];
                }
            }
        }
    }
    
    /**
     * Charger les données utilisateur existantes
     * @return void
     */
    private function load_user_data() {
        if (!$this->user_id) {
            return;
        }
        
        $user = get_userdata($this->user_id);
        if (!$user) {
            return;
        }
        
        $this->form_data['user_display_name'] = $user->display_name;
        $this->form_data['user_website'] = $user->user_url;
        
        $meta_fields = [
            'user_bio' => 'sisme_user_bio',
            'user_location' => 'sisme_user_location',
            'platform_preference' => 'sisme_user_platform_preference',
            'favorite_game_genres' => 'sisme_user_favorite_game_genres',
            'skill_level' => 'sisme_user_skill_level',
            'favorite_games' => 'sisme_user_favorite_games',
            'privacy_profile_public' => 'sisme_user_privacy_profile_public',
            'privacy_show_stats' => 'sisme_user_privacy_show_stats',
            'privacy_allow_friend_requests' => 'sisme_user_privacy_allow_friend_requests'
        ];
        
        foreach ($meta_fields as $form_key => $meta_key) {
            $value = get_user_meta($this->user_id, $meta_key, true);
            if ($value !== '') {
                $this->form_data[$form_key] = $value;
            }
        }
    }
    
    /**
     * Traiter les options du formulaire
     * @param array $options Options
     * @return void
     */
    private function process_options($options) {
        $this->form_type = $options['type'] ?? 'profile';
    }
    
    /**
     * Rendu du formulaire complet
     * @return string HTML du formulaire
     */
    public function render() {
        if (!$this->user_id) {
            return '<div class="sisme-profile-error">Vous devez être connecté pour modifier votre profil.</div>';
        }
        
        $message = Sisme_User_Profile_Handlers::get_profile_message();
        
        $output = '<div class="sisme-profile-form-wrapper" id="' . esc_attr($this->module_id) . '">';
        
        if ($message) {
            $output .= '<div class="sisme-profile-message sisme-profile-message--' . esc_attr($message['type']) . '">';
            $output .= esc_html($message['message']);
            $output .= '</div>';
        }
        
        $output .= '<form class="sisme-profile-form" method="post" enctype="multipart/form-data">';
        $output .= wp_nonce_field('sisme_user_profile_update', '_wpnonce', true, false);
        $output .= '<input type="hidden" name="sisme_user_profile_submit" value="1">';
        
        $output .= $this->render_sections();
        
        $output .= '<div class="sisme-profile-form-submit">';
        $output .= '<button type="submit" class="sisme-button sisme-button-vert sisme-profile-submit-btn">';
        $output .= '<span class="sisme-btn-icon">💾</span> Mettre à jour le profil';
        $output .= '</button>';
        $output .= '</div>';
        
        $output .= '</form>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Rendu des sections du formulaire
     * @return string HTML des sections
     */
    private function render_sections() {
        $sections = [
            'basic' => [
                'title' => 'Informations de base',
                'icon' => '📋'
            ],
            'gaming' => [
                'title' => 'Préférences',
                'icon' => '🎮'
            ],
            'privacy' => [
                'title' => 'Confidentialité',
                'icon' => '🔒'
            ]
        ];
        
        $output = '';
        
        foreach ($sections as $section_key => $section_info) {
            $section_components = array_filter($this->components, function($component) use ($section_key) {
                return ($component['section'] ?? '') === $section_key;
            });
            
            if (empty($section_components)) {
                continue;
            }
            
            $output .= '<div class="sisme-profile-section sisme-profile-section--' . esc_attr($section_key) . '">';
            $output .= '<h3 class="sisme-profile-section-title">';
            $output .= '<span class="sisme-section-icon">' . $section_info['icon'] . '</span>';
            $output .= esc_html($section_info['title']);
            $output .= '</h3>';
            
            foreach ($section_components as $component_name => $component) {
                $output .= $this->render_component($component_name, $component);
            }
            
            $output .= '</div>';
        }
        
        return $output;
    }
    
    /**
     * Rendu d'un composant individuel
     * @param string $component_name Nom du composant
     * @param array $component Configuration du composant
     * @return string HTML du composant
     */
    private function render_component($component_name, $component) {
        $value = $this->form_data[$component['output_var']] ?? '';
        $field_id = $this->module_id . '_' . $component_name;
        
        $output = '<div class="sisme-profile-field sisme-profile-field--' . esc_attr($component['type']) . '">';
        
        $output .= '<label for="' . esc_attr($field_id) . '" class="sisme-profile-label">';
        $output .= '<span class="sisme-field-icon">' . $component['icon'] . '</span>';
        $output .= esc_html($component['label']);
        if (!empty($component['required'])) {
            $output .= ' <span class="sisme-required">*</span>';
        }
        $output .= '</label>';
        
        $output .= $this->render_field_input($component, $field_id, $value);
        
        if (!empty($component['description'])) {
            $output .= '<div class="sisme-profile-field-description">' . esc_html($component['description']) . '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Rendu du champ de saisie selon le type
     * @param array $component Configuration du composant
     * @param string $field_id ID du champ
     * @param mixed $value Valeur actuelle
     * @return string HTML du champ
     */
    private function render_field_input($component, $field_id, $value) {
        $name = $component['output_var'];
        $required = !empty($component['required']) ? 'required' : '';
        
        switch ($component['type']) {
            case 'text':
            case 'email':
            case 'url':
                return $this->render_input_field($component, $field_id, $value, $required);
                
            case 'textarea':
                return $this->render_textarea_field($component, $field_id, $value, $required);
                
            case 'select':
                return $this->render_select_field($component, $field_id, $value, $required);
                
            case 'select_multiple':
                return $this->render_select_multiple_field($component, $field_id, $value, $required);
                
            case 'checkbox':
                return $this->render_checkbox_field($component, $field_id, $value);
                
            case 'checkbox_group':
                return $this->render_checkbox_group_field($component, $field_id, $value);
        }
        
        return '';
    }
    
    /**
     * Rendu champ input
     * @param array $component Configuration
     * @param string $field_id ID du champ
     * @param mixed $value Valeur
     * @param string $required Attribut required
     * @return string HTML
     */
    private function render_input_field($component, $field_id, $value, $required) {
        $placeholder = !empty($component['placeholder']) ? 'placeholder="' . esc_attr($component['placeholder']) . '"' : '';
        
        return '<input type="' . esc_attr($component['type']) . '" ' .
               'id="' . esc_attr($field_id) . '" ' .
               'name="' . esc_attr($component['output_var']) . '" ' .
               'value="' . esc_attr($value) . '" ' .
               'class="sisme-profile-input" ' .
               $placeholder . ' ' .
               $required . '>';
    }
    
    /**
     * Rendu champ textarea
     * @param array $component Configuration
     * @param string $field_id ID du champ
     * @param mixed $value Valeur
     * @param string $required Attribut required
     * @return string HTML
     */
    private function render_textarea_field($component, $field_id, $value, $required) {
        $rows = $component['rows'] ?? 3;
        $maxlength = !empty($component['maxlength']) ? 'maxlength="' . intval($component['maxlength']) . '"' : '';
        $placeholder = !empty($component['placeholder']) ? 'placeholder="' . esc_attr($component['placeholder']) . '"' : '';
        
        return '<textarea id="' . esc_attr($field_id) . '" ' .
               'name="' . esc_attr($component['output_var']) . '" ' .
               'class="sisme-profile-textarea" ' .
               'rows="' . intval($rows) . '" ' .
               $placeholder . ' ' .
               $maxlength . ' ' .
               $required . '>' . esc_textarea($value) . '</textarea>';
    }
    
    /**
     * Rendu champ select
     * @param array $component Configuration
     * @param string $field_id ID du champ
     * @param mixed $value Valeur
     * @param string $required Attribut required
     * @return string HTML
     */
    private function render_select_field($component, $field_id, $value, $required) {
        $output = '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($component['output_var']) . '" class="sisme-profile-select" ' . $required . '>';
        
        foreach ($component['options'] as $option_value => $option_label) {
            $selected = ($value == $option_value) ? ' selected' : '';
            $output .= '<option value="' . esc_attr($option_value) . '"' . $selected . '>' . esc_html($option_label) . '</option>';
        }
        
        $output .= '</select>';
        return $output;
    }
    
    /**
     * Rendu champ select multiple
     * @param array $component Configuration
     * @param string $field_id ID du champ
     * @param mixed $value Valeur
     * @param string $required Attribut required
     * @return string HTML
     */
    private function render_select_multiple_field($component, $field_id, $value, $required) {
        $selected_values = is_array($value) ? $value : [];
        
        $output = '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($component['output_var']) . '[]" class="sisme-profile-select-multiple" multiple ' . $required . '>';
        
        foreach ($component['options'] as $option_value => $option_label) {
            $selected = in_array($option_value, $selected_values) ? ' selected' : '';
            $output .= '<option value="' . esc_attr($option_value) . '"' . $selected . '>' . esc_html($option_label) . '</option>';
        }
        
        $output .= '</select>';
        return $output;
    }
    
    /**
     * Rendu checkbox simple
     * @param array $component Configuration
     * @param string $field_id ID du champ
     * @param mixed $value Valeur
     * @return string HTML
     */
    private function render_checkbox_field($component, $field_id, $value) {
        $checked = !empty($value) ? ' checked' : '';
        
        $output = '<div class="sisme-profile-checkbox-wrapper">';
        $output .= '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr($component['output_var']) . '" value="1" class="sisme-profile-checkbox"' . $checked . '>';
        $output .= '<label for="' . esc_attr($field_id) . '" class="sisme-profile-checkbox-label">' . esc_html($component['label']) . '</label>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Rendu groupe de checkboxes
     * @param array $component Configuration
     * @param string $field_id ID du champ
     * @param mixed $value Valeur
     * @return string HTML
     */
    private function render_checkbox_group_field($component, $field_id, $value) {
        $selected_values = is_array($value) ? $value : [];
        
        $output = '<div class="sisme-profile-checkbox-group">';
        
        foreach ($component['options'] as $option_value => $option_label) {
            $checked = in_array($option_value, $selected_values) ? ' checked' : '';
            $item_id = $field_id . '_' . $option_value;
            
            $output .= '<div class="sisme-profile-checkbox-item">';
            $output .= '<input type="checkbox" id="' . esc_attr($item_id) . '" name="' . esc_attr($component['output_var']) . '[]" value="' . esc_attr($option_value) . '" class="sisme-profile-checkbox"' . $checked . '>';
            $output .= '<label for="' . esc_attr($item_id) . '" class="sisme-profile-checkbox-label">' . esc_html($option_label) . '</label>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Vérifier si le formulaire a été soumis
     * @return bool Status de soumission
     */
    public function is_submitted() {
        return isset($_POST['sisme_user_profile_submit']);
    }
    
    /**
     * Obtenir les données soumises et sanitisées
     * @return array Données du formulaire
     */
    public function get_submitted_data() {
        if (!$this->is_submitted()) {
            return [];
        }
        
        $data = [];
        
        foreach ($this->components as $component_name => $component) {
            $output_var = $component['output_var'];
            
            if (isset($_POST[$output_var])) {
                $data[$output_var] = $this->sanitize_component_value($_POST[$output_var], $component);
            }
        }
        
        return $data;
    }
    
    /**
     * Sanitiser une valeur de composant
     * @param mixed $value Valeur brute
     * @param array $component Configuration du composant
     * @return mixed Valeur sanitisée
     */
    private function sanitize_component_value($value, $component) {
        switch ($component['type']) {
            case 'email':
                return sanitize_email($value);
                
            case 'url':
                return esc_url_raw($value);
                
            case 'textarea':
                return sanitize_textarea_field($value);
                
            case 'checkbox':
                return !empty($value);
                
            case 'checkbox_group':
            case 'select_multiple':
                return is_array($value) ? array_map('intval', $value) : [];
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Valider le formulaire
     * @return true|array Validation réussie ou erreurs
     */
    public function validate() {
        if (!$this->is_submitted()) {
            return true;
        }
        
        $data = $this->get_submitted_data();
        $errors = [];
        
        foreach ($this->components as $component_name => $component) {
            if (!empty($component['required'])) {
                $value = $data[$component['output_var']] ?? '';
                if (empty($value)) {
                    $errors[$component['output_var']] = $component['label'] . ' est requis.';
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
}