# Module SEO - API Reference

**Version:** 1.0.0 | **Status:** Module autonome et fonctionnel  
Module SEO complet pour l'optimisation du référencement des pages de jeux indépendants.

---

## 📁 Structure du Module

```
includes/seo/
├── seo-loader.php              # Loader principal et API publique
├── seo-game-detector.php       # Couche d'abstraction détection/données
├── seo-meta-tags.php           # Meta descriptions et keywords
├── seo-open-graph.php          # Balises sociales Facebook/Twitter
├── seo-title-optimizer.php     # Optimisation titres adaptatifs
├── seo-structured-data.php     # Schema.org VideoGame/Article
├── seo-sitemap.php             # Sitemap XML (main/pages/games)
├── seo-images.php              # Alt text et attributs images
├── seo-admin.php               # Page d'administration
└── assets/seo-admin.css        # Styles interface admin
```

---

## 🚀 API Principale

### seo-loader.php

**Classe :** `Sisme_SEO_Loader`

<details>
<summary><code>is_game_page($post_id = null)</code></summary>

```php
// Détecter si on est sur une page de jeu
// @param int|null $post_id - ID du post (null = post actuel)
// @return bool - True si c'est une page de jeu

if (Sisme_SEO_Loader::is_game_page()) {
    echo "Cette page est une fiche de jeu";
}

// Tester un post spécifique
if (Sisme_SEO_Loader::is_game_page(123)) {
    echo "Le post 123 est une page de jeu";
}
```
</details>

<details>
<summary><code>get_current_game_data($post_id = null)</code></summary>

```php
// Récupérer les données formatées du jeu
// @param int|null $post_id - ID du post (null = post actuel)  
// @return array|false - Données formatées ou false

$game_data = Sisme_SEO_Loader::get_current_game_data();
if ($game_data) {
    echo "Jeu: " . $game_data['name'];
    echo "Genres: " . count($game_data['genres']);
    echo "Description: " . $game_data['description'];
}

// Structure retournée:
[
    'id' => 268,
    'name' => 'Hollow Knight',
    'description' => 'Metroidvania dans un royaume souterrain...',
    'genres' => [['id' => 75, 'name' => 'Metroidvania', 'url' => '...']],
    'platforms' => [['key' => 'windows', 'label' => 'Windows']],
    'release_date' => '24 février 2017',
    'covers' => ['main' => 'https://...'],
    'screenshots' => [['id' => 123, 'url' => '...', 'alt' => '...']],
    'trailer_link' => 'https://youtube.com/...',
    'sections' => [['title' => 'Histoire', 'content' => '...']]
]
```
</details>

<details>
<summary><code>get_game_term_id($post_id = null)</code></summary>

```php
// Récupérer l'ID du terme de jeu associé à un post
// @param int|null $post_id - ID du post (null = post actuel)
// @return int|false - Term ID ou false

$term_id = Sisme_SEO_Loader::get_game_term_id(123);
if ($term_id) {
    $term = get_term($term_id, 'post_tag');
    echo "Terme associé: " . $term->name;
}
```
</details>

<details>
<summary><code>get_health_status()</code></summary>

```php
// Obtenir le statut de santé du module SEO
// @return array - Statistiques détaillées

$health = Sisme_SEO_Loader::get_health_status();
echo "Modules: " . $health['loaded_count'] . "/" . $health['total_modules'];
echo "Status: " . $health['status']; // 'healthy', 'partial', 'critical'
echo "Pourcentage: " . $health['health_percentage'] . "%";

// Structure complète:
[
    'total_modules' => 6,
    'loaded_count' => 6, 
    'health_percentage' => 100,
    'status' => 'healthy',
    'loaded_modules' => ['seo-meta-tags.php', '...'],
    'system_dependencies' => true
]
```
</details>

<details>
<summary><code>clear_cache($post_id)</code></summary>

```php
// Nettoyer le cache SEO pour un post spécifique
// @param int $post_id - ID du post
// @return void

Sisme_SEO_Loader::clear_cache(123);
```
</details>

<details>
<summary><code>clear_all_cache()</code></summary>

```php
// Nettoyer tout le cache SEO
// @return void

Sisme_SEO_Loader::clear_all_cache();
```
</details>

<details>
<summary><code>debug_page($post_id = null)</code></summary>

```php
// Debug complet d'une page
// @param int|null $post_id - ID du post (null = post actuel)
// @return array - Informations de debug

$debug = Sisme_SEO_Loader::debug_page(123);
print_r($debug);

// Retourne:
[
    'seo_health' => [...],
    'page_detection' => [
        'post_id' => 123,
        'is_game_page' => true,
        'term_id' => 268,
        'game_name' => 'Hollow Knight'
    ],
    'cache_stats' => ['total_cached' => 15],
    'current_post' => ['ID' => 123, 'post_type' => 'post']
]
```
</details>

