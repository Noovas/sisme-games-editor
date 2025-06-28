# 🎮 User Actions - API Reference

**Version:** 1.0.1  
**Module:** `includes/user/user-actions/`

---

## 📂 Architecture

```
includes/user/user-actions/
├── user-actions-loader.php         # Singleton + chargement assets
├── user-actions-data-manager.php   # Gestion CRUD des données
├── user-actions-api.php            # Rendu boutons + shortcodes
├── user-actions-ajax.php           # Handlers AJAX
└── assets/
    ├── user-actions.css            # Styles de base
    ├── user-actions-favorites.css  # Styles favoris
    ├── user-actions.js             # JavaScript principal
    └── user-actions-favorites.js   # JS spécifique favoris
```

---

## 🔧 Classes & Méthodes

### `Sisme_User_Actions_Loader` (Singleton)

**Instance & Assets**
```php
$loader = Sisme_User_Actions_Loader::get_instance()
$loader->enqueue_frontend_assets()   // Chargement CSS/JS sur frontend
```

**Configuration** - Charge automatiquement partout sur le frontend (version ultra simple)

---

### `Sisme_User_Actions_Data_Manager`

**Constantes de Collection**
```php
Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE  // 'favorite'
Sisme_User_Actions_Data_Manager::COLLECTION_OWNED     // 'owned'
```

**Vérification État**
```php
is_game_in_user_collection($user_id, $game_id, $collection_type)  // bool - Jeu dans collection ?
```

**Gestion CRUD Collections**
```php
add_game_to_user_collection($user_id, $game_id, $type)           // bool - Ajouter jeu
remove_game_from_user_collection($user_id, $game_id, $type)      // bool - Retirer jeu
toggle_game_in_user_collection($user_id, $game_id, $type)        // array - Basculer + état
get_user_collection($user_id, $type, $limit)                     // array - Récupérer collection
```

**Statistiques**
```php
get_game_collection_stats($game_id, $collection_type)            // int - Nb users avec ce jeu
```

**Cache & Optimisation**
```php
invalidate_user_cache($user_id)                                  // Vider cache utilisateur
```

---

### `Sisme_User_Actions_API`

**Rendu Boutons**
```php
render_action_button($game_id, $action_type, $options)           // string - HTML bouton
render_user_collection_shortcode($atts)                          // string - Shortcode collection
```

**Intégrations Automatiques**
```php
integrate_action_buttons_in_hero($html, $game_data)              // string - Boutons dans Hero
integrate_action_button_in_card($html, $game_id, $options)       // string - Bouton dans Card
```

**Initialisation**
```php
init_shortcodes()                                                 // Enregistrer shortcodes
```

---

### `Sisme_User_Actions_Ajax`

**Initialisation AJAX**
```php
init()                                                            // Enregistrer hooks AJAX
```

**Handlers AJAX**
```php
ajax_toggle_game_collection()                                    // Handler toggle collection
ajax_not_logged_in()                                             // Handler user non connecté
```

---

## 🎯 JavaScript API (`user-actions.js`)

**Initialisation**
```javascript
// Auto-initialisation au document ready
// Configuration via window.sismeUserActions
```

**Gestionnaire Principal**
```javascript
handleActionButtonClick(event)                                   // Gestion clic bouton
updateButtonState($button, isActive)                             // MAJ état visuel bouton
updateButtonCount($button, count)                                // MAJ compteur bouton
```

**Configuration**
```javascript
window.sismeUserActions = {
    ajax_url: 'wp-admin/admin-ajax.php',
    security: 'nonce_value',
    is_logged_in: boolean,
    login_url: 'url_connexion',
    i18n: { /* messages */ }
}
```

---

## 📊 Structure des Données

### Options Bouton (`render_action_button`)
```php
[
    'classes' => '',           // string - Classes CSS supplémentaires
    'size' => 'medium',        // string - 'small'|'medium'|'large'
    'show_count' => true,      // bool - Afficher compteur
    'update' => false,         // bool - Forcer MAJ cache
    'show_text' => false,      // bool - Afficher texte
    'text_active' => '',       // string - Texte état actif
    'text_inactive' => ''      // string - Texte état inactif
]
```

### Réponse Toggle Collection
```php
[
    'success' => true,         // bool - Succès opération
    'status' => 'added',       // string - 'added'|'removed'|'error'
    'message' => 'Message',    // string - Message utilisateur
    'is_active' => true        // bool - État final
]
```

### Attributs Shortcode (`[sisme_user_collection]`)
```php
[
    'user_id' => 123,          // int - ID utilisateur (défaut: courant)
    'collection' => 'favorite', // string - 'favorite'|'owned'
    'limit' => 10,             // int - Limite nombre jeux (-1 = tous)
    'title' => '',             // string - Titre section
    'view' => 'grid',          // string - Type affichage
    'columns' => 4,            // int - Colonnes grille
    'empty_text' => 'Aucun...' // string - Message si vide
]
```

