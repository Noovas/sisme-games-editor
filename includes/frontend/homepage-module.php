<?php
/**
 * File: /sisme-games-editor/includes/frontend/homepage-module.php
 * Module Homepage Builder - Générateur de sections d'accueil
 * 
 * RESPONSABILITÉ:
 * - Gérer les différentes sections de la homepage
 * - Système modulaire pour construire la page d'accueil
 * - Integration avec les Game Data et le système de vedettes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Homepage_Module {
    
    private static $instance_counter = 0;
    
    /**
     * Sections disponibles pour la homepage
     */
    private $available_sections = array(
        'hero_carousel' => array(
            'name' => 'Carrousel Hero',
            'description' => 'Carrousel principal en vedette',
            'icon' => '🎠',
            'priority' => 10
        ),
        'featured_picks' => array(
            'name' => 'Coups de Cœur Équipe',
            'description' => 'Sélection éditoriale premium',
            'icon' => '💎',
            'priority' => 20
        ),
        'latest_discoveries' => array(
            'name' => 'Dernières Découvertes',
            'description' => 'Jeux récemment ajoutés',
            'icon' => '🔥',
            'priority' => 30
        ),
        'genres_showcase' => array(
            'name' => 'Explorer par Genre',
            'description' => 'Sections par catégories de jeux',
            'icon' => '🎯',
            'priority' => 40
        ),
        'news_section' => array(
            'name' => 'Actualités Gaming',
            'description' => 'Derniers articles et news',
            'icon' => '📰',
            'priority' => 50
        ),
        'random_discovery' => array(
            'name' => 'Découverte Aléatoire',
            'description' => 'Section surprise et exploration',
            'icon' => '🎲',
            'priority' => 60
        ),
        'community_cta' => array(
            'name' => 'CTA Communauté',
            'description' => 'Appels à l\'action et liens sociaux',
            'icon' => '💬',
            'priority' => 70
        )
    );
    
    /**
     * Configuration par défaut de la homepage
     */
    private $default_config = array(
        'sections' => array('hero_carousel', 'featured_picks', 'latest_discoveries', 'genres_showcase', 'random_discovery', 'community_cta'),
        'container_class' => 'sisme-homepage-container',
        'section_spacing' => 'large'
    );
    
    /**
     * Rendre la homepage complète
     * 
     * @param array $config Configuration des sections
     * @return string HTML de la homepage
     */
    public function render_homepage($config = array()) {
        $config = array_merge($this->default_config, $config);
        
        // Charger les styles (inclut maintenant hero-section.css pour les containers)
        $this->enqueue_homepage_styles();
        
        $output = '';
        
        // 🎮 CARROUSEL HERO EN DEHORS DU CONTAINER (pleine largeur)
        if (in_array('hero_carousel', $config['sections'])) {
            $output .= $this->render_section('hero_carousel', $config);
        }
        
        // 🏠 CONTAINER PRINCIPAL STYLE FICHE DE JEU
        $output .= '<div class="sisme-homepage-main">';
        
        // Générer toutes les autres sections dans le container
        foreach ($config['sections'] as $section_name) {
            // Skip le carrousel déjà affiché en pleine largeur
            if ($section_name === 'hero_carousel') {
                continue;
            }
            
            if (isset($this->available_sections[$section_name])) {
                $output .= $this->render_section($section_name, $config);
            }
        }
        
        $output .= '</div>'; // fin container principal
        
        // JavaScript pour interactions
        $output .= $this->render_homepage_javascript();
        
        return $output;
    }
    
    /**
     * Rendre une section spécifique
     * 
     * @param string $section_name Nom de la section
     * @param array $config Configuration
     * @return string HTML de la section
     */
    private function render_section($section_name, $config = array()) {
        $section_config = $this->available_sections[$section_name];
        
        // Pour le carrousel hero : pas de wrapper section, rendu direct
        if ($section_name === 'hero_carousel') {
            return $this->render_hero_carousel_section($config);
        }
        
        // Pour toutes les autres sections : wrapper léger dans le container principal
        $output = '<div class="sisme-homepage-section sisme-section-' . esc_attr($section_name) . '" ';
        $output .= 'data-section="' . esc_attr($section_name) . '">';
        
        switch ($section_name) {
            case 'featured_picks':
                $output .= $this->render_featured_picks_section($config);
                break;
                
            case 'latest_discoveries':
                $output .= $this->render_latest_discoveries_section($config);
                break;
                
            case 'genres_showcase':
                $output .= $this->render_genres_showcase_section($config);
                break;
                
            case 'news_section':
                $output .= $this->render_news_section($config);
                break;
                
            case 'random_discovery':
                $output .= $this->render_random_discovery_section($config);
                break;
                
            case 'community_cta':
                $output .= $this->render_community_cta_section($config);
                break;
                
            default:
                $output .= $this->render_default_section($section_name);
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 🎠 Section Hero Carrousel
     */
    private function render_hero_carousel_section($config) {
        // Utiliser le système de vedettes existant
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/vedettes/vedettes-api.php';
        
        $carousel_options = array(
            'limit' => 10,
            'height' => '1000px',
            'autoplay' => true,
            'show_arrows' => true,
            'show_dots' => true,
            'title' => 'Jeux à la Une'
        );
        
        return Sisme_Vedettes_API::render_featured_carousel($carousel_options);
    }
    
    /**
     * 💎 Section Coups de Cœur Équipe
     */
    private function render_featured_picks_section($config) {
        $output = '';
        
        // Header de section
        $output .= $this->render_section_header(
            '💎 Coups de Cœur de l\'Équipe',
            'Les jeux indépendants qui nous ont marqués récemment'
        );
        
        // Récupérer les jeux avec un flag "editor_pick" ou les plus récents avec de bonnes notes
        $editor_picks = $this->get_editor_picks(3);
        
        if (!empty($editor_picks)) {
            $output .= '<div class="sisme-featured-picks-grid">';
            
            foreach ($editor_picks as $game) {
                $output .= $this->render_featured_pick_card($game);
            }
            
            $output .= '</div>';
        } else {
            $output .= $this->render_section_empty_state('Aucun coup de cœur configuré pour le moment.');
        }
        
        return $output;
    }
    
    /**
     * 🔥 Section Dernières Découvertes
     */
    private function render_latest_discoveries_section($config) {
        $output = '';
        
        // Header de section
        $output .= $this->render_section_header(
            '🔥 Dernières Découvertes',
            'Les pépites que nous venons de dénicher'
        );
        
        // Récupérer les 6 derniers jeux ajoutés
        $latest_games = $this->get_latest_games(6);
        
        if (!empty($latest_games)) {
            $output .= '<div class="sisme-discoveries-grid">';
            
            foreach ($latest_games as $game) {
                $output .= $this->render_discovery_card($game);
            }
            
            $output .= '</div>';
        } else {
            $output .= $this->render_section_empty_state('Aucune découverte récente.');
        }
        
        return $output;
    }
    
    /**
     * 🎯 Section Explorer par Genre
     */
    private function render_genres_showcase_section($config) {
        $output = '';
        
        // Header de section
        $output .= $this->render_section_header(
            '🎯 Explorer par Genre',
            'Trouvez votre prochain coup de cœur selon vos préférences'
        );
        
        // Récupérer les genres populaires
        $popular_genres = $this->get_popular_genres(4);
        
        if (!empty($popular_genres)) {
            foreach ($popular_genres as $genre) {
                $output .= $this->render_genre_section($genre);
            }
        } else {
            $output .= $this->render_section_empty_state('Aucun genre configuré.');
        }
        
        return $output;
    }
    
    /**
     * 📰 Section Actualités Gaming
     */
    private function render_news_section($config) {
        $output = '';
        
        // Header de section
        $output .= $this->render_section_header(
            '📰 Actualités Gaming',
            'Les dernières nouvelles du monde indépendant'
        );
        
        // Récupérer les derniers articles de blog
        $recent_posts = get_posts(array(
            'numberposts' => 4,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        if (!empty($recent_posts)) {
            $output .= '<div class="sisme-news-grid">';
            
            foreach ($recent_posts as $post) {
                $output .= $this->render_news_card($post);
            }
            
            $output .= '</div>';
            
            // Lien vers toutes les actualités
            $output .= '<div class="sisme-section-cta">';
            $output .= '<a href="' . get_permalink(get_option('page_for_posts')) . '" class="sisme-btn sisme-btn--outline">';
            $output .= 'Voir toutes les actualités →';
            $output .= '</a>';
            $output .= '</div>';
        } else {
            $output .= $this->render_section_empty_state('Aucune actualité récente.');
        }
        
        return $output;
    }
    
    /**
     * 🎲 Section Découverte Aléatoire
     */
    private function render_random_discovery_section($config) {
        $output = '';
        
        // Header de section
        $output .= $this->render_section_header(
            '🎲 Et si on découvrait autre chose ?',
            'Laissez le hasard vous guider vers votre prochaine passion'
        );
        
        // Bouton de découverte aléatoire
        $output .= '<div class="sisme-random-discovery">';
        $output .= '<button class="sisme-random-btn" id="sismeRandomBtn">';
        $output .= '<span class="sisme-random-icon">🎯</span>';
        $output .= '<span class="sisme-random-text">Surprends-moi !</span>';
        $output .= '</button>';
        
        // Container pour les jeux aléatoires (chargé via AJAX)
        $output .= '<div class="sisme-random-results" id="sismeRandomResults"></div>';
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 💬 Section CTA Communauté
     */
    private function render_community_cta_section($config) {
        $output = '';
        
        // Header de section
        $output .= $this->render_section_header(
            '💬 Rejoignez la Communauté',
            'Partagez vos découvertes et restez connectés'
        );
        
        $output .= '<div class="sisme-community-ctas">';
        
        // CTA Soumission de jeu
        $output .= '<div class="sisme-community-card">';
        $output .= '<div class="sisme-community-icon">🎮</div>';
        $output .= '<h3>Vous êtes développeur ?</h3>';
        $output .= '<p>Proposez votre jeu à notre équipe pour une review.</p>';
        $output .= '<a href="/contact" class="sisme-btn sisme-btn--primary">Soumettre un jeu</a>';
        $output .= '</div>';
        
        // CTA Newsletter
        $output .= '<div class="sisme-community-card">';
        $output .= '<div class="sisme-community-icon">📧</div>';
        $output .= '<h3>Newsletter Gaming</h3>';
        $output .= '<p>Recevez nos découvertes directement dans votre boîte mail.</p>';
        $output .= '<a href="/newsletter" class="sisme-btn sisme-btn--secondary">S\'abonner</a>';
        $output .= '</div>';
        
        // CTA Réseaux sociaux
        $output .= '<div class="sisme-community-card">';
        $output .= '<div class="sisme-community-icon">🔗</div>';
        $output .= '<h3>Suivez-nous</h3>';
        $output .= '<p>Retrouvez-nous sur les réseaux pour plus de contenus.</p>';
        $output .= '<div class="sisme-social-links">';
        $output .= '<a href="#" class="sisme-social-link">Twitter</a>';
        $output .= '<a href="#" class="sisme-social-link">Discord</a>';
        $output .= '</div>';
        $output .= '</div>';
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Utilitaire : Header de section
     */
    private function render_section_header($title, $subtitle = '') {
        $output = '<header class="sisme-section-header">';
        $output .= '<h2 class="sisme-section-title">' . esc_html($title) . '</h2>';
        if (!empty($subtitle)) {
            $output .= '<p class="sisme-section-subtitle">' . esc_html($subtitle) . '</p>';
        }
        $output .= '</header>';
        
        return $output;
    }
    
    /**
     * Utilitaire : État vide de section
     */
    private function render_section_empty_state($message) {
        return '<div class="sisme-section-empty">
                    <div class="sisme-empty-icon">📭</div>
                    <p>' . esc_html($message) . '</p>
                </div>';
    }
    
    /**
     * Charger les styles homepage
     */
    private function enqueue_homepage_styles() {
        // 1. Design tokens
        wp_enqueue_style(
            'sisme-frontend-tokens',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/tokens.css',
            array(),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // 2. Hero section (pour les containers .sisme-game-hero)
        wp_enqueue_style(
            'sisme-hero-section',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/hero-section.css',
            array('sisme-frontend-tokens'),
            SISME_GAMES_EDITOR_VERSION
        );
        
        // 3. Homepage styles (pour les sections spécifiques)
        wp_enqueue_style(
            'sisme-homepage',
            SISME_GAMES_EDITOR_PLUGIN_URL . 'assets/css/frontend/homepage.css',
            array('sisme-frontend-tokens', 'sisme-hero-section'),
            SISME_GAMES_EDITOR_VERSION
        );
    }
    
    /**
     * JavaScript pour interactions homepage
     */
    private function render_homepage_javascript() {
        return '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // Gestion du bouton découverte aléatoire
            const randomBtn = document.getElementById("sismeRandomBtn");
            const randomResults = document.getElementById("sismeRandomResults");
            
            if (randomBtn && randomResults) {
                randomBtn.addEventListener("click", function() {
                    this.classList.add("loading");
                    this.querySelector(".sisme-random-text").textContent = "Recherche en cours...";
                    
                    // Simulation AJAX (à remplacer par vraie requête)
                    setTimeout(() => {
                        this.classList.remove("loading");
                        this.querySelector(".sisme-random-text").textContent = "Surprends-moi !";
                        
                        // Ici, charger des jeux aléatoires
                        randomResults.innerHTML = "<p>🎮 Fonctionnalité en développement...</p>";
                    }, 1500);
                });
            }
        });
        </script>';
    }
    
    /**
     * Méthodes de récupération de données (à implémenter)
     */
    private function get_editor_picks($limit = 3) {
        // TODO: Récupérer les jeux marqués comme "coups de cœur éditoriaux"
        return array();
    }
    
    private function get_latest_games($limit = 6) {
        // TODO: Récupérer les derniers jeux ajoutés au système
        return array();
    }
    
    private function get_popular_genres($limit = 4) {
        // TODO: Récupérer les genres les plus populaires
        return array();
    }
    
    /**
     * Méthodes de rendu de cartes (à implémenter)
     */
    private function render_featured_pick_card($game) {
        return '<div class="sisme-featured-pick-card">Carte coup de cœur</div>';
    }
    
    private function render_discovery_card($game) {
        return '<div class="sisme-discovery-card">Carte découverte</div>';
    }
    
    private function render_genre_section($genre) {
        return '<div class="sisme-genre-section">Section genre</div>';
    }
    
    private function render_news_card($post) {
        return '<div class="sisme-news-card">Carte news</div>';
    }
    
    /**
     * Méthode statique d'utilisation rapide
     */
    public static function render($config = array()) {
        $instance = new self();
        return $instance->render_homepage($config);
    }
}

// Hook pour charger le module
add_action('wp_loaded', function() {
    if (class_exists('Sisme_Homepage_Module')) {
        // Le module est prêt à être utilisé
    }
});

// Shortcode pour utilisation dans les pages
add_shortcode('sisme_homepage', function($atts) {
    $atts = shortcode_atts(array(
        'sections' => 'hero_carousel,featured_picks,latest_discoveries,genres_showcase,random_discovery,community_cta'
    ), $atts);
    
    $config = array(
        'sections' => explode(',', $atts['sections'])
    );
    
    return Sisme_Homepage_Module::render($config);
});

?>