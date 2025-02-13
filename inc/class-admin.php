<?php

use Inpsyde\BackWPup\Infrastructure\Restore\Restore;
use Inpsyde\BackWPup\Notice;
use Inpsyde\BackWPup\Notice\DropboxNotice;
use Inpsyde\BackWPup\Notice\NoticeView;
use Inpsyde\BackWPup\Notice\PhpNotice;
use Inpsyde\BackWPup\Notice\WordPressNotice;
use Inpsyde\BackWPup\Pro\Settings;
use Inpsyde\BackWPup\Pro\Settings\AjaxEncryptionKeyHandler;
use Inpsyde\BackWPup\Notice\EvaluateNotice;
use Inpsyde\BackWPup\Notice\EasycronUpdateNotice;
use Inpsyde\BackWPup\Notice\RestoreFeatureInformationNotice;
use Inpsyde\BackWPup\Notice\NewUINotice;

/**
 * BackWPup_Admin.
 */
final class BackWPup_Admin {

	const SLUG = 'backwpup';

	/**
	 * The page hooks list.
	 *
	 * @var array
	 */
	public $page_hooks = [];

    /**
     * @var BackWPup_Page_Settings
     */
    private $settings;

	/**
	 * The URL to the BackWPup website.
	 *
	 * @var string
	 */
	private $backwpup_url = 'https://backwpup.com';

    public function __construct(BackWPup_Page_Settings $settings)
    {
        $this->settings = $settings;

        BackWPup::load_text_domain();
    }

    /**
     * Enqueues main css file.
     */
    public function admin_css()
    {
        $pluginDir = untrailingslashit(BackWPup::get_plugin_data('plugindir'));
        $filePath = "{$pluginDir}/assets/css/main.min.css";

        wp_enqueue_style(
            'backwpup',
            BackWPup::get_plugin_data('URL') . '/assets/css/main.min.css',
            [],
            filemtime($filePath),
            'screen'
        );
    }

	/**
	 * Enqueues main js file.
	 */
	public function enqueue_backup_generation() {
		$job_object = BackWPup_Job::get_working_data();
		if ( ! empty( $job_object->logfile ) ) {
			wp_enqueue_script(
				'backwpup-generate',
				BackWPup::get_plugin_data( 'URL' ) . '/assets/js/backwpup-generate.js',
				[ 'jquery' ],
				'1.0.0',
				true
			);
			wp_localize_script(
				'backwpup-generate',
				'backwpupApi',
				$this->get_backwpup_api_data( $job_object )
			);

			wp_localize_script(
				'backwpup-generate',
				'backwpup',
				[
					'adminUrl' => network_admin_url( 'admin.php?page=backwpup' ),
				]
				);
		}
	}
	/**
	 * Enqueues main css/js file.
	 */
	public function enqueue_admin_assets() {
		// Get the current screen object.
		$screen = get_current_screen();

		// Check if we're on a BackWPUp page.
		if ( isset( $screen->id ) && strpos( $screen->id, 'backwpup' ) !== false ) {
			wp_enqueue_style(
				'backwpup-admin',
				BackWPup::get_plugin_data( 'URL' ) . '/assets/css/backwpup-admin.css',
				[],
				'1.0.0'
			);
		}

		wp_enqueue_script(
			'backwpup-admin',
			BackWPup::get_plugin_data( 'URL' ) . '/assets/js/backwpup-admin.js',
			[ 'jquery' ],
			'1.0.0',
			true
        );

		wp_localize_script(
			'backwpup-admin',
			'backwpupApi',
			$this->get_backwpup_api_data()
		);
	}

    /**
     * Load for all BackWPup pages.
     */
    public static function init_general()
    {
        add_thickbox();

        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        wp_register_script(
            'backwpupgeneral',
            BackWPup::get_plugin_data('URL') . "/assets/js/general{$suffix}.js",
            ['jquery'],
            ($suffix ? BackWPup::get_plugin_data('Version') : time()),
            false
        );

        // Register clipboard.js script
        wp_register_script(
            'backwpup_clipboard',
            BackWPup::get_plugin_data('URL') . '/assets/js/vendor/clipboard.min.js',
            ['jquery'],
            '1.7.1',
            true
		);
    }

    /**
     * Add Message (across site loadings).
     *
     * @param string $message string Message test
     * @param bool   $error   bool ist it a error message
     */
    public static function message($message, $error = false)
    {
        if (empty($message)) {
            return;
        }

        $saved_message = self::get_messages();

        if ($error) {
            $saved_message['error'][] = $message;
        } else {
            $saved_message['updated'][] = $message;
        }

        update_site_option('backwpup_messages', $saved_message);
    }

    /**
     * Get all Message that not displayed.
     *
     * @return array
     */
    public static function get_messages()
    {
        return get_site_option('backwpup_messages', []);
    }

    /**
     * Display Messages.
     *
     * @param bool $echo
     *
     * @return string
     */
    public static function display_messages($echo = true)
    {
        /**
         * This hook can be used to display more messages in all BackWPup pages.
         */
        do_action('backwpup_admin_messages');

        $message_updated = '';
        $message_error = '';
        $saved_message = self::get_messages();
        $message_id = ' id="message"';

        if (empty($saved_message)) {
            return '';
        }

        if (!empty($saved_message['updated'])) {
            foreach ($saved_message['updated'] as $msg) {
                $message_updated .= '<p>' . $msg . '</p>';
            }
        }
        if (!empty($saved_message['error'])) {
            foreach ($saved_message['error'] as $msg) {
                $message_error .= '<p>' . $msg . '</p>';
            }
        }

        update_site_option('backwpup_messages', []);

        if (!empty($message_updated)) {
            $message_updated = '<div' . $message_id . ' class="updated">' . $message_updated . '</div>';
            $message_id = '';
        }
        if (!empty($message_error)) {
            $message_error = '<div' . $message_id . ' class="bwu-message-error">' . $message_error . '</div>';
        }

        if ($echo) {
            echo $message_updated . $message_error;
        }

        return $message_updated . $message_error;
    }

