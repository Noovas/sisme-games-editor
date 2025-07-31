    
/**
 * File: /sisme-games-editor/includes/user/user-developer/game-submission/assets/game-submission.js
 * JavaScript pour la gestion des soumissions de jeux
 * 
 * RESPONSABILITÉ:
 * - Gestion CRUD des soumissions de jeux
 * - Interface utilisateur dynamique
 * - Validation et workflow des soumissions
 * 
 * DÉPENDANCES:
 * - jQuery (WordPress core)
 * - sismeAjax (variables AJAX globales)
 * - SismeDashboard (navigation sections)
 */

(function($) {
    'use strict';
    
    window.SismeGameSubmission = window.SismeGameSubmission || {
        config: {
            formSelector: '#sisme-submit-game-form',
            feedbackSelector: '#sisme-submit-game-feedback',
            submitButtonSelector: '#sisme-submit-game-button',
            draftButtonSelector: '#sisme-submit-game-btn',
            ajaxUrl: sismeAjax.ajaxurl,
            nonce: sismeAjax.nonce,
            currentSubmissionId: null,
            isSubmitting: false,
            isDraftSaving: false
        },
        isInitialized: false
    };
    
    /**
     * Initialisation du module soumissions
     */
    SismeGameSubmission.init = function() {
        if (this.isInitialized) {
            return;
        }
        
        if (typeof sismeAjax === 'undefined') {
            this.log('Erreur: sismeAjax non défini');
            return;
        }
        
        this.bindEvents();
        this.initFormValidation();
        this.isInitialized = true;
        this.log('Module Soumissions Jeux initialisé');
    };
    
    /**
     * Liaison des événements
     */
    SismeGameSubmission.bindEvents = function() {
        // Boutons soumissions dans la liste
        $(document).on('click', '.sisme-submission-item button', this.handleSubmissionAction.bind(this));
        
        // Formulaire de soumission
        $(document).on('click', this.config.draftButtonSelector, this.saveDraft.bind(this));
        $(document).on('click', this.config.submitButtonSelector, this.submitForReview.bind(this));
        
        // Navigation dashboard
        $(document).on('sisme:section:changed', this.onSectionChanged.bind(this));
    };
    
    /**
     * Gérer les actions sur les soumissions
     */
    SismeGameSubmission.handleSubmissionAction = function(e) {
        const $button = $(e.target);
        const $item = $button.closest('.sisme-submission-item');
        const submissionId = $item.data('submission-id');
        const action = $button.text().trim();
        
        if (!submissionId) {
            this.log('Erreur: ID soumission manquant');
            return;
        }
        
        if (action.includes('Continuer')) {
            this.editSubmission(submissionId);
        } else if (action.includes('Supprimer')) {
            this.deleteSubmission(submissionId);
        } else if (action.includes('Voir')) {
            this.viewSubmission(submissionId);
        } else if (action.includes('Réessayer')) {
            this.retrySubmission(submissionId);
        }
    };

    // Limite du nombre de sections
    SismeGameSubmission.getMaxSections = function() {
        return window.Sisme_Utils_Users?.GAME_MAX_SECTIONS_DESCRIPTION || 3;
    };

    SismeGameSubmission.canAddSection = function() {
        const currentCount = $('#game-sections-container .sisme-section-item').length;
        return currentCount < SismeGameSubmission.getMaxSections();
    };

    // Hook sur le bouton d'ajout de section
    $(document).on('click', '#add-section-btn', function(e) {
        if (!SismeGameSubmission.canAddSection()) {
            alert('Nombre maximum de sections atteint (' + SismeGameSubmission.getMaxSections() + ').');
            e.preventDefault();
            return false;
        }
        // Ajout/clonage d'une nouvelle section
        const sectionCount = $('#game-sections-container .sisme-section-item').length;
        const newSection = $('#game-sections-container .sisme-section-item').first().clone();
        newSection.find('input, textarea').val('');
        newSection.appendTo('#game-sections-container');
    });
    
    /**
     * Éditer une soumission existante
     */
    SismeGameSubmission.editSubmission = function(submissionId) {
        if (this.currentEditingId === submissionId) {
            return;
        }
        this.currentEditingId = submissionId;
        this.loadSubmissionData(submissionId).then(() => {
            if (typeof SismeDashboard !== 'undefined') {
                SismeDashboard.setActiveSection('submit-game', true);
            }
            this.config.currentSubmissionId = submissionId;
        }).catch(() => {
            this.showFeedback('Erreur lors du chargement de la soumission', 'error');
        });
    };
    
    /**
     * Supprimer une soumission (brouillons uniquement)
     */
    SismeGameSubmission.deleteSubmission = function(submissionId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette soumission ? Cette action est irréversible.')) {
            return;
        }
        
        this.log('Suppression soumission: ' + submissionId);
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_delete_game_submission',
                security: this.config.nonce,
                submission_id: submissionId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showFeedback(response.data.message, 'success');
                    this.refreshSubmissionsList();
                } else {
                    this.showFeedback(response.data.message, 'error');
                }
            },
            error: () => {
                this.showFeedback('Erreur réseau lors de la suppression', 'error');
            }
        });
    };
    
    /**
     * Voir les détails d'une soumission
     */
    SismeGameSubmission.viewSubmission = function(submissionId) {
        this.log('Affichage détails soumission: ' + submissionId);
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_get_submission_details',
                security: this.config.nonce,
                submission_id: submissionId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showSubmissionModal(response.data.submission);
                } else {
                    this.showFeedback(response.data.message, 'error');
                }
            },
            error: () => {
                this.showFeedback('Erreur lors du chargement des détails', 'error');
            }
        });
    };
    
    /**
     * Créer nouvelle version après rejet
     */
    SismeGameSubmission.retrySubmission = function(submissionId) {
        if (!confirm('Créer une nouvelle version de cette soumission ?')) {
            return;
        }
        
        this.log('Retry soumission: ' + submissionId);
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_retry_rejected_submission',
                security: this.config.nonce,
                original_submission_id: submissionId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showFeedback(response.data.message, 'success');
                    this.refreshSubmissionsList();
                    
                    // Naviguer vers l'édition de la nouvelle soumission
                    if (response.data.new_submission_id) {
                        this.editSubmission(response.data.new_submission_id);
                    }
                } else {
                    this.showFeedback(response.data.message, 'error');
                }
            },
            error: () => {
                this.showFeedback('Erreur lors de la création de la nouvelle version', 'error');
            }
        });
    };
    
    /**
     * Sauvegarder brouillon
     */
