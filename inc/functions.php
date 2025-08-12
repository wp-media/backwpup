<?php
/**
 * Template Function.
 *
 * @since 3.5.0
 *
 * @param object $bind      the object to use within the view
 * @param string $file_path the path of the file to include
 */
function backwpup_template($bind, $file_path)
{
    $file_path = \BackWPup_Sanitize_Path::sanitize_path_regexp($file_path);

    if (!$file_path) {
        throw new \InvalidArgumentException(
            sprintf(
                'Invalid or malformed file path %s: for template function.',
                $file_path
            )
        );
    }

    // Build the path.
    $path = untrailingslashit(BackWPup::get_plugin_data('plugindir')) . '/views/' . ltrim($file_path, '\\/');

    if (!file_exists($path)) {
        throw new \InvalidArgumentException(
            sprintf(
                'Cannot locate the template %s in template function.',
                $path
            )
        );
    }

    include $path;
}

/**
 * Convert String To Boolean.
 *
 * @since 3.5.0
 *
 * @param string|int $value The string to convert to boolean. 'yes', 1, 'true', '1' are converted to true.
 *
 * @return bool true or false depending on the passed value
 */
function backwpup_string_to_bool($value)
{
    return is_bool($value)
        ? $value
        : ('yes' === $value || 1 === $value || 'true' === $value || '1' === $value || 'on' === $value);
}

/**
 * Convert Boolean to String.
 *
 * @since 3.5.0
 *
 * @param bool $bool the bool value to convert
 *
 * @return string The converted value. 'yes' or 'no'.
 */
function backwpup_bool_to_string($bool)
{
    if (!is_bool($bool)) {
        $bool = backwpup_string_to_bool($bool);
    }

    return true === $bool ? 'yes' : 'no';
}

/**
 * Is JSON.
 *
 * Check if a string is a valid json or not.
 *
 * @see  https://codepad.co/snippet/jHa0m4DB
 * @since 3.5.0
 *
 * @param string $data the json string
 *
 * @return bool True if the string is a json, false otherwise
 */
function backwpup_is_json($data)
{
    if (!is_string($data) || !trim($data)) {
        return false;
    }

    return (
            // Maybe an empty string, array or object.
            $data === '""'
            || $data === '[]'
            || $data === '{}'
            || $data[0] === '"' // Maybe an encoded JSON string.
            || $data[0] === '[' // Maybe a flat array.
            || $data[0] === '{' // Maybe an associative array.
        )
        && json_decode($data) !== null;
}

/**
 * Clean JSON from request.
 *
 * @since 3.5.0
 *
 * @param string $json the json
 *
 * @return string The cleaned json string
 */
function backwpup_clean_json_from_request($json)
{
    // Remove slashes added by WordPress.
    $slashed_json = wp_unslash($json);

    if (backwpup_is_json($slashed_json)) {
        // phpcs:ignore
        $json = filter_var($slashed_json);
        $json = html_entity_decode($json);
    }

    return $json;
}

/**
 * Retrieve the instance of the Filesystem used by WordPress.
 *
 * @since 3.5.0
 *
 * @return WP_Filesystem_Base an instance of WP_Filesystem_* depending on the method set
 */
function backwpup_wpfilesystem()
{
    global $wp_filesystem;

    if (!$wp_filesystem) {
        // Make sure the WP_Filesystem function exists.
        if (!function_exists('WP_Filesystem')) {
            require_once untrailingslashit(ABSPATH) . '/wp-admin/includes/file.php';
        }

        WP_Filesystem();
    }

    return $wp_filesystem;
}

/**
 * Get WPDB Instance.
 *
 * With this function you get the global $wpdb instance of the class
 *
 * @since 3.5.0
 *
 * @return wpdb The instance of the class
 */
function backwpup_wpdb()
{
    global $wpdb;

    return $wpdb;
}

/**
 * Remove Invalid Characters from Directory Name.
 *
 * @since 3.5.0
 *
 * @param string $directory the directory path
 *
 * @return string The cleaned directory path
 */
