# 📊 User Dashboard - Documentation Fonctionnelle

**Version:** 1.0.0  
**Status:** Production Ready  
**Module:** `includes/user/user-dashboard/`

---

## 🎯 Vue d'ensemble

Module de tableau de bord utilisateur gaming complet pour Sisme Games Editor. Fournit une interface centralisée pour que les utilisateurs connectés puissent gérer leurs jeux favoris, consulter leur activité et accéder à leurs statistiques gaming personnelles.

## 📂 Structure des fichiers

```
includes/user/user-dashboard/
├── user-dashboard-loader.php        # Singleton + chargement assets
├── user-dashboard-data-manager.php  # Gestion données utilisateur + cache
├── user-dashboard-api.php           # Shortcode unique + rendu HTML
└── assets/
    ├── user-dashboard.css           # Styles gaming complets
    └── user-dashboard.js            # JavaScript interactif
```

---

## 🚀 Utilisation

### Shortcode principal

```html
[sisme_user_dashboard]
```

**Paramètres disponibles :**
- `container_class` (défaut: `sisme-user-dashboard`) - Classes CSS container
- `user_id` (défaut: utilisateur courant) - ID utilisateur spécifique
- `title` (défaut: `Mon Dashboard Gaming`) - Titre personnalisé

**Exemples d'utilisation :**
```html
<!-- Dashboard basique -->
[sisme_user_dashboard]

<!-- Dashboard avec classes custom -->
[sisme_user_dashboard container_class="ma-classe-custom"]

<!-- Dashboard d'un autre utilisateur (admin uniquement) -->
[sisme_user_dashboard user_id="123"]
```

### Intégration dans WordPress

```php
// Dans un template PHP
echo do_shortcode('[sisme_user_dashboard]');

// Dans functions.php pour une page dédiée
add_shortcode('mon_dashboard', function() {
    return do_shortcode('[sisme_user_dashboard]');
});
```

---

## 🎮 Fonctionnalités

### Interface utilisateur

#### **Header Profile**
- **Avatar utilisateur** avec indicateur en ligne
- **Statistiques gaming** (nombre de jeux, favoris, niveau)
- **Actions rapides** (déconnexion)
- **Navigation rapide** vers les sections

#### **Sidebar Navigation**
- **Menu principal** : Vue d'ensemble, Bibliothèque, Favoris, Activité
- **Statistiques temps réel** : Jeux possédés, favoris, niveau
- **Navigation sticky** qui suit le scroll

#### **Contenu Principal**
- **Feed d'activité** : Historique des actions utilisateur
- **Jeux récents** : Derniers jeux ajoutés (via module Cards)
- **Interface responsive** adaptée à tous les écrans

#### **Widgets Sidebar**
- **Widget Favoris** : Aperçu des jeux favoris avec images
- **Widget Statistiques** : Graphique circulaire + détails

### Données utilisateur

#### **Informations affichées**
```php
// Profil de base
- Nom d'affichage
- Avatar (WordPress ou Gravatar)
- Date d'inscription
- Dernière connexion

// Gaming stats
- Nombre total de jeux (favoris)
- Nombre de favoris
- Niveau calculé (Nouveau → Expert)
- Nombre d'articles créés

// Activité récente
- Inscription sur le site
- Dernière connexion
- Ajouts aux favoris
```

#### **Système de niveaux**
```php
Nouveau (0-4 points)       // Utilisateur très récent
Débutant (5-9 points)      // Premiers favoris
Intermédiaire (10-19 points) // Utilisateur actif
Expérimenté (20-49 points)   // Utilisateur régulier
Expert (50+ points)        // Power user
```

**Calcul des points :**
- 1 point par jeu favori
- 2 points par article créé

---

## 🔧 API Technique

### Classes principales

#### `Sisme_User_Dashboard_Loader`
```php
// Singleton principal
$loader = Sisme_User_Dashboard_Loader::get_instance();

// Méthodes publiques
$loader->force_load_assets();        // Force chargement CSS/JS
$loader->are_assets_loaded();        // Vérifier assets chargés
$loader->get_version();              // Version du module
$loader->get_module_stats();         // Stats debug
```

