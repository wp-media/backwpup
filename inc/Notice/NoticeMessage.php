<?php

namespace Inpsyde\BackWPup\Notice;

/**
 * Class NoticeMessage
 *
 * Handles the creation and management of notice messages within the BackWPup Pro plugin.
 * This class is responsible for displaying, storing, and formatting admin notices
 * to inform users about important events or actions required.
 * This class is marked as final and cannot be extended.
 *
 * @package BackWPupPro\Notice
 *
 * @property string $id
 * @property string|null $button_label
 * @property string|null $button_url
 * @property string|null $dismiss_action_url
 * @property string|null $type
 * @property string                  $template
 * @property array<int, string>|null $jobs
 */
final class NoticeMessage {

	/**
	 * Array of message data.
	 *
	 * @var array<string, string|array<int, string>|null>
	 */
	private $data = [];

	/**
	 * Creates a notice message.
	 *
	 * @param string      $template     Template name without extension.
	 * @param string|null $button_label Optional button label.
	 * @param string|null $button_url   Optional button URL.
	 */
	public function __construct( string $template, ?string $button_label = null, ?string $button_url = null ) {
		$template_name              = strtolower( $template );
		$this->data['template']     = sprintf( '/notice/%s.php', $template_name );
		$this->data['button_label'] = $button_label;
		$this->data['button_url']   = $button_url;
	}

	/**
	 * Get a message variable.
	 *
	 * @param string $name The variable name.
	 *
	 * @return string|array<int, string>|null
	 */
	public function __get( $name ) {
		if ( ! isset( $this->data[ $name ] ) ) {
			return null;
		}

		return $this->data[ $name ];
	}

	/**
	 * Sets a variable for the message.
	 *
	 * @param string                    $name  The variable to set.
	 * @param string|array<int, string> $value The value to set.
	 */
	public function __set( $name, $value ): void {
		$this->data[ $name ] = $value;
	}

	/**
	 * Check if variable is set.
	 *
	 * @param string $name The variable to check.
	 */
	public function __isset( $name ) {
		return isset( $this->data[ $name ] );
	}
}
