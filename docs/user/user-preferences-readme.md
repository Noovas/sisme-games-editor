# ⚙️ User Preferences - API Reference

**Version:** 1.0.0  
**Module:** `includes/user/user-preferences/`

---

## 📂 Architecture

```
includes/user/user-preferences/
├── user-preferences-loader.php      # Singleton + chargement assets
├── user-preferences-data-manager.php # CRUD + récupération taxonomies  
├── user-preferences-api.php         # Rendu formulaires + shortcodes
├── user-preferences-ajax.php        # Handlers AJAX auto-save
└── assets/
    ├── user-preferences.css         # Styles toggles iOS + formulaires
    └── user-preferences.js          # Auto-save + interactions
```

**✅ Status: COMPLET - Tous les fichiers créés et fonctionnels**

---

## 🔧 Classes & Méthodes

### `Sisme_User_Preferences_Loader` (Singleton)

**Instance & Assets**
```php
$loader = Sisme_User_Preferences_Loader::get_instance()
$loader->force_load_assets()         // Force chargement CSS/JS
$loader->are_assets_loaded()         // bool - Vérifier si assets chargés
$loader->get_version()               // string - Version module
$loader->is_module_ready()           // bool - Module complètement initialisé
$loader->get_module_stats()          // array - Stats debug complet
```

**Hooks & Intégration**
```php
$loader->on_user_register($user_id)         // Init préférences par défaut
$loader->integrate_with_dashboard()         // Intégration dashboard
$loader->get_preferences_for_user($user_id) // Récupération pour modules externes
```

---

### `Sisme_User_Preferences_Data_Manager`

**Récupération Préférences**
```php
get_user_preferences($user_id)                      // array - Toutes les préfs utilisateur
get_user_preference($user_id, $key)                 // mixed - Une préférence spécifique
get_default_preferences()                           // array - Valeurs par défaut
```

**Sauvegarde Préférences**
```php
update_user_preference($user_id, $key, $value)      // bool - Sauvegarder une préférence
update_multiple_preferences($user_id, $preferences) // bool - Sauvegarder plusieurs préfs
reset_user_preferences($user_id)                    // bool - Reset aux valeurs par défaut
```

**Données Taxonomies**
```php
get_available_platforms()                           // array - Plateformes fixes [pc, console, mobile]
get_available_genres()                              // array - Genres depuis jeux-vidéo (sans modes jeu)
get_available_player_types()                        // array - Types joueur [solo, multijoueur, cooperatif]
get_notification_types()                            // array - Types notifications disponibles
```

**Validation & Utilitaires**
```php
validate_preference_key($key)                       // bool - Clé de préférence valide ?
validate_preference_value($key, $value)             // bool - Valeur valide pour cette clé ?
sanitize_preference_value($key, $value)             // mixed - Valeur nettoyée
```

---

### `Sisme_User_Preferences_API`

**Rendu Formulaires**
```php
render_preferences_shortcode($atts)          // string - Shortcode [sisme_user_preferences]
render_section($section_name, $user_id)     // string - Section spécifique (gaming/notifications/privacy)
render_toggle($key, $label, $value, $options) // string - Toggle iOS individuel
render_multi_select($key, $options, $selected) // string - Multi-sélection avec checkboxes
```

**Sections Disponibles**
```php
render_gaming_section($preferences)         // Section plateformes + genres + types joueur
render_notifications_section($preferences)  // Section 4 toggles notifications
render_privacy_section($preferences)        // Section profil public/privé
```

**Messages d'État**
```php
render_login_required()                     // Message connexion requise
render_access_denied()                      // Message permissions insuffisantes
render_error($message)                      // Message d'erreur personnalisé
```

**Initialisation**
```php
init_shortcodes()                           // Enregistrer shortcode WordPress
```

---

### `Sisme_User_Preferences_Ajax`

**Initialisation AJAX**
```php
init()                                              // Enregistrer hooks AJAX
```

**Handlers AJAX**
```php
ajax_update_preference()                            // Handler sauvegarde auto
ajax_reset_preferences()                           // Handler reset préférences
```

---

## 📊 Structure des Données

