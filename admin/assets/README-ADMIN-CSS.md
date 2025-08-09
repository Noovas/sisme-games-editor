# Documentation du Système CSS Admin Partagé

## 🎯 Vision et Objectif

Le fichier `CSS-admin-shared.css` constitue un **système de design unifié** pour toutes les pages d'administration du plugin WordPress **sisme-games-editor**. 

### Problèmes Résolus
- ✅ **Cohérence visuelle** : Fini les styles différents sur chaque page admin
- ✅ **Maintenabilité** : Variables CSS centralisées et réutilisables  
- ✅ **Séparation claire** : Préfixe `sisme-admin-*` pour éviter les conflits avec le frontend
- ✅ **Efficacité de développement** : Classes utilitaires prêtes à l'emploi

---

## 🎨 Architecture du Système

### 1. Variables CSS (Custom Properties)

Le système repose sur des **variables CSS centralisées** dans `:root` :

#### Couleurs de Base
```css
--sisme-admin-white: #ffffff
--sisme-admin-black: #000000
--sisme-admin-gray-50: #f9fafb  /* Le plus clair */
--sisme-admin-gray-100: #f3f4f6
--sisme-admin-gray-200: #e5e7eb
--sisme-admin-gray-300: #d1d5db
--sisme-admin-gray-400: #9ca3af
--sisme-admin-gray-500: #6b7280
--sisme-admin-gray-600: #4b5563
--sisme-admin-gray-700: #374151
--sisme-admin-gray-800: #1f2937
--sisme-admin-gray-900: #111827  /* Le plus sombre */
```

#### Couleurs Fonctionnelles
```css
/* Couleurs principales avec variations */
--sisme-admin-green: #10b981      (succès)
--sisme-admin-green-light: #d1fae5
--sisme-admin-green-dark: #047857

--sisme-admin-blue: #3b82f6       (primaire/info)
--sisme-admin-blue-light: #dbeafe
--sisme-admin-blue-dark: #1d4ed8

--sisme-admin-red: #ef4444        (danger)
--sisme-admin-red-light: #fecaca
--sisme-admin-red-dark: #dc2626

--sisme-admin-yellow: #eab308     (avertissement)
--sisme-admin-orange: #f97316     (accent)
--sisme-admin-purple: #8b5cf6     (vedettes/spécial)
```

#### Couleurs Sémantiques
```css
--sisme-admin-success: var(--sisme-admin-green)
--sisme-admin-warning: var(--sisme-admin-yellow)
--sisme-admin-danger: var(--sisme-admin-red)
--sisme-admin-info: var(--sisme-admin-blue)
--sisme-admin-primary: var(--sisme-admin-blue)
--sisme-admin-secondary: var(--sisme-admin-gray-500)
```

#### Thèmes Clair/Sombre
```css
/* Thème clair (par défaut) */
--sisme-admin-light-bg: var(--sisme-admin-white)
--sisme-admin-light-bg-gray: var(--sisme-admin-gray-50)
--sisme-admin-light-text: var(--sisme-admin-gray-900)
--sisme-admin-light-text-muted: var(--sisme-admin-gray-600)

/* Thème sombre */
--sisme-admin-dark-bg: var(--sisme-admin-gray-900)
--sisme-admin-dark-bg-light: var(--sisme-admin-gray-800)
--sisme-admin-dark-text: var(--sisme-admin-gray-100)
--sisme-admin-dark-text-muted: var(--sisme-admin-gray-400)
```

#### Espacements Standardisés
```css
--sisme-admin-spacing-xs: 4px    /* Extra petit */
--sisme-admin-spacing-sm: 8px    /* Petit */
--sisme-admin-spacing-md: 16px   /* Moyen (défaut) */
--sisme-admin-spacing-lg: 24px   /* Grand */
--sisme-admin-spacing-xl: 32px   /* Extra grand */
--sisme-admin-spacing-2xl: 48px  /* Très grand */
```

#### Autres Variables
```css
/* Rayons de bordure */
--sisme-admin-radius-sm: 4px
--sisme-admin-radius-md: 8px
--sisme-admin-radius-lg: 12px
--sisme-admin-radius-xl: 16px

/* Ombres */
--sisme-admin-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05)
--sisme-admin-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1)...
--sisme-admin-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1)...

/* Transitions */
--sisme-admin-transition: all 0.2s ease-in-out
```

---

