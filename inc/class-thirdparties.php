<?php
class BackWPup_ThirdParties {

	/**
	 * Register all the third parties depending on what plugin is active
	 * @return void
	 */
    public static function register() {
        if (self::is_wp_rocket_active()) {
            add_filter("backwpup_exclusion_plugins_folders", [self::class, "exclude_wp_rocket_plugins_folders"]);
            add_filter("backwpup_exclusion_plugins_cache_folders", [self::class, "exclude_wp_rocket_plugins_cache_folders"]);
        }
		if (self::is_hummingbird_performance_active()) {
			add_filter("backwpup_exclusion_plugins_folders", [self::class, "exclude_hummingbird_performance_plugins_folders"]);
			add_filter("backwpup_exclusion_plugins_cache_folders", [self::class, "exclude_hummingbird_performance_plugins_cache_folders"]);
		}
		if (self::is_w3_total_cache_active()) {
			add_filter("backwpup_exclusion_plugins_folders", [self::class, "exclude_w3_total_cache_plugins_folders"]);
			add_filter("backwpup_exclusion_plugins_cache_folders", [self::class, "exclude_w3_total_cache_plugins_cache_folders"]);
		}
		if (self::is_wp_super_cache_active()) {
			add_filter("backwpup_exclusion_plugins_folders", [self::class, "exclude_wp_super_cache_plugins_folders"]);
			add_filter("backwpup_exclusion_plugins_cache_folders", [self::class, "exclude_wp_super_cache_plugins_cache_folders"]);
		}
		if (self::is_breeze_active()) {
			add_filter("backwpup_exclusion_plugins_folders", [self::class, "exclude_breeze_plugins_folders"]);
			add_filter("backwpup_exclusion_plugins_cache_folders", [self::class, "exclude_breeze_plugins_cache_folders"]);
		}
		if (self::is_autoptimize_active()) {
			add_filter("backwpup_exclusion_plugins_folders", [self::class, "exclude_autoptimize_plugins_folders"]);
			add_filter("backwpup_exclusion_plugins_cache_folders", [self::class, "exclude_autoptimize_plugins_cache_folders"]);
		}
		if (self::is_wp_optimize_active()) {
			add_filter("backwpup_exclusion_plugins_folders", [self::class, "exclude_wp_optimize_plugins_folders"]);
			add_filter("backwpup_exclusion_plugins_cache_folders", [self::class, "exclude_wp_optimize_plugins_cache_folders"]);
		}
		if (self::is_sg_cachepress_active()) {
			add_filter("backwpup_exclusion_plugins_folders", [self::class, "exclude_sg_cachepress_plugins_folders"]);
			add_filter("backwpup_exclusion_plugins_cache_folders", [self::class, "exclude_sg_cachepress_plugins_cache_folders"]);
		}

    }

    /**
     * Add the wp-rocket plugin folders to the $excluded_folders if wp-rocket plugin is active
     * @param array $excluded_folders
     * @return array
     */
    public static function exclude_wp_rocket_plugins_folders($excluded_folders) {
        if (
            !is_array($excluded_folders) ||
            !self::is_wp_rocket_active() ||
            in_array(WP_ROCKET_PATH, $excluded_folders)
        ) {
            return $excluded_folders;
        }
        $excluded_folders[] = WP_ROCKET_PATH;
        return $excluded_folders;
    }

	/**
	 * Add the wp-rocket cache folders to the $excluded_folders if wp-rocket plugin is active
	 * @param array $excluded_folders
	 *
	 * @return array
	 */
	public static function exclude_wp_rocket_plugins_cache_folders($excluded_folders) {
		if (
			!is_array($excluded_folders) ||
			!self::is_wp_rocket_active() ||
			in_array(WP_ROCKET_CACHE_ROOT_PATH, $excluded_folders)
		) {
			return $excluded_folders;
		}
		$excluded_folders[] = WP_ROCKET_CACHE_ROOT_PATH;
		return $excluded_folders;
	}

	/**
     * Add the hummingbird-performance plugin folders to the $excluded_folders if hummingbird-performance plugin is active
     * @param array $excluded_folders
     * @return array
     */
    public static function exclude_hummingbird_performance_plugins_folders($excluded_folders) {
        if (
            !is_array($excluded_folders) ||
            !self::is_hummingbird_performance_active() ||
            in_array(WPHB_DIR_PATH, $excluded_folders)
        ) {
            return $excluded_folders;
        }
        $excluded_folders[] = WPHB_DIR_PATH;
        return $excluded_folders;
    }

