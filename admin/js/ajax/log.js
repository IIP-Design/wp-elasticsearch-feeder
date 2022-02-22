import { addText, clearText, disableManageButtons, showGrowl } from '../utils/manipulate-dom';

/**
 * Clears the log output on the settings page.
 */
export const clearLog = async () => {
  const { feederNonce } = window.gpalabFeederSettings;

  const logText = document.getElementById( 'log-text' );

  // Disable buttons for the duration of the request.
  disableManageButtons( true );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_clear_logs' );
  formData.append( 'security', feederNonce );

  try {
    await fetch( window.ajaxurl, {
      method: 'POST',
      body: formData,
    } );

    // Clear out any existing text in the log output section.
    clearText( logText );
    showGrowl( 'Logs cleared.' );
  } catch ( err ) {
    console.error( err );
    showGrowl( 'Communication error while clearing logs.' );
  } finally {
    // Re-enable all buttons.
    disableManageButtons( false );
  }
};

/**
 * Loads the last 100 lines of callback.log
 */
export const reloadLog = async () => {
  const { feederNonce } = window.gpalabFeederSettings;

  const logText = document.getElementById( 'log-text' );

  // Clear out any existing text in the response output section.
  clearText( logText );

  // Prepare the API request body.
  const formData = new FormData();

  formData.append( 'action', 'gpalab_feeder_reload_log' );
  formData.append( 'security', feederNonce );

  try {
    const response = await fetch( window.ajaxurl, {
      method: 'POST',
      body: formData,
    } );

    const result = await response.json();

    // Display result message in log.
    addText( JSON.stringify( result, null, 2 ), logText );
  } catch ( err ) {
    // Display error message in log.
    addText( JSON.stringify( err, null, 2 ), logText );
  } finally {
    // Re-enable all buttons.
    disableManageButtons( false );
  }
};
