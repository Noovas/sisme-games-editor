# 📋 Documentation - Module Soumission de Jeux
## Version 1.0 - Guide de Développement

---

## 🎯 **Vue d'ensemble**

Module permettant aux développeurs approuvés de soumettre leurs jeux via une interface frontend multi-étapes avec validation temps réel, crop d'images obligatoire et workflow d'approbation admin.

### **Objectifs**
- ✅ Réutiliser 100% la structure de données existante (`Sisme_Game_Form_Module`)
- ✅ UX progressive avec sauvegarde automatique à chaque étape
- ✅ Crop d'images restrictif pour garantir la qualité
- ✅ Workflow d'approbation admin complet
- ✅ Extensibilité future (liens avec posts news/patch/test)

---

## 🏗️ **PHASE 1 : Structure Base de Données**

### **1.1 Table principale**

```sql
CREATE TABLE wp_sisme_game_submissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- 🔗 Relations
    user_id BIGINT UNSIGNED NOT NULL,               -- Développeur
    published_tag_id BIGINT UNSIGNED NULL,          -- Tag créé après publication
    
    -- 📝 Données
    game_data LONGTEXT NOT NULL,                    -- JSON structuré
    
    -- 📊 Workflow  
    status ENUM('draft','pending','published','rejected','revision') DEFAULT 'draft',
    submission_version INT DEFAULT 1,
    
    -- 👨‍💼 Admin
    admin_user_id BIGINT UNSIGNED NULL,
    admin_notes TEXT NULL,
    admin_action_date DATETIME NULL,
    
    -- 📅 Timestamps
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    submitted_at DATETIME NULL,
    published_at DATETIME NULL,
    
    -- 🔍 Index
    INDEX idx_user_status (user_id, status),
    INDEX idx_status_submitted (status, submitted_at),
    INDEX idx_published_tag (published_tag_id),
    
    -- 🔗 Contraintes
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    FOREIGN KEY (admin_user_id) REFERENCES wp_users(ID) ON DELETE SET NULL
);
```

### **1.2 Structure JSON `game_data`**

```json
{
  "game_name": "Mon Super Jeu",
  "description": "Description complète du jeu...",
  "genres": [12, 34, 56],
  "platforms": [
    {"group": "pc", "label": "PC"},
    {"group": "console", "label": "PlayStation 5"}
  ],
  "modes": ["solo", "multijoueur"],
  "developers": [42],
  "publishers": [15],
  "release_date": "2024-03-15",
  "covers": {
    "horizontal": 123,
    "vertical": 124
  },
  "screenshots": "128,129,130",
  "trailer_link": "https://youtube.com/watch?v=...",
  "external_links": [
    {"platform": "Steam", "url": "https://store.steampowered.com/..."}
  ],
  "metadata": {
    "completion_percentage": 87,
    "last_step_completed": "images",
    "validation_errors": []
  }
}
```

---

## 🎨 **PHASE 2 : Formats d'Images**

### **2.1 Spécifications précises**

```php
const SUBMISSION_IMAGE_FORMATS = [
    'horizontal' => [
        'width' => 920,
        'height' => 430, 
        'ratio' => '920:430', // ≈ 2.14:1
        'max_size' => '2MB',
        'label' => 'Cover Horizontale',
        'description' => 'Bannière principale du jeu'
    ],
    'vertical' => [
        'width' => 600,
        'height' => 900,
        'ratio' => '2:3',
        'max_size' => '1MB', 
        'label' => 'Cover Verticale',
        'description' => 'Pochette style affiche'
    ]
];
```

### **2.2 Screenshots**
- **Format** : 1920x1080 (16:9) 
- **Minimum** : 3 images
- **Maximum** : 8 images
- **Taille max** : 2MB par image

---

## 📁 **PHASE 3 : Architecture Modules**

### **3.1 Structure des fichiers**

```
includes/user/user-developer/submission/
├── submission-loader.php              # Point d'entrée + hooks
├── submission-database.php            # CRUD submissions
├── submission-workflow.php            # États & transitions  
├── submission-renderer.php            # Interface multi-étapes
├── submission-ajax.php               # Handlers AJAX
├── submission-image-cropper.php      # Crop + upload images
├── submission-validation.php         # Validation données
└── assets/
    ├── submission-stepper.css        # Styles progression
    ├── submission-stepper.js         # Navigation étapes
    ├── submission-cropper.css        # Styles crop tool
    └── submission-cropper.js         # Crop + preview
```

### **3.2 Classes principales**

