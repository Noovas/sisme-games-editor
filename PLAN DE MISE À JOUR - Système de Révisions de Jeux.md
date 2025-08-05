# 🎯 PLAN DE MISE À JOUR - Système de Révisions de Jeux

## 📋 VUE D'ENSEMBLE

Implémentation d'un système de révisions permettant aux développeurs de modifier leurs jeux **published** via un workflow de validation admin, sans casser l'existant.

---

## 🗂️ FICHIERS À MODIFIER

### **1. Backend - Gestion des données**
- `includes/user/user-developer/game-submission/game-submission-data-manager.php`
- `includes/user/user-developer/game-submission/game-submission-ajax.php`

### **2. Frontend - Interface utilisateur**
- `includes/user/user-developer/game-submission/assets/game-submission.js`
- `includes/user/user-developer/game-submission/assets/game-submission-tab.css`

### **3. Admin - Interface administration**
- `admin/components/admin-submission-tab.php`
- `admin/assets/admin-submissions.css`

### **4. Email - Templates de notification**
- `includes/email-manager/email-templates.php`

---

## 🛠️ MODIFICATIONS DÉTAILLÉES

## 📊 **1. EXTENSION DES MÉTADONNÉES**

### **Structure metadata étendue (AJOUTS UNIQUEMENT)**
```php
// Dans game-submission-data-manager.php
// Structure existante PRÉSERVÉE + nouveaux champs optionnels

'metadata' => [
    // ... TOUS les champs existants inchangés
    'created_at' => '...',
    'updated_at' => '...',
    'submitted_at' => '...',
    'published_at' => '...',
    'retry_count' => 0,
    'original_submission_id' => '...',  // ✅ EXISTE DÉJÀ
    
    // ✨ NOUVEAUX CHAMPS (optionnels avec fallback)
    'is_revision' => false,             // bool - Marqueur révision
    'revision_type' => null,            // string - 'major' (plus de minor pour le moment)
    'revision_reason' => '',            // string - Raison de la révision
]
```

---

## 🔧 **2. FONCTIONS DATA MANAGER**

### **Nouvelles fonctions à AJOUTER dans `Sisme_Game_Submission_Data_Manager`**

#### **`create_revision($user_id, $published_submission_id, $revision_type = 'major')`**
- **Paramètres :**
  - `int $user_id` - ID développeur
  - `string $published_submission_id` - ID soumission publiée à réviser
  - `string $revision_type` - Type révision (toujours 'major' pour le moment)
