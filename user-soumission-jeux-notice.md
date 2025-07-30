# 🎯 Plan d'Action - Nouveau Système Game Submission User Meta

## 📋 **Architecture Proposée**

### **🏗️ Structure des Fichiers (Nouveaux)**

```
includes/user/user-developer/
├── user-developer-loader.php              ✅ Existant (à étendre)
├── user-developer-data-manager.php        ✅ Existant (à étendre)  
├── user-developer-renderer.php            ✅ Existant (à nettoyer/étendre)
├── user-developer-ajax.php                ✅ Existant (à nettoyer/étendre)
├── game-submission/                        🆕 NOUVEAU DOSSIER
│   ├── game-submission-data-manager.php   🆕 CRUD user meta jeux
│   ├── game-submission-ajax.php           🆕 Handlers AJAX jeux
│   ├── game-submission-renderer.php       🆕 Rendu formulaires/listes
│   ├── game-submission-validator.php      🆕 Validation métier
│   ├── game-submission-admin.php          🆕 Interface admin basique
│   └── assets/
│       ├── game-submission.css            🆕 Styles formulaires
│       ├── game-submission.js             🆕 Interface utilisateur
│       ├── game-submission-validator.js   🆕 Validation frontend
│       └── game-submission-admin.js       🆕 Interface admin
└── assets/                                 ✅ Existant
    ├── user-developer.css                 ✅ Existant
    ├── user-developer.js                  ✅ Existant  
    └── user-developer-ajax.js             ✅ Existant (à nettoyer)
```

---

## 🎯 **Phase 1 : Fondations User Meta**

### **1.1 Étendre les Constantes (`utils-users.php`)**
```php
// Nouvelles constantes pour jeux
const META_GAME_SUBMISSIONS = 'sisme_user_game_submissions';

// Champs soumission jeu
const GAME_FIELD_NAME = 'game_name';
const GAME_FIELD_DESCRIPTION = 'game_description'; 
const GAME_FIELD_RELEASE_DATE = 'game_release_date';
const GAME_FIELD_TRAILER = 'game_trailer';
const GAME_FIELD_STUDIO_NAME = 'game_studio_name';
const GAME_FIELD_STUDIO_URL = 'game_studio_url';
const GAME_FIELD_PUBLISHER_NAME = 'game_publisher_name';
const GAME_FIELD_PUBLISHER_URL = 'game_publisher_url';
const GAME_FIELD_GENRES = 'game_genres';
const GAME_FIELD_PLATFORMS = 'game_platforms';
const GAME_FIELD_COVER_HORIZONTAL = 'cover_horizontal';
const GAME_FIELD_COVER_VERTICAL = 'cover_vertical';
const GAME_FIELD_SCREENSHOTS = 'screenshots';

// Statuts soumission
const GAME_STATUS_DRAFT = 'draft';
const GAME_STATUS_PENDING = 'pending';
const GAME_STATUS_PUBLISHED = 'published';
const GAME_STATUS_REJECTED = 'rejected';
const GAME_STATUS_REVISION = 'revision';
```

### **1.2 Structure User Meta Proposée**
```php
// Clé: 'sisme_user_game_submissions'
// Valeur: JSON
{
    "submissions": [
        {
            "id": "unique_uuid_1",
            "status": "draft|pending|published|rejected|revision",
            "game_data": {
                "game_name": "Mon Super Jeu",
                "game_description": "Description...",
                "game_release_date": "2025-03-15",
                "game_trailer": "https://youtube.com/...",
                "game_studio_name": "Mon Studio",
                "game_studio_url": "https://monstudio.com",
                "game_publisher_name": "Mon Éditeur", 
                "game_publisher_url": "https://monediteur.com",
                "game_genres": ["action", "aventure"],
                "game_platforms": ["pc", "steam"],
                "covers": {
                    "horizontal": {
                        "attachment_id": 123,
                        "url": "https://..."
                    },
                    "vertical": {
                        "attachment_id": 124, 
                        "url": "https://..."
                    }
                },
                "screenshots": [
                    {
                        "attachment_id": 125,
                        "url": "https://...",
                        "caption": "Screenshot 1"
                    }
                ]
            },
            "metadata": {
                "created_at": "2025-01-15 10:30:00",
                "updated_at": "2025-01-15 11:45:00", 
                "submitted_at": null,
                "published_at": null,
                "completion_percentage": 75,
                "retry_count": 0,
                "original_submission_id": null
            },
            "admin_data": {
                "admin_user_id": null,
                "admin_notes": "",
                "reviewed_at": null
            }
        }
    ],
    "stats": {
        "total_submissions": 1,
        "published_count": 0,
        "draft_count": 1,
        "pending_count": 0,
        "rejected_count": 0
    }
}
```

