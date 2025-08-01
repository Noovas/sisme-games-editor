/**
 * File: /sisme-games-editor/includes/user/user-developer/game-submission/assets/game-submission-modal.js
 * Modale de soumission de jeu avec feedback en temps réel
 * 
 * RESPONSABILITÉ:
 * - Gestion complète de la modale de soumission
 * - Affichage en temps réel des étapes (sauvegarde + soumission)
 * - Gestion des résultats (succès/échec) avec actions appropriées
 * - Intégration avec le système game-submission existant
 * 
 * DÉPENDANCES:
 * - jQuery (WordPress core)
 * - game-submission-modal.css (styles)
 * - SismeGameSubmission (pour les appels AJAX)
 */

(function($) {
    'use strict';
    
    /**
     * Classe principale de gestion de la modale de soumission
     */
    class SismeSubmissionModal {
        
        constructor() {
            this.modal = null;
            this.content = null;
            this.isOpen = false;
            this.currentPhase = null; // 'saving', 'submitting', 'success', 'error'
            this.gameSubmissionInstance = null;
            
            this.init();
        }
        
        /**
         * Initialisation de la modale
         */
        init() {
            // Chercher la modale existante ou la créer
            this.modal = document.getElementById('sisme-submission-modal');
            if (!this.modal) {
                this.createModalHTML();
            }
            
            this.content = this.modal.querySelector('.sisme-modal-content');
            this.bindEvents();
            
            if (typeof console !== 'undefined' && console.log) {
                console.log('[SismeSubmissionModal] Initialisé');
            }
        }
        
        /**
         * Créer le HTML de la modale si elle n'existe pas
         */
        createModalHTML() {
            const modalHTML = `
                <div id="sisme-submission-modal" class="sisme-submission-modal">
                    <div class="sisme-modal-content">
                        <!-- Contenu dynamique injecté ici -->
                    </div>
                </div>
            `;
            
            $('body').append(modalHTML);
            this.modal = document.getElementById('sisme-submission-modal');
        }
        
        /**
         * Liaison des événements
         */
        bindEvents() {
            // Empêcher la fermeture par clic extérieur pendant le processing
            $(this.modal).on('click', (e) => {
                if (e.target === e.currentTarget && !this.isProcessing()) {
                    this.hide();
                }
            });
            
            // Gestion touches clavier
            $(document).on('keydown', (e) => {
                if (this.isOpen && e.key === 'Escape' && !this.isProcessing()) {
                    this.hide();
                }
            });
        }
        
        /**
         * Afficher la modale
         */
        show() {
            this.modal.classList.add('active');
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
            
            // Centrer la modale même si on est en bas de page
            this.centerModal();
            
            // Focus management pour accessibilité
            this.modal.focus();
        }
        
        /**
         * Masquer la modale
         */
        hide() {
            this.modal.classList.remove('active');
            this.isOpen = false;
            this.currentPhase = null;
            document.body.style.overflow = '';
        }
        
        /**
         * Centrer la modale sur l'écran
         */
        centerModal() {
            // Forcer le scroll en haut de la modale si nécessaire
            this.modal.scrollTop = 0;
            
            // Alternative: scroll vers la modale si elle déborde
            setTimeout(() => {
                if (this.content) {
                    this.content.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center',
                        inline: 'center'
                    });
                }
            }, 100);
        }
        
        /**
         * Vérifier si la modale est en cours de traitement
         */
        isProcessing() {
            return this.currentPhase === 'saving' || this.currentPhase === 'submitting';
        }
        
        /**
         * Mettre à jour le contenu de la modale avec animation
         */
        setContent(html) {
            this.content.innerHTML = html;
            this.content.classList.add('sisme-phase-transition');
            
            setTimeout(() => {
                this.content.classList.remove('sisme-phase-transition');
            }, 400);
        }
        
        // === PHASES DE LA MODALE ===
        
        /**
         * Phase 1: Sauvegarde en cours
         */
        showSaving() {
            this.currentPhase = 'saving';
            
            const html = `
                <div class="sisme-modal-title">Sauvegarde en cours...</div>
                <div class="sisme-spinner">
                    <div class="sisme-spinner-dot"></div>
                    <div class="sisme-spinner-dot"></div>
                    <div class="sisme-spinner-dot"></div>
                </div>
                <div class="sisme-modal-details">
                    <ul id="saving-steps">
                        <!-- Les étapes apparaîtront ici une par une -->
                    </ul>
                </div>
            `;
            
            this.setContent(html);
        }
        
        /**
         * Phase 2: Soumission pour validation
         */
        showSubmitting() {
            this.currentPhase = 'submitting';
            
            const html = `
                <div class="sisme-modal-title">Soumission pour validation...</div>
                <div class="sisme-spinner">
                    <div class="sisme-spinner-dot"></div>
                    <div class="sisme-spinner-dot"></div>
                    <div class="sisme-spinner-dot"></div>
                </div>
                <div class="sisme-modal-details">
                    <ul id="submitting-steps">
                        <!-- Les étapes de soumission apparaîtront ici -->
                    </ul>
                </div>
            `;
            
            this.setContent(html);
        }
        
        /**
         * Phase 3: Succès de la soumission
         */
        showSuccess(gameName = "Votre jeu") {
            this.currentPhase = 'success';
            
            const html = `
                <div class="sisme-result-success">
                    <div class="sisme-result-icon">🎉</div>
                    <div class="sisme-result-title">Soumission réussie !</div>
                    <div class="sisme-result-message">
                        Votre jeu "<strong>${this.escapeHtml(gameName)}</strong>" a été envoyé pour validation.<br>
                        <strong>Délai de traitement :</strong> 48 heures maximum<br>
                        Vous recevrez un email de confirmation.
                    </div>
                </div>
                <div class="sisme-modal-actions">
                    <button class="sisme-modal-btn" id="success-btn" type="button">
                        D'accord - Retour au dashboard
                    </button>
                </div>
            `;
            
            this.setContent(html);
            
            // Bind du bouton success
            $('#success-btn').on('click', () => this.handleSuccess());
        }
        
        /**
         * Phase 4: Échec de la soumission
         */
        showError(missingFields = [], errorMessage = null) {
            this.currentPhase = 'error';
            
            // Mapping des noms de champs techniques vers noms lisibles pour l'utilisateur
            const fieldNames = {
                'game_name': 'Nom du jeu',
                'game_description': 'Description courte du jeu',
                'game_release_date': 'Date de sortie',
                'game_trailer': 'Vidéo de présentation (YouTube)',
                'game_studio_name': 'Nom de votre studio',
                'game_publisher_name': 'Nom de l\'éditeur',
                'game_genres': 'Genre(s) du jeu (Action, RPG, etc.)',
                'game_platforms': 'Plateformes de sortie (PC, PlayStation, etc.)',
                'game_modes': 'Mode(s) de jeu (Solo, Multijoueur, etc.)',
                'external_links': 'Où acheter le jeu (Steam, Epic Games ou GOG)',
                'screenshots': 'Captures d\'écran du jeu (minimum 2)',
                'covers': 'Images de couverture (horizontale et verticale)',
                'game_studio_url': 'Site web du studio',
                'game_publisher_url': 'Site web de l\'éditeur',
                'sections': 'Sections du jeu (Gameplay, Histoire, etc.)',
            };
            
            let contentHTML = '';
            // Toujours afficher les champs en colonne si possible
            let fieldsToShow = [];
            if (missingFields && missingFields.length > 0) {
                fieldsToShow = missingFields;
            } else if (errorMessage) {
                // Cas spécial : message du type "Champs obligatoires manquants: champ1, champ2, ..."
                const match = errorMessage.match(/Champs obligatoires manquants\s*:\s*(.+)/i);
                if (match && match[1]) {
                    fieldsToShow = match[1].split(',').map(f => f.trim()).filter(Boolean);
                } else {
                    fieldsToShow = [errorMessage];
                }
            }
            if (fieldsToShow.length > 0) {
                const missingList = fieldsToShow.map(field =>
                    `<li>${fieldNames[field] || this.escapeHtml(field)}</li>`
                ).join('');
                contentHTML = `
                    <div class="sisme-result-message">
                        Informations manquantes :
                    </div>
                    <div class="sisme-modal-details">
                        <ul style="display: flex; flex-direction: column; gap: 0.5em;">
                            ${missingList}
                        </ul>
                    </div>
                `;
            } else {
                contentHTML = `
                    <div class="sisme-result-message">
                        Une erreur inattendue s'est produite lors de la soumission.
                    </div>
                `;
            }
            
            const html = `
                <div class="sisme-result-error">
                    <div class="sisme-result-icon">🚫</div>
                    <div class="sisme-result-title">Soumission impossible</div>
                    ${contentHTML}
                </div>
                <div class="sisme-modal-actions">
                    <button class="sisme-modal-btn" id="error-btn" type="button">
                        Corriger
                    </button>
                </div>
            `;
            
            this.setContent(html);
            
            // Bind du bouton error
            $('#error-btn').on('click', () => this.handleError());
        }
        
        // === GESTION DES ÉTAPES EN TEMPS RÉEL ===
        
        /**
         * Ajouter une étape avec animation
         */
        addStep(containerId, text, status = 'processing') {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            const li = document.createElement('li');
            li.className = `sisme-step-${status}`;
            li.innerHTML = this.escapeHtml(text);
            
            // Animation d'apparition
            li.style.opacity = '0';
            li.style.transform = 'translateX(-20px)';
            container.appendChild(li);
            
            // Déclencher l'animation
            setTimeout(() => {
                li.style.transition = 'all 0.3s ease';
                li.style.opacity = '1';
                li.style.transform = 'translateX(0)';
                li.classList.add('sisme-step-appear');
            }, 50);
        }
        
        /**
         * Mettre à jour le statut de la dernière étape
         */
        updateLastStep(containerId, status = 'complete') {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            const lastStep = container.lastElementChild;
            if (lastStep) {
                lastStep.className = `sisme-step-${status}`;
            }
        }
        
        // === PROCESSUS PRINCIPAL DE SOUMISSION ===
        
        /**
         * Démarrer le processus complet de soumission
         */
        async startSubmissionProcess(gameSubmissionInstance) {
            this.gameSubmissionInstance = gameSubmissionInstance;
            
            try {
                // Phase 1: Sauvegarde
                await this.performSaving();
                
                // Pause entre les phases
                await this.sleep(500);
                
                // Phase 2: Soumission
                await this.performSubmission();
                
            } catch (error) {
                console.error('[SismeSubmissionModal] Erreur:', error);
                this.showError([], error.message || 'Erreur inattendue');
            }
        }
        
        /**
         * Phase de sauvegarde avec étapes détaillées
         */
        async performSaving() {
            this.showSaving();
            
            // Étapes avec des noms compréhensibles pour l'utilisateur
            this.addStep('saving-steps', '📷 Traitement des images...', 'processing');
            await this.sleep(800);
            this.updateLastStep('saving-steps', 'complete');
            
            this.addStep('saving-steps', '🖼️ Sauvegarde des visuels...', 'processing');
            await this.sleep(600);
            this.updateLastStep('saving-steps', 'complete');
            
            this.addStep('saving-steps', '💾 Enregistrement de votre jeu...', 'processing');
            
            // Appel réel à la sauvegarde
            if (this.gameSubmissionInstance && typeof this.gameSubmissionInstance.saveDraftSilently === 'function') {
                await this.gameSubmissionInstance.saveDraftSilently();
            }
            
            await this.sleep(400);
            this.updateLastStep('saving-steps', 'complete');
        }
        
        /**
         * Phase de soumission avec validation
         */
        async performSubmission() {
            this.showSubmitting();
            
            // Récapitulatif avec des termes clairs
            await this.sleep(200);
            this.addStep('submitting-steps', '📷 Traitement des images', 'complete');
            await this.sleep(200);
            this.addStep('submitting-steps', '🖼️ Sauvegarde des visuels', 'complete');
            await this.sleep(200);
            this.addStep('submitting-steps', '💾 Enregistrement de votre jeu', 'complete');
            
            // Nouvelle étape: validation avec terme compréhensible
            await this.sleep(400);
            this.addStep('submitting-steps', '✅ Vérification finale...', 'processing');
            
            // Appel AJAX de soumission
            try {
                const result = await this.performSubmissionAjax();
                
                if (result.success) {
                    this.updateLastStep('submitting-steps', 'complete');
                    await this.sleep(300);
                    
                    // Récupérer le nom du jeu depuis les données
                    const gameName = this.getGameName();
                    this.showSuccess(gameName);
                    
                } else {
                    this.updateLastStep('submitting-steps', 'error');
                    await this.sleep(300);
                    
                    // Gestion des erreurs de validation
                    if (result.data && result.data.errors) {
                        this.showError(result.data.errors);
                    } else {
                        this.showError([], result.data ? result.data.message : 'Erreur de soumission');
                    }
                }
                
            } catch (error) {
                this.updateLastStep('submitting-steps', 'error');
                await this.sleep(300);
                throw error;
            }
        }
        
        /**
         * Appel AJAX pour la soumission finale
         */
        performSubmissionAjax() {
            return new Promise((resolve, reject) => {
                if (!this.gameSubmissionInstance || !this.gameSubmissionInstance.config) {
                    reject(new Error('Instance GameSubmission non disponible'));
                    return;
                }
                
                const config = this.gameSubmissionInstance.config;
                
                $.ajax({
                    url: config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sisme_submit_game_for_review',
                        security: config.nonce,
                        submission_id: config.currentSubmissionId
                    },
                    dataType: 'json',
                    success: resolve,
                    error: (xhr, status, error) => {
                        // Gestion des erreurs de validation retournées par le serveur
                        if (xhr.responseJSON) {
                            resolve(xhr.responseJSON);
                        } else {
                            reject(new Error('Erreur réseau: ' + error));
                        }
                    }
                });
            });
        }
        
        // === HANDLERS D'ACTIONS ===
        
        /**
         * Gérer le succès - redirection vers dashboard
         */
        handleSuccess() {
            this.hide();
            
            // Redirection vers le dashboard développeur
            if (typeof SismeDashboard !== 'undefined' && SismeDashboard.setActiveSection) {
                SismeDashboard.setActiveSection('developer', true);
            } else {
                // Fallback: reload de la page
                window.location.reload();
            }
        }
        
        /**
         * Gérer l'erreur - fermer la modale pour corriger
         */
        handleError() {
            this.hide();
            // L'utilisateur peut maintenant corriger les champs du formulaire
        }
        
        // === UTILITAIRES ===
        
        /**
         * Fonction sleep pour les délais
         */
        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
        
        /**
         * Échapper le HTML pour éviter les injections
         */
        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        /**
         * Récupérer le nom du jeu depuis le formulaire
         */
        getGameName() {
            const gameNameInput = document.querySelector('input[name="game_name"]');
            return gameNameInput ? gameNameInput.value : 'Votre jeu';
        }
    }
    
    // === EXPORT GLOBAL ===
    
    // Rendre la classe disponible globalement
    window.SismeSubmissionModal = SismeSubmissionModal;
    
    // Instance globale pour faciliter l'utilisation
    window.sismeSubmissionModal = null;
    
    // Initialisation automatique quand le DOM est prêt
    $(document).ready(function() {
        window.sismeSubmissionModal = new SismeSubmissionModal();
        
        if (typeof console !== 'undefined' && console.log) {
            console.log('[SismeSubmissionModal] Instance globale créée');
        }
    });
    
})(jQuery);