/**
 * Retrieve the security nonce used to authenticate AJAX requests.
 *
 * @returns {string} The localized nonce.
 */
export const getNonce = () => window?.gpalabFeederSettings?.nonce;

/**
 * Sends a fetch request to the WordPress admin AJAX.
 *
 * @param {FormData} data The body of the request as a FormData object.
 * @param {string} method The request method (i.e. GET, POST).
 * @param {function} successFunc A callback function to be called on success.
 * @param {function} errorFunc A callback function to be called on error.
 */
export const sendAjax = ( data, method, successFunc, errorFunc ) => {
  // Get the localized AJAX endpoint from WordPress.
  const { ajaxurl } = window;

  fetch( ajaxurl, {
    method,
    body: data,
  } )
    .then( response => response.json() )
    .then( result => {
      successFunc( result );
    } )
    .catch( err => {
      errorFunc( err );
    } );
};
