<?php

/**
 *     Generate uniq QR code. 
 **/

function swish_qr_code( $order_id ) {
  $swish = '0707070707';
  $order = wc_get_order( $order_id );
	$api_url = "https://mpc.getswish.net/qrg-swish/api/v1/prefilled";
	$qr_width = 300;
	$api_body = '{"format":"png","size":'.$qr_width.',"message":{"value":"'.$order->get_order_number().'","editable":false},"amount":{"value":'.$order->get_total().',"editable":false},"payee":{"value":"'.$swish.'","editable":false}}';	
	$args = array( 'method' => 'POST', 'headers' => array( 'Content-Type' => 'application/json' ), 'body' => $api_body );

	$response = wp_remote_post( $api_url, $args );

	if ( is_wp_error( $response ) ) {
		echo "Something went wrong: " . $response->get_error_message();
	} else {
		return base64_encode( wp_remote_retrieve_body( $response ) );
	}
}



/**
 *      Save the QR code to postmeta table.
 **/

if ( ! function_exists('custom_meta_to_order') ) {
    add_action('woocommerce_checkout_update_order_meta', 'custom_meta_to_order', 20, 1);
    function custom_meta_to_order( $order_id ) {
        $order = wc_get_order( $order_id );
		$qr_code = swish_qr_code( $order_id );
		$qr_date = date('Y-m-d HH:MM');

        if (isset($qr_code)) {
            if (!empty($qr_code)) {
				$order->update_meta_data('_swish_qr_code', $qr_code);
				$order->update_meta_data('_swish_qr_date', $qr_date);
			}
        }
        $order->save();
    }
}



/**
 *     Show QR code at thankyou-page 
 **/

add_action( 'woocommerce_thankyou', 'swish_add_content_thankyou', '6' );
function swish_add_content_thankyou( $order_id ) {
	$order = wc_get_order( $order_id );
	echo '<div style="float: left; padding-right: 50px"><img src="data:image/png;base64,' . $order->get_meta('_swish_qr_code') . '"></div>';
}



/** 
 *       Add QR-code image in confirmation E-mail (customer_on_hold_order)
 **/

add_action( 'woocommerce_email_before_order_table', 'swish_add_content_specific_email', 20, 4 );
function swish_add_content_specific_email( $order, $sent_to_admin, $plain_text, $email ) {
	if ( $email->id == 'customer_on_hold_order' ) {
		echo '<div style="margin: auto;"><img src="data:image/png;base64,' . $order->get_meta('_swish_qr_code') . '"></div>';
	}
}

?>
