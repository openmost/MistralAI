(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory(require("CoreHome"), require("vue"));
	else if(typeof define === 'function' && define.amd)
		define(["CoreHome", ], factory);
	else if(typeof exports === 'object')
		exports["MistralAI"] = factory(require("CoreHome"), require("vue"));
	else
		root["MistralAI"] = factory(root["CoreHome"], root["Vue"]);
})((typeof self !== 'undefined' ? self : this), function(__WEBPACK_EXTERNAL_MODULE__19dc__, __WEBPACK_EXTERNAL_MODULE__8bbf__) {
return /******/ (function(modules) { // webpackBootstrap
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
/******/ 	__webpack_require__.p = "plugins/MistralAI/vue/dist/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "fae3");
/******/ })
/************************************************************************/
/******/ ({

/***/ "02ab":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatLoading_vue_vue_type_style_index_0_id_b7031210_lang_less__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("ea63");
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatLoading_vue_vue_type_style_index_0_id_b7031210_lang_less__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatLoading_vue_vue_type_style_index_0_id_b7031210_lang_less__WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),

/***/ "19dc":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__19dc__;

/***/ }),

/***/ "1cc4":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatMessagesList_vue_vue_type_style_index_0_id_34902d64_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("3c87");
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatMessagesList_vue_vue_type_style_index_0_id_34902d64_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatMessagesList_vue_vue_type_style_index_0_id_34902d64_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),

/***/ "3c87":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "3f00":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "4904":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatMessage_vue_vue_type_style_index_0_id_5598fdea_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("3f00");
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatMessage_vue_vue_type_style_index_0_id_5598fdea_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatMessage_vue_vue_type_style_index_0_id_5598fdea_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),

/***/ "8bbf":
/***/ (function(module, exports) {

module.exports = __WEBPACK_EXTERNAL_MODULE__8bbf__;

/***/ }),

/***/ "921c":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_Chat_vue_vue_type_style_index_0_id_17bc489c_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("a6c1");
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_Chat_vue_vue_type_style_index_0_id_17bc489c_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_Chat_vue_vue_type_style_index_0_id_17bc489c_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),

/***/ "a6c1":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "b30d":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "cecd":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "cfde":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatForm_vue_vue_type_style_index_0_id_406ca338_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("cecd");
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatForm_vue_vue_type_style_index_0_id_406ca338_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatForm_vue_vue_type_style_index_0_id_406ca338_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),

/***/ "ea63":
/***/ (function(module, exports, __webpack_require__) {

// extracted by mini-css-extract-plugin

/***/ }),

/***/ "f092":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatIndexPage_vue_vue_type_style_index_0_id_dfb64452_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__("b30d");
/* harmony import */ var _node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatIndexPage_vue_vue_type_style_index_0_id_dfb64452_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_cli_service_node_modules_mini_css_extract_plugin_dist_loader_js_ref_10_oneOf_1_0_node_modules_vue_cli_service_node_modules_css_loader_dist_cjs_js_ref_10_oneOf_1_1_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_stylePostLoader_js_node_modules_postcss_loader_src_index_js_ref_10_oneOf_1_2_node_modules_less_loader_dist_cjs_js_ref_10_oneOf_1_3_node_modules_vue_cli_service_node_modules_cache_loader_dist_cjs_js_ref_0_0_node_modules_vue_cli_service_node_modules_vue_loader_v16_dist_index_js_ref_0_1_ChatIndexPage_vue_vue_type_style_index_0_id_dfb64452_lang_less_scoped_true__WEBPACK_IMPORTED_MODULE_0__);
/* unused harmony reexport * */


/***/ }),

/***/ "fae3":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
// ESM COMPAT FLAG
__webpack_require__.r(__webpack_exports__);

// EXPORTS
__webpack_require__.d(__webpack_exports__, "ChatIndexPage", function() { return /* reexport */ ChatIndexPage; });
__webpack_require__.d(__webpack_exports__, "Chat", function() { return /* reexport */ Chat; });
__webpack_require__.d(__webpack_exports__, "ChatForm", function() { return /* reexport */ ChatForm; });
__webpack_require__.d(__webpack_exports__, "ChatLoading", function() { return /* reexport */ ChatLoading; });
__webpack_require__.d(__webpack_exports__, "ChatMessage", function() { return /* reexport */ ChatMessage; });
__webpack_require__.d(__webpack_exports__, "ChatMessagesList", function() { return /* reexport */ ChatMessagesList; });

// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/setPublicPath.js
// This file is imported into lib/wc client bundles.

