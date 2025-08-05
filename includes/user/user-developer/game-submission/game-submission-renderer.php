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

// Charger la classe User Dashboard Renderer si nécessaire
if (!class_exists('Sisme_User_Dashboard_Renderer') && file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-dashboard/user-dashboard-renderer.php')) {
    require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-dashboard/user-dashboard-renderer.php';
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
        
        $all_submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user_id);
        
        // Filtrer les soumissions archivées côté front - elles ne doivent pas être visibles
        $submissions = array_filter($all_submissions, function($submission) {
            return ($submission['status'] ?? '') !== Sisme_Utils_Users::GAME_STATUS_ARCHIVED;
        });
        
        $stats = Sisme_Game_Submission_Data_Manager::get_user_stats($user_id);
        
        ob_start();
        ?>
        <div class="sisme-my-games-section">
            <div class="sisme-my-games-header">
                <button class="sisme-btn sisme-btn-primary" onclick="window.location.hash = 'submit-game'">
                    ➕ Nouveau Jeu
                </button>
            </div>

            <!-- < ?php // echo self::render_stats_widget($stats); ?> -->

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

        $user_id = '';
        $is_admin_context = false;
        if (is_array($context)) {
            if (isset($context['user_id']) && current_user_can('manage_options')) {
                $user_id = intval($context['user_id']);
                $is_admin_context = true;
            }
        }
        
        $game_name = $game_data[Sisme_Utils_Users::GAME_FIELD_NAME] ?? 'Jeu sans nom';
        $created_at = $metadata['created_at'] ?? '';
        
        // Récupérer l'ID du jeu - priorité à published_game_id dans les métadonnées
        $game_id = $metadata['published_game_id'] ?? $game_data['id'] ?? null;
        $game_url = Sisme_Game_Creator_Data_Manager::get_game_url($game_id) ?: '';

        ob_start();
        
        // Ouvrir le lien seulement si c'est une soumission approuvée
        if ($game_url !== home_url('/') && $status === 'published') {
            echo '<a href="' . esc_url($game_url) . '" class="sisme-game-link" title="Voir la page du jeu" target="_blank">';
        }
        ?>
            <div class="sisme-submission-item" data-submission-id="<?php echo esc_attr($submission['id']); ?>" data-status="<?php echo esc_attr($status); ?>">
             <?php   
            // Ouvrir le lien seulement si c'est une soumission approuvée
            if ($game_url !== home_url('/') && $status === 'published') {
                echo '<a href="' . esc_url($game_url) . '" class="sisme-game-link" title="Voir la page du jeu" target="_blank">';
            }
            ?>
                <div class="sisme-submission-header">
                    <h5 class="sisme-submission-title">
                        <?php if ($submission['metadata']['is_revision'] ?? false): ?>
                            <span class="revision-indicator" title="Cette soumission est une révision d'un jeu existant">🔄</span>
                        <?php endif; ?>
                        <?php echo esc_html($game_name); ?>
                    </h5>
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
            <?php 
            // Fermer le lien si c'est une soumission approuvée
            if ($game_url !== home_url('/') && $status === 'published') {
                echo '</a>';
            }
            ?>
            
                <div class="sisme-submission-actions">
                    <?php if ($status === 'draft'): ?>
                        <button class="sisme-btn sisme-btn-secondary" onclick="window.location.hash = 'submit-game?edit=<?php echo esc_js($submission['id']); ?>'">
                            📝 Continuer
                        </button>
                        <button class="sisme-btn sisme-btn-small sisme-btn-danger" data-action="delete" data-submission-id="<?php echo esc_attr($submission['id']); ?>">
                            🗑️ Supprimer
                        </button>
                    <?php elseif ($status === 'pending'): ?>
                        <button class="sisme-btn sisme-btn-secondary sisme-expand-btn" 
                                data-submission-id="<?php echo esc_attr($submission['id']); ?>" 
                                <?php if ($is_admin_context && $user_id): ?>
                                data-admin-user-id="<?php echo esc_attr($user_id); ?>"
                                data-admin-token="<?php echo esc_attr(wp_create_nonce('admin_view_' . $user_id . '_' . $submission['id'])); ?>"
                                <?php endif; ?>
                                data-state="collapsed">
                            👁️ Voir plus
                        </button>
                    <?php elseif ($status === 'published'): ?>
                        <button class="sisme-btn sisme-btn-revision" 
                                data-submission-id="<?php echo esc_attr($submission['id']); ?>">
                            📝 Réviser ce jeu
                        </button>
                        <?php 
                        // Boutons d'archives
                        $user_id_for_archives = $is_admin_context ? $user_id : get_current_user_id();
                        $archives = self::get_submission_archives($user_id_for_archives, $submission['id']);
                        if (!empty($archives)): 
                        ?>
                            <div class="sisme-archive-buttons">
                                <button class="sisme-btn-archive" data-action="toggle-archives" 
                                        data-submission-id="<?php echo esc_attr($submission['id']); ?>"
                                        data-state="collapsed">
                                    📁 Archives (<?php echo count($archives); ?>)
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <?php 
                // Section archives (cachée par défaut)
                if ($status === 'published' && !empty($archives)): 
                ?>
                    <div class="sisme-archives-section" style="display: none;" 
                         data-submission-id="<?php echo esc_attr($submission['id']); ?>">
                        <div class="sisme-archives-list">
                            <?php foreach ($archives as $archive): ?>
                                <div class="sisme-archive-item">
                                    <div class="sisme-archive-header">
                                        <span class="sisme-archive-title">
                                            📁 Révision archivée
                                        </span>
                                        <span class="sisme-archive-date">
                                            <?php echo date('d/m/Y H:i', strtotime($archive['metadata']['archived_at'] ?? '')); ?>
                                        </span>
                                    </div>                                    
                                    <?php 
                                    // Afficher le motif de révision original s'il existe
                                    $revision_reason = $archive['metadata']['revision_reason'] ?? '';
                                    if (!empty($revision_reason)): 
                                    ?>
                                        <div class="sisme-revision-reason">
                                            <strong>Motif de révision :</strong> <?php echo esc_html($revision_reason); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        
        <?php
        return ob_get_clean();
    }

    /**
     * Rendu du HTML de la modale de soumission
     * @return string HTML de la modale
     */
    public static function render_submission_modal() {
        ob_start();
        ?>
        <div id="sisme-submission-modal" class="sisme-submission-modal" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
            <div class="sisme-modal-content" role="document">
                <div id="modal-title" class="sisme-modal-title">Préparation...</div>
                <div class="sisme-spinner" aria-label="Chargement en cours">
                    <div class="sisme-spinner-dot"></div>
                    <div class="sisme-spinner-dot"></div>
                    <div class="sisme-spinner-dot"></div>
                </div>
                <div class="sisme-modal-details">
                    <p>Initialisation de la soumission...</p>
                </div>
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
    /*public static function render_stats_widget($stats) {
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
    }*/
    
    /**
     * Récupérer les archives d'une soumission
     * @param int $user_id ID utilisateur
     * @param string $submission_id ID de la soumission principale
     * @return array Archives liées à cette soumission
     */
    private static function get_submission_archives($user_id, $submission_id) {
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            return [];
        }
        
        // Récupérer toutes les soumissions archivées de l'utilisateur
        $archived_submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user_id, 'archived');
        
        // Filtrer celles qui sont liées à cette soumission
        $archives = [];
        foreach ($archived_submissions as $archived) {
            // Vérifier si c'est une révision de cette soumission
            // La métadonnée peut être 'revision_of' ou 'original_published_id'
            $revision_of = $archived['metadata']['revision_of'] ?? null;
            $original_published_id = $archived['metadata']['original_published_id'] ?? null;
            
            if (($revision_of === $submission_id) || ($original_published_id === $submission_id)) {
                $archives[] = $archived;
            }
        }
        
        // Trier par date d'archivage (plus récent en premier)
        usort($archives, function($a, $b) {
            $date_a = $a['metadata']['archived_at'] ?? '';
            $date_b = $b['metadata']['archived_at'] ?? '';
            return strcmp($date_b, $date_a);
        });
        
        // TEST: Retourner des données factices pour tester l'affichage
        if (empty($archives) && current_user_can('manage_options')) {
            $archives = [[
                'id' => 'test_archive_1',
                'status' => 'archived',
                'metadata' => [
                    'archived_at' => current_time('mysql'),
                    'archived_reason' => 'Révision approuvée - Motif : Correction de bugs et amélioration des performances',
                    'revision_reason' => 'Correction de bugs et amélioration des performances',
                    'is_revision' => true
                ],
                'game_data' => [
                    Sisme_Utils_Users::GAME_FIELD_NAME => 'Jeu Test Archive',
                    Sisme_Utils_Users::GAME_FIELD_DESCRIPTION => 'Description test pour l\'archive'
                ]
            ]];
        }
        
        return $archives;
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
            'published' => '✅ Publié',
            'rejected' => '❌ Rejeté',
            // 'archived' => '📁 Archivé', // Supprimé côté front - archives uniquement visibles en admin
        ];
        
        return $labels[$status] ?? ucfirst($status);
    }
}