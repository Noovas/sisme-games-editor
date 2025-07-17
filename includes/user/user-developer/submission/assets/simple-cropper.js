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
        this.init();
    }

    init() {
        this.container.innerHTML = `
            <div>
                <input type="file" id="imageFile_${Date.now()}" accept="image/*" />
                <br><br>
                <div id="imageContainer_${Date.now()}" style="display:none;">
                    <img id="cropImage_${Date.now()}" style="max-width: 100%;" />
                    <br><br>
                    <button type="button" id="cropBtn_${Date.now()}">Crop Image (${this.config.label})</button>
                    <button type="button" id="cancelBtn_${Date.now()}">Annuler</button>
                </div>
                <div id="result_${Date.now()}" style="display:none;">
                    <h4>Résultat :</h4>
                    <img id="resultImage_${Date.now()}" style="max-width: 300px;" />
                    <br>
                    <button type="button" id="changeBtn_${Date.now()}">Changer l'image</button>
                </div>
                <div id="feedback_${Date.now()}"></div>
            </div>
        `;

        this.ids = {
            fileInput: this.container.querySelector('input[type="file"]').id,
            imageContainer: this.container.querySelector('div[id^="imageContainer"]').id,
            cropImage: this.container.querySelector('img[id^="cropImage"]').id,
            cropBtn: this.container.querySelector('button[id^="cropBtn"]').id,
            cancelBtn: this.container.querySelector('button[id^="cancelBtn"]').id,
            result: this.container.querySelector('div[id^="result"]').id,
            resultImage: this.container.querySelector('img[id^="resultImage"]').id,
            changeBtn: this.container.querySelector('button[id^="changeBtn"]').id,
            feedback: this.container.querySelector('div[id^="feedback"]').id
        };

        this.bindEvents();
    }

    bindEvents() {
        const fileInput = document.getElementById(this.ids.fileInput);
        const cropBtn = document.getElementById(this.ids.cropBtn);
        const cancelBtn = document.getElementById(this.ids.cancelBtn);
        const changeBtn = document.getElementById(this.ids.changeBtn);

        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.loadImage(e.target.files[0]);
            }
        });

        cropBtn.addEventListener('click', () => this.cropImage());
        cancelBtn.addEventListener('click', () => this.cancel());
        changeBtn.addEventListener('click', () => this.reset());
    }

    loadImage(file) {
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
                autoCropArea: 0.8
            });
        };
        reader.readAsDataURL(file);
    }

    cropImage() {
        if (!this.cropper) return;

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
        console.log('sismeAjax:', sismeAjax);
        console.log('Blob size:', blob.size);
        console.log('Ratio type:', this.ratioType);
        
        const formData = new FormData();
        formData.append('action', 'sisme_simple_crop_upload');
        formData.append('security', sismeAjax.nonce);
        formData.append('image', blob, `cropped-${this.ratioType}-image.jpg`);
        formData.append('ratio_type', this.ratioType);

        console.log('FormData contents:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + (pair[1] instanceof File ? 'File(' + pair[1].size + ' bytes)' : pair[1]));
        }

        this.showFeedback('Upload en cours...');

        fetch(sismeAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('=== RESPONSE DEBUG ===');
            console.log('Status:', response.status);
            
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON Parse Error:', e);
                    throw new Error('Réponse non-JSON: ' + text);
                }
            });
        })
        .then(data => {
            console.log('=== PARSED DATA ===');
            console.log('Full data:', data);
            
            if (data.success) {
                this.showResult(data.data.url);
                this.showFeedback('Image uploadée avec succès !');
                
                // Trigger custom event avec les données
                const event = new CustomEvent('imageProcessed', {
                    detail: {
                        url: data.data.url,
                        attachmentId: data.data.attachment_id,
                        ratioType: this.ratioType,
                        dimensions: this.config
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
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
        
        document.getElementById(this.ids.fileInput).value = '';
        document.getElementById(this.ids.imageContainer).style.display = 'none';
        document.getElementById(this.ids.result).style.display = 'none';
        document.getElementById(this.ids.feedback).innerHTML = '';
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