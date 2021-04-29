import { emptyElement, getNonce, sendAjax, sendAjaxWithTimeout } from './helpers';
import { i18nize } from '../utils/i18n';
import {
  manageDisabled,
  pauseElements,
  populateSpinner,
  populateProgress,
  showControlButton,
  writeOutput,
} from './page-elements';

/**
 * Remove progress bar and state UI.
 */
const clearProgress = updateSync => {
  updateSync( 'results', null );
  updateSync( 'post', null );
  emptyElement( 'index-spinner' );
  emptyElement( 'progress-wrapper' );

  const controlButton = document.getElementById( 'gpalab-feeder-resync-control' );

  controlButton.style.display = 'none';
};

/**
   * Add relevant markup for the progress bar and state UI/UX.
   */
const createProgress = paused => {
  const text = paused ? i18nize( 'Paused.' ) : i18nize( 'Processing... Leaving this page will pause the resync.' );

  populateSpinner( text, true );
  populateProgress( paused );
  showControlButton( paused );

  emptyElement( 'gpalab-feeder-output' );
};

/**
 * Update the progress bar and state UI using the local sync variable.
 */
const updateProgress = sync => {
  const count = document.querySelector( '.index-spinner .count' );

  count.innerHTML = ( `${sync.complete} / ${sync.total}` );

  // $( '#progress-bar span' ).animate( { width: `${( sync.complete / sync.total ) * 100}%` } );

  const text = sync.post
    ? `${i18nize( 'Indexing post' )}: ${sync?.post?.title || `${sync.post.type} ${i18nize( 'post' )} #${sync.post.post_id}`}`
    : '';

  const current = document.querySelector( '.current-post' );

  current.innerHTML = text;
};

/**
 * Trigger backend processing of the next available Post in the sync queue
 * and relay the results to the result handler function.
 */
const processQueue = paused => {
  if ( paused ) return;

  // Generate request body as formData.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_next' );
  formData.append( 'security', getNonce() );

  // const onSuccess = result => handleQueueResult( result );
  const onSuccess = result => console.log( result );
  const onError = err => console.error( err );

  // Send request.
  sendAjax( formData, 'POST', onSuccess, onError );
};

/**
 * Pause or resume the current sync process and update the UI accordingly.
 */
export const togglePaused = ( paused, updateSync ) => {
  if ( paused ) {
    updateSync( 'paused', false );
    pauseElements( false );
    processQueue( false );
  } else {
    updateSync( 'paused', true );
    pauseElements( true );
    showControlButton( true );
  }
};

/**
 * Send a basic request to the provided URL and print the response in the output container.
 */
export const testConnection = () => {
  // Clear the results output textarea.
  writeOutput();

  // Disable all manage buttons.
  manageDisabled( true );

  // Generate request body as formData.
  const formData = new FormData();

  const url = document.getElementById( 'es_url' ).value;

  formData.append( 'action', 'gpalab_feeder_test' );
  formData.append( 'data[method]', 'GET' );
  formData.append( 'data[url]', url );
  formData.append( 'security', getNonce() );

  // Function to be called on either success or failure.
  const callback = response => {
    // Write the result to the output textarea.
    writeOutput( response );

    // Re-enable all manage buttons.
    manageDisabled( false );
  };

  // Send request.
  sendAjax( formData, 'POST', callback, callback );
};

export const validateSync = syncStatus => {
  const { isPaused, update, value } = syncStatus();

  // if ( isPaused() ) {
  //   togglePaused( isPaused(), update );
  // }

  // Move all elements into a loading state.
  clearProgress( update );
  writeOutput();
  manageDisabled( true );
  populateSpinner( i18nize( 'Validating...' ) );

  // Generate request body as formData.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_validate' );
  formData.append( 'security', getNonce() );

  // Function to be called on either success or failure.
  const callback = response => {
    createProgress( isPaused() );

    // Write the result to the output textarea.
    writeOutput( response );

    if ( !isPaused() ) {
      togglePaused( isPaused(), update );
    } else {
      updateProgress( value() );
    }

    // Re-enable all manage buttons.
    manageDisabled( false );
  };

  // Send request.
  sendAjaxWithTimeout( formData, 'POST', callback, callback, 120000 );
};
