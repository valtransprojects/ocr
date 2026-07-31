/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./webpack/plugins/custom/ckeditor/ckeditor-config.js"
/*!************************************************************!*\
  !*** ./webpack/plugins/custom/ckeditor/ckeditor-config.js ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

eval("{__webpack_require__.r(__webpack_exports__);\n/* harmony export */ __webpack_require__.d(__webpack_exports__, {\n/* harmony export */   LICENSE_KEY: () => (/* binding */ LICENSE_KEY),\n/* harmony export */   normalizeConfig: () => (/* binding */ normalizeConfig)\n/* harmony export */ });\n/**\n * Shared CKEditor 5 config for new ckeditor5 package (v47+).\n * Normalizes legacy toolbar.items and adds licenseKey.\n */\nconst LICENSE_KEY = 'GPL';\n\n/**\n * Normalize create() config: toolbar.items -> toolbar array, add licenseKey if missing.\n * @param {object} config - Config passed from page (e.g. toolbar: { items: [...] })\n * @returns {object} Config suitable for ckeditor5 .create()\n */\nfunction normalizeConfig(config) {\n    const out = { ...config, licenseKey: config.licenseKey || LICENSE_KEY };\n    // Keep toolbar as object with items for ClassicEditor; do not replace with array\n    if (out.toolbar && Array.isArray(out.toolbar)) {\n        out.toolbar = { items: out.toolbar, shouldNotGroupWhenFull: true };\n    } else if (out.toolbar && !Array.isArray(out.toolbar) && !out.toolbar.items && out.toolbar.removeItems) {\n        out.toolbar = { items: [], removeItems: out.toolbar.removeItems, shouldNotGroupWhenFull: true };\n    }\n    return out;\n}\n\n\n//# sourceURL=webpack://keenthemes/./webpack/plugins/custom/ckeditor/ckeditor-config.js?\n}");

/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = {};
/******/ 	__webpack_modules__["./webpack/plugins/custom/ckeditor/ckeditor-config.js"](0,__webpack_exports__,__webpack_require__);
/******/ 	
/******/ })()
;