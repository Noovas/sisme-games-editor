# 🎮 User Developer - API REF

**Version:** 2.2.0 | **Status:** Étape 2/3 terminée - AJAX + Email notifications complets  
Documentation technique pour le module développeur utilisateur.

---

## 📧 user-developer-email-notifications.php

**Classe :** `Sisme_User_Developer_Email_Notifications`

### Système Email Automatique

<details>
<summary><code>init_hooks()</code></summary>

```php
// Initialiser les hooks automatiques pour envoi d'emails
// Hooks écoutés:
// - 'sisme_developer_application_submitted' → Email confirmation
// - 'sisme_developer_application_approved' → Email félicitations  
// - 'sisme_developer_application_rejected' → Email rejet avec conseils
// Auto-initialisation via hook 'init' priorité 20
```
</details>

### Envoi Email par Type

<details>
<summary><code>send_application_submitted_email($user_id)</code></summary>

```php
// Envoyer email de confirmation candidature soumise
// @param int $user_id - ID utilisateur qui a candidaté
// @return bool - Succès envoi
//
// Contenu:
// - Confirmation réception candidature
// - Délai d'examen (3-7 jours ouvrés)
// - Lien vers dashboard
// - Format texte simple anti-spam
$success = Sisme_User_Developer_Email_Notifications::send_application_submitted_email(42);
```
</details>

<details>
<summary><code>send_application_approved_email($user_id, $admin_notes)</code></summary>

```php
// Envoyer email félicitations candidature approuvée
// @param int $user_id - ID utilisateur approuvé
// @param string $admin_notes - Notes admin (optionnel)
// @return bool - Succès envoi
//
// Contenu:
// - Félicitations approbation
// - Liste privilèges développeur
// - Notes admin si présentes (stripslashes appliqué)
// - Lien accès "Mes Jeux"
$success = Sisme_User_Developer_Email_Notifications::send_application_approved_email(42, 'Excellent dossier !');
```
</details>

<details>
<summary><code>send_application_rejected_email($user_id, $admin_notes)</code></summary>

```php
// Envoyer email candidature rejetée avec conseils
// @param int $user_id - ID utilisateur rejeté
// @param string $admin_notes - Notes admin (optionnel)
// @return bool - Succès envoi
//
// Contenu:
// - Information rejet avec tact
// - Commentaires admin si présents
// - Conseils amélioration détaillés
// - Encouragement à recandidater
$success = Sisme_User_Developer_Email_Notifications::send_application_rejected_email(42, 'Portfolio à enrichir');
```
</details>

### Configuration Anti-Spam

<details>
<summary><code>send_simple_email($to, $subject, $message)</code></summary>

```php
// Envoi email optimisé anti-spam
// @param string $to - Destinataire
// @param string $subject - Sujet
// @param string $message - Message texte
// @return bool - Succès envoi
//
// Optimisations:
// - Headers simples (text/plain uniquement)
// - From: correct avec domaine site
// - Nettoyage contenu (strip_tags, html_entity_decode)
// - Logging debug WP_DEBUG
```
</details>

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
- **rejected** - Candidature rejetée (peut recandidater via reset)

### Système de Rôles Multi-Niveaux
- **Rôle WordPress** : `sisme-dev` (ajouté aux rôles existants)
- **Capacités** : `submit_games`, `manage_own_games`
- **Multi-rôles** : Admin peut être développeur (admin + sisme-dev)
- **Révocable** : Suppression du rôle développeur sans affecter les autres

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

### Gestion Assets

<details>
<summary><code>enqueue_developer_assets()</code></summary>

```php
// Charger les assets CSS et JS du module développeur
// Assets chargés:
// - user-developer.css (styles gaming dark)
// - user-developer.js (validation temps réel)
// - user-developer-ajax.js (soumission AJAX + reset)
// - Localisation AJAX avec nonce sécurisé
```
</details>

---

## 📊 user-developer-data-manager.php

**Classe :** `Sisme_User_Developer_Data_Manager`

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

### Sauvegarde AJAX

