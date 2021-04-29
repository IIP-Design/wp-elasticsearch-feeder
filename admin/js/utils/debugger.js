/**
 * Retrieves post data from the Elasticsearch API for the current post.
 *
 * @param {string} url    The endpoint for the Elasticsearch API.
 * @param {string} token  The authorization token for the Elasticsearch API.
 * @returns {Object}      The post data returned from the Elasticsearch API.
 */
export const fetchDebugData = async ( url, token ) => {
  const options = {
    method: 'GET',
    headers: {
      Authorization: `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
  };

  let data;

  await fetch( url, options )
    .then( res => res.json() )
    .then( result => {
      data = {
        ...result,
        content: 'OMITTED',
      };
    } )
    .catch( err => {
      data = err;
    } );

  return data;
};
