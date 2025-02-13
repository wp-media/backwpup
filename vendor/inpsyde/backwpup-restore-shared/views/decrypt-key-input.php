<?php declare(strict_types=1);
/*
 * This file is part of the Inpsyde BackWpUp package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

?>

<div id="decrypt_key" style="display: none;">
	<p>
		<?php esc_html_e('Please enter your decryption key to decrypt your backup.', 'backwpup'); ?>
	</p>
	<p>
		<label for="decryption_key">
			<?php esc_html_e('Decryption Key', 'backwpup'); ?>
		</label>
		<textarea id="decryption_key" name="decryption_key" style="width: 100%; overflow: scroll;" rows="8"></textarea>
	</p>
	<button id="submit_decrypt_key" class="button button-primary">
		<?php esc_html_e('Submit', 'backwpup'); ?>
	</button>
</div>
