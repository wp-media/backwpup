<?php

use Inpsyde\BackWPup\Settings;
use Inpsyde\BackWPup\Settings\SettingUpdatable;

class BackWPup_Page_Settings
{
    public const LICENSE_INSTANCE_KEY = 'license_instance_key';
    public const LICENSE_API_KEY = 'license_api_key';
    public const LICENSE_PRODUCT_ID = 'license_product_id';
    public const LICENSE_STATUS = 'license_status';

    /**
     * @var Settings\SettingTab
     */
    private $settings_views;

    /**
     * @var SettingUpdatable[]
     */
    private $settings_updaters;

    /**
     * @param Settings\SettingTab[] $settings_views
     * @param SettingUpdatable[]    $settings_updaters
     */
    public function __construct(
        array $settings_views,
        array $settings_updaters
    ) {
        $this->settings_views = array_filter(
            $settings_views,
            function ($setting) {
                return $setting instanceof Settings\SettingTab;
            }
        );
        $this->settings_updaters = array_filter(
            $settings_updaters,
            function ($setting) {
                return $setting instanceof SettingUpdatable;
            }
        );
    }

    /**
     * @return array
     */
    public static function get_information()
    {
        global $wpdb;

        $information = [];

        // Wordpress version
        $information['wpversion']['label'] = __('WordPress version', 'backwpup');
        $information['wpversion']['value'] = BackWPup::get_plugin_data('wp_version');

        // BackWPup version
        if (!BackWPup::is_pro()) {
            $information['bwuversion']['label'] = esc_html__('BackWPup version', 'backwpup');
            $information['bwuversion']['value'] = BackWPup::get_plugin_data('Version');
            $information['bwuversion']['html'] = BackWPup::get_plugin_data('Version') .
                                                 ' <a href="' . __('http://backwpup.com', 'backwpup') . '">' .
                                                 esc_html__('Get pro.', 'backwpup') . '</a>';
        } else {
            $information['bwuversion']['label'] = __('BackWPup Pro version', 'backwpup');
            $information['bwuversion']['value'] = BackWPup::get_plugin_data('Version');
        }

        // PHP version
        $information['phpversion']['label'] = esc_html__('PHP version', 'backwpup');
        $bit = '';
        if (PHP_INT_SIZE === 4) {
            $bit = ' (32bit)';
        } elseif (PHP_INT_SIZE === 8) {
            $bit = ' (64bit)';
        }
        $information['phpversion']['value'] = PHP_VERSION . ' ' . $bit;

        // MySQL version
        $information['mysqlversion']['label'] = esc_html__('MySQL version', 'backwpup');
        $information['mysqlversion']['value'] = $wpdb->get_var('SELECT VERSION() AS version');

        // Curl version
        $information['curlversion']['label'] = esc_html__('cURL version', 'backwpup');
        if (function_exists('curl_version')) {
            $curl_version = curl_version();
            $information['curlversion']['value'] = $curl_version['version'];
            $information['curlsslversion']['label'] = __('cURL SSL version', 'backwpup');
            $information['curlsslversion']['value'] = $curl_version['ssl_version'];
        } else {
            $information['curlversion']['value'] = esc_html__('unavailable', 'backwpup');
        }

        // WP cron URL
        $information['wpcronurl']['label'] = esc_html__('WP-Cron url', 'backwpup');
        $information['wpcronurl']['value'] = site_url('wp-cron.php');

        // Response test
        $server_connect = [];
        $server_connect['label'] = __('Server self connect', 'backwpup');

        $raw_response = BackWPup_Job::get_jobrun_url('test');
        $response_code = wp_remote_retrieve_response_code($raw_response);
        $response_body = wp_remote_retrieve_body($raw_response);
        if (strstr($response_body, 'BackWPup test request') === false) {
            $server_connect['value'] = esc_html__('Not expected HTTP response:', 'backwpup') . "\n";
            $server_connect['html'] = wp_kses(
                __('<strong>Not expected HTTP response:</strong><br>', 'backwpup'),
                ['strong' => []]
            );
            if (!$response_code) {
                $server_connect['value'] .= sprintf(
                    wp_kses_post(__('WP Http Error: %s', 'backwpup')),
                    $raw_response->get_error_message()
                ) . "\n";
                $server_connect['html'] = sprintf(
                    __('WP Http Error: <code>%s</code>', 'backwpup'),
                    esc_html($raw_response->get_error_message())
                ) . '<br>';
            } else {
                $server_connect['value'] .= sprintf(__('Status-Code: %d', 'backwpup'), $response_code) . "\n";
                $server_connect['html'] .= sprintf(
                    __('Status-Code: <code>%d</code>', 'backwpup'),
                    esc_html($response_code)
                ) . '<br>';
            }
            $response_headers = wp_remote_retrieve_headers($raw_response);

            foreach ($response_headers as $key => $value) {
                $server_connect['value'] .= ucfirst($key) . ": {$value}\n";
                $server_connect['html'] .= esc_html(ucfirst($key)) . ': <code>' . esc_html(
                    $value
                ) . '</code><br>';
            }
            $content = wp_remote_retrieve_body($raw_response);
            if ($content) {
                $server_connect['value'] .= sprintf(__('Content: %s', 'backwpup'), $content);
                $server_connect['html'] .= sprintf(
                    __('Content: <code>%s</code>', 'backwpup'),
                    esc_html($content)
                );
            }
        } else {
            $server_connect['value'] = __('Response Test O.K.', 'backwpup');
        }
        $information['serverconnect'] = $server_connect;

        // Document root
        $information['docroot']['label'] = 'Document root';
        $information['docroot']['value'] = $_SERVER['DOCUMENT_ROOT'];

        // Temp folder
        $information['tmpfolder']['label'] = esc_html__('Temp folder', 'backwpup');
        if (!is_dir(BackWPup::get_plugin_data('TEMP'))) {
            $information['tmpfolder']['value'] = sprintf(
                esc_html__('Temp folder %s doesn\'t exist.', 'backwpup'),
                BackWPup::get_plugin_data('TEMP')
            );
        } elseif (!is_writable(BackWPup::get_plugin_data('TEMP'))) {
            $information['tmpfolder']['value'] = sprintf(
                esc_html__('Temporary folder %s is not writable.', 'backwpup'),
                BackWPup::get_plugin_data('TEMP')
            );
        } else {
            $information['tmpfolder']['value'] = BackWPup::get_plugin_data('TEMP');
        }

        // Log folder
        $information['logfolder']['label'] = esc_html__('Log folder', 'backwpup');
        $log_folder = BackWPup_File::get_absolute_path(
            get_site_option('backwpup_cfg_logfolder')
        );
        if (!is_dir($log_folder)) {
            $information['logfolder']['value'] = sprintf(
                esc_html__('Log folder %s does not exist.', 'backwpup'),
                $log_folder
            );
        } elseif (!is_writable($log_folder)) {
            $information['logfolder']['value'] = sprintf(
                esc_html__('Log folder %s is not writable.', 'backwpup'),
                $log_folder
            );
        } else {
            $information['logfolder']['value'] = $log_folder;
        }

        // Server
        $information['server']['label'] = esc_html__('Server', 'backwpup');
        $information['server']['value'] = $_SERVER['SERVER_SOFTWARE'];

        // OS
        $information['os']['label'] = esc_html__('Operating System', 'backwpup');
        $information['os']['value'] = PHP_OS;

        // PHP SAPI
        $information['phpsapi']['label'] = esc_html__('PHP SAPI', 'backwpup');
        $information['phpsapi']['value'] = PHP_SAPI;

        // PHP user
        $information['phpuser']['label'] = esc_html__('Current PHP user', 'backwpup');
        if (function_exists('get_current_user')) {
            $information['phpuser']['value'] = get_current_user();
        } else {
            $information['phpuser']['value'] = esc_html__('Function Disabled', 'backwpup');
        }

        // Maximum execution time
        $information['maxexectime']['label'] = esc_html__('Maximum execution time', 'backwpup');
        $information['maxexectime']['value'] = sprintf(
            __('%d seconds', 'backwpup'),
            ini_get('max_execution_time')
        );

        // BackWPup Maximum script execution time
        $information['jobmaxexecutiontime']['label'] = esc_html__(
            'BackWPup maximum script execution time',
            'backwpup'
        );
        $information['jobmaxexecutiontime']['value'] = sprintf(
            __('%d seconds', 'backwpup'),
            absint(get_site_option('backwpup_cfg_jobmaxexecutiontime'))
        );

        // Alternate WP cron
        $information['altwpcron']['label'] = esc_html__('Alternative WP Cron', 'backwpup');
        if (defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON) {
            $information['altwpcron']['value'] = esc_html__('On', 'backwpup');
        } else {
            $information['altwpcron']['value'] = esc_html__('Off', 'backwpup');
        }

        // Disable WP cron
        $information['disablewpcron']['label'] = esc_html__('Disabled WP Cron', 'backwpup');
        if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            $information['disablewpcron']['value'] = esc_html__('On', 'backwpup');
        } else {
            $information['disablewpcron']['value'] = esc_html__('Off', 'backwpup');
        }