### Meta Keys Base de Données
```php
'sisme_user_favorite_games' => [term_id1, term_id2, ...]  // Array favoris
'sisme_user_owned_games' => [term_id1, term_id2, ...]     // Array possessions
```

---

## 🚀 Utilisation

### Rendu Boutons
```php
// Bouton favoris basique
echo Sisme_User_Actions_API::render_action_button(
    $game_id, 
    Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE
);

// Bouton owned avec options
echo Sisme_User_Actions_API::render_action_button(
    $game_id,
    Sisme_User_Actions_Data_Manager::COLLECTION_OWNED,
    [
        'size' => 'large',
        'show_text' => true,
        'show_count' => true
    ]
);
```

### Gestion Données
```php
// Vérifier si favori
$is_fav = Sisme_User_Actions_Data_Manager::is_game_in_user_collection(
    $user_id, $game_id, 'favorite'
);

// Ajouter/retirer
Sisme_User_Actions_Data_Manager::add_game_to_user_collection($user_id, $game_id, 'favorite');
Sisme_User_Actions_Data_Manager::remove_game_from_user_collection($user_id, $game_id, 'owned');

// Basculer (AJAX)
$result = Sisme_User_Actions_Data_Manager::toggle_game_in_user_collection(
    $user_id, $game_id, 'favorite'
);
```

### Shortcodes
```html
<!-- Favoris utilisateur courant -->
[sisme_user_collection collection="favorite" limit="6" title="Mes favoris"]

<!-- Collection spécifique -->
[sisme_user_collection user_id="123" collection="owned" columns="3"]
```

### JavaScript
```javascript
// Écouter les événements
$(document).on('sisme_game_collection_updated', function(e, gameId, actionType, isActive) {
    console.log('Collection mise à jour:', {gameId, actionType, isActive});
});

// Configuration debug
debugUserActions(); // Fonction debug console
```

---

## 🔗 Intégrations Automatiques

**Hero Section** - Boutons ajoutés automatiquement via `integrate_action_buttons_in_hero()`
- Position: après les informations du jeu
- Taille: large avec texte
- Types: favorite + owned

**Cards Module** - Bouton favori via `integrate_action_button_in_card()`  
- Position: coin supérieur droit
- Taille: small sans texte
- Type: favorite uniquement

**Assets** - Chargement automatique sur tout le frontend via loader ultra simple

---

## 🌐 AJAX API

**Endpoint:** `wp_ajax_sisme_toggle_game_collection`

**Paramètres POST:**
```javascript
{
    action: 'sisme_toggle_game_collection',
    security: 'nonce_value',
    game_id: 123,
    collection_type: 'favorite'
}
```

**Réponse Succès:**
```json
{
    "success": true,
    "data": {
        "message": "Jeu ajouté à votre collection",
        "status": "added",
        "game_id": 123,
        "collection_type": "favorite",
        "html": "<button...>",
        "stats": {"count": 15}
    }
}
```

**Réponse Erreur:**
```json
{
    "success": false,
    "data": {
        "message": "Erreur de sécurité",
        "code": "invalid_nonce"
    }
}
```

---

## 🎨 Classes CSS Principales

```css
.sisme-action-btn                 /* Bouton principal */
.sisme-action-favorite           /* Bouton favoris */
.sisme-action-owned              /* Bouton collection */
.sisme-action-active             /* État actif */
.sisme-action-inactive           /* État inactif */
.sisme-action-size-small         /* Taille small */
.sisme-action-size-medium        /* Taille medium */
.sisme-action-size-large         /* Taille large */
.sisme-hero-actions              /* Container Hero Section */
.sisme-user-collection           /* Container shortcode */
.sisme-user-collection-empty     /* Message collection vide */
```

---

## 🔌 Hooks WordPress

### Actions Déclenchées
```php
do_action('sisme_user_favorite_game_added', $user_id, $game_id);
do_action('sisme_user_favorite_game_removed', $user_id, $game_id);
do_action('sisme_user_owned_game_added', $user_id, $game_id);
do_action('sisme_user_owned_game_removed', $user_id, $game_id);
```

### Filtres Disponibles
```php
apply_filters('sisme_hero_section_html', $html, $game_data);        // Intégration Hero
apply_filters('sisme_card_html', $html, $game_id, $options);        // Intégration Cards
```

---

## ⚡ Performance & Cache

**Stratégie Cache**
- Invalidation automatique via `invalidate_user_cache()`
- Intégration avec dashboard cache si disponible
- Requêtes optimisées pour statistiques globales

**Chargement Assets**
- Version "ultra simple" : charge partout sur frontend
- CSS optimisé avec variables tokens
- JavaScript avec debouncing et gestion d'erreurs

**Base de Données**
- Stockage en user_meta WordPress natif
- Arrays sérialisés pour collections
- Requêtes préparées pour statistiques