SismeGameSubmission.saveDraft = async function(e) {
        if (e) e.preventDefault();
        if (this.isDraftSaving) return;
        this.isDraftSaving = true;
        const $button = $(this.config.draftButtonSelector);
        const originalText = $button.text();
        $button.prop('disabled', true).text('💾 Sauvegarde...');

        // MODALE DE PATIENCE
        let modal = document.getElementById('sisme-saving-modal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'sisme-saving-modal';
            modal.style.position = 'fixed';
            modal.style.top = 0;
            modal.style.left = 0;
            modal.style.width = '100vw';
            modal.style.height = '100vh';
            modal.style.background = 'rgba(0,0,0,0.5)';
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.zIndex = 9999;
            modal.innerHTML = '<div style="background:#222;padding:2em 3em;border-radius:12px;color:#fff;font-size:1.3em;box-shadow:0 2px 16px #0006;display:flex;flex-direction:column;align-items:center;"><span style="font-size:2em;">⏳</span><span style="margin-top:1em;">Sauvegarde en cours...<br>Merci de patienter</span></div>';
            document.body.appendChild(modal);
        } else {
            modal.style.display = 'flex';
        }

        // UPLOAD DIFFÉRÉ DES IMAGES (covers)
        (async () => {
            try {
                const croppers = window.sismeCroppers || [];
                // 1. Upload toutes les images croppées (en séquentiel)
                for (const cropper of croppers) {
                    if (cropper.croppedBlob) {
                        const formData = new FormData();
                        formData.append('action', 'sisme_simple_crop_upload');
                        formData.append('security', sismeAjax.nonce);
                        formData.append('image', cropper.croppedBlob, `cropped-${cropper.ratioType}-image.jpg`);
                        formData.append('ratio_type', cropper.ratioType);
                        const response = await fetch(sismeAjax.ajaxurl, { method: 'POST', body: formData });
                        const data = await response.json();
                        if (data.success && data.data && data.data.attachment_id) {
                            // Met à jour le champ caché AVANT la collecte des données
                            const hiddenInput = document.getElementById(cropper.ratioType + '_attachment_id');
                            if (hiddenInput) {
                                hiddenInput.value = data.data.attachment_id;
                            } else {
                            }
                            // On retire le blob pour éviter un re-upload inutile
                            cropper.croppedBlob = null;
                        } else {
                        }
                    } else {
                    }
                }
                // 2. (Screenshots multiples à gérer ici si besoin)
                const screenshotCropper = croppers.find(c => c.ratioType === 'screenshot');
                if (screenshotCropper && screenshotCropper.uploadedImages && screenshotCropper.uploadedImages.length > 0) {
                    
                    // Récupérer les IDs existants pour comparaison (nettoyage)
                    const existingIds = document.getElementById('screenshots_attachment_ids').value;
                    const existingIdsArray = existingIds ? existingIds.split(',').map(id => parseInt(id.trim())).filter(id => id) : [];
                    
                    const newAttachmentIds = [];
                    
                    // Upload chaque screenshot qui a un blob
                    for (let i = 0; i < screenshotCropper.uploadedImages.length; i++) {
                        const screenshot = screenshotCropper.uploadedImages[i];
                        
                        if (screenshot.blob) {
                            const formData = new FormData();
                            formData.append('action', 'sisme_simple_crop_upload');
                            formData.append('security', sismeAjax.nonce);
                            formData.append('image', screenshot.blob, `screenshot-${i + 1}.jpg`);
                            formData.append('ratio_type', 'screenshot');
                            
                            const response = await fetch(sismeAjax.ajaxurl, { method: 'POST', body: formData });
                            const data = await response.json();
                            
                            if (data.success && data.data && data.data.attachment_id) {
                                newAttachmentIds.push(data.data.attachment_id);
                                // Mettre à jour l'objet screenshot avec l'ID
                                screenshotCropper.uploadedImages[i].attachmentId = data.data.attachment_id;
                                screenshotCropper.uploadedImages[i].blob = null; // Nettoyer le blob
                            } else {
                            }
                        } else if (screenshot.attachmentId) {
                            // Screenshot déjà uploadé
                            newAttachmentIds.push(screenshot.attachmentId);
                        }
                    }
                    
                    document.getElementById('screenshots_attachment_ids').value = newAttachmentIds.join(',');
                    
                    // TODO: Nettoyer les anciens médias (à implémenter côté serveur)
                    // Les IDs dans existingIdsArray qui ne sont plus dans newAttachmentIds doivent être supprimés
                }

                await this.uploadSectionImages();

                // 3. Collecte des données APRÈS que tous les uploads soient terminés
                const gameData = this.collectFormData();
                const isNewSubmission = !this.config.currentSubmissionId;
                const ajaxData = {
                    security: this.config.nonce,
                    ...gameData
                };
                if (isNewSubmission) {
                    ajaxData.action = 'sisme_create_game_submission';
                } else {
                    ajaxData.action = 'sisme_save_draft_submission';
                    ajaxData.submission_id = this.config.currentSubmissionId;
                }
                // 4. Sauvegarde AJAX du formulaire
                const result = await $.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: ajaxData,
                    dataType: 'json'
                });
                if (result.success) {
                    if (isNewSubmission && result.data.submission_id) {
                        this.config.currentSubmissionId = result.data.submission_id;
                    }
                    this.showFeedback(result.data.message, 'success');
                    this.updateCompletionProgress(result.data.completion_percentage);
                } else {
                    this.showFeedback(result.data.message, 'error');
                }
            } catch (err) {
                this.showFeedback('Erreur lors de la sauvegarde', 'error');
            } finally {
                $button.prop('disabled', false).text(originalText);
                this.isDraftSaving = false;
                if (modal) modal.style.display = 'none';
            }
        })();
    };
    
    /**
     * Soumettre pour validation
     */
    SismeGameSubmission.submitForReview = function(e) {
        if (e) e.preventDefault();
        
        if (this.isSubmitting || !this.config.currentSubmissionId) {
            return;
        }
        
        if (!this.validateForm()) {
            this.showFeedback('Veuillez corriger les erreurs dans le formulaire', 'error');
            return;
        }
        
        if (!confirm('Soumettre ce jeu pour validation ? Vous ne pourrez plus le modifier.')) {
            return;
        }
        
        this.isSubmitting = true;
        const $button = $(this.config.submitButtonSelector);
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('🚀 Soumission...');
        
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_submit_game_for_review',
                security: this.config.nonce,
                submission_id: this.config.currentSubmissionId
            },
            dataType: 'json',
            success: (response) => {
                if (response.success) {
                    this.showFeedback(response.data.message, 'success');
                    
                    // Retourner à la liste après soumission
                    setTimeout(() => {
                        if (typeof SismeDashboard !== 'undefined') {
                            SismeDashboard.setActiveSection('developer', true);
                        }
                    }, 2000);
                } else {
                    this.showFeedback(response.data.message, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: () => {
                this.showFeedback('Erreur réseau lors de la soumission', 'error');
                $button.prop('disabled', false).text(originalText);
            },
            complete: () => {
                this.isSubmitting = false;
            }
        });
    };
    
    /**
     * Charger les données d'une soumission
     */
    SismeGameSubmission.loadSubmissionData = function(submissionId) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_get_submission_details',
                    security: this.config.nonce,
                    submission_id: submissionId
                },
                dataType: 'json',
                success: (response) => {
                    if (response.success) {
                        this.populateForm(response.data.submission);
                        resolve(response.data.submission);
                    } else {
                        reject(response.data.message);
                    }
                },
                error: () => {
                    reject('Erreur réseau');
                }
            });
        });
    };
    
    /**
     * Remplir le formulaire avec les données d'une soumission
     */
    SismeGameSubmission.populateForm = function(submission) {
        
        const gameData = submission.game_data || {};
        const $form = $(this.config.formSelector);
        console.trace();
        

        Object.keys(gameData).forEach(key => {
            const $field = $form.find('[name="' + key + '"]');
            if ($field.length && gameData[key]) {
                $field.val(gameData[key]);
            }
        });
        
        if (gameData.external_links) {
            Object.entries(gameData.external_links).forEach(([platform, url]) => {
                $form.find(`input[name="external_links[${platform}]"]`).val(url);
            });
        }

        ['game_genres', 'game_platforms', 'game_modes'].forEach(fieldName => {
            if (gameData[fieldName] && Array.isArray(gameData[fieldName])) {
                gameData[fieldName].forEach(value => {
                    $form.find(`input[name="${fieldName}[]"][value="${value}"]`).prop('checked', true);
                });
            }
        });

        if (gameData.covers) {
            if (gameData.covers.horizontal) {
                $form.find('input[name="cover_horizontal_attachment_id"]').val(gameData.covers.horizontal);
                this.loadImageInCropper('cropper1', gameData.covers.horizontal);
            }
            if (gameData.covers.vertical) {
                $form.find('input[name="cover_vertical_attachment_id"]').val(gameData.covers.vertical);
                this.loadImageInCropper('cropper2', gameData.covers.vertical);
            }
        }
        
        if (gameData.screenshots && Array.isArray(gameData.screenshots)) {
            $form.find('input[name="screenshots_attachment_ids"]').val(gameData.screenshots.join(','));
            this.loadScreenshotsInCropper(gameData.screenshots);
        }

        // Charger les sections de description longue
        if (gameData.sections && Array.isArray(gameData.sections)) {
            // Vider d'abord le conteneur des sections
            const $sectionsContainer = $('#game-sections-container');
            $sectionsContainer.empty();
            
            // Recréer chaque section
            gameData.sections.forEach((section, index) => {
                // Déclencher l'ajout d'une nouvelle section via l'interface existante
                // (suppose qu'il existe un bouton ou une fonction pour ajouter une section)
                $('#add-section-btn').click(); // Adapter selon votre interface
                
                // Remplir les champs de la section nouvellement créée
                const $newSection = $sectionsContainer.find('.sisme-section-item').last();
                $newSection.find(`input[name="sections[${index}][title]"]`).val(section.title);
                $newSection.find(`textarea[name="sections[${index}][content]"]`).val(section.content);
                
                if (section.image_attachment_id) {
                    $newSection.find(`input[name="sections[${index}][image_id]"]`).val(section.image_attachment_id);
                    // Charger l'image dans l'interface si nécessaire
                    // this.loadSectionImage(index, section.image_attachment_id);
                }
            });
        }
        const completion = submission.metadata?.completion_percentage || 0;
        this.updateCompletionProgress(completion);
    };

    /**
     * Upload des images de sections avec IDs temporaires
     */
    SismeGameSubmission.uploadSectionImages = async function() {
        const sectionItems = document.querySelectorAll('#game-sections-container .sisme-section-item');
        
        for (let i = 0; i < sectionItems.length; i++) {
            const section = sectionItems[i];
            const imageIdInput = section.querySelector('.section-image-id');
            const imageInput = section.querySelector('.sisme-section-image-input');
            
            // Si c'est un ID temporaire et qu'il y a un fichier à uploader
            if (imageIdInput && imageIdInput.value.startsWith('temp_') && imageInput && imageInput.files[0]) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'sisme_simple_crop_upload');
                    formData.append('security', this.config.nonce);
                    formData.append('image', imageInput.files[0]);
                    formData.append('ratio_type', 'screenshot'); // Utiliser screenshot pour les sections
                    
                    const response = await fetch(this.config.ajaxUrl, { 
                        method: 'POST', 
                        body: formData 
                    });
                    const data = await response.json();
                    
                    if (data.success && data.data && data.data.attachment_id) {
                        // Remplacer l'ID temporaire par l'ID réel
                        imageIdInput.value = data.data.attachment_id;
                    } else {
                        console.error('Erreur upload image section:', data);
                    }
                } catch (error) {
                    console.error('Erreur upload image section:', error);
                }
            }
        }
    };

    SismeGameSubmission.loadImageInCropper = function(cropperId, attachmentId, index = 0) {
        if (cropperId === 'cropper3') return;
        // Récupérer l'URL de l'attachment via AJAX
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_attachment_url',
                attachment_id: attachmentId,
                security: this.config.nonce
            },
            success: (response) => {
                if (response.success && response.data.url) {
                    // Trouver l'instance du cropper et afficher l'image
                    let cropperInstance = window.cropperInstances[cropperId];
                    if (!cropperInstance) {
                        // Cherche une instance dont le uniqueId commence par cropperId
                        for (const key in window.cropperInstances) {
                            if (key.indexOf(cropperId) !== -1) {
                                cropperInstance = window.cropperInstances[key];
                                break;
                            }
                        }
                    }
                    if (cropperInstance) {
                        cropperInstance.displayExistingImage(response.data.url);
                    } else {
                        console.warn('Aucune instance de cropper trouvée pour', cropperId, window.cropperInstances);
                    }
                }
            }
        });
    };

    /**
     * Charger les screenshots existants dans la galerie
     */
    SismeGameSubmission.loadScreenshotsInCropper = function(screenshotIds) {
        if (!screenshotIds || !Array.isArray(screenshotIds)) return;
        
        let screenshotCropper = null;
        for (const key in window.cropperInstances) {
            const instance = window.cropperInstances[key];
            if (instance.ratioType === 'screenshot' && key.includes('cropper3')) {
                screenshotCropper = instance;
                break;
            }
        }
        
        if (!screenshotCropper) {
            console.warn('Cropper screenshot non trouvé');
            return;
        }
        
        // Vider la galerie existante
        screenshotCropper.uploadedImages = [];
        
        // Charger chaque screenshot
        screenshotIds.forEach((attachmentId) => {
            this.loadScreenshotById(screenshotCropper, attachmentId);
        });
    };

    /**
     * Charger un screenshot par son ID
     */
    SismeGameSubmission.loadScreenshotById = function(cropperInstance, attachmentId) {
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_attachment_url',
                attachment_id: attachmentId,
                security: this.config.nonce
            },
            success: (response) => {
                if (response.success && response.data.url) {
                    // Ajouter à la collection du cropper
                    const imageData = {
                        blob: null, // Pas de blob car c'est une image existante
                        url: response.data.url,
                        attachmentId: attachmentId,
                        ratioType: 'screenshot'
                    };
                    
                    cropperInstance.uploadedImages.push(imageData);
                    cropperInstance.updateGallery();
                    
                }
            },
            error: () => {
                console.error('Erreur chargement screenshot:', attachmentId);
            }
        });
    };
    
    /**
     * Collecter les données du formulaire
     */
    SismeGameSubmission.collectFormData = function() {
        const $form = $(this.config.formSelector);
        const gameData = {};
        
        $form.find('input, textarea, select').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            const type = $field.attr('type');
            if (!name || $field.is(':disabled')) return;
            if (name.includes('attachment_id')) return;
            
            if (type === 'checkbox' || type === 'radio') {
                if ($field.is(':checked')) {
                    if (name.endsWith('[]')) {
                        if (!gameData[name]) gameData[name] = [];
                        gameData[name].push($field.val());
                    } else {
                        gameData[name] = $field.val();
                    }
                }
            } else if ($field.is('select[multiple]')) {
                gameData[name] = $field.val() || [];
            } else {
                gameData[name] = $field.val();
            }
        });
        
        const externalLinks = {};
        $form.find('input[name^="external_links["]').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            const match = name.match(/external_links\[([^\]]+)\]/);
            if (match && $field.val().trim()) {
                externalLinks[match[1]] = $field.val().trim();
            }
        });
        if (Object.keys(externalLinks).length > 0) {
            gameData['external_links'] = externalLinks;
        }

        const coverH = $form.find('input[name="cover_horizontal_attachment_id"]').val();
        const coverV = $form.find('input[name="cover_vertical_attachment_id"]').val();
        const screenshots = $form.find('input[name="screenshots_attachment_ids"]').val();

        if (coverH || coverV) {
            gameData['covers'] = {};
            if (coverH) gameData['covers']['horizontal'] = coverH;
            if (coverV) gameData['covers']['vertical'] = coverV;
        }

        if (screenshots) {
            gameData['screenshots'] = screenshots;
        }

        // Collecte des sections de description longue
        const sections = [];
        $('#game-sections-container .sisme-section-item').each(function(i) {
            const $section = $(this);
            const title = $section.find(`input[name="sections[${i}][title]"]`).val() || '';
            const content = $section.find(`textarea[name="sections[${i}][content]"]`).val() || '';
            const imageId = $section.find('.section-image-id').val() || '';
            if (title && content) {
                sections.push({
                    title: title,
                    content: content,
                    image_attachment_id: imageId && !imageId.startsWith('temp_') ? parseInt(imageId) : null
                });
            }
        });
        if (sections.length > 0) {
            gameData['sections'] = sections;
        }
        
        return gameData;
    };
    
    /**
     * Validation du formulaire
     */
    SismeGameSubmission.validateForm = function() {
        const $form = $(this.config.formSelector);
        let isValid = true;
        
        $form.find('[required]').each(function() {
            const $field = $(this);
            if (!$field.val().trim()) {
                $field.addClass('error');
                isValid = false;
            } else {
                $field.removeClass('error');
            }
        });
        
        return isValid;
    };
    
    /**
     * Initialiser la validation du formulaire
     */
    SismeGameSubmission.initFormValidation = function() {
        $(document).on('blur', this.config.formSelector + ' [required]', function() {
            const $field = $(this);
            if ($field.val().trim()) {
                $field.removeClass('error');
            }
        });
    };
    
    /**
     * Mettre à jour l'indicateur de progression
     */
    SismeGameSubmission.updateCompletionProgress = function(percentage) {
        const $button = $(this.config.submitButtonSelector);
        
        if (percentage >= 100) {
            $button.prop('disabled', false).text('🚀 Soumettre pour Validation');
        } else {
            $button.prop('disabled', true).text('📝 Complétez le formulaire (' + percentage + '%)');
        }
    };
    
    /**
     * Rafraîchir la liste des soumissions
     */
    SismeGameSubmission.refreshSubmissionsList = function() {
        // Recharger la section développeur
        if (typeof SismeDashboard !== 'undefined') {
            SismeDashboard.refreshSection('developer');
        }
    };
    
    /**
     * Afficher une modal avec les détails
     */
    SismeGameSubmission.showSubmissionModal = function(submission) {
        // Modal simple pour l'instant
        const gameName = submission.game_data?.game_name || 'Jeu sans nom';
        const status = submission.status || 'unknown';
        
        alert('Détails de "' + gameName + '":\nStatut: ' + status);
        // TODO: Créer une vraie modal
    };
    
    /**
     * Gérer le changement de section dashboard
     */
    SismeGameSubmission.onSectionChanged = function(e, section) {
        if (section === 'submit-game') {
            this.config.currentSubmissionId = null;
        }
    };
    
    /**
     * Afficher un feedback utilisateur
     */
    SismeGameSubmission.showFeedback = function(message, type = 'info') {
        const $feedback = $(this.config.feedbackSelector);
        
        $feedback.removeClass('success error info warning')
                .addClass(type)
                .html(message)
                .show();
        
        if (type === 'success') {
            setTimeout(() => {
                $feedback.fadeOut();
            }, 5000);
        }
    };
    
    /**
     * Log de débogage
     */
    SismeGameSubmission.log = function(message) {
    };
    
    // Initialisation automatique
    $(document).ready(() => {
        SismeGameSubmission.init();
    });
    
})(jQuery);