        // CHMOD dir
        $information['chmoddir']['label'] = esc_html__('CHMOD Dir', 'backwpup');
        if (defined('FS_CHMOD_DIR')) {
            $information['chmoddir']['value'] = FS_CHMOD_DIR;
        } else {
            $information['chmoddir']['value'] = '0755';
        }

        // Server time
        $information['servertime']['label'] = esc_html__('Server Time', 'backwpup');
        $now = localtime(time(), true);
        $information['servertime']['value'] = $now['tm_hour'] . ':' . $now['tm_min'];

        // Blog time
        $information['blogtime']['label'] = esc_html__('Blog Time', 'backwpup');
        $information['blogtime']['value'] = date('H:i', current_time('timestamp'));

        // Blog timezone
        $information['blogtz']['label'] = esc_html__('Blog Timezone', 'backwpup');
        $information['blogtz']['value'] = get_option('timezone_string');

        // Blog time offset
        $information['blogoffset']['label'] = esc_html__('Blog Time offset', 'backwpup');
        $information['blogoffset']['value'] = sprintf(
            esc_html__('%s hours', 'backwpup'),
            (int) get_option('gmt_offset')
        );

        // Blog language
        $information['bloglang']['label'] = esc_html__('Blog language', 'backwpup');
        $information['bloglang']['value'] = get_bloginfo('language');

