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
		    Für eine optimale Weiterentwicklung von <?= $plugin_name ?> möchten wir in Zukunft wissen, welche PHP- und WordPress-Version wir in der Entwicklung unterstützen sollen, damit wir eine schnelle und auf dem neuesten Stand der Technik basierende Weiterentwicklung garantieren können.
		</p>
		<p style="font-size: larger">
			Moderne PHP-Versionen verbessern die Performance und Sicherheit des Systems erheblich und machen die Entwicklung neuer und bestehender Funktionen enorm leichter.
		</p>
		<p style="font-size: larger">
			Alte PHP-Versionen, die nicht mehr (oder sehr selten) verwendet werden, würden bei der zukünftigen Programmierung nicht unterstützt.<br>
			Aber keine Sorge, wir lassen dich nicht allein mit deiner alten Version und machen diesen Schritt einfach so.
		</p>
		<p style="font-size: larger">
			Deshalb ist es für uns wichtig zu wissen, wie viele Benutzer noch eine alte Version verwenden, und wenn ja, wie wir helfen können, um auf die neueste Version zu aktualisieren.<br>
			Meistens reicht ein kurze E-Mail oder Anruf bei ihrem Hosting um auf die neueste PHP-Version zu aktualisieren.
		</p>
		<p style="font-size: larger">
			Wir wären dir sehr dankbar, wenn du uns die Erlaubnis zur Abfrage von Informationen wie PHP- und WordPress-Version gibst.<br>
			Das einzige, was du tun musst, ist auf <em>"Ja, ich bin einverstanden."</em> unten klicken, das wars.
		</p>
		<?php if ( $anonymize ) : ?>
			<p style="font-size: larger">
				<strong>Bitte beachte, dass die gesendeten Daten anonym sind: Wir erfassen keine persönlichen Daten!</strong>
			</p>
		<?php endif ?>
		<p style="font-size: larger">
			Vielen Dank im Voraus!
		</p>

		<p class="page-links">
			<?= $buttons->agree_button() ?>
			<?= $buttons->maybe_button() ?>
			<?= $buttons->disagree_button() ?>
		</p>

	</div>
</div>
