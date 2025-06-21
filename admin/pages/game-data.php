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

// 🗑️ Traitement de la suppression de Game Data
if (isset($_GET['action']) && $_GET['action'] === 'delete_game_data' && isset($_GET['game_id'])) {
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_game_data_' . $_GET['game_id'])) {
        $game_id = intval($_GET['game_id']);
        $tag = get_term($game_id, 'post_tag');
        
        if ($tag && !is_wp_error($tag)) {
            // Supprimer toutes les métadonnées du jeu
            $meta_keys = [
                'game_description', 'game_genres', 'game_modes', 'game_developers', 
                'game_publishers', 'game_platforms', 'release_date', 'external_links',
                'trailer_link', 'cover_main', 'cover_news', 'cover_patch', 'cover_test',
                'screenshots', 'game_sections', 'last_update'
            ];
            
            foreach ($meta_keys as $meta_key) {
                delete_term_meta($game_id, $meta_key);
            }
            
            // Optionnel : Supprimer complètement l'étiquette
            // wp_delete_term($game_id, 'post_tag');
            
            add_action('admin_notices', function() use ($tag) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>✅ Les données du jeu "' . esc_html($tag->name) . '" ont été supprimées avec succès !</p>';
                echo '</div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p>❌ Erreur : Jeu introuvable.</p>';
                echo '</div>';
            });
        }
        
        // Rediriger pour nettoyer l'URL
        wp_redirect(admin_url('admin.php?page=sisme-games-game-data'));
        exit;
    } else {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p>❌ Erreur de sécurité : Token invalide.</p>';
            echo '</div>';
        });
    }
}

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