<?php
/**
 * Plugin Name: Retell Voice Agent
 * Plugin URI: https://github.com/PeterKuehne/retell-voice-assistant
 * Description: Ein Voice Assistant Plugin basierend auf Retell AI
 * Version: 1.0
 * Author: Peter Kühne
 * Text Domain: retell-voice-agent
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

class RetellVoiceAgent {
    /**
     * Plugin Konstruktor
     */
    public function __construct() {
        // Scripts und Styles einbinden
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Shortcode registrieren
        add_shortcode('voice_agent', array($this, 'render_voice_agent'));
        
        // Admin-Menü hinzufügen
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Einstellungen registrieren
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Scripts und Styles registrieren und einbinden
     */
    public function enqueue_scripts() {
        // Retell SDK einbinden
        wp_enqueue_script(
            'retell-sdk', 
            'https://unpkg.com/retell-client-js-sdk@latest/dist/index.js',
            array(),
            '1.0',
            true
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
     * Plugin-Einstellungen registrieren
     */
    public function register_settings() {
        register_setting('voice_agent_options', 'retell_agent_id');
        register_setting('voice_agent_options', 'retell_api_url');
    }

    /**
     * Voice Agent HTML rendern
     */
    public function render_voice_agent() {
        $agent_id = get_option('retell_agent_id');
        $api_url = get_option('retell_api_url');

        if (empty($agent_id) || empty($api_url)) {
            return '<p>' . __('Bitte konfigurieren Sie den Voice Agent in den WordPress-Einstellungen.', 'retell-voice-agent') . '</p>';
        }

        ob_start();
        ?>
        <div id="voice-agent"></div>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                new VoiceAgent("voice-agent", {
                    agentId: "<?php echo esc_js($agent_id); ?>",
                    apiUrl: "<?php echo esc_js($api_url); ?>"
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Admin-Menü hinzufügen
     */
    public function add_admin_menu() {
        add_options_page(
            __('Voice Agent Einstellungen', 'retell-voice-agent'),
            __('Voice Agent', 'retell-voice-agent'),
            'manage_options',
            'voice-agent-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Einstellungsseite rendern
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('voice_agent_options');
                do_settings_sections('voice_agent_options');
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="retell_agent_id"><?php _e('Agent ID', 'retell-voice-agent'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="retell_agent_id"
                                   name="retell_agent_id"
                                   value="<?php echo esc_attr(get_option('retell_agent_id')); ?>"
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Ihre Retell Agent ID', 'retell-voice-agent'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="retell_api_url"><?php _e('Backend URL', 'retell-voice-agent'); ?></label>
                        </th>
                        <td>
                            <input type="url"
                                   id="retell_api_url"
                                   name="retell_api_url"
                                   value="<?php echo esc_attr(get_option('retell_api_url')); ?>"
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Die URL Ihres Backend-Services (z.B. https://ihre-backend-url.onrender.com)', 'retell-voice-agent'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2><?php _e('Verwendung', 'retell-voice-agent'); ?></h2>
            <p><?php _e('Fügen Sie den Voice Agent mit diesem Shortcode ein:', 'retell-voice-agent'); ?></p>
            <code>[voice_agent]</code>
        </div>
        <?php
    }
}

// Plugin initialisieren
new RetellVoiceAgent();
?>