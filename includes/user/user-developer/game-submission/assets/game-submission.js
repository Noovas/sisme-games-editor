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
            return;
        }
        
        this.bindEvents();
        this.enableSubmitButton();
        this.handleHashChange();
        this.bindModalEvents();
        this.bindArchiveEvents();
        
        this.isInitialized = true;

        // Mettre en évidence la description lors du hover sur le bouton de soumission
        $(document).on('mouseenter', '#sisme-submit-game-button', function() {
            $('.sisme-submit-description').addClass('sisme-highlight');
        });
        $(document).on('mouseleave', '#sisme-submit-game-button', function() {
            $('.sisme-submit-description').removeClass('sisme-highlight');
        });
    };
    
    /**
     * Liaison des événements
     */
    SismeGameSubmission.bindEvents = function() {
        // Boutons soumissions dans la liste
        $(document).on('click', '.sisme-submission-item button', this.handleSubmissionAction.bind(this));
        $(document).on('click', '.sisme-btn-revision', this.createRevision.bind(this));
        
        // Formulaire de soumission
        $(document).on('click', this.config.draftButtonSelector, this.saveDraft.bind(this));
        $(document).on('click', this.config.submitButtonSelector, this.submitForReview.bind(this));
        
        // Navigation dashboard
        $(document).on('sisme:section:changed', this.onSectionChanged.bind(this));

        $(document).on('sisme:submit-game:url-params', this.handleUrlParams.bind(this));
        $(window).on('hashchange', this.handleHashChange.bind(this));
    };

    /**
     * Recharge dynamiquement la liste des soumissions du dashboard développeur
     * Remplace le contenu de .sisme-submissions-list par le HTML retourné par AJAX
     * Utilisable partout après une action CRUD
     */
    window.reloadDashboardSubmitionDatas = function() {
        var $block = $('.sisme-my-games-section').first();
        if ($block.length === 0) return;
        $block.addClass('sisme-loading');
        $.ajax({
            url: sismeAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'sisme_reload_submissions_list',
                security: sismeAjax.nonce
            },
            dataType: 'html',
            success: function(html) {
                // Remplacer tout le bloc principal
                var $newBlock = $(html);
                $block.replaceWith($newBlock);
            },
            error: function() {
                $block.html('<div class="sisme-form-feedback sisme-form-feedback-error">Erreur lors du rechargement des soumissions.</div>');
            },
            complete: function() {
                // On ne peut pas retirer la classe si le bloc a été remplacé, donc rien ici
            }
        });
    };

    // Rafraîchir la liste lors du clic sur 'Retour à mes jeux'
    $(document).on('click', 'button.sisme-btn.sisme-button-bleu', function(e) {
        if ($(this).text().includes('Retour à mes jeux')) {
            if (typeof window.reloadDashboardSubmitionDatas === 'function') {
                window.reloadDashboardSubmitionDatas();
            }
        }
    });

    /**
     * Gérer le refresh de page (hashchange)
     */
    SismeGameSubmission.handleHashChange = function() {
        // Si on est sur submit-game, parser les paramètres
        const hash = window.location.hash.substring(1); // Enlever le #
        if (hash.startsWith('submit-game')) {
            const [section, queryString] = hash.split('?');
            const params = new URLSearchParams(queryString || '');
            
            // Déclencher la logique des paramètres
            this.handleUrlParams(null, params);
        }
    };

    

    /**
     * Charger une soumission pour édition
     */
    SismeGameSubmission.loadSubmissionForEdit = function(submissionId) {
        // Éviter de recharger si c'est déjà la soumission courante
        if (this.config.currentSubmissionId === submissionId) {
            return;
        }
        this.config.currentSubmissionId = submissionId;
        this.config.isEditMode = true;
        
        // Mettre à jour l'interface - mode édition
        this.updateFormUIForEdit(submissionId);
        
        // Charger les données
        this.loadSubmissionData(submissionId)
            .then(() => {
            })
            .catch((error) => {
                this.showFeedback('Erreur lors du chargement de la soumission', 'error');
            });
    };

    SismeGameSubmission.viewSubmission = function(submissionId) {
        // TODO: Implémenter la vue d'une soumission
    };

    /**
     * Nettoyer le formulaire pour nouveau jeu
     */
    SismeGameSubmission.clearFormForNewGame = function() {
        // Éviter de nettoyer si on est déjà en mode nouveau
        if (!this.config.currentSubmissionId && !this.config.isEditMode) {
            return;
        }
        this.config.currentSubmissionId = null;
        this.config.isEditMode = false;
        
        // Nettoyer le formulaire
        this.clearForm();
        
        // Mettre à jour l'interface - mode nouveau
        this.updateFormUIForNew();
    };

    /**
     * Mettre à jour l'interface pour le mode édition
     */
    SismeGameSubmission.updateFormUIForEdit = function(submissionId) {
        $('#sisme-form-title').text('✏️ Modifier le jeu');
        $('#sisme-new-game-btn').show();
    };

    /**
     * Mettre à jour l'interface pour le mode nouveau
     */
    SismeGameSubmission.updateFormUIForNew = function() {
        $('#sisme-form-title').text('➕ Soumettre un nouveau jeu');
        $('#sisme-new-game-btn').hide();
        const $description = $('.sisme-submit-game-description');
        $description.text('Partagez votre création avec la communauté Sisme Games. Remplissez les informations essentielles pour commencer.');
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

    SismeGameSubmission.handleUrlParams = function(e, params) {
        const editId = params.get('edit');
        
        if (editId) {
            this.loadSubmissionForEdit(editId);
        } else {
            this.clearFormForNewGame();
        }
    };

    SismeGameSubmission.clearForm = function() {
        const $form = $(this.config.formSelector);
        if ($form.length) {
            // Reset basique du formulaire
            $form[0].reset();
            
            // Nettoyer les sections dynamiques
            $('#game-sections-container').empty();
            
            // Remettre une section par défaut
            const defaultSectionHtml = this.createSectionHtml(0, { 
                title: '', 
                content: '', 
                image_attachment_id: null 
            });
            $('#game-sections-container').append(defaultSectionHtml);
            
            // Nettoyer les previews d'images
            $('.sisme-section-image-preview').hide();
            $('.sisme-upload-area').show();
            
            // Réinitialiser le bouton de soumission
            //this.resetSubmitButton();
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
     * Supprimer une soumission
     */
    SismeGameSubmission.deleteSubmission = function(submissionId) {
        if (this.isDeletingSubmission) {
            return;
        }
        this.isDeletingSubmission = true;
        
        const $button = $(`.sisme-submission-item[data-submission-id="${submissionId}"] button:contains("Supprimer")`);
        const originalText = $button.text();
        $button.prop('disabled', true).text('🗑️ Suppression...');
        
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
                    const $item = $(`.sisme-submission-item[data-submission-id="${submissionId}"]`);
                    $item.fadeOut(300, function() {
                        $item.remove();
                        
                        const remainingItems = $('.sisme-submission-item').length;
                        if (remainingItems === 0) {
                            $('.sisme-submissions-list').html(`
                                <div class="sisme-games-empty">
                                    <div class="sisme-empty-icon">🎮</div>
                                    <h5>Aucun jeu soumis</h5>
                                    <p>Commencez par soumettre votre premier jeu en utilisant le bouton ci-dessus !</p>
                                </div>
                            `);
                        }
                    });
                    
                } else {
                    $button.prop('disabled', false).text(originalText);
                    this.showFeedback(response.data.message, 'error');
                }
            },
            error: () => {
                $button.prop('disabled', false).text(originalText);
                this.showFeedback('Erreur réseau lors de la suppression', 'error');
            },
            complete: () => {
                this.isDeletingSubmission = false;
            }
        });
    };

    /**
     * Sauvegarder brouillon avec modale professionnelle
     */
    SismeGameSubmission.saveDraft = function(e) {
        if (e) e.preventDefault();
        if (this.config.isDraftSaving) return;
        if (typeof window.sismeSubmissionModal === 'undefined') {
            this.showFeedback('❌ Système de modale non disponible', 'error');
            return;
        }
        window.sismeSubmissionModal.show();
        window.sismeSubmissionModal.startDraftOnlyProcess(this);
        this.config.isDraftSaving = true;
    };

    /**
     * Callback après succès de sauvegarde draft
     */
    SismeGameSubmission.onDraftSuccess = function() {
        this.config.isDraftSaving = false;
        if (typeof window.reloadDashboardSubmitionDatas === 'function') {
            window.reloadDashboardSubmitionDatas();
        }
    };
    
    SismeGameSubmission.submitForReview = function(e) {
        if (e) e.preventDefault();
        
        if (this.config.isSubmitting) {
            return;
        }
        
        // Vérifier que l'instance modale est disponible
        if (typeof window.sismeSubmissionModal === 'undefined') {
            this.showFeedback('❌ Système de modale non disponible', 'error');
            return;
        }
        
        // Lancer la modale avec le processus complet
        window.sismeSubmissionModal.show();
        window.sismeSubmissionModal.startSubmissionProcess(this);
        
        // Marquer comme en cours de soumission
        this.config.isSubmitting = true;
    };

    /**
     * Version silencieuse de saveDraft pour la modale
     * Sauvegarde sans afficher de feedback (utilisée par la modale)
     */
    SismeGameSubmission.saveDraftSilently = function() {
        return new Promise(async (resolve, reject) => {
            if (this.configisDraftSaving) {
                resolve();
                return;
            }

            this.config.isDraftSaving = true;

            try {
                const croppers = window.sismeCroppers || [];

                for (const cropper of croppers) {
                    if (cropper.croppedBlob) {
                        const formData = new FormData();
                        formData.append('action', 'sisme_simple_crop_upload');
                        formData.append('security', this.config.nonce);
                        formData.append('image', cropper.croppedBlob, `cropped-${cropper.ratioType}-image.jpg`);
                        formData.append('ratio_type', cropper.ratioType);
                        
                        const response = await fetch(this.config.ajaxUrl, { method: 'POST', body: formData });
                        const data = await response.json();
                        
                        if (data.success && data.data && data.data.attachment_id) {
                            const hiddenInput = document.getElementById(cropper.ratioType + '_attachment_id');
                            if (hiddenInput) {
                                hiddenInput.value = data.data.attachment_id;
                            }
                            cropper.croppedBlob = null;
                        }
                    }
                }

                const screenshotCropper = croppers.find(c => c.ratioType === 'screenshot');
                if (screenshotCropper && screenshotCropper.uploadedImages && screenshotCropper.uploadedImages.length > 0) {
                    
                    const existingIds = document.getElementById('screenshots_attachment_ids').value;
                    const existingIdsArray = existingIds ? existingIds.split(',').map(id => parseInt(id.trim())).filter(id => id) : [];
                    const newAttachmentIds = [];

                    for (let i = 0; i < screenshotCropper.uploadedImages.length; i++) {
                        const screenshot = screenshotCropper.uploadedImages[i];
                        
                        if (screenshot.blob) {
                            const formData = new FormData();
                            formData.append('action', 'sisme_simple_crop_upload');
                            formData.append('security', this.config.nonce);
                            formData.append('image', screenshot.blob, `screenshot-${i + 1}.jpg`);
                            formData.append('ratio_type', 'screenshot');
                            
                            const response = await fetch(this.config.ajaxUrl, { method: 'POST', body: formData });
                            const data = await response.json();
                            
                            if (data.success && data.data && data.data.attachment_id) {
                                newAttachmentIds.push(data.data.attachment_id);
                                screenshotCropper.uploadedImages[i].attachmentId = data.data.attachment_id;
                                screenshotCropper.uploadedImages[i].blob = null;
                            }
                        } else if (screenshot.attachmentId) {
                            newAttachmentIds.push(screenshot.attachmentId);
                        }
                    }
                    
                    document.getElementById('screenshots_attachment_ids').value = newAttachmentIds.join(',');
                }

                await this.uploadSectionImages();

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

                    if (typeof window.reloadDashboardSubmitionDatas === 'function') {
                        window.reloadDashboardSubmitionDatas();
                    }
                    
                    resolve();
                } else {
                    reject(new Error(result.data.message || 'Erreur lors de la sauvegarde'));
                }
                
            } catch (err) {
                reject(err);
            } finally {
                this.config.isDraftSaving = false;
            }
        });
    };

    /**
     * Fonction appelée par la modale en cas de succès
     * La modale gère déjà la redirection, on remet juste l'état
     */
    SismeGameSubmission.onModalSuccess = function() {
        this.onSubmissionComplete();
    };

    /**
     * Fonction appelée par la modale en cas d'erreur
     * La modale se ferme, l'utilisateur peut corriger le formulaire
     */
    SismeGameSubmission.onModalError = function() {
        this.onSubmissionComplete();
    };

    /**
     * Modification de la fonction existante pour gérer la fin de soumission
     * Réinitialiser l'état de soumission quand la modale se ferme
     */
    SismeGameSubmission.onSubmissionComplete = function() {
        // Réinitialiser l'état de soumission quand la modale se ferme
        this.configisSubmitting = false;
    };

    /**
     * Liaison des événements modale
     */
    SismeGameSubmission.bindModalEvents = function() {
        // Écouter les événements de fermeture de modale
        $(document).on('sisme:modal:success', () => {
            this.onModalSuccess();
        });

        // Événement pour succès de draft
        $(document).on('sisme:modal:draft-success', () => {
            this.onDraftSuccess();
        });
        
        $(document).on('sisme:modal:error', () => {
            this.onModalError();
        });
        
        // Alternative polling pour vérifier si la modale est fermée
        this.checkModalStatus = setInterval(() => {
            if (this.config.isSubmitting && window.sismeSubmissionModal && !window.sismeSubmissionModal.isOpen) {
                this.onSubmissionComplete();
            }
        }, 1000);
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
                    const submissionData = response.data.submission || response.data;
                    this.populateForm(submissionData);
                    resolve(submissionData);
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
            this.loadSections(gameData.sections);
        }
    };

    SismeGameSubmission.enableSubmitButton = function() {
        const $button = $(this.config.submitButtonSelector);
        $button.prop('disabled', false).text('🚀 Soumettre pour Validation');
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

        if (screenshotCropper.isLoading) {
            return;
        }
        screenshotCropper.isLoading = true;
        screenshotCropper.uploadedImages = [];
        let loadedCount = 0;
        screenshotIds.forEach((attachmentId) => {
            this.loadScreenshotById(screenshotCropper, attachmentId, () => {
                loadedCount++;
                if (loadedCount === screenshotIds.length) {
                    screenshotCropper.isLoading = false;
                }
            });
        });
    };

    /**
     * Charger un screenshot par son ID
     */
    SismeGameSubmission.loadScreenshotById = function(cropperInstance, attachmentId, callback) {
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
                    const imageData = {
                        blob: null,
                        url: response.data.url,
                        attachmentId: attachmentId,
                        ratioType: 'screenshot'
                    };
                    cropperInstance.uploadedImages.push(imageData);
                    cropperInstance.updateGallery();
                }
                if (callback) callback();
            },
            error: () => {
                console.error('Erreur chargement screenshot:', attachmentId);
                if (callback) callback();
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
     * Rafraîchir la liste des soumissions
     */
    SismeGameSubmission.refreshSubmissionsList = function() {
        // Recharger la section développeur
        if (typeof SismeDashboard !== 'undefined') {
            SismeDashboard.refreshSection('developer');
        }
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
     * Charger et recréer les sections de description longue
     */
    SismeGameSubmission.loadSections = function(sections) {
        const $sectionsContainer = $('#game-sections-container');
        $sectionsContainer.empty();
        
        // Créer chaque section
        sections.forEach((sectionData, index) => {
            const sectionHtml = this.createSectionHtml(index, sectionData);
            $sectionsContainer.append(sectionHtml);
            
            // Charger l'image si elle existe
            if (sectionData.image_attachment_id) {
                this.loadSectionImage(index, sectionData.image_attachment_id);
            }
        });
        
        // S'assurer qu'il y a au moins une section
        if (sections.length === 0) {
            const defaultSectionHtml = this.createSectionHtml(0, { title: '', content: '', image_attachment_id: null });
            $sectionsContainer.append(defaultSectionHtml);
        }
    };

    /**
     * Créer le HTML d'une section
     */
    SismeGameSubmission.createSectionHtml = function(index, sectionData = {}) {
        const sectionNumber = index + 1;
        const title = sectionData.title || '';
        const content = sectionData.content || '';
        
        return `
            <div class="sisme-section-item" data-section-index="${index}">
                <div class="sisme-section-item-header">
                    <h5 class="sisme-section-item-title sisme-form-section-title">Section ${sectionNumber} <span style="color: #dc3545;">*</span></h5>
                    ${index > 0 ? `<button type="button" class="sisme-button-orange sisme-button sisme-btn-icon sisme-remove-section" title="Supprimer cette section" data-section-index="${index}">🗑️</button>` : ''}
                </div>
                
                <div class="sisme-section-item-body">
                    <div class="sisme-form-field">
                        <label class="sisme-form-label">Titre de la section <span style="color: #dc3545;">*</span></label>
                        <input type="text" 
                            name="sections[${index}][title]" 
                            class="sisme-form-input section-title-input"
                            placeholder="Ex: Gameplay, Histoire, Caractéristiques..."
                            maxlength="100"
                            value="${title}"
                            required>
                    </div>
                    
                    <div class="sisme-form-field">
                        <label class="sisme-form-label">Contenu de la section <span style="color: #dc3545;">*</span></label>
                        <textarea name="sections[${index}][content]" 
                                class="sisme-form-textarea section-content-textarea"
                                placeholder="Décrivez cette partie de votre jeu... (minimum 20 caractères)"
                                rows="4"
                                required>${content}</textarea>
                    </div>
                    
                    <div class="sisme-form-field sisme-cropper-container">
                        <label class="sisme-form-label">Image de la section (optionnel)</label>
                        <div class="sisme-section-image-upload" data-section-index="${index}">
                            <div class="sisme-upload-area">
                                <input type="file" 
                                    accept="image/*,image/gif" 
                                    class="sisme-section-image-input"
                                    data-section-index="${index}">
                                <div class="sisme-upload-info">
                                    <span class="sisme-upload-icon">🖼️</span>
                                    <span class="sisme-upload-text">Cliquez pour ajouter une image</span>
                                    <span class="sisme-upload-hint">JPG, PNG ou GIF</span>
                                </div>
                            </div>
                            <div class="sisme-section-image-preview" style="display: none;">
                                <img class="sisme-section-preview-img" src="" alt="Aperçu">
                                <button type="button" class="sisme-remove-section-image" title="Supprimer l'image">❌</button>
                                <input type="hidden" name="sections[${index}][image_id]" class="section-image-id">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    };

    /**
     * Charger une image de section depuis un attachment_id
     */
    SismeGameSubmission.loadSectionImage = function(sectionIndex, attachmentId) {
        if (!attachmentId) return;
        
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
                    const $section = $(`.sisme-section-item[data-section-index="${sectionIndex}"]`);
                    const $uploadArea = $section.find('.sisme-upload-area');
                    const $preview = $section.find('.sisme-section-image-preview');
                    const $img = $preview.find('.sisme-section-preview-img');
                    const $hiddenInput = $preview.find('.section-image-id');
                    
                    // Afficher l'image
                    $img.attr('src', response.data.url);
                    $uploadArea.hide();
                    $preview.show();
                    $hiddenInput.val(attachmentId);
                }
            },
            error: () => {
                console.error('Erreur chargement image section:', attachmentId);
            }
        });
    };

    /**
     * Rebinder les événements des sections (après création dynamique)
     */
    SismeGameSubmission.bindSectionEvents = function() {
        // Nettoyer les anciens événements puis rebinder
        $(document).off('change', '.sisme-section-image-input').on('change', '.sisme-section-image-input', function(e) {
            if (window.submissionValidator && typeof window.submissionValidator.handleSectionImageUpload === 'function') {
                window.submissionValidator.handleSectionImageUpload(e);
            }
        });

        $(document).off('click', '.sisme-remove-section-image').on('click', '.sisme-remove-section-image', function(e) {
            e.preventDefault();
            const $sectionUpload = $(this).closest('.sisme-section-image-upload');
            if (window.submissionValidator && typeof window.submissionValidator.removeSectionImage === 'function') {
                window.submissionValidator.removeSectionImage($sectionUpload[0]);
            }
        });

        $(document).off('click', '.sisme-remove-section-btn').on('click', '.sisme-remove-section-btn', function(e) {
            e.preventDefault();
            $(this).closest('.sisme-section-item').remove();
            SismeGameSubmission.reindexSections();
        });

        // Événement ajout de section avec protection contre les doublons
        $(document).off('click', '#add-game-section').on('click', '#add-game-section', function(e) {
            e.preventDefault();
            
            if (!SismeGameSubmission.canAddSection()) {
                alert('Nombre maximum de sections atteint (' + SismeGameSubmission.getMaxSections() + ').');
                return false;
            }
            
            const sectionCount = $('#game-sections-container .sisme-section-item').length;
            const newSectionHtml = SismeGameSubmission.createSectionHtml(sectionCount, {});
            $('#game-sections-container').append(newSectionHtml);
        });
    };

    /**
     * Réindexer les sections après suppression
     */
    SismeGameSubmission.reindexSections = function() {
        $('#game-sections-container .sisme-section-item').each(function(index) {
            const $section = $(this);
            const sectionNumber = index + 1;
            
            // Mettre à jour les attributs et noms
            $section.attr('data-section-index', index);
            $section.find('.sisme-section-item-title').html(`Section ${sectionNumber} <span style="color: #dc3545;">*</span>`);
            $section.find('input[name*="[title]"]').attr('name', `sections[${index}][title]`);
            $section.find('textarea[name*="[content]"]').attr('name', `sections[${index}][content]`);
            $section.find('input[name*="[image_id]"]').attr('name', `sections[${index}][image_id]`);
            $section.find('.sisme-section-image-input').attr('data-section-index', index);
            $section.find('.sisme-section-image-upload').attr('data-section-index', index);
        });
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
     * Créer une révision d'un jeu publié
     */
    SismeGameSubmission.createRevision = function(e) {
        e.preventDefault();
        
        const $button = $(e.currentTarget);
        const submissionId = $button.data('submission-id');
        
        if (!submissionId) {
            this.showFeedback('❌ ID de soumission manquant', 'error');
            return;
        }

        // Afficher la modale de révision
        this.showRevisionModal((revisionReason) => {
            if (revisionReason === null) {
                return; // Utilisateur a annulé
            }

            // Désactiver le bouton pendant la création
            const originalText = $button.text();
            $button.prop('disabled', true).text('⏳ Création...');
            
            // Appel AJAX pour créer la révision
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sisme_create_game_revision',
                    security: this.config.nonce,
                    original_submission_id: submissionId,
                    revision_reason: revisionReason
                },
                success: (response) => {
                    if (response.success) {
                        // Succès : rediriger vers l'éditeur de révision
                        this.showFeedback('✅ Révision créée ! Redirection...', 'success');
                        
                        setTimeout(() => {
                            window.location.hash = 'submit-game?edit=' + response.data.revision_id;
                        }, 1000);
                    } else {
                        // Erreur dans la réponse
                        $button.prop('disabled', false).text(originalText);
                        this.showFeedback('❌ ' + response.data.message, 'error');
                    }
                },
                error: () => {
                    // Erreur réseau
                    $button.prop('disabled', false).text(originalText);
                    this.showFeedback('❌ Erreur réseau lors de la création', 'error');
                }
            });
        });
    };

    /**
     * Afficher la modale de révision
     */
    SismeGameSubmission.showRevisionModal = function(callback) {
        // Créer la modale si elle n'existe pas
        if (!$('#sisme-revision-modal').length) {
            this.createRevisionModal();
        }

        const $modal = $('#sisme-revision-modal');
        const $textarea = $modal.find('.sisme-revision-modal-textarea');
        const $confirmBtn = $modal.find('.sisme-revision-modal-btn-confirm');
        const $cancelBtn = $modal.find('.sisme-revision-modal-btn-cancel');

        // Réinitialiser le textarea
        $textarea.val('');
        $confirmBtn.prop('disabled', true);

        // Vérifier si le textarea a du contenu (optimisé)
        $textarea.off('input').on('input', () => {
            $confirmBtn.prop('disabled', $textarea.val().trim().length === 0);
        });

        // Gestion des boutons (optimisé)
        $confirmBtn.off('click').on('click', () => {
            const reason = $textarea.val().trim();
            if (reason) {
                this.hideRevisionModal();
                callback(reason);
            }
        });

        $cancelBtn.off('click').on('click', () => {
            this.hideRevisionModal();
            callback(null);
        });

        // Fermer avec Escape (optimisé)
        $(document).off('keydown.revisionModal').on('keydown.revisionModal', (e) => {
            if (e.key === 'Escape') {
                this.hideRevisionModal();
                callback(null);
            }
        });

        // Afficher la modale avec requestAnimationFrame pour fluidité
        $modal.addClass('active');
        requestAnimationFrame(() => {
            $textarea.focus();
        });
    };

    /**
     * Créer la modale de révision
     */
    SismeGameSubmission.createRevisionModal = function() {
        const modalHTML = `
            <div id="sisme-revision-modal" class="sisme-revision-modal">
                <div class="sisme-revision-modal-content">
                    <div class="sisme-revision-modal-header">
                        <h3 class="sisme-revision-modal-title">
                            🔄 Créer une révision
                        </h3>
                        <p class="sisme-revision-modal-subtitle">
                            Expliquez les modifications que vous souhaitez apporter à votre jeu
                        </p>
                    </div>
                    <div class="sisme-revision-modal-body">
                        <label class="sisme-revision-modal-label" for="revision-reason">
                            Motif de la révision *
                        </label>
                        <textarea 
                            class="sisme-revision-modal-textarea" 
                            id="revision-reason"
                            placeholder="Décrivez les changements que vous voulez effectuer..."
                            maxlength="500"></textarea>
                        
                        <div class="sisme-revision-modal-examples">
                            <div class="sisme-revision-modal-examples-title">💡 Exemples :</div>
                            <div class="sisme-revision-modal-examples-list">
                                • Ajouter le genre "Action" • Corriger la description • Ajouter des captures d'écran<br>
                                • Modifier le lien Steam • Mettre à jour les informations • Changer la couverture
                            </div>
                        </div>
                    </div>
                    <div class="sisme-revision-modal-actions">
                        <button type="button" class="sisme-revision-modal-btn sisme-revision-modal-btn-cancel">
                            Annuler
                        </button>
                        <button type="button" class="sisme-revision-modal-btn sisme-revision-modal-btn-confirm" disabled>
                            Créer la révision
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHTML);
    };

    /**
     * Cacher la modale de révision
     */
    SismeGameSubmission.hideRevisionModal = function() {
        const $modal = $('#sisme-revision-modal');
        $modal.removeClass('active');
        $(document).off('keydown.revisionModal');
        
        // Nettoyer les événements immédiatement
        $modal.find('.sisme-revision-modal-textarea').off('input');
    };

    /**
     * Gestion des boutons d'archives
     */
    SismeGameSubmission.bindArchiveEvents = function() {
        // Toggle archives list
        $(document).on('click', '[data-action="toggle-archives"]', (e) => {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const submissionId = $button.data('submission-id');
            const $archivesSection = $(`.sisme-archives-section[data-submission-id="${submissionId}"]`);
            const isExpanded = $button.data('state') === 'expanded';
            
            if (isExpanded) {
                $archivesSection.slideUp(300);
                $button.removeClass('active').data('state', 'collapsed');
            } else {
                $archivesSection.slideDown(300);
                $button.addClass('active').data('state', 'expanded');
            }
        });
        
        // Toggle archive details
        $(document).on('click', '[data-action="toggle-archive-details"]', (e) => {
            e.preventDefault();
            const $button = $(e.currentTarget);
            const archiveId = $button.data('archive-id');
            const $detailsSection = $(`.sisme-archive-details[data-archive-id="${archiveId}"]`);
            const isExpanded = $button.data('state') === 'expanded';
            
            if (isExpanded) {
                $detailsSection.slideUp(300);
                $button.removeClass('active').data('state', 'collapsed').text('👁️');
            } else {
                // Charger les détails si pas encore fait
                const $content = $detailsSection.find('.sisme-archive-details-content');
                if ($content.find('.sisme-loading').length) {
                    this.loadArchiveDetails(archiveId, $content);
                }
                
                $detailsSection.slideDown(300);
                $button.addClass('active').data('state', 'expanded').text('🔼');
            }
        });
    };
    
    /**
     * Charger les détails d'une archive
     */
    SismeGameSubmission.loadArchiveDetails = function(archiveId, $container) {
        $.ajax({
            url: this.config.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sisme_get_archive_details',
                security: this.config.nonce,
                archive_id: archiveId
            },
            success: (response) => {
                if (response.success) {
                    $container.html(response.data.html);
                } else {
                    $container.html('<div class="sisme-error">❌ Erreur: ' + response.data.message + '</div>');
                }
            },
            error: () => {
                $container.html('<div class="sisme-error">❌ Erreur de connexion</div>');
            }
        });
    };
    
    // Initialisation automatique
    $(document).ready(() => {
        SismeGameSubmission.init();
    });
    
})(jQuery);