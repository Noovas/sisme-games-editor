/**
 * File: /sisme-games-editor/includes/user/user-developer/submission/assets/simple-cropper.js
 * SimpleCropper isolé pour réutilisation
 */

class SimpleCropper {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error('SimpleCropper: Container non trouvé:', containerId);
            return;
        }
        
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
                    <button type="button" id="cropBtn_${Date.now()}">Crop Image (920x430)</button>
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

        // Stocker les IDs uniques
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
            
            // Initialiser Cropper.js
            if (this.cropper) {
                this.cropper.destroy();
            }
            
            this.cropper = new Cropper(cropImage, {
                aspectRatio: 920 / 430,
                viewMode: 2,
                autoCropArea: 0.8
            });
        };
        reader.readAsDataURL(file);
    }

    cropImage() {
        if (!this.cropper) return;

        const canvas = this.cropper.getCroppedCanvas({
            width: 920,
            height: 430
        });

        canvas.toBlob((blob) => {
            this.uploadImage(blob);
        }, 'image/jpeg', 0.9);
    }

    uploadImage(blob) {
        // Vérifier que sismeAjax existe
        if (typeof sismeAjax === 'undefined') {
            this.showFeedback('Erreur: Configuration AJAX manquante');
            return;
        }

        const formData = new FormData();
        formData.append('action', 'sisme_simple_crop_upload');
        formData.append('security', sismeAjax.nonce);
        formData.append('image', blob, 'cropped-image.jpg');

        this.showFeedback('Upload en cours...');

        fetch(sismeAjax.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showResult(data.data.url);
                this.showFeedback('Image uploadée avec succès !');
            } else {
                this.showFeedback('Erreur: ' + (data.data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Erreur upload:', error);
            this.showFeedback('Erreur de connexion');
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

// Auto-initialisation pour les éléments avec data-attribute
document.addEventListener('DOMContentLoaded', function() {
    // Attendre que Cropper.js soit chargé
    function initWhenReady() {
        if (typeof Cropper !== 'undefined') {
            document.querySelectorAll('[data-simple-cropper]').forEach(element => {
                const containerId = element.id || element.getAttribute('data-simple-cropper');
                if (containerId) {
                    new SimpleCropper(containerId);
                }
            });
        } else {
            setTimeout(initWhenReady, 100);
        }
    }
    
    initWhenReady();
});

// Export pour usage global
window.SimpleCropper = SimpleCropper;