---

## 🎯 **Phase 2 : Data Manager Core**

### **2.1 Créer `game-submission-data-manager.php`**

#### **Fonctions CRUD Principales**
```php
class Sisme_Game_Submission_Data_Manager {
    
    // === GETTERS ===
    public static function get_user_submissions($user_id, $status = null)
    public static function get_submission_by_id($user_id, $submission_id)
    public static function get_user_stats($user_id)
    
    // === CRUD ===
    public static function create_submission($user_id, $game_data = [])
    public static function update_submission($user_id, $submission_id, $game_data)
    public static function delete_submission($user_id, $submission_id)
    public static function change_submission_status($user_id, $submission_id, $new_status)
    
    // === BUSINESS LOGIC ===
    public static function save_draft($user_id, $submission_id, $game_data)           // 🆕 Sauvegarder brouillon
    public static function submit_for_review($user_id, $submission_id)               // 🆕 Draft → Pending
    public static function create_retry_submission($user_id, $original_id)
    public static function calculate_completion_percentage($game_data)
    
    // === VALIDATION PERMISSIONS ===
    public static function can_create_submission($user_id)
    public static function can_edit_submission($user_id, $submission_id)             // 🆕 Édition autorisée ?
    public static function can_delete_submission($user_id, $submission_id)           // 🆕 Suppression autorisée ?
    public static function can_submit_for_review($user_id, $submission_id)           // 🆕 Soumission autorisée ?
    
    // === ADMIN QUERIES ===
    public static function get_all_submissions_for_admin($status = null, $limit = 50) // 🆕 Liste admin
    public static function delete_submission_admin($submission_id)                    // 🆕 Suppression admin
}
```

#### **Helpers Utilitaires**
```php
// Génération UUID unique pour les soumissions
private static function generate_submission_id()

// Validation permissions développeur
private static function validate_developer_permissions($user_id)

// Nettoyage données avant sauvegarde
private static function sanitize_game_data($game_data)

// Merge intelligent des données existantes
private static function merge_game_data($existing, $new_data)
```

### **2.2 Étendre `user-developer-data-manager.php`**
```php
// Ajouter à la classe existante :

/**
 * Récupérer les jeux soumis par le développeur (NOUVEAU)
 */
public static function get_submitted_games($user_id) {
    if (!class_exists('Sisme_Game_Submission_Data_Manager')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-data-manager.php';
    }
    
    return Sisme_Game_Submission_Data_Manager::get_user_submissions($user_id);
}

/**
 * Récupérer les statistiques développeur (MISE À JOUR)
 */
public static function get_developer_stats($user_id) {
    $game_stats = Sisme_Game_Submission_Data_Manager::get_user_stats($user_id);
    
    return [
        'total_games' => $game_stats['total_submissions'],
        'published_games' => $game_stats['published_count'],
        'pending_games' => $game_stats['pending_count'],
        'draft_games' => $game_stats['draft_count'],  
        'rejected_games' => $game_stats['rejected_count'],
        'total_views' => 0, // À implémenter
        'join_date' => self::get_developer_join_date($user_id)
    ];
}
```

---

## 🎯 **Phase 3 : Handlers AJAX**

### **3.1 Créer `game-submission-ajax.php`**

