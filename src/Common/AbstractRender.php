<?php
declare(strict_types=1);

namespace WPMedia\BackWPup\Common;

use WPMedia\BackWPup\Common\Interfaces\RenderInterface;

abstract class AbstractRender implements RenderInterface {
	/**
	 * Path to the templates
	 *
	 * @var string
	 */
	private string $template_path;

	/**
	 * Constructor
	 *
	 * @param string $template_path Path to the templates.
	 */
	public function __construct( string $template_path ) {
		$this->template_path = $template_path;
	}

	/**
	 * Renders the given template if it's readable.
	 *
	 * @param string $template Template slug.
	 * @param array  $data     Data to pass to the template.
	 *
	 * @return string
	 */
	public function generate( $template, $data = [] ): string {
		$template_path = $this->get_template_path( $template );

		ob_start();

		include $template_path;

		return trim( ob_get_clean() );
	}

	/**
	 * Returns the path a specific template.
	 *
	 * @param string $path Relative path to the template.
	 *
	 * @return string
	 */
	private function get_template_path( string $path ): string {
		return $this->template_path . '/' . $path . '.php';
	}

	/**
	 * Displays the button template.
	 *
	 * @param string $type   Type of button (can be button or link).
	 * @param string $action Action to be performed.
	 * @param array  $args   Optional array of arguments to populate the button attributes.
	 * @return void
	 */
	public function render_action_button( string $type, string $action, array $args = [] ): void {
		$default = [
			'label'      => '',
			'action'     => '',
			'url'        => '',
			'parameter'  => '',
			'attributes' => '',
		];

		$args = wp_parse_args( $args, $default );

		if ( ! empty( $args['attributes'] ) ) {
			$attributes = '';
			foreach ( $args['attributes'] as $key => $value ) {
				$attributes .= ' ' . sanitize_key( $key ) . '="' . esc_attr( $value ) . '"';
			}

			$args['attributes'] = $attributes;
		}

		if ( 'link' !== $type ) {
			$args['action'] = $action;
			echo $this->generate( 'buttons/button', $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic content is properly escaped in the view.

			return;
		}

		echo $this->generate( 'buttons/link', $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Dynamic content is properly escaped in the view.
	}
}
