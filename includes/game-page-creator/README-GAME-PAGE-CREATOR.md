# 🎮 Game Page Creator - API REF

**Version:** 1.0.0 | **Status:** Module autonome et fonctionnel  
Module de génération de pages de jeu avec structure HTML exacte et intégration WordPress complète.

---

## 📁 Structure du Module

```
includes/game-page-creator/
├── game-page-creator-loader.php       # Chargement automatique
├── game-page-creator.php              # API principale
├── game-page-creator-publisher.php    # Publisher WordPress
├── game-data-formatter.php            # Formatage données
├── game-page-renderer.php             # Rendu HTML exact
├── game-media-handler.php             # Gestion médias
├── game-sections-builder.php          # Construction sections
└── assets/game-page.css               # Styles frontend
```

---

## 🚀 API Principale

### game-page-creator.php

**Classe :** `Sisme_Game_Page_Creator`

<details>
<summary><code>create_page($term_id)</code></summary>

```php
// Créer la page HTML complète d'un jeu
// @param int $term_id - ID du terme jeu (post_tag)
// @return string|false - HTML de la page ou false si erreur

$html = Sisme_Game_Page_Creator::create_page(268);
if ($html) {
    echo $html; // Affiche la page complète avec structure exacte
}
```
</details>

<details>
<summary><code>can_create_page($term_id)</code></summary>

```php
// Vérifier si un jeu peut générer une page
// @param int $term_id - ID du terme jeu
// @return bool - Jeu valide pour génération

if (Sisme_Game_Page_Creator::can_create_page(268)) {
    echo "Page peut être générée";
}
```
</details>

<details>
<summary><code>get_formatted_data($term_id)</code></summary>

```php
// Obtenir les données formatées sans rendu
// @param int $term_id - ID du terme jeu
// @return array|false - Données formatées ou false

$data = Sisme_Game_Page_Creator::get_formatted_data(268);
// Structure: name, description, platforms, genres, sections, etc.
```
</details>

---

## 📝 Publisher WordPress

### game-page-creator-publisher.php

**Classe :** `Sisme_Game_Page_Creator_Publisher`

<details>
<summary><code>create_game_fiche($tag_id)</code></summary>

```php
// Créer une fiche complète (article WordPress + contenu)
// @param int $tag_id - ID du tag du jeu (doit exister)
// @return array - ['success' => bool, 'post_id' => int, 'message' => string, 'post_url' => string]

$result = Sisme_Game_Page_Creator_Publisher::create_game_fiche(268);

// Retour:
[
    'success' => true,
    'post_id' => 123,
    'message' => 'Fiche créée avec succès',
    'post_url' => 'https://games.sisme.fr/cyberpunk-2077/',
    'edit_url' => 'https://games.sisme.fr/wp-admin/post.php?post=123&action=edit'
]
```
</details>

<details>
<summary><code>update_game_fiche($post_id, $tag_id)</code></summary>

```php
// Mettre à jour une fiche existante
// @param int $post_id - ID de l'article
// @param int $tag_id - ID du tag du jeu
// @return array - ['success' => bool, 'message' => string]

$result = Sisme_Game_Page_Creator_Publisher::update_game_fiche(123, 268);
```
</details>

<details>
<summary><code>find_existing_fiche($tag_id)</code></summary>

```php
// Chercher si une fiche existe déjà pour un jeu
// @param int $tag_id - ID du tag du jeu
// @return int|false - ID de la fiche ou false si aucune

$existing_post_id = Sisme_Game_Page_Creator_Publisher::find_existing_fiche(268);
```
</details>

<details>
<summary><code>delete_game_fiche($post_id)</code></summary>

```php
// Supprimer une fiche et nettoyer
// @param int $post_id - ID de l'article à supprimer
// @return array - ['success' => bool, 'message' => string]

$result = Sisme_Game_Page_Creator_Publisher::delete_game_fiche(123);
```
</details>

<details>
<summary><code>get_fiches_stats()</code></summary>

```php
// Obtenir les statistiques des fiches
// @return array - ['total_fiches' => int]

$stats = Sisme_Game_Page_Creator_Publisher::get_fiches_stats();
echo "Total fiches: " . $stats['total_fiches'];
```
</details>

