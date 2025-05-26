<?php

namespace WPMedia\BackWPup\API;

interface Rest {

	public const ROUTE_NAMESPACE = 'backwpup/v1';

	public const ROUTE_V2_NAMESPACE = 'backwpup/v2';

	/**
	 * Register REST API routes.
	 *
	 * @return void
	 */
	public function register_routes(): void;

	/**
	 * Check if the user has permission to access the route.
	 *
	 * @return bool
	 */
	public function has_permission(): bool;
}
