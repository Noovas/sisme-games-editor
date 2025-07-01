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
Sisme_Vedettes_Loader::get_instance();

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
            'key' => Sisme_Utils_Games::META_DESCRIPTION,
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
        <h2>🗑️ Retirer des vedettes</h2>
        
        <?php if (!empty($featured_games)): ?>
            <form method="post" class="sisme-vedettes-form" id="removeFeaturedForm">
                <input type="hidden" name="action" value="remove_featured">
                <input type="hidden" name="game_id" id="selectedRemoveGameId">
                
                <!-- Layout en 2 colonnes (même structure) -->
                <div class="sisme-vedettes-layout">
                    
                    <!-- Colonne gauche: Sélection du jeu à retirer -->
                    <div class="sisme-selection-column">
                        <div class="sisme-game-name-component">
                            
                            <!-- Zone jeu sélectionné -->
                            <div class="sisme-selected-game">
                                <label class="sisme-form-label">Jeu à retirer des vedettes</label>
                                <div class="sisme-selected-game-display sisme-selected-game-display--remove" id="selectedRemoveGameDisplay">
                                    <span class="no-game-selected">Aucun jeu sélectionné</span>
                                </div>
                            </div>

                            <!-- Recherche et liste des jeux en vedette -->
                            <div class="sisme-game-search-section">
                                <label class="sisme-form-label">Rechercher dans les jeux en vedette</label>
                                <input type="text" 
                                       id="removeGameSearchInput" 
                                       class="sisme-game-search-input" 
                                       placeholder="Tapez pour filtrer les jeux en vedette..."
                                       autocomplete="off">
                                
                                <!-- Liste des jeux en vedette -->
                                <div class="sisme-game-list sisme-featured-game-list" id="removeGameList">
                                    <?php foreach ($featured_games_data as $featured_game): ?>
                                        <div class="featured-game-item" 
                                             data-game-id="<?php echo $featured_game[Sisme_Utils_Games::KEY_TERM_ID]; ?>"
                                             data-game-name="<?php echo esc_attr($featured_game[Sisme_Utils_Games::KEY_NAME]); ?>"
                                             data-search="<?php echo esc_attr(strtolower($featured_game[Sisme_Utils_Games::KEY_NAME])); ?>">
                                            <div class="featured-game-info">
                                                <span class="featured-game-name"><?php echo esc_html($featured_game[Sisme_Utils_Games::KEY_NAME]); ?></span>
                                                <div class="featured-game-meta">
                                                    <span class="priority-badge">Priorité: <?php echo $featured_game['vedette_data']['featured_priority']; ?></span>
                                                    <?php if (!empty($featured_game['vedette_data']['featured_sponsor'])): ?>
                                                        <span class="sponsor-badge">Sponsor: <?php echo esc_html($featured_game['vedette_data']['featured_sponsor']); ?></span>
                                                    <?php endif; ?>
                                                    <span class="stats-badge">
                                                        👁️ <?php echo $featured_game['vedette_data']['featured_stats']['views']; ?> | 
                                                        🖱️ <?php echo $featured_game['vedette_data']['featured_stats']['clicks']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Colonne droite: Information et confirmation -->
                    <div class="sisme-config-column sisme-config-column--remove">
                        <h3 class="sisme-config-title">Confirmation de suppression</h3>
                        
                        <div class="sisme-config-fields">
                            
                            <!-- Informations du jeu sélectionné -->
                            <div class="sisme-selected-info" id="selectedGameInfo" style="display: none;">
                                <div class="sisme-info-card">
                                    <h4>Jeu sélectionné :</h4>
                                    <div class="selected-game-details">
                                        <span class="selected-name" id="selectedGameName"></span>
                                        <span class="selected-id" id="selectedGameIdText"></span>
                                    </div>
                                </div>
                                
                                <div class="sisme-info-card">
                                    <h4>Statistiques actuelles :</h4>
                                    <div class="current-stats" id="currentStats">
                                        <div class="stat-item">
                                            <span class="stat-label">Priorité :</span>
                                            <span class="stat-value" id="currentPriority">-</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Sponsor :</span>
                                            <span class="stat-value" id="currentSponsor">Aucun</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Vues :</span>
                                            <span class="stat-value" id="currentViews">0</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-label">Clics :</span>
                                            <span class="stat-value" id="currentClicks">0</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Warning -->
                            <div class="sisme-warning-box" id="warningBox" style="display: none;">
                                <div class="warning-icon">⚠️</div>
                                <div class="warning-content">
                                    <strong>Attention :</strong>
                                    <p>Ce jeu sera retiré des vedettes et ne sera plus mis en avant sur le site. Les statistiques seront conservées.</p>
                                </div>
                            </div>
                            
                            <!-- Zone d'action -->
                            <div class="sisme-form-actions">
                                <button type="submit" class="sisme-btn sisme-btn--danger sisme-btn--large" id="removeSubmitBtn" disabled>
                                    🗑️ Retirer des vedettes
                                </button>
                                <button type="button" class="sisme-btn sisme-btn--secondary" onclick="resetRemoveForm()">
                                    🔄 Réinitialiser
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <div class="sisme-empty-state">
                <p>😔 Aucun jeu en vedette actuellement.</p>
                <p>Utilisez la section ci-dessus pour en ajouter !</p>
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

