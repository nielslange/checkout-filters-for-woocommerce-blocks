<?php
/**
 * Plugin Name: Checkout Filters for WooCommerce Blocks
 * Plugin URI: https://github.com/nielslange/checkout-filters-for-woocommerce-blocks
 * Description: Allows managing the checkout filters for WooCommerce Blocks.
 * Version: 1.0.0
 * Author: Niels Lange
 * Author URI: https://nielslange.de
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: checkout-filters-for-woocommerce-blocks
 *
 * Requires at least: 6.3
 * Requires PHP: 8.0
 * WC requires at least: 8.2
 * WC tested up to: 8.2
 *
 * @package Checkout_Filters_for_WooCommerce_Blocks
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main Checkout Filters for WooCommerce Blocks class.
 */
class CheckoutFiltersForWooCommerceBlocks {
	/**
	 * The checkout slug.
	 *
	 * @var string
	 */
	private $checkout_slug;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_custom_checkout_script' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'add_plugin_action_links' ) );
		add_filter( 'woocommerce_get_sections_advanced', array( $this, 'add_wc_advanced_settings_tab' ), 20 );
		add_filter( 'woocommerce_get_settings_advanced', array( $this, 'add_wc_checkout_filters_settings' ) );
		add_action( 'woocommerce_update_options_advanced', array( $this, 'save_wc_checkout_filters_settings' ) );
		add_action( 'woocommerce_init', array( $this, 'get_checkout_slug' ) );
	}

	/**
	 * Get the checkout slug.
	 *
	 * @return void
	 */
	public function get_checkout_slug() {
		$this->checkout_slug = rtrim( str_replace( home_url(), '', wc_get_checkout_url() ), '/' );
	}

	/**
	 * Declare compatibility with custom order tables for WooCommerce.
	 *
	 * @return void
	 */
	public function declare_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}

	/**
	 * Enqueue the custom checkout script and localize the labels.
	 *
	 * @return void
	 */
	public function enqueue_custom_checkout_script() {
		if ( is_cart() || is_checkout() ) {
			wp_enqueue_script( 'custom-checkout-script', plugin_dir_url( __FILE__ ) . '/build/frontend.js', array(), '1.0.0', true );

			$checkout_labels = array(
				// 'cart_line_item_class'                     => get_option( 'cart_line_item_class', '' ),
				// 'cart_line_item_price'                     => get_option( 'cart_line_item_price', '<price/>' ),
				// 'cart_line_item_name'                      => get_option( 'cart_line_item_name', '' ),
				// 'cart_line_item_sale_bade_format'          => get_option( 'cart_line_item_sale_bade_format', '<price/>' ),
				// 'cart_line_item_show_remove_item_link'     => get_option( 'cart_line_item_show_remove_item_link', true ),
				// 'cart_line_item_subtotal_price_format'     => get_option( 'cart_line_item_subtotal_price_format', '<price/>' ),
				// 'order_summary_item_class'                 => get_option( 'order_summary_item_class', '' ),
				// 'order_summary_item_price'                 => get_option( 'order_summary_item_price', '<price/>' ),
				// 'order_summary_item_name'                  => get_option( 'order_summary_item_name', '' ),
				// 'order_summary_item_subtotal_price_format' => get_option( 'order_summary_item_subtotal_price_format', '<price/>' ),
				'place_order_button_label'         => get_option( 'place_order_button_label', __( 'Place Order', 'woo-gutenberg-products-block' ) ),
				'proceed_to_checkout_button_label' => get_option( 'proceed_to_checkout_button_label', __( 'Proceed to Checkout', 'woo-gutenberg-products-block' ) ),
				'proceed_to_checkout_button_link'  => get_option( 'proceed_to_checkout_button_link', $this->checkout_slug ),
				// 'show_apply_coupon_notice'                 => get_option( 'show_apply_coupon_notice', true ),
				// 'show_remove_coupon_notice'                => get_option( 'show_remove_coupon_notice', true ),
				// 'total_footer_item_label'                  => get_option( 'total_footer_item_label', __( 'Total', 'woo-gutenberg-products-block' ) ),
			);

			wp_localize_script( 'custom-checkout-script', 'checkoutLabels', $checkout_labels );
		}
	}


	/**
	 * Add settings link on plugin page
	 *
	 * @param array $links The original array with customizer links.
	 * @return array The updated array with customizer links.
	 */
	public function add_plugin_action_links( array $links ) {
		$admin_url     = admin_url( 'admin.php?page=wc-settings&tab=advanced&section=checkout_filters' );
		$settings_link = sprintf( '<a href="%s">' . __( 'Settings', 'checkout-filters-for-woocommerce-blocks' ) . '</a>', $admin_url );
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Add a new settings section tab to the WooCommerce advanced settings tabs array.
	 *
	 * @param array $sections The original array with the WooCommerce settings tabs.
	 * @return array $sections The updated array with our settings tab added.
	 */
	public function add_wc_advanced_settings_tab( $sections ) {
		$sections['checkout_filters'] = __( 'Checkout Filters', 'checkout-filters-for-woocommerce-blocks' );

		return $sections;
	}

	/**
	 * Add the settings section to the WooCommerce settings tab array on the advanced tab.
	 *
	 * @param array $settings The settings array to add our section to.
	 * @return array $settings The settings array with our section added.
	 */
	public function add_wc_checkout_filters_settings( $settings ) {
		global $current_section;

		if ( 'checkout_filters' !== $current_section ) {
			return $settings;
		}

		$checkout_filters_settings = array(
			// array(
			// 'name' => __( 'Cart Line Items filters',
			// 'checkout-filters-for-woocommerce-blocks' ),
			// 'type' => 'title',
			// 'desc' => '',
			// 'id'   => 'cart_line_items_filters_settings',
			// ),
			// array(
			// 'name'     => __( 'Item Class', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'       => 'cart_line_item_class',
			// 'type'     => 'text',
			// 'default'  => '',
			// 'desc'     => __( 'The class of the <strong>Cart Line Item</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// 'desc_tip' => true,
			// ),
			// array(
			// 'name'        => __( 'Item Price', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'          => 'cart_line_item_price',
			// 'type'        => 'text',
			// 'default'     => '<price/>',
			// 'placeholder' => '<price/>',
			// 'desc'        => __( 'The format of the <strong>Cart Line Item Price</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// 'desc_tip'    => true,
			// ),
			// array(
			// 'name'     => __( 'Item Name', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'       => 'cart_line_item_name',
			// 'type'     => 'text',
			// 'default'  => '',
			// 'desc'     => __( 'The format of the <strong>Cart Line Item Name</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// 'desc_tip' => true,
			// ),
			// array(
			// 'name'        => __( 'Sale Badge Format', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'          => 'cart_line_item_sale_bade_format',
			// 'type'        => 'text',
			// 'default'     => '<price/>',
			// 'placeholder' => '<price/>',
			// 'desc'        => __( 'The format of the <strong>Cart Line Item Sale Badge</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// 'desc_tip'    => true,
			// ),
			// array(
			// 'name'    => __( 'Show Remove Item Link', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'      => 'cart_line_item_show_remove_item_link',
			// 'type'    => 'checkbox',
			// 'default' => 'yes',
			// 'desc'    => __( 'Show the <strong>Cart Line Item Remove Item Link</strong>.', 'checkout-filters-for-woocommerce-blocks' ),

			// ),
			// array(
			// 'name'        => __( 'Subtotal Price Format', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'          => 'cart_line_item_subtotal_price_format',
			// 'type'        => 'text',
			// 'default'     => '<price/>',
			// 'placeholder' => '<price/>',
			// 'desc'        => __( 'The format of the <strong>Cart Line Item Subtotal Price</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// 'desc_tip'    => true,
			// ),
			// array(
			// 'type' => 'sectionend',
			// 'id'   => 'cart_line_items_filters_settings',
			// ),
			// array(
			// 'name' => __( 'Order Summary Items filters', 'checkout-filters-for-woocommerce-blocks' ),
			// 'type' => 'title',
			// 'desc' => '',
			// 'id'   => 'order_summary_items_filters_settings',
			// ),
			// array(
			// 'name'     => __( 'Item Class', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'       => 'order_summary_item_class',
			// 'type'     => 'text',
			// 'default'  => '',
			// 'desc'     => __( 'The class of the <strong>Order Summary Item</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// 'desc_tip' => true,
			// ),
			// array(
			// 'name'        => __( 'Item Price', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'          => 'order_summary_item_price',
			// 'type'        => 'text',
			// 'default'     => '<price/>',
			// 'placeholder' => '<price/>',
			// 'desc'        => __( 'The format of the <strong>Order Summary Item Price</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// 'desc_tip'    => true,
			// ),
			// array(
			// 'name'     => __( 'Item Name', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'       => 'order_summary_item_name',
			// 'type'     => 'text',
			// 'default'  => '',
			// 'desc'     => __( 'The format of the <strong>Order Summary Item Name</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// 'desc_tip' => true,
			// ),
			// array(
			// 'name'        => __( 'Subtotal Price Format', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'          => 'order_summary_item_subtotal_price_format',
			// 'type'        => 'text',
			// 'default'     => '<price/>',
			// 'placeholder' => '<price/>',
			// 'desc'        => __( 'The format of the <strong>Order Summary Item Subtotal Price</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// 'desc_tip'    => true,
			// ),
			// array(
			// 'type' => 'sectionend',
			// 'id'   => 'order_summary_items_filters_settings',
			// ),
			array(
				'name' => __( 'Checkout and Place Order Button Filters', 'checkout-filters-for-woocommerce-blocks' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'checkout_and_place_order_button_filters_settings',
			),
			array(
				'name'        => __( 'Place Order button label', 'checkout-filters-for-woocommerce-blocks' ),
				'id'          => 'place_order_button_label',
				'type'        => 'text',
				'default'     => __( 'Place Order', 'woo-gutenberg-products-block' ),
				'placeholder' => __( 'Place Order', 'woo-gutenberg-products-block' ),
				'desc'        => __( 'The text of the <strong>Place Order button label</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
				'desc_tip'    => true,
			),
			array(
				'name'        => __( 'Proceed To Checkout button label', 'checkout-filters-for-woocommerce-blocks' ),
				'id'          => 'proceed_to_checkout_button_label',
				'type'        => 'text',
				'default'     => __( 'Proceed To Checkout', 'woo-gutenberg-products-block' ),
				'placeholder' => __( 'Proceed To Checkout', 'woo-gutenberg-products-block' ),
				'desc'        => __( 'The text of the <strong>Proceed To Checkout button label</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
				'desc_tip'    => true,
			),
			array(
				'name'        => __( 'Proceed To Checkout button link', 'checkout-filters-for-woocommerce-blocks' ),
				'id'          => 'proceed_to_checkout_button_link',
				'type'        => 'text',
				'desc'        => __( 'The link of the <strong>Proceed To Checkout button</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
				'default'     => $this->checkout_slug,
				'placeholder' => $this->checkout_slug,
				'desc_tip'    => true,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'checkout_and_place_order_button_filters_settings',
			),
			// array(
			// 'name' => __( 'Coupon Filters', 'checkout-filters-for-woocommerce-blocks' ),
			// 'type' => 'title',
			// 'desc' => '',
			// 'id'   => 'coupon_filters_settings',
			// ),
			// array(
			// 'name'    => __( 'Show Apply Coupon Notice', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'      => 'show_apply_coupon_notice',
			// 'type'    => 'checkbox',
			// 'default' => 'yes',
			// 'desc'    => __( 'Show the <strong>Apply Coupon Notice</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// ),
			// array(
			// 'name'    => __( 'Show Remove Coupon Notice', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'      => 'show_remove_coupon_notice',
			// 'type'    => 'checkbox',
			// 'default' => 'yes',
			// 'desc'    => __( 'Show the <strong>Remove Coupon Notice</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// ),
			// array(
			// 'type' => 'sectionend',
			// 'id'   => 'coupon_filters_settings',
			// ),
			// array(
			// 'name'     => __( 'Total Footer Item Filters', 'checkout-filters-for-woocommerce-blocks' ),
			// 'type'     => 'title',
			// 'desc'     => '',
			// 'id'       => 'fotal_footer_item_filters_settings',
			// 'desc_tip' => true,
			// ),
			// array(
			// 'name'        => __( 'Total Footer Item Label', 'checkout-filters-for-woocommerce-blocks' ),
			// 'id'          => 'total_footer_item_label',
			// 'type'        => 'text',
			// 'default'     => __( 'Total', 'woo-gutenberg-products-block' ),
			// 'placeholder' => __( 'Total', 'woo-gutenberg-products-block' ),
			// 'desc'        => __( 'The label of the <strong>Total Footer Item</strong>.', 'checkout-filters-for-woocommerce-blocks' ),
			// ),
			// array(
			// 'type' => 'sectionend',
			// 'id'   => 'fotal_footer_item_filters_settings',
			// ),
		);

		return $checkout_filters_settings;
	}

	/**
	 * Save the settings.
	 *
	 * @return void
	 */
	public function save_wc_checkout_filters_settings() {
		global $current_section;

		if ( 'checkout_filters' !== $current_section ) {
			return;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'woocommerce-settings' ) ) {
			die( __( 'Could not verify request.', 'checkout-filters-for-woocommerce-blocks' ) );
		}

		// $cart_line_item_class = isset( $_POST['cart_line_item_class'] ) && '' !== $_POST['cart_line_item_class'] ? esc_html( $_POST['cart_line_item_class'] ) : '';
		// update_option( 'cart_line_item_class', $cart_line_item_class );

		// $cart_line_item_price = isset( $_POST['cart_line_item_price'] ) && '' !== $_POST['cart_line_item_price'] ? esc_html( $_POST['cart_line_item_price'] ) : '<price/>';
		// update_option( 'cart_line_item_price', $cart_line_item_price );

		// $cart_line_item_name = isset( $_POST['cart_line_item_name'] ) && '' !== $_POST['cart_line_item_name'] ? esc_html( $_POST['cart_line_item_name'] ) : '';
		// update_option( 'cart_line_item_name', $cart_line_item_name );

		// $cart_line_item_sale_bade_format = isset( $_POST['cart_line_item_sale_bade_format'] ) && '' !== $_POST['cart_line_item_sale_bade_format'] ? esc_html( $_POST['cart_line_item_sale_bade_format'] ) : '<price/>';
		// update_option( 'cart_line_item_sale_bade_format', $cart_line_item_sale_bade_format );

		// $cart_line_item_show_remove_item_link = isset( $_POST['cart_line_item_show_remove_item_link'] ) ? 'yes' : 'no';
		// update_option( 'cart_line_item_show_remove_item_link', $cart_line_item_show_remove_item_link );

		// $cart_line_item_subtotal_price_format = isset( $_POST['cart_line_item_subtotal_price_format'] ) && '' !== $_POST['cart_line_item_subtotal_price_format'] ? esc_html( $_POST['cart_line_item_subtotal_price_format'] ) : '<price/>';
		// update_option( 'cart_line_item_subtotal_price_format', $cart_line_item_subtotal_price_format );

		// $order_summary_item_class = isset( $_POST['order_summary_item_class'] ) && '' !== $_POST['order_summary_item_class'] ? esc_html( $_POST['order_summary_item_class'] ) : '';
		// update_option( 'order_summary_item_class', $order_summary_item_class );

		// $order_summary_item_price = isset( $_POST['order_summary_item_price'] ) && '' !== $_POST['order_summary_item_price'] ? esc_html( $_POST['order_summary_item_price'] ) : '<price/>';
		// update_option( 'order_summary_item_price', $order_summary_item_price );

		// $order_summary_item_name = isset( $_POST['order_summary_item_name'] ) && '' !== $_POST['order_summary_item_name'] ? esc_html( $_POST['order_summary_item_name'] ) : '';
		// update_option( 'order_summary_item_name', $order_summary_item_name );

		// $order_summary_item_subtotal_price_format = isset( $_POST['order_summary_item_subtotal_price_format'] ) && '' !== $_POST['order_summary_item_subtotal_price_format'] ? esc_html( $_POST['order_summary_item_subtotal_price_format'] ) : '<price/>';
		// update_option( 'order_summary_item_subtotal_price_format', $order_summary_item_subtotal_price_format );

		$place_order_button_label = isset( $_POST['place_order_button_label'] ) && '' !== $_POST['place_order_button_label'] ? esc_html( $_POST['place_order_button_label'] ) : __( 'Place Order', 'woo-gutenberg-products-block' );
		update_option( 'place_order_button_label', $place_order_button_label );

		$proceed_to_checkout_button_label = isset( $_POST['proceed_to_checkout_button_label'] ) && '' !== $_POST['proceed_to_checkout_button_label'] ? esc_html( $_POST['proceed_to_checkout_button_label'] ) : __( 'Proceed to Checkout', 'woo-gutenberg-products-block' );
		update_option( 'proceed_to_checkout_button_label', $proceed_to_checkout_button_label );

		$proceed_to_checkout_button_link = isset( $_POST['proceed_to_checkout_button_link'] ) && '' !== $_POST['proceed_to_checkout_button_link'] ? esc_html( $_POST['proceed_to_checkout_button_link'] ) : $this->checkout_slug;
		update_option( 'proceed_to_checkout_button_link', $proceed_to_checkout_button_link );

		// $show_apply_coupon_notice = isset( $_POST['show_apply_coupon_notice'] ) ? 'yes' : 'no';
		// update_option( 'show_apply_coupon_notice', $show_apply_coupon_notice );

		// $show_remove_coupon_notice = isset( $_POST['show_remove_coupon_notice'] ) ? 'yes' : 'no';
		// update_option( 'show_remove_coupon_notice', $show_remove_coupon_notice );

		// $total_footer_item_label = isset( $_POST['total_footer_item_label'] ) && '' !== $_POST['total_footer_item_label'] ? esc_html( $_POST['total_footer_item_label'] ) : __( 'Total', 'woo-gutenberg-products-block' );
		// update_option( 'total_footer_item_label', $total_footer_item_label );
	}
}

new CheckoutFiltersForWooCommerceBlocks();