if (typeof window !== 'undefined') {
  var currentScript = window.document.currentScript
  if (false) { var getCurrentScript; }

  var src = currentScript && currentScript.src.match(/(.+\/)[^/]+\.js(\?.*)?$/)
  if (src) {
    __webpack_require__.p = src[1] // eslint-disable-line
  }
}

// Indicate to webpack that this file can be concatenated
/* harmony default export */ var setPublicPath = (null);

// EXTERNAL MODULE: external {"commonjs":"vue","commonjs2":"vue","root":"Vue"}
var external_commonjs_vue_commonjs2_vue_root_Vue_ = __webpack_require__("8bbf");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Pages/ChatIndexPage.vue?vue&type=template&id=dfb64452&scoped=true


Object(external_commonjs_vue_commonjs2_vue_root_Vue_["pushScopeId"])("data-v-dfb64452");

var _hoisted_1 = {
  class: "ai-chat-page-wrapper"
};

Object(external_commonjs_vue_commonjs2_vue_root_Vue_["popScopeId"])();

function render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_Chat = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Chat");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", _hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_Chat)]);
}
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Pages/ChatIndexPage.vue?vue&type=template&id=dfb64452&scoped=true

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Components/Chat.vue?vue&type=template&id=17bc489c&scoped=true


Object(external_commonjs_vue_commonjs2_vue_root_Vue_["pushScopeId"])("data-v-17bc489c");

var Chatvue_type_template_id_17bc489c_scoped_true_hoisted_1 = {
  class: "ai-chat-interface-wrapper"
};

Object(external_commonjs_vue_commonjs2_vue_root_Vue_["popScopeId"])();

function Chatvue_type_template_id_17bc489c_scoped_true_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_ChatMessagesList = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ChatMessagesList");

  var _component_ChatForm = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ChatForm");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", Chatvue_type_template_id_17bc489c_scoped_true_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ChatMessagesList, {
    loading: _ctx.loading,
    errored: _ctx.errored,
    messages: _ctx.messages
  }, null, 8, ["loading", "errored", "messages"]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createVNode"])(_component_ChatForm, {
    onFormSubmit: _ctx.onFormSubmit,
    onSuccess: _ctx.onSuccess,
    onError: _ctx.onError
  }, null, 8, ["onFormSubmit", "onSuccess", "onError"])]);
}
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/Chat.vue?vue&type=template&id=17bc489c&scoped=true

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Components/ChatForm.vue?vue&type=template&id=406ca338&scoped=true


Object(external_commonjs_vue_commonjs2_vue_root_Vue_["pushScopeId"])("data-v-406ca338");

var ChatFormvue_type_template_id_406ca338_scoped_true_hoisted_1 = {
  class: "ai-chat-form-wrapper"
};
var _hoisted_2 = {
  class: "ai-chat-form-group"
};
var _hoisted_3 = ["disabled"];

Object(external_commonjs_vue_commonjs2_vue_root_Vue_["popScopeId"])();

function ChatFormvue_type_template_id_406ca338_scoped_true_render(_ctx, _cache, $props, $setup, $data, $options) {
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("div", ChatFormvue_type_template_id_406ca338_scoped_true_hoisted_1, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("form", {
    class: "ai-chat-form",
    onSubmit: _cache[1] || (_cache[1] = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withModifiers"])(function () {
      return _ctx.onFormSubmit && _ctx.onFormSubmit.apply(_ctx, arguments);
    }, ["prevent"]))
  }, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withDirectives"])(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("input", {
    "onUpdate:modelValue": _cache[0] || (_cache[0] = function ($event) {
      return _ctx.prompt = $event;
    }),
    type: "text",
    name: "mistral-prompt",
    id: "ai-chat-prompt",
    placeholder: "Message Mistral AI...",
    "aria-label": "Mistral AI Prompt",
    minlength: "1",
    required: "",
    autofocus: ""
  }, null, 512), [[external_commonjs_vue_commonjs2_vue_root_Vue_["vModelText"], _ctx.prompt]]), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("button", {
    type: "submit",
    class: "btn",
    id: "ai-chat-submit-button",
    disabled: _ctx.loading
  }, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.loading ? 'Loading' : 'Submit'), 9, _hoisted_3)])], 32)]);
}
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatForm.vue?vue&type=template&id=406ca338&scoped=true

// EXTERNAL MODULE: external "CoreHome"
var external_CoreHome_ = __webpack_require__("19dc");

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Components/ChatForm.vue?vue&type=script&lang=ts


