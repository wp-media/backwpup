<?php

namespace WPMedia\BackWPup\Backup\Database\Schemas;

use WPMedia\BackWPup\Dependencies\BerlinDB\Database\Schema;

/**
 * RUCSS UsedCSS Schema.
 */
class Backup extends Schema {

	/**
	 * Array of database column objects
	 *
	 * @var array
	 */
	public $columns = [

		// ID column.
		[
			'name'     => 'id',
			'type'     => 'bigint',
			'length'   => '20',
			'unsigned' => true,
			'extra'    => 'auto_increment',
			'primary'  => true,
			'sortable' => true,
		],

		// destination column.
		[
			'name'       => 'destination',
			'type'       => 'varchar',
			'length'     => '255',
			'default'    => '',
			'cache_key'  => true,
			'searchable' => true,
			'sortable'   => true,
		],

		// filename column.
		[
			'name'       => 'filename',
			'type'       => 'varchar',
			'length'     => '255',
			'default'    => null,
			'cache_key'  => false,
			'searchable' => false,
			'sortable'   => false,
		],

		// STATUS column.
		[
			'name'       => 'status',
			'type'       => 'varchar',
			'length'     => '255',
			'default'    => null,
			'cache_key'  => true,
			'searchable' => true,
			'sortable'   => false,
		],

		// error_code column.
		[
			'name'       => 'error_code',
			'type'       => 'varchar',
			'length'     => '32',
			'default'    => null,
			'cache_key'  => false,
			'searchable' => true,
			'sortable'   => true,
		],

		// error_message column.
		[
			'name'       => 'error_message',
			'type'       => 'longtext',
			'default'    => null,
			'cache_key'  => false,
			'searchable' => true,
			'sortable'   => true,
		],

		// MODIFIED column.
		[
			'name'       => 'modified',
			'type'       => 'timestamp',
			'default'    => '0000-00-00 00:00:00',
			'modified'   => true,
			'date_query' => true,
			'sortable'   => true,
		],

		// SUBMITTED_AT column.
		[
			'name'       => 'submitted_at',
			'type'       => 'timestamp',
			'default'    => null,
			'created'    => true,
			'date_query' => true,
			'sortable'   => true,
		],

	];
}
