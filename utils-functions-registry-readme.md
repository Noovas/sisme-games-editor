# Utils Functions Registry

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
<summary><code>get_loaded_modules()</code></summary>

```php
// Obtenir la liste des modules utils chargés
// @return array - Liste des modules avec descriptions
$modules = $loader->get_loaded_modules();
```
</details>

<details>
<summary><code>get_loaded_modules_count()</code></summary>

```php
// Obtenir le nombre de modules chargés
// @return int - Nombre de modules
$count = $loader->get_loaded_modules_count();
```
</details>

<details>
<summary><code>get_system_health()</code></summary>

```php
// Diagnostics système complets
// @return array - Informations de santé système
// Keys: total_available, loaded_count, loading_success_rate, missing_modules, loaded_modules, system_ready
$health = $loader->get_system_health();
```
</details>

<details>
<summary><code>get_debug_stats()</code></summary>

```php
// Stats détaillées pour debug
// @return array - Statistiques complètes
// Keys: loader_initialized, modules_loaded_flag, health_check, memory_usage, wp_debug_enabled, php_version, wordpress_version
$stats = $loader->get_debug_stats();
```
</details>

<details>
<summary><code>reload_modules()</code></summary>

```php
// Forcer le rechargement de tous les modules
// @return void
$loader->reload_modules();
```
</details>

<details>
<summary><code>is_ready()</code></summary>

```php
// Vérifier si le système utils est prêt
// @return bool - Système prêt à l'usage (minimum 3 modules)
$ready = $loader->is_ready();
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
// ✂️ Tronquer intelligemment un texte sur les mots
// @param string $text - Texte à tronquer
// @param int $max_length - Longueur maximale (défaut: 150)
// @return string - Texte tronqué avec points de suspension
$truncated = Sisme_Utils_Formatting::truncate_smart($long_text, 100);
```
</details>

<details>
<summary><code>get_platform_icon($platform)</code></summary>

```php
// 🎮 Obtenir l'icône d'une plateforme
// @param string $platform - Nom de la plateforme (windows, mac, linux, playstation, xbox, nintendo-switch, ios, android, web)
// @return string - Icône emoji de la plateforme
$icon = Sisme_Utils_Formatting::get_platform_icon('windows'); // Retourne '🖥️'
```
</details>

<details>
<summary><code>build_css_class($base_class, $modifiers = [], $custom_class = '')</code></summary>

```php
// 🎨 Générer une classe CSS avec modificateurs et classe personnalisée
// @param string $base_class - Classe CSS de base
// @param array $modifiers - Modificateurs BEM (optionnel)
// @param string $custom_class - Classe CSS personnalisée (optionnel)
// @return string - Classes CSS assemblées
$classes = Sisme_Utils_Formatting::build_css_class('card', ['large', 'featured'], 'my-card');
// Retourne: 'card card--large card--featured my-card'
```
</details>

<details>
<summary><code>format_release_date($release_date)</code></summary>

```php
// 📅 Formater une date de sortie - Format court
// @param string $release_date - Date au format YYYY-MM-DD
// @return string - Date formatée (ex: "15 déc 2024") ou chaîne vide
$short_date = Sisme_Utils_Formatting::format_release_date('2024-12-15');
```
</details>

<details>
<summary><code>format_release_date_long($release_date)</code></summary>

```php
// 📅 Formater une date de sortie - Format long
// @param string $release_date - Date au format YYYY-MM-DD
// @return string - Date formatée (ex: "15 décembre 2024") ou chaîne vide
$long_date = Sisme_Utils_Formatting::format_release_date_long('2024-12-15');
```
</details>

<details>
<summary><code>format_release_date_with_status($release_date, $show_status = false)</code></summary>

```php
// 📅 Formater une date avec statut (sorti/à venir)
// @param string $release_date - Date au format YYYY-MM-DD
// @param bool $show_status - Afficher le statut avec icône
// @return string - Date formatée avec statut optionnel
// Ex: "✅ 15 déc 2024" (sorti) ou "📅 15 déc 2024" (à venir)
$date_with_status = Sisme_Utils_Formatting::format_release_date_with_status('2024-12-15', true);
```
</details>

<details>
<summary><code>convert_genre_ids_to_slugs($genre_ids)</code></summary>

```php
// 🏷️ Convertir les IDs de genres en noms
// @param array $genre_ids - Liste des IDs de genres
// @return array - Liste des noms de genres (préfixe "jeux-" supprimé)
$genre_names = Sisme_Utils_Formatting::convert_genre_ids_to_slugs([15, 16, 17]);
```
</details>

---

## 🎮 utils-games.php

**Classe :** `Sisme_Utils_Games`

**Constantes COLLECTIONS:**
```php
COLLECTION_FAVORITE = 'favorite'
COLLECTION_OWNED = 'owned'
```

