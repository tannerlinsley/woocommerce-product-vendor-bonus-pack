<?php
/*
Plugin Name: WooCommerce - Product Vendor Bonus Pack
Plugin URI: 
Description: Tweaks to improve the Woocommerce Product Vendor experience for digital sales.
Version: 0.8
Author: Tanner Linsley
Author URI: http://tannerlinsley.com
License: GPL2
*/


////Enables Vendor Backend Styling via the "vendor_stylesheet.css" file /////
add_action('admin_head', 'vendor_add_product_stylesheet');

function is_not_admin(){
	global $current_user;
	get_currentuserinfo();
	if ($current_user->user_level < 7) {
		return true;
	}
	else{
		return false;
	}
}

function vendor_add_product_stylesheet() {
	if (is_not_admin()) {
		echo '<link rel="stylesheet" type="text/css" href="'.WP_PLUGIN_URL.'/woocommerce-product-vendor-bonus-pack/admin.css" />';
	}
}

/////// Disable TinyMCE options /////
function make_mce_awesome( $init ) {
	if (is_not_admin()) {
		$init['theme_advanced_disable'] = 'underline,spellchecker,wp_help,justifyleft,justifycenter,justifyright,justifyfull,blockquote,link,unlink,wp_more,wp_adv,strikethrough,numlist,fullscreen';
		return $init;
	}
}
add_filter('tiny_mce_before_init', 'make_mce_awesome');


///////Add Help Meta Box ///////
function pv_bonus_pack_help_meta_box()
{
	include 'help.php';
}
add_action( 'add_meta_boxes', 'pv_bonus_pack_help' );
function pv_bonus_pack_help(){
	if (is_not_admin()) {
		add_meta_box( 'pv-bonus-pack-help-meta-box', 'Need Some Help?', 'pv_bonus_pack_help_meta_box', 'product', 'side', 'low' );
	}
}


/////Delete Meta Boxes for Vendors//////
add_action( 'admin_head', 'my_remove_meta_boxes', 0 );
function my_remove_meta_boxes(){
	if (is_not_admin()) {
		remove_meta_box( 'postcustom', 'product', 'normal');//remove Custom Fields
		remove_meta_box( 'slugdiv', 'product', 'normal');//remove Slug Box
		remove_meta_box( 'commentsdiv', 'product', 'normal');//remove Comment Box
		remove_meta_box( 'commentstatusdiv', 'product', 'normal');//remove Discussion Box
		remove_meta_box( 'postexcerpt', 'product', 'normal');//remove Short Descriptions
		remove_meta_box( 'product_catdiv', 'product', 'side');//remove Category Box
		remove_meta_box( 'tagsdiv-product_tag', 'product', 'side');//remove Tags Box
		remove_meta_box( 'woothemes-settings', 'product', 'normal');//remove WooThemes Content Box
		remove_meta_box( 'woocommerce-product-addons', 'product', 'side');//remove Product Addon Box
	}
	///Show Visual Editor Fix///
	add_filter ( 'user_can_richedit' , create_function ( '$a' , 'return true;' ) , 50 );
}


/////Simplify MetaBox Ordering when user registers//////
function myplugin_registration_save($user_id) {
	$metaboxpositions = array(
		'side' => 'submitdiv,pv-bonus-pack-help-meta-box',
		'normal' => 'woocommerce-product-data,woocommerce-product-images,postimagediv');
	update_user_meta($user_id, 'meta-box-order_product', $metaboxpositions);
}
add_action('user_register', 'myplugin_registration_save');


//// Removes the Profile color scheme options
remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );


//////Default Content In Description Editor ///////
add_filter( 'default_content', 'my_editor_content', 10, 2 );
function my_editor_content( $content, $post ) {
	switch( $post->post_type ) {
		case 'product':
		$content = 'Please enter all details including: <strong>Product Description</strong>, <strong>Shipping Information</strong> and any <strong>Fine Print</strong> that the consumer needs to be aware of.';
		break;
		default:
		$content = 'Please enter all details including: <strong>Product Description</strong>, <strong>Shipping Information</strong> and any <strong>Fine Print</strong> that the consumer needs to be aware of.';
		break;
	}
	return $content;
}

