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
        
        this.isInitialized = true;
        this.log('Module Game Submission initialisé');

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
        
        this.log('Chargement soumission pour édition:', submissionId);
        this.config.currentSubmissionId = submissionId;
        this.config.isEditMode = true;
        
        // Mettre à jour l'interface - mode édition
        this.updateFormUIForEdit(submissionId);
        
        // Charger les données
        this.loadSubmissionData(submissionId)
            .then(() => {
                this.log('Soumission chargée avec succès');
            })
            .catch((error) => {
                this.log('Erreur chargement soumission:', error);
                this.showFeedback('Erreur lors du chargement de la soumission', 'error');
            });
    };

    /**
     * Nettoyer le formulaire pour nouveau jeu
     */
    SismeGameSubmission.clearFormForNewGame = function() {
        // Éviter de nettoyer si on est déjà en mode nouveau
        if (!this.config.currentSubmissionId && !this.config.isEditMode) {
            return;
        }
        
        this.log('Nettoyage formulaire pour nouveau jeu');
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
     * Sauvegarder brouillon
     */
    SismeGameSubmission.saveDraft = async function(e) {

        if (e) e.preventDefault();
        if (this.isDraftSaving) return;

        // Vérification: refuser la sauvegarde si la soumission n'est pas un draft
        if (this.config.currentSubmissionId && this.config.submissionStatus && this.config.submissionStatus !== 'draft') {
            this.showFeedback('❌ Impossible de sauvegarder : la soumission n\'est plus un brouillon.', 'error');
            return;
        }

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
        // Lancer le processus de sauvegarde
        (async () => {
            try {
                const croppers = window.sismeCroppers || [];
                // Upload toutes les images croppées
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
                // Screenshots multiples à gérer ici si besoin
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
                }

                await this.uploadSectionImages();

                // Collecte des données APRÈS que tous les uploads soient terminés
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
                // Sauvegarde AJAX du formulaire
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
                    if (typeof window.reloadDashboardSubmitionDatas === 'function') {
                        window.reloadDashboardSubmitionDatas();
                    }
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
    
    SismeGameSubmission.submitForReview = function(e) {
        if (e) e.preventDefault();
        
        if (this.isSubmitting) {
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
        this.isSubmitting = true;
    };

    /**
     * Version silencieuse de saveDraft pour la modale
     * Sauvegarde sans afficher de feedback (utilisée par la modale)
     */
    SismeGameSubmission.saveDraftSilently = function() {
        return new Promise(async (resolve, reject) => {
            if (this.isDraftSaving) {
                resolve();
                return;
            }
            
            this.isDraftSaving = true;
            
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
                    
                    // Recharger dashboard
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
                this.isDraftSaving = false;
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
        this.isSubmitting = false;
    };

    /**
     * Liaison des événements modale
     */
    SismeGameSubmission.bindModalEvents = function() {
        // Écouter les événements de fermeture de modale
        $(document).on('sisme:modal:success', () => {
            this.onModalSuccess();
        });
        
        $(document).on('sisme:modal:error', () => {
            this.onModalError();
        });
        
        // Alternative polling pour vérifier si la modale est fermée
        this.checkModalStatus = setInterval(() => {
            if (this.isSubmitting && window.sismeSubmissionModal && !window.sismeSubmissionModal.isOpen) {
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
     * Log de débogage
     */
    SismeGameSubmission.log = function(message, data = null) {
        if (console && console.log) {
            console.log('[GameSubmission]', message, data || '');
        }
    };
    
    // Initialisation automatique
    $(document).ready(() => {
        SismeGameSubmission.init();
    });
    
})(jQuery);