**Constantes META KEYS (Stockage WordPress):**
```php
META_DESCRIPTION = 'game_description'
META_COVER_MAIN = 'cover_main'
META_COVER_NEWS = 'cover_news'
META_COVER_PATCH = 'cover_patch'
META_COVER_TEST = 'cover_test'
META_RELEASE_DATE = 'release_date'
META_LAST_UPDATE = 'last_update'
META_PLATFORMS = 'game_platforms'
META_GENRES = 'game_genres'
META_MODES = 'game_modes'
META_TEAM_CHOICE = 'is_team_choice'
META_EXTERNAL_LINKS = 'external_links'
META_TRAILER_LINK = 'trailer_link'
META_SCREENSHOTS = 'screenshots'
META_DEVELOPERS = 'game_developers'
META_PUBLISHERS = 'game_publishers'
```

**Constantes API KEYS (Interface publique):**
```php
KEY_TERM_ID = 'term_id'
KEY_ID = 'id'
KEY_NAME = 'name'
KEY_TITLE = 'title'
KEY_SLUG = 'slug'
KEY_DESCRIPTION = 'description'
KEY_COVER_URL = 'cover_url'
KEY_COVER_ID = 'cover_id'
KEY_GAME_URL = 'game_url'
KEY_RELEASE_DATE = 'release_date'
KEY_LAST_UPDATE = 'last_update'
KEY_TIMESTAMP = 'timestamp'
KEY_GENRES = 'genres'
KEY_MODES = 'modes'
KEY_PLATFORMS = 'platforms'
KEY_IS_TEAM_CHOICE = 'is_team_choice'
KEY_EXTERNAL_LINKS = 'external_links'
KEY_TRAILER_LINK = 'trailer_link'
KEY_SCREENSHOTS = 'screenshots'
KEY_DEVELOPERS = 'developers'
KEY_PUBLISHERS = 'publishers'
KEY_COVERS = 'covers'
KEY_RELEASE_STATUS = 'release_status'
```

**Constantes COVERS SUB-KEYS:**
```php
KEY_COVER_MAIN = 'main'
KEY_COVER_NEWS = 'news'
KEY_COVER_PATCH = 'patch'
KEY_COVER_TEST = 'test'
```

<details>
<summary><code>get_game_genres($term_id)</code></summary>

```php
// 🏷️ Récupérer les genres d'un jeu
// @param int $term_id - ID du jeu (term_id)
// @return array - Genres formatés avec id, name, slug (préfixe "jeux-" nettoyé)
$genres = Sisme_Utils_Games::get_game_genres(125);
```
</details>

<details>
<summary><code>get_game_modes($term_id)</code></summary>

```php
// 🎯 Récupérer les modes de jeu
// @param int $term_id - ID du jeu (term_id)
// @return array - Modes formatés avec key et label
// Labels disponibles: Solo, Multijoueur, Coopération, Compétitif, En ligne, Local
$modes = Sisme_Utils_Games::get_game_modes(125);
```
</details>

<details>
<summary><code>get_game_platforms_grouped($term_id)</code></summary>

```php
// 🎮 Récupérer les plateformes groupées par famille
// @param int $term_id - ID du jeu (term_id)
// @return array - Plateformes groupées avec icônes et tooltips
// Groupes: pc, console, mobile, web
$platforms = Sisme_Utils_Games::get_game_platforms_grouped(125);
```
</details>

<details>
<summary><code>get_game_data($term_id)</code></summary>

```php
// 📊 Récupérer les données complètes d'un jeu
// @param int $term_id - ID du jeu (term_id)
// @return array|false - Données complètes du jeu avec clés API ou false si invalide
$game_data = Sisme_Utils_Games::get_game_data(125);
```
</details>

<details>
<summary><code>get_game_release_status($term_id)</code></summary>

```php
// 📅 Détermine le statut de sortie d'un jeu
// @param int $term_id - ID du jeu
// @return array - Statut avec is_released, release_date, days_diff, status_text
$status = Sisme_Utils_Games::get_game_release_status(125);
```
</details>

<details>
<summary><code>get_game_badge($game_data)</code></summary>

```php
// 🏆 Détermine le badge d'un jeu selon sa fraîcheur
// @param array $game_data - Données complètes du jeu
// @return array - Badge avec class et text ou array vide
$badge = Sisme_Utils_Games::get_game_badge($game_data);
```
</details>

<details>
<summary><code>sort_games_by_release_date($term_ids, $order = 'desc')</code></summary>

```php
// 📈 Trie les jeux par date de sortie
// @param array $term_ids - IDs des termes
// @param string $order - Ordre de tri : 'desc' ou 'asc'
// @return array - IDs triés par date
$sorted_ids = Sisme_Utils_Games::sort_games_by_release_date($game_ids, 'desc');
```
</details>

<details>
<summary><code>get_games_by_criteria($criteria = [])</code></summary>

