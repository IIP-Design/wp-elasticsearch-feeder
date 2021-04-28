import { i18nize } from '../utils/i18n';
import { emptyElement, getNonce, sendAjax } from './helpers';

/**
 * Clear the text of the log textarea.
 */
const emptyLog = () => emptyElement( 'log-text' );

/**
 * Clears the log textarea.
 */
export const clearLogs = () => {
  // Generate request body as formData.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_clear_logs' );
  formData.append( 'security', getNonce() );

  // Clear the log textarea.
  const onSuccess = () => {
    emptyLog();
    alert( i18nize( 'Logs cleared.' ) );
  };

  // Report errors.
  const onError = err => {
    console.error( err );
    alert( i18nize( 'Communication error while truncating logs.' ) );
  };

  // Send request.
  sendAjax( formData, 'POST', onSuccess, onError );
};

/**
 * Loads the last 100 lines of callback log.
 */
export const reloadLog = () => {
  // Clear the log before proceeding.
  emptyLog();

  // Generate request body as formData.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_reload_log' );
  formData.append( 'security', getNonce() );

  // Write response to the log textarea.
  const onSuccess = result => {
    const logText = document.getElementById( 'log-text' );

    logText.textContent = result;
  };

  // Report errors.
  const onError = err => {
    console.error( err );
    alert( i18nize( 'Communication error while reloading log.' ) );
  };

  // Send request.
  sendAjax( formData, 'POST', onSuccess, onError );
};
