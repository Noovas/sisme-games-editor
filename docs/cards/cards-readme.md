# 🎴 Module Cards - Sisme Games Editor

**Version:** 1.0.0  
**Status:** Production Ready  
**Module:** `includes/cards/`

---

## 🎯 Vue d'ensemble

Module complet de rendu de cartes de jeux pour Sisme Games Editor. Fournit un système modulaire et flexible pour afficher les jeux sous forme de cartes individuelles, grilles organisées, et carrousels interactifs avec support complet du responsive design et animations.

## 📂 Structure des fichiers

```
includes/cards/
├── cards-loader.php          # Singleton + chargement assets conditionnels
├── cards-api.php            # API principale + shortcodes + validation
├── cards-functions.php      # Utilitaires + récupération données jeux
├── cards-normal-module.php  # Rendu cartes individuelles standard
├── cards-carousel-module.php # Carrousels interactifs 2-5 cartes
├── cards-details-module.php # Cartes détaillées layout horizontal
└── assets/
    ├── cards.css           # Styles cartes individuelles
    ├── cards-grid.css     # Styles grilles + carrousels
    └── cards-carousel.js  # JavaScript carrousel interactif
```

---

## 🚀 Utilisation

### 🎯 Shortcodes principaux

#### 1. `[game_card]` - Carte Individuelle

```html
<!-- Usage basique -->
[game_card id="123"]

<!-- Usage complet -->
[game_card id="123" type="normal" show_description="true" show_genres="true" show_platforms="false" show_date="true" date_format="short" css_class="ma-classe" max_genres="3" max_modes="4"]
```

**Paramètres disponibles :**

| Paramètre | Défaut | Type | Valeurs | Description |
|-----------|--------|------|---------|-------------|
| `id` | `0` | int | `1-999999` | ID du jeu (obligatoire) |
| `type` | `normal` | string | `normal`, `details`, `compact` | Type de carte |
| `show_description` | `true` | bool | `true`, `false` | Afficher description |
| `show_genres` | `true` | bool | `true`, `false` | Afficher genres |
| `show_platforms` | `false` | bool | `true`, `false` | Afficher plateformes |
| `show_date` | `true` | bool | `true`, `false` | Afficher date |
| `date_format` | `short` | string | `short`, `long` | Format date |
| `css_class` | `""` | string | - | Classes CSS personnalisées |
| `max_genres` | `3` | int | `-1`, `0-10` | Nb genres (-1=tous) |
| `max_modes` | `4` | int | `-1`, `0-10` | Nb modes (-1=tous) |

#### 2. `[game_cards_grid]` - Grille de Cartes

```html
<!-- Usage basique -->
[game_cards_grid cards_per_row="2" max_cards="6"]

<!-- Usage complet -->
[game_cards_grid type="normal" cards_per_row="4" max_cards="12" genres="action,rpg" is_team_choice="false" sort_by_date="true" container_class="ma-grille" debug="false" max_genres="3" max_modes="4" title="Nos Jeux" released="0"]
```

**Paramètres disponibles :**

| Paramètre | Défaut | Type | Valeurs | Description |
|-----------|--------|------|---------|-------------|
| `type` | `normal` | string | `normal`, `details`, `compact` | Type cartes |
| `cards_per_row` | `4` | int | `1-6` | Cartes par ligne |
| `max_cards` | `-1` | int | `-1`, `1-50` | Nb max (-1=illimité) |
| `genres` | `""` | string | - | Genres (séparés virgules) |
| `is_team_choice` | `false` | bool | `true`, `false` | Choix équipe uniquement |
| `sort_by_date` | `true` | bool | `true`, `false` | Tri par date |
| `sort_order` | `desc` | string | `asc`, `desc` | Ordre de tri |
| `container_class` | `""` | string | - | Classes container |
| `debug` | `false` | bool | `true`, `false` | Mode debug |
| `max_genres` | `3` | int | `-1`, `0-10` | Genres par carte |
| `max_modes` | `4` | int | `-1`, `0-10` | Modes par carte |
| `title` | `""` | string | - | Titre section |
| `released` | `0` | int | `0`, `1`, `-1` | Filtre sortie |

