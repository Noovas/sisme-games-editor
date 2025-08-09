# 🏢 Dev Editor Manager - API REF

**Version:** 1.0.0 | **Status:** Module complet et fonctionnel  
Documentation technique pour le module gestionnaire de développeurs/éditeurs.

---

## 📁 Structure du Module

```
includes/dev-editor-manager/
├── dev-editor-manager-loader.php      # Chargement automatique
├── dev-editor-manager.php             # API CRUD principal
└── assets/
    └── dev-editor-manager.js           # Interactions modal/recherche
```

---

## 🏢 dev-editor-manager.php

**Classe :** `Sisme_Dev_Editor_Manager`

### API CRUD Principal

<details>
<summary><code>get_all_entities()</code></summary>

```php
// Récupérer tous les développeurs/éditeurs
// @return array - Liste complète avec données formatées
$entities = Sisme_Dev_Editor_Manager::get_all_entities();

// Retour:
[
    [
        'id' => 123,
        'name' => 'Studio XYZ',
        'slug' => 'studio-xyz',
        'website' => 'https://www.studio-xyz.com',
        'games_count' => 5
    ],
    // ...
]
```
</details>

<details>
<summary><code>get_entity_by_id($entity_id)</code></summary>

```php
// Récupérer une entité par son ID
// @param int $entity_id - ID de l'entité (term_id)
// @return array|false - Données de l'entité ou false si introuvable
$entity = Sisme_Dev_Editor_Manager::get_entity_by_id(123);
```
</details>

<details>
<summary><code>find_entity_by_name($name)</code></summary>

```php
// Rechercher une entité par son nom exact
// @param string $name - Nom de l'entité
// @return array|false - Données de l'entité ou false si introuvable
$entity = Sisme_Dev_Editor_Manager::find_entity_by_name('Studio XYZ');
```
</details>

<details>
<summary><code>create_entity($name, $website = '')</code></summary>

```php
// Créer un nouveau développeur/éditeur
// @param string $name - Nom de l'entité (requis)
// @param string $website - Site web optionnel
// @return array|WP_Error - Données de l'entité créée ou erreur
$result = Sisme_Dev_Editor_Manager::create_entity('Nouveau Studio', 'https://example.com');

// Erreurs possibles:
// - 'empty_name': Le nom est requis
// - 'no_parent': Catégorie parent introuvable
// - 'entity_exists': Une entité avec ce nom existe déjà
```
</details>

<details>
<summary><code>update_entity($entity_id, $name, $website = '')</code></summary>

```php
// Mettre à jour une entité existante
// @param int $entity_id - ID de l'entité
// @param string $name - Nouveau nom
// @param string $website - Nouveau site web
// @return array|WP_Error - Données mises à jour ou erreur
$result = Sisme_Dev_Editor_Manager::update_entity(123, 'Nouveau Nom', 'https://new-site.com');

// Erreurs possibles:
// - 'invalid_params': ID et nom requis
// - 'entity_not_found': Entité introuvable
// - 'name_exists': Une autre entité avec ce nom existe déjà
```
</details>

<details>
<summary><code>delete_entity($entity_id)</code></summary>

```php
// Supprimer une entité
// @param int $entity_id - ID de l'entité
// @return bool|WP_Error - true si succès, erreur sinon
$result = Sisme_Dev_Editor_Manager::delete_entity(123);

// Erreurs possibles:
// - 'invalid_id': ID invalide
// - 'entity_not_found': Entité introuvable
// - 'entity_in_use': Entité utilisée dans X jeu(x)
```
</details>

### Fonctions Utilitaires

<details>
<summary><code>count_games_for_entity($entity_id)</code></summary>

```php
// Compter les jeux liés à une entité
// @param int $entity_id - ID de l'entité
// @return int - Nombre de jeux utilisant cette entité
$count = Sisme_Dev_Editor_Manager::count_games_for_entity(123);
```
</details>

<details>
<summary><code>get_entities_stats()</code></summary>

```php
// Récupérer les statistiques globales
// @return array - Statistiques complètes
$stats = Sisme_Dev_Editor_Manager::get_entities_stats();

// Retour:
[
    'total_entities' => 15,
    'with_website' => 8,
    'total_games_linked' => 42
]
```
</details>

<details>
<summary><code>get_parent_category_id()</code></summary>

```php
// Récupérer l'ID de la catégorie parent "editeurs-developpeurs"
// @return int - ID de la catégorie parent ou 0 si introuvable
$parent_id = Sisme_Dev_Editor_Manager::get_parent_category_id();
```
</details>

---

## 🌐 Interface Admin