    /**
     * Admin init function.
     */
    public function admin_init()
    {
        if (!is_admin()) {
            return;
        }

		$is_onboarding = get_site_option( 'backwpup_onboarding', false );
		$allowed_pages = [ 'backwpupabout', 'backwpuponboarding', 'backwpupfirstbackup', 'backwpupsupport' ];

		// Redirect only if onboarding is active and the current page is not in the allowed pages.
		if (
			$is_onboarding &&
			( isset( $_GET['page'] ) && false !== strpos( sanitize_text_field( wp_unslash( $_GET['page'] ) ), 'backwpup' ) && ! in_array( $_GET['page'], $allowed_pages, true ) ) // phpcs:ignore WordPress.Security.NonceVerification
		) {
			wp_safe_redirect( network_admin_url( 'admin.php?page=backwpuponboarding' ) );
			exit;
		}

        if (\BackWPup::is_pro()) {
            $this->admin_init_pro();
		}

		if ( ! defined( 'DOING_AJAX' ) || ( defined( 'DOING_AJAX' ) && ! DOING_AJAX ) ) {
			// Only init notices if this is not an AJAX request.
			$this->init_notices();

            // Everything after this point applies to AJAX
            return;
		}
		$jobtypes     = BackWPup::get_job_types();
		$destinations = BackWPup::get_registered_destinations();

        add_action('wp_ajax_backwpup_working', [\BackWPup_Page_Jobs::class, 'ajax_working']);
        add_action('wp_ajax_backwpup_cron_text', [\BackWPup_Page_Editjob::class, 'ajax_cron_text']);
        add_action('wp_ajax_backwpup_view_log', [\BackWPup_Page_Logs::class, 'ajax_view_log']);
        add_action('wp_ajax_download_backup_file', [\BackWPup_Destination_Downloader::class, 'download_by_ajax']);

        foreach ($jobtypes as $id => $jobtypeclass) {
            add_action('wp_ajax_backwpup_jobtype_' . strtolower($id), [$jobtypeclass, 'edit_ajax']);
		}
		foreach ( $destinations as $id => $dest ) {
			if ( ! empty( $dest['class'] ) ) {
				add_action(
                    'wp_ajax_backwpup_dest_' . strtolower($id),
                    [
                        BackWPup::get_destination($id),
                        'edit_ajax',
                    ],
                    10,
                    0
                );
            }
		}
    }

    private function admin_init_pro()
    {
        $ajax_encryption_key_handler = new AjaxEncryptionKeyHandler();

		add_action( 'wp_ajax_encrypt_key_handler', [ $ajax_encryption_key_handler, 'handle' ] );

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script(
			'backwpuppagesettings-encryption',
			untrailingslashit( BackWPup::get_plugin_data( 'URL' ) ) . "/assets/js/settings-encryption{$suffix}.js",
			[
				'underscore',
				'jquery',
				'thickbox',
			],
			filemtime( untrailingslashit( BackWPup::get_plugin_data( 'plugindir' ) ) . "/assets/js/settings-encryption{$suffix}.js" ),
			false
        );

		wp_localize_script(
			'backwpuppagesettings-encryption',
			'settingsEncryptionVariables',
			[
				'validPublicKey'           => esc_html__( 'Public key is valid.', 'backwpup' ),
				'invalidPublicKey'         => esc_html__( 'Public key is invalid.', 'backwpup' ),
				'privateKeyMissed'         => esc_html__( 'Please enter your private key.', 'backwpup' ),
				'publicKeyMissed'          => esc_html__(
					'Please enter a public key first, or generate a key pair.',
					'backwpup'
				),
				'mustDownloadPrivateKey'   => esc_html__(
					'Please download the private key before continuing. If you do not save it locally, you cannot decrypt your backups later.',
					'backwpup'
				),
				'mustDownloadSymmetricKey' => esc_html__(
					'Please download the key before continuing. If you do not save it locally, you cannot decrypt your backups later.',
					'backwpup'
				),
			]
		);
    }

	/**
	 * Init the plugin notices.
	 */
	private function init_notices() {
		// Show notice if PHP < 7.4.
        $phpNotice = new PhpNotice( //phpcs:ignore
			new NoticeView( PhpNotice::ID )
		);
        $phpNotice->init(PhpNotice::TYPE_ADMIN);

        // Show notice if WordPress < 5.0
        $wpNotice = new WordPressNotice(
            new NoticeView(WordPressNotice::ID)
        );
        $wpNotice->init(WordPressNotice::TYPE_ADMIN);

		// Show notice if Dropbox needs to be reauthenticated.
		$dropbox_notice = new DropboxNotice(
			new NoticeView( DropboxNotice::ID ),
			false // Not dismissible.
		);
		$dropbox_notice->init( DropboxNotice::TYPE_ADMIN );

		$evaluate_notice = new EvaluateNotice(
			new NoticeView( EvaluateNotice::ID )
		);
		// $evaluate_notice->init( EvaluateNotice::TYPE_ADMIN );

		$easycron_update_notice = new EasycronUpdateNotice(
			new NoticeView( EasycronUpdateNotice::ID ),
			true
		);
		$easycron_update_notice->init( EasycronUpdateNotice::TYPE_ADMIN );

		$restore_feature_information_notice = new RestoreFeatureInformationNotice(
			new NoticeView( RestoreFeatureInformationNotice::ID ),
			true
		);
		// $restore_feature_information_notice->init( RestoreFeatureInformationNotice::TYPE_ADMIN );

		$new_ui_notice = new newUINotice(
			new NoticeView( newUINotice::ID )
		);
		$new_ui_notice->init( newUINotice::TYPE_ADMIN );
	}

    /**
     * Add Links in Plugins Menu to BackWPup.
     *
     * @param $links
     * @param $file
     *
     * @return array
	 */
	public function plugin_links( $links, $file ) {
		if ( plugin_basename( BackWPup::get_plugin_data( 'MainFile' ) ) === $file ) {
			$links = array_filter(
				$links,
				function ( $link ) {
					return strpos( $link, 'Visit plugin site' ) === false;
				}
				);
			if ( BackWPup::is_pro() ) {
				$links[] = '<a href="' . network_admin_url( '/plugin-install.php?tab=plugin-information&plugin=backwpup&TB_iframe=true&width=772&height=610' ) . '" class="thickbox open-plugin-details-modal">' . __( 'View details', 'backwpup' ) . '</a>';
			}
		}

        return $links;
    }

