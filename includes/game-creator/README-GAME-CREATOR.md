# Module Game Creator - API Reference

## 🎯 Vue d'ensemble

Module autonome de création/gestion de jeux pour administrateurs uniquement. Gère la conversion des game-submissions en jeux officiels et permet la gestion CRUD complète.

## 📁 Architecture

```
/includes/game-creator/
├── game-creator-loader.php          # Loader et permissions
├── game-creator-constants.php       # Constantes centralisées
├── game-creator-validator.php       # Validation et sanitisation
├── game-creator-data-manager.php    # CRUD WordPress (term + meta)
└── game-creator.php                 # API publique principale
```

## ⚙️ Activation

Dans `sisme-games-editor.php` :
```php
define('SISME_GAMES_MODULES', array(
    //Modules
    "game-creator"  // ← Ajouter cette ligne
));
```

---

## 🔧 API Principale

**Classe :** `Sisme_Game_Creator`

### Création

<details>
<summary><code>create_game($game_data, $user_id = null)</code></summary>

```php
// Créer un nouveau jeu (admin uniquement)
// @param array $game_data - Données du jeu (validation automatique)
// @param int $user_id - ID utilisateur (optionnel, défaut: utilisateur courant)
// @return int|WP_Error - ID du terme créé ou erreur

$game_data = [
    'name' => 'Cyberpunk 2077',
    'description' => 'RPG futuriste dans Night City',
    'release_date' => '2020-12-10',
    'genres' => [15, 16], // IDs catégories
    'platforms' => ['windows', 'macos', 'linux'],
    'developers' => [123], // IDs entités
    'sections' => [
        ['title' => 'Histoire', 'content' => 'Dans un futur dystopique...', 'image_id' => 456]
    ]
];

$game_id = Sisme_Game_Creator::create_game($game_data);
// Retourne: 789 (ID du terme) ou WP_Error
```
</details>

<details>
<summary><code>create_from_submission_data($submission_data, $user_id = null)</code></summary>

```php
// Créer un jeu depuis une soumission développeur
// @param array $submission_data - Structure complète de soumission
// @param int $user_id - ID utilisateur (optionnel)
// @return int|WP_Error - ID du jeu créé ou erreur

$submission = [
    'id' => 'sub_123',
    'status' => 'pending',
    'game_data' => [
        'game_name' => 'Mon Jeu Indé',
        'game_description' => 'Super jeu indépendant',
        'game_genres' => [15, 16],
        'sections' => [...]
    ]
];

$game_id = Sisme_Game_Creator::create_from_submission_data($submission);
// Conversion automatique: game_name → name, game_description → description
```
</details>

### Modification

<details>
<summary><code>update_game($term_id, $game_data, $user_id = null)</code></summary>

```php
// Mettre à jour un jeu existant
// @param int $term_id - ID du jeu à modifier
// @param array $game_data - Nouvelles données (validation automatique)
// @param int $user_id - ID utilisateur (optionnel)
// @return bool|WP_Error - Succès ou erreur

$result = Sisme_Game_Creator::update_game(789, [
    'name' => 'Nouveau nom',
    'description' => 'Nouvelle description',
    'platforms' => ['windows', 'playstation5']
]);
// Retourne: true ou WP_Error
```
</details>

### Suppression

<details>
<summary><code>delete_game($term_id, $cleanup_files = false, $user_id = null)</code></summary>

```php
// Supprimer un jeu complètement
// @param int $term_id - ID du jeu
// @param bool $cleanup_files - Supprimer aussi les médias (défaut: false)
// @param int $user_id - ID utilisateur (optionnel)
// @return bool|WP_Error - Succès ou erreur

$result = Sisme_Game_Creator::delete_game(789, true); // Avec nettoyage médias
// Retourne: true ou WP_Error
```
</details>

### Lecture

<details>
<summary><code>get_game($term_id)</code></summary>

