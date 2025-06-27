# 🎮 Module User Actions - Documentation

**Version:** 1.0.0  
**Status:** Production Ready  
**Module:** `includes/user/user-actions/`

## 🎯 Vue d'ensemble

Module permettant aux utilisateurs d'interagir avec les jeux via des actions comme ajouter aux favoris ou à leur collection personnelle. Fournit des boutons interactifs intégrables dans Hero Section et Cards.

## 📁 Structure des fichiers

```
includes/user/user-actions/
├── user-actions-loader.php         # Singleton + chargement assets
├── user-actions-data-manager.php   # Gestion CRUD des données
├── user-actions-api.php            # Rendu de boutons + shortcodes
├── user-actions-ajax.php           # Handlers AJAX
└── assets/
    ├── user-actions.css            # Styles de base
    ├── user-actions-favorites.css  # Styles favoris
    ├── user-actions.js             # JavaScript commun
    └── user-actions-favorites.js   # JS spécifique favoris
```

## 🚀 Utilisation

### Rendu de boutons d'action

```php
// Bouton favoris basique
echo Sisme_User_Actions_API::render_action_button(
    $game_id, 
    Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE
);

// Bouton collection avec options
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

### Paramètres de render_action_button()

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `$game_id` | int | - | ID du jeu (term_id) |
| `$action_type` | string | - | Type d'action ('favorite' ou 'owned') |
| `$options` | array | [] | Options de personnalisation |

#### Options disponibles

| Option | Type | Défaut | Description |
|--------|------|--------|-------------|
| `classes` | string | '' | Classes CSS supplémentaires |
| `size` | string | 'medium' | Taille du bouton ('small', 'medium', 'large') |
| `show_count` | bool | true | Afficher le compteur d'utilisateurs |
| `update` | bool | false | Forcer la mise à jour du cache |
| `show_text` | bool | false | Afficher le texte en plus de l'icône |
| `text_active` | string | '' | Texte personnalisé état actif |
| `text_inactive` | string | '' | Texte personnalisé état inactif |

### Récupération des collections

```php
// Obtenir les jeux favoris d'un utilisateur
$favorite_games = Sisme_User_Actions_Data_Manager::get_user_collection(
    $user_id,
    Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE,
    10 // Limite
);

// Vérifier si un jeu est dans la collection
$is_favorite = Sisme_User_Actions_Data_Manager::is_game_in_user_collection(
    $user_id,
    $game_id,
    Sisme_User_Actions_Data_Manager::COLLECTION_FAVORITE
);
```

### Shortcode

```html
<!-- Afficher les jeux favoris de l'utilisateur courant -->
[sisme_user_collection collection="favorite" limit="6" title="Mes jeux favoris" columns="3"]

<!-- Afficher les jeux possédés d'un utilisateur spécifique -->
[sisme_user_collection user_id="123" collection="owned" limit="10" title="Sa collection"]
```

## 🧩 Intégration

Le module s'intègre automatiquement avec:
- **Hero Section**: Boutons dans la section d'info du jeu
- **Cards**: Bouton favori dans le coin supérieur droit

## ⚙️ API AJAX

Endpoint: `wp_ajax_sisme_toggle_game_collection`
- **Paramètres**: 
  - `game_id` (int): ID du jeu
  - `collection_type` (string): Type de collection ('favorite' ou 'owned')
  - `security` (string): Nonce de sécurité

## 🎨 Personnalisation CSS

### Variables personnalisables
```css
:root {
    --sisme-action-favorite-color: #ff3333;
    --sisme-action-owned-color: #33aaff;
    --sisme-action-bg: rgba(0, 0, 0, 0.2);
    --sisme-action-hover-bg: rgba(0, 0, 0, 0.3);
}
```

## 🔧 Hooks disponibles

### Actions
- `sisme_user_favorite_game_added` - ($user_id, $game_id)
- `sisme_user_favorite_game_removed` - ($user_id, $game_id)
- `sisme_user_owned_game_added` - ($user_id, $game_id)
- `sisme_user_owned_game_removed` - ($user_id, $game_id)

### Filtres
- `sisme_user_collection_button_html` - ($html, $game_id, $action_type, $options)
- `sisme_user_collection_items` - ($items, $user_id, $collection_type)

## 📋 Meta Keys

- `sisme_user_favorite_games` - Array de term_ids
- `sisme_user_owned_games` - Array de term_ids