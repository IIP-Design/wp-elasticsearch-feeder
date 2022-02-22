import { initializeEventListener } from './utils/event-listeners';
import { initializeSync } from './ajax/sync';
import { ready } from './utils/document-ready';

// ( function( $ ) {

//   const { nonce, syncTotals } = window.gpalabFeederSettings;

//   /**
//    * Register click listener functions, load sync data from the injected variable, and
//    * update sync state if a sync was in progress.
//    */
//   $( window ).load( () => {
//     $( '#es_resync' ).on( 'click', resyncStart( 0 ) );
//     $( '#gpalab-feeder-resync-control' ).on( 'click', resyncControl );
//     $( '#reload_log' ).on( 'click', reloadLog );

//   /**
//    * Pause or resume the current sync process and update the UI accordingly.
//    */
//   function resyncControl() {
//     if ( sync.paused ) {
//       $( '#gpalab-feeder-resync-control' ).html( 'Pause Sync' );
//       sync.paused = false;
//       $( '#progress-bar' ).removeClass( 'paused' );
//       $( '.spinner-text' ).html( 'Processing... Leaving this page will pause the resync.' );
//       processQueue();
//     } else {
//       $( '#gpalab-feeder-resync-control' ).html( 'Resume Sync' );
//       $( '#progress-bar' ).addClass( 'paused' );
//       $( '.spinner-text' ).html( 'Paused.' );
//       sync.paused = true;
//     }
//   }

//   /**
//    * Update the progress bar and state UI using the local sync variable.
//    */
//   function updateProgress() {
//     $( '.index-spinner .count' ).html( `${sync.complete} / ${sync.total}` );
//     $( '#progress-bar span' ).animate( { width: `${( sync.complete / sync.total ) * 100}%` } );
//     $( '.current-post' ).html(
//       sync.post
//         ? `Indexing post: ${
//           sync.post.title ? sync.post.title : `${sync.post.type} post #${sync.post.post_id}`
//         }`
//         : '',
//     );
//   }
// } )( jQuery );

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
  initializeEventListener( sync );
} );
