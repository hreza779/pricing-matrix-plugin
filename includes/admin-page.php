<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Adds the admin menu page
function mpm_add_admin_menu() {
    add_menu_page(
        'Pricing Matrices', 'Pricing Matrices', 'manage_options',
        'pricing_matrices', 'mpm_settings_page_html', 'dashicons-forms', 26
    );
}

// Renders the HTML for the settings page
function mpm_settings_page_html() {
    if (!current_user_can('manage_options')) return;

    $current_product_id = 0;
    if (isset($_POST['mpm_action']) && $_POST['mpm_action'] == 'load_attributes' && isset($_POST['mpm_product_id'])) {
        $current_product_id = intval($_POST['mpm_product_id']);
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <p>Manage your product pricing matrices here. Create a new configuration and then copy its shortcode to any page.</p>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'saved'): ?>
            <div id="message" class="updated notice is-dismissible"><p>Configuration saved successfully.</p></div>
        <?php elseif (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
             <div id="message" class="updated notice is-dismissible"><p>Configuration deleted successfully.</p></div>
        <?php endif; ?>

        <h2>Existing Configurations</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th style="width:20%;">Product</th><th style="width:50%;">Shortcode</th><th style="width:20%;">Actions</th></tr></thead>
            <tbody>
                <?php
                $configs = get_option('mpm_product_configs', []);
                if (empty($configs)) {
                    echo '<tr><td colspan="3">No configurations found. Add one below.</td></tr>';
                } else {
                    foreach ($configs as $id => $config) {
                        $product = wc_get_product($config['product_id']);
                        $delete_url = wp_nonce_url(admin_url('admin.php?page=pricing_matrices&action=mpm_delete&config_id=' . $id), 'mpm_delete_nonce');
                        ?>
                        <tr>
                            <td><?php echo $product ? esc_html($product->get_name()) : 'Product not found'; ?></td>
                            <td><input type="text" value="[pricing_matrix id=&quot;<?php echo esc_attr($id); ?>&quot;]" readonly onfocus="this.select();" style="width: 100%;"></td>
                            <td><a href="<?php echo esc_url($delete_url); ?>" class="button button-secondary">Delete</a></td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>

        <hr style="margin: 30px 0;">
        <h2>Add New Configuration</h2>
        <form method="post" action="<?php echo admin_url('admin.php?page=pricing_matrices'); ?>">
            <?php wp_nonce_field('mpm_save_nonce'); ?>
            <table class="form-table">
                 <tr valign="top">
                    <th scope="row"><label for="mpm_product_id">1. Select Product</label></th>
                    <td>
                        <select id="mpm_product_id" name="mpm_product_id" style="width: 25em;">
                            <option value="">-- Select a Product --</option>
                            <?php
                            $products = new WP_Query(['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish', 'tax_query' => [['taxonomy' => 'product_type', 'field' => 'slug', 'terms' => 'variable']]]);
                            if ($products->have_posts()) {
                                while ($products->have_posts()) {
                                    $products->the_post();
                                    echo '<option value="' . get_the_ID() . '" ' . selected($current_product_id, get_the_ID(), false) . '>' . get_the_title() . '</option>';
                                }
                                wp_reset_postdata();
                            }
                            ?>
                        </select>
                        <button type="submit" name="mpm_action" value="load_attributes" class="button">Load Attributes</button>
                    </td>
                </tr>
                <?php 
                if ($current_product_id): 
                    $product = wc_get_product($current_product_id);
                    $attributes = $product ? $product->get_variation_attributes() : [];
                ?>
                <tr valign="top">
                    <th scope="row"><label for="mpm_tab_attribute">2. Attribute for Tabs</label></th>
                    <td><select id="mpm_tab_attribute" name="mpm_tab_attribute" required><option value="">-- Select --</option><?php foreach ($attributes as $name => $options): ?><option value="<?php echo esc_attr($name); ?>"><?php echo esc_html(wc_attribute_label(str_replace('pa_', '', $name))); ?></option><?php endforeach; ?></select></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="mpm_column_attribute">3. Attribute for Columns</label></th>
                    <td><select id="mpm_column_attribute" name="mpm_column_attribute" required><option value="">-- Select --</option><?php foreach ($attributes as $name => $options): ?><option value="<?php echo esc_attr($name); ?>"><?php echo esc_html(wc_attribute_label(str_replace('pa_', '', $name))); ?></option><?php endforeach; ?></select></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="mpm_row_attribute">4. Attribute for Rows</label></th>
                    <td><select id="mpm_row_attribute" name="mpm_row_attribute" required><option value="">-- Select --</option><?php foreach ($attributes as $name => $options): ?><option value="<?php echo esc_attr($name); ?>"><?php echo esc_html(wc_attribute_label(str_replace('pa_', '', $name))); ?></option><?php endforeach; ?></select></td>
                </tr>
                 <tr>
                    <th scope="row"></th>
                    <td><button type="submit" name="mpm_action" value="save_config" class="button button-primary">Save Configuration</button></td>
                </tr>
                <?php endif; ?>
            </table>
        </form>
    </div>
    <?php
}