```php
// Récupérer les données complètes d'un jeu
// @param int $term_id - ID du jeu
// @return array|false - Données complètes ou false si introuvable

$game = Sisme_Game_Creator::get_game(789);
// Retourne:
[
    'term_id' => 789,
    'name' => 'Cyberpunk 2077',
    'slug' => 'cyberpunk-2077',
    'description' => 'RPG futuriste...',
    'genres' => [15, 16],
    'platforms' => ['windows', 'macos'],
    'sections' => [...],
    'developers' => [123],
    // ... toutes les métadonnées
]
```
</details>

### Utilitaires

<details>
<summary><code>duplicate_game($source_term_id, $new_name, $user_id = null)</code></summary>

```php
// Dupliquer un jeu existant
// @param int $source_term_id - ID du jeu source
// @param string $new_name - Nom du nouveau jeu
// @param int $user_id - ID utilisateur (optionnel)
// @return int|WP_Error - ID du nouveau jeu ou erreur

$new_game_id = Sisme_Game_Creator::duplicate_game(789, 'Cyberpunk 2077 - Édition Director');
```
</details>

<details>
<summary><code>validate_only($game_data)</code></summary>

```php
// Valider des données sans les enregistrer
// @param array $game_data - Données à valider
// @return array - ['valid' => bool, 'errors' => array, 'sanitized_data' => array]

$validation = Sisme_Game_Creator::validate_only([
    'name' => 'Nom trop long pour un jeu vraiment vraiment long...',
    'description' => 'Courte desc'
]);

if (!$validation['valid']) {
    foreach ($validation['errors'] as $error) {
        echo "❌ $error\n";
    }
}
```
</details>

---

## 📊 Constantes

**Classe :** `Sisme_Game_Creator_Constants`

### Limites et Validation

```php
MAX_GAME_NAME_LENGTH = 200
MAX_DESCRIPTION_LENGTH = 180    // Description courte
MAX_SECTIONS = 10
MIN_SECTIONS = 1
MAX_SECTION_TITLE_LENGTH = 100
MAX_SECTION_CONTENT_LENGTH = 2000
MIN_SECTION_CONTENT_LENGTH = 20
MAX_SCREENSHOTS = 10
MAX_EXTERNAL_LINKS = 8
MAX_DEVELOPERS = 5
MAX_PUBLISHERS = 3
```

### Plateformes Supportées

```php
PLATFORMS = [
    'windows' => 'Windows',
    'macos' => 'macOS',
    'linux' => 'Linux',
    'playstation4' => 'PlayStation 4',
    'playstation5' => 'PlayStation 5',
    'xbox-one' => 'Xbox One',
    'xbox-series' => 'Xbox Series X/S',
    'nintendo-switch' => 'Nintendo Switch',
    'android' => 'Android',
    'ios' => 'iOS',
    'web' => 'Navigateur'
]
```

### Liens Externes Supportés

```php
EXTERNAL_LINKS_PLATFORMS = [
    'steam' => 'Steam',
    'epic' => 'Epic Games Store',
    'gog' => 'GOG',
    'itch' => 'Itch.io',
    'playstation' => 'PlayStation Store',
    'xbox' => 'Xbox Store',
    'nintendo' => 'Nintendo eShop',
    'website' => 'Site officiel'
]
```

---

## ✅ Validation

**Classe :** `Sisme_Game_Creator_Validator`

### Règles de Validation

- **Nom** : Obligatoire, max 200 caractères
- **Description** : Optionnelle, max 180 caractères
- **Sections** : Min 1, max 10, titre requis (max 100 chars), contenu requis (20-2000 chars)
- **Plateformes** : Doivent exister dans `PLATFORMS`
- **Liens externes** : URLs valides, plateformes dans `EXTERNAL_LINKS_PLATFORMS`
- **Date sortie** : Format YYYY-MM-DD
- **Trailer** : URL YouTube/Vimeo uniquement
- **Screenshots** : Max 10, IDs numériques valides
- **Développeurs/Éditeurs** : Max 5/3, IDs numériques

