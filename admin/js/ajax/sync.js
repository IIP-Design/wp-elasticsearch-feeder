import { addToElement, clearErrorNotice, disableManageButtons, emptyElement, prependToElement } from '../utils/manipulate-dom';
import { clearProgress, showProgress, showSpinner, updateProgress } from '../utils/progress-bar';
import { i18nize } from '../utils/i18n';
import { getNonce, sendAdminAjax, sendAdminAjaxWithTimeout } from './helpers';
import { reloadLog } from './log';

const OUTPUT_ID = 'gpalab-feeder-output';

/**
 * Re-enable all buttons and hide spinner.
 */
const reset = () => {
  disableManageButtons( false );
  showSpinner( false );
};

/**
 * Trigger backend processing of the next available Post in the sync queue
 * and relay the results to the result handler function.
 */
const processQueue = async sync => {
  showSpinner( true, 'Processing... Leaving this page will pause the resync.' );

  // Abort if the sync process is paused.
  if ( sync.paused ) {
    return;
  }

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_next' );
  formData.append( 'security', getNonce() );

  const onSuccess = result => {
    updateProgress( sync );

    handleQueueResult( sync, result ); // eslint-disable-line no-use-before-define
  };

  const onError = err => {
    // Display error message in results output.
    addToElement( err, OUTPUT_ID );
  };

  sendAdminAjax( formData, 'POST', onSuccess, onError );
};

/**
 * Store result data in the local variable and update the state and progress bar,
 * and spew the raw result into the output container.
 *
 * @param result
 */
const handleQueueResult = ( sync, result ) => {
  const { complete, done, error, message, response, results, total } = result;

  // Log the return from the API.
  if ( results ) {
    const msg = results.length > 0 ? JSON.stringify( results, null, 2 ) : 'No errors.';

    // Display error message in results output.
    addToElement( msg, OUTPUT_ID );
  } else if ( response ) {
    const msg = JSON.stringify( response, null, 2 );

    prependToElement( msg, OUTPUT_ID );
  }

  // End loop if done or in error.
  if ( error || done ) {
    if ( message ) {
      addToElement( message, OUTPUT_ID );
      reloadLog();
    }

    // Reset the initial page state.
    clearProgress( sync );
    reset();
  } else {
    // If loop continues, update sync data.
    sync.complete = complete;
    sync.total = total;

    if ( response ) {
      sync.post = response.req;
      sync.results = null;
    } else if ( results ) {
      sync.results = results;
      sync.post = null;
    } else {
      sync.results = null;
      sync.post = null;
    }

    // Progress with loop.
    processQueue( sync );
    updateProgress( sync );
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
  // Clear out any existing text in the response output section.
  emptyElement( OUTPUT_ID );
  clearProgress( sync );
  clearErrorNotice();

  // Disable buttons for the duration of the request.
  disableManageButtons( true );

  const spinnerMsg = errorsOnly ? i18nize( 'Fixing errors...' ) : i18nize( 'Initiating new resync.' );

  showSpinner( true, spinnerMsg );

  showProgress();

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_sync_init' );
  formData.append( 'security', getNonce() );
  formData.append( 'sync_errors', errorsOnly );
  formData.append( 'method', 'GET' );

  const onSuccess = result => {
    handleQueueResult( sync, result );
  };

  const onError = err => {
    // Display error message in results output.
    addToElement( err, OUTPUT_ID );
    clearProgress( sync );
  };

  sendAdminAjaxWithTimeout( formData, 'POST', onSuccess, onError, null, 120000 );
};

/**
 * Retrieves the a list of posts in error.
 */
export const validateSync = async () => {
  // Clear out any existing text in the response output section.
  emptyElement( OUTPUT_ID );

  showSpinner( true, i18nize( 'Validating...' ) );

  // Disable buttons for the duration of the request.
  disableManageButtons( true );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_validate' );
  formData.append( 'security', getNonce() );

  // Display test response or error in results output.
  const onResponse = result => {
    addToElement( result, OUTPUT_ID );
  };

  sendAdminAjaxWithTimeout( formData, 'POST', onResponse, onResponse, reset, 120000 );
};
