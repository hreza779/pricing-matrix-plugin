<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Registers the shortcode
function mpm_register_shortcode() {
    add_shortcode('pricing_matrix', 'mpm_display_shortcode');
}

// The function that generates the table based on a config ID
function mpm_display_shortcode($atts) {
    $atts = shortcode_atts(['id' => ''], $atts, 'pricing_matrix');
    $config_id = $atts['id'];
    if (empty($config_id)) { return '<p>Error: No "id" provided in the shortcode. e.g., [pricing_matrix id="..."]</p>'; }
    
    $all_configs = get_option('mpm_product_configs', []);
    if (!isset($all_configs[$config_id])) { return '<p>Error: Configuration with id "' . esc_attr($config_id) . '" not found.</p>'; }
    
    $config = $all_configs[$config_id];
    $product_id = $config['product_id'];
    $tab_attr_name = $config['tab_attr'];
    $col_attr_name = $config['col_attr'];
    $row_attr_name = $config['row_attr'];
    
    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('variable')) { return '<p>Selected product is not a valid variable product.</p>'; }
    
    $tab_terms = wc_get_product_terms($product_id, $tab_attr_name, ['fields' => 'all']);
    $col_terms = wc_get_product_terms($product_id, $col_attr_name, ['fields' => 'all']);
    $row_terms = wc_get_product_terms($product_id, $row_attr_name, ['fields' => 'all']);
    
    $variation_map = [];
    $variations = $product->get_available_variations();
    foreach ($variations as $variation) {
        $tab_val = $variation['attributes']['attribute_' . $tab_attr_name] ?? null;
        $col_val = $variation['attributes']['attribute_' . $col_attr_name] ?? null;
        $row_val = $variation['attributes']['attribute_' . $row_attr_name] ?? null;
        if ($tab_val && $col_val && $row_val) {
            $variation_map[$tab_val][$row_val][$col_val] = $variation['price_html'];
        }
    }
    
    ob_start();
    $unique_id = 'cpm-tabs-' . esc_attr($config_id);
    ?>
    <style>
        .cpm-tabs-wrapper { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .cpm-tabs-nav { display: flex; list-style-type: none; margin: 0 0 20px 0; padding: 0; border-bottom: none; }
        .cpm-tabs-nav a { display: block; padding: 10px 25px; text-decoration: none; margin-right: 10px; border-radius: 30px; font-weight: 500; transition: all 0.3s ease; background-color: #fff; border: 1px solid #e0e0e0; color: #555; }
        .cpm-tabs-nav li:not(.active) a:hover { border-color: #1956FF; color: #1956FF; }
        .cpm-tabs-nav li.active a { background-color: #1956FF; color: #ffffff; border-color: #1956FF; font-weight: 600; box-shadow: 0 4px 10px rgba(25, 86, 255, 0.3); }
        .cpm-tab-content { border-radius: 12px; }
        .cpm-tab-pane { display: none; } .cpm-tab-pane.active { display: block; }
        .pricing-matrix-table { width: 100%; border-collapse: separate; border-spacing: 0; text-align: center; border: 1px solid #e0e0e0; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.07); }
        .pricing-matrix-table th, .pricing-matrix-table td { padding: 16px; border-bottom: 1px solid #e0e0e0; }
        .pricing-matrix-table tbody tr:last-child th, .pricing-matrix-table tbody tr:last-child td { border-bottom: none; }
        .pricing-matrix-table thead th { background-color: #1956FF; color: #ffffff; font-size: 1.1em; font-weight: 600; }
        .pricing-matrix-table tbody th { background-color: #fcfcfc; text-align: left; font-weight: 600; border-right: 1px solid #e0e0e0; }
        .pricing-matrix-table .unavailable { color: #999; font-style: italic; }
        .pricing-matrix-table tbody tr:hover { background-color: #f5f5f5; }
    </style>
     <div class="cpm-tabs-wrapper" id="<?php echo esc_attr($unique_id); ?>">
        <ul class="cpm-tabs-nav">
            <?php foreach ($tab_terms as $index => $term): ?>
                <li class="<?php echo $index === 0 ? 'active' : ''; ?>"><a href="#tab-<?php echo esc_attr($unique_id . '-' . $term->slug); ?>"><?php echo esc_html($term->name); ?></a></li>
            <?php endforeach; ?>
        </ul>
        <div class="cpm-tab-content">
            <?php foreach ($tab_terms as $index => $term): ?>
                <div id="tab-<?php echo esc_attr($unique_id . '-' . $term->slug); ?>" class="cpm-tab-pane <?php echo $index === 0 ? 'active' : ''; ?>">
                    <table class="pricing-matrix-table">
                        <thead>
                            <tr>
                                <th></th>
                                <?php foreach ($col_terms as $col_term): ?><th><?php echo esc_html($col_term->name); ?></th><?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($row_terms as $row_term): ?>
                                <tr>
                                    <th><?php echo esc_html($row_term->name); ?></th>
                                    <?php foreach ($col_terms as $col_term): ?>
                                        <td><?php echo $variation_map[$term->slug][$row_term->slug][$col_term->slug] ?? '<span class="unavailable">ناموجود</span>'; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var wrapper = document.getElementById('<?php echo esc_js($unique_id); ?>');
            if (!wrapper) return;
            var navLinks = wrapper.querySelectorAll('.cpm-tabs-nav a');
            var tabPanes = wrapper.querySelectorAll('.cpm-tab-pane');
            navLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    navLinks.forEach(function(l) { l.parentElement.classList.remove('active'); });
                    tabPanes.forEach(function(p) { p.classList.remove('active'); });
                    var targetId = this.getAttribute('href');
                    this.parentElement.classList.add('active');
                    wrapper.querySelector(targetId).classList.add('active');
                });
            });
        });
    </script>
    <?php
    return ob_get_clean();
}