#### `Sisme_User_Dashboard_Data_Manager`
```php
// Récupération données complètes
$data = Sisme_User_Dashboard_Data_Manager::get_dashboard_data($user_id);

// Gestion favoris
Sisme_User_Dashboard_Data_Manager::add_favorite_game($user_id, $game_id);
Sisme_User_Dashboard_Data_Manager::remove_favorite_game($user_id, $game_id);

// Gestion cache
Sisme_User_Dashboard_Data_Manager::clear_user_dashboard_cache($user_id);
Sisme_User_Dashboard_Data_Manager::clear_all_dashboard_caches();

// Utilitaires
Sisme_User_Dashboard_Data_Manager::update_last_dashboard_visit($user_id);
Sisme_User_Dashboard_Data_Manager::init_user_dashboard_data($user_id);
```

#### `Sisme_User_Dashboard_API`
```php
// Shortcode principal (appelé automatiquement)
Sisme_User_Dashboard_API::render_dashboard($atts);

// Méthodes de rendu (privées)
render_dashboard_header($user_info, $gaming_stats);
render_dashboard_grid($dashboard_data);
render_activity_feed($activity_feed);
render_recent_games($recent_games);
```

### Structure des données

#### **Données dashboard complètes**
```php
[
    'user_info' => [
        'id' => 123,
        'display_name' => 'GameurPro',
        'email' => 'user@example.com',
        'avatar_url' => 'https://...',
        'member_since' => '15 janvier 2024',
        'last_login' => '2024-01-20 14:30:00',
        'profile_created' => '2024-01-15 10:00:00'
    ],
    'gaming_stats' => [
        'total_games' => 42,
        'favorite_games' => 12,
        'user_posts' => 5,
        'completion_rate' => 0,     // Future
        'playtime_hours' => 0,      // Future
        'level' => 'Expérimenté'
    ],
    'recent_games' => [
        [
            'id' => 456,
            'name' => 'Hollow Knight',
            'slug' => 'hollow-knight',
            'cover_url' => 'https://...',
            'game_url' => '/tag/hollow-knight/',
            'release_date' => '2023-12-15',
            'genres' => [
                ['id' => 5, 'name' => 'Action', 'slug' => 'action']
            ]
        ]
    ],
    'favorite_games' => [
        // Même structure que recent_games
    ],
    'activity_feed' => [
        [
            'type' => 'register',
            'icon' => '🎮',
            'message' => 'Vous avez créé votre compte gaming',
            'date' => '2024-01-15 10:00:00',
            'timestamp' => 1705320000
        ]
    ],
    'last_updated' => 1705320000
]
```

---

## ⚡ JavaScript API

### Namespace principal
```javascript
window.SismeDashboard = {
    config: {...},           // Configuration depuis PHP
    currentSection: 'overview',
    isInitialized: false
}
```

### Méthodes publiques
```javascript
// Navigation
SismeDashboard.setActiveSection('favorites');
SismeDashboard.isValidSection('library');

// Notifications
SismeDashboard.showNotification('Message', 'success', 3000);

// Utilitaires
SismeDashboard.utils.isMobile();
SismeDashboard.utils.scrollTo('#section');
SismeDashboard.utils.debounce(function, 250);
```

### Événements personnalisés
```javascript
// Changement de section
$(document).on('sisme:dashboard:section-changed', function(e, section) {
    console.log('Nouvelle section:', section);
});

// Navigation
$(document).trigger('sisme:dashboard:section-changed', ['favorites']);
```

### Configuration JavaScript injectée
```javascript
window.sismeUserDashboard = {
    ajaxUrl: '/wp-admin/admin-ajax.php',
    nonce: 'abc123...',
    currentUserId: 42,
    strings: {
        loading: 'Chargement...',
        error: 'Une erreur est survenue',
        success: 'Action réussie'
    },
    config: {
        autoRefresh: false,
        animations: true,
        notifications: true
    },
    debug: false
}
```

---

## 🎨 Interface & Design

### Thème gaming