function remove_invalid_characters_from_directory_name($directory)
{
    return str_replace(
        [
            '?',
            '[',
            ']',
            '\\',
            '=',
            '<',
            '>',
            ':',
            ';',
            ',',
            "'",
            '"',
            '&',
            '$',
            '#',
            '*',
            '(',
            ')',
            '|',
            '~',
            '`',
            '!',
            '{',
            '}',
            chr(0),
        ],
        '',
        $directory
    );
}

/**
 * Escapes a URL, adding https by default if protocol is not specified.
 *
 * @param string     $url       The URL to escape
 * @param array|null $protocols A list of allowed protocols
 *
 * @return string The escaped URL
 */
function backwpup_esc_url_default_secure($url, $protocols = null)
{
    // Add https to protocols if not present
    if (is_array($protocols) && !in_array('https', $protocols)) {
        $protocols[] = 'https';
    }

    $escaped_url = esc_url_raw($url, $protocols);
    if (empty($escaped_url)) {
        return $escaped_url;
    }

    // We must check for both http: and http;
    // because esc_url_raw() corrects http; to http: automatically.
    // so if we do not check for it in the original, we could have invalid results.
    if (!preg_match('/http[:;]/', $url) && strpos($escaped_url, 'http://') === 0) {
        $escaped_url = preg_replace('/^http:/', 'https:', $escaped_url);
    }

    return $escaped_url;
}

/**
 * Compatibility code for cal_days_in_month.
 *
 * @param int $month Month.
 * @param int $year Year.
 *
 * @return int
 */
function backwpup_cal_days_in_month( $month, $year ) {
	$month = (int) $month;
	$year  = (int) $year;

	// Array of the number of days in each month for a non-leap year.
	$days_in_months = [ 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31 ];

	// Check if the month is February and if the year is a leap year.
	if ( 2 === $month && ( ( 0 === ( $year % 4 ) && 0 !== ( $year % 100 ) ) || ( 0 === $year % 400 ) ) ) {
		return 29; // February in a leap year has 29 days.
	}

	// Return the number of days for the given month.
	return $days_in_months[ $month - 1 ];
}

/**
 * Outputs notice HTML
 *
 * @param array $args An array of arguments used to determine the notice output.
 * @return void
 */
function backwpup_notice_html( $args ) {
	$defaults = [
		'status'                 => 'success',
		'dismissible'            => 'is-dismissible',
		'message'                => '',
		'action'                 => '',
		'dismiss_button'         => false,
		'dismiss_button_message' => __( 'Dismiss this notice', 'backwpup' ),
		'readonly_content'       => '',
		'id'                     => '',
	];

	$args = wp_parse_args( $args, $defaults );
	switch ( $args['action'] ) {
		case 'dropbox':
		case 'legacy_jobs':
			$args['action'] = ''; // This can be a specific action to be carried out.
			break;
	}

	$notice_id = '';

	if ( ! empty( $args['id'] ) ) {
		$notice_id = ' id="' . esc_attr( $args['id'] ) . '"';
	}

	?>
	<div <?php echo $notice_id; ?> class="notice notice-inpsyde notice-<?php echo esc_attr( $args['status'] ); ?> <?php echo esc_attr( $args['dismissible'] ); ?>"<?php echo $notice_id; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="notice-inpsyde__content">
			<p class="notice-titre">
				<?php echo $args['title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</p>
			<?php if ( $args['action'] || $args['dismiss_button'] ) : ?>
			<a class="closeIt <?php echo esc_attr( $args['dismiss_button_class'] ?? '' ); ?>"
				data-bwpu-hide="<?php echo esc_attr( $args['id'] ?? '' ); ?>"
				href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=backwpup_dismiss_notice&box=' . $args['dismiss_button'] ), 'backwpup_dismiss_notice_' . $args['dismiss_button'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">
				<span class="screen-reader-text">
					<?php echo esc_html( $args['dismiss_button_message'] ); ?>
				</span>
			</a>
			<?php endif; ?>

			<?php
			$tag = 0 !== strpos( $args['message'], '<p' ) && 0 !== strpos( $args['message'], '<ul' );

			echo ( $tag ? '<p>' : '' ) . $args['message'] . ( $tag ? '</p>' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic content is properly escaped in the view.
			?>
		</div>
	</div>
	<?php
}