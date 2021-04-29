import { onTickPost, requestStatus } from '../ajax/heartbeat';

/**
 * Adds event listeners used to update the publish status indicator.
 */
export const addEventListeners = () => {
  // Heartbeat events.
  jQuery( document ).on( 'heartbeat-send', requestStatus );
  jQuery( document ).on( 'heartbeat-tick', onTickPost );
};