	/**
	 * Add the hummingbird-performance cache folders to the $excluded_folders if hummingbird-performance plugin is active
	 * @param array $excluded_folders
	 *
	 * @return array
	 */
	public static function exclude_hummingbird_performance_plugins_cache_folders($excluded_folders) {
		if (
			!is_array($excluded_folders) ||
			!self::is_hummingbird_performance_active()
		) {
			return $excluded_folders;
		}
		$HummingBirdFilesystem = \Hummingbird\Core\Filesystem::instance();
		if (!in_array($HummingBirdFilesystem->cache_dir, $excluded_folders)) {
			$excluded_folders[] = $HummingBirdFilesystem->cache_dir;
		}
		if (!in_array($HummingBirdFilesystem->gravatar_dir, $excluded_folders)) {
			$excluded_folders[] = $HummingBirdFilesystem->gravatar_dir;
		}

		return $excluded_folders;
	}

	/**
     * Add the w3-total-cache plugin folders to the $excluded_folders if w3-total-cache plugin is active
     * @param array $excluded_folders
     * @return array
     */
    public static function exclude_w3_total_cache_plugins_folders($excluded_folders) {
        if (
            !is_array($excluded_folders) ||
            !self::is_w3_total_cache_active() ||
            in_array(W3TC_DIR, $excluded_folders)
        ) {
            return $excluded_folders;
        }
        $excluded_folders[] = W3TC_DIR;
        return $excluded_folders;
    }

	/**
	 * Add the w3-total-cache cache folders to the $excluded_folders if w3-total-cache plugin is active
	 * @param array $excluded_folders
	 *
	 * @return array
	 */
	public static function exclude_w3_total_cache_plugins_cache_folders($excluded_folders) {
		if (
			!is_array($excluded_folders) ||
			!self::is_w3_total_cache_active() ||
			in_array(W3TC_CACHE_DIR, $excluded_folders)
		) {
			return $excluded_folders;
		}
		$excluded_folders[] = W3TC_CACHE_DIR;
		return $excluded_folders;
	}

	/**
     * Add the wp-super-cache plugin folders to the $excluded_folders if wp-super-cache plugin is active
     * @param array $excluded_folders
     * @return array
     */
    public static function exclude_wp_super_cache_plugins_folders($excluded_folders) {
        if (
            !is_array($excluded_folders) ||
            !self::is_wp_super_cache_active() ||
            in_array(WPCACHEHOME, $excluded_folders)
        ) {
            return $excluded_folders;
        }
        $excluded_folders[] = WPCACHEHOME;
        return $excluded_folders;
    }

	/**
	 * Add the wp-super-cache cache folders to the $excluded_folders if wp-super-cache plugin is active
	 * @param array $excluded_folders
	 *
	 * @return array
	 */
	public static function exclude_wp_super_cache_plugins_cache_folders($excluded_folders) {
		if (
			!is_array($excluded_folders) ||
			!self::is_wp_super_cache_active()
		) {
			return $excluded_folders;
		}
		global $cache_path;
		if (!in_array($cache_path, $excluded_folders)) {
			$excluded_folders[] = $cache_path;
		}
		return $excluded_folders;
	}

	/**
     * Add the breeze plugin folders to the $excluded_folders if breeze plugin is active
     * @param array $excluded_folders
     * @return array
     */
    public static function exclude_breeze_plugins_folders($excluded_folders) {
        if (
            !is_array($excluded_folders) ||
            !self::is_breeze_active() ||
            in_array(BREEZE_PLUGIN_DIR, $excluded_folders)
        ) {
            return $excluded_folders;
        }
        $excluded_folders[] = BREEZE_PLUGIN_DIR;
        return $excluded_folders;
    }

	/**
	 * Add the breeze cache folders to the $excluded_folders if breeze plugin is active
	 * @param array $excluded_folders
	 *
	 * @return array
	 */
	public static function exclude_breeze_plugins_cache_folders($excluded_folders) {
		if (
			!is_array($excluded_folders) ||
			!self::is_breeze_active()
		) {
			return $excluded_folders;
		}
		if (!in_array(BREEZE_MINIFICATION_CACHE, $excluded_folders)) {
			$excluded_folders[] = BREEZE_MINIFICATION_CACHE;
		}
		if (!in_array(BREEZE_MINIFICATION_EXTRA, $excluded_folders)) {
			$excluded_folders[] = BREEZE_MINIFICATION_EXTRA;
		}
		return $excluded_folders;
	}

	/**
     * Add the autoptimize plugin folders to the $excluded_folders if autoptimize plugin is active
     * @param array $excluded_folders
     * @return array
     */
    public static function exclude_autoptimize_plugins_folders($excluded_folders) {
        if (
            !is_array($excluded_folders) ||
            !self::is_autoptimize_active() ||
            in_array(AUTOPTIMIZE_PLUGIN_DIR, $excluded_folders)
        ) {
            return $excluded_folders;
        }
        $excluded_folders[] = AUTOPTIMIZE_PLUGIN_DIR;
        return $excluded_folders;
    }

