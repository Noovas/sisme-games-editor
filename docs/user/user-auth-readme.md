# 👤 Module User Profile - API Reference

**Version:** 1.0.0  
**Status:** Production Ready  

## Architecture

```
includes/user/user-profile/
├── user-profile-loader.php     # Singleton loader
├── user-profile-handlers.php   # Logique métier et traitement
├── user-profile-forms.php      # Générateur de formulaires
├── user-profile-avatar.php     # Gestion avatar et bannière
├── user-profile-api.php        # Shortcodes et API publique
└── assets/
    ├── user-profile.css         # Styles profil
    └── user-profile.js          # Interactions JavaScript
```

## Classes & Methods

### `Sisme_User_Profile_Loader`

**Singleton Pattern**
```php
$loader = Sisme_User_Profile_Loader::get_instance();
```

**Methods:**
- `force_load_assets()` - Load CSS/JS manually
- `are_assets_loaded()` - Check if assets loaded
- `get_version()` - Get module version

### `Sisme_User_Profile_Handlers`

**Core Functions:**
```php
handle_profile_update($data)     // Process profile update, returns array|WP_Error
handle_preferences_update($data) // Update gaming preferences, returns array|WP_Error
```

**Session Management:**
```php
set_profile_message($message, $type)  // Store flash message
get_profile_message()                 // Retrieve & clear message
```

**User Meta Initialization:**
```php
get_default_user_meta()              // Default metadata structure
init_user_profile_meta($user_id)     // Initialize meta for new user
```

### `Sisme_User_Profile_Forms`

**Constructor:**
```php
new Sisme_User_Profile_Forms($components, $options)
```

**Components Available:**

#### Basic Info (3 components)
- `user_display_name` - Display name (text, required)
- `user_bio` - Biography (textarea, 500 chars max)
- `user_website` - Website URL (url, optional)

#### Gaming Preferences (3 components)
- `favorite_game_genres` - Favorite genres (checkbox_group)
- `skill_level` - Gaming skill level (select)
- `favorite_games_display` - Favorite games (display only)

#### Privacy Settings (3 components)
- `privacy_profile_public` - Public profile (checkbox)
- `privacy_show_stats` - Show gaming stats (checkbox)
- `privacy_allow_friend_requests` - Allow friend requests (checkbox)

**Options:**
- `type` - 'profile'|'preferences'
- `redirect_to` - Redirect URL after submit

**Methods:**
```php
$form->render()              // Output complete form HTML
$form->is_submitted()        // Check if form was submitted
$form->get_submitted_data()  // Get sanitized form data
$form->validate()           // Validate form data
```

### `Sisme_User_Profile_Avatar`

**Avatar Management:**
```php
handle_avatar_upload($files)       // Process avatar upload, returns array|WP_Error
delete_user_avatar($user_id)       // Delete user avatar, returns array|WP_Error
get_user_avatar_url($user_id, $size) // Get avatar URL or false
user_has_custom_avatar($user_id)   // Check if user has custom avatar
```

**Banner Management:**
```php
handle_banner_upload($files)       // Process banner upload, returns array|WP_Error
delete_user_banner($user_id)       // Delete user banner, returns array|WP_Error
get_user_banner_url($user_id, $size) // Get banner URL or false
user_has_custom_banner($user_id)   // Check if user has custom banner
```

**Image Info:**
```php
get_user_image_info($user_id, $type) // Get complete image info (avatar|banner)
get_avatar_urls($attachment_id)      // Get all avatar URLs by size
get_banner_urls($attachment_id)      // Get all banner URLs by size
```

**UI Rendering:**
```php
render_avatar_uploader($user_id, $options)  // Render avatar upload interface
render_banner_uploader($user_id, $options)  // Render banner upload interface
get_final_avatar_url($user_id, $size)       // Get final avatar (custom or Gravatar)
```

**Configuration:**
- Avatar sizes: thumbnail (150px), medium (300px), large (600px)
- Banner sizes: medium (800x200), large (1200x300), full (1920x480)
- Max file size: 2Mo
- Allowed types: JPG, PNG, GIF
- Avatar constraints: 100x100 to 2000x2000px
- Banner constraints: 400x100 to 2400x600px, ratio 2:1 to 8:1

