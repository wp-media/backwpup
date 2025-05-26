<?php

declare(strict_types=1);

namespace WPMedia\BackWPup\Adapters;

/**
 * Class CronAdapter
 *
 * Adapter for BackWPup_Cron static methods.
 */
class CronAdapter {
	/**
	 * Get the next run time for a cron expression.
	 *
	 * @param string $cron_expression The cron expression.
	 *
	 * @return int
	 */
	public function cron_next( string $cron_expression ): int {
		return \BackWPup_Cron::cron_next( $cron_expression );
	}

	/**
	 * Get the basic cron expression.
	 *
	 * @param string     $basic_expression Basic expression.
	 * @param int|string $hours Hours of the cron.
	 * @param int        $minutes Minutes of the cron.
	 * @param int        $day_of_week Day of the week default = 0.
	 * @param string     $day_of_month Day of the month.
	 *
	 * @return string Cron expression
	 * @throws InvalidArgumentException If the cron expression is unsupported.
	 */
	public function get_basic_cron_expression( string $basic_expression, $hours = 0, int $minutes = 0, int $day_of_week = 0, string $day_of_month = '' ): string {
		return \BackWPup_Cron::get_basic_cron_expression( $basic_expression, $hours, $minutes, $day_of_week, $day_of_month );
	}
}
