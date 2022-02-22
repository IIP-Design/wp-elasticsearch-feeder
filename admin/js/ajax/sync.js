import { addText, clearText, disableManageButtons } from '../utils/manipulate-dom';
import { clearProgress, showProgress, showSpinner } from '../utils/progress-bar';
import { i18nize } from '../utils/i18n';

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
 * Initializes the sync object, which keeps track
 * of the statuses for ongoing indexing.
 * @param {Object} sync The default initial values.
 */
export const initializeSync = sync => {
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
 * TODO: Initiate a new sync by deleting ALL of this site's posts from ES
 * Clear out old sync post meta (if any) and initiate a new sync process.
 */
export const resync = async ( sync, errorsOnly ) => {
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

  const spinnerMsg = errorsOnly ? i18nize( 'Fixing errors...' ) : i18nize( 'Initiating new resync.' );

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

export const validateSync = async sync => {
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
