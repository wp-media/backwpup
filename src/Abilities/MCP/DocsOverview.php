<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Abilities\MCP;

use WPMedia\BackWPup\Abilities\AbilitiesInterface;

/**
 * DocsOverview Resource
 *
 * Provides documentation about the BackWPup MCP server.
 */
class DocsOverview implements AbilitiesInterface {
	/**
	 * Ability ID
	 */
	private const ABILITY_ID = 'backwpup/docs-overview';

	/**
	 * Resource URI
	 */
	private const RESOURCE_URI = 'groupone-backwpup-plugin://docs/overview';

	/**
	 * Resource name
	 */
	private const RESOURCE_NAME = 'backwpup_docs_overview';

	/**
	 * Register the ability
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		wp_register_ability(
			self::ABILITY_ID,
			[
				'label'               => __( 'BackWPup Documentation Overview', 'backwpup' ),
				'category'            => 'backwpup-jobs',
				'description'         => __( 'Overview documentation for the BackWPup MCP server', 'backwpup' ),
				'execute_callback'    => [ $this, 'execute' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'meta'                => [
					'mcp' => [
						'public'   => true,
						'type'     => 'resource',
						'uri'      => self::RESOURCE_URI,
						'name'     => 'BackWPup MCP Server Overview',
						'mimeType' => 'text/markdown',
					],
				],
			]
		);
	}

	/**
	 * Check if the current user has permission to execute this ability
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		if ( current_user_can( 'backwpup' ) ) {
			return true;
		}

		do_action( 'backwpup_mcp_permission_denied', self::ABILITY_ID, self::RESOURCE_NAME, 'backwpup' );

		return false;
	}

	/**
	 * Execute the ability - return documentation content
	 *
	 * @param array $args Input arguments (unused for resources).
	 *
	 * @return string Markdown documentation content.
	 */
	public function execute( array $args = [] ): string {
		return $this->get_documentation_content();
	}

