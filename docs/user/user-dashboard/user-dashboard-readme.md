# 👤 Module User Dashboard - Documentation Complète

**Version:** 1.0.0  
**Status:** Planned - Ready for Implementation  
**Dépendances:** user-auth (required), cards (required), vedettes (optional)

---

## 📂 Architecture

```
includes/user/user-dashboard/
├── user-dashboard-loader.php        # Singleton loader principal
├── user-dashboard-data-manager.php  # Gestion données utilisateur
├── user-dashboard-widgets.php       # Système de widgets modulaires
├── user-dashboard-api.php           # Shortcodes et API publique
├── user-dashboard-ajax.php          # Handlers AJAX pour interactions
└── assets/
    ├── user-dashboard.css           # Styles principaux
    ├── user-dashboard-widgets.css   # Styles widgets spécifiques
    ├── user-dashboard.js           # JavaScript principal
    └── user-dashboard-widgets.js   # JavaScript widgets
```

---

## 🔧 Classes & Methods

### `Sisme_User_Dashboard_Loader`

**Singleton Pattern**
```php
$loader = Sisme_User_Dashboard_Loader::get_instance();
```

**Methods:**
- `force_load_assets()` - Force le chargement CSS/JS
- `are_assets_loaded()` - Vérifier si assets chargés
- `get_version()` - Version du module
- `register_dashboard_hooks()` - Hooks WordPress spécifiques

### `Sisme_User_Dashboard_Data_Manager`

**Gestion des données utilisateur dashboard**
```php
get_user_dashboard_data($user_id)         // Données complètes dashboard
get_user_activity_feed($user_id, $limit)  // Feed d'activité récente
get_user_game_stats($user_id)            // Statistiques gaming
get_user_dashboard_config($user_id)       // Configuration widgets
set_user_dashboard_config($user_id, $config) // Sauvegarder configuration
track_user_activity($user_id, $action, $data) // Enregistrer activité
```

**Gestion du cache**
```php
clear_user_dashboard_cache($user_id)     // Nettoyer cache utilisateur
refresh_dashboard_data($user_id)         // Actualiser données
get_cached_dashboard_data($user_id)      // Récupérer cache
```

### `Sisme_User_Dashboard_Widgets`

**Système de widgets modulaires**
```php
register_widget($widget_id, $config)     // Enregistrer nouveau widget
get_available_widgets()                  // Liste widgets disponibles
render_widget($widget_id, $user_id, $options) // Rendu widget spécifique
get_user_active_widgets($user_id)        // Widgets actifs utilisateur
set_user_widget_config($user_id, $widgets) // Configuration widgets
```

**Widgets natifs inclus**
- `activity_feed` - Feed d'activité récente
- `game_library` - Aperçu bibliothèque
- `favorites` - Jeux favoris
- `recommendations` - Recommandations personnalisées
- `gaming_stats` - Statistiques de jeu
- `recent_games` - Derniers jeux ajoutés
- `upcoming_releases` - Sorties à venir
- `achievements` - Succès et badges
- `gaming_calendar` - Calendrier gaming
- `friends_activity` - Activité des amis (si user-social activé)

### `Sisme_User_Dashboard_API`

**Shortcodes disponibles**

#### `[sisme_user_dashboard]` - Dashboard complet
```php
[sisme_user_dashboard 
    layout="default"                    // default, compact, full
    widgets="activity,library,favorites" // Widgets à afficher
    user_id=""                         // ID utilisateur (vide = courant)
    container_class="sisme-dashboard"   // Classe CSS container
    show_header="true"                 // Afficher header profil
    show_sidebar="true"                // Afficher sidebar navigation
    responsive="true"                  // Mode responsive
]
```

#### `[sisme_dashboard_widget]` - Widget individuel
```php
[sisme_dashboard_widget 
    widget="activity_feed"             // ID du widget
    user_id=""                        // ID utilisateur
    title="Mon Activité"              // Titre personnalisé
    limit="10"                        // Limite éléments (si applicable)
    compact="false"                   // Mode compact
    container_class=""                // Classe CSS supplémentaire
]
```