#### **Actions AJAX Principales**
```php
// === CRUD SOUMISSIONS ===
sisme_ajax_create_game_submission()     // Créer nouveau brouillon
sisme_ajax_save_draft_submission()      // 🆕 Sauvegarder brouillon (auto-save)
sisme_ajax_update_game_submission()     // 🆕 Modifier soumission existante
sisme_ajax_delete_game_submission()     // 🆕 Supprimer brouillon uniquement
sisme_ajax_get_game_submissions()       // Lister soumissions user

// === WORKFLOW ===
sisme_ajax_submit_game_for_review()     // 🆕 Draft → Pending (soumission finale)
sisme_ajax_retry_rejected_submission()  // Rejected → New Draft

// === DETAILS ===
sisme_ajax_get_submission_details()     // Charger données soumission
sisme_ajax_get_developer_game_stats()   // Statistiques développeur

// === VALIDATION ===
sisme_ajax_validate_game_data()         // Validation temps réel
sisme_ajax_check_submission_limits()    // Vérifier limites user

// === ADMIN (BASIQUE) ===
sisme_ajax_admin_get_submissions()      // 🆕 Liste soumissions admin
sisme_ajax_admin_delete_submission()    // 🆕 Suppression admin
```

#### **Structure des Handlers**
```php
function sisme_ajax_create_game_submission() {
    // 1. Sécurité (nonce + permissions)  
    // 2. Validation données
    // 3. Appel data manager
    // 4. Réponse JSON
}
```

### **3.2 Nettoyer `user-developer-ajax.php`**
```php
// SUPPRIMER tous les anciens handlers table :
// ❌ sisme_ajax_save_submission_game()
// ❌ sisme_ajax_submit_submission_game()  
// ❌ sisme_ajax_create_submission()
// ❌ sisme_ajax_delete_submission()
// ❌ etc.

// GARDER seulement :
// ✅ sisme_ajax_developer_submit()          // Candidature
// ✅ sisme_ajax_developer_reset_rejection() // Reset candidature
// ✅ sisme_handle_simple_crop_upload()      // Upload images
// ✅ sisme_ajax_not_logged_in()            // Handler sécurité
```

---

## 🎯 **Phase 4 : Interface Utilisateur**

### **4.1 Créer `game-submission-renderer.php`**

#### **Rendu Formulaires**
```php
class Sisme_Game_Submission_Renderer {
    
    // === SECTIONS FORMULAIRE ===
    public static function render_submission_form($user_id, $submission_id = null)
    public static function render_basic_info_section($game_data = [])
    public static function render_media_section($game_data = [])
    public static function render_categories_section($game_data = [])
    public static function render_external_links_section($game_data = [])
    
    // === LISTES ===
    public static function render_submissions_list($user_id)
    public static function render_submission_item($submission, $context = 'list')
    
    // === WIDGETS ===
    public static function render_stats_widget($stats)
    public static function render_actions_widget($user_id)
}
```

### **4.2 Mettre à Jour `user-developer-renderer.php`**
```php
// Mettre à jour render_my_games_section() :

private static function render_my_games_section($user_id) {
    if (!class_exists('Sisme_Game_Submission_Renderer')) {
        require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/game-submission/game-submission-renderer.php';
    }
    
    return Sisme_Game_Submission_Renderer::render_submissions_list($user_id);
}

// Mettre à jour render_submit_game_section() :

public static function render_submit_game_section($user_id, $developer_status, $dashboard_data) {
    if ($developer_status !== 'approved') {
        return '<p>Vous devez être un développeur approuvé pour soumettre des jeux.</p>';
    }
    
    return Sisme_Game_Submission_Renderer::render_submission_form($user_id);
}
```

---

## 🎯 **Phase 5 : Frontend JavaScript**

### **5.1 Créer `game-submission.js`**

