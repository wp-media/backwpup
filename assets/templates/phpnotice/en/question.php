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
$plugin_name = isset( $data->plugin_name ) ? esc_html( $data->plugin_name ) : '';
$anonymize   = isset( $data->anonymize ) ? (bool) $data->anonymize : false;
global $title;

if ( ! $plugin_name || ! $title ) {
	return;
}

?>
<div class="wrap">

	<h1><?= $title ?></h1>

	<div class="updated" style="border:none;">

		<p style="font-size: larger">
			For an optimal further development of <?= $plugin_name ?> we would like to know in the future which PHP and
			WordPress version we should support in development, so that we can guarantee a speedy and further development
			based on the latest standards.
		</p>
		<p style="font-size: larger">
			Modern PHP versions greatly improves the performance and security of the system and makes the development of
			new and existing functionalities enormously easier.
		</p>
		<p style="font-size: larger">
			Old PHP versions that are no longer (or very rarely) used, would not be supported in future programming.<br>
			But do not worry, we do not leave you alone with your old version and cut you off just like that.
		</p>
		<p style="font-size: larger">
			That is why it is important to know how may users are using an old version, and if so, how we can help to
			upgrade to the latest version.<br>
			Most of the time a short e-mail or a call to your hosting is enough to have the PHP version updated.
		</p>
		<p style="font-size: larger">
			We would be very grateful to you if you give us the permission to query information like PHP and WordPress
			version.<br>
			The only thing you need to do is click <em>"Yes, I agree."</em> below, and that's it.
		</p>
		<?php if ( $anonymize ) : ?>
			<p style="font-size: larger">
				<strong>Please note any data sent is anonymous: we don't collect any personal data at all!</strong>
			</p>
		<?php endif ?>
		<p style="font-size: larger">
			Thanks a lot in advance!
		</p>

		<p class="page-links">
			<?php echo $buttons->agree_button() ?>
			<?php echo $buttons->maybe_button() ?>
			<?php echo $buttons->disagree_button() ?>
		</p>

	</div>
</div>
