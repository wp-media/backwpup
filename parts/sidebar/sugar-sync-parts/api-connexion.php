<?php
use BackWPup\Utils\BackWPupHelpers;

$jobid = get_site_option( 'backwpup_backup_files_job_id', false );
$token = BackWPup_Option::get($jobid, 'sugarrefreshtoken', false);
?>
<?php
BackWPupHelpers::component("heading", [
	"level" => 2,
	"title" => __("Login", 'backwpup'),
	"font" => "small",
	"class" => "mb-4",
]);
?>

<div class="flex flex-col gap-2">
	<?php if (!$token) : ?>
		<?php
		BackWPupHelpers::component("form/text", [
			"name" => "sugaremail",
			"identifier" => "sugaremail",
			"label" => __("Email Address", 'backwpup'),
			"value" => "",
			"required" => true,
		]);
		?>

		<?php
		BackWPupHelpers::component("form/text", [
			"name" => "sugarpass",
			"identifier" => "sugarpass",
			"type" => "password",
			"label" => __("Password", 'backwpup'),
			"value" => "",
			"required" => true,
		]);
		?>

		<?php
		BackWPupHelpers::component("form/button", [
			"type" => "secondary",
			"label" => __("Authenticate with SugarSync", 'backwpup'),
			"trigger" => "authenticate-sugar-sync",
			"full_width" => true,
		]);
		?>
	<?php else : ?>
		<?php
		BackWPupHelpers::component("form/button", [
			"type" => "secondary",
			"label" => __("Delete Sugar Sync Authentication", 'backwpup'),
			"full_width" => true,
			"trigger" => "delete-sugar-sync-auth",
		]);
		?>
		<?php
		BackWPupHelpers::component("alerts/info", [
			"type" => "info",
			"font" => "xs",
			"content" => __("Authenticated", 'backwpup'),
		]);
		?>
	<?php endif; ?>
</div>
