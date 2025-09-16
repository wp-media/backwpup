<?php
$pro_providers = [];
if( BackWPup::is_pro()) {
	$pro_providers = [
		'WPMedia\BackWPup\License\ServiceProvider',
	];
}

$providers = [
	'WPMedia\BackWPup\Adapters\ServiceProvider',
	'WPMedia\BackWPup\StorageProviders\ServiceProvider',
	'WPMedia\BackWPup\Admin\ServiceProvider',
	'WPMedia\BackWPup\Jobs\ServiceProvider',
	'WPMedia\BackWPup\Backups\ServiceProvider',
	'WPMedia\BackWPup\Backup\ServiceProvider',
	'WPMedia\BackWPup\Frontend\ServiceProvider',
	'WPMedia\BackWPup\Tracking\ServiceProvider',
];


return array_merge( $providers, $pro_providers );