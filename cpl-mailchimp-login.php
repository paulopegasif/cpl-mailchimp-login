<?php
/**
 * Plugin Name: CPL Mailchimp Login
 * Description: Valida acesso às aulas conferindo e-mail e tag no Mailchimp, com configurações avançadas.
 * Version: 1.0.0
 * Author: Paulo Silva - ELPH
 */

if (!defined('ABSPATH')) exit;

const CPL_MC_OPTION_KEY = 'cpl_mc_login_options';

// Load admin page
add_action('admin_menu', function () {
    add_options_page(
        'CPL Mailchimp Login',
        'CPL Mailchimp Login',
        'manage_options',
        'cpl-mc-login',
        'cpl_mc_render_settings_page'
    );
});

// Register settings ajax handlers')
add_action('wp_ajax_cpl_mc_test_key', 'cpl_mc_test_key');
add_action('wp_ajax_cpl_mc_fetch_audiences', 'cpl_mc_fetch_audiences');

// Enqueue admin JS
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'settings_page_cpl-mc-login') return;
    wp_enqueue_script('cpl-mc-admin', plugins_url('assets/admin.js', __FILE__), ['jquery'], '1.0', true);
    wp_localize_script('cpl-mc-admin', 'CPLMC', [
        'ajax' => admin_url('admin-ajax.php'),
        'nonce'=> wp_create_nonce('cpl_mc_nonce')
    ]);
});

function cpl_mc_get_default_options() {
    return [
        'api_key'      => '',
        'list_id'      => '',
        'tag_name'     => 'Ex: Inscrito',
        'cookie_days'  => 7,
    ];
}

function cpl_mc_get_options() {
    $saved = get_option(CPL_MC_OPTION_KEY, []);
    return array_merge(cpl_mc_get_default_options(), is_array($saved) ? $saved : []);
}


//UI Render Page
function cpl_mc_render_settings_page() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['cpl_mc_save_settings']) && check_admin_referer('cpl_mc_save_settings_nonce')) {
        $options = cpl_mc_get_options();
        $options['api_key']     = sanitize_text_field($_POST['api_key']);
        $options['list_id']     = sanitize_text_field($_POST['list_id']);
        $options['tag_name']    = sanitize_text_field($_POST['tag_name']);
        $options['cookie_days'] = max(1, intval($_POST['cookie_days']));
        update_option(CPL_MC_OPTION_KEY, $options);

        echo '<div class="updated"><p>Configurações salvas.</p></div>';
    }

    $opts = cpl_mc_get_options();
    ?>
    <div class="wrap">
      <h1>CPL Mailchimp Login</h1>
      <p>Configure a integração com o Mailchimp.</p>

      <form method="post">
        <?php wp_nonce_field('cpl_mc_save_settings_nonce'); ?>
        <table class="form-table">
            <tr>
                <th>API Key</th>
                <td>
                    <input type="text" name="api_key" id="api_key" class="regular-text"
                           value="<?php echo esc_attr($opts['api_key']); ?>">
                    <button type="button" class="button" id="cpl-test-key">Testar API Key</button>
                    <p id="cpl-test-result"></p>
                </td>
            </tr>

            <tr>
                <th>Audience (List)</th>
                <td>
                    <select name="list_id" id="cpl-audience-select" data-current="<?php echo esc_attr($opts['list_id']); ?>">
                        <option>-- Selecione uma Audience --</option>

                        <?php if (!empty($opts['list_id'])): ?>
                            <option value="<?php echo esc_attr($opts['list_id']); ?>" selected>
                                Audience atual (ID: <?php echo esc_html($opts['list_id']); ?>)
                            </option>
                        <?php endif; ?>
                    </select>
                    <button type="button" class="button" id="cpl-fetch-audiences">Carregar Audiences</button>
                </td>
            </tr>

            <tr>
                <th>Tag necessária</th>
                <td><input type="text" name="tag_name" value="<?php echo esc_attr($opts['tag_name']); ?>"></td>
            </tr>

            <tr>
                <th>Dias de acesso</th>
                <td><input type="number" name="cookie_days" value="<?php echo esc_attr($opts['cookie_days']); ?>"></td>
            </tr>
        </table>

        <p><button class="button button-primary" name="cpl_mc_save_settings">Salvar</button></p>
      </form>
    </div>
    <?php
}

// AJAX: Test API Key
function cpl_mc_test_key() {
    check_ajax_referer('cpl_mc_nonce');
    $key = sanitize_text_field($_POST['api_key']);
    $parts = explode("-", $key);
    if (count($parts)<2) wp_send_json_error("API Key inválida");

    $dc = $parts[1];
    $url = "https://{$dc}.api.mailchimp.com/3.0/";

    $res = wp_remote_get($url, [
        'headers'=>['Authorization'=>'Basic '.base64_encode("any:$key")]
    ]);

    if (is_wp_error($res)) wp_send_json_error("Falha de conexão");
    $code = wp_remote_retrieve_response_code($res);
    if ($code>=200 && $code<300) wp_send_json_success("API Key válida");
    wp_send_json_error("API Key inválida");
}

// AJAX: Fetch audiences
function cpl_mc_fetch_audiences() {
    check_ajax_referer('cpl_mc_nonce');
    $key  = sanitize_text_field($_POST['api_key']);
    $parts = explode("-", $key);
    if (count($parts)<2) wp_send_json_error("API Key inválida");
    $dc = $parts[1];

    $url = "https://{$dc}.api.mailchimp.com/3.0/lists?fields=lists.id,lists.name";

    $res = wp_remote_get($url, [
        'headers'=>['Authorization'=>'Basic '.base64_encode("any:$key")]
    ]);

    if (is_wp_error($res)) wp_send_json_error("Falha de conexão");
    $body = json_decode(wp_remote_retrieve_body($res), true);
    wp_send_json_success($body['lists']);
}
