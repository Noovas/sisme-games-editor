# 🔔 User Notifications - REF API

**Module:** `includes/user/user-notifications/` | **Version:** 1.0.0 | **Status:** ✅ Production

---

## 📂 Architecture

```
includes/user/user-notifications/
├── user-notifications-loader.php       # Singleton + chargement assets
├── user-notifications-data-manager.php # Gestion CRUD notifications
├── user-notifications-api.php          # Rendu interface + shortcodes
├── user-notifications-ajax.php         # Handlers AJAX
└── assets/
    ├── user-notifications.css          # Styles interface
    └── user-notifications.js           # JavaScript interactions
```

---

## 🔧 Classes & Méthodes

### `Sisme_User_Notifications_Loader` (Singleton)

**Instance & Assets**
```php
$loader = Sisme_User_Notifications_Loader::get_instance()
$loader->enqueue_frontend_assets()   // Chargement CSS/JS conditionnel
```

---

### `Sisme_User_Notifications_Data_Manager`

**Constantes de Type**
```php
Sisme_User_Notifications_Data_Manager::TYPE_NEW_GAME  // 'new_game'
```

**Gestion CRUD Notifications**
```php
add_notification($user_id, $game_id, $type)                    // bool - Ajouter notification
mark_as_read($user_id, $notification_index)                    // bool - Marquer comme lue
get_user_notifications($user_id, $unread_only)                 // array - Récupérer notifications
get_unread_count($user_id)                                     // int - Compteur non lues
clear_all_notifications($user_id)                              // bool - Vider toutes
```

**Système FIFO**
```php
cleanup_old_notifications($user_id)                            // void - Maintenir limite 99
```

---

### `Sisme_User_Notifications_API`

**Rendu Interface**
```php
render_notification_badge($user_id)                            // string - Badge compteur
render_notification_panel($user_id)                            // string - Panel latéral
render_notification_item($notification, $index)                // string - Item notification
```

**Shortcodes**
```php
[sisme_user_notifications_badge]                               // Badge seul
[sisme_user_notifications_panel]                               // Panel complet
```

---

### `Sisme_User_Notifications_Ajax`

**Handlers AJAX**
```php
ajax_mark_as_read()                                            // Handler marquer comme lue
ajax_get_notifications()                                       // Handler récupérer notifications
ajax_not_logged_in()                                          // Handler utilisateur non connecté
```

---

## 🎯 JavaScript API (`user-notifications.js`)

**Initialisation**
```javascript
// Auto-initialisation au document ready
// Configuration via window.sismeUserNotifications
```

**Gestionnaire Principal**
```javascript
handleBadgeClick(event)                                        // Gestion clic badge
toggleNotificationPanel()                                      // Afficher/masquer panel
handleMarkAsRead(index)                                        // Marquer notification lue
handleNotificationClick(gameId)                                // Redirection vers jeu
```

**Configuration**
```javascript
window.sismeUserNotifications = {
    ajax_url: '/wp-admin/admin-ajax.php',
    security: 'nonce_value',
    user_id: 123
}
```

---

## 🗂️ Structure Données

### Notification Individuelle
```php
[
    'game_id' => 123,              // ID du jeu (term_id)
    'timestamp' => 1703875200,     // Timestamp création
    'type' => 'new_game'           // Type notification
]
```

### Collection Notifications Utilisateur
```php
'sisme_user_notifications' => [
    ['game_id' => 125, 'timestamp' => 1703875300, 'type' => 'new_game'],
    ['game_id' => 124, 'timestamp' => 1703875250, 'type' => 'new_game'],
    ['game_id' => 123, 'timestamp' => 1703875200, 'type' => 'new_game']
    // ... max 99 items, plus récentes en premier
]
```

### Meta Keys WordPress
```php
'sisme_user_notifications'     // Array notifications utilisateur
```

---

## 🎨 CSS Classes Principales

```css
.sisme-notifications-badge            /* Badge compteur */
.sisme-notifications-badge--active    /* Badge avec notifications */
.sisme-notifications-panel            /* Panel latéral */
.sisme-notifications-panel--open      /* Panel ouvert */
.sisme-notification-item               /* Item notification */
.sisme-notification-item--unread      /* Item non lue */
.sisme-notification-content            /* Contenu notification */
.sisme-notification-game-link          /* Lien vers jeu */
.sisme-notification-mark-read          /* Bouton marquer lue */
.sisme-notifications-empty             /* État vide */
```

---

## ⚡ JavaScript Réponses AJAX

### Marquer comme Lue
```json
{
    "success": true,
    "data": {
        "message": "Notification marquée comme lue",
        "unread_count": 2,
        "notification_index": 0
    }
}
```

### Récupérer Notifications
```json
{
    "success": true,
    "data": {
        "notifications": [...],
        "unread_count": 3,
        "html": "<div class='sisme-notification-item'>...</div>"
    }
}
```

---

## 🚀 Usage Patterns

### Badge Notification Standalone
```php
echo do_shortcode('[sisme_user_notifications_badge]');
```

### Panel Complet
```php
echo do_shortcode('[sisme_user_notifications_panel]');
```

### Ajout Notification Programmatique
```php
// Ajouter notification nouveau jeu
$success = Sisme_User_Notifications_Data_Manager::add_notification(
    $user_id,
    $game_id,
    Sisme_User_Notifications_Data_Manager::TYPE_NEW_GAME
);
```