/* harmony default export */ var ChatFormvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  data: function data() {
    return {
      prompt: '',
      loading: false
    };
  },
  methods: {
    onFormSubmit: function onFormSubmit() {
      var _this = this;

      this.$emit('formSubmit', this.prompt);
      this.loading = true;
      var promptData = this.prompt;
      this.prompt = '';
      external_CoreHome_["AjaxHelper"].fetch({
        method: 'MistralAI.getResponse',
        prompt: promptData
      }).then(function (response) {
        _this.$emit('success', response.choices[0].message.content);
      }).catch(function (error) {
        return _this.$emit('error', error);
      }).finally(function () {
        _this.loading = false;
      });
    }
  }
}));
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatForm.vue?vue&type=script&lang=ts
 
// EXTERNAL MODULE: ./plugins/MistralAI/vue/src/Components/ChatForm.vue?vue&type=style&index=0&id=406ca338&lang=less&scoped=true
var ChatFormvue_type_style_index_0_id_406ca338_lang_less_scoped_true = __webpack_require__("cfde");

// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatForm.vue





ChatFormvue_type_script_lang_ts.render = ChatFormvue_type_template_id_406ca338_scoped_true_render
ChatFormvue_type_script_lang_ts.__scopeId = "data-v-406ca338"

/* harmony default export */ var ChatForm = (ChatFormvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Components/ChatMessagesList.vue?vue&type=template&id=34902d64&scoped=true


Object(external_commonjs_vue_commonjs2_vue_root_Vue_["pushScopeId"])("data-v-34902d64");

var ChatMessagesListvue_type_template_id_34902d64_scoped_true_hoisted_1 = {
  class: "ai-chat-conversation"
};

var ChatMessagesListvue_type_template_id_34902d64_scoped_true_hoisted_2 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])("Ooops, AI have encountered an error.");

Object(external_commonjs_vue_commonjs2_vue_root_Vue_["popScopeId"])();

function ChatMessagesListvue_type_template_id_34902d64_scoped_true_render(_ctx, _cache, $props, $setup, $data, $options) {
  var _component_ChatMessage = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ChatMessage");

  var _component_ChatLoading = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("ChatLoading");

  var _component_Alert = Object(external_commonjs_vue_commonjs2_vue_root_Vue_["resolveComponent"])("Alert");

  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("ul", ChatMessagesListvue_type_template_id_34902d64_scoped_true_hoisted_1, [(Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(true), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])(external_commonjs_vue_commonjs2_vue_root_Vue_["Fragment"], null, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["renderList"])(_ctx.messages, function (message, index) {
    return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_ChatMessage, {
      message: message,
      key: index
    }, null, 8, ["message"]);
  }), 128)), _ctx.loading && !_ctx.errored ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_ChatLoading, {
    key: 0
  })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true), _ctx.errored ? (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createBlock"])(_component_Alert, {
    key: 1,
    severity: "danger"
  }, {
    default: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["withCtx"])(function () {
      return [ChatMessagesListvue_type_template_id_34902d64_scoped_true_hoisted_2];
    }),
    _: 1
  })) : Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createCommentVNode"])("", true)]);
}
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatMessagesList.vue?vue&type=template&id=34902d64&scoped=true

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Components/ChatMessage.vue?vue&type=template&id=5598fdea&scoped=true


Object(external_commonjs_vue_commonjs2_vue_root_Vue_["pushScopeId"])("data-v-5598fdea");

var ChatMessagevue_type_template_id_5598fdea_scoped_true_hoisted_1 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", {
  class: "ai-chat-response-avatar"
}, null, -1);

var ChatMessagevue_type_template_id_5598fdea_scoped_true_hoisted_2 = {
  class: "ai-chat-response-content-wrapper"
};
var ChatMessagevue_type_template_id_5598fdea_scoped_true_hoisted_3 = {
  class: "ai-chat-response-username"
};
var _hoisted_4 = {
  class: "ai-chat-response-body"
};

Object(external_commonjs_vue_commonjs2_vue_root_Vue_["popScopeId"])();

function ChatMessagevue_type_template_id_5598fdea_scoped_true_render(_ctx, _cache, $props, $setup, $data, $options) {
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", {
    class: Object(external_commonjs_vue_commonjs2_vue_root_Vue_["normalizeClass"])(_ctx.chatResponseClasses)
  }, [ChatMessagevue_type_template_id_5598fdea_scoped_true_hoisted_1, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", ChatMessagevue_type_template_id_5598fdea_scoped_true_hoisted_2, [Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", ChatMessagevue_type_template_id_5598fdea_scoped_true_hoisted_3, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.message.author === 'user' ? 'You' : 'AI'), 1), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("div", _hoisted_4, Object(external_commonjs_vue_commonjs2_vue_root_Vue_["toDisplayString"])(_ctx.message.body), 1)])], 2);
}
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatMessage.vue?vue&type=template&id=5598fdea&scoped=true

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Components/ChatMessage.vue?vue&type=script&lang=ts

