# 📊 User Dashboard - REF API

**Module:** `includes/user/user-dashboard/` | **Version:** 1.0.0 | **Status:** ✅ Production

---

## 📂 Architecture

```
user-dashboard/
├── user-dashboard-loader.php        # Singleton + assets
├── user-dashboard-data-manager.php  # Données + cache
├── user-dashboard-api.php           # Shortcode + rendu
└── assets/
    ├── user-dashboard.css           # Styles responsive
    └── user-dashboard.js            # Navigation + sections
```

---

## 🔧 API Principale

### Data Manager (Données & Cache)
```php
// Données complètes
Sisme_User_Dashboard_Data_Manager::get_dashboard_data($user_id)     // array|false complet
Sisme_User_Dashboard_Data_Manager::get_user_info($user_id)          // array infos user
Sisme_User_Dashboard_Data_Manager::get_gaming_stats($user_id)       // array stats gaming

// Collections utilisateur
Sisme_User_Dashboard_Data_Manager::get_favorite_games($user_id)     // array IDs favoris
Sisme_User_Dashboard_Data_Manager::get_owned_games($user_id)        // array IDs possédés
Sisme_User_Dashboard_Data_Manager::get_recent_games($user_id, $limit) // array via Cards
Sisme_User_Dashboard_Data_Manager::get_activity_feed($user_id, $limit) // array activité

// Gestion collections
Sisme_User_Dashboard_Data_Manager::add_favorite_game($user_id, $game_id)    // bool
Sisme_User_Dashboard_Data_Manager::remove_favorite_game($user_id, $game_id) // bool
Sisme_User_Dashboard_Data_Manager::add_owned_game($user_id, $game_id)       // bool
Sisme_User_Dashboard_Data_Manager::remove_owned_game($user_id, $game_id)    // bool

// Cache & système
Sisme_User_Dashboard_Data_Manager::clear_user_dashboard_cache($user_id)     // bool
Sisme_User_Dashboard_Data_Manager::update_last_dashboard_visit($user_id)    // bool
Sisme_User_Dashboard_Data_Manager::init_user_dashboard_data($user_id)       // bool
```

### API Rendu
```php
// Shortcode principal
[sisme_user_dashboard container_class="custom-class" user_id="123"]

// Rendu direct
Sisme_User_Dashboard_API::render_dashboard($atts)                    // string complet
```

### Loader (Singleton)
```php
$loader = Sisme_User_Dashboard_Loader::get_instance();
$loader->force_load_assets()              // Force CSS/JS
$loader->are_assets_loaded()              // bool status
$loader->get_version()                    // string version
$loader->on_user_login($login, $user)     // Hook connexion
$loader->on_profile_update($user_id)      // Hook mise à jour
```

---

## ⚡ JavaScript API

### Configuration Auto-injectée
```javascript
window.sismeUserDashboard = {
    ajax_url: '/wp-admin/admin-ajax.php',
    user_id: 123,
    i18n: {/* messages traduits */},
    debug: false
}
```

### Objet Principal
```javascript
SismeDashboard.init()                        // Init automatique
SismeDashboard.currentSection                // string section active
SismeDashboard.isInitialized                // bool état init
SismeDashboard.validSections                // array sections valides ['overview', 'favorites', 'library', 'activity', 'settings']
```

### Navigation Entre Sections
```javascript
SismeDashboard.setActiveSection(section, animate)  // Changer section avec animation
SismeDashboard.isValidSection(section)             // bool validation
SismeDashboard.updateURL(section)                  // MAJ URL sans reload
SismeDashboard.handleNavigation(event)             // Handler clic navigation
SismeDashboard.handleHashChange()                  // Handler changement URL
```

### Interface & Notifications
```javascript
SismeDashboard.showNotification(msg, type, duration)  // Toast notification
SismeDashboard.closeNotification(event)               // Fermer notification
SismeDashboard.updateMobileNav()                      // MAJ nav mobile
SismeDashboard.createMobileToggle()                   // Toggle sidebar mobile
```

### API Publique
```javascript
SismeDashboard.api.goToSection(section)        // Navigation externe
SismeDashboard.api.notify(msg, type, duration) // Notification externe
SismeDashboard.api.getCurrentSection()         // string section courante
SismeDashboard.api.isReady()                   // bool dashboard prêt
```

### Utilitaires
```javascript
SismeDashboard.utils.isMobile()                    // bool détection mobile
SismeDashboard.utils.scrollTo(target, duration)    // Scroll animé
SismeDashboard.utils.debounce(func, wait)          // Debouncing
SismeDashboard.log(message)                        // Logging conditionnel
```

---

## 🗂️ Structure Données

### Dashboard Complet
```php
[
    'user_info' => [
        'id' => 123,
        'display_name' => 'Username',
        'email' => 'user@example.com',
        'avatar_url' => 'https://...',
        'member_since' => 'j F Y',            // Format français
        'last_login' => 'timestamp',
        'profile_created' => 'Y-m-d H:i:s'
    ],
    'gaming_stats' => [
        'favorite_count' => 15,
        'owned_count' => 8,
        'total_games' => 23,                  // favorite + owned
        'level' => 'Expérimenté',             // Basé sur points
        'level_points' => 25,                 // 1pt/favori + 2pts/article
        'articles_count' => 3
    ],
    'recent_games' => [],                     // Via module Cards
    'favorite_games' => [1, 5, 12],         // Array IDs
    'owned_games' => [2, 8, 15],            // Array IDs
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
'Nouveau' => 0-4 points        // Utilisateur récent
'Débutant' => 5-9 points       // Premiers favoris
'Intermédiaire' => 10-19       // Utilisateur actif
'Expérimenté' => 20-49         // Utilisateur régulier
'Expert' => 50+ points         // Power user
```