#### **Couleurs principales**
```css
--sisme-dashboard-bg-primary: #0d1117      /* Fond principal */
--sisme-dashboard-bg-secondary: #161b22    /* Cards/containers */
--sisme-dashboard-bg-tertiary: #21262d     /* Éléments internes */
--sisme-dashboard-accent-primary: #58a6ff  /* Bleu gaming */
--sisme-dashboard-accent-success: #3fb950  /* Vert succès */
--sisme-dashboard-text-primary: #f0f6fc    /* Texte principal */
--sisme-dashboard-text-secondary: #8b949e  /* Texte secondaire */
```

#### **Layout responsive**
```css
/* Mobile (≤767px) */
- Single column layout
- Sidebar overlay avec toggle hamburger
- Actions rapides en 2 colonnes

/* Tablet (768px-1199px) */
- 2 colonnes : sidebar + main content
- Widgets intégrés au contenu

/* Desktop (≥1200px) */
- 3 colonnes : sidebar + main + widgets
- Layout sticky optimisé
```

### Animations

#### **Effets visuels**
- **Fade-in dashboard** : Apparition progressive au chargement
- **Compteurs animés** : Animation des statistiques (0 → valeur)
- **Hover effects** : Transformation des cards au survol
- **Click feedback** : Animation de confirmation des clics
- **Toast notifications** : Entrée/sortie des notifications

#### **Transitions fluides**
```css
--sisme-dashboard-transition-fast: all 0.2s ease
--sisme-dashboard-transition-normal: all 0.3s ease
--sisme-dashboard-transition-slow: all 0.5s ease
```

---

## 🔗 Intégrations

### Modules requis

#### **user-auth (obligatoire)**
- Vérification authentification utilisateur
- Gestion des sessions et permissions
- Intégration des données utilisateur de base

#### **cards (obligatoire)**
- Affichage des jeux récents via `Sisme_Cards_Functions::get_games_by_criteria()`
- Récupération des données de jeux avec `get_game_data()`
- Fallback gracieux si module indisponible

### Modules optionnels

#### **vedettes**
- Intégration possible pour jeux mis en avant
- Extension future du widget recommandations

#### **user-profile (futur)**
- Synchronisation automatique des données profil
- Extension des métadonnées utilisateur

### Métadonnées WordPress

#### **User meta utilisées**
```php
'sisme_user_favorite_games'        => array(term_ids)    // Jeux favoris
'sisme_user_last_dashboard_visit'  => mysql_date         // Dernière visite
'sisme_user_dashboard_created'     => mysql_date         // Création dashboard
'sisme_user_last_login'           => mysql_date         // Dernière connexion
'sisme_user_profile_created'      => mysql_date         // Création profil
```

#### **Cache système**
```php
// Transients WordPress (5 minutes)
"sisme_dashboard_data_{$user_id}" => $dashboard_data

// Nettoyage automatique
- À la mise à jour du profil utilisateur
- À la modification des favoris
- Via méthode manuelle clear_user_dashboard_cache()
```

---

## 🛡️ Sécurité

### Contrôles d'accès
```php
// Vérification utilisateur connecté
if (!is_user_logged_in()) {
    return render_login_required();
}

// Vérification permissions dashboard autre utilisateur
if ($user_id !== get_current_user_id() && !current_user_can('manage_users')) {
    return render_access_denied();
}
```

### Validation des données
```php
// Sanitisation ID utilisateur
$user_id = intval($atts['user_id']) ?: get_current_user_id();

// Vérification existence utilisateur
if (!get_userdata($user_id)) {
    return render_error('Utilisateur introuvable');
}

// Échappement de TOUTES les sorties
esc_html($data);
esc_url($url);
esc_attr($attribute);
```

### Protection JavaScript
```php
// Nonces pour AJAX (futur)
wp_create_nonce('sisme_dashboard');

// Configuration sécurisée
wp_localize_script('sisme-user-dashboard', 'sismeUserDashboard', $safe_config);
```

---

## 📈 Performance

### Optimisations

