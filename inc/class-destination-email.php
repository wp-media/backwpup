<?php
/**
 * Email destination handler.
 *
 * Swift Mailer v5.2.2
 * http://swiftmailer.org/
 * https://github.com/swiftmailer/swiftmailer
 */

use Inpsyde\BackWPupShared\File\MimeTypeExtractor;
use PHPMailer\PHPMailer\PHPMailer;

class BackWPup_Destination_Email extends BackWPup_Destinations {

	/**
	 * Get default options for email destination.
	 *
	 * @return array
	 */
	public function option_defaults(): array {
		$default                      = [];
		$default['emailaddress']      = sanitize_email( get_bloginfo( 'admin_email' ) );
		$default['emailefilesize']    = 20;
		$default['emailsndemail']     = sanitize_email( get_bloginfo( 'admin_email' ) );
		$default['emailsndemailname'] = 'BackWPup ' . get_bloginfo( 'name' );
		$default['emailmethod']       = '';
		$default['emailsendmail']     = ini_get( 'sendmail_path' );
		$default['emailhost']         = isset( $_SERVER['SERVER_NAME'] )
			? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) )
			: '';
		$default['emailhostport']     = 25;
		$default['emailsecure']       = '';
		$default['emailuser']         = '';
		$default['emailpass']         = '';

		return $default;
	}


	/**
	 * Output inline JavaScript for email settings.
	 *
	 * @return void
	 */
	public function edit_inline_js(): void {
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function ( $ ) {
				$( '#emailmethod' ).on('change',  function () {
					if ( 'smtp' == $( '#emailmethod' ).val() ) {
						$( '.emailsmtp' ).show();
						$( '#emailsendmail' ).hide();
					} else if ( 'sendmail' == $( '#emailmethod' ).val() ) {
						$( '.emailsmtp' ).hide();
						$( '#emailsendmail' ).show();
					} else {
						$( '.emailsmtp' ).hide();
						$( '#emailsendmail' ).hide();
					}
				} );
				$( '#sendemailtest' ).live( 'click', function () {
					$( '#sendemailtest' ).after( '&nbsp;<img id="emailsendtext" src="<?php echo esc_url( get_admin_url() . 'images/loading.gif' ); ?>" width="16" height="16" />' );
					var data = {
						action           : 'backwpup_dest_email',
						emailaddress     : $( 'input[name="emailaddress"]' ).val(),
						emailsndemail    : $( 'input[name="emailsndemail"]' ).val(),
						emailmethod      : $( '#emailmethod' ).val(),
						emailsendmail    : $( 'input[name="emailsendmail"]' ).val(),
						emailsndemailname: $( 'input[name="emailsndemailname"]' ).val(),
						emailhost        : $( 'input[name="emailhost"]' ).val(),
						emailhostport    : $( 'input[name="emailhostport"]' ).val(),
						emailsecure      : $( '#emailsecure' ).val(),
						emailuser        : $( 'input[name="emailuser"]' ).val(),
						emailpass        : $( 'input[name="emailpass"]' ).val(),
						_ajax_nonce      : $( 'input[name="backwpupajaxnonce"]' ).val()
					};
					$.post( ajaxurl, data, function ( response ) {
						$( '#emailsendtext' ).replaceWith( response );
					} );
					return false;
				} );
			} );
		</script>
		<?php
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param int|array $jobid Job id or list of job ids.
	 * @return void
	 *
	 * @phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	 */
	public function edit_form_post_save( $jobid ): void {
				$jobids = (array) $jobid;
		foreach ( $jobids as $jobid ) {
			BackWPup_Option::update( $jobid, 'emailaddress', isset( $_POST['emailaddress'] ) ? implode( ', ', $this->get_email_array( $_POST['emailaddress'] ) ) : '' ); // phpcs:ignore WordPress.Security
			BackWPup_Option::update( $jobid, 'emailefilesize', ! empty( $_POST['emailefilesize'] ) ? absint( $_POST['emailefilesize'] ) : 0 );  // phpcs:ignore WordPress.Security
			BackWPup_Option::update( $jobid, 'emailsndemail', sanitize_email( $_POST['emailsndemail'] ) );  // phpcs:ignore WordPress.Security
			BackWPup_Option::update( $jobid, 'emailmethod', ( '' === $_POST['emailmethod'] || 'mail' === $_POST['emailmethod'] || 'sendmail' === $_POST['emailmethod'] || 'smtp' === $_POST['emailmethod'] ) ? $_POST['emailmethod'] : '' );
			BackWPup_Option::update( $jobid, 'emailsendmail', sanitize_text_field( $_POST['emailsendmail'] ) );
			BackWPup_Option::update( $jobid, 'emailsndemailname', sanitize_text_field( $_POST['emailsndemailname'] ) );
			BackWPup_Option::update( $jobid, 'emailhost', sanitize_text_field( $_POST['emailhost'] ) );
			BackWPup_Option::update( $jobid, 'emailhostport', absint( $_POST['emailhostport'] ) );
			BackWPup_Option::update( $jobid, 'emailsecure', ( 'ssl' === $_POST['emailsecure'] || 'tls' === $_POST['emailsecure'] ) ? $_POST['emailsecure'] : '' );
			BackWPup_Option::update( $jobid, 'emailuser', sanitize_text_field( $_POST['emailuser'] ) );
			BackWPup_Option::update( $jobid, 'emailpass', BackWPup_Encryption::encrypt( $_POST['emailpass'] ) );
		}
	}
	// phpcs:enable

	/**
	 * Run archive job for email destination.
	 *
	 * @param BackWPup_Job $job_object Job object.
	 *
	 * @return bool
	 */
	public function job_run_archive( BackWPup_Job $job_object ): bool {
		$job_object->substeps_todo = 1;
		$job_object->log(
			sprintf(
			/* translators: %d: attempt number. */
			__( '%d. Try to send backup with email&#160;&hellip;', 'backwpup' ),
			$job_object->steps_data[ $job_object->step_working ]['STEP_TRY']
		),
			E_USER_NOTICE
			);

		// Check file size.
		if ( ! empty( $job_object->job['emailefilesize'] ) ) {
			if ( $job_object->backup_filesize > $job_object->job['emailefilesize'] * 1024 * 1024 ) {
				$job_object->log( __( 'Backup archive too big to be sent by email!', 'backwpup' ), E_USER_ERROR );
				$job_object->substeps_done = 1;

				return true;
			}
		}

		$job_object->log(
			sprintf(
			/* translators: %s: email address. */
			__( 'Sending email to %s&hellip;', 'backwpup' ),
			$job_object->job['emailaddress']
		),
			E_USER_NOTICE
			);

		// Get mail settings.
		$emailmethod   = 'mail';
		$emailsendmail = '';
		$emailhost     = '';
		$emailhostport = '';
		$emailsecure   = '';
		$emailuser     = '';
		$emailpass     = '';

		if ( empty( $job_object->job['emailmethod'] ) ) {
			// Do so if wp_mail is used to get the settings.
			$phpmailer = $this->getPhpMailer();

			// Only if PHPMailer is really used.
			if ( is_object( $phpmailer ) ) {
				$hook = 'phpmailer_init';
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
				do_action_ref_array( $hook, [ &$phpmailer ] );
				// Get settings from PHPMailer.
				$phpmailer_vars = get_object_vars( $phpmailer );
				$emailmethod    = $phpmailer_vars['Mailer'] ?? $emailmethod;
				$emailsendmail  = $phpmailer_vars['Sendmail'] ?? $emailsendmail;
				$emailhost      = $phpmailer_vars['Host'] ?? $emailhost;
				$emailhostport  = $phpmailer_vars['Port'] ?? $emailhostport;
				$emailsecure    = $phpmailer_vars['SMTPSecure'] ?? $emailsecure;
				$emailuser      = $phpmailer_vars['Username'] ?? $emailuser;
				$emailpass      = $phpmailer_vars['Password'] ?? $emailpass;
			}
		} else {
			$emailmethod   = $job_object->job['emailmethod'];
			$emailsendmail = $job_object->job['emailsendmail'];
			$emailhost     = $job_object->job['emailhost'];
			$emailhostport = $job_object->job['emailhostport'];
			$emailsecure   = $job_object->job['emailsecure'];
			$emailuser     = $job_object->job['emailuser'];
			$emailpass     = BackWPup_Encryption::decrypt( $job_object->job['emailpass'] );
		}

		// Generate mail with Swift Mailer.
		if ( ! class_exists( \Swift::class, false ) ) {
			require BackWPup::get_plugin_data( 'plugindir' ) . '/vendor/SwiftMailer/swift_required.php';
		}

		$result = null;

		try {
			// Set temp dir for mailing.
			Swift_Preferences::getInstance()->setTempDir( untrailingslashit( BackWPup::get_plugin_data( 'TEMP' ) ) )->setCacheType( 'disk' );
			// Create the transport.
			if ( 'smtp' === $emailmethod ) {
				$transport = Swift_SmtpTransport::newInstance( $emailhost, $emailhostport );
				if ( $emailuser ) {
					$transport->setUsername( $emailuser );
					$transport->setPassword( $emailpass );
				}
				if ( 'ssl' === $emailsecure ) {
					$transport->setEncryption( 'ssl' );
				}
				if ( 'tls' === $emailsecure ) {
					$transport->setEncryption( 'tls' );
				}
			} elseif ( 'sendmail' === $emailmethod ) {
				// Verify command.
				if ( preg_match( '/^[a-zA-Z0-9-_.\/\\\'" ]+$/', $emailsendmail ) === 0 ) {
					$job_object->log( 'The sendmail command has invalid characters.', E_USER_ERROR );
				}
				$transport = Swift_SendmailTransport::newInstance( $emailsendmail );
			} else {
				$job_object->need_free_memory( $job_object->backup_filesize * 8 );
				$transport = Swift_MailTransport::newInstance();
			}
			// Create the mailer using the created transport.
			$emailer = Swift_Mailer::newInstance( $transport );

			// Create a message.
			$message = Swift_Message::newInstance(
				sprintf(
					// translators: %1$s = date, %2$s = job name.
					__( 'BackWPup archive from %1$s: %2$s', 'backwpup' ),
					wp_date( 'd-M-Y H:i', $job_object->start_time ),
					esc_attr( $job_object->job['name'] )
				)
			);
			$message->setFrom( [ $job_object->job['emailsndemail'] => $job_object->job['emailsndemailname'] ] );
			$message->setTo( $this->get_email_array( $job_object->job['emailaddress'] ) );
			$message->setBody(
				sprintf(
					// translators: %s = backup archive.
					__( 'Backup archive: %s', 'backwpup' ),
					$job_object->backup_file
				),
				'text/plain',
				strtolower( get_bloginfo( 'charset' ) )
			);
			$message->attach( Swift_Attachment::fromPath( $job_object->backup_folder . $job_object->backup_file, MimeTypeExtractor::fromFilePath( $job_object->backup_folder . $job_object->backup_file ) ) );
			// Send the message.
			$result = $emailer->send( $message );
		} catch ( Exception $e ) {
			$context = [];
			if ( 'smtp' === $emailmethod ) {
				$lower_message = strtolower( $e->getMessage() );
				if ( preg_match( '/auth|login|credential|username|password/', $lower_message ) ) {
					$context = [
						'reason_code'   => 'incorrect_login',
						'destination'   => 'EMAIL',
						'provider_code' => 'smtp_auth_failed',
					];
				}
			}

			$job_object->log(
				'Swift Mailer: ' . $e->getMessage(),
				E_USER_ERROR,
				__FILE__,
				__LINE__,
				$context
			);
		}

		if ( ! isset( $result ) || ! $result ) {
			$job_object->log( __( 'Error while sending email!', 'backwpup' ), E_USER_ERROR );

			return false;
		}
		$job_object->substeps_done = 1;
		$job_object->log( __( 'Email sent.', 'backwpup' ), E_USER_NOTICE );

		return true;
	}

	/**
	 * Check if email destination can run.
	 *
	 * @param array $job_settings Job settings.
	 *
	 * @return bool
	 */
	public function can_run( array $job_settings ): bool {
		if ( empty( $job_settings['emailaddress'] ) ) {
			return false;
		}

		return 'archive' === $job_settings['backuptype'];
	}

	/**
	 * Send test mail.
	 *
	 * @return void
	 */
	public function edit_ajax(): void {
		if ( ! current_user_can( 'backwpup_jobs_edit' ) ) {
			wp_die( -1 );
		}

		check_ajax_referer( 'backwpup_ajax_nonce' );

		$post = wp_unslash( $_POST );

		// Get mail settings.
		$emailmethod   = 'mail';
		$emailsendmail = '';
		$emailhost     = '';
		$emailhostport = '';
		$emailsecure   = '';
		$emailuser     = '';
		$emailpass     = '';

		$emailmethod_raw = isset( $post['emailmethod'] ) ? $post['emailmethod'] : '';

		if ( empty( $emailmethod_raw ) ) {
			// Do so if wp_mail is used to get the settings.
			$phpmailer = $this->getPhpMailer();

			// Only if PHPMailer is really used.
			if ( is_object( $phpmailer ) ) {
				$hook = 'phpmailer_init';
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
				do_action_ref_array( $hook, [ &$phpmailer ] );
				// Get settings from PHPMailer.
				$phpmailer_vars = get_object_vars( $phpmailer );
				$emailmethod    = $phpmailer_vars['Mailer'] ?? $emailmethod;
				$emailsendmail  = $phpmailer_vars['Sendmail'] ?? $emailsendmail;
				$emailhost      = $phpmailer_vars['Host'] ?? $emailhost;
				$emailhostport  = $phpmailer_vars['Port'] ?? $emailhostport;
				$emailsecure    = $phpmailer_vars['SMTPSecure'] ?? $emailsecure;
				$emailuser      = $phpmailer_vars['Username'] ?? $emailuser;
				$emailpass      = $phpmailer_vars['Password'] ?? $emailpass;
			}
		} else {
			$emailmethod   = sanitize_text_field( $emailmethod_raw );
			$emailsendmail = isset( $post['emailsendmail'] ) ? sanitize_email( $post['emailsendmail'] ) : '';
			$emailhost     = isset( $post['emailhost'] ) ? sanitize_text_field( $post['emailhost'] ) : '';
			$emailhostport = isset( $post['emailhostport'] ) ? absint( $post['emailhostport'] ) : 0;
			$emailsecure   = isset( $post['emailsecure'] ) ? sanitize_text_field( $post['emailsecure'] ) : '';
			$emailuser     = isset( $post['emailuser'] ) ? sanitize_text_field( $post['emailuser'] ) : '';
			$emailpass     = isset( $post['emailpass'] ) ? BackWPup_Encryption::decrypt( sanitize_text_field( $post['emailpass'] ) ) : '';
		}

		// Generate mail with Swift Mailer.
		if ( ! class_exists( \Swift::class, false ) ) {
			require BackWPup::get_plugin_data( 'plugindir' ) . '/vendor/SwiftMailer/swift_required.php';
		}

		$result = null;

		try {
			// Create the transport.
			if ( 'smtp' === $emailmethod ) {
				$transport = Swift_SmtpTransport::newInstance( $emailhost, $emailhostport );
				if ( $emailuser ) {
					$transport->setUsername( $emailuser );
					$transport->setPassword( $emailpass );
				}
				if ( 'ssl' === $emailsecure ) {
					$transport->setEncryption( 'ssl' );
				}
				if ( 'tls' === $emailsecure ) {
					$transport->setEncryption( 'tls' );
				}
			} elseif ( 'sendmail' === $emailmethod ) {
				$transport = Swift_SendmailTransport::newInstance( $emailsendmail );
			} else {
				$transport = Swift_MailTransport::newInstance();
			}
			// Create the mailer using the created transport.
			$emailer = Swift_Mailer::newInstance( $transport );

			$sender_email  = isset( $_POST['emailsndemail'] ) ? sanitize_email( wp_unslash( $_POST['emailsndemail'] ) ) : '';
			$sender_name   = isset( $_POST['emailsndemailname'] ) ? sanitize_text_field( wp_unslash( $_POST['emailsndemailname'] ) ) : '';
			$recipient_raw = isset( $_POST['emailaddress'] ) ? sanitize_text_field( wp_unslash( $_POST['emailaddress'] ) ) : '';
			$recipients    = $this->get_email_array( $recipient_raw );

			// Create a message.
			$message = Swift_Message::newInstance( __( 'BackWPup archive sending TEST Message', 'backwpup' ) );
			$message->setFrom( [ $sender_email => $sender_name ] );
			$message->setTo( $recipients );
			$message->setBody( __( 'If this message reaches your inbox, sending backup archives via email should work for you.', 'backwpup' ) );
			// Send the message.
			$result = $emailer->send( $message );
		} catch ( Exception $e ) {
			echo '<span id="emailsendtext" class="bwu-message-error">Swift Mailer: ' . esc_html( $e->getMessage() ) . '</span>';
		}

		if ( ! isset( $result ) || ! $result ) {
			echo '<span id="emailsendtext" class="bwu-message-error">' . esc_html__( 'Error while sending email!', 'backwpup' ) . '</span>';
		} else {
			echo '<span id="emailsendtext" class="bwu-message-success">' . esc_html__( 'Email sent.', 'backwpup' ) . '</span>';
		}

		exit();
	}

	/**
	 * Get an array of emails from comma-separated string.
	 *
	 * @param string $email_string Email string.
	 *
	 * @return array
	 */
	private function get_email_array( string $email_string ): array {
		$emails = explode( ',', sanitize_text_field( $email_string ) );

		foreach ( $emails as $key => $email ) {
			$emails[ $key ] = sanitize_email( trim( $email ) );
			if ( ! is_email( $emails[ $key ] ) ) {
				unset( $emails[ $key ] );
			}
		}

		return $emails;
	}

	/**
	 * Get PHPMailer object.
	 *
	 * @return PHPMailer
	 */
	private function getPhpMailer() {
		global $phpmailer, $wp_version;
		if ( ! is_object( $phpmailer ) || ! $phpmailer instanceof PHPMailer ) {
			if ( version_compare( $wp_version, '5.5', '>=' ) ) {
				require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';

				require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

				return new PHPMailer();
			}

			require_once ABSPATH . WPINC . '/class-phpmailer.php';

			require_once ABSPATH . WPINC . '/class-smtp.php';

			return new PHPMailer( true );
		}

		return $phpmailer;
	}

	/**
	 * Get service name.
	 *
	 * @return string
	 */
	public function get_service_name(): string {
		return 'Email';
	}
}
