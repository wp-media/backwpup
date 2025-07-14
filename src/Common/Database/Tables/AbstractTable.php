<?php

namespace WPMedia\BackWPup\Common\Database\Tables;

use WPMedia\BackWPup\Common\Database\TableInterface;
use WPMedia\BackWPup\Dependencies\BerlinDB\Database\Table;

class AbstractTable extends Table implements TableInterface {

	/**
	 * Table schema data.
	 *
	 * @var   string
	 */
	protected $schema_data;

	/**
	 * Instantiate class.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'admin_init', [ $this, 'maybe_trigger_recreate_table' ], 9 );
		add_action( 'init',  [ $this, 'maybe_upgrade' ] );
	}

	/**
	 * Setup the database schema
	 *
	 * @return void
	 */
	protected function set_schema() {
		if ( ! $this->schema_data ) {
			return;
		}

		$this->schema = $this->schema_data;
	}

	/**
	 * Returns name from table.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->apply_prefix( $this->table_name );
	}

	/**
	 * Trigger recreation of cache table if not exist.
	 *
	 * @return void
	 */
	public function maybe_trigger_recreate_table() {
		if ( $this->exists() ) {
			return;
		}

		delete_option( $this->db_version_key );
	}
}
