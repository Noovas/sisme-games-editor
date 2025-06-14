/**
 * File: /sisme-games-editor/assets/js/content-builder.js
 * Scripts pour le constructeur de contenu des fiches de jeu
 */

(function($) {
    'use strict';

    let sectionCounter = 0;
    let contentSections = [];
    let draggedSection = null;

    $(document).ready(function() {
        initContentBuilder();
    });

    /**
     * Initialiser le constructeur de contenu
     */
    function initContentBuilder() {
        // Transition vers la construction du contenu
        $('#continue-to-content').on('click', function(e) {
            e.preventDefault();
            
            if (validateMetaForm()) {
                showContentBuilder();
            }
        });

        // Navigation retour
        $('#back-to-meta').on('click', function() {
            hideContentBuilder();
        });

        // Ajouter section
        $('.sisme-add-section-btn, .sisme-add-first-section').on('click', function() {
            addContentSection();
        });

        // Soumission finale
        $('#create-fiche-final').on('click', function() {
            if (validateCompleteForm()) {
                submitCompleteForm();
            }
        });

        // Actions de prévisualisation et sauvegarde
        $('.sisme-preview-btn').on('click', showPreview);
        $('.sisme-save-draft-btn').on('click', saveDraft);

        console.log('Content Builder - Initialisé');
    }

    /**
     * Valider le formulaire des métadonnées
     */
    function validateMetaForm() {
        let isValid = true;
        
        // Vérifier titre
        const title = $('#game_title').val().trim();
        if (!title) {
            showNotification('Le titre du jeu est requis', 'error');
            $('#game_title').focus();
            isValid = false;
        }

        // Vérifier catégories
        if ($('.sisme-categories-selector input:checked').length === 0) {
            showNotification('Veuillez sélectionner au moins une catégorie', 'error');
            isValid = false;
        }

        // Vérifier mode de jeu
        if ($('.sisme-radio-group input:checked').length === 0) {
            showNotification('Veuillez sélectionner au moins un mode de jeu', 'error');
            isValid = false;
        }

        // Vérifier description
        const description = $('#game_description').val().trim();
        if (!description) {
            showNotification('La description du jeu est requise', 'error');
            $('#game_description').focus();
            isValid = false;
        }

        return isValid;
    }

    /**
     * Afficher le constructeur de contenu
     */
    function showContentBuilder() {
        // Masquer le formulaire des métadonnées
        $('#sisme-create-fiche-form').slideUp(400);
        
        // Afficher le constructeur
        setTimeout(function() {
            $('#content-builder-section').slideDown(600);
            
            // Afficher les actions finales
            setTimeout(function() {
                $('.sisme-builder-final-actions').fadeIn(400);
            }, 300);
        }, 200);

        // Mettre à jour le titre de la page
        $('.sisme-games-title').html(`
            <span class="dashicons dashicons-edit-page" style="margin-right: 12px; font-size: 28px; vertical-align: middle;"></span>
            Construction du contenu
        `);
        $('.sisme-games-subtitle').text('Étape 2/2 - Créez le contenu détaillé de votre fiche');

        showNotification('Passons maintenant à la construction du contenu !', 'success');
    }

    /**
     * Masquer le constructeur de contenu
     */
    function hideContentBuilder() {
        $('#content-builder-section').slideUp(400);
        
        setTimeout(function() {
            $('#sisme-create-fiche-form').slideDown(600);
        }, 200);

        // Restaurer le titre
        $('.sisme-games-title').html(`
            <span class="dashicons dashicons-plus-alt" style="margin-right: 12px; font-size: 28px; vertical-align: middle;"></span>
            Créer une fiche de jeu
        `);
        $('.sisme-games-subtitle').text('Remplissez les informations pour générer automatiquement une fiche complète');
    }

    /**
     * Ajouter une section de contenu
     */
    function addContentSection(sectionData = null) {
        sectionCounter++;
        const sectionId = `section_${sectionCounter}`;
        
        // Récupérer le template
        let sectionHtml = $('#section-template').html();
        sectionHtml = sectionHtml.replace(/{{SECTION_ID}}/g, sectionId);
        sectionHtml = sectionHtml.replace(/{{SECTION_NUMBER}}/g, sectionCounter);

        const $section = $(sectionHtml);
        
        // Masquer l'état vide
        $('.sisme-empty-state').fadeOut(300);
        
        // Ajouter la section
        setTimeout(function() {
            $('#sections-container').append($section);
            $section.addClass('new');
            
            // Initialiser les fonctionnalités de la section
            initSectionFeatures($section, sectionId);
            
            // Restaurer les données si fournies
            if (sectionData) {
                restoreSectionData($section, sectionData);
            }
            
            // Faire défiler vers la section
            $('html, body').animate({
                scrollTop: $section.offset().top - 100
            }, 500);
            
        }, 300);

        // Ajouter aux données
        contentSections.push({
            id: sectionId,
            title: '',
            emoji: '',
            content: '',
            imageId: null
        });

        updateSectionNumbers();
    }

    /**
     * Initialiser les fonctionnalités d'une section
     */
    function initSectionFeatures($section, sectionId) {
        // Actions de section
        $section.find('.sisme-section-collapse').on('click', function() {
            toggleSectionCollapse($section);
        });

        $section.find('.sisme-section-duplicate').on('click', function() {
            duplicateSection($section);
        });

        $section.find('.sisme-section-delete').on('click', function() {
            deleteSection($section, sectionId);
        });

        // Sélecteur d'emoji
        initEmojiSelector($section);

        // Éditeur de texte
        initTextEditor($section);

        // Sélecteur d'image
        initSectionImageSelector($section, sectionId);

        // Drag & Drop
        initSectionDragDrop($section);

        // Sauvegarde automatique
        $section.find('input, textarea').on('input change', function() {
            autoSaveSectionData($section, sectionId);
        });
    }

    /**
     * Initialiser le sélecteur d'emoji
     */
    function initEmojiSelector($section) {
        const $trigger = $section.find('.sisme-emoji-trigger');
        const $dropdown = $section.find('.sisme-emoji-dropdown');
        const $display = $section.find('.sisme-emoji-display');

        // Toggle dropdown
        $trigger.on('click', function(e) {
            e.stopPropagation();
            
            // Fermer les autres dropdowns
            $('.sisme-emoji-dropdown').not($dropdown).removeClass('show');
            $('.sisme-emoji-trigger').not($trigger).removeClass('active');
            
            $dropdown.toggleClass('show');
            $trigger.toggleClass('active');
        });

        // Catégories d'emojis
        $section.find('.sisme-emoji-cat').on('click', function() {
            const category = $(this).data('category');
            
            $section.find('.sisme-emoji-cat').removeClass('active');
            $(this).addClass('active');
            
            $section.find('.sisme-emoji-grid').hide();
            $section.find(`[data-category="${category}"]`).show();
        });

        // Sélection d'emoji
        $section.find('.sisme-emoji-item').on('click', function() {
            const emoji = $(this).data('emoji');
            
            $display.text(emoji);
            $trigger.data('emoji', emoji);
            $dropdown.removeClass('show');
            $trigger.removeClass('active');
            
            $(this).addClass('selected');
            setTimeout(() => $(this).removeClass('selected'), 300);
            
            showNotification('Emoji ajouté !', 'success', 1500);
        });

        // Supprimer emoji
        $section.find('.sisme-emoji-remove').on('click', function() {
            $display.text('😀');
            $trigger.data('emoji', '');
            $dropdown.removeClass('show');
            $trigger.removeClass('active');
            
            showNotification('Emoji supprimé', 'success', 1500);
        });

        // Recherche d'emoji
        $section.find('.sisme-emoji-search-input').on('input', function() {
            const query = $(this).val().toLowerCase();
            searchEmojis($section, query);
        });

        // Fermer dropdown en cliquant à l'extérieur
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.sisme-emoji-selector').length) {
                $dropdown.removeClass('show');
                $trigger.removeClass('active');
            }
        });
    }

    /**
     * Rechercher des emojis
     */
    function searchEmojis($section, query) {
        const $items = $section.find('.sisme-emoji-item');
        
        if (!query) {
            $items.show();
            return;
        }

        // Mapping simple pour la recherche (dans un vrai projet, on aurait une base de données d'emojis)
        const emojiKeywords = {
            '🎮': ['jeu', 'game', 'gaming', 'manette'],
            '🕹️': ['joystick', 'arcade', 'retro'],
            '🎯': ['cible', 'target', 'precision'],
            '⚔️': ['épée', 'sword', 'combat', 'fight'],
            '🏰': ['château', 'castle', 'medieval'],
            '🐉': ['dragon', 'fantasy', 'creature'],
            '👾': ['alien', 'space', 'retro', '8bit'],
            '🚀': ['fusée', 'rocket', 'space', 'fast'],
            '💎': ['diamant', 'diamond', 'precious', 'rare'],
            '⭐': ['étoile', 'star', 'favorite'],
            '🔥': ['feu', 'fire', 'hot', 'awesome'],
            '💯': ['cent', 'perfect', 'score'],
            '🏆': ['trophée', 'trophy', 'winner', 'champion'],
            '⚡': ['éclair', 'lightning', 'fast', 'power']
        };

        $items.each(function() {
            const emoji = $(this).data('emoji');
            const keywords = emojiKeywords[emoji] || [];
            const shouldShow = keywords.some(keyword => keyword.includes(query)) || emoji.includes(query);
            
            $(this).toggle(shouldShow);
        });
    }

    /**
     * Initialiser l'éditeur de texte
     */
    function initTextEditor($section) {
        const $toolbar = $section.find('.sisme-editor-toolbar');
        const $textarea = $section.find('.sisme-content-editor');

        // Boutons de formatage
        $toolbar.find('.sisme-format-btn').on('click', function() {
            const format = $(this).data('format');
            applyTextFormat($textarea, format);
            $(this).toggleClass('active');
        });

        // Auto-resize du textarea
        $textarea.on('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.max(120, this.scrollHeight) + 'px';
        });
    }

    /**
     * Appliquer un formatage de texte
     */
    function applyTextFormat($textarea, format) {
        const textarea = $textarea[0];
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        const beforeText = textarea.value.substring(0, start);
        const afterText = textarea.value.substring(end);

        let formattedText = '';
        
        switch (format) {
            case 'h1':
                formattedText = `<h1>${selectedText || 'Titre principal'}</h1>`;
                break;
            case 'h2':
                formattedText = `<h2>${selectedText || 'Titre secondaire'}</h2>`;
                break;
            case 'h3':
                formattedText = `<h3>${selectedText || 'Sous-titre'}</h3>`;
                break;
            case 'strong':
                formattedText = `<strong>${selectedText || 'Texte en gras'}</strong>`;
                break;
            case 'em':
                formattedText = `<em>${selectedText || 'Texte en italique'}</em>`;
                break;
            case 'p':
                formattedText = `<p>${selectedText || 'Nouveau paragraphe'}</p>`;
                break;
        }

        const newValue = beforeText + formattedText + afterText;
        $textarea.val(newValue);
        
        // Repositionner le curseur
        const newPosition = start + formattedText.length;
        setTimeout(() => {
            textarea.setSelectionRange(newPosition, newPosition);
            textarea.focus();
        }, 10);

        // Déclencher l'événement input pour l'auto-resize et la sauvegarde
        $textarea.trigger('input');
        
        showNotification(`Formatage ${format.toUpperCase()} appliqué`, 'success', 1500);
    }

    /**
     * Initialiser le sélecteur d'image de section
     */
    function initSectionImageSelector($section, sectionId) {
        const $selectBtn = $section.find('.sisme-select-section-image');
        const $removeBtn = $section.find('.sisme-remove-section-image');
        const $preview = $section.find('.sisme-media-preview-small');
        const $hiddenInput = $section.find('.sisme-section-image-id');

        let mediaUploader;

        $selectBtn.add($preview).on('click', function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Sélectionner une image pour cette section',
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
                
                $hiddenInput.val(attachment.id);
                $preview.html(`<img src="${attachment.url}" alt="${attachment.alt || ''}">`);
                $preview.addClass('has-image');
                
                $selectBtn.text('Changer l\'image');
                $removeBtn.show();
                
                showNotification('Image ajoutée à la section !', 'success');
            });

            mediaUploader.open();
        });

        $removeBtn.on('click', function() {
            $hiddenInput.val('');
            $preview.html(`
                <div class="sisme-media-placeholder-small">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <p>Ajouter une image</p>
                </div>
            `);
            $preview.removeClass('has-image');
            $selectBtn.text('Choisir une image');
            $removeBtn.hide();
            
            showNotification('Image supprimée', 'success', 1500);
        });
    }

    /**
     * Actions sur les sections
     */
    function toggleSectionCollapse($section) {
        $section.toggleClass('collapsed');
        const $icon = $section.find('.sisme-section-collapse .dashicons');
        
        if ($section.hasClass('collapsed')) {
            $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
        } else {
            $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
        }
    }

    function duplicateSection($section) {
        const sectionData = getSectionData($section);
        sectionData.title += ' (Copie)';
        sectionData.imageId = null; // Ne pas dupliquer l'image
        
        addContentSection(sectionData);
        showNotification('Section dupliquée !', 'success');
    }

    function deleteSection($section, sectionId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette section ?')) {
            return;
        }

        $section.slideUp(400, function() {
            $section.remove();
            
            // Supprimer des données
            contentSections = contentSections.filter(s => s.id !== sectionId);
            
            // Vérifier s'il faut afficher l'état vide
            if (contentSections.length === 0) {
                $('.sisme-empty-state').fadeIn(400);
                $('.sisme-builder-final-actions').fadeOut(300);
            }
            
            updateSectionNumbers();
        });
        
        showNotification('Section supprimée', 'success', 1500);
    }

    /**
     * Mettre à jour la numérotation des sections
     */
    function updateSectionNumbers() {
        $('#sections-container .sisme-section').each(function(index) {
            $(this).find('.sisme-section-type').html(`
                <span class="dashicons dashicons-edit-page"></span>
                Section ${index + 1}
            `);
        });
    }

    /**
     * Initialiser le drag & drop
     */
    function initSectionDragDrop($section) {
        $section.attr('draggable', true);
        
        $section.on('dragstart', function(e) {
            draggedSection = this;
            $(this).addClass('dragging');
            
            // Données pour le drag
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/html', this.outerHTML);
        });

        $section.on('dragend', function() {
            $(this).removeClass('dragging');
            $('.sisme-drop-indicator').removeClass('show');
            draggedSection = null;
        });

        $section.on('dragover', function(e) {
            e.preventDefault();
            e.originalEvent.dataTransfer.dropEffect = 'move';
            
            if (this !== draggedSection) {
                $(this).addClass('drag-over');
            }
        });

        $section.on('dragleave', function() {
            $(this).removeClass('drag-over');
        });

        $section.on('drop', function(e) {
            e.preventDefault();
            
            if (this !== draggedSection) {
                const $dragged = $(draggedSection);
                const $target = $(this);
                
                // Déterminer la position d'insertion
                const draggedIndex = $dragged.index();
                const targetIndex = $target.index();
                
                if (draggedIndex < targetIndex) {
                    $target.after($dragged);
                } else {
                    $target.before($dragged);
                }
                
                // Mise à jour des données
                reorderSections();
                updateSectionNumbers();
                
                showNotification('Section déplacée !', 'success', 1500);
            }
            
            $(this).removeClass('drag-over');
        });
    }

    /**
     * Réorganiser les données des sections
     */
    function reorderSections() {
        const newOrder = [];
        
        $('#sections-container .sisme-section').each(function() {
            const sectionId = $(this).data('section-id');
            const sectionData = contentSections.find(s => s.id === sectionId);
            if (sectionData) {
                newOrder.push(sectionData);
            }
        });
        
        contentSections = newOrder;
    }

    /**
     * Récupérer les données d'une section
     */
    function getSectionData($section) {
        const sectionId = $section.data('section-id');
        
        return {
            id: sectionId,
            title: $section.find('.sisme-section-title-input').val(),
            emoji: $section.find('.sisme-emoji-trigger').data('emoji') || '',
            content: $section.find('.sisme-content-editor').val(),
            imageId: $section.find('.sisme-section-image-id').val() || null
        };
    }

    /**
     * Restaurer les données d'une section
     */
    function restoreSectionData($section, sectionData) {
        if (sectionData.title) {
            $section.find('.sisme-section-title-input').val(sectionData.title);
        }
        
        if (sectionData.emoji) {
            $section.find('.sisme-emoji-display').text(sectionData.emoji);
            $section.find('.sisme-emoji-trigger').data('emoji', sectionData.emoji);
        }
        
        if (sectionData.content) {
            $section.find('.sisme-content-editor').val(sectionData.content);
            // Déclencher l'auto-resize
            $section.find('.sisme-content-editor').trigger('input');
        }
        
        if (sectionData.imageId) {
            // Dans un vrai projet, on récupérerait l'URL de l'image via AJAX
            $section.find('.sisme-section-image-id').val(sectionData.imageId);
        }
    }

    /**
     * Sauvegarde automatique des données de section
     */
    function autoSaveSectionData($section, sectionId) {
        const sectionData = getSectionData($section);
        
        // Mettre à jour dans le tableau
        const index = contentSections.findIndex(s => s.id === sectionId);
        if (index !== -1) {
            contentSections[index] = sectionData;
        }
        
        // Sauvegarder en localStorage
        localStorage.setItem('sisme_content_sections', JSON.stringify(contentSections));
    }

    /**
     * Prévisualisation du contenu
     */
    function showPreview() {
        // Collecter toutes les données
        const allSections = [];
        $('#sections-container .sisme-section').each(function() {
            allSections.push(getSectionData($(this)));
        });

        // Créer la fenêtre de prévisualisation
        const previewWindow = window.open('', 'preview', 'width=800,height=600,scrollbars=yes');
        
        const previewHtml = generatePreviewHtml(allSections);
        previewWindow.document.write(previewHtml);
        previewWindow.document.close();
        
        showNotification('Aperçu généré !', 'success');
    }

    /**
     * Générer le HTML de prévisualisation
     */
    function generatePreviewHtml(sections) {
        const gameTitle = $('#game_title').val() || 'Titre du jeu';
        
        let sectionsHtml = '';
        sections.forEach(section => {
            if (section.title || section.content) {
                sectionsHtml += `
                    <div style="margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
                        ${section.title ? `<h3>${section.emoji} ${section.title}</h3>` : ''}
                        ${section.content ? `<div>${section.content}</div>` : ''}
                        ${section.imageId ? `<p><em>Image sélectionnée (ID: ${section.imageId})</em></p>` : ''}
                    </div>
                `;
            }
        });

        return `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Aperçu - ${gameTitle}</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
                    h1 { color: #2C3E50; border-bottom: 2px solid #A1B78D; padding-bottom: 10px; }
                    h3 { color: #557A46; }
                    p { line-height: 1.6; }
                </style>
            </head>
            <body>
                <h1>${gameTitle}</h1>
                <p><strong>Prévisualisation du contenu de la fiche</strong></p>
                ${sectionsHtml || '<p><em>Aucun contenu à afficher</em></p>'}
            </body>
            </html>
        `;
    }

    /**
     * Sauvegarder le brouillon
     */
    function saveDraft() {
        // Collecter toutes les données
        const draftData = {
            meta: getMetaFormData(),
            sections: []
        };

        $('#sections-container .sisme-section').each(function() {
            draftData.sections.push(getSectionData($(this)));
        });

        // Sauvegarder
        localStorage.setItem('sisme_complete_draft', JSON.stringify(draftData));
        
        showNotification('Brouillon sauvegardé !', 'success');
    }

    /**
     * Récupérer les données du formulaire meta
     */
    function getMetaFormData() {
        return {
            game_title: $('#game_title').val(),
            featured_image_id: $('#featured_image_id').val(),
            game_categories: getSelectedCategories(),
            game_tags: getSelectedTags(),
            game_modes: getSelectedGameModes(),
            release_date: $('#release_date').val(),
            developers: getDevelopers(),
            editors: getEditors(),
            game_description: $('#game_description').val(),
            trailer_url: $('#trailer_url').val(),
            steam_url: $('input[name="steam_url"]').val(),
            epic_url: $('input[name="epic_url"]').val(),
            gog_url: $('input[name="gog_url"]').val()
        };
    }

    /**
     * Fonctions utilitaires pour récupérer les données du formulaire
     */
    function getSelectedCategories() {
        return $('.sisme-categories-selector input:checked').map(function() {
            return $(this).val();
        }).get();
    }

    function getSelectedTags() {
        return selectedTags || []; // Variable du forms.js
    }

    function getSelectedGameModes() {
        return $('.sisme-radio-group input:checked').map(function() {
            return $(this).val();
        }).get();
    }

    function getDevelopers() {
        return developers || []; // Variable du forms.js
    }

    function getEditors() {
        return editors || []; // Variable du forms.js
    }

    /**
     * Valider le formulaire complet
     */
    function validateCompleteForm() {
        // Vérifier les métadonnées
        if (!validateMetaForm()) {
            hideContentBuilder();
            return false;
        }

        // Vérifier qu'il y a au moins une section avec du contenu
        let hasContent = false;
        $('#sections-container .sisme-section').each(function() {
            const sectionData = getSectionData($(this));
            if (sectionData.title.trim() || sectionData.content.trim()) {
                hasContent = true;
                return false; // Break
            }
        });

        if (!hasContent) {
            showNotification('Veuillez ajouter au moins une section avec du contenu', 'error');
            return false;
        }

        return true;
    }

    /**
     * Soumettre le formulaire complet
     */
    function submitCompleteForm() {
        const $submitBtn = $('#create-fiche-final');
        const originalBtnContent = $submitBtn.html();
        
        // Désactiver le bouton
        $submitBtn.html('<span class="dashicons dashicons-update spin"></span> Création en cours...').prop('disabled', true);

        // Préparer toutes les données
        const completeData = {
            action: 'sisme_create_complete_fiche',
            nonce: sismeGamesEditor.nonce,
            meta: getMetaFormData(),
            sections: []
        };

        // Collecter les sections
        $('#sections-container .sisme-section').each(function() {
            completeData.sections.push(getSectionData($(this)));
        });

        // Envoyer via AJAX
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: completeData,
            success: function(response) {
                if (response.success) {
                    showNotification('Fiche de jeu créée avec succès !', 'success');
                    
                    // Nettoyer les brouillons
                    localStorage.removeItem('sisme_form_draft');
                    localStorage.removeItem('sisme_content_sections');
                    localStorage.removeItem('sisme_complete_draft');
                    
                    // Rediriger après 2 secondes
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
     * Fonction utilitaire pour les notifications
     */
    function showNotification(message, type, duration) {
        // Utiliser la fonction du forms.js si disponible
        if (window.SismeGamesEditor && window.SismeGamesEditor.showNotification) {
            window.SismeGamesEditor.showNotification(message, type, duration);
            return;
        }

        // Version simplifiée
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

    // Exposer des fonctions globalement si nécessaire
    window.SismeContentBuilder = {
        addSection: addContentSection,
        saveDraft: saveDraft,
        showPreview: showPreview
    };

})(jQuery);