```php
// Loader principal
class Sisme_Submission_Loader {
    public static function get_instance()
    public function init()
    private function register_hooks()
    public function enqueue_assets()
}

// Gestion base de données  
class Sisme_Submission_Database {
    public static function create_submission($user_id, $game_data = [])
    public static function update_submission($submission_id, $game_data)
    public static function get_user_submissions($user_id, $status = null)
    public static function delete_submission($submission_id, $user_id)
}

// Workflow et états
class Sisme_Submission_Workflow {
    public static function transition_to_pending($submission_id)
    public static function approve_submission($submission_id, $admin_notes)
    public static function reject_submission($submission_id, $admin_notes)
    public static function reset_to_draft($submission_id)
}
```

---

## 🎯 **PHASE 4 : UX Multi-Étapes**

### **4.1 Progression (4 étapes)**

```
1. INFORMATIONS DE BASE     [25%]
   ├─ Nom du jeu ⭐ (obligatoire)
   ├─ Description ⭐ (obligatoire)  
   └─ Genres ⭐ (obligatoire)

2. MÉDIAS VISUELS          [50%]
   ├─ Cover horizontale ⭐ (obligatoire)
   ├─ Cover verticale ⭐ (obligatoire)
   └─ Screenshots ⭐ (min 3)

3. DÉTAILS TECHNIQUES      [75%]
   ├─ Plateformes ⭐ (obligatoire)
   ├─ Modes de jeu
   ├─ Date de sortie
   ├─ Développeurs ⭐ (obligatoire)
   └─ Publishers

4. FINALISATION           [100%]
   ├─ Trailer (optionnel)
   ├─ Liens de vente
   ├─ Prévisualisation complète
   └─ Soumission finale ⭐
```

### **4.2 Validation progressive**

```php
// Validation par étape
class Sisme_Submission_Validation {
    public static function validate_step_1($data) // Base info
    public static function validate_step_2($data) // Images  
    public static function validate_step_3($data) // Technical
    public static function validate_step_4($data) // Final
    public static function validate_complete($data) // Global
}
```

### **4.3 Sauvegarde automatique**

- **Trigger** : À chaque changement de champ (debounce 2s)
- **Statut** : Reste en `draft` jusqu'à soumission finale
- **Recovery** : Reprise automatique à la dernière étape complétée
- **Indicateur** : "💾 Sauvegardé il y a 3s" 

---

## 🛠️ **PHASE 5 : Integration avec l'Existant**

### **5.1 Réutilisation `Sisme_Game_Form_Module`**

```php
// Extension pour nouveaux components
$new_components = [
    'cover_horizontal' => [
        'label' => 'Cover Horizontale',
        'type' => 'image_crop',
        'crop_ratio' => '920:430',
        'required' => true
    ],
    'cover_vertical' => [
        'label' => 'Cover Verticale', 
        'type' => 'image_crop',
        'crop_ratio' => '2:3',
        'required' => true
    ]
];
```

### **5.2 Extension Dashboard**

Ajout de l'onglet "Mes Jeux" dans `user-developer-renderer.php` :

```php
public function render_approved_status($user_id) {
    // Interface existante + nouveau contenu
    echo $this->render_my_games_section($user_id);
}

private function render_my_games_section($user_id) {
    $submissions = Sisme_Submission_Database::get_user_submissions($user_id);
    // Affichage liste + bouton "Soumettre un nouveau jeu"
}
```

---

## 🔧 **PHASE 6 : Crop d'Images**

### **6.1 Bibliothèques**

- **Frontend** : Cropper.js v1.5.13
- **Backend** : WP_Image_Editor (natif WordPress)
- **Fallback** : GD Library / ImageMagick

### **6.2 Workflow crop**

```javascript
// submission-cropper.js
class SismeImageCropper {
    constructor(element, options) {
        this.ratio = options.ratio;
        this.maxWidth = options.maxWidth;
        this.maxHeight = options.maxHeight;
    }
    
    initCropper() {
        // Initialisation Cropper.js
    }
    
    uploadAndCrop() {
        // Upload → Crop → Preview → Save
    }
    
    validateFormat() {
        // Validation taille/format
    }
}
```

### **6.3 Validation server-side**

```php
// submission-image-cropper.php
class Sisme_Submission_Image_Cropper {
    public static function process_upload($file, $format_type)
    public static function validate_dimensions($attachment_id, $format)
    public static function resize_if_needed($attachment_id, $target_width, $target_height)
    private static function get_image_editor($attachment_id)
}
```

---

## 👨‍💼 **PHASE 7 : Interface Admin**

### **7.1 Page "Soumissions de Jeux"**

