<?php
/**
 * File: /sisme-games-editor/admin/pages/developers.php
 * Page admin pour gérer les développeurs
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';

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
                    <th>Données complètes</th>
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
    
</div>

<?php
$page->render_end();
?>