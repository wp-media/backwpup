<?php

use function Inpsyde\BackWPup\Infrastructure\Restore\restore_container;
use Inpsyde\Restore\ViewLoader;

final class BackWPup_Page_Backups extends WP_List_Table
{
    private static $listtable;

    private $destinations = [];

    private $jobid = 1;

    private $dest = 'FOLDER';

    public function __construct()
    {
        parent::__construct([
            'plural' => 'backups',
            'singular' => 'backup',
            'ajax' => true,
        ]);

        $this->destinations = BackWPup::get_registered_destinations();
    }

    public function ajax_user_can()
    {
        return current_user_can('backwpup_backups');
    }

    public function prepare_items()
    {
        $per_page = $this->get_items_per_page('backwpupbackups_per_page');
        if (empty($per_page) || $per_page < 1) {
            $per_page = 20;
        }

        $jobdest = '';
        if (isset($_GET['jobdets-button-top'])) {
            $jobdest = sanitize_text_field($_GET['jobdest-top']);
        }
        if (isset($_GET['jobdets-button-bottom'])) {
            $jobdest = sanitize_text_field($_GET['jobdest-bottom']);
        }

        if (empty($jobdest)) {
            $jobdests = $this->get_destinations_list();
            if (empty($jobdests)) {
                $jobdests = ['_'];
            }
            $jobdest = $jobdests[0];
            $_GET['jobdest-top'] = $jobdests[0];
            $_GET['jobdets-button-top'] = 'empty';
        }

        [$this->jobid, $this->dest] = explode('_', (string) $jobdest);

        if (!empty($this->destinations[$this->dest]['class'])) {
            /** @var BackWPup_Destinations $dest_object */
            $dest_object = BackWPup::get_destination($this->dest);
            $this->items = $dest_object->file_get_list($jobdest);
        }

        if (!$this->items) {
            $this->items = '';

            return;
        }

        // Sorting.
        $order = filter_input(INPUT_GET, 'order') ?: 'desc';
        $orderby = filter_input(INPUT_GET, 'orderby') ?: 'time';
        $tmp = [];

        if ($orderby === 'time') {
            if ($order === 'asc') {
                foreach ($this->items as &$ma) {
                    $tmp[] = &$ma['time'];
                }
                array_multisort($tmp, SORT_ASC, $this->items);
            } else {
                foreach ($this->items as &$ma) {
                    $tmp[] = &$ma['time'];
                }
                array_multisort($tmp, SORT_DESC, $this->items);
            }
        } elseif ($orderby === 'file') {
            if ($order === 'asc') {
                foreach ($this->items as &$ma) {
                    $tmp[] = &$ma['filename'];
                }
                array_multisort($tmp, SORT_ASC, $this->items);
            } else {
                foreach ($this->items as &$ma) {
                    $tmp[] = &$ma['filename'];
                }
                array_multisort($tmp, SORT_DESC, $this->items);
            }
        } elseif ($orderby === 'folder') {
            if ($order === 'asc') {
                foreach ($this->items as &$ma) {
                    $tmp[] = &$ma['folder'];
                }
                array_multisort($tmp, SORT_ASC, $this->items);
            } else {
                foreach ($this->items as &$ma) {
                    $tmp[] = &$ma['folder'];
                }
                array_multisort($tmp, SORT_DESC, $this->items);
            }
        } elseif ($orderby === 'size') {
            if ($order === 'asc') {
                foreach ($this->items as &$ma) {
                    $tmp[] = &$ma['filesize'];
                }
                array_multisort($tmp, SORT_ASC, $this->items);
            } else {
                foreach ($this->items as &$ma) {
                    $tmp[] = &$ma['filesize'];
                }
                array_multisort($tmp, SORT_DESC, $this->items);
            }
        }

        $this->set_pagination_args([
            'total_items' => count($this->items),
            'per_page' => $per_page,
            'jobdest' => $jobdest,
            'orderby' => $orderby,
            'order' => $order,
        ]);

        // Only display items on page.
        $start = intval(($this->get_pagenum() - 1) * $per_page);
        $end = $start + $per_page;
        if ($end > count($this->items)) {
            $end = count($this->items);
        }

        $i = -1;
        $paged_items = [];

        foreach ($this->items as $item) {
            ++$i;
            if ($i < $start) {
                continue;
            }
            if ($i >= $end) {
                break;
            }
            $paged_items[] = $item;
        }

        $this->items = $paged_items;
    }