#### `[sisme_user_stats]` - Statistiques utilisateur
```php
[sisme_user_stats 
    user_id=""                        // ID utilisateur
    stats="games,playtime,completion"  // Stats à afficher
    format="detailed"                 // detailed, compact, mini
    show_icons="true"                 // Afficher icônes
    animate="true"                    // Animation compteurs
]
```

#### `[sisme_user_activity_feed]` - Feed d'activité standalone
```php
[sisme_user_activity_feed 
    user_id=""                        // ID utilisateur
    limit="20"                        // Nombre d'éléments
    types="all"                       // all, games, reviews, favorites
    show_avatars="true"               // Avatars des actions
    show_timestamps="true"            // Horodatage
    container_class=""                // Classes CSS
]
```

### `Sisme_User_Dashboard_Ajax`

**Handlers AJAX**
```php
handle_widget_update()               // Mise à jour configuration widget
handle_activity_load_more()          // Charger plus d'activité
handle_dashboard_layout_save()       // Sauvegarder layout
handle_widget_toggle()               // Activer/désactiver widget
handle_stats_refresh()               // Actualiser statistiques
```

**Actions AJAX WordPress**
- `wp_ajax_sisme_dashboard_widget_update`
- `wp_ajax_sisme_dashboard_activity_more`
- `wp_ajax_sisme_dashboard_layout_save`
- `wp_ajax_sisme_dashboard_widget_toggle`
- `wp_ajax_sisme_dashboard_stats_refresh`

---

## 📊 Schéma des Données

### Meta Keys Utilisateur Dashboard

**Configuration Dashboard**
```php
'sisme_dashboard_layout'            => string      // Layout préféré
'sisme_dashboard_widgets'           => array       // Widgets actifs
'sisme_dashboard_widget_config'     => array       // Config widgets
'sisme_dashboard_sidebar_collapsed' => boolean     // État sidebar
'sisme_dashboard_theme'             => string      // Thème (dark/light)
'sisme_dashboard_last_visit'        => mysql_date  // Dernière visite
```

**Données Gaming Étendues**
```php
'sisme_user_game_library'           => array(term_ids)    // Bibliothèque
'sisme_user_game_progress'          => array              // Progression jeux
'sisme_user_playtime_total'         => int                // Temps total (minutes)
'sisme_user_completion_rate'        => float              // Taux complétion %
'sisme_user_gaming_achievements'    => array              // Succès débloques
'sisme_user_activity_history'       => array              // Historique activité
```

**Statistiques Dashboard**
```php
'sisme_dashboard_visits_count'      => int                // Nombre visites
'sisme_dashboard_time_spent'        => int                // Temps passé (minutes)
'sisme_dashboard_actions_count'     => int                // Actions effectuées
'sisme_dashboard_widgets_interactions' => array          // Interactions widgets
```

### Structure des Données d'Activité

```php
// Format d'une entrée d'activité
[
    'id' => string,              // ID unique
    'user_id' => int,           // ID utilisateur
    'action' => string,         // Type d'action (add_favorite, write_review, etc.)
    'target_type' => string,    // Type cible (game, review, user)
    'target_id' => int,         // ID de la cible
    'data' => array,            // Données supplémentaires
    'timestamp' => mysql_date,  // Date/heure
    'public' => boolean         // Visible publiquement
]
```

### Configuration des Widgets

```php
// Structure configuration widget
[
    'widget_id' => [
        'active' => boolean,           // Widget actif
        'position' => int,             // Position dans layout
        'config' => [                  // Configuration spécifique
            'title' => string,         // Titre personnalisé
            'limit' => int,            // Limite éléments
            'compact' => boolean,      // Mode compact
            'options' => array         // Options spécifiques widget
        ]
    ]
]
```

---

## 🎨 Interface Utilisateur

### Layout Principal

**Structure HTML**
```html
<div class="sisme-user-dashboard">
    <header class="sisme-dashboard-header">
        <!-- Profil utilisateur + actions rapides -->
    </header>
    
    <div class="sisme-dashboard-grid">
        <aside class="sisme-dashboard-sidebar">
            <!-- Navigation + stats rapides -->
        </aside>
        
        <main class="sisme-dashboard-main">
            <!-- Contenu principal modulaire -->
        </main>
        
        <aside class="sisme-dashboard-widgets">
            <!-- Widgets secondaires -->
        </aside>
    </div>
</div>
```

