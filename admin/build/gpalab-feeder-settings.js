!function(){"use strict";var e=window.wp.i18n;const t=t=>"string"!=typeof t?t:(0,e.__)(t,"gpalab-feeder"),n=(e,t)=>{const n=document.getElementById(t),a="string"==typeof e?e:JSON.stringify(e,null,2);n.textContent=a||""},a=e=>{const t=document.getElementById(e);for(;t.firstChild;)t.removeChild(t.firstChild)},o=(e,t)=>{const n=document.getElementById(t),a=document.createElement("p"),o="string"==typeof e?e:JSON.stringify(e,null,2);a.textContent=o||"",n.insertBefore(a,n.firstChild)},s=e=>{const t=document.querySelectorAll(".inside.gpalab-manage-btns button.gpalab-manage-button");document.getElementById("gpalab-feeder-resync").disabled=e,t.forEach((t=>{t.disabled=e}))},r=function(e,t){let n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:"block";const a=document.getElementById(e);a.style.display=t?n:"none"},l=e=>{const t="gpalab-growl";e&&(n(e,t),r(t,!0),setTimeout((()=>{r(t,!1),a(t)}),1500))},d=e=>{const n=t("seconds ago (typically updates every 60 seconds)");document.getElementById("gpalab-feeder-last-heartbeat").innerHTML=`${e} ${n}`},c=()=>{var e,t;return null===(e=window)||void 0===e||null===(t=e.gpalabFeederSettings)||void 0===t?void 0:t.feederNonce},i=()=>{var e;return null===(e=window)||void 0===e?void 0:e.ajaxurl},p=(e,t,n,a,o)=>{const s=o||(()=>{});fetch(i(),{method:t,body:e}).then((e=>e.json())).then((e=>{n(e)})).catch((e=>{a(e)})).finally((()=>s()))},u=(e,t,n,a,o,s)=>{const r=o||(()=>{}),l=new AbortController,{signal:d}=l;((e,t)=>new Promise(((n,a)=>{const o=setTimeout((()=>{a(new Error("Request timed out."))}),e);t.then(n,a).finally((()=>clearTimeout(o)))})))(s,fetch(i(),{method:t,body:e,signal:d})).then((e=>e.json())).then((e=>{n(e)})).catch((e=>{a(e),l.abort()})).finally((()=>r()))},g=()=>{s(!1)},m="log-text",y=async()=>{s(!0);const e=new FormData;e.append("action","gpalab_feeder_clear_logs"),e.append("security",c()),p(e,"POST",(()=>{a(m),l(t("Logs cleared."))}),(e=>{console.error(e),l(t("Communication error while clearing logs."))}),g)},b=async()=>{a(m);const e=new FormData;e.append("action","gpalab_feeder_reload_log"),e.append("security",c());const t=e=>{n(e,m)};p(e,"POST",t,t,g)},f={lastHeartbeat:null,heartbeatTimer:null,clear(){this.lastHeartbeat=0,clearInterval(this.heartbeatTimer)},increment(){this.lastHeartbeat+=2},get beat(){return this.lastHeartbeat},get timer(){return this.heartbeatTimer},set newTimer(e){this.heartbeatTimer=e}},h=(e,t)=>{t.gpalab_feeder_count=1},E=()=>{f.clear(),d(f.beat),f.newTimer=setInterval((()=>{f.increment(),d(f.beat)}),2e3)},v=(e,t)=>{var n;E(),t.gpalab_feeder_count&&(n=t.gpalab_feeder_count,document.querySelectorAll(".status-count").forEach((e=>{const{statusId:t}=e.dataset,a=e.textContent,o=n[t]||0;o!==a&&(e.style.opacity=0,e.textContent=o,e.style.opacity=1)})))},w=(e,t)=>{const o="index-spinner-text";t&&(a(o),n(t,o)),r("index-spinner",e)},I=e=>{e.results=null,e.post=null,e.complete=0,w(!1),r("progress-bar",!1),a("index-spinner-count"),document.getElementById("progress-bar-span").style.width=0},_=e=>{n(`${e.complete} / ${e.total}`,"index-spinner-count"),document.getElementById("progress-bar-span").style.width=e.complete/e.total*100+"%"},T=e=>{const o="gpalab-feeder-resync-control",s=document.getElementById("progress-bar");a(o),e?(n(t("Pause Sync"),o),s.classList.remove("paused")):(n(t("Resume Sync"),o),s.classList.add("paused"))},B=e=>{r("progress-bar",!0),e&&T(e)},S="gpalab-feeder-output",L="gpalab-feeder-resync-control",x=()=>{s(!1),w(!1),r(L,!1)},C=async e=>{if(r(L,!0),e.paused)return;w(!0,"Processing... Leaving this page will pause the resync.");const t=new FormData;t.append("action","gpalab_feeder_next"),t.append("security",c()),p(t,"POST",(t=>{_(e),O(e,t)}),(e=>{n(e,S)}))},O=(e,t)=>{B(e);const{complete:n,done:a,error:s,message:r,response:l,results:d,total:c}=t;if(d){console.log(t);const e=d.length>0?JSON.stringify(d,null,2):"No errors.";o(e,S)}else if(l){const e=JSON.stringify(l,null,2);o(e,S)}s||a?(r&&(o(r,S),b()),I(e),x()):(e.complete=n,e.total=c,l?(e.post=l.req,e.results=null):d?(e.results=d,e.post=null):(e.results=null,e.post=null),C(e),_(e))},P=async(e,o)=>{a(S),I(e),(()=>{const e=document.getElementById("gpalab-feeder-notice");e&&null!==e.parentNode&&e.parentNode.removeChild(e)})(),s(!0);const r=t(o?"Fixing errors...":"Initiating new resync.");w(!0,r);const l=new FormData;l.append("action","gpalab_feeder_sync_init"),l.append("security",c()),l.append("sync_errors",o),l.append("method","GET"),u(l,"POST",(t=>{O(e,t)}),(t=>{n(t,S),I(e)}),null,12e4)},k=async()=>{const e="gpalab-feeder-output",o=document.getElementById("gpalab-feeder-url-input");w(!0,t("Testing connection...")),a(e),s(!0);const r=new FormData;r.append("action","gpalab_feeder_test"),r.append("security",c()),r.append("url",o.value),r.append("method","GET");const l=t=>{n(t,e)};p(r,"POST",l,l,(()=>{s(!1),w(!1)}))};(e=>{if("loading"!==document.readyState)return e();document.addEventListener("DOMContentLoaded",e)})((()=>{const e={total:0,complete:0,post:null,paused:!1,results:null};(e=>{const{syncTotals:t}=window.gpalabFeederSettings;e.total=parseInt(t.total,10),e.complete=parseInt(t.complete,10),e.paused=t.paused,T(e.paused),e.paused&&(I(e),B(e.paused))})(e),(e=>{const o=document.getElementById("gpalab-feeder-clear-logs"),r=document.getElementById("gpalab-feeder-test-connection"),l=document.getElementById("gpalab-feeder-resync"),d=document.getElementById("gpalab-feeder-fix-errors"),i=document.getElementById("gpalab-feeder-resync-control"),p=document.getElementById("gpalab-feeder-validate-sync"),g=document.getElementById("gpalab-feeder-reload-log");o.addEventListener("click",y),r.addEventListener("click",k),l.addEventListener("click",(()=>P(e,!1))),d.addEventListener("click",(()=>P(e,!0))),i.addEventListener("click",(()=>(e=>{const{paused:t}=e;T(t),t?(w(!0,"Processing... Leaving this page will pause the resync."),C({...e,paused:!t})):w(!0,"Paused.")})(e))),p.addEventListener("click",(()=>(async()=>{a(S),w(!0,t("Validating...")),s(!0);const e=new FormData;e.append("action","gpalab_feeder_validate"),e.append("security",c());const o=e=>{n(e,S)};u(e,"POST",o,o,x,12e4)})())),g.addEventListener("click",b),jQuery(document).on("heartbeat-send",h),jQuery(document).on("heartbeat-tick",v),E()})(e)}))}();