<?php
use BackWPup\Utils\BackWPupHelpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div class="inline-block p-1 bg-alert-light text-alert rounded">
	<?php BackWPupHelpers::component( 'icon', [ 'name' => 'alert' ] ); ?>
</div>

<h2 class="mt-4 mb-2 text-primary-darker text-lg font-semibold font-title"><?php esc_html_e( 'Creating a backup might take a few minutes, depending on your site’s size', 'backwpup' ); ?></h2>
<p class="text-xl"><?php esc_html_e( 'The page will update automatically when it’s ready to download. The backup will keep running. You’ll get a notification when it’s done.', 'backwpup' ); ?></p>
<p class="mt-2 text-base font-semibold text-alert"><?php esc_html_e( 'Feel free to leave 👋', 'backwpup' ); ?></p>