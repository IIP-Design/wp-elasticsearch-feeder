import { i18nize } from '../utils/i18n';
import { getNonce, sendAjax } from './helpers';

/**
 * Clear the text of the log textarea.
 */
const emptyLog = () => {
  const logText = document.getElementById( 'log-text' );

  // Recursively remove child elements.
  while ( logText.firstChild ) logText.removeChild( logText.firstChild );
};

/**
 * Clears the log textarea.
 */
export const clearLogs = () => {
  // Generate request body as formData.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_clear_logs' );
  formData.append( 'security', getNonce() );

  // Clear the log textarea.
  const successFunc = () => {
    emptyLog();
    alert( i18nize( 'Logs cleared.' ) );
  };

  // Report errors.
  const errorFunc = err => {
    console.error( err );
    alert( i18nize( 'Communication error while truncating logs.' ) );
  };

  // Send request.
  sendAjax( formData, 'POST', successFunc, errorFunc );
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
  const successFunc = result => {
    const logText = document.getElementById( 'log-text' );

    logText.textContent = result;
  };

  // Report errors.
  const errorFunc = err => {
    console.error( err );
    alert( i18nize( 'Communication error while reloading log.' ) );
  };

  // Send request.
  sendAjax( formData, 'POST', successFunc, errorFunc );
};
