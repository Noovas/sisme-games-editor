<?php
/**
 * File: /sisme-games-editor/includes/user/user-developer/submission-game/submission-game-renderer.php
 * Renderer pour l'interface d'édition de soumissions de jeux
 * 
 * RESPONSABILITÉ:
 * - Rendu de l'interface d'édition simple
 * - Formulaire nom + description + soumission
 * - Validation HTML5 et JavaScript
 * - Intégration avec le système de sauvegarde
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sisme_Submission_Game_Renderer {
    
    public static function render_editor($submission_id = null) {
        $submission = null;
        $game_data = [];
        
        if ($submission_id) {
            if (!class_exists('Sisme_Submission_Database')) {
                require_once SISME_GAMES_EDITOR_PLUGIN_DIR . 'includes/user/user-developer/submission/submission-database.php';
            }
            
            $submission = Sisme_Submission_Database::get_submission($submission_id);
            if ($submission && $submission->user_id == get_current_user_id()) {
                $game_data = $submission->game_data_decoded ?: [];
            }
        }
        
        $game_name = $game_data['game_name'] ?? '';
        $description = $game_data['description'] ?? '';
        $is_edit = !empty($submission_id);
        
        ob_start();
        ?>
        <div class="sisme-submission-game-editor" data-submission-id="<?php echo esc_attr($submission_id ?: ''); ?>">
            <div class="sisme-submission-game-header">
                <h3 class="sisme-submission-game-title">
                    <?php echo $is_edit ? '✏️ Modifier la soumission' : '🎮 Nouvelle soumission'; ?>
                </h3>
                <p class="sisme-submission-game-subtitle">
                    <?php echo $is_edit ? 'Modifiez les informations de votre jeu' : 'Créez votre soumission de jeu'; ?>
                </p>
            </div>
            
            <form id="sisme-submission-game-form" class="sisme-submission-game-form" novalidate>
                <div class="sisme-submission-game-fields">
                    
                    <div class="sisme-form-field">
                        <label for="game_name" class="sisme-form-label">
                            <span class="sisme-form-label-text">Nom du jeu</span>
                            <span class="sisme-form-required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="game_name" 
                            name="game_name" 
                            class="sisme-form-input"
                            value="<?php echo esc_attr($game_name); ?>"
                            placeholder="Entrez le nom de votre jeu"
                            required
                            minlength="3"
                            maxlength="100"
                            autocomplete="off"
                        >
                        <div class="sisme-form-field-feedback"></div>
                    </div>
                    
                    <div class="sisme-form-field">
                        <label for="description" class="sisme-form-label">
                            <span class="sisme-form-label-text">Description courte</span>
                            <span class="sisme-form-required">*</span>
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            class="sisme-form-textarea"
                            placeholder="Décrivez votre jeu en quelques mots..."
                            required
                            minlength="140"
                            maxlength="180"
                            rows="4"
                        ><?php echo esc_textarea($description); ?></textarea>
                        <div class="sisme-form-field-feedback">
                            <span class="sisme-form-char-count">
                                <span class="sisme-char-current"><?php echo strlen($description); ?></span>
                                <span class="sisme-char-separator"> / </span>
                                <span class="sisme-char-max">140-180</span>
                            </span>
                        </div>
                    </div>
                    
                </div>
                
                <div class="sisme-submission-game-actions">
                    <button type="button" class="sisme-btn sisme-btn-secondary" onclick="SismeSubmissionGame.saveAsDraft()">
                        💾 Sauvegarder brouillon
                    </button>
                    <button type="submit" class="sisme-btn sisme-btn-primary">
                        🚀 Soumettre pour validation
                    </button>
                </div>
                
                <div id="sisme-submission-game-feedback" class="sisme-form-feedback" style="display: none;"></div>
            </form>
            
            <div class="sisme-submission-game-info">
                <div class="sisme-submission-game-help">
                    <h4>💡 Conseils</h4>
                    <ul>
                        <li><strong>Nom du jeu :</strong> Choisissez un nom unique et mémorable</li>
                        <li><strong>Description :</strong> Entre 140 et 180 caractères pour donner envie</li>
                        <li><strong>Validation :</strong> Votre soumission sera examinée par notre équipe</li>
                    </ul>
                </div>
            </div>
            
            <div class="sisme-submission-game-auto-save">
                <span class="sisme-auto-save-indicator" style="display: none;">
                    💾 <span class="sisme-auto-save-text">Sauvegarde automatique...</span>
                </span>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
}