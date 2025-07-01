# Utils Functions Registry

## 🎯 Vue d'ensemble

Le système Utils comprend **7 modules** chargés automatiquement par `utils-loader.php` :

1. **utils-validation.php** - Validation et sanitisation
2. **utils-formatting.php** - Formatage dates et textes
3. **utils-cache.php** - Gestion cache WordPress
4. **utils-wp.php** - Helpers WordPress
5. **utils-debug.php** - Logging et debug
6. **utils-games.php** - Données et métier jeux
7. **utils-users.php** - Données et métier utilisateurs

## 📋 Auto-loader : utils-loader.php

**Classe :** `Sisme_Utils_Loader`

<details>
<summary><code>Sisme_Utils_Loader::get_instance()</code></summary>

```php
// Singleton - Récupérer l'instance unique du loader
// @return Sisme_Utils_Loader Instance unique
$loader = Sisme_Utils_Loader::get_instance();
```
</details>

<details>
<summary><code>is_module_loaded($module_name)</code></summary>

```php
// Vérifier si un module utils est chargé
// @param string $module_name - Nom du module (avec ou sans .php)
// @return bool - Module chargé ou non
$is_loaded = $loader->is_module_loaded('utils-formatting');
```
</details>

<details>
<summary><code>get_system_health()</code></summary>

```php
// Diagnostics système complets
// @return array - Informations de santé système
$health = $loader->get_system_health();
// Keys: total_available, loaded_count, loading_success_rate, missing_modules, system_ready
```
</details>

<details>
<summary><code>get_debug_stats()</code></summary>

```php
// Stats détaillées pour debug
// @return array - Statistiques complètes
$stats = $loader->get_debug_stats();
// Keys: loader_initialized, modules_loaded_flag, health_check, memory_usage, etc.
```
</details>

---

## 📝 utils-formatting.php

**Classe :** `Sisme_Utils_Formatting`

**Constantes:**
```php
DEFAULT_TRUNCATE_LENGTH = 150
DEFAULT_DATE_FORMAT = 'j F Y'
DEFAULT_PLATEFORM_PC = '🖥️'
DEFAULT_PLATEFORM_CONSOLE = '🎮'
DEFAULT_PLATEFORM_MOBILE = '📱'
DEFAULT_PLATEFORM_WEB = '🌐'
```

<details>
<summary><code>truncate_smart($text, $max_length = 150)</code></summary>

```php
// Tronque intelligemment un texte sur les mots
// @param string $text - Texte à tronquer
// @param int $max_length - Longueur maximale
// @return string - Texte tronqué avec "..."
$truncated = Sisme_Utils_Formatting::truncate_smart($long_text, 100);
```
</details>

<details>
<summary><code>get_platform_icon($platform)</code></summary>

```php
// Obtient l'icône emoji d'une plateforme
// @param string $platform - Nom de la plateforme  
// @return string - Icône emoji (💻, 🎮, 📱, etc.)
$icon = Sisme_Utils_Formatting::get_platform_icon('windows');
```
</details>

<details>
<summary><code>build_css_class($base_class, $modifiers = [], $custom_class = '')</code></summary>

```php
// Génère classe CSS avec modificateurs BEM
// @param string $base_class - Classe de base
// @param array $modifiers - Modificateurs BEM
// @param string $custom_class - Classe custom
// @return string - Classes assemblées
$classes = Sisme_Utils_Formatting::build_css_class('card', ['large', 'featured'], 'my-card');
```
</details>

<details>
<summary><code>format_release_date($release_date)</code></summary>

```php
// Format court: "15 déc 2024"
// @param string $release_date - Date YYYY-MM-DD
// @return string - Date formatée
$short_date = Sisme_Utils_Formatting::format_release_date('2024-12-15');
```
</details>

<details>
<summary><code>format_release_date_long($release_date)</code></summary>

```php
// Format long: "15 décembre 2024"
// @param string $release_date - Date YYYY-MM-DD
// @return string - Date formatée
$long_date = Sisme_Utils_Formatting::format_release_date_long('2024-12-15');
```
</details>

<details>
<summary><code>format_release_date_with_status($release_date, $show_status = false)</code></summary>

```php
// Date avec statut: "✅ 15 déc 2024"
// @param string $release_date - Date YYYY-MM-DD
// @param bool $show_status - Afficher icône statut
// @return string - Date avec statut optionnel
$date_with_status = Sisme_Utils_Formatting::format_release_date_with_status('2024-12-15', true);
```
</details>

---

## 🎮 utils-games.php

**Classe :** `Sisme_Utils_Games`

**Constantes META:**
```php
META_COVER_MAIN = 'game_cover_main'
META_DESCRIPTION = 'game_description'
META_RELEASE_DATE = 'game_release_date'
META_PLATFORMS = 'game_platforms'
META_DEVELOPER = 'game_developer'
META_GENRES = 'game_genres'
META_EXTERNAL_LINK = 'game_external_link'
META_TRAILER_YOUTUBE = 'game_trailer_youtube'
META_SCREENSHOTS = 'game_screenshots'
META_TAGS = 'game_tags'
META_RATING = 'game_rating'
META_PRICE = 'game_price'
META_SYSTEM_REQUIREMENTS = 'game_system_requirements'
META_PROS = 'game_pros'
META_CONS = 'game_cons'
META_SCORE = 'game_score'
META_VERDICT = 'game_verdict'
META_TEAM_CHOICE = 'game_team_choice'
```