---

## 🔧 Modules Internes

### game-data-formatter.php

**Classe :** `Sisme_Game_Data_Formatter`

<details>
<summary><code>format_game_data($term_id)</code></summary>

```php
// Formater toutes les données d'un jeu
// @param int $term_id - ID du terme jeu
// @return array|false - Données formatées complètes

$data = Sisme_Game_Data_Formatter::format_game_data(268);

// Structure retournée:
[
    'id' => 268,
    'name' => 'Cyberpunk 2077',
    'description' => 'Jeu futuriste dans Night City...',
    'platforms' => [['key' => 'windows', 'icon' => '🖥️', 'tooltip' => 'Disponible sur Windows']],
    'genres' => [['id' => 75, 'name' => 'Action', 'url' => '/category/jeux-action/']],
    'modes' => [['key' => 0, 'label' => 'Solo']],
    'developers' => [['name' => 'CD Projekt RED', 'website' => 'https://cdprojektred.com']],
    'sections' => [['title' => 'Histoire', 'content' => '<p>Dans un futur...</p>', 'image_url' => '...']],
    'external_links' => ['steam' => ['url' => 'https://store.steampowered.com/...', 'available' => true]],
    'trailer_link' => 'https://youtube.com/watch?v=...',
    'screenshots' => [['url' => 'https://...', 'thumbnail' => 'https://...']],
    'covers' => ['main' => 'https://...', 'vertical' => 'https://...']
]
```
</details>

### game-page-renderer.php

**Classe :** `Sisme_Game_Page_Renderer`

<details>
<summary><code>render($game_data)</code></summary>

```php
// Générer le HTML avec structure exacte
// @param array $game_data - Données formatées du jeu
// @return string - HTML complet avec classes CSS identiques

$html = Sisme_Game_Page_Renderer::render($formatted_data);
// Génère: <div class="sisme-game-hero">...</div> avec structure exacte
```
</details>

### game-media-handler.php

**Classe :** `Sisme_Game_Media_Handler`

<details>
<summary><code>extract_youtube_id($url)</code></summary>

```php
// Extraire l'ID YouTube depuis une URL
// @param string $url - URL YouTube
// @return string|false - ID YouTube ou false si invalide

$youtube_id = Sisme_Game_Media_Handler::extract_youtube_id('https://youtube.com/watch?v=abc123');
// Retourne: 'abc123'
```
</details>

<details>
<summary><code>process_screenshot($attachment_id)</code></summary>

```php
// Traiter un screenshot WordPress
// @param int $attachment_id - ID de l'attachment
// @return array|false - Données du screenshot ou false

$screenshot = Sisme_Game_Media_Handler::process_screenshot(4342);
// Retourne: ['id' => 4342, 'url' => '...', 'thumbnail' => '...', 'alt' => 'Screenshot']
```
</details>

<details>
<summary><code>get_youtube_thumbnail($youtube_id, $quality = 'maxresdefault')</code></summary>

```php
// Obtenir l'URL du thumbnail YouTube
// @param string $youtube_id - ID YouTube
// @param string $quality - Qualité (default, mqdefault, hqdefault, sddefault, maxresdefault)
// @return string - URL du thumbnail

$thumbnail = Sisme_Game_Media_Handler::get_youtube_thumbnail('abc123', 'maxresdefault');
// Retourne: 'https://img.youtube.com/vi/abc123/maxresdefault.jpg'
```
</details>

### game-sections-builder.php

**Classe :** `Sisme_Game_Sections_Builder`

<details>
<summary><code>build_sections_html($sections)</code></summary>

```php
// Construire le HTML complet des sections
// @param array $sections - Sections du jeu
// @return string - HTML des sections ou chaîne vide

$html = Sisme_Game_Sections_Builder::build_sections_html($sections);
// Génère: <div class="sisme-game-sections">...</div>
```
</details>

<details>
<summary><code>is_valid_section($section)</code></summary>

