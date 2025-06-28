# 👤 User - REF API Master

**Module:** `includes/user/` | **Version:** 1.0.1 | **Status:** ✅ Production (modulaire)

---

## 📋 Sommaire des Sous-Modules

| Module | Status | Description | Documentation |
|--------|--------|-------------|---------------|
| **user-auth** | ✅ Production | Authentification, sessions, sécurité | [`user-auth-readme.md`](user-auth-readme.md) |
| **user-profile** | ✅ Production | Profil complet, avatar, formulaires | [`user-profile-readme.md`](user-profile-readme.md) |
| **user-preferences** | ✅ Production | Préférences gaming, notifications | [`user-preferences-readme.md`](user-preferences-readme.md) |
| **user-dashboard** | ✅ Production | Dashboard unifié, intégration modules | [`user-dashboard-readme.md`](user-dashboard-readme.md) |
| **user-library** | 🚧 Planifié | Collection jeux, wishlist, progression | *À venir* |
| **user-social** | 🚧 Planifié | Profils publics, amis, communauté | *À venir* |

---

## 📂 Architecture Globale

```
includes/user/
├── user-loader.php          # Master singleton loader
└── user-auth/              # ✅ Authentification (Production)
└── user-profile/           # ✅ Gestion profil (Production)
└── user-preferences/       # ✅ Préférences utilisateur (Production)
└── user-dashboard/         # ✅ Dashboard utilisateur (Production)
└── user-library/           # 🚧 Ludothèque personnelle (Planifié)
└── user-social/            # 🚧 Fonctionnalités sociales (Planifié)
```

---

## 🔧 Master Loader API

### `Sisme_User_Loader` - Point d'Entrée Principal
```php
get_instance()                      // self - Instance unique (singleton)
get_active_modules()                // array - Liste modules chargés
is_module_loaded($module_name)      // bool - Vérifier si module disponible

// Méthodes internes
load_user_module($name, $desc)      // void - Charge module spécifique
register_hooks()                    // void - Hooks WordPress globaux
```

### Auto-Loading Logic
```php
// Détection automatique modules
// Scan: 'user-{name}/user-{name}-loader.php'
// Instanciation: 'Sisme_User_{Name}_Loader::get_instance()'
// Log automatique si WP_DEBUG activé
```

---

## 🎯 Sous-Modules Disponibles

### ✅ **user-auth** - Authentification
**Status:** Production Ready | **Documentation:** [`📄 user-auth-readme.md`](user-auth-readme.md)

**Fonctionnalités:**
- Formulaires login/register frontend
- Gestion sessions sécurisées
- Rate limiting & protection
- Dashboard utilisateur basique
- Endpoints AJAX complets

**Shortcodes:**
```php
[sisme_user_login]      // Formulaire connexion
[sisme_user_register]   // Formulaire inscription  
[sisme_user_profile]    // Profil utilisateur simple
[sisme_user_menu]       // Menu utilisateur connecté
```

**API Core:**
```php
Sisme_User_Auth_Handlers::handle_login($credentials)
Sisme_User_Auth_Handlers::handle_register($user_data)
Sisme_User_Auth_Security::check_rate_limit($action, $user)
```

---

### ✅ **user-profile** - Gestion Profil Complet
**Status:** Production Ready | **Documentation:** [`📄 user-profile-readme.md`](user-profile-readme.md)

**Fonctionnalités:**
- Édition profil complète (basic/gaming/privacy)
- Gestion avatar/bannière avec upload
- Formulaires modulaires (9 composants)
- Validation sécurisée client/serveur
- AJAX pour uploads temps réel

**Shortcodes:**
```php
[sisme_user_profile_edit]        // Formulaire édition complet
[sisme_user_avatar_uploader]     // Upload avatar standalone
[sisme_user_banner_uploader]     // Upload bannière standalone  
[sisme_user_preferences]         // Préférences gaming uniquement
[sisme_user_profile_display]     // Affichage profil public
```

**API Core:**
```php
Sisme_User_Profile_Handlers::handle_profile_update($data)
Sisme_User_Profile_Avatar::handle_avatar_upload($files)
Sisme_User_Profile_Forms::new($components, $options)
```

