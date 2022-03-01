import { i18nize } from './i18n';

/**
 * Write response data to the output box.
 *
 * @param {string|Object} data The response data.
 */
export const addToElement = ( data, id ) => {
  const outputEl = document.getElementById( id );

  const val = typeof data === 'string' ? data : JSON.stringify( data, null, 2 );

  outputEl.textContent = val || '';
};

/**
  * Find an element by its id and clear its text.
  *
  * @param {string} id The id value of the targeted element.
  */
export const emptyElement = id => {
  const el = document.getElementById( id );

  // Remove all child elements.
  while ( el.firstChild ) el.removeChild( el.firstChild );
};

/**
 * Disable/enable the manage buttons.
 * @param {boolean} disable Whether the buttons should be disabled or not.
 */
export const disableManageButtons = disable => {
  const btns = document.querySelectorAll( '.inside.manage-btns button' );

  btns.forEach( btn => { btn.disabled = disable; } );
};

/**
 * Toggles the display property of an element.
 * @param {Object} id The id of the DOM node to clear.
 * @param {boolean} visible Whether or not the element should be visible.
 * @param {string} alt The value to be used as the inverse of display none. Defaults to block.
 */
export const makeVisible = ( id, visible, alt = 'block' ) => {
  const node = document.getElementById( id );

  node.style.display = visible ? alt : 'none';
};

/**
 * Shows the provided message in a growl notification.
 * @param {string} msg The message that should be displayed in the growl notification.
 */
export const showGrowl = msg => {
  const growlId = 'gpalab-growl';

  // Abort if a notification message is not provided.
  if ( !msg ) {
    return;
  }

  // Show the growl.
  addToElement( msg, growlId );
  makeVisible( growlId, true );

  // Clear and hide the growl.
  setTimeout( () => {
    makeVisible( growlId, false );
    emptyElement( growlId );
  }, 1500 );
};

/**
 * Update the publication status indicator on an indexable post edit screen.
 *
 * @param {string} color The indicator color associated with the provided status.
 * @param {string} title The title associated with the provided status.
 */
export const updatePostStatus = ( color, title ) => {
  const indicator = document.querySelector( '.sync-status' );
  const label = document.querySelector( '.sync-status-label' );

  indicator.className = `sync-status sync-status-${color}`;
  label.textContent = title;
};

/**
 * Update the publication status indicators on the settings page.
 *
 * @param {Object} counts List of number of posts for each status.
 */
export const updateStatuses = counts => {
  // Get all status indicator elements.
  const statuses = document.querySelectorAll( '.status-count' );

  statuses.forEach( status => {
    // Determine which status each element represents.
    const { statusId } = status.dataset;

    const currentCount = status.textContent;
    const updatedCount = counts[statusId] || 0;

    // Fade in new value.
    if ( updatedCount !== currentCount ) {
      status.style.opacity = 0;
      status.textContent = updatedCount;
      status.style.opacity = 1;
    }
  } );
};

/**
 * Update the time elapsed since the last heartbeat indicator.
 *
 * @param {string} beat Number of seconds since the last update.
 */
export const updateTicker = beat => {
  const text = i18nize( 'seconds ago (typically updates every 60 seconds)' );
  const indicator = document.getElementById( 'gpalab-feeder-last-heartbeat' );

  indicator.innerHTML = `${beat} ${text}`;
};
