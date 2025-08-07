<?php
/**
 * File: /sisme-games-editor/admin/components/admin-developers.php
 * Classe pour gérer la page admin des développeurs
 * 
 * FONCTIONNALITÉS:
 * - Gestion des candidatures développeurs (approuver/rejeter/révoquer)
 * - Affichage des statistiques
 * - Vue détails des informations développeurs
 * - Gestion des soumissions de jeux
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Developers {
    
    /**
     * Initialisation de la page admin
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('wp_ajax_sisme_admin_get_submission_details', array(__CLASS__, 'ajax_get_submission_details'));
        add_action('admin_init', array(__CLASS__, 'handle_form_submissions'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        
        // Initialiser le composant de création de développeurs/éditeurs
        self::load_developer_creator_component();
    }
    
    /**
     * Charge le composant de gestion des développeurs/éditeurs
     */
    private static function load_developer_creator_component() {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/components/admin-developers-creator.php';
        
        if (class_exists('Sisme_Admin_Developers_Creator')) {
            Sisme_Admin_Developers_Creator::init();
        }
    }
    
    /**
     * Ajoute le sous-menu "Développeurs" dans le menu admin
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'sisme-games-tableau-de-bord',
            'Développeurs',
            '💻 Développeurs',
            'manage_options',
            'sisme-games-developers',
            array(__CLASS__, 'render')
        );
    }

    /**
     * Gère les soumissions de formulaires
     */
    public static function handle_form_submissions() {
        // Traitement des actions développeurs
        if (isset($_POST['action']) && isset($_POST['user_id']) && wp_verify_nonce($_POST['_wpnonce'], 'sisme_developer_action')) {
            self::handle_developer_action();
        }
        
        // Traitement des actions de soumission
        if (isset($_POST['submission_action']) && isset($_POST['submission_id']) && wp_verify_nonce($_POST['_wpnonce'], 'sisme_submission_action')) {
            self::handle_submission_action();
        }
        
        // Traitement de suppression de soumission
        if (isset($_POST['action']) && $_POST['action'] === 'delete_submission' && wp_verify_nonce($_POST['_wpnonce'], 'admin_submission_delete')) {
            self::handle_submission_deletion();
        }
    }

    /**
     * Traite les actions sur les développeurs
     */
    private static function handle_developer_action() {
        $user_id = intval($_POST['user_id']);
        $action = sanitize_text_field($_POST['action']);
        $admin_notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');
        
        if (!class_exists('Sisme_Utils_Developer_Roles')) {
            self::add_admin_notice('❌ Erreur: La classe Sisme_Utils_Developer_Roles n\'est pas disponible.', 'error');
            return;
        }
        
        switch ($action) {
            case 'approve':
                if (Sisme_Utils_Developer_Roles::approve_application($user_id, $admin_notes)) {
                    self::add_admin_notice('✅ Candidature approuvée ! L\'utilisateur est maintenant développeur.', 'success');
                }
                break;
                
            case 'reject':
                if (Sisme_Utils_Developer_Roles::reject_application($user_id, $admin_notes)) {
                    self::add_admin_notice('❌ Candidature rejetée.', 'success');
                }
                break;
                
            case 'revoke':
                if (Sisme_Utils_Developer_Roles::revoke_developer($user_id, $admin_notes)) {
                    self::add_admin_notice('🔄 Statut développeur révoqué. L\'utilisateur est redevenu subscriber.', 'warning');
                }
                break;
                
            case 'reactivate':
                if (Sisme_Utils_Developer_Roles::reactivate_developer($user_id, $admin_notes)) {
                    self::add_admin_notice('✅ Développeur réactivé avec succès !', 'success');
                }
                break;
        }
    }

    /**
     * Traite les actions sur les soumissions
     */
    private static function handle_submission_action() {
        $submission_id = intval($_POST['submission_id']);
        $action = sanitize_text_field($_POST['submission_action']);
        $admin_notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');
        
        switch ($action) {
            case 'approve_submission':
                self::add_admin_notice('✅ Soumission approuvée ! Le jeu sera publié.', 'success');
                break;
                
            case 'reject_submission':
                self::add_admin_notice('❌ Soumission rejetée.', 'warning');
                break;
        }
    }

    /**
     * Traite la suppression de soumission
     */
    private static function handle_submission_deletion() {
        $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);
        
        if (!empty($submission_id) && $user_id) {
            Sisme_Utils_Media_Cleanup::cleanup_submission_media($user_id . '_' . $submission_id);
            
            if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
                require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
            }
            
            $result = Sisme_Game_Submission_Data_Manager::delete_submission($user_id, $submission_id);
            
            if (!is_wp_error($result)) {
                self::add_admin_notice('✅ Soumission et médias supprimés !', 'success');
            } else {
                self::add_admin_notice('❌ ' . $result->get_error_message(), 'error');
            }
        }
    }

    /**
     * Ajoute une notice admin
     */
    private static function add_admin_notice($message, $type = 'info') {
        add_action('admin_notices', function() use ($message, $type) {
            echo '<div class="notice notice-' . $type . ' is-dismissible">';
            echo '<p>' . $message . '</p>';
            echo '</div>';
        });
    }

    /**
     * Gère les requêtes AJAX pour les détails de soumission
     */
    public static function ajax_get_submission_details() {
        if (class_exists('Sisme_Admin_Submission_Functions')) {
            Sisme_Admin_Submission_Functions::ajax_get_submission_details();
        }
    }

    /**
     * Helper pour afficher une valeur ou "N.C" si vide
     */
    public static function display_value_or_nc($value, $default = 'N.C') {
        if (is_array($value)) {
            return !empty($value) ? implode(', ', array_map('esc_html', $value)) : $default;
        }
        
        return !empty($value) && trim($value) !== '' ? esc_html($value) : $default;
    }

    /**
     * Enqueue les styles et scripts
     */
    public static function enqueue_assets($hook) {
        // Ne charger les assets que sur notre page
        if ($hook !== 'sisme-games-editor_page_sisme-games-developers') {
            return;
        }
        
        wp_enqueue_script(
            'sisme-game-submission-details',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-developer/game-submission/assets/game-submission-details.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        wp_enqueue_script(
            'sisme-admin-submissions',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'admin/assets/admin-submissions.js',
            array('jquery'),
            SISME_GAMES_EDITOR_VERSION,
            true
        );
        
        wp_localize_script('sisme-game-submission-details', 'sismeAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisme_developer_nonce'),
            'isAdmin' => true
        ]);
        
        if (!class_exists('Sisme_Game_Submission_Renderer')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-renderer.php';
        }
        
        // Enqueue assets du composant créateur de développeurs/éditeurs
        if (class_exists('Sisme_Admin_Developers_Creator')) {
            Sisme_Admin_Developers_Creator::enqueue_assets($hook);
        }
    }

    /**
     * Render la page des développeurs
     */
    public static function render() {
        ?>
        <div class="sisme-admin-container">
            <h2 class="sisme-admin-title">👥 Gestion des développeurs</h2>
            <p class="sisme-admin-comment">Panneau de gestion des développeurs et de leurs soumissions de jeux</p>
            <div class="sisme-admin-flex-col">
                <?php
                self::render_stats();
                self::render_developers();
                self::render_studio();
                self::render_js();
                ?>
            </div>
        </div>
        <?php
    }



    /**
     * Affiche la section de gestion des studios/éditeurs
     */
    public static function render_studio() {
        // Render le composant de gestion des développeurs/éditeurs
        if (class_exists('Sisme_Admin_Developers_Creator')) {
            ?>
            <div class="sisme-admin-card">
                <h2 class="sisme-admin-section-title">🏢 Gestion des Studios</h2>
                <p class="sisme-admin-comment sisme-admin-mb-lg">Interface de gestion des studios de développement et éditeurs de jeux</p>
                <?php Sisme_Admin_Developers_Creator::render_component(); ?>
            </div>
            <hr class="sisme-admin-separator" style="margin: var(--sisme-admin-spacing-xl) 0; border: none; border-top: 2px solid var(--sisme-admin-gray-200);">
            <?php
        }
    }

    /**
     * Affiche le contenu HTML des développeurs
     */

    public static function render_stats() {
        global $wpdb;

        $meta_key = defined('Sisme_Utils_Users::META_DEVELOPER_STATUS') ? 
                    Sisme_Utils_Users::META_DEVELOPER_STATUS : 'sisme_developer_status';

        $stats = [
            'pending' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
                $meta_key, 'pending'
            )),
            'approved' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
                $meta_key, 'approved'
            )),
            'rejected' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
                $meta_key, 'rejected'
            ))
        ];

        ?>
        <div class="sisme-admin-card">
            <div class="sisme-admin-card-header">
                <h3 class="sisme-admin-heading">📊 Statistiques</h3>
            </div>
            <div class="sisme-admin-stats sisme-mb-6 sisme-admin-flex sisme-admin-gap-4">
                <div class="sisme-admin-stat-card sisme-admin-stat-warning">
                    <div class="sisme-admin-stat-number"><?php echo $stats['pending']; ?></div>
                    <div class="sisme-admin-stat-label">⏳ En attente</div>
                </div>
                <div class="sisme-admin-stat-card sisme-admin-stat-primary">
                    <div class="sisme-admin-stat-number"><?php echo $stats['approved']; ?></div>
                    <div class="sisme-admin-stat-label">✅ Approuvés</div>
                </div>
                <div class="sisme-admin-stat-card sisme-admin-stat-danger">
                    <div class="sisme-admin-stat-number"><?php echo $stats['rejected']; ?></div>
                    <div class="sisme-admin-stat-label">❌ Rejetés</div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Affiche le contenu HTML
     */
    public static function render_developers() {
        global $wpdb;

        $meta_key = defined('Sisme_Utils_Users::META_DEVELOPER_STATUS') ? 
                    Sisme_Utils_Users::META_DEVELOPER_STATUS : 'sisme_developer_status';

        $stats = [
            'pending' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
                $meta_key, 'pending'
            )),
            'approved' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
                $meta_key, 'approved'
            )),
            'rejected' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
                $meta_key, 'rejected'
            ))
        ];
        
        // Récupérer tous les développeurs
        $all_developers = [];
        $user_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$wpdb->usermeta} 
            WHERE meta_key = %s",
            $meta_key
        ));

        if (class_exists('Sisme_Utils_Users') && method_exists('Sisme_Utils_Users', 'get_user_dev_data')) {
            foreach ($user_ids as $user_id) {
                $dev_data = Sisme_Utils_Users::get_user_dev_data($user_id);
                if ($dev_data) {
                    $user_info = get_userdata($user_id);
                    $dev_data['user_info'] = [
                        'ID' => $user_id,
                        'display_name' => $user_info ? $user_info->display_name : 'Utilisateur inconnu',
                        'user_email' => $user_info ? $user_info->user_email : '',
                        'user_registered' => $user_info ? $user_info->user_registered : ''
                    ];
                    $all_developers[] = $dev_data;
                }
            }
        }

        usort($all_developers, function($a, $b) {
            $order = ['pending' => 1, 'approved' => 2, 'rejected' => 3, 'none' => 4];
            return ($order[$a['status']] ?? 5) <=> ($order[$b['status']] ?? 5);
        });

        ?>
        <div class="sisme-admin-card">
            <div class="sisme-admin-card-header">
                <h3 class="sisme-admin-heading">👥 Liste des Développeurs</h3>
            </div>
            <?php
            if (empty($all_developers)): ?>
                <div class="sisme-admin-empty-state">
                    <p>Aucun développeur trouvé.</p>
                </div>
            <?php else: ?>
                <!-- Barre de recherche pour développeurs -->
                <div class="sisme-admin-flex sisme-admin-mb-md">
                    <input type="text" 
                        id="developers-search" 
                        placeholder="Rechercher un développeur par nom ou studio..." 
                        class="sisme-admin-flex-1 sisme-admin-p-sm sisme-admin-border sisme-admin-rounded"
                        style="margin-right: var(--sisme-admin-spacing-sm);">
                    <button type="button" 
                            id="clear-developers-search" 
                            class="sisme-admin-btn sisme-admin-btn-secondary sisme-admin-btn-sm" 
                            onclick="sismesClearDevelopersSearch()">
                        🗑️ Effacer
                    </button>
                </div>
                <table class="sisme-admin-table wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Développeur</th>
                            <th>Statut</th>
                            <th>Studio</th>
                            <th>Date candidature</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_developers as $index => $dev_data): ?>
                            <tr class="sisme-dev-row" 
                                onclick="toggleDevDetails(<?php echo $index; ?>)" 
                                style="cursor: pointer;"
                                data-developer-name="<?php echo esc_attr(strtolower($dev_data['user_info']['display_name'])); ?>"
                                data-developer-email="<?php echo esc_attr(strtolower($dev_data['user_info']['user_email'])); ?>"
                                data-studio-name="<?php echo esc_attr(strtolower($dev_data['application']['studio_name'] ?? '')); ?>"
                                data-status="<?php echo esc_attr($dev_data['status']); ?>">
                                <td>
                                    <div class="sisme-admin-flex sisme-align-center sisme-admin-gap-2">
                                        <span class="sisme-toggle-icon" id="toggle-<?php echo $index; ?>">▶️</span>
                                        <div>
                                            <strong><?php echo esc_html($dev_data['user_info']['display_name']); ?></strong><br>
                                            <small><?php echo esc_html($dev_data['user_info']['user_email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $status_labels = [
                                        'pending' => '<span class="sisme-badge sisme-badge-warning">⏳ En attente</span>',
                                        'approved' => '<span class="sisme-badge sisme-badge-success">✅ Approuvé</span>',
                                        'rejected' => '<span class="sisme-badge sisme-badge-danger">❌ Rejeté</span>',
                                        'revoked' => '<span class="sisme-badge sisme-badge-danger">❌ Révoqué</span>',
                                        'none' => '<span class="sisme-badge sisme-badge-secondary">Aucun</span>'
                                    ];
                                    echo $status_labels[$dev_data['status']] ?? 'Inconnu';
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $studio_name = $dev_data['application']['studio_name'] ?? 'Non renseigné';
                                    echo esc_html($studio_name);
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $submitted_date = $dev_data['application']['submitted_date'] ?? '';
                                    if ($submitted_date) {
                                        echo date('d/m/Y', strtotime($submitted_date));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td class="sisme-admin-actions" onclick="event.stopPropagation();">
                                    <?php if ($dev_data['status'] === 'pending'): ?>
                                        <form method="post" class="sisme-inline-form">
                                            <?php wp_nonce_field('sisme_developer_action'); ?>
                                            <input type="hidden" name="user_id" value="<?php echo $dev_data['user_info']['ID']; ?>">
                                            <button type="submit" name="action" value="approve" 
                                                    class="sisme-btn sisme-btn-success sisme-btn-sm"
                                                    onclick="return confirm('Approuver cette candidature ?')">
                                                ✅ Approuver
                                            </button>
                                            <button type="submit" name="action" value="reject" 
                                                    class="sisme-btn sisme-btn-secondary sisme-btn-sm"
                                                    onclick="return confirm('Rejeter cette candidature ?')">
                                                ❌ Rejeter
                                            </button>
                                        </form>
                                    <?php elseif ($dev_data['status'] === 'approved'): ?>
                                        <form method="post" class="sisme-inline-form">
                                            <?php wp_nonce_field('sisme_developer_action'); ?>
                                            <input type="hidden" name="user_id" value="<?php echo $dev_data['user_info']['ID']; ?>">
                                            <button type="submit" name="action" value="revoke" 
                                                    class="sisme-btn sisme-btn-danger sisme-btn-sm"
                                                    onclick="return confirm('Révoquer le statut développeur ?')">
                                                🔄 Révoquer
                                            </button>
                                        </form>
                                    <?php elseif ($dev_data['status'] === 'revoked'): ?>
                                        <form method="post" class="sisme-inline-form">
                                            <?php wp_nonce_field('sisme_developer_action'); ?>
                                            <input type="hidden" name="user_id" value="<?php echo $dev_data['user_info']['ID']; ?>">
                                            <button type="submit" name="action" value="reactivate" 
                                                    class="sisme-btn sisme-btn-success sisme-btn-sm"
                                                    onclick="return confirm('Réactiver ce développeur ?')">
                                                ✅ Réactiver
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="sisme-admin-text-muted">Aucune action</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Ligne de détails cachée -->
                            <tr class="sisme-dev-details" id="details-<?php echo $index; ?>" style="display: none;">
                                <td colspan="5" class="sisme-admin-dev-details-container">
                                    <div class="sisme-admin-grid sisme-admin-grid-2 sisme-admin-gap-6">
                                        
                                        <!-- Colonne 1: Informations Studio -->
                                        <div>
                                            <h4 class="sisme-admin-details-title">
                                                🏢 Informations Studio
                                            </h4>
                                            
                                            <div class="sisme-admin-detail-item">
                                                <strong class="sisme-admin-detail-label">Nom du studio:</strong><br>
                                                <span><?php echo esc_html($dev_data['application']['studio_name'] ?? 'Non renseigné'); ?></span>
                                            </div>
                                            
                                            <div class="sisme-admin-detail-item">
                                                <strong class="sisme-admin-detail-label">Description:</strong><br>
                                                <span><?php echo esc_html(wp_trim_words($dev_data['application']['studio_description'] ?? 'Non renseignée', 20)); ?></span>
                                            </div>
                                            
                                            <div class="sisme-admin-detail-item">
                                                <strong class="sisme-admin-detail-label">Site web:</strong><br>
                                                <?php if (!empty($dev_data['application']['studio_website'])): ?>
                                                    <a href="<?php echo esc_url($dev_data['application']['studio_website']); ?>" 
                                                    target="_blank" class="sisme-admin-link">
                                                        <?php echo esc_html($dev_data['application']['studio_website']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span>Non renseigné</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if (!empty($dev_data['application']['studio_social_links'])): ?>
                                                <div class="sisme-admin-detail-item">
                                                    <strong class="sisme-admin-detail-label">Réseaux sociaux:</strong><br>
                                                    <?php foreach ($dev_data['application']['studio_social_links'] as $platform => $url): ?>
                                                        <?php if (!empty($url)): ?>
                                                            <div class="sisme-admin-social-link">
                                                                <strong><?php echo ucfirst($platform); ?>:</strong> 
                                                                <a href="<?php echo esc_url($url); ?>" target="_blank" class="sisme-admin-link">
                                                                    <?php echo esc_html($url); ?>
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Colonne 2: Informations Représentant -->
                                        <div>
                                            <h4 class="sisme-admin-details-title">
                                                👤 Représentant Légal
                                            </h4>
                                            
                                            <div class="sisme-admin-detail-item">
                                                <strong class="sisme-admin-detail-label">Nom complet:</strong><br>
                                                <span>
                                                    <?php 
                                                    $firstname = $dev_data['application']['representative_firstname'] ?? '';
                                                    $lastname = $dev_data['application']['representative_lastname'] ?? '';
                                                    echo esc_html(trim($firstname . ' ' . $lastname)) ?: 'Non renseigné';
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <div class="sisme-admin-detail-item">
                                                <strong class="sisme-admin-detail-label">Email:</strong><br>
                                                <span><?php echo esc_html($dev_data['application']['representative_email'] ?? 'Non renseigné'); ?></span>
                                            </div>
                                            
                                            <div class="sisme-admin-detail-item">
                                                <strong class="sisme-admin-detail-label">Téléphone:</strong><br>
                                                <span><?php echo esc_html($dev_data['application']['representative_phone'] ?? 'Non renseigné'); ?></span>
                                            </div>
                                            
                                            <div class="sisme-admin-detail-item">
                                                <strong class="sisme-admin-detail-label">Date de naissance:</strong><br>
                                                <span>
                                                    <?php 
                                                    $birthdate = $dev_data['application']['representative_birthdate'] ?? '';
                                                    if ($birthdate) {
                                                        echo date('d/m/Y', strtotime($birthdate));
                                                    } else {
                                                        echo 'Non renseignée';
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <div class="sisme-admin-detail-item">
                                                <strong class="sisme-admin-detail-label">Adresse:</strong><br>
                                                <span><?php echo esc_html($dev_data['application']['representative_address'] ?? 'Non renseignée'); ?></span>
                                            </div>
                                            
                                            <div class="sisme-admin-detail-item">
                                                <strong class="sisme-admin-detail-label">Ville / Pays:</strong><br>
                                                <span>
                                                    <?php 
                                                    $city = $dev_data['application']['representative_city'] ?? '';
                                                    $country = $dev_data['application']['representative_country'] ?? '';
                                                    if ($city || $country) {
                                                        echo esc_html(trim($city . ', ' . $country, ', '));
                                                    } else {
                                                        echo 'Non renseignés';
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <?php if (!empty($dev_data['application']['admin_notes'])): ?>
                                                <div class="sisme-admin-notes-box">
                                                    <strong class="sisme-admin-notes-label">📝 Notes admin:</strong><br>
                                                    <span class="sisme-admin-notes-text"><?php echo esc_html($dev_data['application']['admin_notes']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Affiche le JavaScript pour la page
     */
    public static function render_js() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Configuration pour les détails de soumission
            if (typeof SismeSubmissionDetails !== 'undefined') {
                const originalExpandDetails = SismeSubmissionDetails.expandDetails;
                SismeSubmissionDetails.expandDetails = function(submissionId, $button) {
                    const adminUserId = $button.data('admin-user-id');
                    const adminToken = $button.data('admin-token');
                    
                    if (adminUserId && adminToken) {
                        const $item = $button.closest('.sisme-submission-item');
                        const $meta = $item.find('.sisme-submission-meta'); 
                        this.setButtonLoading($button, true);
                        
                        $.ajax({
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
                                this.setButtonLoading($button, false);
                                if (response.success && response.data) {
                                    this.cache[submissionId] = response.data;
                                    this.renderDetails($meta, response.data);
                                    this.animateExpand($meta, $button);
                                } else {
                                    this.showError($meta, response.data?.message || 'Erreur lors du chargement');
                                }
                            },
                            error: () => {
                                this.setButtonLoading($button, false);
                                this.showError($meta, 'Erreur de connexion');
                            }
                        });
                    } else {
                        originalExpandDetails.call(this, submissionId, $button);
                    }
                };
                
                SismeSubmissionDetails.init();
            }
            
            // Fonction pour toggle les détails des développeurs
            window.toggleDevDetails = function(index) {
                const detailsRow = document.getElementById('details-' + index);
                const toggleIcon = document.getElementById('toggle-' + index);
                
                if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
                    detailsRow.style.display = 'table-row';
                    toggleIcon.textContent = '🔽';
                } else {
                    detailsRow.style.display = 'none';
                    toggleIcon.textContent = '▶️';
                }
            };
            
            // Fonctionnalité de recherche pour développeurs
            const developersSearchInput = document.getElementById('developers-search');
            if (developersSearchInput) {
                developersSearchInput.addEventListener('input', function() {
                    sismeFilterDevelopers(this.value);
                });
            }
            
            // Fonction pour filtrer les développeurs
            window.sismeFilterDevelopers = function(searchTerm) {
                const developerRows = document.querySelectorAll('.sisme-dev-row');
                const detailRows = document.querySelectorAll('.sisme-dev-details');
                const lowerSearchTerm = searchTerm.toLowerCase().trim();
                
                if (lowerSearchTerm === '') {
                    // Afficher tous les développeurs si la recherche est vide
                    developerRows.forEach(function(row) {
                        row.style.display = 'table-row';
                    });
                    detailRows.forEach(function(row) {
                        row.style.display = 'none'; // Masquer les détails lors du reset
                        // Reset des icônes
                        const toggleIcon = row.previousElementSibling.querySelector('.sisme-toggle-icon');
                        if (toggleIcon) {
                            toggleIcon.textContent = '▶️';
                        }
                    });
                    return;
                }
                
                developerRows.forEach(function(row, index) {
                    const name = row.getAttribute('data-developer-name') || '';
                    const studio = row.getAttribute('data-studio-name') || '';
                    
                    const isVisible = name.includes(lowerSearchTerm) || 
                                     studio.includes(lowerSearchTerm);
                    
                    row.style.display = isVisible ? 'table-row' : 'none';
                    
                    // Masquer aussi les détails correspondants
                    const detailRow = document.getElementById('details-' + index);
                    if (detailRow) {
                        if (!isVisible) {
                            detailRow.style.display = 'none';
                            const toggleIcon = row.querySelector('.sisme-toggle-icon');
                            if (toggleIcon) {
                                toggleIcon.textContent = '▶️';
                            }
                        }
                    }
                });
            };
            
            // Fonction pour effacer la recherche
            window.sismesClearDevelopersSearch = function() {
                const searchInput = document.getElementById('developers-search');
                if (searchInput) {
                    searchInput.value = '';
                    sismeFilterDevelopers('');
                }
            };
        });
        </script>
        <?php
    }
}
