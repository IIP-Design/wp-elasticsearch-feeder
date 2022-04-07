import { addToElement, disableManageButtons, emptyElement } from '../utils/manipulate-dom';
import { i18nize } from '../utils/i18n';
import { showSpinner } from '../utils/progress-bar';
import { getNonce, sendAdminAjax } from './helpers';

/**
 * Send a simple request to the CDP API to confirm that the connection is live.
 */
export const testConnection = async () => {
  const outputId = 'gpalab-feeder-output';
  const url = document.getElementById( 'gpalab-feeder-url-input' );

  showSpinner( true, i18nize( 'Testing connection...' ) );

  // Clear out any existing text in the response output section.
  emptyElement( outputId );

  // Disable buttons for the duration of the request.
  disableManageButtons( true );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_test' );
  formData.append( 'security', getNonce() );
  formData.append( 'url', url.value );
  formData.append( 'method', 'GET' );

  // Display response/error message in results output.
  const handleResponse = result => {
    addToElement( result, outputId );
  };

  // Re-enable all buttons and hide spinner.
  const reset = () => {
    disableManageButtons( false );
    showSpinner( false );
  };

  sendAdminAjax( formData, 'POST', handleResponse, handleResponse, reset );
};