/////Remove Profile Biography Box
function remove_plain_bio($buffer) {
	$titles = array('#<h3>About Yourself</h3>#','#<h3>About the user</h3>#');
	$buffer=preg_replace($titles,'<h3>Password</h3>',$buffer,1);
	$biotable='#<h3>Password</h3>.+?<table.+?/tr>#s';
	$buffer=preg_replace($biotable,'<h3>Password</h3> <table class="form-table">',$buffer,1);
	return $buffer;
}
function profile_admin_buffer_start() { ob_start("remove_plain_bio"); }
function profile_admin_buffer_end() { ob_end_flush(); }
add_action('admin_head', 'profile_admin_buffer_start');
add_action('admin_footer', 'profile_admin_buffer_end');


////////////remove Howdy ////////////
add_action( 'admin_bar_menu', 'wp_admin_bar_my_custom_account_menu', 11 );
function wp_admin_bar_my_custom_account_menu( $wp_admin_bar ) {
	$user_id = get_current_user_id();
	$current_user = wp_get_current_user();
	$profile_url = get_edit_profile_url( $user_id );

	if($current_user->user_firstname){
		$greeting_name = $current_user->user_firstname;
	}
	else {
		$greeting_name = 'Friend';
	}

	if ( 0 != $user_id ) {
		/* Add the "My Account" menu */
		$avatar = get_avatar( $user_id, 28 );
		$howdy = sprintf( __('Hello, %1$s'.'!'), $greeting_name);
		$class = empty( $avatar ) ? '' : 'with-avatar';

		$wp_admin_bar->add_menu( array(
			'id' => 'my-account',
			'parent' => 'top-secondary',
			'title' => $howdy . $avatar,
			'href' => $profile_url,
			'meta' => array(
				'class' => $class,
				),
			) );

	}
}


//// Add Product Vendor Paypal Field to Admin Profile Page
add_action( 'show_user_profile', 'my_show_extra_profile_fields' , 0);
add_action( 'edit_user_profile', 'my_show_extra_profile_fields' );
function my_show_extra_profile_fields( $user ) { ?>

	<h3>Billing/Payment Information</h3>
	<table class="form-table">
		<tr>
			<th><label for="pv_paypal">Paypal Email</label></th>
			<td>
				<input type="text" name="pv_paypal" id="pv_paypal" value="<?php echo esc_attr( get_user_meta( $user->ID, 'pv_paypal', true) ); ?>" class="regular-text" /><br />
				<span class="description">Your commissions and sales will be sent to this Paypal Account.</span>
			</td>
		</tr>
	</table>

<?php }
add_action( 'personal_options_update', 'my_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'my_save_extra_profile_fields' );
function my_save_extra_profile_fields( $user_id ) {
	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;
	/* Copy and paste this line for additional fields. Make sure to change 'twitter' to the field ID. */
	update_usermeta( $user_id, 'twitter', $_POST['pv_paypal'] );
}



//Do not require phone and remove company field on checkout
add_filter( 'woocommerce_checkout_fields' , 'custom_override_checkout_fields' );

// Our hooked in function - $fields is passed via the filter!
function custom_override_checkout_fields( $fields ) {
	unset($fields['billing']['billing_company']);
	$fields['billing']['billing_phone']['required']= false;
	return $fields;
}


//Skip Processing Order Status on virtual products
add_filter( 'woocommerce_payment_complete_order_status', 'virtual_order_payment_complete_order_status', 10, 2 );
function virtual_order_payment_complete_order_status( $order_status, $order_id ) {
  $order = new WC_Order( $order_id );
  	if ( 'processing' == $order_status && ( 'on-hold' == $order->status || 'pending' == $order->status || 'failed' == $order->status ) ) {
	    $virtual_order = null;
	    if ( count( $order->get_items() ) > 0 ) {
	    	foreach( $order->get_items() as $item ) {
	        	if ( 'line_item' == $item['type'] ) {
	        		$_product = $order->get_product_from_item( $item );
	        		if ( ! $_product->is_virtual() ) {
	            		// once we've found one non-virtual product we know we're done, break out of the loop
	           			$virtual_order = false;
	            		break;
	          		}
	          		else {
	            		$virtual_order = true;
	          		}
	        	}
	      	}
	    }
	    // virtual order, mark as completed
	    if ( $virtual_order ) {
	      	return 'completed';
	    }
  	}
	// non-virtual order, return original status
	return $order_status;
}








?>