### Récupération Notifications
```php
// Toutes les notifications
$all = Sisme_User_Notifications_Data_Manager::get_user_notifications($user_id, false);

// Seulement non lues
$unread = Sisme_User_Notifications_Data_Manager::get_user_notifications($user_id, true);

// Compteur
$count = Sisme_User_Notifications_Data_Manager::get_unread_count($user_id);
```

---

## 🔌 Hooks WordPress

### Actions Déclenchées
```php
do_action('sisme_notification_added', $user_id, $game_id, $type);
do_action('sisme_notification_read', $user_id, $notification_index);
do_action('sisme_notifications_cleared', $user_id);
```

### AJAX Endpoints
```php
add_action('wp_ajax_sisme_mark_notification_read', callback);
add_action('wp_ajax_sisme_get_user_notifications', callback);
add_action('wp_ajax_nopriv_sisme_mark_notification_read', callback);
```

---

## ⚡ Performance & Optimisation

### Système FIFO
- **Limite stricte** : 99 notifications maximum par utilisateur
- **Auto-cleanup** : Suppression automatique des plus anciennes
- **Ordre chronologique** : Plus récentes en premier

### Cache & Assets
- **Chargement conditionnel** : Assets uniquement si shortcode présent
- **Nonce sécurisé** : Protection CSRF sur toutes actions AJAX
- **Debouncing** : Interactions utilisateur optimisées

---

## 🐛 Debug & Intégrations

### Debug PHP
```php
// Vérifier module disponible
$loader = Sisme_User_Notifications_Loader::get_instance();

// Debug notifications utilisateur
$notifications = Sisme_User_Notifications_Data_Manager::get_user_notifications($user_id, false);
```

### Intégration Dashboard
```php
// Badge automatiquement intégré dans dashboard header
// Panel accessible via API JavaScript
```

### Logs WordPress
```php
// Activé en mode WP_DEBUG
[Sisme User Notifications] Notification ajoutée pour utilisateur 123, jeu 125
[Sisme User Notifications] Notification marquée lue: index 0, utilisateur 123
```

---

## 🎯 Types de Notifications

| Type | Status | Description | Usage |
|------|--------|-------------|-------|
| `new_game` | ✅ Prod | Nouveau jeu publié | Notification automatique |
| `genre_match` | 🚧 Futur | Jeu correspond aux préférences | Filtrage avancé |
| `friend_activity` | 🚧 Futur | Activité d'amis | Module user-social |

## 🎯 Tests & Validation

### Test Shortcode
```php
// Badge seul
[sisme_user_notifications_badge]

// Panel complet
[sisme_user_notifications_panel]

// Dans dashboard (intégration auto)
[sisme_user_dashboard]
```

### Test Programmatique
```php
// Vérifier module chargé
$loader = Sisme_User_Loader::get_instance();
$is_loaded = $loader->is_module_loaded('user-notifications');

// Ajouter notification test
$success = Sisme_User_Notifications_Data_Manager::add_notification(
    get_current_user_id(),
    123, // ID jeu existant
    Sisme_User_Notifications_Data_Manager::TYPE_NEW_GAME
);

// Vérifier compteur
$count = Sisme_User_Notifications_Data_Manager::get_unread_count(get_current_user_id());
```

### Debug Logs
```bash
# Logs attendus avec WP_DEBUG = true
[Sisme User] Module 'Notifications utilisateur' initialisé : Sisme_User_Notifications_Loader
[Sisme User Notifications] Module notifications utilisateur initialisé
[Sisme User Notifications] Assets CSS/JS chargés
[Sisme User Notifications] Notification ajoutée pour utilisateur 123, jeu 125
```

---

### Module Cards
```php
// Récupération données jeu pour notification
$game_data = Sisme_Cards_Functions::get_game_data($game_id);
$game_url = home_url($game_data['slug'] . '/');
```

### Module User-Preferences
```php
// Future intégration filtrage par genres préférés
$preferences = Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id);
$user_genres = $preferences['genres'] ?? [];
```

## 🔗 Intégrations Cross-Module

### Module Cards
```php
// Récupération données jeu pour notification
$game_data = Sisme_Cards_Functions::get_game_data($game_id);
$game_url = home_url($game_data['slug'] . '/');
```

### Module User-Preferences
```php
// Future intégration filtrage par genres préférés
$preferences = Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id);
$user_genres = $preferences['genres'] ?? [];
```

### Module User-Dashboard
```php
// Badge intégré automatiquement dans header dashboard
// API JavaScript exposée pour interactions externes
```

---

## 📦 Installation & Activation

### Structure Fichiers Requise
```
includes/user/user-notifications/
├── user-notifications-loader.php       ✅ Créé
├── user-notifications-data-manager.php ✅ Créé  
├── user-notifications-api.php          ✅ Créé
├── user-notifications-ajax.php         ✅ Créé
└── assets/
    ├── user-notifications.css          ✅ Créé
    └── user-notifications.js           ✅ Créé
```

### Modification Requise
```php
// Dans includes/user/user-loader.php
$available_modules = [
    'user-auth'          => 'Authentification',
    'user-preferences'   => 'Preferences utilisateur', 
    'user-actions'       => 'Actions utilisateur',
    'user-notifications' => 'Notifications utilisateur', // ← Ajouter
    'user-dashboard'     => 'Dashboard utilisateur',
    'user-library'       => 'Ludothèque personnelle'
];
```

### Activation Automatique
Le module se charge automatiquement au prochain rechargement WordPress si les fichiers sont présents.