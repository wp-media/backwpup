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
		Um die Weiterentwicklung von <?= $plugin_name ?> weiter zu optimieren, möchten wir Sie bitten, von Ihnen einige Daten wie z.B. PHP- und WordPress-Version abfragen zu dürfen.
		<?php if ($anonymize) : ?>
			<strong>Es werden keine personenbezogenen Daten erhoben!</strong>
		<?php endif ?>

	</p>
	<p class="notice-links">
		<?= $buttons->agree_button() ?>
		<?= $buttons->more_info_button( $more_info_url ) ?>
	</p>
</div>
