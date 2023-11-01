const { registerCheckoutFilters } = window.wc.blocksCheckout;

const modifyProceedToCheckoutButtonLabel = ( defaultValue ) => {
	return checkoutLabels.proceed_to_checkout_button_label || defaultValue;
};

const modifyProceedToCheckoutButtonLink = ( defaultValue ) => {
	return checkoutLabels.proceed_to_checkout_button_link || defaultValue;
};

const modifyPlaceOrderButtonLabel = ( defaultValue ) => {
	return checkoutLabels.place_order_button_label || defaultValue;
};

registerCheckoutFilters( 'checkout-filters-for-woocommerce-blocks', {
	placeOrderButtonLabel: modifyPlaceOrderButtonLabel,
	proceedToCheckoutButtonLabel: modifyProceedToCheckoutButtonLabel,
	proceedToCheckoutButtonLink: modifyProceedToCheckoutButtonLink,
} );
