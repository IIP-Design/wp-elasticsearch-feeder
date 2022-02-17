import { addText, clearText, makeVisible } from './manipulate-dom';

/**
 * Hides/shows the spinner element.
 * @param {boolean} show Whether or not the spinner should be visible.
 * @param {string} msg An optional value to change the spinner text.
 */
export const showSpinner = ( show, msg ) => {
  const spinner = document.getElementById( 'index-spinner' );

  if ( msg ) {
    const spinnerText = document.getElementById( 'index-spinner-text' );

    clearText( spinnerText );
    addText( msg, spinnerText );
  }

  makeVisible( spinner, show );
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
  const progress = document.getElementById( 'progress-bar' );

  makeVisible( progress, false );

  // $( '#es_resync_control' ).hide();
};

/**
   * Add relevant markup for the progress bar and state UI/UX.
   */
export const showProgress = paused => {
  // Show the spinner element.
  const spinnerMsg = paused ? 'Paused.' : 'Processing... Leaving this page will pause the resync.';

  showSpinner( true, spinnerMsg );

  // Show the progress bar.
  const progress = document.getElementById( 'progress-bar' );

  makeVisible( progress, true );

  if ( paused ) {
    progress.classList.add( 'paused' );
  }

  // $( '#es_resync_control' )
  //   .html( sync.paused ? 'Resume Sync' : 'Pause Sync' )
  //   .show();
  // $( '#es_output' ).empty();
};
