<?php
/**
 * BackWPup backups admin page.
 *
 * @package BackWPup
 */
final class BackWPup_Page_Backups extends WP_List_Table {

	/**
	 * Nonce action for list sorting.
	 */
	private const LIST_NONCE_ACTION = 'backwpup_backups_list';

	/**
	 * List table instance.
	 *
	 * @var self|null
	 */
	private static $listtable;

	/**
	 * Destinations list.
	 *
	 * @var array
	 */
	private $destinations = [];

	/**
	 * Current job ID.
	 *
	 * @var int
	 */
	private $jobid = 1;

	/**
	 * Current destination slug.
	 *
	 * @var string
	 */
	private $dest = 'FOLDER';

	/**
	 * BackWPup_Page_Backups constructor.
	 */
	public function __construct() {
		parent::__construct(
			[
				'plural'   => 'backups',
				'singular' => 'backup',
				'ajax'     => true,
			]
			);

		$this->destinations = BackWPup::get_registered_destinations();
	}

	/**
	 * Check if the user can access the backups list via Ajax.
	 *
	 * @return bool
	 */
	public function ajax_user_can() {
		return current_user_can( 'backwpup_backups' );
	}

	/**
	 * Prepare list table items.
	 *
	 * @return void
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'backwpupbackups_per_page' );
		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = 20;
		}

		$jobdest               = '';
		$jobdest_top_button    = filter_input( INPUT_GET, 'jobdets-button-top', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$jobdest_bottom_button = filter_input( INPUT_GET, 'jobdets-button-bottom', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( $jobdest_top_button ) {
			$jobdest = filter_input( INPUT_GET, 'jobdest-top', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}
		if ( $jobdest_bottom_button ) {
			$jobdest = filter_input( INPUT_GET, 'jobdest-bottom', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}
		$jobdest = $jobdest ? sanitize_text_field( $jobdest ) : '';

		if ( empty( $jobdest ) ) {
			$jobdests = $this->get_destinations_list();
			if ( empty( $jobdests ) ) {
				$jobdests = [ '_' ];
			}
			$jobdest                    = $jobdests[0];
			$_GET['jobdest-top']        = $jobdests[0];
			$_GET['jobdets-button-top'] = 'empty';
		}

		[$this->jobid, $this->dest] = explode( '_', (string) $jobdest );

		if ( ! empty( $this->destinations[ $this->dest ]['class'] ) ) {
			/**
			 * Destination handler instance.
			 *
			 * @var BackWPup_Destinations $dest_object
			 */
			$dest_object = BackWPup::get_destination( $this->dest );
			$this->items = $dest_object->file_get_list( $jobdest );
		}

		if ( ! $this->items ) {
			$this->items = '';

			return;
		}

		// Sorting.
		$order   = 'desc';
		$orderby = 'time';
		$nonce   = $this->get_list_nonce();
		if ( $nonce && wp_verify_nonce( $nonce, self::LIST_NONCE_ACTION ) ) {
			$order = filter_input( INPUT_GET, 'order', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( null === $order && isset( $_GET['order'] ) ) {
				$order = sanitize_text_field( wp_unslash( $_GET['order'] ) );
			} else {
				$order = $order ? sanitize_text_field( $order ) : '';
			}
			$order = $order ? sanitize_key( $order ) : 'desc';
			$order = strtolower( $order );
			$order = in_array( $order, [ 'asc', 'desc' ], true ) ? $order : 'desc';

			$orderby = filter_input( INPUT_GET, 'orderby', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
			if ( null === $orderby && isset( $_GET['orderby'] ) ) {
				$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) );
			} else {
				$orderby = $orderby ? sanitize_text_field( $orderby ) : '';
			}
			$orderby = $orderby ? sanitize_key( $orderby ) : 'time';
			$orderby = strtolower( $orderby );
			$orderby = in_array( $orderby, [ 'time', 'file', 'folder', 'size' ], true ) ? $orderby : 'time';
		}
		$tmp = [];

		if ( 'time' === $orderby ) {
			if ( 'asc' === $order ) {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma['time'];
				}
				array_multisort( $tmp, SORT_ASC, $this->items );
			} else {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma['time'];
				}
				array_multisort( $tmp, SORT_DESC, $this->items );
			}
		} elseif ( 'file' === $orderby ) {
			if ( 'asc' === $order ) {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma['filename'];
				}
				array_multisort( $tmp, SORT_ASC, $this->items );
			} else {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma['filename'];
				}
				array_multisort( $tmp, SORT_DESC, $this->items );
			}
		} elseif ( 'folder' === $orderby ) {
			if ( 'asc' === $order ) {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma['folder'];
				}
				array_multisort( $tmp, SORT_ASC, $this->items );
			} else {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma['folder'];
				}
				array_multisort( $tmp, SORT_DESC, $this->items );
			}
		} elseif ( 'size' === $orderby ) {
			if ( 'asc' === $order ) {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma['filesize'];
				}
				array_multisort( $tmp, SORT_ASC, $this->items );
			} else {
				foreach ( $this->items as &$ma ) {
					$tmp[] = &$ma['filesize'];
				}
				array_multisort( $tmp, SORT_DESC, $this->items );
			}
		}

		$this->set_pagination_args(
			[
				'total_items' => count( $this->items ),
				'per_page'    => $per_page,
				'jobdest'     => $jobdest,
				'orderby'     => $orderby,
				'order'       => $order,
			]
			);

		// Only display items on page.
		$start = intval( ( $this->get_pagenum() - 1 ) * $per_page );
		$end   = $start + $per_page;
		if ( $end > count( $this->items ) ) {
			$end = count( $this->items );
		}

		$i           = -1;
		$paged_items = [];

		foreach ( $this->items as $item ) {
			++$i;
			if ( $i < $start ) {
				continue;
			}
			if ( $i >= $end ) {
				break;
			}
			$paged_items[] = $item;
		}

		$this->items = $paged_items;
	}

	/**
	 * {@inheritdoc}
	 */
	public function no_items() {
		esc_html_e( 'No files could be found. (List will be generated during next backup.)', 'backwpup' );
	}

	/**
	 * Get bulk actions for the backups table.
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		if ( ! $this->has_items() ) {
			return [];
		}

		$actions           = [];
		$actions['delete'] = __( 'Delete', 'backwpup' );

		return $actions;
	}

	/**
	 * Render the extra table navigation.
	 *
	 * @param string $which Top or bottom table nav position.
	 *
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		$destinations_list = $this->get_destinations_list();

		if ( count( $destinations_list ) < 1 ) {
			return;
		}

		if ( count( $destinations_list ) === 1 ) {
			echo '<input type="hidden" name="jobdest-' . esc_attr( $which ) . '" value="' . esc_attr( $destinations_list[0] ) . '">';

			return;
		} ?>
		<div class="alignleft actions">
			<label for="jobdest-<?php echo esc_attr( $which ); ?>">
				<select name="jobdest-<?php echo esc_attr( $which ); ?>" class="postform"
						id="jobdest-<?php echo esc_attr( $which ); ?>">
					<?php
					foreach ( $destinations_list as $jobdest ) {
						[$jobid, $dest] = explode( '_', (string) $jobdest );
						echo "\t<option value=\"" . esc_attr( $jobdest ) . '" ' . selected(
							$this->jobid . '_' . $this->dest,
							$jobdest,
							false
						) . '>' . esc_html( $dest ) . ': ' . esc_html(
							BackWPup_Option::get(
							$jobid,
							'name'
						)
							) . '</option>' . PHP_EOL;
					}
					?>
				</select>
			</label>
			<?php
			submit_button(
						__( 'Change destination', 'backwpup' ),
						'secondary',
						'jobdets-button-' . $which,
						false,
						[ 'id' => 'query-submit-' . $which ]
					);
			?>
		</div>
		<?php
	}

	/**
	 * Get list table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$posts_columns           = [];
		$posts_columns['cb']     = '<input type="checkbox" />';
		$posts_columns['time']   = __( 'Time', 'backwpup' );
		$posts_columns['file']   = __( 'File', 'backwpup' );
		$posts_columns['folder'] = __( 'Folder', 'backwpup' );
		$posts_columns['size']   = __( 'Size', 'backwpup' );

		return $posts_columns;
	}

	/**
	 * Get sortable columns.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return [
			'file'   => [ 'file', false ],
			'folder' => 'folder',
			'size'   => 'size',
			'time'   => [ 'time', false ],
		];
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param array $item Item data.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return '<input type="checkbox" name="backupfiles[]" value="' . esc_attr( $item['file'] ) . '" />';
	}

	/**
	 * Get the destinations list.
	 *
	 * @return array
	 */
	public function get_destinations_list() {
		$jobdest = [];
		$jobids  = BackWPup_Option::get_job_ids();

		foreach ( $jobids as $jobid ) {
			if ( BackWPup_Option::get( $jobid, 'backuptype' ) === 'sync' ) {
				continue;
			}
			$dests = BackWPup_Option::get( $jobid, 'destinations' );

			foreach ( $dests as $dest ) {
				if ( ! $this->destinations[ $dest ]['class'] ) {
					continue;
				}
				$dest_class  = BackWPup::get_destination( $dest );
				$can_do_dest = $dest_class->file_get_list( $jobid . '_' . $dest );
				if ( ! empty( $can_do_dest ) ) {
					$jobdest[] = $jobid . '_' . $dest;
				}
			}
		}

		return $jobdest;
	}

	/**
	 * Render the file column.
	 *
	 * @param array $item Item data.
	 *
	 * @return string
	 */
	public function column_file( $item ) {
		$actions = [];

		$r = '<strong>' . esc_attr( $item['filename'] ) . '</strong><br />';
		if ( ! empty( $item['info'] ) ) {
			$r .= esc_attr( $item['info'] ) . '<br />';
		}

		if ( current_user_can( 'backwpup_backups_delete' ) ) {
			$actions['delete'] = $this->delete_item_action( $item );
		}

		if ( ! empty( $item['downloadurl'] ) && current_user_can( 'backwpup_backups_download' ) ) {
			try {
				$actions['download'] = $this->download_item_action( $item );

				if ( 'HIDRIVE' === $this->dest ) {
					$download_url = wp_nonce_url( $item['downloadurl'], 'backwpup_action_nonce' );

					if ( $item['filesize'] > 10485760 ) { // 10 MB.
						$request       = new BackWPup_Pro_Destination_HiDrive_Request();
						$authorization = new BackWPup_Pro_Destination_HiDrive_Authorization( $request );
						$api           = new BackWPup_Pro_Destination_HiDrive_Api( $request, $authorization );
						$response      = $api->temporal_download_url( $this->jobid, $item['file'] );
						$response_body = json_decode( (string) $response['body'] );

						if ( isset( $response_body->url ) ) {
							$download_url = $response_body->url;
						}
					}

					$actions['download'] = '<a href="' . $download_url . '" class="backup-download-link">Download</a>';
				}
			} catch ( BackWPup_Factory_Exception $e ) {
				$actions['download'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					wp_nonce_url( $item['downloadurl'], 'backwpup_action_nonce' ),
					__( 'Download', 'backwpup' )
				);
			}
		}

		// Add restore URL to link list.
		if ( current_user_can( 'backwpup_restore' ) && ! empty( $item['restoreurl'] ) ) {
			$item['restoreurl'] = add_query_arg(
				[
					'step'             => 1,
					'trigger_download' => 1,
				],
				$item['restoreurl']
			);
			$actions['restore'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				wp_nonce_url( $item['restoreurl'], 'restore-backup_' . $this->jobid ),
				__( 'Restore', 'backwpup' )
			);
		}

		$r .= $this->row_actions( $actions );

		return $r;
	}

	/**
	 * Render the folder column.
	 *
	 * @param array $item Item data.
	 *
	 * @return string
	 */
	public function column_folder( $item ) {
		return esc_attr( $item['folder'] );
	}

	/**
	 * Render the size column.
	 *
	 * @param array $item Item data.
	 *
	 * @return string
	 */
	public function column_size( $item ) {
		if ( ! empty( $item['filesize'] ) && -1 !== $item['filesize'] ) {
			return size_format( $item['filesize'], 2 );
		}

		return esc_html__( '?', 'backwpup' );
	}

	/**
	 * Render the time column.
	 *
	 * @param array $item Item data.
	 *
	 * @return string
	 */
	public function column_time( $item ) {
		return sprintf(
			// translators: %1$s = date, %2$s = time.
			esc_html__( '%1$s at %2$s', 'backwpup' ),
			esc_html( wp_date( get_option( 'date_format' ), $item['time'] ) ),
			esc_html( wp_date( get_option( 'time_format' ), $item['time'] ) )
		);
	}

	/**
	 * Load the backups page.
	 *
	 * @return void
	 */
	public static function load() {
		global $current_user;

		// Create table.
		self::$listtable = new BackWPup_Page_Backups();

		if ( empty( self::$listtable->current_action() ) ) {
			self::ensure_list_nonce();
		}

		switch ( self::$listtable->current_action() ) {
			case 'delete': // Delete backup archives.
				check_admin_referer( 'bulk-backups' );
				if ( ! current_user_can( 'backwpup_backups_delete' ) ) {
					wp_die( esc_html__( 'Sorry, you don\'t have permissions to do that.', 'backwpup' ) );
				}

				$jobdest_param = filter_input( INPUT_GET, 'jobdest', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
				$jobdest_top   = filter_input( INPUT_GET, 'jobdest-top', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
				$jobdest       = $jobdest_top ? $jobdest_top : $jobdest_param;
				$jobdest       = $jobdest ? sanitize_text_field( $jobdest ) : '';

				$_GET['jobdest-top']        = $jobdest;
				$_GET['jobdets-button-top'] = 'submit';

				if ( '' === $jobdest ) {
					return;
				}

					[$jobid, $dest] = explode( '_', $jobdest );
					/**
					 * Destination handler instance.
					 *
					 * @var BackWPup_Destinations $dest_class
					 */
					$dest_class = BackWPup::get_destination( $dest );
				$files          = $dest_class->file_get_list( $jobdest );

				$request_backup_files = filter_input( INPUT_GET, 'backupfiles', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
				if ( empty( $request_backup_files ) ) {
					break;
				}
				$request_backup_files = array_map( 'sanitize_text_field', wp_unslash( $request_backup_files ) );
				$backup_files         = [];
				foreach ( $request_backup_files as $backupfile ) {
					foreach ( $files as $file ) {
						if ( is_array( $file ) && $file['file'] === $backupfile ) {
							$dest_class->file_delete( $jobdest, $backupfile );
						}
					}
					$backup_files[] = $backupfile;
				}
				$files = $dest_class->file_get_list( $jobdest );
				if ( empty( $files ) ) {
					$_GET['jobdest-top'] = '';
				}

				/**
				 * Fires after deleting backups.
				 *
				 * @param array $backup_files Backup files deleted.
				 * @param string $dest Destination.
				 */
				do_action( 'backwpup_after_delete_backups', $backup_files, $dest );
				break;

			default:
				$jobid = absint( filter_input( INPUT_GET, 'jobid', FILTER_SANITIZE_NUMBER_INT ) );
				if ( $jobid > 0 ) {
					if ( ! current_user_can( 'backwpup_backups_download' ) ) {
						wp_die( esc_html__( 'Sorry, you don\'t have permissions to do that.', 'backwpup' ) );
					}
					check_admin_referer( 'backwpup_action_nonce' );

					$local_file  = filter_input( INPUT_GET, 'local_file', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
					$file        = filter_input( INPUT_GET, 'file', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
					$source_file = $local_file ? $local_file : $file;
					if ( empty( $source_file ) ) {
						break;
					}
					$source_file = sanitize_text_field( $source_file );
					$filename    = untrailingslashit( BackWPup::get_plugin_data( 'temp' ) ) . '/' . basename( $source_file );
					if ( file_exists( $filename ) ) {
						$downloader = new BackWPup_Download_File(
							$filename,
							function ( BackWPup_Download_File_Interface $obj ) use ( $filename ) {
								$obj->clean_ob()
									->headers();

								readfile( $filename ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile

								// Delete the temporary file.
								wp_delete_file( $filename );

								exit();
							},
							'backwpup_backups_download'
						);
						$downloader->download();
					} else {
						// If the file doesn't exist, fallback to old way of downloading.
						// This is for destinations without a downloader class.
						$dest = strtoupper( str_replace( 'download', '', self::$listtable->current_action() ) );
						if ( ! empty( $dest ) && false !== strpos( self::$listtable->current_action(), 'download' ) ) {
							/**
							 * Destination handler instance.
							 *
							 * @var BackWPup_Destinations $dest_class
							 */
							$dest_class = BackWPup::get_destination( $dest );

							try {
								if ( ! empty( $file ) ) {
									$dest_class->file_download( $jobid, trim( sanitize_text_field( $file ) ) );
								}
							} catch ( BackWPup_Destination_Download_Exception $e ) {
								header( 'HTTP/1.0 404 Not Found' );
								wp_die(
									esc_html__( 'Ops! Unfortunately the file doesn\'t exists. May be was deleted?', 'backwpup' ),
									esc_html__( '404 - File Not Found.', 'backwpup' ),
									[
										'back_link' => esc_html__( '&laquo; Go back', 'backwpup' ),
									]
								);
							}
						}
					}
				}
				break;
		}

		// Save per page.
		$screen_options_apply = filter_input( INPUT_POST, 'screen-options-apply', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$screen_options       = filter_input( INPUT_POST, 'wp_screen_options', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		if ( ! empty( $screen_options_apply ) && is_array( $screen_options ) ) {
			$screen_options = wp_unslash( $screen_options );
			$option         = isset( $screen_options['option'] ) ? sanitize_text_field( $screen_options['option'] ) : '';
			$value          = isset( $screen_options['value'] ) ? absint( $screen_options['value'] ) : 0;

			if ( 'backwpupbackups_per_page' === $option ) {
				check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

				if ( $value > 0 && $value < 1000 ) {
					update_user_option(
						$current_user->ID,
						'backwpupbackups_per_page',
						$value
					);
					wp_safe_redirect( remove_query_arg( [ 'pagenum', 'apage', 'paged' ], wp_get_referer() ) );

					exit;
				}
			}
		}

		add_screen_option(
			'per_page',
			[
				'label'   => __( 'Backup Files', 'backwpup' ),
				'default' => 20,
				'option'  => 'backwpupbackups_per_page',
			]
		);

		self::$listtable->prepare_items();
	}

	/**
	 * Output CSS styles.
	 *
	 * @return void
	 */
	public static function admin_print_styles() {
		?>
		<style type="text/css" media="screen">
			.column-size, .column-time {
				width: 10%;
			}

			@media screen and (max-width: 782px) {
				.column-size, .column-runtime, .column-size, .column-folder {
					display: none;
				}

				.column-time {
					width: 18%;
				}
			}
		</style>
		<?php
	}

	/**
	 * Enqueue scripts.
	 *
	 * @return void
	 */
	public static function admin_print_scripts() {
		$suffix              = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$plugin_url          = BackWPup::get_plugin_data( 'url' );
		$plugin_dir          = BackWPup::get_plugin_data( 'plugindir' );
		$plugin_scripts_url  = "{$plugin_url}/assets/js";
		$plugin_scripts_dir  = "{$plugin_dir}/assets/js";
		$shared_scripts_path = "{$plugin_url}/vendor/inpsyde/backwpup-shared/resources/js";
		$shared_scripts_dir  = "{$plugin_dir}/vendor/inpsyde/backwpup-shared/resources/js";

		wp_register_script(
			'backwpup_functions',
			"{$shared_scripts_path}/functions{$suffix}.js",
			[ 'underscore', 'jquery' ],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
		wp_register_script(
			'backwpup_states',
			"{$shared_scripts_path}/states{$suffix}.js",
			[
				'backwpup_functions',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);

		$dependencies = [
			'jquery',
			'underscore',
			'backwpupgeneral',
			'backwpup_functions',
			'backwpup_states',
		];
		if ( \BackWPup::is_pro() ) {
			$dependencies[] = 'decrypter';
		}
		wp_enqueue_script(
			'backwpup-backup-downloader',
			"{$plugin_scripts_url}/backup-downloader{$suffix}.js",
			$dependencies,
			BackWPup::get_plugin_data( 'Version' ),
			true
		);

		if ( \BackWPup::is_pro() ) {
			self::admin_print_pro_scripts( $suffix, $plugin_url, $plugin_dir );
		}
	}

	/**
	 * Display the page content.
	 */
	public static function page() {
		include untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . '/pages/backups.php';
	}

	/**
	 * Enqueue pro scripts.
	 *
	 * @param string $suffix     Script suffix.
	 * @param string $plugin_url Plugin URL.
	 * @param string $plugin_dir Plugin directory.
	 *
	 * @return void
	 */
	private static function admin_print_pro_scripts( $suffix, $plugin_url, $plugin_dir ) {
		$restore_scripts_path = "{$plugin_url}/vendor/inpsyde/backwpup-restore-shared/resources/js";
		$restore_scripts_dir  = "{$plugin_dir}/vendor/inpsyde/backwpup-restore-shared/resources/js";

		wp_register_script(
			'decrypter',
			"{$restore_scripts_path}/decrypter{$suffix}.js",
			[
				'underscore',
				'jquery',
				'backwpup_states',
				'backwpup_functions',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
	}

	/**
	 * Build the delete action for a backup item.
	 *
	 * @param array $item Item data.
	 *
	 * @return string
	 */
	private function delete_item_action( $item ) {
		$query = sprintf(
			'?page=backwpupbackups&action=delete&jobdest-top=%1$s&paged=%2$s&backupfiles[]=%3$s',
			$this->jobid . '_' . $this->dest,
			$this->get_pagenum(),
			esc_attr( $item['file'] )
		);
		$url   = wp_nonce_url( network_admin_url( 'admin.php' ) . $query, 'bulk-backups' );
		$js    = sprintf(
			'if ( confirm(\'%s\') ) { return true; } return false;',
			esc_js(
				__(
					'You are about to delete this backup archive. \'Cancel\' to stop, \'OK\' to delete.',
					'backwpup'
				)
			)
		);

		return sprintf(
			'<a class="submitdelete" href="%1$s" onclick="%2$s">%3$s</a>',
			$url,
			$js,
			__( 'Delete', 'backwpup' )
		);
	}

	/**
	 * Build the download action for a backup item.
	 *
	 * @param array $item Item data.
	 *
	 * @return string
	 */
	private function download_item_action( $item ) {
		$local_file = untrailingslashit( BackWPup::get_plugin_data( 'TEMP' ) ) . "/{$item['filename']}";

		return sprintf(
			'<a href="#TB_inline?height=300&width=630&inlineId=tb_download_file" 
				class="backup-download-link thickbox" 
				id="backup-download-link"
				data-jobid="%1$s" 
				data-destination="%2$s" 
				data-file="%3$s" 
				data-local-file="%4$s" 
				data-nonce="%5$s" 
				data-url="%6$s">%7$s</a>',
			intval( $this->jobid ),
			esc_attr( $this->dest ),
			esc_attr( $item['file'] ),
			esc_attr( $local_file ),
			wp_create_nonce( 'backwpup_action_nonce' ),
			wp_nonce_url( $item['downloadurl'], 'backwpup_action_nonce' ),
			__( 'Download', 'backwpup' )
		);
	}

	/**
	 * Ensure the list view has a valid nonce in the URL.
	 *
	 * @return void
	 */
	private static function ensure_list_nonce() {
		$nonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( null === $nonce && isset( $_GET['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
		} else {
			$nonce = $nonce ? sanitize_text_field( $nonce ) : '';
		}

		if ( $nonce && wp_verify_nonce( $nonce, self::LIST_NONCE_ACTION ) ) {
			return;
		}

		$base_url    = is_network_admin() ? network_admin_url( 'admin.php?page=backwpupbackups' ) : admin_url( 'admin.php?page=backwpupbackups' );
		$current_url = add_query_arg( $_GET, $base_url );
		$current_url = remove_query_arg( [ '_wpnonce', '_wp_http_referer' ], $current_url );
		$current_url = add_query_arg( '_wpnonce', wp_create_nonce( self::LIST_NONCE_ACTION ), $current_url );

		wp_safe_redirect( $current_url );
		exit;
	}

	/**
	 * Check if the list nonce is valid.
	 *
	 * @return bool
	 */
	private function get_list_nonce() {
		$nonce = filter_input( INPUT_GET, '_wpnonce', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		if ( null === $nonce && isset( $_GET['_wpnonce'] ) ) {
			$nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
		} else {
			$nonce = $nonce ? sanitize_text_field( $nonce ) : '';
		}
		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::LIST_NONCE_ACTION ) ) {
			return '';
		}

		return (string) $nonce;
	}
}