## 🧱 Composants du Système

### 2. Classes Utilitaires de Base

#### Fonds (Background)
```css
.sisme-admin-bg-light        /* Fond blanc */
.sisme-admin-bg-light-gray   /* Fond gris très clair */
.sisme-admin-bg-dark         /* Fond sombre */
.sisme-admin-bg-green        /* Fond vert */
.sisme-admin-bg-green-light  /* Fond vert clair */
/* ... toutes les variantes de couleurs */

/* Éléments avec fonds prédéfinis */
.sisme-admin-item-neutral    /* Élément avec fond gris neutre */
.sisme-admin-item-purple     /* Élément avec fond violet (featured) */
```

#### Textes (Colors)
```css
.sisme-admin-text-dark       /* Texte sombre */
.sisme-admin-text-light      /* Texte clair */
.sisme-admin-text-muted      /* Texte atténué */
.sisme-admin-text-success    /* Texte vert (succès) */
.sisme-admin-text-danger     /* Texte rouge (erreur) */
.sisme-admin-text-black      /* Texte noir */
/* ... toutes les variantes sémantiques */
```

#### Poids de Police
```css
.sisme-admin-font-medium     /* font-weight: 500 */
```

### 3. Typographie Standardisée

#### Hiérarchie des Titres
```css
.sisme-admin-title     /* 24px, weight: 600 - Titre principal */
.sisme-admin-subtitle  /* 18px, weight: 500 - Sous-titre */
.sisme-admin-heading   /* 16px, weight: 500 - Titre de section */
.sisme-admin-comment   /* 14px, italic - Commentaire */
.sisme-admin-small     /* 12px - Texte petit */
```

#### Alignements
```css
.sisme-admin-text-center
.sisme-admin-text-left
.sisme-admin-text-right
```

### 4. Layout et Grille

#### Conteneurs
```css
.sisme-admin-container      /* Conteneur standard avec ombre */
.sisme-admin-container-dark /* Version sombre */
.sisme-admin-section        /* Section avec marge inférieure */
```

#### Système de Grille
```css
.sisme-admin-grid     /* Grille de base avec gap */
.sisme-admin-grid-2   /* 2 colonnes */
.sisme-admin-grid-3   /* 3 colonnes */
.sisme-admin-grid-4   /* 4 colonnes */

/* Responsive automatique : 1 colonne sur mobile */
```

#### Flexbox
```css
.sisme-admin-flex         /* Flex avec gap horizontal */
.sisme-admin-align-items-center  /* Centré verticalement et horizontalement */
.sisme-admin-flex-between /* Space-between avec alignement vertical */

/* Direction verticale pour espacement vertical */
.sisme-admin-flex-col     /* Flex colonne avec gap moyen (16px) */
.sisme-admin-flex-col-sm  /* Flex colonne avec gap petit (8px) */  
.sisme-admin-flex-col-lg  /* Flex colonne avec gap grand (24px) */

/* Layout avec espacement automatique */
.sisme-admin-layout       /* Container flex vertical avec espacement automatique */
```

**Usage de sisme-admin-layout** : 
- Applique automatiquement un espacement vertical de 24px entre tous les éléments enfants
- Remet à zéro les marges des enfants pour éviter les conflits
- Idéal pour organiser verticalement des cartes, sections, alertes, etc.

### 5. Cartes (Cards)

```css
.sisme-admin-card        /* Carte standard avec ombre et hover */
.sisme-admin-card-dark   /* Version sombre */
.sisme-admin-card-header /* En-tête de carte avec bordure */
```

**Comportement** : Effet hover avec translation et ombre renforcée.

### 6. Boutons

#### Types de Boutons
```css
.sisme-admin-btn           /* Bouton de base */
.sisme-admin-btn-primary   /* Bouton principal (bleu) */
.sisme-admin-btn-success   /* Bouton de succès (vert) */
.sisme-admin-btn-warning   /* Bouton d'avertissement (jaune) */
.sisme-admin-btn-danger    /* Bouton de danger (rouge) */
.sisme-admin-btn-secondary /* Bouton secondaire (gris) */
```

#### Tailles
```css
.sisme-admin-btn-sm  /* Petit bouton */
.sisme-admin-btn-lg  /* Grand bouton */
```

**Comportements** :
- Hover : Translation légère vers le haut + ombre
- Disabled : Opacité réduite et curseur interdit

### 7. Badges et Statuts

