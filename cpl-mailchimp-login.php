<?php
/**
 * Plugin Name: CPL Mailchimp Login
 * Description: Valida acesso às aulas conferindo e-mail e tag no Mailchimp, com configurações avançadas.
 * Version: 2.0.0
 * Author: Paulo Silva - ELPH
 */

if (!defined('ABSPATH')) exit;

const CPL_MC_OPTION_KEY = 'cpl_mc_login_options';
const CPL_MC_META_PROTECT_KEY = '_cpl_mc_protect_page';

/**
 * Default options
 */
function cpl_mc_get_default_options() {
    return [
        'api_key'     => '',
        'list_id'     => '',
        'tag_name'    => 'Inscrito',
        'cookie_days' => 7,
        'popup_id'    => '',
    ];
}

/**
 * Get merged options
 */
function cpl_mc_get_options() {
    $saved = get_option( CPL_MC_OPTION_KEY, [] );
    if ( ! is_array( $saved ) ) {
        $saved = [];
    }
    return array_merge( cpl_mc_get_default_options(), $saved );
}

/**
 * Admin menu: settings page
 */
add_action( 'admin_menu', function () {
    add_options_page(
        'CPL Mailchimp Login',
        'CPL Mailchimp Login',
        'manage_options',
        'cpl-mc-login',
        'cpl_mc_render_settings_page'
    );
} );

/**
 * Settings page render
 */
function cpl_mc_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( isset( $_POST['cpl_mc_save_settings'] ) && check_admin_referer( 'cpl_mc_save_settings_nonce' ) ) {
        $options                = cpl_mc_get_options();
        $options['api_key']     = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
        $options['list_id']     = isset( $_POST['list_id'] ) ? sanitize_text_field( wp_unslash( $_POST['list_id'] ) ) : '';
        $options['tag_name']    = isset( $_POST['tag_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tag_name'] ) ) : 'Inscrito';
        $cookie_days            = isset( $_POST['cookie_days'] ) ? intval( $_POST['cookie_days'] ) : 7;
        $options['cookie_days'] = $cookie_days > 0 ? $cookie_days : 7;
        $options['popup_id']    = isset( $_POST['popup_id'] ) ? sanitize_text_field( wp_unslash( $_POST['popup_id'] ) ) : '';

        update_option( CPL_MC_OPTION_KEY, $options );

        echo '<div class="updated"><p>Configurações salvas.</p></div>';
    }

    $opts = cpl_mc_get_options();
    ?>
    <div class="wrap">
        <h1>CPL Mailchimp Login</h1>
        <p>Configure aqui a integração com o Mailchimp e o popup de login (Elementor) para proteger suas páginas de aulas.</p>

        <form method="post">
            <?php wp_nonce_field( 'cpl_mc_save_settings_nonce' ); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="api_key">Mailchimp API Key</label></th>
                    <td>
                        <input name="api_key" id="api_key" type="text" class="regular-text"
                               value="<?php echo esc_attr( $opts['api_key'] ); ?>" />
                        <button type="button" class="button" id="cpl-test-key">Testar API Key</button>
                        <p id="cpl-test-result" style="margin-top:4px;"></p>
                        <p class="description">Encontre em Mailchimp → Profile → Extras → API Keys.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="cpl-audience-select">Audience / Lista</label></th>
                    <td>
                        <select name="list_id" id="cpl-audience-select"
                                data-current="<?php echo esc_attr( $opts['list_id'] ); ?>">
                            <option value="">-- Selecione --</option>
                            <?php if ( ! empty( $opts['list_id'] ) ) : ?>
                                <option value="<?php echo esc_attr( $opts['list_id'] ); ?>" selected>
                                    Audience atual (ID: <?php echo esc_html( $opts['list_id'] ); ?>)
                                </option>
                            <?php endif; ?>
                        </select>
                        <button type="button" class="button" id="cpl-fetch-audiences">Carregar Audiences</button>
                        <p class="description">Clique para buscar as audiences da API do Mailchimp e selecionar a desejada.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="tag_name">Tag necessária</label></th>
                    <td>
                        <input name="tag_name" id="tag_name" type="text" class="regular-text"
                               value="<?php echo esc_attr( $opts['tag_name'] ); ?>" />
                        <p class="description">Ex.: <code>Inscrito</code>. O contato precisa ter essa tag para acessar as páginas protegidas.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="cookie_days">Dias de acesso liberado (cookie)</label></th>
                    <td>
                        <input name="cookie_days" id="cookie_days" type="number" min="1" step="1"
                               value="<?php echo esc_attr( $opts['cookie_days'] ); ?>" />
                        <p class="description">Quantos dias o aluno ficará sem precisar fazer login novamente.</p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><label for="popup_id">ID do Popup (Elementor)</label></th>
                    <td>
                        <input name="popup_id" id="popup_id" type="text" class="regular-text"
                               value="<?php echo esc_attr( $opts['popup_id'] ); ?>" />
                        <p class="description">
                            ID numérico do popup criado no Elementor para o login (com formulário contendo apenas e-mail).
                            Você pode ver esse ID em Modelos → Popups, passando o mouse sobre o nome.
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="cpl_mc_save_settings" class="button button-primary">Salvar Configurações</button>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Admin enqueue (settings page)
 */
