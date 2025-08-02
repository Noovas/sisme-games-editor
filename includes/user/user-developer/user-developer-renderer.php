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
                            <strong>Publiez vos jeux</strong>Partagez vos créations avec notre communauté
                        </li>
                        <li>
                            <span class="sisme-benefit-icon">📊</span>
                            <strong>Statistiques détaillées</strong>Suivez les performances de vos jeux
                        </li>
                        <li>
                            <span class="sisme-benefit-icon">👥</span>
                            <strong>Profil développeur</strong>Présentez votre studio et vos projets
                        </li>
                        <li>
                            <span class="sisme-benefit-icon">🌟</span>
                            <strong>Visibilité</strong>Bénéficiez de la promotion de vos jeux
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
                                            <span class="sisme-social-icon"></span>
                                            <input type="text" 
                                                   name="social_twitter" 
                                                   class="sisme-form-input sisme-social-input" 
                                                   placeholder="https://x.com/votre_compte">
                                        </div>
                                        <div class="sisme-social-input-group">
                                            <span class="sisme-social-icon"></span>
                                            <input type="text" 
                                                   name="social_discord" 
                                                   class="sisme-form-input sisme-social-input" 
                                                   placeholder="https://discord.gg/gmC7FAZDm6">
                                        </div>
                                        <div class="sisme-social-input-group">
                                            <span class="sisme-social-icon"></span>
                                            <input type="text" 
                                                   name="social_instagram" 
                                                   class="sisme-form-input sisme-social-input" 
                                                   placeholder="https://www.instagram.com/votre_compte">
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
                                        Email <span class="sisme-required">*</span>
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
                <!-- Section Mes Jeux -->
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
    public static function render_my_games_section($user_id) {
        // Charger le nouveau renderer
        if (!class_exists('Sisme_Game_Submission_Renderer')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-renderer.php';
        }
        
        // Utiliser le nouveau renderer au lieu du placeholder
        return Sisme_Game_Submission_Renderer::render_submissions_list($user_id);
    }

    /**
     * Rendu de la section soumission de jeu
     * @param int $user_id ID utilisateur
     * @param string $developer_status Statut développeur
     * @param array $dashboard_data Données dashboard
     * @return string HTML de la section soumission
     */
    public static function render_submit_game_section($user_id, $developer_status, $dashboard_data) {
        if ($developer_status !== 'approved') {
            return '<p>Vous devez être un développeur approuvé pour soumettre des jeux.</p>';
        }
        
        ob_start();
        ?>
        <div class="sisme-submit-game-section">
            <div class="sisme-section-header">
                <div class="sisme-submit-game-intro">
                    <h3 class="sisme-section-title">➕ Soumettre un nouveau jeu</h3>
                    <p class="sisme-submit-game-description">
                        Partagez votre création avec la communauté Sisme Games. Remplissez les informations essentielles pour commencer.
                    </p>
                </div>
            </div>
            
            <div class="sisme-submit-game-content">
                <form id="sisme-submit-game-form" class="sisme-submit-game-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('sisme_submit_game_nonce', 'sisme_submit_game_nonce'); ?>
                    
                    <div class="sisme-form-section">
                        <h4 class="sisme-form-section-title">📋 Informations sur le jeu</h4>
                        <!-- Nom du jeu -->
                        <div class="sisme-form-field">
                            <label for="game_name" class="sisme-form-label sisme-form-section-title">
                                Nom du jeu <span class="sisme-required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="game_name" 
                                name="game_name" 
                                class="sisme-form-input"
                                placeholder="Le nom de votre jeu"
                                maxlength="100"
                                required
                            >
                        </div>
                        <!-- Description du jeu -->
                        <div class="sisme-form-field">
                            <label for="game_description" class="sisme-form-label sisme-form-section-title">
                                Description courte <span class="sisme-required">*</span>
                            </label>
                            <textarea 
                                id="game_description" 
                                name="game_description" 
                                class="sisme-form-textarea"
                                placeholder="Décrivez votre jeu en 180 caractères maximum"
                                maxlength="180"
                                rows="6"
                                required
                            ></textarea>
                        </div>
                        <!-- Date de sortie -->
                        <div class="sisme-form-field">
                            <label for="game_release_date" class="sisme-form-label sisme-form-section-title">
                                Date de sortie <span class="sisme-required">*</span>
                            </label>
                            <input 
                                type="date" 
                                id="game_release_date" 
                                name="game_release_date" 
                                class="sisme-form-input"
                                required
                            >
                        </div>
                    </div>
                    <div class="sisme-form-section">
                        <h4 class="sisme-form-section-title">📋 Liens Utiles</h4>

                        <!-- Lien Teaser Youtube -->
                        <div class="sisme-form-field">
                            <label for="game_trailer" class="sisme-form-label sisme-form-section-title">
                                Teaser Youtube <span class="sisme-required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="game_trailer" 
                                name="game_trailer" 
                                class="sisme-form-input"
                                placeholder="https://www.youtube.com/watch?v=..."
                                required
                            >
                        </div>

                        <!-- Nom du studio de développement -->
                        <div class="sisme-form-field">
                            <label for="game_studio_name" class="sisme-form-label sisme-form-section-title">
                                Nom et site du studio de développement <span class="sisme-required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="game_studio_name" 
                                name="game_studio_name" 
                                class="sisme-form-input"
                                placeholder="Nom du Studio"
                                required
                            >
                        </div>
                        <!-- Lien site web developpeur (optionnel) -->
                        <div class="sisme-form-field">
                            <input 
                                type="text" 
                                id="game_studio_url" 
                                name="game_studio_url" 
                                class="sisme-form-input"
                                placeholder="https://www.site-du-studio.com"
                            >
                        </div>

                        <!-- Nom de l'éditeur -->
                        <div class="sisme-form-field">
                            <label for="game_publisher_name" class="sisme-form-label sisme-form-section-title">
                                Nom et site de l'éditeur <span class="sisme-required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="game_publisher_name" 
                                name="game_publisher_name" 
                                class="sisme-form-input"
                                placeholder="Nom de l'éditeur"
                                required
                            >
                        </div>
                        <!-- Lien site web éditeur (optionnel) -->
                        <div class="sisme-form-field">
                            <input 
                                type="text" 
                                id="game_publisher_url" 
                                name="game_publisher_url" 
                                class="sisme-form-input"
                                placeholder="https://www.site-de-l-editeur.com"
                            >
                        </div>
                    </div>

                        

                    <!-- Section Catégories -->
                    <div class="sisme-form-section">
                        <h4 class="sisme-form-section-title">🏷️ Catégories</h4>
                        
                        <!-- Genres - Récupération depuis les catégories WordPress -->
                        <div class="sisme-form-field">
                            <label class="sisme-form-label sisme-form-section-title">
                                Genres <span class="sisme-required">*</span>
                            </label>
                            <div class="sisme-checkbox-group">
                                <?php
                                $available_genres = Sisme_User_Preferences_Data_Manager::get_available_genres();
                                foreach ($available_genres as $genre): ?>
                                    <div class="sisme-checkbox-item">
                                        <input type="checkbox" 
                                            id="genre_<?php echo esc_attr($genre['id']); ?>" 
                                            name="game_genres[]" 
                                            value="<?php echo esc_attr($genre['id']); ?>">
                                        <label for="genre_<?php echo esc_attr($genre['id']); ?>"><?php echo esc_html($genre['name']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Modes de jeu - Depuis les constantes du système -->
                        <div class="sisme-form-field">
                            <label class="sisme-form-label sisme-form-section-title">
                                Modes de jeu <span class="sisme-required">*</span>
                            </label>
                            <div class="sisme-checkbox-group">
                                <?php
                                $mode_labels = array(
                                    'solo' => 'Solo',
                                    'multijoueur' => 'Multijoueur',
                                    'coop' => 'Coopération',
                                    'competitif' => 'Compétitif'
                                );
                                foreach ($mode_labels as $mode_key => $mode_label): ?>
                                    <div class="sisme-checkbox-item">
                                        <input type="checkbox" 
                                            id="mode_<?php echo esc_attr($mode_key); ?>" 
                                            name="game_modes[]" 
                                            value="<?php echo esc_attr($mode_key); ?>">
                                        <label for="mode_<?php echo esc_attr($mode_key); ?>"><?php echo esc_html($mode_label); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Plateformes - Depuis les constantes du système -->
                        <div class="sisme-form-field">
                            <label class="sisme-form-label sisme-form-section-title">
                                Plateformes <span class="sisme-required">*</span>
                            </label>
                            <div class="sisme-checkbox-group">
                                <?php
                                $platform_groups = array(
                                    'pc' => array(
                                        'platforms' => array('windows', 'mac', 'linux'),
                                        'icon' => '🖥️',
                                        'names' => array('windows' => 'Windows', 'mac' => 'macOS', 'linux' => 'Linux')
                                    ),
                                    'console' => array(
                                        'platforms' => array('xbox', 'playstation', 'switch'),
                                        'icon' => '🎮',
                                        'names' => array('xbox' => 'Xbox', 'playstation' => 'PlayStation', 'switch' => 'Switch')
                                    ),
                                    'mobile' => array(
                                        'platforms' => array('ios', 'android'),
                                        'icon' => '📱',
                                        'names' => array('ios' => 'iOS', 'android' => 'Android')
                                    ),
                                    'web' => array(
                                        'platforms' => array('web'),
                                        'icon' => '🌐',
                                        'names' => array('web' => 'Web')
                                    )
                                );
                                
                                foreach ($platform_groups as $group_key => $group_data):
                                    foreach ($group_data['platforms'] as $platform_key): ?>
                                        <div class="sisme-checkbox-item">
                                            <input type="checkbox" 
                                                id="platform_<?php echo esc_attr($platform_key); ?>" 
                                                name="game_platforms[]" 
                                                value="<?php echo esc_attr($platform_key); ?>">
                                            <label for="platform_<?php echo esc_attr($platform_key); ?>">
                                                <?php echo $group_data['icon']; ?> <?php echo esc_html($group_data['names'][$platform_key]); ?>
                                            </label>
                                        </div>
                                    <?php endforeach;
                                endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Section Liens d'achat -->
                    <div class="sisme-form-section">
                        <h4 class="sisme-form-section-title">🛒 Liens d'achat</h4>
                        <div class="sisme-form-field">
                            <?php
                            $store_platforms = array(
                                'steam' => array('name' => 'Steam', 'logo' => 'Logo-STEAM.webp'),
                                'epic' => array('name' => 'Epic Games', 'logo' => 'Logo-EPIC.webp'),
                                'gog' => array('name' => 'GOG', 'logo' => 'Logo-GOG.webp')
                            );
                            
                            foreach ($store_platforms as $platform_key => $platform_data): ?>
                                <div class="sisme-link-item">
                                    <div class="sisme-link-platform">
                                        <img src="https://games.sisme.fr/wp-content/uploads/2025/06/<?php echo esc_attr($platform_data['logo']); ?>" 
                                            alt="<?php echo esc_attr($platform_data['name']); ?>" 
                                            class="sisme-store-logo">
                                    </div>
                                    <input 
                                        type="url" 
                                        id="link_<?php echo esc_attr($platform_key); ?>" 
                                        name="external_links[<?php echo esc_attr($platform_key); ?>]" 
                                        class="sisme-form-input sisme-link-input"
                                        placeholder="https://store.<?php echo esc_attr($platform_key); ?>.com/..."
                                    >
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Section upload d'images -->
                    <div class="sisme-form-section">
                        <h4 class="sisme-form-section-title">🖼️ Galerie d'images</h4>
                        
                        <!-- Cover horizontale -->
                        <div class="sisme-form-field">
                            <label class="sisme-form-label sisme-form-section-title">
                                Cover horizontale <span class="sisme-required">*</span>
                            </label>
                            <div id="cropper1" data-simple-cropper data-ratio-type="cover_horizontal"></div>
                            <input type="hidden" name="cover_horizontal_attachment_id" id="cover_horizontal_attachment_id" value="">
                        </div>

                        <!-- Cover verticale -->
                        <div class="sisme-form-field">
                            <label class="sisme-form-label sisme-form-section-title">
                                Cover verticale <span class="sisme-required">*</span>
                            </label>
                            <div id="cropper2" data-simple-cropper data-ratio-type="cover_vertical"></div>
                            <input type="hidden" name="cover_vertical_attachment_id" id="cover_vertical_attachment_id" value="">
                        </div>

                        <!-- Screenshots (minimum 1, maximum selon statut du dev) -->
                        <div class="sisme-form-field">
                            <label class="sisme-form-label sisme-form-section-title">
                                Screenshots <span class="sisme-required">*</span>
                            </label>
                            <div id="cropper3" data-simple-cropper data-ratio-type="screenshot" data-max-images="<?php echo Sisme_Utils_Users::GAME_MAX_SCREENSHOTS; ?>"></div>
                            <input type="hidden" name="screenshots_attachment_ids" id="screenshots_attachment_ids" value="">
                        </div>
                    </div>

                    <!-- Description longue du jeu -->
                    <div class="sisme-form-section">
                        <h4 class="sisme-form-section-title">📖 Description longue du jeu</h4>
                        <p class="sisme-form-section-description">
                            Créez une présentation détaillée de votre jeu avec des sections personnalisées. 
                            Chaque section peut contenir un titre, du texte et une image optionnelle.
                        </p>
                        
                        <!-- Container des sections -->
                        <div id="game-sections-container" class="sisme-game-sections-container">
                            <!-- Section par défaut -->
                            <div class="sisme-section-item" data-section-index="0">
                                <div class="sisme-section-item-header">
                                    <h5 class="sisme-section-item-title sisme-form-section-title">Section 1 <span style="color: #dc3545;">*</span></h5>
                                </div>
                                
                                <div class="sisme-section-item-body">
                                    <div class="sisme-form-field">
                                        <label class="sisme-form-label">Titre de la section <span style="color: #dc3545;">*</span></label>
                                        <input type="text" 
                                            name="sections[0][title]" 
                                            class="sisme-form-input section-title-input"
                                            placeholder="Ex: Gameplay, Histoire, Caractéristiques..."
                                            maxlength="100"
                                            required>
                                    </div>
                                    
                                    <div class="sisme-form-field">
                                        <label class="sisme-form-label">Contenu de la section <span style="color: #dc3545;">*</span></label>
                                        <textarea name="sections[0][content]" 
                                                class="sisme-form-textarea section-content-textarea"
                                                placeholder="Décrivez cette partie de votre jeu... (minimum 20 caractères)"
                                                rows="4"
                                                required></textarea>
                                    </div>
                                    
                                    <div class="sisme-form-field sisme-cropper-container">
                                        <label class="sisme-form-label">Image de la section (optionnel)</label>
                                        <div class="sisme-section-image-upload" data-section-index="0">
                                            <div class="sisme-upload-area">
                                                <input type="file" 
                                                    accept="image/*,image/gif" 
                                                    class="sisme-section-image-input"
                                                    data-section-index="0">
                                                <div class="sisme-upload-info">
                                                    <span class="sisme-upload-icon">🖼️</span>
                                                    <span class="sisme-upload-text">Cliquez pour ajouter une image</span>
                                                    <span class="sisme-upload-hint">JPG, PNG ou GIF</span>
                                                </div>
                                            </div>
                                            <div class="sisme-section-image-preview" style="display: none;">
                                                <img class="sisme-section-preview-img" src="" alt="Aperçu">
                                                <button type="button" class="sisme-remove-section-image" title="Supprimer l'image">❌</button>
                                                <input type="hidden" name="sections[0][image_id]" class="section-image-id">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Bouton ajouter section -->
                        <div class="sisme-add-section-container">
                            <button type="button" id="add-game-section" class="sisme-btn sisme-button-bleu">
                                ➕ Ajouter une section
                            </button>
                            <p class="sisme-add-section-hint">Maximum 10 sections</p>
                        </div>
                    </div>
    
                    <!-- Bouton de soumission conditionnel -->
                    <div class="sisme-form-section">
                        <div class="sisme-form-actions">
                            <button type="button" class="sisme-btn sisme-button-vert" id="sisme-submit-game-btn">
                                💾 Enregistrer le brouillon
                            </button>
                            <button type="button" class="sisme-btn sisme-button-bleu" onclick="window.location.replace(window.location.pathname+'#developer');">
                                ↩️ Retour à mes jeux
                            </button>
                        </div>
                        <div class="sisme-form-submit-container">
                            <button 
                                type="button" 
                                id="sisme-submit-game-button" 
                                class="sisme-btn sisme-btn-submit sisme-btn-disabled" 
                                disabled
                            >
                                📝 Complétez le formulaire
                            </button>
                            
                            <div class="sisme-submit-info">
                                <p class="sisme-submit-description">
                                    Votre jeu sera examiné par notre équipe avant publication. 
                                    Assurez-vous que toutes les informations sont correctes car
                                    vous ne pourrez pas les modifier après la soumission tant
                                    qu'elle ne sera pas étudiée.
                                </p>
                                <div class="sisme-validation-summary" id="sisme-validation-summary">
                                    <!-- Résumé de validation sera injecté ici par JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="sisme-submit-game-feedback" class="sisme-form-feedback" style="display: none;"></div>
                </form>
            </div>
        </div>
        <?php
        echo Sisme_Game_Submission_Renderer::render_submission_modal();
        return ob_get_clean();
    }
}