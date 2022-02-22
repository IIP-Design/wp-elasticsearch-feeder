import { clearLog, reloadLog } from '../ajax/log';
import { initStatuses, onTick, resetHeartbeatTimer } from '../ajax/heartbeat';
import { resync, validateSync } from '../ajax/sync';
import { testConnection } from '../ajax/test-connection';

/**
 * Initialize all event listeners for the settings page.
 */
export const initializeEventListener = sync => {
  const clearLogBtn = document.getElementById( 'gpalab-feeder-clear-logs' );
  const testConnectionBtn = document.getElementById( 'gpalab-feeder-test-connection' );
  const resyncBtn = document.getElementById( 'gpalab-feeder-resync' );
  const fixErrorsBtn = document.getElementById( 'gpalab-feeder-fix-errors' );
  const resyncControl = document.getElementById( 'gpalab-feeder-resync-control' );
  const validateSyncBtn = document.getElementById( 'gpalab-feeder-validate-sync' );
  const reloadLogBtn = document.getElementById( 'gpalab-feeder-reload-log' );

  clearLogBtn.addEventListener( 'click', clearLog );
  testConnectionBtn.addEventListener( 'click', testConnection );
  resyncBtn.addEventListener( 'click', () => resync( sync, false ) );
  fixErrorsBtn.addEventListener( 'click', () => resync( sync, true ) );
  resyncControl.addEventListener( 'click', resyncControl );
  validateSyncBtn.addEventListener( 'click', () => validateSync( sync ) );
  reloadLogBtn.addEventListener( 'click', reloadLog );

  // Heartbeat events.
  jQuery( document ).on( 'heartbeat-send', initStatuses );
  jQuery( document ).on( 'heartbeat-tick', onTick );

  // Initialize the heartbeat timer.
  resetHeartbeatTimer();
};