**Constantes API KEYS:**
```php
KEY_TERM_ID = 'term_id'
KEY_NAME = 'name'
KEY_SLUG = 'slug'
KEY_DESCRIPTION = 'description'
KEY_COVER_URL = 'cover_url'
KEY_COVER_ID = 'cover_id'
KEY_RELEASE_DATE = 'release_date'
KEY_PLATFORMS = 'platforms'
KEY_DEVELOPER = 'developer'
KEY_GENRES = 'genres'
KEY_EXTERNAL_LINK = 'external_link'
KEY_TRAILER_YOUTUBE = 'trailer_youtube'
KEY_SCREENSHOTS = 'screenshots'
KEY_TAGS = 'tags'
KEY_RATING = 'rating'
KEY_PRICE = 'price'
KEY_SYSTEM_REQUIREMENTS = 'system_requirements'
KEY_PROS = 'pros'
KEY_CONS = 'cons'
KEY_SCORE = 'score'
KEY_VERDICT = 'verdict'
KEY_IS_TEAM_CHOICE = 'is_team_choice'
KEY_GAME_URL = 'game_url'
KEY_TIMESTAMP = 'timestamp'
```

<details>
<summary><code>get_game_data($term_id)</code></summary>

```php
// 📊 Récupérer les données complètes d'un jeu
// @param int $term_id - ID du jeu (term_id)
// @return array|false - Données complètes du jeu avec clés API ou false
$game_data = Sisme_Utils_Games::get_game_data(125);
```
</details>

<details>
<summary><code>get_games_by_criteria($criteria)</code></summary>

```php
// 🔍 Rechercher des jeux selon des critères
// @param array $criteria - Critères de recherche
// @return array - Liste des jeux correspondants
$games = Sisme_Utils_Games::get_games_by_criteria([
    'genres' => ['action', 'rpg'],
    'platforms' => ['windows', 'mac'],
    'limit' => 10
]);
```
</details>

<details>
<summary><code>group_platforms_by_type($platforms)</code></summary>

```php
// 📱 Grouper les plateformes par type (PC, Console, Mobile, Web)
// @param array $platforms - Liste des plateformes
// @return array - Plateformes groupées avec icônes
$grouped = Sisme_Utils_Games::group_platforms_by_type(['windows', 'mac', 'xbox']);
```
</details>

<details>
<summary><code>validate_game_id($game_id, $context = '')</code></summary>

```php
// ✅ Valider qu'un ID de jeu existe
// @param int $game_id - ID du jeu à valider
// @param string $context - Contexte pour logs
// @return bool - True si valide
$is_valid = Sisme_Utils_Games::validate_game_id(125, 'notification');
```
</details>

---

## 👤 utils-users.php

**Classe :** `Sisme_Utils_Users`

**Constantes COLLECTIONS:**
```php
COLLECTION_FAVORITE = 'favorite'
COLLECTION_OWNED = 'owned'
COLLECTION_WISHLIST = 'wishlist'
COLLECTION_COMPLETED = 'completed'
```

**Constantes URLS:**
```php
LOGIN_URL = '/sisme-user-login/'
REGISTER_URL = '/sisme-user-register/'
PROFILE_URL = '/sisme-user-profil/'
DASHBOARD_URL = '/sisme-user-tableau-de-bord/'
```

<details>
<summary><code>validate_user_id($user_id, $context = '')</code></summary>

```php
// ✅ Valider qu'un ID utilisateur est valide et existe
// @param int $user_id - ID utilisateur à valider
// @param string $context - Contexte pour logs
// @return bool - True si utilisateur valide
$is_valid = Sisme_Utils_Users::validate_user_id(42, 'dashboard');
```
</details>

<details>
<summary><code>get_user_meta_with_default($user_id, $meta_key, $default, $context = '')</code></summary>

```php
// 📊 Récupérer métadonnée utilisateur avec valeur par défaut
// @param int $user_id - ID utilisateur
// @param string $meta_key - Clé métadonnée
// @param mixed $default - Valeur par défaut
// @param string $context - Contexte pour logs
// @return mixed - Valeur métadonnée ou défaut
$prefs = Sisme_Utils_Users::get_user_meta_with_default(42, 'sisme_user_preferences', [], 'dashboard');
```
</details>

<details>
<summary><code>get_user_collections($user_id)</code></summary>

```php
// 📚 Récupérer toutes les collections d'un utilisateur
// @param int $user_id - ID utilisateur
// @return array - Collections avec compteurs
$collections = Sisme_Utils_Users::get_user_collections(42);
// Keys: favorite, owned, wishlist, completed avec arrays de game_ids
```
</details>

