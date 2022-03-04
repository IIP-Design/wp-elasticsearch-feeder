import { initSettingsEventListeners } from './utils/event-listeners';
import { initializeSync } from './ajax/sync';
import { ready } from './utils/document-ready';

/**
 * Set up the page event listeners once the page is loaded.
 */
ready( () => {
  // Sync object contains data related to the current (if any) resync.
  const sync = {
    total: 0,
    complete: 0,
    post: null,
    paused: false,
    results: null,
  };

  initializeSync( sync );
  initSettingsEventListeners( sync );
} );
