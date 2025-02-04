<?php
/**
 * Class to display BackWPup in Adminbar.
 */
class BackWPup_Adminbar
{
    /**
     * @var BackWPup_Admin
     */
    private $admin;

    public function __construct(BackWPup_Admin $admin)
    {
        $this->admin = $admin;
    }

    public function init()
    {
        BackWPup::load_text_domain();

        add_action('admin_bar_menu', [$this, 'adminbar'], 100);
        add_action('wp_head', [$this->admin, 'admin_css']);
    }

    /**
     * @global $wp_admin_bar WP_Admin_Bar
     */
    public function adminbar()
    {
        if (!is_admin_bar_showing()) {
            return;
        }

        /** @var WP_Admin_Bar $wp_admin_bar */
        global $wp_admin_bar;

		$menu_title = '<span class="ab-icon"></span>';
		$menu_herf  = network_admin_url( 'admin.php?page=backwpup' );
		if ( file_exists( BackWPup::get_plugin_data( 'running_file' ) ) && current_user_can( 'backwpup_jobs_start' ) ) {
			$menu_title = '<span class="ab-icon"></span><span class="ab-label">' . esc_html( BackWPup::get_plugin_data( 'name' ) ) . ' <span id="backwpup-adminbar-running">' . esc_html__( 'running', 'backwpup' ) . '</span></span>';
			$menu_herf  = network_admin_url( 'admin.php?page=backwpup' );
		}

        if (current_user_can('backwpup')) {
            $wp_admin_bar->add_menu([
                'id' => 'backwpup',
                'title' => $menu_title,
                'href' => $menu_herf,
                'meta' => ['title' => BackWPup::get_plugin_data('name')],
            ]);
        }

        if (file_exists(BackWPup::get_plugin_data('running_file')) && current_user_can('backwpup_jobs_start')) {
            $wp_admin_bar->add_menu([
                'id' => 'backwpup_working',
				'parent' => 'backwpup_jobs',
				'title'  => __( 'Now Running', 'backwpup' ),
				'href'   => network_admin_url( 'admin.php?page=backwpup' ),
			]
				);
			$wp_admin_bar->add_menu(
				[
					'id'     => 'backwpup_working_abort',
					'parent' => 'backwpup_working',
					'title'  => __( 'Abort!', 'backwpup' ),
					'href'   => wp_nonce_url( network_admin_url( 'admin.php?page=backwpup&action=abort' ), 'abort-job' ),
				]
				);
		}

        if (current_user_can('backwpup_logs')) {
            $wp_admin_bar->add_menu([
                'id' => 'backwpup_logs',
				'parent' => 'backwpup',
				'title'  => __( 'Accessing your logs', 'backwpup' ),
				'href'   => network_admin_url( 'admin.php?page=backwpuplogs' ),
			]
				);
		}

        if (current_user_can('backwpup_backups')) {
            $wp_admin_bar->add_menu([
                'id' => 'backwpup_backups',
				'parent' => 'backwpup',
				'title'  => __( 'Backups', 'backwpup' ),
				'href'   => network_admin_url( 'admin.php?page=backwpup' ),
			]
				);
		}
	}
}
