<?php
/*
Plugin Name: Retell Voice Agent
Plugin URI: https://github.com/PeterKuehne/retell-voice-assistant
Description: Ein Voice Assistant Plugin basierend auf Retell AI
Version: 1.0
Author: Peter Kühne
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class RetellVoiceAgent {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('voice_agent', array($this, 'render_voice_agent'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function enqueue_scripts() {
        // Retell SDK einbinden
        wp_enqueue_script('retell-sdk', 'https://unpkg.com/retell-client-js-sdk@latest/dist/index.js', array(), '1.0', true);
        
        // Voice Agent Script einbinden
        wp_enqueue_script('voice-agent', plugins_url('js/voice-agent.js', __FILE__), array('retell-sdk'), '1.0', true);
        
        // Styles einbinden
        wp_enqueue_style('voice-agent', plugins_url('css/voice-agent.css', __FILE__), array(), '1.0');
    }

    public function register_settings() {
        register_setting('voice_agent_options', 'retell_agent_id');
        register_setting('voice_agent_options', 'retell_api_url');
    }

    public function render_voice_agent() {
        $agent_id = get_option('retell_agent_id');
        $api_url = get_option('retell_api_url');
        
        if (empty($agent_id) || empty($api_url)) {
            return '<p>Bitte konfigurieren Sie den Voice Agent in den Einstellungen.</p>';
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

    public function add_admin_menu() {
        add_options_page(
            'Voice Agent Einstellungen',
            'Voice Agent',
            'manage_options',
            'voice-agent-settings',
            array($this, 'render_settings_page')
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Voice Agent Einstellungen</h1>
            <form method="post" action="options.php">
                <?php settings_fields('voice_agent_options'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Agent ID</th>
                        <td>
                            <input type="text" name="retell_agent_id" 
                                value="<?php echo esc_attr(get_option('retell_agent_id')); ?>" 
                                class="regular-text">
                            <p class="description">Ihre Retell Agent ID</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Backend URL</th>
                        <td>
                            <input type="text" name="retell_api_url" 
                                value="<?php echo esc_attr(get_option('retell_api_url')); ?>" 
                                class="regular-text">
                            <p class="description">Die URL Ihres Backend-Services (z.B. https://ihre-backend-url.onrender.com)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <h2>Verwendung</h2>
            <p>Fügen Sie den Voice Agent mit diesem Shortcode ein:</p>
            <code>[voice_agent]</code>
        </div>
        <?php
    }
}

// Plugin initialisieren
new RetellVoiceAgent();
?>