	/**
	 * Get the documentation content
	 *
	 * @return string
	 */
	private function get_documentation_content(): string {
		return <<<'MARKDOWN'
# BackWPup MCP Server Overview

## Purpose

The BackWPup MCP server enables AI assistants to manage WordPress backups proactively. Before performing any risky operation on the WordPress site, the AI should offer to create a backup.

## Available Tools

### 1. **backwpup_list_jobs**
Lists all configured backup jobs with their destinations and schedules.

**When to call:**
- **Before any risky operation** (plugin updates, theme changes, core updates, database migrations)
- To offer the user a choice of backup destinations
- To check if backup jobs are properly configured

**Example usage:**
```
User: Update WooCommerce to the latest version.
AI: Let me check your backup configuration first...
    [calls backwpup_list_jobs]
    I can see you have:
    - Job #1: "Daily Backup" → Dropbox
    - Job #2: "Weekly Full Backup" → Amazon S3
    
    Should I create a backup before updating? I recommend using the Dropbox job for quick recovery.
```

### 2. **backwpup_get_backup_history**
Returns recent backup history and whether a backup is currently running.

**When to call:**
- **Before triggering a new backup** to avoid duplicates
- To inform the user of their backup status
- After triggering a backup to confirm it started

**Important:** ALWAYS call this before calling `backwpup_run_job` to prevent starting duplicate backups.

### 3. **backwpup_run_job**
Triggers an immediate backup - either a specific job or the default "Backup Now".

**Parameters:**
- `job_id` (optional integer): Pass a job ID from `backwpup_list_jobs` to run a specific job with specific destinations (e.g., Dropbox, S3)
- Omit `job_id` to trigger default "Backup Now" (files + database to local folder)

**When to call:**
- After the user confirms they want a backup
- Before risky operations (with user consent)

**Workflow:**
1. Call `backwpup_list_jobs` to show available jobs
2. Call `backwpup_get_backup_history` to check if a backup is already running
3. If no backup is running, call `backwpup_run_job` with optional `job_id`
4. Wait briefly, then call `backwpup_get_backup_history` again to confirm it started

### 4. **backwpup_cancel_job**
Cancels/aborts a currently running backup job.

**When to call:**
- When a backup is stuck or taking too long
- User explicitly requests to stop the backup
- Need to start a different backup job urgently
- Before shutdown/maintenance operations

**Important:** Always call `backwpup_get_backup_history` first to verify a job is actually running before attempting to cancel.

**Example usage:**
```
User: The backup is taking forever, can you stop it?
AI: [calls backwpup_get_backup_history]
    Yes, there's a backup running. Let me cancel it for you.
    [calls backwpup_cancel_job]
    ✓ Job "Daily Backup" has been cancelled successfully.
```

**Workflow:**
1. Call `backwpup_get_backup_history` to confirm a job is running
2. If running, call `backwpup_cancel_job`
3. Wait briefly, then call `backwpup_get_backup_history` to confirm cancellation

### 5. **backwpup_get_backup_logs**
Retrieves backup log content for troubleshooting and diagnosing backup failures.

**Parameters:**
- `backup_id` (optional integer): Specific backup ID - most precise way to get logs
- `backup_file` (optional string): Backup filename (e.g., "backwpup_1_2024-04-23_10-30-00_ABC123.zip")
- `job_id` (optional integer): Get latest log from this job
- `lines` (optional integer): Number of log lines to return (default 200, max 1000)

**When to call:**
- User asks to see backup logs ("show me the latest backup log")
- Diagnosing failed backups ("why did the backup fail?")
- Troubleshooting errors or warnings
- After a backup completes with errors

**Important:** Parameters are prioritized: `backup_id` > `backup_file` > `job_id` > latest overall. Omit all to get the most recent log.

**Example usage:**
```
User: Can I see the log of the latest backup?
AI: [calls backwpup_get_backup_logs with no parameters]
    Here's the log from your latest backup (Daily Backup, ran 2 hours ago):
    
    [Shows truncated log content]
    
    Status: Completed with 2 warnings
    Runtime: 45 seconds
    
User: Show me the log for backup #4
AI: [calls backwpup_get_backup_history to get backup list]
    [finds backup_id for backup #4]
    [calls backwpup_get_backup_logs with backup_id]
    Here's the log for that backup...
```

**Return data includes:**
- Log content (plain text, HTML stripped)
- Metadata: job name, timestamp, errors/warnings count, runtime
- Truncation info if log exceeds requested lines

**Workflow:**
1. Call `backwpup_get_backup_history` to see recent backups
2. Use `backup_id` from history to call `backwpup_get_backup_logs`
3. Analyze errors/warnings in the log content
4. Suggest solutions based on common error patterns

## Proactive Backup Suggestions

The AI assistant should **proactively suggest backups** in these scenarios:

### Critical Operations (ALWAYS suggest backup):
- **WordPress core updates** (minor or major)
- **Plugin updates** (especially WooCommerce, security plugins, form builders)
- **Theme updates** or theme switches
- **Database operations** (migrations, large imports, bulk deletions)
- **File system changes** (permission changes, .htaccess modifications)
- **PHP version upgrades**

### Moderate Risk Operations (Suggest backup):
- Installing new plugins
- Modifying plugin/theme code files
- Bulk content operations (mass deletes, imports)

### Conversation Flow Example:

```
User: Update WooCommerce to version 9.0.0

AI: Before updating WooCommerce, I strongly recommend creating a backup.
    [calls backwpup_list_jobs]
    You have a Dropbox backup job configured. Should I run it?

User: Yes

AI: [calls backwpup_get_backup_history to check]
    No backup currently running.
    [calls backwpup_run_job with Dropbox job ID]
    ✓ Backup started successfully!
    
    Now proceeding with WooCommerce update...
```

## Best Practices

1. **Always check backup history before triggering** to avoid conflicts
2. **Offer specific job choices** when multiple destinations are available
3. **Confirm backup started** before proceeding with risky operations
4. **Explain why** a backup is recommended (builds user trust)
5. **Don't be pushy** - suggest, don't demand

## Error Handling

- If backup is already running: Offer to cancel it with `backwpup_cancel_job` or wait
- If no jobs configured: Suggest setting up backup jobs
- If backup fails to start: Warn user before proceeding with risky operation
- If trying to cancel when no job is running: Inform user no backup is active
- If backup completes with errors/warnings: Use `backwpup_get_backup_logs` to diagnose the issue

## Security

- All tools require `backwpup` WordPress capability
- Log viewing requires `backwpup_logs` capability
- Unauthorized requests return permission errors
- Job IDs are validated before execution

---

**Remember:** The goal is to make backups a natural part of the workflow, protecting users from disasters without adding friction.
MARKDOWN;
	}
}