<details>
<summary><code>save_developer_application($user_id, $application_data)</code></summary>

```php
// Sauvegarder une candidature développeur (utilisé par AJAX)
// @param int $user_id - ID utilisateur
// @param array $application_data - Données candidature
// @return bool - Succès de la sauvegarde
//
// Données acceptées:
// - studio_name (string, requis, 2-100 chars)
// - studio_description (string, requis, 10-1000 chars)
// - studio_website (string, URL optionnelle)
// - studio_social_links (array: platform => URL)
//   * twitter: twitter.com, x.com
//   * discord: discord.gg, discord.com, discordapp.com
//   * instagram: instagram.com
//   * youtube: youtube.com, youtu.be
//   * twitch: twitch.tv
// - representative_* (données représentant, tous requis)
//
// Actions automatiques:
// - Sanitisation complète des données
// - Validation âge 18+ sur birthdate
// - Ajout submitted_date automatique
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
// @return bool - Est développeur approuvé (statut ET rôle)
$is_approved = Sisme_User_Developer_Data_Manager::is_approved_developer(42);
```
</details>

---

## 🔧 user-developer-ajax.php

**Fonctions :** Handlers AJAX WordPress

### Handler Principal

<details>
<summary><code>sisme_ajax_developer_submit()</code></summary>

```php
// Handler AJAX pour soumission candidature développeur
// Action: 'sisme_developer_submit'
// Nonce: 'sisme_developer_nonce'
//
// Sécurité:
// - Vérification nonce obligatoire
// - Utilisateur connecté requis
// - Validation can_apply()
//
// Validation:
// - Sanitisation complète des données
// - Validation métier (âge, URLs, etc.)
// - Retour JSON avec erreurs spécifiques
//
// Succès:
// - Sauvegarde via save_developer_application()
// - Changement statut automatique
// - Retour JSON avec reload_dashboard: true
```
</details>

<details>
<summary><code>sisme_ajax_developer_reset_rejection()</code></summary>

```php
// Handler AJAX pour reset candidature rejetée
// Action: 'sisme_developer_reset_rejection'
// Nonce: 'sisme_developer_nonce'
//
// Sécurité:
// - Vérification nonce obligatoire
// - Utilisateur connecté requis
// - Validation statut 'rejected' uniquement
//
// Actions:
// - Reset statut vers 'none'
// - Suppression anciennes données candidature
// - Permet nouvelle candidature immédiate
// - Retour JSON avec reload_dashboard: true
```
</details>

### Fonctions Utilitaires

<details>
<summary><code>sisme_sanitize_developer_form_data($raw_data)</code></summary>

```php
// Sanitiser les données du formulaire développeur
// @param array $raw_data - Données brutes POST
// @return array - Données sanitisées
//
// Sanitisation par type:
// - Texte: sanitize_text_field()
// - Textarea: sanitize_textarea_field()
// - URL: esc_url_raw()
// - Email: sanitize_email()
// - Liens sociaux: esc_url_raw() + validation domaine
```
</details>

<details>
<summary><code>sisme_validate_developer_form_data($data)</code></summary>

```php
// Valider les données du formulaire développeur
// @param array $data - Données sanitisées
// @return array - Erreurs de validation (vide si OK)
//
// Validations:
// - Longueurs min/max de champs
// - Format email et URLs
// - Âge minimum 18 ans
// - Domaines spécifiques réseaux sociaux
// - Champs obligatoires
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
// - Formulaire AJAX intégré complet:
//   * Section Studio (nom, description, site web, réseaux sociaux)
//   * Section Représentant (identité, naissance, adresse, contact)
//   * Validation HTML5 et JavaScript temps réel
//   * Nonce de sécurité intégré
//   * Feedback utilisateur (success/error/loading)
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
// - Statut candidature soumise
// - Données candidature affichées
// - Badge "En cours d'examen"
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
// - Statistiques développeur
// - Boutons "Soumettre un jeu" (futur)
// - Liste jeux soumis (futur)
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
// - Notes administrateur si disponibles
// - Conseils pour prochaine candidature
// - Bouton "Faire une nouvelle demande" fonctionnel
// - Zone de feedback pour reset AJAX
```
</details>

