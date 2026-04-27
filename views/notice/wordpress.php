<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p><?php esc_html_e( 'BackWPup is dropping support for WordPress versions less than 5.0. Please update WordPress to the latest version. Without an update, you will not receive any new features.', 'backwpup' ); ?></p>
<p>
<?php
echo wp_kses(
	__( '<a href="https://backwpup.com/support/?utm_source=backwpup_plugin&utm_medium=plugin&utm_campaign=in_product&utm_content=get_support" target="_blank">Contact our support team here</a> if any questions remain.', 'backwpup' ),
	[
		'a' => [
			'href'   => true,
			'target' => true,
		],
	]
);
?>
</p>
