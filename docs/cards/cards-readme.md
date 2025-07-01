# 🎴 Cards - REF API

**Module:** `includes/cards/` | **Version:** 1.0.0 | **Status:** ✅ Production

---

## 📂 Architecture

```
includes/cards/
├── cards-loader.php          # Singleton + assets conditionnels
├── cards-api.php            # API principale + shortcodes
├── cards-functions.php      # Utilitaires + récupération données  
├── cards-normal-module.php  # Rendu cartes standard
├── cards-carousel-module.php # Carrousels interactifs
├── cards-details-module.php # Cartes layout horizontal
└── assets/
    ├── cards.css           # Styles cartes individuelles
    ├── cards-grid.css     # Styles grilles + carrousels  
    └── cards-carousel.js   # JavaScript carrousel
```

---

## 🔧 API Principale

### `Sisme_Cards_API`
```php
// Rendu carte individuelle - POINT D'ENTRÉE PRINCIPAL
render_card($game_id, $type, $options)           // string - HTML carte

// Types supportés
SUPPORTED_TYPES = ['normal', 'details', 'compact']

// Rendu multiple
render_multiple_cards($game_ids, $type, $options) // string - HTML cartes
render_cards_grid($args)                          // string - HTML grille
render_cards_carousel($args)                      // string - HTML carrousel

// Debug
debug_grid($args)                                 // string - HTML debug
```

### `Sisme_Cards_Functions` - Utilitaires Core
```php
// Récupération données
get_game_data($term_id)                          // array|false - Données complètes jeu
get_games_by_criteria($criteria)                 // array - IDs jeux filtrés
sort_games_by_release_date($game_ids, $order)   // array - IDs triés par date

// Statut et validation
get_game_release_status($term_id)                // array - Statut sortie jeu
get_game_badge($game_data)                       // array|false - Badge fraîcheur

// Utilitaires
truncate_smart($text, $length)                   // string - Texte tronqué
get_platform_icon($platform)                     // string - Icône plateforme
build_css_class($base, $modifiers, $custom)     // string - Classes CSS

// Debug et stats
test_criteria($criteria)                         // array - Test performance
get_games_stats_by_criteria($criteria)          // array - Statistiques globales
```

### `Sisme_Cards_Loader` - Singleton
```php
get_instance()                                   // self - Instance unique
enqueue_assets()                                 // void - Force chargement assets
```

---

## ⚡ JavaScript API

### Configuration Auto-injectée
```javascript
window.sismeCarousel = {
    ajaxUrl: 'wp-admin/admin-ajax.php',
    nonce: 'security_nonce',
    loadingText: 'Chargement...',
    errorText: 'Erreur lors du chargement',
    prevText: 'Précédent', 
    nextText: 'Suivant',
    debug: false
};
```

### Carrousel Interactif
```javascript
// Auto-initialisation sur .sisme-cards-carousel
// Touch/swipe support
// Navigation clavier (flèches)
// Infinite loop avec clones
// Responsive breakpoints automatiques
```

---

## 🗂️ Structure Données

### Format Game Data Complet
```php
[
    Sisme_Utils_Games::KEY_TERM_ID => 123,
    Sisme_Utils_Games::KEY_NAME => 'Nom du Jeu',
    'slug' => 'nom-du-jeu', 
    Sisme_Utils_Games::KEY_DESCRIPTION => 'Description...',
    Sisme_Utils_Games::KEY_COVER_URL => 'https://site.com/cover.jpg',
    'game_url' => 'https://site.com/tag/nom-du-jeu/',
    Sisme_Utils_Games::KEY_RELEASE_DATE => '2024-03-15',
    'timestamp' => 1710460800,
    Sisme_Utils_Games::KEY_GENRES => [[Sisme_Utils_Games::KEY_ID => 5, Sisme_Utils_Games::KEY_NAME => 'Action', 'slug' => 'jeux-action']],
    Sisme_Utils_Games::KEY_MODES => ['solo', 'multijoueur'],
    Sisme_Utils_Games::KEY_PLATFORMS => ['pc', 'playstation', 'xbox'],
    'developers' => ['Studio A'],
    'publishers' => ['Éditeur B']
]
```

### Meta Keys WordPress
```php
// Obligatoires
Sisme_Utils_Games::META_DESCRIPTION    // string - Description complète
Sisme_Utils_Games::META_COVER_MAIN         // int - ID attachment cover

// Optionnels  
'release_date'       // string - YYYY-MM-DD
'game_genres'        // array - IDs catégories genres
'game_modes'         // array - Modes de jeu
'platforms'          // array - Plateformes
'game_developers'    // array - Développeurs
'game_publishers'    // array - Éditeurs
'is_team_choice'     // bool - Choix équipe
```

### Critères de Recherche
```php
[
    Sisme_Utils_Games::KEY_GENRES => ['action', 'rpg'],        // Filtrage genres
    Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => false,            // Choix équipe seulement
    'sort_by_date' => true,               // Tri par date sortie
    'sort_order' => 'desc',               // 'asc' ou 'desc'
    'max_results' => 10,                  // Limite (-1 = pas de limite)
    'released' => 0,                      // 0=tous, 1=sortis, -1=pas sortis
    'debug' => false                      // Mode debug
]
```

