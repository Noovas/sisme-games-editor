<?php
/**
 * File: /sisme-games-editor/admin/forms/create-fiche.php
 * Formulaire de création d'une fiche de jeu
 */

// Sécurité : Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les catégories jeux existantes pour le sélecteur
$jeux_categories = get_categories(array(
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
));

$available_jeux_categories = array();
foreach ($jeux_categories as $category) {
    if (strpos($category->slug, 'jeux-') === 0) {
        $available_jeux_categories[] = $category;
    }
}

// Récupérer les étiquettes existantes
$existing_tags = get_tags(array(
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
));

// Récupérer les développeurs/éditeurs existants (depuis les meta posts)
global $wpdb;
$existing_devs = $wpdb->get_col("
    SELECT DISTINCT meta_value 
    FROM {$wpdb->postmeta} 
    WHERE meta_key = '_sisme_developpeurs' 
    AND meta_value != ''
");

$existing_editors = $wpdb->get_col("
    SELECT DISTINCT meta_value 
    FROM {$wpdb->postmeta} 
    WHERE meta_key = '_sisme_editeurs' 
    AND meta_value != ''
");
?>

<div class="sisme-games-container">
    <div class="sisme-games-header">
        <h1 class="sisme-games-title">
            <span class="dashicons dashicons-plus-alt" style="margin-right: 12px; font-size: 28px; vertical-align: middle;"></span>
            Créer une fiche de jeu
        </h1>
        <p class="sisme-games-subtitle">Remplissez les informations pour générer automatiquement une fiche complète</p>
    </div>
    
    <div class="sisme-games-content">
        <form id="sisme-create-fiche-form" class="sisme-form" method="post" action="">
            <?php wp_nonce_field('sisme_create_fiche', 'sisme_create_fiche_nonce'); ?>
            
            <div class="sisme-form-section">
                <h3 class="sisme-section-title">
                    <span class="dashicons dashicons-games"></span>
                    Informations principales
                </h3>
                
                <div class="sisme-form-grid">
                    <!-- Titre du jeu -->
                    <div class="sisme-field-group sisme-field-full">
                        <label for="game_title" class="sisme-field-label">
                            <span class="dashicons dashicons-edit"></span>
                            Titre du jeu *
                        </label>
                        <input type="text" 
                               id="game_title" 
                               name="game_title" 
                               class="sisme-field-input sisme-input-large" 
                               placeholder="Ex: Lost in Random: The Eternal Die"
                               required>
                        <p class="sisme-field-description">Ce sera le titre de la page et de l'article</p>
                    </div>
                    
                    <!-- Image de mise en avant -->
                    <div class="sisme-field-group sisme-field-full">
                        <label class="sisme-field-label">
                            <span class="dashicons dashicons-format-image"></span>
                            Image de mise en avant
                        </label>
                        <div class="sisme-media-uploader">
                            <div class="sisme-media-preview" id="featured-image-preview">
                                <div class="sisme-media-placeholder">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    <p>Cliquez pour sélectionner une image</p>
                                </div>
                            </div>
                            <button type="button" class="sisme-btn-secondary" id="select-featured-image">
                                <span class="dashicons dashicons-admin-media"></span>
                                Choisir une image
                            </button>
                            <input type="hidden" id="featured_image_id" name="featured_image_id" value="">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="sisme-form-section">
                <h3 class="sisme-section-title">
                    <span class="dashicons dashicons-category"></span>
                    Classification
                </h3>
                
                <div class="sisme-form-grid">
                    <!-- Catégories de jeux -->
                    <div class="sisme-field-group">
                        <label class="sisme-field-label">
                            <span class="dashicons dashicons-category"></span>
                            Catégories de jeux *
                        </label>
                        <div class="sisme-categories-selector">
                            <?php foreach ($available_jeux_categories as $category) : ?>
                                <label class="sisme-checkbox-label">
                                    <input type="checkbox" name="game_categories[]" value="<?php echo $category->term_id; ?>">
                                    <span><?php echo esc_html(str_replace('jeux-', '', $category->name)); ?></span>
                                </label>
                            <?php endforeach; ?>
                            
                            <!-- Ajouter nouvelle catégorie -->
                            <div class="sisme-add-category">
                                <input type="text" 
                                       id="new_category_name" 
                                       placeholder="Nouvelle catégorie..." 
                                       class="sisme-field-input">
                                <button type="button" id="add-category-btn" class="sisme-btn-mini">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Étiquettes -->
                    <div class="sisme-field-group">
                        <label class="sisme-field-label">
                            <span class="dashicons dashicons-tag"></span>
                            Étiquettes (noms des jeux)
                        </label>
                        <div class="sisme-tags-selector">
                            <input type="text" 
                                   id="tags_input" 
                                   name="game_tags" 
                                   class="sisme-field-input" 
                                   placeholder="Tapez et appuyez sur Entrée...">
                            <div class="sisme-tags-list" id="selected-tags"></div>
                        </div>
                        <p class="sisme-field-description">Les étiquettes servent à regrouper les actualités par jeu</p>
                    </div>
                    
                    <!-- Mode de jeu -->
                    <div class="sisme-field-group">
                        <label class="sisme-field-label">
                            <span class="dashicons dashicons-groups"></span>
                            Mode de jeu *
                        </label>
                        <div class="sisme-radio-group">
                            <label class="sisme-radio-label">
                                <input type="checkbox" name="game_modes[]" value="solo">
                                <span>Solo</span>
                            </label>
                            <label class="sisme-radio-label">
                                <input type="checkbox" name="game_modes[]" value="multijoueur">
                                <span>Multijoueur</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="sisme-form-section">
                <h3 class="sisme-section-title">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    Détails du jeu
                </h3>
                
                <div class="sisme-form-grid">
                    <!-- Date de sortie -->
                    <div class="sisme-field-group">
                        <label for="release_date" class="sisme-field-label">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            Date de sortie
                        </label>
                        <input type="date" 
                               id="release_date" 
                               name="release_date" 
                               class="sisme-field-input">
                    </div>
                    
                    <!-- Développeurs -->
                    <div class="sisme-field-group">
                        <label class="sisme-field-label">
                            <span class="dashicons dashicons-admin-users"></span>
                            Développeur(s)
                        </label>
                        <div class="sisme-dev-editor-section">
                            <div id="developers-list" class="sisme-dev-list">
                                <!-- Les développeurs ajoutés apparaîtront ici -->
                            </div>
                            <div class="sisme-add-dev">
                                <input type="text" 
                                       id="dev_name" 
                                       placeholder="Nom du développeur..." 
                                       class="sisme-field-input">
                                <input type="url" 
                                       id="dev_url" 
                                       placeholder="Site web (optionnel)..." 
                                       class="sisme-field-input">
                                <button type="button" id="add-developer-btn" class="sisme-btn-mini">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Éditeurs -->
                    <div class="sisme-field-group">
                        <label class="sisme-field-label">
                            <span class="dashicons dashicons-building"></span>
                            Éditeur(s)
                        </label>
                        <div class="sisme-dev-editor-section">
                            <div id="editors-list" class="sisme-dev-list">
                                <!-- Les éditeurs ajoutés apparaîtront ici -->
                            </div>
                            <div class="sisme-add-dev">
                                <input type="text" 
                                       id="editor_name" 
                                       placeholder="Nom de l'éditeur..." 
                                       class="sisme-field-input">
                                <input type="url" 
                                       id="editor_url" 
                                       placeholder="Site web (optionnel)..." 
                                       class="sisme-field-input">
                                <button type="button" id="add-editor-btn" class="sisme-btn-mini">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="sisme-form-section">
                <h3 class="sisme-section-title">
                    <span class="dashicons dashicons-media-text"></span>
                    Contenu
                </h3>
                
                <div class="sisme-form-grid">
                    <!-- Description -->
                    <div class="sisme-field-group sisme-field-full">
                        <label for="game_description" class="sisme-field-label">
                            <span class="dashicons dashicons-text-page"></span>
                            Description du jeu *
                        </label>
                        <textarea id="game_description" 
                                  name="game_description" 
                                  class="sisme-field-textarea" 
                                  rows="6" 
                                  placeholder="Décrivez le jeu, son gameplay, son univers..."
                                  required></textarea>
                        <p class="sisme-field-description">Cette description sera utilisée dans le template de la fiche</p>
                    </div>
                    
                    <!-- Trailer -->
                    <div class="sisme-field-group">
                        <label for="trailer_url" class="sisme-field-label">
                            <span class="dashicons dashicons-video-alt3"></span>
                            Lien du trailer
                        </label>
                        <input type="url" 
                               id="trailer_url" 
                               name="trailer_url" 
                               class="sisme-field-input" 
                               placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                    
                    <!-- Liens boutiques -->
                    <div class="sisme-field-group">
                        <label class="sisme-field-label">
                            <span class="dashicons dashicons-store"></span>
                            Liens boutiques
                        </label>
                        <div class="sisme-store-links">
                            <input type="url" 
                                   name="steam_url" 
                                   placeholder="Lien Steam..." 
                                   class="sisme-field-input sisme-store-input">
                            <input type="url" 
                                   name="epic_url" 
                                   placeholder="Lien Epic Games..." 
                                   class="sisme-field-input sisme-store-input">
                            <input type="url" 
                                   name="gog_url" 
                                   placeholder="Lien GOG..." 
                                   class="sisme-field-input sisme-store-input">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="sisme-form-actions">
                <a href="<?php echo admin_url('admin.php?page=sisme-games-fiches'); ?>" class="sisme-btn-secondary">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    Annuler
                </a>
                <button type="button" id="continue-to-content" class="sisme-btn sisme-btn-large">
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                    Continuer vers le contenu
                </button>
            </div>
        </form>
        
        <!-- Étape 2 : Construction du contenu (initialement masquée) -->
        <div id="content-builder-section" class="sisme-content-builder" style="display: none;">
            <div class="sisme-builder-header">
                <h2 class="sisme-builder-title">
                    <span class="dashicons dashicons-edit-page"></span>
                    Construction du contenu
                </h2>
                <p class="sisme-builder-subtitle">Créez le contenu de votre fiche avec des sections personnalisables</p>
            </div>
            
            <div class="sisme-builder-container">
                <!-- Barre d'outils -->
                <div class="sisme-builder-toolbar">
                    <button type="button" class="sisme-add-section-btn" data-type="content">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Ajouter une section
                    </button>
                    
                    <div class="sisme-builder-actions">
                        <button type="button" class="sisme-preview-btn">
                            <span class="dashicons dashicons-visibility"></span>
                            Aperçu
                        </button>
                        <button type="button" class="sisme-save-draft-btn">
                            <span class="dashicons dashicons-saved"></span>
                            Sauvegarder le brouillon
                        </button>
                    </div>
                </div>
                
                <!-- Zone de construction -->
                <div class="sisme-sections-container" id="sections-container">
                    <!-- Les sections seront ajoutées dynamiquement ici -->
                    <div class="sisme-empty-state">
                        <div class="sisme-empty-icon">
                            <span class="dashicons dashicons-edit-page"></span>
                        </div>
                        <h3>Commencez à construire votre fiche</h3>
                        <p>Ajoutez votre première section pour commencer à créer le contenu de votre fiche de jeu.</p>
                        <button type="button" class="sisme-btn sisme-add-first-section">
                            <span class="dashicons dashicons-plus-alt"></span>
                            Ajouter la première section
                        </button>
                    </div>
                </div>
                
                <!-- Actions finales -->
                <div class="sisme-builder-final-actions" style="display: none;">
                    <button type="button" class="sisme-btn-secondary" id="back-to-meta">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        Retour aux informations
                    </button>
                    <button type="button" class="sisme-btn sisme-btn-large" id="create-fiche-final">
                        <span class="dashicons dashicons-yes-alt"></span>
                        Créer la fiche complète
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template de section (caché) -->
<script type="text/template" id="section-template">
    <div class="sisme-section" data-section-id="{{SECTION_ID}}">
        <div class="sisme-section-header">
            <div class="sisme-section-drag">
                <span class="dashicons dashicons-menu"></span>
            </div>
            <div class="sisme-section-type">
                <span class="dashicons dashicons-edit-page"></span>
                Section {{SECTION_NUMBER}}
            </div>
            <div class="sisme-section-actions">
                <button type="button" class="sisme-section-collapse">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
                <button type="button" class="sisme-section-duplicate">
                    <span class="dashicons dashicons-admin-page"></span>
                </button>
                <button type="button" class="sisme-section-delete">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
        </div>
        
        <div class="sisme-section-content">
            <!-- Titre avec sélecteur d'emoji -->
            <div class="sisme-field-group">
                <label class="sisme-field-label">
                    <span class="dashicons dashicons-heading"></span>
                    Titre de la section
                </label>
                <div class="sisme-title-input-group">
                    <div class="sisme-emoji-selector">
                        <button type="button" class="sisme-emoji-trigger" data-emoji="">
                            <span class="sisme-emoji-display">😀</span>
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                        </button>
                        <div class="sisme-emoji-dropdown">
                            <div class="sisme-emoji-search">
                                <input type="text" placeholder="Rechercher un emoji..." class="sisme-emoji-search-input">
                            </div>
                            <div class="sisme-emoji-categories">
                                <button type="button" class="sisme-emoji-cat active" data-category="gaming">🎮</button>
                                <button type="button" class="sisme-emoji-cat" data-category="faces">😊</button>
                                <button type="button" class="sisme-emoji-cat" data-category="nature">🌟</button>
                                <button type="button" class="sisme-emoji-cat" data-category="objects">⚔️</button>
                                <button type="button" class="sisme-emoji-cat" data-category="symbols">✨</button>
                            </div>
                            <div class="sisme-emoji-grid" data-category="gaming">
                                <!-- Emojis gaming -->
                                <span class="sisme-emoji-item" data-emoji="🎮">🎮</span>
                                <span class="sisme-emoji-item" data-emoji="🕹️">🕹️</span>
                                <span class="sisme-emoji-item" data-emoji="🎯">🎯</span>
                                <span class="sisme-emoji-item" data-emoji="⚔️">⚔️</span>
                                <span class="sisme-emoji-item" data-emoji="🏰">🏰</span>
                                <span class="sisme-emoji-item" data-emoji="🐉">🐉</span>
                                <span class="sisme-emoji-item" data-emoji="👾">👾</span>
                                <span class="sisme-emoji-item" data-emoji="🚀">🚀</span>
                                <span class="sisme-emoji-item" data-emoji="💎">💎</span>
                                <span class="sisme-emoji-item" data-emoji="⭐">⭐</span>
                                <span class="sisme-emoji-item" data-emoji="🔥">🔥</span>
                                <span class="sisme-emoji-item" data-emoji="💯">💯</span>
                                <span class="sisme-emoji-item" data-emoji="🏆">🏆</span>
                                <span class="sisme-emoji-item" data-emoji="⚡">⚡</span>
                                <span class="sisme-emoji-item" data-emoji="🎊">🎊</span>
                                <span class="sisme-emoji-item" data-emoji="🎁">🎁</span>
                            </div>
                            <div class="sisme-emoji-grid" data-category="faces" style="display: none;">
                                <!-- Emojis visages -->
                                <span class="sisme-emoji-item" data-emoji="😀">😀</span>
                                <span class="sisme-emoji-item" data-emoji="😃">😃</span>
                                <span class="sisme-emoji-item" data-emoji="😄">😄</span>
                                <span class="sisme-emoji-item" data-emoji="😁">😁</span>
                                <span class="sisme-emoji-item" data-emoji="😆">😆</span>
                                <span class="sisme-emoji-item" data-emoji="😅">😅</span>
                                <span class="sisme-emoji-item" data-emoji="🤣">🤣</span>
                                <span class="sisme-emoji-item" data-emoji="😂">😂</span>
                                <span class="sisme-emoji-item" data-emoji="😊">😊</span>
                                <span class="sisme-emoji-item" data-emoji="😇">😇</span>
                                <span class="sisme-emoji-item" data-emoji="😍">😍</span>
                                <span class="sisme-emoji-item" data-emoji="🤩">🤩</span>
                                <span class="sisme-emoji-item" data-emoji="😎">😎</span>
                                <span class="sisme-emoji-item" data-emoji="🤯">🤯</span>
                                <span class="sisme-emoji-item" data-emoji="😤">😤</span>
                                <span class="sisme-emoji-item" data-emoji="🥳">🥳</span>
                            </div>
                            <div class="sisme-emoji-grid" data-category="nature" style="display: none;">
                                <!-- Emojis nature -->
                                <span class="sisme-emoji-item" data-emoji="🌟">🌟</span>
                                <span class="sisme-emoji-item" data-emoji="✨">✨</span>
                                <span class="sisme-emoji-item" data-emoji="💫">💫</span>
                                <span class="sisme-emoji-item" data-emoji="🌙">🌙</span>
                                <span class="sisme-emoji-item" data-emoji="☀️">☀️</span>
                                <span class="sisme-emoji-item" data-emoji="🌈">🌈</span>
                                <span class="sisme-emoji-item" data-emoji="🔮">🔮</span>
                                <span class="sisme-emoji-item" data-emoji="💎">💎</span>
                                <span class="sisme-emoji-item" data-emoji="🌸">🌸</span>
                                <span class="sisme-emoji-item" data-emoji="🌺">🌺</span>
                                <span class="sisme-emoji-item" data-emoji="🌻">🌻</span>
                                <span class="sisme-emoji-item" data-emoji="🌹">🌹</span>
                                <span class="sisme-emoji-item" data-emoji="🍄">🍄</span>
                                <span class="sisme-emoji-item" data-emoji="🌿">🌿</span>
                                <span class="sisme-emoji-item" data-emoji="🌱">🌱</span>
                                <span class="sisme-emoji-item" data-emoji="🔥">🔥</span>
                            </div>
                            <div class="sisme-emoji-grid" data-category="objects" style="display: none;">
                                <!-- Emojis objets -->
                                <span class="sisme-emoji-item" data-emoji="⚔️">⚔️</span>
                                <span class="sisme-emoji-item" data-emoji="🗡️">🗡️</span>
                                <span class="sisme-emoji-item" data-emoji="🏹">🏹</span>
                                <span class="sisme-emoji-item" data-emoji="🛡️">🛡️</span>
                                <span class="sisme-emoji-item" data-emoji="🔧">🔧</span>
                                <span class="sisme-emoji-item" data-emoji="🔨">🔨</span>
                                <span class="sisme-emoji-item" data-emoji="⚡">⚡</span>
                                <span class="sisme-emoji-item" data-emoji="🔋">🔋</span>
                                <span class="sisme-emoji-item" data-emoji="💻">💻</span>
                                <span class="sisme-emoji-item" data-emoji="🖥️">🖥️</span>
                                <span class="sisme-emoji-item" data-emoji="📱">📱</span>
                                <span class="sisme-emoji-item" data-emoji="🎧">🎧</span>
                                <span class="sisme-emoji-item" data-emoji="🎪">🎪</span>
                                <span class="sisme-emoji-item" data-emoji="🎭">🎭</span>
                                <span class="sisme-emoji-item" data-emoji="🎨">🎨</span>
                                <span class="sisme-emoji-item" data-emoji="📚">📚</span>
                            </div>
                            <div class="sisme-emoji-grid" data-category="symbols" style="display: none;">
                                <!-- Emojis symboles -->
                                <span class="sisme-emoji-item" data-emoji="✨">✨</span>
                                <span class="sisme-emoji-item" data-emoji="💫">💫</span>
                                <span class="sisme-emoji-item" data-emoji="⭐">⭐</span>
                                <span class="sisme-emoji-item" data-emoji="🌟">🌟</span>
                                <span class="sisme-emoji-item" data-emoji="💯">💯</span>
                                <span class="sisme-emoji-item" data-emoji="💥">💥</span>
                                <span class="sisme-emoji-item" data-emoji="💢">💢</span>
                                <span class="sisme-emoji-item" data-emoji="❤️">❤️</span>
                                <span class="sisme-emoji-item" data-emoji="💜">💜</span>
                                <span class="sisme-emoji-item" data-emoji="💙">💙</span>
                                <span class="sisme-emoji-item" data-emoji="💚">💚</span>
                                <span class="sisme-emoji-item" data-emoji="🧡">🧡</span>
                                <span class="sisme-emoji-item" data-emoji="💛">💛</span>
                                <span class="sisme-emoji-item" data-emoji="🖤">🖤</span>
                                <span class="sisme-emoji-item" data-emoji="🤍">🤍</span>
                                <span class="sisme-emoji-item" data-emoji="✅">✅</span>
                            </div>
                            <div class="sisme-emoji-actions">
                                <button type="button" class="sisme-emoji-remove">Supprimer l'emoji</button>
                            </div>
                        </div>
                    </div>
                    <input type="text" 
                           class="sisme-field-input sisme-section-title-input" 
                           placeholder="Ex: Un jeu au hasard et défi tactique"
                           data-section-id="{{SECTION_ID}}">
                </div>
            </div>
            
            <!-- Éditeur de texte -->
            <div class="sisme-field-group">
                <label class="sisme-field-label">
                    <span class="dashicons dashicons-text-page"></span>
                    Contenu de la section
                </label>
                <div class="sisme-text-editor">
                    <div class="sisme-editor-toolbar">
                        <button type="button" class="sisme-format-btn" data-format="h1" title="Titre principal">
                            <strong>H1</strong>
                        </button>
                        <button type="button" class="sisme-format-btn" data-format="h2" title="Titre secondaire">
                            <strong>H2</strong>
                        </button>
                        <button type="button" class="sisme-format-btn" data-format="h3" title="Sous-titre">
                            <strong>H3</strong>
                        </button>
                        <div class="sisme-toolbar-separator"></div>
                        <button type="button" class="sisme-format-btn" data-format="strong" title="Gras">
                            <span class="dashicons dashicons-editor-bold"></span>
                        </button>
                        <button type="button" class="sisme-format-btn" data-format="em" title="Italique">
                            <span class="dashicons dashicons-editor-italic"></span>
                        </button>
                        <div class="sisme-toolbar-separator"></div>
                        <button type="button" class="sisme-format-btn" data-format="p" title="Paragraphe">
                            <span class="dashicons dashicons-editor-paragraph"></span>
                        </button>
                    </div>
                    <textarea class="sisme-content-editor" 
                              placeholder="Rédigez le contenu de cette section..."
                              data-section-id="{{SECTION_ID}}"></textarea>
                </div>
            </div>
            
            <!-- Sélecteur d'image -->
            <div class="sisme-field-group">
                <label class="sisme-field-label">
                    <span class="dashicons dashicons-format-image"></span>
                    Image de la section (optionnelle)
                </label>
                <div class="sisme-section-media">
                    <div class="sisme-media-preview-small" data-section-id="{{SECTION_ID}}">
                        <div class="sisme-media-placeholder-small">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <p>Ajouter une image</p>
                        </div>
                    </div>
                    <div class="sisme-media-actions">
                        <button type="button" class="sisme-btn-secondary sisme-select-section-image" data-section-id="{{SECTION_ID}}">
                            <span class="dashicons dashicons-admin-media"></span>
                            Choisir une image
                        </button>
                        <button type="button" class="sisme-btn-danger sisme-remove-section-image" data-section-id="{{SECTION_ID}}" style="display: none;">
                            <span class="dashicons dashicons-trash"></span>
                            Supprimer
                        </button>
                    </div>
                    <input type="hidden" class="sisme-section-image-id" data-section-id="{{SECTION_ID}}" value="">
                </div>
            </div>
        </div>
    </div>
</script>