        // MySQL encoding
        $information['mysqlencoding']['label'] = esc_html__('MySQL Client encoding', 'backwpup');
        $information['mysqlencoding']['value'] = defined('DB_CHARSET') ? DB_CHARSET : '';

        // PHP memory limitesc_html__
        $information['phpmemlimit']['label'] = esc_html__('PHP Memory limit', 'backwpup');
        $information['phpmemlimit']['value'] = ini_get('memory_limit');

        // WP memory limit
        $information['wpmemlimit']['label'] = esc_html__('WP memory limit', 'backwpup');
        $information['wpmemlimit']['value'] = WP_MEMORY_LIMIT;

        // WP maximum memory limit
        $information['wpmaxmemlimit']['label'] = esc_html__('WP maximum memory limit', 'backwpup');
        $information['wpmaxmemlimit']['value'] = WP_MAX_MEMORY_LIMIT;

        // Memory in use
        $information['memusage']['label'] = esc_html__('Memory in use', 'backwpup');
        $information['memusage']['value'] = size_format(@memory_get_usage(true), 2);

        // Disabled PHP functions
        $disabled = esc_html(ini_get('disable_functions'));
        if (!empty($disabled)) {
            $information['disabledfunctions']['label'] = esc_html__('Disabled PHP Functions:', 'backwpup');
            $information['disabledfunctions']['value'] = implode(', ', explode(',', $disabled));
        }

        // Loaded PHP extensions
        $information['loadedextensions']['label'] = esc_html__('Loaded PHP Extensions:', 'backwpup');
        $extensions = get_loaded_extensions();
        sort($extensions);
        $information['loadedextensions']['value'] = implode(', ', $extensions);

