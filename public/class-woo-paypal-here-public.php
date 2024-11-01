<?php

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Woo_PayPal_Here
 * @subpackage Woo_PayPal_Here/public
 * @author     Angell EYE <service@angelleye.com>
 */
class Woo_PayPal_Here_Public {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;
    public $checkout;
    public $order;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->checkout = new Woo_PayPal_Here_Checkout();
        $this->home_url = is_ssl() ? home_url('/', 'https') : home_url('/');
        $this->paypal_here_settings = get_option('woocommerce_angelleye_woo_paypal_here_settings');
        $this->paypal_here_endpoint_url = !empty($this->paypal_here_settings['paypal_here_endpoint_url']) ? $this->paypal_here_settings['paypal_here_endpoint_url'] : 'paypal-here';
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        global $wp_query, $wp, $wp_styles, $wp_scripts;
        $wp->query_vars;
        if (!is_null($wp_query) && !is_admin() && is_main_query() && !empty($wp->query_vars['name']) && $wp->query_vars['name'] == 'paypal-here') {
            wp_register_style('jquery-ui-styles', plugin_dir_url(__FILE__) . 'css/jquery-ui.min.css', array(), $this->version, 'all');
            wp_enqueue_style($this->plugin_name . 'bootstrap_css', plugin_dir_url(__FILE__) . 'css/bootstrap.min.css', array(), $this->version, 'all');
            wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/woo-paypal-here-public.css', array(), $this->version, 'all');
        }
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        global $wp_query, $wp;
        $wp->query_vars;
        if (!is_null($wp_query) && !is_admin() && is_main_query() && !empty($wp->query_vars['name']) && $wp->query_vars['name'] == 'paypal-here') {
            wp_enqueue_script($this->plugin_name . 'popper', plugin_dir_url(__FILE__) . 'js/popper.min.js', array('jquery'), $this->version, false);
            wp_enqueue_script($this->plugin_name . 'bootstrap_js', plugin_dir_url(__FILE__) . 'js/bootstrap.min.js', array('jquery'), $this->version, false);
            wp_enqueue_script('jquery-ui-autocomplete');
            wp_enqueue_script($this->plugin_name . 'input_button', plugin_dir_url(__FILE__) . 'js/bootstrap-number-input.js', array('jquery'), $this->version, false);
            wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/woo-paypal-here-public.js', array('jquery', $this->plugin_name . 'input_button'), $this->version, true);
            wp_localize_script($this->plugin_name, 'paypal_here_ajax_param', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'paypal_here_nonce' => wp_create_nonce('paypal_here_nonce'),
                'is_create_new_order' => (!empty($_GET['is_create_new_order']) && $_GET['is_create_new_order'] == 'true') ? 'yes' : 'no'
                    )
            );
        }
    }

    public function is_angelleye_woo_paypal_here_endpoint_url($title = false) {
        global $wp;
        $this->paypal_here_settings = get_option('woocommerce_angelleye_woo_paypal_here_settings');
        if (!empty($wp->query_vars)) {
            if (!empty($this->paypal_here_settings['paypal_here_endpoint_url']) && !empty($wp->query_vars['name']) && $wp->query_vars['name'] == $this->paypal_here_settings['paypal_here_endpoint_url'] && strpos($title, 'not found') !== false) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function angelleye_woo_paypal_here_locate_template($template, $template_name, $template_path) {
        global $wp_query, $wp;
        $wp->query_vars;
        global $woocommerce;
        if (!is_null($wp_query) && !is_admin() && is_main_query() && !empty($wp->query_vars['name']) && $wp->query_vars['name'] == 'paypal-here') {
            $_template = $template;
            if (!$template_path)
                $template_path = $woocommerce->template_url;

            $plugin_path = WOO_PAYPAL_HERE_PLUGIN_DIR . '/templates/';
            $template = locate_template(
                    array(
                        $template_path . $template_name,
                        $template_name
                    )
            );
            if (!$template && file_exists($plugin_path . $template_name))
                $template = $plugin_path . $template_name;

            if (!$template)
                $template = $_template;

            // Return what we found
            return $template;
        } else {
            return $template;
        }
    }

    public function paypal_here_get_modal_body() {
        ob_start();
        global $wpdb, $woocommerce, $post;

        $product_id = absint($_POST['product_id']);
        check_ajax_referer('paypal_here_nonce', 'security');
        $product = wc_get_product($product_id);
        setup_postdata($product);
        $GLOBALS['post'] = $product;
        $GLOBALS['product'] = $product;
        wp_enqueue_script('wc-add-to-cart-variation', WOO_PAYPAL_HERE_ASSET_URL . 'public/js/add-to-cart-variation.js', array('jquery', 'wp-util'), '10', true);
        ?>
        <script>
            var wc_add_to_cart_variation_params = {"ajax_url": "\/wp-admin\/admin-ajax.php"};
            jQuery.getScript("<?php echo WOO_PAYPAL_HERE_ASSET_URL; ?>public/js/add-to-cart-variation.js");
        </script>
        <?php
        $input_id = uniqid('quantity_');
        $input_name = 'quantity';
        $input_value = '1';
        $min_value = apply_filters('woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product);
        $max_value = apply_filters('woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product);
        $min_value = max($min_value, 0);
        $max_value = 0 < $max_value ? $max_value : '';
        if ('' !== $max_value && $max_value < $min_value) {
            $max_value = $min_value;
        }
        $step = apply_filters('woocommerce_quantity_input_step', 1, $product);
        $pattern = apply_filters('woocommerce_quantity_input_pattern', has_filter('woocommerce_stock_amount', 'intval') ? '[0-9]*' : '');
        $inputmode = apply_filters('woocommerce_quantity_input_inputmode', has_filter('woocommerce_stock_amount', 'intval') ? 'numeric' : '');
        ?>
        <div class="container-fluid product">
            <?php
            if ($product->get_type() == 'variable') {
                $get_variations = count($product->get_children()) <= apply_filters('woocommerce_ajax_variation_threshold', 30, $product);
                $available_variations = $get_variations ? $product->get_available_variations() : false;
                $attributes = $product->get_variation_attributes();
                $selected_attributes = $product->get_default_attributes();
                ?>
                <form class="variations_form cart" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint($product->get_id()); ?>" data-product_variations="<?php echo htmlspecialchars(wp_json_encode($available_variations)) ?>">
                <?php } else { ?>
                    <form class="variations_form cart">
                    <?php } ?> 

                    <div class="row form-group  variations">
                        <div class="col-9 col-sm-9">
                            <input name="variation_id" class="variation_id" value="" type="hidden">
                            <input type="hidden" name="add-to-cart" value="<?php echo $product->get_id(); ?>">
                            <span><?php echo $product->get_title(); ?></span>
                        </div>
                        <div class="col-3 col-sm-3 tal price_html">
                            <?php echo $product->get_price_html(); ?>
                        </div>
                        <div class="col-12 col-sm-12 mtonerem">
                            <?php if ($max_value && $min_value === $max_value) {
                                ?>
                                <div class="quantity hidden form-group">
                                    <input type="hidden" id="<?php echo esc_attr($input_id); ?>" class="input-text qty text form-control paypal_here_number_input" name="<?php echo esc_attr($input_name); ?>" value="<?php echo esc_attr($min_value); ?>" />
                                </div>
                                <?php
                            } else {
                                ?>
                                <div class="quantity form-group">
                                    <input type="number" id="<?php echo esc_attr($input_id); ?>" class="input-text qty text form-control paypal_here_number_input" step="<?php echo esc_attr($step); ?>" min="<?php echo esc_attr($min_value); ?>" max="<?php echo esc_attr(0 < $max_value ? $max_value : empty($max_value) ? 99 : '' ); ?>" name="<?php echo esc_attr($input_name); ?>" value="<?php echo esc_attr($input_value); ?>" title="<?php echo esc_attr_x('Qty', 'Product quantity input tooltip', 'woocommerce') ?>" size="4" pattern="<?php echo esc_attr($pattern); ?>" inputmode="<?php echo esc_attr($inputmode); ?>" />
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                    if ($product->get_type() == 'variable') {

                        $get_variations = count($product->get_children()) <= apply_filters('woocommerce_ajax_variation_threshold', 30, $product);
                        $available_variations = $get_variations ? $product->get_available_variations() : false;
                        $attributes = $product->get_variation_attributes();
                        $attribute_keys = array_keys($attributes);
                        $selected_attributes = $product->get_default_attributes();

                        if (empty($available_variations) && false !== $available_variations) {
                            ?>
                            <p class="stock out-of-stock"><?php _e('This product is currently out of stock and unavailable.', 'woocommerce'); ?></p>
                        <?php } else { ?>

                            <div class="variations" cellspacing="0">

                                <?php foreach ($attributes as $name => $options) : ?>
                                    <div class=" clearfix attribute-<?php echo sanitize_title($name); ?>">
                                        <div class="label">

                                            <hr class="hr-text" data-content="<?php echo wc_attribute_label($name); ?>">
                                        </div>
                                        <?php
                                        $sanitized_name = sanitize_title($name);
                                        if (isset($_REQUEST['attribute_' . $sanitized_name])) {
                                            $checked_value = $_REQUEST['attribute_' . $sanitized_name];
                                        } elseif (isset($selected_attributes[$sanitized_name])) {
                                            $checked_value = $selected_attributes[$sanitized_name];
                                        } else {
                                            $checked_value = '';
                                        }
                                        ?>
                                        <div class="value default-ceneter-button">
                                            <?php
                                            if (!empty($options)) {
                                                if (taxonomy_exists($name)) {
                                                    // Get terms if this is a taxonomy - ordered. We need the names too.
                                                    $terms = wc_get_product_terms($product->get_id(), $name, array('fields' => 'all'));

                                                    foreach ($terms as $term) {
                                                        if (!in_array($term->slug, $options)) {
                                                            continue;
                                                        }
                                                        print_attribute_radio($checked_value, $term->slug, $term->name, $sanitized_name);
                                                    }
                                                } else {
                                                    foreach ($options as $option) {
                                                        print_attribute_radio($checked_value, $option, $option, $sanitized_name);
                                                    }
                                                }
                                            }

                                            echo end($attribute_keys) === $name ? apply_filters('woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . __('Clear', 'woocommerce') . '</a>') : '';
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php
                            }
                        }
                        ?>
                </form>
        </div>
        <?php
        $data['html'] = ob_get_clean();
        wp_send_json_success($data);
    }

    public function paypal_here_add_to_cart() {
        try {
            global $wpdb, $post, $product;
            $product_id = '';
            if (!defined('WOOCOMMERCE_CART')) {
                define('WOOCOMMERCE_CART', true);
            }
            check_ajax_referer('paypal_here_nonce', 'security');
            if (!empty($_POST['is_create_new_order']) && $_POST['is_create_new_order'] == 'yes') {
                WC()->cart->empty_cart();
                paypal_here_unset_session('angelleye_woo_paypal_here_order_awaiting_payment');
            }
            WC()->shipping->reset_shipping();
            if (empty($post->ID)) {
                $product_id = absint($_POST['product_id']);
            } else {
                $product_id = $post->ID;
            }
            $product = wc_get_product($product_id);
            if (is_object($product)) {
                if (!defined('WOOCOMMERCE_CART')) {
                    define('WOOCOMMERCE_CART', true);
                }
                $qty = !isset($_POST['qty']) ? 1 : absint($_POST['qty']);
                if ($product->is_type('variable')) {
                    $attributes = array_map('wc_clean', $_POST['attributes']);
                    if (version_compare(WC_VERSION, '3.0', '<')) {
                        $variation_id = $product->get_matching_variation($attributes);
                    } else {
                        $data_store = WC_Data_Store::load('product');
                        $variation_id = $data_store->find_matching_product_variation($product, $attributes);
                    }
                    WC()->cart->add_to_cart($product->get_id(), $qty, $variation_id, $attributes);
                } elseif ($product->is_type('simple')) {
                    WC()->cart->add_to_cart($product->get_id(), $qty);
                }
                WC()->shipping->reset_shipping();
                WC()->cart->calculate_totals();
                $order_id = $this->checkout->create_order(array());
                $order = wc_get_order($order_id);
                $billing_address = paypal_here_get_session('billing_address');
                if (!empty($billing_address)) {
                    paypal_here_set_address($order_id, $billing_address);
                }
                $shipping_address = paypal_here_get_session('shipping_address');
                if (!empty($shipping_address)) {
                    paypal_here_set_address($order_id, $shipping_address);
                }
                $order->calculate_totals();
                WC()->cart->empty_cart();
                paypal_here_set_session('angelleye_woo_paypal_here_order_awaiting_payment', $order_id);
                if (is_wp_error($order_id)) {
                    $this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders', 'order_id' => $order_id), $this->home_url . $this->paypal_here_endpoint_url));
                } else {
                    $this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders', 'order_id' => $order_id), $this->home_url . $this->paypal_here_endpoint_url));
                }
            }
        } catch (Exception $ex) {
            wc_add_notice($ex->getMessage(), 'error');
            $this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders'), $this->home_url . $this->paypal_here_endpoint_url));
        }
    }

    public function paypal_here_paypal_here_delete_order_item() {
        check_ajax_referer('paypal_here_nonce', 'security');
        try {
            $item_id = absint($_POST['item_id']);
            wc_delete_order_item($item_id);
            $this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders'), $this->home_url . $this->paypal_here_endpoint_url));
        } catch (Exception $ex) {
            
        }
    }

    public function paypal_here_paypal_here_delete_order() {
        check_ajax_referer('paypal_here_nonce', 'security');
        try {
            $order_id = absint($_POST['order_id']);
            wp_delete_post($order_id, true);
            $this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders'), $this->home_url . $this->paypal_here_endpoint_url));
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_woo_paypal_here_redirect($location) {
        if (is_ajax()) {
            wp_send_json(array(
                'result' => 'success',
                'redirect' => $location
            ));
            exit();
        } else {
            wp_redirect($location);
            exit();
        }
    }

    public function paypal_here_apply_coupon_handler($coupon_code, $order_id, $is_delete = false) {
        if (!empty($coupon_code)) {
            if (!empty($coupon_code) && WC()->cart->is_empty() == false) {
                WC()->cart->apply_coupon($coupon_code);
                WC()->shipping->reset_shipping();
                WC()->cart->calculate_totals();
                $order_id = $this->checkout->create_order(array());
                $order = wc_get_order($order_id);
                $order->calculate_totals();
                paypal_here_set_session('angelleye_woo_paypal_here_order_awaiting_payment', $order_id);
                if (is_wp_error($order_id)) {
                    throw new Exception($order_id->get_error_message());
                } else {
                    if ($is_delete == true) {
                        $coupon = new WC_Coupon($coupon_code);
                        if (!empty($coupon)) {
                            $coupon->delete(true);
                        }
                    }
                    $this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders', 'order_id' => $order_id), $this->home_url . $this->paypal_here_endpoint_url));
                }
            } else {
                if (!empty($order_id) && !empty($coupon_code)) {
                    $order_id = $order_id;
                    $this->order = wc_get_order($order_id);
                    $coupons_item = $this->order->get_items('coupon');
                    foreach ($coupons_item as $item_id => $ex_coupon) {
                        $this->order->remove_coupon($ex_coupon->get_code());
                    }
                    try {
                        $return = $this->order->apply_coupon($coupon_code);
                        if ($is_delete == true) {
                            $coupon = new WC_Coupon($coupon_code);
                            if (!empty($coupon)) {
                                $coupon->delete(true);
                            }
                        }
                        if (is_wp_error($return)) {
                            wc_add_notice($return->get_error_message(), 'error');
                            $this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders', 'order_id' => $order_id), $this->home_url . $this->paypal_here_endpoint_url));
                        }
                        if ($return == true) {
                            wc_add_notice(__('Coupon code applied successfully.', 'woocommerce'), 'success');
                            $this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders', 'order_id' => $order_id), $this->home_url . $this->paypal_here_endpoint_url));
                        }
                    } catch (Exception $ex) {
                        wc_add_notice($ex->getMessage(), 'error');
                        $this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders', 'order_id' => $order_id), $this->home_url . $this->paypal_here_endpoint_url));
                    }
                }
            }
        }
    }

    public function paypal_here_apply_coupon() {
        if (!empty($_POST['coupon_code']) && !empty($_POST['order_id'])) {
            $coupon_code = wc_clean($_POST['coupon_code']);
            $order_id = absint($_POST['order_id']);
            $this->paypal_here_apply_coupon_handler($coupon_code, $order_id);
        } elseif (!empty($_POST['paypal_here_percentage']) && !empty($_POST['order_id'])) {
            $arg = array('coupon_code' => 'Discount_PayPal_Here' . wp_rand(1, 10000),
                'amount' => str_replace('%', '', wc_clean($_POST['paypal_here_percentage'])),
                'discount_type' => 'percent'
            );
            $order_id = absint($_POST['order_id']);
            $this->paypal_here_create_coupon($arg);
            $this->paypal_here_apply_coupon_handler($arg['coupon_code'], $order_id, true);
        } elseif (!empty($_POST['paypal_here_amount']) && !empty($_POST['order_id'])) {
            $arg = array('coupon_code' => 'Discount_PayPal_Here' . wp_rand(1, 10000),
                'amount' => str_replace('$', '', wc_clean($_POST['paypal_here_amount'])),
                'discount_type' => 'fixed_cart'
            );
            $order_id = absint($_POST['order_id']);
            $this->paypal_here_create_coupon($arg);
            $this->paypal_here_apply_coupon_handler($arg['coupon_code'], $order_id, true);
        }
    }

    public function paypal_here_create_coupon($arg) {
        $coupon_code = $arg['coupon_code'];
        $amount = $arg['amount'];
        $discount_type = $arg['discount_type'];
        $coupon = array(
            'post_title' => $coupon_code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon'
        );
        $new_coupon_id = wp_insert_post($coupon);
        update_post_meta($new_coupon_id, 'discount_type', $discount_type);
        update_post_meta($new_coupon_id, 'coupon_amount', $amount);
        update_post_meta($new_coupon_id, 'individual_use', 'no');
        update_post_meta($new_coupon_id, 'product_ids', '');
        update_post_meta($new_coupon_id, 'exclude_product_ids', '');
        update_post_meta($new_coupon_id, 'usage_limit', '');
        update_post_meta($new_coupon_id, 'expiry_date', '');
        update_post_meta($new_coupon_id, 'apply_before_tax', 'no');
        update_post_meta($new_coupon_id, 'free_shipping', 'no');
    }

    public function send_to_paypal_here_action() {
        if (!class_exists('Woo_PayPal_Here_Payment')) {
            require_once WOO_PAYPAL_HERE_PLUGIN_DIR . 'includes/class-woo-paypal-here-payment.php';
        }
        $payment_gateway = new Woo_PayPal_Here_Payment();
        $payment_gateway->angelleye_woo_paypal_here_process_payment();
    }

    public function paypal_here_apply_tax() {
        try {
			$order_id = absint( $_POST['order_id'] );
			$rate_id  = absint( $_POST['rate_id'] );
			$order    = wc_get_order( $order_id );
			$data     = get_post_meta( $order_id );

			// Add new tax
			$item = new WC_Order_Item_Tax();
			$item->set_rate( $rate_id );
			$item->set_order_id( $order_id );
			$item->save();
                        wc_add_notice('Order updated.', 'success');
			ob_start();
			

			$this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders', 'order_id' => $order->get_id()), $this->home_url . $this->paypal_here_endpoint_url));
		} catch ( Exception $e ) {
			$this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders', 'order_id' => $order->get_id()), $this->home_url . $this->paypal_here_endpoint_url));
		}
    }

    public function paypal_here_apply_shipping() {
        $shipping['method_title'] = 'Others';
        $shipping['method_id'] = 'paypal_here';
        $order_id = absint($_POST['order_id']);
        $order = wc_get_order($order_id);
        if (isset($_POST['paypal_here_shipping_postal_code']) && !empty($_POST['paypal_here_shipping_postal_code'])) {
            $loop = 0;
            for ($loop = 0; $loop < 4; $loop++) {
                $bool = $this->angelleye_woo_paypal_here_get_country_code(wc_clean($_POST['paypal_here_shipping_postal_code']));
                if ($bool !== false) {
                    update_post_meta($order_id, '_billing_country', $bool);
                    update_post_meta($order_id, '_shipping_country', $bool);
                    if (WC()->cart->is_empty()) {
                        foreach ($order->get_items() as $item_id => $item) {
                            $product = $item->get_product();
                            if ($item->get_variation_id()) {
                                $variation_id = $item->get_variation_id();
                                WC()->cart->add_to_cart($product->get_id(), $item->get_quantity(), $variation_id, $attributes = null);
                            } else {
                                $item->get_product_id();
                                WC()->cart->add_to_cart($product->get_id(), $item->get_quantity());
                            }
                        }
                    }
                    $order->remove_order_items('shipping');
                    WC()->customer->set_props(array(
                        'billing_country' => isset($bool) ? wp_unslash($bool) : null,
                        'billing_state' => isset($_POST['state']) ? wp_unslash($_POST['state']) : null,
                        'billing_postcode' => isset($_POST['paypal_here_shipping_postal_code']) ? wp_unslash($_POST['paypal_here_shipping_postal_code']) : null,
                        'billing_city' => isset($_POST['city']) ? wp_unslash($_POST['city']) : null,
                        'billing_address_1' => isset($_POST['address']) ? wp_unslash($_POST['address']) : null,
                        'billing_address_2' => isset($_POST['address_2']) ? wp_unslash($_POST['address_2']) : null,
                    ));
                    if (wc_ship_to_billing_address_only()) {
                        WC()->customer->set_props(array(
                            'shipping_country' => isset($bool) ? wp_unslash($bool) : null,
                            'shipping_state' => isset($_POST['state']) ? wp_unslash($_POST['state']) : null,
                            'shipping_postcode' => isset($_POST['paypal_here_shipping_postal_code']) ? wp_unslash($_POST['paypal_here_shipping_postal_code']) : null,
                            'shipping_city' => isset($_POST['city']) ? wp_unslash($_POST['city']) : null,
                            'shipping_address_1' => isset($_POST['address']) ? wp_unslash($_POST['address']) : null,
                            'shipping_address_2' => isset($_POST['address_2']) ? wp_unslash($_POST['address_2']) : null,
                        ));
                    } else {
                        WC()->customer->set_props(array(
                            'shipping_country' => isset($bool) ? wp_unslash($bool) : null,
                            'shipping_state' => isset($_POST['s_state']) ? wp_unslash($_POST['s_state']) : null,
                            'shipping_postcode' => isset($_POST['paypal_here_shipping_postal_code']) ? wp_unslash($_POST['paypal_here_shipping_postal_code']) : null,
                            'shipping_city' => isset($_POST['s_city']) ? wp_unslash($_POST['s_city']) : null,
                            'shipping_address_1' => isset($_POST['s_address']) ? wp_unslash($_POST['s_address']) : null,
                            'shipping_address_2' => isset($_POST['s_address_2']) ? wp_unslash($_POST['s_address_2']) : null,
                        ));
                    }
                    WC()->shipping->reset_shipping();
                    WC()->customer->set_calculated_shipping(true);
                    WC()->customer->save();
                    WC()->cart->calculate_totals();
                    $shipping['total'] = WC()->cart->shipping_total;
                    break;
                }
            }
            update_post_meta($order_id, '_shipping_postcode', wc_clean($_POST['paypal_here_shipping_postal_code']));
            update_post_meta($order_id, '_billing_postcode', wc_clean($_POST['paypal_here_shipping_postal_code']));
            WC()->cart->empty_cart();
            $this->paypal_here_apply_shipping_handler($order, $shipping);
        } elseif (isset($_POST['paypal_here_shipping_percentage']) && !empty ($_POST['paypal_here_shipping_percentage'])) {
            $paypal_here_shipping_percentage = str_replace('%', '', wc_clean($_POST['paypal_here_shipping_percentage']));
            $shipping['total'] = number_format(( $order->get_subtotal() * ( $paypal_here_shipping_percentage / 100 )), 2);
            $this->paypal_here_apply_shipping_handler($order, $shipping);
        } elseif (isset($_POST['paypal_here_shipping_dollar']) && !empty ($_POST['paypal_here_shipping_dollar'])) {
            $shipping['total'] = wc_clean($_POST['paypal_here_shipping_dollar']);
            $this->paypal_here_apply_shipping_handler($order, $shipping);
        }
    }

    public function paypal_here_apply_shipping_handler($order, $shipping) {
        if (isset($shipping['total'])) {
            $shipping_total = number_format($shipping['total'], 2, '.', '');
            if ($shipping_total == '0.00') {
                $shipping['method_title'] = 'Free!';
            }
        }
        $rate = new WC_Shipping_Rate($shipping['method_id'], isset($shipping['method_title']) ? $shipping['method_title'] : '', isset($shipping['total']) ? $shipping_total : 0, array(), $shipping['method_id']);
        $item = new WC_Order_Item_Shipping();
        $item->set_order_id($order->get_id());
        $item->set_shipping_rate($rate);
        $order->remove_order_items('shipping');
        $order->add_item($item);
        $order->calculate_totals(true);
        $this->angelleye_woo_paypal_here_redirect(add_query_arg(array('actions' => 'view_pending_orders', 'order_id' => $order->get_id()), $this->home_url . $this->paypal_here_endpoint_url));
    }

    public function woo_paypal_here_available_payment_gateways($available_payment_gateways) {
        unset($available_payment_gateways['angelleye_woo_paypal_here']);
        return $available_payment_gateways;
    }

    public function paypal_here_register_session() {
        if (!session_id()) {
            session_start();
        }
    }

    public function angelleye_woo_paypal_here_get_country_code($post_code) {
        $response = wp_remote_get("http://maps.googleapis.com/maps/api/geocode/json?address=$post_code&sensor=false");
        if (!is_wp_error($response)) {
            if (!empty($response['body'])) {
                $output = json_decode($response['body']);
                if (isset($output->results)) {
                    if (isset($output->results[0])) {
                        if (isset($output->results[0]->address_components)) {
                            foreach ($output->results[0]->address_components as $key => $value) {
                                if (isset($value->types)) {
                                    if (isset($value->types[0])) {
                                        if ($value->types[0] == 'country') {
                                            return $value->short_name;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

}
