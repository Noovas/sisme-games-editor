/**
 * File: /sisme-games-editor/includes/user/user-developer/assets/submission-form-validator.js
 * Validation en temps réel du formulaire de soumission de jeu
 * 
 * RESPONSABILITÉ:
 * - Validation de tous les champs requis
 * - Activation/désactivation du bouton Soumettre
 * - Feedback visuel pour l'utilisateur
 * - Intégration avec le système de crop multi-ratio
 * 
 * DÉPENDANCES:
 * - jQuery
 * - simple-cropper.js (système de crop multi-ratio)
 * - Cropper.js (CDN)
 */

class SubmissionFormValidator {
    constructor() {
        this.formId = 'sisme-submit-game-form';
        this.submitButtonId = 'sisme-submit-game-button';
        this.rules = {
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
            game_publisher_name: {
                required: true,
                minLength: 2,
                maxLength: 50,
                message: 'Nom de l\'éditeur obligatoire (2-50 caractères)'
            }
        };
        
        this.imageRules = {
            cover_horizontal: {
                required: true,
                min: 1,
                max: 1,
                message: 'Cover horizontale obligatoire'
            },
            cover_vertical: {
                required: true,
                min: 1,
                max: 1,
                message: 'Cover verticale obligatoire'
            },
            screenshot: {
                required: true,
                min: 1,
                max: 9,
                message: 'Au moins 1 screenshot obligatoire (max 9)'
            }
        };
        
        this.uploadedImages = {
            cover_horizontal: [],
            cover_vertical: [],
            screenshot: []
        };
        
        this.validationState = {};
        this.isInitialized = false;
        this.init();
    }
    
