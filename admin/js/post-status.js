import { ready } from './utils/document-ready';
import { addEventListeners } from './event-listeners/indexable-post';

/**
 * Set up the event listeners used by the Publish to Commons box once the page is loaded.
 */
ready( () => {
  addEventListeners();
} );