```css
.sisme-admin-badge           /* Badge de base */
.sisme-admin-badge-success   /* Badge vert */
.sisme-admin-badge-warning   /* Badge jaune */
.sisme-admin-badge-danger    /* Badge rouge */
.sisme-admin-badge-info      /* Badge bleu */
.sisme-admin-badge-secondary /* Badge gris */
```

### 8. Tableaux

```css
.sisme-admin-table      /* Table avec ombres et bordures arrondies */
.sisme-admin-table-dark /* Version sombre */
```

**Fonctionnalités** :
- Hover sur les lignes
- En-têtes stylisés
- Compatible thème sombre

### 9. Alertes et Notifications

```css
.sisme-admin-alert         /* Base des alertes */
.sisme-admin-alert-success /* Alerte verte */
.sisme-admin-alert-warning /* Alerte jaune */
.sisme-admin-alert-danger  /* Alerte rouge */
.sisme-admin-alert-info    /* Alerte bleue */
.sisme-admin-alert-purple  /* Alerte violette */
```

**Style** : Bordure gauche colorée + fond teinté.

### 10. Statistiques et Métriques

```css
.sisme-admin-stats              /* Grille responsive pour stats */
.sisme-admin-stat-card          /* Carte de statistique */
.sisme-admin-stat-number        /* Nombre principal (32px, bold) */
.sisme-admin-stat-label         /* Label de la stat */

/* Variantes colorées */
.sisme-admin-stat-card-success
.sisme-admin-stat-card-warning
.sisme-admin-stat-card-danger
.sisme-admin-stat-card-info
```

### 11. Classes Utilitaires d'Espacement

#### Marges
```css
.sisme-admin-m-0, .sisme-admin-mt-0, .sisme-admin-mb-0...  /* 0 */
.sisme-admin-m-sm, .sisme-admin-mt-sm, .sisme-admin-mb-sm... /* 8px */
.sisme-admin-m-md, .sisme-admin-mt-md, .sisme-admin-mb-md... /* 16px */
.sisme-admin-m-lg, .sisme-admin-mt-lg, .sisme-admin-mb-lg... /* 24px */
```

#### Paddings
```css
.sisme-admin-p-0, .sisme-admin-pt-0, .sisme-admin-pb-0...  /* 0 */
.sisme-admin-p-sm, .sisme-admin-pt-sm, .sisme-admin-pb-sm... /* 8px */
.sisme-admin-p-md, .sisme-admin-pt-md, .sisme-admin-pb-md... /* 16px */
.sisme-admin-p-lg, .sisme-admin-pt-lg, .sisme-admin-pb-lg... /* 24px */
```

### 12. Utilitaires Divers

#### Visibilité
```css
.sisme-admin-hidden    /* display: none */
.sisme-admin-visible   /* display: block */
```

#### Conteneurs Utilitaires
```css
.sisme-admin-scrollable-container  /* Conteneur avec défilement vertical (max-height: 300px) */
```

#### Bordures et Ombres
```css
.sisme-admin-rounded       /* border-radius moyen */
.sisme-admin-rounded-lg    /* border-radius large */
.sisme-admin-rounded-full  /* border-radius: 50% */
.sisme-admin-shadow        /* Ombre moyenne */
.sisme-admin-shadow-lg     /* Ombre large */
.sisme-admin-border        /* Bordure standard */
.sisme-admin-border-dark   /* Bordure sombre */
```

#### Interactions
```css
.sisme-admin-cursor-pointer     /* cursor: pointer */
.sisme-admin-cursor-not-allowed /* cursor: not-allowed */
.sisme-admin-opacity-50         /* opacity: 0.5 */
.sisme-admin-opacity-75         /* opacity: 0.75 */
.sisme-admin-smooth-transition  /* Transition douce (all 0.2s ease) */
```

#### Code et Développement
```css
.sisme-admin-code           /* Typographie monospace standardisée */
.sisme-admin-pre-code       /* Blocs de code avec styles cohérents */
.sisme-admin-pre-code-dark  /* Version sombre pour les blocs de code */
```

**Usage des classes de code** :
- `sisme-admin-code` : Police monospace pour les éléments `<code>` inline
- `sisme-admin-pre-code` : Blocs `<pre>` avec fond clair, bordure et défilement
- `sisme-admin-pre-code-dark` : Variante sombre pour contraster avec les données claires

