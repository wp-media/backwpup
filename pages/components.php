<?php
  use BackWPup\Utils\BackWPupHelpers;
?>
<!-- Notification -->
<?php
BackWPupHelpers::component("alerts/notification", [
  "children" => "example/notification"
]);
?>
<!-- /Notification -->


<div class="mt-10 grid grid-cols-4 gap-16">




  <div class="flex flex-col gap-8">
    <!-- Title -->
    <?php
    BackWPupHelpers::component("heading", [
      "level" => 1,
      "title" => "Title level 1",
    ]);
    ?>
    <!-- /Title -->

    <!-- Title:level2 -->
    <?php
    BackWPupHelpers::component("heading", [
      "level" => 2,
      "title" => "Title level 2",
    ]);
    ?>
    <!-- /Title:level2 -->

    <!-- Title:level3 -->
    <?php
    BackWPupHelpers::component("heading", [
      "level" => 3,
      "title" => "Title level 3",
    ]);
    ?>
    <!-- /Title:level3 -->

    <!-- Title:level3:tooltip -->
    <?php
    BackWPupHelpers::component("heading", [
      "level" => 3,
      "title" => "Title level 3",
      "tooltip" => "Tooltip Content",
    ]);
    ?>
    <!-- /Title:level3:tooltip  -->

    <!-- Closable-heading -->
    <?php
    BackWPupHelpers::component('closable-heading', [
      'title' => "Heading for modal/sidebar",
      'type' => 'modal'
    ]);
    ?>
    <!-- Closable-heading -->

    <p>
      <!-- Link -->
      <?php
      BackWPupHelpers::component("navigation/link", [
        "url" => "#",
        "newtab" => true,
        "content" => "Link",
      ]);
      ?>
      <!-- /Link -->
    </p>

    <p>
      <!-- Link:small -->
      <?php
      BackWPupHelpers::component("navigation/link", [
        "url" => "#",
        "newtab" => true,
        "font" => "small",
        "content" => "Small link",
      ]);
      ?>
      <!-- /Link:small -->
    </p>

    <p>
      <!-- Link:small:icon-right -->
      <?php
      BackWPupHelpers::component("navigation/link", [
        "url" => "#",
        "newtab" => true,
        "font" => "small",
        "content" => "Small link icon",
        "icon_position" => "after",
        "icon_name" => "arrow-right",
      ]);
      ?>
      <!-- /Link:small:icon-right -->
    </p>

    <p>
      <!-- Link:icon-left -->
      <?php
      BackWPupHelpers::component("navigation/link", [
        "url" => "#",
        "newtab" => true,
        "font" => "medium",
        "content" => "Small link icon",
        "icon_position" => "before",
        "icon_name" => "arrow-left",
      ]);
      ?>
      <!-- /Link:icon-rileftght -->
    </p>

    <p>
      <!-- Link:primary -->
      <?php
      BackWPupHelpers::component("navigation/link", [
        "type" => "primary",
        "href" => "#",
        "newtab" => true,
        "content" => "Link looking like a button",
      ]);
      ?>
      <!-- /Link:primary -->
    </p>

    <!-- Info-block:pro:small:link -->
    <?php
    BackWPupHelpers::component("alerts/info", [
      "type" => "pro",
      "font" => "small",
      "content" => "Info block with light green background",
      "children" => "example/link",
    ]);
    ?>
    <!-- /Info-block:pro:small:link -->

    <!-- Info-block:alert:small:link -->
    <?php
    BackWPupHelpers::component("alerts/info", [
      "type" => "alert",
      "font" => "small",
      "content" => "Info block with light yellow background",
      "children" => "example/link",
    ]);
    ?>
    <!-- /Info-block:alert:small:link -->

    <!-- Info-block:danger:small:link -->
    <?php
    BackWPupHelpers::component("alerts/info", [
      "type" => "danger",
      "font" => "small",
      "content" => "Info block with light red background",
      "children" => "example/link",
    ]);
    ?>
    <!-- /Info-block:danger:small:link -->

    <!-- Info-block:default:small:link -->
    <?php
    BackWPupHelpers::component("alerts/info", [
      "type" => "default",
      "font" => "small",
      "content" => "Info block default",
      "children" => "example/link",
    ]);
    ?>
    <!-- /Info-block:default:small:link -->

    <!-- Info-block:alert:medium:multiline -->
    <?php
    BackWPupHelpers::component("alerts/info", [
      "type" => "alert",
      "content" => "Text medium",
      "content2" => "With a second line",
    ]);
    ?>
    <!-- /Info-block:alert:medium:multiline -->

    <!-- Info-block:danger:xs:link -->
    <?php
    BackWPupHelpers::component("alerts/info", [
      "type" => "danger",
      "font" => "xs",
      "content" => "Very small text",
      "children" => "example/link-xs",
    ]);
    ?>
    <!-- /Info-block:danger:xs:link -->

    <!-- Grey-box -->
    <?php
    BackWPupHelpers::component("containers/grey-box", [
      "children" => "example/grey-box-content",
    ]);
    ?>
    <!-- /Grey-box -->

    <!-- App-container:1280px -->
    <?php
    BackWPupHelpers::component("containers/max-screen", [
      "children" => "example/container-content",
    ]);
    ?>
    <!-- /App-container:1200px -->

    <!-- Separator -->
    <?php BackWPupHelpers::component("separator"); ?>
    <!-- /Separator -->
  </div>




  <div class="flex flex-col items-start gap-8">
    <!-- Button:link -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "link",
      "label" => "Button link",
    ]);
    ?>
    <!-- /Button:link -->

    <!-- Button:link:small -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "link",
      "font" => "small",
      "label" => "Button link small",
    ]);
    ?>
    <!-- /Button:link:small -->

    <!-- Button:link:disabled -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "link",
      "disabled" => true,
      "label" => "Button link disabled",
    ]);
    ?>
    <!-- /Button:link:disabled -->

    <!-- Button:primary -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "primary",
      "label" => "Button primary",
    ]);
    ?>
    <!-- /Button:primary -->

    <!-- Button:primary:disabled -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "primary",
      "disabled" => true,
      "label" => "Button primary disabled",
    ]);
    ?>
    <!-- /Button:primary:disabled -->

    <!-- Button:primary:full-width -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "primary",
      "label" => "Button primary full width",
      "full_width" => true,
    ]);
    ?>
    <!-- /Button:primary:full-width -->

    <!-- Button:primary:icon-right -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "primary",
      "label" => "Button primary with right icon",
      "icon_name" => "arrow-right",
      "icon_position" => "after",
    ]);
    ?>
    <!-- /Button:primary:icon-right -->

    <!-- Button:primary:icon-left -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "primary",
      "label" => "Button primary with left icon",
      "icon_name" => "arrow-left",
      "icon_position" => "before",
    ]);
    ?>
    <!-- /Button:primary:icon-left -->


    <!-- Button:primary:small -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "primary",
      "font" => "small",
      "label" => "Button primary small",
    ]);
    ?>
    <!-- /Button:primary:small -->

    <!-- Button:primary:small:icon-right -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "primary",
      "font" => "small",
      "label" => "Button primary small right icon",
      "icon_name" => "arrow-right",
      "icon_position" => "after",
    ]);
    ?>
    <!-- /Button:primary:small:icon-right -->

    <!-- Button:primary:small:icon-left -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "primary",
      "font" => "small",
      "label" => "Button primary small left icon",
      "icon_name" => "plus",
      "icon_position" => "before",
    ]);
    ?>
    <!-- /Button:primary:small:icon-left -->

    <!-- Button:secondary -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "label" => "Button secondary",
    ]);
    ?>
    <!-- /Button:secondary -->

    <!-- Button:secondary:disabled -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "disabled" => true,
      "label" => "Button secondary disabled",
    ]);
    ?>
    <!-- /Button:secondary:disabled -->

    <!-- Button:secondary:full-width -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "full_width" => true,
      "label" => "Button secondary full width",
    ]);
    ?>
    <!-- /Button:secondary:full-width -->

    <!-- Button:secondary:icon-right -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "label" => "Button secondary with right icon",
      "icon_name" => "external",
      "icon_position" => "after",
    ]);
    ?>
    <!-- /Button:secondary:icon-right -->

    <!-- Button:secondary:icon-left -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "label" => "Button secondary with left icon",
      "icon_name" => "arrow-left",
      "icon_position" => "before",
    ]);
    ?>
    <!-- /Button:secondary:icon-left -->

    <!-- Button:secondary:small -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "font" => "small",
      "label" => "Button secondary small",
    ]);
    ?>
    <!-- /Button:secondary:small -->

    <!-- Button:secondary:small:full-width -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "font" => "small",
      "full_width" => true,
      "label" => "Button secondary small full",
    ]);
    ?>
    <!-- /Button:secondary:small:full-width -->

    <!-- Button:secondary:icon-right -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "font" => "small",
      "label" => "Button secondary small right icon",
      "icon_name" => "external",
      "icon_position" => "after",
    ]);
    ?>
    <!-- /Button:secondary:icon-right -->

    <!-- Button:secondary:small:icon-left -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "secondary",
      "font" => "small",
      "label" => "Button secondary small left icon",
      "icon_name" => "arrow-left",
      "icon_position" => "before",
    ]);
    ?>
    <!-- /Button:secondary:small:icon-left -->

    <!-- Button:settings:icon-right -->
    <?php
    BackWPupHelpers::component("form/button", [
      "type" => "settings",
      "label" => "Settings nav",
      "icon_name" => "arrow-right",
      "icon_position" => "after",
      "full_width" => true,
    ]);
    ?>
    <!-- /Button:settings:icon-right -->
  </div>




  <div class="flex flex-col gap-8">
    <!-- Toggle:off -->
    <?php
    BackWPupHelpers::component("form/toggle", [
      "name" => "toggle-1",
    ]);
    ?>
    <!-- /Toggle:off -->

    <!-- Toggle:on -->
    <?php
    BackWPupHelpers::component("form/toggle", [
      "name" => "toggle-2",
      "checked" => true,
    ]);
    ?>
    <!-- /Toggle:on -->

    <!-- Toggle:with-label:on -->
    <?php
    BackWPupHelpers::component("form/toggle", [
      "name" => "toggle-3",
      "checked" => true,
      "label" => "Toggle with label",
    ]);
    ?>
    <!-- /Toggle:with-label:on -->

    <!-- Checkbox -->
    <?php
    BackWPupHelpers::component("form/checkbox", [
      "name" => "checkox-1",
      "label" => "Checkbox",
    ]);
    ?>
    <!-- Checkbox -->

    <!-- Checkbox:checked -->
    <?php
    BackWPupHelpers::component("form/checkbox", [
      "name" => "checkox-2",
      "checked" => true,
      "label" => "Checkbox checked",
    ]);
    ?>
    <!-- Checkbox:checked -->

    <!-- Checkbox:multiline:checked -->
    <?php
    BackWPupHelpers::component("form/checkbox", [
      "name" => "checkox-3",
      "checked" => true,
      "label" => " A checkbox <br /> With a multiline text",
      "multiline" => true,
    ]);
    ?>
    <!-- Checkbox:multiline:checked -->

    <!-- Checkbox:checked:tooltip -->
    <?php
    BackWPupHelpers::component("form/checkbox", [
      "name" => "checkox-4",
      "checked" => true,
      "label" => "Checkbox with tooltip",
      "tooltip" => "Tooltip content",
    ]);
    ?>
    <!-- Checkbox:checked:tooltip -->

    <!-- Input-add -->
    <?php
    BackWPupHelpers::component("form/add", [
      "name" => "add-1",
      "placeholder" => "Field with button",
      "tags" => ["List", "Of", "Tags"]
    ]);
    ?>
    <!-- /Input-add -->

    <!-- Input-search -->
    <?php
    BackWPupHelpers::component("form/search", [
      "name" => "search-1",
      "placeholder" => "Input Search",
    ]);
    ?>
    <!-- /Input-search -->

    <!-- Select:empty-choice -->
    <?php
    BackWPupHelpers::component("form/select", [
      "name" => "search-1",
      "label" => "Select Field",
      "withEmpty" => true,
      "value" => "",
      "options" => [
        "option-1" => "Foo",
        "option-2" => "Bar",
        "option-3" => "Option",
      ],
    ]);
    ?>
    <!-- /Select:empty-choice -->

    <!-- Select:selected -->
    <?php
    BackWPupHelpers::component("form/select", [
      "name" => "search-1",
      "label" => "Select Field",
      "withEmpty" => true,
      "value" => "option-3",
      "options" => [
        "option-1" => "Foo",
        "option-2" => "Bar",
        "option-3" => "Option",
      ],
    ]);
    ?>
    <!-- /Select:selected -->

    <!-- Select:tooltip -->
    <?php
    BackWPupHelpers::component("form/select", [
      "name" => "search-1",
      "label" => "Select Field",
      "withEmpty" => true,
      "value" => "option-3",
      "options" => [
        "option-1" => "Foo",
        "option-2" => "Bar",
        "option-3" => "Option",
      ],
      "tooltip" => "Tooltip content",
    ]);
    ?>
    <!-- /Select:tooltip -->

    <!-- Input:text -->
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "text-1",
      "label" => "Standard input",
      "value" => "",
      "required" => true,
    ]);
    ?>
    <!-- /Input:text -->

    <!-- Input:text:value -->
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "text-2",
      "label" => "Standard input",
      "value" => "With a value",
      "required" => true,
    ]);
    ?>
    <!-- /Input:text:value -->

    <!-- Input:email:value:invalid -->
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "text-3",
      "type" => "email",
      "label" => "Email invalid input",
      "value" => "Not an email",
      "required" => true,
      "invalid" => true,
    ]);
    ?>
    <!-- /Input:email:value:invalid -->

    <!-- Input:number:min-max -->
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "text-4",
      "type" => "number",
      "label" => "Number input with min and max values",
      "value" => "4",
      "min" => 0,
      "max" => 10,
      "required" => true,
    ]);
    ?>
    <!-- /Input:number:min-max -->

    <!-- Input:text:tooltip -->
    <?php
    BackWPupHelpers::component("form/text", [
      "name" => "text-5",
      "label" => "Standard input with tooltip",
      "required" => true,
      "tooltip" => "Tooltip content",
    ]);
    ?>
    <!-- /Input:text:tooltip -->
  </div>




  <div class="flex flex-col gap-8">
    <!-- Accordion:open -->
    <?php
    BackWPupHelpers::component("containers/accordion", [
      "title" => "Accordion open",
      "open" => true,
      "children" => "example/accordion-content",
    ]);
    ?>
    <!-- /Accordion:open -->

    <!-- Accordion:closed -->
    <?php
    BackWPupHelpers::component("containers/accordion", [
      "title" => "Accordion closed",
      "open" => false,
      "children" => "example/accordion-content",
    ]);
    ?>
    <!-- /Accordion:closed -->

    <div class="group flex items-center gap-2 rounded-lg p-4 bg-grey-100">
      <!-- Storage-list -->
      <?php
      BackWPupHelpers::component("storage-list", [
        "storages" => [
          [
            "slug" => "google-drive",
            "name" => "Google Drive",
            "active" => true,
          ],
          [
            "slug" => "microsoft-onedrive",
            "name" => "Microsoft OneDrive",
            "active" => false,
          ],
          [
            "slug" => "amazon-s3",
            "name" => "Amazon S3",
            "active" => false,
          ],
          [
            "slug" => "sftp",
            "name" => "SFTP",
            "active" => false,
          ],
        ]
      ]);
      ?>
      <!-- /Storage-list -->
    </div>

    <!-- Storage-list-compact -->
    <?php BackWPupHelpers::component("storage-list-compact", ["storages" => ['DROPBOX', 'GDRIVE', 'GLACIER']]); ?>
    <!-- /Storage-list-compact -->

    <!-- Navigation -->
    <?php
    BackWPupHelpers::component("navigation/pagination", [
      "max_pages" => 10,
    ]);
    ?>
    <!-- /Navigation -->

    <!-- Menu-3-dots -->
    <?php
    BackWPupHelpers::component("navigation/menu", [
      "actions" => [
        ["name" => __("Download", 'backwpup'), "icon" => "download"],
        ["name" => __("Restore Full Backup", 'backwpup'), "icon" => "restore"],
        ["name" => __("Restore Database Only", 'backwpup'), "icon" => "restore"],
        ["name" => __("Delete", 'backwpup'), "icon" => "trash"],
      ],
    ]);
    ?>
    <!-- /Menu-3-dots -->

    <!-- Icon-tooltip -->
    <?php
    BackWPupHelpers::component("tooltip", [
      "content" => "Tooltip content",
      "icon_name" => "user-settings",
      "icon_size" => "large",
      "position" => "center",
    ]);
    ?>
    <!-- /Icon-tooltip -->

  </div>




</div>