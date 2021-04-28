export const sendAjax = ( data, method, successFunc, errorFunc ) => {
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