#### Icônes
```css
.sisme-admin-icon-lg               /* Icône de taille large (24px) */
```

---

## ✨ Animations et Effets

### Animations CSS
```css
@keyframes sisme-admin-fade-in  /* Apparition en fondu */
@keyframes sisme-admin-pulse    /* Pulsation */

.sisme-admin-fade-in  /* Applique l'animation de fondu */
.sisme-admin-pulse    /* Applique l'animation de pulsation */
```

---

## 🪟 Système de Modale Admin

Le système de modale admin permet d'afficher des dialogues de confirmation ou d'action en overlay, soit en plein écran, soit directement sur une ligne de tableau.

### Classes principales
```css
.sisme-admin-modal            /* Overlay principal (plein écran ou custom) */
.sisme-admin-modal-visible    /* Affiche la modale (display: flex) */
.sisme-admin-modal-content    /* Conteneur du contenu de la modale */
.sisme-admin-modal-header     /* En-tête de la modale */
.sisme-admin-modal-title      /* Titre principal */
.sisme-admin-modal-subtitle   /* Sous-titre */
.sisme-admin-modal-body       /* Corps de la modale */
.sisme-admin-modal-actions    /* Groupe de boutons d'action */
.sisme-admin-modal-btn        /* Bouton de base */
.sisme-admin-modal-btn-cancel /* Bouton annuler (gris) */
.sisme-admin-modal-btn-confirm/* Bouton confirmer (rouge) */
```

### Utilisation

- Pour une modale plein écran, insérer un `<div class="sisme-admin-modal">` à la racine du body.
- Pour une modale sur une ligne de tableau, remplacer le `<tr>` par un `<td colspan="X">` contenant le contenu de la modale.

#### Exemple d'intégration sur une ligne de tableau
```html
<tr id="game-row-123">
  <td colspan="5" style="position:relative; background:rgba(0,0,0,0.9);">
    <div style="display:flex;align-items:center;justify-content:center;min-height:60px;padding:20px;">
      <div class="sisme-admin-modal-content">
        <div class="sisme-admin-modal-header">
          <h3 class="sisme-admin-modal-title">🚫 Dépublier le jeu</h3>
          <p class="sisme-admin-modal-subtitle">Confirmer la dépublication de "Nom du jeu"</p>
        </div>
        <div class="sisme-admin-modal-body">
          <p><strong>Cette action va :</strong></p>
          <ul>
            <li>• Rendre le jeu inaccessible publiquement</li>
            <li>• Mettre le post WordPress en brouillon</li>
            <li>• Conserver toutes les données (action réversible)</li>
          </ul>
        </div>
        <div class="sisme-admin-modal-actions">
          <button class="sisme-admin-modal-btn sisme-admin-modal-btn-cancel">Annuler</button>
          <button class="sisme-admin-modal-btn sisme-admin-modal-btn-confirm">Dépublier</button>
        </div>
      </div>
    </div>
  </td>
</tr>
```

---

## 🖼️ Miniatures, Overlays & Groupes

Le système gère les miniatures d'images, les overlays emoji, et les groupes de miniatures empilées.

### Classes principales
```css
.sisme-admin-thumb                /* Miniature de base */
.sisme-admin-thumb-sm             /* Petite miniature */
.sisme-admin-thumb-md             /* Moyenne (défaut) */
.sisme-admin-thumb-lg             /* Grande miniature */
.sisme-admin-thumb-overlay        /* Overlay emoji au survol */
.sisme-admin-thumb-overlay-permanent /* Overlay emoji permanent */
.sisme-admin-thumb-stack          /* Groupe de miniatures empilées */
.sisme-admin-thumb-group          /* Groupe de miniatures alignées */
```

### Utilisation des overlays emoji
- Ajouter `data-overlay="🔗"` sur la miniature pour afficher un emoji au survol.
- Utiliser les variantes `-permanent`, `-top-left`, `-bottom-right`, `-badge` pour des positions différentes.

#### Exemple
```html
<div class="sisme-admin-thumb-group">
  <a class="sisme-admin-thumb sisme-admin-thumb-sm sisme-admin-thumb-overlay" data-overlay="🔗" href="#"><img src="..." /></a>
  <a class="sisme-admin-thumb sisme-admin-thumb-sm sisme-admin-thumb-overlay-permanent" data-overlay="⭐" href="#"><img src="..." /></a>
</div>
```

---

