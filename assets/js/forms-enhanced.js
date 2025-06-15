/**
 * File: /sisme-games-editor/assets/js/forms-enhanced.js
 * Scripts JavaScript perfectionnés pour les formulaires Sisme Games
 * Architecture moderne avec classes ES6 et gestion d'état avancée
 */

(function($) {
    'use strict';

    /**
     * Classe principale pour la gestion des formulaires
     */
    class SismeFormsManager {
        constructor() {
            this.state = {
                selectedTags: [],
                developers: [],
                editors: [],
                featuredImageId: null,
                formData: {},
                isDirty: false,
                isSubmitting: false
            };
            
            this.config = {
                autoSaveInterval: 3000,
                maxTags: 10,
                maxDevelopers: 5,
                maxEditors: 5,
                validationRules: {
                    title: { required: true, minLength: 3, maxLength: 100 },
                    description: { required: true, minLength: 50, maxLength: 2000 },
                    categories: { required: true, min: 1 },
                    modes: { required: true, min: 1 }
                }
            };
            
            this.mediaUploader = null;
            this.autoSaveTimer = null;
            this.validationTimer = null;
            
            this.init();
        }

        /**
         * Initialisation principale
         */
        init() {
            this.bindEvents();
            this.initComponents();
            this.setupValidation();
            this.loadSavedData();
            this.startAutoSave();
            
            console.log('🎮 Sisme Forms Manager - Initialisé avec succès');
        }

        /**
         * Liaison des événements
         */
        bindEvents() {
            // Événements de soumission
            $(document).on('submit', '#sisme-create-fiche-form', (e) => this.handleSubmit(e));
            
            // Événements de changement pour la sauvegarde automatique
            $(document).on('input change', '.sisme-field-input, .sisme-field-textarea, .sisme-field-select', 
                (e) => this.handleFieldChange(e));
            
            // Événements de validation en temps réel
            $(document).on('blur', '.sisme-field-input[required], .sisme-field-textarea[required]', 
                (e) => this.validateField($(e.target)));
            
            // Événements de fermeture de dropdowns
            $(document).on('click', (e) => this.handleGlobalClick(e));
            
            // Événements de raccourcis clavier
            $(document).on('keydown', (e) => this.handleKeyboardShortcuts(e));
            
            // Événements personnalisés
            $(document).on('sismeform:fieldchange', (e, data) => this.onFieldChange(data));
            $(document).on('sismeform:validate', (e, data) => this.onValidate(data));
        }

        /**
         * Initialisation des composants
         */
        initComponents() {
            this.mediaUploader = new MediaUploaderComponent(this);
            this.categorySelector = new CategorySelectorComponent(this);
            this.tagsSelector = new TagsSelectorComponent(this);
            this.gameModeSelector = new GameModeSelectorComponent(this);
            this.developerEditor = new DeveloperEditorComponent(this);
            this.progressIndicator = new ProgressIndicatorComponent(this);
            this.notificationManager = new NotificationManager();
        }

        /**
         * Configuration de la validation
         */
        setupValidation() {
            this.validator = new FormValidator(this.config.validationRules);
        }

        /**
         * Gestion de la soumission du formulaire
         */
        async handleSubmit(e) {
            e.preventDefault();
            
            if (this.state.isSubmitting) {
                return;
            }
            
            this.state.isSubmitting = true;
            const $form = $(e.target);
            const $submitBtn = $form.find('button[type="submit"], .sisme-btn[data-action="submit"]');
            
            try {
                // Validation complète
                const validationResult = await this.validateForm();
                if (!validationResult.isValid) {
                    throw new Error(validationResult.errors.join('\n'));
                }
                
                // Préparation des données
                const formData = this.prepareFormData();
                
                // Animation du bouton
                this.animateSubmitButton($submitBtn, 'loading');
                
                // Envoi AJAX
                const response = await this.submitForm(formData);
                
                if (response.success) {
                    this.onSubmitSuccess(response);
                } else {
                    throw new Error(response.data || 'Erreur lors de la soumission');
                }
                
            } catch (error) {
                this.onSubmitError(error);
            } finally {
                this.state.isSubmitting = false;
                this.animateSubmitButton($submitBtn, 'reset');
            }
        }

        /**
         * Gestion des changements de champs
         */
        handleFieldChange(e) {
            const $field = $(e.target);
            const fieldName = $field.attr('name') || $field.attr('id');
            const fieldValue = $field.val();
            
            // Mise à jour de l'état
            this.state.formData[fieldName] = fieldValue;
            this.state.isDirty = true;
            
            // Validation en temps réel avec debounce
            clearTimeout(this.validationTimer);
            this.validationTimer = setTimeout(() => {
                this.validateField($field);
            }, 300);
            
            // Événement personnalisé
            $(document).trigger('sismeform:fieldchange', {
                field: fieldName,
                value: fieldValue,
                element: $field
            });
        }

        /**
         * Validation d'un champ individuel
         */
        async validateField($field) {
            const fieldName = $field.attr('name') || $field.attr('id');
            const fieldValue = $field.val();
            
            try {
                const result = await this.validator.validateField(fieldName, fieldValue);
                
                $field.removeClass('error warning success');
                this.hideFieldMessage($field);
                
                if (result.isValid) {
                    $field.addClass('success');
                    if (result.message) {
                        this.showFieldMessage($field, result.message, 'success');
                    }
                } else {
                    $field.addClass(result.severity || 'error');
                    this.showFieldMessage($field, result.message, result.severity || 'error');
                }
                
                return result;
            } catch (error) {
                console.error('Erreur de validation:', error);
                return { isValid: false, message: 'Erreur de validation' };
            }
        }

        /**
         * Validation complète du formulaire
         */
        async validateForm() {
            const errors = [];
            const warnings = [];
            
            // Validation des champs requis
            const requiredFields = $('.sisme-field-input[required], .sisme-field-textarea[required]');
            
            for (let field of requiredFields) {
                const $field = $(field);
                const result = await this.validateField($field);
                
                if (!result.isValid) {
                    if (result.severity === 'warning') {
                        warnings.push(result.message);
                    } else {
                        errors.push(result.message);
                    }
                }
            }
            
            // Validation des sélections multiples
            if (this.state.selectedTags.length === 0) {
                // Optionnel, pas d'erreur mais warning
                warnings.push('Aucune étiquette sélectionnée');
            }
            
            if ($('.sisme-categories-selector input:checked').length === 0) {
                errors.push('Veuillez sélectionner au moins une catégorie');
            }
            
            if ($('.sisme-radio-group input:checked').length === 0) {
                errors.push('Veuillez sélectionner au moins un mode de jeu');
            }
            
            return {
                isValid: errors.length === 0,
                errors: errors,
                warnings: warnings
            };
        }

        /**
         * Préparation des données pour l'envoi
         */
        prepareFormData() {
            return {
                action: 'sisme_create_fiche',
                nonce: sismeGamesEditor.nonce,
                game_title: $('#game_title').val(),
                featured_image_id: this.state.featuredImageId,
                game_categories: this.getSelectedCategories(),
                game_tags: this.state.selectedTags,
                game_modes: this.getSelectedGameModes(),
                release_date: $('#release_date').val(),
                developers: this.state.developers,
                editors: this.state.editors,
                game_description: $('#game_description').val(),
                trailer_url: $('#trailer_url').val(),
                steam_url: $('input[name="steam_url"]').val(),
                epic_url: $('input[name="epic_url"]').val(),
                gog_url: $('input[name="gog_url"]').val()
            };
        }

        /**
         * Envoi du formulaire via AJAX
         */
        async submitForm(formData) {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: formData,
                    timeout: 30000,
                    success: resolve,
                    error: (xhr, status, error) => {
                        if (status === 'timeout') {
                            reject(new Error('Délai d\'attente dépassé'));
                        } else {
                            reject(new Error(error || 'Erreur de connexion'));
                        }
                    }
                });
            });
        }

        /**
         * Gestion du succès de soumission
         */
        onSubmitSuccess(response) {
            this.notificationManager.show('Fiche créée avec succès !', 'success');
            
            // Nettoyer les données sauvegardées
            this.clearSavedData();
            
            // Animation de succès
            this.animateSuccessForm();
            
            // Redirection après délai
            setTimeout(() => {
                window.location.href = response.redirect_url || sismeGamesEditor.ficheListUrl;
            }, 2000);
        }

        /**
         * Gestion des erreurs de soumission
         */
        onSubmitError(error) {
            console.error('Erreur de soumission:', error);
            this.notificationManager.show(error.message, 'error');
            
            // Animation d'erreur
            this.animateErrorForm();
        }

        /**
         * Animation du bouton de soumission
         */
        animateSubmitButton($btn, state) {
            const originalContent = $btn.data('original-content') || $btn.html();
            
            if (!$btn.data('original-content')) {
                $btn.data('original-content', originalContent);
            }
            
            switch (state) {
                case 'loading':
                    $btn.html('<span class="dashicons dashicons-update sisme-spin"></span> Création en cours...')
                        .prop('disabled', true)
                        .addClass('loading');
                    break;
                case 'success':
                    $btn.html('<span class="dashicons dashicons-yes-alt"></span> Succès !')
                        .addClass('success');
                    break;
                case 'error':
                    $btn.html('<span class="dashicons dashicons-warning"></span> Erreur')
                        .addClass('error')
                        .addClass('sisme-shake');
                    setTimeout(() => $btn.removeClass('sisme-shake'), 500);
                    break;
                case 'reset':
                default:
                    $btn.html(originalContent)
                        .prop('disabled', false)
                        .removeClass('loading success error');
                    break;
            }
        }

        /**
         * Animation de succès du formulaire
         */
        animateSuccessForm() {
            $('.sisme-form-section').each((index, element) => {
                setTimeout(() => {
                    $(element).addClass('sisme-pulse');
                    setTimeout(() => $(element).removeClass('sisme-pulse'), 500);
                }, index * 100);
            });
        }

        /**
         * Animation d'erreur du formulaire
         */
        animateErrorForm() {
            $('.sisme-form-section').addClass('sisme-shake');
            setTimeout(() => $('.sisme-form-section').removeClass('sisme-shake'), 500);
        }

        /**
         * Affichage de message de champ
         */
        showFieldMessage($field, message, type = 'info') {
            this.hideFieldMessage($field);
            
            const $message = $(`
                <div class="sisme-field-message ${type}">
                    <span class="dashicons dashicons-${this.getMessageIcon(type)}"></span>
                    <span>${message}</span>
                </div>
            `);
            
            $field.after($message);
            
            // Animation d'entrée
            setTimeout(() => $message.addClass('show'), 10);
        }

        /**
         * Masquage de message de champ
         */
        hideFieldMessage($field) {
            const $message = $field.next('.sisme-field-message');
            $message.removeClass('show');
            setTimeout(() => $message.remove(), 300);
        }

        /**
         * Obtenir l'icône pour un type de message
         */
        getMessageIcon(type) {
            const icons = {
                error: 'dismiss',
                warning: 'warning',
                success: 'yes-alt',
                info: 'info'
            };
            return icons[type] || 'info';
        }

        /**
         * Sauvegarde automatique
         */
        startAutoSave() {
            this.autoSaveTimer = setInterval(() => {
                if (this.state.isDirty && !this.state.isSubmitting) {
                    this.saveFormData();
                }
            }, this.config.autoSaveInterval);
        }

        /**
         * Sauvegarde des données du formulaire
         */
        saveFormData() {
            const formData = {
                ...this.state.formData,
                selectedTags: this.state.selectedTags,
                developers: this.state.developers,
                editors: this.state.editors,
                featuredImageId: this.state.featuredImageId,
                timestamp: Date.now()
            };
            
            try {
                localStorage.setItem('sisme_form_draft', JSON.stringify(formData));
                this.state.isDirty = false;
                
                // Notification discrète
                this.notificationManager.show('Brouillon sauvegardé', 'success', 1500);
            } catch (error) {
                console.warn('Impossible de sauvegarder le brouillon:', error);
            }
        }

        /**
         * Chargement des données sauvegardées
         */
        loadSavedData() {
            try {
                const savedData = localStorage.getItem('sisme_form_draft');
                if (savedData) {
                    const data = JSON.parse(savedData);
                    
                    // Vérifier la fraîcheur des données (24h max)
                    const maxAge = 24 * 60 * 60 * 1000; // 24 heures
                    if (Date.now() - data.timestamp > maxAge) {
                        this.clearSavedData();
                        return;
                    }
                    
                    // Proposer de restaurer
                    if (confirm('Un brouillon récent a été trouvé. Voulez-vous le restaurer ?')) {
                        this.restoreFormData(data);
                    } else {
                        this.clearSavedData();
                    }
                }
            } catch (error) {
                console.warn('Erreur lors du chargement du brouillon:', error);
                this.clearSavedData();
            }
        }

        /**
         * Restauration des données du formulaire
         */
        restoreFormData(data) {
            // Restaurer les champs texte
            Object.keys(data).forEach(key => {
                const $field = $(`#${key}, [name="${key}"]`);
                if ($field.length && typeof data[key] === 'string') {
                    $field.val(data[key]);
                }
            });
            
            // Restaurer les données complexes
            if (data.selectedTags) {
                this.state.selectedTags = data.selectedTags;
                this.tagsSelector.renderTags();
            }
            
            if (data.developers) {
                this.state.developers = data.developers;
                this.developerEditor.renderDevelopers();
            }
            
            if (data.editors) {
                this.state.editors = data.editors;
                this.developerEditor.renderEditors();
            }
            
            if (data.featuredImageId) {
                this.state.featuredImageId = data.featuredImageId;
                this.mediaUploader.updatePreview(data.featuredImageId);
            }
            
            this.notificationManager.show('Brouillon restauré', 'success');
            this.progressIndicator.update();
        }

        /**
         * Suppression des données sauvegardées
         */
        clearSavedData() {
            localStorage.removeItem('sisme_form_draft');
        }

        /**
         * Gestion des clics globaux
         */
        handleGlobalClick(e) {
            // Fermer les dropdowns ouverts
            if (!$(e.target).closest('.sisme-dropdown-container').length) {
                $('.sisme-dropdown').removeClass('show');
            }
        }

        /**
         * Gestion des raccourcis clavier
         */
        handleKeyboardShortcuts(e) {
            // Ctrl+S pour sauvegarder
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                this.saveFormData();
                this.notificationManager.show('Brouillon sauvegardé manuellement', 'info', 1000);
            }
            
            // Escape pour fermer les dropdowns
            if (e.key === 'Escape') {
                $('.sisme-dropdown').removeClass('show');
            }
        }

        /**
         * Obtenir les catégories sélectionnées
         */
        getSelectedCategories() {
            return $('.sisme-categories-selector input:checked').map(function() {
                return $(this).val();
            }).get();
        }

        /**
         * Obtenir les modes de jeu sélectionnés
         */
        getSelectedGameModes() {
            return $('.sisme-radio-group input:checked').map(function() {
                return $(this).val();
            }).get();
        }

        /**
         * Événement de changement de champ
         */
        onFieldChange(data) {
            this.progressIndicator.update();
        }

        /**
         * Événement de validation
         */
        onValidate(data) {
            // Mise à jour de l'indicateur de progression si nécessaire
            if (data.isValid) {
                this.progressIndicator.update();
            }
        }

        /**
         * Nettoyage lors de la destruction
         */
        destroy() {
            if (this.autoSaveTimer) {
                clearInterval(this.autoSaveTimer);
            }
            
            if (this.validationTimer) {
                clearTimeout(this.validationTimer);
            }
            
            // Nettoyer les composants
            Object.values(this).forEach(component => {
                if (component && typeof component.destroy === 'function') {
                    component.destroy();
                }
            });
        }
    }

    /**
     * Composant de gestion des médias
     */
    class MediaUploaderComponent {
        constructor(manager) {
            this.manager = manager;
            this.mediaUploader = null;
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            $(document).on('click', '#select-featured-image, .sisme-media-preview', 
                (e) => this.openMediaUploader(e));
        }

        openMediaUploader(e) {
            e.preventDefault();

            if (this.mediaUploader) {
                this.mediaUploader.open();
                return;
            }

            this.mediaUploader = wp.media({
                title: 'Sélectionner l\'image de mise en avant',
                button: { text: 'Utiliser cette image' },
                multiple: false,
                library: { type: 'image' }
            });

            this.mediaUploader.on('select', () => {
                const attachment = this.mediaUploader.state().get('selection').first().toJSON();
                this.selectImage(attachment);
            });

            this.mediaUploader.open();
        }

        selectImage(attachment) {
            this.manager.state.featuredImageId = attachment.id;
            $('#featured_image_id').val(attachment.id);
            
            const $preview = $('#featured-image-preview');
            $preview.html(`<img src="${attachment.url}" alt="${attachment.alt || ''}">`);
            $preview.addClass('has-image');
            
            $('#select-featured-image').text('Changer l\'image');
            
            this.manager.notificationManager.show('Image sélectionnée !', 'success');
            
            // Animation de succès
            $preview.addClass('sisme-pulse');
            setTimeout(() => $preview.removeClass('sisme-pulse'), 500);
        }

        updatePreview(imageId) {
            // Dans un vrai projet, on ferait un appel AJAX pour récupérer les données de l'image
            $('#featured_image_id').val(imageId);
            $('#select-featured-image').text('Changer l\'image');
        }
    }

    /**
     * Composant de sélection de catégories
     */
    class CategorySelectorComponent {
        constructor(manager) {
            this.manager = manager;
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            $(document).on('change', '.sisme-categories-selector input[type="checkbox"]', 
                (e) => this.handleCategoryChange(e));
            
            $(document).on('click', '#add-category-btn', 
                (e) => this.handleAddCategory(e));
            
            $(document).on('keypress', '#new_category_name', 
                (e) => {
                    if (e.which === 13) {
                        e.preventDefault();
                        this.handleAddCategory(e);
                    }
                });
        }

        handleCategoryChange(e) {
            const $checkbox = $(e.target);
            const $label = $checkbox.closest('.sisme-checkbox-label');
            
            if ($checkbox.is(':checked')) {
                $label.addClass('selected');
                this.animateSelection($label);
            } else {
                $label.removeClass('selected');
            }
            
            $(document).trigger('sismeform:fieldchange', {
                field: 'categories',
                value: this.manager.getSelectedCategories()
            });
        }

        async handleAddCategory(e) {
            e.preventDefault();
            
            const $input = $('#new_category_name');
            const categoryName = $input.val().trim();
            
            if (!categoryName) {
                this.manager.showFieldMessage($input, 'Veuillez saisir un nom de catégorie', 'error');
                return;
            }
            
            if (categoryName.length < 2 || categoryName.length > 50) {
                this.manager.showFieldMessage($input, 'Le nom doit contenir entre 2 et 50 caractères', 'error');
                return;
            }
            
            try {
                await this.createCategory(categoryName);
                $input.val('');
                this.manager.hideFieldMessage($input);
            } catch (error) {
                this.manager.showFieldMessage($input, error.message, 'error');
            }
        }

        async createCategory(name) {
            const $btn = $('#add-category-btn');
            const originalContent = $btn.html();
            
            $btn.html('<span class="dashicons dashicons-update sisme-spin"></span>').prop('disabled', true);
            
            try {
                const response = await $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'sisme_create_category',
                        category_name: name,
                        nonce: sismeGamesEditor.nonce
                    }
                });
                
                if (response.success) {
                    this.addCategoryToDOM(response.data.term_id, name);
                    this.manager.notificationManager.show(`Catégorie "${name}" créée !`, 'success');
                } else {
                    throw new Error(response.data || 'Erreur lors de la création');
                }
            } finally {
                $btn.html(originalContent).prop('disabled', false);
            }
        }

        addCategoryToDOM(termId, name) {
            const $newCategory = $(`
                <label class="sisme-checkbox-label selected sisme-animate-in">
                    <input type="checkbox" name="game_categories[]" value="${termId}" checked>
                    <span>${name}</span>
                </label>
            `);
            
            $('.sisme-add-category').before($newCategory);
            
            // Animation d'apparition
            setTimeout(() => $newCategory.removeClass('sisme-animate-in'), 600);
        }

        animateSelection($element) {
            $element.addClass('sisme-pulse');
            setTimeout(() => $element.removeClass('sisme-pulse'), 300);
        }
    }

    /**
     * Composant de sélection d'étiquettes
     */
    class TagsSelectorComponent {
        constructor(manager) {
            this.manager = manager;
            this.init();
        }

        init() {
            this.bindEvents();
        }

        bindEvents() {
            $(document).on('keypress', '#tags_input', (e) => this.handleTagInput(e));
            $(document).on('input', '#tags_input', (e) => this.handleTagSearch(e));
            $(document).on('click', '.sisme-tag-remove', (e) => this.handleTagRemove(e));
        }

        handleTagInput(e) {
            if (e.which === 13) { // Entrée
                e.preventDefault();
                const tagName = $(e.target).val().trim();
                
                if (tagName && !this.manager.state.selectedTags.includes(tagName)) {
                    if (this.manager.state.selectedTags.length < this.manager.config.maxTags) {
                        this.addTag(tagName);
                        $(e.target).val('');
                    } else {
                        this.manager.notificationManager.show(
                            `Maximum ${this.manager.config.maxTags} étiquettes autorisées`, 
                            'warning'
                        );
                    }
                }
            }
        }

        handleTagSearch(e) {
            const query = $(e.target).val().toLowerCase();
            
            if (query.length >= 2) {
                // Simulation d'auto-complétion
                // Dans un vrai projet, on ferait un appel AJAX
                this.showTagSuggestions(query);
            } else {
                this.hideTagSuggestions();
            }
        }

        handleTagRemove(e) {
            e.preventDefault();
            const $tag = $(e.target).closest('.sisme-tag-item');
            const tagName = $tag.data('tag');
            
            this.removeTag(tagName);
        }

        addTag(tagName) {
            if (this.manager.state.selectedTags.includes(tagName)) {
                return;
            }
            
            this.manager.state.selectedTags.push(tagName);
            this.renderTags();
            
            $(document).trigger('sismeform:fieldchange', {
                field: 'tags',
                value: this.manager.state.selectedTags
            });
        }

        removeTag(tagName) {
            this.manager.state.selectedTags = this.manager.state.selectedTags.filter(tag => tag !== tagName);
            this.renderTags();
            
            $(document).trigger('sismeform:fieldchange', {
                field: 'tags',
                value: this.manager.state.selectedTags
            });
        }

        renderTags() {
            const $container = $('#selected-tags');
            $container.empty();
            
            this.manager.state.selectedTags.forEach(tag => {
                const $tag = $(`
                    <div class="sisme-tag-item" data-tag="${tag}">
                        <span>${tag}</span>
                        <button type="button" class="sisme-tag-remove">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                `);
                
                $container.append($tag);
            });
        }

        showTagSuggestions(query) {
            // Simulation de suggestions
            const suggestions = ['Lost in Random', 'The Eternal Die', 'Roguelike Games', 'Indie Gaming'];
            const matches = suggestions.filter(tag => 
                tag.toLowerCase().includes(query) && 
                !this.manager.state.selectedTags.includes(tag)
            );
            
            if (matches.length > 0) {
                this.renderTagSuggestions(matches);
            } else {
                this.hideTagSuggestions();
            }
        }

        render