- **Retour :** `string|WP_Error` - ID nouveau brouillon révision ou erreur
- **Logique :**
  1. Vérifier soumission originale existe et status = 'published'
  2. Vérifier limites révisions (`can_create_submission($user_id, true)`)
  3. Copier toutes `game_data` de l'original
  4. Créer nouveau brouillon avec metadata spéciale :
     - `'is_revision' => true`
     - `'original_published_id' => $published_submission_id`
     - `'revision_type' => $revision_type`
     - `'revision_reason' => ''` (sera rempli par l'utilisateur)
  5. Générer nouvel ID avec `generate_submission_id()`
  6. Sauvegarder via `add_submission_to_user_data()`

#### **`is_revision_submission($submission)`**
- **Paramètres :** `array $submission` - Données soumission
- **Retour :** `bool` - True si révision
- **Logique :** `return $submission['metadata']['is_revision'] ?? false;`

#### **`get_original_submission_data($revision_submission)`**
- **Paramètres :** `array $revision_submission` - Données révision
- **Retour :** `array|null` - Données soumission originale ou null
- **Logique :**
  1. Extraire `original_published_id` de metadata
  2. Chercher dans toutes les soumissions développeur
  3. Retourner données originales

### **Fonctions à MODIFIER dans `Sisme_Game_Submission_Data_Manager`**

#### **`can_create_submission($user_id, $is_revision = false)`** - ÉTENDRE
- **Nouveau paramètre :** `bool $is_revision = false`
- **Logique étendue :**
  ```php
  if (!$is_revision) {
      // Logique existante INCHANGÉE pour nouveaux jeux
      $new_game_drafts = array_filter($drafts, function($draft) {
          return !($draft['metadata']['is_revision'] ?? false);
      });
      return count($new_game_drafts) < Sisme_Utils_Users::GAME_MAX_DRAFTS_PER_USER;
  } else {
      // Nouvelle logique pour révisions
      $revision_drafts = array_filter($drafts, function($draft) {
          return ($draft['metadata']['is_revision'] ?? false);
      });
      return count($revision_drafts) < 3; // Max 3 révisions simultanées
  }
  ```

#### **`submit_for_review($user_id, $submission_id)`** - ÉTENDRE
- **Logique à ajouter AVANT logique existante :**
  ```php
  // Détecter si révision
  $is_revision = $submission['metadata']['is_revision'] ?? false;
  
  if ($is_revision) {
      // Workflow spécial révisions (toujours 'major' pour le moment)
      $submission['status'] = Sisme_Utils_Users::GAME_STATUS_PENDING;
      $submission['metadata']['submitted_at'] = current_time('mysql');
      // Pas d'auto-approval, tout passe par admin
      return self::update_submission_in_user_data($user_id, $submission);
  }
  
  // PUIS logique existante inchangée pour nouveaux jeux
  ```

---

## 🌐 **3. NOUVELLES ACTIONS AJAX**

### **Ajouter dans `game-submission-ajax.php`**

#### **Hook d'initialisation**
```php
// Dans sisme_init_game_submission_ajax()
add_action('wp_ajax_sisme_create_game_revision', 'sisme_ajax_create_game_revision');
```

#### **`sisme_ajax_create_game_revision()`** - NOUVELLE FONCTION
- **Vérifications :**
  - `sisme_verify_submission_nonce()`
  - `is_user_logged_in()`
  - `sisme_load_submission_data_manager()`
- **Paramètres POST :**
  - `original_submission_id` (required)
  - `revision_reason` (optional)
- **Logique :**
  1. Valider paramètres
  2. Appeler `Sisme_Game_Submission_Data_Manager::create_revision()`
  3. Si succès : `wp_send_json_success(['revision_id' => $revision_id])`
  4. Si erreur : `wp_send_json_error(['message' => $error])`

---

## 🎨 **4. INTERFACE FRONTEND**

### **JavaScript - `game-submission.js`**

#### **Nouvelle fonction `SismeGameSubmission.createRevision($submissionId)`**
- **Déclencheur :** Clic bouton `.sisme-btn-revision`
- **Logique :**
  1. Afficher modale saisie raison révision
  2. Si validation : appel AJAX `sisme_create_game_revision`
  3. Si succès : redirection `window.location.href = '/dashboard#submit-game?edit=' + revision_id`

#### **Event listener à ajouter dans `bindEvents()`**
```javascript
$(document).on('click', '.sisme-btn-revision', this.createRevision.bind(this));
```

### **CSS - `game-submission-tab.css`**

#### **Styles pour bouton révision**
```css
.sisme-btn-revision {
    background: rgba(255, 152, 0, 0.2);
    border: solid 1px rgba(255, 152, 0, 0.8);
    color: rgb(255, 152, 0);
}

.sisme-btn-revision:hover {
    background: rgba(255, 152, 0, 0.3);
    transform: translateY(-1px);
}

.revision-indicator {
    display: inline-block;
    background: #ff9800;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
    margin-right: 8px;
}
```

### **Template HTML - `game-submission-renderer.php`**

#### **Modifier `render_submission_item()` - CONDITION À AJOUTER**
```php
// Dans la section actions, après les boutons existants
<?php if ($status === 'published'): ?>
    <button class="sisme-btn sisme-btn-secondary sisme-btn-revision" 
            data-submission-id="<?php echo esc_attr($submission['id']); ?>">
        📝 Réviser ce jeu
    </button>
<?php endif; ?>

// Dans l'affichage du titre, AVANT titre existant
<?php if ($submission['metadata']['is_revision'] ?? false): ?>
    <span class="revision-indicator">🔄 Révision de :</span>
<?php endif; ?>
```

---

## 👨‍💼 **5. INTERFACE ADMIN**

### **admin-submission-tab.php**

#### **Modifier `render_submission_row()` - LOGIQUE À AJOUTER**
```php
// Récupération données révision
$is_revision = $submission['metadata']['is_revision'] ?? false;
$original_id = $submission['metadata']['original_published_id'] ?? null;
$original_name = '';

if ($is_revision && $original_id) {
    $original_submission = self::get_submission_for_admin($original_id);
    $original_name = $original_submission['game_data'][Sisme_Utils_Users::GAME_FIELD_NAME] ?? 'Jeu inconnu';
}

// Dans colonne jeu, REMPLACER affichage titre par :
<?php if ($is_revision): ?>
    <span class="revision-badge">🔄</span>
    <strong>Révision :</strong> <?php echo esc_html($original_name); ?>
    <div class="revision-note">
        <?php echo esc_html($submission['metadata']['revision_reason'] ?? 'Pas de raison spécifiée'); ?>
    </div>
<?php else: ?>
    <strong><?php echo esc_html($game_name); ?></strong>
<?php endif; ?>

// Ajouter classe CSS à la ligne
<tr class="sisme-submission-row <?php echo $is_revision ? 'revision-row' : ''; ?>">
```

#### **Modifier `ajax_approve_submission()` - LOGIQUE À REMPLACER**
```php
// REMPLACER logique existante par détection révision
$submission = self::get_submission_for_admin($submission_id);
$is_revision = $submission['metadata']['is_revision'] ?? false;

if ($is_revision) {
    $result = self::approve_revision($submission, $user_id);
} else {
    // GARDER logique existante pour nouveaux jeux
    $result = self::approve_new_submission($submission, $user_id);
}
```

#### **Nouvelle méthode `approve_revision($revision_submission, $user_id)`**
- **Logique :**
  1. Récupérer soumission originale via `original_published_id`
  2. Remplacer `game_data` originale par celle de la révision
  3. Mettre à jour metadata originale (`updated_at`, `reviewed_at`, `admin_user_id`)
  4. Sauvegarder soumission originale modifiée
  5. Supprimer brouillon révision
  6. Mettre à jour jeu publié via `Sisme_Game_Creator_Data_Manager::update_game()`
  7. Envoyer email `revision_approved`

#### **Modifier `calculate_stats()` - COMPTEURS À AJOUTER**
```php
$stats = [
    // ... stats existantes
    'revisions' => 0,        // NOUVEAU
    'new_games' => 0         // NOUVEAU
];

foreach ($submissions as $submission) {
    // ... logique existante
    
    // AJOUTER compteurs spéciaux
    if ($submission['metadata']['is_revision'] ?? false) {
        $stats['revisions']++;
    } else {
        $stats['new_games']++;
    }
}
```

#### **Modifier `render_stats()` - CARTES À AJOUTER**
```php
// AJOUTER après cartes existantes
<div class="sisme-stat-card revisions">
    <span class="stat-number"><?php echo $stats['revisions']; ?></span>
    <span class="stat-label">🔄 Révisions</span>
</div>
<div class="sisme-stat-card new-games">
    <span class="stat-number"><?php echo $stats['new_games']; ?></span>
    <span class="stat-label">🎮 Nouveaux jeux</span>
</div>
```

### **admin-submissions.css**

#### **Styles révisions à AJOUTER**
```css
.revision-row {
    border-left: 3px solid #ff9800;
    background: rgba(255, 152, 0, 0.05);
}

.revision-badge {
    display: inline-block;
    background: #ff9800;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
    margin-right: 8px;
}

.revision-note {
    font-size: 12px;
    color: #666;
    font-style: italic;
    margin-top: 4px;
}

.sisme-stat-card.revisions {
    border-left-color: #ff9800;
}

.sisme-stat-card.new-games {
    border-left-color: #4caf50;
}
```

---

## 📧 **6. TEMPLATES EMAIL**

### **email-templates.php**

#### **Nouvelle méthode `revision_approved($user_name, $game_name)`**
```php
public static function revision_approved($user_name, $game_name) {
    $dashboard_link = home_url(self::DASHBOARD_LINK);
    return "Félicitations {$user_name} !

Nous avons le plaisir de vous informer que votre révision du jeu \"{$game_name}\" a été approuvée et mise en ligne.

Les modifications que vous avez apportées sont maintenant visibles par notre communauté. Merci de maintenir la qualité de votre contenu.

Gérer vos jeux : {$dashboard_link}

Continuez à faire évoluer vos créations !

L'équipe Sisme Games

---
Si vous avez des questions, contactez-nous à sisme-games@sisme.fr";
}
```

#### **Nouvelle méthode `revision_rejected($user_name, $game_name, $rejection_reason)`**
```php
public static function revision_rejected($user_name, $game_name, $rejection_reason) {
    $edit_link = home_url(self::EDIT_LINK);
    return "Bonjour {$user_name},

Nous avons examiné votre révision du jeu \"{$game_name}\" mais nous ne pouvons pas l'approuver dans son état actuel.

Motif du rejet :
{$rejection_reason}

Votre jeu original reste en ligne et accessible. Vous pouvez créer une nouvelle révision en tenant compte de nos commentaires.

Pour créer une nouvelle révision : {$edit_link}

Cordialement,
L'équipe Sisme Games

---
Si vous avez des questions, contactez-nous à sisme-games@sisme.fr";
}
```

---

## 🔄 **7. WORKFLOW COMPLET**

### **Côté Développeur**
1. **Jeu published** → Bouton "📝 Réviser ce jeu" visible
2. **Clic révision** → Modale saisie raison
3. **Validation** → AJAX `sisme_create_game_revision`
4. **Succès** → Redirection `/dashboard#submit-game?edit={revision_id}`
5. **Édition** → Interface normale pré-remplie avec données originales
6. **Soumission** → Workflow normal vers `pending`

### **Côté Admin**
1. **Dashboard admin** → Révision apparaît avec badge "🔄" et nom jeu original
2. **Détails** → Affichage raison révision
3. **Approbation** → `approve_revision()` : met à jour jeu original + supprime brouillon révision
4. **Email** → Développeur reçoit `revision_approved`

### **Résultat Final**
- **Jeu original** mis à jour avec nouvelles données
- **Brouillon révision** supprimé
- **Jeu publié** automatiquement mis à jour
- **Développeur** notifié

---

## 🔒 **8. POINTS DE SÉCURITÉ**

### **Vérifications obligatoires**
- **Nonces WordPress** sur tous les AJAX
- **Ownership check** : seul le développeur propriétaire peut réviser
- **Status check** : seuls les jeux `published` peuvent être révisés
- **Limites** : max 3 révisions simultanées par développeur

### **Fallbacks de compatibilité**
- **Lecture metadata** : `$submission['metadata']['is_revision'] ?? false`
- **Fonctions étendues** : paramètres optionnels avec valeurs par défaut
- **Conditions** : toujours vérifier existence avant utilisation

---

## ✅ **9. TESTS DE NON-RÉGRESSION**

### **Scénarios à valider**
1. **Création normale** : nouveaux jeux fonctionnent identiquement
2. **Limites** : 3 brouillons normaux + 3 révisions = OK
3. **Interface** : boutons et actions existants inchangés
4. **Admin** : soumissions normales traitées comme avant
5. **Emails** : templates existants inchangés

### **Points de contrôle**
- **Dashboard développeur** : affichage normal + boutons révision sur `published`
- **Admin dashboard** : distinction visuelle révisions vs nouveaux jeux
- **Workflow validation** : révisions et nouveaux jeux traités différemment
- **Données** : jeux originaux mis à jour, pas de doublons

---

## 🎯 **10. RÉSUMÉ DES AVANTAGES**

### **Technique**
- ✅ **Zéro nouveau statut** : utilise `draft`, `pending`, `published`
- ✅ **Métadonnées étendues** : champs optionnels avec fallbacks
- ✅ **Réutilisation totale** : même interface d'édition
- ✅ **Compatibilité** : code existant inchangé

### **Fonctionnel**
- ✅ **UX cohérente** : même workflow que création
- ✅ **Validation admin** : contrôle qualité maintenu
- ✅ **Traçabilité** : historique des révisions
- ✅ **Notifications** : emails adaptés au contexte

### **Métier**
- ✅ **Évolution jeux** : développeurs peuvent améliorer leurs créations
- ✅ **Qualité** : validation admin pour changements importants
- ✅ **Flexibilité** : révisions illimitées dans le temps
- ✅ **Transparence** : utilisateurs voient toujours la dernière version

Cette implémentation respecte parfaitement l'architecture existante tout en ajoutant la fonctionnalité demandée de manière élégante et sécurisée.