#### 3. `[game_cards_carousel]` - Carrousel Interactif

```html
<!-- Usage basique -->
[game_cards_carousel cards_per_view="3" total_cards="9"]

<!-- Usage complet -->
[game_cards_carousel cards_per_view="3" total_cards="9" genres="action,rpg" is_team_choice="false" sort_by_date="true" navigation="true" pagination="true" infinite="true" autoplay="false" debug="false" max_genres="3" max_modes="4" title="Carrousel" released="0"]
```

**Paramètres disponibles :**

| Paramètre | Défaut | Type | Valeurs | Description |
|-----------|--------|------|---------|-------------|
| `cards_per_view` | `3` | int | `2-5` | Cartes visibles |
| `total_cards` | `9` | int | `1-50` | Nb total cartes |
| `genres` | `""` | string | - | Genres (séparés virgules) |
| `is_team_choice` | `false` | bool | `true`, `false` | Choix équipe uniquement |
| `sort_by_date` | `true` | bool | `true`, `false` | Tri par date |
| `sort_order` | `desc` | string | `asc`, `desc` | Ordre de tri |
| `navigation` | `true` | bool | `true`, `false` | Boutons prev/next |
| `pagination` | `true` | bool | `true`, `false` | Dots pagination |
| `infinite` | `true` | bool | `true`, `false` | Défilement infini |
| `autoplay` | `false` | bool | `true`, `false` | Lecture auto |
| `debug` | `false` | bool | `true`, `false` | Mode debug |
| `max_genres` | `3` | int | `-1`, `0-10` | Genres par carte |
| `max_modes` | `4` | int | `-1`, `0-10` | Modes par carte |
| `title` | `""` | string | - | Titre carrousel |
| `released` | `0` | int | `0`, `1`, `-1` | Filtre sortie |

### 🔧 Usage PHP direct

#### Rendu de cartes individuelles
```php
<?php
if (class_exists('Sisme_Cards_API')) {
    // Carte simple
    echo Sisme_Cards_API::render_card(123, 'normal');
    
    // Carte avec options
    $options = [
        'show_description' => true,
        'show_genres' => true,
        'css_class' => 'ma-carte-custom'
    ];
    echo Sisme_Cards_API::render_card(123, 'normal', $options);
}
?>
```

#### Rendu de grilles
```php
<?php
if (class_exists('Sisme_Cards_API')) {
    $args = [
        'type' => 'normal',
        'cards_per_row' => 2,
        'max_cards' => 6,
        'sort_by_date' => true,
        'sort_order' => 'desc',
        'container_class' => 'ma-grille-2cols'
    ];
    
    echo Sisme_Cards_API::render_cards_grid($args);
}
?>
```

#### Rendu de carrousels
```php
<?php
if (class_exists('Sisme_Cards_API')) {
    $args = [
        'cards_per_view' => 3,
        'total_cards' => 9,
        'navigation' => true,
        'infinite' => true
    ];
    
    echo Sisme_Cards_API::render_cards_carousel($args);
}
?>
```

---

## 🎮 Fonctionnalités

### Types de cartes

#### **Carte Normal**
- **Layout** - Vertical avec image en haut
- **Badge** - Overlay automatique selon la date
- **Contenu** - Titre, description tronquée, genres, date
- **Usage** - Grilles standard, carrousels

#### **Carte Details**
- **Layout** - Horizontal pour mode liste
- **Contenu** - Description complète non tronquée
- **Plateformes** - Affichage groupé par famille
- **Usage** - Listes détaillées, pages de recherche

#### **Carte Compact** *(à venir)*
- **Layout** - Version minimaliste
- **Contenu** - Titre et image uniquement
- **Usage** - Widgets, sidebars