	/**
	 * Add the autoptimize cache folders to the $excluded_folders if autoptimize plugin is active
	 * @param array $excluded_folders
	 *
	 * @return array
	 */
	public static function exclude_autoptimize_plugins_cache_folders($excluded_folders) {
		if (
			!is_array($excluded_folders) ||
			!self::is_autoptimize_active() ||
			in_array(AUTOPTIMIZE_CACHE_DIR, $excluded_folders)
		) {
			return $excluded_folders;
		}
		$excluded_folders[] = AUTOPTIMIZE_CACHE_DIR;
		return $excluded_folders;
	}

	/**
     * Add the wp-optimize plugin folders to the $excluded_folders if wp-optimize plugin is active
     * @param array $excluded_folders
     * @return array
     */
    public static function exclude_wp_optimize_plugins_folders($excluded_folders) {
        if (
            !is_array($excluded_folders) ||
            !self::is_wp_optimize_active() ||
            in_array(WPO_PLUGIN_MAIN_PATH, $excluded_folders)
        ) {
            return $excluded_folders;
        }
        $excluded_folders[] = WPO_PLUGIN_MAIN_PATH;
        return $excluded_folders;
    }

	/**
	 * Add the wp-optimize cache folders to the $excluded_folders if wp-optimize plugin is active
	 * @param array $excluded_folders
	 *
	 * @return array
	 */
	public static function exclude_wp_optimize_plugins_cache_folders($excluded_folders) {
		if (
			!is_array($excluded_folders) ||
			!self::is_wp_optimize_active()
		) {
			return $excluded_folders;
		}
		if (!in_array(WPO_CACHE_FILES_DIR, $excluded_folders)) {
			$excluded_folders[] = WPO_CACHE_FILES_DIR;
		}
		if (!in_array(WPO_CACHE_MIN_FILES_DIR, $excluded_folders)) {
			$excluded_folders[] = WPO_CACHE_MIN_FILES_DIR;
		}
		return $excluded_folders;
	}

	/**
     * Add the sg-cachepress plugin folders to the $excluded_folders if sg-cachepress plugin is active
     * @param array $excluded_folders
     * @return array
     */
    public static function exclude_sg_cachepress_plugins_folders($excluded_folders) {
        if (
            !is_array($excluded_folders) ||
            !self::is_sg_cachepress_active() ||
            in_array(SiteGround_Optimizer\DIR, $excluded_folders)
        ) {
            return $excluded_folders;
        }
        $excluded_folders[] = SiteGround_Optimizer\DIR;
        return $excluded_folders;
    }

	/**
	 * Add the sg-cachepress cache folders to the $excluded_folders if sg-cachepress plugin is active
	 * @param array $excluded_folders
	 *
	 * @return array
	 */
	public static function exclude_sg_cachepress_plugins_cache_folders($excluded_folders) {
		if (
			!is_array($excluded_folders) ||
			!self::is_sg_cachepress_active()
		) {
			return $excluded_folders;
		}
		$file_cacher = new \SiteGround_Optimizer\File_Cacher\File_Cacher();
		if (!in_array($file_cacher->get_cache_dir(), $excluded_folders)) {
			$excluded_folders[] = $file_cacher->get_cache_dir();
		}
		return $excluded_folders;
	}



	/**
	 * Tells if wp-rocket plugin is active
	 * @return bool
	 */
    protected static function is_wp_rocket_active():bool {
        return defined( 'WP_ROCKET_VERSION' );
    }

	/**
	 * Tells if hummingbird-performance plugin is active
	 * @return bool
	 */
	protected static function is_hummingbird_performance_active():bool {
		return defined( 'WPHB_VERSION' );
	}

	/**
	 * Tells if w3-total-cache plugin is active
	 * @return bool
	 */
	protected static function is_w3_total_cache_active():bool {
		return defined( 'W3TC' );
	}

	/**
	 * Tells if wp-super-cache plugin is active
	 * @return bool
	 */
	protected static function is_wp_super_cache_active():bool {
		return defined( 'WPSC_VERSION' );
	}

	/**
	 * Tells if breeze plugin is active
	 * @return bool
	 */
	protected static function is_breeze_active():bool {
		return defined( 'BREEZE_VERSION' );
	}

	/**
	 * Tells if autoptimize plugin is active
	 * @return bool
	 */
	protected static function is_autoptimize_active():bool {
		return defined( 'AUTOPTIMIZE_PLUGIN_VERSION' );
	}

	/**
	 * Tells if wp-optimize plugin is active
	 * @return bool
	 */
	protected static function is_wp_optimize_active():bool {
		return defined( 'WPO_VERSION' );
	}

	/**
	 * Tells if sg-cachepress plugin is active
	 * @return bool
	 */
	protected static function is_sg_cachepress_active():bool {
		return defined( 'SiteGround_Optimizer\VERSION' );
	}
}