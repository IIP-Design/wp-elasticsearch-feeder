import { clearLog, reloadLog } from '../ajax/log';
import { onTickPost, onTickSettings, requestStatus, requestStatuses, resetHeartbeatTimer } from '../ajax/heartbeat';
import { resync, togglePause, validateSync } from '../ajax/sync';
import { testConnection } from '../ajax/test-connection';

/**
 * Initialize all event listeners for the settings page.
 */
export const initSettingsEventListeners = sync => {
  const testConnectionBtn = document.getElementById( 'gpalab-feeder-test-connection' );
  const resyncBtn = document.getElementById( 'gpalab-feeder-resync' );
  const fixErrorsBtn = document.getElementById( 'gpalab-feeder-fix-errors' );
  const validateSyncBtn = document.getElementById( 'gpalab-feeder-validate-sync' );
  const pauseResyncBtn = document.getElementById( 'gpalab-feeder-resync-control' );

  testConnectionBtn.addEventListener( 'click', testConnection );
  resyncBtn.addEventListener( 'click', () => resync( sync, false ) );
  fixErrorsBtn.addEventListener( 'click', () => resync( sync, true ) );
  pauseResyncBtn.addEventListener( 'click', () => togglePause( sync ) );
  validateSyncBtn.addEventListener( 'click', () => validateSync( sync ) );

  // Log section, these buttons are conditionally rendered.
  const clearLogBtn = document.getElementById( 'gpalab-feeder-clear-logs' );
  const reloadLogBtn = document.getElementById( 'gpalab-feeder-reload-log' );

  if ( clearLogBtn ) {
    clearLogBtn.addEventListener( 'click', clearLog );
  }

  if ( reloadLogBtn ) {
    reloadLogBtn.addEventListener( 'click', reloadLog );
  }

  // Heartbeat events.
  jQuery( document ).on( 'heartbeat-send', requestStatuses );
  jQuery( document ).on( 'heartbeat-tick', onTickSettings );

  // Initialize the heartbeat timer.
  resetHeartbeatTimer();
};

/**
 * Adds event listeners used to update the publish status indicator.
 */
export const initPostStatusEventListeners = () => {
  // Heartbeat events.
  jQuery( document ).on( 'heartbeat-send', requestStatus );
  jQuery( document ).on( 'heartbeat-tick', onTickPost );
};
