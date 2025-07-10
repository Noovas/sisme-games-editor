<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/user-developer-renderer.php
 * Renderer pour la section développeur du dashboard
 * 
 * RESPONSABILITÉ:
 * - Rendu HTML de la section développeur
 * - Gestion des différents états (none, pending, approved, rejected)
 * - Interface utilisateur pour candidature
 * - Intégration avec le renderer dashboard existant
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_User_Developer_Renderer {
    
    /**
     * Rendu principal de la section développeur
     */
    public static function render_developer_section($user_id, $developer_status, $dashboard_data) {
        ob_start();
        
        // Rendu selon le statut
        switch ($developer_status) {
            case 'none':
                echo self::render_application_form($user_id);
                break;
            case 'pending':
                echo self::render_pending_status($user_id);
                break;
            case 'approved':
                echo self::render_developer_dashboard($user_id);
                break;
            case 'rejected':
                echo self::render_rejected_status($user_id);
                break;
            default:
                echo self::render_application_form($user_id);
        }
        
        return ob_get_clean();
    }
    
    /**
     * État 1 : Formulaire de candidature (statut 'none')
     */
    private static function render_application_form($user_id) {
        ob_start();
        ?>
        <div class="sisme-developer-state sisme-developer-state-apply">
            <div class="sisme-developer-header">
                <div class="sisme-developer-icon">
                    <span class="sisme-developer-icon-main">📝</span>
                </div>
                <div class="sisme-developer-intro">
                    <h3 class="sisme-developer-title">Devenir Développeur</h3>
                    <p class="sisme-developer-description">
                        Rejoignez notre communauté de développeurs indépendants et partagez vos créations avec les joueurs de Sisme Games.
                    </p>
                </div>
            </div>
            
            <div class="sisme-developer-content">
                <div class="sisme-developer-benefits">
                    <h4 class="sisme-section-subtitle">🎯 Pourquoi devenir développeur ?</h4>
                    <ul class="sisme-benefits-list">
                        <li>
                            <span class="sisme-benefit-icon">🎮</span>
                            <strong>Publiez vos jeux</strong> - Partagez vos créations avec notre communauté
                        </li>
                        <li>
                            <span class="sisme-benefit-icon">📊</span>
                            <strong>Statistiques détaillées</strong> - Suivez les performances de vos jeux
                        </li>
                        <li>
                            <span class="sisme-benefit-icon">👥</span>
                            <strong>Profil développeur</strong> - Présentez votre studio et vos projets
                        </li>
                        <li>
                            <span class="sisme-benefit-icon">🌟</span>
                            <strong>Visibilité</strong> - Bénéficiez de la promotion de vos jeux
                        </li>
                    </ul>
                </div>
                
                <!-- Formulaire de candidature directement dans l'onglet -->
                <div class="sisme-developer-application">
                    <h4 class="sisme-section-subtitle">📝 Candidature Développeur</h4>
                    <p class="sisme-form-intro">
                        Parlez-nous de votre studio et de vos projets. Plus votre candidature sera détaillée, plus nous pourrons l'évaluer rapidement.
                    </p>
                    
                    <form id="sisme-developer-form" class="sisme-application-form">
                        <?php wp_nonce_field('sisme_developer_application', 'sisme_developer_nonce'); ?>
                        
                        <!-- Section Studio -->
                        <div class="sisme-form-section">
                            <h5 class="sisme-form-section-title">🏢 Informations Studio</h5>
                            
                            <div class="sisme-form-row">
                                <div class="sisme-form-field">
                                    <label for="studio_name" class="sisme-form-label">
                                        Nom du studio <span class="sisme-required">*</span>
                                    </label>
                                    <input type="text" 
                                           id="studio_name" 
                                           name="studio_name" 
                                           class="sisme-form-input" 
                                           required 
                                           maxlength="100"
                                           placeholder="Ex: Pixel Dreams Studio">
                                    <span class="sisme-form-error" id="error-studio_name"></span>
                                </div>
                            </div>
                            
                            <div class="sisme-form-row">
                                <div class="sisme-form-field">
                                    <label for="studio_description" class="sisme-form-label">
                                        Description du studio <span class="sisme-required">*</span>
                                    </label>
                                    <textarea id="studio_description" 
                                              name="studio_description" 
                                              class="sisme-form-textarea" 
                                              required 
                                              rows="4"
                                              maxlength="500"
                                              placeholder="Décrivez votre studio, votre vision, vos spécialités..."></textarea>
                                    <span class="sisme-form-help">Maximum 500 caractères</span>
                                    <span class="sisme-form-error" id="error-studio_description"></span>
                                </div>
                            </div>
                            
                            <div class="sisme-form-row">
                                <div class="sisme-form-field">
                                    <label for="studio_website" class="sisme-form-label">
                                        Site web du studio
                                    </label>
                                    <input type="url" 
                                           id="studio_website" 
                                           name="studio_website" 
                                           class="sisme-form-input" 
                                           placeholder="https://votre-studio.com">
                                    <span class="sisme-form-error" id="error-studio_website"></span>
                                </div>
                            </div>
                            
                            <div class="sisme-form-row">
                                <div class="sisme-form-field">
                                    <label class="sisme-form-label">Réseaux sociaux (optionnel)</label>
                                    <div class="sisme-social-inputs">
                                        <div class="sisme-social-input-group">
                                            <span class="sisme-social-icon">🐦</span>
                                            <input type="text" 
                                                   name="social_twitter" 
                                                   class="sisme-form-input sisme-social-input" 
                                                   placeholder="@votre_compte">
                                        </div>
                                        <div class="sisme-social-input-group">
                                            <span class="sisme-social-icon">💬</span>
                                            <input type="text" 
                                                   name="social_discord" 
                                                   class="sisme-form-input sisme-social-input" 
                                                   placeholder="votre_pseudo#1234">
                                        </div>
                                        <div class="sisme-social-input-group">
                                            <span class="sisme-social-icon">📷</span>
                                            <input type="text" 
                                                   name="social_instagram" 
                                                   class="sisme-form-input sisme-social-input" 
                                                   placeholder="@votre_compte">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Section Représentant -->
                        <div class="sisme-form-section">
                            <h5 class="sisme-form-section-title">👤 Informations Représentant</h5>
                            
                            <div class="sisme-form-row sisme-form-row-double">
                                <div class="sisme-form-field">
                                    <label for="representative_firstname" class="sisme-form-label">
                                        Prénom <span class="sisme-required">*</span>
                                    </label>
                                    <input type="text" 
                                           id="representative_firstname" 
                                           name="representative_firstname" 
                                           class="sisme-form-input" 
                                           required 
                                           maxlength="50">
                                    <span class="sisme-form-error" id="error-representative_firstname"></span>
                                </div>
                                <div class="sisme-form-field">
                                    <label for="representative_lastname" class="sisme-form-label">
                                        Nom <span class="sisme-required">*</span>
                                    </label>
                                    <input type="text" 
                                           id="representative_lastname" 
                                           name="representative_lastname" 
                                           class="sisme-form-input" 
                                           required 
                                           maxlength="50">
                                    <span class="sisme-form-error" id="error-representative_lastname"></span>
                                </div>
                            </div>
                            
                            <div class="sisme-form-row">
                                <div class="sisme-form-field">
                                    <label for="representative_birthdate" class="sisme-form-label">
                                        Date de naissance <span class="sisme-required">*</span>
                                    </label>
                                    <input type="date" 
                                           id="representative_birthdate" 
                                           name="representative_birthdate" 
                                           class="sisme-form-input" 
                                           required 
                                           max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                                    <span class="sisme-form-help">Vous devez être majeur pour candidater</span>
                                    <span class="sisme-form-error" id="error-representative_birthdate"></span>
                                </div>
                            </div>
                            
                            <div class="sisme-form-row">
                                <div class="sisme-form-field">
                                    <label for="representative_address" class="sisme-form-label">
                                        Adresse <span class="sisme-required">*</span>
                                    </label>
                                    <textarea id="representative_address" 
                                              name="representative_address" 
                                              class="sisme-form-textarea" 
                                              required 
                                              rows="2"
                                              maxlength="200"
                                              placeholder="Numéro, rue, code postal..."></textarea>
                                    <span class="sisme-form-error" id="error-representative_address"></span>
                                </div>
                            </div>
                            
                            <div class="sisme-form-row sisme-form-row-double">
                                <div class="sisme-form-field">
                                    <label for="representative_city" class="sisme-form-label">
                                        Ville <span class="sisme-required">*</span>
                                    </label>
                                    <input type="text" 
                                           id="representative_city" 
                                           name="representative_city" 
                                           class="sisme-form-input" 
                                           required 
                                           maxlength="100">
                                    <span class="sisme-form-error" id="error-representative_city"></span>
                                </div>
                                <div class="sisme-form-field">
                                    <label for="representative_country" class="sisme-form-label">
                                        Pays <span class="sisme-required">*</span>
                                    </label>
                                    <select id="representative_country" 
                                            name="representative_country" 
                                            class="sisme-form-select" 
                                            required>
                                        <option value="">Sélectionnez un pays</option>
                                        <option value="FR">France</option>
                                        <option value="BE">Belgique</option>
                                        <option value="CH">Suisse</option>
                                        <option value="CA">Canada</option>
                                        <option value="US">États-Unis</option>
                                        <option value="GB">Royaume-Uni</option>
                                        <option value="DE">Allemagne</option>
                                        <option value="ES">Espagne</option>
                                        <option value="IT">Italie</option>
                                        <option value="OTHER">Autre</option>
                                    </select>
                                    <span class="sisme-form-error" id="error-representative_country"></span>
                                </div>
                            </div>
                            
                            <div class="sisme-form-row sisme-form-row-double">
                                <div class="sisme-form-field">
                                    <label for="representative_email" class="sisme-form-label">
                                        Email de contact <span class="sisme-required">*</span>
                                    </label>
                                    <input type="email" 
                                           id="representative_email" 
                                           name="representative_email" 
                                           class="sisme-form-input" 
                                           required 
                                           placeholder="contact@votre-studio.com">
                                    <span class="sisme-form-error" id="error-representative_email"></span>
                                </div>
                                <div class="sisme-form-field">
                                    <label for="representative_phone" class="sisme-form-label">
                                        Téléphone <span class="sisme-required">*</span>
                                    </label>
                                    <input type="tel" 
                                           id="representative_phone" 
                                           name="representative_phone" 
                                           class="sisme-form-input" 
                                           required 
                                           placeholder="+33 1 23 45 67 89">
                                    <span class="sisme-form-error" id="error-representative_phone"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="sisme-form-actions">
                            <button type="submit" 
                                    class="sisme-btn sisme-btn-primary sisme-btn-large" 
                                    id="sisme-submit-application">
                                📤 Envoyer ma candidature
                            </button>
                        </div>
                        
                        <!-- Zone de feedback -->
                        <div id="sisme-form-feedback" class="sisme-form-feedback" style="display: none;"></div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * État 2 : Candidature en cours (statut 'pending')
     */
    private static function render_pending_status($user_id) {
        ob_start();
        ?>
        <div class="sisme-developer-state sisme-developer-state-pending">
            <div class="sisme-developer-header">
                <div class="sisme-developer-icon">
                    <span class="sisme-developer-icon-main">⏳</span>
                </div>
                <div class="sisme-developer-intro">
                    <h3 class="sisme-developer-title">Candidature en cours</h3>
                    <p class="sisme-developer-description">
                        Votre candidature est en cours d'examen par notre équipe.
                    </p>
                </div>
            </div>
            
            <div class="sisme-developer-content">
                <div class="sisme-status-card">
                    <div class="sisme-status-icon">📋</div>
                    <div class="sisme-status-info">
                        <h4>Candidature soumise</h4>
                        <p>Nous examinerons votre demande dans les 48h</p>
                    </div>
                </div>
                
                <div class="sisme-pending-actions">
                    <button class="sisme-btn sisme-btn-secondary" onclick="SismeDeveloper.showApplicationDetails()">
                        📄 Voir ma candidature
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * État 3 : Développeur approuvé (statut 'approved')
     */
    private static function render_developer_dashboard($user_id) {
        ob_start();
        ?>
        <div class="sisme-developer-state sisme-developer-state-approved">
            <div class="sisme-developer-header">
                <div class="sisme-developer-icon">
                    <span class="sisme-developer-icon-main">🎮</span>
                </div>
                <div class="sisme-developer-intro">
                    <h3 class="sisme-developer-title">Mes Jeux</h3>
                    <p class="sisme-developer-description">
                        Gérez vos jeux publiés et soumettez de nouveaux projets.
                    </p>
                </div>
            </div>
            
            <div class="sisme-developer-content">
                <div class="sisme-developer-stats">
                    <div class="sisme-stat-card">
                        <span class="sisme-stat-icon">🎯</span>
                        <span class="sisme-stat-number">0</span>
                        <span class="sisme-stat-label">Jeux publiés</span>
                    </div>
                    <div class="sisme-stat-card">
                        <span class="sisme-stat-icon">⏳</span>
                        <span class="sisme-stat-number">0</span>
                        <span class="sisme-stat-label">En attente</span>
                    </div>
                    <div class="sisme-stat-card">
                        <span class="sisme-stat-icon">👁️</span>
                        <span class="sisme-stat-number">0</span>
                        <span class="sisme-stat-label">Vues totales</span>
                    </div>
                </div>
                
                <div class="sisme-developer-actions">
                    <button class="sisme-btn sisme-btn-primary">
                        ➕ Soumettre un jeu
                    </button>
                    <button class="sisme-btn sisme-btn-secondary">
                        📊 Statistiques détaillées
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * État 4 : Candidature rejetée (statut 'rejected')
     */
    private static function render_rejected_status($user_id) {
        ob_start();
        ?>
        <div class="sisme-developer-state sisme-developer-state-rejected">
            <div class="sisme-developer-header">
                <div class="sisme-developer-icon">
                    <span class="sisme-developer-icon-main">❌</span>
                </div>
                <div class="sisme-developer-intro">
                    <h3 class="sisme-developer-title">Candidature non retenue</h3>
                    <p class="sisme-developer-description">
                        Votre candidature n'a pas été retenue cette fois-ci.
                    </p>
                </div>
            </div>
            
            <div class="sisme-developer-content">
                <div class="sisme-rejection-info">
                    <h4>💡 Conseils pour une prochaine candidature</h4>
                    <ul class="sisme-tips-list">
                        <li>Présentez des projets terminés et jouables</li>
                        <li>Détaillez votre expérience en développement</li>
                        <li>Montrez votre motivation pour la communauté</li>
                        <li>Assurez-vous que votre portfolio est accessible</li>
                    </ul>
                </div>
                
                <div class="sisme-retry-actions">
                    <button class="sisme-btn sisme-btn-primary" onclick="SismeDeveloper.showApplicationForm()">
                        🔄 Faire une nouvelle demande
                    </button>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}