/**
 * File: /sisme-games-editor/includes/dev-editor-manager/assets/dev-editor-manager.js
 * JavaScript pour l'interface admin des développeurs/éditeurs
 * 
 * RESPONSABILITÉ:
 * - Gestion de la modal de modification
 * - Interactions boutons d'actions
 * - Validation côté client
 * - Feedback utilisateur en temps réel
 * 
 * DÉPENDANCES:
 * - jQuery (WordPress core)
 * - sismeDevEditorAjax (localisé depuis PHP)
 */

jQuery(document).ready(function($) {
    
    const config = {
        ajaxUrl: sismeDevEditorAjax.ajaxurl,
        nonce: sismeDevEditorAjax.nonce
    };
    
    initModalHandlers();
    initFormValidation();
    initSearchFilter();
    
    function initModalHandlers() {
        $(document).on('click', '.sisme-btn-edit', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const entityId = $button.data('entity-id');
            const entityName = $button.data('entity-name');
            const entityWebsite = $button.data('entity-website') || '';
            
            openEditModal(entityId, entityName, entityWebsite);
        });
        
        $(document).on('click', '.sisme-modal-close', function(e) {
            e.preventDefault();
            closeEditModal();
        });
        
        $(document).on('click', '.sisme-modal', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
        
        $(document).keyup(function(e) {
            if (e.key === 'Escape') {
                closeEditModal();
            }
        });
    }
    
    function openEditModal(entityId, entityName, entityWebsite) {
        const $modal = $('#sisme-edit-modal');
        
        $('#edit_entity_id').val(entityId);
        $('#edit_entity_name').val(entityName);
        $('#edit_entity_website').val(entityWebsite);
        
        $modal.fadeIn(300);
        $('#edit_entity_name').focus();
    }
    
    function closeEditModal() {
        const $modal = $('#sisme-edit-modal');
        $modal.fadeOut(200);
        
        $('#edit_entity_id').val('');
        $('#edit_entity_name').val('');
        $('#edit_entity_website').val('');
    }
    
    function initFormValidation() {
        $(document).on('submit', '.sisme-entity-form', function(e) {
            const $form = $(this);
            const nameField = $form.find('input[name="entity_name"]');
            const websiteField = $form.find('input[name="entity_website"]');
            
            clearFieldErrors($form);
            
            if (!validateEntityName(nameField.val())) {
                showFieldError(nameField, 'Le nom doit contenir au moins 2 caractères');
                e.preventDefault();
                return false;
            }
            
            if (websiteField.val() && !validateUrl(websiteField.val())) {
                showFieldError(websiteField, 'Format d\'URL invalide');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
        
        $(document).on('input', 'input[name="entity_name"]', function() {
            const $field = $(this);
            clearFieldErrors($field.closest('.sisme-form-field'));
            
            if ($field.val().length >= 2) {
                $field.removeClass('sisme-field-error');
            }
        });
        
        $(document).on('input', 'input[name="entity_website"]', function() {
            const $field = $(this);
            const $formField = $field.closest('.sisme-form-field');
            
            clearFieldErrors($formField);
            
            if ($field.val() && validateUrl($field.val())) {
                $field.removeClass('sisme-field-error');
            }
        });
    }
    
    function validateEntityName(name) {
        return name && name.trim().length >= 2;
    }
    
    function validateUrl(url) {
        if (!url) return true;
        try {
            new URL(url);
            return url.startsWith('http://') || url.startsWith('https://');
        } catch (e) {
            return false;
        }
    }
    
    function showFieldError($field, message) {
        const $formField = $field.closest('.sisme-form-field');
        $field.addClass('sisme-field-error');
        
        if (!$formField.find('.sisme-field-error-message').length) {
            $formField.append(`<div class="sisme-field-error-message">${escapeHtml(message)}</div>`);
        }
    }
    
    function clearFieldErrors($container) {
        $container.find('.sisme-field-error').removeClass('sisme-field-error');
        $container.find('.sisme-field-error-message').remove();
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function showNotification(message, type = 'success') {
        const $notification = $(`
            <div class="sisme-notification sisme-notification--${type}">
                ${escapeHtml(message)}
                <button class="sisme-notification-close">&times;</button>
            </div>
        `);
        
        $('body').append($notification);
        
        setTimeout(() => {
            $notification.fadeOut(300, () => $notification.remove());
        }, 5000);
        
        $notification.find('.sisme-notification-close').click(() => {
            $notification.fadeOut(200, () => $notification.remove());
        });
    }
    
    function initSearchFilter() {
        const $searchInput = $('#sisme-entity-search');
        const $tableRows = $('.sisme-dev-editor-table tbody tr');
        
        $searchInput.on('input', function() {
            const searchTerm = $(this).val().toLowerCase().trim();
            
            if (searchTerm === '') {
                $tableRows.show();
                return;
            }
            
            $tableRows.each(function() {
                const $row = $(this);
                const entityName = $row.find('.sisme-entity-name strong').text().toLowerCase();
                
                if (entityName.includes(searchTerm)) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        });
        
        $searchInput.on('keyup', function(e) {
            if (e.key === 'Escape') {
                $(this).val('').trigger('input');
            }
        });
    }
});