### Messages d'Erreur Types

```php
"Le nom du jeu est obligatoire"
"Section 2 : titre obligatoire"
"Plateforme non supportée : playstation6"
"URL invalide pour steam : invalid-url"
"Nombre maximum de sections dépassé (10)"
"La date de sortie doit être au format YYYY-MM-DD"
```

---

## 🗃️ Structure de Données

### Stockage WordPress

**Taxonomie :** `post_tag` (jeux = étiquettes WordPress)
**Métadonnées :** `wp_termmeta` avec clés préfixées

### META_KEYS (Stockage)

```php
// Informations de base
'game_description'     // Description courte
'release_date'         // Date sortie YYYY-MM-DD
'last_update'          // Timestamp dernière modif

// Classification
'game_genres'          // Array IDs catégories
'game_platforms'       // Array plateformes
'game_modes'           // Array modes de jeu

// Entités
'game_developers'      // Array IDs développeurs
'game_publishers'      // Array IDs éditeurs

// Média
'cover_main'           // ID attachment principale
'cover_news'           // ID attachment news
'cover_patch'          // ID attachment patch
'cover_test'           // ID attachment test
'cover_vertical'       // ID attachment verticale
'screenshots'          // Array IDs attachments

// Contenu
'game_sections'        // Array sections fiche
'external_links'       // Array [plateforme => URL]
'trailer_link'         // URL YouTube/Vimeo

// Statuts
'is_team_choice'       // Boolean coup de cœur
```

### API_KEYS (Interface)

Les clés publiques pour récupérer les données :

```php
'term_id' => ID WordPress
'name' => Nom du jeu
'slug' => Slug URL
'description' => Description courte
'genres' => Genres formatés
'platforms' => Plateformes formatées
'sections' => Sections de contenu
'developers' => Développeurs
'covers' => ['main' => ID, 'news' => ID, ...]
```

---

## 🔄 Conversion Game Submission

### Mapping Automatique

```php
// Soumission → Game Creator
'game_name' → 'name'
'game_description' → 'description'
'game_release_date' → 'release_date'
'game_trailer' → 'trailer_link'
'game_genres' → 'genres'
'game_platforms' → 'platforms'
'game_modes' → 'modes'
'game_external_links' → 'external_links'
'covers' → 'covers'
'screenshots' → 'screenshots'
'sections' → 'sections'
```

### Résolution Entités

```php
// Auto-création développeur/éditeur via Sisme_Dev_Editor_Manager
'game_studio_name' + 'game_studio_url' → 'developers' = [ID_créé]
'game_publisher_name' + 'game_publisher_url' → 'publishers' = [ID_créé]
```

### Logique Choix Équipe

Marque automatiquement `is_team_choice = true` si :
- Admin note contient "excellent" 
- Soumission avec 5+ sections détaillées

---

## 🔐 Permissions

**Restriction :** Administrateurs uniquement (`manage_options`)

```php
// Vérification automatique sur toutes les méthodes
if (!user_can($user_id, 'manage_options')) {
    return new WP_Error('permission_denied', 'Permissions insuffisantes');
}
```

---

## 🚀 Hooks WordPress

### Actions Disponibles

```php
// Lors de la création
do_action('sisme_game_creator_game_created', $term_id, $game_data);

// Lors de la mise à jour
do_action('sisme_game_creator_game_updated', $term_id, $game_data);

// Avant suppression
do_action('sisme_game_creator_before_delete', $term_id, $term_object, $cleanup_files);

// Après suppression
do_action('sisme_game_creator_game_deleted', $term_id, $term_object);

// Lors de suppression de terme (automatique)
do_action('sisme_game_creator_term_deleted', $term_id, $deleted_term);
```

### Exemples d'Usage

