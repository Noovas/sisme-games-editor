/**
 * File: /sisme-games-editor/includes/user/user-developer/submission/assets/simple-cropper.js
 * SimpleCropper avec support multi-ratio
 * 
 * RESPONSABILITÉ:
 * - Gestion de 3 ratios différents via paramètre
 * - Même fonctionnement que l'original
 * - Upload AJAX identique
 * 
 * DÉPENDANCES:
 * - Cropper.js (CDN)
 * - sismeAjax (localization)
 */

class SimpleCropper {
    constructor(containerId, ratioType = 'cover_horizontal', options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('SimpleCropper: Container non trouvé:', containerId);
            return;
        }

        // Enregistrement global pour la soumission différée
        if (!window.sismeCroppers) window.sismeCroppers = [];
        window.sismeCroppers.push(this);

        // Configuration des ratios
        this.ratios = {
            'cover_horizontal': {
                ratio: 920 / 430,
                width: 920,
                height: 430,
                label: 'Cover Horizontale (920x430)',
                emoji: '📸',
                multiple: false
            },
            'cover_vertical': {
                ratio: 600 / 900,
                width: 600,
                height: 900,
                label: 'Cover Verticale (600x900)',
                emoji: '🖼️',
                multiple: false
            },
            'screenshot': {
                ratio: 1920 / 1080,
                width: 1920,
                height: 1080,
                label: 'Screenshot (1920x1080)',
                emoji: '🎮',
                multiple: true,
                maxImages: 9
            }
        };
        
