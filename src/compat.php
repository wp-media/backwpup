<?php
defined( 'ABSPATH' ) || exit;

// Make sure CAL_GREGORIAN defined.
if ( ! defined( 'CAL_GREGORIAN' ) ) {
	define( 'CAL_GREGORIAN', 1 );
}

// Sometimes we will not find this function because calendar extension is not enabled, rare case but this happens.
if ( ! function_exists( 'cal_days_in_month' ) ) {

	/**
	 * Compatibility code for cal_days_in_month.
	 *
	 * @param int $calendar Only accepts GREGORIAN.
	 * @param int $month Month.
	 * @param int $year Year.
	 *
	 * @return int
	 */
	function cal_days_in_month( $calendar, $month, $year ) {
		return backwpup_cal_days_in_month( $month, $year );
	}
}
