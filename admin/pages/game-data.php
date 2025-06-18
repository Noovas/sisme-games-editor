<?php
/**
 * File: /sisme-games-editor/admin/pages/game-data.php
 * Page Game Data - Gestion des données de jeux
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-table-game-data.php';

// Créer la page avec le wrapper
$page = new Sisme_Admin_Page_Wrapper(
    'Game Data',
    'Gestion des données des jeux par étiquettes',
    'database',
    admin_url('admin.php?page=sisme-games-editor'),
    'Retour au tableau de bord'
);

// Créer le module table
$table = new Sisme_Game_Data_Table_Module([
    'per_page' => -1,
    'edit_url' => admin_url('admin.php?page=sisme-games-edit-game-data')
]);

// Afficher les statistiques
$stats = $table->get_stats();

$page->render_start();
?>

<div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
    <!-- Statistiques -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
        <div style="background: #e7f3e7; padding: 15px; border-radius: 8px; text-align: center;">
            <strong style="font-size: 24px; color: #2c5e2e;"><?php echo $stats['total_games']; ?></strong>
            <div>Jeux total</div>
        </div>
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center;">
            <strong style="font-size: 24px; color: #856404;"><?php echo $stats['games_with_data']; ?></strong>
            <div>Avec données</div>
        </div>
        <div style="background: #d1ecf1; padding: 15px; border-radius: 8px; text-align: center;">
            <strong style="font-size: 24px; color: #0c5460;"><?php echo $stats['games_with_articles']; ?></strong>
            <div>Avec articles</div>
        </div>
        <div style="background: #f8d7da; padding: 15px; border-radius: 8px; text-align: center;">
            <strong style="font-size: 24px; color: #721c24;"><?php echo $stats['total_articles']; ?></strong>
            <div>Articles total</div>
        </div>
    </div>
    
    <!-- Tableau des données -->
    <?php $table->render(); ?>
</div>

<?php
$page->render_end();