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

// Chargement des classes nécessaires
if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php')) {
    require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
}
if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-workflow.php')) {
    require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-workflow.php';
}

// Récupérer l'onglet actuel
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'developers';

// Traitement des actions (existant)
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

// Traitement des actions sur les soumissions (NOUVEAU SYSTÈME)
if (isset($_POST['submission_action']) && isset($_POST['submission_id']) && wp_verify_nonce($_POST['_wpnonce'], 'sisme_submission_action')) {
    $submission_id = sanitize_text_field($_POST['submission_id']);
    $user_id = intval($_POST['user_id']);
    $action = sanitize_text_field($_POST['submission_action']);
    $admin_notes = sanitize_textarea_field($_POST['admin_notes'] ?? '');
    
    // Vérifier que la classe Workflow est disponible
    if (!class_exists('Sisme_Game_Submission_Workflow')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>❌ Le système de workflow des soumissions n\'est pas disponible.</p>';
            echo '</div>';
        });
    } else {
        
        if ($action === 'approve_submission') {
            $result = Sisme_Game_Submission_Workflow::approve_submission($submission_id, $user_id, $admin_notes);
            
            if (is_wp_error($result)) {
                add_action('admin_notices', function() use ($result) {
                    echo '<div class="notice notice-error is-dismissible">';
                    echo '<p>❌ Erreur lors de l\'approbation : ' . esc_html($result->get_error_message()) . '</p>';
                    echo '</div>';
                });
            } else {
                add_action('admin_notices', function() use ($admin_notes) {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p>✅ Soumission approuvée ! Le jeu sera publié.</p>';
                    if (!empty($admin_notes)) {
                        echo '<p><small>Notes envoyées au développeur : ' . esc_html(substr($admin_notes, 0, 100)) . '...</small></p>';
                    }
                    echo '</div>';
                });
            }
            
        } elseif ($action === 'reject_submission') {
            $result = Sisme_Game_Submission_Workflow::reject_submission($submission_id, $user_id, $admin_notes);
            
            if (is_wp_error($result)) {
                add_action('admin_notices', function() use ($result) {
                    echo '<div class="notice notice-error is-dismissible">';
                    echo '<p>❌ Erreur lors du rejet : ' . esc_html($result->get_error_message()) . '</p>';
                    echo '</div>';
                });
            } else {
                add_action('admin_notices', function() use ($admin_notes) {
                    echo '<div class="notice notice-warning is-dismissible">';
                    echo '<p>❌ Soumission rejetée.</p>';
                    if (!empty($admin_notes)) {
                        echo '<p><small>Raison communiquée au développeur : ' . esc_html(substr($admin_notes, 0, 100)) . '...</small></p>';
                    }
                    echo '</div>';
                });
            }
            
        } elseif ($action === 'delete_submission') {
            $result = Sisme_Game_Submission_Workflow::delete_submission_admin($submission_id, $user_id);
            
            if (is_wp_error($result)) {
                add_action('admin_notices', function() use ($result) {
                    echo '<div class="notice notice-error is-dismissible">';
                    echo '<p>❌ Erreur lors de la suppression : ' . esc_html($result->get_error_message()) . '</p>';
                    echo '</div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible">';
                    echo '<p>🗑️ Brouillon supprimé avec succès.</p>';
                    echo '</div>';
                });
            }
            
        } elseif ($action === 'request_revision') {
            $result = Sisme_Game_Submission_Workflow::request_revision($submission_id, $user_id, $admin_notes);
            
            if (is_wp_error($result)) {
                add_action('admin_notices', function() use ($result) {
                    echo '<div class="notice notice-error is-dismissible">';
                    echo '<p>❌ Erreur lors de la demande de révision : ' . esc_html($result->get_error_message()) . '</p>';
                    echo '</div>';
                });
            } else {
                add_action('admin_notices', function() use ($admin_notes) {
                    echo '<div class="notice notice-info is-dismissible">';
                    echo '<p>🔄 Révision demandée au développeur.</p>';
                    if (!empty($admin_notes)) {
                        echo '<p><small>Modifications demandées : ' . esc_html(substr($admin_notes, 0, 100)) . '...</small></p>';
                    }
                    echo '</div>';
                });
            }
            
        } else {
            add_action('admin_notices', function() use ($action) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p>❌ Action non reconnue : ' . esc_html($action) . '</p>';
                echo '</div>';
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

        // Trier par statut (pending en premier)
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
        
    <?php elseif ($current_tab === 'submissions' || (!isset($_GET['tab']) && $current_tab === 'base')): ?>
        <?php
        // Chargement du nouveau système
        if (file_exists(SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php')) {
            require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
        }
        
        // Vérifier que la classe est disponible
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            echo '<div class="notice notice-error"><p>❌ Le système de soumissions n\'est pas disponible.</p></div>';
        } else {
            
            // Statistiques soumissions avec le nouveau système
            $submission_stats = [
                'pending' => Sisme_Game_Submission_Data_Manager::get_all_submissions_count(Sisme_Utils_Users::GAME_STATUS_PENDING),
                'published' => Sisme_Game_Submission_Data_Manager::get_all_submissions_count(Sisme_Utils_Users::GAME_STATUS_PUBLISHED),
                'rejected' => Sisme_Game_Submission_Data_Manager::get_all_submissions_count(Sisme_Utils_Users::GAME_STATUS_REJECTED),
                'draft' => Sisme_Game_Submission_Data_Manager::get_all_submissions_count(Sisme_Utils_Users::GAME_STATUS_DRAFT),
                'revision' => Sisme_Game_Submission_Data_Manager::get_all_submissions_count(Sisme_Utils_Users::GAME_STATUS_REVISION)
            ];
            ?>
            
            <div style="display: flex; gap: 20px; margin-bottom: 30px;">
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
                    <strong>⏳ En attente:</strong> <?php echo $submission_stats['pending']; ?>
                </div>
                <div style="background: #d1edff; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;">
                    <strong>✅ Publiés:</strong> <?php echo $submission_stats['published']; ?>
                </div>
                <div style="background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;">
                    <strong>❌ Rejetés:</strong> <?php echo $submission_stats['rejected']; ?>
                </div>
                <div style="background: #e2e3e5; padding: 15px; border-radius: 5px; border-left: 4px solid #6c757d;">
                    <strong>📝 Brouillons:</strong> <?php echo $submission_stats['draft']; ?>
                </div>
                <div style="background: #fef3e2; padding: 15px; border-radius: 5px; border-left: 4px solid #fd7e14;">
                    <strong>🔄 Révisions:</strong> <?php echo $submission_stats['revision']; ?>
                </div>
            </div>
            
            <?php 
            // Récupérer toutes les soumissions avec le nouveau système
            $submissions = Sisme_Game_Submission_Data_Manager::get_all_submissions_for_admin();
            
            if (empty($submissions)): 
            ?>
                <div style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 5px;">
                    <p>🎮 Aucune soumission de jeu pour le moment.</p>
                    <p><small>Les soumissions apparaîtront ici quand les développeurs commenceront à soumettre leurs jeux.</small></p>
                </div>
            <?php else: ?>
                
                <style>
                .sisme-submission-row {
                    cursor: pointer;
                    transition: background-color 0.2s;
                }
                .sisme-submission-row:hover {
                    background-color: #f8f9fa;
                }
                .sisme-submission-details {
                    display: none;
                    background: #f1f3f4;
                    border-left: 4px solid #007cba;
                }
                .sisme-submission-details.open {
                    display: table-row;
                }
                .sisme-expand-icon {
                    transition: transform 0.2s;
                    display: inline-block;
                }
                .sisme-expand-icon.expanded {
                    transform: rotate(90deg);
                }
                .sisme-game-details {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 20px;
                    padding: 20px;
                    background: white;
                    border-radius: 5px;
                    margin: 10px;
                }
                .sisme-detail-section h4 {
                    margin: 0 0 10px 0;
                    color: #333;
                    font-size: 14px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                .sisme-detail-item {
                    margin-bottom: 8px;
                    font-size: 13px;
                }
                .sisme-detail-label {
                    font-weight: 600;
                    color: #666;
                    display: inline-block;
                    width: 120px;
                }
                .sisme-admin-actions {
                    margin-top: 15px;
                    padding-top: 15px;
                    border-top: 1px solid #ddd;
                }
                </style>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="30px"></th>
                            <th>Studio</th>
                            <th>Développeur</th>
                            <th>Nom du jeu</th>
                            <th>Date soumission</th>
                            <th>Statut</th>
                            <th>Progression</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): 
                            $game_name = $submission['game_data'][Sisme_Utils_Users::GAME_FIELD_NAME] ?? 'Nom non défini';
                            $studio_name = $submission['game_data'][Sisme_Utils_Users::GAME_FIELD_STUDIO_NAME] ?? 'Studio non défini';
                            $status = $submission['status'];
                            $completion = $submission['metadata']['completion_percentage'] ?? 0;
                            $submitted_at = $submission['metadata']['submitted_at'] ?? $submission['metadata']['updated_at'];
                            $user_data = $submission['user_data'];
                            $submission_id = $submission['id'];
                            $user_id = $user_data['user_id'];
                            
                            // Couleur du statut
                            $status_colors = [
                                Sisme_Utils_Users::GAME_STATUS_DRAFT => '#6c757d',
                                Sisme_Utils_Users::GAME_STATUS_PENDING => '#ffc107', 
                                Sisme_Utils_Users::GAME_STATUS_PUBLISHED => '#28a745',
                                Sisme_Utils_Users::GAME_STATUS_REJECTED => '#dc3545',
                                Sisme_Utils_Users::GAME_STATUS_REVISION => '#fd7e14'
                            ];
                            
                            $status_labels = [
                                Sisme_Utils_Users::GAME_STATUS_DRAFT => '📝 Brouillon',
                                Sisme_Utils_Users::GAME_STATUS_PENDING => '⏳ En attente',
                                Sisme_Utils_Users::GAME_STATUS_PUBLISHED => '✅ Publié',
                                Sisme_Utils_Users::GAME_STATUS_REJECTED => '❌ Rejeté',
                                Sisme_Utils_Users::GAME_STATUS_REVISION => '🔄 Révision'
                            ];
                            
                            $status_color = $status_colors[$status] ?? '#6c757d';
                            $status_label = $status_labels[$status] ?? ucfirst($status);
                        ?>
                            <tr class="sisme-submission-row" onclick="toggleSubmissionDetails('<?php echo $submission_id; ?>')">
                                <td>
                                    <span class="sisme-expand-icon" id="icon-<?php echo $submission_id ?>">▶</span>
                                </td>
                                <td><strong><?php echo esc_html($studio_name); ?></strong></td>
                                <td>
                                    <?php echo esc_html($user_data['display_name']); ?><br>
                                    <small style="color: #666;"><?php echo esc_html($user_data['user_email']); ?></small>
                                </td>
                                <td><strong><?php echo esc_html($game_name); ?></strong></td>
                                <td><?php echo $submitted_at ? date('d/m/Y H:i', strtotime($submitted_at)) : 'Non soumis'; ?></td>
                                <td>
                                    <span style="color: <?php echo $status_color; ?>; font-weight: bold;">
                                        <?php echo $status_label; ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="flex: 1; background: #e9ecef; border-radius: 10px; height: 8px;">
                                            <div style="background: <?php echo $completion >= 80 ? '#28a745' : ($completion >= 60 ? '#ffc107' : '#dc3545'); ?>; width: <?php echo $completion; ?>%; height: 100%; border-radius: 10px;"></div>
                                        </div>
                                        <span style="font-size: 12px; color: #666;"><?php echo $completion; ?>%</span>
                                    </div>
                                </td>
                                <td onclick="event.stopPropagation();">
                                    <?php if ($status === Sisme_Utils_Users::GAME_STATUS_PENDING): ?>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('sisme_submission_action'); ?>
                                            <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                            <input type="hidden" name="submission_action" value="approve_submission">
                                            <button type="submit" class="button button-primary button-small">✅ Approuver</button>
                                        </form>
                                        <form method="post" style="display: inline; margin-left: 5px;">
                                            <?php wp_nonce_field('sisme_submission_action'); ?>
                                            <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                            <input type="hidden" name="submission_action" value="reject_submission">
                                            <button type="submit" class="button button-secondary button-small">❌ Rejeter</button>
                                        </form>
                                    <?php elseif ($status === Sisme_Utils_Users::GAME_STATUS_DRAFT): ?>
                                        <form method="post" style="display: inline;">
                                            <?php wp_nonce_field('sisme_submission_action'); ?>
                                            <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                            <input type="hidden" name="submission_action" value="delete_submission">
                                            <button type="submit" class="button button-secondary button-small" onclick="return confirm('Supprimer définitivement ce brouillon ?')">🗑️ Supprimer</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #666; font-size: 12px;">Aucune action</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Ligne de détails déroulante -->
                            <tr class="sisme-submission-details" id="details-<?php echo $submission_id; ?>">
                                <td colspan="8">
                                    <div class="sisme-game-details">
                                        <div class="sisme-detail-section">
                                            <h4>🎮 Informations du jeu</h4>
                                            <div class="sisme-detail-item">
                                                <span class="sisme-detail-label">Nom:</span>
                                                <?php echo esc_html($submission['game_data'][Sisme_Utils_Users::GAME_FIELD_NAME] ?? 'Non défini'); ?>
                                            </div>
                                            <div class="sisme-detail-item">
                                                <span class="sisme-detail-label">Description:</span>
                                                <?php 
                                                $description = $submission['game_data'][Sisme_Utils_Users::GAME_FIELD_DESCRIPTION] ?? '';
                                                echo esc_html(substr($description, 0, 100)) . (strlen($description) > 100 ? '...' : '');
                                                ?>
                                            </div>
                                            <div class="sisme-detail-item">
                                                <span class="sisme-detail-label">Date sortie:</span>
                                                <?php echo esc_html($submission['game_data'][Sisme_Utils_Users::GAME_FIELD_RELEASE_DATE] ?? 'Non définie'); ?>
                                            </div>
                                            <div class="sisme-detail-item">
                                                <span class="sisme-detail-label">Genres:</span>
                                                <?php 
                                                $genres = $submission['game_data'][Sisme_Utils_Users::GAME_FIELD_GENRES] ?? [];
                                                echo esc_html(is_array($genres) ? implode(', ', $genres) : 'Non définis');
                                                ?>
                                            </div>
                                            <div class="sisme-detail-item">
                                                <span class="sisme-detail-label">Plateformes:</span>
                                                <?php 
                                                $platforms = $submission['game_data'][Sisme_Utils_Users::GAME_FIELD_PLATFORMS] ?? [];
                                                echo esc_html(is_array($platforms) ? implode(', ', $platforms) : 'Non définies');
                                                ?>
                                            </div>
                                        </div>
                                        
                                        <div class="sisme-detail-section">
                                            <h4>🏢 Studio & Publication</h4>
                                            <div class="sisme-detail-item">
                                                <span class="sisme-detail-label">Studio:</span>
                                                <?php echo esc_html($submission['game_data'][Sisme_Utils_Users::GAME_FIELD_STUDIO_NAME] ?? 'Non défini'); ?>
                                            </div>
                                            <div class="sisme-detail-item">
                                                <span class="sisme-detail-label">Site studio:</span>
                                                <?php 
                                                $studio_url = $submission['game_data'][Sisme_Utils_Users::GAME_FIELD_STUDIO_URL] ?? '';
                                                if ($studio_url) {
                                                    echo '<a href="' . esc_url($studio_url) . '" target="_blank">' . esc_html($studio_url) . '</a>';
                                                } else {
                                                    echo 'Non défini';
                                                }
                                                ?>
                                            </div>
                                            <div class="sisme-detail-item">
                                                <span class="sisme-detail-label">Éditeur:</span>
                                                <?php echo esc_html($submission['game_data'][Sisme_Utils_Users::GAME_FIELD_PUBLISHER_NAME] ?? 'Non défini'); ?>
                                            </div>
                                            <div class="sisme-detail-item">
                                                <span class="sisme-detail-label">Trailer:</span>
                                                <?php 
                                                $trailer = $submission['game_data'][Sisme_Utils_Users::GAME_FIELD_TRAILER] ?? '';
                                                if ($trailer) {
                                                    echo '<a href="' . esc_url($trailer) . '" target="_blank">🎬 Voir le trailer</a>';
                                                } else {
                                                    echo 'Non défini';
                                                }
                                                ?>
                                            </div>
                                            <div class="sisme-detail-item">
                                                <span class="sisme-detail-label">Créé le:</span>
                                                <?php echo date('d/m/Y H:i', strtotime($submission['metadata']['created_at'])); ?>
                                            </div>
                                            <div class="sisme-detail-item">
                                                <span class="sisme-detail-label">Mis à jour:</span>
                                                <?php echo date('d/m/Y H:i', strtotime($submission['metadata']['updated_at'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($submission['admin_data']['admin_notes'])): ?>
                                    <div style="margin: 10px; padding: 15px; background: #fff3cd; border-radius: 5px;">
                                        <h4 style="margin: 0 0 10px 0;">📝 Notes admin</h4>
                                        <p style="margin: 0;"><?php echo esc_html($submission['admin_data']['admin_notes']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="sisme-admin-actions">
                                        <?php if ($status === Sisme_Utils_Users::GAME_STATUS_PENDING): ?>
                                            <form method="post" style="display: inline;">
                                                <?php wp_nonce_field('sisme_submission_action'); ?>
                                                <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                                <input type="hidden" name="submission_action" value="approve_submission">
                                                <textarea name="admin_notes" placeholder="Notes pour le développeur (optionnel)" style="width: 300px; height: 60px; margin-right: 10px;"></textarea>
                                                <button type="submit" class="button button-primary">✅ Approuver avec notes</button>
                                            </form>
                                            <br><br>
                                            <form method="post" style="display: inline;">
                                                <?php wp_nonce_field('sisme_submission_action'); ?>
                                                <input type="hidden" name="submission_id" value="<?php echo $submission_id; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                                <input type="hidden" name="submission_action" value="reject_submission">
                                                <textarea name="admin_notes" placeholder="Raison du rejet (recommandé)" style="width: 300px; height: 60px; margin-right: 10px;"></textarea>
                                                <button type="submit" class="button button-secondary">❌ Rejeter avec notes</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <script>
                function toggleSubmissionDetails(submissionId) {
                    const detailsRow = document.getElementById('details-' + submissionId);
                    const icon = document.getElementById('icon-' + submissionId);
                    
                    if (detailsRow.classList.contains('open')) {
                        detailsRow.classList.remove('open');
                        icon.classList.remove('expanded');
                    } else {
                        // Fermer toutes les autres lignes ouvertes
                        document.querySelectorAll('.sisme-submission-details.open').forEach(row => {
                            row.classList.remove('open');
                        });
                        document.querySelectorAll('.sisme-expand-icon.expanded').forEach(ico => {
                            ico.classList.remove('expanded');
                        });
                        
                        // Ouvrir la ligne actuelle
                        detailsRow.classList.add('open');
                        icon.classList.add('expanded');
                    }
                }
                </script>
                
            <?php endif; ?>
        
        <?php } // Fin vérification classe ?>

    <?php endif; ?>
</div>

<?php $page->render_end(); ?>

<script>
function toggleDevDetails(index) {
    const detailsRow = document.getElementById('details-' + index);
    const toggleIcon = document.getElementById('toggle-' + index);
    
    if (detailsRow.style.display === 'none' || detailsRow.style.display === '') {
        detailsRow.style.display = 'table-row';
        toggleIcon.textContent = '🔽';
    } else {
        detailsRow.style.display = 'none';
        toggleIcon.textContent = '▶️';
    }
}
</script>