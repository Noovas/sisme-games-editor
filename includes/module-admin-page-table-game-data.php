<?php
/**
 * File: /sisme-games-editor/includes/module-admin-page-table-game-data.php
 * Module: Table Game Data - Version ÉPURÉE sans module de filtre
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
        'game' => 'Jeu',
        'actions' => 'Actions'
    ];
    
    /**
     * Constructeur - SIMPLIFIÉ
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
            'per_page' => -1,  // Tous par défaut
            'show_actions' => true,
            'edit_url' => admin_url('admin.php?page=sisme-games-edit-game-data'),
            'columns' => $this->default_columns,
            'show_pagination' => false,  // Désactivé par défaut
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
     * Charger les données des jeux - SIMPLIFIÉ
     */
    private function load_games_data() {
        // Arguments de base pour récupérer les étiquettes
        $args = [
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => 0, // Tous
        ];
        
        // Ajouter recherche simple si présente
        if (!empty($_GET['s'])) {
            $args['search'] = sanitize_text_field($_GET['s']);
        }
        
        // Récupérer les étiquettes
        $tags = get_terms($args);
        
        if (is_wp_error($tags)) {
            $this->games_data = [];
            $this->total_items = 0;
            return;
        }
        
        // Traiter les données pour chaque étiquette
        $processed_data = [];
        foreach ($tags as $tag) {
            $game_data = $this->process_tag_data($tag);
            if ($game_data) {
                $processed_data[] = $game_data;
            }
        }
        
        // Pagination si activée
        $this->total_items = count($processed_data);
        
        if ($this->per_page > 0) {
            $offset = ($this->current_page - 1) * $this->per_page;
            $processed_data = array_slice($processed_data, $offset, $this->per_page);
        }
        
        $this->games_data = $processed_data;
    }
    
    /**
     * Traiter les données d'une étiquette
     */
    private function process_tag_data($tag) {
        // Récupérer toutes les métadonnées du jeu
        $meta_data = [];
        $meta_keys = [
            'game_description', 'game_genres', 'game_modes', 'game_developers',
            'game_publishers', 'game_platforms', 'release_date', 'external_links',
            'trailer_link', 'cover_main', 'cover_news', 'cover_patch', 'cover_test',
            'screenshots', 'game_sections', 'last_update'
        ];
        
        foreach ($meta_keys as $key) {
            $meta_data[$key] = get_term_meta($tag->term_id, $key, true);
        }
        
        // Compter les articles liés
        $articles_count = $this->get_tag_posts_count($tag->term_id);
        
        // Préparer les données de base
        $game_data = [
            'id' => $tag->term_id,
            'name' => $tag->name,
            'slug' => $tag->slug,
            'description' => $tag->description,
            'articles_count' => $articles_count,
            'meta_data' => $meta_data,
            'last_update' => ''
        ];
        
        // Traiter certaines métadonnées spécifiques
        if (!empty($meta_data['game_description'])) {
            $game_data['description'] = wp_trim_words($meta_data['game_description'], 15);
        }
        
        if (!empty($meta_data['last_update'])) {
            $game_data['last_update'] = $meta_data['last_update'];
        }
        
        return $game_data;
    }
    
    /**
     * Compter les articles pour un tag
     */
    private function get_tag_posts_count($tag_id) {
        $posts = get_posts([
            'tag_id' => $tag_id,
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'private'],
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
        
        return count($posts);
    }
    
    /**
     * Vérifier si un jeu a une présentation
     */
    private function game_has_presentation($tag_id) {
        // Chercher un article avec des sections
        $posts_with_sections = get_posts([
            'tag_id' => $tag_id,
            'post_type' => 'post',
            'post_status' => ['publish', 'draft', 'private'],
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_sisme_game_sections',
                    'compare' => 'EXISTS'
                ]
            ],
            'fields' => 'ids'
        ]);
        
        return !empty($posts_with_sections);
    }
    
    /**
     * Rendre le tableau principal
     */
    public function render() {
        ?>
        <div class="sisme-game-data-table-module" id="<?php echo esc_attr($this->module_id); ?>">
            
            <?php if (empty($this->games_data)): ?>
                <div class="sisme-no-data-message">
                    <h3 class="sisme-no-data-title">Aucun jeu trouvé</h3>
                    <?php if (!empty($_GET['s'])): ?>
                        <p class="sisme-no-data-search">Aucun résultat pour "<?php echo esc_html($_GET['s']); ?>"</p>
                        <a href="<?php echo remove_query_arg(['s', 'paged']); ?>" class="sisme-btn sisme-btn--secondary">Voir tous les jeux</a>
                    <?php else: ?>
                        <p class="sisme-no-data-empty">Commencez par créer des jeux.</p>
                        <a href="<?php echo $this->options['edit_url']; ?>" class="sisme-btn sisme-btn--primary">
                            ➕ Ajouter un jeu
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                
                <table class="sisme-game-data-table">
                    <tbody class="sisme-table-body">
                        <?php foreach ($this->games_data as $index => $game_data): ?>
                            <?php $this->render_table_row($game_data); ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
            <?php endif; ?>
            
        </div>
        <?php
    }
    
    /**
     * Rendre une ligne du tableau
     */
    private function render_table_row($game_data) {
        ?>
        <!-- Ligne principale avec nom du jeu + actions -->
        <tr class="sisme-game-data-main-row">
            <!-- Nom du jeu (colonne principale) -->
            <td class="sisme-game-header-cell">
                <div class="sisme-game-header">
                    <h3 class="sisme-game-title"><?php echo esc_html($game_data['name']); ?></h3>
                    <div class="sisme-game-meta">
                        <span class="sisme-game-id">ID: <?php echo $game_data['id']; ?></span>
                        <span class="sisme-game-slug">Slug: <?php echo esc_html($game_data['slug']); ?></span>
                        <span class="sisme-game-articles">
                            <?php if ($game_data['articles_count'] > 0): ?>
                                📄 <?php echo $game_data['articles_count']; ?> article<?php echo $game_data['articles_count'] > 1 ? 's' : ''; ?>
                            <?php else: ?>
                                Aucun article
                            <?php endif; ?>
                        </span>
                        <?php if (!empty($game_data['last_update'])): ?>
                            <span class="sisme-last-update">
                                Modifié: <?php echo date('d/m/Y H:i', strtotime($game_data['last_update'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            
            <!-- Actions (colonne droite) -->
            <td class="sisme-game-actions-cell">
                <div class="sisme-game-actions">
                    <a href="<?php echo add_query_arg('tag_id', $game_data['id'], $this->options['edit_url']); ?>"
                        class="sisme-action-btn sisme-action-edit" 
                        data-sisme-tooltip="Modifier les données du jeu">✏️</a>

                        <?php
                        // Choix de l'équipe en bouton switch
                        $is_team_choice = get_term_meta($game_data['id'], 'is_team_choice', true) === '1';
                        $heart_class = $is_team_choice ? 'team-choice-active' : 'team-choice-inactive';
                        $heart_icon = $is_team_choice ? '💖' : '🤍';?>
                        <button type="button"
                            class="sisme-action-btn team-choice-btn <?php echo $heart_class; ?>" 
                            data-game-id="<?php echo $game_data['id']; ?>" 
                            data-team-choice="<?php echo ($is_team_choice ? '1' : '0'); ?>" 
                            data-sisme-tooltip="<?php echo ($is_team_choice ? 'Retirer des choix équipe' : 'Ajouter aux choix équipe'); ?>">
                            <?php echo $heart_icon; ?>
                        </button>

                        <?php
                        // Vérifier si le jeu a des sections (présentation)
                        $has_presentation = $this->game_has_presentation($game_data['id']);
                        $fiche_url = admin_url('admin.php?page=sisme-games-edit-fiche-jeu&tag_id=' . $game_data['id']);

                        // Classes et textes conditionnels
                        if ($has_presentation) {
                            $btn_class = 'sisme-action-btn sisme-action-fiche sisme-has-presentation';
                            $btn_tooltip = 'Modifier la fiche existante';
                            $btn_icon = '📝'
                        } else {
                            $btn_class = 'sisme-action-btn sisme-action-fiche sisme-no-presentation';
                            $btn_tooltip = 'Créer une nouvelle fiche';
                            $btn_icon = '📄'
                        }
                        ?>

                        <a href="<?php echo esc_url($fiche_url); ?>" 
                           class="<?php echo esc_attr($btn_class); ?>"
                           data-sisme-tooltip="<?php echo esc_attr($btn_tooltip); ?>">
                            <?php echo $btn_icon; ?>
                        </a>
                        
                        <button type="button" 
                                class="sisme-action-btn sisme-action-delete delete-game-data" 
                                data-game-id="<?php echo $game_data['id']; ?>"
                                data-game-name="<?php echo esc_attr($game_data['name']); ?>"
                                data-sisme-tooltip="Supprimer définitivement ce jeu"
                                data-sisme-tooltip-type="error">💀</button>
                </div>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Obtenir les statistiques des données
     */
    public function get_stats() {
        // Récupérer tous les jeux pour les stats
        $all_tags = get_terms([
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'number' => 0
        ]);
        
        $stats = [
            'total_games' => 0,
            'games_with_data' => 0,
            'games_with_fiches' => 0,
            'total_articles' => 0
        ];
        
        if (!is_wp_error($all_tags)) {
            $stats['total_games'] = count($all_tags);
            
            foreach ($all_tags as $tag) {
                // Vérifier si le jeu a des données
                $has_data = get_term_meta($tag->term_id, 'game_description', true);
                if (!empty($has_data)) {
                    $stats['games_with_data']++;
                }
                
                // Vérifier si le jeu a des fiches
                if ($this->game_has_presentation($tag->term_id)) {
                    $stats['games_with_fiches']++;
                }
                
                // Compter les articles
                $stats['total_articles'] += $this->get_tag_posts_count($tag->term_id);
            }
        }
        
        return $stats;
    }
}