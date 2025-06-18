<?php
/**
 * File: /sisme-games-editor/includes/module-admin-page-statistiques.php
 * Module: Statistiques Sisme Games Editor
 * 
 * Ce module centralise le calcul et l'affichage des statistiques du plugin.
 * Il permet de générer facilement des blocs de stats personnalisés pour n'importe quelle page.
 * 
 * Utilisation:
 * $stats = new Sisme_Stats_Module();
 * $stats->add_stat('fiches', 'Fiches de jeu', 'game');
 * $stats->add_stat('total', 'Total articles', 'stats');
 * $stats->render();
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Stats_Module {
    
    private $stats_to_display = array();
    private $cache_duration = 300; // 5 minutes
    private $title = '';
    private $subtitle = '';
    
    /**
     * Constructeur
     * 
     * @param string $title Titre de la section statistiques
     * @param string $subtitle Sous-titre explicatif (optionnel)
     */
    public function __construct($title = '📊 Statistiques', $subtitle = '') {
        $this->title = $title;
        $this->subtitle = $subtitle;
    }
    
    /**
     * Ajouter une statistique à afficher
     * 
     * @param string $type Type de stat (fiches, news, patch, tests, total, drafts, published, etc.)
     * @param string $label Label à afficher
     * @param string $icon Icône (emoji ou classe CSS)
     * @param string $color Couleur spécifique (optionnel)
     */
    public function add_stat($type, $label, $icon = '', $color = '') {
        $this->stats_to_display[] = array(
            'type' => $type,
            'label' => $label,
            'icon' => $icon,
            'color' => $color
        );
    }
    
    /**
     * Configuration rapide - Statistiques principales
     */
    public function add_main_stats() {
        $this->add_stat('total', 'Total articles', '📊', 'total');
        $this->add_stat('fiches', 'Fiches de jeu', '🎮', 'fiches');
        $this->add_stat('patch_news', 'Patch & News', '📰', 'news');
        $this->add_stat('tests', 'Tests', '🧪', 'tests');
    }
    
    /**
     * Configuration rapide - Statistiques de statut
     */
    public function add_status_stats() {
        $this->add_stat('published', 'Publiés', '✅', 'published');
        $this->add_stat('draft', 'Brouillons', '📝', 'draft');
        $this->add_stat('private', 'Privés', '🔒', 'private');
    }
    
    /**
     * Configuration rapide - Statistiques détaillées
     */
    public function add_detailed_stats() {
        $this->add_stat('total', 'Total', '📊', 'total');
        $this->add_stat('fiches', 'Fiches', '🎮', 'fiches');
        $this->add_stat('news', 'News', '📰', 'news');
        $this->add_stat('patch', 'Patch', '🔧', 'patch');
        $this->add_stat('tests', 'Tests', '🧪', 'tests');
    }
    
    /**
     * Calculer toutes les statistiques (avec cache)
     */
    private function calculate_all_stats() {
        $cache_key = 'sisme_stats_data';
        $cached_stats = get_transient($cache_key);
        
        if ($cached_stats !== false) {
            return $cached_stats;
        }
        
        $stats = array();
        
        // Statistiques par type d'article
        $stats['fiches'] = $this->count_posts_by_category_prefix('jeux-');
        $stats['news'] = $this->count_posts_by_category_prefix('news');
        $stats['patch'] = $this->count_posts_by_category_prefix('patch');
        $stats['tests'] = $this->count_posts_by_category_prefix('tests');
        
        // Statistiques combinées
        $stats['patch_news'] = $stats['news'] + $stats['patch'];
        $stats['total'] = $stats['fiches'] + $stats['news'] + $stats['patch'] + $stats['tests'];
        
        // Statistiques par statut
        $stats['published'] = $this->count_posts_by_status('publish');
        $stats['draft'] = $this->count_posts_by_status('draft');
        $stats['private'] = $this->count_posts_by_status('private');
        
        // Statistiques brouillons par type
        $stats['drafts_fiches'] = $this->count_drafts_by_category_prefix('jeux-');
        $stats['drafts_news'] = $this->count_drafts_by_category_prefix('news');
        $stats['drafts_patch'] = $this->count_drafts_by_category_prefix('patch');
        $stats['drafts_tests'] = $this->count_drafts_by_category_prefix('tests');
        $stats['drafts_total'] = $stats['drafts_fiches'] + $stats['drafts_news'] + $stats['drafts_patch'] + $stats['drafts_tests'];
        
        // Mettre en cache pour 5 minutes
        set_transient($cache_key, $stats, $this->cache_duration);
        
        return $stats;
    }
    
    /**
     * Compter les articles par préfixe de catégorie
     */
    private function count_posts_by_category_prefix($prefix) {
        $all_categories = get_categories(array('hide_empty' => false));
        $category_ids = array();
        
        if ($prefix === 'jeux-') {
            foreach ($all_categories as $category) {
                if (strpos($category->slug, 'jeux-') === 0) {
                    $category_ids[] = $category->term_id;
                }
            }
        } else {
            $category = get_category_by_slug($prefix);
            if ($category) {
                $category_ids[] = $category->term_id;
            }
        }
        
        if (empty($category_ids)) {
            return 0;
        }
        
        $query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft', 'private'),
            'category__in' => $category_ids,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        return $query->found_posts;
    }
    
    /**
     * Compter les articles par statut
     */
    private function count_posts_by_status($status) {
        // Récupérer les catégories du plugin
        $sisme_categories = $this->get_all_sisme_categories();
        
        if (empty($sisme_categories)) {
            return 0;
        }
        
        $query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => $status,
            'category__in' => $sisme_categories,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        return $query->found_posts;
    }
    
    /**
     * Compter les brouillons par préfixe de catégorie
     */
    private function count_drafts_by_category_prefix($prefix) {
        $all_categories = get_categories(array('hide_empty' => false));
        $category_ids = array();
        
        if ($prefix === 'jeux-') {
            foreach ($all_categories as $category) {
                if (strpos($category->slug, 'jeux-') === 0) {
                    $category_ids[] = $category->term_id;
                }
            }
        } else {
            $category = get_category_by_slug($prefix);
            if ($category) {
                $category_ids[] = $category->term_id;
            }
        }
        
        if (empty($category_ids)) {
            return 0;
        }
        
        $query = new WP_Query(array(
            'post_type' => 'post',
            'post_status' => 'draft',
            'category__in' => $category_ids,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        return $query->found_posts;
    }
    
    /**
     * Récupérer toutes les catégories du plugin
     */
    private function get_all_sisme_categories() {
        $categories = array();
        $all_cats = get_categories(array('hide_empty' => false));
        
        foreach ($all_cats as $cat) {
            if (strpos($cat->slug, 'jeux-') === 0 || 
                in_array($cat->slug, array('news', 'patch', 'tests'))) {
                $categories[] = $cat->term_id;
            }
        }
        
        return $categories;
    }
    
    /**
     * Obtenir la classe CSS pour une statistique
     */
    private function get_stat_css_class($type, $color) {
        if (!empty($color)) {
            return "stat-{$color}";
        }
        
        // Classes par défaut selon le type
        $default_classes = array(
            'total' => 'stat-total',
            'fiches' => 'stat-fiches',
            'news' => 'stat-news',
            'patch' => 'stat-patch',
            'tests' => 'stat-tests',
            'patch_news' => 'stat-news',
            'published' => 'stat-published',
            'draft' => 'stat-draft',
            'private' => 'stat-private'
        );
        
        return isset($default_classes[$type]) ? $default_classes[$type] : 'stat-default';
    }
    
    /**
     * Rendre la section de statistiques
     */
    public function render() {
        if (empty($this->stats_to_display)) {
            echo '<p>Aucune statistique configurée pour l\'affichage.</p>';
            return;
        }
        
        $all_stats = $this->calculate_all_stats();
        
        ?>
        <div class="stats-section">
            <h3><?php echo esc_html($this->title); ?></h3>
            <?php if (!empty($this->subtitle)) : ?>
                <p class="stats-subtitle"><?php echo esc_html($this->subtitle); ?></p>
            <?php endif; ?>
            
            <div class="stats-grid">
                <?php foreach ($this->stats_to_display as $stat_config) : ?>
                    <?php
                    $type = $stat_config['type'];
                    $value = isset($all_stats[$type]) ? $all_stats[$type] : 0;
                    $css_class = $this->get_stat_css_class($type, $stat_config['color']);
                    ?>
                    <div class="stat-card <?php echo esc_attr($css_class); ?>">
                        <?php if (!empty($stat_config['icon'])) : ?>
                            <div class="stat-icon"><?php echo $stat_config['icon']; ?></div>
                        <?php endif; ?>
                        <span class="stat-number"><?php echo number_format($value); ?></span>
                        <span class="stat-label"><?php echo esc_html($stat_config['label']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Rendre uniquement la grille de stats (sans wrapper)
     */
    public function render_grid_only() {
        if (empty($this->stats_to_display)) {
            return;
        }
        
        $all_stats = $this->calculate_all_stats();
        
        ?>
        <div class="stats-grid">
            <?php foreach ($this->stats_to_display as $stat_config) : ?>
                <?php
                $type = $stat_config['type'];
                $value = isset($all_stats[$type]) ? $all_stats[$type] : 0;
                $css_class = $this->get_stat_css_class($type, $stat_config['color']);
                ?>
                <div class="stat-card <?php echo esc_attr($css_class); ?>">
                    <?php if (!empty($stat_config['icon'])) : ?>
                        <div class="stat-icon"><?php echo $stat_config['icon']; ?></div>
                    <?php endif; ?>
                    <span class="stat-number"><?php echo number_format($value); ?></span>
                    <span class="stat-label"><?php echo esc_html($stat_config['label']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Récupérer une statistique spécifique
     */
    public function get_stat($type) {
        $all_stats = $this->calculate_all_stats();
        return isset($all_stats[$type]) ? $all_stats[$type] : 0;
    }
    
    /**
     * Vider le cache des statistiques
     */
    public static function clear_cache() {
        delete_transient('sisme_stats_data');
    }
    
    /**
     * Méthode statique pour un usage rapide
     */
    public static function quick_stats($config = 'main', $title = '📊 Statistiques') {
        $stats = new self($title);
        
        switch ($config) {
            case 'main':
                $stats->add_main_stats();
                break;
            case 'status':
                $stats->add_status_stats();
                break;
            case 'detailed':
                $stats->add_detailed_stats();
                break;
        }
        
        $stats->render();
    }
}