    init() {
        if (this.isInitialized) {
            return;
        }
        
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.bindEvents());
        } else {
            this.bindEvents();
        }
    }
    
    bindEvents() {
        if (this.isInitialized) {
            return;
        }
        
        this.initializeValidationState();
        this.bindTextFieldEvents();
        this.bindImageEvents();
        this.validateForm();
        this.isInitialized = true;
        
        console.log('SubmissionFormValidator: Événements liés, validation initiale effectuée');
    }
    
    initializeValidationState() {
        Object.keys(this.rules).forEach(fieldName => {
            this.validationState[fieldName] = {
                isValid: false,
                message: '',
                touched: false
            };
        });
        
        Object.keys(this.imageRules).forEach(imageType => {
            this.validationState[imageType] = {
                isValid: false,
                message: '',
                touched: false
            };
        });
        
        console.log('SubmissionFormValidator: État de validation initialisé', this.validationState);
    }
    
    bindTextFieldEvents() {
        Object.keys(this.rules).forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.addEventListener('input', () => {
                    this.validationState[fieldName].touched = true;
                    this.validateField(fieldName);
                });
                field.addEventListener('blur', () => {
                    this.validationState[fieldName].touched = true;
                    this.validateField(fieldName);
                });
                
                const initialValue = field.value.trim();
                if (initialValue) {
                    this.validationState[fieldName].touched = true;
                    this.validateField(fieldName);
                }
            }
        });
    }
    
    bindImageEvents() {
        console.log('Binding image events...');
        
        document.querySelectorAll('[data-simple-cropper]').forEach(container => {
            const ratioType = container.getAttribute('data-ratio-type');
            
            if (this.imageRules[ratioType]) {
                console.log(`Setting up listeners for ${ratioType}`);
                
                container.addEventListener('imageProcessed', (event) => {
                    console.log(`Image processed for ${ratioType}:`, event.detail);
                    
                    // MÊME LOGIQUE POUR TOUS : utiliser allImages si disponible
                    if (event.detail.allImages && Array.isArray(event.detail.allImages)) {
                        this.uploadedImages[ratioType] = [...event.detail.allImages];
                        console.log(`Using allImages for ${ratioType}:`, this.uploadedImages[ratioType]);
                    } else {
                        // Fallback : créer un tableau avec l'image unique
                        this.uploadedImages[ratioType] = [{
                            url: event.detail.url,
                            attachmentId: event.detail.attachmentId
                        }];
                        console.log(`Using single image for ${ratioType}:`, this.uploadedImages[ratioType]);
                    }
                    
                    this.validationState[ratioType].touched = true;
                    this.validateImages();
                });
                
                container.addEventListener('imageRemoved', (event) => {
                    console.log(`Image removed for ${ratioType}:`, event.detail);
                    
                    // ACCÈS DIRECT À L'INSTANCE CROPPER
                    const cropperId = event.detail.cropperId;
                    if (window.cropperInstances && window.cropperInstances[cropperId]) {
                        const instance = window.cropperInstances[cropperId];
                        console.log(`Direct access to cropper instance:`, instance.uploadedImages);
                        
                        // COPIER DIRECTEMENT depuis l'instance
                        this.uploadedImages[ratioType] = [...(instance.uploadedImages || [])];
                    } else {
                        // Fallback
                        if (this.uploadedImages[ratioType] && event.detail.index !== undefined) {
                            this.uploadedImages[ratioType].splice(event.detail.index, 1);
                        }
                    }
                    
                    this.validationState[ratioType].touched = true;
                    console.log(`Updated images after removal for ${ratioType}:`, this.uploadedImages[ratioType]);
                    this.validateImages();
                });
            }
        });
        
        // Synchronisation initiale DIRECTE avec les instances
        setTimeout(() => {
            this.syncWithCropperInstances();
        }, 300);
    }
    
    syncWithCropperInstances() {
        console.log('=== SYNC DIRECT AVEC INSTANCES ===');
        console.log('window.cropperInstances:', window.cropperInstances);
        
        if (window.cropperInstances) {
            Object.entries(window.cropperInstances).forEach(([cropperId, instance]) => {
                const ratioType = instance.ratioType;
                console.log(`Instance ${cropperId} (${ratioType}):`, instance.uploadedImages);
                
                if (this.imageRules[ratioType]) {
                    // COPIE DIRECTE peu importe le nombre
                    if (instance.uploadedImages && instance.uploadedImages.length > 0) {
                        this.uploadedImages[ratioType] = [...instance.uploadedImages];
                        this.validationState[ratioType].touched = true;
                        console.log(`✅ SYNC ${ratioType}: ${instance.uploadedImages.length} images`);
                    } else {
                        console.log(`❌ ${ratioType}: Pas d'images dans l'instance`);
                    }
                }
            });
        } else {
            console.log('❌ Pas d\'instances croppers trouvées');
        }
        
        // Revalider après synchronisation
        this.validateImages();
        console.log('=== ÉTAT FINAL uploadedImages ===', this.uploadedImages);
    }
    
    validateField(fieldName) {
        const field = document.getElementById(fieldName);
        const rule = this.rules[fieldName];
        
        if (!field || !rule) {
            return false;
        }
        
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        if (rule.required && !value) {
            isValid = false;
            errorMessage = 'Ce champ est obligatoire';
        } else if (value) {
            if (rule.minLength && value.length < rule.minLength) {
                isValid = false;
                errorMessage = `Minimum ${rule.minLength} caractères`;
            } else if (rule.maxLength && value.length > rule.maxLength) {
                isValid = false;
                errorMessage = `Maximum ${rule.maxLength} caractères`;
            } else if (rule.isYouTubeUrl && !this.isValidYouTubeUrl(value)) {
                isValid = false;
                errorMessage = 'URL YouTube non valide';
            } else if (rule.isDate && !this.isValidDate(value)) {
                isValid = false;
                errorMessage = 'Date non valide';
            }
        }
        
        this.validationState[fieldName] = {
            isValid: isValid,
            message: errorMessage,
            touched: this.validationState[fieldName].touched
        };
        
        this.updateFieldVisual(field, isValid, errorMessage);
        this.validateForm();
        
        return isValid;
    }
    
    validateImages() {
        Object.keys(this.imageRules).forEach(imageType => {
            const rule = this.imageRules[imageType];
            const count = this.uploadedImages[imageType] ? this.uploadedImages[imageType].length : 0;
            
            let isValid = true;
            let errorMessage = '';
            
            if (rule.required && count < rule.min) {
                isValid = false;
                errorMessage = rule.message;
            } else if (count > rule.max) {
                isValid = false;
                errorMessage = `Maximum ${rule.max} images`;
            }
            
            this.validationState[imageType] = {
                isValid: isValid,
                message: errorMessage,
                touched: this.validationState[imageType].touched
            };
        });
        
        this.updateImageSectionVisuals();
        this.validateForm();
    }
    
    validateForm() {
        const allStates = Object.values(this.validationState);
        const touchedStates = allStates.filter(state => state.touched);
        const requiredFieldsValid = allStates.every(state => state.isValid);
        
        const hasAnyTouchedFields = touchedStates.length > 0;
        const hasInvalidTouchedFields = touchedStates.some(state => !state.isValid);
        
        let isFormValid = false;
        let buttonText = '📝 Complétez le formulaire';
        
        if (!hasAnyTouchedFields) {
            isFormValid = false;
            buttonText = '📝 Complétez le formulaire';
        } else if (hasInvalidTouchedFields) {
            isFormValid = false;
            buttonText = '❌ Corrigez les erreurs';
        } else if (requiredFieldsValid) {
            isFormValid = true;
            buttonText = '🚀 Soumettre le jeu';
        }
        
        this.updateSubmitButton(isFormValid, buttonText);
        
        return isFormValid;
    }
    
    updateFieldVisual(field, isValid, message) {
        if (!field) return;
        
        const container = field.closest('.sisme-form-field');
        if (!container) return;
        
        field.classList.remove('sisme-field-valid', 'sisme-field-invalid');
        
        if (field.value.trim()) {
            field.classList.add(isValid ? 'sisme-field-valid' : 'sisme-field-invalid');
        }
        
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
        Object.keys(this.imageRules).forEach(imageType => {
            const container = document.querySelector(`[data-ratio-type="${imageType}"]`);
            if (container) {
                const state = this.validationState[imageType];
                const parentSection = container.closest('.sisme-form-field');
                
                if (parentSection) {
                    const label = parentSection.querySelector('.sisme-form-label');
                    if (label) {
                        label.classList.remove('sisme-label-valid', 'sisme-label-invalid');
                        if (this.uploadedImages[imageType].length > 0) {
                            label.classList.add(state.isValid ? 'sisme-label-valid' : 'sisme-label-invalid');
                        }
                    }
                }
            }
        });
    }
    
    updateSubmitButton(isValid, buttonText) {
        const submitButton = document.getElementById(this.submitButtonId);
        if (!submitButton) return;
        
        submitButton.disabled = !isValid;
        submitButton.classList.toggle('sisme-btn-disabled', !isValid);
        submitButton.classList.toggle('sisme-btn-enabled', isValid);
        submitButton.innerHTML = buttonText;
    }
    
    addUploadedImage(ratioType, imageData) {
        if (!this.uploadedImages[ratioType]) {
            this.uploadedImages[ratioType] = [];
        }
        
        if (ratioType === 'cover_horizontal' || ratioType === 'cover_vertical') {
            this.uploadedImages[ratioType] = [imageData];
        } else {
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
    
    isValidYouTubeUrl(url) {
        const youtubeRegex = /^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[\w-]+(&[\w=]*)?$/;
        return youtubeRegex.test(url);
    }
    
    isValidDate(dateString) {
        const date = new Date(dateString);
        return !isNaN(date.getTime()) && dateString.match(/^\d{4}-\d{2}-\d{2}$/);
    }
    
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
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        data.images = this.uploadedImages;
        return data;
    }
    
    debugValidation() {
        console.log('=== DEBUG VALIDATION FORMULAIRE ===');
        console.log('Tous les champs et leur état :');
        
        // Champs texte
        Object.keys(this.rules).forEach(fieldName => {
            const field = document.getElementById(fieldName);
            const state = this.validationState[fieldName];
            const value = field ? field.value.trim() : 'CHAMP INTROUVABLE';
            
            console.log(`📝 ${fieldName}:`);
            console.log(`   Valeur: "${value}"`);
            console.log(`   Valide: ${state.isValid ? '✅' : '❌'}`);
            console.log(`   Touché: ${state.touched ? '✅' : '❌'}`);
            console.log(`   Message: "${state.message}"`);
            console.log('');
        });
        
        // Images
        Object.keys(this.imageRules).forEach(imageType => {
            const state = this.validationState[imageType];
            const count = this.uploadedImages[imageType].length;
            
            console.log(`🖼️ ${imageType}:`);
            console.log(`   Images: ${count}`);
            console.log(`   Valide: ${state.isValid ? '✅' : '❌'}`);
            console.log(`   Touché: ${state.touched ? '✅' : '❌'}`);
            console.log(`   Message: "${state.message}"`);
            console.log('');
        });
        
        // État global
        const allStates = Object.values(this.validationState);
        const touchedStates = allStates.filter(state => state.touched);
        const validStates = allStates.filter(state => state.isValid);
        const invalidStates = allStates.filter(state => !state.isValid);
        
        console.log('📊 RÉSUMÉ GLOBAL:');
        console.log(`   Total champs: ${allStates.length}`);
        console.log(`   Champs touchés: ${touchedStates.length}`);
        console.log(`   Champs valides: ${validStates.length}`);
        console.log(`   Champs invalides: ${invalidStates.length}`);
        
        if (invalidStates.length > 0) {
            console.log('❌ CHAMPS PROBLÉMATIQUES:');
            invalidStates.forEach(state => {
                const fieldName = Object.keys(this.validationState).find(key => 
                    this.validationState[key] === state
                );
                console.log(`   - ${fieldName}: ${state.message || 'Invalide'}`);
            });
        }
        
        const isFormValid = this.validateForm();
        console.log(`🎯 FORMULAIRE VALIDE: ${isFormValid ? '✅ OUI' : '❌ NON'}`);
        
        return {
            validationState: this.validationState,
            uploadedImages: this.uploadedImages,
            isValid: isFormValid
        };
    }
}

window.SubmissionFormValidator = SubmissionFormValidator;

document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        if (!window.submissionValidator) {
            window.submissionValidator = new SubmissionFormValidator();
            console.log('SubmissionFormValidator initialisé avec succès');
        }
    }, 100);
});