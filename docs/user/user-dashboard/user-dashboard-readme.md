# 📊 User Dashboard - API Reference

**Version:** 1.0.0  
**Module:** `includes/user/user-dashboard/`

---

## 📂 Architecture

```
includes/user/user-dashboard/
├── user-dashboard-loader.php        # Singleton + chargement assets
├── user-dashboard-data-manager.php  # Gestion données + cache
├── user-dashboard-api.php           # Shortcode + rendu HTML
└── assets/
    ├── user-dashboard.css           # Styles dashboard
    └── user-dashboard.js            # Navigation + interactions
```

---

## 🔧 Classes & Méthodes

### `Sisme_User_Dashboard_Loader` (Singleton)

**Instance & Assets**
```php
$loader = Sisme_User_Dashboard_Loader::get_instance()
$loader->force_load_assets()         // Force chargement CSS/JS
$loader->are_assets_loaded()         // bool - Vérifier si assets chargés
$loader->get_version()               // string - Version module
```

**Hooks WordPress**
```php
$loader->on_user_login($login, $user)   // Hook connexion utilisateur
$loader->on_profile_update($user_id)    // Hook mise à jour profil
```

---

### `Sisme_User_Dashboard_Data_Manager`

**Données Dashboard**
```php
get_dashboard_data($user_id)         // array|false - Données complètes dashboard
get_user_info($user_id)              // array - Infos utilisateur (nom, avatar, dates)
get_gaming_stats($user_id)           // array - Stats gaming (favoris, owned, niveau)
get_recent_games($user_id, $limit)   // array - Jeux récents avec module Cards
get_favorite_games($user_id)         // array - Collection favoris utilisateur
get_owned_games($user_id)            // array - Collection possession utilisateur
get_activity_feed($user_id, $limit)  // array - Feed activité utilisateur
```

**Gestion Collections**
```php
add_favorite_game($user_id, $game_id)      // bool - Ajouter aux favoris
remove_favorite_game($user_id, $game_id)   // bool - Retirer des favoris
add_owned_game($user_id, $game_id)         // bool - Ajouter aux possessions
remove_owned_game($user_id, $game_id)      // bool - Retirer des possessions
```

**Cache & Utilitaires**
```php
clear_user_dashboard_cache($user_id)       // bool - Nettoyer cache utilisateur
clear_all_dashboard_caches()               // bool - Nettoyer tous les caches
update_last_dashboard_visit($user_id)      // bool - MAJ dernière visite
init_user_dashboard_data($user_id)         // bool - Initialiser données utilisateur
```

**Système & Debug**
```php
get_system_stats()                          // array - Stats système dashboard
format_time_ago($date)                      // string - "Il y a X minutes"
```

---

### `Sisme_User_Dashboard_API`

**Shortcode & Rendu**
```php
render_dashboard($atts)                     // string - Shortcode [sisme_user_dashboard]
```

**Méthodes Privées de Rendu**
```php
render_dashboard_header($user_info, $stats) // string - Header profil utilisateur
render_dashboard_grid($dashboard_data)       // string - Grille principale + sidebar
render_sidebar_navigation()                 // string - Menu navigation sidebar
render_quick_stats($gaming_stats)           // string - Widget statistiques
render_activity_feed($activity_feed)        // string - Feed activité utilisateur
render_recent_games($recent_games)          // string - Jeux récents via Cards
render_favorites_section($favorite_games)   // string - Section favoris
render_login_required()                     // string - Message connexion requise
render_access_denied()                      // string - Message accès refusé
render_error($message)                      // string - Message d'erreur
```

---

## 🎮 JavaScript API (`user-dashboard.js`)

**Objet Principal**
```javascript
SismeDashboard.init()                       // Initialisation automatique
SismeDashboard.currentSection               // string - Section active actuelle
SismeDashboard.isInitialized                // bool - État initialisation
```

**Navigation**
```javascript
SismeDashboard.setActiveSection(section, scroll) // Changer section active
SismeDashboard.isValidSection(section)      // bool - Vérifier section valide
SismeDashboard.updateURL(section)           // MAJ URL sans reload
```

**Interface**
```javascript
SismeDashboard.updateMobileNav()            // MAJ navigation mobile
SismeDashboard.showNotification(msg, type)  // Afficher notification toast
SismeDashboard.updateStats(stats)           // MAJ statistiques sidebar
```

**API Publique**
```javascript
SismeDashboard.api.goToSection(section)     // Changer section programmatiquement
SismeDashboard.api.notify(msg, type, duration) // Notification externe
SismeDashboard.api.getCurrentSection()      // string - Section courante
SismeDashboard.api.isReady()                // bool - Dashboard prêt
```

**Utilitaires**
```javascript
SismeDashboard.utils.isMobile()             // bool - Détection mobile
SismeDashboard.utils.scrollTo(target, duration) // Scroll animé
SismeDashboard.utils.debounce(func, wait)   // Fonction debounce
```

---

## 📊 Structure des Données

### Données Dashboard Complètes
```php
[
    'user_info' => [
        'id' => 123,
        'display_name' => 'Nom Utilisateur',
        'email' => 'user@example.com',
        'avatar_url' => 'https://...',
        'member_since' => 'j F Y',
        'last_login' => 'timestamp',
        'profile_created' => 'Y-m-d H:i:s'
    ],
    'gaming_stats' => [
        'favorite_count' => 15,
        'owned_count' => 8,
        'total_games' => 23,
        'level' => 'Expérimenté',
        'level_points' => 25,
        'articles_count' => 3
    ],
    'recent_games' => [/* Données module Cards */],
    'favorite_games' => [/* IDs jeux favoris */],
    'owned_games' => [/* IDs jeux possédés */],
    'activity_feed' => [
        [
            'type' => 'favorite_added',
            'message' => 'Ajouté aux favoris',
            'date' => 'timestamp',
            'game_name' => 'Nom du Jeu'
        ]
    ],
    'last_updated' => 'timestamp'
]
```

### Système de Niveaux
```php
'Nouveau' => 0-4 points      // Utilisateur récent
'Débutant' => 5-9 points     // Premiers favoris
'Intermédiaire' => 10-19     // Utilisateur actif
'Expérimenté' => 20-49       // Utilisateur régulier  
'Expert' => 50+ points       // Power user

// Calcul: 1 pt/favori + 2 pts/article
```

---

## 🚀 Utilisation

### Shortcode
```html
[sisme_user_dashboard]                              <!-- Basique -->
[sisme_user_dashboard container_class="ma-classe"] <!-- Classes custom -->
[sisme_user_dashboard user_id="123"]               <!-- Autre utilisateur (admin) -->
```

### PHP
```php
// Chargement forcé
$loader = Sisme_User_Dashboard_Loader::get_instance();
$loader->force_load_assets();

// Données utilisateur
$data = Sisme_User_Dashboard_Data_Manager::get_dashboard_data($user_id);

// Gestion favoris
Sisme_User_Dashboard_Data_Manager::add_favorite_game($user_id, $game_id);
```

### JavaScript
```javascript
// Navigation programmatique
SismeDashboard.api.goToSection('favorites');

// Notifications
SismeDashboard.api.notify('Favori ajouté !', 'success');
```

---

## ⚡ Performance

**Cache**
- Durée: 5 minutes par utilisateur
- Invalidation auto: Mise à jour profil
- Clé: `sisme_dashboard_data_{user_id}`

**Assets Conditionnels**
- Chargement sur détection shortcode
- URLs dashboard connues
- Forçage manuel possible

**Optimisations CSS/JS**
- GPU acceleration (`will-change`)
- Debouncing des interactions
- Navigation sans reload