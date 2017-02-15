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
class Inpsyde_PhoneHome_Template_Loader {

	const TEMPLATE_NOTICE = 'notice';
	const TEMPLATE_QUESTION = 'question';

	private static $types = array(
		self::TEMPLATE_NOTICE   => 'notice.php',
		self::TEMPLATE_QUESTION => 'question.php',
	);

	/**
	 * @var string
	 */
	private static $lang_dir;

	/**
	 * @param string $folder
	 */
	public function __construct( $folder ) {
		$this->folder = trailingslashit( (string) $folder );
	}

	/**
	 * @param       $template_type
	 * @param array $data
	 *
	 * @return string
	 */
	public function load( $template_type, array $data = array() ) {

		if ( ! array_key_exists( $template_type, self::$types ) ) {
			return '';
		}

		$file     = self::$types[ $template_type ];
		$lang_dir = $this->language_dir();

		// If the localized templates dir wasn't found, or the required template isn't available there, use default dir
		if ( ! $lang_dir || ! file_exists( $this->folder . $lang_dir . $file ) ) {
			$lang_dir = 'en/';
		}

		ob_start();
		/** @noinspection PhpUnusedLocalVariableInspection */
		$data = (object) $data;
		/** @noinspection PhpIncludeInspection */
		include $this->folder . $lang_dir . $file;

		return ob_get_clean();
	}

	/**
	 * Determinates the path where to look for templates based on current locale
	 *
	 * @return string
	 */
	private function language_dir() {

		if ( is_string( self::$lang_dir ) ) {
			return self::$lang_dir;
		}

		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = filter_var( $locale, FILTER_SANITIZE_URL );

		if ( ! $locale ) {
			self::$lang_dir = '';

			return '';
		}

		// Found exact match for the locale, just use it
		if ( is_dir( $this->folder. $locale )  ) {
			self::$lang_dir = "{$locale}/";

			return self::$lang_dir;
		}

		$locale_parts = explode( '_', $locale );

		// Found a match for the first part of locale (e.g. "de" in "de_DE"), use it
		if ( $locale_parts && is_dir( $this->folder . $locale_parts[ 0 ] ) ) {
			self::$lang_dir = trailingslashit( $locale_parts[ 0 ] );

			return self::$lang_dir;
		}

		self::$lang_dir = '';

		return '';
	}
}