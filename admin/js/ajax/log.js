import { addToElement, disableManageButtons, emptyElement, showGrowl } from '../utils/manipulate-dom';
import { i18nize } from '../utils/i18n';
import { getNonce, sendAdminAjax } from './helpers';

/**
 * Shared cleanup function to run after AJAX response or rejection.
 * It re-enable all buttons.
 */
const reset = () => { disableManageButtons( false ); };

/**
 * The id for the log outputs HTML element.
 */
const LOG_ID = 'log-text';

/**
 * Clears the log output on the settings page.
 */
export const clearLog = async () => {
  // Disable buttons for the duration of the request.
  disableManageButtons( true );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_clear_logs' );
  formData.append( 'security', getNonce() );

  const onSuccess = () => {
    // Clear out any existing text in the log output section.
    emptyElement( LOG_ID );
    showGrowl( i18nize( 'Logs cleared.' ) );
  };

  const onError = err => {
    console.error( err );
    showGrowl( i18nize( 'Communication error while clearing logs.' ) );
  };

  sendAdminAjax( formData, 'POST', onSuccess, onError, reset );
};

/**
 * Loads the last 100 lines of callback.log
 */
export const reloadLog = async () => {
  // Clear out any existing text in the response output section.
  emptyElement( LOG_ID );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_reload_log' );
  formData.append( 'security', getNonce() );

  // Display response/error message in results output.
  const handleResponse = result => {
    addToElement( result, LOG_ID );
  };

  sendAdminAjax( formData, 'POST', handleResponse, handleResponse, reset );
};
