/**
 * File: /sisme-games-editor/assets/js/forms.js
 * Scripts JavaScript pour les formulaires de création
 */

(function($) {
    'use strict';

    let selectedTags = [];
    let developers = [];
    let editors = [];
    let featuredImageId = null;

    $(document).ready(function() {
        initFormFeatures();
    });

    /**
     * Initialiser toutes les fonctionnalités du formulaire
     */
    function initFormFeatures() {
        initMediaUploader();
        initCategorySelector();
        initTagsSelector();
        initGameModeSelector();
        initDeveloperEditor();
        initFormValidation();
        initProgressIndicator();
        initAutoSave();
        
        console.log('Sisme Games Forms - Initialisé');
    }

    /**
     * Sélecteur d'image de mise en avant
     */
    function initMediaUploader() {
        let mediaUploader;

        $('#select-featured-image, .sisme-media-preview').on('click', function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Sélectionner l\'image de mise en avant',
                button: {
                    text: 'Utiliser cette image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                
                featuredImageId = attachment.id;
                $('#featured_image_id').val(attachment.id);
                
                const $preview = $('#featured-image-preview');
                $preview.html(`<img src="${attachment.url}" alt="${attachment.alt || ''}">`);
                $preview.addClass('has-image');
                
                $('#select-featured-image').text('Changer l\'image');
                
                showNotification('Image sélectionnée avec succès !', 'success');
            });

            mediaUploader.open();
        });
    }

    /**
     * Sélecteur de catégories avec création
     */
    function initCategorySelector() {
        // Gestion de la sélection
        $('.sisme-categories-selector input[type="checkbox"]').on('change', function() {
            const $label = $(this).closest('.sisme-checkbox-label');
            
            if ($(this).is(':checked')) {
                $label.addClass('selected');
                animateSelection($label);
            } else {
                $label.removeClass('selected');
            }
            
            updateProgressIndicator();
        });

        // Ajouter nouvelle catégorie
        $('#add-category-btn').on('click', function() {
            const categoryName = $('#new_category_name').val().trim();
            
            if (!categoryName) {
                showFieldMessage($('#new_category_name'), 'Veuillez saisir un nom de catégorie', 'error');
                return;
            }
            
            if (categoryName.length < 2) {
                showFieldMessage($('#new_category_name'), 'Le nom doit contenir au moins 2 caractères', 'error');
                return;
            }
            
            // Créer la nouvelle catégorie via AJAX
            createNewCategory(categoryName);
        });

        // Validation en temps réel
        $('#new_category_name').on('input', function() {
            const $field = $(this);
            const value = $field.val().trim();
            
            if (value.length >= 2) {
                hideFieldMessage($field);
            }
        });
    }

    /**
     * Créer une nouvelle catégorie
     */
    function createNewCategory(name) {
        const $btn = $('#add-category-btn');
        const $input = $('#new_category_name');
        const originalBtnContent = $btn.html();
        
        $btn.html('<span class="dashicons dashicons-update spin"></span>').prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'sisme_create_category',
                category_name: name,
                nonce: sismeGamesEditor.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Ajouter la nouvelle catégorie à la liste
                    const newCheckbox = `
                        <label class="sisme-checkbox-label selected">
                            <input type="checkbox" name="game_categories[]" value="${response.data.term_id}" checked>
                            <span>${name}</span>
                        </label>
                    `;
                    
                    $('.sisme-add-category').before(newCheckbox);
                    
                    // Réinitialiser le champ
                    $input.val('');
                    
                    showNotification(`Catégorie "${name}" créée avec succès !`, 'success');
                } else {
                    showFieldMessage($input, response.data || 'Erreur lors de la création', 'error');
                }
            },
            error: function() {
                showFieldMessage($input, 'Erreur de connexion', 'error');
            },
            complete: function() {
                $btn.html(originalBtnContent).prop('disabled', false);
            }
        });
    }

    /**
     * Sélecteur d'étiquettes avec création
     */
    function initTagsSelector() {
        const $tagsInput = $('#tags_input');
        const $tagsList = $('#selected-tags');
        
        $tagsInput.on('keypress', function(e) {
            if (e.which === 13) { // Entrée
                e.preventDefault();
                const tagName = $(this).val().trim();
                
                if (tagName && !selectedTags.includes(tagName)) {
                    addTag(tagName);
                    $(this).val('');
                }
            }
        });

        // Auto-complétion des étiquettes existantes
        $tagsInput.on('input', function() {
            const value = $(this).val().toLowerCase();
            
            if (value.length >= 2) {
                // Simuler une recherche d'étiquettes existantes
                // Dans une version complète, on ferait un appel AJAX
                showTagSuggestions(value);
            } else {
                hideTagSuggestions();
            }
        });
    }

    /**
     * Ajouter une étiquette
     */
    function addTag(tagName) {
        selectedTags.push(tagName);
        
        const $tagItem = $(`
            <div class="sisme-tag-item" data-tag="${tagName}">
                <span>${tagName}</span>
                <button type="button" class="sisme-tag-remove">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
        `);
        
        $('#selected-tags').append($tagItem);
        
        // Gérer la suppression
        $tagItem.find('.sisme-tag-remove').on('click', function() {
            removeTag(tagName);
            $tagItem.remove();
        });
        
        updateProgressIndicator();
    }

    /**
     * Supprimer une étiquette
     */
    function removeTag(tagName) {
        selectedTags = selectedTags.filter(tag => tag !== tagName);
        updateProgressIndicator();
    }

    /**
     * Sélecteur de mode de jeu
     */
    function initGameModeSelector() {
        $('.sisme-radio-label input').on('change', function() {
            const $label = $(this).closest('.sisme-radio-label');
            
            if ($(this).is(':checked')) {
                $label.addClass('selected');
                animateSelection($label);
            } else {
                $label.removeClass('selected');
            }
            
            updateProgressIndicator();
        });
    }

    /**
     * Gestion des développeurs et éditeurs
     */
    function initDeveloperEditor() {
        // Ajouter développeur
        $('#add-developer-btn').on('click', function() {
            const name = $('#dev_name').val().trim();
            const url = $('#dev_url').val().trim();
            
            if (!name) {
                showFieldMessage($('#dev_name'), 'Nom requis', 'error');
                return;
            }
            
            addDeveloper(name, url);
            $('#dev_name, #dev_url').val('');
        });

        // Ajouter éditeur
        $('#add-editor-btn').on('click', function() {
            const name = $('#editor_name').val().trim();
            const url = $('#editor_url').val().trim();
            
            if (!name) {
                showFieldMessage($('#editor_name'), 'Nom requis', 'error');
                return;
            }
            
            addEditor(name, url);
            $('#editor_name, #editor_url').val('');
        });
    }

    /**
     * Ajouter un développeur
     */
    function addDeveloper(name, url) {
        const dev = { name, url };
        developers.push(dev);
        
        const $devItem = $(`
            <div class="sisme-dev-item" data-dev-index="${developers.length - 1}">
                <div class="sisme-dev-info">
                    <div class="sisme-dev-name">${name}</div>
                    ${url ? `<a href="${url}" class="sisme-dev-url" target="_blank">${url}</a>` : ''}
                </div>
                <button type="button" class="sisme-dev-remove">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        `);
        
        $('#developers-list').append($devItem);
        
        // Gérer la suppression
        $devItem.find('.sisme-dev-remove').on('click', function() {
            const index = parseInt($devItem.data('dev-index'));
            developers.splice(index, 1);
            $devItem.remove();
            updateDeveloperIndexes();
        });
        
        updateProgressIndicator();
    }

    /**
     * Ajouter un éditeur
     */
    function addEditor(name, url) {
        const editor = { name, url };
        editors.push(editor);
        
        const $editorItem = $(`
            <div class="sisme-dev-item" data-editor-index="${editors.length - 1}">
                <div class="sisme-dev-info">
                    <div class="sisme-dev-name">${name}</div>
                    ${url ? `<a href="${url}" class="sisme-dev-url" target="_blank">${url}</a>` : ''}
                </div>
                <button type="button" class="sisme-dev-remove">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        `);
        
        $('#editors-list').append($editorItem);
        
        // Gérer la suppression
        $editorItem.find('.sisme-dev-remove').on('click', function() {
            const index = parseInt($editorItem.data('editor-index'));
            editors.splice(index, 1);
            $editorItem.remove();
            updateEditorIndexes();
        });
        
        updateProgressIndicator();
    }

    /**
     * Validation du formulaire
     */
    function initFormValidation() {
        const $form = $('#sisme-create-fiche-form');
        
        // Validation en temps réel
        $form.find('input[required], textarea[required]').on('blur', function() {
            validateField($(this));
        });
        
        // Validation URL
        $form.find('input[type="url"]').on('blur', function() {
            validateUrl($(this));
        });
        
        // Soumission du formulaire
        $form.on('submit', function(e) {
            e.preventDefault();
            
            if (validateForm()) {
                submitForm();
            }
        });
    }

    /**
     * Valider un champ
     */
    function validateField($field) {
        const value = $field.val().trim();
        const isRequired = $field.prop('required');
        
        $field.removeClass('error success');
        hideFieldMessage($field);
        
        if (isRequired && !value) {
            $field.addClass('error');
            showFieldMessage($field, 'Ce champ est requis', 'error');
            return false;
        }
        
        if (value) {
            $field.addClass('success');
            return true;
        }
        
        return true;
    }

    /**
     * Valider une URL
     */
    function validateUrl($field) {
        const url = $field.val().trim();
        
        if (url) {
            try {
                new URL(url);
                $field.removeClass('error').addClass('success');
                hideFieldMessage($field);
                return true;
            } catch (e) {
                $field.removeClass('success').addClass('error');
                showFieldMessage($field, 'URL invalide', 'error');
                return false;
            }
        }
        
        return true;
    }

    /**
     * Valider tout le formulaire
     */
    function validateForm() {
        let isValid = true;
        
        // Valider les champs requis
        $('#sisme-create-fiche-form input[required], #sisme-create-fiche-form textarea[required]').each(function() {
            if (!validateField($(this))) {
                isValid = false;
            }
        });
        
        // Vérifier qu'au moins une catégorie est sélectionnée
        if ($('.sisme-categories-selector input:checked').length === 0) {
            showNotification('Veuillez sélectionner au moins une catégorie', 'error');
            isValid = false;
        }
        
        // Vérifier qu'au moins un mode de jeu est sélectionné
        if ($('.sisme-radio-group input:checked').length === 0) {
            showNotification('Veuillez sélectionner au moins un mode de jeu', 'error');
            isValid = false;
        }
        
        return isValid;
    }

    /**
     * Soumettre le formulaire
     */
    function submitForm() {
        const $form = $('#sisme-create-fiche-form');
        const $submitBtn = $form.find('button[type="submit"]');
        const originalBtnContent = $submitBtn.html();
        
        // Désactiver le bouton et afficher le loading
        $submitBtn.html('<span class="dashicons dashicons-update spin"></span> Création en cours...').prop('disabled', true);
        
        // Préparer les données
        const formData = {
            action: 'sisme_create_fiche',
            nonce: sismeGamesEditor.nonce,
            game_title: $('#game_title').val(),
            featured_image_id: featuredImageId,
            game_categories: getSelectedCategories(),
            game_tags: selectedTags,
            game_modes: getSelectedGameModes(),
            release_date: $('#release_date').val(),
            developers: developers,
            editors: editors,
            game_description: $('#game_description').val(),
            trailer_url: $('#trailer_url').val(),
            steam_url: $('input[name="steam_url"]').val(),
            epic_url: $('input[name="epic_url"]').val(),
            gog_url: $('input[name="gog_url"]').val()
        };
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotification('Fiche de jeu créée avec succès !', 'success');
                    
                    // Rediriger vers la liste après 2 secondes
                    setTimeout(function() {
                        window.location.href = sismeGamesEditor.ficheListUrl || 'admin.php?page=sisme-games-fiches';
                    }, 2000);
                } else {
                    showNotification(response.data || 'Erreur lors de la création', 'error');
                }
            },
            error: function() {
                showNotification('Erreur de connexion', 'error');
            },
            complete: function() {
                $submitBtn.html(originalBtnContent).prop('disabled', false);
            }
        });
    }

    /**
     * Fonctions utilitaires
     */
    function getSelectedCategories() {
        return $('.sisme-categories-selector input:checked').map(function() {
            return $(this).val();
        }).get();
    }

    function getSelectedGameModes() {
        return $('.sisme-radio-group input:checked').map(function() {
            return $(this).val();
        }).get();
    }

    function updateDeveloperIndexes() {
        $('#developers-list .sisme-dev-item').each(function(index) {
            $(this).data('dev-index', index);
        });
    }

    function updateEditorIndexes() {
        $('#editors-list .sisme-dev-item').each(function(index) {
            $(this).data('editor-index', index);
        });
    }

    /**
     * Indicateur de progression
     */
    function initProgressIndicator() {
        updateProgressIndicator();
    }

    function updateProgressIndicator() {
        const totalFields = 8; // Nombre de sections importantes
        let completedFields = 0;
        
        // Vérifier titre
        if ($('#game_title').val().trim()) completedFields++;
        
        // Vérifier image
        if (featuredImageId) completedFields++;
        
        // Vérifier catégories
        if ($('.sisme-categories-selector input:checked').length > 0) completedFields++;
        
        // Vérifier étiquettes
        if (selectedTags.length > 0) completedFields++;
        
        // Vérifier mode de jeu
        if ($('.sisme-radio-group input:checked').length > 0) completedFields++;
        
        // Vérifier développeurs
        if (developers.length > 0) completedFields++;
        
        // Vérifier description
        if ($('#game_description').val().trim()) completedFields++;
        
        // Vérifier liens
        if ($('input[name="steam_url"]').val() || $('input[name="epic_url"]').val() || $('input[name="gog_url"]').val()) {
            completedFields++;
        }
        
        const percentage = Math.round((completedFields / totalFields) * 100);
        
        // Afficher/masquer l'indicateur
        if (percentage > 0 && percentage < 100) {
            showProgressIndicator(percentage);
        } else {
            hideProgressIndicator();
        }
    }

    function showProgressIndicator(percentage) {
        let $indicator = $('.sisme-progress-indicator');
        
        if ($indicator.length === 0) {
            $indicator = $(`
                <div class="sisme-progress-indicator">
                    <span>Progression</span>
                    <div class="sisme-progress-bar">
                        <div class="sisme-progress-fill"></div>
                    </div>
                    <span class="sisme-progress-text">0%</span>
                </div>
            `);
            $('body').append($indicator);
        }
        
        $indicator.addClass('show');
        $indicator.find('.sisme-progress-fill').css('width', percentage + '%');
        $indicator.find('.sisme-progress-text').text(percentage + '%');
    }

    function hideProgressIndicator() {
        $('.sisme-progress-indicator').removeClass('show');
    }

    /**
     * Sauvegarde automatique
     */
    function initAutoSave() {
        let autoSaveTimeout;
        
        $('#sisme-create-fiche-form input, #sisme-create-fiche-form textarea').on('input change', function() {
            clearTimeout(autoSaveTimeout);
            
            autoSaveTimeout = setTimeout(function() {
                saveFormData();
            }, 2000);
        });
        
        // Charger les données sauvegardées au chargement
        loadFormData();
    }

    function saveFormData() {
        const formData = {
            game_title: $('#game_title').val(),
            featured_image_id: featuredImageId,
            selectedTags: selectedTags,
            developers: developers,
            editors: editors,
            game_description: $('#game_description').val(),
            trailer_url: $('#trailer_url').val(),
            steam_url: $('input[name="steam_url"]').val(),
            epic_url: $('input[name="epic_url"]').val(),
            gog_url: $('input[name="gog_url"]').val(),
            release_date: $('#release_date').val()
        };
        
        localStorage.setItem('sisme_form_draft', JSON.stringify(formData));
        showNotification('Brouillon sauvegardé', 'success', 1500);
    }

    function loadFormData() {
        const savedData = localStorage.getItem('sisme_form_draft');
        
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                
                // Proposer de restaurer
                if (confirm('Un brouillon a été trouvé. Voulez-vous le restaurer ?')) {
                    restoreFormData(data);
                } else {
                    localStorage.removeItem('sisme_form_draft');
                }
            } catch (e) {
                localStorage.removeItem('sisme_form_draft');
            }
        }
    }

    function restoreFormData(data) {
        if (data.game_title) $('#game_title').val(data.game_title);
        if (data.game_description) $('#game_description').val(data.game_description);
        if (data.trailer_url) $('#trailer_url').val(data.trailer_url);
        if (data.steam_url) $('input[name="steam_url"]').val(data.steam_url);
        if (data.epic_url) $('input[name="epic_url"]').val(data.epic_url);
        if (data.gog_url) $('input[name="gog_url"]').val(data.gog_url);
        if (data.release_date) $('#release_date').val(data.release_date);
        
        if (data.featured_image_id) {
            featuredImageId = data.featured_image_id;
            $('#featured_image_id').val(data.featured_image_id);
            // Charger l'aperçu de l'image (nécessiterait un appel AJAX)
        }
        
        if (data.selectedTags && Array.isArray(data.selectedTags)) {
            data.selectedTags.forEach(tag => addTag(tag));
        }
        
        if (data.developers && Array.isArray(data.developers)) {
            data.developers.forEach(dev => addDeveloper(dev.name, dev.url));
        }
        
        if (data.editors && Array.isArray(data.editors)) {
            data.editors.forEach(editor => addEditor(editor.name, editor.url));
        }
        
        showNotification('Brouillon restauré', 'success');
        updateProgressIndicator();
    }

    /**
     * Fonctions utilitaires pour l'UI
     */
    function animateSelection($element) {
        $element.css('transform', 'scale(1.05)');
        setTimeout(() => {
            $element.css('transform', '');
        }, 200);
    }

    function showFieldMessage($field, message, type) {
        hideFieldMessage($field);
        
        const $message = $(`<div class="sisme-field-message ${type}">${message}</div>`);
        $field.after($message);
        
        setTimeout(() => {
            $message.addClass('show');
        }, 10);
    }

    function hideFieldMessage($field) {
        const $message = $field.siblings('.sisme-field-message');
        $message.removeClass('show');
        
        setTimeout(() => {
            $message.remove();
        }, 300);
    }

    function showNotification(message, type, duration) {
        const $notification = $(`
            <div class="sisme-notification sisme-notification-${type}">
                <span class="dashicons dashicons-${type === 'success' ? 'yes-alt' : 'warning'}"></span>
                <span>${message}</span>
            </div>
        `);
        
        $('body').append($notification);
        
        setTimeout(() => {
            $notification.addClass('show');
        }, 10);
        
        setTimeout(() => {
            $notification.removeClass('show');
            setTimeout(() => {
                $notification.remove();
            }, 300);
        }, duration || 3000);
    }

    function showTagSuggestions(query) {
        // Dans une implémentation complète, on ferait un appel AJAX
        // pour récupérer les étiquettes existantes
        const suggestions = ['Lost in Random', 'The Eternal Die', 'Roguelike Games'];
        const matches = suggestions.filter(tag => 
            tag.toLowerCase().includes(query) && !selectedTags.includes(tag)
        );
        
        if (matches.length > 0) {
            showAutoCompleteDropdown($('#tags_input'), matches, addTag);
        } else {
            hideTagSuggestions();
        }
    }

    function hideTagSuggestions() {
        $('#tags_input').siblings('.sisme-autocomplete-dropdown').remove();
    }

    function showAutoCompleteDropdown($field, suggestions, callback) {
        hideAutoCompleteDropdown($field);
        
        const $dropdown = $('<div class="sisme-autocomplete-dropdown"></div>');
        
        suggestions.forEach(suggestion => {
            const $item = $(`<div class="sisme-autocomplete-item">${suggestion}</div>`);
            $item.on('click', function() {
                callback(suggestion);
                $field.val('');
                hideAutoCompleteDropdown($field);
            });
            $dropdown.append($item);
        });
        
        $field.after($dropdown);
        
        setTimeout(() => {
            $dropdown.addClass('show');
        }, 10);
    }

    function hideAutoCompleteDropdown($field) {
        $field.siblings('.sisme-autocomplete-dropdown').remove();
    }

    // Nettoyer le brouillon à la soumission réussie
    $(document).on('sisme-form-submitted', function() {
        localStorage.removeItem('sisme_form_draft');
    });

})(jQuery);

// CSS dynamique pour les animations
jQuery(document).ready(function($) {
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .spin {
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            
            .sisme-notification {
                position: fixed;
                top: 32px;
                right: 20px;
                background: white;
                border-left: 4px solid var(--theme-palette-color-1);
                border-radius: 8px;
                padding: 15px 20px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                display: flex;
                align-items: center;
                gap: 10px;
                transform: translateX(400px);
                opacity: 0;
                transition: all 0.3s ease;
                z-index: 100000;
                max-width: 350px;
                font-size: 14px;
            }
            
            .sisme-notification.show {
                transform: translateX(0);
                opacity: 1;
            }
            
            .sisme-notification-error {
                border-left-color: #e74c3c;
            }
            
            .sisme-notification-success {
                border-left-color: #27ae60;
            }
            
            .sisme-notification .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }
            
            @media (max-width: 768px) {
                .sisme-notification {
                    right: 10px;
                    left: 10px;
                    max-width: none;
                    transform: translateY(-100px);
                }
                
                .sisme-notification.show {
                    transform: translateY(0);
                }
            }
        `)
        .appendTo('head');
});