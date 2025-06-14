/**
 * File: /sisme-games-editor/assets/js/meta-boxes.js
 * Scripts JavaScript pour les meta boxes des fiches de jeu
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initSismeMetaBoxes();
    });

    /**
     * Initialisation des meta boxes
     */
    function initSismeMetaBoxes() {
        // Gestion du slider de notation
        initRatingSlider();
        
        // Validation en temps réel
        initFieldValidation();
        
        // Amélioration UX des checkboxes
        initPlatformCheckboxes();
        
        // Gestion des URLs
        initUrlValidation();
        
        console.log('Sisme Games Meta Boxes - Initialisé');
    }

    /**
     * Gestion du slider de notation avec mise à jour des étoiles
     */
    function initRatingSlider() {
        const $ratingSlider = $('#sisme_note_globale');
        const $ratingValue = $('.sisme-rating-value');
        const $ratingStars = $('.sisme-rating-stars');

        if ($ratingSlider.length) {
            // Mise à jour initiale
            updateRatingDisplay($ratingSlider.val());

            // Mise à jour en temps réel
            $ratingSlider.on('input', function() {
                const value = $(this).val();
                updateRatingDisplay(value);
            });
        }

        function updateRatingDisplay(value) {
            $ratingValue.text(value);
            
            // Calculer les étoiles (sur 5)
            const stars = Math.round(value / 20);
            let starsHtml = '';
            
            for (let i = 1; i <= 5; i++) {
                if (i <= stars) {
                    starsHtml += '<span class="dashicons dashicons-star-filled sisme-star-filled"></span>';
                } else {
                    starsHtml += '<span class="dashicons dashicons-star-empty sisme-star-empty"></span>';
                }
            }
            
            $ratingStars.html(starsHtml);
            
            // Animation des étoiles
            $ratingStars.find('.dashicons').each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
                $(this).addClass('star-animation');
            });
            
            setTimeout(function() {
                $('.star-animation').removeClass('star-animation');
            }, 1000);
        }
    }

    /**
     * Validation des champs en temps réel
     */
    function initFieldValidation() {
        // Validation de la date
        $('#sisme_date_sortie').on('change', function() {
            const $field = $(this);
            const date = new Date($field.val());
            const today = new Date();
            
            $field.removeClass('error success');
            
            if ($field.val()) {
                if (date > today) {
                    // Date future - warning mais pas erreur
                    $field.addClass('future-date');
                    showFieldMessage($field, 'Date dans le futur', 'warning');
                } else {
                    $field.addClass('success');
                    $field.removeClass('future-date');
                    hideFieldMessage($field);
                }
            }
        });

        // Validation du prix
        $('#sisme_prix').on('input', function() {
            const $field = $(this);
            const value = parseFloat($field.val());
            
            $field.removeClass('error success');
            
            if ($field.val() && (isNaN(value) || value < 0)) {
                $field.addClass('error');
                showFieldMessage($field, 'Le prix doit être un nombre positif', 'error');
            } else if ($field.val()) {
                $field.addClass('success');
                hideFieldMessage($field);
            }
        });

        // Validation des champs texte obligatoires
        $('#sisme_developpeur, #sisme_editeur').on('blur', function() {
            const $field = $(this);
            $field.removeClass('error success');
            
            if ($field.val().trim()) {
                $field.addClass('success');
            }
        });
    }

    /**
     * Amélioration UX des checkboxes de plateformes
     */
    function initPlatformCheckboxes() {
        $('.sisme-