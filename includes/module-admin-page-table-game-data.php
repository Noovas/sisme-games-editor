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

require_once plugin_dir_path(__FILE__) . 'module-admin-page-filtre-article.php';

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

        // Créer le module de filtre
        $this->filter_module = new Sisme_Article_Filter_Module([
            'search' => true,
            'genres' => true,
            'platforms' => true
        ], 'game_data');
        
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
        // Récupérer les arguments de filtre
        $filter_args = $this->filter_module->get_game_data_filter_args();
        
        // Arguments de base pour récupérer TOUTES les étiquettes d'abord
        $args = [
            'taxonomy' => 'post_tag',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
            'number' => 0, // Récupérer tous
        ];
        
        // Récupérer TOUTES les étiquettes (pas de filtre search ici)
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

        // MAINTENANT appliquer les filtres en PHP
        
        // Filtrer par recherche de nom (en PHP, pas avec get_terms)
        if (!empty($filter_args['search'])) {
            $search_term = strtolower($filter_args['search']);
            $processed_data = array_filter($processed_data, function($game) use ($search_term) {
                return strpos(strtolower($game['name']), $search_term) !== false;
            });
        }

        // Filtrer par genre
        if (!empty($filter_args['genres'])) {
            $processed_data = array_filter($processed_data, function($game) use ($filter_args) {
                $genres = isset($game['meta_data']['game_genres']) && is_array($game['meta_data']['game_genres']) 
                    ? $game['meta_data']['game_genres'] : [];
                return in_array($filter_args['genres'], $genres);
            });
        }

        // Filtrer par développeur/éditeur
        if (!empty($filter_args['developers'])) {
            $processed_data = array_filter($processed_data, function($game) use ($filter_args) {
                // Récupérer les développeurs
                $developers = isset($game['meta_data']['game_developers']) && is_array($game['meta_data']['game_developers']) 
                    ? $game['meta_data']['game_developers'] : [];
                
                // Récupérer les éditeurs  
                $publishers = isset($game['meta_data']['game_publishers']) && is_array($game['meta_data']['game_publishers']) 
                    ? $game['meta_data']['game_publishers'] : [];
                
                // Fusionner développeurs et éditeurs
                $all_entities = array_merge($developers, $publishers);
                
                // Chercher dans les deux
                return in_array($filter_args['developers'], $all_entities);
            });
        }

        // Filtrer par plateforme
        if (!empty($filter_args['platforms'])) {
            $processed_data = array_filter($processed_data, function($game) use ($filter_args) {
                $platforms = isset($game['meta_data']['game_platforms']) && is_array($game['meta_data']['game_platforms']) 
                    ? $game['meta_data']['game_platforms'] : [];
                return in_array($filter_args['platforms'], $platforms);
            });
        }

        // Calculer la pagination APRÈS tous les filtres
        $this->total_items = count($processed_data);
        
        // Appliquer la pagination seulement si on en a besoin
        if ($this->per_page > 0) {
            $offset = ($this->current_page - 1) * $this->per_page;
            $this->games_data = array_slice($processed_data, $offset, $this->per_page);
        } else {
            // Pas de pagination (per_page = -1)
            $this->games_data = $processed_data;
        }
    }
    
    /**
     * Traiter les données d'une étiquette (d'un jeu)
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
                        
                    case 'cover_main':
                    case 'cover_news':
                    case 'cover_patch':
                    case 'cover_test':
                        $game_data['meta_data'][$meta_key] = $value;
                        break;
                        
                    case 'game_genres':
                        $game_data['meta_data']['game_genres'] = maybe_unserialize($value);
                        break;

                    case 'game_modes':
                        $game_data['meta_data']['game_modes'] = maybe_unserialize($value);
                        break;

                    case 'game_developers':
                        $game_data['meta_data']['game_developers'] = maybe_unserialize($value);
                        break;
                        
                    case 'game_publishers':
                        $game_data['meta_data']['game_publishers'] = maybe_unserialize($value);
                        break;

                    case 'game_platforms':
                        $game_data['meta_data']['game_platforms'] = maybe_unserialize($value);
                        break;
                        
                    case 'release_date':
                        $game_data['meta_data']['release_date'] = $value;
                        break;
                        
                    case 'external_links':
                        $game_data['meta_data']['external_links'] = maybe_unserialize($value);
                        break;
                                            
                    default:
                        // Stocker les autres métadonnées
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
        <div class="sisme-search-form-container">
            <form method="get" class="sisme-search-form">
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
                       class="sisme-search-input regular-text">
                <button type="submit" class="button sisme-search-btn">🔍 Rechercher</button>
                <?php if (!empty($search_value)): ?>
                    <a href="<?php echo remove_query_arg(['s', 'paged']); ?>" class="button sisme-search-clear">✖️ Effacer</a>
                <?php endif; ?>
            </form>
            
            <?php if ($this->options['show_add_button']): ?>
                <div class="sisme-search-actions">
                    <a href="<?php echo $this->options['edit_url']; ?>" class="button button-primary sisme-add-game-btn">
                        ➕ Créer un nouveau jeu
                    </a>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=post_tag'); ?>" class="button sisme-manage-tags-btn">
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
        ?><thead class="sisme-table-header"></thead><?php
    }

    /**
     * Tableau
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
                                <span class="sisme-articles-count-badge">
                                    📄 <?php echo $game_data['articles_count']; ?> article<?php echo $game_data['articles_count'] > 1 ? 's' : ''; ?>
                                </span>
                            <?php else: ?>
                                <span class="sisme-no-articles-badge">Aucun article</span>
                            <?php endif; ?>
                        </span>
                        <?php if (!empty($game_data['last_update'])): ?>
                            <?php $date = date_create($game_data['last_update']); ?>
                            <?php if ($date): ?>
                                <span class="sisme-last-update-badge">
                                    Modifié: <?php echo date_format($date, 'd/m/Y H:i'); ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            
            <!-- Actions (colonne droite) -->
            <td class="sisme-game-actions-cell">
                <div class="sisme-game-actions">
                    <a href="<?php echo add_query_arg('tag_id', $game_data['id'], $this->options['edit_url']); ?>" 
                       class="sisme-action-btn sisme-action-edit" 
                       title="Modifier">✏️</a>
                    
                    <?php if ($game_data['articles_count'] > 0): ?>
                        <a href="<?php echo admin_url('admin.php?page=sisme-games-all-articles&s=' . urlencode($game_data['name'])); ?>" 
                           class="sisme-action-btn sisme-action-view-articles" 
                           title="Voir les articles de ce jeu">📄</a>
                    <?php endif; ?>
                    
                    <button type="button" 
                            class="sisme-action-btn sisme-action-delete delete-game-data" 
                            data-game-id="<?php echo $game_data['id']; ?>"
                            data-game-name="<?php echo esc_attr($game_data['name']); ?>"
                            title="Supprimer">🗑️</button>
                </div>
            </td>
        </tr>

        <!-- Description (si elle existe) -->
        <?php if (!empty($game_data['description'])): ?>
        <tr class="sisme-game-data-description-row">
            <td colspan="2" class="sisme-game-data-description-cell">
                <div class="sisme-game-description-container">
                    <strong class="sisme-game-description-label">Description :</strong>
                    <div class="sisme-game-description-content">
                        <?php echo esc_html($game_data['description']); ?>
                    </div>
                </div>
            </td>
        </tr>
        <?php endif; ?>

        <!-- Ligne des genres ET modes -->
        <tr class="sisme-game-data-genres-row">
            <td colspan="2" class="sisme-game-data-genres-cell">
                <div class="sisme-game-data-genres-container">
                    
                    <!-- Colonne Genres -->
                    <div class="sisme-genres-column">
                        <strong class="sisme-game-data-genres-label">Genres :</strong>
                        <div class="sisme-game-data-genres-list">
                            <?php 
                            $genres = isset($game_data['meta_data']['game_genres']) ? $game_data['meta_data']['game_genres'] : array();
                            if (!empty($genres) && is_array($genres)):
                                foreach ($genres as $genre_id):
                                    $genre = get_category($genre_id);
                                    if ($genre && !is_wp_error($genre)):
                                        $genre_name = str_replace('jeux-', '', $genre->name ?? '');
                            ?>
                                <span class="sisme-genre-tag">
                                    <?php echo esc_html($genre_name); ?>
                                </span>
                            <?php 
                                    endif;
                                endforeach;
                            else:
                            ?>
                                <span class="sisme-no-genres">Aucun genre défini</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Colonne Modes -->
                    <div class="sisme-modes-column">
                        <strong class="sisme-game-data-modes-label">Modes :</strong>
                        <div class="sisme-game-data-modes-list">
                            <?php 
                            $modes = isset($game_data['meta_data']['game_modes']) ? $game_data['meta_data']['game_modes'] : array();
                            $mode_labels = [
                                'solo' => 'Solo',
                                'multijoueur' => 'Multijoueur',
                                'cooperatif' => 'Coopératif', 
                                'competitif' => 'Compétitif'
                            ];
                            
                            if (!empty($modes) && is_array($modes)):
                                foreach ($modes as $mode_key):
                                    if (isset($mode_labels[$mode_key])):
                            ?>
                                <span class="sisme-mode-tag">
                                    <?php echo esc_html($mode_labels[$mode_key]); ?>
                                </span>
                            <?php 
                                    endif;
                                endforeach;
                            else:
                            ?>
                                <span class="sisme-no-modes">Aucun mode défini</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </td>
        </tr>

        <!-- Ligne des développeurs ET éditeurs -->
        <tr class="sisme-game-data-entities-row">
            <td colspan="2" class="sisme-game-data-entities-cell">
                <div class="sisme-game-data-entities-container">
                    
                    <!-- Colonne Développeurs -->
                    <div class="sisme-developers-column">
                        <strong class="sisme-game-data-developers-label">Développeurs :</strong>
                        <div class="sisme-game-data-developers-list">
                            <?php 
                            $developers = isset($game_data['meta_data']['game_developers']) ? $game_data['meta_data']['game_developers'] : array();
                            if (!empty($developers) && is_array($developers)):
                                foreach ($developers as $developer_id):
                                    $developer = get_category($developer_id);
                                    if ($developer && !is_wp_error($developer)):
                                        $developer_website = get_term_meta($developer_id, 'entity_website', true);
                            ?>
                                <span class="sisme-developer-tag">
                                    <?php if (!empty($developer_website)): ?>
                                        <a href="<?php echo esc_url($developer_website); ?>" 
                                           target="_blank" 
                                           class="sisme-developer-link"
                                           title="Site web de <?php echo esc_attr($developer->name); ?>"
                                           alt="<?php echo esc_attr($developer->name); ?>">
                                            <?php echo esc_html($developer->name); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="sisme-developer-name"><?php echo esc_html($developer->name); ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php 
                                    endif;
                                endforeach;
                            else:
                            ?>
                                <span class="sisme-no-developers">Aucun développeur défini</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Colonne Éditeurs -->
                    <div class="sisme-publishers-column">
                        <strong class="sisme-game-data-publishers-label">Éditeurs :</strong>
                        <div class="sisme-game-data-publishers-list">
                            <?php 
                            $publishers = isset($game_data['meta_data']['game_publishers']) ? $game_data['meta_data']['game_publishers'] : array();
                            if (!empty($publishers) && is_array($publishers)):
                                foreach ($publishers as $publisher_id):
                                    $publisher = get_category($publisher_id);
                                    if ($publisher && !is_wp_error($publisher)):
                                        $publisher_website = get_term_meta($publisher_id, 'entity_website', true);
                            ?>
                                <span class="sisme-publisher-tag">
                                    <?php if (!empty($publisher_website)): ?>
                                        <a href="<?php echo esc_url($publisher_website); ?>" 
                                           target="_blank" 
                                           class="sisme-publisher-link"
                                           title="Site web de <?php echo esc_attr($publisher->name); ?>"
                                           alt="<?php echo esc_attr($publisher->name); ?>">
                                            <?php echo esc_html($publisher->name); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="sisme-publisher-name"><?php echo esc_html($publisher->name); ?></span>
                                    <?php endif; ?>
                                </span>
                            <?php 
                                    endif;
                                endforeach;
                            else:
                            ?>
                                <span class="sisme-no-publishers">Aucun éditeur défini</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
            </td>
        </tr>
        <!-- Plateformes, date de sortie et liens externes -->
        <tr style="background: #f4f4f4;">
            <td colspan="<?php echo count($this->options['columns']); ?>" style="padding: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; gap: 30px;">
                    
                    <!-- Plateformes -->
                    <div style="flex: 1;">
                        <strong style="color: #666;">Plateformes :</strong>
                        <?php 
                        $platforms = isset($game_data['meta_data']['game_platforms']) ? $game_data['meta_data']['game_platforms'] : [];
                        if (!empty($platforms)) {
                            $platform_groups = [
                                'Mobile' => ['ios', 'android'],
                                'Console' => ['xbox', 'playstation', 'switch'], 
                                'PC' => ['pc', 'web', 'mac', 'windows']
                            ];
                            
                            $displayed_groups = [];
                            $platform_details = [];
                            
                            foreach ($platform_groups as $group => $group_platforms) {
                                $group_matches = array_intersect($platforms, $group_platforms);
                                if (!empty($group_matches)) {
                                    $displayed_groups[] = $group;
                                    $platform_details[$group] = $group_matches;
                                }
                            }
                            
                            if (!empty($displayed_groups)) {
                                foreach ($displayed_groups as $group) {
                                    $details = implode(', ', array_map('ucfirst', $platform_details[$group]));
                                    echo '<span style="background: #e1f5fe; padding: 4px 8px; border-radius: 4px; margin-right: 8px; cursor: help;" title="' . esc_attr($details) . '">';
                                    echo esc_html($group);
                                    echo '</span>';
                                }
                            }
                        } else {
                            echo '<span style="color: #999;">Non spécifiées</span>';
                        }
                        ?>
                    </div>
                    
                    <!-- Date de sortie -->
                    <div style="flex: 1;">
                        <strong style="color: #666;">Date de sortie :</strong>
                        <?php 
                        $release_date = isset($game_data['meta_data']['release_date']) ? $game_data['meta_data']['release_date'] : '';
                        if (!empty($release_date)) {
                            $formatted_date = date_i18n('j F Y', strtotime($release_date));
                            echo '<span style="background: #fff3e0; padding: 4px 8px; border-radius: 4px;">' . esc_html($formatted_date) . '</span>';
                        } else {
                            echo '<span style="color: #999;">Non spécifiée</span>';
                        }
                        ?>
                    </div>
                    
                    <!-- Liens externes -->
                    <div style="flex: 1;">
                        <strong style="color: #666;">Liens de vente :</strong>
                        <?php 
                        $external_links = isset($game_data['meta_data']['external_links']) ? $game_data['meta_data']['external_links'] : [];
                        if (!empty($external_links)) {
                            $link_labels = ['steam' => 'Steam', 'epic' => 'Epic', 'gog' => 'GOG'];
                            $link_count = 0;
                            foreach ($external_links as $platform => $url) {
                                if (!empty($url)) {
                                    $label = isset($link_labels[$platform]) ? $link_labels[$platform] : ucfirst($platform);
                                    echo '<a href="' . esc_url($url) . '" target="_blank" style="background: #e8f5e8; padding: 4px 8px; border-radius: 4px; margin-right: 8px; text-decoration: none; color: #2e7d32;">' . esc_html($label) . '</a>';
                                    $link_count++;
                                }
                            }
                            if ($link_count === 0) {
                                echo '<span style="color: #999;">Aucun lien</span>';
                            }
                        } else {
                            echo '<span style="color: #999;">Aucun lien</span>';
                        }
                        ?>
                    </div>
                    
                </div>
            </td>
        </tr>
        <!-- Ligne des covers -->
        <tr class="sisme-game-data-covers-row">
            <td colspan="2" class="sisme-game-data-covers-cell">
                <div class="sisme-game-data-covers-container">
                    <strong class="sisme-game-data-covers-label">Covers :</strong>
                    
                    <div class="sisme-game-data-covers-list">
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
                            <div class="sisme-cover-item sisme-cover-<?php echo esc_attr($cover_type); ?>">
                                <div class="sisme-cover-label">
                                    <?php echo $cover_labels[$cover_type]; ?>
                                </div>
                                <?php if (!empty($cover_id)): ?>
                                    <?php
                                    $image = wp_get_attachment_image_src($cover_id, 'large');
                                    if ($image):
                                    ?>
                                        <img src="<?php echo esc_url($image[0]); ?>" 
                                             class="sisme-cover-image sisme-cover-image-valid"
                                             title="<?php echo esc_attr(get_the_title($cover_id)); ?>"
                                             alt="<?php echo esc_attr($cover_labels[$cover_type]); ?>">
                                    <?php else: ?>
                                        <div class="sisme-cover-placeholder sisme-cover-error">❌</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="sisme-cover-placeholder sisme-cover-empty">📷</div>
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
     * Afficher la pagination
     */
    private function render_pagination() {
        if (!$this->options['show_pagination'] || $this->total_items <= $this->per_page) {
            return;
        }
        
        $total_pages = ceil($this->total_items / $this->per_page);
        $current_page = $this->current_page;
        
        ?>
        <div class="sisme-pagination-wrapper">
            <div class="sisme-pagination-info">
                Affichage de <?php echo (($current_page - 1) * $this->per_page) + 1; ?> à 
                <?php echo min($current_page * $this->per_page, $this->total_items); ?> 
                sur <?php echo $this->total_items; ?> jeu<?php echo $this->total_items > 1 ? 'x' : ''; ?>
            </div>
            
            <?php if ($total_pages > 1): ?>
                <div class="sisme-pagination-links">
                    <?php
                    $base_url = remove_query_arg('paged');
                    
                    // Bouton précédent
                    if ($current_page > 1) {
                        echo '<a href="' . add_query_arg('paged', $current_page - 1, $base_url) . '" class="button sisme-pagination-prev">‹ Précédent</a> ';
                    }
                    
                    // Numéros de pages
                    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
                        if ($i == $current_page) {
                            echo '<span class="button button-primary sisme-pagination-current">' . $i . '</span> ';
                        } else {
                            echo '<a href="' . add_query_arg('paged', $i, $base_url) . '" class="button sisme-pagination-page">' . $i . '</a> ';
                        }
                    }
                    
                    // Bouton suivant
                    if ($current_page < $total_pages) {
                        echo '<a href="' . add_query_arg('paged', $current_page + 1, $base_url) . '" class="button sisme-pagination-next">Suivant ›</a>';
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
        <div class="sisme-game-data-table-module" id="<?php echo esc_attr($this->module_id); ?>">
            
            <?php if ($this->options['show_search']): ?>
                <?php $this->filter_module->render(); ?>
            <?php endif; ?>
            
            <?php if (empty($this->games_data)): ?>
                <div class="sisme-no-data-message">
                    <h3 class="sisme-no-data-title">🎮 Aucun jeu trouvé</h3>
                    <?php if (isset($_GET['s']) && !empty($_GET['s'])): ?>
                        <p class="sisme-no-data-search">Aucun résultat pour "<?php echo esc_html($_GET['s']); ?>"</p>
                        <a href="<?php echo remove_query_arg(['s', 'paged']); ?>" class="button sisme-no-data-clear">Voir tous les jeux</a>
                    <?php else: ?>
                        <p class="sisme-no-data-empty">Commencez par créer des étiquettes de jeux ou ajouter des données.</p>
                        <a href="<?php echo $this->options['edit_url']; ?>" class="button button-primary sisme-no-data-add">
                            ➕ Ajouter un jeu
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                
                <table class="wp-list-table widefat fixed sisme-game-data-table">
                    <?php $this->render_table_header(); ?>
                    
                    <tbody class="sisme-table-body">
                        <?php foreach ($this->games_data as $index => $game_data): ?>
                            <?php $this->render_table_row($game_data); ?>
                            
                            <!-- Séparateur visuel entre les jeux (sauf pour le dernier) -->
                            <?php if ($index < count($this->games_data) - 1): ?>
                                <tr class="sisme-game-separator">
                                    <td colspan="2" class="sisme-game-separator-cell"></td>
                                </tr>
                            <?php endif; ?>
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