import { addToElement, emptyElement, makeVisible } from './manipulate-dom';

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
  sync.complete = 0;

  // Hide the spinner element.
  showSpinner( false );

  // Hide the progress bar.
  makeVisible( 'progress-bar', false );
  emptyElement( 'index-spinner-count' );
  document.getElementById( 'progress-bar-span' ).style.width = 0;
};

/**
   * Add relevant markup for the progress bar and state UI/UX.
   */
export const showProgress = paused => {
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

/**
 * Update the progress bar and state UI using the local sync variable.
 */
export const updateProgress = sync => {
  addToElement( `${sync.complete} / ${sync.total}`, 'index-spinner-count' );

  const bar = document.getElementById( 'progress-bar-span' );

  bar.style.width = `${( sync.complete / sync.total ) * 100}%`;
};
