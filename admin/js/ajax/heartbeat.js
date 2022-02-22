import { updatePostStatus, updateStatuses, updateTicker } from '../utils/manipulate-dom';

/**
 * Heartbeat object used to maintain the state of the status indicators.
 */
const heartbeat = {
  lastHeartbeat: null,
  heartbeatTimer: null,
  clear() {
    this.lastHeartbeat = 0;
    clearInterval( this.heartbeatTimer );
  },
  increment() { this.lastHeartbeat += 2; },
  get beat() { return this.lastHeartbeat; },
  get timer() { return this.heartbeatTimer; },
  set newTimer( timeout ) { this.heartbeatTimer = timeout; },
};

// ---- Functions pertaining to the settings page. ---- //

/**
 * Appends the gpalab_feeder_count property to the data sent to the server by the heartbeat API.
 * This initiates a DB query to retrieve the publication statuses and provide the on the return tick.
 *
 * @param {Event} _ The event object, unused.
 * @param {Object} data The page values forwarded to the server.
 */
export const requestStatuses = ( _, data ) => { data.gpalab_feeder_count = 1; };

/**
 * Clear and restart the timer which shows the seconds since last update from the server.
 */
export const resetHeartbeatTimer = () => {
  heartbeat.clear();

  updateTicker( heartbeat.beat );

  // Increment the time elapsed indicator every two seconds.
  heartbeat.newTimer = setInterval( () => {
    heartbeat.increment();
    updateTicker( heartbeat.beat );
  }, 2000 );
};

/**
 * Update the status indicators upon response from the heartbeat API.
 *
 * @param {Event} _ The event object, unused.
 * @param {Object} data The response received from the server.
 */
export const onTickSettings = ( _, data ) => {
  // Restart the time since heartbeat ticker.
  resetHeartbeatTimer();

  // Exit early if no count is returned from the server.
  if ( !data.gpalab_feeder_count ) {
    return;
  }

  // Update the publish statuses on the page.
  updateStatuses( data.gpalab_feeder_count );
};

// ---- Functions pertaining to the indexable post edit screens. ---- //

/**
 * Appends the gpalab_feeder_post_id property to the data sent to the server by the heartbeat API.
 * This initiates a DB query to retrieve the publication status of the given post and provide the on the return tick.
 *
 * @param {Event} _ The event object, unused.
 * @param {Object} data The page values forwarded to the server.
 */
export const requestStatus = ( _, data ) => {
  const id = window?.gpalabFeederSyncStatus?.postId;

  if ( id ) {
    data.gpalab_feeder_post_id = id;
  }
};

/**
 * Update the post's status indicator upon response from the heartbeat API.
 *
 * @param {Event} _ The event object, unused.
 * @param {Object} data The response received from the server.
 */
export const onTickPost = ( _, data ) => {
  // Exit early if no status is returned from the server.
  if ( !data.gpalab_feeder_sync_codes ) {
    return;
  }

  const { color, title } = data.gpalab_feeder_sync_codes;

  // Update the publish statuses on the page.
  updatePostStatus( color, title );
};