### Layouts Disponibles

**1. Default Layout (3 colonnes)**
- Sidebar navigation (280px)
- Contenu principal (flexible)
- Widgets sidebar (320px)

**2. Compact Layout (2 colonnes)**
- Navigation intégrée au header
- Contenu principal + widgets à droite

**3. Full Layout (1 colonne)**
- Navigation horizontale
- Widgets intégrés au contenu principal

### Responsive Breakpoints

```css
/* Mobile First */
@media (max-width: 767px) {
    /* Single column stack */
    .sisme-dashboard-grid {
        grid-template-columns: 1fr;
    }
}

/* Tablet */
@media (min-width: 768px) and (max-width: 1199px) {
    /* 2 columns: sidebar collapse */
    .sisme-dashboard-grid {
        grid-template-columns: 1fr;
    }
    .sisme-dashboard-sidebar {
        position: fixed; /* Overlay mobile toggle */
    }
}

/* Desktop */
@media (min-width: 1200px) {
    /* 3 columns full layout */
    .sisme-dashboard-grid {
        grid-template-columns: 280px 1fr 320px;
    }
}
```

---

## 🔗 Intégrations avec Modules Existants

### Module User-Auth (Required)
```php
// Extension du shortcode existant [sisme_user_profile]
// Remplacé par [sisme_user_dashboard] plus complet

// Réutilisation des fonctions d'authentification
if (!is_user_logged_in()) {
    return Sisme_User_Auth_API::render_login_required();
}

// Intégration données utilisateur
$current_user = wp_get_current_user();
$user_data = Sisme_User_Auth_Handlers::get_user_data($current_user->ID);
```

### Module Cards (Required)
```php
// Utilisation des shortcodes cards existants dans widgets
[game_cards_grid type="compact" max_cards="6" user_games="true"]
[game_cards_carousel user_favorites="true" cards_per_view="3"]

// Intégration données jeux
$user_games = Sisme_Cards_Functions::get_games_by_criteria([
    'user_id' => $current_user->ID,
    'sort_by_date' => true
]);
```

### Module Vedettes (Optional)
```php
// Widget vedettes personnalisé pour utilisateur
$featured_games = Sisme_Vedettes_API::get_frontend_featured_games(5, true);

// Intégration carrousel vedettes dans dashboard
[sisme_vedettes_carousel limit="5" user_context="true"]
```

### Module User-Profile (Future)
```php
// Synchronisation automatique données profil
add_action('sisme_profile_updated', ['Sisme_User_Dashboard_Data_Manager', 'refresh_user_data']);

// Widget profil intégré
'profile_summary' => [
    'title' => 'Mon Profil',
    'callback' => 'render_profile_summary_widget'
]
```

### Module User-Library (Future)
```php
// Synchronisation bibliothèque
add_action('sisme_library_updated', ['Sisme_User_Dashboard_Data_Manager', 'refresh_library_data']);

// Widgets bibliothèque avancés
'library_stats' => ['title' => 'Statistiques Bibliothèque'],
'library_recent' => ['title' => 'Derniers Ajouts']
```

---

## ⚡ Fonctionnalités JavaScript

### Interactions Principales
```javascript
// Navigation tabs avec persistance
SismeDashboard.Navigation.init({
    saveState: true,
    defaultTab: 'overview',
    storageKey: 'sisme_dashboard_tab'
});

// Système de widgets drag & drop
SismeDashboard.Widgets.init({
    draggable: true,
    sortable: true,
    saveOrder: true
});

// Notifications toast
SismeDashboard.Notifications.show(message, type, options);

// Auto-refresh des données
SismeDashboard.DataManager.startAutoRefresh({
    interval: 300000, // 5 minutes
    widgets: ['activity_feed', 'stats']
});
```

### API JavaScript

**Namespace principal**
```javascript
window.SismeDashboard = {
    Navigation: {...},      // Gestion navigation
    Widgets: {...},         // Système widgets
    DataManager: {...},     // Gestion données
    Notifications: {...},   // Système notifications
    Utils: {...}           // Utilitaires
};
```

