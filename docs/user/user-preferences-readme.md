# ⚙️ User Preferences - REF API

**Module:** `includes/user/user-preferences/` | **Version:** 1.0.0 | **Status:** ✅ Production

---

## 📂 Architecture

```
user-preferences/
├── user-preferences-loader.php      # Singleton + assets
├── user-preferences-data-manager.php # CRUD + validation  
├── user-preferences-api.php         # Rendu + shortcodes
├── user-preferences-ajax.php        # Handlers AJAX
└── assets/
    ├── user-preferences.css         # Styles iOS toggles
    └── user-preferences.js          # Auto-save + events
```

---

## 🔧 API Principale

### Data Manager (CRUD)
```php
// Récupération
Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id)        // array complet
Sisme_User_Preferences_Data_Manager::get_user_preference($user_id, $key)   // valeur unique
Sisme_User_Preferences_Data_Manager::get_available_genres()                // depuis taxonomie

// Sauvegarde  
Sisme_User_Preferences_Data_Manager::update_user_preference($user_id, $key, $value)
Sisme_User_Preferences_Data_Manager::update_multiple_preferences($user_id, $array)
Sisme_User_Preferences_Data_Manager::reset_user_preferences($user_id)

// Validation
Sisme_User_Preferences_Data_Manager::validate_preference_key($key)         // bool
Sisme_User_Preferences_Data_Manager::validate_preference_value($key, $val) // bool
```

### API Rendu
```php
// Shortcode principal
[sisme_user_preferences sections="gaming,notifications,privacy"]

// Rendu direct
Sisme_User_Preferences_API::render_preferences_shortcode($atts)
Sisme_User_Preferences_API::render_section($section_name, $user_id)
Sisme_User_Preferences_API::render_toggle($key, $label, $value, $options)
```

### Loader (Singleton)
```php
$loader = Sisme_User_Preferences_Loader::get_instance();
$loader->force_load_assets()              // Pour shortcodes dynamiques
$loader->is_module_ready()                // Vérifier dépendances
$loader->integrate_with_dashboard()       // Intégration dashboard
```

---

## 🌐 AJAX API

### Sauvegarde Préférence
```javascript
// POST wp-admin/admin-ajax.php
{
    action: 'sisme_update_user_preference',
    security: 'nonce',
    preference_key: 'platforms',
    preference_value: ['pc', 'console']
}

// Réponse
{"success": true, "data": {"message": "Préférence sauvegardée", "key": "platforms"}}
```

### Reset Préférences
```javascript
// POST wp-admin/admin-ajax.php  
{
    action: 'sisme_reset_user_preferences',
    security: 'nonce'
}

// Réponse avec nouvelles valeurs
{"success": true, "data": {"preferences": {...}, "timestamp": 1703875200}}
```

---

## ⚡ JavaScript API

### Configuration Auto-injectée
```javascript
window.sismeUserPreferences = {
    ajax_url: '/wp-admin/admin-ajax.php',
    security: 'nonce_value',
    auto_save: true,
    save_delay: 1000,
    user_id: 123
}
```

### Méthodes Publiques
```javascript
SismeUserPreferences.savePreference(key, value)      // Promise - sauvegarde immédiate
SismeUserPreferences.autoSavePreference(key, value)  // Debounced auto-save
SismeUserPreferences.resetAllPreferences()           // Reset avec confirmation
```

### Événements jQuery
```javascript
$(document).on('sisme_preference_saved', function(e, key, value, success) {
    if (success) console.log(`${key} sauvegardé`);
});

$(document).on('sisme_preference_error', function(e, key, error) {
    console.error(`Erreur ${key}:`, error);
});
```

---

## 🎨 CSS Classes

### Structure
```css
.sisme-user-preferences           /* Container principal */
.sisme-preferences-section        /* Section (gaming/notifications/privacy) */
.sisme-preference-toggle-group    /* Groupe toggle iOS */
.sisme-multi-select-grid          /* Grille multi-sélection */
```

### États
```css
.sisme-save-indicator--saving     /* Indicateur sauvegarde */
.sisme-save-indicator--success    /* Succès */
.sisme-save-indicator--error      /* Erreur */
```

---

## 🗂️ Structure Données

### Préférences Utilisateur
```php
[
    Sisme_Utils_Games::KEY_PLATFORMS => ['pc', 'console', 'mobile'],     // Array strings
    Sisme_Utils_Games::KEY_GENRES => [1, 5, 12],                         // Array IDs taxonomie
    'player_types' => ['solo', 'multijoueur'],      // Array strings
    'notifications' => [
        'new_games_in_genres' => false,
        'favorite_games_updates' => true,           // Seul activé par défaut
        'new_indie_releases' => false,
        'newsletter' => false
    ],
    'privacy_public' => true                        // Boolean
]
```

### Meta Keys WordPress
```php
'sisme_user_platforms'      // Plateformes
'sisme_user_genres'         // Genres favoris  
'sisme_user_player_types'   // Types joueur
'sisme_user_notifications'  // Notifications
'sisme_user_privacy_public' // Public/privé
```

---

## 🚀 Usage Patterns

### Récupération Simple
```php
$prefs = Sisme_User_Preferences_Data_Manager::get_user_preferences($user_id);
$platforms = $prefs['platforms']; // ['pc', 'console']
```

### Sauvegarde avec Validation
```php
$success = Sisme_User_Preferences_Data_Manager::update_user_preference(
    $user_id, 
    'platforms', 
    ['pc', 'mobile']
);
```

### Rendu Formulaire
```php
echo do_shortcode('[sisme_user_preferences]');
// ou
echo Sisme_User_Preferences_API::render_section('gaming', $user_id);
```

### Auto-save JavaScript
```javascript
$('.sisme-preference-toggle').on('change', function() {
    const key = $(this).data('preference-key');
    const value = $(this).is(':checked');
    SismeUserPreferences.autoSavePreference(key, value);
});
```

---

## 🐛 Debug

### Vérifications
```php
$loader = Sisme_User_Preferences_Loader::get_instance();
$ready = $loader->is_module_ready();              // bool - module OK ?
$stats = $loader->get_module_stats();             // array - debug complet
```

### JavaScript Debug  
```javascript
debugUserPreferences();                          // Console debug global
SismeUserPreferences.setConfig({debug: true});   // Activer logs
```

### WP Debug
Activer dans `wp-config.php` pour voir les logs du module :
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

---

## 🔗 Intégrations

### Dashboard
```php
// Auto-intégration lors du chargement dashboard
$loader->integrate_with_dashboard();
```

### Hooks WordPress
```php
// Hook après mise à jour préférences
do_action('sisme_preferences_updated', $user_id, $updated_prefs);
```

### Modules Externes
```php
// Récupérer préférences pour autre module
$prefs = $loader->get_preferences_for_user($user_id);
```