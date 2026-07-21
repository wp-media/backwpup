<?php

namespace WPMedia\BackWPup\API;

/**
 * Shared capability-string constants for BackWPup.
 *
 * Centralizes the capability strings registered in `inc/class-install.php` so
 * REST controllers and Abilities-API classes can gate their mutating actions
 * against the correct granular capability instead of hardcoding string
 * literals ad hoc.
 *
 * This class holds only constants, no logic or state.
 */
final class Capability {

	/**
	 * Broad, umbrella capability granted to every BackWPup role (e.g. Dashboard access).
	 *
	 * @var string
	 */
	public const GENERAL = 'backwpup';

	/**
	 * Capability required to create/update/delete jobs.
	 *
	 * @var string
	 */
	public const JOBS_EDIT = 'backwpup_jobs_edit';

	/**
	 * Capability required to start a backup job.
	 *
	 * @var string
	 */
	public const JOBS_START = 'backwpup_jobs_start';

	/**
	 * Capability required to access/update the standalone Settings page.
	 *
	 * @var string
	 */
	public const SETTINGS = 'backwpup_settings';

	/**
	 * Capability required to download backup files.
	 *
	 * @var string
	 */
	public const BACKUPS_DOWNLOAD = 'backwpup_backups_download';

	/**
	 * Capability required to delete backup files.
	 *
	 * @var string
	 */
	public const BACKUPS_DELETE = 'backwpup_backups_delete';

	/**
	 * Capability required to access the Restore page.
	 *
	 * @var string
	 */
	public const RESTORE = 'backwpup_restore';
}