**Événements personnalisés**
```javascript
// Émis lors du changement de tab
document.addEventListener('sisme:dashboard:tab:changed', function(e) {
    console.log('Nouvel onglet:', e.detail.tab);
});

// Émis lors de la mise à jour d'un widget
document.addEventListener('sisme:dashboard:widget:updated', function(e) {
    console.log('Widget mis à jour:', e.detail.widget);
});
```

---

## 🚀 Roadmap d'Implémentation

### Phase 1: Structure de base (Semaine 1-2)
**Classes fondamentales**
- [ ] `Sisme_User_Dashboard_Loader` - Singleton et chargement
- [ ] `Sisme_User_Dashboard_Data_Manager` - Gestion données de base
- [ ] `Sisme_User_Dashboard_API` - Shortcode `[sisme_user_dashboard]` basique
- [ ] CSS responsive et thème gaming dark
- [ ] JavaScript navigation et interactions de base

**Intégrations critiques**
- [ ] Intégration avec user-auth (authentification)
- [ ] Intégration avec module cards (affichage jeux)
- [ ] Meta keys utilisateur de base
- [ ] Cache système simple

### Phase 2: Système de Widgets (Semaine 3-4)
**Widgets essentiels**
- [ ] `Sisme_User_Dashboard_Widgets` - Système modulaire
- [ ] Widget `activity_feed` - Feed d'activité
- [ ] Widget `game_library` - Aperçu bibliothèque
- [ ] Widget `favorites` - Jeux favoris
- [ ] Widget `gaming_stats` - Statistiques utilisateur

**Fonctionnalités avancées**
- [ ] Configuration widgets par utilisateur
- [ ] Drag & drop pour réorganisation
- [ ] Widgets personnalisables (titre, options)
- [ ] Système de cache widgets

### Phase 3: AJAX et Interactions (Semaine 5)
**Handlers AJAX**
- [ ] `Sisme_User_Dashboard_Ajax` - Classe handlers
- [ ] Chargement dynamique contenu
- [ ] Sauvegarde configuration en temps réel
- [ ] Auto-refresh données

**UX avancées**
- [ ] Système de notifications toast
- [ ] Loading states et feedback
- [ ] Animations et transitions
- [ ] Mode mobile optimisé

### Phase 4: Widgets Avancés (Semaine 6)
**Widgets supplémentaires**
- [ ] Widget `recommendations` - Recommandations IA
- [ ] Widget `upcoming_releases` - Sorties à venir
- [ ] Widget `achievements` - Système de succès
- [ ] Widget `gaming_calendar` - Calendrier événements

**Optimisations**
- [ ] Performance et optimisation requêtes
- [ ] Cache intelligent multi-niveau
- [ ] Lazy loading widgets
- [ ] SEO et analytics

### Phase 5: Tests et Finalisation (Semaine 7)
**Tests et qualité**
- [ ] Tests unitaires classes principales
- [ ] Tests d'intégration avec modules existants
- [ ] Tests responsive et accessibilité
- [ ] Optimisation performance

**Documentation**
- [ ] Documentation technique complète
- [ ] Guide d'utilisation utilisateur final
- [ ] Documentation API pour développeurs
- [ ] Exemples d'intégration

---

## 📈 Métriques et Analytics

### Données collectées (optionnel)
```php
// Métriques d'utilisation dashboard
'dashboard_page_views'      => int,         // Vues de page
'dashboard_time_on_page'    => int,         // Temps passé (secondes)
'dashboard_widgets_clicks'  => array,       // Clics par widget
'dashboard_navigation_usage' => array,      // Usage navigation
'dashboard_feature_adoption' => array      // Adoption fonctionnalités
```

### KPIs Dashboard
- **Engagement utilisateur** : Temps passé, pages vues
- **Adoption fonctionnalités** : Usage widgets, personnalisation
- **Performance** : Temps de chargement, erreurs JS
- **Satisfaction** : Taux de retour, actions effectuées

---

## 🔒 Sécurité et Permissions

