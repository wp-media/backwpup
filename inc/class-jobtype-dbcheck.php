<?php
/**
 *
 */
class BackWPup_JobType_DBCheck extends BackWPup_JobTypes {

	/**
	 *
	 */
	public function __construct() {

		$this->info[ 'ID' ]          = 'DBCHECK';
		$this->info[ 'name' ]        = __( 'DB Check', 'backwpup' );
		$this->info[ 'description' ] = __( 'Check database tables', 'backwpup' );
		$this->info[ 'URI' ]         = translate( BackWPup::get_plugin_data( 'PluginURI' ), 'backwpup' );
		$this->info[ 'author' ]      = BackWPup::get_plugin_data( 'Author' );
		$this->info[ 'authorURI' ]   = translate( BackWPup::get_plugin_data( 'AuthorURI' ), 'backwpup' );
		$this->info[ 'version' ]     = BackWPup::get_plugin_data( 'Version' );

	}

	/**
	 * @return array
	 */
	public function option_defaults() {
		return array( 'dbcheckwponly' => TRUE, 'dbcheckrepair' => FALSE );
	}


	/**
	 * @param $jobid
	 */
	public function edit_tab( $jobid ) {
		?>
		<h3 class="title"><?php _e( 'Settings for database check', 'backwpup' ) ?></h3>
		<p></p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php _e( 'WordPress tables only', 'backwpup' ); ?></th>
				<td>
					<label for="iddbcheckwponly">
					<input class="checkbox" value="1" id="iddbcheckwponly"
						   type="checkbox" <?php checked( BackWPup_Option::get( $jobid, 'dbcheckwponly' ), TRUE ); ?>
						   name="dbcheckwponly"/> <?php _e( 'Check WordPress database tables only', 'backwpup' ); ?>
                    </label>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php _e( 'Repair', 'backwpup' ); ?></th>
				<td>
                    <label for="iddbcheckrepair">
					<input class="checkbox" value="1" id="iddbcheckrepair"
						   type="checkbox" <?php checked( BackWPup_Option::get( $jobid, 'dbcheckrepair' ), TRUE ); ?>
						   name="dbcheckrepair" /> <?php _e( 'Try to repair defect table', 'backwpup' ); ?>
					</label>
				</td>
			</tr>
		</table>
		<?php
	}


	/**
	 * @param $jobid
	 */
	public function edit_form_post_save( $jobid ) {
		BackWPup_Option::update( $jobid, 'dbcheckwponly', ( isset( $_POST[ 'dbcheckwponly' ] ) && $_POST[ 'dbcheckwponly' ] == 1 ) ? TRUE : FALSE );
		BackWPup_Option::update( $jobid, 'dbcheckrepair', ( isset( $_POST[ 'dbcheckrepair' ] ) && $_POST[ 'dbcheckrepair' ] == 1 ) ? TRUE : FALSE );
	}

	/**
	 * @param $job_object
	 * @return bool
	 */
	public function job_run( $job_object ) {
		global $wpdb;
		/* @var wpdb $wpdb */

		$job_object->log( sprintf( __( '%d. Trying to check database&#160;&hellip;', 'backwpup' ), $job_object->steps_data[ $job_object->step_working ][ 'STEP_TRY' ] ) );
		if ( ! isset( $job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ] ) || ! is_array( $job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ] ) )
			$job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ] = array();

		//to check
		$tables = array();
		$tablestype = array();
		$restables = $wpdb->get_results( 'SHOW FULL TABLES FROM `' . DB_NAME . '`', ARRAY_N );
		foreach ( $restables as $table ) {
			if ( $job_object->job[ 'dbcheckwponly' ] && substr( $table[ 0 ], 0, strlen( $wpdb->prefix ) ) != $wpdb->prefix ) 
				continue;	
			$tables[ ]                 = $table[ 0 ];
			$tablestype[ $table[ 0 ] ] = $table[ 1 ];
		}

		//Set num
		$job_object->substeps_todo = sizeof( $tables );

		//Get table status
		$status = array();
		$resstatus = $wpdb->get_results( "SHOW TABLE STATUS FROM `" . DB_NAME . "`", ARRAY_A );
		foreach ( $resstatus as $tablestatus ) {
			$status[ $tablestatus[ 'Name' ] ] = $tablestatus;
		}

		//check tables
		if ( $job_object->substeps_todo > 0 ) {
			foreach ( $tables as $table ) {
				if ( in_array( $table, $job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ] ) )
					continue;

				if ( $tablestype[ $table ] == 'VIEW' ) {
					$job_object->log( sprintf( __( 'Table %1$s is a view. Not checked.', 'backwpup' ), $table ) );
					continue;
				}

				if ( $status[ $table ][ 'Engine' ] != 'MyISAM' && $status[ $table ][ 'Engine' ] != 'InnoDB' ) {
					$job_object->log( sprintf( __( 'Table %1$s is not a MyISAM/InnoDB table. Not checked.', 'backwpup' ), $table ) );
					continue;
				}

				//CHECK TABLE funktioniert bei MyISAM- und InnoDB-Tabellen (http://dev.mysql.com/doc/refman/5.1/de/check-table.html)
				$check = $wpdb->get_row( "CHECK TABLE `" . $table . "` MEDIUM", OBJECT );
				if ( $check->Msg_text == 'OK' )
					$job_object->log( sprintf( __( 'Result of table check for %1$s is: %2$s', 'backwpup' ), $table, $check->Msg_text ) );
				elseif ( strtolower( $check->Msg_type ) == 'warning' )
					$job_object->log( sprintf( __( 'Result of table check for %1$s is: %2$s', 'backwpup' ), $table, $check->Msg_text ), E_USER_WARNING );
				else
					$job_object->log( sprintf( __( 'Result of table check for %1$s is: %2$s', 'backwpup' ), $table, $check->Msg_text ), E_USER_ERROR );

				//Try to Repair table
				if ( ! empty( $job_object->job[ 'dbcheckrepair' ] ) && $check->Msg_text != 'OK' && $status[ $table ][ 'Engine' ] == 'MyISAM' ) {
					$repair = $wpdb->get_row( 'REPAIR TABLE `' . $table . '` EXTENDED', OBJECT );
					if ( $repair->Msg_type == 'OK' )
						$job_object->log( sprintf( __( 'Result of table repair for %1$s is: %2$s', 'backwpup' ), $table, $repair->Msg_text ) );
					elseif ( strtolower( $repair->Msg_type ) == 'warning' )
						$job_object->log( sprintf( __( 'Result of table repair for %1$s is: %2$s', 'backwpup' ), $table, $repair->Msg_text ), E_USER_WARNING );
					else
						$job_object->log( sprintf( __( 'Result of table repair for %1$s is: %2$s', 'backwpup' ), $table, $repair->Msg_text ), E_USER_ERROR );
				}
				$job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ][ ] = $table;
				$job_object->substeps_done ++;
			}
			$job_object->log( __( 'Database check done!', 'backwpup' ) );
		}
		else {
			$job_object->log( __( 'No tables to check.', 'backwpup' ) );
		}

		unset( $job_object->steps_data[ $job_object->step_working ][ 'DONETABLE' ] );
		return TRUE;
	}
}
