!function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s=15)}({1:function(e,t){e.exports=window.regeneratorRuntime},15:function(e,t,n){"use strict";n.r(t);var r=n(2),o=n.n(r),c=n(1),u=n.n(c),a=function(e){document.querySelectorAll(".inside.manage-btns button").forEach((function(t){t.disabled=e}))},i=function(e,t){var n=document.createTextNode(e);t.appendChild(n)},d=function(){var e=o()(u.a.mark((function e(){var t,n,r;return u.a.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return t=window.gpalabFeederSettings.feederNonce,n=document.getElementById("log_text"),(r=new FormData).append("action","gpalab_feeder_clear_logs"),r.append("security",t),e.prev=5,e.next=8,fetch(window.ajaxurl,{method:"POST",body:r});case 8:n.innerHTML="",alert("Logs truncated."),e.next=16;break;case 12:e.prev=12,e.t0=e.catch(5),console.error(e.t0),alert("Communication error while truncating logs.");case 16:case"end":return e.stop()}}),e,null,[[5,12]])})));return function(){return e.apply(this,arguments)}}(),l=function(){var e=o()(u.a.mark((function e(){var t,n,r,o,c,d;return u.a.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return t=window.gpalabFeederSettings.feederNonce,n=document.getElementById("es_output"),r=document.getElementById("es_url"),n.innerHTML="",a(!0),(o=new FormData).append("action","gpalab_feeder_test"),o.append("security",t),o.append("url",r.value),o.append("method","GET"),e.prev=10,e.next=13,fetch(window.ajaxurl,{method:"POST",body:o});case 13:return c=e.sent,e.next=16,c.json();case 16:d=e.sent,i(JSON.stringify(d,null,2),n),e.next=23;break;case 20:e.prev=20,e.t0=e.catch(10),i(JSON.stringify(e.t0,null,2),n);case 23:return e.prev=23,a(!1),e.finish(23);case 26:case"end":return e.stop()}}),e,null,[[10,20,23,26]])})));return function(){return e.apply(this,arguments)}}();!function(e){if("loading"!==document.readyState)return e();document.addEventListener("DOMContentLoaded",e)}((function(){var e,t,n,r,o,c,u,a;e=document.getElementById("truncate_logs"),t=document.getElementById("es_test_connection"),n=document.getElementById("es_query_index"),r=document.getElementById("es_resync"),o=document.getElementById("es_resync_errors"),c=document.getElementById("es_resync_control"),u=document.getElementById("es_validate_sync"),a=document.getElementById("reload_log"),e.addEventListener("click",d),t.addEventListener("click",l),n.addEventListener("click",n),r.addEventListener("click",(function(){return r(0)})),o.addEventListener("click",(function(){return r(1)})),c.addEventListener("click",c),u.addEventListener("click",u),a.addEventListener("click",a)}))},2:function(e,t){function n(e,t,n,r,o,c,u){try{var a=e[c](u),i=a.value}catch(e){return void n(e)}a.done?t(i):Promise.resolve(i).then(r,o)}e.exports=function(e){return function(){var t=this,r=arguments;return new Promise((function(o,c){var u=e.apply(t,r);function a(e){n(u,o,c,a,i,"next",e)}function i(e){n(u,o,c,a,i,"throw",e)}a(void 0)}))}},e.exports.default=e.exports,e.exports.__esModule=!0}});