# 📋 Module User Soumission Jeux - API Reference

## 🏗️ **Architecture actuelle**

### **Structure des modules**
```
includes/user/user-dashboard/               # Dashboard base
├── user-dashboard-loader.php              # Shortcode + assets
├── user-dashboard-api.php                 # Shortcode [sisme_user_dashboard]
├── user-dashboard-renderer.php            # Rendu sections
├── user-dashboard-data-manager.php        # Données utilisateur
└── assets/
    ├── user-dashboard.css                 # Styles dashboard
    └── user-dashboard.js                  # Navigation + interactions

includes/user/user-developer/               # Extension développeur
├── user-developer-loader.php              # Extension dashboard + hooks
├── user-developer-renderer.php            # Interface développeur + formulaire soumission
├── user-developer-data-manager.php        # Statuts + candidatures
├── user-developer-ajax.php                # AJAX développeur + soumissions
├── user-developer-email-notifications.php # Emails candidature
├── submission/                             # Image cropping simple
│   ├── simple-image-cropper.php           # Upload + crop basique
│   ├── submission-database.php            # Table wp_sisme_game_submissions
│   └── assets/simple-cropper.js           # Cropper.js frontend
├── submission-game/                        # Soumissions avancées
│   ├── submission-game-loader.php         # Auto-loader
│   ├── submission-game-ajax.php           # AJAX soumissions
│   └── assets/submission-game.js          # Interface éditeur
└── assets/
    ├── user-developer.css                 # Styles développeur
    ├── user-developer.js                  # Interactions base
    └── user-developer-ajax.js             # AJAX client + mes jeux
```

---

## 📦 **Dashboard Base (`user-dashboard`)**

**Classe principale :** `Sisme_User_Dashboard_Loader`

### Shortcode
<details>
<summary><code>[sisme_user_dashboard]</code></summary>

```php
// Shortcode principal : [sisme_user_dashboard]
// Rendu par: Sisme_User_Dashboard_API::render_dashboard()
// Condition assets: has_shortcode($post->post_content, 'sisme_user_dashboard')
// Assets: user-dashboard.css/js + tokens.css
// Localisation: sismeUserDashboard {ajaxUrl, nonce: sisme_dashboard, currentUserId}
```
</details>

### Hooks d'extension
<details>
<summary><code>sisme_dashboard_accessible_sections</code></summary>

```php
// Hook pour ajouter sections accessibles
// @param array $sections - Sections de base
// @param int $user_id - ID utilisateur 
// @return array - Sections étendues
add_filter('sisme_dashboard_accessible_sections', $callback, 10, 2);
```
</details>

<details>
<summary><code>sisme_dashboard_navigation_items</code></summary>

```php
// Hook pour ajouter items navigation
// @param array $nav_items - Items existants
// @param int $user_id - ID utilisateur
// @return array - Items étendus avec {section, icon, text, badge, class}
add_filter('sisme_dashboard_navigation_items', $callback, 10, 2);
```
</details>

<details>
<summary><code>sisme_dashboard_render_section</code></summary>

```php
// Hook pour rendre sections personnalisées
// @param string $content - Contenu actuel
// @param string $section - Section demandée
// @param array $dashboard_data - Données dashboard
// @return string - HTML de la section
add_filter('sisme_dashboard_render_section', $callback, 10, 3);
```
</details>

---

## 🎮 **Extension Développeur (`user-developer`)**

**Classe principale :** `Sisme_User_Developer_Loader`

### Initialisation & Base de données
<details>
<summary><code>ensure_database_ready()</code></summary>

```php
// S'assure que table wp_sisme_game_submissions existe
// Auto-création si manquante via Sisme_Submission_Database::create_table()
// Appelé dans __construct() du loader
```
</details>

### Hooks Dashboard
<details>
<summary><code>add_developer_section($sections, $user_id)</code></summary>

```php
// Ajoute 'developer' et 'submit-game' (si approved) aux sections
// Hook: sisme_dashboard_accessible_sections
// Condition: is_user_logged_in()
```
</details>

<details>
<summary><code>add_developer_nav_item($nav_items, $user_id)</code></summary>

```php
// Ajoute navigation selon statut développeur
// Hook: sisme_dashboard_navigation_items
// Statuts retournés:
// - none: 📝 "Devenir Développeur"
// - pending: ⏳ "Candidature en cours" + badge "1"
// - approved: 🎮 "Mes Jeux"  
// - rejected: ❌ "Candidature rejetée"
```
</details>

<details>
<summary><code>render_developer_section($content, $section, $data)</code></summary>

```php
// Rendu sections 'developer' et 'submit-game'
// Hook: sisme_dashboard_render_section
// Délègue à: Sisme_User_Developer_Renderer
// Section 'submit-game': render_submit_game_section() avec crop test
```
</details>

### Assets
<details>
<summary><code>enqueue_developer_assets()</code></summary>

```php
// Assets: user-developer.css/js + user-developer-ajax.js
// Condition: should_load_assets() -> shortcode dashboard dans post_content
// Localisation: sismeAjax {ajaxurl, nonce: sisme_developer_nonce, currentUserId}
// Hook: wp_enqueue_scripts
```
</details>

<details>
<summary><code>enqueue_submission_assets()</code></summary>

```php
// Assets crop: cropper.min.css/js (CDN) + simple-cropper.js
// Dépendances: ['cropperjs', 'jquery']
// Localisation: sismeAjax {ajaxurl, nonce: sisme_developer_nonce}
// Hook: wp_enqueue_scripts
```
</details>

---

## 📊 **Data Manager Développeur**

**Classe :** `Sisme_User_Developer_Data_Manager`

### Statuts développeur
<details>
<summary><code>get_developer_status($user_id)</code></summary>

