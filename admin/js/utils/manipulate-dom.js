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
 * Toggles the display property of an element.
 * @param {Object} node The DOM node to clear.
 * @param {boolean} visible Whether or not the element should be visible.
 * @param {string} alt The value to be used as the inverse of display none. Defaults to block.
 */
const makeVisible = ( node, visible, alt = 'block' ) => {
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
