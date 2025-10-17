<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Handle Save and Delete actions
function mpm_handle_form_actions() {
    if (!current_user_can('manage_options')) return;

    // Action: Save a new configuration
    if (isset($_POST['mpm_action']) && $_POST['mpm_action'] == 'save_config' && check_admin_referer('mpm_save_nonce')) {
        $configs = get_option('mpm_product_configs', []);
        
        $new_config = [
            'product_id' => intval($_POST['mpm_product_id']),
            'tab_attr'   => sanitize_text_field($_POST['mpm_tab_attribute']),
            'col_attr'   => sanitize_text_field($_POST['mpm_column_attribute']),
            'row_attr'   => sanitize_text_field($_POST['mpm_row_attribute']),
        ];

        if ($new_config['product_id'] && $new_config['tab_attr'] && $new_config['col_attr'] && $new_config['row_attr']) {
            $config_id = 'config_' . time();
            $configs[$config_id] = $new_config;
            update_option('mpm_product_configs', $configs);
            
            wp_redirect(admin_url('admin.php?page=pricing_matrices&status=saved'));
            exit;
        }
    }

    // Action: Delete a configuration
    if (isset($_GET['action']) && $_GET['action'] == 'mpm_delete' && isset($_GET['config_id']) && check_admin_referer('mpm_delete_nonce')) {
        $configs = get_option('mpm_product_configs', []);
        $config_id_to_delete = sanitize_key($_GET['config_id']);

        if (isset($configs[$config_id_to_delete])) {
            unset($configs[$config_id_to_delete]);
            update_option('mpm_product_configs', $configs);
        }

        wp_redirect(admin_url('admin.php?page=pricing_matrices&status=deleted'));
        exit;
    }
}
add_action('admin_init', 'mpm_handle_form_actions');