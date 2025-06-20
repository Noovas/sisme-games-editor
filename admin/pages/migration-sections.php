<?php
/**
 * File: /sisme-games-editor/admin/pages/migration-sections.php
 * Outil de migration unique pour transférer les sections vers Game Data
 */

if (!defined('ABSPATH')) {
    exit;
}

// Inclure les modules nécessaires
require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-wrapper.php';

// Traitement de la migration
$migration_results = array();
$migration_executed = false;

if (isset($_POST['execute_migration']) && wp_verify_nonce($_POST['migration_nonce'], 'sisme_migration_sections')) {
    $migration_results = execute_sections_migration();
    $migration_executed = true;
}

// Analyses préliminaires (toujours exécutées)
$analysis = analyze_sections_for_migration();

// Initialiser le wrapper de page
$page_wrapper = new Sisme_Admin_Page_Wrapper(
    'Migration des Sections',
    'Outil de migration unique : ancien format → Game Data',
    'database-alt',
    admin_url('admin.php?page=sisme-games-game-data'),
    'Retour au Game Data'
);

$page_wrapper->render_start();

/**
 * Analyser les données avant migration
 */
function analyze_sections_for_migration() {
    global $wpdb;
    
    $analysis = array(
        'total_posts_with_sections' => 0,
        'total_sections' => 0,
        'games_mapping' => array(),
        'orphaned_sections' => array(),
        'ready_to_migrate' => array()
    );
    
    // Trouver tous les posts avec _sisme_sections
    $posts_with_sections = $wpdb->get_results("
        SELECT post_id, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_sisme_sections' 
        AND meta_value != '' 
        AND meta_value != 'a:0:{}'
    ");
    
    $analysis['total_posts_with_sections'] = count($posts_with_sections);
    
    foreach ($posts_with_sections as $post_meta) {
        $post_id = $post_meta->post_id;
        $sections = maybe_unserialize($post_meta->meta_value);
        
        if (!is_array($sections) || empty($sections)) {
            continue;
        }
        
        // Compter les sections valides
        $valid_sections = 0;
        foreach ($sections as $section) {
            if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])) {
                $valid_sections++;
            }
        }
        
        if ($valid_sections == 0) {
            continue;
        }
        
        $analysis['total_sections'] += $valid_sections;
        
        // Récupérer les tags du post
        $post_tags = wp_get_post_tags($post_id);
        $post_title = get_the_title($post_id);
        
        if (empty($post_tags)) {
            $analysis['orphaned_sections'][] = array(
                'post_id' => $post_id,
                'post_title' => $post_title,
                'sections_count' => $valid_sections
            );
        } else {
            // Prendre le premier tag comme jeu principal
            $main_tag = $post_tags[0];
            
            if (!isset($analysis['games_mapping'][$main_tag->term_id])) {
                $analysis['games_mapping'][$main_tag->term_id] = array(
                    'tag_name' => $main_tag->name,
                    'posts' => array(),
                    'total_sections' => 0
                );
            }
            
            $analysis['games_mapping'][$main_tag->term_id]['posts'][] = array(
                'post_id' => $post_id,
                'post_title' => $post_title,
                'sections_count' => $valid_sections
            );
            
            $analysis['games_mapping'][$main_tag->term_id]['total_sections'] += $valid_sections;
            
            $analysis['ready_to_migrate'][] = array(
                'post_id' => $post_id,
                'post_title' => $post_title,
                'tag_id' => $main_tag->term_id,
                'tag_name' => $main_tag->name,
                'sections_count' => $valid_sections
            );
        }
    }
    
    return $analysis;
}

/**
 * Exécuter la migration complète
 */
