<?php
/**
 * Settings updater interface.
 */

namespace Inpsyde\BackWPup\Settings;

/**
 * Class SettingsUpdatable.
 */
interface SettingUpdatable {

	/**
	 * Update settings from the request payload.
	 *
	 * @return array|void
	 */
	public function update();

	/**
	 * Reset stored settings to defaults.
	 *
	 * @return void
	 */
	public function reset();
}
