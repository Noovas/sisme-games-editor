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

// Récupérer les stats Game Data
$stats_data = $table->get_stats();

$page->render_start();
?>

<!-- Statistiques Game Data -->
<div class="sisme-card sisme-mb-lg">
    <div class="sisme-card__header">
        <h2 class="sisme-heading-3">📊 Statistiques Game Data</h2>
    </div>
    <div class="sisme-card__body">
        <div class="sisme-grid sisme-grid-4">
            <div class="sisme-card sisme-card--secondary">
                <div class="sisme-card__body sisme-text-center">
                    <div class="sisme-text-4xl sisme-color-primary sisme-mb-sm">
                        <?php echo $stats_data['total_games']; ?>
                    </div>
                    <div class="sisme-text-sm sisme-text-muted">Jeux total</div>
                </div>
            </div>
            <div class="sisme-card sisme-card--secondary">
                <div class="sisme-card__body sisme-text-center">
                    <div class="sisme-text-4xl sisme-color-primary sisme-mb-sm">
                        <?php echo $stats_data['games_with_data']; ?>
                    </div>
                    <div class="sisme-text-sm sisme-text-muted">Avec données</div>
                </div>
            </div>
            <div class="sisme-card sisme-card--secondary">
                <div class="sisme-card__body sisme-text-center">
                    <div class="sisme-text-4xl sisme-color-primary sisme-mb-sm">
                        <?php echo $stats_data['games_with_articles']; ?>
                    </div>
                    <div class="sisme-text-sm sisme-text-muted">Avec articles</div>
                </div>
            </div>
            <div class="sisme-card sisme-card--secondary">
                <div class="sisme-card__body sisme-text-center">
                    <div class="sisme-text-4xl sisme-color-primary sisme-mb-sm">
                        <?php echo $stats_data['total_articles']; ?>
                    </div>
                    <div class="sisme-text-sm sisme-text-muted">Articles total</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tableau Game Data -->
<div class="sisme-card">
    <div class="sisme-card__header sisme-card-game-data__header">
        <h2 class="sisme-heading-3">🎮 Gestion des jeux</h2>
        <div class="sisme-card__header-actions">
            <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-game-data'); ?>" 
               class="sisme-btn sisme-btn--primary">
                ➕ Créer un jeu
            </a>
        </div>
    </div>
    <div class="sisme-card__body sisme-card__body--no-padding">
        <?php $table->render(); ?>
    </div>
</div>

<?php
$page->render_end();