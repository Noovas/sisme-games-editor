# 📧 Email Manager - API REF

**Version:** 1.0.0 | **Status:** Module complet et fonctionnel  
Documentation technique pour le module gestionnaire d'emails.

---

## 📁 Structure du Module

```
includes/email-manager/
├── email-manager-loader.php      # Chargement automatique
├── email-manager.php             # API d'envoi principal
├── email-templates.php           # Templates spécialisés
```

---

## 📧 email-manager.php

**Classe :** `Sisme_Email_Manager`

### API d'Envoi Principal

<details>
<summary><code>send_email($user_ids, $subject, $content)</code></summary>

```php
// Envoyer un email à une liste d'utilisateurs
// @param array|int $user_ids - ID(s) des utilisateurs destinataires
// @param string $subject - Sujet de l'email (max 100 caractères)
// @param string $content - Contenu texte de l'email
// @return array - Résultat avec compteurs success/error
$result = Sisme_Email_Manager::send_email([42, 43], 'Sujet email', 'Contenu...');

// Retour:
[
    'success' => 2,        // Nombre d'envois réussis
    'errors' => 0,         // Nombre d'erreurs
    'details' => []        // Liste des erreurs détaillées
]
```
</details>

### Optimisations Anti-Spam

**Nettoyage contenu :**
- Suppression HTML (`strip_tags`)
- Décodage entités (`html_entity_decode`)
- Limitation sujet (100 caractères)

---

## 📝 email-templates.php

**Classe :** `Sisme_Email_Templates`

### Templates Soumissions

<details>
<summary><code>submission_rejected($user_name, $game_name, $rejection_reason)</code></summary>

```php
// Générer email de soumission rejetée
// @param string $user_name - Nom de l'utilisateur
// @param string $game_name - Nom du jeu soumis
// @param string $rejection_reason - Motif du rejet admin
// @return string - Contenu email complet

$content = Sisme_Email_Templates::submission_rejected(
    'John Doe',
    'Super Mario Bros',
    'Images de couverture manquantes'
);
```
</details>

<details>
<summary><code>submission_approved($user_name, $game_name, $game_link)</code></summary>

```php
// Générer email de soumission approuvée
// @param string $user_name - Nom de l'utilisateur
// @param string $game_name - Nom du jeu approuvé
// @param string $game_link - URL de la fiche jeu publiée
// @return string - Contenu email complet

$content = Sisme_Email_Templates::submission_approved(
    'John Doe',
    'Super Mario Bros',
    'https://games.sisme.fr/[nom-du-jeu-en-slug]'
);
```
</details>

---

## 🚀 email-manager-loader.php

**Classe :** `Sisme_Email_Manager_Loader`

### Initialisation

<details>
<summary><code>Sisme_Email_Manager_Loader::get_instance()</code></summary>

```php
// Singleton - Récupérer l'instance unique du loader
// @return Sisme_Email_Manager_Loader Instance unique
// Chargement automatique via plugin principal
$loader = Sisme_Email_Manager_Loader::get_instance();
```
</details>

<details>
<summary><code>is_module_ready()</code></summary>

```php
// Vérifier si le module est prêt à l'usage
// @return bool - Module chargé et fonctionnel
$ready = $loader->is_module_ready();
```
</details>

---

## 💡 Exemples d'Usage

### Envoi Simple

```php
// Email basique
$content = "Bonjour,\n\nVotre compte a été créé.\n\nCordialement,\nL'équipe";
$result = Sisme_Email_Manager::send_email([42], 'Bienvenue', $content);

if ($result['success'] > 0) {
    echo "Email envoyé !";
}
```

### Workflow Soumission Rejetée

```php
// Récupérer données
$user = get_userdata($developer_user_id);
$game_name = $submission['game_data']['game_name'];
$edit_url = home_url('/dashboard#edit-submission-' . $submission_id);

// Générer contenu
$content = Sisme_Email_Templates::submission_rejected(
    $user->display_name,
    $game_name,
    $admin_rejection_notes,
    $edit_url
);

// Envoyer
$result = Sisme_Email_Manager::send_email(
    [$developer_user_id], 
    "Soumission rejetée : {$game_name}", 
    $content
);
```

### Workflow Soumission Approuvée

```php
// Récupérer données
$user = get_userdata($developer_user_id);
$game_name = $submission['game_data']['game_name'];
$game_url = get_permalink($published_game_id);
$dashboard_url = home_url('/dashboard');

// Générer contenu
$content = Sisme_Email_Templates::submission_approved(
    $user->display_name,
    $game_name,
    $game_url,
    $dashboard_url
);

// Envoyer
$result = Sisme_Email_Manager::send_email(
    [$developer_user_id], 
    "Soumission approuvée : {$game_name}", 
    $content
);
```

---

## 🔧 Intégration Plugin

### Chargement Automatique

Le module se charge automatiquement via le plugin principal :

```php
// Dans sisme-games-editor.php
define('SISME_GAMES_MODULES', array(
    "utils",
    "email-manager",  // ← Module ajouté
    // ... autres modules
));
```

### Logging Debug

```php
// Logs automatiques si WP_DEBUG = true
// [Sisme Email Manager] Module gestionnaire d'emails initialisé
// [Sisme Email Manager] Module chargé: email-manager.php
// [Sisme Email] SUCCESS - To: user@example.com, Subject: Test
// [Sisme Email] Envoi terminé - Succès: 1, Erreurs: 0
```

---

## ⚠️ Notes Importantes

### Limitations
- **Format texte uniquement** (pas de HTML)
- **Sujet limité** à 100 caractères
- **Nettoyage automatique** du contenu

### Bonnes Pratiques
- Toujours vérifier `$result['success']` après envoi
- Utiliser les templates pour la cohérence
- Tester les emails avec WP_DEBUG activé
- Éviter l'envoi en masse (limitation serveur)

### Anti-Spam
- Pas de HTML pour maximiser la délivrabilité
- From/Reply-To configurés correctement
- Message-ID unique par email