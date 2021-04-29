import { ready } from './utils/document-ready';
import { addEventListeners } from './event-listeners/settings-page';

/**
 * Set up the event listeners required by the settings page once the page is loaded.
 */
ready( () => {
  const syncStatus = () => {
    const sync = {
      complete: 0,
      total: 0,
      paused: false,
      post: null,
      results: null,
    };

    const updateSync = ( key, val ) => {
      sync[key] = val;
    };

    return {
      isPaused: () => sync.paused,
      update: ( key, val ) => updateSync( key, val ),
      value: () => sync,
    };
  };

  addEventListeners( syncStatus );
} );