        this.ratioType = ratioType;
        this.config = this.ratios[ratioType] || this.ratios['cover_horizontal'];
        this.cropper = null;
        this.uniqueId = 'cropper_' + containerId + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        this.isProcessing = false;
        this.uploadedImages = []; 
        this.maxImages = options.maxImages || this.config.maxImages || (this.ratioType === 'screenshot' ? 5 : 1);
        this.init();
    }

    init() {
        const isMultiple = this.config.multiple;
        const counterText = isMultiple ? ` (${this.uploadedImages.length}/${this.maxImages})` : '';
        
        this.container.innerHTML = `
            <div class="sisme-cropper-container">
                ${isMultiple ? `<div id="gallery_${this.uniqueId}" class="sisme-image-gallery"></div>` : ''}
                <div class="sisme-upload-section">
                    <input type="file" id="imageFile_${this.uniqueId}" accept="image/*" 
                           ${this.shouldDisableUpload() ? 'disabled' : ''} />
                    <div class="sisme-upload-info">
                        ${this.config.emoji} ${this.config.label}${counterText}
                        ${isMultiple ? '<br><small>Minimum 1 image, maximum ' + this.maxImages + '</small>' : ''}
                    </div>
                </div>
                <div id="imageContainer_${this.uniqueId}" style="display:none;">
                    <img id="cropImage_${this.uniqueId}" style="max-width: 100%; max-height: 400px;" />
                    <div class="sisme-crop-actions" style="margin-top: 10px;">
                        <button type="button" id="cropBtn_${this.uniqueId}" class="sisme-btn sisme-btn-primary">
                            ${this.config.emoji} Découper l'image
                        </button>
                        <button type="button" id="cancelBtn_${this.uniqueId}" class="sisme-btn sisme-btn-secondary">
                            Annuler
                        </button>
                    </div>
                </div>
                ${!isMultiple ? `
                <div id="result_${this.uniqueId}" style="display:none;">
                    <div class="sisme-result-preview">
                        <img id="resultImage_${this.uniqueId}" style="max-width: 300px; border: 1px solid #ddd; border-radius: 4px;" />
                    </div>
                </div>
                ` : ''}
                <div id="feedback_${this.uniqueId}" class="sisme-feedback"></div>
            </div>
        `;

        this.ids = {
            fileInput: `imageFile_${this.uniqueId}`,
            imageContainer: `imageContainer_${this.uniqueId}`,
            cropImage: `cropImage_${this.uniqueId}`,
            cropBtn: `cropBtn_${this.uniqueId}`,
            cancelBtn: `cancelBtn_${this.uniqueId}`,
            result: `result_${this.uniqueId}`,
            resultImage: `resultImage_${this.uniqueId}`,
            changeBtn: `changeBtn_${this.uniqueId}`,
            feedback: `feedback_${this.uniqueId}`,
            gallery: `gallery_${this.uniqueId}`
        };

        this.bindEvents();
        this.updateGallery();
    }

    bindEvents() {
        const fileInput = document.getElementById(this.ids.fileInput);
        const cropBtn = document.getElementById(this.ids.cropBtn);
        const cancelBtn = document.getElementById(this.ids.cancelBtn);
        
        fileInput.addEventListener('change', (e) => {
            if (this.config.multiple && this.uploadedImages.length >= this.maxImages) {
                this.showFeedback(`Limite atteinte ! Maximum ${this.maxImages} screenshots.`);
                e.target.value = ''; 
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            
            if (this.isProcessing) {
                this.showFeedback('Traitement en cours, veuillez patienter...');
                e.target.value = '';
                return;
            }
            
            if (e.target.files.length > 0) {
                this.loadImage(e.target.files[0]);
            }
        });

        cropBtn.addEventListener('click', () => {
            if (this.isProcessing) {
                this.showFeedback('Traitement en cours, veuillez patienter...');
                return;
            }
            this.cropImage();
        });
        
        cancelBtn.addEventListener('click', () => this.cancel());
        
        // Pour images uniques seulement
        if (!this.config.multiple) {
            const removeBtn = document.getElementById(this.ids.removeBtn);
            if (removeBtn) {
                removeBtn.addEventListener('click', () => this.removeImageUnique());
            }
        }
    }

    /**
     * Pour images uniques : supprime l'image, reset l'UI et le champ caché d'ID d'attachment
     */
    removeImageUnique() {
        // Nouveau comportement : vider l'aperçu, réafficher le cropper, masquer l'aperçu, pas de suppression média
        console.log('[SimpleCropper] removeImageUnique (UI only) for', this.ratioType);
        // Vider le champ caché d'ID
        const hiddenInput = document.getElementById(this.ratioType + '_attachment_id');
        if (hiddenInput) hiddenInput.value = '';
        // Vider l'aperçu
        const resultImage = document.getElementById(this.ids.resultImage);
        if (resultImage) resultImage.src = '';
        // Réinitialiser le blob local
        this.croppedBlob = null;
        // Réinitialiser l'input file
        const fileInput = document.getElementById(this.ids.fileInput);
        if (fileInput) fileInput.value = '';
        // Réafficher le cropper (imageContainer), masquer l'aperçu (result)
        const imageContainer = document.getElementById(this.ids.imageContainer);
        if (imageContainer) imageContainer.style.display = 'block';
        const resultContainer = document.getElementById(this.ids.result);
        if (resultContainer) resultContainer.style.display = 'none';
        // Détruire l'instance cropper si présente
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
        this.showFeedback('Image retirée du formulaire. Elle sera automatiquement supprimée du média lors de la sauvegarde.');
        // Event custom pour notifier la suppression UI
        const event = new CustomEvent('imageRemoved', {
            detail: {
                cropperId: this.uniqueId,
                ratioType: this.ratioType
            }
        });
        this.container.dispatchEvent(event);
    }

    loadImage(file) {
        console.log('=== LOAD IMAGE DEBUG ===');
        console.log('Ratio type:', this.ratioType);
        console.log('All IDs:', this.ids);
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const cropImage = document.getElementById(this.ids.cropImage);
            const imageContainer = document.getElementById(this.ids.imageContainer);
            const resultContainer = document.getElementById(this.ids.result);
            
            console.log('Elements check:');
            console.log('- cropImage:', cropImage);
            console.log('- imageContainer:', imageContainer);
            console.log('- resultContainer:', resultContainer);
            
            if (!cropImage) {
                console.error('ERREUR: Element cropImage non trouvé!', this.ids.cropImage);
                return;
            }
            
            if (!imageContainer) {
                console.error('ERREUR: Element imageContainer non trouvé!', this.ids.imageContainer);
                return;
            }
            
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
            
            // Afficher l'image d'abord
            cropImage.src = e.target.result;
            imageContainer.style.display = 'block';
            
            if (resultContainer) {
                resultContainer.style.display = 'none';
            }
            
            // Forcer l'initialisation avec un délai
            setTimeout(() => {
                console.log('Force init Cropper after timeout');
                try {
                    this.cropper = new Cropper(cropImage, {
                        aspectRatio: this.config.ratio,
                        viewMode: 2,
                        autoCropArea: 0.8,
                        ready: () => {
                            console.log('Cropper ready!');
                        }
                    });
                    console.log('Cropper instance created:', this.cropper);
                } catch (error) {
                    console.error('Erreur Cropper:', error);
                }
            }, 200);
        };
        reader.readAsDataURL(file);
    }

    cropImage() {
        if (!this.cropper) {
            return;
        }
        this.setProcessing(true);
        const canvas = this.cropper.getCroppedCanvas({
            width: this.config.width,
            height: this.config.height
        });
        canvas.toBlob((blob) => {
            console.log('Blob created pour', this.ratioType, ':', blob);
            
            if (this.config.multiple) {
                // Mode screenshots multiples : ajouter à la collection locale
                const imageData = {
                    blob: blob,
                    url: URL.createObjectURL(blob),
                    timestamp: Date.now(),
                    ratioType: this.ratioType
                };
                
                this.uploadedImages.push(imageData);
                
                this.resetCropInterface();
                this.showFeedback(`Screenshot ajouté ! (${this.uploadedImages.length}/${this.maxImages})`);
                
                const fileInput = document.getElementById(this.ids.fileInput);
                if (fileInput) {
                    fileInput.disabled = this.shouldDisableUpload();
                    if (this.shouldDisableUpload()) {
                        this.showFeedback(`Limite atteinte ! Maximum ${this.maxImages} screenshots.`);
                    }
                }

                // Trigger custom event
                const event = new CustomEvent('imageProcessed', {
                    detail: {
                        url: imageData.url,
                        ratioType: this.ratioType,
                        isMultiple: true,
                        totalImages: this.uploadedImages.length
                    }
                });
                this.container.dispatchEvent(event);
                this.updateGallery();
                
            } else {
                // Mode image unique : stocker le blob pour upload ultérieur
                this.croppedBlob = blob;
                this.showFeedback('Image prête à être uploadée (sera envoyée lors de la sauvegarde).');
                
                // Afficher l'aperçu pour les images uniques
                const resultImage = document.getElementById(this.ids.resultImage);
                if (resultImage) {
                    resultImage.src = URL.createObjectURL(blob);
                }
                
                const imageContainer = document.getElementById(this.ids.imageContainer);
                if (imageContainer) imageContainer.style.display = 'none';
                const resultContainer = document.getElementById(this.ids.result);
                if (resultContainer) {
                    resultContainer.style.display = 'block';
                }
            }
            
            this.setProcessing(false);
        }, 'image/jpeg', 0.9);
    }

    uploadImage(blob) {
        console.log('=== DEBUG UPLOAD ===');
        console.log('Instance ID:', this.uniqueId);
        console.log('Ratio type:', this.ratioType);
        
        const formData = new FormData();
        formData.append('action', 'sisme_simple_crop_upload');
        formData.append('security', sismeAjax.nonce);
        formData.append('image', blob, `cropped-${this.ratioType}-image.jpg`);
        formData.append('ratio_type', this.ratioType);

        this.showFeedback('Upload en cours...');

        fetch(sismeAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    throw new Error('Réponse non-JSON: ' + text);
                }
            });
        })
        .then(data => {
            if (data.success) {
                this.handleUploadSuccess(data.data);
            } else {
                const message = data.data ? data.data.message : data.message || 'Erreur inconnue';
                this.showFeedback('Erreur serveur: ' + message);
            }
        })
        .catch(error => {
            console.error('=== CATCH ERROR ===');
            console.error('Error:', error);
            this.showFeedback('Erreur de connexion: ' + error.message);
        })
        .finally(() => {
            this.setProcessing(false);
        });
    }

    handleUploadSuccess(data) {
        if (this.config.multiple) {
            console.warn('handleUploadSuccess appelé pour un multiple, cela ne devrait pas arriver');
            return;
        }
        this.showResult(data.url);
        this.showFeedback('Image uploadée avec succès !');
        document.getElementById(this.ratioType + '_attachment_id').value = data.attachment_id;

        const event = new CustomEvent('imageProcessed', {
            detail: {
                url: data.url,
                attachmentId: data.attachment_id,
                ratioType: this.ratioType,
                dimensions: this.config,
                cropperId: this.uniqueId,
                isMultiple: this.config.multiple,
                allImages: this.config.multiple ? this.uploadedImages : [{ url: data.url, attachmentId: data.attachment_id }]
            }
        });
        this.container.dispatchEvent(event);
    }

    /**
     * Sauvegarde automatique du brouillon après upload d'image
     */
    autoSaveAfterImageUpload(attachmentId) {
        // Récupérer l'ID de soumission (champ caché ou global)
        let submissionIdInput = document.querySelector('input[name="submission_id"]');
        let submission_id = submissionIdInput ? submissionIdInput.value : (window.sismeSubmissionId || null);
        if (!submission_id) {
            console.warn('[SimpleCropper] Impossible de trouver submission_id pour auto-save après upload image.');
            return;
        }

        // Préparer les données covers
        let covers = {};
        let horizontal = document.getElementById('cover_horizontal_attachment_id');
        let vertical = document.getElementById('cover_vertical_attachment_id');
        if (horizontal && horizontal.value) covers['horizontal'] = horizontal.value;
        if (vertical && vertical.value) covers['vertical'] = vertical.value;

        // Construire le FormData
        let formData = new FormData();
        formData.append('action', 'sisme_save_draft_submission');
        formData.append('security', sismeAjax.nonce);
        formData.append('submission_id', submission_id);
        formData.append('covers[horizontal]', covers['horizontal'] || '');
        formData.append('covers[vertical]', covers['vertical'] || '');

        // Ajouter d'autres champs si besoin (optionnel)

        fetch(sismeAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('[SimpleCropper] Auto-save après upload image : OK');
            } else {
                console.warn('[SimpleCropper] Auto-save après upload image : erreur', data);
            }
        })
        .catch(e => {
            console.warn('[SimpleCropper] Auto-save après upload image : erreur AJAX', e);
        });
    }

    showResult(imageUrl) {
        if (!this.config.multiple) {
            const resultImage = document.getElementById(this.ids.resultImage);
            // Ajouter un paramètre de cache-busting à l'URL
            const cacheBustedUrl = this.addCacheBustingParam(imageUrl);
            resultImage.src = cacheBustedUrl;
            document.getElementById(this.ids.imageContainer).style.display = 'none';
            document.getElementById(this.ids.result).style.display = 'block';
            // (Re)lier le bouton supprimer par classe (robuste)
            const removeBtn = this.container.querySelector('.sisme-btn-danger');
            if (removeBtn) {
                removeBtn.onclick = () => this.removeImageUnique();
            }
        }
    }

    updateGallery() {
        if (!this.config.multiple) return;
        
        const gallery = document.getElementById(this.ids.gallery);
        const fileInput = document.getElementById(this.ids.fileInput);
        
        if (this.uploadedImages.length === 0) {
            gallery.innerHTML = '<p style="color: #666; font-style: italic;">Aucune image ajoutée</p>';
        } else {
            gallery.innerHTML = `
                <div class="sisme-gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px; margin-bottom: 15px;">
                    ${this.uploadedImages.map((img, index) => `
                        <div class="sisme-gallery-item" style="position: relative;">
                            <img src="${img.url}" style="width: 100%; height: 80px; object-fit: cover; border-radius: 4px;" />
                            <button type="button" onclick="window.cropperInstances['${this.uniqueId}'].removeImageFromGallery(${index})" 
                                    style="position: absolute; top: 5px; right: 5px; background: rgba(255,0,0,0.8); color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 12px;">
                                ×
                            </button>
                            <div style="position: absolute; bottom: 2px; left: 2px; background: rgba(0,0,0,0.7); color: white; padding: 2px 4px; font-size: 10px; border-radius: 2px;">
                                ${index + 1}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        // Désactiver l'upload si limite atteinte
        if (fileInput) {
            const shouldDisable = this.shouldDisableUpload();
            fileInput.disabled = shouldDisable;
            
            if (shouldDisable) {
                fileInput.style.opacity = '0.5';
                fileInput.style.cursor = 'not-allowed';
            } else {
                fileInput.style.opacity = '1';
                fileInput.style.cursor = 'pointer';
            }
        }
        
        // Mettre à jour le compteur dans le label
        this.updateCounterDisplay();
    }

    updateCounterDisplay() {
        if (!this.config.multiple) return;
        
        const counterText = ` (${this.uploadedImages.length}/${this.maxImages})`;
        const infoDiv = this.container.querySelector('.sisme-upload-info');
        if (infoDiv) {
            // Remplacer le compteur dans le texte
            infoDiv.innerHTML = infoDiv.innerHTML.replace(/\(\d+\/\d+\)/, counterText);
        }
    }

    shouldDisableUpload() {
        if (!this.config.multiple) return false;
        return this.uploadedImages.length >= this.maxImages;
    }

    removeImage(index) {
        if (index >= 0 && index < this.uploadedImages.length) {
            this.uploadedImages.splice(index, 1);
            this.updateGallery();
            this.showFeedback(`Image supprimée (${this.uploadedImages.length}/${this.maxImages}). L'image sera définitivement supprimée lors de la sauvegarde.`);
            
            const event = new CustomEvent('imageRemoved', {
                detail: {
                    index: index,
                    remaining: this.uploadedImages.length,
                    cropperId: this.uniqueId
                }
            });
            this.container.dispatchEvent(event);
        }
    }

    removeImageFromGallery(index) {
        if (index >= 0 && index < this.uploadedImages.length) {
            const removedImage = this.uploadedImages[index];
            
            // Libérer l'URL blob pour éviter les fuites mémoire
            if (removedImage.url && removedImage.url.startsWith('blob:')) {
                URL.revokeObjectURL(removedImage.url);
            }
            
            // Supprimer de la collection
            this.uploadedImages.splice(index, 1);
            
            this.updateGallery();
            this.showFeedback(`Image supprimée ! (${this.uploadedImages.length}/${this.maxImages})`);
            
            // Trigger update event
            const event = new CustomEvent('imageRemoved', {
                detail: {
                    index: index,
                    remaining: this.uploadedImages.length,
                    cropperId: this.uniqueId
                }
            });
            this.container.dispatchEvent(event);
        }
    }

    updateScreenshotsField() {
        if (this.ratioType !== 'screenshot') return;
        
        const idsField = document.getElementById('screenshots_attachment_ids');
        if (idsField) {
            const ids = this.uploadedImages.map(img => img.attachmentId).filter(id => id);
            idsField.value = ids.join(',');
        }
    }

    resetCropInterface() {
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
        
        document.getElementById(this.ids.fileInput).value = '';
        document.getElementById(this.ids.imageContainer).style.display = 'none';
        

        if (!this.config.multiple) {
            document.getElementById(this.ids.feedback).innerHTML = '';
        }
    }

    cancel() {
        this.reset();
    }

    reset() {
        this.setProcessing(false);
        
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
        
        document.getElementById(this.ids.fileInput).value = '';
        document.getElementById(this.ids.imageContainer).style.display = 'none';
        document.getElementById(this.ids.result).style.display = 'none';
        document.getElementById(this.ids.feedback).innerHTML = '';
    }

    setProcessing(processing) {
        this.isProcessing = processing;
        const fileInput = document.getElementById(this.ids.fileInput);
        const cropBtn = document.getElementById(this.ids.cropBtn);
        
        if (processing) {
            fileInput.disabled = true;
            cropBtn.disabled = true;
            this.container.style.opacity = '0.7';
        } else {
            fileInput.disabled = false;
            cropBtn.disabled = false;
            this.container.style.opacity = '1';
        }
    }

    showFeedback(message) {
        document.getElementById(this.ids.feedback).innerHTML = '<p>' + message + '</p>';
    }
    
    /**
     * Ajoute un paramètre de cache-busting à une URL d'image
     * @param {string} url - L'URL de l'image
     * @return {string} - L'URL avec le paramètre de cache-busting
     */
    addCacheBustingParam(url) {
        if (!url) return url;
        
        const separator = url.includes('?') ? '&' : '?';
        return `${url}${separator}v=${Date.now()}`;
    }

    displayExistingImage(imageUrl) {
        // Affiche l'image dans le bloc résultat standard avec les boutons d'action
        const resultContainer = document.getElementById(this.ids.result);
        const resultImage = document.getElementById(this.ids.resultImage);
        if (resultContainer && resultImage) {
            // Ajouter un paramètre de cache-busting à l'URL
            const cacheBustedUrl = this.addCacheBustingParam(imageUrl);
            resultImage.src = cacheBustedUrl;
            document.getElementById(this.ids.imageContainer).style.display = 'none';
            resultContainer.style.display = 'block';
            // (Re)lier le bouton supprimer par classe (robuste)
            const removeBtn = this.container.querySelector('.sisme-btn-danger');
            if (removeBtn) {
                removeBtn.onclick = () => this.removeImageUnique();
            }
        } else {
            console.warn('Conteneur résultat ou image non trouvé pour', this.ids.result, this.ids.resultImage);
        }
    }
}

// Auto-initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Registre global pour les instances
    window.cropperInstances = window.cropperInstances || {};
    
    function initWhenReady() {
        if (typeof Cropper !== 'undefined') {
            document.querySelectorAll('[data-simple-cropper]').forEach(element => {
                const containerId = element.id || element.getAttribute('data-simple-cropper');
                const ratioType = element.getAttribute('data-ratio-type') || 'cover_horizontal';
                const maxImages = element.getAttribute('data-max-images') || null;
                
                if (containerId) {
                    const options = {};
                    if (maxImages) options.maxImages = parseInt(maxImages);
                    
                    const instance = new SimpleCropper(containerId, ratioType, options);
                    window.cropperInstances[instance.uniqueId] = instance;
                }
            });
        } else {
            setTimeout(initWhenReady, 100);
        }
    }
    
    initWhenReady();
});

window.SimpleCropper = SimpleCropper;