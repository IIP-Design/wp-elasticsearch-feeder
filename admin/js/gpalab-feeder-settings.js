( function( $ ) {
  // sync object contains data related to the current (if any) resync
  let sync = {
    total: 0,
    complete: 0,
    post: null,
    paused: false,
    results: null,
  };

  const { nonce, syncTotals } = window.gpalabFeederSettings;

  /**
   * Register click listener functions, load sync data from the injected variable, and
   * update sync state if a sync was in progress.
   */
  $( window ).load( () => {
    $( '#gpalab-feeder-resync' ).on( 'click', resyncStart( 0 ) );
    $( '#gpalab-feeder-resync-errors' ).on( 'click', resyncStart( 1 ) );

    sync.total = parseInt( syncTotals.total, 10 );
    sync.complete = parseInt( syncTotals.complete, 10 );
    sync.paused = syncTotals.paused === '1';
    if ( sync.paused ) {
      createProgress();
      updateProgress();
    }
  } );

  /**
   * TODO: Initiate a new sync by deleting ALL of this site's posts from ES
   * Clear out old sync post meta (if any) and initiate a new sync process.
   */
  function resyncStart( errorsOnly ) {
    return function() {
      const $notice = $( '.feeder-notice.notice-error' );

      if ( $notice.length > 0 ) {
        $notice.fadeTo( 100, 0, () => {
          $notice.slideUp( 100, () => {
            $notice.remove();
          } );
        } );
      }
      sync = {
        total: 0,
        complete: 0,
        post: null,
        paused: false,
      };
      createProgress();
      updateProgress();
      disableManage();
      $.ajax( {
        timeout: 120000,
        url: window.ajaxurl,
        type: 'POST',
        dataType: 'JSON',
        data: {
          action: 'gpalab_feeder_sync_init',
          security: nonce,
          sync_errors: errorsOnly,
        },
        success( result ) {
          handleQueueResult( result );
        },
        error( result ) {
          console.error( result );
          clearProgress();
        },
      } ).always( enableManage );
    };
  }

  /**
   * Store result data in the local variable and update the state and progress bar,
   * and spew the raw result into the output container.
   *
   * @param result
   */
  function handleQueueResult( result ) {
    console.log( result );
    if ( result.error || result.done ) {
      clearProgress();
      if ( result.error && result.message ) $( '#gpalab-feeder-output' ).html( result.message );
      reloadLog();
    } else {
      sync.complete = result.complete;
      sync.total = result.total;
      if ( result.response ) {
        sync.post = result.response.req;
        sync.results = null;
      } else if ( result.results ) {
        sync.results = result.results;
        sync.post = null;
      } else {
        sync.results = null;
        sync.post = null;
      }
      updateProgress();
      processQueue();
    }
    if ( result.results ) $( '#gpalab-feeder-output' ).html(
      result.results.length > 0 ? JSON.stringify( result.results, null, 2 ) : 'No errors.',
    );
    else if ( result.response ) $( '#gpalab-feeder-output' ).prepend( `${JSON.stringify( result, null, 2 )}\r\n\r\n` );
  }
} )( jQuery );
