<?php
/**
 * File: /sisme-games-editor/admin/components/admin-all-games.php
 * Classe pour gérer le sous-menu Tous les jeux et ses pages
 * 
 * RESPONSABILITÉ:
 * - Interface d'affichage uniquement (render methods)
 * - Utilise les fonctions métier de php-admin-submission-functions.php
 * 
 * ARCHITECTURE:
 * - admin-all-games.php → Interface & Affichage
 * - php-admin-submission-functions.php → Logique métier & Data
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure la logique métier
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/assets/php-admin-submission-functions.php';

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

        $page = new Sisme_Admin_Page_Wrapper(
            'Tous les Jeux',
            'Gestion complète de tous les jeux disponibles',
            'game',
            admin_url('admin.php?page=sisme-games-jeux'),
            'Retour au menu Jeux',
        );
        
        $page->render_start();
        self::render_game_stats();
        self::render_game_submission_pending();
        self::render_game_submission_draft();
        self::render_game_list();
        $page->render_end();    
    }

    /**
     * Affiche les statistiques générales des jeux
     */
    public static function render_game_stats() {
        Sisme_Admin_Page_Wrapper::render_card_start(
            'Statistiques des jeux',
            'stats',
            'Vue d\'ensemble des données et soumissions'
        );
        $submissions_data = self::get_submissions_stats();
        $games_data = self::get_games_stats();
        self::render_stats_content($submissions_data, $games_data);
        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    /**
     * Affiche les soumissions en attente de validation
     */
    public static function render_game_submission_pending() {
        Sisme_Admin_Page_Wrapper::render_card_start(
            'Soumissions en attente',
            'pending',
            'Jeux soumis par les développeurs en attente de validation'
        );
        Sisme_Admin_Submission_Functions::render('pending', 'exclude_revisions');
        
        Sisme_Admin_Submission_Functions::render('pending', 'only_revisions');

        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    /**
     * Affiche les brouillons de soumissions
     */
    public static function render_game_submission_draft() {
        Sisme_Admin_Page_Wrapper::render_card_start(
            'Soumissions brouillon',
            'draft',
            'Soumissions sauvegardées en tant que brouillons'
        );
        Sisme_Admin_Submission_Functions::render('draft');
        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    /**
     * Affiche les révisions en cours
     */
    public static function render_game_submission_pending_revisions() {
        Sisme_Admin_Page_Wrapper::render_card_start(
            'Révisions en cours',
            'pending',
            'Révisions en attente de validation'
        );
        Sisme_Admin_Submission_Functions::render('revision_draft');
        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    /**
     * Affiche les révisions archivées
     */
    public static function render_game_submission_archived_revisions() {
        Sisme_Admin_Page_Wrapper::render_card_start(
            'Révisions archivées',
            'archived',
            'Révisions archivées'
        );
        Sisme_Admin_Submission_Functions::render('archived');
        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    /**
     * Affiche la liste complète des jeux
     */
    public static function render_game_list() {
        Sisme_Admin_Page_Wrapper::render_card_start(
            'Liste des jeux',
            'game',
            'Catalogue complet des jeux publiés et en cours'
        );
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
        self::render_games_table($all_games);
        Sisme_Admin_Page_Wrapper::render_card_end();
    }

    /**
     * Rendu du contenu des statistiques
     */
    private static function render_stats_content($submissions_data, $games_data) {
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
    }

    /**
     * Rendu du tableau des jeux
     */
    private static function render_games_table($games) {
        ?>
        <div class="sisme-admin-table-container">
            <table class="sisme-admin-table">
                <thead>
                    <tr>
                        <th>Jeu</th>
                        <th>ID</th>
                        <th>Status</th>
                        <th>Développeur</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($games as $game): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($game->name); ?></strong>
                        </td>
                        <td>
                            <span class="sisme-admin-badge sisme-admin-badge-secondary">
                                <?php echo $game->term_id; ?>
                            </span>
                        </td>
                        <td>
                            <!-- Afficher le statut du jeu : is_featured is_team_choice etc.. -->
                        </td>
                        <td>
                            <!-- Afficher l'utilisateur propriétaire de la page du jeu -->
                        </td>
                        <td>
                            <!-- Afficher les boutons d'action -->
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
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
}
