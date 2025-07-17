/**
 * File: /sisme-games-editor/includes/user/user-developer/assets/submission-form-validator.js
 * Validation en temps réel du formulaire de soumission de jeu
 * 
 * RESPONSABILITÉ:
 * - Validation de tous les champs requis
 * - Activation/désactivation du bouton Soumettre
 * - Feedback visuel pour l'utilisateur
 * - Intégration avec le système de crop multi-ratio
 */

class SubmissionFormValidator {
    constructor() {
        this.formId = 'sisme-submit-game-form';
        this.submitButtonId = 'sisme-submit-game-button';
        this.rules = {
            // Champs texte obligatoires
            game_name: {
                required: true,
                minLength: 3,
                maxLength: 100,
                message: 'Le nom du jeu doit faire entre 3 et 100 caractères'
            },
            game_description: {
                required: true,
                minLength: 50,
                maxLength: 180,
                message: 'La description doit faire entre 50 et 180 caractères'
            },
            game_release_date: {
                required: true,
                isDate: true,
                message: 'Date de sortie obligatoire'
            },
            game_trailer: {
                required: true,
                isYouTubeUrl: true,
                message: 'URL YouTube valide obligatoire'
            },
            game_studio_name: {
                required: true,
                minLength: 2,
                maxLength: 50,
                message: 'Nom du studio obligatoire (2-50 caractères)'
            },
            // Images obligatoires
            images: {
                cover_horizontal: {
                    required: true,
                    message: 'Cover horizontale obligatoire'
                },
                cover_vertical: {
                    required: true,
                    message: 'Cover verticale obligatoire'
                },
                screenshots: {
                    required: true,
                    min: 1,
                    max: 9,
                    message: 'Au moins 1 screenshot obligatoire (max 9)'
                }
            }
        };
        
        this.uploadedImages = {
            cover_horizontal: [],
            cover_vertical: [],
            screenshots: []
        };
        
        this.validationState = {};
        this.init();
    }
    