function execute_sections_migration() {
    global $wpdb;
    
    $results = array(
        'success' => false,
        'migrated_games' => 0,
        'migrated_sections' => 0,
        'errors' => array(),
        'details' => array()
    );
    
    // Récupérer tous les posts avec sections
    $posts_with_sections = $wpdb->get_results("
        SELECT post_id, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_sisme_sections' 
        AND meta_value != '' 
        AND meta_value != 'a:0:{}'
    ");
    
    $games_sections = array(); // Regrouper par jeu
    
    foreach ($posts_with_sections as $post_meta) {
        $post_id = $post_meta->post_id;
        $sections = maybe_unserialize($post_meta->meta_value);
        
        if (!is_array($sections) || empty($sections)) {
            continue;
        }
        
        // Nettoyer les sections
        $clean_sections = array();
        foreach ($sections as $section) {
            if (!empty($section['title']) || !empty($section['content']) || !empty($section['image_id'])) {
                $clean_sections[] = array(
                    'title' => $section['title'] ?? '',
                    'content' => $section['content'] ?? '',
                    'image_id' => intval($section['image_id'] ?? 0)
                );
            }
        }
        
        if (empty($clean_sections)) {
            continue;
        }
        
        // Récupérer le tag principal
        $post_tags = wp_get_post_tags($post_id);
        
        if (empty($post_tags)) {
            $results['errors'][] = "Post #{$post_id} : Aucune étiquette trouvée, sections ignorées";
            continue;
        }
        
        $main_tag_id = $post_tags[0]->term_id;
        
        // Regrouper les sections par jeu
        if (!isset($games_sections[$main_tag_id])) {
            $games_sections[$main_tag_id] = array();
        }
        
        $games_sections[$main_tag_id] = array_merge($games_sections[$main_tag_id], $clean_sections);
        
        $results['details'][] = array(
            'post_id' => $post_id,
            'post_title' => get_the_title($post_id),
            'tag_id' => $main_tag_id,
            'tag_name' => $post_tags[0]->name,
            'sections_migrated' => count($clean_sections)
        );
    }
    
    // Sauvegarder dans term_meta
    foreach ($games_sections as $tag_id => $sections) {
        $updated = update_term_meta($tag_id, 'game_sections', $sections);
        
        if ($updated !== false) {
            $results['migrated_games']++;
            $results['migrated_sections'] += count($sections);
            
            // Marquer la date de migration
            update_term_meta($tag_id, 'sections_migrated_date', current_time('mysql'));
        } else {
            $tag_name = get_term($tag_id)->name ?? 'Inconnu';
            $results['errors'][] = "Échec sauvegarde pour le jeu #{$tag_id} ({$tag_name})";
        }
    }
    
    if ($results['migrated_games'] > 0) {
        $results['success'] = true;
    }
    
    return $results;
}

?>

<!-- Interface de migration -->
<?php if ($migration_executed): ?>
    <!-- Résultats de migration -->
    <div class="sisme-card <?php echo $migration_results['success'] ? 'sisme-card--success' : 'sisme-card--error'; ?>">
        <div class="sisme-card__header">
            <h2><?php echo $migration_results['success'] ? '✅ Migration réussie !' : '❌ Erreurs de migration'; ?></h2>
        </div>
        <div class="sisme-card__body">
            <?php if ($migration_results['success']): ?>
                <div style="background: #d4edda; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <h3 style="color: #155724; margin: 0 0 0.5rem 0;">📊 Résumé de la migration</h3>
                    <ul style="margin: 0; color: #155724;">
                        <li><strong><?php echo $migration_results['migrated_games']; ?></strong> jeux traités</li>
                        <li><strong><?php echo $migration_results['migrated_sections']; ?></strong> sections migrées</li>
                        <li>Data stockées dans <code>term_meta.game_sections</code></li>
                    </ul>
                </div>
                
                <h3>📋 Détail par jeu :</h3>
                <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                    <?php foreach ($migration_results['details'] as $detail): ?>
                        <div style="padding: 0.5rem; border-bottom: 1px solid #eee;">
                            <strong><?php echo esc_html($detail['tag_name']); ?></strong> 
                            (ID: <?php echo $detail['tag_id']; ?>) 
                            → <?php echo $detail['sections_migrated']; ?> section(s)
                            <br><small style="color: #666;">Source: <?php echo esc_html($detail['post_title']); ?> (#<?php echo $detail['post_id']; ?>)</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($migration_results['errors'])): ?>
                <h3 style="color: #dc3545;">⚠️ Erreurs rencontrées :</h3>
                <ul style="color: #dc3545;">
                    <?php foreach ($migration_results['errors'] as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <div style="margin-top: 2rem;">
                <a href="<?php echo admin_url('admin.php?page=sisme-games-game-data'); ?>" class="sisme-btn sisme-btn--primary">
                    Voir Game Data
                </a>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Analyse préliminaire -->
    <div class="sisme-card sisme-card--primary">
        <div class="sisme-card__header">
            <h2>📊 Analyse des données à migrer</h2>
        </div>
        <div class="sisme-card__body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div style="background: #e3f2fd; padding: 1rem; border-radius: 6px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #1976d2;"><?php echo $analysis['total_posts_with_sections']; ?></div>
                    <div style="color: #1976d2;">Articles avec sections</div>
                </div>
                <div style="background: #f3e5f5; padding: 1rem; border-radius: 6px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #7b1fa2;"><?php echo $analysis['total_sections']; ?></div>
                    <div style="color: #7b1fa2;">Total sections</div>
                </div>
                <div style="background: #e8f5e8; padding: 1rem; border-radius: 6px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #388e3c;"><?php echo count($analysis['games_mapping']); ?></div>
                    <div style="color: #388e3c;">Jeux identifiés</div>
                </div>
                <div style="background: #fff3e0; padding: 1rem; border-radius: 6px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #f57c00;"><?php echo count($analysis['orphaned_sections']); ?></div>
                    <div style="color: #f57c00;">Articles orphelins</div>
                </div>
            </div>
            
            <?php if (!empty($analysis['games_mapping'])): ?>
                <h3>🎮 Aperçu des jeux qui seront traités :</h3>
                <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 1rem;">
                    <?php foreach ($analysis['games_mapping'] as $tag_id => $game_data): ?>
                        <div style="padding: 0.5rem; border-bottom: 1px solid #eee;">
                            <strong><?php echo esc_html($game_data['tag_name']); ?></strong> 
                            → <?php echo $game_data['total_sections']; ?> section(s) 
                            depuis <?php echo count($game_data['posts']); ?> article(s)
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($analysis['orphaned_sections'])): ?>
                <div style="background: #fff3cd; padding: 1rem; border-radius: 6px; margin: 1rem 0;">
                    <h4 style="color: #856404; margin: 0 0 0.5rem 0;">⚠️ Articles sans étiquettes (ignorés) :</h4>
                    <ul style="margin: 0; color: #856404;">
                        <?php foreach ($analysis['orphaned_sections'] as $orphan): ?>
                            <li><?php echo esc_html($orphan['post_title']); ?> (<?php echo $orphan['sections_count']; ?> sections)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Formulaire de migration -->
    <?php if ($analysis['total_sections'] > 0): ?>
        <div class="sisme-card">
            <div class="sisme-card__header">
                <h2>🚀 Exécution de la migration</h2>
            </div>
            <div class="sisme-card__body">
                <div style="background: #f8d7da; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <h4 style="color: #721c24; margin: 0 0 0.5rem 0;">⚠️ Attention - Migration unique</h4>
                    <ul style="margin: 0; color: #721c24;">
                        <li>Cette migration va <strong>regrouper toutes les sections</strong> par jeu dans <code>term_meta</code></li>
                        <li>Les sections de plusieurs articles du même jeu seront <strong>fusionnées</strong></li>
                        <li>Cette opération ne peut être annulée facilement</li>
                        <li>Sauvegardez votre base de données avant de continuer</li>
                    </ul>
                </div>
                
                <form method="post" action="">
                    <?php wp_nonce_field('sisme_migration_sections', 'migration_nonce'); ?>
                    <button type="submit" name="execute_migration" class="sisme-btn sisme-btn--primary" 
                            onclick="return confirm('Êtes-vous sûr de vouloir migrer <?php echo $analysis['total_sections']; ?> sections vers le système Game Data ?\n\nCette action est irréversible.')">
                        🚀 Migrer <?php echo $analysis['total_sections']; ?> sections vers Game Data
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="sisme-card">
            <div class="sisme-card__body" style="text-align: center; padding: 3rem;">
                <h3>✅ Aucune section à migrer</h3>
                <p>Toutes les sections sont déjà dans le nouveau format ou aucune section n'existe.</p>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
$page_wrapper->render_end();
?>