<!-- Notice d'utilisation du carrousel -->
<div class="sisme-vedettes-section">
    <h2>🎠 Utilisation du Carrousel Vedettes</h2>
    
    <div class="sisme-usage-grid">
        
        <!-- Shortcode simple -->
        <div class="sisme-usage-card">
            <h3>Shortcode Simple</h3>
            <div class="sisme-code-block">
                <code>[sisme_vedettes_carousel]</code>
            </div>
            <p>Affiche toutes les vedettes en carrousel 400px</p>
        </div>
        
        <!-- Shortcode avec options -->
        <div class="sisme-usage-card">
            <h3>Avec Options</h3>
            <div class="sisme-code-block">
                <code>[sisme_vedettes_carousel limit="5" height="300px" autoplay="false"]</code>
            </div>
            <p>5 jeux max, 300px de haut, pas d'autoplay</p>
        </div>
        
        <!-- Usage PHP -->
        <div class="sisme-usage-card">
            <h3>Usage PHP Template</h3>
            <div class="sisme-code-block">
                <code>echo Sisme_Vedettes_API::render_featured_carousel();</code>
            </div>
            <p>Intégration directe dans un thème</p>
        </div>
        
    </div>
    
    <!-- Options disponibles -->
    <div class="sisme-usage-options">
        <h3>Options Disponibles</h3>
        <ul>
            <li><strong>limit</strong> : Nombre max de jeux (défaut: 10)</li>
            <li><strong>height</strong> : Hauteur du carrousel (défaut: 400px)</li>
            <li><strong>autoplay</strong> : Lecture automatique (défaut: true)</li>
            <li><strong>show_arrows</strong> : Flèches navigation (défaut: true)</li>
            <li><strong>show_dots</strong> : Points navigation (défaut: true)</li>
        </ul>
    </div>
    
</div>