---

## 🔐 utils-developer-roles.php

**Classe :** `Sisme_Utils_Developer_Roles`

### Gestion Rôles

<details>
<summary><code>create_developer_role()</code></summary>

```php
// Créer le rôle développeur WordPress
// Rôle: 'sisme-dev'
// Capacités: subscriber + submit_games + manage_own_games
// Auto-création lors du chargement du plugin
```
</details>

<details>
<summary><code>promote_to_developer($user_id)</code></summary>

```php
// Promouvoir un utilisateur au rôle développeur
// @param int $user_id - ID utilisateur
// @return bool - Succès de la promotion
//
// Actions:
// - Ajoute le rôle 'sisme-dev' (conserve les autres)
// - Met à jour le statut vers 'approved'
// - Admin reste admin + devient développeur
// - Subscriber devient subscriber + développeur
```
</details>

<details>
<summary><code>revoke_developer($user_id, $admin_notes)</code></summary>

```php
// Révoquer le statut développeur
// @param int $user_id - ID utilisateur
// @param string $admin_notes - Notes admin optionnelles
// @return bool - Succès de la révocation
//
// Actions:
// - Supprime SEULEMENT le rôle 'sisme-dev'
// - Conserve tous les autres rôles (admin, etc.)
// - Met à jour le statut vers 'none'
// - Sauvegarde les notes admin et date révision
```
</details>

### Actions Admin

<details>
<summary><code>approve_application($user_id, $admin_notes)</code></summary>

```php
// Approuver une candidature développeur
// @param int $user_id - ID utilisateur
// @param string $admin_notes - Notes admin
// @return bool - Succès de l'approbation
//
// Workflow:
// 1. Promotion au rôle développeur
// 2. Statut vers 'approved'
// 3. Mise à jour données candidature
// 4. Sauvegarde notes admin et date révision
```
</details>

<details>
<summary><code>reject_application($user_id, $admin_notes)</code></summary>

```php
// Rejeter une candidature développeur
// @param int $user_id - ID utilisateur
// @param string $admin_notes - Notes admin
// @return bool - Succès du rejet
//
// Actions:
// - Statut vers 'rejected' (SANS toucher au rôle)
// - Sauvegarde notes admin et date révision
// - Utilisateur peut recandidater plus tard
```
</details>

---

## 🌐 Interface Admin

### Page admin/pages/developers.php

<details>
<summary>Page de gestion développeurs</summary>

**URL :** Admin → Sisme Games → Développeurs

**Fonctionnalités :**
- **Liste complète** des développeurs avec statuts
- **Actions conditionnelles** selon le statut :
  * `pending` : Boutons "✅ Approuver" et "❌ Rejeter"
  * `approved` : Badge "🎮 Développeur actif" + "🔄 Révoquer"
  * Autres : "Aucune action"
- **Modal de confirmation** avec zone notes admin
- **Données complètes** en accordéon (debug)
- **Statistiques** visuelles par statut

**Sécurité :**
- Nonces WordPress obligatoires
- Traitement POST sécurisé
- Messages de feedback appropriés
</details>

---

## 🎯 JavaScript - SismeDeveloperAjax

**Namespace :** `window.SismeDeveloperAjax`

### Configuration

```javascript
// Configuration du module AJAX
SismeDeveloperAjax.config = {
    formSelector: '#sisme-developer-form',
    feedbackSelector: '#sisme-form-feedback',
    submitButtonSelector: '#sisme-developer-submit',
    retryButtonSelector: '#sisme-retry-application',
    retryFeedbackSelector: '#sisme-retry-feedback',
    ajaxUrl: sismeAjax.ajaxurl,
    nonce: sismeAjax.nonce
};
```

### Méthodes Principales

<details>
<summary><code>submitApplication(formData)</code></summary>