### Système de badges automatiques

#### **Logique de calcul**
```php
$now = time();
$game_time = strtotime($release_date);
$diff_days = floor(($now - $game_time) / 86400);

if ($diff_days < 0) → 'À VENIR'
elseif ($diff_days == 0) → 'AUJOURD'HUI'  
elseif ($diff_days > 0 && $diff_days <= 7) → 'NOUVEAU'
elseif ($diff_days <= 30 && !empty($last_update)) → 'MIS À JOUR'
else → Pas de badge
```

#### **Styles des badges**
- **À VENIR** : Badge bleu avec classe `.sisme-badge-coming`
- **AUJOURD'HUI** : Badge rouge avec classe `.sisme-badge-today`
- **NOUVEAU** : Badge vert avec classe `.sisme-badge-new`
- **MIS À JOUR** : Badge orange avec classe `.sisme-badge-updated`

### Carrousels interactifs

#### **Fonctionnalités avancées**
- **Navigation tactile** - Support swipe mobile/tablet
- **Loop infini** - Avec clones automatiques
- **Pagination** - Dots cliquables
- **Autoplay** - Avec pause au hover
- **Responsive** - Adaptation automatique
- **Performance** - Animations GPU optimisées

#### **Configuration JavaScript**
```javascript
// Configuration automatique injectée
window.sismeCarousel = {
    ajaxUrl: 'wp-admin/admin-ajax.php',
    nonce: 'security_nonce',
    loadingText: 'Chargement...',
    errorText: 'Erreur lors du chargement',
    debug: false
};
```

---

## ⚙️ API Technique

### Classes principales

#### `Sisme_Cards_Loader` (Singleton)
```php
// Instance unique
$loader = Sisme_Cards_Loader::get_instance();

// Chargement forcé des assets
$loader->force_load_assets();

// Vérification du chargement
$is_loaded = $loader->are_assets_loaded();
```

#### `Sisme_Cards_API`
```php
// Rendu carte individuelle
Sisme_Cards_API::render_card($id, $type, $options);

// Rendu grille de cartes
Sisme_Cards_API::render_cards_grid($args);

// Rendu carrousel
Sisme_Cards_API::render_cards_carousel($args);

// Validation des paramètres
Sisme_Cards_API::validate_card_params($params);
```

#### `Sisme_Cards_Functions`
```php
// Récupération données complètes
$game_data = Sisme_Cards_Functions::get_game_data($term_id);

// Filtrage par critères
$game_ids = Sisme_Cards_Functions::get_games_by_criteria($criteria);

// Tri par date
$sorted_ids = Sisme_Cards_Functions::sort_games_by_release_date($ids, $order);

// Utilitaires
$truncated = Sisme_Cards_Functions::truncate_smart($text, 120);
$icon = Sisme_Cards_Functions::get_platform_icon('windows');
```

### Structure des données

#### **Données de jeu complètes**
```php
[
    'term_id' => 123,
    'name' => 'Nom du Jeu',
    'slug' => 'nom-du-jeu',
    'description' => 'Description complète...',
    'cover_url' => 'https://site.com/uploads/cover.jpg',
    'game_url' => 'https://site.com/tag/nom-du-jeu/',
    'genres' => [
        ['id' => 5, 'name' => 'Action', 'slug' => 'jeux-action']
    ],
    'modes' => ['solo', 'multijoueur'],
    'platforms' => ['pc', 'playstation', 'xbox'],
    'developers' => ['Studio A', 'Studio B'],
    'publishers' => ['Publisher X'],
    'release_date' => '2023-12-15',
    'last_update' => '2024-01-10',
    'timestamp' => 1704931200,
    'external_links' => [
        'steam' => 'https://store.steampowered.com/...',
        'epic' => 'https://store.epicgames.com/...'
    ],
    'trailer_link' => 'https://youtube.com/watch?v=...',
    'screenshots' => [123, 456, 789]
]
```

