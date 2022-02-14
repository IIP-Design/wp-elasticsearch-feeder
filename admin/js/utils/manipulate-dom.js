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