### Meta Keys WordPress
```php
'sisme_user_platforms' => ['pc', 'console', 'mobile']           // Plateformes préférées
'sisme_user_genres' => [15, 23, 47]                            // IDs genres favoris
'sisme_user_player_types' => ['solo', 'multijoueur']           // Types de joueur
'sisme_user_notifications' => [                                // Préfs notifications
    'new_games_in_genres' => true,
    'favorite_games_updates' => false,
    'new_indie_releases' => true,
    'newsletter' => false
]
'sisme_user_privacy_public' => true                            // Profil public/privé
```

### Préférences Complètes (`get_user_preferences`)
```php
[
    'platforms' => ['pc', 'console'],
    'genres' => [15, 23, 47],
    'player_types' => ['solo', 'cooperatif'],
    'notifications' => [
        'new_games_in_genres' => true,
        'favorite_games_updates' => true,
        'new_indie_releases' => false,
        'newsletter' => true
    ],
    'privacy_public' => false
]
```

### Données Taxonomies
```php
// get_available_platforms()
[
    ['slug' => 'pc', 'name' => 'PC'],
    ['slug' => 'console', 'name' => 'Console'],
    ['slug' => 'mobile', 'name' => 'Mobile']
]

// get_available_genres() 
[
    ['id' => 15, 'name' => 'Action', 'slug' => 'jeux-action'],
    ['id' => 23, 'name' => 'RPG', 'slug' => 'jeux-rpg'],
    // (exclut jeux-solo, jeux-multijoueur, jeux-cooperatif)
]

// get_available_player_types()
[
    ['slug' => 'solo', 'name' => 'Solo', 'category_slug' => 'jeux-solo'],
    ['slug' => 'multijoueur', 'name' => 'Multijoueur', 'category_slug' => 'jeux-multijoueur'],
    ['slug' => 'cooperatif', 'name' => 'Coopératif', 'category_slug' => 'jeux-cooperatif']
]
```

### Options Rendu
```php
// render_toggle() - Toggles iOS
[
    'classes' => '',           // string - Classes CSS supplémentaires
    'disabled' => false,       // bool - Désactiver le toggle
    'description' => ''        // string - Description sous le toggle
]

// render_multi_select() - Multi-sélections
[
    'columns' => 3,           // int - Nombre colonnes grille (3 ou 4)
    'display_key' => 'name',  // string - Clé affichage ('name' pour texte)
    'value_key' => 'slug',    // string - Clé valeur ('slug', 'id')
    'allow_all' => false,     // bool - Boutons "Tout sélectionner"
    'max_selections' => 0     // int - Limite sélections (0 = illimité)
]

// render_preferences_shortcode() - Shortcode principal
[
    'sections' => 'gaming,notifications,privacy', // string - Sections (gaming/notifications/privacy)
    'user_id' => 0,           // int - ID utilisateur (défaut: courant)
    'container_class' => 'sisme-user-preferences', // string - Classes CSS
    'title' => 'Mes préférences' // string - Titre formulaire
]
```

---

## 🎯 JavaScript API (`user-preferences.js`)

**Configuration Automatique**
```javascript
window.sismeUserPreferences = {
    ajax_url: 'wp-admin/admin-ajax.php',
    security: 'nonce_value',
    auto_save: true,
    save_delay: 1000,
    user_id: 123,
    i18n: { /* messages traduits */ },
    debug: false
}
```

**API Publique**
```javascript
// Initialisation
SismeUserPreferences.init()                     // Init manuelle
SismeUserPreferences.isReady()                  // bool - État initialisation

// Sauvegarde
SismeUserPreferences.savePreference(key, value) // Sauvegarde immédiate
SismeUserPreferences.autoSavePreference(key, value) // Auto-save avec debouncing
SismeUserPreferences.resetAllPreferences()      // Reset complet avec confirmation

// Interface
SismeUserPreferences.showSaveIndicator(type, message) // Indicateur sauvegarde
SismeUserPreferences.updateInterfaceWithPreferences(prefs) // MAJ interface

// Utilitaires
SismeUserPreferences.getCurrentNotificationValues() // Object notifications actuelles
SismeUserPreferences.getMultiSelectValues(selector) // Array valeurs sélectionnées
SismeUserPreferences.getConfig()                // Object config actuelle
SismeUserPreferences.setConfig(newConfig)       // Modifier config
```

