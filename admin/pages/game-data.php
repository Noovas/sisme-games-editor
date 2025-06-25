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
    
    $game_id = intval($_GET['game_id']);
    $tag = get_term($game_id, 'post_tag');
    
    if ($tag && !is_wp_error($tag)) {
        $game_name = $tag->name;
        
        // Supprimer l'étiquette (et ses métadonnées automatiquement)
        $result = wp_delete_term($game_id, 'post_tag');
        
        if (!is_wp_error($result)) {
            add_action('admin_notices', function() use ($game_name) {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>✅ Jeu "' . esc_html($game_name) . '" supprimé définitivement.</p>';
                echo '</div>';
            });
        } else {
            add_action('admin_notices', function() use ($game_name) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p>❌ Erreur lors de la suppression de "' . esc_html($game_name) . '".</p>';
                echo '</div>';
            });
        }
    }
}

// Créer la page SANS bouton retour (page principale)
$page = new Sisme_Admin_Page_Wrapper(
    'Sisme Games',
    'Gestion des jeux et création de contenu',
    'screen'
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

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-game-data')) {
        e.preventDefault();
        
        const gameId = e.target.dataset.gameId;
        const gameName = e.target.dataset.gameName;
        
        if (confirm(`Supprimer "${gameName}" définitivement ?`)) {
            const deleteUrl = new URL(window.location);
            deleteUrl.searchParams.set('action', 'delete_game_data');
            deleteUrl.searchParams.set('game_id', gameId);
            deleteUrl.searchParams.set('_wpnonce', '<?php echo wp_create_nonce("delete_game_data_"); ?>' + gameId);
            
            window.location.href = deleteUrl.toString();
        }
    }
});
</script>

<script>
// JavaScript simple pour la confirmation
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('delete-game-data')) {
        e.preventDefault();
        
        const gameId = e.target.dataset.gameId;
        const gameName = e.target.dataset.gameName;
        
        if (confirm(`Supprimer définitivement "${gameName}" ?`)) {
            window.location.href = `?page=sisme-games-game-data&action=delete_game_data&game_id=${gameId}`;
        }
    }

    // team choice switch
    if (e.target.classList.contains('team-choice-btn')) {
        e.preventDefault();
        
        const btn = e.target;
        const gameId = btn.getAttribute('data-game-id');
        const currentValue = btn.getAttribute('data-team-choice');
        
        // Désactiver le bouton temporairement
        btn.disabled = true;
        btn.style.opacity = '0.5';
        
        // Requête AJAX
        const formData = new FormData();
        formData.append('action', 'sisme_toggle_team_choice');
        formData.append('term_id', gameId);
        formData.append('current_value', currentValue);
        formData.append('nonce', '<?php echo wp_create_nonce("sisme_team_choice_nonce"); ?>');
        
        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le bouton
                btn.textContent = data.data.icon;
                btn.setAttribute('data-team-choice', data.data.new_value);
                btn.setAttribute('title', data.data.title);
                btn.className = 'action-btn team-choice-btn ' + data.data.class;
                
                // Animation de succès
                btn.style.transform = 'scale(1.3)';
                setTimeout(() => {
                    btn.style.transform = '';
                }, 200);
                
            } else {
                alert('Erreur: ' + data.data);
            }
        })
        .catch(error => {
            console.error('Erreur AJAX:', error);
            alert('Erreur de connexion');
        })
        .finally(() => {
            // Réactiver le bouton
            btn.disabled = false;
            btn.style.opacity = '';
        });
    }
});
</script>
<?php
$page->render_end();
?>