/* harmony default export */ var ChatMessagevue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  props: {
    message: {
      type: Object,
      required: true
    }
  },
  computed: {
    chatResponseClasses: function chatResponseClasses() {
      return ['ai-chat-response', "ai-chat-".concat(this.message.author, "-response")];
    }
  }
}));
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatMessage.vue?vue&type=script&lang=ts
 
// EXTERNAL MODULE: ./plugins/MistralAI/vue/src/Components/ChatMessage.vue?vue&type=style&index=0&id=5598fdea&lang=less&scoped=true
var ChatMessagevue_type_style_index_0_id_5598fdea_lang_less_scoped_true = __webpack_require__("4904");

// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatMessage.vue





ChatMessagevue_type_script_lang_ts.render = ChatMessagevue_type_template_id_5598fdea_scoped_true_render
ChatMessagevue_type_script_lang_ts.__scopeId = "data-v-5598fdea"

/* harmony default export */ var ChatMessage = (ChatMessagevue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-babel/node_modules/cache-loader/dist/cjs.js??ref--12-0!./node_modules/@vue/cli-plugin-babel/node_modules/thread-loader/dist/cjs.js!./node_modules/babel-loader/lib!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist/templateLoader.js??ref--6!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Components/ChatLoading.vue?vue&type=template&id=b7031210

var ChatLoadingvue_type_template_id_b7031210_hoisted_1 = {
  class: "ai-chat-loader"
};

var ChatLoadingvue_type_template_id_b7031210_hoisted_2 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementVNode"])("span", {
  class: "ai-chat-spinner"
}, null, -1);

var ChatLoadingvue_type_template_id_b7031210_hoisted_3 = /*#__PURE__*/Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createTextVNode"])(" Waiting for response... ");

var ChatLoadingvue_type_template_id_b7031210_hoisted_4 = [ChatLoadingvue_type_template_id_b7031210_hoisted_2, ChatLoadingvue_type_template_id_b7031210_hoisted_3];
function ChatLoadingvue_type_template_id_b7031210_render(_ctx, _cache, $props, $setup, $data, $options) {
  return Object(external_commonjs_vue_commonjs2_vue_root_Vue_["openBlock"])(), Object(external_commonjs_vue_commonjs2_vue_root_Vue_["createElementBlock"])("li", ChatLoadingvue_type_template_id_b7031210_hoisted_1, ChatLoadingvue_type_template_id_b7031210_hoisted_4);
}
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatLoading.vue?vue&type=template&id=b7031210

// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Components/ChatLoading.vue?vue&type=script&lang=ts&scoped=true

/* harmony default export */ var ChatLoadingvue_type_script_lang_ts_scoped_true = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({}));
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatLoading.vue?vue&type=script&lang=ts&scoped=true
 
// EXTERNAL MODULE: ./plugins/MistralAI/vue/src/Components/ChatLoading.vue?vue&type=style&index=0&id=b7031210&lang=less
var ChatLoadingvue_type_style_index_0_id_b7031210_lang_less = __webpack_require__("02ab");

// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatLoading.vue





ChatLoadingvue_type_script_lang_ts_scoped_true.render = ChatLoadingvue_type_template_id_b7031210_render

/* harmony default export */ var ChatLoading = (ChatLoadingvue_type_script_lang_ts_scoped_true);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Components/ChatMessagesList.vue?vue&type=script&lang=ts




/* harmony default export */ var ChatMessagesListvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  components: {
    Alert: external_CoreHome_["Alert"],
    ChatMessage: ChatMessage,
    ChatLoading: ChatLoading
  },
  props: {
    errored: {
      type: Boolean,
      default: false
    },
    loading: {
      type: Boolean,
      default: false
    },
    messages: {
      type: Array,
      default: function _default() {
        return [];
      }
    }
  }
}));
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatMessagesList.vue?vue&type=script&lang=ts
 
// EXTERNAL MODULE: ./plugins/MistralAI/vue/src/Components/ChatMessagesList.vue?vue&type=style&index=0&id=34902d64&lang=less&scoped=true
var ChatMessagesListvue_type_style_index_0_id_34902d64_lang_less_scoped_true = __webpack_require__("1cc4");

// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/ChatMessagesList.vue





ChatMessagesListvue_type_script_lang_ts.render = ChatMessagesListvue_type_template_id_34902d64_scoped_true_render
ChatMessagesListvue_type_script_lang_ts.__scopeId = "data-v-34902d64"