	/**
	 * Adds custom links to the plugin's action links in the Plugins page.
	 *
	 * @param array $links An array of the plugin's existing action links.
	 * @return array Modified array of action links with custom links added.
	 */
	public function plugin_name_links( $links ) {

		$is_pro_user   = BackWPup::is_pro();
		$settings_link = '<a href="' . network_admin_url( 'admin.php?page=backwpup' ) . '">' . __( 'Settings', 'backwpup' ) . '</a>';
		$faqs_link     = '<a href="' . $this->backwpup_url . '/docs-category/faq/" target="_blank">FAQ</a>';
		$docs_link     = '<a href="' . $this->backwpup_url . '/docs/" target="_blank">Docs</a>';

		if ( $is_pro_user ) {
			// Pro user links.
			$support_link = '<a href="' . $this->backwpup_url . '/support/" target="_blank">Support</a>';

			// Append custom links for pro users.
			$links = array_merge( [ $settings_link, $faqs_link, $docs_link, $support_link ], $links );
		} else {
			// Free user links.
			$buy_pro_link = '<a href="' . $this->backwpup_url . '/#buy" target="_blank" style="color: #3ac495; font-weight: bold;">Upgrade to Pro</a>';

			// Append custom links for free users.
			$links = array_merge( [ $buy_pro_link, $settings_link, $faqs_link, $docs_link ], $links );
		}

		return $links;
    }

