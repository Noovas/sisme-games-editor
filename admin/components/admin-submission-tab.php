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
 * - admin-submissions.js (comportements admin)
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_sisme_admin_get_submission_details', ['Sisme_Admin_Submission_Tab', 'ajax_get_submission_details']);
add_action('wp_ajax_sisme_admin_reject_submission', ['Sisme_Admin_Submission_Tab', 'ajax_reject_submission']);
add_action('wp_ajax_sisme_admin_approve_submission', ['Sisme_Admin_Submission_Tab', 'ajax_approve_submission']);
add_action('wp_ajax_sisme_admin_delete_submission', ['Sisme_Admin_Submission_Tab', 'ajax_delete_submission']);

/**
 * Classe pour l'onglet admin des soumissions
 */
class Sisme_Admin_Submission_Tab {    
    /**
     * Convertir les IDs de genre en noms lisibles
     */
    private static function format_genres($genre_ids) {
        if (empty($genre_ids) || !is_array($genre_ids)) {
            return [];
        }
        
        $formatted_genres = [];
        foreach ($genre_ids as $genre_id) {
            $term = get_term($genre_id);
            if ($term && !is_wp_error($term)) {
                $formatted_genres[] = $term->name;
            }
        }
        
        return $formatted_genres;
    }
    
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
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_admin_nonce')) {
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
        
        // Convertir les genres d'IDs en noms
        $genres_formatted = self::format_genres($game_data['game_genres'] ?? []);

