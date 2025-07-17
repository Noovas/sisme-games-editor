/**
 * File: simple-cropper.js
 * Version minimaliste du crop - Juste pour tester
 */

class SimpleCropper {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.cropper = null;
        this.init();
    }

    init() {
        this.container.innerHTML = `
            <div>
                <input type="file" id="imageFile" accept="image/*" />
                <br><br>
                <div id="imageContainer" style="display:none;">
                    <img id="cropImage" style="max-width: 100%;" />
                    <br><br>
                    <button type="button" id="cropBtn">Crop Image (920x430)</button>
                    <button type="button" id="cancelBtn">Annuler</button>
                </div>
                <div id="result" style="display:none;">
                    <h4>Résultat :</h4>
                    <img id="resultImage" style="max-width: 300px;" />
                    <br>
                    <button type="button" id="changeBtn">Changer l'image</button>
                </div>
                <div id="feedback"></div>
            </div>
        `;

        this.bindEvents();
    }

    bindEvents() {
        const fileInput = document.getElementById('imageFile');
        const cropBtn = document.getElementById('cropBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const changeBtn = document.getElementById('changeBtn');

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
            const cropImage = document.getElementById('cropImage');
            cropImage.src = e.target.result;
            
            document.getElementById('imageContainer').style.display = 'block';
            document.getElementById('result').style.display = 'none';
            
            // Initialiser Cropper.js
            if (this.cropper) {
                this.cropper.destroy();
            }
            
            this.cropper = new Cropper(cropImage, {
                aspectRatio: 920 / 430, // Ratio pour cover horizontale
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
        const formData = new FormData();
        formData.append('action', 'sisme_simple_crop_upload');
        formData.append('security', window.sismeAjax.nonce);
        formData.append('image', blob, 'cropped-image.jpg');

        this.showFeedback('Upload en cours...');

        fetch(window.sismeAjax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.showResult(data.data.url);
                this.showFeedback('Image uploadée avec succès !');
            } else {
                this.showFeedback('Erreur: ' + data.data.message);
            }
        })
        .catch(error => {
            this.showFeedback('Erreur de connexion');
        });
    }

    showResult(imageUrl) {
        const resultImage = document.getElementById('resultImage');
        resultImage.src = imageUrl;
        
        document.getElementById('imageContainer').style.display = 'none';
        document.getElementById('result').style.display = 'block';
    }

    cancel() {
        this.reset();
    }

    reset() {
        if (this.cropper) {
            this.cropper.destroy();
            this.cropper = null;
        }
        
        document.getElementById('imageFile').value = '';
        document.getElementById('imageContainer').style.display = 'none';
        document.getElementById('result').style.display = 'none';
        document.getElementById('feedback').innerHTML = '';
    }

    showFeedback(message) {
        document.getElementById('feedback').innerHTML = '<p>' + message + '</p>';
    }
}