import { addText, clearText, showGrowl } from './utils/manipulate-dom';
import { clearProgress, showProgress, showSpinner } from './utils/progress-bar';
import { ready } from './utils/document-ready';

// ( function( $ ) {
//   let lastHeartbeat = null;
//   let lastHeartbeatTimer = null;

//   const { nonce, syncTotals } = window.gpalabFeederSettings;

//   /**
//    * Register click listener functions, load sync data from the injected variable, and
//    * update sync state if a sync was in progress.
//    */
//   $( window ).load( () => {
//     $( '#es_resync' ).on( 'click', resyncStart( 0 ) );
//     $( '#gpalab-feeder-resync-control' ).on( 'click', resyncControl );
//     $( '#reload_log' ).on( 'click', reloadLog );

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
//    * Pause or resume the current sync process and update the UI accordingly.
//    */
//   function resyncControl() {
//     if ( sync.paused ) {
//       $( '#gpalab-feeder-resync-control' ).html( 'Pause Sync' );
//       sync.paused = false;
//       $( '#progress-bar' ).removeClass( 'paused' );
//       $( '.spinner-text' ).html( 'Processing... Leaving this page will pause the resync.' );
//       processQueue();
//     } else {
//       $( '#gpalab-feeder-resync-control' ).html( 'Resume Sync' );
//       $( '#progress-bar' ).addClass( 'paused' );
//       $( '.spinner-text' ).html( 'Paused.' );
//       sync.paused = true;
//     }
//   }

//   /**
//    * Update the progress bar and state UI using the local sync variable.
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
// } )( jQuery );

/**
 * Disable/enable the manage buttons.
 * @param {boolean} disable Whether the buttons should be disabled or not.
 */
const disableManageButtons = disable => {
  const btns = document.querySelectorAll( '.inside.manage-btns button' );

  btns.forEach( btn => { btn.disabled = disable; } );
};

/**
 * Clears the log output on the settings page.
 */
const clearLog = async () => {
  const { feederNonce } = window.gpalabFeederSettings;

  const logText = document.getElementById( 'log-text' );

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

    // Clear out any existing text in the log output section.
    clearText( logText );
    showGrowl( 'Logs cleared.' );
  } catch ( err ) {
    console.error( err );
    showGrowl( 'Communication error while clearing logs.' );
  } finally {
    // Re-enable all buttons.
    disableManageButtons( false );
  }
};

/**
 * Send a simple request to the CDP API to confirm that the connection is live.
 */
const testConnection = async () => {
  const { feederNonce } = window.gpalabFeederSettings;

  const output = document.getElementById( 'gpalab-feeder-output' );
  const url = document.getElementById( 'gpalab-feeder-url-input' );

  showSpinner( true, 'Testing connection...' );

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
    // Re-enable all buttons and hide spinner.
    disableManageButtons( false );
    showSpinner( false );
  }
};

/**
 * Trigger backend processing of the next available Post in the sync queue
 * and relay the results to the result handler function.
 */
const processQueue = async sync => {
  const { feederNonce } = window.gpalabFeederSettings;

  const output = document.getElementById( 'gpalab-feeder-output' );

  // Abort if the sync process is paused.
  if ( sync.paused ) {
    return;
  }

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_next' );
  formData.append( 'security', feederNonce );

  try {
    const response = await fetch( window.ajaxurl, {
      method: 'POST',
      body: formData,
    } );

    const result = await response.json();

    console.log( 'processQueue: ', result );


    handleQueueResult( sync, result ); // eslint-disable-line no-use-before-define
  } catch ( err ) {
    // Display error message in results output.
    addText( JSON.stringify( err, null, 2 ), output );
  }
};

/**
 * Store result data in the local variable and update the state and progress bar,
 * and spew the raw result into the output container.
 *
 * @param result
 */
const handleQueueResult = ( sync, result ) => {
  const output = document.getElementById( 'gpalab-feeder-output' );

  console.log( 'handleQueueResult: ', result );
  if ( result.error || result.done ) {
    clearProgress( sync );

    if ( result.error && result.message ) {
      addText( result.message, output );
    }
    // reloadLog();
  } else {
    sync.complete = result.complete;
    sync.total = result.total;

    if ( result.response ) {
      sync.post = result.response.req;
      sync.results = null;
    } else if ( result.results ) {
      sync.results = result.results;
      sync.post = null;
    } else {
      sync.results = null;
      sync.post = null;
    }

    // updateProgress();
    processQueue( sync );
  }

  if ( result.results ) {
    const msg = result.results.length > 0 ? JSON.stringify( result.results, null, 2 ) : 'No errors.';

    // Display error message in results output.
    addText( JSON.stringify( msg, null, 2 ), output );
  } else if ( result.response ) {
    // $( '#gpalab-feeder-output' ).prepend( `${JSON.stringify( result, null, 2 )}\r\n\r\n` );
  }
};

/**
 * TODO: Initiate a new sync by deleting ALL of this site's posts from ES
 * Clear out old sync post meta (if any) and initiate a new sync process.
 */
