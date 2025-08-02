<?php
/**
 * File: /sisme-games-editor/admin/pages/developers.php
 * Page admin pour gérer les développeurs avec onglets
 * 
 * MODIFICATION: Ajout système d'onglets
 * - Onglet 1: "Les Devs" (gestion existante)
 * - Onglet 2: "Leurs Jeux" (soumissions de jeux)
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php')) {
    require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
}


$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'developers';

if (isset($_POST['action']) && isset($_POST['user_id']) && wp_verify_nonce($_POST['_wpnonce'], 'sisme_developer_action')) {
    $user_id = intval($_POST['user_id']);
    $action = sanitize_text_field($_POST['action']);
    $admin_notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');
    
    if ($action === 'approve') {
        if (Sisme_Utils_Developer_Roles::approve_application($user_id, $admin_notes)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>✅ Candidature approuvée ! L\'utilisateur est maintenant développeur.</p>';
                echo '</div>';
            });
        }
    } elseif ($action === 'reject') {
        if (Sisme_Utils_Developer_Roles::reject_application($user_id, $admin_notes)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>❌ Candidature rejetée.</p>';
                echo '</div>';
            });
        }
    } elseif ($action === 'revoke') {
        if (Sisme_Utils_Developer_Roles::revoke_developer($user_id, $admin_notes)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p>🔄 Statut développeur révoqué. L\'utilisateur est redevenu subscriber.</p>';
                echo '</div>';
            });
        }
    } elseif ($action === 'reactivate') {
        if (Sisme_Utils_Developer_Roles::reactivate_developer($user_id, $admin_notes)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>✅ Développeur réactivé avec succès !</p>';
                echo '</div>';
            });
        }
    }
}

if (isset($_POST['submission_action']) && isset($_POST['submission_id']) && wp_verify_nonce($_POST['_wpnonce'], 'sisme_submission_action')) {
    $submission_id = intval($_POST['submission_id']);
    $action = sanitize_text_field($_POST['submission_action']);
    $admin_notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');
    
    if ($action === 'approve_submission') {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>✅ Soumission approuvée ! Le jeu sera publié.</p>';
            echo '</div>';
        });
    } elseif ($action === 'reject_submission') {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>❌ Soumission rejetée.</p>';
            echo '</div>';
        });
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'delete_submission' && wp_verify_nonce($_POST['_wpnonce'], 'admin_submission_delete')) {
    $submission_id = sanitize_text_field($_POST['submission_id'] ?? '');
    $user_id = intval($_POST['user_id'] ?? 0);
    if (!empty($submission_id) && $user_id) {
        Sisme_Utils_Media_Cleanup::cleanup_submission_media($user_id . '_' . $submission_id);
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
        }
        $result = Sisme_Game_Submission_Data_Manager::delete_submission($user_id, $submission_id);
        if (!is_wp_error($result)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success"><p>✅ Soumission et médias supprimés !</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="notice notice-error"><p>❌ ' . $result->get_error_message() . '</p></div>';
            });
        }
    }
}

$page = new Sisme_Admin_Page_Wrapper(
    'Gestion des Développeurs',
    'Gérer les développeurs et leurs soumissions de jeux',
    'game',
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour au Dashboard'
);

/**
 * Helper pour afficher une valeur ou "N.C" si vide
 * @param mixed $value - Valeur à vérifier
 * @param string $default - Texte par défaut (N.C)
 * @return string
 */
function sisme_display_value_or_nc($value, $default = 'N.C') {
    if (is_array($value)) {
        return !empty($value) ? implode(', ', array_map('esc_html', $value)) : $default;
    }
    
    return !empty($value) && trim($value) !== '' ? esc_html($value) : $default;
}

/**
 * Enqueue les styles et scripts pour la page de soumission
 */
function sisme_enqueue_submission_assets() {
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
    wp_localize_script('sisme-game-submission-details', 'sismeAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sisme_developer_nonce'),
        'isAdmin' => true
    ]);
    if (!class_exists('Sisme_Game_Submission_Renderer')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-renderer.php';
    }
}
sisme_enqueue_submission_assets();

$page->render_start();
?>

<style>
/* Background unique moyen foncé */
.sisme-admin-tabs,
.sisme-tab-content {
    background: #4a4a4a;
}

/* Onglets avec foncissement rgba */
.sisme-admin-tabs {
    margin: 20px 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.3);
}

