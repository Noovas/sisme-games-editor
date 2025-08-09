# API REF - PHP Admin User Getter

**Fichier:** `/sisme-games-editor/admin/assets/PHP-admin-user-getter.php`  
**Classe:** `Sisme_Admin_User_Getter`

## 🔒 Constantes de métadonnées utilisateur

### Préférences utilisateur
```php
const META_PLATFORMS = 'sisme_user_platforms';
const META_GENRES = 'sisme_user_genres';
const META_PLAYER_TYPES = 'sisme_user_player_types';
const META_NOTIFICATIONS = 'sisme_user_notifications';
const META_PRIVACY_PUBLIC = 'sisme_user_privacy_public';
const META_AVATAR = 'sisme_user_avatar';
```

### Données développeur
```php
const META_DEVELOPER_STATUS = 'sisme_user_developer_status';
const META_DEVELOPER_APPLICATION = 'sisme_user_developer_application';
const META_DEVELOPER_PROFILE = 'sisme_user_developer_profile';
```

### Collections de jeux
```php
const META_FAVORITE_GAMES = 'sisme_user_favorite_games';
const META_OWNED_GAMES = 'sisme_user_owned_games';
const META_WISHLIST_GAMES = 'sisme_user_wishlist_games';
const META_COMPLETED_GAMES = 'sisme_user_completed_games';
```

### Données sociales
```php
const META_FRIENDS_LIST = 'sisme_user_friends_list';
```

### Données dashboard
```php
const META_LAST_LOGIN = 'sisme_user_last_login';
const META_PROFILE_CREATED = 'sisme_user_profile_created';
const META_PROFILE_VISIBILITY = 'sisme_user_profile_visibility';
```

### URLs avatars disponibles
```php
const SISME_ROOT_URL = 'https://games.sisme.fr/preprod/';
const AVATARS_URL = self::SISME_ROOT_URL . 'images/avatar/avatar-user-';
const AVATARS_USER_URL = [
    'default' => self::AVATARS_URL . 'borne-arcade.png',
    'borne-arcade' => self::AVATARS_URL . 'borne-arcade.png',
    'cd-rom' => self::AVATARS_URL . 'cd-rom.png',
    'clavier' => self::AVATARS_URL . 'clavier.png',
    'flipper' => self::AVATARS_URL . 'flipper.png',
    'gameboy' => self::AVATARS_URL . 'gameboy.png',
    'joystick' => self::AVATARS_URL . 'joystick.png',
    'manette' => self::AVATARS_URL . 'manette.png',
    'tourne-disque' => self::AVATARS_URL . 'tourne-disque.png'
];
```

## 📋 Fonctions principales

### `sisme_admin_get_user_data($user_id)`

**Description:** Récupérer toutes les données d'un utilisateur avec vérifications de permissions

**Paramètres:**
- `int $user_id` - ID de l'utilisateur

**Return structure complète:**
```php
[
    'ID' => int,
    'display_name' => string,
    'user_login' => string, 
    'user_email' => string,
    'user_nicename' => string,
    'user_registered' => string, // Format: Y-m-d H:i:s
    'user_status' => int,
    'roles' => array, // Liste des rôles WordPress
    'capabilities' => array, // Toutes les capabilities

    'preferences' => [
        'platforms' => array, // Plateformes préférées
        'genres' => array, // Genres préférés  
        'player_types' => array, // Types de joueur
        'notifications' => array, // Paramètres notifications
        'privacy_public' => bool, // Profil public/privé
        'avatar' => string // URL complète avatar sélectionné
    ],

    'developer' => [
        'status' => string, // 'none', 'pending', 'approved', 'rejected'
        'has_developer_role' => bool,
        'application' => [
            // Données candidature si autorisé à les voir
            'company_name' => string,
            'company_type' => string,
            'company_address' => string,
            'company_city' => string,
            'company_postal_code' => string,
            'company_country' => string,
            'representative_name' => string,
            'representative_country' => string,
            'representative_email' => string,
            'representative_phone' => string,
            'submitted_date' => string, // Y-m-d H:i:s
            'reviewed_date' => string, // Y-m-d H:i:s
            'admin_notes' => string
        ],
        'profile' => [
            // Profil développeur public
            'studio_name' => string,
            'website' => string,
            'bio' => string,
            'avatar_studio' => string, // attachment_id
            'verified' => bool,
            'public_contact' => string
        ]
    ],

    'gaming_stats' => [
        'favorite_games' => array, // IDs des jeux favoris
        'owned_games' => array, // IDs des jeux possédés
        'wishlist_games' => array, // IDs wishlist
        'completed_games' => array, // IDs jeux terminés
        'total_games_collections' => int // Somme totale
    ],

    'social_stats' => [
        'friends_list' => array, // IDs des amis
        'total_friends' => int
    ],

    'profile_settings' => [
        'profile_visibility' => string, // 'public', 'private' 
        'show_stats' => bool, // Afficher stats publiquement
        'allow_friend_requests' => bool, // Autoriser demandes d'amis
        'last_login' => string, // Timestamp dernier login
        'profile_created' => string // Date création profil
    ],

    'activity' => [
        'registration_date' => string, // Date inscription WordPress
        'last_login' => string, // Timestamp dernier login
        'profile_created' => string, // Date création profil gaming
        'total_posts' => int, // Posts publiés
        'total_comments' => int, // Commentaires postés
        'is_active' => bool // Actif dans les 30 derniers jours
    ]
]
```