    public function no_items()
    {
        _e('No files could be found. (List will be generated during next backup.)', 'backwpup');
    }

    public function get_bulk_actions()
    {
        if (!$this->has_items()) {
            return [];
        }

        $actions = [];
        $actions['delete'] = __('Delete', 'backwpup');

        return $actions;
    }

    public function extra_tablenav($which)
    {
        $destinations_list = $this->get_destinations_list();

        if (count($destinations_list) < 1) {
            return;
        }

        if (count($destinations_list) === 1) {
            echo '<input type="hidden" name="jobdest-' . $which . '" value="' . $destinations_list[0] . '">';

            return;
        } ?>
		<div class="alignleft actions">
			<label for="jobdest-<?php echo esc_attr($which); ?>">
				<select name="jobdest-<?php echo esc_html($which); ?>" class="postform"
				        id="jobdest-<?php echo esc_attr($which); ?>">
					<?php
                    foreach ($destinations_list as $jobdest) {
                        [$jobid, $dest] = explode('_', (string) $jobdest);
                        echo "\t<option value=\"" . $jobdest . '" ' . selected(
                            $this->jobid . '_' . $this->dest,
                            $jobdest,
                            false
                        ) . '>' . $dest . ': ' . esc_html(BackWPup_Option::get(
                            $jobid,
                            'name'
                        )) . '</option>' . PHP_EOL;
                    } ?>
				</select>
			</label>
			<?php submit_button(
                        __('Change destination', 'backwpup'),
                        'secondary',
                        'jobdets-button-' . $which,
                        false,
                        ['id' => 'query-submit-' . $which]
                    ); ?>
		</div>
		<?php
    }

    public function get_columns()
    {
        $posts_columns = [];
        $posts_columns['cb'] = '<input type="checkbox" />';
        $posts_columns['time'] = __('Time', 'backwpup');
        $posts_columns['file'] = __('File', 'backwpup');
        $posts_columns['folder'] = __('Folder', 'backwpup');
        $posts_columns['size'] = __('Size', 'backwpup');

        return $posts_columns;
    }

    public function get_sortable_columns()
    {
        return [
            'file' => ['file', false],
            'folder' => 'folder',
            'size' => 'size',
            'time' => ['time', false],
        ];
    }

    public function column_cb($item)
    {
        return '<input type="checkbox" name="backupfiles[]" value="' . esc_attr($item['file']) . '" />';
    }

    public function get_destinations_list()
    {
        $jobdest = [];
        $jobids = BackWPup_Option::get_job_ids();

        foreach ($jobids as $jobid) {
            if (BackWPup_Option::get($jobid, 'backuptype') === 'sync') {
                continue;
            }
            $dests = BackWPup_Option::get($jobid, 'destinations');

            foreach ($dests as $dest) {
                if (!$this->destinations[$dest]['class']) {
                    continue;
                }
                $dest_class = BackWPup::get_destination($dest);
                $can_do_dest = $dest_class->file_get_list($jobid . '_' . $dest);
                if (!empty($can_do_dest)) {
                    $jobdest[] = $jobid . '_' . $dest;
                }
            }
        }

        return $jobdest;
    }

    public function column_file($item)
    {
        $actions = [];

        $r = '<strong>' . esc_attr($item['filename']) . '</strong><br />';
        if (!empty($item['info'])) {
            $r .= esc_attr($item['info']) . '<br />';
        }

        if (current_user_can('backwpup_backups_delete')) {
            $actions['delete'] = $this->delete_item_action($item);
        }

        if (!empty($item['downloadurl']) && current_user_can('backwpup_backups_download')) {
            try {
                $actions['download'] = $this->download_item_action($item);

                if ($this->dest === 'HIDRIVE') {
                    $downloadUrl = wp_nonce_url($item['downloadurl'], 'backwpup_action_nonce');

                    if ($item['filesize'] > 10485760) { // 10 MB
                        $request = new BackWPup_Pro_Destination_HiDrive_Request();
                        $authorization = new BackWPup_Pro_Destination_HiDrive_Authorization($request);
                        $api = new BackWPup_Pro_Destination_HiDrive_Api($request, $authorization);
                        $response = $api->temporalDownloadUrl($this->jobid, $item['file']);
                        $responsBody = json_decode((string) $response['body']);

                        if (isset($responsBody->url)) {
                            $downloadUrl = $responsBody->url;
                        }
                    }

                    $actions['download'] = '<a href="' . $downloadUrl . '" class="backup-download-link">Download</a>';
                }
            } catch (BackWPup_Factory_Exception $e) {
                $actions['download'] = sprintf(
                    '<a href="%1$s">%2$s</a>',
                    wp_nonce_url($item['downloadurl'], 'backwpup_action_nonce'),
                    __('Download', 'backwpup')
                );
            }
        }

        // Add restore url to link list
        if (current_user_can('backwpup_restore') && !empty($item['restoreurl'])) {
            $item['restoreurl'] = add_query_arg(
                [
                    'step' => 1,
                    'trigger_download' => 1,
                ],
                $item['restoreurl']
            );
            $actions['restore'] = sprintf(
                '<a href="%1$s">%2$s</a>',
                wp_nonce_url($item['restoreurl'], 'restore-backup_' . $this->jobid),
                __('Restore', 'backwpup')
            );
        }

        $r .= $this->row_actions($actions);

        return $r;
    }

