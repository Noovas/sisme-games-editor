<?php
/**
 * File: /sisme-games-editor/admin/pages/developers.php
 * Page admin pour gérer les développeurs
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';

// Traitement des actions
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
    }
}

$page = new Sisme_Admin_Page_Wrapper(
    'Gestion des Développeurs',
    'Liste de tous les développeurs avec leurs données',
    'game',
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour au Dashboard'
);

$page->render_start();

// Récupérer tous les développeurs
$all_developers = [];

// Récupérer tous les utilisateurs avec des données développeur
global $wpdb;
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
        $all_developers[$user_id] = $dev_data;
    }
}

?>

<div class="wrap">
    <h2>Liste des Développeurs (<?php echo count($all_developers); ?>)</h2>
    
    <?php if (empty($all_developers)): ?>
        <div class="notice notice-info">
            <p>Aucun développeur trouvé dans la base de données.</p>
        </div>
    <?php else: ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Statut</th>
                    <th>Studio</th>
                    <th>Date inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_developers as $user_id => $dev_data): ?>
                    <tr>
                        <td><?php echo esc_html($user_id); ?></td>
                        <td><strong><?php echo esc_html($dev_data['user_info']['display_name']); ?></strong></td>
                        <td><?php echo esc_html($dev_data['user_info']['user_email']); ?></td>
                        <td>
                            <span class="sisme-dev-status sisme-dev-status-<?php echo esc_attr($dev_data['status']); ?>">
                                <?php echo strtoupper($dev_data['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            if ($dev_data['application'] && !empty($dev_data['application'][Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME])) {
                                echo esc_html($dev_data['application'][Sisme_Utils_Users::APPLICATION_FIELD_STUDIO_NAME]);
                            } else {
                                echo '<em>Pas de studio</em>';
                            }
                            ?>
                        </td>
                        <td><?php echo esc_html($dev_data['user_info']['user_registered']); ?></td>
                        <td>
                            <?php if ($dev_data['status'] === 'pending'): ?>
                                <div class="sisme-dev-actions">
                                    <button type="button" class="button button-primary sisme-approve-btn" 
                                            data-user-id="<?php echo esc_attr($user_id); ?>"
                                            data-user-name="<?php echo esc_attr($dev_data['user_info']['display_name']); ?>">
                                        ✅ Approuver
                                    </button>
                                    <button type="button" class="button button-secondary sisme-reject-btn"
                                            data-user-id="<?php echo esc_attr($user_id); ?>"
                                            data-user-name="<?php echo esc_attr($dev_data['user_info']['display_name']); ?>">
                                        ❌ Rejeter
                                    </button>
                                </div>
                            <?php elseif ($dev_data['status'] === 'approved'): ?>
                                <span class="sisme-dev-role-badge">🎮 Développeur actif</span>
                            <?php else: ?>
                                <em>Aucune action</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <details class="sisme-dev-data-details">
                                <summary class="sisme-dev-data-summary">Voir les données</summary>
                                <pre class="sisme-dev-data-dump">
<?php echo esc_html(print_r($dev_data, true)); ?>
                                </pre>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h3>Statistiques</h3>
        <div class="sisme-dev-stats">
            <?php
            $stats = [
                'none' => 0,
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0
            ];
            
            foreach ($all_developers as $dev_data) {
                $status = $dev_data['status'];
                if (isset($stats[$status])) {
                    $stats[$status]++;
                }
            }
            ?>
            
            <div class="sisme-dev-stat-card sisme-dev-stat-none">
                <h4>Aucun statut</h4>
                <p class="sisme-dev-stat-number"><?php echo $stats['none']; ?></p>
            </div>
            
            <div class="sisme-dev-stat-card sisme-dev-stat-pending">
                <h4>En attente</h4>
                <p class="sisme-dev-stat-number"><?php echo $stats['pending']; ?></p>
            </div>
            
            <div class="sisme-dev-stat-card sisme-dev-stat-approved">
                <h4>Approuvés</h4>
                <p class="sisme-dev-stat-number"><?php echo $stats['approved']; ?></p>
            </div>
            
            <div class="sisme-dev-stat-card sisme-dev-stat-rejected">
                <h4>Rejetés</h4>
                <p class="sisme-dev-stat-number"><?php echo $stats['rejected']; ?></p>
            </div>
        </div>
        
    <?php endif; ?>
    
    <!-- Modal de confirmation -->
    <div id="sisme-dev-action-modal" class="sisme-modal" style="display: none;">
        <div class="sisme-modal-content">
            <div class="sisme-modal-header">
                <h3 id="modal-title">Confirmer l'action</h3>
                <span class="sisme-modal-close">&times;</span>
            </div>
            <div class="sisme-modal-body">
                <p id="modal-message"></p>
                <form id="developer-action-form" method="post">
                    <?php wp_nonce_field('sisme_developer_action'); ?>
                    <input type="hidden" id="action-type" name="action" value="">
                    <input type="hidden" id="action-user-id" name="user_id" value="">
                    
                    <div class="sisme-form-field">
                        <label for="admin_notes">Notes administrateur (optionnel) :</label>
                        <textarea id="admin_notes" name="admin_notes" rows="3" 
                                  placeholder="Commentaires pour l'utilisateur..."></textarea>
                    </div>
                </form>
            </div>
            <div class="sisme-modal-footer">
                <button type="button" class="button" id="modal-cancel">Annuler</button>
                <button type="submit" form="developer-action-form" class="button button-primary" id="modal-confirm">Confirmer</button>
            </div>
        </div>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Gérer les clics sur les boutons d'action
    $('.sisme-approve-btn').on('click', function() {
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');
        
        $('#modal-title').text('Approuver la candidature');
        $('#modal-message').html(`Êtes-vous sûr de vouloir approuver la candidature de <strong>${userName}</strong> ?<br>L'utilisateur obtiendra le rôle "Développeur Sisme" et pourra soumettre des jeux.`);
        $('#action-type').val('approve');
        $('#action-user-id').val(userId);
        $('#modal-confirm').removeClass('button-secondary').addClass('button-primary').text('✅ Approuver');
        
        $('#sisme-dev-action-modal').show();
    });
    
    $('.sisme-reject-btn').on('click', function() {
        const userId = $(this).data('user-id');
        const userName = $(this).data('user-name');
        
        $('#modal-title').text('Rejeter la candidature');
        $('#modal-message').html(`Êtes-vous sûr de vouloir rejeter la candidature de <strong>${userName}</strong> ?<br>L'utilisateur pourra candidater à nouveau plus tard.`);
        $('#action-type').val('reject');
        $('#action-user-id').val(userId);
        $('#modal-confirm').removeClass('button-primary').addClass('button-secondary').text('❌ Rejeter');
        
        $('#sisme-dev-action-modal').show();
    });
    
    // Fermer le modal
    $('.sisme-modal-close, #modal-cancel').on('click', function() {
        $('#sisme-dev-action-modal').hide();
        $('#admin_notes').val('');
    });
    
    // Fermer le modal en cliquant à l'extérieur
    $(window).on('click', function(event) {
        if (event.target.id === 'sisme-dev-action-modal') {
            $('#sisme-dev-action-modal').hide();
            $('#admin_notes').val('');
        }
    });
});
</script>

<style>
.sisme-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

#modal-title, #modal-message {
    color: #000;
}

.sisme-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 80%;
    max-width: 500px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.sisme-modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    position: relative;
}

.sisme-modal-header h3 {
    margin: 0;
}

.sisme-modal-close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.sisme-modal-close:hover {
    color: #000;
}

.sisme-modal-body {
    padding: 20px;
}

.sisme-modal-footer {
    padding: 20px;
    border-top: 1px solid #ddd;
    text-align: right;
}

.sisme-modal-footer button {
    margin-left: 10px;
}

.sisme-dev-actions {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.sisme-dev-actions button {
    font-size: 12px;
    padding: 4px 8px;
}

.sisme-dev-role-badge {
    display: inline-block;
    background: #d4edda;
    color: #155724;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.sisme-form-field {
    margin-bottom: 15px;
}

.sisme-form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.sisme-form-field textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: vertical;
}
</style>

<?php
$page->render_end();
?>