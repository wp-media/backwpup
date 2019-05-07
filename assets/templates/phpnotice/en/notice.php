<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde phone-home-client package.
 *
 * (c) 2017 Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$buttons = new Inpsyde_PhoneHome_Template_Buttons();
( isset( $data ) && is_object( $data ) ) or $data = new stdClass();

$plugin_name   = isset( $data->plugin_name ) ? esc_html( $data->plugin_name ) : '';
$more_info_url = isset( $data->more_info_url ) ? $data->more_info_url : '';
$anonymize   = isset( $data->anonymize ) ? (bool) $data->anonymize : false;

if ( ! $plugin_name || ! $more_info_url ) {
	return;
}

?>
<div class="notice notice-warning is-dismissible">
	<p>
		To optimize further development of <?= $plugin_name ?>, we would like to ask you the consent to query from you
		some data like PHP and WordPress version.
		<?php if ($anonymize) : ?>
			<strong>No personal data will be collected!</strong>
		<?php endif ?>

	</p>
	<p class="notice-links">
		<?php echo $buttons->agree_button() ?>
		<?php echo $buttons->more_info_button( $more_info_url ) ?>
	</p>
</div>
