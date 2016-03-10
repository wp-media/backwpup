<?php
/**
 *
 */
class BackWPup_Help {

	/**
	 *
	 */
	public static function help() {

		if ( method_exists( get_current_screen(), 'add_help_tab' ) ) {
			get_current_screen()->add_help_tab( array(
													 'id'      => 'plugininfo',
													 'title'   => __( 'Plugin Info', 'backwpup' ),
													 'content' =>
													 '<p> '.str_replace( '\"','"', sprintf( _x( '%1$s version %2$s. A project by <a href="http://inpsyde.com">Inpsyde GmbH</a>.','Plugin name and link; Plugin Version','backwpup' ), '<a href="' . translate( BackWPup::get_plugin_data( 'PluginURI' ), 'backwpup' ) . '">' . BackWPup::get_plugin_data( 'Name' ) . '</a>' , BackWPup::get_plugin_data( 'Version' ) ) ) . '</p>'
													 . '<p>' . esc_html__( 'BackWPup comes with ABSOLUTELY NO WARRANTY. This is a free software, and you are welcome to redistribute it under certain conditions.', 'backwpup' ) . '</p>'
												) );

			$text_help_sidebar = '<p><strong>' . __( 'For more information:', 'backwpup' ) . '</strong></p>';
			$text_help_sidebar .= '<p><a href="' . translate( BackWPup::get_plugin_data( 'PluginURI' ), 'backwpup' ) . '">' . BackWPup::get_plugin_data( 'Name' ) . '</a></p>';
			$text_help_sidebar .= '<p><a href="http://wordpress.org/extend/plugins/backwpup/">' . esc_html__( 'Plugin on wordpress.org', 'backwpup' ) . '</a></p>';
			$text_help_sidebar .= '<p><a href="' . esc_attr__( 'https://marketpress.com/news/', 'backwpup' ) . '">' . __( 'News', 'backwpup' ) . '</a></p>';
			if ( class_exists( 'BackWPup_Pro', FALSE ) )
				$text_help_sidebar .= '<p><a href="' . esc_attr__( 'https://marketpress.com/support/forum/plugins/backwpup-pro/', 'backwpup' ) . '">' . esc_html__( 'Pro Support', 'backwpup' ) . '</a></p>';
			else
				$text_help_sidebar .= '<p><a href="' . esc_attr__( 'http://wordpress.org/support/plugin/backwpup/', 'backwpup' ) . '">' . esc_html__( 'Support', 'backwpup' ) . '</a></p>';
			$text_help_sidebar .= '<p><a href="' . esc_attr__( 'https://marketpress.com/documentation/backwpup-pro/', 'backwpup' ) . '">' . esc_html__( 'Manual', 'backwpup' ) . '</a></p>';

			get_current_screen()->set_help_sidebar( $text_help_sidebar );
		}

	}

	/**
	 * @static
	 *
	 * @param array $tab
	 */
	public static function add_tab( $tab = array() ) {

		if ( method_exists( get_current_screen(), 'add_help_tab' ) )
			get_current_screen()->add_help_tab( $tab );

	}

}
