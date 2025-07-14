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
	protected $version = 20250324;

	/**
	 * Key => value array of versions => methods.
	 *
	 * @var array
	 */
	protected $upgrades = [];

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
				modified         		timestamp           NOT NULL default '0000-00-00 00:00:00',
				submitted_at     		timestamp           NULL,
				PRIMARY KEY (id),
				KEY modified (modified),
				INDEX `destination` (`destination`(191)),
				INDEX `error_code_index` (`error_code`(32))";
}