**Événements Personnalisés**
```javascript
// Écouter les changements
$(document).on('sisme_preference_changed', function(e, key, value, type) {
    console.log(`Préférence ${key} modifiée: ${value} (type: ${type})`);
});

$(document).on('sisme_preference_saved', function(e, key, value, success) {
    if (success) console.log(`${key} sauvegardé avec succès`);
});

$(document).on('sisme_preferences_reset', function(e, success, preferences) {
    console.log('Reset préférences:', success ? 'réussi' : 'échoué');
});

$(document).on('sisme_preference_error', function(e, key, error) {
    console.error(`Erreur sur ${key}:`, error);
});
```

**Debug & Utilitaires**
```javascript
debugUserPreferences()                          // Fonction debug globale console
// Affiche: config, état init, timeouts, nombre éléments, valeurs actuelles
```

---

## 🚀 Utilisation

### Récupération Données
```php
// Toutes les préférences
$prefs = Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id);

// Préférence spécifique
$platforms = Sisme_User_Preferences_Data_Manager::get_user_preference($user_id, 'platforms');

// Genres disponibles
$genres = Sisme_User_Preferences_Data_Manager::get_available_genres();
```

### Sauvegarde
```php
// Sauvegarde individuelle
Sisme_User_Preferences_Data_Manager::update_user_preference(
    $user_id, 
    'platforms', 
    ['pc', 'console']
);

// Sauvegarde multiple
Sisme_User_Preferences_Data_Manager::update_multiple_preferences($user_id, [
    'platforms' => ['pc'],
    'privacy_public' => true
]);
```

### Rendu Formulaires
```php
// Formulaire complet
echo Sisme_User_Preferences_API::render_preferences_form($user_id, [
    'gaming', 'notifications', 'privacy'
]);

// Section spécifique
echo Sisme_User_Preferences_API::render_section('gaming', $user_id);

// Toggle individuel
echo Sisme_User_Preferences_API::render_toggle(
    'privacy_public',
    'Profil public',
    true,
    ['description' => 'Votre profil sera visible par tous']
);
```

### Shortcode
```html
<!-- Formulaire complet -->
[sisme_user_preferences]

<!-- Sections spécifiques -->
[sisme_user_preferences sections="gaming,notifications"]
```

### JavaScript
```javascript
// Sauvegarder automatiquement
$('.sisme-preference-toggle').on('change', function() {
    const key = $(this).data('preference-key');
    const value = $(this).is(':checked');
    autoSavePreference(key, value);
});

// Écouter les sauvegardes
$(document).on('sisme_preference_saved', function(e, key, value, success) {
    if (success) {
        console.log(`Préférence ${key} sauvegardée: ${value}`);
    }
});
```

---

## 🌐 AJAX API

**Endpoint Sauvegarde:** `wp_ajax_sisme_update_user_preference`

**Paramètres POST:**
```javascript
{
    action: 'sisme_update_user_preference',
    security: 'nonce_value',
    preference_key: 'platforms',
    preference_value: ['pc', 'console']
}
```

**Réponse:**
```json
{
    "success": true,
    "data": {
        "message": "Préférence sauvegardée",
        "key": "platforms",
        "value": ["pc", "console"]
    }
}
```

**Réponse Reset:** `wp_ajax_sisme_reset_user_preferences`
```json
{
    "success": true,
    "data": {
        "message": "Préférences réinitialisées",
        "preferences": {
            "platforms": [],
            "genres": [],
            "player_types": [],
            "notifications": {
                "new_games_in_genres": false,
                "favorite_games_updates": true,
                "new_indie_releases": false,
                "newsletter": false
            },
            "privacy_public": true
        },
        "timestamp": 1703875200
    }
}
```

---

## 🎨 Classes CSS Principales

**Structure Générale**
```css
.sisme-user-preferences              /* Container principal max-width 800px */
.sisme-preferences-header            /* Header avec titre centré */
.sisme-preferences-title             /* Titre avec icône flex */
.sisme-preferences-form              /* Formulaire avec position relative */
.sisme-preferences-section           /* Section avec background + border */
.sisme-section-title                 /* Titre section avec border-bottom */
.sisme-preference-group              /* Groupe préférence flex column */
```

