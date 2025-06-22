<?php
/**
 * File: /sisme-games-editor/admin/pages/vedettes.php
 * Page de gestion des jeux vedettes - Interface de test
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
$featured_games = null;
$global_stats = null;
$test_game_id = null;

// Traitement des actions de test
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'run_migration':
            $migration_results = Sisme_Vedettes_Migration::run_migration();
            break;
            
        case 'test_set_featured':
            $game_id = intval($_POST['game_id']);
            $priority = intval($_POST['priority']);
            $sponsor = sanitize_text_field($_POST['sponsor']);
            
            $success = Sisme_Vedettes_Data_Manager::set_as_featured($game_id, $priority, $sponsor);
            echo $success ? "✅ Jeu $game_id mis en vedette !" : "❌ Erreur mise en vedette";
            break;
            
        case 'test_remove_featured':
            $game_id = intval($_POST['game_id']);
            $success = Sisme_Vedettes_Data_Manager::remove_from_featured($game_id);
            echo $success ? "✅ Jeu $game_id retiré des vedettes !" : "❌ Erreur suppression vedette";
            break;
            
        case 'clear_cache':
            Sisme_Vedettes_API::clear_cache();
            echo "✅ Cache vidé !";
            break;
    }
}

// Récupérer les données pour affichage
$migration_report = Sisme_Vedettes_Migration::get_migration_report();
$featured_games = Sisme_Vedettes_API::get_frontend_featured_games(10, false);
$global_stats = Sisme_Vedettes_API::get_global_stats();

// Récupérer quelques jeux pour les tests
$sample_games = get_terms(array(
    'taxonomy' => 'post_tag',
    'hide_empty' => false,
    'number' => 5,
    'meta_query' => array(
        array(
            'key' => 'game_description',
            'compare' => 'EXISTS'
        )
    )
));

// Créer la page
$page = new Sisme_Admin_Page_Wrapper(
    'Vedettes - Tests',
    'Interface de test pour le système de vedettes',
    'star',
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour à Game Data'
);

$page->render_start();
?>

<div class="sisme-admin-content">
    
    <!-- Section Migration -->
    <div class="sisme-test-section">
        <h2>🔄 Migration</h2>
        
        <form method="post" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="run_migration">
            <button type="submit" style="background: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
                Lancer la migration
            </button>
        </form>
        
        <?php if ($migration_results): ?>
            <div style="background: var(--sisme-gaming-dark); padding: 15px; border-radius: 5px; margin: 10px 0; border: 1px solid rgba(255, 255, 255, 0.1);">
                <h4>Résultats de migration :</h4>
                <pre><?php echo json_encode($migration_results, JSON_PRETTY_PRINT); ?></pre>
            </div>
        <?php endif; ?>
        
        <div style="background: var(--sisme-gaming-dark); padding: 15px; border-radius: 5px; border: 1px solid rgba(255, 255, 255, 0.1);">
            <h4>Rapport migration actuel :</h4>
            <pre><?php echo json_encode($migration_report, JSON_PRETTY_PRINT); ?></pre>
        </div>
    </div>

    <!-- Section Tests CRUD -->
    <div class="sisme-test-section">
        <h2>🎮 Tests CRUD Vedettes</h2>
        
        <?php if (!empty($sample_games)): ?>
            <div style="background: var(--sisme-gaming-dark); padding: 15px; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 5px; margin: 10px 0;">
                <h4 style="color: var(--sisme-gaming-text-bright);">Mettre un jeu en vedette :</h4>
                <form method="post" style="display: flex; gap: 10px; align-items: end;">
                    <input type="hidden" name="action" value="test_set_featured">
                    
                    <div>
                        <label>Jeu :</label><br>
                        <select name="game_id" required>
                            <?php foreach ($sample_games as $game): ?>
                                <option value="<?php echo $game->term_id; ?>">
                                    <?php echo esc_html($game->name); ?> (ID: <?php echo $game->term_id; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label>Priorité :</label><br>
                        <input type="number" name="priority" value="50" min="0" max="100" required>
                    </div>
                    
                    <div>
                        <label>Sponsor :</label><br>
                        <input type="text" name="sponsor" placeholder="Nom du sponsor">
                    </div>
                    
                    <button type="submit" style="background: #46b450; color: white; padding: 8px 15px; border: none; border-radius: 3px;">
                        ⭐ Mettre en vedette
                    </button>
                </form>
            </div>
            
            <div style="background: var(--sisme-gaming-dark); padding: 15px; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 5px; margin: 10px 0;">
                <h4 style="color: var(--sisme-gaming-text-bright);">Retirer un jeu des vedettes :</h4>
                <form method="post" style="display: flex; gap: 10px; align-items: end;">
                    <input type="hidden" name="action" value="test_remove_featured">
                    
                    <div>
                        <label>Jeu :</label><br>
                        <select name="game_id" required>
                            <?php foreach ($sample_games as $game): ?>
                                <option value="<?php echo $game->term_id; ?>">
                                    <?php echo esc_html($game->name); ?> (ID: <?php echo $game->term_id; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" style="background: #dc3232; color: white; padding: 8px 15px; border: none; border-radius: 3px;">
                        🗑️ Retirer des vedettes
                    </button>
                </form>
            </div>
        <?php else: ?>
            <p style="color: #d63638;">Aucun jeu disponible pour les tests. Créez d'abord des jeux dans Game Data.</p>
        <?php endif; ?>
    </div>

    <!-- Section Affichage -->
    <div class="sisme-test-section">
        <h2>📊 Données actuelles</h2>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h4 style="color: var(--sisme-gaming-text-bright);">Statistiques globales :</h4>
                <div style="background: var(--sisme-gaming-dark); padding: 15px; border-radius: 5px; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <pre><?php echo json_encode($global_stats, JSON_PRETTY_PRINT); ?></pre>
                </div>
            </div>
            
            <div>
                <h4 style="color: var(--sisme-gaming-text-bright);">Jeux en vedette :</h4>
                <div style="background: var(--sisme-gaming-dark); padding: 15px; border-radius: 5px; max-height: 300px; overflow-y: auto; border: 1px solid rgba(255, 255, 255, 0.1);">
                    <?php if (!empty($featured_games)): ?>
                        <?php foreach ($featured_games as $game): ?>
                            <div style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding: 10px 0;">
                                <strong style="color: var(--sisme-gaming-text-bright);"><?php echo esc_html($game['name']); ?></strong><br>
                                <small style="color: var(--sisme-gaming-text-muted);">
                                    ID: <?php echo $game['term_id']; ?> | 
                                    Priorité: <?php echo $game['priority']; ?> | 
                                    Vues: <?php echo $game['stats']['views']; ?> | 
                                    Clics: <?php echo $game['stats']['clicks']; ?>
                                    <?php if (!empty($game['sponsor'])): ?>
                                        <br>Sponsor: <?php echo esc_html($game['sponsor']); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>Aucun jeu en vedette actuellement.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Cache -->
    <div class="sisme-test-section">
        <h2>🗂️ Cache</h2>
        
        <form method="post">
            <input type="hidden" name="action" value="clear_cache">
            <button type="submit" style="background: #f56565; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
                Vider le cache
            </button>
        </form>
        
        <p style="color: #666; margin-top: 10px;">
            <em>Le cache est automatiquement vidé lors des modifications. Durée : 5 minutes.</em>
        </p>
    </div>

    <!-- Section Console Log JavaScript -->
    <div class="sisme-test-section">
        <h2>🔍 Tests JavaScript (voir console)</h2>
        
        <button onclick="testVedettesAPI()" style="background: #7c3aed; color: white; padding: 10px 20px; border: none; border-radius: 5px;">
            Tester API JavaScript
        </button>
        
        <p style="color: #666; margin-top: 10px;">
            <em>Ouvrez la console du navigateur (F12) et cliquez sur le bouton pour voir les tests.</em>
        </p>
    </div>

</div>

<style>
.sisme-test-section {
    background: var(--sisme-gaming-dark-light);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.sisme-test-section h2 {
    color: var(--sisme-gaming-text-bright);
    margin-top: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    padding-bottom: 10px;
}

.sisme-test-section pre {
    background: var(--sisme-gaming-dark);
    color: var(--sisme-gaming-text);
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
    font-size: 12px;
}
</style>

<script>
function testVedettesAPI() {
    console.log("=== Tests API Vedettes ===");
    
    // Test données globales
    const globalStats = <?php echo json_encode($global_stats); ?>;
    console.log("📊 Statistiques globales:", globalStats);
    
    // Test jeux vedettes
    const featuredGames = <?php echo json_encode($featured_games); ?>;
    console.log("🎮 Jeux en vedette:", featuredGames);
    
    // Test rapport migration
    const migrationReport = <?php echo json_encode($migration_report); ?>;
    console.log("🔄 Rapport migration:", migrationReport);
    
    // Simuler tracking
    if (featuredGames.length > 0) {
        const firstGame = featuredGames[0];
        console.log("👁️ Simulation tracking vue pour:", firstGame.name);
        console.log("🖱️ Simulation tracking clic pour:", firstGame.name);
    }
    
    console.log("=== Fin des tests ===");
}

// Auto-test au chargement
document.addEventListener('DOMContentLoaded', function() {
    console.log("🎯 Page Vedettes chargée - Système opérationnel");
    console.log("💡 Utilisez testVedettesAPI() pour tester l'API");
});
</script>

<?php
$page->render_end();
?>