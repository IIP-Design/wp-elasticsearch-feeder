import { addToElement, emptyElement, makeVisible } from './manipulate-dom';
import { i18nize } from './i18n';

/**
 * Hides/shows the spinner element.
 * @param {boolean} show Whether or not the spinner should be visible.
 * @param {string} msg An optional value to change the spinner text.
 */
export const showSpinner = ( show, msg ) => {
  const spinnerText = 'index-spinner-text';

  if ( msg ) {
    emptyElement( spinnerText );
    addToElement( msg, spinnerText );
  }

  makeVisible( 'index-spinner', show );
};

/**
 * Remove progress bar and state UI.
 */
export const clearProgress = sync => {
  sync.results = null;
  sync.post = null;

  // Hide the spinner element.
  showSpinner( false );

  // Hide the progress bar.
  makeVisible( 'progress-bar', false );

  // $( '#gpalab-feeder-resync-control' ).hide();
};

/**
   * Add relevant markup for the progress bar and state UI/UX.
   */
export const showProgress = paused => {
  // Show the spinner element.
  const spinnerMsg = paused ? i18nize( 'Paused.' ) : i18nize( 'Processing... Leaving this page will pause the resync.' );

  showSpinner( true, spinnerMsg );

  // Show the progress bar.
  const progressId = 'progress-bar';

  makeVisible( progressId, true );

  if ( paused ) {
    const progress = document.getElementById( progressId );

    progress.classList.add( 'paused' );
  }

  // $( '#gpalab-feeder-resync-control' )
  //   .html( sync.paused ? 'Resume Sync' : 'Pause Sync' )
  //   .show();
  // $( '#gpalab-feeder-output' ).empty();
};