**Toggles iOS Style**
```css
.sisme-preference-toggle-group       /* Container toggle avec hover */
.sisme-toggle-container              /* Flex container toggle + texte */
.sisme-preference-toggle             /* Input masqué (opacity: 0) */
.sisme-toggle-label                  /* Label 52x28px background animé */
.sisme-toggle-slider                 /* Slider 24x24px translateX animation */
.sisme-toggle-text                   /* Texte du toggle */
.sisme-toggle-description           /* Description sous toggle */
.sisme-toggle-animating             /* Classe temporaire animation */
```

**Multi-Sélections**
```css
.sisme-multi-select                  /* Container multi-select */
.sisme-multi-select-grid            /* Grille CSS responsive */
.sisme-multi-select-item            /* Item sélectionnable avec transitions */
.sisme-multi-select-item.selected   /* État sélectionné (bleu) */
.sisme-multi-select-checkbox        /* Checkbox masqué */
.sisme-multi-select-label           /* Label de l'item */
.sisme-multi-select-actions         /* Container boutons "Tout..." */
```

**Indicateur Sauvegarde**
```css
.sisme-save-indicator               /* Indicateur fixe top-right z-index 9999 */
.sisme-save-indicator.sisme-save-saving   /* État sauvegarde (bleu info) */
.sisme-save-indicator.sisme-save-success  /* État succès (vert) */
.sisme-save-indicator.sisme-save-error    /* État erreur (rouge) */
.sisme-save-text                    /* Texte avec pseudo-element animé */
```

**Boutons & Actions**
```css
.sisme-btn                          /* Bouton de base inline-flex */
.sisme-btn--primary                 /* Bouton primaire (bleu) */
.sisme-btn--secondary               /* Bouton secondaire (gris) */
.sisme-btn--small                   /* Petite taille */
.sisme-reset-preferences            /* Bouton reset (jaune warning) */
```

**Messages & États**
```css
.sisme-preferences-card             /* Card message centré */
.sisme-preferences-message          /* Message avec icône */
.sisme-preferences-message--warning /* Message avertissement */
.sisme-preferences-message--error   /* Message erreur */
.sisme-preferences-actions          /* Actions flex center */
```

**Responsive**
```css
/* Mobile (max-width: 768px) */
.sisme-multi-select-grid { grid-template-columns: 1fr !important; }
.sisme-save-indicator { position: relative; } /* Pas fixe sur mobile */

/* Small mobile (max-width: 480px) */
.sisme-toggle-container { flex-direction: column; }
```

---

## ⚡ Performance & Intégration

**Optimisations**
- **Auto-save intelligent** : Debouncing 1 seconde, annulation timeouts précédents
- **Validation côté client** : Évite les requêtes AJAX inutiles
- **Cache taxonomies** : `get_available_genres()` optimisé avec fallbacks
- **Assets conditionnels** : Chargement selon présence shortcode/dashboard
- **Transitions GPU** : Toggles et animations avec `transform` et `opacity`

**Chargement Assets**
```php
// Conditions de chargement automatique
- Shortcode [sisme_user_preferences] détecté
- Shortcode [sisme_user_dashboard] présent (intégration)
- URLs spécifiques (/preferences/, /dashboard/, /mon-profil/)
- Chargement forcé via $loader->force_load_assets()
```

**Sécurité**
- **Nonces WordPress** : `sisme_user_preferences_nonce` pour tous les AJAX
- **Validation permissions** : Seul l'utilisateur peut modifier ses préférences
- **Sanitisation données** : Validation stricte selon type de préférence
- **Vérification utilisateur** : `get_current_user_id()` dans tous les handlers

**Intégration Dashboard**
```php
// Le module user-preferences s'intègre automatiquement
$loader = Sisme_User_Preferences_Loader::get_instance();
$success = $loader->integrate_with_dashboard(); // Vérification disponibilité
$prefs = $loader->get_preferences_for_user($user_id); // Récupération données

// Dans le dashboard, utiliser:
echo do_shortcode('[sisme_user_preferences sections="gaming,notifications"]');
```

**Debug & Monitoring**
```php
// Activation debug complet si WP_DEBUG
$stats = $loader->get_module_stats();
// Retourne: modules chargés, classes disponibles, assets, version

// JavaScript debug
debugUserPreferences(); // Console browser
```