```
admin.php?page=sisme-game-submissions

┌─ NOUVELLES SOUMISSIONS ─────────────────┐
│ 🔴 Game Awesome par StudioDev (3j)       │
│    Genre: Action, RPG | PC, PS5          │
│    [👁️ Prévisualiser] [✅ Approuver] [❌ Rejeter] │
└─────────────────────────────────────────┘

┌─ FILTRES ───────────────────────────────┐
│ Status: [Pending ▼] Genre: [Tous ▼]     │
│ Développeur: [Rechercher...]             │
└─────────────────────────────────────────┘
```

### **7.2 Actions admin**

```php
// submission-admin.php
class Sisme_Submission_Admin {
    public function render_submissions_page()
    public function handle_approve_submission()
    public function handle_reject_submission() 
    public function render_submission_preview($submission_id)
    public function render_submission_modal($submission_id)
}
```

### **7.3 Workflow approbation**

**APPROUVER** :
1. Créer le tag de jeu (`wp_insert_term`)
2. Mapper toutes les données (`update_term_meta`)
3. Marquer comme `published`
4. Envoyer email de confirmation
5. Initialiser données vedettes si besoin

**REJETER** :
1. Marquer comme `rejected`
2. Sauvegarder notes admin
3. Envoyer email avec explications
4. Possibilité de remise en `draft`

---

## 📧 **PHASE 8 : Notifications Email**

### **8.1 Templates email**

Réutiliser `user-developer-email-notifications.php` :

```php
// Nouveaux templates
public static function send_submission_received($user_id, $game_name)
public static function send_submission_approved($user_id, $game_name, $game_url)  
public static function send_submission_rejected($user_id, $game_name, $admin_notes)
```

### **8.2 Contenu des emails**

**SOUMISSION REÇUE** :
```
Sujet: 🎮 Votre jeu "{game_name}" a été soumis
Corps: Confirmation + délai d'examen + lien suivi
```

**APPROBATION** :
```
Sujet: ✅ Votre jeu "{game_name}" est maintenant publié !
Corps: Félicitations + lien fiche + conseils promotion
```

**REJET** :
```
Sujet: ❌ Votre jeu "{game_name}" nécessite des modifications
Corps: Explications + notes admin + bouton "Modifier"
```

---

## 🔒 **PHASE 9 : Sécurité & Permissions**

### **9.1 Contrôles d'accès**

```php
// Vérifications obligatoires
- Utilisateur connecté ✓
- Statut développeur = 'approved' ✓  
- Limite soumissions simultanées (max 3 en draft) ✓
- Nonce AJAX pour toutes les actions ✓
- Sanitisation complète des données ✓
```

### **9.2 Rate limiting**

```php
// Limitations  
- 1 soumission par jour max
- 3 brouillons simultanés max
- Upload images : 5 par minute max
- Taille totale assets : 50MB max par soumission
```

---

## 🚀 **PHASE 10 : Plan de Développement**

### **10.1 Ordre d'implémentation**

```
SEMAINE 1 : Base de données + Structure
├─ Créer table submissions ✓
├─ Classes Database + Workflow ✓
└─ Migration données test ✓

SEMAINE 2 : Interface Frontend  
├─ Stepper multi-étapes ✓
├─ Réutilisation Form Module ✓  
└─ Sauvegarde automatique ✓

SEMAINE 3 : Crop d'Images
├─ Integration Cropper.js ✓
├─ Validation server-side ✓
└─ Preview temps réel ✓

SEMAINE 4 : Workflow Admin
├─ Interface admin submissions ✓
├─ Actions approve/reject ✓
└─ Notifications email ✓

SEMAINE 5 : Tests & Polish
├─ Tests complets workflow ✓
├─ Optimisations performance ✓
└─ Documentation finale ✓
```

### **10.2 Points critiques**

⚠️ **Attention particulière à** :
- Mapping exact vers structure existante
- Performance upload/crop images
- UX progressive sans perte de données
- Validation robuste côté server
- Gestion erreurs et fallbacks

---

## 📝 **Notes d'Implémentation**

### **Réutilisation maximale**
- `Sisme_Game_Form_Module` pour tous les champs
- `user-developer-ajax.php` pattern pour AJAX
- `user-developer-email-notifications.php` pour emails
- CSS/JS existant comme base

### **Extensions futures**
- Système de commentaires admin ↔ développeur
- Versioning des soumissions 
- Analytics soumissions
- API pour apps mobiles
- Liens automatiques posts news/patch/test

### **KPIs à tracker**
- Nombre soumissions par mois
- Taux d'approbation
- Temps moyen traitement admin
- Taux de completion étapes

---

**🎯 Document vivant - Mise à jour à chaque étape**