### Contrôles d'accès
```php
// Vérification utilisateur connecté
if (!is_user_logged_in()) {
    return Sisme_User_Auth_API::render_login_required();
}

// Vérification propriétaire dashboard
if ($requested_user_id !== get_current_user_id() && !current_user_can('manage_users')) {
    wp_die('Accès non autorisé');
}

// Nonces pour actions AJAX
wp_verify_nonce($_POST['nonce'], 'sisme_dashboard_action');
```

### Validation des données
```php
// Sanitisation configuration widgets
$widget_config = Sisme_User_Dashboard_Widgets::sanitize_widget_config($raw_config);

// Validation layout utilisateur
$layout = in_array($requested_layout, ['default', 'compact', 'full']) ? $requested_layout : 'default';
```

---

## 🛠️ Outils de Développement

### Mode Debug
```php
// Activation mode debug dashboard
define('SISME_DASHBOARD_DEBUG', true);

// Logs détaillés
if (SISME_DASHBOARD_DEBUG) {
    error_log('[Sisme Dashboard] Widget loaded: ' . $widget_id);
}
```

### Hooks pour Extensions
```php
// Avant rendu dashboard
do_action('sisme_dashboard_before_render', $user_id, $config);

// Après rendu dashboard
do_action('sisme_dashboard_after_render', $user_id, $output);

// Enregistrement widget personnalisé
add_filter('sisme_dashboard_available_widgets', function($widgets) {
    $widgets['custom_widget'] = [
        'title' => 'Mon Widget',
        'callback' => 'render_custom_widget'
    ];
    return $widgets;
});
```

---

## 📋 Checklist de Déploiement

### Pré-requis
- [ ] Module user-auth fonctionnel
- [ ] Module cards disponible
- [ ] WordPress 5.0+
- [ ] PHP 7.4+
- [ ] jQuery (WordPress core)

### Tests obligatoires
- [ ] Authentification et permissions
- [ ] Responsive design (mobile/tablet/desktop)
- [ ] Performance (< 2s chargement initial)
- [ ] Compatibilité navigateurs (Chrome, Firefox, Safari, Edge)
- [ ] Accessibilité (WCAG 2.1 AA)

### Déploiement
- [ ] Activation via `Sisme_User_Loader`
- [ ] Migration données utilisateur existants
- [ ] Cache warming première utilisation
- [ ] Monitoring erreurs JavaScript
- [ ] Analytics activation (optionnel)

---

## UPDATE 

### 🎯 Fonctionnalités finales (simplifiées)
- ✅ Ce qu'on garde

- Layout fixe 3 colonnes responsive
- Header profil avec stats
- Navigation sidebar
- Feed d'activité simple
- Grille jeux récents (Cards)
- Statistiques gaming
- Design gaming cohérent

❌ Ce qu'on enlève

- Configuration widgets
- Drag & drop
- Choix de layout
- Personnalisation utilisateur
- Widgets complexes
- Cache avancé
- AJAX complexe

```php
📋 Structure finale simplifiée
includes/user/user-dashboard/
├── user-dashboard-loader.php      # Singleton + assets
├── user-dashboard-api.php         # Shortcode unique
├── user-dashboard-data-manager.php # Données utilisateur
└── assets/
    ├── user-dashboard.css         # Styles complets
    └── user-dashboard.js          # Interactions basiques
🎮 Résultat attendu
Un dashboard fonctionnel et moderne avec :

Interface fixe mais responsive
Données utilisateur dynamiques
Intégration parfaite avec l'existant
Code simple et maintenable
```

---

## 🎯 Conclusion

Le module **User Dashboard** représente le cœur de l'expérience utilisateur gaming dans Sisme Games Editor. Il offre une interface moderne, personnalisable et performante qui s'intègre parfaitement avec l'architecture modulaire existante.

**Points forts:**
- ✅ **Architecture solide** - Singleton pattern et séparation des responsabilités
- ✅ **Intégration native** - Compatible avec tous les modules existants
- ✅ **Personnalisation avancée** - Widgets configurables et layouts adaptatifs
- ✅ **Performance optimisée** - Cache multi-niveau et lazy loading
- ✅ **UX moderne** - Interface gaming responsive avec interactions fluides