<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Common\Database;

interface TableInterface {
	/**
	 * Returns name from table.
	 *
	 * @return string
	 */
	public function get_name();

	/**
	 * Trigger recreation of cache table if not exist.
	 *
	 * @return void
	 */
	public function maybe_trigger_recreate_table();
}
