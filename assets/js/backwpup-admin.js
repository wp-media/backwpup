let requestWPApi, loadBackupsListingAndPagination, getUrlParameter, backwpupDisplaySettingsToast;
jQuery(document).ready(function ($) {
  const $document = $(document); // Cache document lookup

  // Helpers
  async function postToWP(data) {
    return await fetch(ajaxurl, {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "Cache-Control": "no-cache",
      },
      body: new URLSearchParams(data),
    }).then((response) => response.json());
  }

  /**
   * Request the WP Rest API.
   * @param string route
   * @param array data
   * @param function callback
   * @param string method (default: 'GET')
   */
  requestWPApi = function requestWPApi(route, data, callback, method = 'GET', error_callback = null) {
	  const $trigger = $(document.activeElement);
	  const $overlayTemplate = $('#backwpup-loading-overlay-template').children().first();
	  const $jobCard = $trigger.closest('.backwpup-job-card');

	  let $overlay;

	  if ($jobCard.length) {
		  // Add overlay to the job card
		  $overlay = $overlayTemplate.clone();
		  $overlay.find('svg').addClass('animate-spin');
		  $jobCard.find('.backwpup-loading-overlay').remove();
		  $jobCard.append($overlay);
	  }

	  $.ajax({
		  url: route,
		  beforeSend: function(xhr) {
			  xhr.setRequestHeader('X-WP-Nonce', backwpupApi.nonce);
		  },
		  method: method,
		  data: data,
		  success: function(response) {
			  $overlay?.remove();
			  $trigger.prop('disabled', false);
			  $trigger.siblings('.backwpup-loading-overlay').remove();
			  callback(response, data);
		  },
		  error: function(request, error) {
			  console.error("API Request Failed:", route, method, data, request.status, request.statusText);
			  console.trace("Error triggered in requestWPApi");

			  $overlay?.remove();
			  $trigger.prop('disabled', false);
			  $trigger.siblings('.backwpup-active-spinner').remove();
			  if (error_callback) {
				  error_callback(request, error);
			  }
		  }
	  });
  }

  // Function to enable or disable all the backup button.
  function enableBackupButton(enable = true) {
    $(".backwpup-button-backup").prop("disabled", !enable);
    $('.backwpup-btn-backup-job').prop('disabled', !enable);
    let toolTipVisibility = enable ? 'visible' : 'hidden';
    jQuery(".backwpup-btn-backup-job span span.tooltip").css("visibility", toolTipVisibility);
  }

  function enableDeleteJob(enable = true) {
    console.log("enableDeleteJob", enable);
    if ( enable ) {
      $(".js-backwpup-delete-job").removeClass("disabled");
    } else {
      $(".js-backwpup-delete-job").addClass("disabled");
    }
  }
  // Function to get URL parameter.
    getUrlParameter = function getUrlParameter(name, defaultValue="") {
    let searchParams = new URLSearchParams(window.location.search)
    if (searchParams.has(name)) {
      return searchParams.get(name);
    } else {
      return defaultValue;
    }
  }

    /**
     * Display a toast notification in the settings page.
     * @param type disable auto-remove if type is not 'success'.
     * @param message
     * @param duration - The duration in milliseconds to display the toast. Default is 5000ms. Set to -1 to disable auto-remove.
     */
    backwpupDisplaySettingsToast = function backwpupDisplaySettingsToast(type = 'info', message = '', duration = 5000) {
        if(!message) {
            return;
        }
        requestWPApi(
            backwpupApi.getblock,
            {
                'block_name': 'alerts/info',
                'block_type': 'component',
                'block_data': {
                    'type': type,
                    'font': 'small',
                    'dismiss_icon': true,
                    'content': message
                },
            },
            function(response) {
                const toast = jQuery('<div class="transform translate-y-2 transition-all duration-300"></div>').html(response);
                $('#bwp-settings-toast').html('');
                $('#bwp-settings-toast').append(toast);
                // Animate in
                setTimeout(() => {
                    toast.addClass('opacity-100 translate-y-0');
                }, 10);

                // Auto-remove after duration
                if (duration !== -1 || type !== 'success') {
                    setTimeout(() => {
                        toast.removeClass('opacity-100 translate-y-0').addClass('opacity-0 translate-y-2');
                        setTimeout(() => {
                            toast.remove();
                        }, 300);
                    }, duration);
                }
            },
            'POST',
            function(request, error) {
                console.log(error,request);
            }
        );
    }

  // Function to be sure the value on this class is an integer.
  $(".js-backwpup-intonly").on('keydown', function(event) {
    return ['Backspace','Delete','ArrowLeft','ArrowRight'].includes(event.code) ? true : !isNaN(Number(event.key)) && event.code!=='Space';
  });

  // Function to refresh the storage destinations.
  window.refresh_storage_destinations= function (job_id, destinationName, authenticated) {
    let checkBox = $('#destination-'+destinationName);
    checkBox.prop('checked', authenticated);

    const storage_destinations = [""];
    $('input[name="storage_destinations[]"]').each(function () {
      if ($(this).is(':checked')) {
        storage_destinations.push($(this).val());
      }
    });
    requestWPApi(
      backwpupApi.updatejob,
      {
        'job_id': job_id,
        'storage_destinations': storage_destinations,
      },
      function (response) {
        requestWPApi(
          backwpupApi.storagelistcompact,
          {},
          function (response) {
            $("#backwpup-storage-list-compact-container").html(response);
          },
          "GET",
          function (request, error) {
            $("#backwpup-storage-list-compact-container").html(request.responseText);
          }
        )
      },
      "POST",
      function (request, error) {
        $("#backwpup-storage-list-compact-container").html(request.responseJSON.error);
      }
    );
    // Check if there is a storage selected to enable the submit button.
    const checkedStorageCheckboxes = $('input[type="checkbox"][name^="onboarding_storage"]:checked');
    if (0 !== checkedStorageCheckboxes.length) {
      $(".js-backwpup-onboarding-submit-form").prop("disabled", false);
    } else {
      $(".js-backwpup-onboarding-submit-form").prop("disabled", true);
    }
  }

  // Refresh the gdrive authentification block.
  window.gdrive_refresh_authentification = function() {
    requestWPApi(
      backwpupApi.cloud_is_authenticated,
      {
        'cloud_name': 'gdrive',
      },
      function(response) {
        $('#gdrive_authenticate_label').html(response);
      },
      "GET"
    )
  }

  //Refresh the onedrive authentification block.
  window.onedrive_refresh_authentification = function() {
    requestWPApi(
      backwpupApi.cloud_is_authenticated,
      {
        'cloud_name': 'onedrive',
      },
      function(response) {
        $('#onedrive_authenticate_label').html(response);
      },
      "GET"
    )
  }

  //Refresh the dropbox authentification block.
  window.dropbox_refresh_authentification = function(job_id) {
    requestWPApi(
      backwpupApi.cloud_is_authenticated,
      {
		'job_id' : job_id,
        'cloud_name': 'dropbox',
      },
      function(response) {
        $('#drobox_authenticate_infos').html(response);
      },
      "GET"
    )
  }

  /**
   * Updates the license by sending a request to the WordPress API.
   *
   * This function collects the license action and instance key from the DOM,
   * constructs a data object, and sends it to the WordPress API endpoint for
   * license updates. Depending on the action (activate or deactivate), it may
   * also include the license API key and product ID. After the request is sent,
   * it handles the response by displaying a message and refreshing the license
   * block.
   *
   * @function
  */
  window.update_license = function() {
    const license_action = $('#license_action').val();
    const license_instance_key = $('#license_instance_key').val();
    const data = {
      'license_action': license_action,
      'license_instance_key': license_instance_key,
      'license_submit': true,
    }
    let next_block = 'activate';
    if ('activate' === license_action) {
      next_block = 'deactivate';
      data['license_api_key'] = $('#license_api_key').val();
      data['license_product_id'] = $('#license_product_id').val();
    }
    requestWPApi(
      backwpupApi.license_update,
      data,
      function(response) {
        requestWPApi(
          backwpupApi.getblock,
          {
            'block_name': 'alerts/info',
            'block_type': 'component',
            'block_data': {
              'type': 'info',
              'font': 'xs',
              'content': response.message
            },
          },
          function(response) {
            $('#backwpup_message').html(response);
          },
          'POST',
          function(request, error) {
            console.log(request.responseJSON.error);
          }
        );
        backwpup_license_refresh(next_block);
      },
      'POST',
      function(request, error) {
        requestWPApi(
          backwpupApi.getblock,
          {
            'block_name': 'alerts/info',
            'block_type': 'component',
            'block_data': {
              'type': 'alert',
              'font': 'xs',
              'content': request.responseJSON.error
            },
          },
          function(response) {
            $('#backwpup_message').html(response);
          },
          'POST',
          function(request, error) {
            console.log(request.responseJSON.error);
          }
        );
      }
    );
  }

  /**
   * Refreshes the license block in the sidebar.
   *
   * This function constructs the block name based on the activation status
   * and sends a request to the WordPress API to retrieve the updated license
   * block. The response is then used to update the DOM with the new license
   * block content.
   *
   * @function
   * @param {string} activated - The activation status, either 'activate' or 'deactivate'.
  */
  window.backwpup_license_refresh = function(activated) {
    let block_name = 'sidebar/license-parts/'+activated;
    requestWPApi(
      backwpupApi.getblock,
      {
        'block_name': block_name,
        'block_type': 'children',
      },
      function(response) {
        $('#backwpup_license').html(response);
        $('.js-backwpup-license_update').on('click', update_license);
      },
      'POST'
    );
  }

  /**
   * Load and open the storage sidebar using the WordPress API.
   *
   * @param {*} event
   */
  window.load_and_open_storage = function(event) {
    let that = $(event.currentTarget);
    let panel = that.data("content");
    let job_id = that.data("job-id");
    let storage = that.data("storage");
    let target = $sidebar.find("#sidebar-" + panel);
    requestWPApi(
      backwpupApi.getblock,
      {
        'block_name': 'sidebar/' + panel,
        'block_type': 'children',
        'block_data': {
          'job_id': job_id,
          'is_in_form': false,
        },
      },
      function(response) {
        target.html(response);
        $(".js-backwpup-close-sidebar").on('click', closeSidebar);
        $(".js-backwpup-load-and-open-sidebar").on('click', load_and_open_sidebar);
        switch (storage) {
          case 'DROPBOX':
            initDropboxEvents();
            break;
          case 'SUGARSYNC':
            initSugarSyncEvents();
            $('.js-backwpup-test-' + storage + '-storage').on('click', window['test_' + storage + '_storage']);
            break;
          case 'GDRIVE':
            initGdriveEvents();
            $('.js-backwpup-test-GDRIVE-storage').on('click', window['test_GDRIVE_storage']);
            break;
          case 'HIDRIVE':
            initHidriveEvents();
            break;
          case 'ONEDRIVE':
            initOnedriveEvents();
            $('.js-backwpup-test-ONEDRIVE-storage').on('click', window['test_ONEDRIVE_storage']);
            break;
          default:
            $('.js-backwpup-test-' + storage + '-storage').on('click', window['test_' + storage + '_storage']);
            break;
        }

        openSidebar(panel);
      },
      'POST',
      function(request, error) {
        console.log(error);
        console.log(request);
      }
    );
  }

  // Sidebar & Modal
  const $modal = $("#backwpup-modal");
  const $sidebar = $("#backwpup-sidebar");

  function openSidebar(panel) {
    // Fill infos
    $sidebar.find("article").hide();
    $sidebar.find("#sidebar-" + panel).show();

    // Animate
    $("body").addClass("overflow-hidden");
    $sidebar.removeClass("translate-x-[450px]");
  }

  function closeSidebar() {
    $("body").removeClass("overflow-hidden");
    $sidebar.addClass("translate-x-[450px]");
  }

	/**
	 * Load and open the sidebar using the WordPress API.
	 *
	 * @param {*} event
	 */
	window.load_and_open_sidebar = function(event) {
		let that = $(event.currentTarget);
		let panel = that.data("content"); // Get the panel to open
		let job_id = that.data("job-id");
		$sidebar.find("article").hide();
		let target = $sidebar.find("#sidebar-" + panel);

		// Set default block data payload.
		let block_data = {
		  'job_id': job_id,
            'job_type': that.data("job-type")
		};

		// Define basic frequency settings selectors on the onboarding screen.
		let basic_frequency_selectors = {
		  files: $('.js-backwpup-onboarding-files-frequency'),
		  database: $('.js-backwpup-onboarding-database-frequency')
		}

		// Check that we are requesting frequency settings and basic frequency settings are present.
		if ('frequency' === panel && basic_frequency_selectors.files.is(':visible') && basic_frequency_selectors.database.is(':visible')) {
		  let basic_frequency_data = {
			job_2: basic_frequency_selectors.files.val(),
			job_3: basic_frequency_selectors.database.val()
		  }

		  // Update block data payload with basic frequency data.
		  block_data['basic_frequency'] = basic_frequency_data[`job_${job_id}`];
		}

		requestWPApi(
			backwpupApi.getblock,
			{
				'block_name': that.data("block-name"),
				'block_type': that.data("block-type"),
				'block_data': block_data,
			},
			function(response) {
				// Fill infos
				target.html(response);
				openSidebar(panel);

        // Trigger custom event for disabling elements with legacy frequency start days set.
        $document.trigger('disableLegacyFrequency', { panel: panel });
       
				$(".js-backwpup-close-sidebar").on('click', closeSidebar);
				$(".js-backwpup-toggle-storage").on('click', load_and_open_storage);
			},
			'POST',
			function(request, error) {
				console.log(error);
				console.log(request);
			}
		);
	}
	$document.on('click', '.js-backwpup-load-and-open-sidebar', load_and_open_sidebar);

  // Array of legacy frequency start days.
  const legacy_start_days = [
    'first-monday',
    'first-sunday',
  ];

  let save_settings_button, start_time, day_of_month;

  $document.on('disableLegacyFrequency', function (_, data) {
    save_settings_button = $('#save-job-settings');
    start_time = save_settings_button.closest("article").find("input[name='start_time']");
    day_of_month = $('#backwpup_day_of_month');

    // Bail out if panel is not frequency.
    if ('frequency' !== data.panel) {
      return;
    }

    // Disable start time and save button when start day is legacy.
    if (legacy_start_days.includes(day_of_month.val())) {
      start_time.prop('disabled', true);
      save_settings_button.prop('disabled', true);
    }
  });

  // Array of target select elements to watch on change.
  const frequency_field_targets = [
    '#backwpup_day_of_month',
    '#backwpup_frequency',
  ];
  
  frequency_field_targets.forEach(selector => {
    $('#backwpup-sidebar').on('change', selector, function() {
      if (legacy_start_days.includes(day_of_month.val()) && 'monthly' === $('#backwpup_frequency').val()) {
        start_time.prop('disabled', true);
        save_settings_button.prop('disabled', true);

        return;
      }
      
      start_time.prop('disabled', false);
      save_settings_button.prop('disabled', false);
    });
  });

	/**
	 * Load and open modals using the WordPress API.
	 *
	 * @param {*} event
	 */
	window.load_and_open_modal = function(event) {
		let that = $(event.currentTarget);
		let panel = that.data("content"); // Get the panel to open
		let job_id = that.data("job-id");
		let target = $modal.find("#sidebar-" + panel);

		requestWPApi(
			backwpupApi.getblock,
			{
				'block_name': that.data("block-name"),
				'block_type': that.data("block-type"),
				'block_data': {
					'job_id': job_id,
				},
			},
			function(response) {
				// Fill infos
				target.html(response);
				openModal(panel);
				$(".js-backwpup-close-modal").on('click', closeModal);
			},
			'POST',
			function(request, error) {
				console.log(error);
				console.log(request);
			}
		);
	}
	$document.on('click', '.js-backwpup-load-and-open-modal', load_and_open_modal);

	window.load_exclusions_modal = function(job_id, panel) {
		let target = $modal.find("#sidebar-" + panel);
		let block_name = 'modal/'+ panel;
		let block_type = 'children';

		requestWPApi(
			backwpupApi.getblock,
			{
				'block_name': block_name,
				'block_type': block_type,
				'block_data': {
					'job_id': job_id,
				},
			},
			function(response) {
				// Fill infos
				target.html(response);
				$(".js-backwpup-close-modal").on('click', closeModal);
			},
			'POST',
			function(request, error) {
				console.log(error);
				console.log(request);
			}
		);
	}

	$document.on('click', '.js-data-settings-files, .onboarding-advanced-files-settings ', function () {
		let that = $(this);
		let job_id = that.data("job-id");
		let panels = ['exclude-files-core', 'exclude-files-plugins', 'exclude-files-root', 'exclude-files-themes', 'exclude-files-uploads', 'exclude-files-wp-content'];
		panels.forEach(function (panel) {
			load_exclusions_modal(job_id, panel)
		});
	});

	$document.on('click', '.js-backwpup-open-sidebar', function () {
	    const panel = $(this).data("content");
	    openSidebar(panel);
	});

  $(".js-backwpup-close-sidebar").on('click', closeSidebar);

  $(".js-backwpup-open-url").on('click', function() {
    if ($(this).data("href")) {
      window.location= $(this).data("href");
    }
  });

  function openModal(panel, dataset={}) {
    // Fill informations
    $modal.find("article").hide();
    let thePanel = $modal.find("#sidebar-" + panel);
    thePanel.show();
    if (dataset.url) {
      thePanel.find(".js-backwpup-open-url").attr("data-href", dataset.url);
    }
    // Animate
    $("body").addClass("overflow-hidden");
    $modal.removeClass("hidden").addClass("flex");
  }

  function closeModal() {
    $("body").removeClass("overflow-hidden");
    $modal.addClass("hidden").removeClass("flex");
  }

  //Refresh the SugarSync root folders.
  window.refreshSugarSyncRootFolders = function(job_id) {
      const $overlayTemplate = $('#backwpup-loading-overlay-template').children().first();
      const $sugarsyncSidebar = $('#sidebar-storage-SUGARSYNC');

      let $overlay;

      if ($sugarsyncSidebar.length) {
          // Add overlay to the sidebar.
          $overlay = $overlayTemplate.clone();
          $overlay.find('svg').addClass('animate-spin');
          $sugarsyncSidebar.find('.backwpup-loading-overlay').remove();
          $sugarsyncSidebar.append($overlay);
      }

    // Reload the root folder list.
    requestWPApi(
      backwpupApi.getblock,
      {
        'block_name': 'sidebar/sugar-sync-parts/root-folder',
        'block_type': 'children',
        'block_data': {
          'job_id': job_id,
        },
      },
      function(response) {
        $('#sugarsyncroot').html(response);
          $overlay?.remove();
      },
      'POST',
      function (request, error) {
        $('#sugarsyncroot').html(request.responseText);
          $overlay?.remove();
      }
    );
  }

  function initSugarSyncEvents() {
    // Toggle SugarSync authenticate action.
    $('.js-backwpup-authenticate-sugar-sync').on('click', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const page = urlParams.get('page');
      let job_id = $(this).data("job-id");
      if (page === 'backwpuponboarding') {
        job_id = null;
      }
      let data = {
        'job_id': job_id,
        'cloud_name' : 'sugarsync',
        'sugaremail' : $('#sugaremail').val(),
        'sugarpass' : $('#sugarpass').val(),
      };

      requestWPApi(
        backwpupApi.authenticate_cloud,
        data,
        function(response) {
          $('#sugarsynclogin').html(response);
          $('#sugarsync_authenticate_infos').html("");
          refreshSugarSyncRootFolders(job_id);
          initSugarSyncEvents();
        },
        "POST",
        function(request, error) {
          $('#sugarsync_authenticate_infos').html(request.responseText);
          refreshSugarSyncRootFolders(job_id);
        }
      );
    });

    // Delete Sugar Sync authentication.
    $('.js-backwpup-delete-sugar-sync-auth').on('click', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const page = urlParams.get('page');
      let job_id = $(this).data("job-id");
      if (page === 'backwpuponboarding') {
        job_id = null;
      }
      const data = {
        'job_id': job_id,
        'cloud_name' : 'sugarsync',
      }
      requestWPApi(
        backwpupApi.delete_auth_cloud,
        data,
        function (response) {
          refresh_storage_destinations(job_id, 'SUGARSYNC', false);
          $('#sugarsynclogin').html(response);
          refreshSugarSyncRootFolders(job_id);
          initSugarSyncEvents();
        },
        "POST",
        function (request, error) {
          alert("Error in cloud configuration");
        }
      );
    });
  }

  // Toggle Google Drive authenticate action.
  function initGdriveEvents() {
    // Toggle Gdrive Api connection first step.
    $('.js-backwpup-gdrive-connect-api').on('click', function() {
      const details = $(this).closest('details');
      let data = {
        'backwpup_cfg_googleclientsecret' : {
          'value': $('#backwpup_cfg_googleclientsecret').val(),
          'secure': true,
        },
        'backwpup_cfg_googleclientid' : {
          'value': $('#backwpup_cfg_googleclientid').val(),
          'secure': false,
        },
      }

      requestWPApi(
        backwpupApi.save_site_option,
        data,
        function (response) {
          $('#gdrive_authenticate_infos').html(response.message);
          // Remove the 'open' attribute
          details.removeAttr('open');
        },
        "POST",
        function (request, error) {
          $('#gdrive_authenticate_infos').html("Error");
        }
      )
    });

    // Toggle Gdrive reauthenticate action.
    $('.js-backwpup-gdrive-reauthenticate').on('click', function() {
      openModal('dialog');
      $('.js-backwpup-refresh-authentification').data('trigger', 'gdrive_refresh_authentification');
      window.open($(this).data('url'), '_blank');
    });
  }


  // Initialize Dropbox cloud events.
  function initDropboxEvents() {
    $('.js-backwpup-modal-and-focus').on('click', modal_and_focus);
    // Test and save Dropbox storage.
    $('.js-backwpup-test-DROPBOX-storage').on('click', function () {
      let job_id = $(this).data("job-id");
      const urlParams = new URLSearchParams(window.location.search);
      const page = urlParams.get('page');
      if (page === 'backwpuponboarding') {
        job_id = null;
      }
      const data = {
        'job_id': job_id,
        'cloud_name' : 'dropbox',
        'dropboxmaxbackups' : $("#dropboxmaxbackups").val(),
        'dropboxdir' : $("#dropboxdir").val(),
      };
      let dropbbox_code = $("#dropbbox_code").val();
      if (dropbbox_code) {
        data['dropbbox_code'] = dropbbox_code;
      }
      let sandbox_code = $("#sandbox_code").val();
      if (sandbox_code && !dropbbox_code) {
        data['sandbox_code'] = sandbox_code;
      }
      requestWPApi(
        backwpupApi.cloudsaveandtest,
        data,
        function (response) {
          refresh_storage_destinations(job_id, 'DROPBOX', response.connected);
          dropbox_refresh_authentification(job_id);
          closeSidebar();
        },
        "POST",
        function (request, error) {
          refresh_storage_destinations(job_id, 'DROPBOX', false);
          alert(request.responseJSON.error);
        }
      );
    });

    // Delete Dropbox authentication.
    $('.js-backwpup-delete-dropbox-auth').on('click', function() {
      let job_id = $(this).data("job-id");
      const urlParams = new URLSearchParams(window.location.search);
      const page = urlParams.get('page');
      if (page === 'backwpuponboarding') {
        job_id = null;
      }
      const data = {
        'job_id': job_id,
        'cloud_name' : 'dropbox',
        'delete_auth' : true,
        'dropboxmaxbackups' : $("#dropboxmaxbackups").val(),
        'dropboxdir' : $("#dropboxdir").val(),
      }
      requestWPApi(
        backwpupApi.cloudsaveandtest,
        data,
        function (response) {
          refresh_storage_destinations(job_id, 'DROPBOX', response.connected);
          dropbox_refresh_authentification(job_id);
          closeSidebar();
        },
        "POST",
        function (request, error) {
          alert(request.responseJSON.error);
        }
      );
    });
  }

  /**
   * Initialize the modal event
   */
  function initModalEvent() {
	  // Delegated (for future elements)
	  $(document).on('click', '.js-backwpup-open-modal', handler);

	  // Direct (for current elements)
	  $('.js-backwpup-open-modal').on('click', handler);

	  function handler() {
		  const panel = $(this).data("content");
		  let dataset = {};
		  if ($(this).data("url")) {
			  dataset.url = $(this).data("url");
		  }
		  openModal(panel, dataset);
	  }
  }

  $document.on('click', '.js-backwpup-close-modal', closeModal);

  // Filter table list
  $document.on('keyup', '.js-backwpup-filter-tables', function () {
    const filter = $(this).val().toLowerCase();

    $(".js-backwpup-tables-list label").each(function () {
      if (
        filter === "" ||
        $(this).find("input").attr("value").toLowerCase().includes(filter)
      ) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  });

  $(".js-backwpup-filter-tables")
    .next(".js-backwpup-clear-search")
    .on('click', function () {
      $(".js-backwpup-tables-list label").show();
    });

  // Select all lines in backup table
  const $backupsTable = $("#backwpup-backup-history");
  
  let isCheckboxListenerInitialized = false;

  $(".js-backwpup-select-all").on('change', function () {
    const checked = $(this).prop("checked");
    $("#bulk-actions-apply").prop("disabled", !checked);
    $backupsTable.find("input[type=checkbox]").prop("checked", checked);

    // Initialize the event listener for individual checkboxes only once
    if (!isCheckboxListenerInitialized) {
      $backupsTable.on("change", "input[type=checkbox]", function () {
        const allChecked = $backupsTable.find("input[type=checkbox]").length === $backupsTable.find("input[type=checkbox]:checked").length;
        $(".js-backwpup-select-all").prop("checked", allChecked);
      });
      isCheckboxListenerInitialized = true;
    }

  });

  /**
   * Initialize the menu event.
   */
  function initMenuEvent() {
    // Menu
    $(".js-backwpup-menu").on('click', function (event) {
      event.stopPropagation();
      const $menu = $(this).find(".js-backwpup-menu-content");

      $(".js-backwpup-menu-content").not($menu).addClass("hidden");

      if ($menu.hasClass("hidden")) {
        $menu.removeClass("hidden");
      } else {
        $menu.addClass("hidden");
      }
    });
    // Backup select
    // This button is disabled by default.
    $("#bulk-actions-apply").prop("disabled", true);
    $(".js-backwpup-select-backup").on('click', function () {
      // Vérifier si au moins un élément est coché
      const isChecked = $(".js-backwpup-select-backup:checked").length > 0;
      // Activer ou désactiver le bouton en fonction de la vérification
      $("#bulk-actions-apply").prop("disabled", !isChecked);
    });
  }

  $(document).on('click', function () {
    $(".js-backwpup-menu-content").addClass("hidden");
  });

  /**
   * Initialize the pagination event.
   */
  function initPaginationEvent() {
    $(".js-backwpup-table-pagination button").on('click', function () {
      let page = $(this).data("page");
      loadBackupsListingAndPagination(page);
      // Update URL
      let url = new URL(window.location.href);
      url.searchParams.set("page_num", page);
      history.pushState({}, "", url);
    });
  }

  /**
   * Load the backups listing and the pagination from the api
   * @param page
   */
  loadBackupsListingAndPagination = function loadBackupsListingAndPagination(page) {
    requestWPApi(backwpupApi.backupslistings, {page: page, length:  backwpupApi.backupslistingslength}, refreshBackupTable, 'POST');
  }


  // Next Scheduled Action Tables
  $(".js-backwpup-toggle-tables").on('click', function () {
    const value = $(this).is(":checked");

    const data = {
      action: "backwpup_toggle_database",
      tables: value,
    };

    postToWP(data).then((body) => {});
  });

  // Clear search field
  $(".js-backwpup-clear-search").on('click', function () {
    $(this).prev().val("");
  });

  // Add tag field
  $document.on('click', '.js-backwpup-add-input-button', function () {
    // Get value and clear field
    const tag = $(this).prev().val().trim(); // Trim whitespace
    if ( tag === "" ) return;
    $( this ).prev().val( "" );

    // Update hidden input
    let values = $( ".js-backwpup-add-input-values" ).val().split( "," );
    if (!values.includes( tag )) {
      values.push( tag );
      values = [...new Set( values )]; // Ensure unique values
      $( ".js-backwpup-add-input-values" ).val( values.join( "," ) );

      // Prevent duplicates on the frontend
      const existingTags = $( ".js-backwpup-add-input-tags button span" )
        .map( function () {
          return $( this ).text();
        })
        .get();

      if ( ! existingTags.includes( tag ) ) {
        // Add tag to the list
        const $newTag = $( this )
          .parents( ".js-backwpup-add-input" )
          .find( ".js-backwpup-add-input-tag-template button" )
          .clone();

        $newTag.find( "span" ).text( tag );
        $newTag.appendTo( ".js-backwpup-add-input-tags" );
      }
    }
  });

  $document.on(
    "click",
    ".js-backwpup-remove-tag",
    function () {
      // Remove tag from list
      $(this).remove();

      // Update hidden input
      let values = $(".js-backwpup-add-input-values").val().split(",");
      values = values.filter((value) => value !== $(this).data("tag"));
      $(".js-backwpup-add-input-values").val(values.join(","));
    },
  );

  // Toggle include / exclude files
  $document.on('click', ".js-backwpup-toggle-include button" , function () {
    const $element = $(this).parents(".js-backwpup-toggle-include");
    const $checkbox = $element.find("input[type=checkbox]");

    $checkbox.prop("checked", !$checkbox.prop("checked"));

    if ($checkbox.prop("checked")) {
      $element.find(".js-backwpup-toggle-include-add").addClass("hidden");
      $element.find(".js-backwpup-toggle-include-remove").removeClass("hidden");
    } else {
      $element.find(".js-backwpup-toggle-include-add").removeClass("hidden");
      $element.find(".js-backwpup-toggle-include-remove").addClass("hidden");
    }
  });

  // Start Backup Now
  $(".js-backwpup-start-backup-now").on('click', function () {
    enableBackupButton(false);
    requestWPApi(backwpupApi.startbackup, {}, function(response) {
      if ( response.status === 200 ) {
        window.location.reload();
      }
    },
    'POST',
    function (request, error) {
      console.log(request);
      console.log(error);
      enableBackupButton(true);
    });
  });

  // Exclude files in sidebar
  $document.on('change', '.js-backwpup-toggle-exclude', function () {
    const checked = $(this).prop("checked");
    $(this).closest("div").find("button").prop("disabled", !checked);
  });

  // Toggle Files.
  $("#backwup-next-scheduled-backups").on('change', '.js-backwpup-toggle-job', function () {
    const checked = $(this).prop("checked");
    let job_id = $(this).data("job-id");
    let tooltip_text = 'Disable';
    if(!checked) {
        tooltip_text = 'Enable'
    }
      const $tooltip = $(`#backwpup-${job_id}-options div`).find('[data-tooltip-position]:eq(2)');
      const spanHTML = $tooltip.find('span').prop('outerHTML');

    $(`#backwpup-${job_id}-options`).find("button:not(.always-enabled)").prop("disabled", !checked);
    requestWPApi(
        backwpupApi.updatejob,
        {
          'job_id': job_id,
          'activ': checked
        },
        function (response) {
            $(`#backwpup-${job_id}-options div span.label-scheduled`).html(response.message);
            $tooltip.html(tooltip_text + ' ' + spanHTML);
        },
        "POST"
    );
  });


  // Toggle Delete Job.
  $("#backwup-next-scheduled-backups").on('click', ".js-backwpup-delete-job", function () {
    let job_id = $(this).data("job-id");
    requestWPApi(
        backwpupApi.delete_job,
        {
          'job_id': job_id,
        },
        function (response) {
          if (response.success) {
            $(`#backwpup-${job_id}-options`).remove();
            if ($('.backwpup-job-card').length === 0) {
              $("#backwpup-backup-now").prop("disabled", true);
            }

            loadBackupsListingAndPagination(getUrlParameter('page_num', 1));
          }
        },
        "DELETE",
        function(request) {
          backwpupDisplaySettingsToast( 'danger', request.responseJSON.message );
        }
    );
  });

  $("#backwup-next-scheduled-backups").on('change', '.backwpup-dynamic-backup-type', function() {
    if (0 === $('#js-backwpup-add-new-backup-form').find('input[name="type"]:checked').length) {
      $( this ).prop( "checked", true );
      return; // Do not allow empty selection.
    }
    if (!$(this).is(':checked')) {
      // Remove classes from the selected label and its child divs
      $(this).closest('label').removeClass('bg-secondary-lighter border-secondary-base');
      $(this).closest('label').find('div').removeClass('border-secondary-base');
    } else {
      // Add classes to the selected label and its child div
      $(this).closest('label').addClass('bg-secondary-lighter border-secondary-base');
      $(this).closest('label').find('div').addClass('border-secondary-base');
    }
  });

  const $target_dynamic_card = '.backwpup-dynamic-backup-card';

  const toggleDynamicCardDisplay = (target, state = 'hidden') => {
    const new_backup_card = '.backwpup-add-new-backup-card';
    switch (state) {
      case 'visible':
         // Check for target visibility.
        if (!$(target).is(':visible')) {
          $(target).addClass('flex').removeClass('hidden');
          $(new_backup_card).addClass('hidden').removeClass('flex');
        }
        break;

      default:
         // Check for target visibility.
        if ($(target).is(':visible')) {
          $(target).addClass('hidden').removeClass('flex');
          $(new_backup_card).addClass('flex').removeClass('hidden');

          $('.backwpup-dynamic-input label')
          .removeClass('bg-secondary-lighter border-secondary-base')
          .find('> div').removeClass('border-secondary-base')
          .end().first()
          .addClass('bg-secondary-lighter border-secondary-base')
          .find('input').prop('checked', true)
          .end().find('> div').addClass('border-secondary-base');
        }
        break;
    }
  }


  $("#backwup-next-scheduled-backups").on('click', '#js_backwpup_close_dynamic_backup_card', function () {
    toggleDynamicCardDisplay($target_dynamic_card);
  });

  //Deprecated since 5.3
  $("#backwup-next-scheduled-backups").on('click', "#js-backwpup-add-new-backup", function (e) {
    e.preventDefault();
    $(this).prop('disabled', true);
    let that = $(this);
    let type = $('#js-backwpup-add-new-backup-form').find('input[name="type"]:checked').val();
    if (2 === $('#js-backwpup-add-new-backup-form').find('input[name="type"]:checked').length) {
      type = 'mixed';
    }
    requestWPApi(
      backwpupApi.addjob,
      {
        type: type,
      },
      function (response) {
        if ( response.success == true ) {
          $("#backwpup-backup-now").prop("disabled", false);
          loadBackupsListingAndPagination(getUrlParameter('page_num', 1));
          // Display floating success notice.
		      backwpupDisplaySettingsToast('success', response.message);
          requestWPApi(
            backwpupApi.getjobslist,
            {},
            function (response) {
              $('#backwup-next-scheduled-backups').html(response);
              // get the html from backwpup_dynamic_response_content and append back to the html container.
              const $dynamic_content = $('#backwpup_dynamic_response_content').html();
              $('#backwup-next-scheduled-backups').append($dynamic_content);
              // Revert state of the dynamic backup addition card.
              toggleDynamicCardDisplay($target_dynamic_card);
            },
            "GET",
          );
        }
      },
      "POST",
      function (request, error) {
        // Display floating error notice.
        backwpupDisplaySettingsToast('error', request.responseText);
        that.prop('disabled', false);
      }
    );
  });

  // Test and save S3 storage.
  window.test_S3_storage = function (event) {
    let job_id = $(event.currentTarget).data("job-id");
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if (page === 'backwpuponboarding') {
      job_id = null;
    }
    if ($("#s3bucketerror").html()!="") {
      refresh_storage_destinations(job_id, 'S3', false);
      alert('Error in Bucket Configurations');
      return;
    }
    const data = {
      'job_id': job_id,
      'cloud_name' : 's3',
      's3region' : $("#s3region").val(),
      's3base_url' : $("#s3base_url").val(),
      's3base_region' : $("#s3base_region").val(),
      's3base_version' : $("#s3base_version").val(),
      's3base_signature' : $("#s3base_signature").val(),
      's3accesskey' : $("#s3accesskey").val(),
      's3secretkey' : $("#s3secretkey").val(),
      's3bucket' : $("#s3bucket").val(),
      's3newbucket' : $("#s3newbucket").val(),
      's3dir' : $("#s3dir").val(),
      's3maxbackups' : $("#s3maxbackups").val(),
      's3storageclass' : $("#s3storageclass").val() === "STANDARD" ? "" : $("#s3storageclass").val(),
    }
    if ($("#s3base_multipart").prop("checked")) {
      data['s3base_multipart'] = $("#s3base_multipart").val();
    }
    if ($("#s3base_pathstylebucket").prop("checked")) {
      data['s3base_pathstylebucket'] = $("#s3base_pathstylebucket").val();
    }
    if ($("#s3ssencrypt").prop("checked")) {
      data['s3ssencrypt'] = $("#s3ssencrypt").val();
    }
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations(job_id, 'S3', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations(job_id, 'S3', false);
        alert("Error in cloud configuration");
      }
    );
  };
  $('.js-backwpup-test-S3-storage').on('click', window['test_S3_storage']);

  // Test and save Glacier storage.
  window.test_GLACIER_storage = function (event) {
    let job_id = $(event.currentTarget).data("job-id");
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if (page === 'backwpuponboarding') {
      job_id = null;
    }
    if ($("#glacierbucketerror").html()!="") {
      refresh_storage_destinations(job_id, 'GLACIER', false);
      alert('Error in Bucket Configurations');
      return;
    }
    const data = {
      'job_id': job_id,
      'cloud_name' : 'glacier',
      'glacieraccesskey' : $("#glacieraccesskey").val(),
      'glaciersecretkey' : $("#glaciersecretkey").val(),
      'glacierregion' : $("#glacierregion").val(),
      'glaciervault' : $("#glaciervault").val(),
      'glaciermaxbackups' : $("#glaciermaxbackups").val(),
      'newvault' : $("#newvault").val()
    }

    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations(job_id, 'GLACIER', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations(job_id, 'GLACIER', false);
        alert("Error in cloud configuration");
      }
    );
  };
  $('.js-backwpup-test-GLACIER-storage').on('click', window['test_GLACIER_storage']);

  // Test and save local folder storage.
  window.test_FOLDER_storage =  function (event) {
    let job_id = $(event.currentTarget).data("job-id");
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if (page === 'backwpuponboarding') {
      job_id = null;
    }
    const data = {
      'job_id': job_id,
      'cloud_name' : 'folder',
      'backupdir' : $("#backupdir").val(),
      'maxbackups' : $("#maxbackups").val(),
    };
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations(job_id, 'FOLDER', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations(job_id, 'FOLDER', false);
        alert("Error in cloud configuration");
      }
    );
  };
  $('.js-backwpup-test-FOLDER-storage').on('click', window['test_FOLDER_storage']);
  

  // Test and save ftp storage.
  window.test_FTP_storage = function (event) {
    let job_id = $(event.currentTarget).data("job-id");
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if (page === 'backwpuponboarding') {
      job_id = null;
    }
    const data = {
      'job_id': job_id,
      'cloud_name' : 'ftp',
      'ftphost' : $("#ftphost").val(),
      'ftphostport' : $("#ftphostport").val(),
      'ftpuser' : $("#ftpuser").val(),
      'ftppass' : $("#ftppass").val(),
      'ftptimeout' : $("#ftptimeout").val(),
      'ftpdir' : $("#ftpdir").val(),
      'ftpmaxbackups' : $("#ftpmaxbackups").val(),
      'ftpssl' : $("#ftpssl").prop("checked") ? 1 : 0,
      'ftppasv' : $("#ftppasv").prop("checked") ? 1 : 0,
    };
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations(job_id, 'FTP', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations(job_id, 'FTP', false);
        alert("Error in cloud configuration");
      }
    );
  };
  $('.js-backwpup-test-FTP-storage').on('click',  window['test_FTP_storage']);

  // Test and save Gdrive storage.
  window.test_GDRIVE_storage = function (event) {
    let job_id = $(event.currentTarget).data("job-id");
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if (page === 'backwpuponboarding') {
      job_id = null;
    }
    const data = {
      'job_id': job_id,
      'cloud_name' : 'gdrive',
      'gdriveusetrash' : $("#gdriveusetrash").prop("checked"),
      'gdrivemaxbackups' : $("#gdrivemaxbackups").val(),
      'gdrivedir' : $("#gdrivedir").val(),
    };
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations(job_id, 'GDRIVE', response.connected);
        backwpupDisplaySettingsToast('success', response.message);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations(job_id, 'GDRIVE', false);
        const errorMessage = request.responseJSON && request.responseJSON.error
            ? request.responseJSON.error
            : (request.responseText || 'Unknown error occurred');
        backwpupDisplaySettingsToast('danger', errorMessage , -1);
      }
    );
  };
  $('.js-backwpup-test-GDRIVE-storage').on('click', window['test_GDRIVE_storage']);

  function initHidriveEvents() {
    $('.js-backwpup-modal-and-focus').on('click', modal_and_focus);
    // Delete Hidrive authentication.
    $('.js-backwpup-delete-hidrive-auth').on('click', function() {
      let job_id = $(this).data("job_id");
      const urlParams = new URLSearchParams(window.location.search);
      const page = urlParams.get('page');
      if (page === 'backwpuponboarding') {
        job_id = null;
      }
      const data = {
        'job_id': job_id,
        'cloud_name' : 'hidrive',
        'hidrive_delete_authorization' : true,
        'hidrive_max_backups' : $("#hidrive_max_backups").val(),
        'hidrive_destination_folder' : $("#hidrive_destination_folder").val(),
      }
      requestWPApi(
        backwpupApi.cloudsaveandtest,
        data,
        function (response) {
          refresh_storage_destinations(job_id, 'HIDRIVE', false);
          closeSidebar();
        },
        "POST",
        function (request, error) {
          refresh_storage_destinations(job_id, 'HIDRIVE', false);
          alert("Error in cloud configuration");
        }
      );
    });

    // Test and save HiDrive storage.
    $('.js-backwpup-test-HIDRIVE-storage').on('click', function () {
      let job_id = $(this).data("job_id");
      const urlParams = new URLSearchParams(window.location.search);
      const page = urlParams.get('page');
      if (page === 'backwpuponboarding') {
        job_id = null;
      }
      const data = {
        'job_id': job_id,
        'cloud_name': 'hidrive',
        'hidrive_max_backups': $("#hidrive_max_backups").val(),
        'hidrive_destination_folder': $("#hidrive_destination_folder").val(),
      }
      let hidrive_authorization_code = $("#hidrive_authorization_code").val();
      if (hidrive_authorization_code) {
        data['hidrive_authorization_code'] = hidrive_authorization_code;
      }
      requestWPApi(
        backwpupApi.cloudsaveandtest,
        data,
        function (response) {
          refresh_storage_destinations(job_id, 'HIDRIVE', response.connected);
          closeSidebar();
        },
        "POST",
        function (request, error) {
          refresh_storage_destinations(job_id, 'HIDRIVE', false);
          alert("Error in cloud configuration");
        }
      );
    });
  }

  // Test and save ONEDRIVE storage.
  window.test_ONEDRIVE_storage = function (event) {
    let job_id = $(event.currentTarget).data("job-id");
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if (page === 'backwpuponboarding') {
      job_id = null;
    }
    const data = {
      'job_id': job_id,
      'cloud_name': 'onedrive',
      'onedrivedir': $("#onedrivedir").val(),
      'onedrivemaxbackups': $("#onedrivemaxbackups").val(),
    }
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations(job_id, 'ONEDRIVE', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations(job_id, 'ONEDRIVE', false);
        alert("Error in cloud configuration");
      }
    );
  };
  $('.js-backwpup-test-ONEDRIVE-storage').on('click', window['test_ONEDRIVE_storage']);

  // Test and save SugarSync storage.
  window.test_SUGARSYNC_storage = function (event) {
    let job_id = $(event.currentTarget).data("job-id");
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if (page === 'backwpuponboarding') {
      job_id = null;
    }
    const data = {
      'job_id': job_id,
      'cloud_name' : 'sugarsync',
      'sugardir' : $("#sugardir").val(),
      'sugarmaxbackups' : $("#sugarmaxbackups").val(),
      'sugarroot' : $("#sugarroot").val(),
    };
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations(job_id, 'SUGARSYNC', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations(job_id, 'SUGARSYNC', false);
        alert("Error in cloud configuration");
      }
    );
  };
  $('.js-backwpup-test-SUGARSYNC-storage').on('click', window['test_SUGARSYNC_storage']);

  // Test and save RackSpace storage.
  window.test_RSC_storage = function (event) {
    let job_id = $(event.currentTarget).data("job-id");
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if (page === 'backwpuponboarding') {
      job_id = null;
    }
    const data = {
      'job_id': job_id,
      'cloud_name' : 'rsc',
      'newrsccontainer' : $("#newrsccontainer").val(),
      'rscdir' : $("#rscdir").val(),
      'rscmaxbackups' : $("#rscmaxbackups").val(),
      'rsccontainer' : $("#rsccontainer").val(),
      'rscusername' : $("#rscusername").val(),
      'rscapikey' : $("#rscapikey").val(),
      'rscregion' : $("#rscregion").val(),
    };
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations(job_id, 'RSC', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations(job_id, 'RSC', false);
        alert("Error in cloud configuration");
      }
    );
  };
  $('.js-backwpup-test-RSC-storage').on('click', window['test_RSC_storage']);

  // Test and save MSAZURE storage.
  window.test_MSAZURE_storage = function (event) {
    let job_id = $(event.currentTarget).data("job-id");
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    if (page === 'backwpuponboarding') {
      job_id = null;
    }
    const data = {
      'job_id': job_id,
      'cloud_name' : 'msazure',
      'msazureaccname' : $("#msazureaccname").val(),
      'msazurekey' : $("#msazurekey").val(),
      'msazurecontainer' : $("#msazurecontainer").val(),
      'newmsazurecontainer' : $("#newmsazurecontainer").val(),
      'msazuredir' : $("#msazuredir").val(),
      'msazuremaxbackups' : $("#msazuremaxbackups").val(),
    };
    // return;
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations(job_id, 'MSAZURE', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations(job_id, 'MSAZURE', false);
        alert("Error in cloud configuration");
      }
    );
  };
  $('.js-backwpup-test-MSAZURE-storage').on('click', window['test_MSAZURE_storage']);

  // Onboarding steps
  const $onboardingSteps = $("#backwpup-onboarding-steps");
  const $onboardingPanes = $("#backwpup-onboarding-panes");

  const lineStyles = "before:bg-secondary-base";
  const circleActiveStyles =
    "bg-secondary-base after:absolute after:z-10 after:inset after:w-12 after:h-12 after:border-secondary-base after:border after:rounded-full after:animate-pulse";
  const circleReachedStyles = "bg-secondary-base";

  $(".js-backwpup-onboarding-step-1").on('click', function () {
    $onboardingPanes.find("article").removeClass("flex").addClass("hidden");

    $onboardingPanes
      .find("article[data-step=1]")
      .removeClass("hidden")
      .addClass("flex");

    $onboardingSteps
      .find("article[data-step=2]")
      .removeClass(lineStyles)
      .find("div:first-child")
      .removeClass(circleActiveStyles);

    closeSidebar();
  });

  $(".js-backwpup-onboarding-step-2").on('click', function () {
    $onboardingPanes.find("article").removeClass("flex").addClass("hidden");

    $onboardingPanes
      .find("article[data-step=2]")
      .removeClass("hidden")
      .addClass("flex");

    $onboardingSteps
      .find("article[data-step=2]")
      .addClass(lineStyles)
      .find("div:first-child")
      .addClass(circleActiveStyles);

    $onboardingSteps
      .find("article[data-step=1] div:first-child")
      .removeClass(circleActiveStyles)
      .addClass(circleReachedStyles);

    $onboardingSteps
      .find("article[data-step=3]")
      .removeClass(lineStyles)
      .find("div:first-child")
      .removeClass(circleActiveStyles);

    closeSidebar();
  });

  $(".js-backwpup-onboarding-step-3").on('click', function () {
    $onboardingPanes.find("article").removeClass("flex").addClass("hidden");

    $onboardingPanes
      .find("article[data-step=3]")
      .removeClass("hidden")
      .addClass("flex");

    $onboardingSteps
      .find("article[data-step=3]")
      .addClass(lineStyles)
      .find("div:first-child")
      .addClass(circleActiveStyles);

    $onboardingSteps
      .find("article[data-step=2] div:first-child")
      .removeClass(circleActiveStyles)
      .addClass(circleReachedStyles);

    closeSidebar();
  });

  // Verifie onboarding form and submit it.
  $(".js-backwpup-onboarding-submit-form").on('click', function() {
    const checkedStorageCheckboxes = $('input[type="checkbox"][name^="onboarding_storage"]:checked');
    if (0 !== checkedStorageCheckboxes.length) {
      $("#backwpup-onboarding-form").submit();
    } else {
      // TODO Show ERROR
    }
  });

  // Toggle storages
  $document.on('click', ".js-backwpup-toggle-storage", function() {
    const content = $(this).data("content");
    openSidebar(content);
  });


  $('.js-backwpup-refresh-authentification').on('click', function() {
    let trigger = $(this).data('trigger');
    if (typeof window[trigger] === 'function') {
      window[trigger]();
    } else {
      eval(trigger);
    }
    closeModal();
  });

  // Open a modal to wait for authentication and focus on an input on after
  window.modal_and_focus = function (event) {
    let that = $(event.currentTarget);
    openModal('dialog');
    const focus = that.data('id-focus-after');
    $('.js-backwpup-refresh-authentification').data('trigger', '$("#'+focus+'").focus()');
    window.open(that.data('url'), '_blank');
  }
  $('.js-backwpup-modal-and-focus').on('click', modal_and_focus);

  function initOnedriveEvents() {
    // Toggle OneDrive reauthenticate action.
    $('.js-backwpup-onedrive-reauthenticate').on('click', function() {
      openModal('dialog');
      $('.js-backwpup-refresh-authentification').data('trigger', 'onedrive_refresh_authentification');
      window.open($(this).data('url'), '_blank');
    });

    // Toggle OneDrive Api connection first step
    $('.js-backwpup-one-drive-connect-api').on('click', function() {
      const details = $(this).closest('details');
      let data = {
        'backwpup_cfg_onedriveclientsecret' : {
          'value': $('#backwpup_cfg_onedriveclientsecret').val(),
          'secure': true,
        },
        'backwpup_cfg_onedriveclientid' : {
          'value': $('#backwpup_cfg_onedriveclientid').val(),
          'secure': false,
        },
      }
      requestWPApi(
        backwpupApi.save_site_option,
        data,
        function (response) {
          $('#onedrive_authenticate_infos').html(response.message);
          // Remove the 'open' attribute
          details.removeAttr('open');
        },
        "POST",
        function (request, error) {
          $('#onedrive_authenticate_infos').html("Error");
        }
      )
    });
  }

  // Validate storage.
  $(".js-backwpup-test-storage").on('click', function() {
    const data = {
      action: "backwpup_test_storage",
      storage: $(this).data("storage"),
    };

    postToWP(data).then((body) => {
      $(`input[name=storage_${data.storage}]`).prop("checked", true);
      const inSidebar = $(this).closest("#backwpup-sidebar").length > 0;
      if (!inSidebar) {
        closeSidebar();
      }
    });

    // TODO : remove (test purpose only while ajax request is not working)
    $(`input[name=storage_${data.storage}]`).prop("checked", true);
    const inSidebar = $(this).closest("#backwpup-sidebar").length > 0;
    const urlParams = new URLSearchParams(window.location.search);
    const page = urlParams.get('page');
    // teste si on est dans le onboarding
    if (page === 'backwpuponboarding') {
      closeSidebar();
    } else if (!inSidebar) {
        closeSidebar();
    } else {
      openSidebar("storages");
    }
  });
  // Network settings
  function init_network_authentication() {
    const value = $(".js-backwpup-network-authentication-method").val();
    let selector = "#network-"+value+"-authentication";
    $(selector).show();
  }
  init_network_authentication();
  $(".js-backwpup-network-authentication-method").on('change', function() {
    $('#network-authentications').children('div').hide();
    init_network_authentication();
  });

  // Submit action for sidebar forms
  $(".js-backwpup-sidebar-submit-form").on('click', function() {
    $(this).siblings("div").children().submit();
  });

  // OnBoarding toggle files.
  $(".js-backwpup-onboarding-toggle-files").on('change', function() {
    const checked = $(this).prop("checked");
    $(".onboarding-advanced-files-settings").prop("disabled", !checked);
    $(".onboarding-files-frequency").prop("disabled", !checked);
    $(".onboarding-files-frequency-settings").prop("disabled", !checked);
    verifyOnboardingStep1();
  });

  // OnBoarding toggle database.
  $(".js-backwpup-onboarding-toggle-database").on('change', function() {
    const checked = $(this).prop("checked");
    $(".onboarding-advanced-database-setings").prop("disabled", !checked);
    $(".onboarding-database-frequency").prop("disabled", !checked);
    $(".onboarding-database-frequency-settings").prop("disabled", !checked);
    verifyOnboardingStep1();
  });

  $(".js-backwpup-onboarding-files-frequency").on('change', function() {
    if ($("#sidebar-frequency-files")) {
      let select = $("#sidebar-frequency-files").find("select[name='frequency']");
      select.val(this.value);
      select.trigger('change');
    }
  });

  $(".js-backwpup-onboarding-database-frequency").on('change', function() {
    if ($("#sidebar-frequency-tables")) {
      let select = $("#sidebar-frequency-tables").find("select[name='frequency']");
      select.val(this.value);
      select.trigger('change');
    }
  });

  // OnBoarding verify step 1
  function verifyOnboardingStep1() {
    const toggle_files_not_checked = !$(".js-backwpup-onboarding-toggle-files").prop("checked");
    const toggle_database_not_checked = !$(".js-backwpup-onboarding-toggle-database").prop("checked");
    if (toggle_files_not_checked && toggle_database_not_checked) {
      $('.js-backwpup-onboarding-step-2').prop("disabled", true);
    } else {
      $('.js-backwpup-onboarding-step-2').prop("disabled", false);
    }
  }

  /**
   * Refreshes the backup table with the provided response data.
   *
   * This function updates the backup history table with new HTML content from the response,
   * reinitializes necessary components, and refreshes the pagination.
   *
   * @param {Object} response - The response object containing the success status and data.
   * @param {boolean} response.success - Indicates if the response was successful.
   * @param {string} response.data - The HTML content to be inserted into the table.
   * @param {Object} data - Additional data required for pagination.
   * @param {number} data.page - The current page number.
   * @param {number} data.length - The number of items per page.
   */
  function refreshBackupTable(response, data) {
    if (response.success && response.data) {
      // Extract the HTML content from the response
      var htmlContent = response.data;
  
      // Use jQuery.parseHTML to decode and safely insert the response into the DOM
      var parsedHTML = jQuery.parseHTML(htmlContent);
      var tableBody = jQuery('#backwpup-backup-history tbody');
      tableBody.html(parsedHTML);
  
      // Reinitialize downloader, menu, and modal actions
      window.BWU.downloader.init();
      initMenuEvent();
      initModalEvent();
  
      // Calculate max pages and refresh the pagination
      let max_pages = Math.ceil(jQuery('input[name="nb_backups"]').val() / data.length);
      requestWPApi(backwpupApi.backupspagination, { page: data.page, max_pages: max_pages }, refreshPagination, 'POST');
    } 
  }

  /**
   * Refreshes the pagination section with new HTML content from the server response.
   *
   * @param {Object} response - The response object from the server.
   * @param {boolean} response.success - Indicates if the request was successful.
   * @param {string} response.data - The HTML content to update the pagination section.
   */
  function refreshPagination(response) {  
    if (response.success && response.data) {
      // Get the HTML content from the response
      var htmlContent = response.data;
      // Parse HTML and append the response
      var parsedHTML = jQuery.parseHTML(htmlContent); 
      var pagination = jQuery('#backwpup-pagination');
      pagination.html(parsedHTML); // Safely add the parsed HTML
  
      // Reinitialize pagination events
      initPaginationEvent();
    } 
  }

  // Initialize the backup table and the pagination only if there is a backup table
  if (jQuery('#backwpup-backup-history tbody').length>0) {
    loadBackupsListingAndPagination(getUrlParameter('page_num', 1));
  }
  // Init the modals events
  initModalEvent();
  initSugarSyncEvents();
  initDropboxEvents();
  initOnedriveEvents();
  initGdriveEvents();
  initHidriveEvents();

  $(document).on( 'change', '#backwpup-job-title', function() {
    if ( ! $(this).val().trim()  ) {
      $('#js-backwpup-edit-title-warning').removeClass('hidden');
      $('#js-backwpup-save-title').prop('disabled', true);
      return;
    }

    if ( $('#js-backwpup-edit-title-warning').hasClass('hidden') ) {
      return;
    }

    $('#js-backwpup-edit-title-warning').addClass('hidden');
    $('#js-backwpup-save-title').prop('disabled', false);
  } );

  $(document).on('click', '#js-backwpup-save-title', function(event) {
    event.preventDefault();
    const job_id = $('#backwpup-job-id').val();

    const data = {
      title: $('#backwpup-job-title').val(),
      job_id: job_id,
    };

    requestWPApi(
      backwpupApi.updatejobtitle,
      data,
      function(response) {
        if (response.code === 'success') {
          $('#backwpup-'+job_id+'-options').find('.backwpup-job-title').html(response.data.title);

          backwpupDisplaySettingsToast( 'success', response.message );
          closeSidebar();
        }
      },
      "POST",
      function(request) {
        backwpupDisplaySettingsToast( 'danger', request.responseJSON.message );
      }
    );
  });

  // Add event listener for the 'Apply' button to trigger the bulk delete action
  $("#bulk-actions-apply").on('click', function () {
    const action = $(this).data("action");
    const selectedBackups = $backupsTable.find("input[type=checkbox]:checked").map(function () {
      return {
        dataset: $(this).data("delete"),
      };
    }).get();

    requestWPApi(backwpupApi.backups_bulk_actions, { action: action, backups: selectedBackups }, function (response) {
      // Refresh the backup table after deletion
      loadBackupsListingAndPagination(getUrlParameter('page_num', 1));
    }, "POST");

    $(".js-backwpup-select-all").prop("checked", false);
  });

  $('#bulk-actions-select').on('change', function() {
    const selectedAction = $(this).val();
    $('#bulk-actions-apply').data('action', selectedAction);
  });
  // Handle Save Settings - excluded tables button click for table selection
  $document.on('click', "#save-excluded-tables", function () {
    // Collect all checkbox elements in the container
    const allCheckboxes = $(".js-backwpup-tables-list input[type='checkbox']");
    const closestArticle = $(this).closest("article");
    const checkedTables = [];

    // Loop through all checkboxes and collect the checked ones
    allCheckboxes.each(function () {
        if ($(this).is(":checked")) {
            checkedTables.push($(this).val());
        }
    });

    // Collect hidden fields dynamically
    const hiddenFields = closestArticle.find("input[type='hidden']");
    const adjustedFormData = [];

    hiddenFields.each(function () {
        adjustedFormData.push({ name: $(this).attr("name"), value: $(this).val() });
    });

    // Add the checked tables as tabledb[] values
    checkedTables.forEach(function (table) {
        adjustedFormData.push({ name: "tabledb[]", value: table });
    });

    // Convert adjusted form data to a query string
    const serializedData = $.param(adjustedFormData);

    // Send the adjusted data to the API
    requestWPApi(
        backwpupApi.save_excluded_tables,
        serializedData,
        function (response) {
            if (response.status === 200) {
                if ( ! $("#backwpup-onboarding-panes").length ) {
	                backwpupDisplaySettingsToast('success', response.message);
                }
                closeSidebar();
            }
        },
        "POST"
    );
  });

  $('.js-backwpup-license_update').on('click', update_license);

  $document.on('click', '.file-exclusions-submit', function (e) {
    const $article = $(this).closest('article');
    const $inputs = $article.find('input');
    const formData = {};

    // Helper function to add values to formData
    function addToFormData(name, value) {
        if (name.endsWith('[]')) {
            if (!formData[name]) {
                formData[name] = [];
            }
            if (value) {
                formData[name].push(value);
            }
        } else {
            formData[name] = value;
        }
    }

    // Collect input data from the closest article
    $inputs.each(function () {
        if ($(this).is(':checkbox')) {
            if ($(this).is(':checked')) {
                addToFormData($(this).attr('name'), $(this).val());
            }
        } else {
            addToFormData($(this).attr('name'), $(this).val());
        }
    });

    // Collect input data from all relevant modals
    $('[id^="sidebar-exclude-files-"]').each(function () {
        const $modalInputs = $(this).find('input');
        $modalInputs.each(function () {
            if ($(this).is(':checkbox')) {
                if ($(this).is(':checked')) {
                    addToFormData($(this).attr('name'), $(this).val());
                }
            } else {
                addToFormData($(this).attr('name'), $(this).val());
            }
        });
    });

    // Convert formData to a query string
    const serializedData = $.param(formData);

    requestWPApi(backwpupApi.save_file_exclusions, serializedData, function (response) {
        if (response.status === 200) {
          closeSidebar();
          if ( ! $("#backwpup-onboarding-panes").length ) {
	          backwpupDisplaySettingsToast('success', response.message);
          }
        }
    }, 'POST');
  });

  function isGenerateJsIncluded() {
    const scripts = document.querySelectorAll('script');
    let scriptName = 'backwpup-generate.js';
    let scriptNameMin = 'backwpup-generate.min.js';
    for ( let script of scripts ) {
        if ( script.src.includes( scriptName ) || script.src.includes( scriptNameMin ) ) {
            return true;
        }
    }
    return false;
  }

  // Function to start the backup process using requestWPApi
	function startBackupProcess( data = {} ) {
		$document.trigger('start-backupjob');
		requestWPApi(
			backwpupApi.startbackup,
		    data,
		    function(response) {
		      if (response.status === 200) {
		        setTimeout(function() {
		            window.location.reload();
		        }, 500);
		      } else if ( 301 === response.status ) {
		        window.location = response.url;
		      }
		    },
			'POST',
			function(request, error) {
		        $document.trigger('backup-ended');
		    }
		);
	}

  $(document).on('start-backupjob', function () {
    enableBackupButton(false);
    enableDeleteJob(false);
  });

  // Re-enable buttons when a backup completes
  $(document).on('backup-complete', function () {
    enableBackupButton(true);
    enableDeleteJob(true);
    loadBackupsListingAndPagination(getUrlParameter('page_num', 1));
  });





	// Call the functions when the "First Backup" page is loaded
  if (window.location.search.includes('backwpupfirstbackup')) {
    if ( ! isGenerateJsIncluded() ) {
      let first_job_id = $('#backwpup_first_backup_job_id').val();
      requestWPApi(backwpupApi.startbackup, {first_backup: 1, job_id : first_job_id}, function(response) {
        if (200 === response.status) {
          setTimeout(function() {
              window.location.reload();
          }, 500);
        } else if ( 301 === response.status ) {
          window.location = response.url;
        }
      }, 'POST');
    }
    
  }

  // Replace the 'Buy Pro' menu item with the correct link.
  var buyProMenuItem = $('#toplevel_page_backwpup ul li a[href="admin.php?page=buypro"]');
  if (buyProMenuItem.length) {
      buyProMenuItem.attr('href', 'https://backwpup.com/#buy');
      buyProMenuItem.attr('target', '_blank');
      buyProMenuItem.css({
        'color': '#3ac495',
        'font-weight': 'bold'
    });
  }
  var DocsMenuItem = $('#toplevel_page_backwpup ul li a[href="admin.php?page=docs"]');
  if (DocsMenuItem.length) {
    DocsMenuItem.attr('href', 'https://backwpup.com/docs/');
    DocsMenuItem.attr('target', '_blank');
  }

  // Handle bwpup-ajax-close
  $('.bwpup-ajax-close').click( function(e) {
    e.preventDefault();
    let current_close = $(this);
    let url = current_close.attr( 'href' );
    if ( ! url ) {
      return;
    }
    let hide_id = current_close.data('bwpu-hide');
    $('#'+hide_id).fadeTo('slow', '0.2');

    $.ajax({
      url,
      success: function(response) {
        $('#'+hide_id).hide();
      },
    });
  } );

  // hide toast on click.
  $(document).on('click', '#bwp-settings-toast #dismiss-icon', function() {
    const toastElements = $('#bwp-settings-toast').children();

    toastElements.removeClass('opacity-100 translate-y-0')
        .addClass('opacity-0 translate-y-2');

    setTimeout(() => {
      toastElements.remove();
    }, 300);
  });

  /**
   * Function to initialize the frequency job settings
   * Runs when the element `.js-backwpup-frequency-job` is found in the DOM.
   */
  function runWhenJobFrequencySettingsLoaded() {
    const $element = $(".js-backwpup-frequency-job");
    if ($element.length) {
      showFrequencyJobFields($element.val()); // Update UI based on the selected frequency
    }
  }

  /**
   * MutationObserver: Watches for new elements being added to the DOM
   * When a `.js-backwpup-frequency-job` field appears, it triggers `runWhenJobFrequencySettingsLoaded()`
   */
  const observer = new MutationObserver(function (mutations) {
    mutations.forEach(function (mutation) {
      mutation.addedNodes.forEach(function (node) {
        // Check if the added node or any of its children contains `.js-backwpup-frequency-job`
        if ($(node).is(".js-backwpup-frequency-job") || $(node).find(".js-backwpup-frequency-job").length) {
          runWhenJobFrequencySettingsLoaded();
        }
      });
    });
  });

  // Start observing DOM changes on the entire document
  observer.observe(document.body, { childList: true, subtree: true });

  /**
   * Event Listener: Detect changes in frequency dropdown
   * Calls `showFrequencyJobFields()` whenever the user selects a new frequency.
   */
  $document.on("change", ".js-backwpup-frequency-job", function () {
    showFrequencyJobFields($(this).val());
  });

  /**
   * Function to show/hide elements based on selected job frequency
   * @param {string} frequency - The selected frequency value (e.g., "hourly", "weekly", "monthly").
   */
  function showFrequencyJobFields(frequency) {
    const frequencies = ["daily", "weekly", "monthly"];
    const hideClasses = [
      ".js-backwpup-frequency-job-show-if-hourly",
      ".js-backwpup-frequency-job-show-if-weekly",
      ".js-backwpup-frequency-job-show-if-monthly",
      ".js-backwpup-frequency-job-hide-if-hourly",
    ];

    // Hide all elements initially
    $(hideClasses.join(", ")).hide();

    // Show the elements that should be visible for the selected frequency
    if (frequencies.includes(frequency)) {
      $(".js-backwpup-frequency-job-hide-if-hourly").show();
    }

    $(`.js-backwpup-frequency-job-show-if-${frequency}`).show();
  }

  /**
   * Event Listener: Handles the "Save Job Settings" button click
   * Extracts form data and sends an AJAX request to save the job settings.
   */
  $document.on("click", "#save-job-settings", function () {
    const container = $(this).closest("article");

    // Collect input values from the form
    const data = {
      frequency: container.find("select[name='frequency']").val(),
      start_time: container.find("input[name='start_time']").val(),
      hourly_start_time: container.find("select[name='hourly_start_time']").val(),
      day_of_week: container.find("select[name='day_of_week']").val(),
      day_of_month: container.find("select[name='day_of_month']").val(),
      job_id: container.find("input[name='job_id']").val(),
    };

    // Send AJAX request to save job settings
    requestWPApi(
        backwpupApi.save_job_settings,
        data,
        function (response) {
          if (response.status === 200) {
            // Update the next scheduled backup time in the UI
            $(`#backwpup-${data.job_id}-options div span.label-scheduled`).html(response.next_backup);

            // Sync onboarding frequency dropdown (if present)
            const onboardingPane = $("#backwpup-onboarding-panes");
            const sidebarFrequency = $("#sidebar-frequency").find("select[name='frequency']");

            if (onboardingPane.length) {
              console.log("select[name='job_"+data.job_id+"_frequency']");
              onboardingPane.find("select[name='job_"+data.job_id+"_frequency']").val(sidebarFrequency.val());
            } else {
	            backwpupDisplaySettingsToast('success', response.message);
			}

            // Close the settings sidebar
            closeSidebar();
          }
        },
        "POST"
    );
  });

  // Run the job settings function on initial page load
  runWhenJobFrequencySettingsLoaded();

  $document.on('click', '.backwpup-start-backup-job', function () {
	  startBackupProcess({ 'job_id': $(this).data('job_id') });
	  closeModal();
  });
});

// Add a custom 'hide' event when the .hide() function is called
(function ($) {
  var originalHide = $.fn.hide;
  $.fn.hide = function () {
      this.trigger('hide'); // Trigger 'hide' event
      return originalHide.apply(this, arguments);
  };
})(jQuery);

/**
 * Unselects a storage option by sending a request to the WordPress API.
 *
 */
document.addEventListener("DOMContentLoaded", function() {
    document.addEventListener("click", function (event) {
        const storage_button = event.target.closest('.js-backwpup-select-storage');
        if (!storage_button) {
            return;
        }

        const storage_checkbox = storage_button.querySelector('input[type="checkbox"]');
        if (!storage_checkbox) {
            return;
        }

        const data = {
            'job_id': storage_button.dataset.jobId,
            'name': storage_button.dataset.storage,
            'checked': storage_checkbox.checked ? 1 : 0,
        };

        if (typeof requestWPApi === 'function') {
            requestWPApi(backwpupApi.storages, data, function(response) {
                    if ( response.status === 200 ) {
                        storage_checkbox.checked = data.checked !== 1;
                    }
                },
                'POST',
                function (request, error) {
                    console.log(request);
                    console.log(error);
                    alert(request.responseJSON.error);
                });
        }
    })
});