#### **Critères de filtrage**
```php
[
    'genres' => ['action', 'rpg'],           // Noms de genres
    'is_team_choice' => false,               // Choix de l'équipe
    'sort_by_date' => true,                  // Tri par date
    'sort_order' => 'desc',                  // Ordre (asc/desc)
    'max_results' => 10,                     // Limite (-1=illimité)
    'released' => 0,                         // 1=sortis, 0=à venir, -1=tous
    'debug' => false                         // Mode debug
]
```

### Validation et sécurité

#### **Validation des paramètres**
```php
// Types de cartes supportés
private static $SUPPORTED_TYPES = ['normal', 'details', 'compact'];

// Validation automatique
$validation = Sisme_Cards_API::validate_grid_args($args);
if (!$validation['valid']) {
    return self::render_error($validation['message']);
}
```

#### **Échappement HTML**
```php
// Toutes les sorties sont échappées
echo esc_html($game_name);
echo esc_url($game_url);
echo esc_attr($css_class);
```

#### **Sanitisation des entrées**
```php
// Sanitisation automatique des shortcodes
$sanitized_genres = array_map('sanitize_text_field', explode(',', $genres));
$sanitized_id = intval($id);
$sanitized_bool = filter_var($value, FILTER_VALIDATE_BOOLEAN);
```

---

## 🎨 Styling CSS

### Classes principales

#### **Containers**
- `.sisme-cards-grid` - Container principal grille
- `.sisme-cards-carousel` - Container principal carrousel
- `.sisme-carousel__track` - Track de défilement carrousel
- `.sisme-carousel__slide` - Wrapper slide individuel

#### **Cartes**
- `.sisme-game-card` - Container carte de base
- `.sisme-game-card--normal` - Modificateur carte normale
- `.sisme-game-card--details` - Modificateur carte détaillée
- `.sisme-card-image` - Container image carte
- `.sisme-card-content` - Container contenu carte

#### **Badges**
- `.sisme-card-badge` - Badge overlay de base
- `.sisme-badge-new` - Badge nouveau/à venir
- `.sisme-badge-updated` - Badge aujourd'hui/mis à jour
- `.sisme-display__none` - Masquer badge

#### **Navigation carrousel**
- `.sisme-carousel__nav` - Container navigation
- `.sisme-carousel__btn` - Boutons prev/next
- `.sisme-carousel__pagination` - Container dots
- `.sisme-carousel__dot` - Dot individuel

### Variables CSS

#### **Design tokens**
```css
/* Chargées depuis tokens.css */
--theme-palette-color-1: #A1B78D;    /* Vert principal */
--theme-palette-color-2: #D4A373;    /* Orange secondaire */
--theme-palette-color-6: #2C3E50;    /* Texte sombre */
--theme-palette-color-8: #7F8C8D;    /* Texte clair */

/* Variables cartes */
--cards-per-row: 4;                   /* Nombre colonnes */
--card-aspect-ratio: 3/4;             /* Ratio image */
--card-border-radius: 8px;            /* Arrondi cartes */
```

#### **Responsive breakpoints**
```css
/* Mobile first */
@media (min-width: 768px) { /* Tablet */ }
@media (min-width: 1024px) { /* Desktop */ }
@media (min-width: 1200px) { /* Large desktop */ }
```

### Animations

#### **Effets de transition**
```css
/* Hover cartes */
.sisme-game-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
}

/* Animations carrousel */
.sisme-carousel__track {
    transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
```

#### **Animations GPU optimisées**
```css
/* Performance */
.sisme-carousel__slide {
    will-change: transform;
    transform: translateZ(0);
}
```

---

## 📊 Performance et Cache

### Chargement conditionnel

