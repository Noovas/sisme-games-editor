<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/game-submission/game-submission-renderer.php
 * Renderer pour les soumissions de jeux
 * 
 * RESPONSABILITÉ:
 * - Rendu formulaire de soumission de jeux (copie exacte de l'existant)
 * - Rendu liste des soumissions utilisateur
 * - Widgets et statistiques développeur
 * - Interface utilisateur pour gestion des soumissions
 * 
 * DÉPENDANCES:
 * - game-submission-data-manager.php
 * - Sisme_Utils_Users (constantes)
 * - Sisme_Utils_Games (métadonnées)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_Submission_Renderer {
    
    /**
     * Formulaire de soumission de jeu (APPEL fonction existante)
     * @param int $user_id ID utilisateur
     * @param string $submission_id ID soumission (null pour nouveau)
     * @return string HTML du formulaire
     */
    public static function render_submission_form($user_id, $submission_id = null) {
        if (!class_exists('Sisme_User_Developer_Renderer')) {
            return '<div class="sisme-error">Renderer développeur non disponible</div>';
        }
        
        // Appeler directement la fonction existante
        return Sisme_User_Developer_Renderer::render_submit_game_section($user_id, 'approved', []);
    }
    
    /**
     * Liste des soumissions utilisateur
     * @param int $user_id ID utilisateur
     * @return string HTML de la liste
     */
    public static function render_submissions_list($user_id) {
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            if (!sisme_load_submission_data_manager()) {
                return '<div class="sisme-error">Système de soumission non disponible</div>';
            }
        }
        
        $submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user_id);
        $stats = Sisme_Game_Submission_Data_Manager::get_user_stats($user_id);
        
        ob_start();
        ?>
        <div class="sisme-my-games-section">
            <div class="sisme-my-games-header">
                <h4 class="sisme-my-games-title">🎮 Mes Soumissions</h4>
                <p class="sisme-my-games-subtitle">Gérez vos jeux en cours de développement et publiés</p>
                <button class="sisme-btn sisme-btn-primary" onclick="window.location.hash = 'submit-game'">
                    ➕ Nouveau Jeu
                </button>
            </div>
            
            <?php echo self::render_stats_widget($stats); ?>
            
            <div class="sisme-my-games-content">
                <?php if (empty($submissions)): ?>
                    <div class="sisme-games-empty">
                        <div class="sisme-empty-icon">🎮</div>
                        <h5>Aucun jeu soumis</h5>
                        <p>Commencez par soumettre votre premier jeu en utilisant le bouton ci-dessus !</p>
                    </div>
                <?php else: ?>
                    <div class="sisme-submissions-list">
                        <?php foreach ($submissions as $submission_id => $submission): ?>
                            <?php echo self::render_submission_item($submission, 'list'); ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Item individuel de soumission
     * @param array $submission Données de la soumission
     * @param string $context Contexte d'affichage
     * @return string HTML de l'item
     */
    public static function render_submission_item($submission, $context = 'list') {
        $status = $submission['status'] ?? 'draft';
        $game_data = $submission['game_data'] ?? [];
        $metadata = $submission['metadata'] ?? [];
        
        $game_name = $game_data[Sisme_Utils_Users::GAME_FIELD_NAME] ?? 'Jeu sans nom';
        $created_at = $metadata['created_at'] ?? '';
        
        ob_start();
        ?>
        <div class="sisme-submission-item" data-submission-id="<?php echo esc_attr($submission['id']); ?>" data-status="<?php echo esc_attr($status); ?>">
            <div class="sisme-submission-header">
                <h5 class="sisme-submission-title"><?php echo esc_html($game_name); ?></h5>
                <div class="sisme-submission-status sisme-status-<?php echo esc_attr($status); ?>">
                    <?php echo self::get_status_label($status); ?>
                </div>
            </div>
            
            <div class="sisme-submission-meta">                
                <?php if ($created_at): ?>
                    <div class="sisme-submission-date">
                        Créé le <?php echo date('d/m/Y', strtotime($created_at)); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="sisme-submission-actions">
                <?php if ($status === 'draft'): ?>
                    <button class="sisme-btn sisme-btn-secondary" onclick="window.location.hash = 'submit-game?edit=<?php echo esc_js($submission['id']); ?>'">
                        📝 Continuer
                    </button>
                    <button class="sisme-btn sisme-btn-small sisme-btn-danger" data-action="delete" data-submission-id="<?php echo esc_attr($submission['id']); ?>">
                        🗑️ Supprimer
                    </button>
                <?php elseif ($status === 'pending'): ?>
                    <button class="sisme-btn sisme-btn-secondary" onclick="window.location.hash = 'submit-game?edit=<?php echo esc_js($submission['id']); ?>'">
                        👁️ Voir
                    </button>
                <?php elseif ($status === 'rejected'): ?>
                    <button class="sisme-btn sisme-btn-small sisme-btn-primary" onclick="SismeGameSubmission.retrySubmission('<?php echo esc_js($submission['id']); ?>')">
                        🔄 Réessayer
                    </button>
                    <button class="sisme-btn sisme-btn-small sisme-btn-secondary" onclick="SismeGameSubmission.viewSubmission('<?php echo esc_js($submission['id']); ?>')">
                        👁️ Voir
                    </button>
                <?php elseif ($status === 'approved'): ?>
                    <button class="sisme-btn sisme-btn-small sisme-btn-success" onclick="SismeGameSubmission.viewSubmission('<?php echo esc_js($submission['id']); ?>')">
                        ✅ Publié
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Widget statistiques développeur
     * @param array $stats Statistiques utilisateur
     * @return string HTML du widget
     */
    public static function render_stats_widget($stats) {
        $total = $stats['total_submissions'] ?? 0;
        $draft = $stats['draft_count'] ?? 0;
        $pending = $stats['pending_count'] ?? 0;
        $approved = $stats['approved_count'] ?? 0;
        $rejected = $stats['rejected_count'] ?? 0;
        
        ob_start();
        ?>
        <div class="sisme-developer-stats-widget">
            <h5 class="sisme-stats-title">📊 Statistiques</h5>
            <div class="sisme-stats-grid">
                <div class="sisme-stat-item">
                    <span class="sisme-stat-number"><?php echo intval($total); ?></span>
                    <span class="sisme-stat-label">Total</span>
                </div>
                <div class="sisme-stat-item">
                    <span class="sisme-stat-number"><?php echo intval($draft); ?></span>
                    <span class="sisme-stat-label">Brouillons</span>
                </div>
                <div class="sisme-stat-item">
                    <span class="sisme-stat-number"><?php echo intval($pending); ?></span>
                    <span class="sisme-stat-label">En attente</span>
                </div>
                <div class="sisme-stat-item">
                    <span class="sisme-stat-number"><?php echo intval($approved); ?></span>
                    <span class="sisme-stat-label">Publiés</span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Obtenir le libellé du statut
     * @param string $status Statut de la soumission
     * @return string Libellé du statut
     */
    private static function get_status_label($status) {
        $labels = [
            'draft' => '📝 Brouillon',
            'pending' => '⏳ En attente',
            'approved' => '✅ Publié',
            'rejected' => '❌ Rejeté'
        ];
        
        return $labels[$status] ?? ucfirst($status);
    }
}