### Page admin

**URL :** Admin → Sisme Games → 🏢 Dev/Éditeurs

**Fonctionnalités :**
- **Statistiques** : Total entités, avec site web, jeux liés
- **Formulaire d'ajout** : Nom + site web optionnel
- **Tableau complet** avec recherche par nom
- **Actions conditionnelles** :
  * Modifier : Toujours disponible (modal)
  * Supprimer : Seulement si 0 jeu lié
- **Recherche temps réel** : Filtre instantané par nom

### Colonnes du tableau
- **Nom** : Nom de l'entité
- **Site web** : Lien cliquable ou "Aucun"
- **Jeux liés** : Badge avec nombre
- **Actions** : Boutons Modifier/Supprimer

### Sécurité
- Nonces WordPress obligatoires
- Permissions admin requises (`manage_options`)
- Validation côté client et serveur
- Protection contre suppression si entités utilisées

---

## 🎯 JavaScript - sismeDevEditorAjax

**Namespace :** `window.sismeDevEditorAjax`

### Configuration

```javascript
// Configuration du module AJAX
sismeDevEditorAjax = {
    ajaxurl: '/wp-admin/admin-ajax.php',
    nonce: 'abc123def456'
};
```

### Fonctionnalités

#### Modal de Modification
```javascript
// Ouverture automatique avec pré-remplissage
// Fermeture : bouton, clic extérieur, Escape
// Validation en temps réel des champs
```

#### Recherche Instantanée
```javascript
// Filtre par nom d'entité
// Recherche insensible à la casse
// Escape pour vider la recherche
```

#### Validation Formulaires
```javascript
// Nom : minimum 2 caractères
// URL : format http/https valide
// Messages d'erreur dynamiques
```

---

## 🔧 Intégration Plugin

### Chargement Automatique

Le module se charge automatiquement via le plugin principal :

```php
// Dans sisme-games-editor.php
define('SISME_GAMES_MODULES', array(
    // Modules
    "dev-editor-manager"  // ← Module ajouté
));
```

### Actions AJAX Disponibles

```php
// Handlers AJAX enregistrés automatiquement
add_action('wp_ajax_sisme_dev_editor_create', [...]);
add_action('wp_ajax_sisme_dev_editor_update', [...]);
add_action('wp_ajax_sisme_dev_editor_delete', [...]);
```

### Logging Debug

```php
// Logs automatiques si WP_DEBUG = true
// [Sisme Dev Editor Manager] Module dev-editor-manager initialisé
// [Sisme] Module initialisé : dev-editor-manager
```

---

## 💡 Exemples d'Usage

### Création Simple

```php
// Créer un développeur
$studio = Sisme_Dev_Editor_Manager::create_entity('Mon Studio');

if (!is_wp_error($studio)) {
    echo "Studio créé avec l'ID : " . $studio['id'];
}
```

### Recherche et Modification

```php
// Rechercher puis modifier
$entity = Sisme_Dev_Editor_Manager::find_entity_by_name('Ancien Nom');

if ($entity) {
    $result = Sisme_Dev_Editor_Manager::update_entity(
        $entity['id'], 
        'Nouveau Nom',
        'https://nouveau-site.com'
    );
}
```

### Intégration Game Submission

```php
// Utilisation dans la création de jeu depuis soumission
$studio_name = $submission['game_data']['game_studio_name'];
$studio_url = $submission['game_data']['game_studio_url'];

// Chercher ou créer l'entité
$studio = Sisme_Dev_Editor_Manager::find_entity_by_name($studio_name);
if (!$studio) {
    $studio = Sisme_Dev_Editor_Manager::create_entity($studio_name, $studio_url);
}

// Utiliser l'ID pour le jeu
$developers_ids = [$studio['id']];
update_term_meta($game_tag_id, Sisme_Utils_Games::META_DEVELOPERS, $developers_ids);
```

---

## ⚠️ Notes Importantes

### Limitations
- **Pas de suppression** si l'entité est utilisée dans des jeux
- **Noms uniques** : pas de doublons autorisés
- **Site web optionnel** : format URL requis si fourni

### Bonnes Pratiques
- Toujours vérifier `is_wp_error()` après création/modification
- Utiliser `find_entity_by_name()` avant création pour éviter doublons
- Compter les jeux liés avant suppression manuelle
- Utiliser les stats pour monitoring

### Structure de Données
```php
// Structure WordPress sous-jacente
// Catégorie parent: "editeurs-developpeurs"
// Chaque entité: catégorie enfant avec meta 'website_url'
// Référencement: term_meta des jeux avec 'game_developers'/'game_publishers'
```