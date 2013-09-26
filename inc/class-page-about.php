<?php
/**
 * Render plugin about Page.
 *
 */
class BackWPup_Page_About {

	/**
	 * Enqueue style.
	 *
	 * @return void
	 */
	public static function admin_print_styles() {

		wp_enqueue_style('backwpupgeneral');

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			wp_enqueue_style( 'backwpuppageabout', BackWPup::get_plugin_data( 'URL' ) . '/css/page_about.css', '', time(), 'screen' );
		} else {
			wp_enqueue_style( 'backwpuppageabout', BackWPup::get_plugin_data( 'URL' ) . '/css/page_about.min.css', '', BackWPup::get_plugin_data( 'Version' ), 'screen' );
		}

	}

	/**
	 * Enqueue script.
	 *
	 * @return void
	 */
	public static function admin_print_scripts() {

		wp_enqueue_script( 'backwpupgeneral' );

	}


	/**
	 * Print the markup.
	 *
	 * @return void
	 */
	public static function page() {

		?>
        <div class="wrap">
        	<div class="inpsyde">
            	<a href="http://inpsyde.com/" title="Inpsyde GmbH">Inpsyde</a>
            </div>
			<?php screen_icon(); ?>
            <h2><?php echo sprintf( __( '%s Welcome', 'backwpup' ), BackWPup::get_plugin_data( 'name') ); ?></h2>
            <div class="welcome">
            	<div class="welcome_inner">
                	<div class="top">
						<?php if ( get_site_transient( 'backwpup_upgrade_from_version_two') ) { ?>
                            <div id="update-notice" class="backwpup-welcome updated">
                                <h3><?php _e( 'Heads up! You have updated from version 2.x', 'backwpup' ); ?></h3>
                                <p><?php echo str_replace( '\"','"', sprintf( __( 'Please <a href="%s">check your settings</a> after updating from version 2.x:', 'backwpup' ), network_admin_url( 'admin.php').'?page=backwpupjobs') ); ?></a></p>
                                <ul><li><?php _e('Dropbox authentication must be re-entered','backwpup'); ?></li>
								<li><?php _e('SugarSync authentication must be re-entered','backwpup'); ?></li>
                                <li><?php _e('S3 Settings','backwpup'); ?></li>
                                <li><?php _e('Google Storage is now a part of S3 service settings','backwpup'); ?></li>
                                <li><?php _e('All your passwords','backwpup'); ?></li>
                                </ul>
                             </div>
                        <?php } ?>
    				</div>
                    <?php if ( class_exists( 'BackWPup_Pro', FALSE ) ) { ?>
                    <div class="welcometxt">
                        <div class="backwpup-welcome">
                        	<div class="banner-pro"></div>
                            <h3><?php _e( 'Welcome to BackWPup Pro', 'backwpup' ); ?></h3>
                            <p><?php _e( 'Here you can schedule backup plans with a wizard.', 'backwpup' );
_e( 'The backup files can be used to save your whole installation including <code>/wp-content/</code> and push them to an external Backup Service, if you don’t want to save the backups on the same server. With a single backup file you are able to restore an installation.', 'backwpup' ); ?></p>
                            <p><?php echo str_replace( '\"','"', sprintf( __( 'First <a href="%1$s">set up a job</a>, and plan what you want to save. You can <a href="%2$s">use the wizards</a> or the expert mode.', 'backwpup' ), network_admin_url( 'admin.php').'?page=backwpupeditjob' , network_admin_url( 'admin.php').'?page=backwpupwizard' ) ); ?></p>
                        </div>
                    </div>
                    <?php } else {?>
                    <div class="welcometxt">
                        <div class="backwpup-welcome">
                        	<div class="banner"></div>
                            <h3><?php _e( 'Welcome to BackWPup', 'backwpup' ); ?></h3>
                            <p><?php
_e( 'The backup files can be used to save your whole installation including <code>/wp-content/</code> and push them to an external Backup Service, if you don’t want to save the backups on the same server. With a single backup file you are able to restore an installation.', 'backwpup' ); ?></p>
                            <p><?php _e( 'First set up a job, and plan what you want to save.', 'backwpup' ); ?></p>
                        </div>
                    </div>
                    <?php } ?>
					<?php
            		if ( class_exists( 'marketpress_autoupdate' ) && class_exists( 'BackWPup_Pro', FALSE ) ) :
						$autoupdate = $autoupdate = marketpress_autoupdate::get_instance( BackWPup::get_plugin_data( 'Slug' ) , BackWPup::get_plugin_data( 'MainFile' ) );
						if ( $autoupdate->license_check() == 'false' ) :
							$plugins = get_plugins();
							$localplugin = FALSE;
							foreach ( $plugins as $plugin ) {
								if ( BackWPup::get_plugin_data( 'Name' ) == $plugin[ 'Name' ] )
									$localplugin = TRUE;
							}
							?>
		             		<div class="welcometxt">
		                        <div class="backwpup-welcome">
		                            <h3><?php _e( 'Please activate your license', 'backwpup' ); ?></h3>
		                            <p><a href="<?php echo $localplugin ? admin_url( 'plugins.php' ) : network_admin_url( 'plugins.php' ); ?>"><?php _e( 'Please go to your plugin page and active the license to have the autoupdates enabled.', 'backwpup' ); ?></a></p>
								</div>
							</div>
						<?php endif; ?>
					<?php endif; ?>
            		<div class="features">

                    	<div class="feature_box">
                        	<div class="feature_image <?php self::feature_class(); ?>">
                                <img title="<?php _e( 'Save your database', 'backwpup' ); ?>" src="<?php echo BackWPup::get_plugin_data( 'URL' ); ?>/images/imagesave.png" />
                            </div>
                            <div class="feature_text <?php self::feature_class(); ?>">
                            	<h3><?php _e( 'Save your database regularly', 'backwpup' ); ?></h3>
                                <p><?php echo str_replace( '\"','"', sprintf( __( 'With BackWPup you can schedule the database backup to run automatically. With a single backup file you can restore your database. You should <a href="%s">set up a backup job</a>, so you will never forget it. There is also an option to repair and optimize the database after each backup.', 'backwpup' ), network_admin_url( 'admin.php').'?page=backwpupeditjob' ) ); ?></p>
                            </div>
                        </div>
                        <div class="feature_box">
                            <div class="feature_text <?php self::feature_class(); ?>">
                            	<h3><?php _e('WordPress XML Export', 'backwpup' ); ?></h3>
                                <p><?php _e('You can choose the built-in WordPress export format in addition or exclusive to save your data. This works in automated backups too of course. The advantage is: you can import these files into a blog with the regular WordPress importer.', 'backwpup'); ?></p>
                            </div>
                            <div class="feature_image <?php self::feature_class(); ?>">
                            	<img title="<?php _e( 'WordPress XML Export', 'backwpup' ); ?>" src="<?php echo BackWPup::get_plugin_data( 'URL' ); ?>/images/imagexml.png" />
                            </div>
                        </div>
                        <div class="feature_box">
                            <div class="feature_image <?php self::feature_class(); ?>">
                            	<img title="<?php _e( 'Save all data from the webserver', 'backwpup' ); ?>" src="<?php echo BackWPup::get_plugin_data( 'URL' ); ?>/images/imagedata.png" />
                            </div>
                            <div class="feature_text <?php self::feature_class(); ?>">
                            	<h3><?php _e('Save all files', 'backwpup'); ?></h3>
                                <p><?php echo str_replace( '\"','"', sprintf( __('You can back up all your attachments, also all system files, plugins and themes in a single file. You can <a href="%s">create a job</a> to update a backup copy of your file system only when files are changed.', 'backwpup'), network_admin_url( 'admin.php' ) . '?page=backwpupeditjob' ) ); ?></p>
                            </div>
                        </div>
                        <div class="feature_box">
                            <div class="feature_text <?php self::feature_class(); ?>">
                            	<h3><?php _e( 'Security!', 'backwpup' ); ?></h3>
                                <p><?php _e('By default everything is encrypted: connections to external services, local files and access to directories.', 'backwpup'); ?></p>
                            </div>
                        	<div class="feature_image <?php self::feature_class(); ?>">
                            	<img title="<?php _e( 'Security!', 'backwpup' ); ?>" src="<?php echo BackWPup::get_plugin_data( 'URL' ); ?>/images/imagesec.png" />
                            </div>
                        </div>
                        <div class="feature_box">
                            <div class="feature_image <?php self::feature_class(); ?>">
                            	<img title="<?php _e( 'Cloud Support', 'backwpup' ); ?>" src="<?php echo BackWPup::get_plugin_data( 'URL' ); ?>/images/imagecloud.png" />
                            </div>
                            <div class="feature_text <?php self::feature_class(); ?>">
                            	<h3><?php _e( 'Cloud Support', 'backwpup' ); ?></h3>
                                <p><?php _e('BackWPup supports multiple cloud services in parallel. This ensures the backups are redundant.', 'backwpup'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

				<?php if ( ! class_exists( 'BackWPup_Pro' ) ) : ?>
					<div class="backwpup_comp">
						<h3><?php _e( 'Features / differences between Free and Pro', 'backwpup' ); ?></h3>
						<table width="100%" border="0" cellspacing="0" cellpadding="0">
							<tr class="even ub">
								<td><?php _e( 'Features', 'backwpup' ); ?></td>
								<td class="free"><?php _e( 'FREE', 'backwpup' ); ?></td>
								<td class="pro"><?php _e( 'PRO', 'backwpup' ); ?></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Complete database backup', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Complete file backup', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Database check', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Data compression', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'WordPress XML export', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'List of installed plugins', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Backup archives management', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Log file management', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Start jobs per WP-Cron, URL, system, backend or WP-CLI', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Log report via email', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Backup to Microsoft Azure', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Backup as email', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Backup to S3 services <small>(Amazon, Google Storage, Hosteurope and more)</small>', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Backup to Dropbox', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Backup to Rackspace Cloud Files', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Backup to FTP server', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Backup to your web space', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Backup to SugarSync', 'backwpup' ); ?></td>
								<td class="tick"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Custom API keys for DropBox and SugarSync', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'XML database backup as PHPMyAdmin schema', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Database backup as mysqldump per command line', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Database backup for additional MySQL databases', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Import and export job settings as XML', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Wizard for system tests', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Wizard for scheduled backup jobs', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Wizard to import settings and backup jobs', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Differential backup of changed directories to Dropbox', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Differential backup of changed directories to Rackspace Cloud Files', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( 'Differential backup of changed directories to S3', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Differential backup of changed directories to MS Azure', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( '<strong>Premium support</strong>', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( '<strong>Dynamically loaded documentation</strong>', 'backwpup' ); ?></td>
								<td class="error" style="border-bottom:none;"></td>
								<td class="tick" style="border-bottom:none;"></td>
							</tr>
							<tr class="odd">
								<td><?php _e( '<strong>Automatic update from MarketPress</strong>', 'backwpup' ); ?></td>
								<td class="error" style="border-bottom:none;"></td>
								<td class="tick" style="border-bottom:none;"></td>
							</tr>
							<tr class="even ubdown">
								<td></td>
								<td></td>
								<td class="pro buylink"><a href="<?php _e( 'http://marketpress.com/product/backwpup-pro/', 'backwpup' ); ?>"><?php _e( 'GET PRO', 'backwpup' ); ?></a></td>
							</tr>
						</table>
					</div>
				<?php
				endif;
 				?>
            </div>
        </div>
	<?php
	}

	/**
	 * Alternate between 'left' and 'right' CSS class.
	 *
	 * @since  2013.02.19
	 * @return void
	 */
	protected static function feature_class() {

		static $class = 'alignleft';

		print $class;

		$class = 'alignleft' === $class ? 'alignright' : 'alignleft';
	}
}
