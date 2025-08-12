/**
 * Restore Controller
 *
 * @type {*|{}}
 */

window.BWU = window.BWU || {};
window.BWU.Restore = window.BWU.Restore || {};
window.BWU.Restore.Factory = window.BWU.Restore.Factory || {};

(
	// eslint-disable-next-line
	function (
		BWU,
		ajaxurl,
		jsURL,
		plupload,
		_,
		$,
		backwpupRestoreLocalized
	) {
		'use strict';

		/**
		 * Database Connection
		 *
		 * @param save
		 * @param successCallback
		 */
		function databaseConnection( save, successCallback ) {

			var db = BWU.Restore.FactoryDatabase( this.url, this.nonce, JSON.stringify( {
				dbhost: document.querySelector( '#db_host' ).value.trim(),
				dbname: document.querySelector( '#db_name' ).value.trim(),
				dbuser: document.querySelector( '#db_user' ).value.trim(),
				dbpassword: document.querySelector( '#db_pw' ).value.trim(),
				dbcharset: document.querySelector( '#db_charset' ).value.trim()
			} ) );

			if ( !save ) {
				db.testConnection();

				return;
			}

			if ( _.isFunction( successCallback ) ) {
				db.saveConnectionSettings( successCallback );
			}
		}

		/**
		 * Show the Decryption Fields
		 *
		 * @param data
		 * @param error
		 */
		function showDecryptField( data, error ) {
			let error_message = {
				message: '',
			};
			if ( error && error.data ) {
				error_message = JSON.parse( error.data );
			}
			if ( data.state || error_message.message === BWU.States.NEED_DECRYPTION_KEY ) {
				this.decrypter.needDecryption( data.state );
			}
		}

		/**
		 * Ask to decrypt a backup
		 */
		function decrypt() {
			this.decrypter.decrypt( {
				backwpup_action_nonce: this.nonce,
				encrypted_file_path: this.urlParser( '?restore_file' )
			} );
		}

		/**
		 * Ask to decompress a backup
		 */
		function decompress() {
			$( '.restore-progress-container .progressbar' )
				.fadeIn( function () {
					this.decompresser.decompress();
				}.bind( this ) );
		}

		/**
		 * Load Step
		 *
		 * @param {object} evt The event object.
		 *
		 * @returns {BWU} this for chaining
		 */
		function loadStep( evt ) {
			evt.preventDefault();

			BWU.Restore.Functions.loadNextStep(
				parseInt( evt.currentTarget.getAttribute( 'data-next-step' ), 10 ),
				this.nonce
			);

			return this;
		}

		/**
		 * Add General listeners
		 *
		 * @returns {BWU} this for chaining
		 */
		function addListeners() {

			$( '#submit_decrypt_key' ).on( 'click', decrypt.bind( this ) );

			$( 'body' ).on( this.ACTION_UPLOAD_SUCCESS, decompress.bind( this ) );

			if ( this.downloader ) {
				$( 'body' ).on( this.downloader.ACTION_DOWNLOAD_REQUIRE_DECRYPTION, showDecryptField.bind( this ) );
				$( 'body' ).on( this.downloader.ACTION_DOWNLOAD_SUCCESS, this.decompresser.decompress );
			}

			$( 'body' ).on( this.decrypter.ACTION_DECRYPTION_SUCCESS, this.decompresser.decompress );

			$( 'body' ).on( this.decompresser.ACTION_DECOMPRESS_FAILED, showDecryptField.bind( this ) );
			$( 'body' ).on( this.decompresser.ACTION_DECOMPRESS_SUCCESS, function () {
				BWU.Restore.Functions.loadNextStep( 2, this.nonce );
			}.bind( this ) );
		}

		/**
		 * Initialize Uploader
		 *
		 * @returns {BWU} this for chaining
		 */
		function initializeUploader() {
			this.uploader = new this.uploader.Uploader(
				{
					runtimes: 'html5,flash,silverlight,html4',
					browse_button: 'plupload-browse-button',
					drop_element: 'drag-drop-area',
					multi_selection: false,
					url: this.url + '?action=upload&backwpup_action_nonce=' + this.nonce,
					chunk_size: '2mb',
					filters: {
						max_file_size: '0',
						mime_types: [
							{
								title: 'Zip files',
								extensions: 'zip,tar,tar.gz,tar.bz2,sql,sql.gz'
							}
						]
					},
					flash_swf_url: 'components/moxie/bin/flash/Moxie.swf',
					silverlight_xap_url: 'components/moxie/bin/silverlight/Moxie.xap',
					id: 'restore',
					init: {
						FilesAdded: function () {
							$( this.uploadElement )
								.hide();

							this.uploader.start();
						}.bind( this ),
						UploadProgress: function ( up, file ) {
							$( '#upload_progress' )
								.text( backwpupRestoreLocalized.uploadingArchive + file.percent + '%' );
						},
						FileUploaded: function ( up, file ) {

							if ( -1 === file.name.indexOf( '.sql' ) ) {
								$( 'body' ).trigger( this.ACTION_UPLOAD_SUCCESS, up, file );
							}
						}.bind( this ),
						Error: function ( up, err ) {
							BWU.Functions.printMessageError( err.message, $( '#restore_step' ) );
							$( 'body' ).trigger( this.ACTION_UPLOAD_FAILED, up, err );
						}
					}
				}
			);

			this.uploader.init();

			return this;
		}

		/**
		 * Trigger Download
		 *
		 * @returns {BWU} this for chaining
		 */
		function triggerDownloadByUrlQuery() {

			if ( !this.urlParser( '?trigger_download' ) ) {
				return false;
			}

			$( this.uploadElement )
				.hide();

			$( '#upload_progress' )
				.text( backwpupRestoreLocalized.downloadingArchive );

			$( '.restore-progress-container .progressbar' )
				.fadeIn();

			this.downloader.download();
		}

		var Controller = {

			/**
			 * Step 1
			 *
			 * @returns {BWU} this for chaining
			 */
			step1: function () {
				// Try to start the download automatically if requested.
				// The downloader exists only in the BackWPup plugin not in the standalone app.
				this.downloader && triggerDownloadByUrlQuery.call( this );

				// Set up the uploader plugin.
				initializeUploader.call( this );

				$( this.uploadElement )
					.on( 'dragover', function () {
						$( this.uploadElement )
							.addClass( 'drag-drop-active' );
					}.bind( this ) )
					.on( 'dragleave', function () {
						$( this.uploadElement )
							.removeClass( 'drag-drop-active' );
					}.bind( this ) )
					.on( 'drop', function () {
						$( this.uploadElement )
							.removeClass( 'drag-drop-active' );
					}.bind( this ) );

				return this;
			},

			/**
			 * Step 2
			 *
			 * @returns {BWU} this for chaining
			 */
			step2: function () {
				$( '.restore-select-strategy' )
					.on( 'click', function ( evt ) {
						evt.preventDefault();

						this.strategy.save( evt.currentTarget.getAttribute( 'data-strategy' ), function() {
							loadStep.call( this, evt );
							} );
					}.bind( this ) );

				return this;
			},

			/**
			 * Step 3
			 *
			 * @returns {BWU} this for chaining
			 */
			step3: function () {
				var that = this;

				$( '#db_edit_btn' )
					.on( 'click', function ( evt ) {
						evt.preventDefault();

						$( '#db-settings-form' ).find( 'input' ).removeAttr( 'readonly' );
						$( '#db_host' ).focus();
					} );

				$( '#db_test_btn' )
					.on( 'click', function ( evt ) {
						evt.preventDefault();
						databaseConnection.call( that, false );
					} );

				$( '#db_form_continue_btn' )
					.on( 'click', function ( evt ) {
						evt.preventDefault();
						// Because the button will trigger the step load and we'll not able to check for the connection.
						databaseConnection.call( that, true, function () {
							loadStep.call( that, evt );
						} );
					} );

				return this;
			},

			/**
			 * Step 4
			 *
			 * @returns {BWU} this for chaining
			 */
			step4: function () {
				$( '#do-migrate' )
					.on( 'change', function ( evt ) {
						this.migrate.retrieve( function (response) {
							if ( evt.currentTarget.checked == true ) {
								$( '#migration-old-url' ).val( response.data.message );
								$('#migration-settings-container').removeClass('hidden');
							} else {
								$('#migration-settings-container').addClass('hidden');
							}
						})
					}.bind( this ) );

					$( '#migration-form-continue-btn' )
						.on( 'click', function ( evt ) {
							evt.preventDefault();

							if ($( '#do-migrate' ).is( ':checked' )) {
								this.migrate.save( $( '#migration-old-url' ).val(), $( '#migration-new-url' ).val(), function (response) {
									if (response.success === true) {
										loadStep.call( this, evt );
									} else {
										BWU.Functions.printMessageError( response.data.message, $('#restore_step') );
									}
								} );
							} else {
								// If we're not migrating, then just continue
								loadStep.call( this, evt );
							}
						}.bind( this ) );

				return this;
			},

			/**
			 * Step 5
			 *
			 * @returns {BWU} this for chaining
			 */
			step5: function () {
				$( '#start-restore' )
					.on( 'click', function ( evt ) {
						var self = this;

						evt.preventDefault();

						this.strategy.retrieve( function ( response ) {

							if ( 'complete restore' === response.data.message ) {
								self.filesrestore
									.init()
									.restore();

								return;
							}

							if ( 'db only restore' === response.data.message ) {
								self.databaserestore
									.init()
									.restore();
							}
						} );
					}.bind( this ) );

				return this;
			},

			/**
			 * Initialize Controller
			 *
			 * @returns {BWU} this for chaining
			 */
			init: function () {
				addListeners.call( this );

				return this;
			},

			/**
			 * Construct
			 *
			 * @param {string} url The url where to call the server.
			 * @param {string} nonce The nonce to send to the server.
			 * @param {Function} urlParser The function used to parse urls.
			 * @param {Object} uploader The file uploader object.
			 * @param {HTMLElement} uploadElement The element used as upload area.
			 * @param {Object} strategy The object to use for handle strategy.
			 * @param {Object} databaserestore The object to use to restore the database.
			 * @param {Object} filesrestore The object to use to restore the files.
			 * @param {Object} decompresser The object to use to decompress the archive.
			 * @param {Object} downloader The object to download the file.
			 *
			 * @returns {BWU} this for chaining
			 */
			// eslint-disable-next-line
			construct: function (
				url,
				nonce,
				urlParser,
				uploader,
				uploadElement,
				strategy,
				databaserestore,
				filesrestore,
				decompresser,
				downloader,
				decrypter,
				migrate
			) {
				_.bindAll(
					this,
					'step1',
					'step2',
					'step3',
					'step4',
					'step5',
					'init'
				);

				this.url = url;
				this.nonce = nonce;
				this.urlParser = urlParser;
				this.uploadElement = uploadElement;
				this.uploader = uploader;
				this.strategy = strategy;
				this.databaserestore = databaserestore;
				this.filesrestore = filesrestore;
				this.decompresser = decompresser;
				this.downloader = downloader;
				this.decrypter = decrypter;
				this.migrate = migrate;

				return this;
			}
		};

		/**
		 * Factory Controller
		 *
		 * @param {string} url The url where to call the server.
		 * @param {string} nonce The nonce to send to the server.
		 * @param {Function} urlParser The function used to parse urls.
		 * @param {Object} uploader The file uploader object.
		 * @param {HTMLElement} uploadElement The element used as upload area.
		 * @param {Object} strategy The object to use for handle strategy.
		 * @param {Object} databaserestore The object to use to restore the database.
		 * @param {Object} filesrestore The object to use to restore the files.
		 * @param {Object} decompresser The object to use to decompress the archive.
		 * @param {Object} downloader The object to download the archive.
		 *
		 * @returns {Controller} this for chaining
		 */
		// eslint-disable-next-line
		BWU.Restore.FactoryController = function (
			url,
			nonce,
			urlParser,
			uploader,
			uploadElement,
			strategy,
			databaserestore,
			filesrestore,
			decompresser,
			downloader,
			decrypter,
			migrate
		) {
			return Object
				.create( Controller, {
					ACTION_UPLOAD_SUCCESS: BWU.Functions.makeConstant( 'backwpup.upload_success' ),
					ACTION_UPLOAD_FAILED: BWU.Functions.makeConstant( 'backwpup.upload_error' )
				} )
				.construct(
					url,
					nonce,
					urlParser,
					uploader,
					uploadElement,
					strategy,
					databaserestore,
					filesrestore,
					decompresser,
					downloader,
					decrypter,
					migrate
				);
		};
	}(
		window.BWU,
		window.ajaxurl,
		window.url,
		window.plupload,
		window._,
		window.jQuery,
		window.backwpupRestoreLocalized
	)
);
