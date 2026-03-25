<?php
/**
 * Settings tab interface.
 */

namespace Inpsyde\BackWPup\Settings;

/**
 * Class SettingTab.
 */
interface SettingTab {

	/**
	 * Render the settings tab contents.
	 *
	 * @return void
	 */
	public function tab();
}
