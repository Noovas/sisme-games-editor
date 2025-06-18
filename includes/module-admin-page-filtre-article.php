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
    
    /**
     * Constructeur
     * 
     * @param array $options Options de filtrage à activer
     * Options disponibles:
     * - search: Recherche par mot-clé (titre, contenu)
     * - status: Filtre par statut (publié, brouillon, privé)
     * - categories: Filtre par catégories
     * - tags: Filtre par étiquettes
     * - author: Filtre par auteur
     */
    public function __construct($options = []) {
        // Définir les options par défaut
        $default_options = [
            'search' => true,
            'status' => false,
            'categories' => false,
            'tags' => false,
            'author' => false
        ];
        
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
     * Affiche le formulaire de filtrage
     */
    public function render() {
        $reset_url = remove_query_arg(array_keys($this->filter_values));
        ?>
        <div class="sisme-article-filter-module" id="<?php echo esc_attr($this->module_id); ?>">
            <form method="get" action="">
                <?php 
                // Préserver les paramètres non liés au filtrage
                foreach ($_GET as $key => $value) {
                    if (!in_array($key, array_keys($this->filter_values)) && $key !== 'paged') {
                        echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
                    }
                }
                ?>
                
                <div class="filter-container">
                    <?php if ($this->options['search']) : ?>
                        <div class="filter-item filter-search">
                            <input type="text" name="s" value="<?php echo esc_attr($this->filter_values['search']); ?>" placeholder="Rechercher...">
                            <button type="submit" class="search-button">🔍</button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($this->options['status']) : ?>
                        <div class="filter-item filter-status">
                            <select name="status" class="filter-select">
                                <option value="">Tous les statuts</option>
                                <option value="publish" <?php selected($this->filter_values['status'], 'publish'); ?>>Publiés</option>
                                <option value="draft" <?php selected($this->filter_values['status'], 'draft'); ?>>Brouillons</option>
                                <option value="private" <?php selected($this->filter_values['status'], 'private'); ?>>Privés</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($this->options['categories']) : ?>
                        <div class="filter-item filter-category">
                            <select name="category" class="filter-select">
                                <option value="">Toutes les catégories</option>
                                <?php 
                                $categories = get_categories(['hide_empty' => false]);
                                foreach ($categories as $category) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($category->slug),
                                        selected($this->filter_values['category'], $category->slug, false),
                                        esc_html($category->name)
                                    );
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($this->options['tags']) : ?>
                        <div class="filter-item filter-tag">
                            <select name="tag" class="filter-select">
                                <option value="">Tous les jeux</option>
                                <?php 
                                $tags = get_tags(['hide_empty' => false]);
                                foreach ($tags as $tag) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($tag->slug),
                                        selected($this->filter_values['tag'], $tag->slug, false),
                                        esc_html($tag->name)
                                    );
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($this->options['author']) : ?>
                        <div class="filter-item filter-author">
                            <select name="author" class="filter-select">
                                <option value="">Tous les auteurs</option>
                                <?php 
                                // Utiliser 'capability' au lieu de 'who' (déprécié depuis WP 5.9)
                                $authors = get_users(['capability' => 'edit_posts']);
                                foreach ($authors as $author) {
                                    printf(
                                        '<option value="%d" %s>%s</option>',
                                        esc_attr($author->ID),
                                        selected($this->filter_values['author'], $author->ID, false),
                                        esc_html($author->display_name)
                                    );
                                }
                                ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="filter-actions">
                        <button type="submit" class="filter-button">Filtrer</button>
                        
                        <?php if ($this->has_active_filters()) : ?>
                            <a href="<?php echo esc_url($reset_url); ?>" class="reset-filters">Réinitialiser</a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($this->has_active_filters()) : ?>
                    <div class="active-filters">
                        <span class="active-filters-label">Filtres actifs:</span>
                        <?php echo $this->render_active_filters(); ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        <?php
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
                '<span class="active-filter">Recherche: %s <a href="%s" class="remove-filter">×</a></span>',
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
                '<span class="active-filter">Statut: %s <a href="%s" class="remove-filter">×</a></span>',
                esc_html($status_label),
                esc_url($this->get_filtered_url(['status' => '']))
            );
        }
        
        // Catégorie
        if (!empty($this->filter_values['category'])) {
            $category = get_category_by_slug($this->filter_values['category']);
            $category_name = $category ? $category->name : $this->filter_values['category'];
            
            $active_filters[] = sprintf(
                '<span class="active-filter">Catégorie: %s <a href="%s" class="remove-filter">×</a></span>',
                esc_html($category_name),
                esc_url($this->get_filtered_url(['category' => '']))
            );
        }
        
        // Étiquette
        if (!empty($this->filter_values['tag'])) {
            $tag = get_term_by('slug', $this->filter_values['tag'], 'post_tag');
            $tag_name = $tag ? $tag->name : $this->filter_values['tag'];
            
            $active_filters[] = sprintf(
                '<span class="active-filter">Jeux: %s <a href="%s" class="remove-filter">×</a></span>',
                esc_html($tag_name),
                esc_url($this->get_filtered_url(['tag' => '']))
            );
        }
        
        // Auteur
        if (!empty($this->filter_values['author'])) {
            $author = get_user_by('id', $this->filter_values['author']);
            $author_name = $author ? $author->display_name : 'ID: ' . $this->filter_values['author'];
            
            $active_filters[] = sprintf(
                '<span class="active-filter">Auteur: %s <a href="%s" class="remove-filter">×</a></span>',
                esc_html($author_name),
                esc_url($this->get_filtered_url(['author' => '']))
            );
        }
        
        return implode(' ', $active_filters);
    }
}