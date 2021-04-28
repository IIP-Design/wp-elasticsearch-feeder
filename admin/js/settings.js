import { ready } from './utils/document-ready';
import { settingsEventListeners } from './utils/settings-event-listeners';

/**
 * Set up the page event listeners once the page is loaded.
 */
ready( () => {
  settingsEventListeners();
} );