## 🛠️ Utilitaires avancés et classes spécifiques

- `.sisme-admin-dev-details-container` : conteneur sombre pour détails développeur
- `.sisme-admin-detail-label` : label de champ dans les détails
- `.sisme-admin-link` : lien stylisé admin
- `.sisme-admin-notes-box` : encadré pour notes admin
- `.sisme-inline-form` : formulaire inline
- `.sisme-admin-actions` : groupe d'actions admin
- `.sisme-admin-empty-state` : état vide stylisé
- `.sisme-admin-section-title` : titre de section stylisé

---

## 🌓 Mode sombre automatique

Le CSS gère automatiquement le mode sombre via `@media (prefers-color-scheme: dark)`.

---

## 🚀 Guide d'Usage

### Exemple 1: Carte de Statistique
```html
<div class="sisme-admin-stats">
    <div class="sisme-admin-stat-card sisme-admin-stat-card-success">
        <span class="sisme-admin-stat-number">42</span>
        <span class="sisme-admin-stat-label">✅ Jeux approuvés</span>
    </div>
    <div class="sisme-admin-stat-card sisme-admin-stat-card-warning">
        <span class="sisme-admin-stat-number">7</span>
        <span class="sisme-admin-stat-label">⏳ En attente</span>
    </div>
</div>
```

### Exemple 2: Layout avec Grille
```html
<div class="sisme-admin-container">
    <h2 class="sisme-admin-title">Gestion des Jeux</h2>
    
    <div class="sisme-admin-grid-2">
        <div class="sisme-admin-card">
            <div class="sisme-admin-card-header">
                <h3 class="sisme-admin-heading">Soumissions récentes</h3>
            </div>
            <p class="sisme-admin-comment">Les derniers jeux soumis...</p>
        </div>
        
        <div class="sisme-admin-card">
            <div class="sisme-admin-card-header">
                <h3 class="sisme-admin-heading">Actions rapides</h3>
            </div>
            <div class="sisme-admin-flex">
                <button class="sisme-admin-btn sisme-admin-btn-primary">Nouveau</button>
                <button class="sisme-admin-btn sisme-admin-btn-secondary">Paramètres</button>
            </div>
        </div>
    </div>
</div>
```

### Exemple 3: Layout Vertical avec Espacement Automatique
```html
<div class="sisme-admin-container">
    <h1 class="sisme-admin-title">Interface d'Administration</h1>
    
    <div class="sisme-admin-alert sisme-admin-alert-info">
        <p>Information importante sur cette page</p>
    </div>
    
    <div class="sisme-admin-layout">
        <!-- Espacement automatique de 24px entre chaque élément -->
        <div class="sisme-admin-card">
            <h2 class="sisme-admin-heading">Section 1</h2>
            <p>Contenu de la première section</p>
        </div>
        
        <div class="sisme-admin-card">
            <h2 class="sisme-admin-heading">Section 2</h2>
            <p>Contenu de la deuxième section</p>
        </div>
        
        <div class="sisme-admin-stats">
            <div class="sisme-admin-stat-card">
                <span class="sisme-admin-stat-number">123</span>
                <span class="sisme-admin-stat-label">Statistique</span>
            </div>
        </div>
    </div>
</div>
```

### Exemple 4: Alerte avec Badge
```html
<div class="sisme-admin-alert sisme-admin-alert-success">
    <span class="sisme-admin-badge sisme-admin-badge-success">✅ Succès</span>
    Le jeu a été publié avec succès !
</div>
```

### Exemple 5: Conteneur Scrollable avec Items
```html
<div class="sisme-admin-scrollable-container">
    <div class="sisme-admin-smooth-transition sisme-admin-item-neutral sisme-admin-p-sm sisme-admin-border sisme-admin-rounded sisme-admin-mb-sm">
        <span class="sisme-admin-text-black sisme-admin-font-medium">Nom du jeu normal</span>
    </div>
    <div class="sisme-admin-smooth-transition sisme-admin-item-purple sisme-admin-p-sm sisme-admin-border sisme-admin-rounded sisme-admin-mb-sm">
        <span class="sisme-admin-text-black sisme-admin-font-medium">Jeu featured (violet)</span>
    </div>
</div>
```

