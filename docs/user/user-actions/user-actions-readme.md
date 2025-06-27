# 🎮 Module User Actions

**Version:** 1.0.1  
**Status:** ✅ Production Ready  
**Module:** `includes/user/user-actions/`

## Vue d'ensemble

Module d'actions utilisateur pour les jeux (favoris, collection). Boutons interactifs avec AJAX temps réel, intégration automatique dans Hero Section et Cards.

## Structure des fichiers

```
includes/user/user-actions/
├── user-actions-loader.php         # Singleton + chargement assets
├── user-actions-data-manager.php   # Gestion CRUD des données
├── user-actions-api.php            # Rendu de boutons + shortcodes
├── user-actions-ajax.php           # Handlers AJAX
└── assets/
    ├── user-actions.css            # Styles de base
    ├── user-actions-favorites.css  # Styles favoris
    ├── user-actions.js             # JavaScript principal
    └── user-actions-favorites.js   # JS spécifique favoris
```

## Utilisation

### Rendu de boutons

```php
// Bouton favoris basique
echo Sisme_User_Actions_API::render_action_button(
    $game_id, 
    Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE
);

// Bouton avec options
echo Sisme_User_Actions_API::render_action_button(
    $game_id,
    Sisme_User_Actions_Data_Manager::COLLECTION_OWNED,
    [
        'size' => 'large',
        'show_text' => true,
        'show_count' => true,
        'classes' => 'my-custom-class'
    ]
);
```

### Options disponibles

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `size` | string | 'medium' | Taille ('small', 'medium', 'large') |
| `show_text` | bool | false | Afficher le texte |
| `show_count` | bool | true | Afficher le compteur |
| `classes` | string | '' | Classes CSS supplémentaires |

### API de données

```php
// Vérifier si un jeu est favori
$is_favorite = Sisme_User_Actions_Data_Manager::is_game_in_user_collection(
    $user_id, $game_id, 'favorite'
);

// Récupérer les favoris d'un utilisateur
$favorites = Sisme_User_Actions_Data_Manager::get_user_collection(
    $user_id, 'favorite', 10
);

// Actions programmatiques
Sisme_User_Actions_Data_Manager::add_game_to_user_collection($user_id, $game_id, 'favorite');
Sisme_User_Actions_Data_Manager::remove_game_from_user_collection($user_id, $game_id, 'favorite');
```

### Shortcode

```html
<!-- Afficher les favoris de l'utilisateur courant -->
[sisme_user_collection collection="favorite" limit="6" title="Mes favoris"]

<!-- Collection d'un utilisateur spécifique -->
[sisme_user_collection user_id="123" collection="owned" limit="10"]
```

## Intégration automatique

- **Hero Section** : Boutons ajoutés automatiquement dans les infos du jeu
- **Cards** : Bouton favori en coin supérieur droit
- **Assets** : Chargés automatiquement sur tout le frontend

## AJAX

**Endpoint :** `wp_ajax_sisme_toggle_game_collection`

**Paramètres :**
- `game_id` (int) : ID du jeu
- `collection_type` (string) : 'favorite' ou 'owned'  
- `security` (string) : Nonce

**Réponse :**
```json
{
    "success": true,
    "data": {
        "status": "added|removed",
        "is_active": true,
        "message": "Jeu ajouté à votre collection"
    }
}
```

## Hooks WordPress

### Actions
```php
do_action('sisme_user_favorite_game_added', $user_id, $game_id);
do_action('sisme_user_favorite_game_removed', $user_id, $game_id);
do_action('sisme_user_owned_game_added', $user_id, $game_id);
do_action('sisme_user_owned_game_removed', $user_id, $game_id);
```

### Filtres
```php
apply_filters('sisme_user_collection_button_html', $html, $game_id, $action_type, $options);
apply_filters('sisme_user_collection_items', $items, $user_id, $collection_type);
```

## Base de données

**Meta Keys :**
- `sisme_user_favorite_games` : Array de term_ids (jeux favoris)
- `sisme_user_owned_games` : Array de term_ids (jeux possédés)

## CSS Classes principales

```css
.sisme-action-btn                 /* Bouton principal */
.sisme-action-favorite           /* Bouton favoris */
.sisme-action-owned              /* Bouton collection */
.sisme-action-active             /* État actif */
.sisme-action-inactive           /* État inactif */
.sisme-hero-actions              /* Container Hero Section */
```

## Configuration

Le module se charge automatiquement via `user-loader.php`. Aucune configuration manuelle requise.

**Prérequis :** WordPress 5.0+, PHP 7.4+, Plugin Sisme Games Editor activé.

## Debug

Si `WP_DEBUG` activé, logs automatiques dans les fichiers de log WordPress. Fonction debug JS : `debugUserActions()` dans la console.