/* harmony default export */ var ChatMessagesList = (ChatMessagesListvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Components/Chat.vue?vue&type=script&lang=ts



/* harmony default export */ var Chatvue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  components: {
    ChatMessagesList: ChatMessagesList,
    ChatForm: ChatForm
  },
  data: function data() {
    return {
      errored: false,
      loading: false,
      messages: [{
        author: 'user',
        body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.'
      }, {
        author: 'ai',
        body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.'
      }, {
        author: 'user',
        body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.'
      }, {
        author: 'ai',
        body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.'
      }, {
        author: 'user',
        body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.'
      }, {
        author: 'ai',
        body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.'
      }, {
        author: 'user',
        body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.'
      }, {
        author: 'ai',
        body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas varius bibendum mauris ac lacinia. In ac arcu a mauris fermentum pellentesque nec quis lacus. Vestibulum non pulvinar ligula. Etiam lobortis malesuada facilisis. Maecenas ut nisl vitae lectus elementum mattis quis ut erat. Curabitur in purus tempus, pharetra magna efficitur, maximus augue. In ullamcorper sollicitudin rhoncus. Suspendisse luctus tellus quis dolor interdum ornare. Nunc vestibulum, risus vel blandit vehicula, elit orci faucibus sapien, at cursus ante metus non enim. Quisque consectetur pulvinar orci, sit amet euismod turpis vulputate ac. Vestibulum quis elit ut nisi tempor posuere eget ac mauris.'
      }]
    };
  },
  methods: {
    onFormSubmit: function onFormSubmit(prompt) {
      this.messages.push({
        author: 'user',
        body: prompt
      });
      this.loading = true;
      this.scrollDown();
    },
    onSuccess: function onSuccess(response) {
      this.messages.push({
        author: 'ai',
        body: response
      });
      this.loading = false;
      this.scrollDown();
    },
    onError: function onError() {
      this.errored = true;
    },
    scrollDown: function scrollDown() {
      console.log('SCROLL DOWN', window, document);
      setTimeout(function () {
        return window.scrollTo(0, document.body.scrollHeight);
      }, 1);
    }
  }
}));
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/Chat.vue?vue&type=script&lang=ts
 
// EXTERNAL MODULE: ./plugins/MistralAI/vue/src/Components/Chat.vue?vue&type=style&index=0&id=17bc489c&lang=less&scoped=true
var Chatvue_type_style_index_0_id_17bc489c_lang_less_scoped_true = __webpack_require__("921c");

// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Components/Chat.vue





Chatvue_type_script_lang_ts.render = Chatvue_type_template_id_17bc489c_scoped_true_render
Chatvue_type_script_lang_ts.__scopeId = "data-v-17bc489c"

/* harmony default export */ var Chat = (Chatvue_type_script_lang_ts);
// CONCATENATED MODULE: ./node_modules/@vue/cli-plugin-typescript/node_modules/cache-loader/dist/cjs.js??ref--14-0!./node_modules/babel-loader/lib!./node_modules/@vue/cli-plugin-typescript/node_modules/ts-loader??ref--14-2!./node_modules/@vue/cli-service/node_modules/cache-loader/dist/cjs.js??ref--0-0!./node_modules/@vue/cli-service/node_modules/vue-loader-v16/dist??ref--0-1!./plugins/MistralAI/vue/src/Pages/ChatIndexPage.vue?vue&type=script&lang=ts


/* harmony default export */ var ChatIndexPagevue_type_script_lang_ts = (Object(external_commonjs_vue_commonjs2_vue_root_Vue_["defineComponent"])({
  components: {
    Chat: Chat
  }
}));
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Pages/ChatIndexPage.vue?vue&type=script&lang=ts
 
// EXTERNAL MODULE: ./plugins/MistralAI/vue/src/Pages/ChatIndexPage.vue?vue&type=style&index=0&id=dfb64452&lang=less&scoped=true
var ChatIndexPagevue_type_style_index_0_id_dfb64452_lang_less_scoped_true = __webpack_require__("f092");

// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/Pages/ChatIndexPage.vue





ChatIndexPagevue_type_script_lang_ts.render = render
ChatIndexPagevue_type_script_lang_ts.__scopeId = "data-v-dfb64452"

/* harmony default export */ var ChatIndexPage = (ChatIndexPagevue_type_script_lang_ts);
// CONCATENATED MODULE: ./plugins/MistralAI/vue/src/index.ts
// Pages
 // Components






// CONCATENATED MODULE: ./node_modules/@vue/cli-service/lib/commands/build/entry-lib-no-default.js




/***/ })

/******/ });
});
//# sourceMappingURL=MistralAI.umd.js.map