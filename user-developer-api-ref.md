# 🎮 User Developer - API REF

**Version:** 1.0.0 | **Status:** WIP - Étape 1/3 terminée  
Documentation technique pour le module développeur utilisateur.

---

## 🚀 Vue d'Ensemble

### Module user-developer
- **Objectif :** Permettre aux utilisateurs de candidater pour devenir développeur et soumettre leurs jeux
- **Intégration :** Extension du dashboard utilisateur existant
- **Phases :** 3 phases (Candidature → Approbation → Gestion jeux)

### États Développeur
- **none** - Utilisateur lambda (peut candidater)
- **pending** - Candidature en cours d'examen
- **approved** - Développeur approuvé (peut soumettre des jeux)
- **rejected** - Candidature rejetée (peut recandidater)

---

## 📋 user-developer-loader.php

**Classe :** `Sisme_User_Developer_Loader`

### Initialisation

<details>
<summary><code>Sisme_User_Developer_Loader::get_instance()</code></summary>

```php
// Singleton - Récupérer l'instance unique du loader
// @return Sisme_User_Developer_Loader Instance unique
$loader = Sisme_User_Developer_Loader::get_instance();
```
</details>

### Hooks Dashboard

<details>
<summary><code>add_developer_section($accessible_sections, $user_id)</code></summary>

```php
// Ajouter la section développeur aux sections accessibles du dashboard
// @param array $accessible_sections - Sections accessibles actuelles
// @param int $user_id - ID utilisateur
// @return array - Sections avec 'developer' ajouté
// Hook: 'sisme_dashboard_accessible_sections'
```
</details>

<details>
<summary><code>add_developer_nav_item($nav_items, $user_id)</code></summary>

```php
// Ajouter l'item de navigation développeur au dashboard
// @param array $nav_items - Items de navigation actuels
// @param int $user_id - ID utilisateur
// @return array - Items avec navigation développeur ajoutée
// Hook: 'sisme_dashboard_navigation_items'
//
// Structure retournée:
// [
//     'section' => 'developer',
//     'icon' => '📝|⏳|🎮|❌', // Selon statut
//     'text' => 'Devenir Développeur|Candidature en cours|Mes Jeux|Candidature rejetée',
//     'badge' => '1'|null, // Badge pour statut pending
//     'class' => 'sisme-nav-developer-{status}'
// ]
```
</details>

<details>
<summary><code>render_developer_section($content, $section, $dashboard_data)</code></summary>

```php
// Rendu de la section développeur
// @param string $content - Contenu actuel
// @param string $section - Section demandée
// @param array $dashboard_data - Données dashboard
// @return string - HTML de la section développeur
// Hook: 'sisme_dashboard_render_section'
```
</details>

### Utilitaires

<details>
<summary><code>can_submit_games($user_id)</code></summary>

```php
// Vérifier si un utilisateur peut soumettre des jeux
// @param int $user_id - ID utilisateur
// @return bool - Peut soumettre des jeux (statut 'approved')
$can_submit = Sisme_User_Developer_Loader::can_submit_games(42);
```
</details>

<details>
<summary><code>get_developer_data($user_id)</code></summary>

```php
// Récupérer toutes les données développeur d'un utilisateur
// @param int $user_id - ID utilisateur
// @return array|null - Données développeur ou null si module indisponible
$data = Sisme_User_Developer_Loader::get_developer_data(42);
```
</details>

---

## 📊 user-developer-data-manager.php

**Classe :** `Sisme_User_Developer_Data_Manager`

### Constantes

```php
// Métadonnées utilisateur
const META_DEVELOPER_STATUS = 'sisme_user_developer_status';
const META_DEVELOPER_APPLICATION = 'sisme_user_developer_application';
const META_DEVELOPER_PROFILE = 'sisme_user_developer_profile';

// Statuts possibles
const STATUS_NONE = 'none';
const STATUS_PENDING = 'pending';
const STATUS_APPROVED = 'approved';
const STATUS_REJECTED = 'rejected';
```

### Gestion Statuts

<details>
<summary><code>get_developer_status($user_id)</code></summary>

```php
// Récupérer le statut développeur d'un utilisateur
// @param int $user_id - ID utilisateur
// @return string - Statut ('none', 'pending', 'approved', 'rejected')
$status = Sisme_User_Developer_Data_Manager::get_developer_status(42);
```
</details>

<details>
<summary><code>set_developer_status($user_id, $status)</code></summary>

```php
// Définir le statut développeur d'un utilisateur
// @param int $user_id - ID utilisateur
// @param string $status - Nouveau statut
// @return bool - Succès de la mise à jour
$success = Sisme_User_Developer_Data_Manager::set_developer_status(42, 'approved');
```
</details>

