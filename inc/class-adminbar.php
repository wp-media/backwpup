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
        $menu_herf = network_admin_url('admin.php?page=backwpup');
        if (file_exists(BackWPup::get_plugin_data('running_file')) && current_user_can('backwpup_jobs_start')) {
            $menu_title = '<span class="ab-icon"></span><span class="ab-label">' . esc_html(BackWPup::get_plugin_data('name')) . ' <span id="backwpup-adminbar-running">' . esc_html__('running', 'backwpup') . '</span></span>';
            $menu_herf = network_admin_url('admin.php?page=backwpupjobs');
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
                'title' => __('Now Running', 'backwpup'),
                'href' => network_admin_url('admin.php?page=backwpupjobs'),
            ]);
            $wp_admin_bar->add_menu([
                'id' => 'backwpup_working_abort',
                'parent' => 'backwpup_working',
                'title' => __('Abort!', 'backwpup'),
                'href' => wp_nonce_url(network_admin_url('admin.php?page=backwpup&action=abort'), 'abort-job'),
            ]);
        }

        if (current_user_can('backwpup_jobs')) {
            $wp_admin_bar->add_menu([
                'id' => 'backwpup_jobs',
                'parent' => 'backwpup',
                'title' => __('Jobs', 'backwpup'),
                'href' => network_admin_url('admin.php?page=backwpupjobs'),
            ]);
        }

        if (current_user_can('backwpup_jobs_edit')) {
            $wp_admin_bar->add_menu([
                'id' => 'backwpup_jobs_new',
                'parent' => 'backwpup_jobs',
                'title' => __('Add new', 'backwpup'),
                'href' => network_admin_url('admin.php?page=backwpupeditjob&tab=job'),
            ]);
        }

        if (current_user_can('backwpup_logs')) {
            $wp_admin_bar->add_menu([
                'id' => 'backwpup_logs',
                'parent' => 'backwpup',
                'title' => __('Logs', 'backwpup'),
                'href' => network_admin_url('admin.php?page=backwpuplogs'),
            ]);
        }

        if (current_user_can('backwpup_backups')) {
            $wp_admin_bar->add_menu([
                'id' => 'backwpup_backups',
                'parent' => 'backwpup',
                'title' => __('Backups', 'backwpup'),
                'href' => network_admin_url('admin.php?page=backwpupbackups'),
            ]);
        }

        //add jobs
        $jobs = (array) BackWPup_Option::get_job_ids();

        foreach ($jobs as $jobid) {
            if (current_user_can('backwpup_jobs_edit')) {
                $name = BackWPup_Option::get($jobid, 'name');
                $wp_admin_bar->add_menu([
                    'id' => 'backwpup_jobs_' . $jobid,
                    'parent' => 'backwpup_jobs',
                    'title' => $name,
                    'href' => wp_nonce_url(network_admin_url('admin.php?page=backwpupeditjob&tab=job&jobid=' . $jobid), 'edit-job'),
                ]);
            }
            if (current_user_can('backwpup_jobs_start')) {
                $url = BackWPup_Job::get_jobrun_url('runnowlink', $jobid);
                $wp_admin_bar->add_menu([
                    'id' => 'backwpup_jobs_runnow_' . $jobid,
                    'parent' => 'backwpup_jobs_' . $jobid,
                    'title' => __('Run Now', 'backwpup'),
                    'href' => esc_url($url['url']),
                ]);
            }
        }
    }
}
