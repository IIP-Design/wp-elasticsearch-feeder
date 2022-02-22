/**
 * Retrieves post data from the Elasticsearch API for the current post.
 *
 * @param {string} url    The endpoint for the Elasticsearch API.
 * @param {string} token  The authorization token for the Elasticsearch API.
 * @returns {Object}      The post data returned from the Elasticsearch API.
 */
export const fetchDebugData = async () => {
  const { apiVars, feederNonce } = window.gpalabFeederAdmin;

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_debug' );
  formData.append( 'method', 'GET' );
  formData.append( 'security', feederNonce );
  formData.append( 'url', apiVars.endpoint );

  try {
    const response = await fetch( window.ajaxurl, {
      method: 'POST',
      body: formData,
    } );

    const result = await response.json();

    const data = ( typeof result === 'object' )
      ? JSON.stringify( {
        ...result,
        content: 'OMITTED',
      }, null, 2 )
      : result;

    return data;
  } catch ( err ) {
    // Display error message in log.
    return err;
  }
};
