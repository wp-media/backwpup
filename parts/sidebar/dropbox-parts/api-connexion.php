<?php
use BackWPup\Utils\BackWPupHelpers;
$dropbox = new BackWPup_Destination_Dropbox_API('dropbox');
$dropbox_auth_url = $dropbox->oAuthAuthorize();
$dropbox = new BackWPup_Destination_Dropbox_API('sandbox');
$sandbox_auth_url = $dropbox->oAuthAuthorize();

$dropboxtoken = BackWPup_Option::get($job_id, 'dropboxtoken', []);
?>

<?php
  BackWPupHelpers::component("heading", [
    "level" => 2,
    "title" => __("Login", 'backwpup'),
    "font" => "small",
    "class" => "mb-4",
  ]);
  ?>
	<?php if (empty($dropboxtoken['refresh_token'])) : ?>
  <?php
  BackWPupHelpers::component("navigation/link", [
    "type" => "secondary",
    "content" => __("Create Account", 'backwpup'),
    "icon_name" => "external",
    "icon_position" => "after",
		"url" => "https://www.dropbox.com/register",
    "full_width" => true,
    "newtab" => true,
  ]);
  ?>
  <p class="mt-2 text-base text-danger"><?php _e("Not authenticated", 'backwpup'); ?></p>
  <?php BackWPupHelpers::component("separator"); ?>

<div class="flex flex-col gap-2">
    <?php
    BackWPupHelpers::component("heading", [
      "level" => 3,
      "font" => "xs",
      "title" => __("App Access to Dropbox", 'backwpup'),
      "tooltip" => __("Add Dropbox Authentification code", 'backwpup'),
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "sandbox_code",
      "identifier" => "sandbox_code",
      "label" => __("Authentification code", 'backwpup'),
      "value" => "",
    ]);
    ?>
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "label" => __("Get Dropbox App auth code", 'backwpup'),
      "icon_name" => "external",
      "icon_position" => "after",
      "full_width" => true,
      "trigger" => "modal-and-focus",
      "data" => [
        "url" => $sandbox_auth_url,
        "id-focus-after" => "sandbox_code",
      ]
    ]);
    ?>
    <p class="px-2 font-light text-xs">
      <?php _e("A dedicated folder named BackWPup will be created inside of the Apps folder in your Dropbox. BackWPup will get read and write access to that folder only. You can specify a subfolder as your backup destination for this job in the destination field below.", 'backwpup'); ?>
    </p>

    <p class="my-2 text-center text-sm"><?php _e("OR", 'backwpup'); ?></p>

    <?php
    BackWPupHelpers::component("heading", [
      "level" => 3,
      "font" => "xs",
      "title" => __("Full Access to Dropbox", 'backwpup'),
      "tooltip" => "Add your Dropbox Authentification code",
    ]);
    ?>

    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "dropbbox_code",
      "identifier" => "dropbbox_code",
      "label" => __("Authentification code", 'backwpup'),
      "value" => "",
    ]);
    ?>
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "label" => __("Get Dropbox App auth code", 'backwpup'),
      "icon_name" => "external",
      "icon_position" => "after",
      "full_width" => true,
      "trigger" => "modal-and-focus",
      "data" => [
        "url" => $dropbox_auth_url,
        "id-focus-after" => "dropbbox_code",
      ]
    ]);
    ?>

    <p class="px-2 font-light text-xs">
      <?php _e("BackWPup will have full read and write access to your entire Dropbox. You can specify your backup destination wherever you want, just be aware that ANY files or folders inside of your Dropbox can be overridden or deleted by BackWPup", 'backwpup'); ?>
    </p>

  </div>
	<?php else : ?>
	<?php
	BackWPupHelpers::component("form/button", [
		"type" => "secondary",
		"label" => __("Delete Dropbox Authentication", 'backwpup'),
		"full_width" => true,
		"trigger" => "delete-dropbox-auth",
    "data" => [
      "job-id" => $job_id,
    ],
	]);
	?>
	<p class="mt-2 text-base text-secondary-base"><?php _e("Authenticated", 'backwpup'); ?></p>
	<?php endif;?>