```php
// @return string 'none'|'pending'|'approved'|'rejected'
// Source: get_user_meta($user_id, 'sisme_developer_status', true)
// Défaut: 'none'
```
</details>

<details>
<summary><code>is_approved_developer($user_id)</code></summary>

```php
// @return bool - True si statut = 'approved' ET rôle 'sisme-dev'
// Utilisé pour: permissions soumission jeux
```
</details>

---

## 💾 **Base de Données Soumissions**

**Classe :** `Sisme_Submission_Database`
**Table :** `wp_sisme_game_submissions`

### Structure table
<details>
<summary><code>Schema wp_sisme_game_submissions</code></summary>

```sql
CREATE TABLE wp_sisme_game_submissions (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    user_id bigint(20) unsigned NOT NULL,
    game_data longtext NOT NULL,
    status enum('draft','pending','published','rejected','revision') DEFAULT 'draft',
    admin_user_id bigint(20) unsigned DEFAULT NULL,
    admin_notes text DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    submitted_at datetime DEFAULT NULL,
    published_at datetime DEFAULT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE
);
```
</details>

### CRUD Operations
<details>
<summary><code>create_submission($user_id, $game_data)</code></summary>

```php
// Créer nouvelle soumission
// @param int $user_id - ID développeur approuvé
// @param array $game_data - Données jeu (JSON stocké)
// @return int|WP_Error - ID soumission ou erreur
// Validation: limits + permissions
```
</details>

<details>
<summary><code>get_user_submissions($user_id, $status)</code></summary>

```php
// Récupérer soumissions utilisateur
// @param int $user_id
// @param string|null $status - Filtrer par statut (optionnel)
// @return array - Objets soumission avec game_data_decoded
```
</details>

<details>
<summary><code>update_submission($submission_id, $game_data, $user_id)</code></summary>

```php
// Mettre à jour soumission
// @param int $submission_id
// @param array $game_data - Nouvelles données
// @param int $user_id - Propriétaire (sécurité)
// @return bool|WP_Error
```
</details>

---

## 🔄 **AJAX Handlers**

### user-developer-ajax.php
<details>
<summary><code>wp_ajax_sisme_simple_crop_upload</code></summary>

```php
// Handler: sisme_handle_simple_crop_upload()
// Nonce: sisme_developer_nonce  
// Classe: Sisme_Simple_Image_Cropper::process_upload()
// Retour: {attachment_id, url, message}
```
</details>

<details>
<summary><code>wp_ajax_sisme_create_submission</code></summary>

```php
// Handler: sisme_ajax_create_submission()
// Crée nouvelle soumission draft
// Retour: {submission_id, message}
```
</details>

<details>
<summary><code>wp_ajax_sisme_delete_submission</code></summary>

```php
// Handler: sisme_ajax_delete_submission()
// Supprime soumission (draft/revision uniquement)
// Validation: ownership + status
```
</details>

<details>
<summary><code>wp_ajax_sisme_retry_submission</code></summary>

```php
// Handler: sisme_ajax_retry_submission()
// Copie soumission rejetée vers nouveau draft
// Ajoute metadata: retry_count, original_submission_id
```
</details>

### submission-game-ajax.php
<details>
<summary><code>wp_ajax_sisme_save_submission_game</code></summary>

```php
// Handler: sisme_ajax_save_submission_game()
// Sauvegarde draft avec validation
// Fonction: sisme_validate_submission_game_data()
```
</details>

<details>
<summary><code>wp_ajax_sisme_submit_submission_game</code></summary>

```php
// Handler: sisme_ajax_submit_submission_game()
// Draft -> pending pour validation admin
// Update status + submitted_at timestamp
```
</details>

---

## 🖼️ **Image Cropping**

**Classe :** `Sisme_Simple_Image_Cropper`

### Méthodes principales
<details>
<summary><code>process_upload($file)</code></summary>

```php
// Upload + validation basique
// @param array $file - $_FILES['image']
// @return int|WP_Error - attachment_id
// Limite: 5MB, types: JPG/PNG
```
</details>

### Frontend (simple-cropper.js)
<details>
<summary><code>SimpleCropper class</code></summary>

```javascript
// Initialisation: new SimpleCropper(containerId)
// Dépendances: Cropper.js (CDN)
// Ratio fixe: 920/430 (cover horizontale)
// Upload AJAX: action 'sisme_simple_crop_upload'
// Events: imageProcessed avec {url, attachmentId}
```
</details>

---

## 🎯 **Workflow Soumission Actuel**

### États soumission
```
draft -> pending -> published
  ↓        ↓         
revision <- rejected
```

### Permissions
- **Soumission** : Statut développeur = 'approved' + rôle 'sisme-dev'
- **Édition** : Propriétaire + statut 'draft'|'revision'
- **Suppression** : Propriétaire + statut 'draft'|'revision'

### Assets loading
- **Condition** : Page avec shortcode `[sisme_user_dashboard]`
- **Dashboard base** : Toujours chargé si shortcode présent
- **Développeur** : Chargé si user connecté sur page dashboard
- **Crop images** : Chargé avec assets développeur

---

## 🚀 **Hooks d'initialisation**

### Ordre de chargement
```php
// 1. Dashboard base
add_action('init', user-dashboard-loader singleton)

// 2. Extension développeur  
add_action('init', user-developer-loader singleton)
    -> ensure_database_ready()
    -> register_hooks() avec filters dashboard

// 3. AJAX développeur
add_action('wp_loaded', 'sisme_init_developer_ajax')

// 4. Module soumission-game (auto)
add_action('init', 'sisme_init_submission_game_loader')
    -> sisme_init_submission_game_ajax()
```

### Dépendances critiques
- Dashboard AVANT développeur (hooks)
- Database AVANT AJAX handlers
- Assets conditionnels sur shortcode détection