```php
// 🔍 Récupérer les IDs des jeux selon les critères
// @param array $criteria - Critères de recherche
// Critères disponibles: genres, is_team_choice, sort_by_date, sort_order, max_results, released, debug
// @return array - IDs des jeux trouvés
$games = Sisme_Utils_Games::get_games_by_criteria([
    'genres' => ['action', 'rpg'],
    'max_results' => 10,
    'sort_by_date' => true
]);
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

**Constantes META:**
```php
META_PREFIX = 'sisme_user_'
META_FRIENDS_LIST = 'sisme_user_friends_list'
```

**Constantes URLS:**
```php
LOGIN_URL = '/sisme-user-login/'
REGISTER_URL = '/sisme-user-register/'
PROFILE_URL = '/sisme-user-profil/'
DASHBOARD_URL = '/sisme-user-tableau-de-bord/'
FORGOT_URL = '/sisme-user-forgot-password/'
RESET_URL = '/sisme-user-reset-password/'
```

**Constantes MESSAGES:**
```php
DEFAULT_LOGIN_REQUIRED_MESSAGE = 'Vous devez être connecté pour accéder à cette page.'
DEFAULT_DASHBOARD_LOGIN_MESSAGE = 'Vous devez être connecté pour accéder à votre dashboard.'
```

<details>
<summary><code>validate_user_id($user_id, $context = '')</code></summary>

```php
// ✅ Valider qu'un ID utilisateur est valide et que l'utilisateur existe
// @param int $user_id - ID de l'utilisateur à valider
// @param string $context - Contexte pour les logs (optionnel)
// @return bool - True si l'utilisateur est valide, false sinon
$is_valid = Sisme_Utils_Users::validate_user_id(42, 'dashboard');
```
</details>

<details>
<summary><code>get_user_meta_with_default($user_id, $meta_key, $default, $context = '')</code></summary>

```php
// 📊 Récupérer une métadonnée utilisateur avec valeur par défaut
// @param int $user_id - ID de l'utilisateur
// @param string $meta_key - Clé de la métadonnée
// @param mixed $default - Valeur par défaut si la métadonnée n'existe pas
// @param string $context - Contexte pour les logs (optionnel)
// @return mixed - Valeur de la métadonnée ou valeur par défaut
$prefs = Sisme_Utils_Users::get_user_meta_with_default(42, 'sisme_user_preferences', [], 'dashboard');
```
</details>

<details>
<summary><code>render_login_required($message = '', $login_url = '', $register_url = '')</code></summary>

```php
// 🔒 Rendu du message demandant une connexion utilisateur
// @param string $message - Message personnalisé (optionnel)
// @param string $login_url - URL de connexion personnalisée (optionnel)
// @param string $register_url - URL d'inscription personnalisée (optionnel)
// @return string - HTML du message de connexion requise
$html = Sisme_Utils_Users::render_login_required();
```
</details>

<details>
<summary><code>get_user_by_slug($slug)</code></summary>

```php
// 🔍 Obtenir un utilisateur par son slug (user_nicename)
// @param string $slug - Slug de l'utilisateur
// @return WP_User|false - Utilisateur ou false si non trouvé
$user = Sisme_Utils_Users::get_user_by_slug('pseudo-utilisateur');
```
</details>

<details>
<summary><code>get_user_profile_url($user, $section = 'overview')</code></summary>

```php
// 🔗 Obtenir l'URL du profil d'un utilisateur avec section optionnelle
// @param int|WP_User $user - ID utilisateur ou objet WP_User
// @param string $section - Section du profil à afficher (défaut: overview)
// @return string - URL du profil avec hash de section
$profile_url = Sisme_Utils_Users::get_user_profile_url(42, 'overview');
// Retourne: "/sisme-user-profil/?user=pseudo-utilisateur#overview"
```
</details>

<details>
<summary><code>get_current_user_profile_url()</code></summary>

```php
// 👤 Obtenir l'URL du profil de l'utilisateur connecté
// @return string - URL du profil ou chaîne vide si non connecté
$my_profile = Sisme_Utils_Users::get_current_user_profile_url();
```
</details>

<details>
<summary><code>search_users_by_display_name($search_term, $max_results = 10)</code></summary>

```php
// 🔍 Rechercher des utilisateurs par display_name avec WP_User_Query
// @param string $search_term - Terme de recherche (minimum 2 caractères)
// @param int $max_results - Nombre maximum de résultats (défaut: 10, max: 50)
// @return array - Liste des utilisateurs [['id' => int, 'display_name' => string, 'user_nicename' => string, 'profile_url' => string, 'avatar_url' => string]]
$users = Sisme_Utils_Users::search_users_by_display_name('alice', 5);
$results = Sisme_Utils_Users::search_users_by_display_name('jean');
$results = Sisme_Utils_Users::search_users_by_display_name('marie', 3);
$results = Sisme_Utils_Users::search_users_by_display_name('a'); // [] (minimum 2 caractères)
```
</details>

---

## 🔍 utils-filters.php

**Classe :** `Sisme_Utils_Filters`

<details>
<summary><code>filter_by_search_term($game_ids, $search_term)</code></summary>

```php
// 🔍 Filtrer les jeux par terme de recherche textuelle
// @param array $game_ids - IDs des jeux à filtrer
// @param string $search_term - Terme de recherche
// @return array - IDs des jeux correspondants
$matching_games = Sisme_Utils_Filters::filter_by_search_term([123, 124, 125], 'after');
```
</details>

<details>
<summary><code>apply_sorting($games, $sort_type)</code></summary>

```php
// 📊 Appliquer le tri aux résultats
// @param array $games - Liste des IDs de jeux
// @param string $sort_type - Type de tri ('relevance', 'name_asc', 'name_desc', 'date_asc', 'date_desc')
// @return array - IDs des jeux triés
$sorted_games = Sisme_Utils_Filters::apply_sorting([123, 124, 125], 'name_asc');
```
</details>

<details>
<summary><code>get_search_summary($params, $total_results)</code></summary>

```php
// 📋 Générer un résumé de recherche lisible
// @param array $params - Paramètres de recherche
// @param int $total_results - Nombre total de résultats
// @return string - Résumé lisible
$summary = Sisme_Utils_Filters::get_search_summary(['query' => 'after'], 1);
// Retourne: "1 jeu trouvé pour "after""
```
</details>

---

## 🔔 utils-notifications.php

**Classe :** `Sisme_Utils_Notification`

<details>
<summary><code>get_users_with_notification_preference($notification_type)</code></summary>

```php
// 👥 Récupérer la liste des utilisateurs avec une préférence notification activée
// @param string $notification_type - Type de notification ('new_indie_releases', 'new_games_in_genres', etc.)
// @return array - IDs des utilisateurs avec cette préférence activée
$user_ids = Sisme_Utils_Notification::get_users_with_notification_preference('new_indie_releases');
```
</details>

<details>
<summary><code>send_notification_to_users($user_ids, $game_id, $notification_type = 'new_game')</code></summary>

```php
// 📤 Envoyer une notification à une liste d'utilisateurs
// @param array $user_ids - Liste des IDs utilisateurs
// @param int $game_id - ID du jeu
// @param string $notification_type - Type de notification
// @return array - Résultat avec statistiques ['success', 'message', 'stats']
$result = Sisme_Utils_Notification::send_notification_to_users([1, 2, 3], 125, 'new_game');
```
</details>

<details>
<summary><code>send_new_game_notification($game_id)</code></summary>

```php
// 🎮 Envoyer notification pour nouveau jeu avec logique de filtrage par genres
// @param int $game_id - ID du jeu publié
// @return array - Résultat de l'envoi avec statistiques
// Logique: Users avec 'new_indie_releases' activé + filtrage optionnel par genres si 'new_games_in_genres' activé
$result = Sisme_Utils_Notification::send_new_game_notification(125);
```
</details>

<details>
<summary><code>on_game_published($new_status, $old_status, $term)</code></summary>

```php
// 🔄 Hook automatique lors de la publication d'un jeu (term)
// @param string $new_status - Nouveau statut
// @param string $old_status - Ancien statut
// @param WP_Term $term - Term du jeu
// Se déclenche automatiquement via WordPress hooks
```
</details>

<details>
<summary><code>on_post_saved($post_id)</code></summary>

```php
// 📝 Hook automatique après sauvegarde complète d'un post (tags inclus)
// @param int $post_id - ID du post publié
// Envoie notifications unifiées pour nouveaux posts avec tags jeux
// Se déclenche automatiquement via save_post hook
```
</details>

<details>
<summary><code>init_hooks()</code></summary>

```php
// ⚙️ Initialiser les hooks automatiques
// Démarre automatiquement le système de notifications
Sisme_Utils_Notification::init_hooks();
```
</details>

---

## 👥 user-social-api.php

**Classe :** `Sisme_User_Social_API`

**Constantes:**
```php
STATUS_PENDING = 'pending'
STATUS_ACCEPTED = 'accepted'
```

### Gestion des Relations d'Amitié

<details>
<summary><code>send_friend_request($sender_id, $receiver_id)</code></summary>

```php
// 📤 Envoyer une demande d'ami
// @param int $sender_id - ID de l'utilisateur qui envoie
// @param int $receiver_id - ID de l'utilisateur qui reçoit
// @return array - ['success' => bool, 'message' => string]
$result = Sisme_User_Social_API::send_friend_request(1, 2);
// Structure stockée des deux côtés avec 'type' => 'sent'|'received'
```
</details>

<details>
<summary><code>accept_friend_request($sender_id, $receiver_id)</code></summary>

```php
// ✅ Accepter une demande d'ami
// @param int $sender_id - ID de l'utilisateur qui a envoyé la demande
// @param int $receiver_id - ID de l'utilisateur qui accepte
// @return array - ['success' => bool, 'message' => string]
$result = Sisme_User_Social_API::accept_friend_request(1, 2);
// Change le statut vers 'accepted' des deux côtés
```
</details>

<details>
<summary><code>decline_friend_request($sender_id, $receiver_id)</code></summary>

```php
// ❌ Refuser une demande d'ami
// @param int $sender_id - ID de l'utilisateur qui a envoyé la demande
// @param int $receiver_id - ID de l'utilisateur qui refuse
// @return array - ['success' => bool, 'message' => string]
$result = Sisme_User_Social_API::decline_friend_request(1, 2);
// Supprime l'entrée des deux côtés
```
</details>

<details>
<summary><code>cancel_friend_request($sender_id, $receiver_id)</code></summary>

```php
// 🗑️ Annuler une demande d'ami envoyée
// @param int $sender_id - ID de l'utilisateur qui annule
// @param int $receiver_id - ID de l'utilisateur destinataire
// @return array - ['success' => bool, 'message' => string]
$result = Sisme_User_Social_API::cancel_friend_request(1, 2);
// Supprime l'entrée des deux côtés
```
</details>

<details>
<summary><code>remove_friend($user1_id, $user2_id)</code></summary>

```php
// 💔 Supprimer une amitié existante
// @param int $user1_id - Premier utilisateur
// @param int $user2_id - Deuxième utilisateur (ami à supprimer)
// @return array - ['success' => bool, 'message' => string]
$result = Sisme_User_Social_API::remove_friend(1, 2);
// Supprime la relation des deux côtés
```
</details>

### Récupération de Données

<details>
<summary><code>get_user_friends($user_id)</code></summary>

```php
// 👥 Obtenir la liste des amis d'un utilisateur
// @param int $user_id - ID de l'utilisateur
// @return array - Liste des amis [user_id => ['status' => 'accepted', 'date' => '2025-01-15 14:30:25', 'type' => 'sent'|'received']]
$friends = Sisme_User_Social_API::get_user_friends(42);
```
</details>

<details>
<summary><code>get_pending_friend_requests($user_id)</code></summary>

```php
// 📩 Obtenir les demandes d'ami reçues
// @param int $user_id - ID de l'utilisateur
// @return array - Demandes reçues [sender_id => ['status' => 'pending', 'date' => '2025-01-15 14:30:25', 'type' => 'received']]
$requests = Sisme_User_Social_API::get_pending_friend_requests(42);
// Filtre sur type = 'received'
```
</details>

<details>
<summary><code>get_user_social_stats($user_id)</code></summary>

```php
// 📈 Obtenir les statistiques sociales d'un utilisateur
// @param int $user_id - ID de l'utilisateur
// @return array - ['friends_count' => int, 'pending_requests' => int]
$stats = Sisme_User_Social_API::get_user_social_stats(42);
```
</details>

### Vérifications

<details>
<summary><code>are_friends($user1_id, $user2_id)</code></summary>

```php
// 🤝 Vérifier si deux utilisateurs sont amis
// @param int $user1_id - Premier utilisateur
// @param int $user2_id - Deuxième utilisateur
// @return bool - True s'ils sont amis
$friends = Sisme_User_Social_API::are_friends(1, 2);
```
</details>

<details>
<summary><code>has_pending_request($sender_id, $receiver_id)</code></summary>

```php
// ⏳ Vérifier s'il y a une demande d'ami en attente
// @param int $sender_id - ID de l'expéditeur
// @param int $receiver_id - ID du destinataire
// @return bool - True si demande en attente
$pending = Sisme_User_Social_API::has_pending_request(1, 2);
```
</details>

<details>
<summary><code>get_relationship_status($user1_id, $user2_id)</code></summary>

```php
// 🔄 Obtenir le statut de relation entre deux utilisateurs
// @param int $user1_id - Premier utilisateur
// @param int $user2_id - Deuxième utilisateur
// @return string - 'friends'|'pending_from_user1'|'pending_from_user2'|'none'
$status = Sisme_User_Social_API::get_relationship_status(1, 2);
```
</details>

### Structure de Données

```php
// Structure dans wp_usermeta avec clé 'sisme_user_friends_list'
[
    'friend_user_id' => [
        'status' => 'pending'|'accepted',
        'date' => '2025-01-15 14:30:25',
        'type' => 'sent'|'received'
    ]
]
```

---

## 👤 user-preferences-data-manager.php

**Classe :** `Sisme_User_Preferences_Data_Manager`

### Gestion des Avatars

<details>
<summary><code>get_user_avatar_url($user_id, $size = 'thumbnail')</code></summary>

```php
// 🖼️ Récupérer l'URL de l'avatar utilisateur
// @param int $user_id - ID de l'utilisateur
// @param string $size - Taille de l'image ('thumbnail', 'medium', 'large')
// @return string|false - URL de l'avatar ou false si erreur
$avatar = Sisme_User_Preferences_Data_Manager::get_user_avatar_url(42, 'thumbnail');
// Utilise la meta 'sisme_user_avatar' avec fallback vers 'default'
```
</details>

<details>
<summary><code>update_user_avatar($user_id, $avatar_key)</code></summary>

```php
// 🔄 Mettre à jour l'avatar d'un utilisateur
// @param int $user_id - ID de l'utilisateur
// @param string $avatar_key - Clé de l'avatar dans la librairie
// @return bool - Succès de la sauvegarde
$success = Sisme_User_Preferences_Data_Manager::update_user_avatar(42, 'avatar_gaming_1');
// Valide avec Sisme_Constants::is_valid_avatar()
```
</details>

---

## 🚀 Initialisation Système

### Chargement Automatique
```php
// Le système utils se charge automatiquement via le plugin principal
// Hook: 'plugins_loaded' priorité 5 dans utils-loader.php