    init() {
        // Attendre que le DOM soit prêt
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.bindEvents());
        } else {
            this.bindEvents();
        }
    }
    
    bindEvents() {
        // Validation des champs texte
        Object.keys(this.rules).forEach(fieldName => {
            if (fieldName !== 'images') {
                const field = document.getElementById(fieldName);
                if (field) {
                    field.addEventListener('input', () => this.validateField(fieldName));
                    field.addEventListener('blur', () => this.validateField(fieldName));
                }
            }
        });
        
        // Écouter les événements des croppers
        this.bindImageEvents();
        
        // Validation initiale
        this.validateForm();
    }
    
    bindImageEvents() {
        // Écouter les événements des croppers multi-ratio
        document.querySelectorAll('[data-simple-cropper]').forEach(container => {
            const ratioType = container.getAttribute('data-ratio-type');
            
            container.addEventListener('imageProcessed', (event) => {
                this.addUploadedImage(ratioType, event.detail);
                this.validateImages();
            });
            
            container.addEventListener('imageRemoved', (event) => {
                this.removeUploadedImage(ratioType, event.detail.index);
                this.validateImages();
            });
        });
    }
    
    validateField(fieldName) {
        const field = document.getElementById(fieldName);
        const rule = this.rules[fieldName];
        const value = field ? field.value.trim() : '';
        
        let isValid = true;
        let errorMessage = '';
        
        // Validation obligatoire
        if (rule.required && !value) {
            isValid = false;
            errorMessage = 'Ce champ est obligatoire';
        }
        
        // Validation longueur minimale
        if (isValid && rule.minLength && value.length < rule.minLength) {
            isValid = false;
            errorMessage = `Minimum ${rule.minLength} caractères`;
        }
        
        // Validation longueur maximale
        if (isValid && rule.maxLength && value.length > rule.maxLength) {
            isValid = false;
            errorMessage = `Maximum ${rule.maxLength} caractères`;
        }
        
        // Validation YouTube URL
        if (isValid && rule.isYouTubeUrl) {
            const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[\w-]+(&[\w=]*)?$/;
            if (!youtubeRegex.test(value)) {
                isValid = false;
                errorMessage = 'URL YouTube non valide';
            }
        }
        
        // Validation date
        if (isValid && rule.isDate) {
            const date = new Date(value);
            if (isNaN(date.getTime())) {
                isValid = false;
                errorMessage = 'Date non valide';
            }
        }
        
        // Stocker l'état de validation
        this.validationState[fieldName] = {
            isValid: isValid,
            message: errorMessage
        };
        
        // Affichage visuel
        this.updateFieldVisual(field, isValid, errorMessage);
        
        // Revalider le formulaire complet
        this.validateForm();
        
        return isValid;
    }
    
    validateImages() {
        const imageRules = this.rules.images;
        
        // Valider cover horizontale
        const coverHorizontalValid = this.uploadedImages.cover_horizontal.length >= 1;
        this.validationState.cover_horizontal = {
            isValid: coverHorizontalValid,
            message: coverHorizontalValid ? '' : imageRules.cover_horizontal.message
        };
        
        // Valider cover verticale
        const coverVerticalValid = this.uploadedImages.cover_vertical.length >= 1;
        this.validationState.cover_vertical = {
            isValid: coverVerticalValid,
            message: coverVerticalValid ? '' : imageRules.cover_vertical.message
        };
        
        // Valider screenshots
        const screenshotCount = this.uploadedImages.screenshots.length;
        const screenshotsValid = screenshotCount >= imageRules.screenshots.min && screenshotCount <= imageRules.screenshots.max;
        this.validationState.screenshots = {
            isValid: screenshotsValid,
            message: screenshotsValid ? '' : imageRules.screenshots.message
        };
        
        // Mettre à jour l'affichage visuel des sections d'images
        this.updateImageSectionVisuals();
        
        // Revalider le formulaire complet
        this.validateForm();
    }
    
    validateForm() {
        const allValid = Object.values(this.validationState).every(state => state.isValid);
        
        // Mettre à jour le bouton
        this.updateSubmitButton(allValid);
        
        // Retourner l'état global
        return allValid;
    }
    
    updateFieldVisual(field, isValid, message) {
        if (!field) return;
        
        const container = field.closest('.sisme-form-field');
        if (!container) return;
        
        // Supprimer les classes existantes
        field.classList.remove('sisme-field-valid', 'sisme-field-invalid');
        
        // Ajouter la classe appropriée
        if (field.value.trim()) {
            field.classList.add(isValid ? 'sisme-field-valid' : 'sisme-field-invalid');
        }
        
        // Gérer le message d'erreur
        let errorElement = container.querySelector('.sisme-field-error');
        if (!isValid && message) {
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'sisme-field-error';
                container.appendChild(errorElement);
            }
            errorElement.textContent = message;
        } else if (errorElement) {
            errorElement.remove();
        }
    }
    
    updateImageSectionVisuals() {
        // Mettre à jour les indicateurs visuels des sections d'images
        const imageTypes = ['cover_horizontal', 'cover_vertical', 'screenshots'];
        
        imageTypes.forEach(type => {
            const container = document.querySelector(`[data-ratio-type="${type}"]`);
            if (container) {
                const isValid = this.validationState[type]?.isValid || false;
                const parentSection = container.closest('.sisme-form-field');
                
                if (parentSection) {
                    const label = parentSection.querySelector('.sisme-form-label');
                    if (label) {
                        label.classList.remove('sisme-label-valid', 'sisme-label-invalid');
                        if (this.uploadedImages[type].length > 0) {
                            label.classList.add(isValid ? 'sisme-label-valid' : 'sisme-label-invalid');
                        }
                    }
                }
            }
        });
    }
    
    updateSubmitButton(isValid) {
        const submitButton = document.getElementById(this.submitButtonId);
        if (!submitButton) return;
        
        submitButton.disabled = !isValid;
        submitButton.classList.toggle('sisme-btn-disabled', !isValid);
        submitButton.classList.toggle('sisme-btn-enabled', isValid);
        
        // Mettre à jour le texte du bouton
        if (isValid) {
            submitButton.innerHTML = '🚀 Soumettre le jeu';
        } else {
            submitButton.innerHTML = '📝 Complétez le formulaire';
        }
    }
    
    addUploadedImage(ratioType, imageData) {
        if (!this.uploadedImages[ratioType]) {
            this.uploadedImages[ratioType] = [];
        }
        
        // Pour les images uniques, remplacer
        if (ratioType === 'cover_horizontal' || ratioType === 'cover_vertical') {
            this.uploadedImages[ratioType] = [imageData];
        } else {
            // Pour les screenshots, ajouter
            this.uploadedImages[ratioType].push(imageData);
        }
        
        console.log(`Image ajoutée (${ratioType}):`, this.uploadedImages[ratioType]);
    }
    
    removeUploadedImage(ratioType, index) {
        if (this.uploadedImages[ratioType] && this.uploadedImages[ratioType][index]) {
            this.uploadedImages[ratioType].splice(index, 1);
            console.log(`Image supprimée (${ratioType}):`, this.uploadedImages[ratioType]);
        }
    }
    
    // Méthodes publiques pour accès externe
    getValidationState() {
        return this.validationState;
    }
    
    getUploadedImages() {
        return this.uploadedImages;
    }
    
    isFormValid() {
        return this.validateForm();
    }
    
    getFormData() {
        const form = document.getElementById(this.formId);
        if (!form) return null;
        
        const formData = new FormData(form);
        const data = {};
        
        // Données texte
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        // Données images
        data.images = this.uploadedImages;
        
        return data;
    }
}

// Export global AVANT l'auto-initialisation
window.SubmissionFormValidator = SubmissionFormValidator;

// Auto-initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Attendre que les croppers soient initialisés
    setTimeout(() => {
        if (!window.submissionValidator) {
            window.submissionValidator = new SubmissionFormValidator();
            console.log('Validation formulaire soumission initialisée');
        }
    }, 100);
});