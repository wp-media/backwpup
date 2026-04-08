<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Date formatting logic
$date = new DateTime();
$date->setTimestamp( $backup['time'] );

$formatted_date = $date->format('M j, Y');
$formatted_time = $date->format('g:ia');
$type_icon      = 'clock';
$backup_trigger = $backup['backup_trigger'] ?? '';
$type           = $backup['type'];
$status         = $backup['status'] ?? 'completed';
$is_failed      = 'failed' === $status;
$failure_reason = '';
$failure_message = '';
$backup_id      = isset( $backup['backup_id'] ) ? (int) $backup['backup_id'] : 0;
$job_id         = isset( $backup['id'] ) ? (int) $backup['id'] : 0;
$failed_modal_style = 'width:90vw; max-width:860px; max-height:90vh;';

if ( $is_failed ) {
	$failure_reason = isset( $backup['error_message'] ) ? trim( (string) $backup['error_message'] ) : '';
	$known_reasons  = [
		__( 'not enough storage', 'backwpup' ),
		__( 'incorrect login', 'backwpup' ),
	];
	if ( '' !== $failure_reason && in_array( $failure_reason, $known_reasons, true ) ) {
		/* translators: %s: failure reason. */
		$failure_message = sprintf( __( 'Backup failed – %s', 'backwpup' ), $failure_reason );
	} else {
		$failure_message = __( 'Backup failed', 'backwpup' );
	}
}

	if ( 'link' === $backup_trigger ) {
		$type_icon = 'link';
		$type = __( 'Link', 'backwpup' );
	} elseif( '' === $backup['type'] ) {
		$type_icon = 'user-settings';
		$type = __( 'Manual', 'backwpup' );
	}
$actions = [];

if ( $is_failed && $backup_id > 0 ) {
	$actions[] = [
		"name" => __( "View log", 'backwpup' ),
		"icon" => "info",
		"trigger" => "load-and-open-modal",
		"display" => "failed-backup-log",
		"dataset" => [
			"data-block-name" => "modal/failed-backup-log",
			"data-block-type" => "children",
			"data-backup-id" => $backup_id,
			"data-job-id" => $job_id,
			"data-modal-style" => $failed_modal_style,
		],
	];
	$actions[] = [
		"name" => __( "Delete", 'backwpup' ),
		"icon" => "trash",
		"trigger" => "load-and-open-modal",
		"display" => "delete-failed-backup",
		"dataset" => [
			"data-block-name" => "modal/delete-failed-backup",
			"data-block-type" => "children",
			"data-backup-id" => $backup_id,
		],
	];
} else {
	//Add the download and restore action
	//If we can't restore the backup, we can't download it either.
	if (isset($backup['dataset-download'])) {
		$actions[] = ["name" => __("Download", 'backwpup'), "icon" => "download", "trigger" => $backup["download-trigger"], "dataset" => $backup['dataset-download']];
	}

	if (isset($backup['dataset-restore'])) {
		$actions[] = ["name" => $backup['dataset-restore']['label'], "icon" => "restore", "trigger" => "open-modal", "display" => "restore-backup","dataset" => $backup['dataset-restore']];
	}

	// Add the delete action
	if (isset($backup['dataset-delete'])) {
	  $actions[] = [
	    "name" => __("Delete", 'backwpup'),
	    "icon" => "trash", 
	    "trigger" => "open-modal", 
	    "display" => "delete-backup", 
	    "dataset" => $backup['dataset-delete']
	  ];
	}
}

$delete_dataset = null;
if ( $is_failed && $backup_id > 0 ) {
	$delete_dataset = [ 'backup_id' => $backup_id ];
} elseif ( isset( $backup['dataset-delete'] ) ) {
	$delete_dataset = $backup['dataset-delete'];
}
$row_attrs = $backup_id > 0 ? ' data-backup-id="' . esc_attr( $backup_id ) . '"' : '';

// Start output buffering
ob_start();
?>