#### **Assets CSS/JS**
```php
// Chargement automatique selon contexte
public function should_load_assets() {
    return is_singular() || 
           has_shortcode($GLOBALS['post']->post_content ?? '', 'game_card') ||
           has_shortcode($GLOBALS['post']->post_content ?? '', 'game_cards_grid');
}
```

#### **Lazy loading**
```php
// Chargement différé images
wp_enqueue_script('sisme-lazy-loading');
```

### Cache et optimisation

#### **Cache WordPress transients**
```php
// Cache données jeux (5 minutes)
$cache_key = "sisme_cards_data_{$term_id}";
$cached_data = get_transient($cache_key);
if ($cached_data === false) {
    $data = $this->generate_game_data($term_id);
    set_transient($cache_key, $data, 300);
}
```

#### **Optimisation requêtes**
```php
// Requête optimisée avec meta_query
$terms = get_terms([
    'taxonomy' => 'post_tag',
    'fields' => 'ids',
    'meta_query' => [
        [
            'key' => 'game_description',
            'compare' => 'EXISTS'
        ]
    ]
]);
```

---

## 🔧 Installation et Configuration

### Prérequis techniques
- **WordPress** 5.0+
- **PHP** 7.4+
- **Module Sisme Games Editor** activé
- **Taxonomie post_tag** avec meta fields

### Installation automatique
```php
// Le module se charge automatiquement via le loader principal
// Aucune configuration manuelle requise
$loader = Sisme_Cards_Loader::get_instance();
```

### Tests fonctionnels
- [ ] Shortcodes s'affichent correctement
- [ ] Assets CSS/JS se chargent
- [ ] Navigation carrousel fonctionne
- [ ] Responsive design opérationnel
- [ ] Badges s'affichent selon dates
- [ ] Images se chargent correctement
- [ ] Liens vers fiches fonctionnent

---

## 🐛 Debug et Troubleshooting

### Mode debug

#### **Activation**
```html
<!-- Dans les shortcodes -->
[game_cards_grid debug="true"]

<!-- Ou en PHP -->
$args['debug'] = true;
```

#### **Logs de debug**
```php
// Logs automatiques si WP_DEBUG activé
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('[Sisme Cards] Message de debug');
}
```

### Problèmes courants

#### **Cartes ne s'affichent pas**
- Vérifier que les jeux ont `game_description` renseigné
- Contrôler que les images cover existent
- Vérifier les logs d'erreur WordPress

#### **Assets CSS/JS non chargés**
- Forcer le chargement avec `force_load_assets()`
- Vérifier les URLs des assets
- Contrôler les dépendances

#### **Carrousel ne fonctionne pas**
- Vérifier que JavaScript est activé
- Contrôler la console pour erreurs JS
- Vérifier la configuration carrousel

### Debug avancé
```php
// Tester critères de recherche
$test = Sisme_Cards_Functions::test_criteria([
    'genres' => ['action'],
    'max_results' => 5,
    'debug' => true
]);
var_dump($test);

// Analyser données d'un jeu
$game_data = Sisme_Cards_Functions::get_game_data(123);
var_dump($game_data);

// Statistiques globales
$stats = Sisme_Cards_Functions::get_games_stats_by_criteria();
var_dump($stats);
```

---

## 📞 Support et Contribution

### Problèmes connus
- **Compact cards** - En cours de développement
- **Pagination carrousel** - Amélioration prévue
- **Filtres avancés** - Extension future

### Évolutions prévues
- **Cartes compactes** pour widgets
- **Pagination** pour grandes grilles
- **Filtres interactifs** AJAX
- **Mode sombre** automatique
- **PWA support** pour mobile

### Documentation API
- Voir `cards-api.php` pour référence complète
- Consulter `cards-functions.php` pour utilitaires
- Examiner `cards-*.css` pour personnalisation styles

---

**📝 Note :** Cette documentation couvre la version 1.0.0 du module Cards. Pour les mises à jour et nouvelles fonctionnalités, consulter les logs de version du plugin principal Sisme Games Editor.