---

## 🎨 CSS Classes

### Cartes
```css
.sisme-game-card                    /* Carte de base */
.sisme-game-card--normal            /* Carte standard */
.sisme-game-card--details           /* Carte détaillée */
.sisme-game-card--compact           /* Carte compacte */

.sisme-card-image                   /* Container image */
.sisme-card-content                 /* Zone contenu */
.sisme-card-title                   /* Titre jeu */
.sisme-card-description             /* Description */

.sisme-card-genres                  /* Container genres */
.sisme-card-genre                   /* Pill genre individuel */
.sisme-card-badge                   /* Badge fraîcheur */
```

### Grilles et Carrousels
```css
.sisme-cards-grid                   /* Grille de cartes */
.sisme-cards-carousel               /* Container carrousel */
.sisme-carousel__track              /* Track défilement */
.sisme-carousel__slide              /* Slide individuel */

.sisme-carousel__btn                /* Boutons navigation */
.sisme-carousel__btn--prev          /* Bouton précédent */
.sisme-carousel__btn--next          /* Bouton suivant */

.sisme-ultra-dots                   /* Container pagination */
.sisme-ultra-dot                    /* Dot individuel */
.sisme-ultra-dot--active            /* Dot actif */
```

---

## 🚀 Usage Patterns

### Carte Individuelle Basique
```php
// Via shortcode
echo do_shortcode('[game_card id="123"]');

// Via API directe
echo Sisme_Cards_API::render_card(123, 'normal', [
    'show_description' => true,
    'max_genres' => 3
]);
```

### Grille de Cartes avec Filtres
```php
echo do_shortcode('[game_cards_grid 
    type="normal" 
    cards_per_row="4" 
    max_cards="8"
    genres="action,rpg"
    sort_order="desc"
]');
```

### Carrousel Interactif
```php
echo do_shortcode('[game_cards_carousel
    cards_per_view="4"
    total_cards="12" 
    infinite="true"
    autoplay="false"
    title="🎮 Dernières Découvertes"
]');
```

### Recherche Avancée
```php
$criteria = [
    Sisme_Utils_Games::KEY_GENRES => ['action'],
    Sisme_Utils_Games::KEY_IS_TEAM_CHOICE => true,
    'sort_by_date' => true,
    'sort_order' => 'desc',
    'max_results' => 5,
    'released' => 1  // Jeux sortis uniquement
];

$game_ids = Sisme_Cards_Functions::get_games_by_criteria($criteria);
$html = Sisme_Cards_API::render_multiple_cards($game_ids, 'normal');
```

---

## ⚡ Performance & Cache

### Chargement Conditionnel Assets
```php
// Assets chargés PARTOUT automatiquement
// Optimisation : tokens.css → cards.css → cards-grid.css
// JavaScript carousel chargé avec dépendances jQuery + tooltip
```

### Optimisations Intégrées
```css
/* GPU acceleration pour carrousels */
transform: translateZ(0);
will-change: transform;
backface-visibility: hidden;

/* Responsive images */
background-size: cover;
background-position: center;
```

---

## 🐛 Debug & Hooks

### Mode Debug
```php
// Activation dans shortcode
echo do_shortcode('[game_cards_grid debug="true"]');

// Test performance critères
$test = Sisme_Cards_Functions::test_criteria([
    Sisme_Utils_Games::KEY_GENRES => ['action'],
    'max_results' => 5,
    'debug' => true
]);

// Statistiques globales
$stats = Sisme_Cards_Functions::get_games_stats_by_criteria();
```

### Hooks WordPress
```php
// Assets
add_action('wp_enqueue_scripts', [loader, 'enqueue_assets'], 15);
add_action('admin_enqueue_scripts', [loader, 'enqueue_assets'], 15);

// Shortcodes automatiques
add_shortcode('game_card', callback);
add_shortcode('game_cards_grid', callback);
add_shortcode('game_cards_carousel', callback);
```

### Debug CSS Classes
```css
.sisme-cards-carousel--debug        /* Mode debug carrousel */
.sisme-carousel__slide--clone-start  /* Clone début (orange) */
.sisme-carousel__slide--clone-end    /* Clone fin (violet) */
.sisme-carousel__slide--original     /* Slide original (vert) */
```

---

## 🔗 Intégrations

### Modules Liés
- **Taxonomies** - Récupération genres via catégories
- **User-Profile** - Potentiel wishlist et favoris
- **Game-Management** - Données meta games

### Dépendances WordPress
- **Taxonomies** `post_tag` pour jeux
- **Meta API** pour données étendues
- **Attachments** pour covers images
- **Transients** pour optimisation requêtes

### Assets Externes
- **jQuery** (WordPress core)
- **Frontend-tooltip** (module frontend)
- **Tokens.css** (design system)

---

## 🎯 Types Cartes

| Type | Status | Description | Usage |
|------|--------|-------------|-------|
| `normal` | ✅ Prod | Carte standard avec image + contenu | Grilles générales |
| `details` | ✅ Prod | Layout horizontal étendu | Pages détaillées |
| `compact` | 🚧 Dev | Version condensée pour widgets | Sidebar, footer |