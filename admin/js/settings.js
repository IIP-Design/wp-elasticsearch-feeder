import { addText, clearText } from './utils/manipulate-dom';
import { ready } from './utils/document-ready';

// ( function( $ ) {
//   // sync object contains data related to the current (if any) resync
//   let sync = {
//     total: 0,
//     complete: 0,
//     post: null,
//     paused: false,
//     results: null,
//   };

//   let lastHeartbeat = null;
//   let lastHeartbeatTimer = null;

//   const { nonce, syncTotals } = window.gpalabFeederSettings;

//   /**
//    * Register click listener functions, load sync data from the injected variable, and
//    * update sync state if a sync was in progress.
//    */
//   $( window ).load( () => {
//     $( '#es_resync' ).on( 'click', resyncStart( 0 ) );
//     $( '#es_resync_errors' ).on( 'click', resyncStart( 1 ) );
//     $( '#es_resync_control' ).on( 'click', resyncControl );
//     $( '#es_validate_sync' ).on( 'click', validateSync );
//     $( '#reload_log' ).on( 'click', reloadLog );

//     sync.total = parseInt( syncTotals.total, 10 );
//     sync.complete = parseInt( syncTotals.complete, 10 );
//     sync.paused = syncTotals.paused === '1';
//     if ( sync.paused ) {
//       createProgress();
//       updateProgress();
//     }

//     $( document ).on( 'heartbeat-send', ( event, data ) => {
//       data.es_sync_status_counts = 1;
//     } );
//     $( document ).on( 'heartbeat-tick', ( event, data ) => {
//       resetHeartbeatTimer();
//       if ( !data.es_sync_status_counts ) return;
//       $( '.status-count' ).each( ( i, status ) => {
//         const $status = $( status );
//         const id = $status.attr( 'data-status-id' );
//         const newCount = data.es_sync_status_counts[id] || 0;

//         if ( $status.html() !== `${newCount}` ) {
//           $status.fadeOut( 'slow', () => {
//             $status.html( newCount );
//             $status.fadeIn( 'slow' );
//           } );
//         }
//       } );
//     } );
//     resetHeartbeatTimer();
//   } );

//   function resetHeartbeatTimer() {
//     if ( lastHeartbeatTimer ) clearInterval( lastHeartbeatTimer );
//     lastHeartbeat = 0;
//     $( '#last-heartbeat' ).html( `${lastHeartbeat}s ago (usually every 15s)` );
//     lastHeartbeatTimer = setInterval( () => {
//       lastHeartbeat += 1;
//       $( '#last-heartbeat' ).html( `${lastHeartbeat}s ago (usually every 15s)` );
//     }, 1000 );
//   }

//   /**
//    * Loads the last 100 lines of callback.log
//    */
//   function reloadLog() {
//     $( '#log_text' ).empty();
//     $.ajax( {
//       url: window.ajaxurl,
//       type: 'POST',
//       dataType: 'JSON',
//       data: {
//         action: 'gpalab_feeder_reload_log',
//         security: nonce,
//       },
//       success( result ) {
//         $( '#log_text' ).text( result );
//       },
//       error( result ) {
//         console.error( result );
//         alert( 'Communication error while reloading log.' );
//       },
//     } );
//   }

//   function validateSync() {
//     let unpause = false;

//     if ( !sync.paused ) {
//       unpause = true;
//       resyncControl();
//     }
//     clearProgress();
//     $( '#es_output' ).text( '' );
//     let html = '<div class="spinner is-active spinner-animation">';

//     html += '<span class="spinner-text">Validating...</span>';
//     html += '</div>';
//     $( '.index-spinner' ).html( html );
//     disableManage();
//     $.ajax( {
//       timeout: 120000,
//       url: window.ajaxurl,
//       type: 'POST',
//       dataType: 'JSON',
//       data: {
//         action: 'gpalab_feeder_validate',
//         security: nonce,
//       },
//       success( result ) {
//         clearProgress();
//         createProgress();
//         $( '#es_output' ).text( JSON.stringify( result, null, 2 ) );
//         if ( unpause ) resyncControl();
//         else updateProgress();
//       },
//       error( result ) {
//         console.error( result );
//         clearProgress();
//         createProgress();
//         $( '#es_output' ).text( JSON.stringify( result, null, 2 ) );
//         if ( unpause ) resyncControl();
//         else updateProgress();
//       },
//     } ).always( enableManage );
//   }


