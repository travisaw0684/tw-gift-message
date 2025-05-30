<?php
/*
Plugin Name: WooCommerce TW Gift Message
Description: Adds a gift message field to WooCommerce product pages, saving data through cart, order, and email flow.
Version: 1.0.0
Author: Travis Walker
Text Domain: tw-gift-message
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class TW_Gift_Message {

    public function __construct() {
        // Initialize hooks
        add_action('woocommerce_init', [$this, 'init_hooks']);
    }

    public function init_hooks() {
        // Frontend input field
        add_action('woocommerce_before_add_to_cart_button', [$this, 'add_gift_message_field']);
        // Save to cart
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_gift_message_to_cart'], 10, 3);
        // Display in cart and checkout
        add_filter('woocommerce_get_item_data', [$this, 'display_gift_message_in_cart'], 10, 2);
        // Save to order
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_gift_message_to_order'], 10, 4);
        // Display in order details
        add_action('woocommerce_order_item_meta_start', [$this, 'display_gift_message_in_order'], 10, 3);
        // Admin order column
        add_filter('manage_edit-shop_order_columns', [$this, 'add_gift_message_admin_column']);
        add_action('manage_shop_order_posts_custom_column', [$this, 'render_gift_message_admin_column'], 10, 2);
        // Email display
        add_action('woocommerce_email_order_meta', [$this, 'add_gift_message_to_email'], 10, 3);
        // JavaScript for character counter
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    // Add gift message input field
    public function add_gift_message_field() {
        if (!current_user_can('read')) {
            return;
        }
        ?>
        <div class="tw-gift-message-field">
            <label for="gift_message"><?php esc_html_e('Gift Message (max 150 characters)', $this->text_domain); ?></label>
            <textarea id="gift_message" name="gift_message" maxlength="150" rows="3"></textarea>
            <p class="gift-message-counter">Characters: <span id="gift-message-count">0</span>/150</p>
        </div>
        <?php
    }

    // Save gift message to cart
    public function add_gift_message_to_cart($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['gift_message']) && current_user_can('read')) {
            $gift_message = sanitize_textarea_field(wp_unslash($_POST['gift_message']));
            if (strlen($gift_message) <= 150) {
                $cart_item_data['gift_message'] = $gift_message;
            }
        }
        return $cart_item_data;
    }

    // Display gift message in cart and checkout
    public function display_gift_message_in_cart($item_data, $cart_item) {
        if (isset($cart_item['gift_message']) && !empty($cart_item['gift_message'])) {
            $item_data[] = [
                'key'   => esc_html__('Gift Message', $this->text_domain),
                'value' => esc_html($cart_item['gift_message']),
            ];
        }
        return $item_data;
    }

    // Save gift message to order
    public function save_gift_message_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['gift_message']) && !empty($values['gift_message'])) {
            $item->add_meta_data(esc_html__('Gift Message', $this->text_domain), esc_html($values['gift_message']));
        }
    }

    // Display gift message in order details
    public function display_gift_message_in_order($item_id, $item, $order) {
        $gift_message = $item->get_meta(esc_html__('Gift Message', $this->text_domain));
        if ($gift_message) {
            echo '<p><strong>' . esc_html__('Gift Message', $this->text_domain) . ':</strong> ' . esc_html($gift_message) . '</p>';
        }
    }

    // Add gift message column to admin orders
    public function add_gift_message_admin_column($columns) {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'order_status') {
                $new_columns['gift_message'] = esc_html__('Gift Message', $this->text_domain);
            }
        }
        return $new_columns;
    }

    // Render gift message in admin column
    public function render_gift_message_admin_column($column, $post_id) {
        if ($column === 'gift_message') {
            $order = wc_get_order($post_id);
            foreach ($order->get_items() as $item) {
                $gift_message = $item->get_meta(esc_html__('Gift Message', $this->text_domain));
                if ($gift_message) {
                    echo esc_html(substr($gift_message, 0, 50)) . (strlen($gift_message) > 50 ? '...' : '');
                    break;
                }
            }
        }
    }
}

?>