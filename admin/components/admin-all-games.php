<?php
/**
 * File: /sisme-games-editor/admin/components/admin-all-games.php
 * Classe pour gérer le sous-menu Tous les jeux et ses pages
 * 
 * RESPONSABILITÉ:
 * - Interface d'affichage uniquement (render methods)
 * - Utilise les fonctions métier de PHP-admin-submission-functions.php
 * 
 * ARCHITECTURE:
 * - admin-all-games.php → Interface & Affichage
 * - PHP-admin-submission-functions.php → Logique métier & Data
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure la logique métier
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/assets/PHP-admin-submission-functions.php';

class Sisme_Admin_All_Games {
    

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            null,
            'Jeux',
            'Jeux',
            'manage_options',
            'sisme-games-all-games',
            array(__CLASS__, 'render')
        );
    }

    public static function render() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
        self::enqueue_admin_assets();

        $page = new Sisme_Admin_Page_Wrapper(
            'Tous les Jeux',
            'Gestion complète de tous les jeux disponibles',
            'game',
            admin_url('admin.php?page=sisme-games-jeux'),
            'Retour au menu Jeux',
        );
        
        $page->render_start();
        self::render_game_stats();
        self::render_submission_pending();
        self::render_submission_draft();
        self::render_game_list();
        $page->render_end();    
    }

    /**
     * Affiche les statistiques générales des jeux
     */
    public static function render_game_stats() {
        $submissions_data = self::get_submissions_stats();
        $games_data = self::get_games_stats();
        $content = self::render_stats_content($submissions_data, $games_data);
        Sisme_Admin_Page_Wrapper::render_card(
            'Statistiques des jeux',
            'stats',
            'Vue d\'ensemble des données et soumissions',
            '',
            false,
            $content
        );
    }

    /**
     * Affiche les soumissions en attente de validation
     */
    public static function render_submission_pending() {
        Sisme_Admin_Page_Wrapper::render_card(
            'Soumissions en attente', 
            'submission', 
            'Toutes les soumissions en attente de validation',
            'sisme-admin-flex-col sisme-admin-gap-6',
            false,
            self::render_submission_game_pending() . self::render_submission_revision_pending()
        );
    }

    public static function render_submission_game_pending() {
        ob_start();
        Sisme_Admin_Page_Wrapper::render_card(
            'Les nouveaux jeux', 
            'game', 
            '',
            '',
            false,
            self::render_table('pending', 'exclude_revisions')
        );
        return ob_get_clean();
    }

    public static function render_submission_revision_pending() {
        ob_start();
        Sisme_Admin_Page_Wrapper::render_card(
            'Les révisions', 
            'revision', 
            '',
            '',
            false,
            self::render_table('pending', 'only_revisions')
        );
        return ob_get_clean();
    }

    public static function render_submission_draft() {
        Sisme_Admin_Page_Wrapper::render_card(
            'Brouillon en cours', 
            'draft', 
            'Toutes les brouillons en cours',
            'sisme-admin-flex-col sisme-admin-gap-6',
            false,
            self::render_submission_game_draft() . self::render_submission_revision_draft()
        );
    }

    public static function render_submission_game_draft() {
        ob_start();
        Sisme_Admin_Page_Wrapper::render_card(
            'Les brouillons de jeux', 
            'game', 
            '',
            '',
            false,
            self::render_table('draft', 'exclude_revisions')
        );
        return ob_get_clean();
    }

    public static function render_submission_revision_draft() {
        ob_start();
        Sisme_Admin_Page_Wrapper::render_card(
            'Les brouillons de révisions', 
            'revision', 
            '',
            '',
            false,
            self::render_table('draft', 'only_revisions')
        );
        return ob_get_clean();
    }

    /**
     * Affiche la liste complète des jeux
     */
    public static function render_game_list() {
        $all_games = get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'game_description',
                    'compare' => 'EXISTS'
                )
            )
        ));
        Sisme_Admin_Page_Wrapper::render_card(
            'Liste des jeux',
            'lib',
            'Catalogue complet des jeux publiés et en cours',
            '',
            false,
            self::render_games_table($all_games)
        );
    }

    /**
     * Rendu du contenu des statistiques
     */
    private static function render_stats_content($submissions_data, $games_data) {
        ob_start();
        ?>
        <div class="sisme-admin-grid sisme-admin-grid-5">
            <div class="sisme-admin-stat-card sisme-admin-stat-card-info">
                <div class="sisme-admin-stat-number"><?php echo count($games_data); ?></div>
                <div class="sisme-admin-stat-label">Jeux Totaux</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-warning">
                <div class="sisme-admin-stat-number"><?php echo $submissions_data['pending']; ?></div>
                <div class="sisme-admin-stat-label">Soumission en Attente</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-special">
                <div class="sisme-admin-stat-number"><?php echo $submissions_data['approved']; ?></div>
                <div class="sisme-admin-stat-label">Soumission Approuvée</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-secondary">
                <div class="sisme-admin-stat-number"><?php echo $submissions_data['draft']; ?></div>
                <div class="sisme-admin-stat-label">Brouillons en cours</div>
            </div>
            <div class="sisme-admin-stat-card sisme-admin-stat-card-danger">
                <div class="sisme-admin-stat-number"><?php echo $submissions_data['archived']; ?></div>
                <div class="sisme-admin-stat-label">Révisions Archivées</div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Rendu du tableau des jeux
     */
    private static function render_games_table($games) {
        ob_start();
        ?>
        <div class="sisme-admin-table-container">
            <table class="sisme-admin-table">
                <thead>
                    <tr>
                        <th>Jeu</th>
                        <th>ID</th>
                        <th>État</th>
                        <th>Propriétaire</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($games as $game): ?>
                    <tr id="game-row-<?php echo esc_attr($game->term_id); ?>">
                        <td>
                            <strong><?php echo esc_html($game->name); ?></strong>
                        </td>
                        <td>
                            <span class="sisme-admin-badge sisme-admin-badge-secondary">
                                <?php echo $game->term_id; ?>
                            </span>
                        </td>
                        <!-- Afficher le statut du jeu : is_featured is_team_choice etc.. -->
                        <td>
                            <?php
                            $game_meta = get_term_meta($game->term_id);
                            $is_featured = !empty($game_meta['game_is_featured'][0]) && $game_meta['game_is_featured'][0] === 'true';
                            $is_team_choice = !empty($game_meta['is_team_choice'][0]) ? $game_meta['is_team_choice'][0] : false;
                            $is_unpublished = !empty($game_meta['game_is_unpublished'][0]) && $game_meta['game_is_unpublished'][0] === 'true';
                            ?>
                            <div class="sisme-admin-grid-3">
                                <?php if ($is_featured): ?>
                                    <span class="sisme-admin-badge sisme-admin-badge-purple"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('featured', 0, 12); ?></span>
                                <?php endif; ?>
                                <?php if ($is_team_choice): ?>
                                    <span class="sisme-admin-badge sisme-admin-badge-danger"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('heart', 0, 12); ?></span>
                                <?php endif; ?>
                                <?php if ($is_unpublished): ?>
                                    <span class="sisme-admin-badge sisme-admin-badge-danger"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('unpublished', 0, 12); ?></span>
                                <?php else: ?>
                                    <span class="sisme-admin-badge sisme-admin-badge-success"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('published', 0, 12); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <!-- Afficher l'utilisateur propriétaire de la page du jeu (Placeholder) -->
                        <!-- TODO -->
                        <td>
                            <?php
                            $developer_id = !empty($game_meta['developer_user_id'][0]) ? $game_meta['developer_user_id'][0] : null;
                            $developer_info = $developer_id ? get_userdata($developer_id) : null;
                            ?>
                            <?php if ($developer_info): ?>
                                <div class="sisme-admin-flex-col-sm">
                                    <strong><?php echo esc_html($developer_info->display_name); ?></strong>
                                    <small>ID: <?php echo esc_html($developer_info->ID); ?></small>
                                </div>
                            <?php else: ?>
                                <span class="sisme-admin-badge sisme-admin-badge-secondary">👤 Inconnu</span>
                            <?php endif; ?>
                        </td>
                        <!-- Afficher les boutons d'action -->
                        <td>
                            <?php
                            // Réutiliser les variables déjà définies
                            $game_url = null;
                            
                            // Trouver l'URL du jeu s'il est publié et non dépublié
                            if (!$is_unpublished) {
                                $posts = get_posts([
                                    'tag__in' => [$game->term_id],
                                    'post_type' => 'post',
                                    'post_status' => 'publish',
                                    'numberposts' => 1
                                ]);
                                if (!empty($posts)) {
                                    $game_url = get_permalink($posts[0]->ID);
                                }
                            }
                            ?>
                            <div class="sisme-admin-action-group">
                                <!-- Bouton Voir le jeu -->
                                <?php if ($game_url && !$is_unpublished): ?>
                                    <a href="<?php echo esc_url($game_url); ?>" 
                                       target="_blank" 
                                       class="sisme-admin-action-btn" 
                                       title="Voir le jeu">🔗</a>
                                <?php else: ?>
                                    <span class="sisme-admin-action-btn sisme-admin-disabled" 
                                          title="Jeu non accessible">🔗</span>
                                <?php endif; ?>
                                
                                <!-- Bouton Dépublier ou Publier selon le statut -->
                                <?php if ($is_unpublished): ?>
                                    <button type="button"
                                            id="publish-game-<?php echo esc_attr($game->term_id); ?>" 
                                            class="sisme-admin-action-btn" 
                                            data-game-name="<?php echo esc_attr($game->name); ?>"
                                            title="Republier le jeu"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('published', 0, 12)?></button>
                                <?php else: ?>
                                    <button type="button"
                                            id="unpublish-game-<?php echo esc_attr($game->term_id); ?>" 
                                            class="sisme-admin-action-btn" 
                                            data-game-name="<?php echo esc_attr($game->name); ?>"
                                            title="Dépublier le jeu"><?php echo Sisme_Admin_Page_Wrapper::get_predefined_icon('unpublished', 0, 12)?></button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Récupère les statistiques des soumissions via la classe métier
     */
    private static function get_submissions_stats() {
        // Utiliser la méthode publique de la classe métier
        if (class_exists('Sisme_Admin_Submission_Functions')) {
            try {
                $stats = Sisme_Admin_Submission_Functions::get_submissions_statistics();
                return [
                    'pending' => $stats['pending'] ?? 'NC',
                    'approved' => $stats['published'] ?? 'NC',
                    'rejected' => $stats['rejected'] ?? 'NC',
                    'draft' => $stats['draft'] ?? 'NC',
                    'total' => $stats['total'] ?? 'NC',
                    'archived' => $stats['archived_count'] ?? 'NC'
                ];
            } catch (Exception $e) {
                error_log('Erreur lors de la récupération des stats de soumissions: ' . $e->getMessage());
            }
        }
        return [
            'pending' => 'NC',
            'approved' => 'NC',
            'rejected' => 'NC',
            'draft' => 'NC',
            'total' => 'NC',
            'archived' => 'NC'
        ];
    }

    /**
     * Récupère les statistiques des jeux
     */
    private static function get_games_stats() {
        return get_terms(array(
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key' => 'game_description',
                    'compare' => 'EXISTS'
                )
            )
        )) ?: [];
    }

    // ========================================
    // MÉTHODES DE RENDU POUR LES SOUMISSIONS
    // ========================================

    /**
     * Rendu du tableau des soumissions
     * @param string|null $filter_status Statut à filtrer ('pending', 'draft', 'published', 'rejected', 'archived')
     * @param string|null $revision_filter Filtre pour les révisions ('only_revisions', 'exclude_revisions', null pour tout)
     */
    public static function render_table($filter_status = null, $revision_filter = null) {
        $submissions_data = Sisme_Admin_Submission_Functions::get_submissions_data($filter_status, $revision_filter);
        ob_start();
        self::render_submissions_table($submissions_data);
        return ob_get_clean();
    }
    
    /**
     * Charger les assets admin
     */
    private static function enqueue_admin_assets() {
        
        wp_enqueue_style(
            'sisme-admin-shared',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'admin/assets/CSS-admin-shared.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );
        
        wp_enqueue_script(
            'sisme-admin-submissions',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'admin/assets/JS-admin-submissions.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        wp_enqueue_script(
            'sisme-admin-games-actions',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'admin/assets/JS-admin-games-actions.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        // Localiser les scripts avec les données AJAX
        wp_localize_script('sisme-admin-submissions', 'sismeAdminAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_admin_nonce'),
            'isAdmin' => true
        ]);
        
        wp_localize_script('sisme-admin-games-actions', 'sismeAdminAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_admin_nonce'),
            'isAdmin' => true
        ]);
    }

    /**
     * Rendu de la section des archives
     */
    public static function render_archives_section($archived_data) {
        if (empty($archived_data)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="sisme-admin-section">
            <h3 class="sisme-admin-subtitle">📁 Archives (<?php echo count($archived_data); ?>)</h3>
            <p class="sisme-admin-comment">Révisions approuvées et autres soumissions archivées</p>
            
            <div class="sisme-admin-container">
                <table class="sisme-admin-table">
                    <thead>
                        <tr>
                            <th>Jeu/Révision</th>
                            <th>Développeur</th>
                            <th>Date d'archivage</th>
                            <th>Motif</th>
                            <th>Actions</th>
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
    public static function render_archive_row($submission) {
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
            $original_submission = Sisme_Admin_Submission_Functions::get_submission_for_admin($metadata['original_published_id']);
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
        <tr>            
            <!-- Jeu/Révision -->
            <td>
                <div class="sisme-admin-flex-col-sm">
                    <?php if ($is_revision): ?>
                        <span class="sisme-admin-badge-purple">🔄</span>
                    <?php endif; ?>
                    <strong><?php echo esc_html($game_name); ?></strong>
                    <small class="sisme-admin-small sisme-admin-code">ID: <?php echo esc_html($submission['id']); ?></small>
                </div>
            </td>
            <!-- Développeur -->
            <td>
                <div class="sisme-admin-flex-col-sm">
                    <strong><?php echo esc_html($user_data['display_name'] ?? 'Inconnu'); ?></strong>
                    <small>ID: <?php echo esc_html($user_data['user_id'] ?? 'N/A'); ?></small>
                </div>
            </td>
            <!-- Date d'archivage -->
            <td>
                <time><?php echo esc_html($archived_at ?: 'N/A'); ?></time>
            </td>
            <!-- Motif -->
            <td>
                <span class="sisme-admin-small"><?php echo esc_html($display_reason); ?></span>
            </td>
            <!-- Actions -->
            <td>
                <div class="sisme-admin-action-group">
                    <!-- Lien vers le jeu -->
                    <?php if ($game_url): ?>
                        <a href="<?php echo esc_url($game_url); ?>" 
                           target="_blank" 
                           class="sisme-admin-action-btn" 
                           title="Voir la page du jeu">
                            🔗
                        </a>
                    <?php else: ?>
                        <span class="sisme-admin-action-btn sisme-admin-opacity-50" 
                              title="Lien non disponible">
                            🔗
                        </span>
                    <?php endif; ?>
                    
                    <!-- Voir plus -->
                    <button class="sisme-admin-action-btn" 
                            id="view-btn-<?php echo esc_attr($submission['id']); ?>"
                            data-submission-id="<?php echo esc_attr($submission['id']); ?>"
                            data-user-id="<?php echo esc_attr($user_data['user_id']); ?>"
                            title="Voir les détails">
                        👁️
                    </button>
                    
                    <!-- Supprimer définitivement -->
                    <button class="sisme-admin-action-btn active" 
                            id="delete-btn-<?php echo esc_attr($submission['id']); ?>"
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
            <td colspan="5" class="sisme-admin-p-lg">
                <div class="sisme-admin-card sisme-admin-card-no-transformation">
                    <div class="admin-details-content">
                        <div class="admin-loading sisme-admin-text-center">
                            ⏳ Chargement des détails de l'archive...
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php
        return ob_get_clean();
    }

    /**
     * Rendu des statistiques
     */
    public static function render_stats($stats) {
        ob_start();
        ?>
        <div class="sisme-admin-stats">
            <h3 class="sisme-admin-subtitle">📊 Statistiques des Soumissions</h3>
            <div class="sisme-admin-grid sisme-admin-grid-6">
                <div class="sisme-admin-stat-card sisme-admin-stat-card-secondary">
                    <span class="sisme-admin-stat-number"><?php echo $stats['draft']; ?></span>
                    <span class="sisme-admin-stat-label">📝 Brouillons</span>
                </div>
                <div class="sisme-admin-stat-card sisme-admin-stat-card-warning">
                    <span class="sisme-admin-stat-number"><?php echo $stats['pending']; ?></span>
                    <span class="sisme-admin-stat-label">⏳ En attente</span>
                </div>
                <div class="sisme-admin-stat-card sisme-admin-stat-card-success">
                    <span class="sisme-admin-stat-number"><?php echo $stats['published']; ?></span>
                    <span class="sisme-admin-stat-label">✅ Publiés</span>
                </div>
                <div class="sisme-admin-stat-card sisme-admin-stat-card-danger">
                    <span class="sisme-admin-stat-number"><?php echo $stats['rejected']; ?></span>
                    <span class="sisme-admin-stat-label">❌ Rejetés</span>
                </div>
                <div class="sisme-admin-stat-card sisme-admin-stat-card-info">
                    <span class="sisme-admin-stat-number"><?php echo $stats['archived_count']; ?></span>
                    <span class="sisme-admin-stat-label">📁 Archivés</span>
                </div>
                <div class="sisme-admin-stat-card sisme-admin-stat-card-special">
                    <span class="sisme-admin-stat-number"><?php echo $stats['total']; ?></span>
                    <span class="sisme-admin-stat-label">📊 Total</span>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Rendu du tableau des soumissions
     */
    public static function render_submissions_table($submissions) {
        if (empty($submissions)) {
            ?>
            <div class="sisme-admin-alert sisme-admin-alert-info sisme-admin-text-center">Aucune données pour le moment.</div>
            <?php
        } else {       
        ?>
        <table class="sisme-admin-table">
            <thead>
                <tr>
                    <th class="col-game">Jeu</th>
                    <th class="col-developer">Développeur</th>
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
        <?php
        }
    }

    /**
     * Rendu d'une ligne de soumission
     */
    public static function render_submission_row($submission) {
        $game_data = $submission['game_data'] ?? [];
        $metadata = $submission['metadata'] ?? [];
        $user_data = $submission['user_data'] ?? [];

        // Détection révision
        $is_revision = $submission['metadata']['is_revision'] ?? false;
        $original_id = $submission['metadata']['original_published_id'] ?? null;
        $original_name = '';
        if ($is_revision && $original_id) {
            // Récupérer le nom du jeu original
            $original_submission = Sisme_Admin_Submission_Functions::get_submission_for_admin($original_id);
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

            <!-- Jeu -->
            <td class="col-game">
                <div class="sisme-admin-flex-col-sm">
                    <?php if ($is_revision): ?>
                        <span class="sisme-admin-badge sisme-admin-badge-purple">🔄 RÉVISION</span>
                    <?php endif; ?>
                    <strong><?php echo esc_html($game_name); ?></strong>
                    <?php if ($is_revision && $original_name): ?>
                        <small class="sisme-admin-small sisme-admin-text-blue">Jeu original : <?php echo esc_html($original_name); ?></small>
                    <?php endif; ?>
                    <?php if ($studio_name): ?>
                        <small class="sisme-admin-small"><?php echo esc_html($studio_name); ?></small>
                    <?php endif; ?>
                    <small class="sisme-admin-small sisme-admin-code">ID: <?php echo esc_html($submission['id']); ?></small>
                </div>
            </td>

            <!-- Développeur -->
            <td class="col-developer">
                <div class="sisme-admin-flex-col-sm">
                    <strong><?php echo esc_html($user_data['display_name'] ?? 'Inconnu'); ?></strong>
                    <small>ID: <?php echo esc_html($user_data['user_id'] ?? 'N/A'); ?></small>
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
            <td colspan="6" class="sisme-admin-p-lg">
                <div class="sisme-admin-card sisme-admin-card-no-transformation">
                    <div class="admin-details-content">
                        <div class="admin-loading sisme-admin-text-center">
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
    public static function render_media_thumbnails($game_data) {
        $covers = $game_data['covers'] ?? [];
        $screenshots = $game_data['screenshots'] ?? [];
        
        ob_start();
        ?>
        <div class="sisme-admin-thumb-group">
            <?php if (!empty($covers['horizontal'])): ?>
                <a href="<?php echo esc_url(wp_get_attachment_url($covers['horizontal'])); ?>" 
                   target="_blank" 
                   class="sisme-admin-thumb sisme-admin-thumb-sm sisme-admin-thumb-hover-blue sisme-admin-thumb-overlay" 
                   data-overlay="🔗"
                   title="Cover horizontale">
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($covers['horizontal'], 'thumbnail')); ?>" 
                         alt="Cover H" />
                </a>
            <?php endif; ?>
            
            <?php if (!empty($covers['vertical'])): ?>
                <a href="<?php echo esc_url(wp_get_attachment_url($covers['vertical'])); ?>" 
                   target="_blank" 
                   class="sisme-admin-thumb sisme-admin-thumb-sm sisme-admin-thumb-hover-green sisme-admin-thumb-overlay" 
                   data-overlay="🔗"
                   title="Cover verticale">
                    <img src="<?php echo esc_url(wp_get_attachment_image_url($covers['vertical'], 'thumbnail')); ?>" 
                         alt="Cover V" />
                </a>
            <?php endif; ?>
            
            <?php if (!empty($screenshots)): ?>
                <?php foreach (array_slice($screenshots, 0, 3) as $screenshot_id): ?>
                    <a href="<?php echo esc_url(wp_get_attachment_url($screenshot_id)); ?>" 
                       target="_blank" 
                       class="sisme-admin-thumb sisme-admin-thumb-sm sisme-admin-thumb-hover-purple sisme-admin-thumb-overlay" 
                       data-overlay="🔗"
                       title="Screenshot">
                        <img src="<?php echo esc_url(wp_get_attachment_image_url($screenshot_id, 'thumbnail')); ?>" 
                             alt="Screenshot" />
                    </a>
                <?php endforeach; ?>
                
                <?php if (count($screenshots) > 3): ?>
                    <span class="sisme-admin-badge sisme-admin-badge-secondary">+<?php echo count($screenshots) - 3; ?></span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (empty($covers) && empty($screenshots)): ?>
                <span class="sisme-admin-text-muted sisme-admin-small">Aucun média</span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Rendu du badge de statut
     */
    public static function render_status_badge($status) {
        $status_config = [
            'draft' => ['badge_class' => 'sisme-admin-badge-secondary', 'text' => '📝 Brouillon'],
            'pending' => ['badge_class' => 'sisme-admin-badge-warning', 'text' => '⏳ En attente'],
            'published' => ['badge_class' => 'sisme-admin-badge-success', 'text' => '✅ Publié'],
            'rejected' => ['badge_class' => 'sisme-admin-badge-danger', 'text' => '❌ Rejeté'],
            'archived' => ['badge_class' => 'sisme-admin-badge-info', 'text' => '📁 Archivé']
        ];
        
        $config = $status_config[$status] ?? $status_config['draft'];
        
        return sprintf(
            '<span class="sisme-admin-badge %s">%s</span>',
            esc_attr($config['badge_class']),
            esc_html($config['text'])
        );
    }

    /**
     * Rendu des boutons d'actions
     */
    public static function render_action_buttons($submission, $user_data) {
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
            $original_submission = Sisme_Admin_Submission_Functions::get_submission_for_admin($submission['metadata']['original_published_id']);
            if ($original_submission && isset($original_submission['metadata']['published_game_id'])) {
                $game_id = $original_submission['metadata']['published_game_id'];
                if (class_exists('Sisme_Game_Creator_Data_Manager')) {
                    $game_url = Sisme_Game_Creator_Data_Manager::get_game_url($game_id);
                }
            }
        }
        
        ob_start();
        ?>
        <div class="sisme-admin-action-group">

            <!-- Voir plus -->
            <button class="sisme-admin-action-btn" 
                    id="view-btn-<?php echo esc_attr($submission_id); ?>"
                    data-submission-id="<?php echo esc_attr($submission_id); ?>"
                    data-user-id="<?php echo esc_attr($user_id); ?>"
                    title="Voir les détails">
                👁️
            </button>
            
            <!-- Lien vers le jeu -->
            <?php if ($game_url && $game_url !== home_url('/')): ?>
                <a href="<?php echo esc_url($game_url); ?>" 
                   target="_blank" 
                   class="sisme-admin-action-btn"
                   title="Voir la page du jeu">
                    🔗
                </a>
            <?php else: ?>
                <button class="sisme-admin-action-btn sisme-admin-opacity-50" 
                        disabled
                        title="Jeu non publié ou lien indisponible">
                    🔗
                </button>
            <?php endif; ?>
            
            <!-- Approuver -->
            <button class="sisme-admin-action-btn <?php echo $status === 'pending' ? 'active' : 'sisme-admin-opacity-50'; ?>" 
                    id="approve-btn-<?php echo esc_attr($submission_id); ?>"
                    data-submission-id="<?php echo esc_attr($submission_id); ?>"
                    data-user-id="<?php echo esc_attr($user_id); ?>"
                    <?php echo $status === 'pending' ? '' : 'disabled'; ?>
                    title="Approuver la soumission">
                ✅
            </button>
            
            <!-- Rejeter -->
            <button class="sisme-admin-action-btn <?php echo in_array($status, ['pending', 'published']) ? 'active' : 'sisme-admin-opacity-50'; ?>" 
                    id="reject-btn-<?php echo esc_attr($submission_id); ?>"
                    data-submission-id="<?php echo esc_attr($submission_id); ?>"
                    data-user-id="<?php echo esc_attr($user_id); ?>"
                    <?php echo in_array($status, ['pending', 'published']) ? '' : 'disabled'; ?>
                    title="Rejeter la soumission">
                ❌
            </button>
            
            <!-- Supprimer -->
            <button class="sisme-admin-action-btn active" 
                    id="delete-btn-<?php echo esc_attr($submission_id); ?>"
                    data-submission-id="<?php echo esc_attr($submission_id); ?>"
                    data-user-id="<?php echo esc_attr($user_id); ?>"
                    title="<?php echo $is_revision ? 'Supprimer la révision (conserve les médias)' : 'Supprimer la soumission (supprime les médias)'; ?>">
                🗑️
            </button>
        </div>
        <?php
        return ob_get_clean();
    }
}