### Récupération Données

<details>
<summary><code>get_developer_data($user_id)</code></summary>

```php
// Récupérer toutes les données développeur
// @param int $user_id - ID utilisateur
// @return array - Données complètes développeur
//
// Structure retournée:
// [
//     'status' => 'none|pending|approved|rejected',
//     'application' => array|null, // Données candidature
//     'profile' => array|null, // Profil développeur public
//     'submitted_games' => array, // Jeux soumis (vide pour l'instant)
//     'stats' => array // Statistiques développeur
// ]
$data = Sisme_User_Developer_Data_Manager::get_developer_data(42);
```
</details>

<details>
<summary><code>get_developer_application($user_id)</code></summary>

```php
// Récupérer les données de candidature
// @param int $user_id - ID utilisateur
// @return array|null - Données candidature ou null
//
// Structure retournée:
// [
//     'studio_name' => string,
//     'studio_description' => string,
//     'studio_website' => string,
//     'studio_social_links' => array,
//     'representative_firstname' => string,
//     'representative_lastname' => string,
//     'representative_birthdate' => string,
//     'representative_address' => string,
//     'representative_city' => string,
//     'representative_country' => string,
//     'representative_email' => string,
//     'representative_phone' => string,
//     'submitted_date' => string,
//     'reviewed_date' => string,
//     'admin_notes' => string
// ]
$application = Sisme_User_Developer_Data_Manager::get_developer_application(42);
```
</details>

<details>
<summary><code>get_developer_profile($user_id)</code></summary>

```php
// Récupérer le profil développeur public
// @param int $user_id - ID utilisateur
// @return array|null - Profil développeur ou null
//
// Structure retournée:
// [
//     'studio_name' => string,
//     'website' => string,
//     'bio' => string,
//     'avatar_studio' => string,
//     'verified' => bool,
//     'public_contact' => string
// ]
$profile = Sisme_User_Developer_Data_Manager::get_developer_profile(42);
```
</details>

<details>
<summary><code>get_developer_stats($user_id)</code></summary>

```php
// Récupérer les statistiques développeur
// @param int $user_id - ID utilisateur
// @return array - Statistiques développeur
//
// Structure retournée:
// [
//     'total_games' => int,
//     'approved_games' => int,
//     'pending_games' => int,
//     'total_views' => int,
//     'total_downloads' => int,
//     'join_date' => string|null
// ]
$stats = Sisme_User_Developer_Data_Manager::get_developer_stats(42);
```
</details>

### Sauvegarde Données

<details>
<summary><code>save_developer_application($user_id, $application_data)</code></summary>

```php
// Sauvegarder une candidature développeur
// @param int $user_id - ID utilisateur
// @param array $application_data - Données candidature
// @return bool - Succès de la sauvegarde
//
// Données acceptées:
// - studio_name (string, requis)
// - studio_description (string, requis, max 500 chars)
// - studio_website (string, URL optionnelle)
// - studio_social_links (array: platform => handle)
// - representative_firstname (string, requis, max 50 chars)
// - representative_lastname (string, requis, max 50 chars)
// - representative_birthdate (string, date YYYY-MM-DD, requis)
// - representative_address (string, requis, max 200 chars)
// - representative_city (string, requis, max 100 chars)
// - representative_country (string, requis, code pays)
// - representative_email (string, email requis)
// - representative_phone (string, téléphone requis)
//
// Actions automatiques:
// - Sanitisation des données
// - Ajout submitted_date
// - Changement statut vers 'pending'
$success = Sisme_User_Developer_Data_Manager::save_developer_application(42, $form_data);
```
</details>

### Validation

<details>
<summary><code>can_apply($user_id)</code></summary>

```php
// Vérifier si un utilisateur peut candidater
// @param int $user_id - ID utilisateur
// @return bool - Peut candidater (statut 'none' ou 'rejected')
$can_apply = Sisme_User_Developer_Data_Manager::can_apply(42);
```
</details>

<details>
<summary><code>is_approved_developer($user_id)</code></summary>

```php
// Vérifier si un utilisateur est développeur approuvé
// @param int $user_id - ID utilisateur
// @return bool - Est développeur approuvé
$is_approved = Sisme_User_Developer_Data_Manager::is_approved_developer(42);
```
</details>

---

## 🎨 user-developer-renderer.php

**Classe :** `Sisme_User_Developer_Renderer`

### Rendu Principal

<details>
<summary><code>render_developer_section($user_id, $developer_status, $dashboard_data)</code></summary>

