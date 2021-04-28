import { clearLogs, reloadLog } from '../ajax/log';

/**
 * Adds event listeners required to run the settings page tabbed container.
 */
export const settingsEventListeners = () => {
  // Manage section buttons.
  // const testBtn = document.getElementById( 'gpalab-feeder-test-connection' );
  // const queryIndexBtn = document.getElementById( 'gpalab-feeder-query-index' );
  // const errorsBtn = document.getElementById( 'gpalab-feeder-resync-errors' );
  // const validateBtn = document.getElementById( 'gpalab-feeder-validate-sync' );
  // const controlBtn = document.getElementById( 'gpalab-feeder-resync-control' );
  // const resyncBtn = document.getElementById( 'gpalab-feeder-resync' );

  // testBtn.addEventListener( 'click', testConnection() );
  // queryIndexBtn.addEventListener( 'click', queryIndex() );
  // errorsBtn.addEventListener( 'click', resyncStart( 0 ) );
  // validateBtn.addEventListener( 'click', resyncStart( 1 ) );
  // controlBtn.addEventListener( 'click', resyncControl() );
  // resyncBtn.addEventListener( 'click', validateSync() );

  // Log section buttons.
  const clearBtn = document.getElementById( 'gpalab-feeder-clear-logs' );
  const reloadBtn = document.getElementById( 'gpalab-feeder-reload-log' );

  clearBtn.addEventListener( 'click', clearLogs );
  reloadBtn.addEventListener( 'click', reloadLog );
};