---

## 🔧 Modules Internes

### seo-game-detector.php

**Classe :** `Sisme_SEO_Game_Detector`

<details>
<summary><code>is_game_page($post_id = null)</code></summary>

```php
// Détection native avec cache
// @param int|null $post_id - ID du post
// @return bool - True si page de jeu

$is_game = Sisme_SEO_Game_Detector::is_game_page(123);
```
</details>

<details>
<summary><code>get_game_data($post_id = null)</code></summary>

```php
// Récupération données avec cache
// @param int|null $post_id - ID du post  
// @return array|false - Données formatées

$data = Sisme_SEO_Game_Detector::get_game_data(123);
```
</details>

<details>
<summary><code>clear_cache($post_id)</code></summary>

```php
// Nettoyer cache spécifique
// @param int $post_id - ID du post

Sisme_SEO_Game_Detector::clear_cache(123);
```
</details>

<details>
<summary><code>get_cache_stats()</code></summary>

```php
// Statistiques du cache
// @return array - Stats détaillées

$stats = Sisme_SEO_Game_Detector::get_cache_stats();
// ['memory_detection' => 5, 'transient_data' => 10, 'total_cached' => 15]
```
</details>

### seo-title-optimizer.php

**Classe :** `Sisme_SEO_Title_Optimizer`

<details>
<summary><code>debug_title_variations($post_id = null)</code></summary>

```php
// Debug des variations de titre possibles
// @param int|null $post_id - ID du post
// @return array - Toutes les variations avec longueurs

$variations = $optimizer->debug_title_variations(123);
foreach ($variations as $type => $data) {
    echo $type . ": " . $data['title'] . " (" . $data['length'] . " chars)\n";
    echo "Optimal: " . ($data['optimal'] ? 'Oui' : 'Non') . "\n";
}
```
</details>

### seo-sitemap.php

**Classe :** `Sisme_SEO_Sitemap`

<details>
<summary><code>get_sitemap_stats()</code></summary>

```php
// Statistiques du sitemap
// @return array - Stats complètes

$sitemap = new Sisme_SEO_Sitemap();
$stats = $sitemap->get_sitemap_stats();

[
    'total_pages' => 150,
    'game_pages' => 45,
    'normal_pages' => 105,
    'cache_status' => 'active', // 'active' ou 'expired'
    'last_generated' => 'Moins de 30min'
]
```
</details>

<details>
<summary><code>get_sitemap_urls()</code></summary>

```php
// URLs des sitemaps
// @return array - URLs disponibles

$urls = $sitemap->get_sitemap_urls();
// ['main' => '/sitemap.xml', 'pages' => '/sitemap-pages.xml', 'games' => '/sitemap-games.xml']
```
</details>

<details>
<summary><code>validate_sitemap_access()</code></summary>

```php
// Valider l'accès aux sitemaps
// @return array - Résultats de validation

$validation = $sitemap->validate_sitemap_access();
foreach ($validation as $type => $result) {
    echo "$type: " . $result['status'] . " (Code: " . $result['status_code'] . ")\n";
}
```
</details>

### seo-images.php

**Classe :** `Sisme_SEO_Images`

<details>
<summary><code>get_images_stats()</code></summary>

```php
// Statistiques des images optimisées
// @return array - Stats du cache images

$images = new Sisme_SEO_Images();
$stats = $images->get_images_stats();

[
    'cached_images' => 25,
    'cache_duration' => 7200,
    'supported_types' => ['cover', 'screenshot', 'section', 'featured']
]
```
</details>

<details>
<summary><code>debug_page_images($post_id = null)</code></summary>

```php
// Analyser les images d'une page de jeu
// @param int|null $post_id - ID du post
// @return array - Analyse détaillée

$analysis = $images->debug_page_images(123);
echo "Jeu: " . $analysis['game_name'] . "\n";
echo "Covers: " . count($analysis['covers']) . "\n";
echo "Screenshots: " . count($analysis['screenshots']) . "\n";
```
</details>

---

## ⚙️ Configuration et Activation

### Activation du Module

Dans `sisme-games-editor.php` :
```php
define('SISME_GAMES_MODULES', array(
    "utils", "vedettes", "cards", "search", "team-choice", "user",
    "game-data-creator", "game-page-creator",
    "seo"  // ← Ajouter cette ligne
));
```

### URLs des Sitemaps

Le module génère automatiquement :
- `/sitemap.xml` - Index principal
- `/sitemap-pages.xml` - Pages normales et articles blog
- `/sitemap-games.xml` - Fiches de jeu uniquement