.sisme-admin-tabs .nav-tab-wrapper {
    border: none;
    background: transparent;
}

.sisme-admin-tabs .nav-tab {
    margin-bottom: -1px;
    border-bottom: 1px solid transparent;
    background: rgba(0, 0, 0, 0.3);
    color: white;
    border: 1px solid rgba(0, 0, 0, 0.3);
    border-bottom: none;
}

.sisme-admin-tabs .nav-tab:hover {
    background: rgba(0, 0, 0, 0.4);
    color: white;
}

.sisme-admin-tabs .nav-tab-active {
    border-bottom-color: transparent;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    border-color: rgba(0, 0, 0, 0.3);
}

/* Contenu avec foncissement */
.sisme-tab-content {
    color: white;
    padding: 20px;
    border: 1px solid rgba(0, 0, 0, 0.3);
    border-top: none;
    margin-top: -1px;
}

/* Statistiques avec fond foncé mais texte noir */
.sisme-tab-content h3 {
    color: white;
}

.sisme-tab-content > div[style*="display: flex"] > div {
    background: rgba(0, 0, 0, 0.3) !important;
    color: black !important;
}

/* Tableaux avec foncissement */
.sisme-tab-content .wp-list-table {
    background: rgba(0, 0, 0, 0.3);
    color: white;
    border: 1px solid rgba(0, 0, 0, 0.3);
}

.sisme-tab-content .wp-list-table th {
    background: rgba(0, 0, 0, 0.4);
    color: white;
    border-bottom: 1px solid rgba(0, 0, 0, 0.3);
}

.sisme-tab-content .wp-list-table td {
    border-bottom: 1px solid rgba(0, 0, 0, 0.3);
    background: rgba(0, 0, 0, 0.3);
    color: white;
}

.sisme-tab-content .wp-list-table .striped > tbody > :nth-child(odd) {
    background: rgba(0, 0, 0, 0.4);
}

/* Labels de statut - texte noir */
.sisme-tab-content .wp-list-table span[style*="padding: 4px 8px"] {
    color: black !important;
}

/* Boutons avec foncissement */
.sisme-tab-content .button-primary {
    background: rgba(0, 0, 0, 0.3);
    border-color: rgba(0, 0, 0, 0.3);
    color: white;
}

.sisme-tab-content .button-primary:hover {
    background: rgba(0, 0, 0, 0.4);
    border-color: rgba(0, 0, 0, 0.4);
}

.sisme-tab-content .button-secondary {
    background: rgba(0, 0, 0, 0.3);
    border-color: rgba(0, 0, 0, 0.3);
    color: white;
}

.sisme-tab-content .button-secondary:hover {
    background: rgba(0, 0, 0, 0.4);
    border-color: rgba(0, 0, 0, 0.4);
}

.sisme-tab-content .button-link-delete {
    color: #ff6b6b;
}

.sisme-tab-content .button-link-delete:hover {
    color: #ff5252;
}

.sisme-tab-content .button {
    background: rgba(0, 0, 0, 0.3);
    color: white;
    border-color: rgba(0, 0, 0, 0.3);
}

.sisme-tab-content .button:hover {
    background: rgba(0, 0, 0, 0.4);
}

/* Message d'info avec foncissement */
.sisme-tab-content > div[style*="text-align: center"] {
    background: rgba(0, 0, 0, 0.3) !important;
    color: white !important;
}
</style>

<!-- Système d'onglets -->
<div class="sisme-admin-tabs">
    <h2 class="nav-tab-wrapper">
        <a href="<?php echo admin_url('admin.php?page=sisme-games-developers&tab=developers'); ?>" 
           class="nav-tab <?php echo ($current_tab === 'developers') ? 'nav-tab-active' : ''; ?>">
            👥 Les Devs
        </a>
        <a href="<?php echo admin_url('admin.php?page=sisme-games-developers&tab=submissions'); ?>" 
           class="nav-tab <?php echo ($current_tab === 'submissions') ? 'nav-tab-active' : ''; ?>">
            🎮 Leurs Jeux
        </a>
    </h2>
</div>