// Auto-initialisation du système utils
add_action('plugins_loaded', function() {
    Sisme_Utils_Loader::get_instance();
}, 5);
```

### Diagnostic Système
```php
// Diagnostics complets
$loader = Sisme_Utils_Loader::get_instance();
$health = $loader->get_system_health();

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

## 🔧 Modules Non Détaillés

Basé sur la liste dans `utils-loader.php`, les modules suivants sont référencés mais les détails ne sont pas disponibles dans les fichiers analysés :

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

## 📊 Exemples d'Usage

### Validation et Récupération Données
```php
// Validation utilisateur + récupération métadonnées
if (Sisme_Utils_Users::validate_user_id($user_id, 'dashboard')) {
    $prefs = Sisme_Utils_Users::get_user_meta_with_default($user_id, 'sisme_user_preferences', []);
}
```

### Formatage Dates et Textes
```php
// Formatage dates multiples
$short_date = Sisme_Utils_Formatting::format_release_date('2024-12-15');
$long_date = Sisme_Utils_Formatting::format_release_date_long('2024-12-15');
$date_with_status = Sisme_Utils_Formatting::format_release_date_with_status('2024-12-15', true);

// Troncature intelligente
$truncated = Sisme_Utils_Formatting::truncate_smart($long_text, 100);
```