        // Réponse complète
        wp_send_json_success([
            'submission_id' => $submission_id,
            'developer' => [
                'user_id' => $developer_user_id,
                'display_name' => $developer_info ? $developer_info->display_name : 'Inconnu',
                'user_email' => $developer_info ? $developer_info->user_email : ''
            ],
            'game_data' => $game_data,
            'genres_formatted' => $genres_formatted,
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
        $archived_data = self::get_archived_submissions_data();
        $stats = self::calculate_stats(array_merge($submissions_data, $archived_data)); // Calculer stats sur tout
        
        ob_start();
        ?>
        <div class="sisme-admin-submissions">
            <!-- Statistiques -->
            <?php echo self::render_stats($stats); ?>
            
            <!-- Tableau principal -->
            <?php echo self::render_submissions_table($submissions_data); ?>
            
            <!-- Section Archives -->
            <?php if (!empty($archived_data)): ?>
                <?php echo self::render_archives_section($archived_data); ?>
            <?php endif; ?>
            
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu de la section des archives
     */
    private static function render_archives_section($archived_data) {
        if (empty($archived_data)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="sisme-admin-archives-section">
            <h3>📁 Archives (<?php echo count($archived_data); ?>)</h3>
            <p class="sisme-archives-description">Révisions approuvées et autres soumissions archivées</p>
            
            <div class="sisme-archives-table-container">
                <table class="sisme-admin-table sisme-archives-table">
                    <thead>
                        <tr>
                            <th class="col-developer">Développeur</th>
                            <th class="col-game">Jeu/Révision</th>
                            <th class="col-date">Date d'archivage</th>
                            <th class="col-reason">Motif</th>
                            <th class="col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archived_data as $submission): ?>
                            <?php echo self::render_archive_row($submission); ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Rendu d'une ligne d'archive
     */
    private static function render_archive_row($submission) {
        $game_data = $submission['game_data'] ?? [];
        $metadata = $submission['metadata'] ?? [];
        $user_data = $submission['user_data'] ?? [];
        
        $game_name = $game_data[Sisme_Utils_Users::GAME_FIELD_NAME] ?? 'Jeu sans nom';
        $is_revision = $metadata['is_revision'] ?? false;
        $archived_at = $metadata['archived_at'] ?? '';
        $archived_reason = $metadata['archived_reason'] ?? 'Aucun motif spécifié';
        $revision_reason = $metadata['revision_reason'] ?? '';
        
        // Déterminer l'URL du jeu pour les archives
        $game_url = null;
        if ($is_revision && isset($metadata['original_published_id'])) {
            // Archive de révision : utiliser l'ID du jeu original
            $original_submission = self::get_submission_for_admin($metadata['original_published_id']);
            if ($original_submission && isset($original_submission['metadata']['published_game_id'])) {
                $game_id = $original_submission['metadata']['published_game_id'];
                if (class_exists('Sisme_Game_Creator_Data_Manager')) {
                    $game_url = Sisme_Game_Creator_Data_Manager::get_game_url($game_id);
                }
            }
        }
        
        // Si c'est une révision et qu'elle a un motif original, l'afficher
        if ($is_revision && !empty($revision_reason)) {
            $display_reason = "Motif : " . $revision_reason;
        } else {
            $display_reason = $archived_reason;
        }
        
        if ($archived_at) {
            $archived_at = date('d/m/Y H:i', strtotime($archived_at));
        }
        
        ob_start();
        ?>
        <tr class="sisme-archive-row">
            <!-- Développeur -->
            <td class="col-developer">
                <div class="developer-info">
                    <strong><?php echo esc_html($user_data['display_name'] ?? 'Inconnu'); ?></strong>
                    <small>ID: <?php echo esc_html($user_data['user_id'] ?? 'N/A'); ?></small>
                </div>
            </td>
            
            <!-- Jeu/Révision -->
            <td class="col-game">
                <div class="game-info">
                    <?php if ($is_revision): ?>
                        <span class="revision-badge">🔄 RÉVISION</span>
                    <?php endif; ?>
                    <strong><?php echo esc_html($game_name); ?></strong>
                    <small class="submission-id">ID: <?php echo esc_html($submission['id']); ?></small>
                </div>
            </td>
            
            <!-- Date d'archivage -->
            <td class="col-date">
                <time><?php echo esc_html($archived_at ?: 'N/A'); ?></time>
            </td>
            
            <!-- Motif -->
            <td class="col-reason">
                <span class="archive-reason"><?php echo esc_html($display_reason); ?></span>
            </td>
            
            <!-- Actions -->
            <td class="col-actions">
                <div class="action-buttons">
                    <!-- Lien vers le jeu -->
                    <?php if ($game_url): ?>
                        <a href="<?php echo esc_url($game_url); ?>" 
                           target="_blank" 
                           class="action-btn link-btn active" 
                           title="Voir la page du jeu">
                            🔗
                        </a>
                    <?php else: ?>
                        <span class="action-btn link-btn inactive" 
                              title="Lien non disponible">
                            🔗
                        </span>
                    <?php endif; ?>
                    
                    <!-- Voir plus -->
                    <button class="action-btn view-btn" 
                            data-submission-id="<?php echo esc_attr($submission['id']); ?>"
                            data-user-id="<?php echo esc_attr($user_data['user_id']); ?>"
                            title="Voir les détails">
                        👁️
                    </button>
                    
                    <!-- Supprimer définitivement -->
                    <button class="action-btn delete-btn active" 
                            data-submission-id="<?php echo esc_attr($submission['id']); ?>"
                            data-user-id="<?php echo esc_attr($user_data['user_id']); ?>"
                            title="Supprimer définitivement l'archive">
                        🗑️
                    </button>
                </div>
            </td>
        </tr>
        
        <!-- Ligne de détails pour archive (cachée par défaut) -->
        <tr class="sisme-details-row" id="details-<?php echo esc_attr($submission['id']); ?>" style="display: none;">
            <td colspan="5" class="details-container">
                <div class="admin-details-content">
                    <div class="admin-loading">
                        ⏳ Chargement des détails de l'archive...
                    </div>
                </div>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Charger les assets admin
     */
    private static function enqueue_admin_assets() {
        
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
            'nonce' => wp_create_nonce('sisme_admin_nonce'),
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
                    // Exclure les soumissions archivées du tableau principal
                    if (($submission['status'] ?? '') !== 'archived') {
                        $submission['user_data'] = [
                            'user_id' => $user->ID,
                            'display_name' => $user->display_name,
                            'user_email' => $user->user_email
                        ];
                        $all_submissions[] = $submission;
                    }
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
     * Récupérer les données des archives
     */
    private static function get_archived_submissions_data() {
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            $file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
        
        $archived_submissions = [];
        
        if (class_exists('Sisme_Game_Submission_Data_Manager')) {
            $developer_users = get_users([
                'meta_key' => Sisme_Utils_Users::META_DEVELOPER_STATUS,
                'meta_value' => Sisme_Utils_Users::DEVELOPER_STATUS_APPROVED,
                'fields' => ['ID', 'display_name', 'user_email']
            ]);
            
            foreach ($developer_users as $user) {
                $user_submissions = Sisme_Game_Submission_Data_Manager::get_user_submissions($user->ID, 'archived');
                
                foreach ($user_submissions as $submission) {
                    $submission['user_data'] = [
                        'user_id' => $user->ID,
                        'display_name' => $user->display_name,
                        'user_email' => $user->user_email
                    ];
                    $archived_submissions[] = $submission;
                }
            }
            
            // Trier par date d'archivage
            usort($archived_submissions, function($a, $b) {
                $date_a = $a['metadata']['archived_at'] ?? $a['metadata']['updated_at'];
                $date_b = $b['metadata']['archived_at'] ?? $b['metadata']['updated_at'];
                return strtotime($date_b) - strtotime($date_a);
            });
        }
        
        return $archived_submissions;
    }
    
    /**
     * Calculer les statistiques
     */
    private static function calculate_stats($submissions) {
        $stats = [
            'draft' => 0, 'pending' => 0, 'published' => 0,
            'rejected' => 0, 'archived_count' => 0, 'total' => 0
        ];
        
        foreach ($submissions as $submission) {
            $status = $submission['status'] ?? 'draft';
            if ($status === 'archived') {
                $stats['archived_count']++;
            } elseif (isset($stats[$status])) {
                $stats[$status]++;
                $stats['total']++; // Only count non-archived submissions in total
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
                <div class="sisme-stat-card archived">
                    <span class="stat-number"><?php echo $stats['archived_count']; ?></span>
                    <span class="stat-label">📁 Archivés</span>
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

        // Détection révision
        $is_revision = $submission['metadata']['is_revision'] ?? false;
        $original_id = $submission['metadata']['original_published_id'] ?? null;
        $original_name = '';
        if ($is_revision && $original_id) {
            // Récupérer le nom du jeu original
            $original_submission = self::get_submission_for_admin($original_id);
            if ($original_submission) {
                $original_name = $original_submission['game_data'][Sisme_Utils_Users::GAME_FIELD_NAME] ?? 'Jeu inconnu';
            }
        }
        
        $game_name = $game_data[Sisme_Utils_Users::GAME_FIELD_NAME] ?? 'Jeu sans nom';
        $studio_name = $game_data[Sisme_Utils_Users::GAME_FIELD_STUDIO_NAME] ?? '';
        $status = $submission['status'] ?? 'draft';
        $display_date = $metadata['submitted_at'] ?? $metadata['updated_at'] ?? '';
        
        if ($display_date) {
            $display_date = date('d/m/Y H:i', strtotime($display_date));
        }
        
        ob_start();
        ?>
        <tr class="sisme-submission-row" 
            data-submission-id="<?php echo esc_attr($submission['id']); ?>" 
            data-status="<?php echo esc_attr($status); ?>"
            data-is-revision="<?php echo $is_revision ? 'true' : 'false'; ?>">

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
                    <?php if ($is_revision): ?>
                        <span class="revision-badge">🔄 RÉVISION</span>
                    <?php endif; ?>
                    <strong><?php echo esc_html($game_name); ?></strong>
                    <?php if ($is_revision && $original_name): ?>
                        <small class="original-game">Jeu original : <?php echo esc_html($original_name); ?></small>
                    <?php endif; ?>
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
            'rejected' => ['class' => 'rejected', 'text' => '❌ Rejeté', 'color' => '#dc3545'],
            'archived' => ['class' => 'archived', 'text' => '📁 Archivé', 'color' => '#17a2b8']
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
        $is_revision = isset($submission['metadata']['is_revision']) && $submission['metadata']['is_revision'];
        
        // Déterminer l'URL du jeu si disponible
        $game_url = null;
        if ($status === 'published' && isset($submission['metadata']['published_game_id'])) {
            // Jeu publié : utiliser l'ID du jeu publié
            $game_id = $submission['metadata']['published_game_id'];
            if (class_exists('Sisme_Game_Creator_Data_Manager')) {
                $game_url = Sisme_Game_Creator_Data_Manager::get_game_url($game_id);
            }
        } elseif ($is_revision && isset($submission['metadata']['original_published_id'])) {
            // Révision : utiliser l'ID du jeu original
            $original_submission = self::get_submission_for_admin($submission['metadata']['original_published_id']);
            if ($original_submission && isset($original_submission['metadata']['published_game_id'])) {
                $game_id = $original_submission['metadata']['published_game_id'];
                if (class_exists('Sisme_Game_Creator_Data_Manager')) {
                    $game_url = Sisme_Game_Creator_Data_Manager::get_game_url($game_id);
                }
            }
        }
        
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
            
            <!-- Lien vers le jeu -->
            <?php if ($game_url && $game_url !== home_url('/')): ?>
                <a href="<?php echo esc_url($game_url); ?>" 
                   target="_blank" 
                   class="action-btn link-btn active"
                   title="Voir la page du jeu">
                    🔗
                </a>
            <?php else: ?>
                <button class="action-btn link-btn disabled" 
                        disabled
                        title="Jeu non publié ou lien indisponible">
                    🔗
                </button>
            <?php endif; ?>
            
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
            <button class="action-btn delete-btn active" 
                    data-submission-id="<?php echo esc_attr($submission_id); ?>"
                    data-user-id="<?php echo esc_attr($user_id); ?>"
                    title="<?php echo $is_revision ? 'Supprimer la révision (conserve les médias)' : 'Supprimer la soumission (supprime les médias)'; ?>">
                🗑️
            </button>
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
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_admin_nonce')) {
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
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
        }
        
        if (!wp_verify_nonce($_POST['security'] ?? '', 'sisme_admin_nonce')) {
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
        
        // AJOUT ICI : Détecter si c'est une révision
        $is_revision = $submission['metadata']['is_revision'] ?? false;
        
        if ($is_revision) {
            // Traitement spécial pour les révisions
            $result = self::approve_revision($submission, $user_id);
            
            if ($result) {
                wp_send_json_success([
                    'message' => 'Révision approuvée avec succès',
                    'is_revision' => true
                ]);
            } else {
                wp_send_json_error([
                    'message' => 'Erreur lors de l\'approbation de la révision'
                ]);
            }
            return; // Important : arrêter l'exécution ici pour les révisions
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

    /**
     * Approuver une révision et mettre à jour le jeu original
     * 
     * @param array $revision_submission Données de la révision
     * @param int $user_id ID du développeur
     * @return bool Succès de l'opération
     */
    private static function approve_revision($revision_submission, $user_id) {
        error_log('DEBUG: Début approve_revision pour user_id=' . $user_id . ', revision_id=' . $revision_submission['id']);
        
        // Récupérer l'ID de la soumission originale
        $original_id = $revision_submission['metadata']['original_published_id'] ?? null;
        if (!$original_id) {
            error_log('DEBUG: Échec - original_id manquant dans metadata');
            return false;
        }
        
        // Récupérer la soumission originale
        $original_submission = self::get_submission_for_admin($original_id);
        if (!$original_submission) {
            error_log('DEBUG: Échec - soumission originale introuvable pour ID=' . $original_id);
            return false;
        }
        
        // Récupérer l'ID du jeu publié
        $game_id = $original_submission['metadata']['published_game_id'] ?? null;
        if (!$game_id) {
            error_log('DEBUG: Échec - published_game_id manquant dans la soumission originale');
            return false;
        }
        
        // Mettre à jour le jeu avec les données de la révision
        if (class_exists('Sisme_Game_Creator_Data_Manager')) {
            // Utiliser la même logique que pour les nouvelles soumissions
            if (class_exists('Sisme_Game_Creator')) {
                $updated_game_data = Sisme_Game_Creator::extract_game_data_from_submission($revision_submission);
                
                if (is_wp_error($updated_game_data)) {
                    error_log('DEBUG: Échec - Erreur lors de l\'extraction des données: ' . $updated_game_data->get_error_message());
                    return false;
                }
            } else {
                error_log('DEBUG: Échec - Classe Sisme_Game_Creator non trouvée');
                return false;
            }
            
            // Tentative de mise à jour du jeu
            error_log('DEBUG: Tentative de mise à jour du jeu game_id=' . $game_id);
            $update_result = Sisme_Game_Creator_Data_Manager::update_game($game_id, $updated_game_data);
            
            if (is_wp_error($update_result)) {
                error_log('DEBUG: Échec - Erreur lors de la mise à jour du jeu: ' . $update_result->get_error_message());
                return false;
            }
            
            error_log('DEBUG: Jeu mis à jour avec succès');
        } else {
            error_log('DEBUG: Échec - Classe Sisme_Game_Creator_Data_Manager introuvable');
            return false;
        }
        
        // 2. Gérer les soumissions via le module dédié
        if (class_exists('Sisme_Game_Submission_Data_Manager')) {
            error_log('DEBUG: Début de l\'approbation de révision via le module de soumissions');
            
            $approval_result = Sisme_Game_Submission_Data_Manager::approve_revision($user_id, $revision_submission['id']);
            
            if (is_wp_error($approval_result)) {
                error_log('DEBUG: Échec - Erreur lors de l\'approbation de révision: ' . $approval_result->get_error_message());
                return false;
            }
            
            error_log('DEBUG: Révision approuvée dans le système de soumissions');
        } else {
            error_log('DEBUG: Échec - Classe Sisme_Game_Submission_Data_Manager introuvable');
            return false;
        }
        
        // 3. Envoyer l'email de confirmation
        $user = get_userdata($user_id);
        if ($user && class_exists('Sisme_Email_Templates') && class_exists('Sisme_Email_Manager')) {
            $game_name = $updated_game_data['name'] ?? 'Votre jeu';
            
            $email_content = Sisme_Email_Templates::revision_approved(
                $user->display_name,
                $game_name
            );
            
            $email_result = Sisme_Email_Manager::send_email(
                [$user_id],
                "Révision approuvée : {$game_name}",
                $email_content
            );
            
            if (!$email_result) {
                error_log('DEBUG: Avertissement - Échec de l\'envoi de l\'email');
            }
        } else {
            error_log('DEBUG: Avertissement - Classes d\'email manquantes ou utilisateur introuvable');
        }
        
        error_log('DEBUG: Révision approuvée avec succès');
        return true;
    }
    
    /**
     * Handler AJAX pour supprimer une soumission (révision)
     */
    public static function ajax_delete_submission() {
        // Vérification de sécurité
        if (!check_ajax_referer('sisme_admin_nonce', 'security', false)) {
            wp_send_json_error(['message' => 'Token de sécurité invalide']);
            return;
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permissions insuffisantes']);
            return;
        }
        
        $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if (empty($submission_id) || empty($user_id)) {
            wp_send_json_error(['message' => 'Paramètres manquants']);
            return;
        }
        
        // Vérifier que la soumission existe
        $submission = self::get_submission_for_admin($submission_id);
        if (!$submission) {
            wp_send_json_error(['message' => 'Soumission introuvable']);
            return;
        }
        
        // Information sur le type pour le log
        $is_revision = isset($submission['metadata']['is_revision']) && $submission['metadata']['is_revision'];
        $submission_type = $is_revision ? 'révision' : 'soumission';
        
        // Supprimer via le module de soumissions (sans supprimer les médias)
        if (class_exists('Sisme_Game_Submission_Data_Manager')) {
            $delete_result = Sisme_Game_Submission_Data_Manager::delete_submission($user_id, $submission_id);
            
            if (is_wp_error($delete_result)) {
                wp_send_json_error(['message' => 'Erreur lors de la suppression: ' . $delete_result->get_error_message()]);
                return;
            }
            
            if ($delete_result) {
                // Log de l'action admin
                error_log("ADMIN: Suppression de {$submission_type} {$submission_id} par admin " . get_current_user_id());
                
                wp_send_json_success(['message' => ucfirst($submission_type) . ' supprimée avec succès']);
            } else {
                wp_send_json_error(['message' => 'Échec de la suppression']);
            }
        } else {
            wp_send_json_error(['message' => 'Module de gestion des soumissions introuvable']);
        }
    }
}