<div class="sisme-tab-content">
    <?php if ($current_tab === 'developers'): ?>
        
        <!-- ONGLET 1: GESTION DES DÉVELOPPEURS (Code existant) -->
        <h3>📊 Statistiques</h3>
        <?php
        // Statistiques développeurs
        global $wpdb;
        $stats = [
            'pending' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
                Sisme_Utils_Users::META_DEVELOPER_STATUS, 'pending'
            )),
            'approved' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
                Sisme_Utils_Users::META_DEVELOPER_STATUS, 'approved'
            )),
            'rejected' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
                Sisme_Utils_Users::META_DEVELOPER_STATUS, 'rejected'
            ))
        ];
        ?>
        
        <div style="display: flex; gap: 20px; margin-bottom: 30px;">
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
                <strong>⏳ En attente:</strong> <?php echo $stats['pending']; ?>
            </div>
            <div style="background: #d1edff; padding: 15px; border-radius: 5px; border-left: 4px solid #0073aa;">
                <strong>✅ Approuvés:</strong> <?php echo $stats['approved']; ?>
            </div>
            <div style="background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;">
                <strong>❌ Rejetés:</strong> <?php echo $stats['rejected']; ?>
            </div>
        </div>

        <h3>👥 Liste des Développeurs</h3>
        
        <?php
        // Récupérer tous les développeurs (code existant)
        $all_developers = [];
        $user_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$wpdb->usermeta} 
             WHERE meta_key = %s",
            Sisme_Utils_Users::META_DEVELOPER_STATUS
        ));

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

        usort($all_developers, function($a, $b) {
            $order = ['pending' => 1, 'approved' => 2, 'rejected' => 3, 'none' => 4];
            return ($order[$a['status']] ?? 5) <=> ($order[$b['status']] ?? 5);
        });

        if (empty($all_developers)):
        ?>
            <div style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 5px;">
                <p>Aucun développeur trouvé.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
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
                        <tr class="sisme-dev-row" onclick="toggleDevDetails(<?php echo $index; ?>)" style="cursor: pointer;">
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
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
                                    'pending' => '<span style="color: #856404; background: #fff3cd; padding: 4px 8px; border-radius: 3px;">⏳ En attente</span>',
                                    'approved' => '<span style="color: #0c5460; background: #d1ecf1; padding: 4px 8px; border-radius: 3px;">✅ Approuvé</span>',
                                    'rejected' => '<span style="color: #721c24; background: #f8d7da; padding: 4px 8px; border-radius: 3px;">❌ Rejeté</span>',
                                    'revoked' => '<span style="color: #721c24; background: #f8d7da; padding: 4px 8px; border-radius: 3px;">❌ Révoqué</span>',
                                    'none' => '<span style="color: #6c757d; background: #e9ecef; padding: 4px 8px; border-radius: 3px;">Aucun</span>'
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
                            <td onclick="event.stopPropagation();">
                                <?php if ($dev_data['status'] === 'pending'): ?>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('sisme_developer_action'); ?>
                                        <input type="hidden" name="user_id" value="<?php echo $dev_data['user_info']['ID']; ?>">
                                        <button type="submit" name="action" value="approve" 
                                                class="button button-primary button-small"
                                                onclick="return confirm('Approuver cette candidature ?')">
                                            ✅ Approuver
                                        </button>
                                        <button type="submit" name="action" value="reject" 
                                                class="button button-secondary button-small"
                                                onclick="return confirm('Rejeter cette candidature ?')">
                                            ❌ Rejeter
                                        </button>
                                    </form>
                                <?php elseif ($dev_data['status'] === 'approved'): ?>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('sisme_developer_action'); ?>
                                        <input type="hidden" name="user_id" value="<?php echo $dev_data['user_info']['ID']; ?>">
                                        <button type="submit" name="action" value="revoke" 
                                                class="button button-link-delete button-small"
                                                onclick="return confirm('Révoquer le statut développeur ?')">
                                            🔄 Révoquer
                                        </button>
                                    </form>
                                <?php elseif ($dev_data['status'] === 'revoked'): ?>
                                    <form method="post" style="display: inline;">
                                        <?php wp_nonce_field('sisme_developer_action'); ?>
                                        <input type="hidden" name="user_id" value="<?php echo $dev_data['user_info']['ID']; ?>">
                                        <button type="submit" name="action" value="reactivate" 
                                                class="button button-primary button-small"
                                                onclick="return confirm('Réactiver ce développeur ?')">
                                            ✅ Réactiver
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #6c757d;">Aucune action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        
                        <!-- Ligne de détails cachée -->
                        <tr class="sisme-dev-details" id="details-<?php echo $index; ?>" style="display: none;">
                            <td colspan="5" style="background: rgba(0, 0, 0, 0.5); padding: 20px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                    
                                    <!-- Colonne 1: Informations Studio -->
                                    <div>
                                        <h4 style="color: white; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 10px;">
                                            🏢 Informations Studio
                                        </h4>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <strong style="color: #bbb;">Nom du studio:</strong><br>
                                            <span><?php echo esc_html($dev_data['application']['studio_name'] ?? 'Non renseigné'); ?></span>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <strong style="color: #bbb;">Description:</strong><br>
                                            <span><?php echo esc_html(wp_trim_words($dev_data['application']['studio_description'] ?? 'Non renseignée', 20)); ?></span>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <strong style="color: #bbb;">Site web:</strong><br>
                                            <?php if (!empty($dev_data['application']['studio_website'])): ?>
                                                <a href="<?php echo esc_url($dev_data['application']['studio_website']); ?>" 
                                                   target="_blank" style="color: #66ccff;">
                                                    <?php echo esc_html($dev_data['application']['studio_website']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span>Non renseigné</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($dev_data['application']['studio_social_links'])): ?>
                                            <div style="margin-bottom: 15px;">
                                                <strong style="color: #bbb;">Réseaux sociaux:</strong><br>
                                                <?php foreach ($dev_data['application']['studio_social_links'] as $platform => $url): ?>
                                                    <?php if (!empty($url)): ?>
                                                        <div style="margin: 5px 0;">
                                                            <strong><?php echo ucfirst($platform); ?>:</strong> 
                                                            <a href="<?php echo esc_url($url); ?>" target="_blank" style="color: #66ccff;">
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
                                        <h4 style="color: white; margin-top: 0; border-bottom: 1px solid rgba(255,255,255,0.3); padding-bottom: 10px;">
                                            👤 Représentant Légal
                                        </h4>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <strong style="color: #bbb;">Nom complet:</strong><br>
                                            <span>
                                                <?php 
                                                $firstname = $dev_data['application']['representative_firstname'] ?? '';
                                                $lastname = $dev_data['application']['representative_lastname'] ?? '';
                                                echo esc_html(trim($firstname . ' ' . $lastname)) ?: 'Non renseigné';
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <strong style="color: #bbb;">Email:</strong><br>
                                            <span><?php echo esc_html($dev_data['application']['representative_email'] ?? 'Non renseigné'); ?></span>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <strong style="color: #bbb;">Téléphone:</strong><br>
                                            <span><?php echo esc_html($dev_data['application']['representative_phone'] ?? 'Non renseigné'); ?></span>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <strong style="color: #bbb;">Date de naissance:</strong><br>
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
                                        
                                        <div style="margin-bottom: 15px;">
                                            <strong style="color: #bbb;">Adresse:</strong><br>
                                            <span><?php echo esc_html($dev_data['application']['representative_address'] ?? 'Non renseignée'); ?></span>
                                        </div>
                                        
                                        <div style="margin-bottom: 15px;">
                                            <strong style="color: #bbb;">Ville / Pays:</strong><br>
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
                                            <div style="margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.3); border-radius: 5px;">
                                                <strong style="color: #ffeb3b;">📝 Notes admin:</strong><br>
                                                <span style="font-style: italic;"><?php echo esc_html($dev_data['application']['admin_notes']); ?></span>
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
        <?php elseif ($current_tab === 'submissions'): ?>  
        <?php
        if (!class_exists('Sisme_Admin_Submission_Tab')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'admin/components/admin-submission-tab.php';
        }
        echo Sisme_Admin_Submission_Tab::render();
        ?>    
    <?php endif; ?>
</div>
<?php $page->render_end(); ?>

<script>
jQuery(document).ready(function($) {
    if (typeof SismeSubmissionDetails !== 'undefined') {
        const originalExpandDetails = SismeSubmissionDetails.expandDetails;
        SismeSubmissionDetails.expandDetails = function(submissionId, $button) {
            const adminUserId = $button.data('admin-user-id');
            const adminToken = $button.data('admin-token');
            // Si on a des données admin, utiliser l'endpoint sécurisé
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
        console.log('🔒 Module admin sécurisé initialisé');
    }
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
});
</script>