### Recherche et Tri Jeux
```php
// Recherche avec critères multiples
$games = Sisme_Utils_Games::get_games_by_criteria([
    'genres' => ['action', 'rpg'],
    'is_team_choice' => true,
    'max_results' => 10,
    'sort_by_date' => true,
    'sort_order' => 'desc'
]);

// Récupération données complètes
foreach ($games as $game_id) {
    $game_data = Sisme_Utils_Games::get_game_data($game_id);
    if ($game_data) {
        echo $game_data[Sisme_Utils_Games::KEY_NAME];
    }
}
```

### Gestion des Relations d'Amitié
```php
// Envoyer une demande d'ami
$result = Sisme_User_Social_API::send_friend_request(1, 2);
if ($result['success']) {
    echo "Demande envoyée !";
}

// Vérifier le statut de relation
$status = Sisme_User_Social_API::get_relationship_status(1, 2);
switch ($status) {
    case 'friends':
        echo "Vous êtes amis";
        break;
    case 'pending_from_user1':
        echo "Demande envoyée en attente";
        break;
    case 'pending_from_user2':
        echo "Demande reçue en attente";
        break;
    default:
        echo "Aucune relation";
}

// Récupérer les statistiques sociales
$stats = Sisme_User_Social_API::get_user_social_stats(42);
echo "Amis: " . $stats['friends_count'];
echo "Demandes: " . $stats['pending_requests'];
```