```php
// Rendu principal de la section développeur
// @param int $user_id - ID utilisateur
// @param string $developer_status - Statut développeur
// @param array $dashboard_data - Données dashboard
// @return string - HTML de la section selon le statut
$html = Sisme_User_Developer_Renderer::render_developer_section(42, 'none', $dashboard_data);
```
</details>

### Rendus par État

<details>
<summary><code>render_application_form($user_id)</code></summary>

```php
// État 1: Formulaire de candidature (statut 'none')
// @param int $user_id - ID utilisateur
// @return string - HTML du formulaire de candidature
//
// Contenu inclus:
// - Header avec icône et description
// - Liste des avantages développeur
// - Exemples de développeurs
// - Bouton "Faire une demande"
// - Modal de candidature (placeholder)
```
</details>

<details>
<summary><code>render_pending_status($user_id)</code></summary>

```php
// État 2: Candidature en cours (statut 'pending')
// @param int $user_id - ID utilisateur
// @return string - HTML du statut pending
//
// Contenu inclus:
// - Header avec icône d'attente
// - Statut card de candidature soumise
// - Bouton "Voir ma candidature"
```
</details>

<details>
<summary><code>render_developer_dashboard($user_id)</code></summary>

```php
// État 3: Développeur approuvé (statut 'approved')
// @param int $user_id - ID utilisateur
// @return string - HTML du dashboard développeur
//
// Contenu inclus:
// - Header "Mes Jeux"
// - Statistiques développeur (jeux publiés, en attente, vues)
// - Boutons "Soumettre un jeu" et "Statistiques détaillées"
```
</details>

<details>
<summary><code>render_rejected_status($user_id)</code></summary>

```php
// État 4: Candidature rejetée (statut 'rejected')
// @param int $user_id - ID utilisateur
// @return string - HTML du statut rejeté
//
// Contenu inclus:
// - Header avec icône de rejet
// - Conseils pour prochaine candidature
// - Bouton "Faire une nouvelle demande"
```
</details>

---

## 🎯 JavaScript - SismeDeveloper

**Namespace :** `window.SismeDeveloper`

### Configuration

```javascript
// Configuration du module
SismeDeveloper.config = {
    formSelector: '#sisme-developer-application-form',
    containerSelector: '.sisme-developer-form-container'
};
```

### Méthodes Principales

<details>
<summary><code>SismeDeveloper.init()</code></summary>

```javascript
// Initialisation du module développeur
// @return void
// Lie les événements et initialise les interactions
SismeDeveloper.init();
```
</details>

<details>
<summary><code>SismeDeveloper.showApplicationForm()</code></summary>

```javascript
// Afficher le formulaire de candidature
// @return void
// Affiche la modal avec animation fadeIn
SismeDeveloper.showApplicationForm();
```
</details>

<details>
<summary><code>SismeDeveloper.hideApplicationForm()</code></summary>

```javascript
// Masquer le formulaire de candidature
// @return void
// Masque la modal avec animation fadeOut
SismeDeveloper.hideApplicationForm();
```
</details>

<details>
<summary><code>SismeDeveloper.showApplicationDetails()</code></summary>

```javascript
// Afficher les détails de candidature (statut pending)
// @return void
// Placeholder pour future implémentation
SismeDeveloper.showApplicationDetails();
```
</details>

### Utilitaires

<details>
<summary><code>SismeDeveloper.log(message, data)</code></summary>

```javascript
// Logging utilitaire avec préfixe
// @param string message - Message à logger
// @param mixed data - Données optionnelles
// @return void
SismeDeveloper.log('Test message', {key: 'value'});
```
</details>

---

## 🎨 Styles CSS

### Variables CSS

```css
:root {
    /* Couleurs spécifiques développeur */
    --sisme-developer-bg-card: rgba(255, 255, 255, 0.05);
    --sisme-developer-bg-hover: rgba(255, 255, 255, 0.08);
    --sisme-developer-border: rgba(255, 255, 255, 0.1);
    --sisme-developer-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    --sisme-developer-shadow-hover: 0 8px 24px rgba(0, 0, 0, 0.4);
    
    /* États développeur */
    --sisme-developer-pending: #d29922;
    --sisme-developer-approved: #3fb950;
    --sisme-developer-rejected: #f85149;
    --sisme-developer-none: #58a6ff;
}
```

### Classes Principales

