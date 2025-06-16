<?php
/**
 * File: /sisme-games-editor/templates/single-page-news.php
 * Plugin: Sisme Games Editor  
 * Author: Sisme Games
 * Description: Template pour les pages news de jeux (catégorie page-news)
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

// Récupérer les données news préparées par le News Manager
global $sisme_news_data;

$game_name = $sisme_news_data['game_name'] ?? 'Jeu Indépendant';
$parent_fiche = $sisme_news_data['parent_fiche'] ?? null;
$news_articles = $sisme_news_data['news_articles'] ?? array();
$current_page = $sisme_news_data['current_page'] ?? get_post();

// URL de la fiche parent
$fiche_url = $parent_fiche ? get_permalink($parent_fiche->ID) : '#';
$fiche_title = $parent_fiche ? $parent_fiche->post_title : $game_name;

// Image header (depuis la fiche parent ou par défaut)
$header_image = '';
if ($parent_fiche && has_post_thumbnail($parent_fiche->ID)) {
    $header_image = get_the_post_thumbnail_url($parent_fiche->ID, 'full');
} else {
    $header_image = 'https://games.sisme.fr/wp-content/uploads/2025/06/sisme-games-default-header.webp';
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('sisme-news-page'); ?>>
    
    <!-- Header avec image du jeu -->
    <header class="sisme-news-header" style="background-image: url('<?php echo esc_url($header_image); ?>');">
        <div class="sisme-news-header-overlay">
            <div class="sisme-news-header-content">
                <div class="sisme-news-logo">
                    <img src="<?php echo esc_url($header_image); ?>" alt="<?php echo esc_attr($game_name); ?>">
                </div>
                <h1 class="sisme-news-title"><?php echo esc_html($game_name); ?> : News</h1>
                <div class="sisme-news-subtitle">
                    <span class="sisme-tag">Jeux News</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Breadcrumb SEO -->
    <nav class="sisme-breadcrumb" aria-label="Fil d'Ariane">
        <ol itemscope itemtype="https://schema.org/BreadcrumbList">
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="<?php echo home_url(); ?>">
                    <span itemprop="name">Accueil</span>
                </a>
                <meta itemprop="position" content="1" />
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="<?php echo home_url('/actualite/'); ?>">
                    <span itemprop="name">Actualité</span>
                </a>
                <meta itemprop="position" content="2" />
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a itemprop="item" href="<?php echo home_url('/page-news/'); ?>">
                    <span itemprop="name">Page News</span>
                </a>
                <meta itemprop="position" content="3" />
            </li>
            <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <span itemprop="name"><?php echo esc_html($game_name); ?> : News</span>
                <meta itemprop="position" content="4" />
            </li>
        </ol>
    </nav>

    <div class="sisme-news-content">
        
        <!-- Introduction SEO optimisée -->
        <section class="sisme-news-intro">
            <h2>Toutes les news de <?php echo esc_html($game_name); ?></h2>
            
            <div class="sisme-intro-content">
                <p>
                    <strong>Découvrez toute l'actualité de <?php echo esc_html($game_name); ?></strong>, 
                    un jeu indépendant passionnant qui mérite votre attention. Chez <strong>Sisme Games</strong>, 
                    nous suivons de près l'évolution de ce titre pour vous tenir informés des dernières 
                    <strong>mises à jour</strong>, <strong>patchs</strong>, et <strong>actualités gaming</strong>.
                </p>
                
                <p>
                    Notre équipe teste régulièrement les nouvelles fonctionnalités et modifications 
                    apportées à <?php echo esc_html($game_name); ?> pour vous offrir des 
                    <strong>guides détaillés</strong> et des <strong>analyses approfondies</strong>. 
                    Que vous soyez un joueur occasionnel ou un passionné de jeux indépendants, 
                    vous trouverez ici toutes les informations essentielles.
                </p>
                
                <p>
                    <strong>Patch notes</strong>, <strong>guides de gameplay</strong>, 
                    <strong>tests de nouvelles versions</strong> et <strong>actualités du développement</strong> : 
                    nous couvrons tous les aspects de <?php echo esc_html($game_name); ?> pour enrichir 
                    votre expérience de jeu. Restez connectés pour ne rien manquer des évolutions 
                    de ce <strong>jeu indépendant innovant</strong> !
                </p>
                
                <?php if ($parent_fiche) : ?>
                    <p class="sisme-fiche-link">
                        💡 <strong>Nouveau sur <?php echo esc_html($game_name); ?> ?</strong> 
                        Consultez d'abord notre <a href="<?php echo esc_url($fiche_url); ?>" title="Fiche complète de <?php echo esc_attr($fiche_title); ?>">
                            <strong>fiche complète du jeu</strong>
                        </a> pour découvrir ses principales caractéristiques, son gameplay et nos premières impressions.
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Articles News -->
        <?php if (!empty($news_articles)) : ?>
            <section class="sisme-news-articles">
                <h3>Dernières actualités et mises à jour</h3>
                
                <div class="sisme-news-grid">
                    <?php 
                    $news_manager = new Sisme_News_Manager();
                    foreach ($news_articles as $index => $article) : 
                        $article_type = $news_manager->get_article_type($article);
                        $article_url = get_permalink($article->ID);
                        $article_image = get_the_post_thumbnail_url($article->ID, 'medium_large');
                        
                        // Image par défaut selon le type
                        if (!$article_image) {
                            switch ($article_type) {
                                case 'PATCH':
                                    $article_image = 'https://games.sisme.fr/wp-content/uploads/2025/06/default-patch.webp';
                                    break;
                                case 'TEST':
                                    $article_image = 'https://games.sisme.fr/wp-content/uploads/2025/06/default-test.webp';
                                    break;
                                default:
                                    $article_image = 'https://games.sisme.fr/wp-content/uploads/2025/06/default-news.webp';
                            }
                        }
                        
                        // Couleur du badge selon le type
                        $badge_class = 'sisme-badge-' . strtolower($article_type);
                    ?>
                        <article class="sisme-news-card" itemscope itemtype="https://schema.org/Article">
                            <a href="<?php echo esc_url($article_url); ?>" class="sisme-card-link" 
                               title="<?php echo esc_attr($article->post_title); ?> - <?php echo esc_attr($article_type); ?>">
                                
                                <div class="sisme-card-image" style="background-image: url('<?php echo esc_url($article_image); ?>');">
                                    <div class="sisme-card-overlay">
                                        <div class="sisme-game-title"><?php echo esc_html($game_name); ?></div>
                                        <div class="sisme-article-badge <?php echo esc_attr($badge_class); ?>">
                                            <?php echo esc_html($article_type); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="sisme-card-content">
                                    <h4 class="sisme-card-title" itemprop="headline">
                                        <?php echo esc_html($article->post_title); ?>
                                    </h4>
                                    
                                    <?php if (!empty($article->post_excerpt)) : ?>
                                        <p class="sisme-card-excerpt" itemprop="description">
                                            <?php echo esc_html(wp_trim_words($article->post_excerpt, 20)); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="sisme-card-meta">
                                        <time datetime="<?php echo esc_attr(get_the_date('c', $article->ID)); ?>" itemprop="datePublished">
                                            <?php echo esc_html(get_the_date('j M Y', $article->ID)); ?>
                                        </time>
                                        <span class="sisme-read-time">📖 3 min de lecture</span>
                                    </div>
                                </div>
                                
                                <!-- Métadonnées Schema.org -->
                                <meta itemprop="author" content="Sisme Games">
                                <meta itemprop="publisher" content="Sisme Games">
                                <meta itemprop="url" content="<?php echo esc_url($article_url); ?>">
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
            
        <?php else : ?>
            <!-- Aucun article trouvé -->
            <section class="sisme-news-empty">
                <div class="sisme-empty-content">
                    <h3>Actualités en préparation</h3>
                    <p>
                        Nous travaillons activement sur le contenu dédié à <strong><?php echo esc_html($game_name); ?></strong>. 
                        Les premières actualités, guides et analyses arriveront très prochainement !
                    </p>
                    <p>
                        En attendant, n'hésitez pas à découvrir notre 
                        <a href="<?php echo esc_url($fiche_url); ?>"><strong>fiche complète du jeu</strong></a> 
                        pour tout savoir sur ses mécaniques et notre première impression.
                    </p>
                </div>
            </section>
        <?php endif; ?>

        <!-- Call-to-Action Participation -->
        <section class="sisme-cta-participate">
            <h3>Tu veux participer à actualiser <?php echo esc_html($game_name); ?> ?</h3>
            
            <div class="sisme-cta-content">
                <p>
                    <strong>Passionné de jeux indépendants ?</strong> Rejoignez notre communauté ! 
                    Si vous êtes un fin connaisseur de <?php echo esc_html($game_name); ?> et que la rédaction 
                    de <strong>guides détaillés</strong>, <strong>analyses de builds</strong>, 
                    <strong>trucs et astuces</strong> vous intéresse, nous serions ravis de collaborer avec vous.
                </p>
                
                <p>
                    Chez <strong>Sisme Games</strong>, nous privilégions la <strong>qualité du contenu</strong> 
                    à la quantité. Nos créateurs de contenu vidéo et nos <strong>rédacteurs spécialisés</strong> 
                    travaillent en étroite collaboration pour produire des guides à la fois précis et accessibles. 
                    Que vous préfériez être devant la caméra ou derrière la plume, il y a une place pour vous !
                </p>
                
                <div class="sisme-cta-specialties">
                    <p><strong>Nous recherchons particulièrement :</strong></p>
                    <div class="sisme-specialties-grid">
                        <div class="sisme-specialty">
                            <strong>🎮 Experts Gaming</strong><br>
                            Testeurs, guides gameplay, découverte de secrets
                        </div>
                        <div class="sisme-specialty">
                            <strong>✍️ Rédacteurs</strong><br>
                            Articles, patch notes, analyses techniques
                        </div>
                        <div class="sisme-specialty">
                            <strong>🎬 Créateurs Vidéo</strong><br>
                            Tutoriels, gameplay commentés, lives
                        </div>
                        <div class="sisme-specialty">
                            <strong>🎨 Créatifs</strong><br>
                            Graphismes, montage, illustrations
                        </div>
                    </div>
                </div>
                
                <p>
                    <strong>Intéressé ?</strong> N'hésitez pas même si ce n'est que pour une fois, 
                    pour savoir si cela te plait. Il faut essayer surtout si tu es un jeu en partie !
                </p>
                
                <div class="sisme-cta-buttons">
                    <a href="<?php echo home_url('/nous-rejoindre/'); ?>" class="sisme-btn-primary">
                        <strong>Nous rejoindre</strong>
                    </a>
                    <a href="<?php echo home_url('/contact/'); ?>" class="sisme-btn-secondary">
                        Nous contacter
                    </a>
                </div>
            </div>
        </section>

        <!-- Section Gaming SEO -->
        <section class="sisme-gaming-seo">
            <h3>L'univers des jeux indépendants sur Sisme Games</h3>
            
            <div class="sisme-seo-content">
                <p>
                    <strong>Sisme Games</strong> est votre destination de référence pour découvrir les 
                    <strong>meilleurs jeux indépendants</strong> du moment. Notre équipe de passionnés 
                    teste, analyse et vous présente une sélection rigoureuse de 
                    <strong>titres indie innovants</strong> qui méritent votre attention.
                </p>
                
                <div class="sisme-gaming-features">
                    <div class="sisme-feature">
                        <strong>🔍 Tests Approfondis</strong>
                        <p>Analyses détaillées, gameplay, graphismes, bande sonore : nous passons chaque jeu au crible.</p>
                    </div>
                    
                    <div class="sisme-feature">
                        <strong>📚 Guides Complets</strong>
                        <p>Tutorials, builds optimisés, secrets cachés : maîtrisez vos jeux favoris.</p>
                    </div>
                    
                    <div class="sisme-feature">
                        <strong>📰 Actualités Gaming</strong>
                        <p>Mises à jour, patchs, DLC : restez informés des dernières évolutions.</p>
                    </div>
                    
                    <div class="sisme-feature">
                        <strong>🎮 Communauté Active</strong>
                        <p>Échangez avec d'autres passionnés et partagez vos découvertes gaming.</p>
                    </div>
                </div>
                
                <p>
                    De l'<strong>action-RPG</strong> au <strong>puzzle-platformer</strong>, en passant par les 
                    <strong>simulateurs innovants</strong> et les <strong>jeux narratifs</strong>, 
                    nous explorons tous les genres pour vous faire découvrir les pépites du 
                    <strong>gaming indépendant</strong>.
                </p>
            </div>
        </section>

    </div>
</article>

<?php
// Données structurées pour la page news
$news_structured_data = array(
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $game_name . ' : Actualités et News',
    'description' => 'Toutes les actualités, mises à jour et guides pour ' . $game_name . ', un jeu indépendant analysé par Sisme Games.',
    'url' => get_permalink(),
    'mainEntity' => array(
        '@type' => 'ItemList',
        'name' => 'Articles sur ' . $game_name,
        'numberOfItems' => count($news_articles),
        'itemListElement' => array()
    ),
    'author' => array(
        '@type' => 'Organization',
        'name' => 'Sisme Games',
        'url' => home_url()
    ),
    'publisher' => array(
        '@type' => 'Organization',
        'name' => 'Sisme Games',
        'url' => home_url()
    ),
    'inLanguage' => 'fr-FR',
    'datePublished' => get_the_date('c'),
    'dateModified' => get_the_modified_date('c')
);

// Ajouter les articles à la liste
foreach ($news_articles as $index => $article) {
    $news_structured_data['mainEntity']['itemListElement'][] = array(
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => get_permalink($article->ID),
        'name' => $article->post_title
    );
}

echo '<script type="application/ld+json">' . wp_json_encode($news_structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
?>

<?php get_footer(); ?>