#### **Cache intelligent**
- **Cache par utilisateur** : 5 minutes de durée
- **Invalidation automatique** : Lors des modifications
- **Fallbacks gracieux** : Si cache indisponible

#### **Assets conditionnels**
```php
// Chargement seulement si nécessaire
should_load_assets() {
    - Détection shortcode dans contenu
    - URLs de dashboard connues
    - Forçage manuel via force_load_assets()
}
```

#### **CSS/JS optimisés**
```css
/* Performance CSS */
will-change: transform;              /* GPU acceleration */
contain: layout style paint;        /* Isolation rendering */
```

### Métriques

#### **Données système**
```php
Sisme_User_Dashboard_Data_Manager::get_system_stats();
// Retourne :
[
    'users_with_favorites' => 156,
    'total_favorite_entries' => 234,
    'cache_duration_minutes' => 5,
    'cards_module_available' => true
]
```

---

## 🔧 Maintenance

### Nettoyage automatique
```php
// Nettoyer tous les caches dashboard
Sisme_User_Dashboard_Data_Manager::clear_all_dashboard_caches();

// Hooks WordPress pour nettoyage auto
add_action('profile_update', 'clear_user_dashboard_cache');
add_action('wp_login', 'update_last_dashboard_visit');
```

### Debug et logs
```php
// Activation debug
define('WP_DEBUG', true);

// Logs automatiques si WP_DEBUG actif
error_log('[Sisme Dashboard] Message de debug');

// Stats module pour diagnostic
$loader->get_module_stats();
```

---

## 🚀 Extensibilité

### Hooks disponibles (futurs)
```php
// Actions
do_action('sisme_dashboard_before_render', $user_id, $data);
do_action('sisme_dashboard_after_render', $user_id, $output);

// Filters
apply_filters('sisme_dashboard_data', $data, $user_id);
apply_filters('sisme_dashboard_sections', $sections);
```

### Architecture modulaire
Le dashboard est conçu pour être étendu facilement :
- **Nouveaux widgets** : Ajout dans la sidebar widgets
- **Nouvelles sections** : Extension du système de navigation
- **Intégrations tierces** : API publique JavaScript disponible
- **Thèmes personnalisés** : Variables CSS surchargeable

---

## ✅ Checklist d'installation

### Prérequis
- [ ] WordPress 5.0+
- [ ] PHP 7.4+
- [ ] Module `user-auth` activé
- [ ] Module `cards` disponible
- [ ] jQuery (WordPress core)

### Activation
1. Placer les fichiers dans `includes/user/user-dashboard/`
2. Le module se charge automatiquement via `user-loader.php`
3. Utiliser `[sisme_user_dashboard]` sur une page
4. Vérifier que les assets se chargent correctement

### Tests fonctionnels
- [ ] Dashboard s'affiche pour utilisateur connecté
- [ ] Message de connexion pour utilisateur déconnecté
- [ ] Navigation entre sections fonctionne
- [ ] Jeux récents s'affichent via module Cards
- [ ] Favoris s'affichent correctement
- [ ] Responsive design sur mobile/tablet
- [ ] Notifications toast fonctionnent

---

## 📞 Support

### Problèmes courants

#### **Dashboard ne s'affiche pas**
- Vérifier que l'utilisateur est connecté
- Contrôler que le shortcode est bien `[sisme_user_dashboard]`
- Vérifier les logs d'erreur WordPress

#### **Assets CSS/JS non chargés**
- Vérifier que `should_load_assets()` retourne true
- Forcer le chargement avec `force_load_assets()`
- Contrôler les URLs des assets

#### **Données manquantes**
- Vérifier que le module Cards est disponible
- Nettoyer le cache avec `clear_user_dashboard_cache()`
- Contrôler les user_meta dans la base de données

### Debug avancé
```php
// Activer le debug complet
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Vérifier l'état du module
$loader = Sisme_User_Dashboard_Loader::get_instance();
var_dump($loader->get_module_stats());

// Vérifier les données utilisateur
$data = Sisme_User_Dashboard_Data_Manager::get_dashboard_data($user_id);
var_dump($data);
```