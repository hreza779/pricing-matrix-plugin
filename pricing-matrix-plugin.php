<?php
/**
 * Plugin Name:       Multi-Product Pricing Matrix
 * Description:       Creates manageable, tabbed pricing matrix tables for multiple WooCommerce products using unique shortcodes.
 * Version:           1.1.0 (File-structured)
 * Author:            Your Name
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mpm-matrix
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin path for easy access
define('MPM_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include the necessary files
require_once(MPM_PLUGIN_PATH . 'includes/actions.php');
require_once(MPM_PLUGIN_PATH . 'includes/admin-page.php');
require_once(MPM_PLUGIN_PATH . 'includes/shortcode.php');

// Register the admin menu
add_action('admin_menu', 'mpm_add_admin_menu');

// Register the shortcode
add_action('init', 'mpm_register_shortcode');