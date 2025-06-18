<?php
/**
 * File: /sisme-games-editor/includes/module-admin-page-table-game-data.php
 * Module: Table Game Data - Sisme Games Editor
 * 
 * Ce module affiche un tableau des étiquettes (jeux) avec leurs données associées
 * stockées dans term_meta. Permet la gestion complète des données de jeux.
 * 
 * Utilisation:
 * 1. Inclure ce fichier
 * 2. Initialiser la classe
 * 3. Appeler render() pour afficher le tableau
 * 
 * Exemples:
 * // Tableau basique
 * $table = new Sisme_Game_Data_Table_Module();
 * $table->render();
 * 
 * // Avec options personnalisées
 * $table = new Sisme_Game_Data_Table_Module([
 *     'per_page' => 20,
 *     'show_actions' => true,
 *     'edit_url' => admin_url('admin.php?page=edit-game-data')
 * ]);
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Game_Data_Table_Module {
    
    private $options = [];
    private $current_page = 1;
    private $per_page = 15;
    private $total_items = 0;
    private $games_data = [];
    private $module_id;
    private static $instance_counter = 0;
    
    // Colonnes par défaut du tableau
    private $default_columns = [
        'name' => 'Nom du jeu',
        'description' => 'Description',
        'articles_count' => 'Articles liés',
        'last_update' => 'Dernière mise à jour',
        'actions' => 'Actions'
    ];
    
    /**
     * Constructeur
     * 
     * @param array $options Options du tableau
     */
    public function __construct($options = []) {
        // Générer un ID unique pour chaque instance
        self::$instance_counter++;
        $this->module_id = 'game-data-table-' . self::$instance_counter;
        
        // Traiter les options
        $this->process_options($options);
        
        // Traiter la pagination
        $this->process_pagination();
        
        // Charger les données
        $this->load_games_data();
    }
    
    /**
     * Traiter les options du tableau
     */
    private function process_options($options) {
        $default_options = [
            'per_page' => 15,
            'show_actions' => true,
            'edit_url' => admin_url('admin.php?page=sisme-games-edit-test'),
            'columns' => $this->default_columns,
            'show_pagination' => true,
            'show_search' => true,
            'show_add_button' => true
        ];
        
        $this->options = wp_parse_args($options, $default_options);
        $this->per_page = $this->options['per_page'];
    }
    
    /**
     * Traiter la pagination
     */
    private function process_pagination() {
        $this->current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    }
    
    /**
     * Charger les données des jeux depuis les étiquettes et term_meta
     */
    private function load_games_data() {
        // Récupérer le terme de recherche si présent
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Arguments de base pour récupérer les étiquettes
        $args = [
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => 0, // Récupérer tous pour pouvoir filtrer
        ];
        
        // Ajouter la recherche si présente
        if (!empty($search)) {
            $args['search'] = $search;
        }
        
        // Récupérer toutes les étiquettes
        $all_tags = get_terms($args);
        
        if (is_wp_error($all_tags)) {
            $this->games_data = [];
            $this->total_items = 0;
            return;
        }
        
        // Traiter les données pour chaque étiquette
        $processed_data = [];
        foreach ($all_tags as $tag) {
            $game_data = $this->process_tag_data($tag);
            if ($game_data) {
                $processed_data[] = $game_data;
            }
        }
        
        // Calculer la pagination
        $this->total_items = count($processed_data);
        $offset = ($this->current_page - 1) * $this->per_page;
        $this->games_data = array_slice($processed_data, $offset, $this->per_page);
    }
    
    /**
     * Traiter les données d'une étiquette
     */
    private function process_tag_data($tag) {
        $game_data = [
            'id' => $tag->term_id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'description' => '',
            'articles_count' => $tag->count,
            'last_update' => '',
            'meta_data' => []
        ];
        
        // Récupérer toutes les métadonnées de l'étiquette
        $all_meta = get_term_meta($tag->term_id);
        
        // Traiter les métadonnées spécifiques
        foreach ($all_meta as $meta_key => $meta_values) {
            if (is_array($meta_values) && count($meta_values) > 0) {
                $value = $meta_values[0];
                
                switch ($meta_key) {
                    case 'game_description':
                    case 'description':
                        $game_data['description'] = wp_trim_words($value, 15);
                        break;
                    case 'last_update':
                        $game_data['last_update'] = $value;
                        break;
                    default:
                        $game_data['meta_data'][$meta_key] = $value;
                        break;
                }
            }
        }
        
        // Si pas de dernière mise à jour, utiliser la date de création des articles liés
        if (empty($game_data['last_update']) && $tag->count > 0) {
            $recent_post = get_posts([
                'tag_id' => $tag->term_id,
                'posts_per_page' => 1,
                'orderby' => 'modified',
                'order' => 'DESC',
                'fields' => 'ids'
            ]);
            
            if (!empty($recent_post)) {
                $game_data['last_update'] = get_the_modified_date('Y-m-d H:i', $recent_post[0]);
            }
        }
        
        return $game_data;
    }
    
    /**
     * Afficher le formulaire de recherche
     */
    private function render_search_form() {
        $search_value = isset($_GET['s']) ? esc_attr($_GET['s']) : '';
        ?>
        <div class="search-form-container" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <form method="get" style="display: flex; gap: 10px;">
                <?php
                // Préserver les paramètres existants
                foreach ($_GET as $key => $value) {
                    if ($key !== 's' && $key !== 'paged') {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                    }
                }
                ?>
                <input type="text" 
                       name="s" 
                       value="<?php echo $search_value; ?>" 
                       placeholder="Rechercher un jeu..."
                       class="regular-text">
                <button type="submit" class="button">🔍 Rechercher</button>
                <?php if (!empty($search_value)): ?>
                    <a href="<?php echo remove_query_arg(['s', 'paged']); ?>" class="button">✖️ Effacer</a>
                <?php endif; ?>
            </form>
            
            <?php if ($this->options['show_add_button']): ?>
                <div style="display: flex; gap: 10px;">
                    <a href="<?php echo $this->options['edit_url']; ?>" class="button button-primary">
                        ➕ Créer un nouveau jeu
                    </a>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=post_tag'); ?>" class="button">
                        🏷️ Gérer les étiquettes
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Afficher l'en-tête du tableau
     */
    private function render_table_header() {
        ?>
        <thead>
            <tr>
                <?php foreach ($this->options['columns'] as $column_key => $column_label): ?>
                    <th class="column-<?php echo esc_attr($column_key); ?>">
                        <?php echo esc_html($column_label); ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <?php
    }
    
    /**
     * Afficher une ligne du tableau
     */
    private function render_table_row($game_data) {
        ?>
        <tr>
            <?php foreach (array_keys($this->options['columns']) as $column_key): ?>
                <td class="column-<?php echo esc_attr($column_key); ?>">
                    <?php $this->render_column_content($column_key, $game_data); ?>
                </td>
            <?php endforeach; ?>
        </tr>
        
        <!-- Ligne supplémentaire pour les covers -->
        <tr style="background: #f9f9f9;">
            <td colspan="<?php echo count($this->options['columns']); ?>" style="padding: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <strong style="color: #666;">Covers :</strong>
                    
                    <div style="display: flex; flex: 1; justify-content: flex-start; align-items: center; margin-left: 20px; gap: 20px;">

                        <?php
                        $covers = ['cover_main', 'cover_news', 'cover_patch', 'cover_test'];
                        $cover_labels = [
                            'cover_main' => 'Principale',
                            'cover_news' => 'News', 
                            'cover_patch' => 'Patch',
                            'cover_test' => 'Test'
                        ];
                        
                        foreach ($covers as $cover_type) {
                            $cover_id = isset($game_data['meta_data'][$cover_type]) ? $game_data['meta_data'][$cover_type] : '';
                            ?>
                            <div style="text-align: center; flex: 1;">
                                <div style="font-size: 11px; color: #666; margin-bottom: 5px; font-weight: 500;">
                                    <?php echo $cover_labels[$cover_type]; ?>
                                </div>
                                <?php if (!empty($cover_id)): ?>
                                    <?php
                                    $image = wp_get_attachment_image_src($cover_id, 'large');
                                    if ($image):
                                    ?>
                                        <img src="<?php echo esc_url($image[0]); ?>" 
                         style="gap: 15px; max-width: 100%; height: auto; border-radius: 6px; border: 2px solid #ddd; box-shadow: 0 2px 4px rgba(0,0,0,0.1);"
                         title="<?php echo esc_attr(get_the_title($cover_id)); ?>">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #e0e0e0; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 14px; color: #999; margin: 0 auto;">❌</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div style="width: 50px; height: 50px; background: #f8f8f8; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #ccc; border: 2px dashed #ddd; margin: 0 auto;">📷</div>
                                <?php endif; ?>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Afficher le contenu d'une colonne
     */
    private function render_column_content($column_key, $game_data) {
        switch ($column_key) {
            case 'name':
                ?>
                <strong><?php echo esc_html($game_data['name']); ?></strong>
                <div style="font-size: 12px; color: #666;">
                    ID: <?php echo $game_data['id']; ?> | Slug: <?php echo esc_html($game_data['slug']); ?>
                </div>
                <?php
                break;
                
            case 'description':
                if (!empty($game_data['description'])) {
                    echo '<div style="max-width: 300px;">' . esc_html($game_data['description']) . '</div>';
                } else {
                    echo '<em style="color: #999;">Aucune description</em>';
                }
                break;
                
            case 'articles_count':
                if ($game_data['articles_count'] > 0) {
                    ?>
                    <span class="articles-count" style="background: #e7f3e7; padding: 3px 8px; border-radius: 12px; font-size: 12px;">
                        📄 <?php echo $game_data['articles_count']; ?> article<?php echo $game_data['articles_count'] > 1 ? 's' : ''; ?>
                    </span>
                    <?php
                } else {
                    echo '<span style="color: #999;">Aucun article</span>';
                }
                break;
                
            case 'last_update':
                if (!empty($game_data['last_update'])) {
                    $date = date_create($game_data['last_update']);
                    if ($date) {
                        echo '<span style="font-size: 12px;">' . date_format($date, 'd/m/Y H:i') . '</span>';
                    } else {
                        echo '<span style="color: #999;">-</span>';
                    }
                } else {
                    echo '<span style="color: #999;">Jamais</span>';
                }
                break;
                
            case 'actions':
                if ($this->options['show_actions']) {
                    ?>
                    <div style="display: flex; gap: 5px;">
                        <a href="<?php echo add_query_arg('tag_id', $game_data['id'], $this->options['edit_url']); ?>" 
                           class="button button-small">✏️</a>
                        
                        <?php if ($game_data['articles_count'] > 0): ?>
                            <a href="<?php echo admin_url('admin.php?page=sisme-games-all-articles&s=' . urlencode($game_data['name'])); ?>" 
                               class="button button-small" 
                               title="Voir les articles de ce jeu">📄</a>
                        <?php endif; ?>
                        
                        <button type="button" 
                                class="button button-small delete-game-data" 
                                data-game-id="<?php echo $game_data['id']; ?>"
                                data-game-name="<?php echo esc_attr($game_data['name']); ?>"
                                style="background-color: #f8d7da;">🗑️</button>
                    </div>
                    <?php
                }
                break;
                
            default:
                // Pour les colonnes personnalisées, vérifier dans les meta_data
                if (isset($game_data['meta_data'][$column_key])) {
                    echo esc_html($game_data['meta_data'][$column_key]);
                } else {
                    echo '<span style="color: #999;">-</span>';
                }
                break;
        }
    }
    
    /**
     * Afficher la pagination
     */
    private function render_pagination() {
        if (!$this->options['show_pagination'] || $this->total_items <= $this->per_page) {
            return;
        }
        
        $total_pages = ceil($this->total_items / $this->per_page);
        $current_page = $this->current_page;
        
        ?>
        <div class="pagination-wrapper" style="margin-top: 20px; text-align: center;">
            <div class="pagination-info" style="margin-bottom: 10px; color: #666;">
                Affichage de <?php echo (($current_page - 1) * $this->per_page) + 1; ?> à 
                <?php echo min($current_page * $this->per_page, $this->total_items); ?> 
                sur <?php echo $this->total_items; ?> jeu<?php echo $this->total_items > 1 ? 'x' : ''; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination-links">
                    <?php
                    $base_url = remove_query_arg('paged');
                    
                    // Bouton précédent
                    if ($current_page > 1) {
                        echo '<a href="' . add_query_arg('paged', $current_page - 1, $base_url) . '" class="button">‹ Précédent</a> ';
                    }
                    
                    // Numéros de pages
                    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
                        if ($i == $current_page) {
                            echo '<span class="button button-primary" style="margin: 0 2px;">' . $i . '</span> ';
                        } else {
                            echo '<a href="' . add_query_arg('paged', $i, $base_url) . '" class="button" style="margin: 0 2px;">' . $i . '</a> ';
                        }
                    }
                    
                    // Bouton suivant
                    if ($current_page < $total_pages) {
                        echo '<a href="' . add_query_arg('paged', $current_page + 1, $base_url) . '" class="button">Suivant ›</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Afficher le JavaScript pour les actions
     */
    private function render_javascript() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Gestion de la suppression
            $('.delete-game-data').on('click', function(e) {
                e.preventDefault();
                
                var gameId = $(this).data('game-id');
                var gameName = $(this).data('game-name');
                
                if (confirm('Êtes-vous sûr de vouloir supprimer toutes les données du jeu "' + gameName + '" ?\n\nAttention : Cette action est irréversible !')) {
                    // TODO: Implémenter la suppression AJAX
                    alert('Suppression non encore implémentée.\nGameID: ' + gameId);
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Afficher le tableau complet
     */
    public function render() {
        ?>
        <div class="game-data-table-module" id="<?php echo esc_attr($this->module_id); ?>">
            
            <?php if ($this->options['show_search']): ?>
                <?php $this->render_search_form(); ?>
            <?php endif; ?>
            
            <?php if (empty($this->games_data)): ?>
                <div class="no-data-message" style="text-align: center; padding: 40px; background: #f9f9f9; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin: 0 0 10px; color: #666;">🎮 Aucun jeu trouvé</h3>
                    <?php if (isset($_GET['s']) && !empty($_GET['s'])): ?>
                        <p>Aucun résultat pour "<?php echo esc_html($_GET['s']); ?>"</p>
                        <a href="<?php echo remove_query_arg(['s', 'paged']); ?>" class="button">Voir tous les jeux</a>
                    <?php else: ?>
                        <p>Commencez par créer des étiquettes de jeux ou ajouter des données.</p>
                        <a href="<?php echo $this->options['edit_url']; ?>" class="button button-primary">
                            ➕ Ajouter un jeu
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                
                <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
                    <?php $this->render_table_header(); ?>
                    
                    <tbody>
                        <?php foreach ($this->games_data as $game_data): ?>
                            <?php $this->render_table_row($game_data); ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php $this->render_pagination(); ?>
                
            <?php endif; ?>
            
        </div>
        
        <?php $this->render_javascript(); ?>
        <?php
    }
    
    /**
     * Obtenir les statistiques des données
     */
    public function get_stats() {
        // Récupérer toutes les étiquettes avec métadonnées
        $all_tags = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
        ]);
        
        $stats = [
            'total_games' => 0,
            'games_with_data' => 0,
            'games_with_articles' => 0,
            'total_articles' => 0
        ];
        
        if (!is_wp_error($all_tags)) {
            $stats['total_games'] = count($all_tags);
            
            foreach ($all_tags as $tag) {
                $meta = get_term_meta($tag->term_id);
                if (!empty($meta)) {
                    $stats['games_with_data']++;
                }
                
                if ($tag->count > 0) {
                    $stats['games_with_articles']++;
                    $stats['total_articles'] += $tag->count;
                }
            }
        }
        
        return $stats;
    }
}