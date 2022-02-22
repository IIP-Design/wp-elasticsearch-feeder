import { ready } from './utils/document-ready';
import { initPostStatusEventListeners } from './utils/event-listeners';

/**
 * Set up the event listeners used by the Publish to Commons box once the page is loaded.
 */
ready( () => {
  initPostStatusEventListeners();
} );
