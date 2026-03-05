<?php
class BackWPup_JobType_DBCheck extends BackWPup_JobTypes
{
    public function __construct()
    {
        $this->info['ID'] = 'DBCHECK';
        $this->info['name'] = __('DB Check', 'backwpup');
        $this->info['description'] = __('Check database tables', 'backwpup');
        $this->info['URI'] = __('http://backwpup.com', 'backwpup');
        $this->info['author'] = 'WP Media';
        $this->info['authorURI'] = 'https://wp-media.me';
        $this->info['version'] = BackWPup::get_plugin_data('Version');
    }

    /**
     * @return array
     */
    public function option_defaults()
    {
        return ['dbcheckwponly' => true, 'dbcheckrepair' => false];
    }

    /**
     * @param $jobid
     */
    public function edit_tab($jobid)
    {
        ?>
		<h3 class="title"><?php esc_html_e('Settings for database check', 'backwpup'); ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e('WordPress tables only', 'backwpup'); ?></th>
				<td>
					<label for="iddbcheckwponly">
					<input class="checkbox" value="1" id="iddbcheckwponly"
						   type="checkbox" <?php checked(BackWPup_Option::get($jobid, 'dbcheckwponly'), true); ?>
						   name="dbcheckwponly"/> <?php esc_html_e('Check WordPress database tables only', 'backwpup'); ?>
                    </label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e('Repair', 'backwpup'); ?></th>
				<td>
                    <label for="iddbcheckrepair">
					<input class="checkbox" value="1" id="iddbcheckrepair"
						   type="checkbox" <?php checked(BackWPup_Option::get($jobid, 'dbcheckrepair'), true); ?>
						   name="dbcheckrepair" /> <?php esc_html_e('Try to repair defect table', 'backwpup'); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
    }

    /**
     * @param $jobid
     */
    public function edit_form_post_save($jobid)
    {
        BackWPup_Option::update($jobid, 'dbcheckwponly', !empty($_POST['dbcheckwponly']));
        BackWPup_Option::update($jobid, 'dbcheckrepair', !empty($_POST['dbcheckrepair']));
    }

