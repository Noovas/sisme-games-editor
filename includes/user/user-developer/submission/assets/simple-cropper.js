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
    constructor(containerId, ratioType = 'cover_horizontal') {
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
                label: 'Cover Horizontale (920x430)'
            },
            'cover_vertical': {
                ratio: 600 / 900,
                width: 600,
                height: 900,
                label: 'Cover Verticale (600x900)'
            },
            'screenshot': {
                ratio: 1920 / 1080,
                width: 1920,
                height: 1080,
                label: 'Capture d\'écran (1920x1080)'
            }
        };
        
        this.ratioType = ratioType;
        this.config = this.ratios[ratioType] || this.ratios['cover_horizontal'];
        this.cropper = null;
        this.uniqueId = 'cropper_' + containerId + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        this.isProcessing = false;
        this.init();
    }

    init() {
        this.container.innerHTML = `
            <div>
                <input type="file" id="imageFile_${this.uniqueId}" accept="image/*" />
                <br><br>
                <div id="imageContainer_${this.uniqueId}" style="display:none;">
                    <img id="cropImage_${this.uniqueId}" style="max-width: 100%;" />
                    <br><br>
                    <button type="button" id="cropBtn_${this.uniqueId}">Crop Image (${this.config.label})</button>
                    <button type="button" id="cancelBtn_${this.uniqueId}">Annuler</button>
                </div>
                <div id="result_${this.uniqueId}" style="display:none;">
                    <h4>Résultat :</h4>
                    <img id="resultImage_${this.uniqueId}" style="max-width: 300px;" />
                    <br>
                    <button type="button" id="changeBtn_${this.uniqueId}">Changer l'image</button>
                </div>
                <div id="feedback_${this.uniqueId}"></div>
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
            feedback: `feedback_${this.uniqueId}`
        };

        this.bindEvents();
    }

    bindEvents() {
        const fileInput = document.getElementById(this.ids.fileInput);
        const cropBtn = document.getElementById(this.ids.cropBtn);
        const cancelBtn = document.getElementById(this.ids.cancelBtn);
        const changeBtn = document.getElementById(this.ids.changeBtn);

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
        changeBtn.addEventListener('click', () => this.reset());
    }

    loadImage(file) {
        this.setProcessing(true);
        
        const reader = new FileReader();
        reader.onload = (e) => {
            const cropImage = document.getElementById(this.ids.cropImage);
            cropImage.src = e.target.result;
            
            document.getElementById(this.ids.imageContainer).style.display = 'block';
            document.getElementById(this.ids.result).style.display = 'none';
            
            if (this.cropper) {
                this.cropper.destroy();
            }
            
            this.cropper = new Cropper(cropImage, {
                aspectRatio: this.config.ratio,
                viewMode: 2,
                autoCropArea: 0.8,
                ready: () => {
                    this.setProcessing(false);
                }
            });
        };
        reader.readAsDataURL(file);
    }

    cropImage() {
        if (!this.cropper) return;
        
        this.setProcessing(true);

        const canvas = this.cropper.getCroppedCanvas({
            width: this.config.width,
            height: this.config.height
        });

        canvas.toBlob((blob) => {
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
                this.showResult(data.data.url);
                this.showFeedback('Image uploadée avec succès !');
                
                // Trigger custom event
                const event = new CustomEvent('imageProcessed', {
                    detail: {
                        url: data.data.url,
                        attachmentId: data.data.attachment_id,
                        ratioType: this.ratioType,
                        dimensions: this.config,
                        cropperId: this.uniqueId
                    }
                });
                this.container.dispatchEvent(event);
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

    showResult(imageUrl) {
        const resultImage = document.getElementById(this.ids.resultImage);
        resultImage.src = imageUrl;
        
        document.getElementById(this.ids.imageContainer).style.display = 'none';
        document.getElementById(this.ids.result).style.display = 'block';
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
    function initWhenReady() {
        if (typeof Cropper !== 'undefined') {
            document.querySelectorAll('[data-simple-cropper]').forEach(element => {
                const containerId = element.id || element.getAttribute('data-simple-cropper');
                const ratioType = element.getAttribute('data-ratio-type') || 'cover_horizontal';
                if (containerId) {
                    new SimpleCropper(containerId, ratioType);
                }
            });
        } else {
            setTimeout(initWhenReady, 100);
        }
    }
    
    initWhenReady();
});

window.SimpleCropper = SimpleCropper;