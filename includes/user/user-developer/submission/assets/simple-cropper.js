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
        this.uploadedImages = []; // Pour screenshots multiples
        this.maxImages = options.maxImages || this.config.maxImages || 1;
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
                    <button type="button" id="changeBtn_${this.uniqueId}" class="sisme-btn sisme-btn-secondary" style="margin-top: 10px;">
                        Changer l'image
                    </button>
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
            if (this.isProcessing) {
                this.showFeedback('Traitement en cours, veuillez patienter...');
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
            const changeBtn = document.getElementById(this.ids.changeBtn);
            changeBtn.addEventListener('click', () => this.reset());
        }
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
        console.log('=== CROP IMAGE DEBUG ===');
        console.log('Cropper instance:', this.cropper);
        
        if (!this.cropper) {
            console.error('No cropper instance!');
            return;
        }
        
        this.setProcessing(true);

        console.log('Getting cropped canvas...');
        const canvas = this.cropper.getCroppedCanvas({
            width: this.config.width,
            height: this.config.height
        });

        console.log('Canvas:', canvas);
        console.log('Converting to blob...');

        canvas.toBlob((blob) => {
            console.log('Blob created:', blob);
            this.uploadImage(blob);
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
            // Ajouter à la galerie
            this.uploadedImages.push({
                url: data.url,
                attachmentId: data.attachment_id,
                ratioType: this.ratioType
            });
            
            this.updateGallery();
            this.resetCropInterface();
            this.showFeedback(`Image ajoutée ! (${this.uploadedImages.length}/${this.maxImages})`);
        } else {
            // Image unique
            this.showResult(data.url);
            this.showFeedback('Image uploadée avec succès !');
        }
        
        // Trigger custom event
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
        
        if (this.ratioType === 'screenshot') {
            // Pour screenshots multiples, ajouter à la liste
            let currentIds = document.getElementById('screenshots_attachment_ids').value;
            let idsArray = currentIds ? currentIds.split(',') : [];
            idsArray.push(data.attachment_id);
            document.getElementById('screenshots_attachment_ids').value = idsArray.join(',');
        } else {
            // Pour covers, remplacer la valeur
            document.getElementById(this.ratioType + '_attachment_id').value = data.attachment_id;
        }
        this.container.dispatchEvent(event);
    }

    showResult(imageUrl) {
        if (!this.config.multiple) {
            const resultImage = document.getElementById(this.ids.resultImage);
            resultImage.src = imageUrl;
            
            document.getElementById(this.ids.imageContainer).style.display = 'none';
            document.getElementById(this.ids.result).style.display = 'block';
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
                            <img src="${img.url}" style="width: 100%; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;" />
                            <button type="button" class="sisme-remove-btn" 
                                    onclick="window.cropperInstances['${this.uniqueId}'].removeImage(${index})"
                                    style="position: absolute; top: -5px; right: -5px; background: #ff4444; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer;">
                                ×
                            </button>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        // Actualiser le counter dans le label
        const uploadInfo = this.container.querySelector('.sisme-upload-info');
        if (uploadInfo) {
            uploadInfo.innerHTML = `
                ${this.config.emoji} ${this.config.label} (${this.uploadedImages.length}/${this.maxImages})
                <br><small>Minimum 1 image, maximum ${this.maxImages}</small>
            `;
        }
        
        // Désactiver upload si max atteint
        fileInput.disabled = this.shouldDisableUpload();
    }

    shouldDisableUpload() {
        return this.config.multiple && this.uploadedImages.length >= this.maxImages;
    }

    removeImage(index) {
        if (index >= 0 && index < this.uploadedImages.length) {
            this.uploadedImages.splice(index, 1);
            this.updateGallery();
            this.showFeedback(`Image supprimée (${this.uploadedImages.length}/${this.maxImages})`);
            
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

    resetCropInterface() {
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
        
        document.getElementById(this.ids.fileInput).value = '';
        document.getElementById(this.ids.imageContainer).style.display = 'none';
        
        // Ne pas effacer le feedback pour les multiples
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