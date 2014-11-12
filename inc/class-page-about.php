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

		?>
		<style type="text/css" media="screen">
			.inpsyde {
				width:79px;
				height:119px;
				background: url( '<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/inpsyde.png' ) no-repeat;
				position: absolute;
				top:0;
				right: 100px;
				z-index: 1;
			}
			.inpsyde a, .inpsyde a:link{
				float:left;
				font-size:14px;
				color:#fff;
				text-decoration:none;
				padding:65px 15px 15px 15px;
				text-align:center;
			}
			@media screen and (max-width: 782px) {
				.inpsyde {
					right:10px;
				}
			}
			@media screen and (max-width: 600px) {
				.inpsyde {
					display: none;
				}
			}
			#backwpup-page {
				background: #fff;
				margin-top: 22px;
				padding: 0 20px;
			}
			#backwpup-page .inpsyde + h2 {
				visibility: hidden;
			}
			.welcome {
/* 				max-width: 960px; */
			}
			.welcome .welcome_inner {
				margin:0 auto;
				max-width: 960px;
			}
			.backwpup-welcome {
/*
				margin: 0 auto;
				max-width: 960px;
*/
			}
			.welcome .welcome_inner h3{
				font-size:42px;
			}
			.welcome .welcome_inner .welcometxt {
/* 				width: 100%; */
				margin-bottom: 40px;
				overflow: hidden;
				border-bottom: 1px #ccc dotted;
				text-align: center;
				padding-bottom: 25px;
				position: relative;
			}
			.welcome .welcome_inner .welcometxt p{
				line-height:20px;
				font-size:18px;
			}
			.welcome .welcome_inner .feature-box{
				clear: both;
				margin-bottom: 40px;
				overflow: hidden;
			}
			.welcome .welcome_inner .feature-box .feature-image{
				float: left;
				width:18%;
				height:auto;
			}
			.welcome .welcome_inner .feature-box .feature-image img{
				width:100%;
				height:auto;
				max-width:350px;
			}
			.welcome .welcome_inner .feature-box .feature-text{
				float: left;
				width:72%;
				padding: 0 0 20px 20px;
			}
			.welcome .welcome_inner .feature-box-right .feature-text {
				padding: 0 20px 20px 0;
			}
			.welcome .welcome_inner .feature-box .feature-text h3{
				color:rgb(0, 155, 204);
				font-weight:normal;
				font-size:24px;
				margin:0 0 10px 0;
				text-align:left;
			}
			.welcome .welcome_inner .feature-box .left {
				float:left;
			}
			.welcome .welcome_inner .feature-box .right {
				float:right;
			}
			.welcome .welcome_inner .featuretitle h3 {
				font-size:28px;
				font-weight:normal;
				text-align:left;
				margin-bottom:25px;
			}
			.welcome .button-primary-bwp {
				float:left;
				padding:15px;
				font-size:18px;
				text-decoration:none;
				background-color:#38b0eb;
				color:#fff;
				border:none;
				cursor:pointer;
				margin: 35px 0;
			}
			.welcome .button-primary-bwp:hover {
				background-color:#064565;
				cursor:pointer;
			}
			@media only screen and (max-width: 1100px), only screen and (max-device-width: 1100px) {
				.welcome .welcome_inner h3{
					font-size:32px;
				}
				.welcome .welcome_inner .featuretitle h3 {
					font-size:22px;
					font-weight:normal;
					text-align:left;
					margin-bottom:25px;
				}
				.welcome .welcome_inner .welcometxt p{
					line-height:20px;
					font-size:14px;
				}
				.welcome .welcome_inner .feature-box .feature-text h3{
					font-weight:normal;
					font-size:20px;
					margin:0 0 10px 0;
					text-align:left;
				}
				.welcome .welcome_inner .feature-box .feature-text{
					width:72%;
					font-size:14px;
					line-height:20px;
				}
				.welcome .button-primary-bwp {
					float:left;
					padding:10px;
					font-size:16px;
					text-decoration:none;
					background-color:#38b0eb;
					color:#fff;
					border:none;
					cursor:pointer;
					margin: 35px 0;
				}
			}
			@media only screen and (max-width: 780px), only screen and (max-device-width: 780px) {
				.welcome .welcome_inner h3{
					font-size:22px;
				}
				.welcome .welcome_inner .featuretitle h3 {
					font-size:22px;
					font-weight:normal;
					text-align:left;
				}
				.welcome .welcome_inner .welcometxt p{
					line-height:20px;
					font-size:14px;
				}
				.welcome .welcome_inner .feature-box .feature-text h3{
					font-weight:normal;
					font-size:16px;
					margin:0 0 10px 0;
				}
				.welcome .welcome_inner .feature-box .feature-text{
					width:72%;
					font-size:12px;
					line-height:16px;
					text-align:left;
				}
				.welcome .button-primary-bwp {
					float:left;
					padding:10px;
					font-size:16px;
					text-decoration:none;
					background-color:#38b0eb;
					color:#fff;
					border:none;
					cursor:pointer;
					margin: 35px 0;
				}
			}

			.backwpup_comp {
				margin: 20px auto;
				width: 100%;
				font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
				font-size: 16px;
			}

			.backwpup_comp table {
				border: none;
			}

			.backwpup_comp table tbody tr.even td {
				border: none;
				background: none;
				padding: 15px;
				margin: 0;
			}

			.backwpup_comp table tbody tr.odd td {
				border: none;
				background: none;
				padding: 15px;
				margin: 0;
			}

			.backwpup_comp h3 {
				font-family: "Arial", sans-serif;
				font-size: 42px;
				text-align: center;
				font-weight: normal;
				color: #333;
				line-height: 44px;
				margin: 20px 0;
			}

			.backwpup_comp table tbody tr.ub {
				font-family: 'MisoRegular', "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
				font-size: 26px;
			}

			.backwpup_comp table tbody tr.ubdown {
				font-family: 'MisoRegular', "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif;
				font-size: 26px;
				background: none !important;
			}

			.backwpup_comp table tbody tr.even {
				background-image: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/even.png);
			}

			.backwpup_comp table tbody tr.odd {
				background-image: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/odd.png);
			}

			.backwpup_comp table tbody tr.ub td.pro {
				height: 50px;
				text-align: center;
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/hgtopgreen.png) no-repeat bottom center;
				color: #fff;
				border-left: 1px solid #112a32;
			}

			.backwpup_comp table tbody tr.ub td.free {
				height: 50px;
				text-align: center;
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/hgtopgreen.png) no-repeat bottom center;
				color: #fff;
			}

			.backwpup_comp table tbody tr.ubdown td.pro {
				height: 50px;
				text-align: center;
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/hgbtgreen.png) no-repeat top center;
				color: #fff;
				border-left: 1px solid #1c3e49;
			}

			.backwpup_comp table tbody tr.ubdown td.pro a {
				color: #fff;
				text-decoration: none;
				cursor: auto;
				font-weight: 300;
				line-height: 1.4em;
				font-size: 18px;
			}

			.backwpup_comp table tbody tr.ubdown td.free {
				height: 50px;
				text-align: center;
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/hgbtred.png) no-repeat top center;
				color: #fff;
			}

			.backwpup_comp table tbody tr.ubdown td.free a {
				color: #fff;
			}

			.backwpup_comp table tbody tr.even td.tick {
				width: 100px;
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/tickeven.png) no-repeat center;
				border-bottom: 1px solid #799e14;
				border-top: 1px solid #a2d123;
				border-left: 1px solid #799e14;
			}

			.backwpup_comp table tbody tr.odd td.tick {
				width: 100px;
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/tickodd.png) no-repeat center;
				border-left: 1px solid #799e14;
			}

			.backwpup_comp table tbody tr.even td.error {
				width: 100px;
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/erroreven.png) no-repeat center;
				border-bottom: 1px solid #b13020;
				border-top: 1px solid #e84936;
			}

			.backwpup_comp table tbody tr.odd td.error {
				width: 100px;
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/errorodd.png) no-repeat center;
			}

			.backwpup_comp table tbody tr.even:hover {
				background-image: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/hover.png);
			}

			.backwpup_comp table tbody tr.odd:hover {
				background-image: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/hover.png);
			}

			.backwpup_comp table tbody tr.even:hover td.tick {
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/tickhover.png) center;
			}

			.backwpup_comp table tbody tr.odd:hover td.tick {
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/tickhover.png) center;
			}

			.backwpup_comp table tbody tr.even:hover td.error {
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/errorhover.png) center;
			}

			.backwpup_comp table tbody tr.odd:hover td.error {
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/errorhover.png) center;
			}

			.backwpup_comp table tbody tr.ubdown:hover td.pro:hover {
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/hgbtgreenhover.png) no-repeat top center;
			}

			.backwpup_comp table tbody tr.ubdown:hover td.free:hover {
				background: url(<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/hgbtredhover.png) no-repeat top center;
			}
		</style>
		<?php
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
        <div class="wrap" id="backwpup-page">
        	<div class="inpsyde">
            	<a href="http://inpsyde.com/" title="Inpsyde GmbH">Inpsyde</a>
            </div>
            <h2><span id="backwpup-page-icon">&nbsp;</span><?php echo sprintf( __( '%s Welcome', 'backwpup' ), BackWPup::get_plugin_data( 'name') ); ?></h2>
			<?php BackWPup_Admin::display_messages(); ?>
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
							<img class="backwpup-banner-img" src="<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/backwpupbanner-pro.png" />
                            <h3><?php _e( 'Welcome to BackWPup Pro', 'backwpup' ); ?></h3>
                            <p><?php _e( 'BackWPup’s job wizards make planning and scheduling your backup jobs a breeze.', 'backwpup' ); echo ' ';
