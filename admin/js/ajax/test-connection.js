import { addText, clearText, disableManageButtons } from '../utils/manipulate-dom';
import { showSpinner } from '../utils/progress-bar';

/**
 * Send a simple request to the CDP API to confirm that the connection is live.
 */
export const testConnection = async () => {
  const { feederNonce } = window.gpalabFeederSettings;

  const output = document.getElementById( 'gpalab-feeder-output' );
  const url = document.getElementById( 'gpalab-feeder-url-input' );

  showSpinner( true, 'Testing connection...' );

  // Clear out any existing text in the response output section.
  clearText( output );

  // Disable buttons for the duration of the request.
  disableManageButtons( true );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_test' );
  formData.append( 'security', feederNonce );
  formData.append( 'url', url.value );
  formData.append( 'method', 'GET' );

  try {
    const response = await fetch( window.ajaxurl, {
      method: 'POST',
      body: formData,
    } );

    const result = await response.json();

    // Display test response in results output.
    addText( JSON.stringify( result, null, 2 ), output );
  } catch ( err ) {
    // Display error message in results output.
    addText( JSON.stringify( err, null, 2 ), output );
  } finally {
    // Re-enable all buttons and hide spinner.
    disableManageButtons( false );
    showSpinner( false );
  }
};
