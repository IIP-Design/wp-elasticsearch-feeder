import { i18nize } from '../utils/i18n';

/**
 * Write response data to the output box.
 *
 * @param {Object} data The response data.
 */
export const writeOutput = data => {
  const outputText = document.getElementById( 'gpalab-feeder-output' );

  outputText.textContent = data ? JSON.stringify( data, null, 2 ) : '';
};

/**
 * Disable/enable all manage buttons.
 *
 * @param {boolean} disabled Whether or not the manage buttons should be disabled.
 */
export const manageDisabled = disabled => {
  // Get all manage buttons.
  const manageBtns = document.querySelectorAll( '.inside.manage-btns button' );

  if ( disabled ) {
    manageBtns.forEach( btn => {
      btn.setAttribute( 'disabled', '' );
    } );
  } else {
    manageBtns.forEach( btn => {
      btn.removeAttribute( 'disabled' );
    } );
  }
};

/**
 * Render out the sync processing spinner.
 *
 * @param {string} text The message to display along side the spinner.
 * @param {boolean} count Whether or not to show the count of items.
 */
export const populateSpinner = ( text, count ) => {
  const html = `
    <div class="spinner is-active spinner-animation">
      <span class="spinner-text">${text}</span>
      ${count ? '<span class="count"></span><span class="current-post"></span>' : ''}
    </div>
  `;

  const spinner = document.getElementById( 'index-spinner' );

  spinner.innerHTML = html;
};

/**
 * Render out the sync progress bar with.
 *
 * @param {boolean} paused Whether or not the sync is paused.
 */
export const populateProgress = paused => {
  const progress = document.getElementById( 'progress-wrapper' );

  progress.innerHTML = `<div id="progress-bar" ${paused ? 'class="paused"' : ''}><span></span></div>`;
};

/**
 * Display the sync pause control button.
 *
 * @param {boolean} paused Whether or not the sync is paused.
 */
export const showControlButton = paused => {
  const controlButton = document.getElementById( 'gpalab-feeder-resync-control' );

  controlButton.innerHTML = paused ? i18nize( 'Resume Sync' ) : i18nize( 'Pause Sync' );
  controlButton.style.display = 'block';
};

/**
 * Update the settings UI elements to indicate when a request is paused/in progress.
 *
 * @param {boolean} paused Whether or not the sync is paused.
 */
export const pauseElements = paused => {
  const progressBar = document.getElementById( 'progress-bar' );
  const spinnerText = document.querySelector( '.spinner-text' );

  if ( paused ) {
    showControlButton( true );
    progressBar.classList.add( 'paused' );
    spinnerText.innerHTML = i18nize( 'Paused.' );
  } else {
    showControlButton( false );
    progressBar.classList.remove( 'paused' );
    spinnerText.innerHTML = i18nize( 'Processing... Leaving this page will pause the resync.' );
  }
};
