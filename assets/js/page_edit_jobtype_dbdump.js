jQuery(document).ready(function ($) {

    $('#dball').click(function () {
        $('input[name="tabledb[]"]').prop("checked", true).change();;
    });

    $('#dbnone').click(function () {
        $('input[name="tabledb[]"]').prop("checked", false).change();;
    });

    $('#dbwp').click(function () {
        $('input[name="tabledb[]"]').prop("checked", false).change();;
        $('input[name="tabledb[]"][value^="' + $('#dbwp').val() + '"]').prop("checked", true).change();;
    });

    $('input[name="dbdumpwpdbsettings"]').change(function () {
        if ( $('input[name="dbdumpwpdbsettings"]').prop("checked") ) {
            $('#dbconnection').hide();
        } else {
            $('#dbconnection').show();
        }
    });

    function db_tables() {
        var data = {
            action:'backwpup_jobtype_dbdump',
            action2:'tables',
            dbname:$('#dbdumpdbname').val(),
            dbhost:$('#dbdumpdbhost').val(),
            dbuser:$('#dbdumpdbuser').val(),
            dbpassword:$('#dbdumpdbpassword').val(),
            wpdbsettings:$('#dbdumpwpdbsettings:checked').val(),
            jobid:$('#jobid').val(),
            _ajax_nonce:$('#backwpupajaxnonce').val()
        };
        $.post(ajaxurl, data, function (response) {
            $('#dbtables').replaceWith(response);
        });
    }
    $('#dbdumpdbname').change(function () {
        db_tables();
    });
    $('#dbdumpwpdbsettings').change(function () {
        if ($('#dbdumpwpdbsettings:checked').val()) {
            db_tables();
        } else {
            db_databases();
        }
    });

    function db_databases() {
        var data = {
            action:'backwpup_jobtype_dbdump',
            action2:'databases',
            dbhost:$('#dbdumpdbhost').val(),
            dbuser:$('#dbdumpdbuser').val(),
            dbpassword:$('#dbdumpdbpassword').val(),
            dbname:$('input[name="dbselected"]').val(),
            _ajax_nonce:$('#backwpupajaxnonce').val()

        };
        $.post(ajaxurl, data, function (response) {
            $('#dbdumpdbname').replaceWith(response);
            db_tables();
            $('#dbdumpdbname').change(function () {
                db_tables();
            });
        });
    }
    $('#dbdumpdbhost').change(function () {
        db_databases();
    });
    $('#dbdumpdbuser').change(function () {
        db_databases();
    });
    $('#dbdumpdbpassword').change(function () {
        db_databases();
    });
});