```php
// Valider une section
// @param array $section - Données de la section
// @return bool - Section valide

$valid = Sisme_Game_Sections_Builder::is_valid_section([
    'title' => 'Histoire',
    'content' => 'Contenu de plus de 20 caractères...',
    'image_id' => 4342
]);
```
</details>

<details>
<summary><code>get_sections_stats($sections)</code></summary>

```php
// Obtenir les statistiques des sections
// @param array $sections - Liste des sections
// @return array - Statistiques détaillées

$stats = Sisme_Game_Sections_Builder::get_sections_stats($sections);
// Retourne: ['total' => 3, 'valid' => 2, 'with_images' => 1, 'average_content_length' => 150]
```
</details>

---

## 🔗 Hooks WordPress

### Hooks disponibles

```php
// Hook après publication de Game Data
add_action('sisme_game_data_published', 'callback', 10, 2);
// Paramètres: $term_id, $game_data

// Hook après création de fiche
add_action('sisme_game_fiche_created', 'callback', 10, 2);
// Paramètres: $post_id, $tag_id
```

### Intégration automatique

Le module s'intègre automatiquement avec :
- **Content Filter** - Détecte `_sisme_game_sections` pour remplacer le contenu
- **Game Data Creator** - Écoute la publication pour créer les fiches
- **Thème WordPress** - Préserve header, footer, navigation
- **Module SEO** - Fonctionne avec les articles standards

---

## 📊 Exemples d'Usage

### Créer une fiche complète

```php
// 1. Le terme du jeu doit déjà exister (via Game Data Creator)
$term_id = 268;

// 2. Créer la fiche WordPress
$result = Sisme_Game_Page_Creator_Publisher::create_game_fiche($term_id);

if ($result['success']) {
    echo "Fiche créée: " . $result['post_url'];
    echo "Éditer: " . $result['edit_url'];
} else {
    echo "Erreur: " . $result['message'];
}
```

### Générer uniquement le HTML

```php
// Pour usage dans un shortcode ou template personnalisé
$html = Sisme_Game_Page_Creator::create_page(268);

if ($html) {
    echo $html; // HTML complet avec structure exacte
}
```

### Récupérer et formater les données

```php
// Pour usage dans API REST ou autre
$data = Sisme_Game_Page_Creator::get_formatted_data(268);

if ($data) {
    echo "Jeu: " . $data['name'];
    echo "Plateformes: " . count($data['platforms']);
    echo "Sections: " . count($data['sections']);
}
```

### Statistiques et maintenance

```php
// Obtenir les stats globales
$stats = Sisme_Game_Page_Creator_Publisher::get_fiches_stats();
echo "Total fiches créées: " . $stats['total_fiches'];

// Vérifier si une fiche existe
$existing = Sisme_Game_Page_Creator_Publisher::find_existing_fiche(268);
if ($existing) {
    echo "Fiche existe déjà: " . get_permalink($existing);
}
```

---

## ⚙️ Configuration

### Assets CSS

Le module charge automatiquement :
```css
/includes/game-page-creator/assets/game-page.css
```

**Dépendances :** `sisme-frontend-tokens-global`

### Conditions de chargement

Les assets se chargent automatiquement quand :
- Article avec `_sisme_game_sections` meta
- Page de tag (terme de jeu)

### Intégration système

Ajout automatique via :
```php
// Dans sisme-games-editor.php
define('SISME_GAMES_MODULES', [
    // ...
    'game-page-creator'
]);
```

---

## 🚨 Notes Importantes

### Structure HTML Exacte

Le module génère **exactement** la même structure HTML que l'ancien système :
- Classes CSS identiques
- Hiérarchie DOM identique  
- Compatibilité CSS complète

### Intégration WordPress

Le module respecte les standards WordPress :
- Articles standard (`post_type = 'post'`)
- Tags et catégories assignés
- Images à la une définies
- Content filter pour rendu

### Performance

- Cache automatique des données formatées
- Assets chargés conditionnellement
- HTML généré à la demande

### Compatibilité

- **Thème :** Préservé (header, footer, navigation)
- **SEO :** Compatible (articles standards)
- **Plugins :** Compatible (hooks WordPress)