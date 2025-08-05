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

add_action('wp_ajax_sisme_admin_get_submission_details', ['Sisme_Admin_Submission_Tab', 'ajax_get_submission_details']);
add_action('wp_ajax_sisme_admin_reject_submission', ['Sisme_Admin_Submission_Tab', 'ajax_reject_submission']);
add_action('wp_ajax_sisme_admin_approve_submission', ['Sisme_Admin_Submission_Tab', 'ajax_approve_submission']);

/**
 * Classe pour l'onglet admin des soumissions
 */
class Sisme_Admin_Submission_Tab {    
    /**
     * Récupérer une soumission par son ID parmi tous les développeurs (admin)
     */
    private static function get_submission_for_admin($submission_id) {
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            $file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
        $developer_users = get_users([
            'meta_key' => Sisme_Utils_Users::META_DEVELOPER_STATUS,
            'meta_value' => Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED,
            'fields' => ['ID', 'display_name', 'user_email']
        ]);
        foreach ($developer_users as $user) {
            $user_submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user->ID);
            foreach ($user_submissions as $submission) {
                if ($submission['id'] === $submission_id) {
                    $submission['user_data'] = [
                        'user_id' => $user->ID,
                        'display_name' => $user->display_name,
                        'user_email' => $user->user_email
                    ];
                    return $submission;
                }
            }
        }
        return null;
    }
    /**
     * AJAX : Récupérer les détails d'une soumission
     */
    public static function ajax_get_submission_details() {
        error_log('DEBUG: ajax_get_submission_details CALLED');
        // Vérification admin obligatoire
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
            return;
        }
        
        // Vérification nonce
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_developer_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
            return;
        }
        
        $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');
        $developer_user_id = intval($_POST['user_id'] ?? 0);
        error_log('DEBUG: submission_id=' . $submission_id . ' developer_user_id=' . $developer_user_id);
        error_log('AJAX APPELÉ !');
        if (empty($submission_id) || !$developer_user_id) {
            wp_send_json_error(['message' => 'Paramètres manquants']);
            return;
        }
        
        // Charger le module data manager
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            $file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
            if (!file_exists($file)) {
                wp_send_json_error(['message' => 'Module soumission non disponible']);
                return;
            }
            require_once $file;
        }

        $submission = self::get_submission_for_admin($submission_id);
        error_log('DEBUG: submission=' . print_r($submission, true));
        if (!$submission) {
            wp_send_json_error(['message' => 'Soumission introuvable pour ce développeur']);
            return;
        }
        
        // Enrichir les données avec des infos développeur
        $developer_info = get_userdata($developer_user_id);
        
        $game_data = $submission['game_data'] ?? [];
        // Covers
        $covers = [
            'horizontal' => !empty($game_data['covers']['horizontal']) ? [
                'id' => $game_data['covers']['horizontal'],
                'url' => wp_get_attachment_url($game_data['covers']['horizontal']),
                'thumb' => wp_get_attachment_image_url($game_data['covers']['horizontal'], 'thumbnail')
            ] : null,
            'vertical' => !empty($game_data['covers']['vertical']) ? [
                'id' => $game_data['covers']['vertical'],
                'url' => wp_get_attachment_url($game_data['covers']['vertical']),
                'thumb' => wp_get_attachment_image_url($game_data['covers']['vertical'], 'thumbnail')
            ] : null
        ];
        // Screenshots
        $screenshots = [];
        if (!empty($game_data['screenshots']) && is_array($game_data['screenshots'])) {
            foreach ($game_data['screenshots'] as $i => $sid) {
                $screenshots[] = [
                    'id' => $sid,
                    'url' => wp_get_attachment_url($sid),
                    'thumb' => wp_get_attachment_image_url($sid, 'thumbnail')
                ];
            }
        }
        // Sections détaillées
        $sections = [];
        if (!empty($game_data['sections']) && is_array($game_data['sections'])) {
            foreach ($game_data['sections'] as $section) {
                $sections[] = [
                    'title' => $section['title'] ?? '',
                    'content' => $section['content'] ?? '',
                    'image' => !empty($section['image_attachment_id']) ? [
                        'id' => $section['image_attachment_id'],
                        'url' => wp_get_attachment_url($section['image_attachment_id']),
                        'thumb' => wp_get_attachment_image_url($section['image_attachment_id'], 'thumbnail')
                    ] : null
                ];
            }
        }
        // Liens externes
        $external_links = $game_data['external_links'] ?? [];

        // Réponse complète
        wp_send_json_success([
            'submission_id' => $submission_id,
            'developer' => [
                'user_id' => $developer_user_id,
                'display_name' => $developer_info ? $developer_info->display_name : 'Inconnu',
                'user_email' => $developer_info ? $developer_info->user_email : ''
            ],
            'game_data' => $game_data,
            'covers' => $covers,
            'screenshots' => $screenshots,
            'sections' => $sections,
            'external_links' => $external_links,
            'metadata' => $submission['metadata'] ?? [],
            'admin_data' => $submission['admin_data'] ?? [],
            'status' => $submission['status'] ?? 'unknown',
            'is_admin_access' => true
        ]);
    }
    
    /**
     * Rendu de l'onglet complet
     */
    public static function render() {
        
        // Charger les assets admin
        self::enqueue_admin_assets();
        
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
        
        // Localiser pour l'admin
        wp_localize_script('sisme-admin-submissions', 'sismeAdminAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_developer_nonce'),
            'isAdmin' => true
        ]);
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
            'rejected' => 0, 'total' => count($submissions)
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
         <tr class="sisme-details-row" id="details-<?php echo esc_attr($submission['id']); ?>" style="display: none;">
            <td colspan="6" class="details-container">
                <div class="admin-details-content">
                    <div class="admin-loading">
                        ⏳ Chargement des détails...
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
            'rejected' => ['class' => 'rejected', 'text' => '❌ Rejeté', 'color' => '#dc3545']
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

            <!-- Voir plus -->
            <button class="action-btn view-btn" 
                    data-submission-id="<?php echo esc_attr($submission_id); ?>"
                    data-user-id="<?php echo esc_attr($user_id); ?>"
                    title="Voir les détails">
                👁️
            </button>
            
            <!-- Approuver -->
            <button class="action-btn approve-btn <?php echo $status === 'pending' ? 'active' : 'disabled'; ?>" 
                    data-submission-id="<?php echo esc_attr($submission_id); ?>"
                    data-user-id="<?php echo esc_attr($user_id); ?>"
                    <?php echo $status === 'pending' ? '' : 'disabled'; ?>
                    title="Approuver la soumission">
                ✅
            </button>
            
            <!-- Rejeter -->
            <button class="action-btn reject-btn <?php echo in_array($status, ['pending', 'published']) ? 'active' : 'disabled'; ?>" 
                    data-submission-id="<?php echo esc_attr($submission_id); ?>"
                    data-user-id="<?php echo esc_attr($user_id); ?>"
                    <?php echo in_array($status, ['pending', 'published']) ? '' : 'disabled'; ?>
                    title="Rejeter la soumission">
                ❌
            </button>
            
            <!-- Supprimer -->
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
     * AJAX : Rejeter une soumission
     */
    public static function ajax_reject_submission() {
        error_log('DEBUG: Début ajax_reject_submission');
        error_log('DEBUG: POST data: ' . print_r($_POST, true));
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_developer_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);
        $reason = wp_unslash(sanitize_textarea_field($_POST['rejection_reason'] ?? ''));
        
        error_log("DEBUG: submission_id='$submission_id', user_id=$user_id, reason='$reason'");
        
        if (!$submission_id || !$user_id || !$reason) {
            error_log('DEBUG: Paramètre manquant détecté');
            wp_send_json_error(['message' => "Paramètres manquants - ID:$submission_id, User:$user_id, Reason:$reason"]);
        }
        
        // Charger le data manager
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
        }
        
        // Changer le statut vers draft
        $result = Sisme_Game_Submission_Data_Manager::change_submission_status(
            $user_id, 
            $submission_id, 
            Sisme_Utils_Users::GAME_STATUS_DRAFT,
            [
                'rejected_at' => current_time('mysql'),
                'rejection_reason' => $reason
            ]
        );
        
        if ($result) {
            $user = get_userdata($user_id);
            $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
            $game_name = $submission['game_data']['game_name'] ?? 'Votre jeu';
            
            $email_content = Sisme_Email_Templates::submission_rejected(
                $user->display_name,
                $game_name,
                $reason
            );
            
            Sisme_Email_Manager::send_email(
                [$user_id],
                "Soumission rejetée : {$game_name}",
                $email_content
            );
            
            wp_send_json_success(['message' => 'Soumission rejetée avec succès']);
        } else {
            wp_send_json_error(['message' => 'Erreur lors du rejet']);
        }
    }

    /**
     * AJAX : Approuver une soumission
     */
    public static function ajax_approve_submission() {
        error_log('DEBUG: Début ajax_approve_submission');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_developer_nonce')) {
            wp_send_json_error(['message' => 'Erreur de sécurité']);
        }
        
        $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if (!$submission_id || !$user_id) {
            wp_send_json_error(['message' => "Paramètres manquants - ID:$submission_id, User:$user_id"]);
        }
        
        // Charger le data manager
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
        }
        
        // Vérifier que la soumission existe et est en attente
        $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
        if (!$submission) {
            wp_send_json_error(['message' => 'Soumission introuvable']);
        }
        
        if ($submission['status'] !== Sisme_Utils_Users::GAME_STATUS_PENDING) {
            wp_send_json_error(['message' => 'Seules les soumissions en attente peuvent être approuvées']);
        }
        
        // Changer le statut vers published
        $result = Sisme_Game_Submission_Data_Manager::change_submission_status(
            $user_id, 
            $submission_id, 
            Sisme_Utils_Users::GAME_STATUS_PENDING,
            [
                'approval_timestamp' => current_time('mysql')
            ]
        );
        
        if ($result) {
            if (class_exists('Sisme_Game_Creator')) {
                // Débogage des modes avant création
                echo '<pre>';
                echo "DÉBOGAGE DES MODES DE JEU:\n\n";
                
                echo "1. MODES DANS LA SOUMISSION:\n";
                if (isset($submission['game_data']['game_modes'])) {
                    echo "Type: " . gettype($submission['game_data']['game_modes']) . "\n";
                    echo "Valeur brute: \n";
                    var_export($submission['game_data']['game_modes']);
                    echo "\n\nSérialisé: " . serialize($submission['game_data']['game_modes']);
                } else {
                    echo "Aucun mode trouvé dans game_data\n";
                }
                
                echo "\n\n2. EXTRACTION ET CONVERSION DES DONNÉES:\n";
                $extracted_data = Sisme_Game_Creator::extract_game_data_from_submission($submission);
                if (!is_wp_error($extracted_data) && isset($extracted_data['modes'])) {
                    echo "Type après extraction: " . gettype($extracted_data['modes']) . "\n";
                    echo "Valeur après extraction: \n";
                    var_export($extracted_data['modes']);
                    echo "\n\nSérialisé: " . serialize($extracted_data['modes']);
                } else {
                    echo "Aucun mode trouvé après extraction ou erreur\n";
                }
                
                echo "\n\nARRÊT DU DÉBOGAGE - Exécution interrompue avant création du jeu";
                echo '</pre>';
                
                $game_id = Sisme_Game_Creator::create_from_submission_data($submission);
                
                if (!is_wp_error($game_id)) {
                    // Mettre à jour la soumission avec l'ID du jeu créé
                    Sisme_Game_Submission_Data_Manager::change_submission_status(
                        $user_id,
                        $submission_id, 
                        Sisme_Utils_Users::GAME_STATUS_PUBLISHED,
                        [
                            'published_at' => current_time('mysql'),
                            'admin_user_id' => get_current_user_id(),
                            'published_game_id' => $game_id
                        ]
                    );
                    
                    // Email de confirmation
                    $user = get_userdata($user_id);
                    $submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);
                    $game_name = $submission['game_data']['game_name'] ?? 'Votre jeu';
                    $game_link = Sisme_Game_Creator_Data_Manager::get_game_url($submission['metadata']['published_game_id'] ?? 'Erreur de génération du lien');

                    $email_content = Sisme_Email_Templates::submission_approved(
                        $user->display_name,
                        $game_name
                    );
                    
                    Sisme_Email_Manager::send_email(
                        [$user_id],
                        "Soumission approuvée : {$game_name}",
                        $email_content
                    );
                    
                    wp_send_json_success([
                        'message' => 'Jeu approuvé et créé avec succès',
                        'game_id' => $game_id
                    ]);
                } else {
                    // Rollback - remettre en pending si création échoue
                    Sisme_Game_Submission_Data_Manager::change_submission_status(
                        $user_id,
                        $submission_id, 
                        Sisme_Utils_Users::GAME_STATUS_PENDING
                    );
                    
                    wp_send_json_error([
                        'message' => 'Erreur lors de la création du jeu: ' . $game_id->get_error_message()
                    ]);
                }
            } else {
                wp_send_json_error(['message' => 'Module de création de jeu non disponible']);
            }
        } else {
            wp_send_json_error(['message' => 'Erreur lors de l\'approbation']);
        }
    }
}