**Return en cas d'erreur permissions:**
```php
[
    'error' => 'Vous n\'avez pas l\'autorisation de voir ces données',
    'user_id' => int
]
```

---

### `sisme_admin_get_user_preferences($user_id)`

**Description:** Récupérer uniquement les préférences utilisateur  

**Paramètres:**
- `int $user_id` - ID de l'utilisateur

**Return:**
```php
[
    'platforms' => array, // Ex: ['pc', 'playstation', 'xbox']
    'genres' => array, // Ex: ['action', 'rpg', 'strategy'] 
    'player_types' => array, // Ex: ['casual', 'hardcore']
    'notifications' => array, // Paramètres notifications
    'privacy_public' => bool, // true = public, false = privé
    'avatar' => string // URL complète de l'avatar (avec fallback sur default)
]
```

---

### `get_user_developer_data($user_id)`

**Description:** Récupérer les données développeur avec vérification de permissions

**Paramètres:**
- `int $user_id` - ID de l'utilisateur

**Return:**
```php
[
    'status' => string, // 'none', 'pending', 'approved', 'rejected'
    'has_developer_role' => bool,
    'application' => [
        // Visible seulement si permissions admin/propriétaire
        'company_name' => string,
        'company_type' => string, // 'individual', 'company', 'association'
        'company_address' => string,
        'company_city' => string,
        'company_postal_code' => string,
        'company_country' => string,
        'representative_name' => string,
        'representative_country' => string,
        'representative_email' => string,
        'representative_phone' => string,
        'submitted_date' => string, // Y-m-d H:i:s
        'reviewed_date' => string, // Y-m-d H:i:s ou vide
        'admin_notes' => string
    ],
    'profile' => [
        // Profil public développeur (futur)
        'studio_name' => string,
        'website' => string,
        'bio' => string,
        'avatar_studio' => string, // attachment_id
        'verified' => bool,
        'public_contact' => string
    ]
]
```

---

### `get_user_gaming_stats($user_id)`

**Description:** Statistiques gaming de l'utilisateur

**Paramètres:**
- `int $user_id` - ID de l'utilisateur

**Return:**
```php
[
    'favorite_games' => array, // IDs des jeux favoris
    'owned_games' => array, // IDs des jeux possédés
    'wishlist_games' => array, // IDs de la wishlist
    'completed_games' => array, // IDs des jeux terminés
    'total_games_collections' => int // Somme de toutes les collections
]
```

---

### `get_user_social_stats($user_id)`

**Description:** Statistiques sociales de l'utilisateur

**Paramètres:**
- `int $user_id` - ID de l'utilisateur

**Return:**
```php
[
    'friends_list' => array, // IDs des utilisateurs amis
    'total_friends' => int // Nombre d'amis
]
```

---

### `get_user_profile_settings($user_id)`

**Description:** Paramètres de profil et confidentialité

**Paramètres:**
- `int $user_id` - ID de l'utilisateur

