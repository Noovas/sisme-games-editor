<?php
/**
 * File: /sisme-games-editor/admin/pages/game-data.php
 * Page Game Data - Dashboard principal sobre et simple
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

// Créer la page SANS bouton retour (page principale)
$page = new Sisme_Admin_Page_Wrapper(
    'Sisme Games',
    'Gestion des jeux et création de contenu',
    'database'
);

// Créer le module table
$table = new Sisme_Game_Data_Table_Module([
    'per_page' => -1,
    'edit_url' => admin_url('admin.php?page=sisme-games-edit-game-data')
]);

$page->render_start();
?>

<!-- Actions principales -->
<div class="sisme-card">
    <div class="sisme-card__body">
        <div class="sisme-dashboard-sections">
            <div class="sisme-section-item">
                <h3 class="sisme-section-title">🎮 Créer un jeu</h3>
                <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-game-data'); ?>" 
                   class="sisme-section-link">
                    Ajouter un nouveau jeu dans la base de données
                </a>
            </div>
            
            <div class="sisme-section-item sisme-section-disabled">
                <h3 class="sisme-section-title">📰 Créer une MàJ</h3>
                <span class="sisme-section-disabled-text">
                    Rédiger une mise à jour ou actualité (bientôt disponible)
                </span>
            </div>
            
            <div class="sisme-section-item sisme-section-disabled">
                <h3 class="sisme-section-title">📄 Tous les articles</h3>
                <span class="sisme-section-disabled-text">
                    Gérer tous les contenus publiés (bientôt disponible)
                </span>
            </div>
            
            <div class="sisme-section-item sisme-section-disabled">
                <h3 class="sisme-section-title">⚙️ Paramètres</h3>
                <span class="sisme-section-disabled-text">
                    Configuration du plugin (bientôt disponible)
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Ligne de contrôles épurée -->
<div class="sisme-table-controls">
    <h2 class="sisme-table-title">Jeux</h2>
    
    <div class="sisme-table-filters">
        <input type="text" 
               class="sisme-filter-input" 
               placeholder="Rechercher un jeu..."
               value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>">
    </div>
    
    <div class="sisme-table-actions">
        <a href="<?php echo admin_url('admin.php?page=sisme-games-edit-game-data'); ?>" 
           class="sisme-btn sisme-btn--primary sisme-btn--compact">
            ➕ Ajouter
        </a>
    </div>
</div>

<!-- Tableau épuré -->
<div class="sisme-card sisme-card--table">
    <div class="sisme-card__body">
        <?php $table->render(); ?>
    </div>
</div>

<script>
// Script simple pour la recherche
document.querySelector('.sisme-filter-input').addEventListener('input', function(e) {
    const searchValue = e.target.value;
    const url = new URL(window.location);
    
    if (searchValue) {
        url.searchParams.set('s', searchValue);
    } else {
        url.searchParams.delete('s');
    }
    
    // Débounce simple
    clearTimeout(this.searchTimeout);
    this.searchTimeout = setTimeout(() => {
        window.location.href = url.toString();
    }, 500);
});
</script>

<?php
$page->render_end();
?>