---

### ✅ **user-preferences** - Préférences Utilisateur
**Status:** Production Ready | **Documentation:** [`📄 user-preferences-readme.md`](user-preferences-readme.md)

**Fonctionnalités:**
- Préférences gaming (plateformes/genres/skill)
- Notifications par email
- Paramètres confidentialité
- Auto-save intelligent AJAX
- Toggles iOS design

**Shortcodes:**
```php
[sisme_user_preferences]         // Interface préférences complète
```

**API Core:**
```php
Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id)
Sisme_User_Preferences_Data_Manager::update_user_preference($user_id, $key, $value)
Sisme_User_Preferences_API::render_preferences_shortcode($atts)
```

---

### ✅ **user-dashboard** - Dashboard Utilisateur
**Status:** Production Ready | **Documentation:** [`📄 user-dashboard-readme.md`](user-dashboard-readme.md)

**Fonctionnalités:**
- Dashboard unifié multi-sections
- Intégration automatique sous-modules
- Activité récente utilisateur
- Navigation sections intelligente
- Responsive design complet

**Shortcodes:**
```php
[sisme_user_dashboard]           // Dashboard complet
```

**API Core:**
```php
Sisme_User_Dashboard_API::render_dashboard_shortcode($atts)
Sisme_User_Dashboard_Integration::integrate_module($module_name)
```

---

### 🚧 **user-library** - Ludothèque Personnelle
**Status:** Planifié | **Documentation:** *À venir*

**Fonctionnalités prévues:**
- Collection personnelle de jeux
- Gestion favoris/wishlist avancée
- Tracking progression/complétion
- Notes privées sur jeux
- Statistiques temps de jeu

---

### 🚧 **user-social** - Fonctionnalités Sociales  
**Status:** Planifié | **Documentation:** *À venir*

**Fonctionnalités prévues:**
- Pages profil publiques
- Système amis/followers
- Partage wishlists publiques
- Reviews publiques
- Interactions communautaires

---

## 🗂️ Schema Meta Global

### Profile Data (Partagé)
```php
'sisme_user_avatar'           => attachment_id    // Avatar custom
'sisme_user_bio'             => text(500)         // Biographie utilisateur
'sisme_user_profile_created' => mysql_date        // Création profil
'sisme_user_last_login'      => mysql_date        // Dernière connexion
'sisme_user_profile_version' => string            // Version schema
'sisme_user_profile_updated' => mysql_date        // Dernière MAJ profil
```

### Gaming Data (user-profile + user-preferences)
```php
'sisme_user_favorite_games'       => array(term_ids)  // Jeux favoris
'sisme_user_wishlist_games'       => array(term_ids)  // Wishlist
'sisme_user_completed_games'      => array(term_ids)  // Jeux terminés
'sisme_user_favorite_game_genres' => array(term_ids)  // Genres préférés
'sisme_user_gaming_platforms'     => array(strings)   // Plateformes ['PC', 'PS5']
'sisme_user_skill_level'          => string           // Niveau gaming
```

### Preferences & Privacy
```php
'sisme_user_notifications_email'         => boolean  // Notifications email
'sisme_user_privacy_profile_public'      => boolean  // Profil public
'sisme_user_privacy_show_stats'          => boolean  // Afficher stats
'sisme_user_privacy_allow_friend_requests' => boolean // Demandes amis
```

---

## 🚀 Usage Patterns Cross-Module

### Dashboard Complet avec Tous les Modules
```php
// Dashboard unifié automatique
echo do_shortcode('[sisme_user_dashboard]');

// Sections spécifiques
echo do_shortcode('[sisme_user_dashboard sections="profile,preferences,activity"]');
```

### Intégration Profile + Preferences
```php
// Profil complet avec préférences
echo do_shortcode('[sisme_user_profile_edit sections="basic,gaming,privacy"]');

// Préférences standalone
echo do_shortcode('[sisme_user_preferences sections="gaming,notifications"]');
```

