<?php

namespace BackWPup\ThirdParty;

interface ThirdPartyInterface {

	/**
	 * Add the third party plugin folder to the excluded folders list if the plugin is active.
	 *
	 * @param array $excluded_folders
	 *
	 * @return mixed
	 */
	public function exclude_folders( $excluded_folders );

	/**
	 * Add the third party plugin cache folder to the excluded cache folders list if the plugin is active.
	 *
	 * @param array $excluded_cache_folders
	 *
	 * @return mixed
	 */
	public function exclude_cache_folders( $excluded_cache_folders );

	/**
	 * Check if the third party plugin is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool;

	/**
	 * Initialize the third party plugin.
	 *
	 * @return void
	 */
	public function init(): void;
}
