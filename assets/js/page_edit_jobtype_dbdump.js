jQuery(document).ready(function ($) {
  $("#dball").on('click', function () {
    $('input[name="tabledb[]"]').prop("checked", true).change();
  });

  $("#dbnone").on('click', function () {
    $('input[name="tabledb[]"]').prop("checked", false).change();
  });

  $("#dbwp").on('click', function () {
    $('input[name="tabledb[]"]').prop("checked", false).change();
    $('input[name="tabledb[]"][value^="' + $("#dbwp").val() + '"]')
      .prop("checked", true)
      .change();
  });

  var dbdumpwpdbsettings = $('input[name="dbdumpwpdbsettings"]');
  if (dbdumpwpdbsettings.length > 0) {
    dbdumpwpdbsettings.on('change', function () {
      if (dbdumpwpdbsettings.prop("checked")) {
        $("#dbconnection").hide();
      } else {
        $("#dbconnection").show();
      }
    });
  }

  var trdbdumpmysqlfolder = $("#trdbdumpmysqlfolder");
  $('input[name="dbdumptype"]').on('change', function () {
    if ($("#iddbdumptype-syssql").prop("checked")) {
      trdbdumpmysqlfolder.show();
    } else {
      trdbdumpmysqlfolder.hide();
    }
  });

  $("a#fix-mysqldump-path").on('click', function (e) {
    e.preventDefault();
    trdbdumpmysqlfolder.show();
    $("input", trdbdumpmysqlfolder).focus();
  });

  if ($("#iddbdumptype-syssql").prop("checked")) {
    trdbdumpmysqlfolder.show();
  } else {
    trdbdumpmysqlfolder.hide();
  }

  function db_tables() {
    var data = {
      action: "backwpup_jobtype_dbdump",
      action2: "tables",
      dbname: $("#dbdumpdbname").val(),
      dbhost: $("#dbdumpdbhost").val(),
      dbuser: $("#dbdumpdbuser").val(),
      dbpassword: $("#dbdumpdbpassword").val(),
      wpdbsettings: $("#dbdumpwpdbsettings:checked").val(),
      jobid: $("#jobid").val(),
      _ajax_nonce: $("#backwpupajaxnonce").val(),
    };
    $.post(ajaxurl, data, function (response) {
      $("#dbtables").replaceWith(response);
    });
  }

  $("#dbdumpdbname").on('change', function () {
    db_tables();
  });
  $("#dbdumpwpdbsettings").on('change', function () {
    db_tables();
    db_databases();
    db_charsets();
  });

  function db_databases() {
    var data = {
      action: "backwpup_jobtype_dbdump",
      action2: "databases",
      dbhost: $("#dbdumpdbhost").val(),
      dbuser: $("#dbdumpdbuser").val(),
      dbpassword: $("#dbdumpdbpassword").val(),
      wpdbsettings: $("#dbdumpwpdbsettings:checked").val(),
      _ajax_nonce: $("#backwpupajaxnonce").val(),
    };
    $.post(ajaxurl, data, function (response) {
      $("#dbdumpdbname").replaceWith(response);
      db_tables();
      $("#dbdumpdbname").on('change', function () {
        db_tables();
      });
    });
  }

  function db_charsets() {
    var data = {
      action: "backwpup_jobtype_dbdump",
      action2: "charsets",
      dbhost: $("#dbdumpdbhost").val(),
      dbuser: $("#dbdumpdbuser").val(),
      dbpassword: $("#dbdumpdbpassword").val(),
      wpdbsettings: $("#dbdumpwpdbsettings:checked").val(),
      jobid: $("#jobid").val(),
      _ajax_nonce: $("#backwpupajaxnonce").val(),
    };

    $.post(ajaxurl, data, function (response) {
      $("#dbdumpdbcharset").replaceWith(response);
    });
  }

  $("#dbdumpdbhost").on('change', function () {
    db_databases();
    db_charsets();
  });
  $("#dbdumpdbuser").on('change', function () {
    db_databases();
    db_charsets();
  });
  $("#dbdumpdbpassword").on('change', function () {
    db_databases();
    db_charsets();
  });
});