### `Sisme_User_Profile_API`

**Shortcodes:**

#### `[sisme_user_profile_edit]`
```php
[sisme_user_profile_edit 
    sections="basic,gaming,privacy"  // Sections to display
    title="Modifier mon profil"
    show_avatar="true"
    show_banner="true"
    redirect_to="/profil/"
    container_class="custom-class"
]
```

#### `[sisme_user_avatar_uploader]`
```php
[sisme_user_avatar_uploader 
    size="large"                     // thumbnail, medium, large
    show_delete="true"
    show_info="false"
    container_class="custom-class"
]
```

#### `[sisme_user_banner_uploader]`
```php
[sisme_user_banner_uploader 
    size="large"                     // medium, large, full
    show_delete="true"
    show_info="false"
    container_class="custom-class"
]
```

#### `[sisme_user_preferences]`
```php
[sisme_user_preferences 
    title="Mes préférences"
    compact="false"
    container_class="custom-class"
]
```

#### `[sisme_user_profile_display]`
```php
[sisme_user_profile_display 
    user_id="123"                    // If empty = current user
    sections="basic,gaming"
    show_avatar="true"
    show_banner="true"
    show_stats="true"
    container_class="custom-class"
]
```

## User Meta Schema

### Profile Data
```php
'sisme_user_bio'                    => string(500)     // User biography
'sisme_user_avatar'                 => attachment_id   // Custom avatar
'sisme_user_banner'                 => attachment_id   // Custom banner
'sisme_user_profile_updated'        => mysql_date      // Last profile update
'sisme_user_avatar_updated'         => mysql_date      // Avatar update date
'sisme_user_banner_updated'         => mysql_date      // Banner update date
```

### Gaming Preferences
```php
'sisme_user_favorite_game_genres'   => array(term_ids) // Favorite genres (max 10)
'sisme_user_skill_level'            => string          // beginner|casual|experienced|hardcore|professional
'sisme_user_favorite_games'         => array(term_ids) // Favorite games (display only)
```

### Privacy Settings
```php
'sisme_user_privacy_profile_public'      => boolean // Public profile visibility
'sisme_user_privacy_show_stats'          => boolean // Show gaming statistics
'sisme_user_privacy_allow_friend_requests' => boolean // Allow friend requests
```

## AJAX Endpoints

### Profile Management
- `wp_ajax_sisme_update_profile` - Update profile data
- `wp_ajax_sisme_update_preferences` - Update gaming preferences

### Avatar Management
- `wp_ajax_sisme_upload_avatar` - Upload new avatar
- `wp_ajax_sisme_delete_avatar` - Delete current avatar

### Banner Management
- `wp_ajax_sisme_upload_banner` - Upload new banner
- `wp_ajax_sisme_delete_banner` - Delete current banner

**Security:** All endpoints require nonce validation and user authentication.

## Form Sections

### Basic Info Section
- **Components:** display_name, bio, website
- **Validation:** Required display_name (2-50 chars), bio max 500 chars, valid URL

### Gaming Section
- **Components:** favorite_genres, skill_level, favorite_games_display
- **Logic:** Max 10 genres, skill levels predefined, games display-only

### Privacy Section
- **Components:** profile_public, show_stats, allow_friend_requests
- **Defaults:** Private profile, show stats enabled, friend requests enabled

## CSS Classes

### Profile Edit Form
```css
.sisme-profile-edit-container
.sisme-profile-edit-header
.sisme-profile-edit-title
.sisme-profile-images-section
.sisme-profile-banner-section
.sisme-profile-avatar-section
.sisme-profile-form-section
```

### Profile Display
```css
.sisme-profile-display-container
.sisme-profile-display
.sisme-profile-display-banner
.sisme-profile-display-header
.sisme-profile-display-avatar
.sisme-profile-display-info
.sisme-profile-display-sections
```

