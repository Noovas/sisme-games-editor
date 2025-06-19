<?php
/**
 * File: /sisme-games-editor/includes/module-admin-page-filtre-article.php
 * Module: Filtre d'Articles - Sisme Games Editor
 * 
 * Ce module fournit une interface de filtrage pour les listes d'articles
 * avec différentes options de recherche et de filtrage.
 * 
 * Utilisation:
 * 1. Inclure ce fichier
 * 2. Initialiser la classe avec les options souhaitées
 * 3. Appeler la méthode render() pour afficher l'interface de filtrage
 * 4. Utiliser get_filter_args() pour récupérer les arguments de filtrage à passer à WP_Query
 * 
 * Exemples:
 * // Filtre complet avec toutes les options
 * $filtre = new Sisme_Article_Filter_Module([
 *     'search' => true,
 *     'status' => true,
 *     'categories' => true,
 *     'tags' => true,
 *     'author' => true
 * ]);
 * 
 * // Filtre simplifié
 * $filtre = new Sisme_Article_Filter_Module([
 *     'search' => true,
 *     'status' => true
 * ]);
 * 
 * // Utilisation avec WP_Query
 * $filtre->render();
 * $args = array_merge(
 *     ['post_type' => 'post', 'posts_per_page' => 20],
 *     $filtre->get_filter_args()
 * );
 * $query = new WP_Query($args);
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Article_Filter_Module {
    
    private $options = [];
    private $filter_values = [];
    private $module_id;
    private static $instance_counter = 0;
    private static $css_loaded = false;
    private $mode = 'articles';
    
    /**
     * Constructeur
     * 
     * @param array $options Options de filtrage à activer
     * @param string $mode Mode de fonctionnement ('articles' ou 'game_data')
     */
    public function __construct($options = [], $mode = 'articles') {
        $this->mode = $mode; // 'articles' ou 'game_data'
        
        if ($mode === 'game_data') {
            $default_options = [
                'search' => true,
                'genres' => true,      // Filtrer par genres de jeux
                'platforms' => false,   // Filtrer par plateformes  
                'developers' => true,  // Filtrer par développeurs
                'has_data' => false,    // Jeux avec/sans données
                'has_articles' => false // Jeux avec/sans articles
            ];
        } else {
            $default_options = [
                'search' => true,
                'status' => false,
                'categories' => false,
                'tags' => false,
                'author' => false
            ];
        }
        
        // Fusionner avec les options fournies
        $this->options = wp_parse_args($options, $default_options);
        
        // Générer un ID unique pour chaque instance du module
        self::$instance_counter++;
        $this->module_id = 'article-filter-' . self::$instance_counter;
        
        // Récupérer les valeurs de filtrage depuis l'URL
        $this->get_filter_values_from_url();
        
        // Enregistrer le CSS uniquement une fois
        if (!self::$css_loaded) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
            self::$css_loaded = true;
        }
    }

    /**
     * Retourne les arguments de filtrage pour Game Data
     */
    public function get_game_data_filter_args() {
        if ($this->mode !== 'game_data') {
            return [];
        }
        
        $args = [];
        
        // Recherche par nom de jeu (terme d'étiquette)
        if (!empty($this->filter_values['search'])) {
            $args['search'] = $this->filter_values['search'];
        }

        // Filtrer par genre
        if (!empty($this->filter_values['genres'])) {
            $args['genres'] = $this->filter_values['genres'];
        }

        // Filtrer par développeur
        if (!empty($this->filter_values['developers'])) {
            $args['developers'] = $this->filter_values['developers'];
        }
        
        // Filtrer par plateforme
        if (!empty($this->filter_values['platforms'])) {
            $args['platforms'] = $this->filter_values['platforms'];
        }
        
        return $args;
    }
    
    /**
     * Enregistre et charge les styles CSS
     */
    public function enqueue_styles() {
        // Vérifier si nous sommes dans l'admin
        if (!is_admin()) {
            return;
        }
        
        // Charger le CSS si disponible
        if (defined('SISME_GAMES_EDITOR_PLUGIN_URL')) {
            wp_enqueue_style(
                'sisme-article-filter-styles',
                SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/admin/module-admin-page-filtre-article.css',
                array(),
                defined('SISME_GAMES_EDITOR_VERSION') ? SISME_GAMES_EDITOR_VERSION : '1.0'
            );
        }
    }
    
    /**
     * Récupère les valeurs de filtrage depuis l'URL
     */
    private function get_filter_values_from_url() {
        $this->filter_values = [
            'search' => isset($_GET['s']) && $_GET['s'] !== '' ? sanitize_text_field($_GET['s']) : '',
            'status' => isset($_GET['status']) && $_GET['status'] !== '' ? sanitize_text_field($_GET['status']) : '',
            'category' => isset($_GET['category']) && $_GET['category'] !== '' ? sanitize_text_field($_GET['category']) : '',
            'tag' => isset($_GET['tag']) && $_GET['tag'] !== '' ? sanitize_text_field($_GET['tag']) : '',
            'genres' => isset($_GET['genres']) && $_GET['genres'] !== '' ? intval($_GET['genres']) : 0,
            'developers' => isset($_GET['developers']) && $_GET['developers'] !== '' ? intval($_GET['developers']) : 0,
            'platforms' => isset($_GET['platforms']) && $_GET['platforms'] !== '' ? sanitize_text_field($_GET['platforms']) : '',
            'author' => isset($_GET['author']) && $_GET['author'] !== '' ? intval($_GET['author']) : 0
        ];
    }
    
    /**
     * Retourne les arguments de filtrage pour WP_Query
     * 
     * @return array Arguments de requête pour WP_Query
     */
    public function get_filter_args() {
        $args = [];
        
        // Recherche
        if (!empty($this->filter_values['search'])) {
            $args['s'] = $this->filter_values['search'];
        }
        
        // Statut
        if (!empty($this->filter_values['status'])) {
            $args['post_status'] = $this->filter_values['status'];
        } else {
            $args['post_status'] = ['publish', 'draft', 'private'];
        }
        
        // Catégorie
        if (!empty($this->filter_values['category'])) {
            $args['category_name'] = $this->filter_values['category'];
        }
        
        // Étiquette
        if (!empty($this->filter_values['tag'])) {
            $args['tag'] = $this->filter_values['tag'];
        }
        
        // Auteur
        if (!empty($this->filter_values['author'])) {
            $args['author'] = $this->filter_values['author'];
        }
        
        return $args;
    }
    
    /**
     * Génère l'URL actuelle avec les paramètres de filtrage mis à jour
     * 
     * @param array $updated_params Paramètres à mettre à jour
     * @return string URL avec les paramètres mis à jour
     */
    private function get_filtered_url($updated_params = []) {
        $current_url = add_query_arg([], '');
        $current_params = $_GET;
        
        // Supprimer 'paged' si on change les filtres
        if (!empty($updated_params) && isset($current_params['paged'])) {
            unset($current_params['paged']);
        }
        
        // Fusionner les paramètres actuels avec les nouveaux
        $params = array_merge($current_params, $updated_params);
        
        // Construire l'URL
        $url = strtok($_SERVER['REQUEST_URI'], '?');
        
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        return $url;
    }
    
    /**
     * Affiche l'interface de filtrage
     */
    public function render() {
        if ($this->mode === 'game_data') {
            $this->render_game_data_filters();
            return;
        }
        
        // Mode articles (code existant)
        ?>
        <div class="sisme-filter-form" id="<?php echo esc_attr($this->module_id); ?>">
            <div class="sisme-card sisme-mb-md">
                <div class="sisme-card__body">
                    <form method="get" class="sisme-flex sisme-flex-wrap sisme-gap-md sisme-align-items-end">
                        
                        <!-- Conserver les paramètres de page existants -->
                        <?php foreach ($_GET as $key => $value): ?>
                            <?php if (!in_array($key, ['s', 'status', 'category', 'tag', 'author', 'paged'])): ?>
                                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <div class="sisme-form-group">
                            <!-- Recherche -->
                            <?php if ($this->options['search']): ?>
                                <div class="sisme-flex sisme-gap-sm">
                                    <label for="search-<?php echo $this->module_id; ?>" class="sisme-label">
                                        🔍 Rechercher
                                    </label>
                                    <input type="text" 
                                           id="search-<?php echo $this->module_id; ?>" 
                                           name="s" 
                                           value="<?php echo esc_attr($this->filter_values['search']); ?>" 
                                           placeholder="Titre, contenu..."
                                           class="sisme-form-input">
                                </div>
                            <?php endif; ?>
                            
                            <!-- Statut -->
                            <?php if ($this->options['status']): ?>
                                <div class="sisme-flex sisme-gap-sm">
                                    <label for="status-<?php echo $this->module_id; ?>" class="sisme-label">
                                        📋 Statut
                                    </label>
                                    <select id="status-<?php echo $this->module_id; ?>" name="status" class="sisme-form-select">
                                        <option value="">Tous les statuts</option>
                                        <option value="publish" <?php selected($this->filter_values['status'], 'publish'); ?>>Publié</option>
                                        <option value="draft" <?php selected($this->filter_values['status'], 'draft'); ?>>Brouillon</option>
                                        <option value="private" <?php selected($this->filter_values['status'], 'private'); ?>>Privé</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Catégorie -->
                            <?php if ($this->options['categories']): ?>
                                <div class="sisme-flex sisme-gap-sm">
                                    <label for="category-<?php echo $this->module_id; ?>" class="sisme-label">
                                        📁 Catégorie
                                    </label>
                                    <select id="category-<?php echo $this->module_id; ?>" name="category" class="sisme-form-select">
                                        <option value="">Toutes les catégories</option>
                                        <?php
                                        $categories = get_categories(array('hide_empty' => false));
                                        foreach ($categories as $category) {
                                            printf(
                                                '<option value="%s" %s>%s (%d)</option>',
                                                $category->slug,
                                                selected($this->filter_values['category'], $category->slug, false),
                                                $category->name,
                                                $category->count
                                            );
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Étiquettes -->
                            <?php if ($this->options['tags']): ?>
                                <div class="sisme-flex sisme-gap-sm">
                                    <label for="tag-<?php echo $this->module_id; ?>" class="sisme-label">
                                        🏷️ Étiquette
                                    </label>
                                    <select id="tag-<?php echo $this->module_id; ?>" name="tag" class="sisme-form-select">
                                        <option value="">Toutes les étiquettes</option>
                                        <?php
                                        $tags = get_tags(array('hide_empty' => false, 'orderby' => 'name'));
                                        foreach ($tags as $tag) {
                                            printf(
                                                '<option value="%s" %s>%s (%d)</option>',
                                                $tag->slug,
                                                selected($this->filter_values['tag'], $tag->slug, false),
                                                $tag->name,
                                                $tag->count
                                            );
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Auteur -->
                            <?php if ($this->options['author']): ?>
                                <div class="sisme-flex sisme-gap-sm">
                                    <label for="author-<?php echo $this->module_id; ?>" class="sisme-label">
                                        👤 Auteur
                                    </label>
                                    <select id="author-<?php echo $this->module_id; ?>" name="author" class="sisme-form-select">
                                        <option value="">Tous les auteurs</option>
                                        <?php
                                        $authors = get_users(array('who' => 'authors'));
                                        foreach ($authors as $author) {
                                            printf(
                                                '<option value="%d" %s>%s</option>',
                                                $author->ID,
                                                selected($this->filter_values['author'], $author->ID, false),
                                                $author->display_name
                                            );
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="sisme-flex sisme-gap-sm sisme-align-items-center">
                            <button type="submit" class="sisme-btn sisme-btn--primary">
                                Filtrer
                            </button>
                            
                            <?php if ($this->has_active_filters()): ?>
                                <a href="<?php echo esc_url($this->get_filtered_url(['s' => '', 'status' => '', 'category' => '', 'tag' => '', 'author' => '', 'paged' => ''])); ?>" 
                                   class="sisme-btn sisme-btn--secondary">Réinitialiser</a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($this->has_active_filters()): ?>
                            <div class="sisme-active-filters">
                                <span class="sisme-text-sm sisme-text-muted sisme-mr-sm">Filtres actifs:</span>
                                <div class="sisme-flex sisme-flex-wrap sisme-gap-xs">
                                    <?php echo $this->render_active_filters(); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Affiche l'interface de filtrage spécifique aux Game Data
     */
    private function render_game_data_filters() {
        ?>
        <div class="sisme-filter-form game-data-filters">
            <div class="sisme-card sisme-mb-md">
                <div class="sisme-card__body">
                    
                    <!-- Formulaire centré avec juste la recherche -->
                    <form method="get" class="sisme-simple-search-form">
                        
                        <!-- Conserver les paramètres de page existants -->
                        <?php foreach ($_GET as $key => $value): ?>
                            <?php if (!in_array($key, ['s', 'paged'])): ?>
                                <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <div class="sisme-search-container">
                            <div class="sisme-search-icon">🔍</div>
                            <input type="text" 
                                   name="s" 
                                   value="<?php echo esc_attr($this->filter_values['search']); ?>" 
                                   placeholder="Rechercher un jeu par son nom..."
                                   class="sisme-search-input">
                            
                            <?php if (!empty($this->filter_values['search'])): ?>
                                <a href="<?php echo esc_url($this->get_filtered_url(['s' => '', 'paged' => ''])); ?>" 
                                   class="sisme-search-clear" title="Effacer la recherche">&times;</a>
                            <?php endif; ?>
                            
                            <button type="submit" class="sisme-search-btn">
                                Rechercher
                            </button>
                        </div>
                        
                    </form>
                    
                    <?php if (!empty($this->filter_values['search'])): ?>
                        <div class="sisme-search-result">
                            <span class="sisme-search-result-text">
                                Recherche : <strong><?php echo esc_html($this->filter_values['search']); ?></strong>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Génère les options de genres pour AJAX (format différent)
     */
    private function render_game_genres_options_ajax() {
        $genres = get_categories(array(
            'hide_empty' => false,
            'parent' => $this->get_parent_category_id('jeux-video'),
            'orderby' => 'name'
        ));
        
        $output = '';
        foreach ($genres as $genre) {
            $output .= sprintf(
                '<div class="suggestion-item" data-genre-id="%d" style="padding: 6px 10px; margin: 2px 0; background: #fff; border-radius: 3px; cursor: pointer; border: 1px solid #e0e0e0;">%s (%d jeux)</div>',
                $genre->term_id,
                esc_html(str_replace('jeux-', '', $genre->name)),
                $genre->count
            );
        }
        
        return $output;
    }

    /**
     * Génère les options de développeurs pour AJAX (format différent)
     */
    private function render_game_developers_options_ajax() {
        $developers = get_categories(array(
            'hide_empty' => false,
            'parent' => $this->get_parent_category_id('editeurs-developpeurs'),
            'orderby' => 'name'
        ));
        
        $output = '';
        foreach ($developers as $developer) {
            $output .= sprintf(
                '<div class="suggestion-item" data-developer-id="%d" style="padding: 6px 10px; margin: 2px 0; background: #fff; border-radius: 3px; cursor: pointer; border: 1px solid #e0e0e0;">%s</div>',
                $developer->term_id,
                esc_html($developer->name)
            );
        }
        
        return $output;
    }

    /**
     * Génère les options de select pour les genres de jeux
     */
    private function render_game_genres_options() {
        $genres = get_categories(array(
            'hide_empty' => false,
            'parent' => $this->get_parent_category_id('jeux-video'),
            'orderby' => 'name'
        ));
        
        $output = '';
        foreach ($genres as $genre) {
            $selected = selected($this->filter_values['genres'] ?? '', $genre->term_id, false);
            $output .= sprintf(
                '<option value="%d" %s>%s (%d jeux)</option>',
                $genre->term_id,
                $selected,
                esc_html($genre->name),
                $genre->count
            );
        }
        
        return $output;
    }

    /**
     * Génère les options de select pour les développeurs
     */
    private function render_game_developers_options() {
        $developers = get_categories(array(
            'hide_empty' => false,
            'parent' => $this->get_parent_category_id('editeurs-developpeurs'),
            'orderby' => 'name'
        ));
        
        $output = '';
        foreach ($developers as $developer) {
            $selected = selected($this->filter_values['developers'] ?? '', $developer->term_id, false);
            $output .= sprintf(
                '<option value="%d" %s>%s</option>',
                $developer->term_id,
                $selected,
                esc_html($developer->name)
            );
        }
        
        return $output;
    }

    /**
     * Obtient l'ID de la catégorie parent par nom
     */
    private function get_parent_category_id($parent_name) {
        $parent = get_category_by_slug(sanitize_title($parent_name));
        if (!$parent) {
            // Essayer par nom si le slug ne fonctionne pas
            $parents = get_categories(array(
                'name' => $parent_name,
                'hide_empty' => false,
                'number' => 1
            ));
            if (!empty($parents)) {
                $parent = $parents[0];
            }
        }
        
        return $parent ? $parent->term_id : 0;
    }

    /**
     * Affiche les filtres actifs pour Game Data
     */
    private function render_active_game_data_filters() {
        $active_filters = [];
        
        // Recherche
        if (!empty($this->filter_values['search'])) {
            $active_filters[] = sprintf(
                '<span class="sisme-tag sisme-tag--filter">Recherche: %s <a href="%s" class="sisme-tag__remove">×</a></span>',
                esc_html($this->filter_values['search']),
                esc_url($this->get_filtered_url(['s' => '']))
            );
        }
        
        // Genre
        if (!empty($this->filter_values['genres'])) {
            $genre = get_category($this->filter_values['genres']);
            $genre_name = $genre ? $genre->name : 'Genre #' . $this->filter_values['genres'];
            
            $active_filters[] = sprintf(
                '<span class="sisme-tag sisme-tag--filter">Genre: %s <a href="%s" class="sisme-tag__remove">×</a></span>',
                esc_html($genre_name),
                esc_url($this->get_filtered_url(['genres' => '']))
            );
        }
        
        // Développeur
        if (!empty($this->filter_values['developers'])) {
            $developer = get_category($this->filter_values['developers']);
            $developer_name = $developer ? $developer->name : 'Dev/Éditeur #' . $this->filter_values['developers'];
            
            $active_filters[] = sprintf(
                '<span class="sisme-tag sisme-tag--filter">Dev/Éditeur: %s <a href="%s" class="sisme-tag__remove">×</a></span>',
                esc_html($developer_name),
                esc_url($this->get_filtered_url(['developers' => '']))
            );
        }
        
        // Plateforme
        if (!empty($this->filter_values['platforms'])) {
            $platform_labels = [
                'pc' => 'PC',
                'ios' => 'iOS', 
                'android' => 'Android',
                'xbox' => 'Xbox',
                'playstation' => 'PlayStation',
                'switch' => 'Nintendo Switch'
            ];
            
            $platform_name = $platform_labels[$this->filter_values['platforms']] ?? $this->filter_values['platforms'];
            
            $active_filters[] = sprintf(
                '<span class="sisme-tag sisme-tag--filter">Plateforme: %s <a href="%s" class="sisme-tag__remove">×</a></span>',
                esc_html($platform_name),
                esc_url($this->get_filtered_url(['platforms' => '']))
            );
        }
        
        return implode(' ', $active_filters);
    }
    
    /**
     * Vérifie si des filtres sont actifs
     * 
     * @return bool True si au moins un filtre est actif
     */
    public function has_active_filters() {
        foreach ($this->filter_values as $value) {
            if (!empty($value)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Affiche les filtres actifs
     * 
     * @return string HTML des filtres actifs
     */
    private function render_active_filters() {
        $active_filters = [];
        
        // Recherche
        if (!empty($this->filter_values['search'])) {
            $active_filters[] = sprintf(
                '<span class="sisme-tag sisme-tag--filter">Recherche: %s <a href="%s" class="sisme-tag__remove">×</a></span>',
                esc_html($this->filter_values['search']),
                esc_url($this->get_filtered_url(['s' => '']))
            );
        }
        
        // Statut
        if (!empty($this->filter_values['status'])) {
            $status_labels = [
                'publish' => 'Publié',
                'draft' => 'Brouillon',
                'private' => 'Privé'
            ];
            $status_label = isset($status_labels[$this->filter_values['status']]) ? $status_labels[$this->filter_values['status']] : $this->filter_values['status'];
            
            $active_filters[] = sprintf(
                '<span class="sisme-tag sisme-tag--filter">Statut: %s <a href="%s" class="sisme-tag__remove">×</a></span>',
                esc_html($status_label),
                esc_url($this->get_filtered_url(['status' => '']))
            );
        }
        
        // Catégorie
        if (!empty($this->filter_values['category'])) {
            $category = get_category_by_slug($this->filter_values['category']);
            $category_name = $category ? $category->name : $this->filter_values['category'];
            
            $active_filters[] = sprintf(
                '<span class="sisme-tag sisme-tag--filter">Catégorie: %s <a href="%s" class="sisme-tag__remove">×</a></span>',
                esc_html($category_name),
                esc_url($this->get_filtered_url(['category' => '']))
            );
        }
        
        // Étiquette
        if (!empty($this->filter_values['tag'])) {
            $tag = get_term_by('slug', $this->filter_values['tag'], 'post_tag');
            $tag_name = $tag ? $tag->name : $this->filter_values['tag'];
            
            $active_filters[] = sprintf(
                '<span class="sisme-tag sisme-tag--filter">Étiquette: %s <a href="%s" class="sisme-tag__remove">×</a></span>',
                esc_html($tag_name),
                esc_url($this->get_filtered_url(['tag' => '']))
            );
        }
        
        // Auteur
        if (!empty($this->filter_values['author'])) {
            $author = get_userdata($this->filter_values['author']);
            $author_name = $author ? $author->display_name : 'Auteur #' . $this->filter_values['author'];
            
            $active_filters[] = sprintf(
                '<span class="sisme-tag sisme-tag--filter">Auteur: %s <a href="%s" class="sisme-tag__remove">×</a></span>',
                esc_html($author_name),
                esc_url($this->get_filtered_url(['author' => '']))
            );
        }
        
        return implode(' ', $active_filters);
    }
}