```javascript
// Soumettre la candidature via AJAX
// @param object formData - Données du formulaire
//
// Processus:
// 1. Désactivation bouton + feedback loading
// 2. Requête AJAX vers 'sisme_developer_submit'
// 3. Gestion réponse success/error
// 4. Affichage feedback utilisateur
// 5. Rechargement dashboard si succès
```
</details>

<details>
<summary><code>handleRetryApplication(event)</code></summary>

```javascript
// Gérer le reset d'une candidature rejetée
// @param Event event - Événement click du bouton retry
//
// Processus:
// 1. Confirmation utilisateur obligatoire
// 2. Désactivation bouton + feedback loading
// 3. Requête AJAX vers 'sisme_developer_reset_rejection'
// 4. Gestion réponse success/error
// 5. Rechargement dashboard si succès
```
</details>

<details>
<summary><code>validateFormData(formData)</code></summary>

```javascript
// Validation côté client avant soumission
// @param object formData - Données à valider
// @return object - Erreurs trouvées
//
// Validations:
// - Longueurs min/max
// - Formats email et URL
// - Âge minimum 18 ans
// - Domaines réseaux sociaux
// - Champs obligatoires
```
</details>

---

## 🎨 Styles CSS

### Classes principales

```css
/* États développeur */
.sisme-developer-state-none
.sisme-developer-state-pending
.sisme-developer-state-approved
.sisme-developer-state-rejected

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

/* État rejeté spécifique */
.sisme-admin-feedback            /* Notes administrateur */
.sisme-rejection-info            /* Zone de conseils */
.sisme-tips-list                 /* Liste des conseils */
.sisme-retry-actions             /* Actions de retry */
#sisme-retry-application         /* Bouton retry */
#sisme-retry-feedback            /* Feedback retry */

/* Admin */
.sisme-dev-status                 /* Badges statut */
.sisme-dev-actions               /* Boutons actions admin */
.sisme-dev-stat-card             /* Cartes statistiques */
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

// Modules chargés automatiquement:
$required_modules = [
    'user-developer-data-manager.php',    // Gestion données
    'user-developer-renderer.php',       // Rendu interfaces
    'user-developer-ajax.php',           // Handlers AJAX
    'user-developer-email-notifications.php'  // ✅ NOUVEAU: Emails
];
```

---

## 📋 Métadonnées WordPress

### Structure des Données