        return $information;
    }

    public function admin_print_scripts()
    {
        $suffix = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? '' : '.min';

        wp_enqueue_script(
            'backwpuppagesettings',
            untrailingslashit(BackWPup::get_plugin_data('URL')) . "/assets/js/page_settings{$suffix}.js",
            [
                'jquery',
                'backwpupgeneral',
                'backwpup_clipboard',
            ],
            filemtime(untrailingslashit(BackWPup::get_plugin_data('plugindir')) . "/assets/js/page_settings{$suffix}.js"),
            true
        );

        if (\BackWPup::is_pro()) {
            wp_enqueue_script(
                'backwpuppagesettings-encryption',
                untrailingslashit(BackWPup::get_plugin_data('URL')) . "/assets/js/pro/settings-encryption{$suffix}.js",
                [
                    'underscore',
                    'jquery',
                    'backwpuppagesettings',
                    'thickbox',
                ],
                filemtime(untrailingslashit(BackWPup::get_plugin_data('plugindir')) . "/assets/js/pro/settings-encryption{$suffix}.js"),
                true
            );

            wp_localize_script(
                'backwpuppagesettings-encryption',
                'settingsEncryptionVariables',
                [
                    'validPublicKey' => esc_html__('Public key is valid.', 'backwpup'),
                    'invalidPublicKey' => esc_html__('Public key is invalid.', 'backwpup'),
                    'privateKeyMissed' => esc_html__('Please enter your private key.', 'backwpup'),
                    'publicKeyMissed' => esc_html__(
                        'Please enter a public key first, or generate a key pair.',
                        'backwpup'
                    ),
                    'mustDownloadPrivateKey' => esc_html__(
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
    }

    public function save_post_form()
    {
        if (!current_user_can('backwpup_settings')) {
            return;
        }

        // Set default options if button clicked.
        if (isset($_POST['default_settings']) && $_POST['default_settings']) { // phpcs:ignore
            delete_site_option('backwpup_cfg_showadminbar');
            delete_site_option('backwpup_cfg_showfoldersize');
            delete_site_option('backwpup_cfg_jobstepretry');
            delete_site_option('backwpup_cfg_jobmaxexecutiontime');
            delete_site_option('backwpup_cfg_loglevel');
            delete_site_option('backwpup_cfg_jobwaittimems');
            delete_site_option('backwpup_cfg_jobrunauthkey');
            delete_site_option('backwpup_cfg_jobdooutput');
            delete_site_option('backwpup_cfg_windows');
            delete_site_option('backwpup_cfg_maxlogs');
            delete_site_option('backwpup_cfg_gzlogs');
            delete_site_option('backwpup_cfg_protectfolders');
            delete_site_option('backwpup_cfg_authentication');
            delete_site_option('backwpup_cfg_logfolder');
            delete_site_option('backwpup_cfg_dropboxappkey');
            delete_site_option('backwpup_cfg_dropboxappsecret');
            delete_site_option('backwpup_cfg_dropboxsandboxappkey');
            delete_site_option('backwpup_cfg_dropboxsandboxappsecret');
            delete_site_option('backwpup_cfg_sugarsynckey');
            delete_site_option('backwpup_cfg_sugarsyncsecret');
            delete_site_option('backwpup_cfg_sugarsyncappid');
            delete_site_option('backwpup_cfg_hash');
            delete_site_option('backwpup_cfg_keepplugindata');

            foreach ($this->settings_updaters as $setting) {
                $setting->reset();
            }

            delete_site_option(self::LICENSE_INSTANCE_KEY);
            delete_site_option(self::LICENSE_API_KEY);
            delete_site_option(self::LICENSE_PRODUCT_ID);
            delete_site_option(self::LICENSE_STATUS);

            BackWPup_Option::default_site_options();
            BackWPup_Admin::message(__('Settings reset to default', 'backwpup'));

            return;
        }

        foreach ($this->settings_updaters as $setting) {
            $setting->update();
        }

        update_site_option('backwpup_cfg_showadminbar', !empty($_POST['showadminbarmenu']));
        update_site_option('backwpup_cfg_showfoldersize', !empty($_POST['showfoldersize']));

        if (empty($_POST['jobstepretry']) || 100 < $_POST['jobstepretry'] || 1 > $_POST['jobstepretry']) {
            $_POST['jobstepretry'] = 3;
        }

        update_site_option('backwpup_cfg_jobstepretry', absint($_POST['jobstepretry']));

        if ((int) $_POST['jobmaxexecutiontime'] > 300) {
            $_POST['jobmaxexecutiontime'] = 300;
        }

        update_site_option('backwpup_cfg_jobmaxexecutiontime', absint($_POST['jobmaxexecutiontime']));
        update_site_option(
            'backwpup_cfg_loglevel',
            in_array(
                $_POST['loglevel'],
                ['normal_translated', 'normal', 'debug_translated', 'debug'],
                true
            ) ? $_POST['loglevel'] : 'normal_translated'
        );
        update_site_option('backwpup_cfg_jobwaittimems', absint($_POST['jobwaittimems']));
        update_site_option('backwpup_cfg_jobdooutput', !empty($_POST['jobdooutput']));
        update_site_option('backwpup_cfg_windows', !empty($_POST['windows']));

        update_site_option('backwpup_cfg_maxlogs', absint($_POST['maxlogs']));
        update_site_option('backwpup_cfg_gzlogs', !empty($_POST['gzlogs']));
        update_site_option('backwpup_cfg_protectfolders', !empty($_POST['protectfolders']));

        $_POST['jobrunauthkey'] = preg_replace('/[^a-zA-Z0-9]/', '', trim($_POST['jobrunauthkey']));

        update_site_option('backwpup_cfg_jobrunauthkey', $_POST['jobrunauthkey']);

        $_POST['logfolder'] = trailingslashit(
            str_replace('\\', '/', trim(stripslashes(sanitize_text_field($_POST['logfolder']))))
        );

        //set def. folders
        if (empty($_POST['logfolder']) || $_POST['logfolder'] === '/') {
            delete_site_option('backwpup_cfg_logfolder');
            BackWPup_Option::default_site_options();
        } else {
            update_site_option('backwpup_cfg_logfolder', $_POST['logfolder']);
        }

        $authentication = get_site_option(
            'backwpup_cfg_authentication',
            [
                'method' => '',
                'basic_user' => '',
                'basic_password' => '',
                'user_id' => 0,
                'query_arg' => '',
            ]
        );
        $authentication['method'] = (in_array(
            $_POST['authentication_method'],
            ['user', 'basic', 'query_arg'],
            true
        )) ? $_POST['authentication_method'] : '';
        $authentication['basic_user'] = sanitize_text_field($_POST['authentication_basic_user']);
        $authentication['basic_password'] = BackWPup_Encryption::encrypt(
            (string) $_POST['authentication_basic_password']
        );
        $authentication['query_arg'] = sanitize_text_field($_POST['authentication_query_arg']);
        $authentication['user_id'] = absint($_POST['authentication_user_id']);
        update_site_option('backwpup_cfg_authentication', $authentication);
        delete_site_transient('backwpup_cookies');

        update_site_option('backwpup_cfg_keepplugindata', !empty($_POST['keepplugindata']));

        do_action('backwpup_page_settings_save');

        BackWPup_Admin::message(__('Settings saved', 'backwpup'));
    }

    public function page()
    {
        ?>
		<div class="wrap" id="backwpup-page">
			<h1>
				<?php printf(
            esc_html__('%s &rsaquo; Settings', 'backwpup'),
            BackWPup::get_plugin_data('name')
        ); ?>
			</h1>
			<?php
            $tabs = [];
        $tabs['general'] = esc_html__('General', 'backwpup');
        $tabs['job'] = esc_html__('Jobs', 'backwpup');
        if (BackWPup::is_pro()) {
            $tabs['encryption'] = esc_html__('Encryption', 'backwpup');
        }
        $tabs['log'] = esc_html__('Logs', 'backwpup');
        $tabs['net'] = esc_html__('Network', 'backwpup');
        $tabs['apikey'] = esc_html__('API Keys', 'backwpup');
        $tabs['information'] = esc_html__('Information', 'backwpup');
        if (BackWPup::is_pro()) {
            $tabs['license'] = esc_html__('License', 'backwpup');
        }
        $tabs = apply_filters('backwpup_page_settings_tab', $tabs);
        echo '<h2 class="nav-tab-wrapper">';

        foreach ($tabs as $id => $name) {
            echo '<a href="#backwpup-tab-' . esc_attr($id) . '" class="nav-tab">' . esc_attr($name) . '</a>';
        }
        echo '</h2>';
        BackWPup_Admin::display_messages(); ?>

			<form id="settingsform" action="<?php echo admin_url('admin-post.php'); ?>" method="post">
				<?php wp_nonce_field('backwpupsettings_page'); ?>
				<?php wp_nonce_field('backwpup_ajax_nonce', 'backwpupajaxnonce', false); ?>
				<input type="hidden" name="page" value="backwpupsettings"/>
				<input type="hidden" name="action" value="backwpup"/>
				<input type="hidden" name="anchor" value="#backwpup-tab-general"/>

				<div class="table ui-tabs-hide" id="backwpup-tab-general">

					<h3 class="title"><?php esc_html_e('Display Settings', 'backwpup'); ?></h3>
					<p><?php _e('Do you want to see BackWPup in the WordPress admin bar?', 'backwpup'); ?></p>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e('Admin bar', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e('Admin Bar', 'backwpup'); ?>
										</span>
									</legend>
									<label for="showadminbarmenu">
										<input name="showadminbarmenu" type="checkbox" id="showadminbarmenu"
										       value="1" <?php checked(
            get_site_option('backwpup_cfg_showadminbar'),
            true
        ); ?> />
										<?php esc_html_e('Show BackWPup links in admin bar.', 'backwpup'); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('Folder sizes', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e('Folder sizes', 'backwpup'); ?>
										</span>
									</legend>
									<label for="showfoldersize">
										<input name="showfoldersize" type="checkbox" id="showfoldersize"
										       value="1" <?php checked(
            get_site_option('backwpup_cfg_showfoldersize'),
            true
        ); ?> />
										<?php esc_html_e(
            'Display folder sizes in the files tab when editing a job. (Might increase loading time of files tab.)',
            'backwpup'
        ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
					</table>
					<h3 class="title"><?php esc_html_e('Security', 'backwpup'); ?></h3>
					<p><?php _e('Security option for BackWPup', 'backwpup'); ?></p>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e('Protect folders', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e('Protect folders', 'backwpup'); ?>
										</span>
									</legend>
									<label for="protectfolders">
										<input name="protectfolders" type="checkbox" id="protectfolders"
										       value="1" <?php checked(
            get_site_option('backwpup_cfg_protectfolders'),
            true
        ); ?> />
										<?php echo wp_kses(
            __(
                'Protect BackWPup folders ( Temp, Log and Backups ) with <code>.htaccess</code> and <code>index.php</code>',
                'backwpup'
            ),
            ['code' => []]
        ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
					</table>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Plugin data', 'backwpup'); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
											<span>
												<?php esc_html_e('Keep plugin data', 'backwpup'); ?>
											</span>
                                    </legend>
                                    <label for="keepplugindata">
                                        <input name="keepplugindata" type="checkbox"
                                               id="keepplugindata"
                                               value="1" <?php checked(
            get_site_option('backwpup_cfg_keepplugindata'),
            true
        ); ?> />
                                        <?php esc_html_e(
            'Keep BackWPup data stored in the database after uninstall',
            'backwpup'
        ); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
					<?php do_action('backwpup_page_settings_tab_generel'); ?>
				</div>

				<div class="table ui-tabs-hide" id="backwpup-tab-log">
					<p>
						<?php esc_html_e(
            'Every time BackWPup runs a backup job, a log file is being generated. Choose where to store your log files and how many of them.',
            'backwpup'
        ); ?>
					</p>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="logfolder">
									<?php esc_html_e('Log file folder', 'backwpup'); ?>
								</label>
							</th>
							<td>
								<input name="logfolder" type="text" id="logfolder" value="<?php echo esc_attr(
            get_site_option('backwpup_cfg_logfolder')
        ); ?>" class="regular-text code"/>
								<p class="description">
									<?php printf(
            wp_kses(
                __(
                    'You can use absolute or relative path! Relative path is relative to %s.',
                    'backwpup'
                ),
                ['code' => []]
            ),
            '<code>' . trailingslashit(
                str_replace('\\', '/', WP_CONTENT_DIR)
            ) . '</code>'
        ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="maxlogs">
									<?php esc_html_e('Maximum log files', 'backwpup'); ?>
								</label>
							</th>
							<td>
								<input name="maxlogs" type="number" min="0" step="1" id="maxlogs"
								       value="<?php echo absint(
            get_site_option('backwpup_cfg_maxlogs')
        ); ?>" class="small-text"/>
								<?php esc_html_e('Maximum log files in folder.', 'backwpup'); ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('Compression', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php _e(
            'Compression',
            'backwpup'
        ); ?></span></legend>
									<label for="gzlogs">
										<input name="gzlogs" type="checkbox" id="gzlogs" value="1" <?php checked(
            get_site_option('backwpup_cfg_gzlogs'),
            true
        ); ?><?php if (!function_exists('gzopen')) {
            echo ' disabled="disabled"';
        } ?> />
										<?php esc_html_e('Compress log files with GZip.', 'backwpup'); ?>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('Logging Level', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e('Logging Level', 'backwpup'); ?>
										</span>
									</legend>
									<label for="loglevel">
										<select name="loglevel" size="1">
											<option value="normal_translated" <?php selected(
            get_site_option('backwpup_cfg_loglevel', 'normal_translated'),
            'normal_translated'
        ); ?>><?php esc_html_e('Normal (translated)', 'backwpup'); ?></option>
											<option value="normal" <?php selected(
            get_site_option('backwpup_cfg_loglevel'),
            'normal'
        ); ?>><?php esc_html_e('Normal (not translated)', 'backwpup'); ?></option>
											<option value="debug_translated" <?php selected(
            get_site_option('backwpup_cfg_loglevel'),
            'debug_translated'
        ); ?>><?php _e('Debug (translated)', 'backwpup'); ?></option>
											<option value="debug" <?php selected(
            get_site_option('backwpup_cfg_loglevel'),
            'debug'
        ); ?>><?php esc_html_e('Debug (not translated)', 'backwpup'); ?></option>
										</select>
									</label>
									<p class="description">
										<?php esc_html_e(
            'Debug log has much more information than normal logs. It is for support and should be handled carefully. For support is the best to use a not translated log file. Usage of not translated logs can reduce the PHP memory usage too.',
            'backwpup'
        ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
					</table>

				</div>
				<div class="table ui-tabs-hide" id="backwpup-tab-job">

					<p><?php _e(
            'There are a couple of general options for backup jobs. Set them here.',
            'backwpup'
        ); ?></p>
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="jobstepretry">
									<?php esc_html_e('Maximum number of retries for job steps', 'backwpup'); ?>
								</label>
							</th>
							<td>
								<input name="jobstepretry" type="number" min="1" step="1" max="99" id="jobstepretry"
								       value="<?php echo absint(get_site_option('backwpup_cfg_jobstepretry')); ?>" class="small-text"/>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('Maximum script execution time', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e('Maximum PHP Script execution time', 'backwpup'); ?>
										</span>
									</legend>
									<label for="jobmaxexecutiontime">
										<input name="jobmaxexecutiontime" type="number" min="0" step="1" max="300"
										       id="jobmaxexecutiontime" value="<?php echo absint(get_site_option('backwpup_cfg_jobmaxexecutiontime')); ?>" class="small-text"/>
										<?php _e('seconds.', 'backwpup'); ?>
										<p class="description">
											<?php echo wp_kses(
            __(
                'Job will restart before hitting maximum execution time. Restarts will be disabled on CLI usage. If <code>ALTERNATE_WP_CRON</code> has been defined, WordPress Cron will be used for restarts, so it can take a while. 0 means no maximum.',
                'backwpup'
            ),
            ['code' => []]
        ); ?>
										</p>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="jobrunauthkey">
									<?php esc_html_e('Key to start jobs externally with an URL', 'backwpup'); ?>
								</label>
							</th>
							<td>
								<input name="jobrunauthkey" type="text" id="jobrunauthkey" value="<?php echo esc_attr(
            get_site_option('backwpup_cfg_jobrunauthkey')
        ); ?>" class="text code"/>
								<p class="description">
									<?php esc_html_e(
            'Will be used to protect job starts from unauthorized person.',
            'backwpup'
        ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('Reduce server load', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php _e('Reduce server load', 'backwpup'); ?>
										</span>
									</legend>
									<label for="jobwaittimems">
										<select name="jobwaittimems" size="1">
											<option value="0" <?php selected(
            get_site_option('backwpup_cfg_jobwaittimems'),
            0
        ); ?>><?php esc_html_e('disabled', 'backwpup'); ?></option>
											<option value="10000" <?php selected(
            get_site_option('backwpup_cfg_jobwaittimems'),
            10000
        ); ?>><?php esc_html_e('minimum', 'backwpup'); ?></option>
											<option value="30000" <?php selected(
            get_site_option('backwpup_cfg_jobwaittimems'),
            30000
        ); ?>><?php esc_html_e('medium', 'backwpup'); ?></option>
											<option value="90000" <?php selected(
            get_site_option('backwpup_cfg_jobwaittimems'),
            90000
        ); ?>><?php esc_html_e('maximum', 'backwpup'); ?></option>
										</select>
									</label>
									<p class="description">
										<?php esc_html_e(
            'This adds short pauses to the process. Can be used to reduce the CPU load.',
            'backwpup'
        ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('Empty output on working', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e(
            'Enable an empty Output on backup working.',
            'backwpup'
        ); ?>
										</span>
									</legend>
									<label for="jobdooutput">
										<input name="jobdooutput" type="checkbox" id="jobdooutput"
										       value="1" <?php checked(
            get_site_option('backwpup_cfg_jobdooutput'),
            true
        ); ?> />
										<?php esc_html_e('Enable an empty Output on backup working.', 'backwpup'); ?>
									</label>
									<p class="description">
										<?php esc_html_e(
            'This do an empty output on job working. This can help in some situations or can break the working. You must test it.',
            'backwpup'
        ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e('Windows IIS compatibility', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e(
            'Enable compatibility with IIS on Windows.',
            'backwpup'
        ); ?>
										</span>
									</legend>
									<label for="windows">
										<input name="windows" type="checkbox" id="windows" value="1"<?php checked(
            get_site_option('backwpup_cfg_windows'),
            true
        ); ?> />
										<?php esc_html_e('Enable compatibility with IIS on Windows.', 'backwpup'); ?>
									</label>
									<p class="description">
										<?php echo wp_kses(
            __(
                'There is a PHP bug (<a href="https://bugs.php.net/43817">bug #43817</a>), which is triggered on some versions of Windows and IIS. Checking this box will enable a workaround for that bug. Only enable if you are getting errors about &ldquo;Permission denied&rdquo; in your logs.',
                'backwpup'
            ),
            ['a' => []]
        ); ?>
									</p>
								</fieldset>
							</td>
						</tr>
					</table>

				</div>

				<?php
                foreach ($this->settings_views as $setting) {
                    $setting->tab();
                } ?>

				<div class="table ui-tabs-hide" id="backwpup-tab-net">

					<h3>
						<?php printf(
                    wp_kses(
                        __('Authentication for <code>%s</code>', 'backwpup'),
                        ['code' => []]
                    ),
                    site_url('wp-cron.php')
                ); ?>
					</h3>
					<p>
						<?php esc_html_e(
                    'If you protected your blog with HTTP basic authentication (.htaccess), or you use a Plugin to secure wp-cron.php, then use the authentication methods below.',
                    'backwpup'
                ); ?>
					</p>
					<?php
                    $authentication = get_site_option(
                    'backwpup_cfg_authentication',
                    [
                        'method' => '',
                        'basic_user' => '',
                        'basic_password' => '',
                        'user_id' => 0,
                        'query_arg' => '',
                    ]
                ); ?>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e('Authentication method', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span>
											<?php esc_html_e('Authentication method', 'backwpup'); ?>
										</span>
									</legend>
									<label for="authentication_method">
										<select name="authentication_method" id="authentication_method" size="1">
											<option value="" <?php selected(
                    $authentication['method'],
                    ''
                ); ?>><?php esc_html_e('none', 'backwpup'); ?></option>
											<option value="basic" <?php selected(
                    $authentication['method'],
                    'basic'
                ); ?>><?php esc_html_e('Basic auth', 'backwpup'); ?></option>
											<option value="user" <?php selected(
                    $authentication['method'],
                    'user'
                ); ?>><?php esc_html_e('WordPress User', 'backwpup'); ?></option>
											<option value="query_arg" <?php selected(
                    $authentication['method'],
                    'query_arg'
                ); ?>><?php esc_html_e('Query argument', 'backwpup'); ?></option>
										</select>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr class="authentication_basic" <?php if ($authentication['method'] !== 'basic') {
                    echo 'style="display:none"';
                } ?>>
							<th scope="row">
								<label for="authentication_basic_user">
									<?php esc_html_e(
                    'Basic Auth Username:',
                    'backwpup'
                ); ?>
								</label>
							</th>
							<td>
								<input name="authentication_basic_user" type="text" id="authentication_basic_user"
								       value="<?php echo esc_attr(
                    $authentication['basic_user']
                ); ?>" class="regular-text" autocomplete="off"/>
							</td>
						</tr>
						<tr class="authentication_basic" <?php if ($authentication['method'] !== 'basic') {
                    echo 'style="display:none"';
                } ?>>
							<th scope="row">
								<label for="authentication_basic_password">
									<?php esc_html_e(
                    'Basic Auth Password:',
                    'backwpup'
                ); ?>
								</label>
							</th>
							<td>
								<input name="authentication_basic_password" type="password"
								       id="authentication_basic_password" value="<?php echo esc_attr(
                    BackWPup_Encryption::decrypt($authentication['basic_password'])
                ); ?>" class="regular-text" autocomplete="off"/>
						</tr>
						<tr class="authentication_user" <?php if ($authentication['method'] !== 'user') {
                    echo 'style="display:none"';
                } ?>>
							<th scope="row"><?php _e('Select WordPress User', 'backwpup'); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e(
                    'Select WordPress User',
                    'backwpup'
                ); ?></span>
									</legend>
									<label for="authentication_user_id">
										<select name="authentication_user_id" size="1">
											<?php
                                            $users = get_users(
                    [
                        'role' => 'administrator',
                        'number' => 99,
                        'orderby' => 'display_name',
                    ]
                );

        foreach ($users as $user) {
            echo '<option value="' . $user->ID . '" ' . selected(
                $authentication['user_id'],
                $user->ID,
                false
            ) . '>' . esc_attr($user->display_name) . '</option>';
        } ?>
										</select>
									</label>
								</fieldset>
							</td>
						</tr>
						<tr class="authentication_query_arg" <?php if ($authentication['method'] != 'query_arg') {
            echo 'style="display:none"';
        } ?>>
							<th scope="row">
								<label for="authentication_query_arg">
									<?php esc_html_e(
            'Query arg key=value:',
            'backwpup'
        ); ?>
								</label>
							</th>
							<td>
								?<input name="authentication_query_arg" type="text" id="authentication_query_arg"
								        value="<?php echo esc_attr(
            $authentication['query_arg']
        ); ?>" class="regular-text"/>
							</td>
						</tr>
					</table>

				</div>

				<div class="table ui-tabs-hide" id="backwpup-tab-apikey">
					<?php do_action('backwpup_page_settings_tab_apikey'); ?>
				</div>

				<div class="table ui-tabs-hide" id="backwpup-tab-information">
					<br/>
					<?php $information = self::get_information(); ?>

					<p>
						<?php
                        esc_html_e(
            'Experiencing an issue and need to contact BackWPup support? Click the link below to get debug information you can send to us.',
            'backwpup'
        ); ?>
					</p>
					<p>
						<a href="#TB_inline?height=440&width=630&inlineId=tb-debug-info" id="debug-button"
						   class="thickbox button button-primary" title="<?php esc_html_e(
            'Debug Info',
            'backwpup'
        ); ?>">
							<?php _e('Get Debug Info', 'backwpup'); ?>
						</a>
					</p>

					<div id="tb-debug-info" tabindex="-1" style="display: none;">
						<?php ob_start(); ?>
						<p>
							<?php esc_html_e(
            'You will find debug information below. Click the button to copy the debug info to send to support.',
            'backwpup'
        ); ?>
						</p>
						<p>
							<?php
                            echo wp_kses(
            __(
                '<strong>Note</strong>: ' .
                                    'Would you like faster, more streamlined support? Pro users can contact BackWPup from right within the plugin.',
                'backwpup'
            ),
            ['strong' => []]
        ); ?>
							<a href="<?php _e('https://backwpup.com', 'backwpup'); ?>">
								<?php _e('Get Pro', 'backwpup'); ?>
							</a>
						</p>

						<?php
                        $html = ob_get_clean();
        echo apply_filters('backwpup_get_debug_info_text', $html); ?>

						<p>
							<a href="#" id="backwpup-copy-debug-info" data-clipboard-target="#backwpup-debug-info"
							   class="button button-primary">
								<?php _e('Copy Debug Info', 'backwpup'); ?>
							</a>
						</p>

						<div class="inline" id="backwpup-copy-debug-info-success" style="display:none;">
							<p><span class="dashicons dashicons-yes"></span><?php esc_html_e(
            'Debug info copied to clipboard.',
            'backwpup'
        ); ?></p>
						</div>
						<div class="inline" id="backwpup-copy-debug-info-error" style="display:none;">
							<p>
								<span class="dashicons dashicons-no"></span>
								<?php esc_html_e(
            'Could not copy debug info. You can simply press ctrl+C to copy it.',
            'backwpup'
        ); ?>
							</p>
						</div>

						<textarea id="backwpup-debug-info" readonly="readonly"
						          style="width: 100%;height: 100%;overflow: scroll;"><?php
                            foreach ($information as $item) {
                                echo esc_html($item['label']) . ': ' . esc_html($item['value']) . "\n";
                            } ?></textarea>
					</div>

					<script type="text/javascript">
                        jQuery( document ).ready( function ( $ )
                        {
                            clipboard = new Clipboard( '#backwpup-copy-debug-info' );

                            clipboard.on( 'success', function ( e )
                            {
                                setTimeout(
                                    function ()
                                    {
                                        $( '#backwpup-copy-debug-info-success' )
                                            .attr( 'style', 'display:inline-block !important;color:green' );
                                    },
                                    300
                                );

                                setTimeout(
                                    function ()
                                    {
                                        $( '#backwpup-copy-debug-info-success' )
                                            .attr( 'style', 'display:none !important;' );
                                    },
                                    5000
                                );
                                e.clearSelection();
                            } );

                            clipboard.on( 'error', function ( e )
                            {
                                $( 'backwpup-copy-debug-info-error' )
                                    .attr( 'style', 'display:inline-block !important;color:red' );
                            } );

                            $( '#debug-button' ).on( 'click', function ()
                            {
                                $( '#tb-debug-info' ).focus();
                                //  $("#TB_ajaxWindowTitle").text("<?php _e('Debug Info'); ?>");
                                $( '#TB_ajaxWindowTitle' ).text( 'WTF' );
                            } );
                        } );
					</script>

					<?php

                    echo '<table class="wp-list-table widefat fixed" cellspacing="0" style="width:100%;margin-left:auto;margin-right:auto;">';
        echo '<thead><tr><th width="35%">' . __('Setting', 'backwpup') . '</th><th>' . __(
            'Value',
            'backwpup'
        ) . '</th></tr></thead>';
        echo '<tfoot><tr><th>' . __('Setting', 'backwpup') . '</th><th>' . __(
            'Value',
            'backwpup'
        ) . '</th></tr></tfoot>';

        foreach ($information as $item) {
            echo "<tr>\n" .
                             '<td>' . $item['label'] . "</td>\n" .
                             '<td>' .
                             ($item['html'] ?? esc_html($item['value'])) .
                             "</td>\n" .
                             "</tr>\n";
        }
        echo '</table>'; ?>
				</div>

				<?php do_action('backwpup_page_settings_tab_content'); ?>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_attr_e(
            'Save Changes',
            'backwpup'
        ); ?>"/>
					&nbsp;
					<input type="submit" name="default_settings" id="default_settings" class="button-secondary"
					       value="<?php esc_attr_e(
            'Reset all settings to default',
            'backwpup'
        ); ?>"/>
				</p>
			</form>
		</div>

        <?php
    }
}
