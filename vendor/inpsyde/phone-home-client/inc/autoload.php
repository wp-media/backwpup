<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde phone-home-client package.
 *
 * (c) 2017 Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if ( defined( 'INPSYDE_PHONE_HOME_CLASS_FILES_ROOT' ) ) {
	return;
}

define( 'INPSYDE_PHONE_HOME_CLASS_FILES_ROOT', dirname( dirname( __FILE__ ) ) . '/src/' );

/**
 * @param string $class
 */
function inpsyde_phone_home_autoload( $class ) {

	static $class_map;

	if ( ! $class_map ) {
		$class_map = array(
			'Inpsyde_PhoneHome_ActionController'          => 'ActionController',
			'Inpsyde_PhoneHome_Configuration'             => 'Configuration',
			'Inpsyde_PhoneHome_CronController'            => 'CronController',
			'Inpsyde_PhoneHome_FrontController'           => 'FrontController',
			'Inpsyde_PhoneHome_HttpClient'                => 'HttpClient',
			'Inpsyde_PhoneHome_Consent'                   => 'Consent/Consent',
			'Inpsyde_PhoneHome_Consent_DisplayController' => 'Consent/DisplayController',
			'Inpsyde_PhoneHome_Template_Buttons'          => 'Template/Buttons',
			'Inpsyde_PhoneHome_Template_Loader'           => 'Template/Loader',
		);
	}

	if ( ! array_key_exists( $class, $class_map ) ) {
		return;
	}

	if ( ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) ) {
		/** @noinspection PhpIncludeInspection */
		@require_once INPSYDE_PHONE_HOME_CLASS_FILES_ROOT . "{$class_map[$class]}.php";

		return;
	}

	/** @noinspection PhpIncludeInspection */
	require_once INPSYDE_PHONE_HOME_CLASS_FILES_ROOT . "{$class_map[$class]}.php";
}

spl_autoload_register( 'inpsyde_phone_home_autoload' );