//   /**
//    * TODO: Initiate a new sync by deleting ALL of this site's posts from ES
//    * Clear out old sync post meta (if any) and initiate a new sync process.
//    */
//   function resyncStart( errorsOnly ) {
//     return function() {
//       const $notice = $( '.feeder-notice.notice-error' );

//       if ( $notice.length > 0 ) {
//         $notice.fadeTo( 100, 0, () => {
//           $notice.slideUp( 100, () => {
//             $notice.remove();
//           } );
//         } );
//       }
//       sync = {
//         total: 0,
//         complete: 0,
//         post: null,
//         paused: false,
//       };
//       createProgress();
//       updateProgress();
//       disableManage();
//       $.ajax( {
//         timeout: 120000,
//         url: window.ajaxurl,
//         type: 'POST',
//         dataType: 'JSON',
//         data: {
//           action: 'gpalab_feeder_sync_init',
//           security: nonce,
//           sync_errors: errorsOnly,
//         },
//         success( result ) {
//           handleQueueResult( result );
//         },
//         error( result ) {
//           console.error( result );
//           clearProgress();
//         },
//       } ).always( enableManage );
//     };
//   }

//   /**
//    * Pause or resume the current sync process and update the UI accordingly.
//    */
//   function resyncControl() {
//     if ( sync.paused ) {
//       $( '#es_resync_control' ).html( 'Pause Sync' );
//       sync.paused = false;
//       $( '#progress-bar' ).removeClass( 'paused' );
//       $( '.spinner-text' ).html( 'Processing... Leaving this page will pause the resync.' );
//       processQueue();
//     } else {
//       $( '#es_resync_control' ).html( 'Resume Sync' );
//       $( '#progress-bar' ).addClass( 'paused' );
//       $( '.spinner-text' ).html( 'Paused.' );
//       sync.paused = true;
//     }
//   }

//   /**
//    * Trigger backend processing of the next available Post in the sync queue
//    * and relay the results to the result handler function.
//    */
//   function processQueue() {
//     if ( sync.paused ) return;
//     $.ajax( {
//       type: 'POST',
//       dataType: 'JSON',
//       url: window.ajaxurl,
//       data: {
//         action: 'gpalab_feeder_next',
//         security: nonce,
//       },
//       success( result ) {
//         handleQueueResult( result );
//       },
//       error( result ) {
//         console.error( result );
//       },
//     } );
//   }

//   /**
//    * Store result data in the local variable and update the state and progress bar,
//    * and spew the raw result into the output container.
//    *
//    * @param result
//    */
//   function handleQueueResult( result ) {
//     console.log( result );
//     if ( result.error || result.done ) {
//       clearProgress();
//       if ( result.error && result.message ) $( '#es_output' ).html( result.message );
//       reloadLog();
//     } else {
//       sync.complete = result.complete;
//       sync.total = result.total;
//       if ( result.response ) {
//         sync.post = result.response.req;
//         sync.results = null;
//       } else if ( result.results ) {
//         sync.results = result.results;
//         sync.post = null;
//       } else {
//         sync.results = null;
//         sync.post = null;
//       }
//       updateProgress();
//       processQueue();
//     }
//     if ( result.results ) $( '#es_output' ).html(
//       result.results.length > 0 ? JSON.stringify( result.results, null, 2 ) : 'No errors.',
//     );
//     else if ( result.response ) $( '#es_output' ).prepend( `${JSON.stringify( result, null, 2 )}\r\n\r\n` );
//   }

//   /**
//    * Add relevant markup for the progress bar and state UI/UX.
//    */
//   function createProgress() {
//     let html = '<div class="spinner is-active spinner-animation">';

