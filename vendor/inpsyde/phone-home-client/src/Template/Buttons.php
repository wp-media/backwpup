<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde phone-home-client package.
 *
 * (c) 2017 Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package phone-home-client
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GNU General Public License, version 3
 */
class Inpsyde_PhoneHome_Template_Buttons {

	/**
	 * @return string
	 */
	public function agree_button() {
		ob_start();
		$action = Inpsyde_PhoneHome_ActionController::ACTION_AGREE;
		?>
		<a
			href="<?= esc_url( Inpsyde_PhoneHome_ActionController::url_for_action( $action ) ) ?>"
			class="button button-primary">
			<?= esc_html__( 'Yes, I agree.', 'inpsyde-phone-home' ) ?>
		</a>
		<?php

		return ob_get_clean();
	}

	/**
	 * @return string
	 */
	public function maybe_button() {
		ob_start();
		$action = Inpsyde_PhoneHome_ActionController::ACTION_MAYBE;
		?>
		<a
			href="<?= esc_url( Inpsyde_PhoneHome_ActionController::url_for_action( $action ) ) ?>"
			class="button">
			<?= esc_html__( 'I have to think about that, ask me later.', 'inpsyde-phone-home' ) ?>
		</a>
		<?php

		return ob_get_clean();
	}

	/**
	 * @return string
	 */
	public function disagree_button() {
		ob_start();
		$action = Inpsyde_PhoneHome_ActionController::ACTION_DISAGREE;
		?>
		<a
			href="<?= esc_url( Inpsyde_PhoneHome_ActionController::url_for_action( $action ) ) ?>"
			class="button">
			<?= esc_html__( 'Please no. Don\'t ask me again.', 'inpsyde-phone-home' ) ?>
		</a>
		<?php

		return ob_get_clean();
	}

	/**
	 * @param string $more_info_url
	 *
	 * @return string
	 */
	public function more_info_button( $more_info_url ) {
		if ( ! is_string( $more_info_url ) || ! $more_info_url ) {
			return '';
		}

		ob_start();
		?>
		<a
			href="<?= esc_url( $more_info_url ) ?>"
			class="button button-secondary">
			<?= esc_html__( 'More info', 'inpsyde-phone-home' ) ?>
		</a>
		<?php

		return ob_get_clean();
	}
}