#### **Gestion Formulaire**
```javascript
class GameSubmissionManager {
    
    // === INITIALISATION ===
    init()
    bindEvents()
    
    // === CRUD ===
    createSubmission()                  // Créer nouveau brouillon
    saveDraft(submissionId)            // 🆕 Sauvegarde automatique brouillon
    editSubmission(submissionId)       // 🆕 Ouvrir soumission en édition
    updateSubmission(submissionId)     // 🆕 Sauvegarder modifications
    deleteSubmission(submissionId)     // 🆕 Supprimer (brouillons uniquement)
    
    // === WORKFLOW ===
    submitForReview(submissionId)       // 🆕 Draft → Pending (soumission finale)
    retryRejectedSubmission(submissionId)
    
    // === UI ===
    showSubmissionForm(submissionId = null)  // Nouveau ou édition
    showSubmissionsList()
    updateStatsDisplay()
    
    // === AUTO-SAVE ===
    enableAutoSave()                    // 🆕 Sauvegarde automatique toutes les 30s
    disableAutoSave()                   // 🆕 Désactiver auto-save
    
    // === VALIDATION ===
    validateForm()
    validateSection(sectionName)
    calculateCompletionPercentage()
    canDeleteSubmission(submission)     // 🆕 Vérifier si suppression autorisée
}
```

### **5.2 Créer `game-submission-validator.js`**
```javascript
class GameSubmissionValidator {
    
    // === RÈGLES VALIDATION ===
    rules: {
        game_name: { required: true, minLength: 3, maxLength: 100 },
        game_description: { required: true, minLength: 50, maxLength: 180 },
        game_release_date: { required: true, isDate: true },
        game_trailer: { required: true, isYouTubeUrl: true },
        game_studio_name: { required: true, minLength: 2, maxLength: 50 },
        game_studio_url: { required: false, isUrl: true },
        game_publisher_name: { required: true, minLength: 2, maxLength: 50 },
        game_publisher_url: { required: false, isUrl: true }
    }
    
    // === MÉTHODES ===
    validateField(fieldName, value)
    validateForm()
    isFormValid()
    getValidationErrors()
    showFieldError(fieldName, message)
    clearFieldError(fieldName)
}
```

### **5.3 Nettoyer `user-developer-ajax.js`**
```javascript
// SUPPRIMER toutes les fonctions liées aux soumissions :
// ❌ startNewSubmission()
// ❌ continueSubmission()
// ❌ loadSubmissionData()
// ❌ handleSaveDraft()
// ❌ handleSubmitGame()
// ❌ etc.

// GARDER seulement :
// ✅ handleFormSubmit()        // Candidature développeur
// ✅ handleRetryApplication()  // Reset candidature  
// ✅ showFeedback()           // Feedback utilisateur
// ✅ showLoader()             // Loader modal
```

---

## 🎯 **Phase 6 : Intégration et Chargement**

### **6.1 Étendre `user-developer-loader.php`**
```php
// Ajouter chargement modules soumission :

private function load_developer_modules() {
    // ... modules existants ...
    
    // NOUVEAUX modules soumission
    $submission_modules = [
        'game-submission/game-submission-data-manager.php',
        'game-submission/game-submission-ajax.php', 
        'game-submission/game-submission-renderer.php',
        'game-submission/game-submission-validator.php',
        'game-submission/game-submission-admin.php'
    ];
    
    foreach ($submission_modules as $module) {
        $this->load_submission_module($module);
    }
}

// Ajouter chargement assets soumission :
public function enqueue_submission_assets() {
    // CSS soumission
    wp_enqueue_style(
        'sisme-game-submission',
        SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-developer/game-submission/assets/game-submission.css'
    );
    
    // JS soumission
    wp_enqueue_script(
        'sisme-game-submission',
        SISME_GAMES_EDITOR_PLUGIN_URL . 'includes/user/user-developer/game-submission/assets/game-submission.js',
        ['jquery', 'sisme-user-developer-ajax']
    );
}
```

---

## 🎯 **Phase 7 : Tests et Validation**

### **7.1 Tests Unitaires Data Manager**
- CRUD opérations user meta
- Validation permissions
- Calculs statistiques
- Gestion des statuts

