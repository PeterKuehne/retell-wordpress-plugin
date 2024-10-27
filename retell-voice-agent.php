<?php
/**
 * Plugin Name: Retell Voice Agent
 * Plugin URI: https://github.com/PeterKuehne/retell-voice-assistant
 * Description: Ein Voice Assistant Plugin basierend auf Retell AI
 * Version: 1.0
 * Author: Peter Kühne
 * License: GPL v2 oder später
 * Text Domain: retell-voice-agent
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hauptklasse für das Retell Voice Agent Plugin
 */
class RetellVoiceAgent {

    /**
     * Konstruktor.
     * Initialisiert alle nötigen Hooks und Aktionen.
     */
    public function __construct() {
        // Scripts und Styles einbinden
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Shortcode registrieren
        add_shortcode('voice_agent', array($this, 'render_voice_agent'));
        
        // Admin-Menü und Einstellungen
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Registriert und bindet alle benötigten Scripts und Styles ein
     */
    public function enqueue_scripts() {
        // EventEmitter3 einbinden
        wp_enqueue_script(
            'eventemitter3',
            'https://cdn.jsdelivr.net/npm/eventemitter3@5.0.1/dist/eventemitter3.min.js',
            array(),
            '5.0.1',
            false
        );

        // Lokale Version des Retell SDK einbinden
        wp_enqueue_script(
            'retell-sdk',
            plugins_url('lib/retell-sdk.js', __FILE__),
            array('eventemitter3'),
            '1.0',
            false
        );

        // Voice Agent Script einbinden
        wp_enqueue_script(
            'voice-agent',
            plugins_url('js/voice-agent.js', __FILE__),
            array('retell-sdk'),
            '1.0',
            true
        );

        // Styles einbinden
        wp_enqueue_style(
            'voice-agent',
            plugins_url('css/voice-agent.css', __FILE__),
            array(),
            '1.0'
        );
    }

    /**
     * Registriert die Plugin-Einstellungen
     */
    public function register_settings() {
        register_setting('voice_agent_options', 'retell_agent_id');
        register_setting('voice_agent_options', 'retell_api_url');
    }

    /**
     * Rendert den Voice Agent HTML-Code
     * 
     * @return string HTML-Code des Voice Agents
     */
    public function render_voice_agent() {
        $agent_id = get_option('retell_agent_id');
        $api_url = get_option('retell_api_url');

        if (empty($agent_id) || empty($api_url)) {
            return '<p>Bitte konfigurieren Sie den Voice Agent in den WordPress-Einstellungen.</p>';
        }

        ob_start();
        ?>
        <div id="voice-agent"></div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                let checkRetell = setInterval(function() {
                    if (window.RetellClient && window.RetellClient.RetellWebClient) {
                        clearInterval(checkRetell);
                        new VoiceAgent("voice-agent", {
                            agentId: <?php echo json_encode($agent_id); ?>,
                            apiUrl: <?php echo json_encode($api_url); ?>
                        });
                    }
                }, 100);

                // Nach 5 Sekunden abbrechen, wenn SDK nicht geladen wurde
                setTimeout(function() {
                    clearInterval(checkRetell);
                    if (!window.RetellClient || !window.RetellClient.RetellWebClient) {
                        console.error("RetellClient konnte nicht geladen werden.");
                    }
                }, 5000);
            });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Fügt das Admin-Menü zu WordPress hinzu
     */
    public function add_admin_menu() {
        add_options_page(
            'Voice Agent Einstellungen',       // Seitentitel
            'Voice Agent',                     // Menütitel
            'manage_options',                  // Erforderliche Berechtigung
            'voice-agent-settings',            // Menü-Slug
            array($this, 'render_settings_page') // Callback-Funktion
        );
    }

    /**
     * Rendert die Einstellungsseite im WordPress Admin-Bereich
     */
    public function render_settings_page() {
        // Prüfen ob Benutzer die erforderlichen Rechte hat
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo get_admin_page_title(); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('voice_agent_options');
                do_settings_sections('voice_agent_options');
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="retell_agent_id">Agent ID</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="retell_agent_id"
                                   name="retell_agent_id"
                                   value="<?php echo esc_attr(get_option('retell_agent_id')); ?>"
                                   class="regular-text">
                            <p class="description">
                                Ihre Retell Agent ID
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="retell_api_url">Backend URL</label>
                        </th>
                        <td>
                            <input type="url"
                                   id="retell_api_url"
                                   name="retell_api_url"
                                   value="<?php echo esc_attr(get_option('retell_api_url')); ?>"
                                   class="regular-text">
                            <p class="description">
                                Die URL Ihres Backend-Services (z.B. https://ihre-backend-url.onrender.com)
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Einstellungen speichern'); ?>
            </form>

            <hr>

            <h2>Verwendung</h2>
            <p>Fügen Sie den Voice Agent mit diesem Shortcode ein:</p>
            <code>[voice_agent]</code>
        </div>
        <?php
    }
}

// Plugin initialisieren
new RetellVoiceAgent();