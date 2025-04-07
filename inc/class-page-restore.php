<?php
/**
 * Restore Page.
 */

use function Inpsyde\BackWPup\Infrastructure\Restore\restore_container;
use Inpsyde\BackWPup\Infrastructure\Restore\TemplateLoader;

/**
 * Class for BackWPup restore page.
 */
class BackWPup_Page_Restore {

	/**
	 * Enqueue JS.
	 */
	public static function admin_print_scripts() {
		$url                  = untrailingslashit( BackWPup::get_plugin_data( 'url' ) );
		$dir                  = untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) );
		$path_js              = "{$url}/assets/js";
		$dir_js               = "{$dir}/assets/js";
		$shared_scripts_path  = "{$url}/vendor/inpsyde/backwpup-shared/resources/js";
		$shared_scripts_dir   = "{$dir}/vendor/inpsyde/backwpup-shared/resources/js";
		$restore_scripts_path = "{$url}/vendor/inpsyde/backwpup-restore-shared/resources/js";
		$restore_scripts_dir  = "{$dir}/vendor/inpsyde/backwpup-restore-shared/resources/js";
		$suffix               = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Vendor
		wp_register_script( 'js-url', "{$path_js}/vendor/url.min.js", [ 'jquery' ], '', true );

		wp_register_script(
			'backwpup_functions',
			"{$shared_scripts_path}/functions{$suffix}.js",
			[
				'underscore',
				'jquery',
			],
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
		wp_register_script(
			'restore_functions',
			"{$restore_scripts_path}/restore-functions{$suffix}.js",
			[
				'underscore',
				'jquery',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
		wp_register_script(
			'restore_decompress',
			"{$restore_scripts_path}/decompress{$suffix}.js",
			[
				'underscore',
				'jquery',
				'backwpup_functions',
				'restore_functions',
				'decrypter',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
		wp_register_script(
			'restore_download',
			"{$restore_scripts_path}/download{$suffix}.js",
			[
				'underscore',
				'jquery',
				'backwpup_functions',
				'restore_functions',
				'backwpup_states',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
		wp_register_script(
			'restore_strategy',
			"{$restore_scripts_path}/strategy{$suffix}.js",
			[
				'underscore',
				'jquery',
				'backwpup_functions',
				'restore_functions',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
		wp_register_script(
			'restore_database',
			"{$restore_scripts_path}/database{$suffix}.js",
			[
				'underscore',
				'jquery',
				'backwpup_functions',
				'restore_functions',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
		wp_register_script(
			'restore_database_restore',
			"{$restore_scripts_path}/database-restore{$suffix}.js",
			[
				'underscore',
				'jquery',
				'backwpup_functions',
				'restore_functions',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
		wp_register_script(
			'restore_files_restore',
			"{$restore_scripts_path}/files-restore{$suffix}.js",
			[
				'underscore',
				'jquery',
				'backwpup_functions',
				'restore_functions',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
		wp_register_script(
			'restore_controller',
			"{$restore_scripts_path}/controller{$suffix}.js",
			[
				'underscore',
				'jquery',
				'backwpup_functions',
				'restore_functions',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
		wp_register_script(
			'decrypter',
			"{$restore_scripts_path}/decrypter{$suffix}.js",
			[
				'underscore',
				'jquery',
				'backwpup_functions',
				'restore_functions',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
		wp_register_script(
			'restore_migrate',
			"{$restore_scripts_path}/migrate{$suffix}.js",
			[
				'underscore',
				'jquery',
				'backwpup_functions',
				'restore_functions',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);

		wp_enqueue_script( 'backwpupgeneral', [], '', BackWPup::get_plugin_data( 'Version' ), true );
		wp_enqueue_script(
			'restore_restore',
			"{$path_js}/restore{$suffix}.js",
			[
				'underscore',
				'jquery',
				'plupload',
				'js-url',
				'backwpup_functions',
				'restore_functions',
				'restore_decompress',
				'restore_download',
				'restore_strategy',
				'restore_database',
				'restore_database_restore',
				'restore_files_restore',
				'restore_controller',
				'decrypter',
				'restore_migrate',
			],
			BackWPup::get_plugin_data( 'Version' ),
			true
		);
	}

	/**
	 * The Content of the page.
	 */
	public function content() {
		$template = new TemplateLoader( restore_container( null ) );
		$template->load();

		backwpup_template( null, '/restore/index.php' );
	}

	/**
	 * Page Title.
	 */
	public function title() {
		echo esc_html(
			sanitize_text_field(
				sprintf(
				// Translators: $1 is the name of the plugin
					esc_html__( '%s &rsaquo; Restore', 'backwpup' ),
					BackWPup::get_plugin_data( 'name' )
				)
			)
		);
	}

	/**
	 * Load.
	 *
	 * Load the basic for the page and also, perform stuffs before render the content.
	 */
	public static function load() {
		do_action( 'backwpup_page_restore' );
	}

	/**
	 * Entry method to display WordPress page.
	 */
	public static function page() {
		$restore_page = new self(); ?>
		<div class="wrap" id="backwpup-page">
			<h1>
				<?php $restore_page->title(); ?>
			</h1>

			<?php $restore_page->content(); ?>
		</div>
		<?php
	}
}
