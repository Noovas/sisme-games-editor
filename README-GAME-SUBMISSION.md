# 🎮 Game Submission Module - API Reference

## 📋 **Vue d'Ensemble**

Module permettant aux développeurs approuvés de soumettre et gérer leurs jeux via user meta WordPress. Intégration au dashboard utilisateur avec workflow draft → pending → published.

---

## 🔧 **Constants**

```php
// User Meta & Champs
Sisme_Utils_Users::META_GAME_SUBMISSIONS = 'sisme_user_game_submissions'
Sisme_Utils_Users::GAME_FIELD_NAME = 'game_name'
Sisme_Utils_Users::GAME_FIELD_DESCRIPTION = 'game_description'
Sisme_Utils_Users::GAME_FIELD_RELEASE_DATE = 'game_release_date'
Sisme_Utils_Users::GAME_FIELD_TRAILER = 'game_trailer'
Sisme_Utils_Users::GAME_FIELD_STUDIO_NAME = 'game_studio_name'
Sisme_Utils_Users::GAME_FIELD_PUBLISHER_NAME = 'game_publisher_name'
Sisme_Utils_Users::GAME_FIELD_GENRES = 'game_genres'
Sisme_Utils_Users::GAME_FIELD_PLATFORMS = 'game_platforms'

// Statuts
Sisme_Utils_Users::GAME_STATUS_DRAFT = 'draft'
Sisme_Utils_Users::GAME_STATUS_PENDING = 'pending'
Sisme_Utils_Users::GAME_STATUS_PUBLISHED = 'published'
Sisme_Utils_Users::GAME_STATUS_REJECTED = 'rejected'
```

---

## 💾 **Data Manager - Sisme_Game_Submission_Data_Manager**

### **Getters**
```php
get_user_submissions($user_id, $status = null)      // Liste soumissions user
get_submission_by_id($user_id, $submission_id)      // Soumission spécifique
get_user_stats($user_id)                            // Stats développeur
```

### **CRUD**
```php
create_submission($user_id, $game_data = [])               // Créer brouillon
update_submission($user_id, $submission_id, $game_data)    // MAJ soumission
delete_submission($user_id, $submission_id)                // Supprimer (draft only)
change_submission_status($user_id, $submission_id, $status) // Changer statut
```

### **Business Logic**
```php
save_draft($user_id, $submission_id, $game_data)           // Auto-save brouillon
submit_for_review($user_id, $submission_id)                // Draft → Pending
create_retry_submission($user_id, $original_id)            // Rejected → New Draft
calculate_completion_percentage($game_data)                // % completion
```

### **Permissions**
```php
can_create_submission($user_id)                     // Peut créer?
can_edit_submission($user_id, $submission_id)       // Peut éditer?
can_delete_submission($user_id, $submission_id)     // Peut supprimer?
can_submit_for_review($user_id, $submission_id)     // Peut soumettre?
```

### **Admin**
```php
get_all_submissions_for_admin($status, $limit, $offset)    // Toutes soumissions
delete_submission_admin($submission_id, $user_id)          // Suppression admin
```

---

## 🌐 **AJAX Actions**

### **CRUD Submissions**
```javascript
// Créer nouvelle soumission
action: 'sisme_create_game_submission'
data: { security, game_name?, ... }
response: { submission_id, message, game_name }

// Sauvegarder brouillon (auto-save)
action: 'sisme_save_draft_submission'  
data: { security, submission_id, ...form_data }
response: { message, completion_percentage, last_save_time }

// Mettre à jour soumission
action: 'sisme_update_game_submission'
data: { security, submission_id, ...form_data }
response: { message, completion_percentage }

// Supprimer soumission (draft only)
action: 'sisme_delete_game_submission'
data: { security, submission_id }
response: { message }

// Lister soumissions user
action: 'sisme_get_game_submissions'
data: { security, status? }
response: { submissions[], stats }
```

### **Workflow**
```javascript
// Soumettre pour validation (draft → pending)
action: 'sisme_submit_game_for_review'
data: { security, submission_id }
response: { message, new_status }

// Créer retry depuis rejected
action: 'sisme_retry_rejected_submission'
data: { security, original_submission_id }
response: { new_submission_id, message, game_name }
```

