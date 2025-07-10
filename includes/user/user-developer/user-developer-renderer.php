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
                
                <div class="sisme-developer-examples">
                    <h4 class="sisme-section-subtitle">🏆 Exemples de développeurs</h4>
                    <div class="sisme-examples-grid">
                        <div class="sisme-example-card">
                            <span class="sisme-example-avatar">🎨</span>
                            <div class="sisme-example-info">
                                <strong>Studio Pixel</strong>
                                <span class="sisme-example-games">12 jeux publiés</span>
                            </div>
                        </div>
                        <div class="sisme-example-card">
                            <span class="sisme-example-avatar">🚀</span>
                            <div class="sisme-example-info">
                                <strong>Indie Dreams</strong>
                                <span class="sisme-example-games">8 jeux publiés</span>
                            </div>
                        </div>
                        <div class="sisme-example-card">
                            <span class="sisme-example-avatar">🎯</span>
                            <div class="sisme-example-info">
                                <strong>GameCraft</strong>
                                <span class="sisme-example-games">15 jeux publiés</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="sisme-developer-action">
                    <button class="sisme-btn sisme-btn-primary sisme-btn-large" onclick="SismeDeveloper.showApplicationForm()">
                        📝 Faire une demande
                    </button>
                    <p class="sisme-developer-note">
                        <span class="sisme-note-icon">ℹ️</span>
                        Votre candidature sera examinée par notre équipe dans les 48h
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Formulaire de candidature (masqué par défaut) -->
        <div id="sisme-developer-application-form" class="sisme-developer-form-container" style="display: none;">
            <div class="sisme-developer-form">
                <div class="sisme-form-header">
                    <h3 class="sisme-form-title">📝 Candidature Développeur</h3>
                    <button class="sisme-form-close" onclick="SismeDeveloper.hideApplicationForm()">×</button>
                </div>
                
                <div class="sisme-form-content">
                    <p class="sisme-form-intro">
                        Parlez-nous de votre studio et de vos projets. Plus votre candidature sera détaillée, plus nous pourrons l'évaluer rapidement.
                    </p>
                    
                    <!-- Le formulaire détaillé sera ajouté dans la prochaine étape -->
                    <div class="sisme-form-placeholder">
                        <p>🚧 Formulaire en cours de développement</p>
                        <p>Les champs de candidature seront ajoutés dans la prochaine étape.</p>
                    </div>
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