```css
/* Container principal */
.sisme-developer-state

/* États spécifiques */
.sisme-developer-state-apply    /* Statut 'none' */
.sisme-developer-state-pending  /* Statut 'pending' */
.sisme-developer-state-approved /* Statut 'approved' */
.sisme-developer-state-rejected /* Statut 'rejected' */

/* Composants */
.sisme-developer-header
.sisme-developer-icon
.sisme-developer-content
.sisme-benefits-list
.sisme-developer-application      /* Formulaire intégré */
.sisme-form-section
.sisme-form-field
.sisme-social-input-group
.sisme-form-feedback
```

---

## 🚀 Intégration Dashboard

### Hooks WordPress

```php
// Extension des sections accessibles
add_filter('sisme_dashboard_accessible_sections', [$this, 'add_developer_section'], 10, 2);

// Extension de la navigation
add_filter('sisme_dashboard_navigation_items', [$this, 'add_developer_nav_item'], 10, 2);

// Rendu de la section
add_filter('sisme_dashboard_render_section', [$this, 'render_developer_section'], 10, 3);

// Extension des sections JavaScript valides
add_filter('sisme_dashboard_valid_sections', [$this, 'add_developer_valid_section'], 10, 1);
```

### Chargement Automatique

```php
// Le module se charge automatiquement via user-loader.php
// Structure respectée: user-developer/user-developer-loader.php
// Classe: Sisme_User_Developer_Loader
// Méthode: get_instance()
```

---

## 📋 Métadonnées WordPress

### Structure des Données

```php
// Métadonnée: 'sisme_user_developer_status'
// Valeurs: 'none', 'pending', 'approved', 'rejected'

// Métadonnée: 'sisme_user_developer_application'
// Structure:
[
    'studio_name' => string,
    'website' => string (URL),
    'description' => string,
    'portfolio_links' => array of URLs,
    'experience' => string,
    'motivation' => string,
    'contact_email' => string (email),
    'social_links' => [
        'twitter' => string,
        'discord' => string,
        'instagram' => string,
        'youtube' => string,
        'twitch' => string
    ],
    'submitted_date' => string (Y-m-d H:i:s),
    'reviewed_date' => string (Y-m-d H:i:s),
    'admin_notes' => string
]

// Métadonnée: 'sisme_user_developer_profile'
// Structure:
[
    'studio_name' => string,
    'website' => string (URL),
    'bio' => string,
    'avatar_studio' => string (attachment_id),
    'verified' => bool,
    'public_contact' => string (email)
]
```

---

## 🔄 Workflow Développeur

### Phase 1: Candidature (none → pending)
1. Utilisateur remplit formulaire candidature
2. Système sauvegarde avec `save_developer_application()`
3. Statut passe automatiquement à 'pending'
4. Notification admin (à implémenter)

### Phase 2: Validation Admin (pending → approved/rejected)
1. Admin examine candidature depuis interface admin
2. Admin approuve ou rejette avec notes
3. Statut mis à jour vers 'approved' ou 'rejected'
4. Notification utilisateur (à implémenter)

### Phase 3: Développeur Actif (approved)
1. Utilisateur accède à l'interface "Mes Jeux"
2. Possibilité de soumettre des jeux
3. Gestion du catalogue de jeux
4. Statistiques et analytics

---

## 🎯 Prochaines Étapes

### Étape 2 - Formulaire Candidature
- [ ] Formulaire complet avec validation
- [ ] Gestion AJAX pour soumission
- [ ] Système de sauvegarde et sécurité
- [ ] Interface "Voir ma candidature"

### Étape 3 - Interface Admin
- [ ] Page admin "Candidatures Développeur"
- [ ] Workflow approbation/rejet
- [ ] Système de notifications
- [ ] Extension interface jeux

### Étape 4 - Soumission Jeux
- [ ] Formulaire soumission jeu frontend
- [ ] Workflow modération jeux
- [ ] Interface "Mes Jeux" complète
- [ ] Statistiques développeur

---

## 🔗 Dépendances

### Modules Utils Utilisés
- `Sisme_Utils_Users` - Gestion utilisateurs et métadonnées
- `Sisme_Utils_Validation` - Validation des données formulaire
- `Sisme_Utils_Notifications` - Système de notifications (futur)

### Modules Dashboard Réutilisés
- `Sisme_User_Dashboard_Renderer` - Rendu composants dashboard
- `Sisme_User_Dashboard_Data_Manager` - Gestion données dashboard
- Navigation et assets dashboard existants

### Fonctions WordPress
- `update_user_meta()` / `get_user_meta()` - Métadonnées utilisateur
- `sanitize_text_field()` / `sanitize_textarea_field()` - Sanitisation
- `esc_url_raw()` / `sanitize_email()` - Validation URLs/emails