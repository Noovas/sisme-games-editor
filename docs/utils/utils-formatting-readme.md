# Utils Functions Registry

## utils-formatting.php

**Constantes:**
```php
DEFAULT_TRUNCATE_LENGTH = 150
DEFAULT_DATE_FORMAT = 'j F Y'
DEFAULT_PLATEFORM_PC = '🖥️'
DEFAULT_PLATEFORM_CONSOLE = '🎮'
DEFAULT_PLATEFORM_MOBILE = '📱'
DEFAULT_PLATEFORM_WEB = '🌐'
```

<details>
<summary><code>truncate_smart($text, $max_length = 150)</code></summary>

```php
// Tronque intelligemment un texte sur les mots
// @param string $text - Texte à tronquer
// @param int $max_length - Longueur maximale
// @return string - Texte tronqué avec "..."
```
</details>

<details>
<summary><code>get_platform_icon($platform)</code></summary>

```php
// Obtient l'icône emoji d'une plateforme
// @param string $platform - Nom de la plateforme  
// @return string - Icône emoji (💻, 🎮, 📱, etc.)
```
</details>

<details>
<summary><code>build_css_class($base_class, $modifiers = [], $custom_class = '')</code></summary>

```php
// Génère classe CSS avec modificateurs BEM
// @param string $base_class - Classe de base
// @param array $modifiers - Modificateurs BEM
// @param string $custom_class - Classe custom
// @return string - Classes assemblées
```
</details>

<details>
<summary><code>format_release_date($release_date)</code></summary>

```php
// Format court: "15 déc 2024"
// @param string $release_date - Date YYYY-MM-DD
// @return string - Date formatée
```
</details>

<details>
<summary><code>format_release_date_long($release_date)</code></summary>

```php
// Format long: "15 décembre 2024"
// @param string $release_date - Date YYYY-MM-DD
// @return string - Date formatée
```
</details>

<details>
<summary><code>format_release_date_with_status($release_date, $show_status = false)</code></summary>

```php
// Date avec statut: "✅ 15 déc 2024"
// @param string $release_date - Date YYYY-MM-DD
// @param bool $show_status - Afficher icône statut
// @return string - Date avec statut optionnel
```
</details>