### **7.2 Tests Frontend**
- Formulaire soumission
- Validation temps réel  
- AJAX handlers
- Navigation sections

### **7.3 Tests d'Intégration**
- Dashboard développeur
- Système de crop images
- Workflow complet soumission
- Gestion des erreurs

---

## 🎯 **Phase 8 : Interface Admin (Basique)**

### **8.1 Créer `game-submission-admin.php`**

#### **Page Admin Simple**
```php
class Sisme_Game_Submission_Admin {
    
    // === INTERFACE ADMIN ===
    public static function render_admin_page()
    public static function render_submissions_table($submissions)
    public static function render_submission_details_modal($submission)
    
    // === ACTIONS ADMIN ===  
    public static function handle_admin_delete_submission()
    
    // === AJAX ADMIN ===
    public static function ajax_get_submissions_list()
    public static function ajax_get_submission_details() 
    public static function ajax_delete_submission()
}
```

#### **Structure Table Admin**
```html
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th>Jeu</th>
            <th>Studio</th>
            <th>Développeur</th>
            <th>Statut</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><strong>Nom du Jeu</strong></td>
            <td>Nom Studio</td>
            <td>Nom Utilisateur</td>
            <td><span class="status-badge pending">En attente</span></td>
            <td>2025-01-15</td>
            <td>
                <button class="button view-details" data-id="123">👁️ Détails</button>
                <button class="button delete-submission" data-id="123">🗑️ Supprimer</button>
            </td>
        </tr>
    </tbody>
</table>
```

#### **Modal Détails Submission**
```html
<div id="submission-details-modal" class="sisme-admin-modal">
    <div class="modal-content">
        <h3>📋 Détails de la Soumission</h3>
        
        <!-- Informations principales -->
        <div class="submission-info">
            <p><strong>Jeu :</strong> <span id="detail-game-name"></span></p>
            <p><strong>Studio :</strong> <span id="detail-studio-name"></span></p>
            <p><strong>Développeur :</strong> <span id="detail-user-name"></span></p>
            <p><strong>Statut :</strong> <span id="detail-status"></span></p>
        </div>
        
        <!-- Accordéon avec toutes les données -->
        <div class="submission-details-accordion">
            <button class="accordion-toggle">📋 Informations détaillées</button>
            <div class="accordion-content">
                <div id="full-submission-data"></div>
            </div>
        </div>
        
        <div class="modal-actions">
            <button class="button-primary close-modal">Fermer</button>
        </div>
    </div>
</div>
```

### **8.2 JavaScript Admin**
```javascript
// game-submission-admin.js
class GameSubmissionAdmin {
    
    init() {
        this.bindEvents();
        this.loadSubmissionsList();
    }
    
    // Charger liste soumissions
    loadSubmissionsList() {
        // AJAX vers sisme_ajax_admin_get_submissions
    }
    
    // Afficher détails en modal
    showSubmissionDetails(submissionId) {
        // AJAX vers sisme_ajax_admin_get_submission_details
        // Remplir modal + afficher
    }
    
    // Supprimer soumission
    deleteSubmission(submissionId) {
        if (confirm('Supprimer cette soumission ?')) {
            // AJAX vers sisme_ajax_admin_delete_submission
        }
    }
}
```

---

## 🎯 **Phase 9 : Règles de Gestion Précises**

### **9.1 Permissions Utilisateur**

#### **Création de Soumission**
```php
public static function can_create_submission($user_id) {
    // ✅ Doit être développeur approuvé
    if (!Sisme_User_Developer_Data_Manager::is_approved_developer($user_id)) {
        return false;
    }
    
    // ✅ Limite max brouillons simultanés (ex: 3)
    $drafts = self::get_user_submissions($user_id, 'draft');
    if (count($drafts) >= 3) {
        return false;
    }
    
    return true;
}
```

