/*
 * This file is part of the BackWPup Shared package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

window.BWU = window.BWU || {}
window.BWU.States = window.BWU.States || {};

(function (BWU) {

  var makeConstant = BWU.Functions.makeConstant

  BWU.States = Object.create({}, {
    DONE: makeConstant('done'),
    DOWNLOADING: makeConstant('downloading'),
    NEED_DECRYPTION_KEY: makeConstant('need_decryption_key'),
  })

  Object.freeze(BWU.States)
}(BWU))
