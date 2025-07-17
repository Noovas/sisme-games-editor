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
                <!-- Section existante - Stats -->
                <?php if (!class_exists('Sisme_Submission_Database')) {
                    require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
                }

                $user_submissions = Sisme_Submission_Database::get_user_submissions($user_id);
                $stats = [
                    'published' => 0,
                    'pending' => 0,
                    'total_views' => 0
                ];

                foreach ($user_submissions as $submission) {
                    if ($submission->status === 'published') {
                        $stats['published']++;
                        // TODO: Ajouter vraies vues depuis les analytics
                    } elseif ($submission->status === 'pending') {
                        $stats['pending']++;
                    }
                }
                ?>

                <div class="sisme-developer-stats">
                    <div class="sisme-stat-card">
                        <span class="sisme-stat-icon">🎯</span>
                        <span class="sisme-stat-number"><?php echo $stats['published']; ?></span>
                        <span class="sisme-stat-label">Jeux publiés</span>
                    </div>
                    <div class="sisme-stat-card">
                        <span class="sisme-stat-icon">⏳</span>
                        <span class="sisme-stat-number"><?php echo $stats['pending']; ?></span>
                        <span class="sisme-stat-label">En attente</span>
                    </div>
                    <!--
                    <div class="sisme-stat-card">
                        <span class="sisme-stat-icon">👁️</span>
                        <span class="sisme-stat-number"><?php echo $stats['total_views']; ?></span>
                        <span class="sisme-stat-label">Vues totales</span>
                    </div>-->
                </div>
                
                <!-- Section existante - Actions rapides -->
                <div class="sisme-developer-actions">
                    <button class="sisme-button sisme-button-vert" onclick="SismeDeveloper.startNewSubmission()">
                        ➕ Soumettre un jeu
                    </button>
                    <button class="sisme-btn sisme-btn-secondary sisme-disabled">
                        📊 Statistiques détaillées
                    </button>
                </div>
                
                <!-- SECTION : Mes Jeux -->
                <?php echo self::render_my_games_section($user_id); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * État 4 : Candidature rejetée (statut 'rejected')
     */
    private static function render_rejected_status($user_id) {
        // Récupérer les données de candidature pour afficher les notes admin si disponibles
        $application_data = Sisme_User_Developer_Data_Manager::get_developer_application($user_id);
        $admin_notes = '';
        
        if ($application_data && !empty($application_data[Sisme_Utils_Users::APPLICATION_FIELD_ADMIN_NOTES])) {
            $admin_notes = $application_data[Sisme_Utils_Users::APPLICATION_FIELD_ADMIN_NOTES];
        }
        
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
                        Votre candidature n'a pas été retenue cette fois-ci. Vous pouvez néanmoins refaire une demande en tenant compte des conseils ci-dessous.
                    </p>
                </div>
            </div>
            
            <div class="sisme-developer-content">
                <?php if (!empty($admin_notes)): ?>
                <div class="sisme-admin-feedback">
                    <h4>📝 Commentaires de l'équipe</h4>
                    <div class="sisme-admin-notes">
                        <?php echo wp_kses_post(wpautop($admin_notes)); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="sisme-rejection-info">
                    <h4>💡 Conseils pour une prochaine candidature</h4>
                    <ul class="sisme-tips-list">
                        <li>Présentez des projets terminés et jouables</li>
                        <li>Détaillez votre expérience en développement</li>
                        <li>Montrez votre motivation pour la communauté</li>
                        <li>Assurez-vous que votre portfolio est accessible</li>
                        <li>Répondez précisément à toutes les questions du formulaire</li>
                    </ul>
                </div>
                
                <div class="sisme-retry-actions">
                    <button id="sisme-retry-application" class="sisme-btn sisme-btn-primary" type="button">
                        🔄 Faire une nouvelle demande
                    </button>
                    <div id="sisme-retry-feedback" class="sisme-form-feedback" style="display: none;"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Rendu d'un item de jeu
     */
    private static function render_game_item($submission, $context) {
        $game_data = $submission->game_data_decoded;
        $game_name = $game_data['game_name'] ?? 'Jeu sans titre';
        $completion = $game_data['metadata']['completion_percentage'] ?? 0;
        $last_updated = human_time_diff(strtotime($submission->updated_at), current_time('timestamp')) . ' ago';
        
        // Calculer le temps depuis soumission pour les pending
        $pending_days = '';
        if ($submission->status === 'pending' && $submission->submitted_at) {
            $days = floor((current_time('timestamp') - strtotime($submission->submitted_at)) / DAY_IN_SECONDS);
            $pending_days = $days > 0 ? "({$days}j)" : "(aujourd'hui)";
        }
        
        ob_start();
        ?>
        <div class="sisme-game-item sisme-game-item-<?php echo esc_attr($submission->status); ?>" data-submission-id="<?php echo esc_attr($submission->id); ?>">
            <div class="sisme-game-info">
                <div class="sisme-game-title">
                    <span class="sisme-game-name"><?php echo esc_html($game_name); ?></span>
                    <?php if ($context === 'draft' && $completion > 0): ?>
                        <span class="sisme-game-progress">(<?php echo esc_html($completion); ?>%)</span>
                    <?php endif; ?>
                    <?php if ($context === 'pending' && $pending_days): ?>
                        <span class="sisme-game-pending-time"><?php echo esc_html($pending_days); ?></span>
                    <?php endif; ?>
                </div>
                <div class="sisme-game-meta">
                    <span class="sisme-game-updated">Mis à jour <?php echo esc_html($last_updated); ?></span>
                    <span class="sisme-game-status sisme-status-<?php echo esc_attr($submission->status); ?>">
                        <?php echo self::get_status_label($submission->status); ?>
                    </span>
                </div>
            </div>
            
            <div class="sisme-game-actions">
                <?php echo self::render_game_actions($submission, $context); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Actions pour chaque jeu selon son statut
     */
    private static function render_game_actions($submission, $context) {
        ob_start();
        
        switch ($submission->status) {
            case 'draft':
            case 'revision':
                ?>
                <button class="sisme-btn sisme-btn-small sisme-btn-primary" onclick="SismeDeveloper.continueSubmission(<?php echo esc_attr($submission->id); ?>)">
                    ✏️ Continuer
                </button>
                <button class="sisme-btn sisme-btn-small sisme-btn-danger" onclick="SismeDeveloper.deleteSubmission(<?php echo esc_attr($submission->id); ?>)">
                    🗑️ Supprimer
                </button>
                <?php
                break;
                
            case 'pending':
                ?>
                <button class="sisme-btn sisme-btn-small sisme-btn-secondary" onclick="SismeDeveloper.viewSubmission(<?php echo esc_attr($submission->id); ?>)">
                    👁️ Voir
                </button>
                <?php
                break;
                
            case 'published':
                ?>
                <button class="sisme-btn sisme-btn-small sisme-btn-secondary" onclick="SismeDeveloper.viewPublishedGame(<?php echo esc_attr($submission->id); ?>)">
                    🔗 Voir la page
                </button>
                <button class="sisme-btn sisme-btn-small sisme-btn-tertiary" onclick="SismeDeveloper.viewStats(<?php echo esc_attr($submission->id); ?>)">
                    📊 Statistiques
                </button>
                <?php
                break;
                
            case 'rejected':
                ?>
                <button class="sisme-btn sisme-btn-small sisme-btn-secondary" onclick="SismeDeveloper.viewRejectionNotes(<?php echo esc_attr($submission->id); ?>)">
                    📄 Voir les notes
                </button>
                <button class="sisme-btn sisme-btn-small sisme-btn-primary" onclick="SismeDeveloper.retrySubmission(<?php echo esc_attr($submission->id); ?>)">
                    🔄 Réessayer
                </button>
                <?php
                break;
        }
        
        return ob_get_clean();
    }

    /**
     * Labels des statuts
     */
    private static function get_status_label($status) {
        $labels = [
            'draft' => 'Brouillon',
            'revision' => 'À réviser',
            'pending' => 'En attente',
            'published' => 'Publié',
            'rejected' => 'Rejeté'
        ];
        
        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Rendu de la section "Mes Jeux"
     */
    private static function render_my_games_section($user_id) {
        // Ensure submission database is available
        if (!class_exists('Sisme_Submission_Database')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
        }
        
        // Récupérer les soumissions de l'utilisateur
        $user_submissions = Sisme_Submission_Database::get_user_submissions($user_id);
        
        // Organiser par statut
        $submissions_by_status = [
            'draft' => [],
            'revision' => [],
            'pending' => [],
            'published' => [],
            'rejected' => []
        ];
        
        foreach ($user_submissions as $submission) {
            if (isset($submissions_by_status[$submission->status])) {
                $submissions_by_status[$submission->status][] = $submission;
            }
        }
        
        ob_start();
        ?>
        <div class="sisme-my-games-section">
            <div class="sisme-my-games-header">
                <h4 class="sisme-my-games-title">🎮 Mes Soumissions</h4>
                <p class="sisme-my-games-subtitle">Gérez vos jeux en cours de développement et publiés</p>
            </div>
            
            <div class="sisme-my-games-content">
                
                <!-- Brouillons et révisions -->
                <?php if (!empty($submissions_by_status['draft']) || !empty($submissions_by_status['revision'])): ?>
                <div class="sisme-games-group sisme-games-drafts">
                    <h5 class="sisme-games-group-title">📝 BROUILLONS & RÉVISIONS (<?php echo count($submissions_by_status['draft']) + count($submissions_by_status['revision']); ?>)</h5>
                    <div class="sisme-games-list">
                        <?php foreach (array_merge($submissions_by_status['draft'], $submissions_by_status['revision']) as $submission): ?>
                            <?php echo self::render_game_item($submission, 'draft'); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- En attente -->
                <?php if (!empty($submissions_by_status['pending'])): ?>
                <div class="sisme-games-group sisme-games-pending">
                    <h5 class="sisme-games-group-title">⏳ EN ATTENTE (<?php echo count($submissions_by_status['pending']); ?>)</h5>
                    <div class="sisme-games-list">
                        <?php foreach ($submissions_by_status['pending'] as $submission): ?>
                            <?php echo self::render_game_item($submission, 'pending'); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Publiés -->
                <?php if (!empty($submissions_by_status['published'])): ?>
                <div class="sisme-games-group sisme-games-published">
                    <h5 class="sisme-games-group-title">✅ PUBLIÉS (<?php echo count($submissions_by_status['published']); ?>)</h5>
                    <div class="sisme-games-list">
                        <?php foreach ($submissions_by_status['published'] as $submission): ?>
                            <?php echo self::render_game_item($submission, 'published'); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Rejetés -->
                <?php if (!empty($submissions_by_status['rejected'])): ?>
                <div class="sisme-games-group sisme-games-rejected">
                    <h5 class="sisme-games-group-title">❌ REJETÉS (<?php echo count($submissions_by_status['rejected']); ?>)</h5>
                    <div class="sisme-games-list">
                        <?php foreach ($submissions_by_status['rejected'] as $submission): ?>
                            <?php echo self::render_game_item($submission, 'rejected'); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Aucune soumission -->
                <div class="sisme-games-empty" style="<?php echo empty($user_submissions) ? 'display: block;' : 'display: none;'; ?>">
                    <div class="sisme-empty-icon">🎮</div>
                    <h5>Aucun jeu soumis</h5>
                    <p>Commencez par soumettre votre premier jeu en utilisant le bouton ci-dessus !</p>
                </div>
                
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Section Soumettre un jeu (nouvel onglet invisible au menu)
     */
    public static function render_submit_game_section($user_id, $dashboard_data) {
        if (!Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
            return '<div class="sisme-access-denied"><p>Accès refusé. Vous devez être un développeur approuvé.</p></div>';
        }
        
        ob_start();
        ?>
        <div class="sisme-submit-game-section">
            <div class="sisme-submit-game-content">
                <div class="sisme-empty-state">
                    <div class="sisme-empty-icon">📝</div>
                    <h3>Formulaire de soumission</h3>
                    <p>Le formulaire complet de soumission de jeu sera implémenté ici prochainement.</p>
                    <div class="sisme-placeholder-info">
                        <h4>Fonctionnalités à venir :</h4>
                        <ul>
                            <li>✅ Formulaire multi-étapes</li>
                            <li>✅ Validation temps réel</li>
                            <li>✅ Sauvegarde automatique</li>
                            <li>✅ Upload et crop d'images</li>
                            <li>✅ Prévisualisation du jeu</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}