    /**
     * @param $job_object
     *
     * @return bool
     */
    public function job_run(BackWPup_Job $job_object)
    {
        /** @var wpdb $wpdb */
        global $wpdb;

		$job_object->log(
			sprintf(
			/* translators: %d: attempt number. */
			__( '%d. Trying to check database&#160;&hellip;', 'backwpup' ),
			$job_object->steps_data[ $job_object->step_working ]['STEP_TRY']
		)
			);
		if ( ! isset( $job_object->steps_data[ $job_object->step_working ]['DONETABLE'] ) || ! is_array( $job_object->steps_data[ $job_object->step_working ]['DONETABLE'] ) ) {
			$job_object->steps_data[ $job_object->step_working ]['DONETABLE'] = [];
		}

		// To check.
		$tables      = [];
		$tablestype  = [];
		$cache_group = 'backwpup_dbcheck';
		$tables_key  = 'show_full_tables_' . DB_NAME;
		$restables   = wp_cache_get( $tables_key, $cache_group );
		if ( false === $restables ) {
			$restables = $wpdb->get_results( 'SHOW FULL TABLES', ARRAY_N ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- No WP API for SHOW FULL TABLES.
			wp_cache_set( $tables_key, $restables, $cache_group, MINUTE_IN_SECONDS );
		}

        foreach ($restables as $table) {
            if ($job_object->job['dbcheckwponly'] && substr((string) $table[0], 0, strlen($wpdb->prefix)) != $wpdb->prefix) {
                continue;
            }
            $tables[] = $table[0];
            $tablestype[$table[0]] = $table[1];
        }

		// Set num.
		$job_object->substeps_todo = count( $tables );

		// Get table status.
		$status     = [];
		$status_key = 'show_table_status_' . DB_NAME;
		$resstatus  = wp_cache_get( $status_key, $cache_group );
		if ( false === $resstatus ) {
			$resstatus = $wpdb->get_results( 'SHOW TABLE STATUS', ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- No WP API for SHOW TABLE STATUS.
			wp_cache_set( $status_key, $resstatus, $cache_group, MINUTE_IN_SECONDS );
		}

        foreach ($resstatus as $tablestatus) {
            $status[$tablestatus['Name']] = $tablestatus;
        }

		// Check tables.
		if ( $job_object->substeps_todo > 0 ) {
			foreach ( $tables as $table ) {
				if ( in_array( $table, $job_object->steps_data[ $job_object->step_working ]['DONETABLE'], true ) ) {
					continue;
                }

				if ( ! isset( $tablestype[ $table ] ) ) {
					continue;
                }

				$table_name = esc_sql( $table );

				if ( 'VIEW' === $tablestype[ $table ] ) {
					/* translators: %s: table name. */
					$job_object->log( sprintf( __( 'Table %1$s is a view. Not checked.', 'backwpup' ), $table ) );

                    continue;
                }

				if ( 'MyISAM' !== $status[ $table ]['Engine'] && 'InnoDB' !== $status[ $table ]['Engine'] ) {
					/* translators: %s: table name. */
					$job_object->log( sprintf( __( 'Table %1$s is not a MyISAM/InnoDB table. Not checked.', 'backwpup' ), $table ) );

                    continue;
                }

				// CHECK TABLE funktioniert bei MyISAM- und InnoDB-Tabellen (http://dev.mysql.com/doc/refman/5.1/de/check-table.html).
				$check                = $wpdb->get_row( "CHECK TABLE `{$table_name}` MEDIUM", OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name comes from SHOW TABLES.
				$check_msg_text       = (string) $check->Msg_text; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$check_msg_type       = (string) $check->Msg_type; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$check_msg_text_lower = strtolower( $check_msg_text );
				$check_msg_type_lower = strtolower( $check_msg_type );
				/* translators: 1: table name, 2: database message. */
				$check_result_message = __( 'Result of table check for %1$s is: %2$s', 'backwpup' );
				if ( 'ok' === $check_msg_text_lower ) {
					if ( $job_object->is_debug() ) {
						$job_object->log( sprintf( $check_result_message, $table, $check_msg_text ) );
					}
				} elseif ( 'warning' === $check_msg_type_lower ) {
					$job_object->log( sprintf( $check_result_message, $table, $check_msg_text ), E_USER_WARNING );
				} else {
					$job_object->log( sprintf( $check_result_message, $table, $check_msg_text ), E_USER_ERROR );
				}
				// Try to repair table.
				if ( ! empty( $job_object->job['dbcheckrepair'] ) && 'ok' !== $check_msg_text_lower && 'MyISAM' === $status[ $table ]['Engine'] ) {
					$repair                = $wpdb->get_row( "REPAIR TABLE `{$table_name}` EXTENDED", OBJECT ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table name comes from SHOW TABLES.
					$repair_msg_text       = (string) $repair->Msg_text; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$repair_msg_type       = (string) $repair->Msg_type; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$repair_msg_text_lower = strtolower( $repair_msg_text );
					$repair_msg_type_lower = strtolower( $repair_msg_type );
					/* translators: 1: table name, 2: database message. */
					$repair_result_message = __( 'Result of table repair for %1$s is: %2$s', 'backwpup' );
					if ( 'ok' === $repair_msg_text_lower ) {
						$job_object->log( sprintf( $repair_result_message, $table, $repair_msg_text ) );
					} elseif ( 'warning' === $repair_msg_type_lower ) {
						$job_object->log( sprintf( $repair_result_message, $table, $repair_msg_text ), E_USER_WARNING );
					} else {
						$job_object->log( sprintf( $repair_result_message, $table, $repair_msg_text ), E_USER_ERROR );
					}
				}
				$job_object->steps_data[ $job_object->step_working ]['DONETABLE'][] = $table;
				++$job_object->substeps_done;
			}
			$job_object->log( __( 'Database check done!', 'backwpup' ) );
		} else {
			$job_object->log( __( 'No tables to check.', 'backwpup' ) );
		}

		unset( $job_object->steps_data[ $job_object->step_working ]['DONETABLE'] );

        return true;
    }
}
