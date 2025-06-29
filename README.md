# 📚 Sisme Games Editor - Documentation API REF

**Version:** 1.0.0 | **Status:** Production  
Documentation technique condensée pour tous les modules du plugin Sisme Games Editor.

---

## 🚀 Accès Rapide

### Modules Core
- **[👤 User](docs/user/user-readme.md)** - Système utilisateur complet
  - [👤 User Auth](docs/user/user-auth-readme.md) - Authentification et sessions
  - [⚙️ User Preferences](docs/user/user-preferences-readme.md) - Préférences gaming
  - [📊 User Dashboard](docs/user/user-dashboard-readme.md) - Tableau de bord utilisateur
  - [🖼️ User Profile](docs/user/user-profile-readme.md) - Gestion profils et avatars
  - [⚡ User Actions](docs/user/user-actions-readme.md) - Actions utilisateur (favoris, owned, etc.)
  - [🔔 User Notifications](docs/user/user-notifications-readme.md) - **NEW** Notifications automatiques

- **[🎴 Cards](docs/cards/cards-readme.md)** - Rendu cartes de jeux

- **[🔧 Utils Registry](docs/utils/utils-functions-registry-readme.md)** Dictionnaire des fonctions 

---

## 📖 Conventions

**Structure standard :**
- 📂 Architecture des fichiers
- 🔧 API principale (classes + méthodes)  
- ⚡ JavaScript API (si applicable)
- 🗂️ Structure des données
- 🚀 Patterns d'usage
- 🐛 Debug & intégrations

---

## 🔥 Nouveautés v1.0.0

### ✅ Module Notifications Automatiques
```php
// Workflow automatique complet :
// 1. User active "Nouvelles sorties indépendantes" (préférences)
// 2. Publication fiche jeu → Hook automatique
// 3. Notifications envoyées aux users concernés
// 4. Badge dashboard mis à jour
// 5. Navigation vers jeu depuis notification
```

### ✅ Système Utils Extensible
```php
// Auto-loader pour includes/utils/
function sisme_load_utils() {
    // Scan automatique *.php dans includes/utils/
    // Chargement conditionnel avec logs
    // Support extensions futures
}
```

### ✅ Intégrations Cross-Module
- **Dashboard ↔ Notifications** : Badge automatique dans header
- **Preferences ↔ Notifications** : Filtrage par types de notifications
- **Cards ↔ Notifications** : Récupération données jeux pour notifications
- **Fiche Creator ↔ Notifications** : Hook publication automatique

---

## 🏗️ Architecture Plugin

### Structure Principale
```
sisme-games-editor/
├── includes/
│   ├── user/                    # Système utilisateur modulaire
│   │   ├── user-auth/          # Authentification
│   │   ├── user-dashboard/     # Dashboard unifié
│   │   ├── user-notifications/ # 🔥 Notifications automatiques
│   │   └── user-preferences/   # Préférences gaming
│   ├── utils/                  # 🔥 Utilitaires globaux auto-chargés
│   │   └── sisme-notification-utils.php
│   ├── cards/                  # Système cartes jeux
│   └── fiche-creator.php      # Création fiches jeux
├── docs/                       # Documentation API REF
└── assets/                     # CSS/JS globaux
```

### Auto-Loading System
```php
// Master loaders automatiques
Sisme_User_Loader::get_instance()           // Charge tous sous-modules user
sisme_load_utils()                           // Charge tous fichiers utils/
Sisme_Cards_Loader::get_instance()          // Charge système cartes

// Détection et chargement conditionnels
// Logs de debug complets
// Gestion d'erreurs intégrée
```

---

## 🎯 Workflows Automatisés

### 🔔 Notifications Publication Jeux
```php
// 1. Détection publication fiche (hook save_post priorité 99)
// 2. Vérification tags jeu + game_description
// 3. Récupération users avec préférence 'new_indie_releases'
// 4. Envoi notifications en masse (max 99 par user, FIFO)
// 5. Logs complets pour traçabilité
```

### 👤 Système User Modulaire
```php
// Chargement automatique sous-modules
$modules = ['user-auth', 'user-dashboard', 'user-notifications', 'user-preferences'];
// Intégrations cross-module intelligentes
// Assets conditionnels optimisés
```

---

## 🚀 Getting Started

### Installation Modules
1. **Placer fichiers** dans structure correcte
2. **Modifier loaders** pour inclure nouveaux modules
3. **Activation automatique** au rechargement WordPress

### Ajout Utils Personnalisés
```php
// 1. Créer fichier dans includes/utils/mon-util.php
// 2. Auto-chargement automatique
// 3. Classes/fonctions disponibles globalement
```

### Extension Système
```php
// Pattern pour nouveau module user
class Sisme_User_MonModule_Loader {
    public static function get_instance() { /* singleton */ }
    private function __construct() { /* init */ }
}

// Ajout dans user-loader.php
'user-mon-module' => 'Description Module'
```

---

## 🔧 APIs Transversales

### Notifications
```php
// Récupérer users avec préférence
Sisme_Notification_Utils::get_users_with_notification_preference($type)

// Envoi notifications masse
Sisme_Notification_Utils::send_notification_to_users($user_ids, $game_id, $type)

// UI notifications
[sisme_user_notifications_badge]    // Badge compteur
[sisme_user_notifications_panel]    // Panel latéral
```

### User Management
```php
// Dashboard unifié
[sisme_user_dashboard]              // Interface complète

// Préférences
Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id)

// Actions utilisateur
Sisme_User_Actions_Data_Manager::add_game_to_user_collection($user_id, $game_id, $type)
```

### Cards & Jeux
```php
// Rendu cartes
[game_card id="123"]                // Carte individuelle
[game_cards_grid genres="action"]   // Grille filtrée

// Données jeux
Sisme_Cards_Functions::get_game_data($game_id)
Sisme_Cards_Functions::get_games_by_criteria($criteria)
```

---

## 🐛 Debug & Logs

### Activation Debug WordPress
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Logs Système
```bash
# Chargement modules
[Sisme User] Module 'Notifications utilisateur' initialisé
[Sisme Games Editor] 3 utilitaires chargés depuis utils/

# Notifications automatiques  
[Sisme Notification Utils] Trouvé 5 utilisateurs avec préférence 'new_indie_releases'
[Sisme Notification Utils] Publication jeu 125: Notifications envoyées à 5 utilisateurs
```

### Debug APIs
```php
// Test système notifications
Sisme_Notification_Utils::test_notification_system($game_id)

// Stats utilisateurs
$loader = Sisme_User_Loader::get_instance();
$active_modules = $loader->get_active_modules();
```

---

## 📈 Performance

### Optimisations Intégrées
- **Chargement conditionnel** : Assets uniquement si nécessaires
- **Cache intelligent** : Meta queries optimisées
- **FIFO automatique** : Limit 99 notifications par user
- **Singleton patterns** : Une instance par loader
- **Hooks prioritaires** : Chargement optimal des dépendances

### Monitoring
- **Logs détaillés** en mode debug
- **Compteurs d'erreurs** pour chaque module
- **Statistiques usage** via méthodes get_stats()

---

## 🔗 Liens Utiles

- **[Documentation User Notifications](docs/user/user-notifications-readme.md)** - Module complet notifications
- **[Documentation User Dashboard](docs/user/user-dashboard-readme.md)** - Interface utilisateur unifiée
- **[Documentation Cards](docs/cards/cards-readme.md)** - Système rendu cartes jeux