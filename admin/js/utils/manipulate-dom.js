import { i18nize } from './i18n';

/**
 * Append a text node to an existing DOM element.
 * @param {string} text The text content to add.
 * @param {Object} node The DOM node to append the text to.
 */
export const addText = ( text, node ) => {
  const toAdd = document.createTextNode( text );

  node.appendChild( toAdd );
};

/**
 * Sets the innerHTML of an element to an empty string.
 * @param {Object} node The DOM node to clear.
 */
export const clearText = node => {
  node.innerHTML = '';
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
 * @param {Object} node The DOM node to clear.
 * @param {boolean} visible Whether or not the element should be visible.
 * @param {string} alt The value to be used as the inverse of display none. Defaults to block.
 */
export const makeVisible = ( node, visible, alt = 'block' ) => {
  node.style.display = visible ? alt : 'none';
};

/**
 * Shows the provided message in a growl notification.
 * @param {string} msg The message that should be displayed in the growl notification.
 */
export const showGrowl = msg => {
  const growl = document.getElementById( 'gpalab-growl' );

  // Abort if the required growl container is missing
  // or a notification message is not provided.
  if ( !growl || !msg ) {
    return;
  }

  // Show the growl.
  addText( msg, growl );
  makeVisible( growl, true );

  // Clear and hide the growl.
  setTimeout( () => {
    makeVisible( growl, false );
    clearText( growl );
  }, 1500 );
};

/**
 * Update the publication status indicators.
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
