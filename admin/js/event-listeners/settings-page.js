import { clearLogs, reloadLog } from '../ajax/log';
import { onTickSettings, requestStatuses, resetHeartbeatTimer } from '../ajax/heartbeat';
import { testConnection, togglePaused, validateSync } from '../ajax/sync';

/**
 * Adds event listeners required to run the settings.
 */
export const addEventListeners = syncStatus => {
  const { isPaused, update } = syncStatus();

  // Manage section buttons.
  const testBtn = document.getElementById( 'gpalab-feeder-test-connection' );
  // const errorsBtn = document.getElementById( 'gpalab-feeder-resync-errors' );
  const validateBtn = document.getElementById( 'gpalab-feeder-validate-sync' );
  const controlBtn = document.getElementById( 'gpalab-feeder-resync-control' );
  // const resyncBtn = document.getElementById( 'gpalab-feeder-resync' );

  testBtn.addEventListener( 'click', testConnection );
  // errorsBtn.addEventListener( 'click', resyncStart( 0 ) );
  validateBtn.addEventListener( 'click', () => validateSync( syncStatus ) );
  controlBtn.addEventListener( 'click', () => togglePaused( isPaused(), update ) );
  // resyncBtn.addEventListener( 'click', resyncStart( 1 ) );

  // Log section buttons.
  const clearBtn = document.getElementById( 'gpalab-feeder-clear-logs' );
  const reloadBtn = document.getElementById( 'gpalab-feeder-reload-log' );

  clearBtn.addEventListener( 'click', clearLogs );
  reloadBtn.addEventListener( 'click', reloadLog );

  // Heartbeat events.
  jQuery( document ).on( 'heartbeat-send', requestStatuses );
  jQuery( document ).on( 'heartbeat-tick', onTickSettings );

  // Initialize the heartbeat timer.
  resetHeartbeatTimer();
};
