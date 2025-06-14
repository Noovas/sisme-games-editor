/**
 * File: /sisme-games-editor/assets/js/admin.js
 * Scripts JavaScript pour l'interface admin du plugin Sisme Games Editor
 */

(function($) {
    'use strict';

    /**
     * Initialisation au chargement du document
     */
    $(document).ready(function() {
        initSismeGamesEditor();
    });

    /**
     * Fonction principale d'initialisation
     */
    function initSismeGamesEditor() {
        // Animation d'entrée progressive des cartes
        animateCards();
        
        // Gestion des interactions des cartes
        handleCardInteractions();
        
        // Ajout d'effets visuels
        addVisualEffects();
        
        console.log('Sisme Games Editor - Interface admin initialisée');
    }

    /**
     * Animation d'entrée progressive des cartes du tableau de bord
     */
    function animateCards() {
        $('.sisme-dashboard-card').each(function(index) {
            const $card = $(this);
            
            // Retard progressif pour chaque carte
            setTimeout(function() {
                $card.addClass('animate-in');
            }, index * 100);
        });
    }

    /**
     * Gestion des interactions avec les cartes
     */
    function handleCardInteractions() {
        // Effet de parallaxe léger sur les cartes
        $('.sisme-dashboard-card').on('mousemove', function(e) {
            const $card = $(this);
            const cardRect = this.getBoundingClientRect();
            const cardCenterX = cardRect.left + cardRect.width / 2;
            const cardCenterY = cardRect.top + cardRect.height / 2;
            
            const deltaX = (e.clientX - cardCenterX) / cardRect.width * 10;
            const deltaY = (e.clientY - cardCenterY) / cardRect.height * 10;
            
            $card.css('transform', `translateY(-4px) rotateX(${deltaY}deg) rotateY(${deltaX}deg)`);
        });

        $('.sisme-dashboard-card').on('mouseleave', function() {
            $(this).css('transform', '');
        });

        // Animation des boutons
        $('.sisme-btn, .sisme-btn-secondary').on('mouseenter', function() {
            $(this).addClass('btn-hover');
        }).on('mouseleave', function() {
            $(this).removeClass('btn-hover');
        });
    }

    /**
     * Ajout d'effets visuels
     */
    function addVisualEffects() {
        // Effet de pulsation sur les icônes
        setInterval(function() {
            $('.sisme-card-icon').each(function() {
                const $icon = $(this);
                if (Math.random() > 0.8) { // 20% de chance
                    $icon.addClass('pulse-effect');
                    setTimeout(function() {
                        $icon.removeClass('pulse-effect');
                    }, 1000);
                }
            });
        }, 3000);

        // Gestion du scroll fluide
        $('a[href^="#"]').on('click', function(e) {
            e.preventDefault();
            const target = $($(this).attr('href'));
            if (target.length) {
                $('html, body').animate({
                    scrollTop: target.offset().top - 20
                }, 600, 'easeInOutCubic');
            }
        });
    }

    /**
     * Fonction utilitaire pour les notifications
     */
    function showNotification(message, type = 'success') {
        const $notification = $(`
            <div class="sisme-notification sisme-notification-${type}">
                <span class="dashicons dashicons-${type === 'success' ? 'yes-alt' : 'warning'}"></span>
                <span>${message}</span>
                <button class="sisme-notification-close">
                    <span class="dashicons dashicons-dismiss"></span>
                </button>
            </div>
        `);

        $('body').append($notification);

        // Animation d'entrée
        setTimeout(function() {
            $notification.addClass('show');
        }, 10);

        // Auto-suppression après 5 secondes
        setTimeout(function() {
            hideNotification($notification);
        }, 5000);

        // Gestion du bouton fermer
        $notification.find('.sisme-notification-close').on('click', function() {
            hideNotification($notification);
        });
    }

    /**
     * Masquer une notification
     */
    function hideNotification($notification) {
        $notification.removeClass('show');
        setTimeout(function() {
            $notification.remove();
        }, 300);
    }

    /**
     * Fonction utilitaire pour valider les formulaires
     */
    function validateForm($form) {
        let isValid = true;
        const errors = [];

        // Validation des champs requis
        $form.find('[required]').each(function() {
            const $field = $(this);
            if (!$field.val().trim()) {
                isValid = false;
                $field.addClass('error');
                errors.push(`Le champ "${$field.attr('placeholder') || $field.attr('name')}" est requis.`);
            } else {
                $field.removeClass('error');
            }
        });

        // Validation des emails
        $form.find('input[type="email"]').each(function() {
            const $field = $(this);
            const email = $field.val().trim();
            if (email && !isValidEmail(email)) {
                isValid = false;
                $field.addClass('error');
                errors.push('Veuillez saisir une adresse email valide.');
            }
        });

        if (!isValid) {
            showNotification(errors.join('<br>'), 'error');
        }

        return isValid;
    }

    /**
     * Validation d'email
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Fonction pour gérer les uploads d'images
     */
    function initMediaUploader($button, $preview, $hiddenInput) {
        let mediaUploader;

        $button.on('click', function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Sélectionner une image',
                button: {
                    text: 'Utiliser cette image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                
                $hiddenInput.val(attachment.id);
                $preview.html(`<img src="${attachment.url}" alt="" style="max-width: 100%; height: auto; border-radius: 8px;">`);
                $button.text('Changer l\'image');
                
                showNotification('Image sélectionnée avec succès !');
            });

            mediaUploader.open();
        });
    }

    // Exposition des fonctions utilitaires dans l'objet global
    window.SismeGamesEditor = {
        showNotification: showNotification,
        validateForm: validateForm,
        initMediaUploader: initMediaUploader
    };

})(jQuery);

// CSS d'animation ajouté dynamiquement
jQuery(document).ready(function($) {
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .pulse-effect {
                animation: pulse 1s ease-in-out;
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
            
            .btn-hover {
                transform: translateY(-2px) !important;
            }
            
            .sisme-notification {
                position: fixed;
                top: 32px;
                right: 20px;
                background: white;
                border-left: 4px solid var(--theme-palette-color-1);
                border-radius: 8px;
                padding: 15px 20px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                display: flex;
                align-items: center;
                gap: 10px;
                transform: translateX(400px);
                opacity: 0;
                transition: all 0.3s ease;
                z-index: 100000;
                max-width: 350px;
            }
            
            .sisme-notification.show {
                transform: translateX(0);
                opacity: 1;
            }
            
            .sisme-notification-error {
                border-left-color: #e74c3c;
            }
            
            .sisme-notification-close {
                background: none;
                border: none;
                cursor: pointer;
                color: #999;
                margin-left: auto;
            }
            
            .sisme-notification-close:hover {
                color: #333;
            }
        `)
        .appendTo('head');
});