```php
// Métadonnée: 'sisme_user_developer_status'
// Valeurs: 'none', 'pending', 'approved', 'rejected'

// Métadonnée: 'sisme_user_developer_application'
// Structure complète avec tous les champs du formulaire
[
    'studio_name' => string,
    'studio_description' => string,
    'studio_website' => string (URL),
    'studio_social_links' => [
        'twitter' => string (URL),
        'discord' => string (URL),
        'instagram' => string (URL),
        'youtube' => string (URL),
        'twitch' => string (URL)
    ],
    'representative_firstname' => string,
    'representative_lastname' => string,
    'representative_birthdate' => string (Y-m-d),
    'representative_address' => string,
    'representative_city' => string,
    'representative_country' => string,
    'representative_email' => string (email),
    'representative_phone' => string,
    'submitted_date' => string (Y-m-d H:i:s),
    'reviewed_date' => string (Y-m-d H:i:s),
    'admin_notes' => string
]

// Métadonnée: 'sisme_user_developer_profile'
// Structure pour profil développeur public (futur)
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

## 🔄 Workflow Développeur Complet

### Phase 1: Candidature (none → pending)
1. **Utilisateur** remplit formulaire candidature avec validation temps réel
2. **Soumission AJAX** avec nonce de sécurité
3. **Validation serveur** complète des données
4. **Sauvegarde** avec `save_developer_application()`
5. **Statut** passe automatiquement à 'pending'
6. **Rechargement** dashboard avec nouveau statut

### Phase 2: Validation Admin (pending → approved/rejected)
1. **Admin** examine candidature depuis interface dédiée
2. **Action** via modal avec notes administrateur
3. **Approbation** : Ajout rôle 'sisme-dev' + statut 'approved'
4. **Rejet** : Statut 'rejected' (rôle inchangé)
5. **Notification** utilisateur (à implémenter)

### Phase 3: Développeur Actif (approved)
1. **Navigation** change vers "🎮 Mes Jeux"
2. **Capacités** : `submit_games`, `manage_own_games`
3. **Interface** dédiée pour soumission jeux (futur)
4. **Révocation** possible par admin (retour none)

### Phase 4: Révocation (approved → none)
1. **Admin** peut révoquer le statut développeur
2. **Suppression** rôle 'sisme-dev' uniquement
3. **Conservation** autres rôles (admin reste admin)
4. **Statut** vers 'none' (peut recandidater)
5. **Navigation** redevient "📝 Devenir Développeur"

### Phase 5: Reset Rejection (rejected → none)
1. **Utilisateur rejeté** voit notes admin et conseils
2. **Bouton "Nouvelle demande"** avec confirmation obligatoire
3. **Reset AJAX** sécurisé avec nonce
4. **Statut** vers 'none' + suppression anciennes données
5. **Rechargement** dashboard → formulaire candidature accessible

### Phase 6: Notifications Email ✅ NOUVEAU
1. **Candidature soumise** → Email confirmation immédiat
2. **Approbation admin** → Email félicitations + privilèges
3. **Rejet admin** → Email constructif + conseils amélioration
4. **Format anti-spam** → Texte simple, headers corrects
5. **Logging intégré** → Debug via WP_DEBUG

---

## 🎯 Prochaines Étapes - Phase 3

### Interface "Mes Jeux" ✅ Base
- [x] Interface développeur approuvé avec statistiques
- [x] Placeholder boutons "Soumettre un jeu"
- [ ] Formulaire soumission jeu frontend fonctionnel
- [ ] Liste jeux soumis avec statuts
- [ ] Workflow modération jeux admin

### Système de Notifications
- [ ] Email approbation/rejet candidature
- [ ] Notifications dashboard
- [ ] Alertes admin nouvelles candidatures
- [ ] Système de badges développeur

### Extensions Admin
- [x] Interface gestion candidatures complète
- [x] Actions approbation/rejet/révocation
- [ ] Filters par statut développeur
- [ ] Export données développeurs
- [ ] Statistiques globales développeurs
- [ ] Modération jeux soumis

### Améliorations UX ✅ Terminé
- [x] Reset candidature rejetée fonctionnel
- [x] Affichage notes admin pour rejets
- [x] Conseils amélioration candidature
- [x] Confirmation utilisateur pour actions critiques

### Notifications Email ✅ Terminé
- [x] Email candidature soumise (confirmation + délai)
- [x] Email candidature approuvée (félicitations + privilèges)
- [x] Email candidature rejetée (conseils + encouragements)
- [x] Format anti-spam optimisé (texte simple)
- [x] Intégration hooks automatiques

---

## 🔗 Dépendances

### Modules Utils Utilisés
- `Sisme_Utils_Users` - Fonctions getter développeur + constantes
- `Sisme_Utils_Developer_Roles` - Gestion rôles WordPress multi-niveaux
- `Sisme_Utils_Validation` - Validation données formulaire (futur)

### Modules Dashboard Réutilisés
- `Sisme_User_Dashboard_Renderer` - Rendu composants dashboard
- `Sisme_User_Dashboard_Data_Manager` - Gestion données dashboard
- Navigation et assets dashboard existants

### Fonctions WordPress
- `add_role()` / `remove_role()` - Gestion rôles multi-niveaux ✅
- `update_user_meta()` / `get_user_meta()` - Métadonnées utilisateur
- `wp_ajax_` hooks - Handlers AJAX sécurisés ✅
- `wp_nonce_` functions - Sécurité CSRF ✅

### Actions AJAX Disponibles
- `sisme_developer_submit` - Soumission candidature ✅
- `sisme_developer_reset_rejection` - Reset candidature rejetée ✅

### Hooks Email Automatiques 
- `sisme_developer_application_submitted` - Déclenché après sauvegarde candidature
- `sisme_developer_application_approved` - Déclenché lors approbation admin
- `sisme_developer_application_rejected` - Déclenché lors rejet admin