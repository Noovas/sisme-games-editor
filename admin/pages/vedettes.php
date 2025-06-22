<?php
/**
 * File: /sisme-games-editor/admin/pages/vedettes.php
 * Page de gestion des jeux vedettes - Interface de gestion
 * 
 * CSS externe: assets/css/admin-vedettes.css
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure le wrapper et les modules vedettes
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/vedettes/vedettes-loader.php';

// Initialiser le système vedettes
Sisme_Vedettes_Loader::init();

// Variables pour les tests
$migration_results = null;
$action_message = '';

// Traitement des actions
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'run_migration':
            $migration_results = Sisme_Vedettes_Migration::run_migration();
            break;
            
        case 'add_featured':
            $game_id = intval($_POST['game_id']);
            $priority = intval($_POST['priority']) ?: 50;
            $sponsor = sanitize_text_field($_POST['sponsor'] ?? '');
            
            $success = Sisme_Vedettes_Data_Manager::set_as_featured($game_id, $priority, $sponsor);
            $action_message = $success ? "✅ Jeu ajouté aux vedettes !" : "❌ Erreur lors de l'ajout";
            break;
            
        case 'remove_featured':
            $game_id = intval($_POST['game_id']);
            $success = Sisme_Vedettes_Data_Manager::remove_from_featured($game_id);
            $action_message = $success ? "✅ Jeu retiré des vedettes !" : "❌ Erreur lors de la suppression";
            break;
            
        case 'clear_cache':
            Sisme_Vedettes_API::clear_cache();
            $action_message = "✅ Cache vidé !";
            break;

        case 'search_games':
            $search_term = sanitize_text_field($_POST['search'] ?? '');
            $games = Sisme_Vedettes_Data_Manager::search_games($search_term, true); // only_non_featured = true
            header('Content-Type: application/json');
            echo json_encode($games);
            wp_die();
            break;
    }
}

// Récupérer les données
$all_games = get_terms(array(
    'taxonomy' => 'post_tag',
    'hide_empty' => false,
    'meta_query' => array(
        array(
            'key' => 'game_description',
            'compare' => 'EXISTS'
        )
    )
));

$featured_games_data = Sisme_Vedettes_Data_Manager::get_featured_games(false);
$global_stats = Sisme_Vedettes_API::get_global_stats();

// Séparer les jeux featured et non-featured
$featured_games = array();
$non_featured_games = array();
$featured_ids = array_column($featured_games_data, 'term_id');

foreach ($all_games as $game) {
    if (in_array($game->term_id, $featured_ids)) {
        $featured_games[] = $game;
    } else {
        $non_featured_games[] = $game;
    }
}

// Créer la page
$page = new Sisme_Admin_Page_Wrapper(
    'Gestion des Vedettes',
    'Interface de gestion des jeux en vedette',
    'star',
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour à Game Data'
);

$page->render_start();
?>

<div class="sisme-admin-content">
    
    <?php if ($action_message): ?>
        <div class="sisme-notice sisme-notice--info">
            <?php echo $action_message; ?>
        </div>
    <?php endif; ?>

    <!-- Section Statistiques -->
    <div class="sisme-vedettes-stats">
        <h2>📊 Statistiques</h2>
        <div class="sisme-stats-grid">
            <div class="sisme-stat-card">
                <span class="sisme-stat-number"><?php echo count($all_games); ?></span>
                <span class="sisme-stat-label">Jeux totaux</span>
            </div>
            <div class="sisme-stat-card">
                <span class="sisme-stat-number"><?php echo count($featured_games); ?></span>
                <span class="sisme-stat-label">Jeux en vedette</span>
            </div>
            <div class="sisme-stat-card">
                <span class="sisme-stat-number"><?php echo count($non_featured_games); ?></span>
                <span class="sisme-stat-label">Jeux disponibles</span>
            </div>
        </div>
    </div>

    <!-- Section Ajouter aux vedettes -->
    <div class="sisme-vedettes-section">
        <h2>⭐ Ajouter un jeu aux vedettes</h2>
        
        <?php if (!empty($non_featured_games)): ?>
            <form method="post" class="sisme-vedettes-form" id="addFeaturedForm">
                <input type="hidden" name="action" value="add_featured">
                <input type="hidden" name="game_id" id="selectedGameId">
                
                <!-- Layout amélioré en 2 colonnes -->
                <div class="sisme-vedettes-layout">
                    
                    <!-- Colonne gauche: Sélection du jeu -->
                    <div class="sisme-selection-column">
                        <div class="sisme-game-name-component">
                            
                            <!-- Zone jeu sélectionné -->
                            <div class="sisme-selected-game">
                                <label class="sisme-form-label">Jeu sélectionné</label>
                                <div class="sisme-selected-game-display" id="selectedGameDisplay">
                                    <span class="no-game-selected">Aucun jeu sélectionné</span>
                                </div>
                            </div>

                            <!-- Recherche et liste -->
                            <div class="sisme-game-search-section">
                                <label class="sisme-form-label">Rechercher un jeu</label>
                                <input type="text" 
                                       id="gameSearchInput" 
                                       class="sisme-game-search-input" 
                                       placeholder="Tapez pour filtrer les jeux..."
                                       autocomplete="off">
                                
                                <!-- Liste des jeux filtrés -->
                                <div class="sisme-game-list" id="gameList">
                                    <?php foreach ($non_featured_games as $game): ?>
                                        <div class="game-item" 
                                             data-game-id="<?php echo $game->term_id; ?>"
                                             data-game-name="<?php echo esc_attr($game->name); ?>"
                                             data-search="<?php echo esc_attr(strtolower($game->name)); ?>">
                                            <?php echo esc_html($game->name); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Colonne droite: Configuration -->
                    <div class="sisme-config-column">
                        <h3 class="sisme-config-title">Configuration de la vedette</h3>
                        
                        <div class="sisme-config-fields">
                            <div class="sisme-form-field">
                                <label for="priority">Priorité d'affichage</label>
                                <input type="number" 
                                       name="priority" 
                                       id="priority" 
                                       value="50" 
                                       min="0" 
                                       max="100" 
                                       class="sisme-input">
                                <small class="sisme-field-help">Intensité de priorité (Max : 100)</small>
                            </div>
                            
                            <div class="sisme-form-field">
                                <label for="sponsor">Sponsor</label>
                                <input type="text" 
                                       name="sponsor" 
                                       id="sponsor" 
                                       placeholder="Nom du sponsor (optionnel)" 
                                       class="sisme-input">
                                <small class="sisme-field-help">Nom de l'entreprise ou organisation qui sponsorise cette vedette</small>
                            </div>
                            
                            <!-- Zone d'action -->
                            <div class="sisme-form-actions">
                                <button type="submit" class="sisme-btn sisme-btn--primary sisme-btn--large" id="submitBtn" disabled>
                                    ⭐ Mettre en vedette
                                </button>
                                <button type="button" class="sisme-btn sisme-btn--secondary" onclick="resetForm()">
                                    🔄 Réinitialiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="sisme-empty-state">
                <p>🎉 Tous les jeux sont déjà en vedette !</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section Retirer des vedettes -->
    <div class="sisme-vedettes-section">
        <h2>⭐ Retirer des vedettes</h2>
        
        <?php if (!empty($non_featured_games)): ?>
            <form method="post" class="sisme-vedettes-form" id="addFeaturedForm">
                <input type="hidden" name="action" value="add_featured">
                <input type="hidden" name="game_id" id="selectedGameId">
                
                <div class="sisme-form-row">
                    <div class="sisme-form-field">
                        <div class="sisme-game-name-component">
                            
                            <!-- Jeu sélectionné -->
                            <div class="sisme-selected-game">
                                <label class="sisme-form-label">Jeu à mettre en vedette</label>
                                <div class="sisme-selected-game-display sisme-selected-display-base" id="selectedGameDisplay">
                                    <span class="no-game-selected" style="color: #666; font-style: italic;">Aucun jeu sélectionné</span>
                                </div>
                            </div>

                            <!-- Recherche et sélection -->
                            <div class="sisme-game-search-container">
                                <label class="sisme-form-label">Rechercher un jeu :</label>
                                <div class="sisme-search-box">
                                    <input type="text" 
                                           id="gameSearchInput" 
                                           class="sisme-game-search-input sisme-form-input" 
                                           placeholder="Tapez le nom d'un jeu..." 
                                           autocomplete="off">
                                    <div class="sisme-game-suggestions" id="gameSuggestions"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sisme-form-field">
                        <label for="priority">Priorité (0-100) :</label>
                        <input type="number" name="priority" id="priority" value="50" min="0" max="100" class="sisme-input">
                    </div>
                    
                    <div class="sisme-form-field">
                        <label for="sponsor">Sponsor (optionnel) :</label>
                        <input type="text" name="sponsor" id="sponsor" placeholder="Nom du sponsor" class="sisme-input">
                    </div>
                    
                    <div class="sisme-form-field">
                        <button type="submit" class="sisme-btn sisme-btn--primary" id="submitBtn" disabled>
                            ⭐ Mettre en vedette
                        </button>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="sisme-empty-state">
                <p>🎉 Tous les jeux sont déjà en vedette !</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Section Migration -->
    <div class="sisme-vedettes-section">
        <h2>🔄 Migration & Maintenance</h2>
        
        <div class="sisme-maintenance-actions">
            <form method="post" style="display: inline-block;">
                <input type="hidden" name="action" value="run_migration">
                <button type="submit" class="sisme-btn sisme-btn--secondary">
                    🔄 Lancer la migration
                </button>
            </form>
            
            <form method="post" style="display: inline-block;">
                <input type="hidden" name="action" value="clear_cache">
                <button type="submit" class="sisme-btn sisme-btn--secondary">
                    🧹 Vider le cache
                </button>
            </form>
        </div>
        
        <?php if ($migration_results): ?>
            <div class="sisme-migration-results">
                <h4>Résultats de migration :</h4>
                <div class="sisme-migration-summary">
                    <span class="sisme-migration-stat">
                        <strong>Total:</strong> <?php echo $migration_results['total_games']; ?>
                    </span>
                    <span class="sisme-migration-stat">
                        <strong>Migrés:</strong> <?php echo $migration_results['migrated_games']; ?>
                    </span>
                    <span class="sisme-migration-stat">
                        <strong>Erreurs:</strong> <?php echo count($migration_results['errors']); ?>
                    </span>
                    <span class="sisme-migration-stat">
                        <strong>Temps:</strong> <?php echo round($migration_results['execution_time'], 2); ?>s
                    </span>
                </div>
                
                <?php if (!empty($migration_results['errors'])): ?>
                    <details class="sisme-migration-errors">
                        <summary>Voir les erreurs (<?php echo count($migration_results['errors']); ?>)</summary>
                        <ul>
                            <?php foreach ($migration_results['errors'] as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('gameSearchInput');
    const gameList = document.getElementById('gameList');
    const selectedDisplay = document.getElementById('selectedGameDisplay');
    const submitBtn = document.getElementById('submitBtn');
    const allGameItems = document.querySelectorAll('.game-item');
    
    let selectedGameId = null;
    
    // Filtrage en temps réel
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        allGameItems.forEach(item => {
            const gameSearch = item.dataset.search;
            if (!searchTerm || gameSearch.includes(searchTerm)) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Clic sur un jeu
    gameList.addEventListener('click', function(e) {
        const gameItem = e.target.closest('.game-item');
        if (!gameItem) return;
        
        const gameId = parseInt(gameItem.dataset.gameId);
        const gameName = gameItem.dataset.gameName;
        
        selectGame(gameId, gameName);
    });
    
    // Sélectionner un jeu
    function selectGame(gameId, gameName) {
        selectedGameId = gameId;
        
        // Mettre à jour l'affichage
        selectedDisplay.innerHTML = `
            <div class="selected-game-info">
                <div class="selected-game-details">
                    <span class="game-name">${gameName}</span>
                    <span class="game-id">ID: ${gameId}</span>
                </div>
                <button type="button" class="remove-game" onclick="clearSelection()">✕</button>
            </div>
        `;
        selectedDisplay.classList.add('has-selection');
        
        // Remplir le champ caché
        document.getElementById('selectedGameId').value = gameId;
        
        // Activer le bouton
        submitBtn.disabled = false;
        
        // Marquer comme sélectionné dans la liste
        allGameItems.forEach(item => item.classList.remove('selected'));
        document.querySelector(`[data-game-id="${gameId}"]`).classList.add('selected');
        
        // Focus sur priorité pour workflow fluide
        document.getElementById('priority').focus();
    }
    
    // Fonction globale pour vider la sélection
    window.clearSelection = function() {
        selectedGameId = null;
        selectedDisplay.innerHTML = '<span class="no-game-selected">Aucun jeu sélectionné</span>';
        selectedDisplay.classList.remove('has-selection');
        document.getElementById('selectedGameId').value = '';
        submitBtn.disabled = true;
        
        // Enlever la sélection de la liste
        allGameItems.forEach(item => item.classList.remove('selected'));
        
        // Focus sur recherche
        searchInput.focus();
    };
    
    // Fonction globale pour réinitialiser le formulaire
    window.resetForm = function() {
        clearSelection();
        document.getElementById('priority').value = '50';
        document.getElementById('sponsor').value = '';
        searchInput.value = '';
        
        // Réafficher tous les jeux
        allGameItems.forEach(item => item.style.display = 'block');
    };
    
    // Validation du formulaire
    document.getElementById('addFeaturedForm').addEventListener('submit', function(e) {
        if (!selectedGameId) {
            e.preventDefault();
            alert('Veuillez sélectionner un jeu');
            searchInput.focus();
            return false;
        }
    });
});
</script>
<?php
$page->render_end();
?>