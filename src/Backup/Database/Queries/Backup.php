<?php

namespace WPMedia\BackWPup\Backup\Database\Queries;

use WPMedia\BackWPup\Common\Database\Queries\AbstractQuery;
use WPMedia\BackWPup\Backup\Database\Row\Backup as BackupRow;
use WPMedia\BackWPup\Backup\Database\Schemas\Backup as BackupSchema;

/**
 * Backup Query.
 */
class Backup extends AbstractQuery {

	/**
	 * Name of the database table to query.
	 *
	 * @var   string
	 */
	protected $table_name = 'bwpup_backups';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * Keep this short, but descriptive. I.E. "tr" for term relationships.
	 *
	 * This is used to avoid collisions with JOINs.
	 *
	 * @var   string
	 */
	protected $table_alias = 'bwpup_backups';

	/**
	 * Name of class used to setup the database schema.
	 *
	 * @var   string
	 */
	protected $table_schema = BackupSchema::class;

	/** Item ******************************************************************/

	/**
	 * Name for a single item.
	 *
	 * Use underscores between words. I.E. "term_relationship"
	 *
	 * This is used to automatically generate action hooks.
	 *
	 * @var   string
	 */
	protected $item_name = 'backup';

	/**
	 * Plural version for a group of items.
	 *
	 * Use underscores between words. I.E. "term_relationships"
	 *
	 * This is used to automatically generate action hooks.
	 *
	 * @var   string
	 */
	protected $item_name_plural = 'backups';

	/**
	 * Name of class used to turn IDs into first-class objects.
	 *
	 * This is used when looping through return values to guarantee their shape.
	 *
	 * @var   mixed
	 */
	protected $item_shape = BackupRow::class;

	/**
	 * Add new backup row with status created
	 *
	 * @param string $destination_id Destination ID.
	 * @param string $filename Backup filename.
	 *
	 * @return bool
	 */
	public function add( $destination_id, $filename ) {
		return $this->add_item(
			[
				'destination' => $destination_id,
				'filename'    => $filename,
				'status'      => 'created',
			]
		);
	}

	/**
	 * Set backup row status
	 *
	 * @param int    $id Backup ID.
	 * @param string $status Backup status.
	 *
	 * @return bool
	 */
	public function set_status( $id, $status ) {
		return $this->update_item(
			$id,
			[
				'status' => $status,
			]
		);
	}
}