const resync = async ( sync, errorsOnly ) => {
  const { feederNonce } = window.gpalabFeederSettings;

  const output = document.getElementById( 'gpalab-feeder-output' );

  // const $notice = $( '.feeder-notice.notice-error' );

  // if ( $notice.length > 0 ) {
  //   $notice.fadeTo( 100, 0, () => {
  //     $notice.slideUp( 100, () => {
  //       $notice.remove();
  //     } );
  //   } );
  // }
  // sync = {
  //   total: 0,
  //   complete: 0,
  //   post: null,
  //   paused: false,
  // };
  // createProgress();
  // updateProgress();

  const spinnerMsg = errorsOnly ? 'Fixing errors...' : 'Initiating new resync.';

  showSpinner( true, spinnerMsg );

  // Clear out any existing text in the response output section.
  clearText( output );

  // Disable buttons for the duration of the request.
  disableManageButtons( true );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_sync_init' );
  formData.append( 'security', feederNonce );
  formData.append( 'sync_errors', errorsOnly );
  formData.append( 'method', 'GET' );

  try {
    // timeout: 120000,

    const response = await fetch( window.ajaxurl, {
      method: 'POST',
      body: formData,
    } );

    const result = await response.json();

    handleQueueResult( sync, result );
  } catch ( err ) {
    clearProgress( sync );
    // Display error message in results output.
    addText( JSON.stringify( err, null, 2 ), output );
  } finally {
  // Re-enable all buttons and hide spinner.
    disableManageButtons( false );
    showSpinner( false );
  }
};

const validateSync = async sync => {
  const { feederNonce } = window.gpalabFeederSettings;

  const output = document.getElementById( 'gpalab-feeder-output' );

  // let unpause = false;

  // if ( !sync.paused ) {
  //   unpause = true;
  //   resyncControl();
  // }
  // clearProgress();

  // Clear out any existing text in the response output section.
  clearText( output );

  showProgress( sync.paused );

  // Disable buttons for the duration of the request.
  disableManageButtons( true );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_validate' );
  formData.append( 'security', feederNonce );

  try {
    // timeout: 120000,

    const response = await fetch( window.ajaxurl, {
      method: 'POST',
      body: formData,
    } );

    const result = await response.json();

    clearProgress( sync );

    // Display test response in results output.
    addText( JSON.stringify( result, null, 2 ), output );

    // if ( unpause ) resyncControl();
    // else updateProgress();
  } catch ( err ) {
    clearProgress( sync );
    // createProgress();

    // Display error message in results output.
    addText( JSON.stringify( err, null, 2 ), output );

    // if ( unpause ) resyncControl();
    // else updateProgress();
  } finally {
    // Re-enable all buttons.
    disableManageButtons( false );
  }
};

/**
 * Loads the last 100 lines of callback.log
 */
const reloadLog = async () => {
  const { feederNonce } = window.gpalabFeederSettings;

  const logText = document.getElementById( 'log-text' );

  // Clear out any existing text in the response output section.
  clearText( logText );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_reload_log' );
  formData.append( 'security', feederNonce );

  try {
    const response = await fetch( window.ajaxurl, {
      method: 'POST',
      body: formData,
    } );

    const result = await response.json();

    // Display result message in log.
    addText( JSON.stringify( result, null, 2 ), logText );
  } catch ( err ) {
    // Display error message in log.
    addText( JSON.stringify( err, null, 2 ), logText );
  } finally {
    // Re-enable all buttons.
    disableManageButtons( false );
  }
};

/**
 * Initialize all event listeners for the settings page.
 */
const initializeEventListener = sync => {
  const clearLogBtn = document.getElementById( 'gpalab-feeder-clear-logs' );
  const testConnectionBtn = document.getElementById( 'gpalab-feeder-test-connection' );
  const resyncBtn = document.getElementById( 'gpalab-feeder-resync' );
  const fixErrorsBtn = document.getElementById( 'gpalab-feeder-fix-errors' );
  const resyncControl = document.getElementById( 'gpalab-feeder-resync-control' );
  const validateSyncBtn = document.getElementById( 'gpalab-feeder-validate-sync' );
  const reloadLogBtn = document.getElementById( 'gpalab-feeder-reload-log' );

  clearLogBtn.addEventListener( 'click', clearLog );
  testConnectionBtn.addEventListener( 'click', testConnection );
  resyncBtn.addEventListener( 'click', () => resync( sync, false ) );
  fixErrorsBtn.addEventListener( 'click', () => resync( sync, true ) );
  resyncControl.addEventListener( 'click', resyncControl );
  validateSyncBtn.addEventListener( 'click', () => validateSync( sync ) );
  reloadLogBtn.addEventListener( 'click', reloadLog );
};

/**
 * Initializes the sync object, which keeps track
 * of the statuses for ongoing indexing.
 * @param {Object} sync The default initial values.
 */
const initializeSync = sync => {
  const { syncTotals } = window.gpalabFeederSettings;

  sync.total = parseInt( syncTotals.total, 10 );
  sync.complete = parseInt( syncTotals.complete, 10 );
  sync.paused = syncTotals.paused;

  if ( sync.paused ) {
    clearProgress( sync );
    showProgress( sync.paused );
  }
};

/**
 * Set up the page event listeners once the page is loaded.
 */
ready( () => {
  // Sync object contains data related to the current (if any) resync.
  const sync = {
    total: 0,
    complete: 0,
    post: null,
    paused: false,
    results: null,
  };

  initializeSync( sync );
  initializeEventListener( sync );
} );