### Gestion des Avatars
```php
// Récupérer l'avatar d'un utilisateur
$avatar_url = Sisme_User_Preferences_Data_Manager::get_user_avatar_url(42, 'thumbnail');
if ($avatar_url) {
    echo '<img src="' . $avatar_url . '" alt="Avatar">';
}

// Mettre à jour un avatar
$success = Sisme_User_Preferences_Data_Manager::update_user_avatar(42, 'avatar_gaming_2');
```

### Recherche d'Utilisateurs
```php
// Rechercher des utilisateurs avec avatars
$users = Sisme_Utils_Users::search_users_by_display_name('alice', 5);
foreach ($users as $user) {
    echo $user['display_name'] . ' - ' . $user['profile_url'];
    if (isset($user['avatar_url'])) {
        echo '<img src="' . $user['avatar_url'] . '" alt="Avatar">';
    }
}
```

### Diagnostic Système
```php
// Vérification santé système
$loader = Sisme_Utils_Loader::get_instance();
if ($loader->is_ready()) {
    $stats = $loader->get_debug_stats();
    echo "Modules chargés: " . $stats['health_check']['loaded_count'];
}
```

## 🔍 search-api.php

**Classe :** `Sisme_Search_API`

<details>
<summary><code>render_search_interface($atts = [])</code></summary>

```php
// 🔍 Shortcode principal pour l'interface de recherche complète
// @param array $atts - Attributs du shortcode
// Attributs disponibles: hero_title, hero_subtitle, placeholder, columns, max_results, debug
// @return string - HTML complet de l'interface de recherche avec hero
$html = Sisme_Search_API::render_search_interface([
    'hero_title' => '🔍 Rechercher un jeu',
    'hero_subtitle' => 'Trouvez vos jeux préférés',
    'placeholder' => 'Nom du jeu...',
    'columns' => '4',
    'max_results' => '12',
    'debug' => 'false'
]);

// Shortcode WordPress
echo do_shortcode('[sisme_search hero_title="Ma recherche" columns="3"]');
```
</details>

<details>
<summary><code>perform_search($params)</code></summary>

