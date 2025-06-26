/**
 * File: /sisme-games-editor/includes/user/user-profile/assets/user-profile.js
 * JavaScript pour la gestion des uploads d'avatar et bannière
 */

(function($) {
    'use strict';
    
    // Configuration globale (injectée par wp_localize_script)
    const config = window.sismeUserProfile || {};
    
    /**
     * Initialisation au chargement du DOM
     */
    $(document).ready(function() {
        initializeImageUploaders();
        initializeProfileForms();
    });
    
    /**
     * Initialiser les uploaders d'images (avatar + bannière)
     */
    function initializeImageUploaders() {
        // Gestionnaire pour tous les inputs de fichier avatar/bannière
        $(document).on('change', 'input[name="avatar_file"], input[name="banner_file"]', handleFileSelection);
        
        // Gestionnaire pour tous les boutons de suppression
        $(document).on('click', '.sisme-avatar-delete-btn, .sisme-banner-delete-btn', handleImageDelete);
        
        // Initialiser l'affichage des uploaders existants
        $('.sisme-avatar-uploader, .sisme-banner-uploader').each(function() {
            const $uploader = $(this);
            updateUploadProgress($uploader, false);
        });
    }
    
    /**
     * Initialiser les formulaires de profil
     */
    function initializeProfileForms() {
        // Gestionnaire soumission formulaire profil
        $(document).on('submit', '.sisme-profile-form', handleProfileFormSubmit);
    }
    
    /**
     * Gérer la sélection d'un fichier d'image
     * @param {Event} e Événement change du input file
     */
    function handleFileSelection(e) {
        const input = e.target;
        const file = input.files[0];
        
        console.log('File selection:', input.name, file);
        
        if (!file) {
            return;
        }
        
        // Déterminer le type (avatar ou banner)
        const type = input.name.replace('_file', '');
        const $uploader = $(input).closest('.sisme-' + type + '-uploader');
        
        console.log('Upload type:', type, 'Uploader found:', $uploader.length);
        
        // Validation côté client
        const validationResult = validateFile(file, type);
        if (!validationResult.isValid) {
            console.log('Validation failed:', validationResult.message);
            showError($uploader, validationResult.message);
            input.value = ''; // Reset input
            return;
        }
        
        // Prévisualisation immédiate
        showImagePreview($uploader, file, type);
        
        // Upload automatique avec délai pour éviter les conflits
        setTimeout(() => {
            uploadImage(input, file, type, $uploader);
        }, 100);
    }
    
    /**
     * Valider un fichier avant upload
     * @param {File} file Fichier à valider
     * @param {string} type Type d'image (avatar ou banner)
     * @return {Object} Résultat de validation
     */
    function validateFile(file, type) {
        // Vérifier le type MIME
        const allowedTypes = config.config?.allowed_types || ['image/jpeg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            return {
                isValid: false,
                message: config.messages?.error_format || 'Format de fichier non supporté'
            };
        }
        
        // Vérifier la taille
        const maxSize = config.config?.max_file_size || 2097152; // 2Mo
        if (file.size > maxSize) {
            return {
                isValid: false,
                message: config.messages?.error_size || 'Fichier trop volumineux (max 2Mo)'
            };
        }
        
        return { isValid: true };
    }
    
    /**
     * Afficher une prévisualisation de l'image sélectionnée
     * @param {jQuery} $uploader Container uploader
     * @param {File} file Fichier image
     * @param {string} type Type d'image
     */
    function showImagePreview($uploader, file, type) {
        console.log('Showing preview for:', type, file.name);
        
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const $preview = $uploader.find('.sisme-' + type + '-preview');
            const imageUrl = e.target.result;
            
            console.log('Preview loaded for:', type, 'Preview element found:', $preview.length);
            
            // Remplacer le contenu de prévisualisation
            $preview.html('<img src="' + imageUrl + '" alt="Prévisualisation" class="sisme-' + type + '-preview-new">');
            
            // Ajouter classe de prévisualisation
            $uploader.addClass('sisme-' + type + '-previewing');
            
            console.log('Preview set, uploader classes:', $uploader[0].className);
        };
        
        reader.onerror = function(e) {
            console.error('FileReader error:', e);
            showError($uploader, 'Erreur lors de la lecture du fichier');
        };
        
        reader.readAsDataURL(file);
    }
    
    /**
     * Uploader une image via AJAX
     * @param {HTMLInputElement} input Input file
     * @param {File} file Fichier à uploader
     * @param {string} type Type d'image (avatar ou banner)
     * @param {jQuery} $uploader Container uploader
     */
    function uploadImage(input, file, type, $uploader) {
        console.log('Starting upload for:', type, file.name);
        
        // Préparer FormData
        const formData = new FormData();
        formData.append('action', 'sisme_upload_' + type);
        formData.append('nonce', config.nonce);
        formData.append(type + '_file', file);
        
        console.log('Upload data:', {
            action: 'sisme_upload_' + type,
            nonce: config.nonce,
            file: file.name,
            ajax_url: config.ajax_url
        });
        
        // Afficher le loading
        updateUploadProgress($uploader, true);
        
        // Requête AJAX
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000, // 30 secondes
            success: function(response) {
                console.log('Upload response:', response);
                if (response.success) {
                    handleUploadSuccess($uploader, response.data, type);
                } else {
                    handleUploadError($uploader, response.data || 'Erreur inconnue', type);
                }
            },
            error: function(xhr, status, error) {
                console.error('Upload error:', status, error, xhr.responseText);
                let errorMessage = 'Erreur de connexion';
                if (status === 'timeout') {
                    errorMessage = 'Délai d\'attente dépassé';
                } else if (xhr.status === 413) {
                    errorMessage = 'Fichier trop volumineux';
                } else if (xhr.responseText) {
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        errorMessage = errorData.data || errorMessage;
                    } catch (e) {
                        // Garder le message par défaut
                    }
                }
                handleUploadError($uploader, errorMessage, type);
            },
            complete: function() {
                updateUploadProgress($uploader, false);
                // Reset input pour permettre re-sélection du même fichier
                input.value = '';
            }
        });
    }
    
    /**
     * Gérer le succès d'un upload
     * @param {jQuery} $uploader Container uploader
     * @param {Object} data Données de réponse
     * @param {string} type Type d'image
     */
    function handleUploadSuccess($uploader, data, type) {
        // Mettre à jour l'image affichée
        if (data.urls && data.urls.medium) {
            const $preview = $uploader.find('.sisme-' + type + '-preview');
            const imageClass = type === 'banner' ? 'sisme-banner-current' : 'sisme-avatar-current';
            $preview.html('<img src="' + data.urls.medium + '" alt="' + type + ' actuel" class="' + imageClass + '">');
        }
        
        // Retirer la classe de prévisualisation
        $uploader.removeClass('sisme-' + type + '-previewing');
        
        // Mettre à jour les boutons d'action
        updateActionButtons($uploader, true, type);
        
        // Afficher message de succès
        const message = config.messages?.[type + '_updated'] || type + ' mis à jour !';
        showSuccess($uploader, message);
        
        // Trigger événement personnalisé
        $uploader.trigger('sisme:' + type + '_uploaded', [data]);
    }
    
    /**
     * Gérer les erreurs d'upload
     * @param {jQuery} $uploader Container uploader
     * @param {string} message Message d'erreur
     * @param {string} type Type d'image
     */
    function handleUploadError($uploader, message, type) {
        // Retirer la prévisualisation
        $uploader.removeClass('sisme-' + type + '-previewing');
        
        // Restaurer l'affichage précédent
        restorePreviousImage($uploader, type);
        
        // Afficher l'erreur
        showError($uploader, message);
        
        // Trigger événement d'erreur
        $uploader.trigger('sisme:' + type + '_upload_error', [message]);
    }
    
    /**
     * Restaurer l'image précédente en cas d'erreur
     * @param {jQuery} $uploader Container uploader
     * @param {string} type Type d'image
     */
    function restorePreviousImage($uploader, type) {
        const $preview = $uploader.find('.sisme-' + type + '-preview');
        const hasCurrentImage = $uploader.data('has-' + type);
        
        if (hasCurrentImage) {
            // Restaurer l'image existante (sera rechargée par une requête séparée si nécessaire)
            const currentImageUrl = $uploader.data('current-' + type + '-url');
            if (currentImageUrl) {
                const imageClass = type === 'banner' ? 'sisme-banner-current' : 'sisme-avatar-current';
                $preview.html('<img src="' + currentImageUrl + '" alt="' + type + ' actuel" class="' + imageClass + '">');
            }
        } else {
            // Restaurer le placeholder
            const placeholderIcon = type === 'banner' ? '🖼️' : '👤';
            const placeholderText = type === 'banner' ? 'Aucune bannière' : 'Aucun avatar';
            
            $preview.html(
                '<div class="sisme-' + type + '-placeholder">' +
                '<span class="sisme-' + type + '-icon">' + placeholderIcon + '</span>' +
                '<p>' + placeholderText + '</p>' +
                '</div>'
            );
        }
    }
    
    /**
     * Gérer la suppression d'une image
     * @param {Event} e Événement click
     */
    function handleImageDelete(e) {
        e.preventDefault();
        
        const $button = $(e.target).closest('button');
        const $uploader = $button.closest('.sisme-avatar-uploader, .sisme-banner-uploader');
        
        // Déterminer le type
        const type = $uploader.hasClass('sisme-avatar-uploader') ? 'avatar' : 'banner';
        
        // Confirmation
        const confirmMessage = 'Êtes-vous sûr de vouloir supprimer ce ' + type + ' ?';
        if (!confirm(confirmMessage)) {
            return;
        }
        
        deleteImage($uploader, type);
    }
    
    /**
     * Supprimer une image via AJAX
     * @param {jQuery} $uploader Container uploader
     * @param {string} type Type d'image
     */
    function deleteImage($uploader, type) {
        // Préparer les données
        const data = {
            action: 'sisme_delete_' + type,
            nonce: config.nonce
        };
        
        // Afficher le loading
        updateUploadProgress($uploader, true);
        
        // Requête AJAX
        $.ajax({
            url: config.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    handleDeleteSuccess($uploader, type);
                } else {
                    showError($uploader, response.data || 'Erreur lors de la suppression');
                }
            },
            error: function() {
                showError($uploader, 'Erreur de connexion');
            },
            complete: function() {
                updateUploadProgress($uploader, false);
            }
        });
    }
    
    /**
     * Gérer le succès d'une suppression
     * @param {jQuery} $uploader Container uploader
     * @param {string} type Type d'image
     */
    function handleDeleteSuccess($uploader, type) {
        // Restaurer le placeholder
        const $preview = $uploader.find('.sisme-' + type + '-preview');
        const placeholderIcon = type === 'banner' ? '🖼️' : '👤';
        const placeholderText = type === 'banner' ? 'Aucune bannière' : 'Aucun avatar';
        
        $preview.html(
            '<div class="sisme-' + type + '-placeholder">' +
            '<span class="sisme-' + type + '-icon">' + placeholderIcon + '</span>' +
            '<p>' + placeholderText + '</p>' +
            '</div>'
        );
        
        // Mettre à jour les boutons
        updateActionButtons($uploader, false, type);
        
        // Message de succès
        const message = config.messages?.[type + '_deleted'] || type + ' supprimé';
        showSuccess($uploader, message);
        
        // Marquer comme n'ayant plus d'image
        $uploader.data('has-' + type, false);
        
        // Trigger événement
        $uploader.trigger('sisme:' + type + '_deleted');
    }
    
    /**
     * Mettre à jour l'affichage du progress/loading
     * @param {jQuery} $uploader Container uploader
     * @param {boolean} isLoading État de chargement
     */
    function updateUploadProgress($uploader, isLoading) {
        if (isLoading) {
            $uploader.addClass('sisme-uploading');
            
            // Ajouter indicateur de loading s'il n'existe pas
            if (!$uploader.find('.sisme-upload-progress').length) {
                $uploader.append('<div class="sisme-upload-progress"><span>⏳ Upload en cours...</span></div>');
            }
        } else {
            $uploader.removeClass('sisme-uploading');
            $uploader.find('.sisme-upload-progress').remove();
        }
    }
    
    /**
     * Mettre à jour les boutons d'action selon l'état
     * @param {jQuery} $uploader Container uploader
     * @param {boolean} hasImage Si une image est présente
     * @param {string} type Type d'image
     */
    function updateActionButtons($uploader, hasImage, type) {
        const $uploadBtn = $uploader.find('label[for*="' + type + '-file-input"]');
        const $deleteBtn = $uploader.find('.sisme-' + type + '-delete-btn');
        
        // Mettre à jour le texte du bouton upload
        const uploadText = hasImage ? 'Changer' : 'Ajouter';
        
        // Récupérer le contenu actuel et remplacer seulement le texte
        const currentHtml = $uploadBtn.html();
        const iconMatch = currentHtml.match(/<span[^>]*sisme-btn-icon[^>]*>.*?<\/span>/);
        const iconHtml = iconMatch ? iconMatch[0] : '<span class="sisme-btn-icon">📤</span>';
        
        $uploadBtn.html(iconHtml + uploadText);
        
        // Afficher/masquer le bouton supprimer
        if (hasImage) {
            $deleteBtn.show();
        } else {
            $deleteBtn.hide();
        }
        
        // Marquer l'état
        $uploader.data('has-' + type, hasImage);
    }
    
    /**
     * Gérer la soumission du formulaire de profil
     * @param {Event} e Événement submit
     */
    function handleProfileFormSubmit(e) {
        // Pour l'instant, laisser la soumission normale se faire
        // On pourrait ajouter ici une validation AJAX si nécessaire
        return true;
    }
    
    /**
     * Afficher un message de succès
     * @param {jQuery} $container Container pour le message
     * @param {string} message Message à afficher
     */
    function showSuccess($container, message) {
        showMessage($container, message, 'success');
    }
    
    /**
     * Afficher un message d'erreur
     * @param {jQuery} $container Container pour le message
     * @param {string} message Message à afficher
     */
    function showError($container, message) {
        showMessage($container, message, 'error');
    }
    
    /**
     * Afficher un message (succès ou erreur)
     * @param {jQuery} $container Container pour le message
     * @param {string} message Message à afficher
     * @param {string} type Type de message (success ou error)
     */
    function showMessage($container, message, type) {
        // Supprimer les messages existants
        $container.find('.sisme-upload-message').remove();
        
        // Créer le nouveau message
        const $message = $('<div class="sisme-upload-message sisme-upload-message--' + type + '">' + message + '</div>');
        
        // Ajouter le message
        $container.append($message);
        
        // Auto-suppression après 5 secondes
        setTimeout(function() {
            $message.fadeOut(300, function() {
                $message.remove();
            });
        }, 5000);
    }
    
    /**
     * API publique pour les développeurs
     */
    window.SismeUserProfile = {
        // Forcer un upload programmatiquement
        uploadImage: function(fileInput, type) {
            if (fileInput.files && fileInput.files[0]) {
                const $uploader = $(fileInput).closest('.sisme-' + type + '-uploader');
                handleFileSelection({ target: fileInput });
            }
        },
        
        // Supprimer une image programmatiquement
        deleteImage: function(type, userId) {
            const $uploader = $('.sisme-' + type + '-uploader[data-user-id="' + userId + '"]');
            if ($uploader.length) {
                deleteImage($uploader, type);
            }
        },
        
        // Événements disponibles pour écoute
        events: {
            AVATAR_UPLOADED: 'sisme:avatar_uploaded',
            BANNER_UPLOADED: 'sisme:banner_uploaded',
            AVATAR_DELETED: 'sisme:avatar_deleted',
            BANNER_DELETED: 'sisme:banner_deleted',
            UPLOAD_ERROR: 'sisme:upload_error'
        }
    };
    
})(jQuery);