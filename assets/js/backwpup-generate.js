jQuery(document).ready(function ($) {
  $("#runningjob").show('slow');
  backwpup_show_progress = function() {
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
        if (rundata.job_id && rundata.job_next_id) {
          $('#next_job_id').val(rundata.job_next_id);
        }
        if ('' != rundata.log_text) {
          $('#showworking').append(rundata.log_text);
          $('#TB_ajaxContent').scrollTop(rundata.log_pos * 15);
        }
        if (0 < rundata.step_percent) {
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
          if ('' !== $('#next_job_id').val()) {
            $.ajax({
              url: backwpupApi.startbackup,
              beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', backwpupApi.nonce);
              },
              method: 'POST',
              data: {
                job_id: $('#next_job_id').val()
              },
              success: function(response) {
                if ( response.status === 200 ) {
                  setTimeout(function() {
                    newUrl = window.location.href
                    if (newUrl.indexOf('#') === -1) {
                      newUrl += '#dbbackup';
                      window.location.href = newUrl;
                    }
                  }, 500);
                }
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
          if (window.location.search.includes('backwpupfirstbackup')) {
            $('#info_container_2').hide();
            $('#first-congratulations').show();
          }
        } else {
          if (rundata.restart_url !== '') {
            backwpup_trigger_cron(rundata.restart_url);
          }
          setTimeout('backwpup_show_progress()', 750);
        }
      },
      error: function() {
        console.log('error');
        setTimeout('backwpup_show_progress()', 750);
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
    if ( redirect_url ) {
      window.location.href = redirect_url;
    } else {
      $("#runningjob").hide('slow');
    }
    return false;
  });

  $('.js-backwpup-abortbutton').on('click', function() {
    var url = $(this).data('url');
    if (url) {
      $.ajax({
        type: 'GET',
        url: url,
        success: function(response) {
          // Nothing to show there cause the UI will be updated by the next ajax call
        },
        error: function() {
          console.log('Request failed');
        }
      });
    }
    return false;
  });

});