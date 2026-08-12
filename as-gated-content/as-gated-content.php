<?php
/**
 * Plugin Name: AS Gated Content
 * Plugin URI: https://github.com/cchatterton/as-gated-content/releases/latest
 * Description: Reusable gated content overlays powered by ACF Pro and Gravity Forms.
 * Version: 0.2.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Update URI: https://github.com/cchatterton/as-gated-content
 * Author: AlphaSys
 * Author URI: https://alphasys.com.au
 * Text Domain: as-gated-content
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ASGC_VERSION', '0.2.0');
define('ASGC_PLUGIN_FILE', __FILE__);
define('ASGC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASGC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASGC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ASGC_GITHUB_OWNER', 'cchatterton');
define('ASGC_GITHUB_REPO', 'as-gated-content');
define('ASGC_PLUGIN_SLUG', 'as-gated-content');
define('ASGC_RELEASE_ASSET', 'as-gated-content.zip');

require_once ASGC_PLUGIN_DIR . 'functions/helpers.php';
require_once ASGC_PLUGIN_DIR . 'functions/post-types.php';
require_once ASGC_PLUGIN_DIR . 'functions/gravity-forms.php';
require_once ASGC_PLUGIN_DIR . 'functions/meta-boxes.php';
require_once ASGC_PLUGIN_DIR . 'functions/frontend.php';
require_once ASGC_PLUGIN_DIR . 'functions/github-updater.php';
