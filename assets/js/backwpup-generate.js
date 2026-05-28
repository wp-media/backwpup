jQuery(document).ready(function ($) {

  var userAborted = false;

  $("#runningjob").show('slow');
  $('.js-backwpup-open-modal').prop('disabled', true);
  $(document).trigger('start-backupjob');
  backwpup_show_progress = function() {
    if (userAborted) {
      return;
    }
    var save_log_pos = 0;
    $.ajax({
      type: 'GET',
      url: ajaxurl,
      cache: false,
      data: {
        action: 'backwpup_working',
        logpos: $('#logpos').val(),
        logfile: backwpupApi.logfile,
        _ajax_nonce: backwpupApi.nonce_generate
      },
      dataType: 'json',
      success: function(rundata) {
        if (rundata == 0) {
          $("#runningjob").hide('slow');
          $("#backwpup-adminbar-running").remove();
        }
        if (0 < rundata.log_pos) {
          $('#logpos').val(rundata.log_pos);
        }
        if ('' != rundata.log_text) {
          $('#showworking').append(rundata.log_text);
          $('#TB_ajaxContent').scrollTop(rundata.log_pos * 15);
        }
        if (5 < rundata.step_percent) {
          let progress = Math.round((rundata.step_done-1+rundata.sub_step_percent/100)*100/rundata.step_todo);
          $('.backupgeneration-progress-box .progress-bar .progress-step span').text( progress + '%');
          $('.backupgeneration-progress-box .progress-bar .progress-step').css('width', parseFloat(progress) + '%');
        }
        if ('' != rundata.last_msg) {
          $('.backupgeneration-progress-box-step').html(rundata.on_step +" : "+rundata.last_msg);
        }
        if (rundata.job_done == 1) {
          $("#abortbutton").remove();
          $('.backupgeneration-progress-box .progress-bar').hide();
          $("#backwpup-adminbar-running").remove();
          $("#backupgeneration-progress-box-title").html(rundata.last_msg);
          $('.backupgeneration-progress-box-step').remove();
          $('.js-backwpup-open-modal').prop('disabled', false);
          if (!userAborted && window.location.search.includes('backwpupfirstbackup')) {
            $('#info_container_2').hide();
            $('#first-congratulations').show();
          }
          if (!userAborted) {
            $(document).trigger('backup-complete');
          }
        } else {
          if (rundata.restart_url !== '') {
            backwpup_trigger_cron(rundata.restart_url);
          }
          if (!userAborted) {
            setTimeout('backwpup_show_progress()', 750);
          }
        }
      },
      error: function() {
        console.log('error');
        $('.js-backwpup-open-modal').prop('disabled', false);
        if (!userAborted) {
          setTimeout('backwpup_show_progress()', 750);
        }
      }
    });
  };
  backwpup_trigger_cron = function(cron_url) {
    $.ajax({
      type: 'POST',
      url: cron_url,
      dataType: 'text',
      cache: false,
      processData: false,
      timeout: 1
    });
  };
  backwpup_show_progress();
  $('#showworkingclose').on('click', function() {
    let redirect_url = $(this).data('bwpup_redirect_url');
    if ( redirect_url && redirect_url !== window.location.href ) {
      window.location.href = redirect_url;
    } else {
      $("#runningjob").hide('slow');
    }
    return false;
  });

  /**
   * Redirect to redirectUrl if provided, otherwise reload the current page.
   *
   * @param {string|null} redirectUrl
   */
  function doReloadOrRedirect(redirectUrl) {
    if (redirectUrl) {
      window.location.href = redirectUrl;
    } else {
      window.location.reload();
    }
  }

  /**
   * Poll the job-abort-status endpoint until the background PHP process has
   * finished updating the DB row from 'created' to 'aborted', then navigate.
   *
   * @param {string|null} redirectUrl  URL to redirect to, or null to reload.
   * @param {number}      attempts     Number of polling attempts so far.
   */
  function pollAbortStatus(redirectUrl, attempts) {
    var maxAttempts = 30;
    if (attempts >= maxAttempts) {
      doReloadOrRedirect(redirectUrl);
      return;
    }
    $.ajax({
      type: 'GET',
      url: backwpupApi.job_abort_status,
      data: { job_id: backwpupApi.job_id },
      headers: { 'X-WP-Nonce': backwpupApi.nonce },
      success: function(response) {
        if (response && response.is_done) {
          doReloadOrRedirect(redirectUrl);
        } else {
          setTimeout(function() {
            pollAbortStatus(redirectUrl, attempts + 1);
          }, 1000);
        }
      },
      error: function() {
        setTimeout(function() {
          pollAbortStatus(redirectUrl, attempts + 1);
        }, 1000);
      }
    });
  }

  $('.js-backwpup-abortbutton').on('click', function() {
    var $btn = $(this);
    var url = $btn.data('url');
    userAborted = true;
    $btn.prop('disabled', true);
    if (url) {
      $.ajax({
        type: 'GET',
        url: url,
        success: function() {
          $("#backwpup-adminbar-running").remove();
          $('.js-backwpup-open-modal').prop('disabled', false);
          var redirectUrl = window.location.search.includes('backwpupfirstbackup') ? backwpup.adminUrl : null;
          if (backwpupApi.job_id && backwpupApi.job_id > 0) {
            pollAbortStatus(redirectUrl, 0);
          } else {
            doReloadOrRedirect(redirectUrl);
          }
        },
        error: function() {
          userAborted = false;
          $btn.prop('disabled', false);
          console.log('Abort request failed');
        }
      });
    }
    return false;
  });
});