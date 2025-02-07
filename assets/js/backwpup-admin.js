jQuery(document).ready(function ($) {
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
  function requestWPApi(route, data, callback, method = 'GET', error_callback = null) {
    $.ajax({
      url: route,
      beforeSend: function(xhr) {
        xhr.setRequestHeader('X-WP-Nonce', backwpupApi.nonce);
      },
      method: method,
      data: data,
      success: function(response) {
        callback(response, data);
      },
      error: function(request, error) {
        if (error_callback) {
          error_callback(request, error);
        } else {
          console.log(error);
        }
      }
    });
  }

  // Function to get URL parameter.
  function getUrlParameter(name, defaultValue="") {
    let searchParams = new URLSearchParams(window.location.search)
    if (searchParams.has(name)) {
      return searchParams.get(name);
    } else {
      return defaultValue;
    }
  }

  // Function to be sure the value on this class is an integer.
  $(".js-backwpup-intonly").on('keydown', function(event) {
    return ['Backspace','Delete','ArrowLeft','ArrowRight'].includes(event.code) ? true : !isNaN(Number(event.key)) && event.code!=='Space';
  });

  // Function to refresh the storage destinations.
  window.refresh_storage_destinations= function (destinationName, authenticated) {
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
  window.dropbox_refresh_authentification = function() {
    requestWPApi(
      backwpupApi.cloud_is_authenticated,
      {
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
        console.log(response);
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
        if (response.status === 200) {
          console.log(response);
        }
      },
      'POST',
      function(request, error) {
        console.log(request.responseJSON.error);
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
    console.log(block_name);
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

  $(".js-backwpup-open-sidebar").on('click', function () {
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

  function initSugarSyncEvents() {
    // Toggle SugarSync authenticate action.
    $('.js-backwpup-authenticate-sugar-sync').on('click', function() {
      let data = {
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
          initSugarSyncEvents();
        },
        "POST",
        function(request, error) {
          $('#sugarsync_authenticate_infos').html(request.responseText);
        }
      );
    });

    // Delete Sugar Sync authentication.
    $('.js-backwpup-delete-sugar-sync-auth').on('click', function() {
      const data = {
        'cloud_name' : 'sugarsync',
      }
      requestWPApi(
        backwpupApi.delete_auth_cloud,
        data,
        function (response) {
          refresh_storage_destinations('SUGARSYNC', response.connected);
          $('#sugarsynclogin').html(response);
          initSugarSyncEvents();
        },
        "POST",
        function (request, error) {
          alert("Error in cloud configuration");
        }
      );
    });

    // Reload the root folder list.
    requestWPApi(
      backwpupApi.getblock,
      {
        'block_name': 'sidebar/sugar-sync-parts/root-folder',
        'block_type': 'children',
      },
      function(response) {
        $('#sugarsyncroot').html(response);
      },
      'POST',
      function (request, error) {
        $('#sugarsyncroot').html(request.responseText);
      }
    );
  }


  // Initialize Dropbox cloud events.
  function initDropboxEvents() {
    
    // Test and save Dropbox storage.
    $('.js-backwpup-test-dropbox-storage').on('click', function () {
      const data = {
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
          refresh_storage_destinations('DROPBOX', response.connected);
          dropbox_refresh_authentification();
          closeSidebar();
        },
        "POST",
        function (request, error) {
          refresh_storage_destinations('DROPBOX', false);
          alert(request.responseJSON.error);
        }
      );
    });

    // Delete Dropbox authentication.
    $('.js-backwpup-delete-dropbox-auth').on('click', function() {
      const data = {
        'cloud_name' : 'dropbox',
        'delete_auth' : true,
        'dropboxmaxbackups' : $("#dropboxmaxbackups").val(),
        'dropboxdir' : $("#dropboxdir").val(),
      }
      requestWPApi(
        backwpupApi.cloudsaveandtest,
        data,
        function (response) {
          refresh_storage_destinations('DROPBOX', response.connected);
          dropbox_refresh_authentification();
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
    $(".js-backwpup-open-modal").on('click', function () {
      const panel = $(this).data("content");
      let dataset = {};
      if ($(this).data("url")) {
        dataset.url = $(this).data("url");
      }
      openModal(panel,dataset);
    });
  }

  $(".js-backwpup-close-modal").on('click', closeModal);

  // Filter table list
  $(".js-backwpup-filter-tables").on('keyup', function () {
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

  // Frequency fields
  function showFrequencyFilesFields(frequency) {
    if (frequency === "weekly") {
      $(".js-backwpup-frequency-file-show-if-weekly").show();
      $(".js-backwpup-frequency-file-show-if-monthly").hide();
    }

    if (frequency === "monthly") {
      $(".js-backwpup-frequency-file-show-if-weekly").hide();
      $(".js-backwpup-frequency-file-show-if-monthly").show();
    }

    if (frequency === "daily") {
      $(".js-backwpup-frequency-file-show-if-weekly").hide();
      $(".js-backwpup-frequency-file-show-if-monthly").hide();
    }
  }

  function showFrequencyTablesFields(frequency) {
    if (frequency === "weekly") {
      $(".js-backwpup-frequency-table-show-if-weekly").show();
      $(".js-backwpup-frequency-table-show-if-monthly").hide();
    }

    if (frequency === "monthly") {
      $(".js-backwpup-frequency-table-show-if-weekly").hide();
      $(".js-backwpup-frequency-table-show-if-monthly").show();
    }

    if (frequency === "daily") {
      $(".js-backwpup-frequency-table-show-if-weekly").hide();
      $(".js-backwpup-frequency-table-show-if-monthly").hide();
    }
  }

  showFrequencyFilesFields($(".js-backwpup-frequency-files").val());
  showFrequencyTablesFields($(".js-backwpup-frequency-tables").val());

  $(".js-backwpup-frequency-files").on('change', function () {
    showFrequencyFilesFields($(this).val());
  });

  $(".js-backwpup-frequency-tables").on('change', function () {
    showFrequencyTablesFields($(this).val());
  });

  /**
   * Initialize the menu event.
   */
  function initMenuEvent() {
    // Menu
    $(".js-backwpup-menu").on('click', function (event) {
      event.stopPropagation();
      const $menu = $(this).find(".js-backwpup-menu-content");

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
      console.log(isChecked);
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
  function loadBackupsListingAndPagination(page) {
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
  $( ".js-backwpup-add-input-button" ).on( 'click', function () {
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

  $(".js-backwpup-add-input-tags").on(
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
  $(".js-backwpup-toggle-include button").on('click', function () {
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

  // Start backup
  $(".js-backwpup-start-backup").on('click', function () {
    requestWPApi(backwpupApi.startbackup, {}, function(response) {
      if ( response.status === 200 ) {
        setTimeout(function() {
            window.location.reload();
        }, 500);
      }
    }, 'POST');
  });

  // Exclude files in sidebar
  $(".js-backwpup-toggle-exclude").on('change', function () {
    const checked = $(this).prop("checked");
    $(this).closest("div").find("button").prop("disabled", !checked);
  });

  // Toggle Files.
  $(".js-backwpup-toggle-files").on('change', function () {
    const checked = $(this).prop("checked");
    let job_id = $(this).data("job-id");
    $("#backwpup-files-options").find("button").prop("disabled", !checked);
    requestWPApi(
      backwpupApi.updatejob,
      {
        'job_id': job_id,
        'activ': checked,
        'type': 'files',
      },
      function (response) {
        $('#backwpup-files-options div p.label-scheduled').html(response.message);
      },
      "POST"
    );
  });

  // Toggle Database.
  $(".js-backwpup-toggle-database").on('change', function () {
    const checked = $(this).prop("checked");
    let job_id = $(this).data("job-id");
    $("#backwpup-database-options").find("button").prop("disabled", !checked);
    requestWPApi(
      backwpupApi.updatejob,
      {
        'job_id': job_id,
        'activ': checked,
        'type': 'database',
      },
      function (response) {
        $('#backwpup-database-options div p.label-scheduled').html(response.message);
      },
      "POST"
    );  
  });

  // Test and save S3 storage.
  $(".js-backwpup-test-s3-storage").on('click', function () {
    if ($("#s3bucketerror").html()!="") {
      refresh_storage_destinations('S3', false);
      alert('Error in Bucket Configurations');
      return;
    }
    const data = {
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
        refresh_storage_destinations('S3', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations('S3', false);
        alert("Error in cloud configuration");
      }
    );

  });

  // Test and save Glacier storage.
  $('.js-backwpup-test-glacier-storage').on('click', function () {
    if ($("#glacierbucketerror").html()!="") {
      refresh_storage_destinations('GLACIER', false);
      alert('Error in Bucket Configurations');
      return;
    }
    const data = {
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
        refresh_storage_destinations('GLACIER', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations('GLACIER', false);
        alert("Error in cloud configuration");
      }
    );
  });

  // Test and save local folder storage.
  $('.js-backwpup-test-folder-storage').on('click', function () {
    const data = {
      'cloud_name' : 'folder',
      'backupdir' : $("#backupdir").val(),
      'maxbackups' : $("#maxbackups").val(),
    };
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations('FOLDER', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations('FOLDER', false);
        alert("Error in cloud configuration");
      }
    );
  });
  

  // Test and save ftp storage.
  $('.js-backwpup-test-ftp-storage').on('click', function () {
    const data = {
      'cloud_name' : 'ftp',
      'ftphost' : $("#ftphost").val(),
      'ftphostport' : $("#ftphostport").val(),
      'ftpuser' : $("#ftpuser").val(),
      'ftppass' : $("#ftppass").val(),
      'ftptimeout' : $("#ftptimeout").val(),
      'ftpdir' : $("#ftpdir").val(),
      'ftpmaxbackups' : $("#ftpmaxbackups").val(),
      'ftpssl' : $("#ftpssl").prop("checked"),
      'ftppasv' : $("#ftppasv").prop("checked"),
    };
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations('FTP', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations('FTP', false);
        alert("Error in cloud configuration");
      }
    );
  });

  // Test and save Gdrive storage.
  $('.js-backwpup-test-gdrive-storage').on('click', function () {
    const data = {
      'cloud_name' : 'gdrive',
      'gdriveusetrash' : $("#gdriveusetrash").prop("checked"),
      'gdrivemaxbackups' : $("#gdrivemaxbackups").val(),
      'gdrivedir' : $("#gdrivedir").val(),
    };
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations('GDRIVE', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations('GDRIVE', false);
        alert("Error in cloud configuration");
      }
    );
  });

  // Delete Hidrive authentication.
  $('.js-backwpup-delete-hidrive-auth').on('click', function() {
    const data = {
      'cloud_name' : 'hidrive',
      'hidrive_delete_authorization' : true,
      'hidrive_max_backups' : $("#hidrive_max_backups").val(),
      'hidrive_destination_folder' : $("#hidrive_destination_folder").val(),
    }
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        closeSidebar();
      },
      "POST",
      function (request, error) {
        alert("Error in cloud configuration");
      }
    );
  });

  // Test and save HiDrive storage.
  $('.js-backwpup-test-hidrive-storage').on('click', function () {
    const data = {
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
        refresh_storage_destinations('HIDRIVE', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations('HIDRIVE', false);
        alert("Error in cloud configuration");
      }
    );
  });

  // Test and save ONEDRIVE storage.
  $('.js-backwpup-test-onedrive-storage').on('click', function () {
    const data = {
      'cloud_name': 'onedrive',
      'onedrivedir': $("#onedrivedir").val(),
      'onedrivemaxbackups': $("#onedrivemaxbackups").val(),
    }
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations('ONEDRIVE', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations('ONEDRIVE', true);
        alert("Error in cloud configuration");
      }
    );
  });

  // Test and save SugarSync storage.
  $('.js-backwpup-test-sugar-sync-storage').on('click', function () {
    // TODO Test the connection
    const data = {
      'cloud_name' : 'sugarsync',
      'sugardir' : $("#sugardir").val(),
      'sugarmaxbackups' : $("#sugarmaxbackups").val(),
      'sugarroot' : $("#sugarroot").val(),
    };
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations('SUGARSYNC', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations('SUGARSYNC', false);
        alert("Error in cloud configuration");
      }
    );
  });

  // Test and save RackSpace storage.
  $('.js-backwpup-test-rackspace-cloud-storage').on('click', function () {
    const data = {
      'cloud_name' : 'rsc',
      'newrsccontainer' : $("#newrsccontainer").val(),
      'rscdir' : $("#rscdir").val(),
      'rscmaxbackups' : $("#rscmaxbackups").val(),
      'rsccontainer' : $("#rsccontainer").val(),
      'rscusername' : $("#rscusername").val(),
      'rscapikey' : $("#rscapikey").val(),
    };
    requestWPApi(
      backwpupApi.cloudsaveandtest,
      data,
      function (response) {
        refresh_storage_destinations('RSC', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations('RSC', false);
        alert("Error in cloud configuration");
      }
    );
  });

  // Test and save MSAZURE storage.
  $('.js-backwpup-test-msazure-storage').click(function () {
    const data = {
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
        refresh_storage_destinations('MSAZURE', response.connected);
        closeSidebar();
      },
      "POST",
      function (request, error) {
        refresh_storage_destinations('MSAZURE', false);
        alert("Error in cloud configuration");
      }
    );
  });

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
  $(".js-backwpup-toggle-storage").on('click', function() {
    const content = $(this).data("content");
    openSidebar(content);
  });

  // Toggle Gdrive reauthenticate action.
  $('.js-backwpup-gdrive-reauthenticate').on('click', function() {
    openModal('dialog');
    $('.js-backwpup-refresh-authentification').data('trigger', 'gdrive_refresh_authentification');
    window.open($(this).data('url'), '_blank');
  });

  // Toggle OneDrive reauthenticate action.
  $('.js-backwpup-onedrive-reauthenticate').on('click', function() {
    openModal('dialog');
    $('.js-backwpup-refresh-authentification').data('trigger', 'onedrive_refresh_authentification');
    window.open($(this).data('url'), '_blank');
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
  $('.js-backwpup-modal-and-focus').on('click', function() {
    openModal('dialog');
    const focus = $(this).data('id-focus-after');
    $('.js-backwpup-refresh-authentification').data('trigger', '$("#'+focus+'").focus()');
    window.open($(this).data('url'), '_blank');
  });

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
    $(".onboarding-advanced-files-setings").prop("disabled", !checked);
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

  // Handle Save Settings button click
  $(".save_database_settings").on('click', function () {
    const container = $(this).closest("article");
    const frequency = container.find("select[name='frequency']").val();
    const startTime = container.find("input[name='start_time']").val();

    const data = {
        frequency: frequency,
        start_time: startTime,
    };

    requestWPApi(
      backwpupApi.save_database_settings,
      data,
      function (response) {
        if (response.status === 200) {
          $('#backwpup-database-options div p.label-scheduled').html(response.next_backup);
          if ($("#backwpup-onboarding-panes")) {
            let onboarding_select = $("#backwpup-onboarding-panes").find("select[name='database_frequency']");
            let select = $("#sidebar-frequency-tables-pro").find("select[name='frequency']");
            onboarding_select.val(select.val());
          }
          closeSidebar();
        } 
      },
      "POST"
    );
  });

  // Handle Save Settings button click
  $(".save_files_settings").on('click', function () {
    const container = $(this).closest("article");
    const frequency = container.find("select[name='frequency']").val();
    const startTime = container.find("input[name='start_time']").val();

    const data = {
        frequency: frequency,
        start_time: startTime,
    };

    requestWPApi(
      backwpupApi.save_files_settings,
      data,
      function (response) {
        if (response.status === 200) {
          $('#backwpup-files-options div p.label-scheduled').html(response.next_backup);
          if ($("#backwpup-onboarding-panes")) {
            let onboarding_select = $("#backwpup-onboarding-panes").find("select[name='files_frequency']");
            let select = $("#sidebar-frequency-files-pro").find("select[name='frequency']");
            onboarding_select.val(select.val());
          }
          closeSidebar();
        } 
      },
      "POST"
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
  $("#save-excluded-tables").on("click", function () {
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
                closeSidebar();
            }
        },
        "POST"
    );
  });

  $('.js-backwpup-license_update').on('click', update_license);

  $('.file-exclusions-submit').on('click', function (e) {
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
  function startBackupProcess() {
    if ( ! isGenerateJsIncluded() ) { 
      requestWPApi(backwpupApi.startbackup, {}, function(response) {
        if (response.status === 200) {
          setTimeout(function() {
            if ('#dbbackup' !== window.location.hash ) {
              window.location.reload();
            }
        }, 500);
        }
      }, 'POST');
    } else {
      // Add a listener for the custom 'hide' event
      $('.progress-bar').on('hide', function () {
        console.log('.progress-bar is being hidden');
      });
    }
  }
  
  // Call the functions when the "First Backup" page is loaded
  if (window.location.search.includes('backwpupfirstbackup')) {
    startBackupProcess();
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
});

// Add a custom 'hide' event when the .hide() function is called
(function ($) {
  var originalHide = $.fn.hide;
  $.fn.hide = function () {
      this.trigger('hide'); // Trigger 'hide' event
      return originalHide.apply(this, arguments);
  };
})(jQuery);