//     html += `<span class="spinner-text">${
//       sync.paused ? 'Paused.' : 'Processing... Leaving this page will pause the resync.'
//     }</span> <span class="count"></span> <span class="current-post"></span>`;
//     html += '</div>';
//     $( '.index-spinner' ).html( html );
//     $( '.progress-wrapper' ).html(
//       `<div id="progress-bar" ${sync.paused ? 'class="paused"' : ''}><span></span></div>`,
//     );
//     $( '#es_resync_control' )
//       .html( sync.paused ? 'Resume Sync' : 'Pause Sync' )
//       .show();
//     $( '#es_output' ).empty();
//   }

//   /**
//    * Update the pgoress bar and state UI using the local sync variable.
//    */
//   function updateProgress() {
//     $( '.index-spinner .count' ).html( `${sync.complete} / ${sync.total}` );
//     $( '#progress-bar span' ).animate( { width: `${( sync.complete / sync.total ) * 100}%` } );
//     $( '.current-post' ).html(
//       sync.post
//         ? `Indexing post: ${
//           sync.post.title ? sync.post.title : `${sync.post.type} post #${sync.post.post_id}`
//         }`
//         : '',
//     );
//   }

//   /**
//    * Remove progress bar and state UI.
//    */
//   function clearProgress() {
//     sync.results = null;
//     sync.post = null;
//     $( '.index-spinner' ).empty();
//     $( '.progress-wrapper' ).empty();
//     $( '#es_resync_control' ).hide();
//   }
// } )( jQuery );

/**
 * Disable/enable the manage buttons.
 * @param {boolean} disable Whether the buttons should be disabled or not.
 */
const disableManageButtons = disable => {
  const btns = document.querySelectorAll( '.inside.manage-btns button' );

  btns.forEach( btn => { btn.disabled = disable; } );
};

const truncateLogs = async () => {
  const { feederNonce } = window.gpalabFeederSettings;

  const logText = document.getElementById( 'log_text' );

  // Disable buttons for the duration of the request.
  disableManageButtons( true );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_clear_logs' );
  formData.append( 'security', feederNonce );

  try {
    await fetch( window.ajaxurl, {
      method: 'POST',
      body: formData,
    } );

    logText.innerHTML = '';
    alert( 'Logs truncated.' );
  } catch ( err ) {
    console.error( err );
    alert( 'Communication error while truncating logs.' );
  }
};

/**
 * Send a simple request to the CDP API to confirm that the connection is live.
 */
const testConnection = async () => {
  const { feederNonce } = window.gpalabFeederSettings;

  const output = document.getElementById( 'es_output' );
  const url = document.getElementById( 'es_url' );

  // Clear out any existing text in the response output section.
  clearText( output );

  // Disable buttons for the duration of the request.
  disableManageButtons( true );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_test' );
  formData.append( 'security', feederNonce );
  formData.append( 'url', url.value );
  formData.append( 'method', 'GET' );

  try {
    const response = await fetch( window.ajaxurl, {
      method: 'POST',
      body: formData,
    } );

    const result = await response.json();

    // Display test response in results output.
    addText( JSON.stringify( result, null, 2 ), output );
  } catch ( err ) {
    // Display error message in results output.
    addText( JSON.stringify( err, null, 2 ), output );
  } finally {
    // Re-enable all buttons.
    disableManageButtons( false );
  }
};

/**
 * Initialize all event listeners for the settings page.
 */
const initializeEventListener = () => {
  const trimLogs = document.getElementById( 'truncate_logs' );
  const connection = document.getElementById( 'es_test_connection' );
  const resync = document.getElementById( 'es_resync' );
  const resyncErrors = document.getElementById( 'es_resync_errors' );
  const resyncControl = document.getElementById( 'es_resync_control' );
  const validateSync = document.getElementById( 'es_validate_sync' );
  const reloadLog = document.getElementById( 'reload_log' );

  trimLogs.addEventListener( 'click', truncateLogs );
  connection.addEventListener( 'click', testConnection );
  resync.addEventListener( 'click', () => resync( 0 ) );
  resyncErrors.addEventListener( 'click', () => resync( 1 ) );
  resyncControl.addEventListener( 'click', resyncControl );
  validateSync.addEventListener( 'click', validateSync );
  reloadLog.addEventListener( 'click', reloadLog );
};

/**
 * Set up the page event listeners once the page is loaded.
 */
ready( () => {
  initializeEventListener();
} );
