/*
 * This file is part of the BackWPup Shared package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

window.BWU = window.BWU || {}
window.BWU.Functions = window.BWU.Functions || {};

(
  function iife (BWU, $) {
    'use strict'

    BWU.Functions = {

      /**
       * Remove previously printed messages
       */
      removeMessages: function () {
        $(document.body).find('#bwu_response').remove()
      },

      /**
       * Print Error Messages
       *
       * @param {string} message The message to print.
       *
       * @return {void}
       */
      printMessageError: function (message, container) {
        var $container = $(container)

        if (!message) {
          return
        }

        this.removeMessages()

        $container.append(
          '<p id="bwu_response" class="response response-error">' + message +
          '</p>')
      },

      /**
       * Print Success Messages
       *
       * @param {string} message The message to print.
       *
       * @return {void}
       */
      printMessageSuccess: function (message, container) {
        var $container = $(container)

        if (!message) {
          return
        }

        this.removeMessages()

        $container.append(
          '<p id="bwu_response" class="response response-success">' + message +
          '</p>')
      },

      /**
       * Create a constant property
       * @param value
       * @returns {{value: *, writable: boolean, configurable: boolean, enumerable: boolean}}
       */
      makeConstant: function (value) {
        return {
          value: value,
          writable: false,
          configurable: false,
          enumerable: false,
        }
      },
    }

    Object.freeze(BWU.Functions)
  }(window.BWU, window.jQuery)
)
