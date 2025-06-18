<?php
/**
 * File: /sisme-games-editor/includes/module-admin-page-liste-article.php
 * Module: Liste d'Articles par Catégorie - Sisme Games Editor
 * 
 * Ce module fournit une classe réutilisable pour afficher des listes d'articles
 * filtrées par catégorie(s) avec chargement en scroll infini.
 * 
 * Utilisation:
 * 1. Inclure ce fichier
 * 2. Initialiser la classe et appeler la méthode render()
 * 
 * Exemples:
 * // Une seule catégorie
 * $article_list = new Sisme_Article_List_Module('news', 20);
 * $article_list->render();
 * 
 * // Plusieurs catégories
 * $article_list = new Sisme_Article_List_Module(['news', 'patch', 'tests'], 20);
 * $article_list->render();
 * 
 * // Toutes les catégories Sisme
 * $article_list = new Sisme_Article_List_Module('all', 20);
 * $article_list->render();
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Article_List_Module {
    
    private $category_slugs = [];
    private $items_per_page;
    private $module_id;
    private $filter_options = [];
    private $filter_module = null;
    private static $instance_counter = 0;
    
    /**
     * Constructeur
     * 
     * @param mixed $category_slugs Slug(s) de la/les catégorie(s) pour filtrer les articles (string ou array)
     * @param int $items_per_page Nombre d'articles par chargement
     * @param array|bool $filter_options Options de filtrage (array) ou activation/désactivation (bool)
     */
    public function __construct($category_slugs, $items_per_page = 20, $filter_options = false) {
        // Accepter soit une chaîne soit un tableau de slugs
        if (is_array($category_slugs)) {
            $this->category_slugs = $category_slugs;
        } else {
            $this->category_slugs = [$category_slugs];
        }
        $this->items_per_page = $items_per_page;
        
        // Générer un ID unique pour chaque instance du module
        self::$instance_counter++;
        $this->module_id = 'article-list-' . self::$instance_counter;
        
        // Initialiser les options de filtrage
        $this->init_filter_options($filter_options);
        
        // Enregistrer le hook AJAX
        add_action('wp_ajax_sisme_load_more_' . $this->module_id, array($this, 'ajax_load_more'));
    }
    
    /**
     * Initialise les options de filtrage
     * 
     * @param array|bool $filter_options Options de filtrage
     */
    private function init_filter_options($filter_options) {
        // Si filter_options est un booléen, activer/désactiver tous les filtres
        if (is_bool($filter_options)) {
            if ($filter_options) {
                // Activer tous les filtres de base
                $this->filter_options = [
                    'search' => true,
                    'status' => true,
                    'categories' => false,
                    'tags' => false,
                    'author' => false
                ];
            } else {
                // Désactiver tous les filtres
                $this->filter_options = [];
            }
        } else if (is_array($filter_options)) {
            // Utiliser les options fournies
            $this->filter_options = $filter_options;
        }
        
        // Si des options de filtrage sont définies, essayer de créer le module de filtre
        if (!empty($this->filter_options)) {
            // Plusieurs tentatives pour trouver le fichier du module de filtre
            $filter_file = dirname(__FILE__) . '/module-admin-page-filtre-article.php';
            if (!file_exists($filter_file)) {
                // Essayer avec le chemin complet du plugin si défini
                if (defined('SISME_GAMES_EDITOR_PLUGIN_DIR')) {
                    $filter_file = SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/module-admin-page-filtre-article.php';
                }
            }
            
            // Vérifier si le fichier existe avant de l'inclure
            if (file_exists($filter_file)) {
                require_once $filter_file;
                
                // Vérifier si la classe existe
                if (class_exists('Sisme_Article_Filter_Module')) {
                    try {
                        $this->filter_module = new Sisme_Article_Filter_Module($this->filter_options);
                    } catch (Exception $e) {
                        // En cas d'erreur, désactiver le filtre et continuer
                        $this->filter_module = null;
                        error_log('Erreur lors de la création du module de filtre: ' . $e->getMessage());
                    }
                }
            }
        }
    }
    
    /**
     * Handler AJAX pour charger plus d'articles
     */
    public function ajax_load_more() {
        // Vérification de sécurité
        if (!check_ajax_referer('sisme_load_more_' . $this->module_id, 'nonce', false)) {
            wp_send_json_error(array('message' => 'Erreur de sécurité'));
        }
        
        $page_num = isset($_POST['page_num']) ? intval($_POST['page_num']) : 1;
        
        // Récupérer les articles
        $articles = $this->get_articles($page_num);
        
        ob_start();
        $this->render_articles_list($articles);
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Récupère les articles pour les catégories spécifiées
     * 
     * @param int $page Numéro de page
     * @return WP_Query Requête avec les résultats
     */
    private function get_articles($page = 1) {
        $category_ids = [];
        
        // Si on a "all" dans les slugs, récupérer toutes les catégories Sisme
        if (in_array('all', $this->category_slugs)) {
            $category_ids = $this->get_all_sisme_categories();
        } else {
            // Récupérer les IDs de toutes les catégories spécifiées
            foreach ($this->category_slugs as $slug) {
                $category = get_category_by_slug($slug);
                if ($category) {
                    $category_ids[] = $category->term_id;
                }
            }
        }
        
        // Si aucune catégorie valide, retourner une requête vide
        if (empty($category_ids)) {
            return new WP_Query();
        }
        
        // Construire les arguments de base de la requête
        $args = array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'posts_per_page' => $this->items_per_page,
            'paged' => $page,
            'orderby' => 'date',
            'order' => 'DESC',
            'category__in' => $category_ids
        );
        
        // Si un module de filtre est disponible, fusionner avec ses arguments
        if ($this->filter_module && method_exists($this->filter_module, 'get_filter_args')) {
            try {
                $filter_args = $this->filter_module->get_filter_args();
                
                // Fusionner les arguments, mais conserver category__in
                $category_in = $args['category__in'];
                $args = array_merge($args, $filter_args);
                
                // S'assurer que nous filtrons toujours par les catégories spécifiées
                // à moins que le filtre spécifie explicitement une catégorie
                if (empty($filter_args['cat']) && empty($filter_args['category_name']) && empty($filter_args['category__in'])) {
                    $args['category__in'] = $category_in;
                }
            } catch (Exception $e) {
                // En cas d'erreur, ignorer les arguments de filtre
                error_log('Erreur lors de la récupération des arguments de filtre: ' . $e->getMessage());
            }
        }
        
        // Exécuter la requête
        return new WP_Query($args);
    }
    
    /**
     * Récupère toutes les catégories liées à Sisme Games
     * 
     * @return array IDs des catégories
     */
    private function get_all_sisme_categories() {
        $all_categories = get_categories(array('hide_empty' => false));
        $sisme_categories = array();
        
        foreach ($all_categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0 || 
                in_array($category->slug, array('news', 'patch', 'tests'))) {
                $sisme_categories[] = $category->term_id;
            }
        }
        
        return $sisme_categories;
    }
    
    /**
     * Détermine le type d'article et renvoie les informations associées
     * 
     * @param int $post_id ID de l'article
     * @return array Informations sur le type d'article
     */
    private function get_article_type_info($post_id) {
        $categories = get_the_category($post_id);
        
        foreach ($categories as $category) {
            if (strpos($category->slug, 'jeux-') === 0) {
                return array(
                    'type' => 'fiche',
                    'icon' => '🎮',
                    'label' => 'Fiche',
                    'edit_url' => admin_url('admin.php?page=sisme-games-edit-fiche&post_id=' . $post_id)
                );
            } elseif ($category->slug === 'news') {
                return array(
                    'type' => 'news',
                    'icon' => '📰',
                    'label' => 'News',
                    'edit_url' => admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id)
                );
            } elseif ($category->slug === 'patch') {
                return array(
                    'type' => 'patch',
                    'icon' => '🔧',
                    'label' => 'Patch',
                    'edit_url' => admin_url('admin.php?page=sisme-games-edit-patch-news&post_id=' . $post_id)
                );
            } elseif ($category->slug === 'tests') {
                return array(
                    'type' => 'test',
                    'icon' => '🧪',
                    'label' => 'Test',
                    'edit_url' => get_edit_post_link($post_id)
                );
            }
        }
        
        return array(
            'type' => 'other',
            'icon' => '📄',
            'label' => 'Article',
            'edit_url' => get_edit_post_link($post_id)
        );
    }
    
    /**
     * Affiche la liste des articles
     * 
     * @param WP_Query $articles_query Requête contenant les articles
     */
    private function render_articles_list($articles_query) {
        if (!$articles_query->have_posts()) {
            return;
        }
        
        while ($articles_query->have_posts()) : $articles_query->the_post();
            $post_id = get_the_ID();
            $article_type = $this->get_article_type_info($post_id);
            $status = get_post_status();
            
            $status_labels = array(
                'publish' => 'Publié',
                'draft' => 'Brouillon',
                'private' => 'Privé'
            );
            ?>
            <div class="article-item">
                <!-- Image à gauche -->
                <div class="article-image">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php echo get_the_post_thumbnail($post_id, 'medium', array('class' => 'article-thumb')); ?>
                    <?php else : ?>
                        <div class="no-image"><?php echo $article_type['icon']; ?></div>
                    <?php endif; ?>
                </div>
                
                <!-- Contenu à droite -->
                <div class="article-content">
                    <!-- Ligne de données -->
                    <div class="article-data">
                        <div class="data-col title-col">
                            <h4 class="article-title">
                                <a href="<?php echo esc_url($article_type['edit_url']); ?>">
                                    <?php the_title(); ?>
                                </a>
                            </h4>
                            <div class="article-meta">
                                <span class="article-type">
                                    <?php echo $article_type['icon']; ?> <?php echo $article_type['label']; ?>
                                </span>
                                <?php 
                                // Afficher les catégories
                                $categories = get_the_category();
                                if (!empty($categories)) {
                                    echo '<span class="meta-categories">';
                                    foreach ($categories as $category) {
                                        // Afficher toutes les catégories qui ne sont pas dans notre liste de filtrage
                                        // sauf si nous avons 'all' comme filtre, auquel cas nous affichons les catégories qui ne sont pas des catégories Sisme
                                        if (
                                            (!in_array('all', $this->category_slugs) && !in_array($category->slug, $this->category_slugs)) ||
                                            (in_array('all', $this->category_slugs) && !in_array($category->slug, array('news', 'patch', 'tests')) && strpos($category->slug, 'jeux-') !== 0)
                                        ) {
                                            echo '<span class="meta-tag">' . esc_html($category->name) . '</span>';
                                        }
                                    }
                                    echo '</span>';
                                }
                                
                                // Afficher les étiquettes
                                $tags = get_the_tags();
                                if (!empty($tags)) {
                                    echo '<span class="meta-tags">';
                                    foreach ($tags as $tag) {
                                        echo '<span class="meta-tag">' . esc_html($tag->name) . '</span>';
                                    }
                                    echo '</span>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="data-col status-col">
                            <span class="status-badge status-<?php echo esc_attr($status); ?>">
                                <?php echo esc_html($status_labels[$status] ?? $status); ?>
                            </span>
                        </div>
                        <div class="data-col date-col">
                            <?php echo get_the_date(); ?>
                        </div>
                    </div>
                    
                    <!-- Ligne d'actions -->
                    <div class="article-actions">
                        <a href="<?php echo esc_url($article_type['edit_url']); ?>" 
                           class="action-btn edit-btn">✏️ Modifier</a>
                        
                        <?php if ($status === 'draft') : ?>
                            <a href="<?php echo wp_nonce_url(
                                add_query_arg(
                                    array('action' => 'publish', 'post_id' => $post_id),
                                    admin_url('admin.php?page=sisme-games-all-articles')
                                ),
                                'publish_post_' . $post_id
                            ); ?>" class="action-btn publish-btn">✅ Publier</a>
                        <?php elseif ($status === 'publish') : ?>
                            <a href="<?php echo wp_nonce_url(
                                add_query_arg(
                                    array('action' => 'unpublish', 'post_id' => $post_id),
                                    admin_url('admin.php?page=sisme-games-all-articles')
                                ),
                                'unpublish_post_' . $post_id
                            ); ?>" class="action-btn draft-btn">📝 Brouillon</a>
                        <?php endif; ?>
                        
                        <a href="<?php echo get_permalink($post_id); ?>" 
                           target="_blank" 
                           class="action-btn view-btn">👁️ Voir</a>
                        
                        <a href="<?php echo wp_nonce_url(
                            add_query_arg(
                                array('action' => 'delete', 'post_id' => $post_id),
                                admin_url('admin.php?page=sisme-games-all-articles')
                            ),
                            'delete_post_' . $post_id
                        ); ?>" 
                           onclick="return confirm('Supprimer cet article ?');"
                           class="action-btn delete-btn">🗑️ Supprimer</a>
                    </div>
                </div>
            </div>
        <?php endwhile;
    }
    
    /**
     * Affiche la liste complète avec le chargement en scroll infini
     */
    public function render() {
        // Récupérer les articles pour la première page
        $articles_query = $this->get_articles();
        $max_pages = $articles_query->max_num_pages;
        
        // ID unique pour ce module
        $container_id = $this->module_id . '-container';
        $loader_id = $this->module_id . '-loader';
        $end_id = $this->module_id . '-end';
        ?>
        
        <div class="sisme-article-list-module" id="<?php echo esc_attr($this->module_id); ?>">
            <?php 
            // Afficher le module de filtre s'il est disponible
            if ($this->filter_module && method_exists($this->filter_module, 'render')) {
                $this->filter_module->render();
                
                // Afficher le nombre de résultats si filtrage actif
                if (method_exists($this->filter_module, 'has_active_filters') && $this->filter_module->has_active_filters()) :
                ?>
                <div class="filtered-results-count">
                    <p><?php echo $articles_query->found_posts; ?> article(s) trouvé(s)</p>
                </div>
                <?php endif;
            }
            
            if ($articles_query->have_posts()) : ?>
                
                <!-- Liste des articles -->
                <div class="articles-container" id="<?php echo esc_attr($container_id); ?>">
                    <?php $this->render_articles_list($articles_query); ?>
                </div>
                
                <!-- Indicateur de chargement et scroll infini -->
                <?php if ($max_pages > 1) : ?>
                    <div class="infinite-scroll-loader" id="<?php echo esc_attr($loader_id); ?>" style="display: none;">
                        <div class="loader-spinner">🔄</div>
                        <p>Chargement des articles suivants...</p>
                    </div>
                    
                    <div class="infinite-scroll-end" id="<?php echo esc_attr($end_id); ?>" style="display: none;">
                        <p>📋 Tous les articles ont été chargés</p>
                    </div>
                <?php endif; ?>
                
            <?php else : ?>
                
                <!-- État vide -->
                <div class="empty-state">
                    <div class="empty-icon">📄</div>
                    <?php if (in_array('all', $this->category_slugs)) : ?>
                        <h3>Aucun article trouvé</h3>
                        <p>Aucun contenu Sisme Games n'a été trouvé</p>
                    <?php else : ?>
                        <h3>Aucun article trouvé</h3>
                        <p>Aucun contenu dans la/les catégorie(s) spécifiée(s)</p>
                    <?php endif; ?>
                </div>
                
            <?php endif; ?>
        </div>
        
        <?php if ($articles_query->have_posts() && $max_pages > 1) : ?>
        <!-- JavaScript pour le scroll infini -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variables pour ce module spécifique
            let currentPage = 1;
            const maxPages = <?php echo intval($max_pages); ?>;
            let loading = false;
            const moduleId = '<?php echo esc_js($this->module_id); ?>';
            const container = document.getElementById('<?php echo esc_js($container_id); ?>');
            const loader = document.getElementById('<?php echo esc_js($loader_id); ?>');
            const endIndicator = document.getElementById('<?php echo esc_js($end_id); ?>');
            
            function loadMoreArticles() {
                if (loading || currentPage >= maxPages) return;
                
                loading = true;
                currentPage++;
                loader.style.display = 'block';
                
                // Construire les paramètres
                const params = new URLSearchParams({
                    action: 'sisme_load_more_' + moduleId,
                    page_num: currentPage,
                    nonce: '<?php echo wp_create_nonce('sisme_load_more_' . $this->module_id); ?>'
                });
                
                // Appel AJAX
                const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: params
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data.html) {
                        container.insertAdjacentHTML('beforeend', data.data.html);
                    }
                    
                    loader.style.display = 'none';
                    loading = false;
                    
                    // Si on a atteint la fin
                    if (currentPage >= maxPages) {
                        endIndicator.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Erreur de chargement:', error);
                    loader.style.display = 'none';
                    loading = false;
                });
            }
            
            // Observer d'intersection pour détecter quand le module est visible
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Le module est visible, on active le scroll infini
                        window.addEventListener('scroll', handleScroll);
                    } else {
                        // Le module n'est plus visible, on désactive le scroll infini
                        window.removeEventListener('scroll', handleScroll);
                    }
                });
            });
            
            // Observer le module
            observer.observe(document.getElementById(moduleId));
            
            // Fonction de détection du scroll
            function handleScroll() {
                if (loading || currentPage >= maxPages) return;
                
                const moduleRect = document.getElementById(moduleId).getBoundingClientRect();
                const isModuleVisible = moduleRect.top < window.innerHeight && moduleRect.bottom > 0;
                
                if (!isModuleVisible) return;
                
                const scrollPosition = window.innerHeight + window.scrollY;
                const documentHeight = document.documentElement.offsetHeight;
                
                // Charger quand on est à 200px du bas
                if (scrollPosition >= documentHeight - 200) {
                    loadMoreArticles();
                }
            }
        });
        </script>
        <?php endif; ?>
        
        <?php
        wp_reset_postdata();
    }
}