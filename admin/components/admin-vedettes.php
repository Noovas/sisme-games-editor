<?php
/**
 * File: /sisme-games-editor/admin/components/admin-vedettes.php
 * Page de gestion des jeux vedettes - Interface de gestion complète
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Admin_Vedettes {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            null,
            'Vedettes',
            'Vedettes',
            'manage_options',
            'sisme-games-vedettes',
            array(__CLASS__, 'render')
        );
    }
    
    public static function render() {
        // Inclure les dépendances
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
            admin_url('admin.php?page=sisme-games-jeux'),
            'Retour à Jeux'
        );

        $page->render_start();
        ?>

        <div class="sisme-admin-layout">
            
            <?php if ($action_message): ?>
                <div class="sisme-admin-alert sisme-admin-alert-info">
                    <?php echo $action_message; ?>
                </div>
            <?php endif; ?>

            <!-- Section Statistiques -->
            <div class="sisme-admin-section">
                <h2 class="sisme-admin-subtitle">📊 Statistiques</h2>
                <div class="sisme-admin-stats">
                    <div class="sisme-admin-stat-card">
                        <span class="sisme-admin-stat-number"><?php echo count($all_games); ?></span>
                        <span class="sisme-admin-stat-label">Jeux totaux</span>
                    </div>
                    <div class="sisme-admin-stat-card sisme-admin-stat-card-info">
                        <span class="sisme-admin-stat-number"><?php echo count($featured_games); ?></span>
                        <span class="sisme-admin-stat-label">Jeux en vedette</span>
                    </div>
                    <div class="sisme-admin-stat-card sisme-admin-stat-card-success">
                        <span class="sisme-admin-stat-number"><?php echo count($non_featured_games); ?></span>
                        <span class="sisme-admin-stat-label">Jeux disponibles</span>
                    </div>
                </div>
            </div>

            <!-- Section Ajouter aux vedettes -->
            <div class="sisme-admin-card">
                <div class="sisme-admin-card-header">
                    <h2 class="sisme-admin-heading">⭐ Ajouter un jeu aux vedettes</h2>
                </div>
                
                <?php if (!empty($non_featured_games)): ?>
                    <form method="post" class="sisme-admin-form" id="addFeaturedForm">
                        <input type="hidden" name="action" value="add_featured">
                        <input type="hidden" name="game_id" id="selectedGameId">
                        
                        <!-- Layout amélioré en 2 colonnes -->
                        <div class="sisme-admin-grid-2">
                            
                            <!-- Colonne gauche: Sélection du jeu -->
                            <div class="sisme-admin-card">
                                <div class="sisme-admin-card-header">
                                    <h3 class="sisme-admin-heading">🎮 Sélection du jeu</h3>
                                </div>
                                    
                                <!-- Zone jeu sélectionné pour AJOUTER = VIOLET -->
                                <div class="sisme-admin-alert sisme-admin-alert-info sisme-admin-mb-md selected-game sisme-admin-alert-purple" id="selectedGameDisplay">
                                    <span class="no-game-selected">Aucun jeu sélectionné</span>
                                </div>

                                <!-- Recherche et liste des jeux -->
                                <div class="sisme-admin-flex-col">
                                    <input type="text" 
                                           id="gameSearchInput" 
                                           placeholder="🔍 Rechercher un jeu..." 
                                           class="sisme-admin-input"
                                           autocomplete="off">
                                    
                                    <div class="sisme-admin-card sisme-admin-card-dark game-list sisme-admin-scrollable-container" id="gameList">
                                        <?php foreach ($non_featured_games as $game): ?>
                                            <div class="sisme-admin-flex-between sisme-admin-p-sm sisme-admin-border sisme-admin-rounded sisme-admin-mb-sm sisme-admin-cursor-pointer game-item sisme-admin-smooth-transition sisme-admin-item-neutral" 
                                                 data-game-id="<?php echo $game->term_id; ?>" 
                                                 data-game-name="<?php echo esc_attr($game->name); ?>"
                                                 data-search="<?php echo esc_attr(strtolower($game->name . ' ' . $game->term_id)); ?>">
                                                <span class="game-name sisme-admin-text-black sisme-admin-font-medium"><?php echo esc_html($game->name); ?></span>
                                                <span class="sisme-admin-badge sisme-admin-badge-secondary game-id">ID: <?php echo $game->term_id; ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Colonne droite: Configuration -->
                            <div class="sisme-admin-card">
                                <div class="sisme-admin-card-header">
                                    <h3 class="sisme-admin-heading">⚙️ Configuration de la vedette</h3>
                                </div>
                                
                                <div class="sisme-admin-flex-col">
                                    <div class="sisme-admin-flex-col-sm">
                                        <label for="priority" class="sisme-admin-heading">Priorité d'affichage</label>
                                        <input type="number" id="priority" name="priority" value="50" min="1" max="100" class="sisme-admin-input">
                                        <small class="sisme-admin-comment">Plus élevé = plus prioritaire (1-100)</small>
                                    </div>
                                    
                                    <div class="sisme-admin-flex-col-sm">
                                        <label for="sponsor" class="sisme-admin-heading">Sponsor (optionnel)</label>
                                        <input type="text" id="sponsor" name="sponsor" placeholder="Nom du sponsor" class="sisme-admin-input">
                                        <small class="sisme-admin-comment">Laisser vide si aucun sponsor</small>
                                    </div>
                                    
                                    <div class="sisme-admin-flex sisme-admin-mt-lg">
                                        <button type="submit" class="sisme-admin-btn sisme-admin-btn-success sisme-admin-btn-lg" id="submitBtn" disabled>
                                            ⭐ Ajouter aux vedettes
                                        </button>
                                        <button type="button" class="sisme-admin-btn sisme-admin-btn-secondary" onclick="resetForm()">
                                            🔄 Réinitialiser
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="sisme-admin-alert sisme-admin-alert-success sisme-admin-text-center">
                        <p class="sisme-admin-heading">🎉 Tous les jeux sont déjà en vedette !</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Section Retirer des vedettes -->
            <div class="sisme-admin-card">
                <div class="sisme-admin-card-header">
                    <h2 class="sisme-admin-heading">🗑️ Retirer des vedettes</h2>
                </div>
                
                <?php if (!empty($featured_games)): ?>
                    <form method="post" class="sisme-admin-form" id="removeFeaturedForm">
                        <input type="hidden" name="action" value="remove_featured">
                        <input type="hidden" name="game_id" id="selectedRemoveGameId">
                        
                        <!-- Layout en 2 colonnes (même structure) -->
                        <div class="sisme-admin-grid-2">
                            
                            <!-- Colonne gauche: Sélection du jeu à retirer -->
                            <div class="sisme-admin-card">
                                <div class="sisme-admin-card-header">
                                    <h3 class="sisme-admin-heading">🎯 Jeu à retirer</h3>
                                </div>
                                    
                                <!-- Zone jeu sélectionné pour SUPPRIMER = ROUGE -->
                                <div class="sisme-admin-alert sisme-admin-alert-danger sisme-admin-mb-md selected-game" id="selectedRemoveGameDisplay">
                                    <span class="no-game-selected">Aucun jeu sélectionné</span>
                                </div>

                                <!-- Recherche et liste des jeux featured -->
                                <div class="sisme-admin-flex-col">
                                    <input type="text" 
                                           id="removeGameSearchInput" 
                                           placeholder="🔍 Rechercher dans les vedettes..." 
                                           class="sisme-admin-input"
                                           autocomplete="off">
                                    
                                    <div class="sisme-admin-card sisme-admin-card-dark game-list sisme-admin-scrollable-container" id="removeGameList">
                                        <?php foreach ($featured_games as $game): ?>
                                            <div class="sisme-admin-flex-between sisme-admin-p-sm sisme-admin-border sisme-admin-rounded sisme-admin-mb-sm sisme-admin-cursor-pointer featured-game-item sisme-admin-smooth-transition sisme-admin-item-purple" 
                                                 data-game-id="<?php echo $game->term_id; ?>" 
                                                 data-game-name="<?php echo esc_attr($game->name); ?>"
                                                 data-search="<?php echo esc_attr(strtolower($game->name . ' ' . $game->term_id)); ?>">
                                                <div class="sisme-admin-flex-col-sm">
                                                    <span class="game-name sisme-admin-text-black sisme-admin-font-medium"><?php echo esc_html($game->name); ?></span>
                                                    <span class="sisme-admin-badge sisme-admin-badge-secondary featured-badge">⭐ Vedette</span>
                                                </div>
                                                <span class="sisme-admin-badge sisme-admin-badge-secondary game-id">ID: <?php echo $game->term_id; ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Colonne droite: Information et confirmation -->
                            <div class="sisme-admin-card">
                                <div class="sisme-admin-card-header">
                                    <h3 class="sisme-admin-heading">⚠️ Confirmation de suppression</h3>
                                </div>
                                
                                <div class="sisme-admin-flex-col">
                                    
                                    <!-- Informations du jeu sélectionné -->
                                    <div class="sisme-admin-alert sisme-admin-alert-info sisme-admin-hidden" id="selectedGameInfo">
                                        <div class="sisme-admin-flex-between">
                                            <strong id="selectedGameName" class="sisme-admin-heading"></strong>
                                            <span id="selectedGameIdText" class="sisme-admin-badge sisme-admin-badge-info"></span>
                                        </div>
                                        <div class="sisme-admin-flex sisme-admin-mt-sm">
                                            <span class="sisme-admin-badge sisme-admin-badge-secondary">
                                                Priorité: <strong id="currentPriority"></strong>
                                            </span>
                                            <span class="sisme-admin-badge sisme-admin-badge-secondary">
                                                Sponsor: <strong id="currentSponsor"></strong>
                                            </span>
                                        </div>
                                        <div class="sisme-admin-flex sisme-admin-mt-sm">
                                            <span class="sisme-admin-badge sisme-admin-badge-info">
                                                👁️ <strong id="currentViews"></strong> vues
                                            </span>
                                            <span class="sisme-admin-badge sisme-admin-badge-success">
                                                🖱️ <strong id="currentClicks"></strong> clics
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Warning -->
                                    <div class="sisme-admin-alert sisme-admin-alert-danger sisme-admin-hidden" id="warningBox">
                                        <div class="sisme-admin-flex">
                                            <div class="sisme-admin-text-danger sisme-admin-icon-lg">⚠️</div>
                                            <div>
                                                <strong>Attention !</strong><br>
                                                Ce jeu sera retiré des vedettes et n'apparaîtra plus dans le carrousel.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Zone d'action -->
                                    <div class="sisme-admin-flex">
                                        <button type="submit" class="sisme-admin-btn sisme-admin-btn-danger sisme-admin-btn-lg" id="removeSubmitBtn" disabled>
                                            🗑️ Retirer des vedettes
                                        </button>
                                        <button type="button" class="sisme-admin-btn sisme-admin-btn-secondary" onclick="resetRemoveForm()">
                                            🔄 Annuler
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="sisme-admin-alert sisme-admin-alert-warning sisme-admin-text-center">
                        <p class="sisme-admin-heading">😔 Aucun jeu en vedette actuellement.</p>
                        <p class="sisme-admin-comment">Utilisez la section ci-dessus pour en ajouter !</p>
                    </div>
                <?php endif; ?>
            </div>

        <!-- Notice d'utilisation du carrousel -->
        <div class="sisme-admin-card">
            <div class="sisme-admin-card-header">
                <h2 class="sisme-admin-heading">🎠 Utilisation du Carrousel Vedettes</h2>
            </div>
            
            <div class="sisme-admin-grid-3">
                
                <!-- Shortcode simple -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <h3 class="sisme-admin-heading">Shortcode Simple</h3>
                    </div>
                    <pre class="sisme-admin-pre-code sisme-admin-text-center">[sisme_vedettes_carousel]</pre>
                    <p class="sisme-admin-comment">Affiche toutes les vedettes en carrousel 400px</p>
                </div>
                
                <!-- Shortcode avec options -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <h3 class="sisme-admin-heading">Avec Options</h3>
                    </div>
                    <pre class="sisme-admin-pre-code sisme-admin-text-center">[sisme_vedettes_carousel limit="5" height="300px" autoplay="false"]</pre>
                    <p class="sisme-admin-comment">5 jeux max, 300px de haut, pas d'autoplay</p>
                </div>
                
                <!-- Usage PHP -->
                <div class="sisme-admin-card">
                    <div class="sisme-admin-card-header">
                        <h3 class="sisme-admin-heading">Usage PHP Template</h3>
                    </div>
                    <pre class="sisme-admin-pre-code sisme-admin-text-center">echo Sisme_Vedettes_API::render_featured_carousel();</pre>
                    <p class="sisme-admin-comment">Intégration directe dans un thème</p>
                </div>
                
            </div>
            
            <!-- Options disponibles -->
            <div class="sisme-admin-mt-lg">
                <h3 class="sisme-admin-heading">Options Disponibles</h3>
                <ul class="sisme-admin-ml-md">
                    <li><strong>limit</strong> : Nombre max de jeux (défaut: 10)</li>
                    <li><strong>height</strong> : Hauteur du carrousel (défaut: 400px)</li>
                    <li><strong>autoplay</strong> : Lecture automatique (défaut: true)</li>
                    <li><strong>show_arrows</strong> : Flèches navigation (défaut: true)</li>
                    <li><strong>show_dots</strong> : Points navigation (défaut: true)</li>
                </ul>
            </div>
            
        </div>

        </div>

        <style>
        /* Styles spécifiques pour les interactions JavaScript */
        
        /* Zone de sélection pour AJOUTER = VIOLET */
        #selectedGameDisplay.selected-game.has-selection {
            background-color: var(--sisme-admin-purple-light) !important;
            border-left-color: var(--sisme-admin-purple) !important;
            color: var(--sisme-admin-purple-dark) !important;
        }
        
        /* Zone de sélection pour SUPPRIMER = ROUGE */
        #selectedRemoveGameDisplay.selected-game.has-selection {
            background-color: var(--sisme-admin-red-light) !important;
            border-left-color: var(--sisme-admin-red) !important;
            color: var(--sisme-admin-red-dark) !important;
        }
        
        .game-item:hover, .featured-game-item:hover {
            background-color: var(--sisme-admin-purple-light) !important;
            border-color: var(--sisme-admin-purple) !important;
            transform: translateY(-1px);
            color: var(--sisme-admin-black) !important;
        }
        
        /* Jeux NON-VEDETTES sélectionnés (pour AJOUTER) = VIOLET */
        .game-item.selected {
            background-color: var(--sisme-admin-purple) !important;
            border-color: var(--sisme-admin-purple-dark) !important;
            font-weight: 600;
            color: var(--sisme-admin-white) !important;
        }
        
        .game-item.selected .game-name {
            color: var(--sisme-admin-white) !important;
            font-weight: 700;
        }
        
        /* Jeux VEDETTES sélectionnés (pour SUPPRIMER) = ROUGE */
        .featured-game-item.selected {
            background-color: var(--sisme-admin-red) !important;
            border-color: var(--sisme-admin-red-dark) !important;
            font-weight: 600;
            color: var(--sisme-admin-white) !important;
        }
        
        .featured-game-item.selected .game-name {
            color: var(--sisme-admin-white) !important;
            font-weight: 700;
        }
        
        .selected-game-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        .selected-game-details {
            display: flex;
            flex-direction: column;
            gap: var(--sisme-admin-spacing-xs);
        }
        
        .selected-game-details .game-name {
            font-weight: 600;
            color: var(--sisme-admin-purple-dark); /* Pour la section ajout */
        }
        
        /* Zone de sélection pour suppression = rouge */
        #selectedRemoveGameDisplay.has-selection .selected-game-details .game-name {
            color: var(--sisme-admin-red-dark) !important;
        }
        
        .selected-game-details .game-id {
            font-size: 12px;
            color: var(--sisme-admin-gray-500);
        }
        
        .remove-game {
            background: var(--sisme-admin-red);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--sisme-admin-transition);
        }
        
        .remove-game:hover {
            background: var(--sisme-admin-red-dark);
            transform: scale(1.1);
        }
        
        .no-game-selected {
            font-style: italic;
            color: var(--sisme-admin-gray-500);
        }

        /* Ajustements pour les input admin */
        .sisme-admin-input {
            padding: var(--sisme-admin-spacing-sm);
            border: 1px solid var(--sisme-admin-gray-300);
            border-radius: var(--sisme-admin-radius-md);
            font-size: 14px;
            transition: var(--sisme-admin-transition);
        }

        .sisme-admin-input:focus {
            border-color: var(--sisme-admin-blue);
            outline: none;
            box-shadow: 0 0 0 2px var(--sisme-admin-blue-light);
        }

        .sisme-admin-form {
            display: flex;
            flex-direction: column;
            gap: var(--sisme-admin-spacing-lg);
        }
        </style>

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
    }
}