```php
// Notification lors de création
add_action('sisme_game_creator_game_created', function($term_id, $game_data) {
    error_log("Nouveau jeu créé : " . $game_data['name'] . " (ID: $term_id)");
});

// Nettoyage personnalisé avant suppression
add_action('sisme_game_creator_before_delete', function($term_id, $term, $cleanup) {
    if ($cleanup) {
        // Nettoyage personnalisé
    }
});
```

---

## 📈 Statistiques

<details>
<summary><code>get_stats()</code></summary>

```php
// Obtenir les statistiques du module
// @return array - Statistiques diverses

$stats = Sisme_Game_Creator::get_stats();
// Retourne:
[
    'total_games' => 42,
    'team_choice_games' => 8,
    'constants_loaded' => true,
    'validator_loaded' => true,
    'data_manager_loaded' => true
]
```
</details>

---

## 🧪 Exemples Complets

### Création Standard

```php
$game_data = [
    'name' => 'Hades',
    'description' => 'Roguelike mythologique dans les Enfers grecs',
    'release_date' => '2020-09-17',
    'platforms' => ['windows', 'macos', 'nintendo-switch'],
    'genres' => [23, 24], // IDs Roguelike, Action
    'external_links' => [
        'steam' => 'https://store.steampowered.com/app/1145360/Hades/',
        'epic' => 'https://store.epicgames.com/p/hades'
    ],
    'sections' => [
        [
            'title' => 'Gameplay',
            'content' => 'Échappez-vous des Enfers dans ce roguelike narratif...',
            'image_id' => 789
        ],
        [
            'title' => 'Histoire',
            'content' => 'Incarnez Zagreus, fils d\'Hadès, dans sa quête...'
        ]
    ],
    'is_team_choice' => true
];

$game_id = Sisme_Game_Creator::create_game($game_data);

if (is_wp_error($game_id)) {
    echo "❌ Erreur : " . $game_id->get_error_message();
} else {
    echo "✅ Jeu créé avec l'ID : $game_id";
}
```

### Conversion Soumission

```php
// Dans le handler d'approbation admin
$submission = Sisme_Game_Submission_Data_Manager::get_submission_by_id($user_id, $submission_id);

$game_id = Sisme_Game_Creator::create_from_submission_data($submission);

if (!is_wp_error($game_id)) {
    // Sauvegarder l'ID dans la soumission
    Sisme_Game_Submission_Data_Manager::change_submission_status(
        $user_id, $submission_id, 'published',
        ['published_game_id' => $game_id]
    );
    
    echo "✅ Jeu créé depuis soumission : ID $game_id";
}
```

### Gestion d'Erreurs

```php
$validation = Sisme_Game_Creator::validate_only($data);

if (!$validation['valid']) {
    echo "❌ Erreurs de validation :\n";
    foreach ($validation['errors'] as $error) {
        echo "  • $error\n";
    }
} else {
    // Données valides, procéder à la création
    $game_id = Sisme_Game_Creator::create_game($validation['sanitized_data']);
}
```

---

## ⚠️ Notes Importantes

### Limitations

- **Admin uniquement** : Seuls les utilisateurs avec `manage_options`
- **Noms uniques** : Pas de doublons de slugs
- **Médias partagés** : Vérification avant suppression
- **Rollback automatique** : Si création échoue après changement statut

### Bonnes Pratiques

- Toujours vérifier `is_wp_error()` après les opérations
- Utiliser `validate_only()` pour tester avant création
- Prévoir la gestion d'erreurs dans les handlers AJAX
- Logger les créations importantes
- Tester la conversion soumission → jeu sur un environnement de dev

### Compatibilité

- **WordPress** : 5.0+
- **PHP** : 7.4+
- **Dépendances** : Système de modules Sisme Games Editor
- **Intégrations** : `Sisme_Dev_Editor_Manager` pour entités (optionnelle)