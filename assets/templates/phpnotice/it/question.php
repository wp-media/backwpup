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
			Per uno sviluppo ottimale di <?= $plugin_name ?> vorremmo sapere quale versione PHP e quale versione
			WordPress dovremmo supportare, in modo da poter garantire un migliore futuro sviluppo, basato sugli
			ultimi standard.
		</p>
		<p style="font-size: larger">
			Le versioni PHP moderne hanno performance di gran lunga migliori, una maggiore sicurezza e rendono più
			semplice lo sviluppo di nuove funzionalità e il mantenimento di quelle esistenti.
		</p>
		<p style="font-size: larger">
			Le versioni obsolete di PHP sono oggi usate molto raramente, e probabilmente non verranno più supportate.<br>
			Ma non preoccuparti, non abbiamo intenzione di lasciarti indietro se utilizzi una vecchia versione.
		</p>
		<p style="font-size: larger">
			Per questo è importante per noi sapere quanti utenti utilizzano vecchie versioni, e in modo che possiamo
			aiutarli a migrare ad una versione più recente.<br>
			La maggior parte delle volte basta una breve email o una chiamata al proprio hosting per ottenere un
			aggiornamento senza costi aggiuntivi.
		</p>
		<p style="font-size: larger">
			Ti saremmo molto grati se volessi concederci il permesso di ottenere da questo sito alcune informazioni
			come la versioni in uso di PHP e di WordPress.<br>
			L'unica cosa che devi fare è cliccare "Sì" qui di seguito.
		</p>
		<?php if ( $anonymize ) : ?>
			<p style="font-size: larger">
				<strong>Nota che tutte le informazioni saranno anonime: non raccogliamo informazioni personali!</strong>
			</p>
		<?php endif ?>
		<p style="font-size: larger">
			Grazie molte in anticipo!
		</p>

		<p class="page-links">
			<?php echo $buttons->agree_button() ?>
			<?php echo $buttons->maybe_button() ?>
			<?php echo $buttons->disagree_button() ?>
		</p>

	</div>
</div>