### Form Elements
```css
.sisme-profile-form
.sisme-profile-section
.sisme-profile-section-title
.sisme-profile-field
.sisme-profile-label
.sisme-profile-input
.sisme-profile-textarea
.sisme-profile-select
.sisme-profile-checkbox
```

### Avatar/Banner Uploaders
```css
.sisme-avatar-uploader
.sisme-banner-uploader
.sisme-avatar-preview
.sisme-banner-preview
.sisme-avatar-placeholder
.sisme-banner-placeholder
.sisme-avatar-actions
.sisme-banner-actions
```

## JavaScript API

**Global Object:**
```javascript
window.sismeUserProfile = {
    ajax_url: string,
    nonce: string,
    messages: {
        profile_updated: string,
        avatar_updated: string,
        avatar_deleted: string,
        banner_updated: string,
        banner_deleted: string,
        error_upload: string,
        error_format: string,
        error_size: string
    },
    config: {
        max_file_size: 2097152,
        allowed_types: ['image/jpeg', 'image/png', 'image/gif'],
        avatar_sizes: object,
        banner_sizes: object
    }
}
```

## Action Hooks

```php
// Profile updates
do_action('sisme_profile_updated', $user_id, $updated_fields);
do_action('sisme_preferences_updated', $user_id, $preferences);

// Avatar management
do_action('sisme_avatar_uploaded', $user_id, $attachment_id);
do_action('sisme_avatar_deleted', $user_id, $attachment_id);

// Banner management
do_action('sisme_banner_uploaded', $user_id, $attachment_id);
do_action('sisme_banner_deleted', $user_id, $attachment_id);

// Meta initialization
do_action('sisme_user_profile_meta_initialized', $user_id);
```

## Dependencies

- WordPress 5.0+
- PHP 7.4+
- jQuery (WordPress core)
- Parent User module (user-auth)
- CSS variables from main plugin

## Technical Notes

**Form Processing:**
- Session-based flash messages
- Nonce validation on all forms
- Comprehensive data sanitization
- Graceful error handling

**Image Management:**
- WordPress Media Library integration
- Automatic thumbnail generation
- Smart cleanup of old images
- Fallback to Gravatar for avatars

**Security:**
- File type validation (MIME + extension)
- Image dimension validation
- File size limits (2Mo max)
- User permission checks
- XSS protection via `esc_*` functions

**Performance:**
- Conditional asset loading
- Optimized database queries
- Efficient image processing
- Smart caching strategies

## Usage Examples

### Basic Profile Edit Form
```php
// Simple profile editor
echo do_shortcode('[sisme_user_profile_edit sections="basic"]');

// Full editor with images
echo do_shortcode('[sisme_user_profile_edit show_banner="true" show_avatar="true"]');
```

### Standalone Components
```php
// Avatar uploader only
echo do_shortcode('[sisme_user_avatar_uploader size="large" show_info="true"]');

// Banner uploader only
echo do_shortcode('[sisme_user_banner_uploader show_delete="true"]');

// Preferences only
echo do_shortcode('[sisme_user_preferences title="Gaming Settings"]');
```

### Public Profile Display
```php
// Current user profile
echo do_shortcode('[sisme_user_profile_display]');

// Specific user profile
echo do_shortcode('[sisme_user_profile_display user_id="123" sections="basic,gaming"]');

// Public profile with banner
echo do_shortcode('[sisme_user_profile_display show_banner="true" show_avatar="true"]');
```

### Programmatic Usage
```php
// Check if user has custom images
$has_avatar = Sisme_User_Profile_Avatar::user_has_custom_avatar($user_id);
$has_banner = Sisme_User_Profile_Avatar::user_has_custom_banner($user_id);

// Get image URLs
$avatar_url = Sisme_User_Profile_Avatar::get_final_avatar_url($user_id, 'large');
$banner_url = Sisme_User_Profile_Avatar::get_user_banner_url($user_id, 'large');

// Create form programmatically
$form = new Sisme_User_Profile_Forms(['user_display_name', 'user_bio']);
echo $form->render();
```