### Usage Programmatique Multi-Module
```php
// Vérifier modules disponibles
$loader = Sisme_User_Loader::get_instance();
$active_modules = $loader->get_active_modules();

if ($loader->is_module_loaded('user-profile')) {
    $avatar_url = Sisme_User_Profile_Avatar::get_final_avatar_url($user_id, 'large');
}

if ($loader->is_module_loaded('user-preferences')) {
    $prefs = Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id);
}
```

---

## ⚡ Performance & Cache

### Chargement Modulaire Intelligent
```php
// Chaque sous-module charge ses assets conditionnellement
// Détection shortcodes automatique
// Pas de chargement si module non utilisé
// Assets partagés réutilisés (tokens.css, jQuery)
```

### Optimisations Cross-Module
```php
// Meta keys optimisées (pas de sérialisation excessive)
// Réutilisation données entre modules
// Cache intelligent taxonomies (genres, etc.)
// AJAX uniquement pour interactions temps réel
```

---

## 🐛 Debug & Monitoring

### Logs Unifiés
```bash
[Sisme User] Master loader initialisé avec succès
[Sisme User] Module 'Authentification' initialisé : Sisme_User_Auth_Loader
[Sisme User] Module 'user-profile' initialisé : Sisme_User_Profile_Loader
[Sisme User] Module 'user-preferences' initialisé : Sisme_User_Preferences_Loader
[Sisme User] Module 'user-library' non trouvé : {path}
```

### Hooks WordPress Globaux
```php
// Registration/login (tous modules)
do_action('sisme_user_register', $user_id);
do_action('sisme_user_login', $user_login, $user);

// Metadata initialization 
do_action('sisme_user_init_meta', $user_id, $default_meta);

// Module-specific hooks - voir documentation individuelle
```

### Debug Stats
```php
// Stats globales utilisateurs
$stats = [
    'total_users' => wp_count_users()['total_users'],
    'users_with_profiles' => count(get_users(['meta_key' => 'sisme_profile_created'])),
    'active_modules' => $loader->get_active_modules(),
    'loaded_modules_count' => count($loader->get_active_modules())
];
```

---

## 🔗 Intégrations & Dépendances

### Modules Liés
- **cards** - Affichage jeux favoris dans profils
- **taxonomies** - Récupération genres/catégories jeux
- **game-management** - Données jeux pour collections

### Dépendances WordPress
- **User Meta API** pour stockage données
- **Media Library** pour avatars/bannières (user-profile)
- **AJAX API** pour interactions temps réel
- **Nonces** pour sécurité CSRF
- **Hooks System** pour extensibilité

### Assets Globaux
- **Design tokens** partagés (tokens.css)
- **jQuery** (WordPress core)
- **Frontend utilities** pour interactions

---

## 📚 Documentation Détaillée

| Module | Status | Documentation |
|--------|--------|---------------|
| **user-auth** | ✅ Prod | [`user-auth-readme.md`](user-auth-readme.md) |
| **user-profile** | ✅ Prod | [`user-profile-readme.md`](user-profile-readme.md) |
| **user-preferences** | ✅ Prod | [`user-preferences-readme.md`](user-preferences-readme.md) |
| **user-dashboard** | ✅ Prod | [`user-dashboard-readme.md`](user-dashboard-readme.md) |
| **user-library** | 🚧 Planifié | *Documentation à venir* |
| **user-social** | 🚧 Planifié | *Documentation à venir* |

---

## 🎯 Architecture Extensions

### Ajouter un Nouveau Sous-Module
```php
// 1. Créer répertoire: includes/user/user-{name}/
// 2. Créer loader: user-{name}-loader.php
// 3. Implémenter classe: Sisme_User_{Name}_Loader
// 4. Ajouter méthode: get_instance()
// 5. Master loader détecte et charge automatiquement

class Sisme_User_Library_Loader {
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init();
    }
}
```

### Configuration Requise
```php
// Constants obligatoires
SISME_GAMES_EDITOR_PLUGIN_DIR
SISME_GAMES_EDITOR_PLUGIN_URL  
SISME_GAMES_EDITOR_VERSION

// Modules actifs dans SISME_GAMES_MODULES
['user', 'cards', 'taxonomies', ...]
```