#### **Édition de Soumission**
```php
public static function can_edit_submission($user_id, $submission_id) {
    $submission = self::get_submission_by_id($user_id, $submission_id);
    
    // ✅ Doit être propriétaire
    if (!$submission || $submission['user_id'] !== $user_id) {
        return false;
    }
    
    // ✅ Statuts autorisés : draft, revision
    $editable_statuses = ['draft', 'revision'];
    return in_array($submission['status'], $editable_statuses);
}
```

#### **Suppression de Soumission**
```php
public static function can_delete_submission($user_id, $submission_id) {
    $submission = self::get_submission_by_id($user_id, $submission_id);
    
    // ✅ Doit être propriétaire
    if (!$submission || $submission['user_id'] !== $user_id) {
        return false;
    }
    
    // ✅ UNIQUEMENT les brouillons peuvent être supprimés
    return $submission['status'] === 'draft';
}
```

#### **Soumission pour Review**
```php
public static function can_submit_for_review($user_id, $submission_id) {
    $submission = self::get_submission_by_id($user_id, $submission_id);
    
    // ✅ Doit être propriétaire + statut draft/revision
    if (!$submission || $submission['user_id'] !== $user_id) {
        return false;
    }
    
    $submittable_statuses = ['draft', 'revision'];
    if (!in_array($submission['status'], $submittable_statuses)) {
        return false;
    }
    
    // ✅ Formulaire doit être complet (ex: 100%)
    $completion = self::calculate_completion_percentage($submission['game_data']);
    return $completion >= 100;
}
```

### **9.2 Workflow États Précis**

```
┌─────────┐    save_draft()    ┌─────────┐
│  DRAFT  │ ←───────────────── │   NEW   │
└─────────┘                    └─────────┘
     │                              
     │ submit_for_review()           
     ├─────→ (100% requis)          
     ▼                              
┌─────────┐                         
│ PENDING │                         
└─────────┘                         
     │                              
     ├─── ADMIN APPROVE ──→ ┌───────────┐
     │                      │ PUBLISHED │ (❌ Plus modifiable)
     │                      └───────────┘
     │                              
     └─── ADMIN REJECT ───→ ┌──────────┐
                            │ REJECTED │ 
                            └──────────┘
                                 │
                                 │ retry_submission()
                                 ▼
                            ┌──────────┐
                            │ REVISION │ (✅ Modifiable)
                            └──────────┘
```

### **9.3 Auto-Save Brouillons**
```javascript
// Auto-save toutes les 30 secondes pour les brouillons
setInterval(() => {
    if (this.isFormDirty && this.currentStatus === 'draft') {
        this.saveDraft();
    }
}, 30000);
```

---

## 🚀 **Avantages de cette Approche**

### **✅ Architecture Propre**
- Séparation claire des responsabilités
- Modules indépendants et testables
- Réutilisation du système candidature éprouvé

### **✅ Performance**
- User meta indexé WordPress natif
- Pas de requêtes SQL complexes
- Cache WordPress automatique

### **✅ Maintenabilité**
- Structure JSON évolutive
- Ajout facile de nouveaux champs
- Migration simple si nécessaire

### **✅ Sécurité**
- Nonces WordPress sur toutes les actions
- Validation multi-niveaux
- Permissions strictes développeur

### **✅ Cohérence**
- Même approche que candidatures développeur
- Standards WordPress respectés
- Interface utilisateur unifiée

### **✅ Gestion Métier Complète**
- Auto-save intelligent
- Permissions strictes par statut
- Workflow d'états précis
- Interface admin pratique

---

## 📋 **Ordre d'Implémentation Recommandé**

1. **Phase 1** : Constantes et structure user meta
2. **Phase 2** : Data Manager core (CRUD + permissions)
3. **Phase 3** : Handlers AJAX basiques
4. **Phase 4** : Interface de base (formulaire + liste)
5. **Phase 5** : JavaScript et validation + auto-save
6. **Phase 6** : Intégration complète
7. **Phase 7** : Tests et optimisations
8. **Phase 8** : Interface admin basique
9. **Phase 9** : Règles métier finales

**Estimation :** 2-3 jours de développement pour un système complet et fonctionnel avec toutes les fonctionnalités demandées.