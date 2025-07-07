# Module SEO - Sisme Games Editor

## 🎯 Vue d'ensemble

Module SEO complet pour optimiser le référencement des fiches de jeu.

## 📁 Architecture

```
/includes/seo/
├── seo-loader.php           # Loader principal (Sisme_Seo_Loader)
├── seo-meta-tags.php        # Meta descriptions/keywords
├── seo-structured-data.php  # Schema.org VideoGame
├── seo-open-graph.php       # Partage social (FB/Twitter)
├── seo-title-optimizer.php  # Titres adaptatifs
├── seo-images.php           # Alt/title images
└── seo-sitemap.php          # Sitemap XML
```

## ⚙️ Activation

Dans `sisme-games-editor.php` :
```php
define('SISME_GAMES_MODULES', array(
    "utils", "vedettes", "cards", "search", "team-choice", "user",
    "seo"  // ← Ajouter cette ligne
));
```

## 🎮 Détection des pages de jeu

```php
// Méthode de détection
$game_sections = get_post_meta($post_id, '_sisme_game_sections', true);
$is_game_page = !empty($game_sections);
```

## 📊 Modules actifs

### Meta Tags (`seo-meta-tags.php`)
- **Description** : "Découvrez [Jeu], jeu [genre] (2024). [Description]... Téléchargez ce jeu indépendant sur Sisme Games."
- **Keywords** : "[Jeu], [genres], [plateformes], jeu indépendant, indie game"
- **Canonical** : URL propre de la page
- **Cache** : 1h avec invalidation auto

### Structured Data (`seo-structured-data.php`)
```json
{
  "@type": ["VideoGame", "SoftwareApplication"],
  "name": "Nom du Jeu",
  "genre": ["Action", "RPG"],
  "gamePlatform": ["PC", "Console"],
  "operatingSystem": ["Windows", "Linux"],
  "image": { "@type": "ImageObject", "url": "cover.jpg" },
  "trailer": { "@type": "VideoObject", "embedUrl": "youtube.com/..." }
}
```

### Open Graph (`seo-open-graph.php`)
- **Type** : `video.game`
- **Title** : "[Jeu] - [Genre] [Plateforme] | Sisme Games"  
- **Description** : "🎮 Découvrez [Jeu] ! [Description] 🎯 Téléchargez maintenant !"
- **Image** : Cover optimisée 1200x630
- **Twitter Cards** : `summary_large_image` ou `player`

### Title Optimizer (`seo-title-optimizer.php`)
**Stratégies adaptatives :**
- **Nom court** : "Celeste - Platformer Indie 2024 | Sisme Games"
- **Nom moyen** : "Hollow Knight - Metroidvania | Sisme Games"  
- **Nom long** : "The Elder Scrolls V: Skyrim | Sisme Games"
- **Limite** : 60 chars max, 55 chars idéal

### Images SEO (`seo-images.php`)
- **Cover** : "Cover de [Jeu], jeu [genre] indépendant à découvrir"
- **Screenshot** : "Screenshot de gameplay de [Jeu] - Jeu [genre] en action"
- **Section** : "Image de [Jeu] - [titre section]"
- **Lazy loading** : `loading="lazy" decoding="async"`

### Sitemap XML (`seo-sitemap.php`)
```
/sitemap.xml          # Index principal
/sitemap-pages.xml    # Pages normales (priorité 0.6)
/sitemap-games.xml    # Fiches de jeu (priorité 0.8)
```

## 🔧 APIs principales

### Loader
```php
// Vérifier si chargé
if (class_exists('Sisme_Seo_Loader')) {
    $health = Sisme_Seo_Loader::get_health_status();
    // $health['loaded_count'], $health['status']
}
```

### Détection de page
```php
// Utilitaires partagés
$is_game = Sisme_Seo_Loader::is_game_page($post_id);
$game_data = Sisme_Seo_Loader::get_current_game_data($post_id);
```

### Données de jeu
```php
// Structure game_data
$game_data = array(
    Sisme_Utils_Games::KEY_NAME => 'Nom du jeu',
    Sisme_Utils_Games::KEY_DESCRIPTION => 'Description',
    Sisme_Utils_Games::KEY_GENRES => [['name' => 'Action']],
    Sisme_Utils_Games::KEY_PLATFORMS => [['label' => 'PC']],
    Sisme_Utils_Games::KEY_COVERS => ['main' => 123],
    Sisme_Utils_Games::KEY_RELEASE_DATE => '2024-01-15'
);
```

## 🎯 Optimisations SEO

### Priorités des titres
1. **Nom du jeu** (obligatoire)
2. **Genres** (2 max, raccourcis : RPG, FPS, RTS)
3. **Plateforme principale** (PC > Console > Mobile)
4. **Année** (si récente ±1 an)
5. **"Jeu Indépendant"** (si pas déjà présent)

### Cache système
- **Meta tags** : 1h
- **Structured data** : 2h  
- **Open Graph** : 1h
- **Titres** : 30min
- **Sitemap** : 30min
- **Invalidation** : Auto sur save_post/edit_term

### Hooks d'invalidation
```php
// Cache nettoyé automatiquement sur :
add_action('save_post', 'clear_cache');          // Modification article
add_action('edited_term', 'clear_cache');        // Modification jeu
add_action('attachment_updated', 'clear_cache'); // Modification image
```

## 🚀 Statut de santé

```php
$health = Sisme_Seo_Loader::get_health_status();
// Retourne :
// - total_modules: 6
// - loaded_count: 6  
// - health_percentage: 100
// - status: 'healthy'|'partial'|'critical'
// - missing_modules: []
```

## 🔍 Debug & Logs

```php
// Logs automatiques si WP_DEBUG = true
[Sisme SEO] Modules chargés : 6/6 (100%) - Status: healthy
[Sisme SEO] ✓ Module 'Meta tags' chargé : seo-meta-tags.php
[Sisme SEO] ✓ Module 'Sitemap XML' chargé : seo-sitemap.php
```

## 📋 Dépendances

- **Obligatoires** : `Sisme_Utils_Games` (pour meta-tags, structured-data, open-graph, title-optimizer)
- **Optionnelles** : Images, sitemap (aucune dépendance)
- **WordPress** : Hooks `wp_head`, `template_redirect`, `init`

## 🎮 Cas d'usage

### Détection fiches de jeu
```php
// Une page est considérée comme "fiche de jeu" si :
$sections = get_post_meta($post_id, '_sisme_game_sections', true);
$is_game_fiche = !empty($sections);
```

### Articles non-gaming
```php
// Les articles normaux (blog) ne sont PAS traités par le SEO gaming
// Seules les fiches avec _sisme_game_sections sont optimisées
```

### Données manquantes
```php
// Gestion gracieuse si données incomplètes :
// - Pas de genre → Pas de mention genre dans title/meta
// - Pas de cover → Fallback sur image par défaut  
// - Pas de description → Meta description basée sur titre uniquement
```