add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'settings_page_cpl-mc-login' !== $hook ) {
        return;
    }
    wp_enqueue_script(
        'cpl-mc-admin',
        plugins_url( 'assets/admin.js', __FILE__ ),
        [ 'jquery' ],
        '2.0.0',
        true
    );

    wp_localize_script(
        'cpl-mc-admin',
        'CPLMC',
        [
            'ajax'  => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'cpl_mc_nonce' ),
        ]
    );
} );

/**
 * AJAX: Test API Key
 */
add_action( 'wp_ajax_cpl_mc_test_key', 'cpl_mc_test_key' );
function cpl_mc_test_key() {
    check_ajax_referer( 'cpl_mc_nonce' );

    $key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
    if ( empty( $key ) ) {
        wp_send_json_error( 'Informe uma API Key.' );
    }

    $parts = explode( '-', $key );
    if ( count( $parts ) < 2 ) {
        wp_send_json_error( 'API Key inválida.' );
    }
    $dc  = $parts[1];
    $url = "https://{$dc}.api.mailchimp.com/3.0/";

    $res = wp_remote_get(
        $url,
        [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $key ),
            ],
            'timeout' => 15,
        ]
    );

    if ( is_wp_error( $res ) ) {
        wp_send_json_error( 'Falha de conexão com Mailchimp.' );
    }

    $code = wp_remote_retrieve_response_code( $res );
    if ( $code >= 200 && $code < 300 ) {
        wp_send_json_success( 'API Key válida.' );
    }

    wp_send_json_error( 'API Key inválida.' );
}

/**
 * AJAX: Fetch audiences (lists)
 */
add_action( 'wp_ajax_cpl_mc_fetch_audiences', 'cpl_mc_fetch_audiences' );
function cpl_mc_fetch_audiences() {
    check_ajax_referer( 'cpl_mc_nonce' );

    $key = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
    if ( empty( $key ) ) {
        wp_send_json_error( 'Informe uma API Key.' );
    }

    $parts = explode( '-', $key );
    if ( count( $parts ) < 2 ) {
        wp_send_json_error( 'API Key inválida.' );
    }
    $dc  = $parts[1];
    $url = "https://{$dc}.api.mailchimp.com/3.0/lists?fields=lists.id,lists.name";

    $res = wp_remote_get(
        $url,
        [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $key ),
            ],
            'timeout' => 15,
        ]
    );

    if ( is_wp_error( $res ) ) {
        wp_send_json_error( 'Falha de conexão com Mailchimp.' );
    }

    $body = json_decode( wp_remote_retrieve_body( $res ), true );
    if ( isset( $body['lists'] ) && is_array( $body['lists'] ) ) {
        wp_send_json_success( $body['lists'] );
    }

    wp_send_json_error( 'Não foi possível obter as audiences.' );
}

/**
 * REST API endpoint: login validation
 */
add_action( 'rest_api_init', function () {
    register_rest_route(
        'cpl/v1',
        '/login',
        [
            'methods'             => 'POST',
            'callback'            => 'cpl_mc_login_endpoint',
            'permission_callback' => '__return_true',
        ]
    );
} );

