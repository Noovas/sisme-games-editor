<?php
/**
 * Inspecteur de données des jeux
 * 
 * Cet outil permet d'examiner la structure des données des jeux
 * pour faciliter le développement et le débogage.
 */

class Sisme_Admin_Data_Inspector {
    
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_hidden_page'));
    }

    /**
     * Ajouter comme page cachée (accessible par URL mais pas dans le menu)
     */
    public static function add_hidden_page() {
        add_submenu_page(
            null, // null = page cachée
            'Inspecteur de données',
            'Inspecteur de données',
            'manage_options',
            'sisme-games-data-inspector',
            array(__CLASS__, 'render')
        );
    }
    
    public static function add_admin_menu() {
        add_submenu_page(
            'sisme-games-tableau-de-bord',
            'Inspecteur de données',
            '🛢️ Inspecteur de données',
            'manage_options',
            'sisme-games-data-inspector',
            array(__CLASS__, 'render')
        );
    }
    
    
    
    public static function render() {   
        $inspect_game_id = isset($_GET['inspect_game']) ? intval($_GET['inspect_game']) : 0;
        ?>
        <div class="sisme-admin-container">
            <h2 class="sisme-admin-title">🛢️ Inspecteur de données des jeux</h2>
            <p class="sisme-admin-comment">Examinez la structure des données des jeux pour le développement et le débogage</p>
            <div class="sisme-admin-flex-col">
                <?php
                self::render_game_selector($inspect_game_id);
                self::render_game_data($inspect_game_id);
                ?>
            </div>
        </div>
        <?php
    }
    
    private static function render_game_selector($selected_id) {
        // Récupérer tous les jeux (termes de taxonomie)
        $games = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'number' => 100
        ]);
        ?>
        
        <div class="sisme-admin-card">
            <div class="sisme-admin-card-header">
                <h2 class="sisme-admin-heading">🔍 Sélectionner un jeu à inspecter</h2>
            </div>
            
            <form method="get" class="sisme-admin-flex sisme-admin-flex-center">
                <input type="hidden" name="page" value="sisme-games-data-inspector">
                
                <select name="inspect_game" class="sisme-admin-p-sm sisme-admin-border sisme-admin-rounded" style="min-width: 300px;">
                    <option value="0">-- Sélectionner un jeu --</option>
                    <?php foreach ($games as $game): ?>
                        <option value="<?php echo $game->term_id; ?>" <?php selected($selected_id, $game->term_id); ?>>
                            <?php echo esc_html($game->name) . ' (ID: ' . $game->term_id . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="sisme-admin-btn sisme-admin-btn-primary">
                    🔍 Inspecter
                </button>
            </form>
        </div>
        
        <?php
    }
    
    private static function render_game_data($game_id) {
        // Récupérer toutes les métadonnées du jeu
        $meta_data = get_term_meta($game_id);
        $term = get_term($game_id, 'post_tag');
        ?>
        
        <div class="sisme-admin-layout">
            <!-- En-tête avec informations du jeu -->
            <div class="sisme-admin-card">
                <div class="sisme-admin-card-header">
                    <h2 class="sisme-admin-heading">📊 Données du jeu : <?php echo esc_html($term->name); ?></h2>
                </div>
                
                <div class="sisme-admin-stats">
                    <div class="sisme-admin-stat-card sisme-admin-stat-card-info">
                        <div class="sisme-admin-stat-number"><?php echo $game_id; ?></div>
                        <div class="sisme-admin-stat-label">ID du terme</div>
                    </div>
                    <div class="sisme-admin-stat-card sisme-admin-stat-card-success">
                        <div class="sisme-admin-stat-number"><?php echo count($meta_data); ?></div>
                        <div class="sisme-admin-stat-label">Métadonnées trouvées</div>
                    </div>
                    <div class="sisme-admin-stat-card">
                        <div class="sisme-admin-stat-number"><?php echo $term->count; ?></div>
                        <div class="sisme-admin-stat-label">Posts associés</div>
                    </div>
                </div>
            </div>

            <!-- Informations du terme -->
            <div class="sisme-admin-card">
                <div class="sisme-admin-card-header">
                    <h3 class="sisme-admin-heading">🏷️ Terme de taxonomie</h3>
                </div>
                
                <div class="sisme-admin-grid-2">
                    <div>
                        <p><strong class="sisme-admin-text-info">Nom :</strong> <?php echo esc_html($term->name); ?></p>
                        <p><strong class="sisme-admin-text-info">Slug :</strong> <code><?php echo esc_html($term->slug); ?></code></p>
                        <p><strong class="sisme-admin-text-info">Description :</strong> <?php echo esc_html($term->description ?: 'Aucune'); ?></p>
                    </div>
                    <div>
                        <p><strong class="sisme-admin-text-info">Taxonomie :</strong> <?php echo esc_html($term->taxonomy); ?></p>
                        <p><strong class="sisme-admin-text-info">Nombre de posts :</strong> <?php echo $term->count; ?></p>
                        <p><strong class="sisme-admin-text-info">Term ID :</strong> <?php echo $term->term_id; ?></p>
                    </div>
                </div>
                
                <div class="sisme-admin-alert sisme-admin-alert-info sisme-admin-mt-md">
                    <strong>Structure complète du terme :</strong>
                    <pre class="sisme-admin-mt-sm sisme-admin-p-md sisme-admin-bg-light-gray sisme-admin-rounded sisme-admin-small" style="max-height: 200px; overflow-y: auto;"><?php echo esc_html(print_r($term, true)); ?></pre>
                </div>
            </div>

            <!-- Métadonnées -->
            <?php if (!empty($meta_data)): ?>
                <?php foreach ($meta_data as $key => $values): ?>
                    <div class="sisme-admin-card">
                        <div class="sisme-admin-card-header">
                            <h3 class="sisme-admin-heading">
                                🔧 Métadonnée : <code class="sisme-admin-bg-blue-light sisme-admin-text-blue-dark sisme-admin-p-sm sisme-admin-rounded"><?php echo esc_html($key); ?></code>
                            </h3>
                        </div>
                        
                        <div class="sisme-admin-flex-col">
                            <div class="sisme-admin-stats">
                                <div class="sisme-admin-stat-card sisme-admin-stat-card-info">
                                    <div class="sisme-admin-stat-number"><?php echo count($values); ?></div>
                                    <div class="sisme-admin-stat-label">Valeur(s) trouvée(s)</div>
                                </div>
                            </div>
                            
                            <?php foreach ($values as $index => $value): ?>
                                <?php $unserialized = maybe_unserialize($value); ?>
                                <div class="sisme-admin-card sisme-admin-bg-light-gray">
                                    <div class="sisme-admin-flex-between sisme-admin-mb-md">
                                        <h4 class="sisme-admin-subtitle">Valeur #<?php echo ($index + 1); ?></h4>
                                        <span class="sisme-admin-badge sisme-admin-badge-secondary">
                                            Type : <?php echo gettype($unserialized); ?>
                                        </span>
                                    </div>
                                    
                                    <!-- Contenu de la valeur -->
                                    <div class="sisme-admin-grid-2">
                                        <div>
                                            <h5 class="sisme-admin-heading">📋 Structure des données</h5>
                                            <pre class="sisme-admin-p-md sisme-admin-bg-white sisme-admin-border sisme-admin-rounded sisme-admin-small" style="max-height: 300px; overflow-y: auto; font-family: 'Courier New', monospace;"><?php
                                                if (is_array($unserialized) || is_object($unserialized)) {
                                                    echo esc_html(print_r($unserialized, true));
                                                } else {
                                                    echo esc_html($value);
                                                }
                                            ?></pre>
                                        </div>
                                        
                                        <div>
                                            <h5 class="sisme-admin-heading">🔧 Représentation sérialisée</h5>
                                            <pre class="sisme-admin-p-md sisme-admin-bg-dark sisme-admin-rounded sisme-admin-small" style="max-height: 300px; overflow-y: auto; font-family: 'Courier New', monospace;"><?php echo esc_html($value); ?></pre>
                                        </div>
                                    </div>
                                    
                                    <!-- Informations supplémentaires -->
                                    <div class="sisme-admin-flex sisme-admin-mt-md">
                                        <span class="sisme-admin-badge sisme-admin-badge-info">
                                            Taille : <?php echo strlen($value); ?> caractères
                                        </span>
                                        <?php if (is_array($unserialized)): ?>
                                            <span class="sisme-admin-badge sisme-admin-badge-success">
                                                <?php echo count($unserialized); ?> éléments
                                            </span>
                                        <?php endif; ?>
                                        <?php if (is_serialized($value)): ?>
                                            <span class="sisme-admin-badge sisme-admin-badge-warning">
                                                Données sérialisées
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="sisme-admin-alert sisme-admin-alert-warning">
                    <p><strong>⚠️ Aucune métadonnée trouvée</strong> pour ce jeu.</p>
                    <p class="sisme-admin-comment sisme-admin-mt-sm">
                        Cela peut indiquer que ce terme n'est pas configuré comme un jeu, ou que les données n'ont pas encore été créées.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php
    }
}