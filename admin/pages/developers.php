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

// Traitement des actions sur les soumissions
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
        <?php elseif ($current_tab === 'submissions'): ?>
        
        <!-- ONGLET 2: SOUMISSIONS DE JEUX -->
        
        <?php
        // Charger le data manager si pas encore fait
        if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
            $game_submission_file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
            if (file_exists($game_submission_file)) {
                require_once $game_submission_file;
            }
        }
        
        // Récupérer toutes les soumissions via user meta
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
            
            // Trier par date de soumission/mise à jour
            usort($all_submissions, function($a, $b) {
                $date_a = $a['metadata']['submitted_at'] ?? $a['metadata']['updated_at'];
                $date_b = $b['metadata']['submitted_at'] ?? $b['metadata']['updated_at'];
                return strtotime($date_b) - strtotime($date_a);
            });
        }
        
        // Calculer les statistiques
        $submission_stats = [
            'draft' => 0,
            'pending' => 0,
            'published' => 0,
            'rejected' => 0,
            'revision' => 0
        ];
        
        foreach ($all_submissions as $submission) {
            $status = $submission['status'] ?? 'draft';
            if (isset($submission_stats[$status])) {
                $submission_stats[$status]++;
            }
        }
        ?>
        
        <h3>📊 Statistiques des Soumissions</h3>
        <div style="display: flex; gap: 20px; margin-bottom: 30px;">
            <div style="background: #e2e3e5; padding: 15px; border-radius: 5px; border-left: 4px solid #6c757d;">
                <strong>📝 Brouillons:</strong> <?php echo $submission_stats['draft']; ?>
            </div>
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107;">
                <strong>⏳ En attente:</strong> <?php echo $submission_stats['pending']; ?>
            </div>
            <div style="background: #d1edff; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;">
                <strong>✅ Publiés:</strong> <?php echo $submission_stats['published']; ?>
            </div>
            <div style="background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;">
                <strong>❌ Rejetés:</strong> <?php echo $submission_stats['rejected']; ?>
            </div>
            <div style="background: #d4edda; padding: 15px; border-radius: 5px; border-left: 4px solid #155724;">
                <strong>🔄 En révision:</strong> <?php echo $submission_stats['revision']; ?>
            </div>
        </div>
        
        <h3>🎮 Liste des Soumissions</h3>
        
        <?php if (empty($all_submissions)): ?>
            <div style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 5px;">
                <p>🎮 Aucune soumission de jeu pour le moment.</p>
                <p><small>Les soumissions apparaîtront ici quand les développeurs commenceront à soumettre leurs jeux.</small></p>
            </div>
        <?php else: ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 25%;">Jeu</th>
                        <th style="width: 20%;">Développeur</th>
                        <th style="width: 15%;">Studio</th>
                        <th style="width: 12%;">Statut</th>
                        <th style="width: 13%;">Progression</th>
                        <th style="width: 15%;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_submissions as $submission): ?>
                        <?php
                        $game_data = $submission['game_data'] ?? [];
                        $metadata = $submission['metadata'] ?? [];
                        $user_data = $submission['user_data'] ?? [];
                        
                        $game_name = $game_data[Sisme_Utils_Users::GAME_FIELD_NAME] ?? 'Jeu sans nom';
                        $studio_name = $game_data[Sisme_Utils_Users::GAME_FIELD_STUDIO_NAME] ?? 'Studio inconnu';
                        $status = $submission['status'] ?? 'draft';
                        $completion = $metadata['completion_percentage'] ?? 0;
                        $display_date = $metadata['submitted_at'] ?? $metadata['updated_at'] ?? 'Inconnue';
                        
                        // Formatage de la date
                        if ($display_date !== 'Inconnue') {
                            $display_date = date('d/m/Y H:i', strtotime($display_date));
                        }
                        
                        // Badge de statut
                        $status_config = [
                            'draft' => ['class' => 'draft', 'text' => '📝 Brouillon'],
                            'pending' => ['class' => 'pending', 'text' => '⏳ En attente'],
                            'published' => ['class' => 'published', 'text' => '✅ Publié'],
                            'rejected' => ['class' => 'rejected', 'text' => '❌ Rejeté'],
                            'revision' => ['class' => 'revision', 'text' => '🔄 Révision']
                        ];
                        
                        $status_info = $status_config[$status] ?? ['class' => 'draft', 'text' => $status];
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($game_name); ?></strong>
                                <div style="font-size: 12px; color: #666;">
                                    ID: <?php echo esc_html($submission['id'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo esc_html($user_data['display_name'] ?? 'Inconnu'); ?>
                                <div style="font-size: 12px; color: #666;">
                                    ID: <?php echo esc_html($user_data['user_id'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td>
                                <?php echo esc_html($studio_name); ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($status_info['class']); ?>" 
                                    style="padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold;">
                                    <?php echo $status_info['text']; ?>
                                </span>
                            </td>
                            <td>
                                <div style="background: #f1f1f1; border-radius: 3px; height: 20px; position: relative;">
                                    <div style="background: #007cba; height: 100%; width: <?php echo $completion; ?>%; border-radius: 3px;"></div>
                                    <span style="position: absolute; top: 0; left: 0; right: 0; text-align: center; font-size: 11px; line-height: 20px; color: #333;">
                                        <?php echo $completion; ?>%
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php echo esc_html($display_date); ?>
                            </td>
                        </tr>
                        
                        <!-- Ligne de détails (accordéon) -->
                        <tr class="submission-details" style="display: none;" id="details-<?php echo esc_attr($submission['id']); ?>">
                            <td colspan="6" style="background: #f9f9f9; padding: 20px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                    
                                    <!-- Informations principales -->
                                    <div>
                                        <h4>📋 Informations du jeu</h4>
                                        <p><strong>Description:</strong><br>
                                        <?php echo esc_html(substr($game_data[Sisme_Utils_Users::GAME_FIELD_DESCRIPTION] ?? 'Pas de description', 0, 200) . '...'); ?></p>
                                        
                                        <p><strong>Date de sortie:</strong> 
                                        <?php echo esc_html($game_data[Sisme_Utils_Users::GAME_FIELD_RELEASE_DATE] ?? 'Non définie'); ?></p>
                                        
                                        <p><strong>Trailer:</strong> 
                                        <?php if (!empty($game_data[Sisme_Utils_Users::GAME_FIELD_TRAILER])): ?>
                                            <a href="<?php echo esc_url($game_data[Sisme_Utils_Users::GAME_FIELD_TRAILER]); ?>" target="_blank">Voir le trailer</a>
                                        <?php else: echo 'Pas de trailer'; endif; ?></p>
                                        
                                        <p><strong>Éditeur:</strong> 
                                        <?php echo esc_html($game_data[Sisme_Utils_Users::GAME_FIELD_PUBLISHER_NAME] ?? 'Non défini'); ?></p>
                                    </div>
                                    
                                    <!-- Métadonnées système -->
                                    <div>
                                        <h4>⚙️ Métadonnées système</h4>
                                        <p><strong>Créé le:</strong> <?php echo esc_html($metadata['created_at'] ?? 'Inconnue'); ?></p>
                                        <p><strong>Modifié le:</strong> <?php echo esc_html($metadata['updated_at'] ?? 'Inconnue'); ?></p>
                                        <p><strong>Soumis le:</strong> <?php echo esc_html($metadata['submitted_at'] ?? 'Pas encore soumis'); ?></p>
                                        <p><strong>Tentatives:</strong> <?php echo esc_html($metadata['retry_count'] ?? 0); ?></p>
                                        
                                        <?php if (!empty($submission['admin_data']['admin_notes'])): ?>
                                            <h4>📝 Notes admin</h4>
                                            <p style="background: #fff; padding: 10px; border-left: 4px solid #007cba;">
                                                <?php echo esc_html($submission['admin_data']['admin_notes']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Actions admin -->
                                <div style="margin-top: 20px; text-align: right;">
                                    <button class="button" onclick="toggleDetails('<?php echo esc_js($submission['id']); ?>')">Masquer les détails</button>
                                    
                                    <?php if ($status === 'pending'): ?>
                                        <button class="button button-primary" style="margin-left: 10px;">✅ Approuver</button>
                                        <button class="button" style="margin-left: 10px;">❌ Rejeter</button>
                                    <?php elseif ($status === 'draft'): ?>
                                        <button class="button button-secondary" style="margin-left: 10px;">🗑️ Supprimer le brouillon</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <script>
            function toggleDetails(submissionId) {
                const detailsRow = document.getElementById('details-' + submissionId);
                if (detailsRow.style.display === 'none') {
                    detailsRow.style.display = 'table-row';
                } else {
                    detailsRow.style.display = 'none';
                }
            }
            
            // Ajouter un clic sur les lignes pour afficher les détails
            document.addEventListener('DOMContentLoaded', function() {
                const rows = document.querySelectorAll('.wp-list-table tbody tr:not(.submission-details)');
                rows.forEach(function(row, index) {
                    row.style.cursor = 'pointer';
                    row.addEventListener('click', function() {
                        const nextRow = row.nextElementSibling;
                        if (nextRow && nextRow.classList.contains('submission-details')) {
                            toggleDetails(nextRow.id.replace('details-', ''));
                        }
                    });
                });
            });
            </script>
            
            <style>
            .status-badge.status-draft { background: #e2e3e5; color: #6c757d; }
            .status-badge.status-pending { background: #fff3cd; color: #856404; }
            .status-badge.status-published { background: #d1edff; color: #155724; }
            .status-badge.status-rejected { background: #f8d7da; color: #721c24; }
            .status-badge.status-revision { background: #d4edda; color: #155724; }
            
            .wp-list-table tbody tr:hover:not(.submission-details) {
                background-color: #f5f5f5;
            }
            </style>
            
        <?php endif; ?>
        
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