### Meta Keys WordPress
```php
'sisme_user_favorite_games'      // Array IDs favoris
'sisme_user_owned_games'         // Array IDs possédés
'sisme_user_last_login'          // Timestamp dernière connexion
'sisme_user_dashboard_created'   // Date création dashboard
```

---

## 🎨 CSS Classes

### Structure Principale
```css
.sisme-user-dashboard            /* Container principal */
.sisme-dashboard-header          /* Header avec profil */
.sisme-dashboard-grid            /* Grille principale + sidebar */
.sisme-dashboard-content         /* Zone contenu principal */
.sisme-dashboard-sidebar         /* Sidebar navigation + stats */
```

### Navigation
```css
.sisme-nav-list                  /* Liste navigation */
.sisme-nav-link                  /* Lien navigation avec icônes */
.sisme-nav-link.active           /* Lien actif avec animation */
.sisme-nav-badge                 /* Badge notifications */
.sisme-nav-count                 /* Compteur éléments */
```

### Sections & Widgets
```css
.sisme-dashboard-section         /* Section contenu (overview, favorites, etc.) */
.sisme-profile-card              /* Carte profil header */
.sisme-quick-stats               /* Widget statistiques rapides */
.sisme-activity-feed             /* Feed d'activité */
.sisme-game-grid                 /* Grille jeux favoris/possédés */
```

### Mobile & Responsive
```css
.sisme-mobile-toggle             /* Bouton toggle sidebar mobile */
.sisme-mobile-overlay            /* Overlay fermeture sidebar */
@media (max-width: 768px)        /* Adaptations tablette */
@media (max-width: 480px)        /* Adaptations mobile */
```

---

## 🚀 Usage Patterns

### Shortcode Simple
```php
echo do_shortcode('[sisme_user_dashboard]');
```

### Récupération Données
```php
$data = Sisme_User_Dashboard_Data_Manager::get_dashboard_data($user_id);
$favorites = $data['favorite_games'];
$stats = $data['gaming_stats'];
```

### Gestion Collections
```php
// Ajouter aux favoris
$success = Sisme_User_Dashboard_Data_Manager::add_favorite_game($user_id, $game_id);

// Nettoyer cache après modification
Sisme_User_Dashboard_Data_Manager::clear_user_dashboard_cache($user_id);
```

### Navigation JavaScript
```javascript
// Changer de section
SismeDashboard.api.goToSection('favorites');

// Notification utilisateur
SismeDashboard.api.notify('Jeu ajouté aux favoris !', 'success', 3000);

// Écouter changements section
$(document).on('sisme:dashboard:section-changed', function(e, section, previous) {
    console.log(`Navigation: ${previous} → ${section}`);
});
```

---

## ⚡ Performance & Cache

### Cache Système
```php
// Cache automatique 5 minutes par utilisateur
const CACHE_DURATION = 300;
$cache_key = "sisme_dashboard_data_{$user_id}";

// Invalidation auto sur :
- Mise à jour profil utilisateur
- Ajout/suppression favoris ou possédés
- Hooks WordPress profile_update
```

### Assets Conditionnels
```php
// Chargement automatique si :
- Shortcode [sisme_user_dashboard] détecté
- URLs dashboard : /tableau-de-bord/, /dashboard/, /mon-profil/
- Intégration autres modules user

// Forçage manuel
$loader->force_load_assets();
```

### Optimisations JavaScript
```javascript
// Navigation fluide sans reload
- Deep linking avec hash URL
- Sauvegarde section dans localStorage
- Animations CSS transitions
- Debouncing interactions utilisateur
```

---

## 🐛 Debug & Hooks

### Debug PHP
```php
// Logs WP_DEBUG
$data = Sisme_User_Dashboard_Data_Manager::get_dashboard_data($user_id);
// [Sisme Dashboard Data] Cache hit pour utilisateur 123

// Stats système
$stats = Sisme_User_Dashboard_Data_Manager::get_system_stats();
```

### Debug JavaScript
```javascript
// Config debug
SismeDashboard.config.debug = true;

// Logs conditionnels
SismeDashboard.log('Navigation vers:', section);
```

### Hooks WordPress
```php
// Hooks internes
add_action('wp_login', [$loader, 'on_user_login'], 20, 2);
add_action('profile_update', [$loader, 'on_profile_update'], 10, 1);

// Événements personnalisés
do_action('sisme_dashboard_data_updated', $user_id, $dashboard_data);
```

### Événements JavaScript
```javascript
// Section changée
$(document).on('sisme:dashboard:section-changed', function(e, section, previous) {
    // Logique personnalisée
});

// Dashboard initialisé
$(document).on('sisme:dashboard:ready', function() {
    // Actions post-initialisation
});
```

---

## 🔗 Intégrations

### Module Cards
```php
// Récupération jeux récents via Cards
$recent_games = Sisme_User_Dashboard_Data_Manager::get_recent_games($user_id, 6);
// Utilise Sisme_Cards_Functions::get_games_by_criteria()
```

### Module User-Auth
```php
// Synchronisation automatique
- Hook wp_login pour màj last_login
- Hook profile_update pour cache invalidation
- Permissions via get_current_user_id()
```

### Modules Externes
```php
// Récupérer données dashboard depuis autre module
$loader = Sisme_User_Dashboard_Loader::get_instance();
if ($loader->are_assets_loaded()) {
    $data = Sisme_User_Dashboard_Data_Manager::get_dashboard_data($user_id);
}
```