```php
// 🎯 Effectuer une recherche programmatique (utilisé par AJAX)
// @param array $params - Paramètres de recherche
// Keys: query, genre, status, columns, max_results
// @return array - Résultat avec success, html, total, params
$result = Sisme_Search_API::perform_search([
    'query' => 'zelda',
    'genre' => 'action',
    'status' => 'released',
    'columns' => 4,
    'max_results' => 12
]);
```
</details>

---

## 🔧 search-ajax.php

**Classe :** `Sisme_Search_Ajax`

<details>
<summary><code>handle_search_request()</code></summary>

```php
// 🚀 Handler AJAX principal pour les requêtes de recherche
// Hook WordPress: wp_ajax_sisme_search_games, wp_ajax_nopriv_sisme_search_games
// @param void - Lit $_POST pour les paramètres
// @return void - Renvoie JSON via wp_send_json_success/error
// Paramètres POST attendus: action, nonce, query, genre, status, columns, max_results, page, load_more
add_action('wp_ajax_sisme_search_games', ['Sisme_Search_Ajax', 'handle_search_request']);
add_action('wp_ajax_nopriv_sisme_search_games', ['Sisme_Search_Ajax', 'handle_search_request']);
```
</details>

---

## 🎨 search-filters.php

**Classe :** `Sisme_Search_Filters`

<details>
<summary><code>perform_search($search_params)</code></summary>

```php
// 🔍 Effectuer une recherche avec cache et validation
// @param array $search_params - Paramètres de recherche bruts
// @return array - Résultats avec games, total, page, per_page, has_more
$results = Sisme_Search_Filters::perform_search([
    'query' => 'mario',
    'genres' => [15, 16],
    'status' => 'upcoming',
    'sort' => 'date_asc',
    'page' => 1,
    'per_page' => 12
]);
```
</details>

<details>
<summary><code>get_cache_key($params)</code></summary>

```php
// 🗄️ Générer une clé de cache pour les paramètres de recherche
// @param array $params - Paramètres validés
// @return string - Clé de cache unique
$cache_key = Sisme_Search_Filters::get_cache_key($validated_params);
```
</details>

<details>
<summary><code>clear_search_cache()</code></summary>

```php
// 🧹 Vider tout le cache de recherche
// @return void
Sisme_Search_Filters::clear_search_cache();
```
</details>

---

## 📊 search-suggestions.php

**Classe :** `Sisme_Search_Suggestions`

<details>
<summary><code>get_suggestions($query, $max_results = 5)</code></summary>

```php
// 💡 Obtenir des suggestions de recherche
// @param string $query - Début de recherche (minimum 2 caractères)
// @param int $max_results - Nombre maximum de suggestions
// @return array - Suggestions avec term, count, type
$suggestions = Sisme_Search_Suggestions::get_suggestions('zel', 5);
// Retourne: [['term' => 'zelda', 'count' => 15, 'type' => 'game'], ...]
```
</details>

<details>
<summary><code>record_search($query, $results_count = 0)</code></summary>

```php
// 📈 Enregistrer une recherche pour les statistiques
// @param string $query - Terme recherché
// @param int $results_count - Nombre de résultats trouvés
// @return void
Sisme_Search_Suggestions::record_search('zelda breath', 3);
```
</details>

<details>
<summary><code>get_popular_searches($limit = 10)</code></summary>

```php
// 🔥 Obtenir les recherches populaires
// @param int $limit - Nombre maximum de résultats
// @return array - Recherches populaires avec term, count, last_searched
$popular = Sisme_Search_Suggestions::get_popular_searches(5);
```
</details>

<details>
<summary><code>get_search_trends($days = 7)</code></summary>

```php
// 📈 Obtenir les tendances de recherche
// @param int $days - Période en jours
// @return array - Tendances avec term, count, growth_rate
$trends = Sisme_Search_Suggestions::get_search_trends(30);
```
</details>

<details>
<summary><code>cleanup_old_data()</code></summary>

```php
// 🧹 Nettoyer les anciennes données de recherche (30+ jours)
// @return void
Sisme_Search_Suggestions::cleanup_old_data();
```
</details>

<details>
<summary><code>get_suggestions_stats()</code></summary>

```php
// 📊 Obtenir les statistiques des suggestions
// @return array - Stats avec total_searches, unique_terms, top_term, top_count
$stats = Sisme_Search_Suggestions::get_suggestions_stats();
```
</details>

---

## 🎮 JavaScript API

**Classe :** `SismeSearchInstance`

<details>
<summary><code>sismeSearchInit(containerId, options = {})</code></summary>

```javascript
// 🚀 Initialiser une instance de recherche
// @param string containerId - ID du container HTML
// @param object options - Options de configuration
// Options: columns, max_results, debug
// @return SismeSearchInstance - Instance créée
const searchInstance = sismeSearchInit('sisme-search-123', {
    columns: 4,
    max_results: 12,
    debug: true
});
```
</details>

<details>
<summary><code>sismeSearchGet(containerId)</code></summary>

```javascript
// 🔍 Obtenir une instance existante
// @param string containerId - ID du container
// @return SismeSearchInstance|null - Instance ou null si non trouvée
const instance = sismeSearchGet('sisme-search-123');
```
</details>

