<?php

namespace WPMedia\BackWPup\Backup\Database\Tables;

use WPMedia\BackWPup\Common\Database\Tables\AbstractTable;

/**
 * Backups Table.
 */
class Backup extends AbstractTable {

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected $name = 'bwpup_backups';

	/**
	 * Database version key (saved in _options or _sitemeta)
	 *
	 * @var string
	 */
	protected $db_version_key = 'bwpup_backups_version';

	/**
	 * Database version
	 *
	 * @var int
	 */
	protected $version = 20251105;

	/**
	 * Key => value array of versions => methods.
	 *
	 * @var array
	 */
	protected $upgrades = [
		20251105 => 'add_backup_trigger_columns',
	];

	/**
	 * Table schema data.
	 *
	 * @var   string
	 */
	protected $schema_data = "
				id               		bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				destination        		varchar(255)       NOT NULL default '',
				filename              	varchar(255)                     default NULL,
				status           		varchar(255)        NOT NULL default '',
				error_code       		varchar(32)             NULL default NULL,
				error_message    		longtext                NULL default NULL,
				backup_trigger          varchar(32)         NOT NULL default '',
				modified         		timestamp           NOT NULL default '0000-00-00 00:00:00',
				submitted_at     		timestamp           NULL,
				PRIMARY KEY (id),
				KEY modified (modified),
				INDEX `destination` (`destination`(191)),
				INDEX `error_code_index` (`error_code`(32))";

	/**
	 * Add backup trigger columns
	 *
	 * @return bool
	 */
	public function add_backup_trigger_columns() {
		$trigger_column_exists = $this->column_exists( 'backup_trigger' );

		$created = true;

		if ( ! $trigger_column_exists ) {
			$created &= $this->get_db()->query( "ALTER TABLE {$this->table_name} ADD COLUMN backup_trigger VARCHAR(32) NOT NULL default '' AFTER error_message " );
		}

		return $this->is_success( $created );
	}
}
