<?php
/**
 * File: /sisme-games-editor/admin/components/admin-submission-tab.php
 * Interface admin moderne pour les soumissions de jeux
 * 
 * RESPONSABILITÉ:
 * - Tableau condensé avec miniatures média
 * - Actions admin (approuver/rejeter/supprimer/voir)
 * - Expansion des détails avec le système front
 * - Styles admin isolés
 * 
 * DÉPENDANCES:
 * - game-submission système existant (aucune modification)
 * - admin-submissions.css (styles spécifiques)
 * - admin-submissions.js (comportements admin)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe pour l'onglet admin des soumissions
 */
class Sisme_Admin_Submission_Tab {
    
    /**
     * Rendu de l'onglet complet
     */
    public static function render() {
        // Charger les assets admin
        self::enqueue_admin_assets();
        
        // Charger les assets front pour la compatibilité
        self::enqueue_front_compatibility();
        
        // Récupérer les données
        $submissions_data = self::get_submissions_data();
        $stats = self::calculate_stats($submissions_data);
        
        ob_start();
        ?>
        <div class="sisme-admin-submissions">
            
            <!-- Statistiques -->
            <?php echo self::render_stats($stats); ?>
            
            <!-- Tableau principal -->
            <?php echo self::render_submissions_table($submissions_data); ?>
            
        </div>
        
        <!-- JavaScript admin -->
        <script>
        jQuery(document).ready(function($) {
            if (typeof SismeSubmissionDetails !== 'undefined') {
                // Override pour l'admin avec sécurité
                <?php echo self::get_admin_javascript_override(); ?>
                
                SismeSubmissionDetails.init();
                console.log('🎯 Interface admin submissions initialisée');
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Charger les assets admin
     */
    private static function enqueue_admin_assets() {
        wp_enqueue_style(
            'sisme-admin-submissions',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'admin/assets/admin-submissions.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_script(
            'sisme-admin-submissions',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'admin/assets/admin-submissions.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
    }
    
    /**
     * Charger les assets front pour compatibilité
     */
    private static function enqueue_front_compatibility() {
        wp_enqueue_style(
            'sisme-game-submission-details',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-developer/game-submission/assets/game-submission-details.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_script(
            'sisme-game-submission-details',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-developer/game-submission/assets/game-submission-details.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        wp_localize_script('sisme-game-submission-details', 'sismeAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_developer_nonce'),
            'isAdmin' => true
        ]);
    }
    
    /**
     * Récupérer les données des soumissions
     */
    private static function get_submissions_data() {
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            $file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
        
        $all_submissions = [];
        
        if (class_exists('Sisme_Game_Submission_Data_Manager')) {
            $developer_users = get_users([
                'meta_key' => Sisme_Utils_Users::META_DEVELOPER_STATUS,
                'meta_value' => Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED,
                'fields' => ['ID', 'display_name', 'user_email']
            ]);
            
            foreach ($developer_users as $user) {
                $user_submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user->ID);
                
                foreach ($user_submissions as $submission) {
                    $submission['user_data'] = [
                        'user_id' => $user->ID,
                        'display_name' => $user->display_name,
                        'user_email' => $user->user_email
                    ];
                    $all_submissions[] = $submission;
                }
            }
            
            // Trier par date
            usort($all_submissions, function($a, $b) {
                $date_a = $a['metadata']['submitted_at'] ?? $a['metadata']['updated_at'];
                $date_b = $b['metadata']['submitted_at'] ?? $b['metadata']['updated_at'];
                return strtotime($date_b) - strtotime($date_a);
            });
        }
        
        return $all_submissions;
    }
    
    /**
     * Calculer les statistiques
     */
    private static function calculate_stats($submissions) {
        $stats = [
            'draft' => 0, 'pending' => 0, 'published' => 0,
            'rejected' => 0, 'revision' => 0, 'total' => count($submissions)
        ];
        
        foreach ($submissions as $submission) {
            $status = $submission['status'] ?? 'draft';
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Rendu des statistiques
     */
    private static function render_stats($stats) {
        ob_start();
        ?>
        <div class="sisme-admin-stats">
            <h3>📊 Statistiques des Soumissions</h3>
            <div class="sisme-stats-grid">
                <div class="sisme-stat-card draft">
                    <span class="stat-number"><?php echo $stats['draft']; ?></span>
                    <span class="stat-label">📝 Brouillons</span>
                </div>
                <div class="sisme-stat-card pending">
                    <span class="stat-number"><?php echo $stats['pending']; ?></span>
                    <span class="stat-label">⏳ En attente</span>
                </div>
                <div class="sisme-stat-card published">
                    <span class="stat-number"><?php echo $stats['published']; ?></span>
                    <span class="stat-label">✅ Publiés</span>
                </div>
                <div class="sisme-stat-card rejected">
                    <span class="stat-number"><?php echo $stats['rejected']; ?></span>
                    <span class="stat-label">❌ Rejetés</span>
                </div>
                <div class="sisme-stat-card revision">
                    <span class="stat-number"><?php echo $stats['revision']; ?></span>
                    <span class="stat-label">🔄 Révisions</span>
                </div>
                <div class="sisme-stat-card total">
                    <span class="stat-number"><?php echo $stats['total']; ?></span>
                    <span class="stat-label">📊 Total</span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu du tableau des soumissions
     */
    private static function render_submissions_table($submissions) {
        if (empty($submissions)) {
            return '<div class="sisme-admin-empty">🎮 Aucune soumission pour le moment.</div>';
        }
        
        ob_start();
        ?>
        <div class="sisme-admin-table-container">
            <h3>🎮 Soumissions de Jeux</h3>
            
            <table class="sisme-admin-table">
                <thead>
                    <tr>
                        <th class="col-developer">Développeur</th>
                        <th class="col-game">Jeu</th>
                        <th class="col-media">Médias</th>
                        <th class="col-status">Statut</th>
                        <th class="col-date">Date</th>
                        <th class="col-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <?php echo self::render_submission_row($submission); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu d'une ligne de soumission
     */
    private static function render_submission_row($submission) {
        $game_data = $submission['game_data'] ?? [];
        $metadata = $submission['metadata'] ?? [];
        $user_data = $submission['user_data'] ?? [];
        
        $game_name = $game_data[Sisme_Utils_Users::GAME_FIELD_NAME] ?? 'Jeu sans nom';
        $studio_name = $game_data[Sisme_Utils_Users::GAME_FIELD_STUDIO_NAME] ?? '';
        $status = $submission['status'] ?? 'draft';
        $display_date = $metadata['submitted_at'] ?? $metadata['updated_at'] ?? '';
        
        if ($display_date) {
            $display_date = date('d/m/Y H:i', strtotime($display_date));
        }
        
        ob_start();
        ?>
        <tr class="sisme-submission-row" data-submission-id="<?php echo esc_attr($submission['id']); ?>" data-status="<?php echo esc_attr($status); ?>">
            
            <!-- Développeur -->
            <td class="col-developer">
                <div class="developer-info">
                    <strong><?php echo esc_html($user_data['display_name'] ?? 'Inconnu'); ?></strong>
                    <small>ID: <?php echo esc_html($user_data['user_id'] ?? 'N/A'); ?></small>
                </div>
            </td>
            
            <!-- Jeu -->
            <td class="col-game">
                <div class="game-info">
                    <strong><?php echo esc_html($game_name); ?></strong>
                    <?php if ($studio_name): ?>
                        <small><?php echo esc_html($studio_name); ?></small>
                    <?php endif; ?>
                    <small class="submission-id">ID: <?php echo esc_html($submission['id']); ?></small>
                </div>
            </td>
            
            <!-- Médias avec miniatures -->
            <td class="col-media">
                <?php echo self::render_media_thumbnails($game_data); ?>
            </td>
            
            <!-- Statut -->
            <td class="col-status">
                <?php echo self::render_status_badge($status); ?>
            </td>
            
            <!-- Date -->
            <td class="col-date">
                <time><?php echo esc_html($display_date ?: 'N/A'); ?></time>
            </td>
            
            <!-- Actions -->
            <td class="col-actions">
                <?php echo self::render_action_buttons($submission, $user_data); ?>
            </td>
        </tr>
        
        <!-- Ligne de détails (cachée par défaut) -->
        <tr class="sisme-details-row" style="display: none;">
            <td colspan="6" class="details-container">
                <div class="admin-details-content">
                    <?php
                    // ✅ STRUCTURE MINIMALE pour que le JS front fonctionne
                    $adapted_submission = [
                        'id' => $submission['id'],
                        'status' => $submission['status'],
                        'game_data' => $submission['game_data'],
                        'metadata' => $submission['metadata']
                    ];
                    ?>
                    
                    <!-- Structure invisible pour le JS front -->
                    <div class="sisme-submission-item hidden-structure" 
                         data-submission-id="<?php echo esc_attr($submission['id']); ?>" 
                         data-status="<?php echo esc_attr($status); ?>" 
                         style="display: none;">
                        <div class="sisme-submission-meta">
                            <!-- Les détails AJAX seront injectés ici -->
                        </div>
                    </div>
                    
                    <!-- Affichage admin visible -->
                    <div class="admin-details-display">
                        <div class="admin-loading" style="text-align: center; padding: 20px; color: #666;">
                            ⏳ Chargement des détails...
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu des miniatures médias
     */
    private static function render_media_thumbnails($game_data) {
        $covers = $game_data['covers'] ?? [];
        $screenshots = $game_data['screenshots'] ?? [];
        
        ob_start();
        ?>
        <div class="media-thumbnails">
            <?php if (!empty($covers['horizontal'])): ?>
                <a href="<?php echo esc_url(wp_get_attachment_url($covers['horizontal'])); ?>" 
                   target="_blank" class="media-thumb cover-h" title="Cover horizontale">
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($covers['horizontal'], 'thumbnail')); ?>" 
                         alt="Cover H" />
                    <span class="media-label">H</span>
                </a>
            <?php endif; ?>
            
            <?php if (!empty($covers['vertical'])): ?>
                <a href="<?php echo esc_url(wp_get_attachment_url($covers['vertical'])); ?>" 
                   target="_blank" class="media-thumb cover-v" title="Cover verticale">
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($covers['vertical'], 'thumbnail')); ?>" 
                         alt="Cover V" />
                    <span class="media-label">V</span>
                </a>
            <?php endif; ?>
            
            <?php if (!empty($screenshots)): ?>
                <div class="screenshots-group">
                    <?php foreach (array_slice($screenshots, 0, 3) as $screenshot_id): ?>
                        <a href="<?php echo esc_url(wp_get_attachment_url($screenshot_id)); ?>" 
                           target="_blank" class="media-thumb screenshot" title="Screenshot">
                            <img src="<?php echo esc_url(wp_get_attachment_image_url($screenshot_id, 'thumbnail')); ?>" 
                                 alt="Screenshot" />
                        </a>
                    <?php endforeach; ?>
                    
                    <?php if (count($screenshots) > 3): ?>
                        <span class="media-count">+<?php echo count($screenshots) - 3; ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($covers) && empty($screenshots)): ?>
                <span class="no-media">Aucun média</span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu du badge de statut
     */
    private static function render_status_badge($status) {
        $status_config = [
            'draft' => ['class' => 'draft', 'text' => '📝 Brouillon', 'color' => '#6c757d'],
            'pending' => ['class' => 'pending', 'text' => '⏳ En attente', 'color' => '#ffc107'],
            'published' => ['class' => 'published', 'text' => '✅ Publié', 'color' => '#28a745'],
            'rejected' => ['class' => 'rejected', 'text' => '❌ Rejeté', 'color' => '#dc3545'],
            'revision' => ['class' => 'revision', 'text' => '🔄 Révision', 'color' => '#17a2b8']
        ];
        
        $config = $status_config[$status] ?? $status_config['draft'];
        
        return sprintf(
            '<span class="status-badge status-%s" style="border-left-color: %s">%s</span>',
            esc_attr($config['class']),
            esc_attr($config['color']),
            esc_html($config['text'])
        );
    }
    
    /**
     * Rendu des boutons d'actions
     */
    private static function render_action_buttons($submission, $user_data) {
        $status = $submission['status'] ?? 'draft';
        $submission_id = $submission['id'];
        $user_id = $user_data['user_id'];
        
        ob_start();
        ?>
        <div class="action-buttons">
            
            <!-- Voir plus (toujours disponible) -->
            <button class="action-btn view-btn sisme-expand-btn" 
                    data-submission-id="<?php echo esc_attr($submission_id); ?>"
                    data-admin-user-id="<?php echo esc_attr($user_id); ?>"
                    data-admin-token="<?php echo esc_attr(wp_create_nonce('admin_view_' . $user_id . '_' . $submission_id)); ?>"
                    data-state="collapsed"
                    title="Voir les détails">
                👁️
            </button>
            
            <!-- Approuver (actif si pending/revision) -->
            <button class="action-btn approve-btn <?php echo in_array($status, ['pending', 'revision']) ? 'active' : 'disabled'; ?>" 
                    <?php echo in_array($status, ['pending', 'revision']) ? 'onclick="alert(\'Approbation à implémenter\')"' : 'disabled'; ?>
                    title="Approuver la soumission">
                ✅
            </button>
            
            <!-- Rejeter (actif si pending/revision) -->
            <button class="action-btn reject-btn <?php echo in_array($status, ['pending', 'revision']) ? 'active' : 'disabled'; ?>" 
                    <?php echo in_array($status, ['pending', 'revision']) ? 'onclick="alert(\'Rejet à implémenter\')"' : 'disabled'; ?>
                    title="Rejeter la soumission">
                ❌
            </button>
            
            <!-- Supprimer (toujours actif pour admin) -->
            <form method="post" style="display: inline;" class="delete-form"
                  onsubmit="return confirm('⚠️ Supprimer définitivement cette soumission ET tous ses médias ?');">
                <?php wp_nonce_field('admin_submission_delete'); ?>
                <input type="hidden" name="action" value="delete_submission">
                <input type="hidden" name="submission_id" value="<?php echo esc_attr($submission_id); ?>">
                <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                <input type="hidden" name="tab" value="submissions">
                
                <button type="submit" class="action-btn delete-btn active" title="Supprimer définitivement">
                    🗑️
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * JavaScript pour l'override admin
     */
    private static function get_admin_javascript_override() {
        return "
        const originalExpandDetails = SismeSubmissionDetails.expandDetails;
        
        SismeSubmissionDetails.expandDetails = function(submissionId, \$button) {
            const adminUserId = \$button.data('admin-user-id');
            const adminToken = \$button.data('admin-token');
            
            if (adminUserId && adminToken) {
                const \$row = \$button.closest('.sisme-submission-row');
                const \$detailsRow = \$row.next('.sisme-details-row');
                const \$meta = \$detailsRow.find('.sisme-submission-meta'); // ✅ Structure invisible
                
                // Toggle l'affichage de la ligne de détails
                if (\$detailsRow.is(':visible')) {
                    \$detailsRow.slideUp(300);
                    \$button.data('state', 'collapsed').text('👁️');
                    return;
                }
                
                this.setButtonLoading(\$button, true);
                \$detailsRow.show();
                
                \$.ajax({
                    url: this.config.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'sisme_get_submission_details',
                        submission_id: submissionId,
                        admin_user_id: adminUserId,
                        admin_token: adminToken,
                        security: this.config.nonce
                    },
                    success: (response) => {
                        this.setButtonLoading(\$button, false);
                        
                        if (response.success && response.data) {
                            this.cache[submissionId] = response.data;
                            
                            // ✅ Injecter dans la structure invisible
                            this.renderDetails(\$meta, response.data);
                            
                            // ✅ Copier le contenu vers l'affichage admin
                            const detailsHtml = \$meta.find('.sisme-meta-details').html() || 'Aucun détail disponible';
                            \$detailsRow.find('.admin-details-display').html('<div class=\"admin-details-wrapper\">' + detailsHtml + '</div>');
                            
                            \$button.data('state', 'expanded').text('🔼');
                        } else {
                            \$detailsRow.find('.admin-details-display').html('<div class=\"admin-error\">❌ ' + (response.data?.message || 'Erreur') + '</div>');
                        }
                    },
                    error: () => {
                        this.setButtonLoading(\$button, false);
                        this.showError(\$meta, 'Erreur de connexion');
                    }
                });
            } else {
                originalExpandDetails.call(this, submissionId, \$button);
            }
        };
        ";
    }
}