    public function column_folder($item)
    {
        return esc_attr($item['folder']);
    }

    public function column_size($item)
    {
        if (!empty($item['filesize']) && $item['filesize'] != -1) {
            return size_format($item['filesize'], 2);
        }

        return __('?', 'backwpup');
    }

    public function column_time($item)
    {
		return sprintf(
			// translators: %1$s = date, %2$s = time.
			__( '%1$s at %2$s', 'backwpup' ),
			wp_date( get_option( 'date_format' ), $item['time'] ),
			wp_date( get_option( 'time_format' ), $item['time'] )
		);
    }

    public static function load()
    {
        global $current_user;

        //Create Table
        self::$listtable = new BackWPup_Page_Backups();

        switch (self::$listtable->current_action()) {
            case 'delete': //Delete Backup archives
                check_admin_referer('bulk-backups');
                if (!current_user_can('backwpup_backups_delete')) {
                    wp_die(__('Sorry, you don\'t have permissions to do that.', 'backwpup'));
                }

                $jobdest = '';
                if (isset($_GET['jobdest'])) {
                    $jobdest = sanitize_text_field($_GET['jobdest']);
                }
                if (isset($_GET['jobdest-top'])) {
                    $jobdest = sanitize_text_field($_GET['jobdest-top']);
                }

                $_GET['jobdest-top'] = $jobdest;
                $_GET['jobdets-button-top'] = 'submit';

                if ($jobdest === '') {
                    return;
                }

                [$jobid, $dest] = explode('_', $jobdest);
                /** @var BackWPup_Destinations $dest_class */
                $dest_class = BackWPup::get_destination($dest);
                $files = $dest_class->file_get_list($jobdest);

				$request_backup_files = ! empty( $_GET['backupfiles'] ) ? wp_unslash( $_GET['backupfiles'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( empty( $request_backup_files ) ) {
					break;
				}
				$backup_files = [];
				foreach ( $request_backup_files as $backupfile ) {
					foreach ( $files as $file ) {
						if ( is_array( $file ) && $file['file'] === $backupfile ) {
							$dest_class->file_delete( $jobdest, $backupfile );
						}
					}
					$backup_files[] = sanitize_text_field( $backupfile );
				}
                $files = $dest_class->file_get_list($jobdest);
                if (empty($files)) {
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
                if (isset($_GET['jobid'])) {
                    $jobid = absint($_GET['jobid']);
                    if (!current_user_can('backwpup_backups_download')) {
                        wp_die(__('Sorry, you don\'t have permissions to do that.', 'backwpup'));
                    }
                    check_admin_referer('backwpup_action_nonce');

                    $filename = untrailingslashit(BackWPup::get_plugin_data('temp')) . '/' . basename($_GET['local_file'] ?? $_GET['file']);
                    if (file_exists($filename)) {
                        $downloader = new BackWPup_Download_File(
                            $filename,
                            function (BackWPup_Download_File_Interface $obj) use ($filename) {
                                $obj->clean_ob()
                                    ->headers()
                                ;

                                readfile($filename);

                                // Delete the temporary file.
                                unlink($filename);

                                exit();
                            },
                            'backwpup_backups_download'
                        );
                        $downloader->download();
                    } else {
                        // If the file doesn't exist, fallback to old way of downloading
                        // This is for destinations without a downloader class
                        $dest = strtoupper(str_replace('download', '', self::$listtable->current_action()));
                        if (!empty($dest) && strstr(self::$listtable->current_action(), 'download')) {
                            /** @var BackWPup_Destinations $dest_class */
                            $dest_class = BackWPup::get_destination($dest);

                            try {
                                $dest_class->file_download($jobid, trim(sanitize_text_field($_GET['file'])));
                            } catch (BackWPup_Destination_Download_Exception $e) {
                                header('HTTP/1.0 404 Not Found');
                                wp_die(
                                    esc_html__('Ops! Unfortunately the file doesn\'t exists. May be was deleted?'),
                                    esc_html__('404 - File Not Found.'),
                                    [
                                        'back_link' => esc_html__('&laquo; Go back', 'backwpup'),
                                    ]
                                );
                            }
                        }
                    }
                }
                break;
        }

        //Save per page
        if (isset($_POST['screen-options-apply'], $_POST['wp_screen_options']['option'], $_POST['wp_screen_options']['value']) && $_POST['wp_screen_options']['option'] == 'backwpupbackups_per_page') {
            check_admin_referer('screen-options-nonce', 'screenoptionnonce');

            if ($_POST['wp_screen_options']['value'] > 0 && $_POST['wp_screen_options']['value'] < 1000) {
                update_user_option(
                    $current_user->ID,
                    'backwpupbackups_per_page',
                    (int) $_POST['wp_screen_options']['value']
                );
                wp_redirect(remove_query_arg(['pagenum', 'apage', 'paged'], wp_get_referer()));

                exit;
            }
        }

        add_screen_option(
            'per_page',
            [
                'label' => __('Backup Files', 'backwpup'),
                'default' => 20,
                'option' => 'backwpupbackups_per_page',
            ]
        );

        self::$listtable->prepare_items();
    }

    public static function admin_print_styles()
    {
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

    public static function admin_print_scripts()
    {
        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';
        $plugin_url = BackWPup::get_plugin_data('url');
        $plugin_dir = BackWPup::get_plugin_data('plugindir');
        $plugin_scripts_url = "{$plugin_url}/assets/js";
        $plugin_scripts_dir = "{$plugin_dir}/assets/js";
        $shared_scripts_path = "{$plugin_url}/vendor/inpsyde/backwpup-shared/resources/js";
        $shared_scripts_dir = "{$plugin_dir}/vendor/inpsyde/backwpup-shared/resources/js";

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
        if (\BackWPup::is_pro()) {
            $dependencies[] = 'decrypter';
        }
        wp_enqueue_script(
            'backwpup-backup-downloader',
            "{$plugin_scripts_url}/backup-downloader{$suffix}.js",
			$dependencies,
			BackWPup::get_plugin_data( 'Version' ),
			true
        );

        if (\BackWPup::is_pro()) {
            self::admin_print_pro_scripts($suffix, $plugin_url, $plugin_dir);
        }
    }

	/**
	 * Display the page content.
	 */
	public static function page() {
		include untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . '/pages/backups.php';
	}

    private static function admin_print_pro_scripts($suffix, $plugin_url, $plugin_dir)
    {
        $restore_scripts_path = "{$plugin_url}/vendor/inpsyde/backwpup-restore-shared/resources/js";
        $restore_scripts_dir = "{$plugin_dir}/vendor/inpsyde/backwpup-restore-shared/resources/js";

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

    private function delete_item_action($item)
    {
        $query = sprintf(
            '?page=backwpupbackups&action=delete&jobdest-top=%1$s&paged=%2$s&backupfiles[]=%3$s',
            $this->jobid . '_' . $this->dest,
            $this->get_pagenum(),
            esc_attr($item['file'])
        );
        $url = wp_nonce_url(network_admin_url('admin.php') . $query, 'bulk-backups');
        $js = sprintf(
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
            __('Delete', 'backwpup')
        );
    }

    private function download_item_action($item)
    {
        $local_file = untrailingslashit(BackWPup::get_plugin_data('TEMP')) . "/{$item['filename']}";

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
            intval($this->jobid),
            esc_attr($this->dest),
            esc_attr($item['file']),
            esc_attr($local_file),
            wp_create_nonce('backwpup_action_nonce'),
            wp_nonce_url($item['downloadurl'], 'backwpup_action_nonce'),
            __('Download', 'backwpup')
        );
    }
}
