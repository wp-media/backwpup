/**
 * Restore Functions
 */

window.BWU = window.BWU || {};
window.BWU.Restore = window.BWU.Restore || {};
window.BWU.Restore.Functions = window.BWU.Restore.Functions || {};

(
	function iife( BWU, $ ) {
		'use strict';

		BWU.Restore.Functions = {

			/**
			 * Load Site
			 *
			 * @param {number} id The step to point to.
			 * @param {string} nonce The value for the nonce.
			 */
			loadNextStep: function ( id, nonce ) {
				var search = 'step=' + id;
				location.replace(
					location.origin + location.pathname + '?' + search + '&page=backwpuprestore&backwpup_action_nonce=' + nonce );
			},

			/**
			 * Calculate Percentage
			 *
			 * @param {number} index The current index of the file extracted.
			 * @param {number} total The total count of files to extract.
			 *
			 * @returns {number} The percentage value
			 */
			calculatePercentage: function ( index, total ) {
				var value = index / total;

				return Math.round( value * 100 );
			}
		};
	}( window.BWU, window.jQuery )
);