    /**
     * Add menu entries.
     */
    public function admin_menu()
    {
        add_menu_page(
            BackWPup::get_plugin_data('name'),
            BackWPup::get_plugin_data('name'),
            'backwpup',
            'backwpup',
			[
                \BackWPup_Page_Backups::class,
				'page',
            ],
            'div'
		);
		// External menu pages.
		add_submenu_page(
			'backwpup',
			__( 'Docs', 'backwpup' ),
			__( 'Docs', 'backwpup' ),
			'backwpup',
			'docs',
			'__return_false',
		);
		if ( ! BackWPup::is_pro() ) {
			add_submenu_page(
				'backwpup',
				__( 'Upgrade to Pro', 'backwpup' ),
				__( 'Upgrade to Pro', 'backwpup' ),
				'backwpup',
				'buypro',
				'__return_false',
			);
		}

		// Add pages form plugins.
		$this->page_hooks = apply_filters( 'backwpup_admin_pages', $this->page_hooks );

		global $submenu;
		$submenu['backwpup'][0][0] = __( 'Settings', 'backwpup' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	/**
	 * Handle the "First Backup" page.
	 *
	 * @param array $page_hooks  The page hooks list.
	 * @return mixed
	 */
	public function admin_page_first_backup( $page_hooks ) {
		$this->page_hooks['backwpupfirstbackup'] = add_submenu_page(
			'backwpup_null',
			__( 'First Backup', 'backwpup' ),
			__( 'First Backup', 'backwpup' ),
			'backwpup',
			'backwpupfirstbackup',
			[ \BackWPup_Page_First_Backup::class, 'page' ]
		);

		add_action( 'load-' . $this->page_hooks['backwpupfirstbackup'], [ self::class, 'init_general' ] );
		add_action( 'load-' . $this->page_hooks['backwpupfirstbackup'], [ \BackWPup_Page_First_Backup::class, 'init' ] );

        return $page_hooks;
    }

    /**
     * @param $page_hooks
     *
     * @return mixed
     */
    public function admin_page_jobs($page_hooks)
    {
		$this->page_hooks['backwpupjobs'] = add_submenu_page(
			'backwpup_null',
			__( 'Jobs', 'backwpup' ),
			__( 'Jobs', 'backwpup' ),
			'backwpup_jobs',
            'backwpupjobs',
            [
                \BackWPup_Page_Jobs::class,
                'page',
            ]
        );
        add_action('load-' . $this->page_hooks['backwpupjobs'], [\BackWPup_Admin::class, 'init_general']);
        add_action('load-' . $this->page_hooks['backwpupjobs'], [\BackWPup_Page_Jobs::class, 'load']);
        add_action(
            'admin_print_styles-' . $this->page_hooks['backwpupjobs'],
            [
                \BackWPup_Page_Jobs::class,
                'admin_print_styles',
            ]
        );
        add_action(
            'admin_print_scripts-' . $this->page_hooks['backwpupjobs'],
            [
                \BackWPup_Page_Jobs::class,
                'admin_print_scripts',
            ]
        );

        return $page_hooks;
    }

    /**
     * @param $page_hooks
     *
     * @return mixed
     */
    public function admin_page_editjob($page_hooks)
    {
		$this->page_hooks['backwpupeditjob'] = add_submenu_page(
			'backwpup_null',
			__( 'Add new job', 'backwpup' ),
			__( 'Add new job', 'backwpup' ),
			'backwpup_jobs_edit',
            'backwpupeditjob',
            [
                \BackWPup_Page_Editjob::class,
                'page',
            ]
        );
        add_action('load-' . $this->page_hooks['backwpupeditjob'], [\BackWPup_Admin::class, 'init_general']);
        add_action('load-' . $this->page_hooks['backwpupeditjob'], [\BackWPup_Page_Editjob::class, 'auth']);
        add_action(
            'admin_print_styles-' . $this->page_hooks['backwpupeditjob'],
            [
                \BackWPup_Page_Editjob::class,
                'admin_print_styles',
            ]
        );
        add_action(
            'admin_print_scripts-' . $this->page_hooks['backwpupeditjob'],
            [
                \BackWPup_Page_Editjob::class,
                'admin_print_scripts',
            ]
        );

        return $page_hooks;
    }

    /**
     * @param $page_hooks
     *
     * @return mixed
     */
    public function admin_page_logs($page_hooks)
    {
		$this->page_hooks['backwpuplogs'] = add_submenu_page(
			'backwpup_null',
			__( 'Logs', 'backwpup' ),
			__( 'Logs', 'backwpup' ),
			'backwpup_logs',
            'backwpuplogs',
            [
                \BackWPup_Page_Logs::class,
                'page',
            ]
        );
        add_action('load-' . $this->page_hooks['backwpuplogs'], [\BackWPup_Admin::class, 'init_general']);
        add_action('load-' . $this->page_hooks['backwpuplogs'], [\BackWPup_Page_Logs::class, 'load']);
        add_action(
            'admin_print_styles-' . $this->page_hooks['backwpuplogs'],
            [
                \BackWPup_Page_Logs::class,
                'admin_print_styles',
            ]
        );
        add_action(
            'admin_print_scripts-' . $this->page_hooks['backwpuplogs'],
            [
                \BackWPup_Page_Logs::class,
                'admin_print_scripts',
            ]
        );

        return $page_hooks;
    }

	/**
	 * The onboarding admin page.
	 *
	 * @param array $page_hooks  The page hooks list.
	 *
     * @return mixed
	 */
	public function admin_page_onboarding( $page_hooks ) {
		if ( false === (bool) get_site_option( 'backwpup_onboarding', false ) ) {
			return $page_hooks;
		}
		$this->page_hooks['backwpupbackups'] = add_submenu_page(
			'backwpup',
			__( 'Onboarding', 'backwpup' ),
			__( 'Onboarding', 'backwpup' ),
			'backwpup_backups',
			'backwpuponboarding',
			[
				\BackWPup_Page_Onboarding::class,
				'page',
            ]
		);
		return $page_hooks;
    }

    /**
     * @param $page_hooks
     *
     * @return mixed
	 */
	public function admin_page_backups( $page_hooks ) {
		$this->page_hooks['backwpupbackups'] = add_submenu_page(
			'backwpup_null',
			__( 'Backups', 'backwpup' ),
			__( 'Backups', 'backwpup' ),
			'backwpup_backups',
            'backwpupbackups',
            [
                \BackWPup_Page_Backups::class,
                'page',
            ]
		);
		add_action( 'load-' . $this->page_hooks['backwpupbackups'], [ self::class, 'init_general' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_backup_generation' ] );
		add_action( 'load-' . $this->page_hooks['backwpupbackups'], [ \BackWPup_Page_Backups::class, 'load' ] );
		add_action(
            'admin_print_styles-' . $this->page_hooks['backwpupbackups'],
            [
                \BackWPup_Page_Backups::class,
                'admin_print_styles',
            ]
        );
        add_action(
            'admin_print_scripts-' . $this->page_hooks['backwpupbackups'],
            [
                \BackWPup_Page_Backups::class,
                'admin_print_scripts',
            ]
        );

        return $page_hooks;
    }

    /**
     * @param $page_hooks
     *
     * @return mixed
     */
    public function admin_page_settings($page_hooks)
    {
		$this->page_hooks['backwpupsettings'] = add_submenu_page(
			'backwpup_null',
			esc_html__( 'Settings', 'backwpup' ),
			esc_html__( 'Settings', 'backwpup' ),
			'backwpup_settings',
            'backwpupsettings',
            [$this->settings, 'page']
        );
        add_action('load-' . $this->page_hooks['backwpupsettings'], [\BackWPup_Admin::class, 'init_general']);
        add_action(
            'admin_print_scripts-' . $this->page_hooks['backwpupsettings'],
            [$this->settings, 'admin_print_scripts']
        );

        return $page_hooks;
    }

	/**
	 * Admin Page Restore.
	 *
	 * @param array $page_hooks the page hooks list
	 *
	 * @return array $page_hooks
	 */
	public function admin_page_restore( $page_hooks ) {
		$this->page_hooks['backwpuprestore'] = add_submenu_page(
			'backwpup_null',
			esc_html__( 'Restore', 'backwpup' ),
			esc_html__( 'Restore', 'backwpup' ),
			'backwpup_restore',
			'backwpuprestore',
			[
				\BackWPup_Page_Restore::class,
				'page',
            ]
        );

		// Register the submenu page (WP take care of capability) but prevent other stuffs to be executed if user
		// doesn't have correct privileges.
		if ( ! current_user_can( 'backwpup_restore' ) ) {
			return $page_hooks;
		}

		add_action( 'load-' . $this->page_hooks['backwpuprestore'], [ self::class, 'init_general' ] );
		add_action(
			'load-' . $this->page_hooks['backwpuprestore'],
			[ \BackWPup_Page_Restore::class, 'load' ]
		);
		add_action(
			'admin_print_scripts-' . $this->page_hooks['backwpuprestore'],
			[
				\BackWPup_Page_Restore::class,
				'admin_print_scripts',
            ]
        );

        return $page_hooks;
    }

    /**
     * @param $page_hooks
     *
     * @return mixed
     */
    public function admin_page_about($page_hooks)
    {
		$this->page_hooks['backwpupabout'] = add_submenu_page(
			'backwpup_null',
			__( 'About', 'backwpup' ),
			__( 'About', 'backwpup' ),
			'backwpup',
            'backwpupabout',
            [
                \BackWPup_Page_About::class,
                'page',
            ]
        );
        add_action('load-' . $this->page_hooks['backwpupabout'], [\BackWPup_Admin::class, 'init_general']);
        add_action(
            'admin_print_styles-' . $this->page_hooks['backwpupabout'],
            [
                \BackWPup_Page_About::class,
                'admin_print_styles',
            ]
        );
        add_action(
            'admin_print_scripts-' . $this->page_hooks['backwpupabout'],
            [
                \BackWPup_Page_About::class,
                'admin_print_scripts',
            ]
        );

        return $page_hooks;
    }


    /**
     * Called on save form. Only POST allowed.
	 */
	public function save_post_form() {
		// TODO delete backwpupsettings option in $allowed_pages.
		$allowed_pages = [
            'backwpupeditjob',
            'backwpupinformation',
			'backwpupsettings',
			'backwpup',
			'backwpupfirstbackup',
		];

		// Sanitize $_POST data.
		$post_page    = isset( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : null;
		$post_anchor  = isset( $_POST['anchor'] ) ? sanitize_text_field( wp_unslash( $_POST['anchor'] ) ) : null;
		$post_tab     = isset( $_POST['tab'] ) ? sanitize_text_field( wp_unslash( $_POST['tab'] ) ) : null;
		$post_nexttab = isset( $_POST['nexttab'] ) ? sanitize_text_field( wp_unslash( $_POST['nexttab'] ) ) : null;

		if ( isset( $post_page ) && ! in_array( $post_page, $allowed_pages, true ) ) {
			wp_die( esc_html__( 'Cheating, huh?', 'backwpup' ) );
		}

		// nonce check.
		check_admin_referer( $post_page . '_page' );

		if ( ! current_user_can( 'backwpup' ) ) {
			wp_die( esc_html__( 'Cheating, huh?', 'backwpup' ) );
		}

		$query_args = [];
		if ( isset( $post_page ) ) {
			$query_args['page'] = $post_page;
		}
		if ( isset( $post_tab ) ) {
			$query_args['tab'] = $post_tab;
		}
		if ( isset( $post_tab, $post_nexttab ) && $post_tab !== $post_nexttab ) {
			$query_args['tab'] = $post_nexttab;
		}

		$jobid = null;
		if ( isset( $_POST['jobid'] ) ) {
			$jobid               = (int) $_POST['jobid'];
			$query_args['jobid'] = $jobid;
        }

		// Call method to save data.
		if ( 'backwpupeditjob' === $post_page ) {
			BackWPup_Page_Editjob::save_post_form( $post_tab, $jobid ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		} elseif ( 'backwpup' === $post_page ) {
			if ( isset( $_POST['onboarding'] ) && '1' === $_POST['onboarding'] ) {
				BackWPup_Page_Onboarding::save_post_form();
			} else {
				$this->settings->save_post_form();
			}
        }

		// Back to topic.
		wp_safe_redirect( add_query_arg( $query_args, network_admin_url( 'admin.php' ) ) . $post_anchor );

        exit;
    }

    /**
     * Overrides WordPress text in Footer.
     *
     * @param $admin_footer_text string
     *
     * @return string
     */
    public function admin_footer_text($admin_footer_text)
    {
        $default_text = $admin_footer_text;
        if (isset($_REQUEST['page']) && strstr((string) $_REQUEST['page'], 'backwpup')) {
            $admin_footer_text = <<<'EOT'
<a href="https://wp-media.me" class="wpmedia_logo" title="WP Media">
    <svg width="81px" height="24px" viewBox="0 0 459 136" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <!-- Generator: Sketch 54.1 (76490) - https://sketchapp.com -->
        <title>WP Media Logo</title>
        <desc>Created with Sketch.</desc>
        <g id="WPM-Black-on-white" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
            <path d="M421.581975,69.6420872 L421.581975,64.0461992 L420.6733,64.0023992 C418.929626,63.9142152 417.634443,63.3489032 416.94212,62.4016552 C416.431063,61.6371992 416.136358,60.2969192 416.036368,58.3516152 C416.036368,58.1775832 416.025258,57.9042712 416.012979,57.5287592 C415.981404,56.7491192 415.930532,55.5098712 415.930532,53.7707192 C415.930532,51.7524152 415.815339,50.2170792 415.710087,49.0683512 C415.35457,46.3626792 414.170486,44.1463992 412.071295,42.2811032 C409.844633,40.4099672 407.306307,39.5024312 404.308967,39.5024312 C404.062794,39.5024312 403.728912,39.5024312 403.259371,39.6577752 L402.60564,39.8744392 L402.60564,45.3856472 L403.482739,45.4574792 C405.64859,45.6402712 407.061889,46.5770072 407.686384,48.2700232 C407.899226,48.8008792 408.256498,50.3075992 408.256498,54.4113672 C408.256498,58.3895752 408.393326,60.7109752 408.70031,62.1557912 C409.152309,64.5040552 410.109516,66.2140072 411.61111,67.3311992 C410.218277,68.4635752 409.244697,70.2003912 408.706158,72.5177032 C408.393326,73.8655752 408.256498,76.2307752 408.256498,80.1885432 C408.256498,84.2905592 407.899226,85.7966952 407.692816,86.3135352 C406.954298,88.0649512 405.614675,88.9643112 403.482739,89.1430152 L402.60564,89.2142632 L402.60564,94.0328472 L402.655927,94.9292872 L403.549984,94.9876872 C403.609042,94.9923592 403.67804,95.0092952 403.749962,95.0238952 C403.947602,95.0636072 404.149335,95.0951432 404.308967,95.0951432 C407.308062,95.0951432 409.846971,94.1876072 412.088837,92.2995352 C414.170486,90.4535112 415.35457,88.2395672 415.71652,85.4562232 L415.871474,82.5893672 C416.023504,79.8901192 416.14279,77.7596872 416.141036,76.1893112 C416.243949,74.2650312 416.540993,72.9609592 417.04971,72.1959192 C417.705781,71.2130472 418.927872,70.6903672 420.780306,70.5957592 L420.732358,69.6420872 L421.581975,69.6420872 Z" id="Fill-1" fill="#151921"></path>
            <path d="M56.2747034,39.5024896 C53.2779476,39.5024896 50.7396224,40.4117776 48.4913247,42.3004336 C46.4114301,44.1487936 45.2291003,46.3627376 44.8700744,49.1057856 C44.7689156,50.2165536 44.6531385,51.7524736 44.6531385,53.7707776 C44.6531385,55.5099296 44.6028515,56.7491776 44.5712759,57.5282336 C44.5560728,57.9043296 44.5455476,58.1776416 44.5455476,58.3032016 C44.4467278,60.2969776 44.1502682,61.6390096 43.6626011,62.3701776 C42.949227,63.3507136 41.6499507,63.9136896 39.9086163,64.0018736 L38.9999415,64.0462576 L38.9999415,69.6415616 L39.8267537,69.6415616 L39.8033644,70.5952336 C41.6563827,70.6898416 42.8761354,71.2125216 43.5322056,72.1959776 C44.0432621,72.9604336 44.3397217,74.2650896 44.4385415,76.1385616 C44.4385415,77.7597456 44.5584118,79.8901776 44.7075186,82.5888416 L44.8735827,85.5292816 C45.2291003,88.2396256 46.4114301,90.4529856 48.5123751,92.3159456 C50.7372835,94.1876656 53.2756087,95.0952016 56.2747034,95.0952016 C56.4343354,95.0952016 56.634314,95.0636656 56.8360469,95.0257056 C56.9038759,95.0087696 56.9728744,94.9918336 57.0190682,94.9900816 L57.9786148,94.9900816 L57.9786148,89.2137376 L57.0991766,89.1424896 C54.966656,88.9637856 53.6293721,88.0673456 52.8972866,86.3299456 C52.6826896,85.7961696 52.3254179,84.2882816 52.3254179,80.1880176 C52.3254179,76.2308336 52.1862515,73.8656336 51.8751736,72.5171776 C51.3389736,70.2004496 50.3653934,68.4630496 48.9708056,67.3335936 C50.4700606,66.2134816 51.4272682,64.5105376 51.8769278,62.1722016 C52.1926835,60.7104496 52.3254179,58.3896336 52.3254179,54.4114256 C52.3254179,50.3111616 52.6826896,48.8032736 52.9054728,48.2467216 C53.5217812,46.5764816 54.9333263,45.6397456 57.0991766,45.4569536 L57.9786148,45.3857056 L57.9786148,39.8762496 L57.3242988,39.6578336 C56.8553431,39.5024896 56.5208759,39.5024896 56.2747034,39.5024896" id="Fill-4" fill="#151921"></path>
            <polygon id="Fill-7" fill="#151921" points="115.516559 69.5867824 108.942407 46.6980704 97.1085846 46.6980704 90.5338487 69.5867824 83.3936762 46.6980704 67.9912289 46.6980704 83.017693 89.6629504 97.2956991 89.6629504 101.802235 76.9025504 102.931939 72.4010784 104.246418 76.7156704 108.755293 89.6629504 123.03096 89.6629504 138.059763 46.6980704 122.467278 46.6980704"></polygon>
            <path d="M163.043526,59.6433648 C168.86688,59.6433648 171.497593,63.9579568 171.497593,68.0868368 C171.497593,72.9644048 167.927214,76.5291408 163.043526,76.5291408 C158.159837,76.5291408 154.589458,72.9644048 154.589458,68.0868368 C154.589458,63.9579568 157.220171,59.6433648 163.043526,59.6433648 M163.794323,45.9462288 C160.601681,45.9462288 156.469375,46.8853008 153.08728,49.3240848 L152.711297,46.6960848 L139.375295,46.6960848 L139.375295,105.420789 L153.463848,105.420789 L153.463848,87.7851568 C156.656489,89.6627168 160.601681,90.4108208 163.794323,90.4108208 C176.568396,90.4108208 185.397277,81.2192448 185.397277,68.0868368 C185.773845,55.3270208 176.568396,45.9462288 163.794323,45.9462288" id="Fill-9" fill="#151921"></path>
            <path d="M247.576537,87.2231152 L247.576537,64.3344032 C247.576537,57.0162992 242.693434,51.7632192 235.366147,51.7632192 C228.041198,51.7632192 222.968641,57.2031792 222.968641,64.5212832 L222.968641,87.2231152 L219.212318,87.2231152 L219.212318,64.5212832 C219.212318,57.2031792 214.13976,51.7632192 206.812473,51.7632192 C199.487525,51.7632192 194.604421,57.0162992 194.604421,64.5212832 L194.604421,87.2231152 L190.845759,87.2231152 L190.845759,48.9483392 L194.414968,48.9483392 L194.414968,55.3273712 C197.045681,50.2611712 201.929369,48.1984832 206.812473,48.1984832 C212.636412,48.1984832 218.83575,50.8241472 221.090479,57.5792752 C223.345209,51.3871232 229.355678,48.1984832 235.366147,48.1984832 C244.75871,48.1984832 251.3352,54.7643952 251.3352,64.3344032 L251.3352,87.2231152 L247.576537,87.2231152 Z" id="Fill-12" fill="#151921"></path>
            <path d="M290.970671,66.584672 C290.970671,57.204464 284.772503,51.9508 275.380525,51.9508 C267.489555,51.9508 260.725951,57.204464 259.788039,66.584672 L290.970671,66.584672 Z M256.031716,68.08672 C256.031716,56.641488 264.671143,48.386064 275.567055,48.386064 C286.276436,48.386064 296.043229,54.95256 294.53988,69.961944 L259.975154,69.961944 C260.725951,78.968976 267.679009,84.21972 275.567055,84.21972 C280.640196,84.21972 286.461212,82.157616 289.469077,78.403664 L292.098036,80.655568 C288.341713,85.532552 281.765222,87.973672 275.567055,87.973672 C264.671143,87.78504 256.031716,80.281808 256.031716,68.08672 L256.031716,68.08672 Z" id="Fill-14" fill="#151921"></path>
            <path d="M302.054925,68.0863112 C302.054925,78.7799352 309.195098,84.0330152 317.645072,84.0330152 C326.663406,84.0330152 333.237557,77.4665192 333.237557,67.8994312 C333.237557,58.3294232 326.473953,51.9503912 317.645072,51.9503912 C309.195098,51.9503912 302.054925,57.3909352 302.054925,68.0863112 L302.054925,68.0863112 Z M337.18275,33.0001752 L337.18275,87.2234072 L333.426426,87.2234072 L333.426426,78.7799352 C330.232031,84.5959912 324.032108,87.7840472 317.457957,87.7840472 C306.74916,87.7840472 298.296847,80.6551592 298.296847,68.0863112 C298.296847,55.5151272 306.74916,48.3862392 317.457957,48.3862392 C324.032108,48.3862392 330.232031,51.2005352 333.426426,57.3909352 L333.426426,33.0001752 L337.18275,33.0001752 Z" id="Fill-16" fill="#151921"></path>
            <path d="M344.322922,87.222064 L348.079246,87.222064 L348.079246,48.760992 L344.322922,48.760992 L344.322922,87.222064 Z M349.206611,36.56532 C349.206611,40.506152 343.006104,40.506152 343.006104,36.56532 C343.006104,32.625072 349.206611,32.438192 349.206611,36.56532 L349.206611,36.56532 Z" id="Fill-18" fill="#151921"></path>
            <path d="M356.718907,68.0863112 C356.718907,77.6539832 363.671965,84.0330152 372.310808,84.0330152 C393.726648,84.0330152 393.726648,51.9503912 372.310808,51.9503912 C363.671965,52.1372712 356.718907,58.5163032 356.718907,68.0863112 L356.718907,68.0863112 Z M391.846732,48.9486312 L391.846732,87.2234072 L388.090408,87.2234072 L388.090408,79.1554472 C384.334669,85.1583832 378.698429,87.9732632 372.500261,87.9732632 C361.791464,87.9732632 352.962584,79.9053032 352.962584,68.2731912 C352.962584,56.6404952 361.791464,48.7623352 372.500261,48.7623352 C378.698429,48.7623352 384.896013,51.5766312 388.090408,57.5795672 L388.090408,49.1360952 L391.846732,49.1360952 L391.846732,48.9486312 Z" id="Fill-20" fill="#151921"></path>
        </g>
    </svg>
</a>
EOT;

            if (!class_exists(\BackWPup_Pro::class, false)) {
                $admin_footer_text .= sprintf(
                    __(
                        '<a class="backwpup-get-pro" href="%s">Get BackWPup Pro now.</a>',
                        'backwpup'
                    ),
                    __('http://backwpup.com', 'backwpup')
                );
            }

            return $admin_footer_text . $default_text;
        }

        return $admin_footer_text;
    }

    /**
     * Overrides WordPress Version in Footer.
     *
     * @param $update_footer_text string
     *
     * @return string
     */
    public function update_footer($update_footer_text)
    {
        $default_text = $update_footer_text;

        if (isset($_REQUEST['page']) && strstr((string) $_REQUEST['page'], 'backwpup')) {
			$update_footer_text = '<span class="backwpup-update-footer"><a href="' . __(
					'http://backwpup.com',
					'backwpup'
				) . '">' . BackWPup::get_plugin_data( 'Name' ) . '</a> ' . sprintf(
			// Translators: %s is the version number of the BackWPup plugin.
			__(
						'version %s',
						'backwpup'
					),
					BackWPup::get_plugin_data( 'Version' )
				) . '</span>';

            return $update_footer_text . $default_text;
        }

        return $update_footer_text;
    }

    /**
     *  Add filed for selecting user role in user section.
     *
     * @param $user WP_User
     */
    public function user_profile_fields($user)
    {
        global $wp_roles;

        if (!is_super_admin() && !current_user_can('backwpup_admin')) {
            return;
        }

        //user is admin and has BackWPup rights
        if ($user->has_cap('administrator') && $user->has_cap('backwpup_settings')) {
            return;
        }

        //get backwpup roles
        $backwpup_roles = [];

        foreach ($wp_roles->roles as $role => $role_value) {
            if (substr((string) $role, 0, 8) != 'backwpup') {
                continue;
            }
            $backwpup_roles[$role] = $role_value;
        }

        //only if user has other than backwpup role
        if (!empty($user->roles[0]) && in_array($user->roles[0], array_keys($backwpup_roles), true)) {
            return;
		} ?>
		<h3><?php echo esc_html__( BackWPup::get_plugin_data( 'name' ), 'backwpup' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText ?></h3>
		<table class="form-table">
		<tr>
			<th>
			<label for="backwpup_role"><?php esc_html_e( 'Add BackWPup Role', 'backwpup' ); ?></label>
			</th>
			<td>
			<select name="backwpup_role" id="backwpup_role" style="display:inline-block; float:none;">
				<option
				value="">
				<?php
				esc_html_e(
						'&mdash; No additional role for BackWPup &mdash;',
						'backwpup'
						);
				?>
					</option>
				<?php
				foreach ( $backwpup_roles as $role => $role_value ) {
					echo '<option value="' . $role . '" ' . selected( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							$user->has_cap( $role ),
							true,
							false
						) . '>' . $role_value['name'] . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
			</select>
			</td>
		</tr>
		</table>
		<?php
    }

    /**
     * Save for user role adding.
     *
     * @param $user_id int
     */
    public function save_profile_update($user_id)
    {
        global $wp_roles;

        if (!is_super_admin() && !current_user_can('backwpup_admin')) {
            return;
        }

        if (empty($user_id)) {
            return;
        }

        if (!isset($_POST['backwpup_role'])) {
            return;
        }

        $backwpup_role = esc_attr($_POST['backwpup_role']);

        //get BackWPup roles
        $backwpup_roles = [];

        foreach (array_keys($wp_roles->roles) as $role) {
            if (!strstr($role, 'backwpup_')) {
                continue;
            }
            $backwpup_roles[] = $role;
        }

        //get user for adding/removing role
        $user = new WP_User($user_id);
        //a admin needs no extra role
        if ($user->has_cap('administrator') && $user->has_cap('backwpup_settings')) {
            $backwpup_role = '';
        }

        //remove BackWPup role from user if it not the actual
        foreach ($user->roles as $role) {
            if (!strstr($role, 'backwpup_')) {
                continue;
            }
            if ($role !== $backwpup_role) {
                $user->remove_role($role);
            } else {
                $backwpup_role = '';
            }
        }

        //add new role to user if it not the actual
        if ($backwpup_role && in_array($backwpup_role, $backwpup_roles, true)) {
            $user->add_role($backwpup_role);
        }
    }

	public function init() {
		$restore = new Restore();
		$restore->set_hooks()->init();

		// Add menu pages.
		add_filter( 'backwpup_admin_pages', [ $this, 'admin_page_onboarding' ], 2 );
		add_filter( 'backwpup_admin_pages', [ $this, 'admin_page_backups' ], 3 );
		add_filter( 'backwpup_admin_pages', [ $this, 'admin_page_jobs' ], 4 );
		add_filter( 'backwpup_admin_pages', [ $this, 'admin_page_about' ], 5 );

		// Hidden menu pages.
		add_filter( 'backwpup_admin_pages', [ $this, 'admin_page_restore' ], 19 );
		add_filter( 'backwpup_admin_pages', [ $this, 'admin_page_editjob' ], 20 );
		add_filter( 'backwpup_admin_pages', [ $this, 'admin_page_first_backup' ], 21 );

		// Desactivated menu pages.
		add_filter( 'backwpup_admin_pages', [ $this, 'admin_page_logs' ], 5 );
		// add_filter( 'backwpup_admin_pages', [ $this, 'admin_page_settings' ], 7 );.

		// Add Menu.
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', [ $this, 'admin_menu' ] );
		} else {
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		}
		add_action(
			'admin_enqueue_scripts',
			[
				self::class,
				'init_general',
			]
        );
		add_action(
			'admin_enqueue_scripts',
			[
                \BackWPup_Page_Backups::class,
                'admin_print_scripts',
            ]
		);
		// add Plugin links.
		add_filter( 'plugin_row_meta', [ $this, 'plugin_links' ], 10, 2 );
		add_filter( 'plugin_action_links_backwpup/backwpup.php', [ $this, 'plugin_name_links' ], 10, 1 );
		add_filter( 'plugin_action_links_backwpup-pro/backwpup.php', [ $this, 'plugin_name_links' ], 10, 1 );
		// add more actions.
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_css' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		// Save Form posts general.
		add_action( 'admin_post_backwpup', [ $this, 'save_post_form' ] );
		// Save Form posts wizard.
		add_action( 'admin_post_backwpup_wizard', [ \BackWPup_Pro_Page_Wizard::class, 'save_post_form' ] );
		// Save form posts for support.
		add_action( 'admin_post_backwpup_support', [ \BackWPup_Pro_Page_Support::class, 'save_post_form' ] );
		// Admin Footer Text replacement.
		add_filter( 'admin_footer_text', [ $this, 'admin_footer_text' ], 100 );
		add_filter( 'update_footer', [ $this, 'update_footer' ], 100 );
		// User Profile fields.
		add_action( 'show_user_profile', [ $this, 'user_profile_fields' ] );
		add_action( 'edit_user_profile', [ $this, 'user_profile_fields' ] );
		add_action( 'profile_update', [ $this, 'save_profile_update' ] );
	}

    private function __clone()
    {
	}

	/**
	 * Retrieves the BackWPup API data.
	 *
	 * This function generates an array of data required for various BackWPup API endpoints,
	 * including nonces for security and REST URLs for different actions.
	 *
	 * @param object|null $job_object Optional. The job object containing the logfile information.
	 *                                If provided, the logfile basename will be included in the returned data.
	 *
	 * @return array An associative array containing the BackWPup API data.
	 *               - 'nonce_generate' (string): Nonce for BackWPup working AJAX.
	 *               - 'backupslistings' (string): REST URL for backups listings.
	 *               - 'backupspagination' (string): REST URL for backups pagination.
	 *               - 'updatejob' (string): REST URL for updating a job.
	 *               - 'nonce' (string): Nonce for WP REST API.
	 *               - 'cloudsaveandtest' (string): REST URL for cloud save and test.
	 *               - 'storagelistcompact' (string): REST URL for storage list compact.
	 *               - 'save_database_settings' (string): REST URL for saving database settings.
	 *               - 'save_files_settings' (string): REST URL for saving files settings.
	 *               - 'startbackup' (string): REST URL for starting a backup.
	 *               - 'save_excluded_tables' (string): REST URL for saving excluded tables.
	 *               - 'backups_bulk_actions' (string): REST URL for processing bulk actions.
	 *               - 'save_file_exclusions' (string): REST URL for saving file exclusions.
	 *               - 'cloud_is_authenticated' (string): REST URL for checking cloud authentication.
	 *               - 'authenticate_cloud' (string): REST URL for authenticating cloud.
	 *               - 'delete_auth_cloud' (string): REST URL for deleting cloud authentication.
	 *               - 'getblock' (string): REST URL for getting a block.
	 *               - 'backupslistingslength' (int): Length of backups listings.
	 *               - 'logfile' (string): Optional. Basename of the logfile if $job_object is provided.
	 */
	private function get_backwpup_api_data( $job_object = null ) {
		$data = [
			'nonce_generate'         => wp_create_nonce( 'backwpupworking_ajax_nonce' ),
			'backupslistings'        => rest_url( 'backwpup/v1/backups' ),
			'backupspagination'      => rest_url( 'backwpup/v1/pagination' ),
			'updatejob'              => rest_url( 'backwpup/v1/updatejob' ),
			'nonce'                  => wp_create_nonce( 'wp_rest' ),
			'cloudsaveandtest'       => rest_url( 'backwpup/v1/cloudsaveandtest' ),
			'storagelistcompact'     => rest_url( 'backwpup/v1/storagelistcompact' ),
			'save_database_settings' => rest_url( 'backwpup/v1/save_database_settings' ),
			'save_files_settings'    => rest_url( 'backwpup/v1/save_files_settings' ),
			'startbackup'            => rest_url( 'backwpup/v1/startbackup' ),
			'save_excluded_tables'   => rest_url( 'backwpup/v1/save_excluded_tables' ),
			'backups_bulk_actions'   => rest_url( 'backwpup/v1/process_bulk_actions' ),
			'save_file_exclusions'   => rest_url( 'backwpup/v1/save_files_exclusions' ),
			'cloud_is_authenticated' => rest_url( 'backwpup/v1/cloud_is_authenticated' ),
			'authenticate_cloud'     => rest_url( 'backwpup/v1/authenticate_cloud' ),
			'delete_auth_cloud'      => rest_url( 'backwpup/v1/delete_auth_cloud' ),
			'getblock'               => rest_url( 'backwpup/v1/getblock' ),
			'license_update'         => rest_url( 'backwpup/v1/license_update' ),
			'save_site_option'       => rest_url( 'backwpup/v1/save_site_option' ),
			'backupslistingslength'  => 10,
		];

		if ( $job_object ) {
			$data['logfile'] = basename( (string) $job_object->logfile );
		}

		return $data;
	}
}
