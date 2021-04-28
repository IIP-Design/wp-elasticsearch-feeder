/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./admin/js/settings.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./admin/js/settings.js":
/*!******************************!*\
  !*** ./admin/js/settings.js ***!
  \******************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _utils_document_ready__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./utils/document-ready */ "./admin/js/utils/document-ready.js");
/* harmony import */ var _utils_settings_event_listeners__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./utils/settings-event-listeners */ "./admin/js/utils/settings-event-listeners.js");


/**
 * Set up the page event listeners once the page is loaded.
 */

Object(_utils_document_ready__WEBPACK_IMPORTED_MODULE_0__["ready"])(function () {
  Object(_utils_settings_event_listeners__WEBPACK_IMPORTED_MODULE_1__["settingsEventListeners"])();
});

/***/ }),

/***/ "./admin/js/utils/ajax.js":
/*!********************************!*\
  !*** ./admin/js/utils/ajax.js ***!
  \********************************/
/*! exports provided: sendAjax */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "sendAjax", function() { return sendAjax; });
var sendAjax = function sendAjax(data, method, successFunc, errorFunc) {
  var _window = window,
      ajaxurl = _window.ajaxurl;
  fetch(ajaxurl, {
    method: method,
    body: data
  }).then(function (response) {
    return response.json();
  }).then(function (result) {
    successFunc(result);
  }).catch(function (err) {
    errorFunc(err);
  });
};

/***/ }),

/***/ "./admin/js/utils/document-ready.js":
/*!******************************************!*\
  !*** ./admin/js/utils/document-ready.js ***!
  \******************************************/
/*! exports provided: ready */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ready", function() { return ready; });
/**
 * Check if the document is ready and then run a function.
 *
 * @param {function} callback The function to be run when the DOM is loaded.
 */
var ready = function ready(callback) {
  if (document.readyState !== 'loading') {
    return callback();
  }

  document.addEventListener('DOMContentLoaded', callback);
};

/***/ }),

/***/ "./admin/js/utils/i18n.js":
/*!********************************!*\
  !*** ./admin/js/utils/i18n.js ***!
  \********************************/
/*! exports provided: i18nize */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "i18nize", function() { return i18nize; });
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);

/**
 * Wraps the provided text with the WordPress internationalization function.
 *
 * @param {string} string   A string to translate.
 * @returns {string}        Translated text.
 */

var i18nize = function i18nize(string) {
  if (typeof string !== 'string') {
    return string;
  }

  return Object(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__["__"])(string, 'gpalab-feeder');
};

/***/ }),

/***/ "./admin/js/utils/log.js":
/*!*******************************!*\
  !*** ./admin/js/utils/log.js ***!
  \*******************************/
/*! exports provided: clearLogs, reloadLog */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "clearLogs", function() { return clearLogs; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "reloadLog", function() { return reloadLog; });
/* harmony import */ var _i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./i18n */ "./admin/js/utils/i18n.js");
/* harmony import */ var _ajax__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ajax */ "./admin/js/utils/ajax.js");


/**
 * Retrieve the security nonce used to authenticate AJAX requests.
 *
 * @returns {string} The localized nonce.
 */

var getNonce = function getNonce() {
  var _window, _window$gpalabFeederS;

  return (_window = window) === null || _window === void 0 ? void 0 : (_window$gpalabFeederS = _window.gpalabFeederSettings) === null || _window$gpalabFeederS === void 0 ? void 0 : _window$gpalabFeederS.nonce;
};
/**
 * Clear the text of the log textarea.
 */


var emptyLog = function emptyLog() {
  var logText = document.getElementById('log-text'); // Recursively remove child elements.

  while (logText.firstChild) {
    logText.removeChild(logText.firstChild);
  }
};
/**
 * Clears the log textarea.
 */


var clearLogs = function clearLogs() {
  // Generate request body as formData.
  var formData = new FormData();
  formData.append('action', 'gpalab_feeder_clear_logs');
  formData.append('security', getNonce()); // Clear the log textarea.

  var successFunc = function successFunc() {
    emptyLog();
    alert(Object(_i18n__WEBPACK_IMPORTED_MODULE_0__["i18nize"])('Logs cleared.'));
  }; // Report errors.


  var errorFunc = function errorFunc(err) {
    console.error(err);
    alert(Object(_i18n__WEBPACK_IMPORTED_MODULE_0__["i18nize"])('Communication error while truncating logs.'));
  }; // Send request.


  Object(_ajax__WEBPACK_IMPORTED_MODULE_1__["sendAjax"])(formData, 'POST', successFunc, errorFunc);
};
/**
 * Loads the last 100 lines of callback log.
 */

var reloadLog = function reloadLog() {
  // Clear the log before proceeding.
  emptyLog(); // Generate request body as formData.

  var formData = new FormData();
  formData.append('action', 'gpalab_feeder_reload_log');
  formData.append('security', getNonce()); // Write response to the log textarea.

  var successFunc = function successFunc(result) {
    var logText = document.getElementById('log-text');
    logText.textContent = result;
  }; // Report errors.


  var errorFunc = function errorFunc(err) {
    console.error(err);
    alert(Object(_i18n__WEBPACK_IMPORTED_MODULE_0__["i18nize"])('Communication error while reloading log.'));
  }; // Send request.


  Object(_ajax__WEBPACK_IMPORTED_MODULE_1__["sendAjax"])(formData, 'POST', successFunc, errorFunc);
};

/***/ }),

/***/ "./admin/js/utils/settings-event-listeners.js":
/*!****************************************************!*\
  !*** ./admin/js/utils/settings-event-listeners.js ***!
  \****************************************************/
/*! exports provided: settingsEventListeners */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "settingsEventListeners", function() { return settingsEventListeners; });
/* harmony import */ var _log__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./log */ "./admin/js/utils/log.js");

/**
 * Adds event listeners required to run the settings page tabbed container.
 */

var settingsEventListeners = function settingsEventListeners() {
  // Manage section buttons.
  // const testBtn = document.getElementById( 'gpalab-feeder-test-connection' );
  // const queryIndexBtn = document.getElementById( 'gpalab-feeder-query-index' );
  // const errorsBtn = document.getElementById( 'gpalab-feeder-resync-errors' );
  // const validateBtn = document.getElementById( 'gpalab-feeder-validate-sync' );
  // const controlBtn = document.getElementById( 'gpalab-feeder-resync-control' );
  // const resyncBtn = document.getElementById( 'gpalab-feeder-resync' );
  // testBtn.addEventListener( 'click', testConnection() );
  // queryIndexBtn.addEventListener( 'click', queryIndex() );
  // errorsBtn.addEventListener( 'click', resyncStart( 0 ) );
  // validateBtn.addEventListener( 'click', resyncStart( 1 ) );
  // controlBtn.addEventListener( 'click', resyncControl() );
  // resyncBtn.addEventListener( 'click', validateSync() );
  // Log section buttons.
  var clearBtn = document.getElementById('gpalab-feeder-clear-logs');
  var reloadBtn = document.getElementById('gpalab-feeder-reload-log');
  clearBtn.addEventListener('click', _log__WEBPACK_IMPORTED_MODULE_0__["clearLogs"]);
  reloadBtn.addEventListener('click', _log__WEBPACK_IMPORTED_MODULE_0__["reloadLog"]);
};

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/*! no static exports found */
/***/ (function(module, exports) {

(function() { module.exports = window["wp"]["i18n"]; }());

/***/ })

/******/ });
//# sourceMappingURL=gpalab-feeder-settings.js.map