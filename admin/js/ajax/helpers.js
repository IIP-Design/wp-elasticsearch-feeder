/**
 * Retrieve the security nonce used to authenticate AJAX requests.
 *
 * @returns {string} The localized nonce.
 */
export const getNonce = () => window?.gpalabFeederSettings?.feederNonce;

/**
  * Retrieve the path to the WordPress admin AJAX file.
  *
  * @returns {string} The admin AJAX path relative to the site root.
  */
const getAdminAjax = () => window?.ajaxurl;

/**
  * Wrap a promise with a timeout.
  *
  * @param {Integer} time Length of the timeout in milliseconds.
  * @param {Promise} promise The promise to br wrapped in a timeout.
  */
const withTimeout = ( time, promise ) => new Promise( ( resolve, reject ) => {
  const timer = setTimeout( () => {
    reject( new Error( 'Request timed out.' ) );
  }, time );

  promise
    .then( resolve, reject )
    .finally( () => clearTimeout( timer ) );
} );

/**
  * Sends a fetch request to the WordPress admin AJAX.
  *
  * @param {FormData} data The body of the request as a FormData object.
  * @param {string} method The request method (i.e. GET, POST).
  * @param {function} onSuccess A callback function to be called on success.
  * @param {function} onError A callback function to be called on error.
  * @param {function} onFinally A callback function to be called regardless of result.
  */
export const sendAdminAjax = ( data, method, onSuccess, onError, onFinally ) => {
  const cleanup = onFinally || ( () => {} );

  fetch( getAdminAjax(), {
    method,
    body: data,
  } )
    .then( response => response.json() )
    .then( result => {
      onSuccess( result );
    } )
    .catch( err => {
      onError( err );
    } )
    .finally( () => cleanup() );
};

/**
  * Sends a time-bound fetch request to the WordPress admin AJAX.
  * If the request is not resolved within the allowed time it will be cancelled.
  *
  * @param {FormData} data The body of the request as a FormData object.
  * @param {string} method The request method (i.e. GET, POST).
  * @param {function} onSuccess A callback function to be called on success.
  * @param {function} onError A callback function to be called on error.
  * @param {function} onFinally A callback function to be called regardless of result.
  * @param {int} time The length of time, in milliseconds, to allow before cancelling the request.
  */
export const sendAdminAjaxWithTimeout = ( data, method, onSuccess, onError, onFinally, time ) => {
  const cleanup = onFinally || ( () => {} );

  const controller = new AbortController();
  const { signal } = controller;

  withTimeout(
    time,
    fetch( getAdminAjax(), {
      method,
      body: data,
      signal,
    } ),
  )
    .then( response => response.json() )
    .then( result => {
      onSuccess( result );
    } )
    .catch( err => {
      onError( err );
      controller.abort();
    } )
    .finally( () => cleanup() );
};
