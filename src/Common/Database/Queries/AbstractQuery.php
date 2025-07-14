<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Common\Database\Queries;

use WPMedia\BackWPup\Dependencies\BerlinDB\Database\Query;

class AbstractQuery extends Query {
	/**
	 * Table status.
	 *
	 * @var boolean
	 */
	public static $table_exists = false;

	/**
	 * Returns the current status of the table; true if it exists, false otherwise.
	 *
	 * @return boolean
	 */
	protected function table_exists(): bool {
		if ( self::$table_exists ) {
			return true;
		}

		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( ! $db ) {
			return false;
		}

		// Query statement.
		$query    = 'SELECT table_name FROM information_schema.tables WHERE table_schema = %s AND table_name = %s LIMIT 1';
		$prepared = $db->prepare( $query, $db->__get( 'dbname' ), $db->{$this->table_name} );
		$result   = $db->get_var( $prepared );

		// Does the table exist?
		$exists = $this->is_success( $result );

		if ( $exists ) {
			self::$table_exists = $exists;
		}

		return $exists;
	}

	/**
	 * Check if db action can be processed.
	 *
	 * @return boolean
	 */
	private function is_allowed() {
		if ( ! self::$table_exists && ! $this->table_exists() ) {
			return false;
		}

		// Bail if no database interface is available.
		if ( empty( $this->get_db() ) ) {
			return false;
		}

		return true;
	}
}