function cpl_mc_login_endpoint( WP_REST_Request $req ) {
    $email = sanitize_email( $req->get_param( 'email' ) );
    if ( empty( $email ) || ! is_email( $email ) ) {
        return new WP_REST_Response(
            [
                'ok'    => false,
                'error' => 'E-mail inválido.',
            ],
            400
        );
    }

    $opts   = cpl_mc_get_options();
    $apiKey = $opts['api_key'];
    $listId = $opts['list_id'];
    $tagReq = $opts['tag_name'];

    if ( empty( $apiKey ) || empty( $listId ) ) {
        return new WP_REST_Response(
            [
                'ok'    => false,
                'error' => 'Configurações de Mailchimp ausentes.',
            ],
            500
        );
    }

    $parts = explode( '-', $apiKey );
    if ( count( $parts ) < 2 ) {
        return new WP_REST_Response(
            [
                'ok'    => false,
                'error' => 'API Key inválida.',
            ],
            500
        );
    }
    $dc = $parts[1];

    $subscriber_hash = md5( strtolower( $email ) );
    $url             = "https://{$dc}.api.mailchimp.com/3.0/lists/{$listId}/members/{$subscriber_hash}?fields=email_address,status,tags";

    $response = wp_remote_get(
        $url,
        [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode( 'anystring:' . $apiKey ),
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 15,
        ]
    );

    if ( is_wp_error( $response ) ) {
        return new WP_REST_Response(
            [
                'ok'    => false,
                'error' => 'Falha ao conectar no Mailchimp.',
            ],
            502
        );
    }

    $code = wp_remote_retrieve_response_code( $response );
    $body = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( 404 === $code ) {
        return new WP_REST_Response(
            [
                'ok'    => false,
                'error' => 'E-mail não cadastrado na base.',
            ],
            404
        );
    }
    if ( $code < 200 || $code >= 300 ) {
        return new WP_REST_Response(
            [
                'ok'    => false,
                'error' => 'Erro ao consultar dados no Mailchimp.',
            ],
            502
        );
    }

    $status_ok = isset( $body['status'] ) && 'subscribed' === $body['status'];

    $tag_ok = false;
    if ( ! empty( $body['tags'] ) && is_array( $body['tags'] ) ) {
        foreach ( $body['tags'] as $tag ) {
            if ( ! empty( $tag['name'] ) && 0 === strcasecmp( $tag['name'], $tagReq ) ) {
                $tag_ok = true;
                break;
            }
        }
    }

    if ( $status_ok && $tag_ok ) {
        return new WP_REST_Response(
            [
                'ok' => true,
            ],
            200
        );
    }

    if ( ! $status_ok ) {
        return new WP_REST_Response(
            [
                'ok'    => false,
                'error' => 'Seu e-mail está cadastrado, mas ainda não foi confirmado. Verifique a confirmação na sua caixa de entrada.',
            ],
            403
        );
    }

    return new WP_REST_Response(
        [
            'ok'    => false,
            'error' => 'Seu e-mail está cadastrado, mas ainda não tem permissão para acessar esta área.',
        ],
        403
    );
}

/**
 * Meta box: protect page toggle
 */
add_action( 'add_meta_boxes', function () {
    add_meta_box(
        'cpl-mc-protect',
        'CPL Mailchimp Login',
        'cpl_mc_render_meta_box',
        [ 'page' ],
        'side',
        'high'
    );
} );

function cpl_mc_render_meta_box( $post ) {
    $value = get_post_meta( $post->ID, CPL_MC_META_PROTECT_KEY, true );
    wp_nonce_field( 'cpl_mc_meta_box_nonce', 'cpl_mc_meta_box_nonce_field' );
    ?>
    <p>
        <label>
            <input type="checkbox" name="cpl_mc_protect_page" value="1" <?php checked( $value, '1' ); ?> />
            Proteger esta página com CPL Mailchimp Login
        </label>
    </p>
    <p class="description">Quando marcada, esta página exigirá login por e-mail (Mailchimp) para liberar o conteúdo.</p>
    <?php
}

add_action( 'save_post', function ( $post_id ) {
    if ( ! isset( $_POST['cpl_mc_meta_box_nonce_field'] ) ||
         ! wp_verify_nonce( $_POST['cpl_mc_meta_box_nonce_field'], 'cpl_mc_meta_box_nonce' )
    ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) {
        if ( ! current_user_can( 'edit_page', $post_id ) ) {
            return;
        }
    }

    $protect = isset( $_POST['cpl_mc_protect_page'] ) ? '1' : '';
    if ( $protect ) {
        update_post_meta( $post_id, CPL_MC_META_PROTECT_KEY, $protect );
    } else {
        delete_post_meta( $post_id, CPL_MC_META_PROTECT_KEY );
    }
} );

/**
 * Helper: is current page protected?
 */
function cpl_mc_is_protected_page() {
    if ( ! is_singular( 'page' ) ) {
        return false;
    }
    $post = get_queried_object();
    if ( ! $post || empty( $post->ID ) ) {
        return false;
    }
    $meta = get_post_meta( $post->ID, CPL_MC_META_PROTECT_KEY, true );
    return ( '1' === $meta );
}

/**
 * Frontend: enqueue CSS/JS when needed
 */
add_action( 'wp_enqueue_scripts', function () {
    if ( ! cpl_mc_is_protected_page() ) {
        return;
    }

    $opts = cpl_mc_get_options();

    // CSS
    wp_enqueue_style(
        'cpl-mc-gate',
        plugins_url( 'assets/gate.css', __FILE__ ),
        [],
        '2.0.0'
    );

    // JS
    wp_enqueue_script(
        'cpl-mc-gate',
        plugins_url( 'assets/gate.js', __FILE__ ),
        [],
        '2.0.0',
        true
    );

    wp_localize_script(
        'cpl-mc-gate',
        'CPLMC_FRONT',
        [
            'restUrl'    => esc_url_raw( rest_url( 'cpl/v1/login' ) ),
            'cookieDays' => (int) $opts['cookie_days'],
            'popupId'    => $opts['popup_id'],
        ]
    );
} );

/**
 * Frontend overlay (body)
 */
add_action( 'wp_body_open', function () {
    if ( ! cpl_mc_is_protected_page() ) {
        return;
    }
    ?>
    <div id="cpl-gate-overlay" aria-hidden="true"></div>
    <?php
} );