<tr class="*:py-6 *:border-b *:border-grey-300 max-md:bg-grey-100 max-md:rounded-lg max-md:block max-md:p-4"<?php echo $row_attrs; ?>>
  <td class="p-0 max-md:hidden">
    <?php
      BackWPupHelpers::component("form/checkbox", [
        "name" => "select_backup",
        "style" => "light",
        "trigger" => "select-backup",
        "data" => [
          "delete" => json_encode( $delete_dataset ),
        ]
      ]);
    ?>
  </td>

  <td class="px-8 max-md:py-4 max-md:px-6 max-md:flex max-md:items-baseline max-md:gap-1 max-md:bg-white max-md:rounded max-md:border-none">
    <p class="text-sm font-bold"><?php echo esc_html( $formatted_date ); ?></p>
    <p class="text-base">at <?php echo esc_html( $formatted_time ); ?></p>
  </td>

  <td class="px-8 max-md:block max-md:px-2 max-md:py-3">
    <div class="flex items-center md:justify-center max-md:justify-between">
      <p class="text-base font-semibold md:hidden"><?php esc_html_e("Type", "backwpup"); ?></p>
      <?php
        BackWPupHelpers::component("tooltip", [
          "content" => $type,
          "icon_name" => $type_icon,
          "icon_size" => "large",
          "position" => "center",
        ]);
      ?>
    </div>
  </td>

  <?php if ( $is_failed ) : ?>
    <td class="px-8 max-md:px-2 max-md:py-3 max-md:flex max-md:flex-col max-md:gap-2" colspan="2">
      <p class="text-base font-semibold md:hidden"><?php esc_html_e("Stored on", "backwpup"); ?></p>
      <div class="flex items-center gap-2">
        <span class="backwpup-failure-icon">
          <?php
            BackWPupHelpers::component("icon", [
              "name" => "danger",
              "size" => "large",
              "position" => "top",
            ]);
          ?>
        </span>
        <span class="text-base text-danger-strong"><?php echo esc_html( $failure_message ); ?></span>
      </div>
    </td>
  <?php else : ?>
    <td class="px-8 max-md:px-2 max-md:py-3 max-md:flex max-md:justify-between max-md:items-center">
      <p class="text-base font-semibold md:hidden"><?php esc_html_e("Stored on", "backwpup"); ?></p>
      <?php
        BackWPupHelpers::component("storage-list-compact", [
          "storages" => (array)$backup['stored_on'],
          "style" => "alt"
        ]);
      ?>
    </td>

    <td class="px-8 max-md:px-2 max-md:py-3 max-md:flex max-md:justify-between max-md:items-center">
      <p class="text-base font-semibold md:hidden"><?php esc_html_e("Data", "backwpup"); ?></p>
      <div class="flex gap-2">
      <?php
        foreach ( (array) $backup['data'] as $data) {
          switch ($data) {
            case 'FILE':
              $icon = 'file-alt';
              $label = __( 'Files', 'backwpup' );
              break;
            case 'DBDUMP':
              $icon = 'database';
              $label = __( 'Database', 'backwpup' );
              break;
            case 'WPPLUGIN':
              $icon = 'file';
              $label = __( 'Plugins', 'backwpup' );
              break;
            default:
              $icon = 'dots';
              $label = $data;
              break;
          }
          BackWPupHelpers::component("tooltip", [
            "content" => $label,
            "icon_name" => $icon,
            "icon_size" => "large",
            "position" => "center",
          ]);
        }
      ?>
      </div>
    </td>
  <?php endif; ?>

  <td class="px-8 max-md:block max-md:p-0 max-md:border-none">
    <?php
      BackWPupHelpers::component("navigation/menu", [
        "class" => "max-md:hidden",
        "actions" => $actions,
      ]);
    ?>
    <ul class="md:hidden flex flex-col">
      <?php if ( $is_failed && $backup_id > 0 ) : ?>
        <li class="py-4 flex justify-end border-b border-grey-400">
          <?php
            BackWPupHelpers::component("form/button", [
              "type" => "link",
              "label" => __( "View log", "backwpup" ),
              "icon_name" => "info",
              "icon_position" => "after",
              "trigger" => "load-and-open-modal",
              "display" => "failed-backup-log",
              "data" => [
                "block-name" => "modal/failed-backup-log",
                "block-type" => "children",
                "backup-id" => $backup_id,
                "job-id" => $job_id,
                "modal-style" => $failed_modal_style,
              ],
            ]);
          ?>
        </li>
        <li class="py-4 flex justify-end">
          <?php
            BackWPupHelpers::component("form/button", [
              "type" => "link",
              "label" => __( "Delete", "backwpup" ),
              "icon_name" => "trash",
              "icon_position" => "after",
              "trigger" => "load-and-open-modal",
              "display" => "delete-failed-backup",
              "data" => [
                "block-name" => "modal/delete-failed-backup",
                "block-type" => "children",
                "backup-id" => $backup_id,
              ],
            ]);
          ?>
        </li>
      <?php else : ?>
        <li class="py-4 flex justify-end border-b border-grey-400">
          <?php
            BackWPupHelpers::component("form/button", [
              "type" => "link",
              "label" => __("Download", "backwpup"),
              "icon_name" => "download",
              "icon_position" => "after",
              "trigger" => "download-backup",
            ]);
          ?>
        </li>
        <li class="py-4 flex justify-end">
          <?php
            BackWPupHelpers::component("form/button", [
              "type" => "link",
              "label" => __("Restore", "backwpup"),
              "icon_name" => "restore",
              "icon_position" => "after",
              "trigger" => "open-modal",
              "display" => "restore-backup"
            ]);
          ?>
        </li>
      <?php endif; ?>
    </ul>
  </td>
</tr>

<?php
// End output buffering and capture the output
$tableRowHtml = ob_get_clean();

// Return the HTML to use or echo it when needed
echo $tableRowHtml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