_e( 'Use your backup archives to save your entire WordPress installation including <code>/wp-content/</code>. Push them to an external storage service if you don’t want to save the backups on the same server. With a single backup archive you are able to restore an installation. Use a tool like phpMyAdmin or a plugin like <a href="http://wordpress.org/plugins/adminer/" target="_blank">Adminer</a> to restore your database backup files.', 'backwpup' ); ?></p>
                            <p><?php echo str_replace( '\"','"', sprintf( __( 'Ready to <a href="%1$s">set up a backup job</a>? You can <a href="%2$s">use the wizards</a> or plan your backup in expert mode.', 'backwpup' ), network_admin_url( 'admin.php').'?page=backwpupeditjob' , network_admin_url( 'admin.php').'?page=backwpupwizard' ) ); ?></p>
                        </div>
                    </div>
                    <?php } else {?>
                    <div class="welcometxt">
                        <div class="backwpup-welcome">
							<img class="backwpup-banner-img" src="<?php echo BackWPup::get_plugin_data( 'URL' );?>/assets/images/backwpupbanner-free.png" />
                            <h3><?php _e( 'Welcome to BackWPup', 'backwpup' ); ?></h3>
                            <p><?php
_e( 'Use your backup archives to save your entire WordPress installation including <code>/wp-content/</code>. Push them to an external storage service if you don’t want to save the backups on the same server. With a single backup archive you are able to restore an installation. Use a tool like phpMyAdmin or a plugin like <a href="http://wordpress.org/plugins/adminer/" target="_blank">Adminer</a> to restore your database backup files.', 'backwpup' ); ?></p>
                            <p><?php _e( 'Ready to set up a backup job? Use one of the wizards to plan what you want to save.', 'backwpup' ); ?></p>
                        </div>
                    </div>
                    <?php } ?>
					<?php
            		if ( class_exists( 'BackWPup_Pro', FALSE ) ) :
						$autoupdate = BackWPup_Pro_MarketPress_Autoupdate::get_instance( BackWPup::get_plugin_data( 'Slug' ) , BackWPup::get_plugin_data( 'MainFile' ) );
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

                    	<div class="feature-box <?php self::feature_class(); ?>">
                        	<div class="feature-image">
                                <img title="<?php _e( 'Save your database', 'backwpup' ); ?>" src="<?php echo BackWPup::get_plugin_data( 'URL' ); ?>/assets/images/imagesave.png" />
                            </div>
                            <div class="feature-text">
                            	<h3><?php _e( 'Save your database regularly', 'backwpup' ); ?></h3>
                                <p><?php echo str_replace( '\"','"', sprintf( __( 'With BackWPup you can schedule the database backup to run automatically. With a single backup file you can restore your database. You should <a href="%s">set up a backup job</a>, so you will never forget it. There is also an option to repair and optimize the database after each backup.', 'backwpup' ), network_admin_url( 'admin.php').'?page=backwpupeditjob' ) ); ?></p>
                            </div>
                        </div>
                        <div class="feature-box <?php self::feature_class(); ?>">
                            <div class="feature-text">
                            	<h3><?php _e('WordPress XML Export', 'backwpup' ); ?></h3>
                                <p><?php _e('You can choose the built-in WordPress export format in addition or exclusive to save your data. This works in automated backups too of course. The advantage is: you can import these files into a blog with the regular WordPress importer.', 'backwpup'); ?></p>
                            </div>
                            <div class="feature-image">
                            	<img title="<?php _e( 'WordPress XML Export', 'backwpup' ); ?>" src="<?php echo BackWPup::get_plugin_data( 'URL' ); ?>/assets/images/imagexml.png" />
                            </div>
                        </div>
                        <div class="feature-box <?php self::feature_class(); ?>">
                            <div class="feature-image">
                            	<img title="<?php _e( 'Save all data from the webserver', 'backwpup' ); ?>" src="<?php echo BackWPup::get_plugin_data( 'URL' ); ?>/assets/images/imagedata.png" />
                            </div>
                            <div class="feature-text">
                            	<h3><?php _e('Save all files', 'backwpup'); ?></h3>
                                <p><?php echo str_replace( '\"','"', sprintf( __('You can backup all your attachments, also all system files, plugins and themes in a single file. You can <a href="%s">create a job</a> to update a backup copy of your file system only when files are changed.', 'backwpup'), network_admin_url( 'admin.php' ) . '?page=backwpupeditjob' ) ); ?></p>
                            </div>
                        </div>
                        <div class="feature-box <?php self::feature_class(); ?>">
                            <div class="feature-text">
                            	<h3><?php _e( 'Security!', 'backwpup' ); ?></h3>
                                <p><?php _e('By default everything is encrypted: connections to external services, local files and access to directories.', 'backwpup'); ?></p>
                            </div>
                        	<div class="feature-image">
                            	<img title="<?php _e( 'Security!', 'backwpup' ); ?>" src="<?php echo BackWPup::get_plugin_data( 'URL' ); ?>/assets/images/imagesec.png" />
                            </div>
                        </div>
                        <div class="feature-box <?php self::feature_class(); ?>">
                            <div class="feature-image">
                            	<img title="<?php _e( 'Cloud Support', 'backwpup' ); ?>" src="<?php echo BackWPup::get_plugin_data( 'URL' ); ?>/assets/images/imagecloud.png" />
                            </div>
                            <div class="feature-text">
                            	<h3><?php _e( 'Cloud Support', 'backwpup' ); ?></h3>
                                <p><?php _e( 'BackWPup supports multiple cloud services in parallel. This ensures backups are redundant.', 'backwpup' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

				<?php if ( ! class_exists( 'BackWPup_Pro', FALSE ) ) : ?>
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
								<td><?php _e( 'Backup to Google Drive', 'backwpup' ); ?></td>
								<td class="error"></td>
								<td class="tick"></td>
							</tr>
							<tr class="even">
								<td><?php _e( 'Backup to Amazon Glacier', 'backwpup' ); ?></td>
								<td class="error"></td>
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

		static $class = 'feature-box-left';

		print $class;

		$class = 'feature-box-left' === $class ? 'feature-box-right' : 'feature-box-left';
	}
}