### Templates SEO Générés

**Meta descriptions :**
```
"Découvrez [Jeu], jeu [Genre] ([Année]). [Description] Référencé sur Sisme Games."
```

**Titres optimisés :**
```
"[Jeu] - [Genre] ([Année]) | Sisme Games"     # Priorité 1
"[Jeu] - [Genre] | Sisme Games"               # Priorité 2  
"[Jeu] - Jeu Indépendant | Sisme Games"       # Fallback
```

**Alt text images :**
```
Cover: "Cover de [Jeu], jeu [Genre] indépendant à découvrir sur Sisme Games"
Screenshot: "Screenshot de gameplay de [Jeu] - Jeu [Genre] en action"  
Section: "Image de [Jeu] - [Titre Section]"
Featured: "[Jeu] - Jeu indépendant mis en avant sur Sisme Games"
```

---

## 📊 Interface d'Administration

### Accès à l'Interface

**Menu :** Sisme Games → 🔍 SEO  
**URL :** `/wp-admin/admin.php?page=sisme-games-seo`  
**Permission :** `manage_options`

### Fonctionnalités Admin

**Monitoring :**
- État de santé SEO (6 modules)
- Statistiques de cache en temps réel
- Status des sitemaps XML

**Actions :**
- Vider le cache SEO complet
- Tester une page de jeu aléatoirement
- Accès direct aux sitemaps

**Debug :**
- Test SEO de pages individuelles
- Analyse de détection des jeux
- Validation des URLs de sitemap

---

## 🎯 Optimisations SEO

### Mots-clés Ciblés

**Priorité 1 :** Nom du jeu + Genre  
**Priorité 2 :** "jeu indépendant", "indie game", "jeux indé"  
**Priorité 3 :** Développeurs, plateformes principales  
**Priorité 4 :** "découvrir jeux indies"

### Cache Performance

**Durées de cache :**
- Meta tags : 1h (3600s)
- Données structured : 2h (7200s)
- Sitemaps : 30min (1800s)
- Images : 2h (7200s)

**Invalidation automatique :**
- `save_post` → Nettoie le cache du post
- `edited_term` → Nettoie tous les posts liés au terme
- Hook `sisme_seo_clear_cache` → Nettoie sur demande

---

## 🔍 Exemples d'Usage

### Vérification SEO Basique

```php
// Vérifier si SEO actif pour une page
if (Sisme_SEO_Loader::is_game_page(123)) {
    $data = Sisme_SEO_Loader::get_current_game_data(123);
    echo "SEO actif pour: " . $data['name'];
}
```

### Monitoring Automatisé

```php
// Check santé SEO
$health = Sisme_SEO_Loader::get_health_status();
if ($health['status'] !== 'healthy') {
    error_log('SEO module pas optimal: ' . $health['health_percentage'] . '%');
}

// Stats cache
$cache = Sisme_SEO_Game_Detector::get_cache_stats();
if ($cache['total_cached'] > 100) {
    error_log('Cache SEO volumineux: ' . $cache['total_cached'] . ' éléments');
}
```

### Debug Complet

```php
// Debug d'une page problématique
$debug = Sisme_SEO_Loader::debug_page(123);
if (!$debug['page_detection']['is_game_page']) {
    echo "Page non détectée comme jeu";
}

if ($debug['seo_health']['status'] !== 'healthy') {
    echo "Problème modules SEO";
}
```

### Maintenance Cache

```php
// Nettoyage périodique
add_action('wp_scheduled_delete', function() {
    // Nettoyer le cache SEO une fois par semaine
    if (rand(1, 100) === 1) {
        Sisme_SEO_Loader::clear_all_cache();
    }
});
```

---

## ⚠️ Notes Importantes

### Dépendances Système

Le module SEO requiert :
- **game-page-creator** : `Sisme_Game_Data_Formatter` pour les données
- **seo-game-detector** : Couche d'abstraction (incluse automatiquement)

### Compatibilité

- **WordPress** : 5.0+ (utilise les hooks modernes)
- **PHP** : 7.4+ (syntaxe moderne)
- **Thème** : Indépendant (n'interfère pas avec le design)

### Performance

- **Cache intelligent** : Évite les requêtes répétées
- **Chargement conditionnel** : SEO actif uniquement sur les pages de jeu
- **Optimisation requêtes** : Une seule requête pour récupérer toutes les données d'un jeu

### Logs Debug

Avec `WP_DEBUG = true`, le module log automatiquement :
```
[Sisme SEO] Système SEO initialisé - 6/6 modules chargés
[Sisme SEO] Module chargé : Meta tags et descriptions automatiques (seo-meta-tags.php)
[Sisme SEO] Cache sitemap nettoyé
```