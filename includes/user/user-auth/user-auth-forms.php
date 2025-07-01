<?php
/**
 * File: /sisme-games-editor/includes/user/user-auth/user-auth-forms.php
 * Module de formulaires d'authentification utilisateur
 * 
 * RESPONSABILITÉ:
 * - Adaptation du module formulaire existant pour l'auth
 * - Composants spécifiques à l'authentification
 * - Rendu des formulaires cohérents avec le design
 * - Validation et gestion des erreurs
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Auth_Forms {
    
    private $components = [];
    private $form_data = [];
    private $form_action = '';
    private $form_method = 'POST';
    private $submit_button_text = 'Valider';
    private $show_nonce = true;
    private $nonce_action = 'sisme_user_auth';
    private $form_type = 'login'; // 'login' ou 'register'
    private $module_id = '';
    private static $instance_counter = 0;
    
    // Composants disponibles pour l'authentification
    private $available_components = [
        'user_email' => [
            'label' => 'Adresse email',
            Sisme_Utils_Games::KEY_DESCRIPTION => 'Votre adresse email de connexion',
            'required' => true,
            'output_var' => 'user_email',
            'type' => 'email',
            'icon' => '📧'
        ],
        'user_password' => [
            'label' => 'Mot de passe',
            Sisme_Utils_Games::KEY_DESCRIPTION => 'Votre mot de passe (8 caractères minimum)',
            'required' => true,
            'output_var' => 'user_password',
            'type' => 'password',
            'icon' => '🔐'
        ],
        'user_confirm_password' => [
            'label' => 'Confirmer le mot de passe',
            Sisme_Utils_Games::KEY_DESCRIPTION => 'Ressaisissez votre mot de passe',
            'required' => true,
            'output_var' => 'user_confirm_password',
            'type' => 'password',
            'icon' => '🔒'
        ],
        'user_display_name' => [
            'label' => 'Nom d\'affichage',
            Sisme_Utils_Games::KEY_DESCRIPTION => 'Le nom qui sera affiché publiquement (optionnel)',
            'required' => false,
            'output_var' => 'user_display_name',
            'type' => 'text',
            'icon' => '👤'
        ],
        'remember_me' => [
            'label' => 'Se souvenir de moi',
            Sisme_Utils_Games::KEY_DESCRIPTION => 'Rester connecté sur cet appareil',
            'required' => false,
            'output_var' => 'remember_me',
            'type' => 'checkbox',
            'icon' => '💾'
        ],
        'redirect_to' => [
            'label' => 'Redirection',
            Sisme_Utils_Games::KEY_DESCRIPTION => 'Page de destination après connexion',
            'required' => false,
            'output_var' => 'redirect_to',
            'type' => 'hidden',
            'icon' => '↗️'
        ]
    ];
    
    /**
     * Constructeur
     */
    public function __construct($components = [], $options = []) {
        // Générer un ID unique
        self::$instance_counter++;
        $this->module_id = 'user-auth-form-' . self::$instance_counter;
        
        // Définir les composants
        $this->set_components($components);
        
        // Traiter les options
        $this->process_form_options($options);
        
        // Récupérer les données soumises
        $this->process_submitted_data();
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sisme User Auth Forms] Formulaire {$this->form_type} initialisé avec " . count($this->components) . " composants");
        }
    }
    
    /**
     * Définir les composants du formulaire
     */
    private function set_components($components) {
        if (empty($components)) {
            $components = ['user_email', 'user_password']; // Par défaut
        }
        
        foreach ($components as $component_name) {
            if (isset($this->available_components[$component_name])) {
                $this->components[$component_name] = $this->available_components[$component_name];
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log("[Sisme User Auth Forms] Composant '$component_name' non reconnu");
                }
            }
        }
    }
    
    /**
     * Traiter les options du formulaire
     */
    private function process_form_options($options) {
        $this->form_action = $options['action'] ?? $_SERVER['REQUEST_URI'];
        $this->form_method = strtoupper($options['method'] ?? 'POST');
        $this->submit_button_text = $options['submit_text'] ?? 'Valider';
        $this->show_nonce = $options['nonce'] ?? true;
        $this->form_type = $options['type'] ?? 'login';
        
        // Actions nonce spécifiques selon le type
        $this->nonce_action = ($this->form_type === 'register') ? 'sisme_user_register' : 'sisme_user_login';
        
        // Redirection par défaut
        if (isset($options['redirect_to'])) {
            $this->form_data['redirect_to'] = esc_url($options['redirect_to']);
        }
    }
    
    /**
     * Traiter les données soumises
     */
    private function process_submitted_data() {
        $submitted_data = ($this->form_method === 'POST') ? $_POST : $_GET;
        
        if (empty($submitted_data)) {
            return;
        }
        
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
     * Sanitiser une valeur de composant
     */
    private function sanitize_component_value($value, $component_name) {
        $component = $this->available_components[$component_name];
        
        switch ($component['type']) {
            case 'email':
                return sanitize_email($value);
                
            case 'password':
                return $value; // Les mots de passe ne sont pas modifiés
                
            case 'text':
                return sanitize_text_field($value);
                
            case 'checkbox':
                return !empty($value);
                
            case 'hidden':
                return esc_url_raw($value);
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Vérifier si le formulaire a été soumis
     */
    public function is_submitted() {
        $submit_key = 'sisme_user_' . $this->form_type . '_submit';
        return isset($_POST[$submit_key]) || isset($_GET[$submit_key]);
    }
    
    /**
     * Obtenir les données soumises
     */
    public function get_submitted_data() {
        return $this->form_data;
    }
    
    /**
     * Valider le formulaire
     */
    public function validate() {
        if (!$this->is_submitted()) {
            return true;
        }
        
        return Sisme_User_Auth_Security::validate_user_data($this->form_data, $this->form_type);
    }
    
    /**
     * Rendu du formulaire complet
     */
    public function render() {
        $form_id = $this->module_id;
        $submit_name = 'sisme_user_' . $this->form_type . '_submit';
        
        // Récupérer les messages d'erreur/succès
        $message = Sisme_User_Auth_Handlers::get_auth_message();
        
        ?>
        <div class="sisme-user-auth-form-wrapper" id="<?php echo esc_attr($form_id); ?>">
            
            <?php if ($message): ?>
                <div class="sisme-auth-message sisme-auth-message--<?php echo esc_attr($message['type']); ?>">
                    <?php echo esc_html($message['text']); ?>
                </div>
            <?php endif; ?>
            
            <form class="sisme-user-auth-form sisme-user-auth-form--<?php echo esc_attr($this->form_type); ?>" 
                  method="<?php echo esc_attr($this->form_method); ?>" 
                  action="<?php echo esc_url($this->form_action); ?>"
                  novalidate>
                
                <?php if ($this->show_nonce): ?>
                    <?php wp_nonce_field($this->nonce_action); ?>
                <?php endif; ?>
                
                <div class="sisme-auth-form-fields">
                    <?php $this->render_components(); ?>
                </div>
                
                <div class="sisme-auth-form-actions">
                    <input type="hidden" name="<?php echo esc_attr($submit_name); ?>" >
                    <button type="submit"
                        class="sisme-button sisme-button-vert sisme-auth-submit">
                        <span class="sisme-btn-icon"><?php echo $this->form_type === 'register' ? '📝' : '🔐'; ?></span>
                        <?php echo esc_html($this->submit_button_text); ?>
                    </button>
                </div>
                
                <?php $this->render_hidden_fields(); ?>
                
            </form>
        </div>
        
        <?php $this->render_form_scripts(); ?>
        <?php
    }
    
    /**
     * Rendu des composants du formulaire
     */
    private function render_components() {
        foreach ($this->components as $component_name => $component) {
            if ($component['type'] === 'hidden') {
                continue; // Les champs cachés sont rendus séparément
            }
            
            $this->render_component($component_name, $component);
        }
    }
    
    /**
     * Rendu d'un composant individuel
     */
    private function render_component($component_name, $component) {
        $field_id = $this->module_id . '_' . $component_name;
        $current_value = $this->form_data[$component['output_var']] ?? '';
        $required_attr = $component['required'] ? 'required' : '';
        $required_label = $component['required'] ? ' *' : '';
        
        ?>
        <div class="sisme-auth-field sisme-auth-field--<?php echo esc_attr($component['type']); ?>">
            
            <label for="<?php echo esc_attr($field_id); ?>" class="sisme-auth-label">
                <span class="sisme-auth-label-icon"><?php echo $component['icon']; ?></span>
                <?php echo esc_html($component['label'] . $required_label); ?>
            </label>
            
            <?php if (!empty($component['description'])): ?>
                <p class="sisme-auth-field-description"><?php echo esc_html($component['description']); ?></p>
            <?php endif; ?>
            
            <?php $this->render_field_input($component, $field_id, $current_value, $required_attr); ?>
            
        </div>
        <?php
    }
    
    /**
     * Rendu du champ de saisie
     */
    private function render_field_input($component, $field_id, $current_value, $required_attr) {
        $name = $component['output_var'];
        
        switch ($component['type']) {
            case 'email':
            case 'text':
            case 'password':
                ?>
                <input type="<?php echo esc_attr($component['type']); ?>" 
                       id="<?php echo esc_attr($field_id); ?>" 
                       name="<?php echo esc_attr($name); ?>" 
                       value="<?php echo esc_attr($current_value); ?>" 
                       class="sisme-auth-input sisme-auth-input--<?php echo esc_attr($component['type']); ?>"
                       autocomplete="<?php echo $this->get_autocomplete($component['type']); ?>"
                       <?php echo $required_attr; ?>>
                <?php
                break;
                
            case 'checkbox':
                ?>
                <label class="sisme-auth-checkbox-wrapper">
                    <input type="checkbox" 
                           id="<?php echo esc_attr($field_id); ?>" 
                           name="<?php echo esc_attr($name); ?>" 
                           value="1" 
                           class="sisme-auth-checkbox"
                           <?php checked($current_value, true); ?>
                           <?php echo $required_attr; ?>>
                    <span class="sisme-auth-checkbox-label"><?php echo esc_html($component['label']); ?></span>
                </label>
                <?php
                break;
        }
    }
    
    /**
     * Rendu des champs cachés
     */
    private function render_hidden_fields() {
        foreach ($this->components as $component_name => $component) {
            if ($component['type'] === 'hidden') {
                $current_value = $this->form_data[$component['output_var']] ?? '';
                ?>
                <input type="hidden" 
                       name="<?php echo esc_attr($component['output_var']); ?>" 
                       value="<?php echo esc_attr($current_value); ?>">
                <?php
            }
        }
    }
    
    /**
     * Obtenir l'attribut autocomplete approprié
     */
    private function get_autocomplete($type) {
        switch ($type) {
            case 'email':
                return 'email';
            case 'password':
                return ($this->form_type === 'register') ? 'new-password' : 'current-password';
            case 'text':
                return 'name';
            default:
                return 'off';
        }
    }
    
    /**
     * Rendu des scripts JavaScript
     */
    private function render_form_scripts() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('<?php echo esc_js($this->module_id); ?>');
            const submitBtn = form.querySelector('.sisme-auth-submit');
            
            if (form && submitBtn) {
                // Validation en temps réel
                const inputs = form.querySelectorAll('.sisme-auth-input');
                inputs.forEach(function(input) {
                    input.addEventListener('blur', function() {
                        validateField(this);
                    });
                });
                
                // Soumission du formulaire
                form.addEventListener('submit', function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Indicateur de chargement
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="sisme-btn-icon">⏳</span> <?php echo esc_js($this->form_type === "register" ? "Inscription..." : "Connexion..."); ?>';
                });
            }
            
            function validateField(field) {
                // Validation basique côté client
                let isValid = true;
                const value = field.value.trim();
                
                if (field.hasAttribute('required') && !value) {
                    isValid = false;
                }
                
                if (field.type === 'email' && value && !isValidEmail(value)) {
                    isValid = false;
                }
                
                if (field.type === 'password' && value && value.length < 8) {
                    isValid = false;
                }
                
                // Ajouter/supprimer classe d'erreur
                field.classList.toggle('sisme-auth-input--error', !isValid);
                field.classList.toggle('sisme-auth-input--valid', isValid && value);
                
                return isValid;
            }
            
            function validateForm() {
                const inputs = form.querySelectorAll('.sisme-auth-input[required]');
                let allValid = true;
                
                inputs.forEach(function(input) {
                    if (!validateField(input)) {
                        allValid = false;
                    }
                });
                
                <?php if ($this->form_type === 'register'): ?>
                // Vérification confirmation mot de passe
                const password = form.querySelector('input[name="user_password"]');
                const confirmPassword = form.querySelector('input[name="user_confirm_password"]');
                
                if (password && confirmPassword && password.value !== confirmPassword.value) {
                    confirmPassword.classList.add('sisme-auth-input--error');
                    allValid = false;
                }
                <?php endif; ?>
                
                return allValid;
            }
            
            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }
        });
        </script>
        <?php
    }
    
    /**
     * Créer un formulaire de connexion rapide
     */
    public static function create_login_form($options = []) {
        $default_options = [
            'type' => 'login',
            'submit_text' => 'Se connecter'
        ];
        
        $options = array_merge($default_options, $options);
        $components = ['user_email', 'user_password', 'remember_me'];
        
        return new self($components, $options);
    }
    
    /**
     * Créer un formulaire d'inscription rapide
     */
    public static function create_register_form($options = []) {
        $default_options = [
            'type' => 'register',
            'submit_text' => 'Créer mon compte'
        ];
        
        $options = array_merge($default_options, $options);
        $components = ['user_email', 'user_password', 'user_confirm_password', 'user_display_name'];
        
        return new self($components, $options);
    }
}