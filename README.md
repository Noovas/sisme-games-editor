# 📚 Sisme Games Editor - Documentation API REF

**Version:** 1.0.0 | **Status:** Production

Documentation technique condensée pour tous les modules du plugin Sisme Games Editor.

---

## 📂 Architecture & Documentation

```
📁 sisme-games-editor/
├── 👤 user/                          # Système utilisateur modulaire
│   ├── 📄 user-readme.md             # → [Master loader + sous-modules](docs/user/user-readme.md)
│   ├── 🔐 user-auth-readme.md        # → [Authentification & sécurité](docs/user/user-auth-readme.md)
│   ├── 👤 user-profile-readme.md     # → [Profil complet + avatar](docs/user/user-profile-readme.md)
│   ├── ⚙️ user-preferences-readme.md # → [Préférences gaming](docs/user/user-preferences-readme.md)
│   └── 📊 user-dashboard-readme.md   # → [Dashboard unifié](docs/user/user-dashboard-readme.md)
│
├── 🎴 cards/                         # Système de cartes de jeux
│   └── 📄 cards-readme.md           # → [Cartes, grilles, carrousels](docs/cards/cards-readme.md)
│
├── 🏷️ taxonomies/                   # Classification jeux & contenus
│   └── 📄 taxonomies-readme.md      # → [Genres, plateformes, modes](docs/taxonomies/taxonomies-readme.md)
│
├── 🎮 game-management/              # Gestion jeux & métadonnées
│   └── 📄 game-management-readme.md # → [CRUD jeux, validation](docs/game-management/game-management-readme.md)
│
└── 🔧 utils/                        # Utilitaires transversaux
    └── 📄 utils-readme.md           # → [Helpers, formatage, cache](docs/utils/utils-readme.md)
```

---

## 📖 Conventions

**Format :** Documentation technique condensée  
**Public :** Développeurs intégrant/étendant le plugin  
**Objectif :** Comprendre + utiliser en < 5 minutes

---

**🎯 Pour commencer :** Consultez [`👤 User`](docs/user/user-readme.md) ou [`🎴 Cards`](docs/cards/cards-readme.md)