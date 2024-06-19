<?php

use BackWPup\ThirdParty\Autoptimize;
use BackWPup\ThirdParty\Breeze;
use BackWPup\ThirdParty\HummingbirdPerformance;
use BackWPup\ThirdParty\SGCachepress;
use BackWPup\ThirdParty\W3TotalCache;
use BackWPup\ThirdParty\WPOptimize;
use BackWPup\ThirdParty\WPRocket;
use BackWPup\ThirdParty\WPSuperCache;
use BackWPup\ThirdParty\WPFastestCache;

class BackWPup_ThirdParties {

	/**
	 * Register all the third parties
	 *
	 * @return void
	 */
	public static function register() {
		$autoptimize = new Autoptimize();
		$autoptimize->init();
		$breeze = new Breeze();
		$breeze->init();
		$hummingbird = new HummingbirdPerformance();
		$hummingbird->init();
		$sg_cachepress = new SGCachepress();
		$sg_cachepress->init();
		$w3_total_cache = new W3TotalCache();
		$w3_total_cache->init();
		$wp_optimize = new WPOptimize();
		$wp_optimize->init();
		$wp_rocket = new WPRocket();
		$wp_rocket->init();
		$wp_super_cache = new WPSuperCache();
		$wp_super_cache->init();
		$wp_fastest_cache = new WPFastestCache();
		$wp_fastest_cache->init();
	}
}