### **Data Retrieval**
```javascript
// Détails soumission complète
action: 'sisme_get_submission_details'
data: { security, submission_id }
response: { submission: {id, status, game_data, metadata, admin_data} }

// Stats développeur
action: 'sisme_get_developer_game_stats'
data: { security }
response: { stats: {total_submissions, draft_count, pending_count, ...} }
```

### **Admin Actions**
```javascript
// Liste toutes soumissions (admin)
action: 'sisme_admin_get_submissions'
data: { security, status?, limit?, offset? }
response: { submissions[], total_count, has_more }

// Supprimer soumission (admin, tous statuts)
action: 'sisme_admin_delete_submission'
data: { security, submission_id, user_id }
response: { message }
```

---

## 🎨 **Renderer - Sisme_Game_Submission_Renderer**

```php
// Formulaire soumission (réutilise existant)
render_submission_form($user_id, $submission_id = null)

// Liste soumissions avec stats et actions
render_submissions_list($user_id)

// Item individuel avec actions contextuelles
render_submission_item($submission, $context = 'list')

// Widget stats développeur
render_stats_widget($stats)

// Helper statuts
get_status_label($status) // private
```

---

## 📱 **Frontend JavaScript**

### **Namespace Principal**
```javascript
window.SismeGameSubmission = {
    config: { ajaxUrl, nonce, autoSaveInterval: 30000 },
    currentSubmissionId: null,
    autoSaveTimer: null
}
```

### **Méthodes Principales**
```javascript
// Initialisation
init()
bindEvents()

// CRUD Interface
createNewSubmission()
loadSubmissionForEdit(submissionId)
saveDraft(submissionId, formData)
deleteSubmission(submissionId)

// Workflow UI
submitForReview(submissionId)
retryRejectedSubmission(submissionId)

// Auto-save
enableAutoSave(submissionId)
disableAutoSave()
performAutoSave()

// UI Updates
updateSubmissionsList()
updateStatsDisplay()
updateCompletionBar(percentage)
```

---

## 📊 **Structure User Meta**

```php
// Meta: 'sisme_user_game_submissions'
[
    'submissions' => [
        [
            'id' => 'sub_67f2a8b4c1d9f',
            'status' => 'draft|pending|published|rejected',
            'game_data' => [
                'game_name' => '...',
                'game_description' => '...',
                // ... tous les champs
            ],
            'metadata' => [
                'created_at' => '2025-01-10 09:15:00',
                'updated_at' => '2025-01-15 14:30:00',
                'submitted_at' => null,
                'completion_percentage' => 75,
                'retry_count' => 0,
                'original_submission_id' => null
            ],
            'admin_data' => [
                'admin_user_id' => null,
                'admin_notes' => null,
                'reviewed_at' => null
            ]
        ]
    ],
    'stats' => [
        'total_submissions' => 3,
        'draft_count' => 1,
        'pending_count' => 1,
        'published_count' => 1,
        'rejected_count' => 0,
        'last_updated' => '2025-01-15 14:25:00'
    ],
    'settings' => [
        'auto_save_interval' => 30,
        'auto_save_enabled' => true,
        'email_notifications' => true
    ]
]
```

---

## 🔄 **Workflow Complet**

```
1. Développeur approuvé → Section "Mes Jeux"
2. Créer nouveau jeu → Brouillon (draft)
3. Remplir formulaire + auto-save toutes les 30s
4. Soumettre pour validation → Pending
5. Admin review → Published ou Rejected
6. Si Rejected → Retry possible vers nouveau Draft
```

---

## 🔒 **Sécurité & Permissions**

- **Nonce**: `sisme_developer_nonce` pour tous les AJAX
- **Permissions**: Développeur approuvé (`sisme-dev` role)
- **Validation**: Côté serveur + client
- **Restrictions**: 
  - Édition: statuts `draft` et `revision` uniquement
  - Suppression: statuts `draft` uniquement
  - Admin bypass: toutes restrictions