<details>
<summary><code>is_game_in_user_collection($user_id, $game_id, $collection_type)</code></summary>

```php
// 🔍 Vérifier si un jeu est dans une collection utilisateur
// @param int $user_id - ID utilisateur
// @param int $game_id - ID du jeu
// @param string $collection_type - Type de collection
// @return bool - True si jeu dans collection
$is_favorite = Sisme_Utils_Users::is_game_in_user_collection(42, 125, 'favorite');
```
</details>

---

## 🔔 Bonus : sisme-notification-utils.php

**Classe :** `Sisme_Notification_Utils` (fichier spécial dans utils/)

<details>
<summary><code>get_users_with_notification_preference($notification_type)</code></summary>

```php
// 📋 Récupérer users avec préférence notification activée
// @param string $notification_type - Type de notification
// @return array - IDs des utilisateurs concernés
$user_ids = Sisme_Notification_Utils::get_users_with_notification_preference('new_indie_releases');
```
</details>

<details>
<summary><code>send_notification_to_users($user_ids, $game_id, $notification_type)</code></summary>

```php
// 📤 Envoyer notification à liste d'utilisateurs
// @param array $user_ids - IDs utilisateurs
// @param int $game_id - ID du jeu
// @param string $notification_type - Type notification
// @return array - Résultat avec statistiques
$result = Sisme_Notification_Utils::send_notification_to_users([42, 43], 125, 'new_game');
```
</details>

<details>
<summary><code>send_new_indie_release_notifications($game_id)</code></summary>

```php
// 🎯 Workflow automatique pour nouvelles sorties indépendantes
// @param int $game_id - ID du jeu publié
// @return array - Résultat avec stats
$result = Sisme_Notification_Utils::send_new_indie_release_notifications(125);
```
</details>

---

## 🔧 Modules Manquants

Basé sur la liste dans `utils-loader.php`, les modules suivants sont référencés mais les détails ne sont pas encore disponibles :

### utils-validation.php
- **Description :** Validation et sanitisation
- **Classe probable :** `Sisme_Utils_Validation`

### utils-cache.php
- **Description :** Gestion cache WordPress
- **Classe probable :** `Sisme_Utils_Cache`

### utils-wp.php
- **Description :** Helpers WordPress
- **Classe probable :** `Sisme_Utils_WP`

### utils-debug.php
- **Description :** Logging et debug
- **Classe probable :** `Sisme_Utils_Debug`

---

## 🚀 Utilisation

### Chargement Automatique
```php
// Le système utils se charge automatiquement via le plugin principal
// Hook: 'plugins_loaded' priorité 5

// Vérifier si le système est prêt
$loader = Sisme_Utils_Loader::get_instance();
if ($loader->is_ready()) {
    // Tous les modules sont chargés
}
```

### Diagnostic Système
```php
// Diagnostics complets
$health = Sisme_Utils_Loader::get_instance()->get_system_health();

if ($health['system_ready']) {
    echo "Système utils opérationnel";
} else {
    echo "Modules manquants: " . implode(', ', $health['missing_modules']);
}
```

### Logging Debug
```php
// Logs automatiques en mode WP_DEBUG
// [Sisme Utils] Système utils initialisé - 7/7 modules chargés
// [Sisme Utils] Module 'Formatage dates et textes' chargé : utils-formatting.php
```

---

## 📊 Patterns d'Usage

### Données Jeu
```php
// Récupération données complètes
$game_data = Sisme_Utils_Games::get_game_data(125);
$game_name = $game_data[Sisme_Utils_Games::KEY_NAME];
$release_date = $game_data[Sisme_Utils_Games::KEY_RELEASE_DATE];

// Formatage date
$formatted_date = Sisme_Utils_Formatting::format_release_date($release_date);
```

### Validation Utilisateur
```php
// Validation + récupération métadonnées
if (Sisme_Utils_Users::validate_user_id($user_id, 'dashboard')) {
    $collections = Sisme_Utils_Users::get_user_collections($user_id);
    $favorites_count = count($collections['favorite']);
}
```

### Notifications Automatiques
```php
// Workflow complet
$user_ids = Sisme_Notification_Utils::get_users_with_notification_preference('new_indie_releases');
$result = Sisme_Notification_Utils::send_notification_to_users($user_ids, 125, 'new_game');
echo "Notifications envoyées: {$result['stats']['sent']}/{$result['stats']['total']}";
```

---

## 🎯 Intégrations

### Avec Module Cards
```php
// Les fonctions utils sont utilisées dans le rendu des cartes
$game_data = Sisme_Utils_Games::get_game_data($term_id);
$platforms = Sisme_Utils_Games::group_platforms_by_type($game_data['platforms']);
```

### Avec Module User
```php
// Validation dans tous les modules user
Sisme_Utils_Users::validate_user_id($user_id, 'user-actions');
```

### Avec Module Search
```php
// Recherche utilise les critères games
$games = Sisme_Utils_Games::get_games_by_criteria($search_criteria);
```