<?php
/**
 * Plugin Name: AS Gated Content
 * Plugin URI: https://github.com/cchatterton/as-gated-conten/releases/latest
 * Description: Reusable gated content overlays powered by ACF Pro and Gravity Forms.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.1
 * Update URI: https://github.com/cchatterton/as-gated-conten
 * Author: AlphaSys
 * Author URI: https://alphasys.com.au
 * Text Domain: as-gated-conten
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ASGC_VERSION', '0.1.0');
define('ASGC_PLUGIN_FILE', __FILE__);
define('ASGC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASGC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASGC_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ASGC_GITHUB_OWNER', 'cchatterton');
define('ASGC_GITHUB_REPO', 'as-gated-conten');
define('ASGC_PLUGIN_SLUG', 'as-gated-conten');
define('ASGC_RELEASE_ASSET', 'as-gated-conten.zip');

require_once ASGC_PLUGIN_DIR . 'functions/helpers.php';
require_once ASGC_PLUGIN_DIR . 'functions/post-types.php';
require_once ASGC_PLUGIN_DIR . 'functions/gravity-forms.php';
require_once ASGC_PLUGIN_DIR . 'functions/meta-boxes.php';
require_once ASGC_PLUGIN_DIR . 'functions/frontend.php';
require_once ASGC_PLUGIN_DIR . 'functions/github-updater.php';
