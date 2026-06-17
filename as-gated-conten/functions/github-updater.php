<?php
/**
 * GitHub release updater.
 *
 * @package AS_Gated_Content
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('pre_set_site_transient_update_plugins', 'asgc_add_github_update_data');
add_filter('site_transient_update_plugins', 'asgc_add_github_update_data');
add_filter('plugins_api', 'asgc_github_plugin_information', 10, 3);
add_filter('plugin_row_meta', 'asgc_add_plugin_row_meta', 10, 2);
add_action('admin_init', 'asgc_handle_manual_update_check');
add_action('upgrader_process_complete', 'asgc_clear_update_cache_after_upgrade', 10, 2);

function asgc_get_github_release(bool $force = false)
{
    $release_transient = 'asgc_github_latest_release';
    $error_transient = 'asgc_github_latest_release_error';

    if ($force || asgc_is_forced_update_check()) {
        delete_site_transient($release_transient);
        delete_site_transient($error_transient);
    }

    $cached = get_site_transient($release_transient);

    if (is_array($cached) && !empty($cached['tag_name'])) {
        return $cached;
    }

    $response = wp_remote_get(
        sprintf('https://api.github.com/repos/%s/%s/releases/latest', ASGC_GITHUB_OWNER, ASGC_GITHUB_REPO),
        array(
            'timeout' => 10,
            'headers' => array(
                'Accept'     => 'application/vnd.github+json',
                'User-Agent' => 'AS-Gated-Content/' . ASGC_VERSION,
            ),
        )
    );

    if (is_wp_error($response)) {
        set_site_transient(
            $error_transient,
            array(
                'type'       => 'wp_error',
                'message'    => $response->get_error_message(),
                'checked_at' => time(),
            ),
            10 * MINUTE_IN_SECONDS
        );
        delete_site_transient($release_transient);
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);

    if (200 !== $response_code) {
        set_site_transient(
            $error_transient,
            array(
                'type'       => 'http_error',
                'code'       => $response_code,
                'message'    => wp_remote_retrieve_response_message($response),
                'body'       => substr(wp_remote_retrieve_body($response), 0, 500),
                'checked_at' => time(),
            ),
            10 * MINUTE_IN_SECONDS
        );
        delete_site_transient($release_transient);
        return false;
    }

    $release = json_decode(wp_remote_retrieve_body($response), true);

    if (!is_array($release) || empty($release['tag_name'])) {
        set_site_transient(
            $error_transient,
            array(
                'type'       => 'json_error',
                'checked_at' => time(),
            ),
            10 * MINUTE_IN_SECONDS
        );
        delete_site_transient($release_transient);
        return false;
    }

    $version = asgc_get_release_version($release);

    if ('' === $version) {
        delete_site_transient($release_transient);
        return false;
    }

    $cache_ttl = version_compare($version, ASGC_VERSION, '>') ? 6 * HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS;
    set_site_transient($release_transient, $release, $cache_ttl);
    delete_site_transient($error_transient);

    return $release;
}

function asgc_get_release_version(array $release): string
{
    return ltrim(sanitize_text_field((string) ($release['tag_name'] ?? '')), 'vV');
}

function asgc_get_release_asset_url(array $release): string
{
    if (empty($release['assets']) || !is_array($release['assets'])) {
        return '';
    }

    foreach ($release['assets'] as $asset) {
        if (ASGC_RELEASE_ASSET === ($asset['name'] ?? '') && !empty($asset['browser_download_url'])) {
            return esc_url_raw((string) $asset['browser_download_url']);
        }
    }

    return '';
}

function asgc_add_github_update_data($transient)
{
    if (!is_object($transient)) {
        $transient = new stdClass();
    }

    $plugin_file = ASGC_PLUGIN_BASENAME;
    $transient->response = isset($transient->response) && is_array($transient->response) ? $transient->response : array();
    $transient->no_update = isset($transient->no_update) && is_array($transient->no_update) ? $transient->no_update : array();

    $release = asgc_get_github_release();

    if (!is_array($release)) {
        return $transient;
    }

    $version = asgc_get_release_version($release);
    $package_url = asgc_get_release_asset_url($release);

    if ('' === $version || '' === $package_url || !version_compare($version, ASGC_VERSION, '>')) {
        unset($transient->response[$plugin_file], $transient->no_update[$plugin_file]);
        return $transient;
    }

    $transient->response[$plugin_file] = (object) array(
        'id'           => 'https://github.com/' . ASGC_GITHUB_OWNER . '/' . ASGC_GITHUB_REPO,
        'slug'         => ASGC_PLUGIN_SLUG,
        'plugin'       => $plugin_file,
        'new_version'  => $version,
        'url'          => 'https://github.com/' . ASGC_GITHUB_OWNER . '/' . ASGC_GITHUB_REPO,
        'package'      => $package_url,
        'requires'     => '6.0',
        'requires_php' => '8.1',
    );

    unset($transient->no_update[$plugin_file]);

    return $transient;
}

function asgc_github_plugin_information($result, string $action, object $args)
{
    if ('plugin_information' !== $action || empty($args->slug) || ASGC_PLUGIN_SLUG !== $args->slug) {
        return $result;
    }

    $release = asgc_get_github_release();

    if (!is_array($release)) {
        return $result;
    }

    $version = asgc_get_release_version($release);
    $package_url = asgc_get_release_asset_url($release);

    return (object) array(
        'name'          => 'AS Gated Content',
        'slug'          => ASGC_PLUGIN_SLUG,
        'version'       => $version ?: ASGC_VERSION,
        'author'        => 'AlphaSys',
        'homepage'      => 'https://github.com/' . ASGC_GITHUB_OWNER . '/' . ASGC_GITHUB_REPO,
        'download_link' => $package_url,
        'requires'      => '6.0',
        'requires_php'  => '8.1',
        'sections'      => array(
            'description' => __('Reusable gated content overlays powered by ACF Pro and Gravity Forms.', 'as-gated-conten'),
            'changelog'   => wp_kses_post((string) ($release['body'] ?? '')),
        ),
    );
}

function asgc_add_plugin_row_meta(array $links, string $file): array
{
    if (ASGC_PLUGIN_BASENAME !== $file || !current_user_can('update_plugins')) {
        return $links;
    }

    $plugins_url = is_multisite() ? network_admin_url('plugins.php') : admin_url('plugins.php');
    $check_url = wp_nonce_url(add_query_arg('asgc_check_updates', '1', $plugins_url), 'asgc_check_updates');

    $links[] = sprintf(
        '<a href="%s">%s</a>',
        esc_url('https://github.com/' . ASGC_GITHUB_OWNER . '/' . ASGC_GITHUB_REPO),
        esc_html__('GitHub', 'as-gated-conten')
    );
    $links[] = sprintf(
        '<a href="%s">%s</a>',
        esc_url($check_url),
        esc_html__('Check for updates', 'as-gated-conten')
    );

    return $links;
}

function asgc_handle_manual_update_check(): void
{
    if (empty($_GET['asgc_check_updates'])) {
        return;
    }

    if (!current_user_can('update_plugins')) {
        wp_die(esc_html__('You do not have permission to check plugin updates.', 'as-gated-conten'));
    }

    check_admin_referer('asgc_check_updates');
    asgc_clear_github_update_cache();
    delete_site_transient('update_plugins');
    wp_update_plugins();
    wp_safe_redirect(is_multisite() ? network_admin_url('plugins.php') : admin_url('plugins.php'));
    exit;
}

function asgc_clear_update_cache_after_upgrade($upgrader, array $hook_extra): void
{
    if (empty($hook_extra['plugins']) || !is_array($hook_extra['plugins'])) {
        return;
    }

    if (in_array(ASGC_PLUGIN_BASENAME, $hook_extra['plugins'], true)) {
        asgc_clear_github_update_cache();
    }
}

function asgc_clear_github_update_cache(): void
{
    delete_site_transient('asgc_github_latest_release');
    delete_site_transient('asgc_github_latest_release_error');
}

function asgc_is_forced_update_check(): bool
{
    if (!is_admin() || !current_user_can('update_plugins')) {
        return false;
    }

    $force_check = isset($_GET['force-check']) || isset($_POST['force-check']);
    $action = isset($_REQUEST['action']) ? sanitize_key($_REQUEST['action']) : '';

    return $force_check || in_array($action, array('update-selected', 'upgrade-plugin', 'do-plugin-upgrade'), true);
}
