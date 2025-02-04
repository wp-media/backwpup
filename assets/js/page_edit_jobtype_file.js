jQuery(document).ready(function ($) {

	$('input[name="backuproot"]').on('change', function () {
		if ($('input[name="backuproot"]').prop("checked")) {
			$('#backuprootexcludedirs').show();
		} else {
			$('#backuprootexcludedirs').hide();
		}
	});

	if ($('input[name="backuproot"]').prop("checked")) {
		$('#backuprootexcludedirs').show();
	} else {
		$('#backuprootexcludedirs').hide();
	}

	$('input[name="backupcontent"]').on('change', function () {
		if ($('input[name="backupcontent"]').prop("checked")) {
			$('#backupcontentexcludedirs').show();
		} else {
			$('#backupcontentexcludedirs').hide();
		}
	});

	if ($('input[name="backupcontent"]').prop("checked")) {
		$('#backupcontentexcludedirs').show();
	} else {
		$('#backupcontentexcludedirs').hide();
	}

	$('input[name="backupplugins"]').on('change', function () {
		if ($('input[name="backupplugins"]').prop("checked")) {
			$('#backuppluginsexcludedirs').show();
		} else {
			$('#backuppluginsexcludedirs').hide();
		}
	});

	if ($('input[name="backupplugins"]').prop("checked")) {
		$('#backuppluginsexcludedirs').show();
	} else {
		$('#backuppluginsexcludedirs').hide();
	}

	$('input[name="backupthemes"]').on('change', function () {
		if ($('input[name="backupthemes"]').prop("checked")) {
			$('#backupthemesexcludedirs').show();
		} else {
			$('#backupthemesexcludedirs').hide();
		}
	});

	if ($('input[name="backupthemes"]').prop("checked")) {
		$('#backupthemesexcludedirs').show();
	} else {
		$('#backupthemesexcludedirs').hide();
	}

	$('input[name="backupuploads"]').on('change', function () {
		if ($('input[name="backupuploads"]').prop("checked")) {
			$('#backupuploadsexcludedirs').show();
		} else {
			$('#backupuploadsexcludedirs').hide();
		}
	});

	if ($('input[name="backupuploads"]').prop("checked")) {
		$('#backupuploadsexcludedirs').show();
	} else {
		$('#backupuploadsexcludedirs').hide();
	}

});