**Return:**
```php
[
    'profile_visibility' => string, // 'public' (défaut) ou 'private'
    'show_stats' => bool, // true (défaut) = afficher stats publiquement
    'allow_friend_requests' => bool, // true (défaut) = autoriser demandes
    'last_login' => string, // Timestamp dernier login
    'profile_created' => string // Date création profil gaming
]
```

---

### `get_user_activity_summary($user_id)`

**Description:** Résumé d'activité utilisateur  

**Paramètres:**
- `int $user_id` - ID de l'utilisateur

**Return:**
```php
[
    'registration_date' => string, // Date inscription WordPress (user_registered)
    'last_login' => string, // Timestamp dernier login gaming
    'profile_created' => string, // Date création profil gaming
    'total_posts' => int, // Nombre de posts publiés
    'total_comments' => int, // Nombre de commentaires postés
    'is_active' => bool // Actif dans les 30 derniers jours
]
```

---

### `sisme_admin_get_users_list($args)`

**Description:** Récupérer une liste d'utilisateurs avec filtres avancés

**Paramètres:**
- `array $args` - Critères de filtrage (optionnel)

**Arguments par défaut:**
```php
[
    'role' => 'all', // Filtrer par rôle spécifique
    'number' => 9999, // Limite de résultats  
    'offset' => 0, // Décalage pour pagination
    'search' => '', // Recherche dans nom/email/login
    'orderby' => 'registered', // Tri par champ
    'order' => 'DESC', // Ordre croissant/décroissant
    'meta_query' => [], // Query métadonnées personnalisée
    'include_meta' => true // Inclure métadonnées de base
]
```

**Return:**
```php
[
    'users' => [
        [
            'ID' => int,
            'display_name' => string,
            'user_login' => string,
            'user_email' => string,
            'user_nicename' => string,
            'user_registered' => string,
            'roles' => array,
            'meta' => [
                // Si include_meta = true
                'last_login' => string,
                'profile_visibility' => string,
                'developer_status' => string,
                'avatar' => string // URL complète
            ]
        ]
        // ... autres utilisateurs
    ],
    'total' => int, // Nombre total dans la base (sans limite)
    'found' => int // Nombre trouvé avec les critères
]
```

**Return en cas d'erreur permissions:**
```php
[
    'error' => 'Permissions insuffisantes',
    'users' => [],
    'total' => 0,
    'found' => 0
]
```

---

### `search_users_advanced($search_args)`

**Description:** Recherche avancée d'utilisateurs avec critères multiples

**Paramètres:**
- `array $search_args` - Critères de recherche avancée

**Arguments disponibles:**
```php
[
    'search_term' => '', // Terme de recherche textuelle
    'search_in' => ['display_name', 'user_email'], // Champs de recherche
    'role' => '', // Filtrer par rôle
    'developer_status' => '', // Filtrer par statut développeur
    'has_games' => false, // Utilisateurs ayant des jeux
    'is_active' => false, // Utilisateurs actifs récemment
    'limit' => 20 // Limite de résultats
]
```

**Return:**
```php
[
    'error' => null, // Message d'erreur si permissions insuffisantes
    'users' => [
        // Structure identique à sisme_admin_get_users_list
    ],
    'total_found' => int, // Nombre trouvé
    'total_possible' => int // Total dans la base
]
```

## 🔐 Fonctions de sécurité et validation

### `can_view_user_data($user_id)`

**Description:** Vérifier si l'utilisateur actuel peut voir les données de l'utilisateur cible

**Return:** `bool` - true si autorisé

### `check_admin_permissions()`

**Description:** Vérifier les permissions administrateur

**Return:** `bool` - true si admin

### `is_user_recently_active($user_id)`

**Description:** Vérifier si un utilisateur est actif dans les 30 derniers jours  

**Return:** `bool` - true si actif récemment

### `validate_user_id($user_id)`

**Description:** Valider qu'un ID utilisateur existe et est valide

**Return:** `bool` - true si ID valide

---

## 🎯 Notes importantes

- **Permissions:** Toutes les fonctions vérifient les permissions avant de retourner des données sensibles
- **Fallbacks:** Chaque métadonnée a une valeur par défaut si non définie
- **Avatar:** L'URL de l'avatar inclut toujours un fallback vers l'avatar par défaut
- **Sanitisation:** Les données sont automatiquement nettoyées selon les permissions de l'utilisateur
- **Performance:** Les fonctions de liste supportent la pagination et la limitation de résultats