### Exemple 6: Icônes et Alertes Spécialisées
```html
<!-- Alerte violette avec icône -->
<div class="sisme-admin-alert sisme-admin-alert-purple">
    <div class="sisme-admin-flex">
        <div class="sisme-admin-icon-lg">💜</div>
        <span>Ce jeu est maintenant en vedette !</span>
    </div>
</div>

<!-- Avertissement avec grande icône -->
<div class="sisme-admin-alert sisme-admin-alert-danger">
    <div class="sisme-admin-flex">
        <div class="sisme-admin-text-danger sisme-admin-icon-lg">⚠️</div>
        <div>
            <strong>Attention !</strong><br>
            Cette action est irréversible.
        </div>
    </div>
</div>
```

### Exemple 7: Inspecteur de Données (Code et Debug)
```html
<div class="sisme-admin-card">
    <div class="sisme-admin-card-header">
        <h3 class="sisme-admin-heading">
            🔧 Métadonnée : <code class="sisme-admin-bg-blue-light sisme-admin-text-blue-dark sisme-admin-p-sm sisme-admin-rounded">game_data</code>
        </h3>
    </div>
    
    <div class="sisme-admin-grid-2">
        <div>
            <h5 class="sisme-admin-heading">📋 Structure des données</h5>
            <pre class="sisme-admin-pre-code" style="max-height: 300px; overflow-y: auto;">
Array(
    [name] => "Hollow Knight"
    [description] => "Jeu d'aventure..."
    [genres] => Array(...)
)
            </pre>
        </div>
        
        <div>
            <h5 class="sisme-admin-heading">🔧 Représentation sérialisée</h5>
            <pre class="sisme-admin-pre-code sisme-admin-pre-code-dark" style="max-height: 300px; overflow-y: auto;">
a:3:{s:4:"name";s:13:"Hollow Knight";s:11:"description";s:17:"Jeu d'aventure...";s:6:"genres";a:2:{...}}
            </pre>
        </div>
    </div>
    
    <div class="sisme-admin-flex sisme-admin-mt-md">
        <span class="sisme-admin-badge sisme-admin-badge-info">Taille : 245 caractères</span>
        <span class="sisme-admin-badge sisme-admin-badge-success">3 éléments</span>
        <span class="sisme-admin-badge sisme-admin-badge-warning">Données sérialisées</span>
    </div>
</div>
```

---

## 📋 Convention de Nommage

### Préfixe Obligatoire
**Toutes les classes commencent par `sisme-admin-`** pour éviter les conflits avec :
- Le frontend du site
- WordPress core
- Autres plugins

### Structure des Noms
```
sisme-admin-[composant]-[variante]-[taille/état]

Exemples :
- sisme-admin-btn-primary-lg
- sisme-admin-card-dark
- sisme-admin-text-success
- sisme-admin-m-md
```

---

## 🔧 Maintenance et Evolution

### Ajout de Nouvelles Couleurs
1. Ajouter la variable dans `:root`
2. Créer les classes utilitaires correspondantes
3. Décliner en versions light/dark si nécessaire

### Modification des Espacements
Les espacements sont centralisés dans les variables CSS. Une modification se répercute automatiquement partout.

### Responsive Design
Le système est **mobile-first** :
- Grilles se replient en 1 colonne sur mobile
- Espacements s'adaptent automatiquement
- Composants restent lisibles sur petits écrans

---

## ⚠️ Points d'Attention

### Performance
- Utilisez `!important` avec parcimonie (uniquement dans les utilitaires)
- Les variables CSS sont bien supportées par les navigateurs modernes
- Le fichier est organisé pour une compression optimale

### Compatibilité
- **WordPress 5.0+** (support des CSS Custom Properties)
- **Navigateurs modernes** (IE11+ avec fallbacks si nécessaire)

### Cohérence
- **Toujours utiliser les classes du système** plutôt que du CSS inline
- **Respecter la hiérarchie des couleurs sémantiques**
- **Utiliser les espacements standardisés**

---

## 🎯 Objectifs Atteints

✅ **Cohérence** : Plus de styles fragmentés  
✅ **Maintenabilité** : Variables centralisées  
✅ **Productivité** : Classes prêtes à l'emploi  
✅ **Responsive** : Adaptation automatique mobile  
✅ **Accessibilité** : Contrastes et tailles respectés  
✅ **Performance** : CSS optimisé et structuré  

Le système CSS `CSS-admin-shared.css` transforme le développement des interfaces admin en fournissant une base solide, cohérente et extensible pour toutes les pages du plugin **sisme-games-editor**.