<!-- Aperçu du carrousel -->
<div class="sisme-vedettes-section">
    <h2>👀 Aperçu du Carrousel</h2>
    <?php 
    // Vérifier qu'on a des jeux vedettes pour l'aperçu
    if (!empty($featured_games_data)): 
    ?>
        <div class="sisme-preview-container">
            <?php
            // Générer l'aperçu avec des options adaptées à l'admin
            $preview_options = array(
                'limit' => 999,              // Limiter à 5 pour l'aperçu
                'height' => '600px',       // Plus petit pour la page admin
                'autoplay' => true,       // Pas d'autoplay pour éviter les distractions
                'show_arrows' => true,
                'show_dots' => true,
                'css_class' => 'sisme-admin-preview-carousel'
            );
            // Afficher le carrousel
            echo Sisme_Vedettes_API::render_featured_carousel($preview_options);
            ?>
        </div>
    <?php else: ?>
        <div class="sisme-preview-empty">
            <div class="sisme-empty-icon">🎯</div>
            <p>Ajoutez des jeux aux vedettes pour voir l'aperçu du carrousel ici !</p>
            <p><small>Le carrousel s'affichera automatiquement dès que vous aurez des jeux vedettes avec des covers principales.</small></p>
        </div>
    <?php endif; ?>
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
                item.style.display = 'flex';
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
        allGameItems.forEach(item => item.style.display = 'flex');
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const removeSearchInput = document.getElementById('removeGameSearchInput');
    const removeGameList = document.getElementById('removeGameList');
    const selectedRemoveDisplay = document.getElementById('selectedRemoveGameDisplay');
    const removeSubmitBtn = document.getElementById('removeSubmitBtn');
    const allFeaturedItems = document.querySelectorAll('.featured-game-item');
    
    // Éléments d'information
    const selectedGameInfo = document.getElementById('selectedGameInfo');
    const warningBox = document.getElementById('warningBox');
    const selectedGameName = document.getElementById('selectedGameName');
    const selectedGameIdText = document.getElementById('selectedGameIdText');
    const currentPriority = document.getElementById('currentPriority');
    const currentSponsor = document.getElementById('currentSponsor');
    const currentViews = document.getElementById('currentViews');
    const currentClicks = document.getElementById('currentClicks');
    
    let selectedRemoveGameId = null;
    let selectedGameData = null;
    
    // Données des jeux featured pour affichage
    const featuredGamesData = <?php echo json_encode(array_map(function($game) {
        return [
            Sisme_Utils_Games::KEY_ID => $game[Sisme_Utils_Games::KEY_TERM_ID],
            Sisme_Utils_Games::KEY_NAME => $game[Sisme_Utils_Games::KEY_NAME],
            'priority' => $game['vedette_data']['featured_priority'],
            'sponsor' => $game['vedette_data']['featured_sponsor'],
            'views' => $game['vedette_data']['featured_stats']['views'],
            'clicks' => $game['vedette_data']['featured_stats']['clicks']
        ];
    }, $featured_games_data)); ?>;
    
    // Filtrage en temps réel
    removeSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        allFeaturedItems.forEach(item => {
            const gameSearch = item.dataset.search;
            if (!searchTerm || gameSearch.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    });
    
    // Clic sur un jeu
    removeGameList.addEventListener('click', function(e) {
        const gameItem = e.target.closest('.featured-game-item');
        if (!gameItem) return;
        
        const gameId = parseInt(gameItem.dataset.gameId);
        const gameName = gameItem.dataset.gameName;
        
        // Trouver les données du jeu
        selectedGameData = featuredGamesData.find(game => game.id === gameId);
        
        selectGameForRemoval(gameId, gameName, selectedGameData);
    });
    
    // Sélectionner un jeu pour suppression
    function selectGameForRemoval(gameId, gameName, gameData) {
        selectedRemoveGameId = gameId;
        
        // Mettre à jour l'affichage de sélection
        selectedRemoveDisplay.innerHTML = `
            <div class="selected-game-info">
                <div class="selected-game-details">
                    <span class="game-name">${gameName}</span>
                    <span class="game-id">ID: ${gameId}</span>
                </div>
                <button type="button" class="remove-game" onclick="clearRemoveSelection()">✕</button>
            </div>
        `;
        selectedRemoveDisplay.classList.add('has-selection');
        
        // Remplir le champ caché
        document.getElementById('selectedRemoveGameId').value = gameId;
        
        // Mettre à jour les informations
        selectedGameName.textContent = gameName;
        selectedGameIdText.textContent = `ID: ${gameId}`;
        currentPriority.textContent = gameData.priority;
        currentSponsor.textContent = gameData.sponsor || 'Aucun';
        currentViews.textContent = gameData.views;
        currentClicks.textContent = gameData.clicks;
        
        // Afficher les panneaux d'info
        selectedGameInfo.style.display = 'flex';
        
        // Activer le bouton
        removeSubmitBtn.disabled = false;
        
        // Marquer comme sélectionné dans la liste
        allFeaturedItems.forEach(item => item.classList.remove('selected'));
        document.querySelector(`[data-game-id="${gameId}"]`).classList.add('selected');
    }
    
    // Fonction globale pour vider la sélection
    window.clearRemoveSelection = function() {
        selectedRemoveGameId = null;
        selectedGameData = null;
        
        selectedRemoveDisplay.innerHTML = '<span class="no-game-selected">Aucun jeu sélectionné</span>';
        selectedRemoveDisplay.classList.remove('has-selection');
        document.getElementById('selectedRemoveGameId').value = '';
        removeSubmitBtn.disabled = true;
        
        // Masquer les panneaux d'info
        selectedGameInfo.style.display = 'none';
        warningBox.style.display = 'none';
        
        // Enlever la sélection de la liste
        allFeaturedItems.forEach(item => item.classList.remove('selected'));
        
        // Focus sur recherche
        removeSearchInput.focus();
    };
    
    // Fonction globale pour réinitialiser le formulaire
    window.resetRemoveForm = function() {
        clearRemoveSelection();
        removeSearchInput.value = '';
        
        // Réafficher tous les jeux
        allFeaturedItems.forEach(item => item.style.display = 'flex');
    };
    
    // Validation du formulaire avec confirmation
    document.getElementById('removeFeaturedForm').addEventListener('submit', function(e) {
        if (!selectedRemoveGameId) {
            e.preventDefault();
            alert('Veuillez sélectionner un jeu à retirer');
            removeSearchInput.focus();
            return false;
        }
        
        // Confirmation de suppression
        const gameName = selectedGameData ? selectedGameData.name : 'ce jeu';
        if (!confirm(`Êtes-vous sûr de vouloir retirer "${gameName}" des vedettes ?\n\nCette action peut être annulée en remettant le jeu en vedette.`)) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
<?php
$page->render_end();
?>