<details>
<summary><code>sismeSearchDestroy(containerId)</code></summary>

```javascript
// 🗑️ Détruire une instance de recherche
// @param string containerId - ID du container
// @return void
sismeSearchDestroy('sisme-search-123');
```
</details>

<details>
<summary><code>instance.performSearch()</code></summary>

```javascript
// 🔍 Effectuer une recherche manuelle
// @return void - Déclenche la recherche AJAX
instance.performSearch();
```
</details>

<details>
<summary><code>instance.loadMoreResults()</code></summary>

```javascript
// 📄 Charger plus de résultats
// @return void - Charge la page suivante
instance.loadMoreResults();
```
</details>

<details>
<summary><code>instance.getSearchParams()</code></summary>

```javascript
// 📋 Obtenir les paramètres de recherche actuels
// @return object - Paramètres avec query, genre, status, columns, max_results
const params = instance.getSearchParams();
// Retourne: {query: 'zelda', genre: 'action', status: '', columns: 4, max_results: 12}
```
</details>

<details>
<summary><code>instance.setLoadingState(isLoading)</code></summary>

```javascript
// ⏳ Définir l'état de chargement
// @param boolean isLoading - État de chargement
// @return void
instance.setLoadingState(true);
```
</details>

---

## 📦 Hooks d'Intégration

<details>
<summary><code>sisme_search_before_results</code></summary>

```php
// 🪝 Hook avant l'affichage des résultats
// @param array $results - Résultats de recherche
// @param array $params - Paramètres de recherche
add_action('sisme_search_before_results', function($results, $params) {
    // Personnaliser les résultats
}, 10, 2);
```
</details>

<details>
<summary><code>sisme_search_after_results</code></summary>

```php
// 🪝 Hook après l'affichage des résultats
// @param array $results - Résultats de recherche
// @param array $params - Paramètres de recherche
add_action('sisme_search_after_results', function($results, $params) {
    // Actions post-recherche
}, 10, 2);
```
</details>

<details>
<summary><code>sisme_search_modify_criteria</code></summary>

```php
// 🪝 Filtre pour modifier les critères de recherche
// @param array $criteria - Critères pour utils-games
// @param array $params - Paramètres originaux
add_filter('sisme_search_modify_criteria', function($criteria, $params) {
    // Personnaliser les critères
    return $criteria;
}, 10, 2);
```
</details>

---

## 🔧 Configuration

**Constantes disponibles :**
```php
// Durée du cache de recherche (secondes)
define('SISME_SEARCH_CACHE_DURATION', 300);

// Nombre minimum de caractères pour la recherche textuelle
define('SISME_SEARCH_MIN_LENGTH', 2);

// Délai de debounce JavaScript (millisecondes)
define('SISME_SEARCH_DEBOUNCE_DELAY', 500);

// Nombre maximum de suggestions
define('SISME_SEARCH_MAX_SUGGESTIONS', 10);
```

**Variables JavaScript globales :**
```javascript
// Configuration globale
const SISME_SEARCH = {
    instances: {},           // Instances actives
    debounceDelay: 500,     // Délai de debounce
    minSearchLength: 2      // Longueur minimum
};

// Variables WordPress injectées
sismeSearch = {
    ajaxUrl: '/wp-admin/admin-ajax.php',
    nonce: 'abc123...',
    debug: true,
    messages: {
        error: 'Une erreur est survenue'
    }
};
```

---

## 🎯 Exemples d'Usage

### Interface Complète
```php
// Shortcode avec personnalisation
echo do_shortcode('[sisme_search hero_title="Découvrir des jeux" columns="3" max_results="9"]');
```

### Recherche Programmatique
```php
// Recherche backend
$results = Sisme_Search_API::perform_search([
    'query' => 'mario',
    'status' => 'released',
    'max_results' => 6
]);

if ($results['success']) {
    echo $results['html'];
}
```

### Intégration JavaScript
```javascript
// Initialisation automatique
$(document).ready(function() {
    sismeSearchInit('my-search-container', {
        columns: 6,
        max_results: 18
    });
});

// Contrôle manuel
const search = sismeSearchGet('my-search-container');
search.performSearch();
```

### Gestion du Cache
```php
// Vider le cache après mise à jour de jeux
add_action('save_post', function($post_id) {
    if (get_post_type($post_id) === 'game') {
        Sisme_Search_Filters::clear_search_cache();
    }
});
```

---

## 🔄 Tri et Filtrage

**Statuts disponibles :**
- `released` : Jeux sortis (tri par date DESC - plus récents en premier)
- `upcoming` : Jeux à venir (tri par date ASC - plus proches en premier)
- *(vide)* : Tous les jeux (tri alphabétique A→Z)

**Types de tri :**
- **Par défaut** : Alphabétique A→Z
- **Avec statut** : Par date de sortie selon le statut
- **Recherche textuelle** : Pertinence + alphabétique
- **Filtrage genre** : Alphabétique dans le genre

**Comportement Load More :**
- Conserve le tri de la recherche initiale
- Fonctionne avec tous les types de filtres
- Gestion automatique de la pagination