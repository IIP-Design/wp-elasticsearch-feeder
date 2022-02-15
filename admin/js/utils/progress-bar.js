import { addText, clearText, makeVisible } from './manipulate-dom';

/**
 * Remove progress bar and state UI.
 */
export const clearProgress = sync => {
  // Hide the spinner element.
  const spinner = document.getElementById( 'index-spinner' );

  sync.results = null;
  sync.post = null;

  makeVisible( spinner, false );

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
  const spinner = document.getElementById( 'index-spinner' );
  const spinnerText = document.getElementById( 'index-spinner-text' );

  const spinnerMsg = paused ? 'Paused.' : 'Processing... Leaving this page will pause the resync.';

  makeVisible( spinner, true );
  clearText( spinnerText );
  addText( spinnerMsg, spinnerText );

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
