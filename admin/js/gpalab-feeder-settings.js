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
    $( '#gpalab-feeder-test-connection' ).on( 'click', testConnection );
    $( '#gpalab-feeder-resync' ).on( 'click', resyncStart( 0 ) );
    $( '#gpalab-feeder-resync-errors' ).on( 'click', resyncStart( 1 ) );
    $( '#gpalab-feeder-resync-control' ).on( 'click', resyncControl );
    $( '#gpalab-feeder-validate-sync' ).on( 'click', validateSync );

    sync.total = parseInt( syncTotals.total, 10 );
    sync.complete = parseInt( syncTotals.complete, 10 );
    sync.paused = syncTotals.paused === '1';
    if ( sync.paused ) {
      createProgress();
      updateProgress();
    }
  } );

  function resetHeartbeatTimer() {
    if ( lastHeartbeatTimer ) clearInterval( lastHeartbeatTimer );
    lastHeartbeat = 0;
    $( '#last-heartbeat' ).html( `${lastHeartbeat}s ago (usually every 15s)` );
    lastHeartbeatTimer = setInterval( () => {
      lastHeartbeat += 1;
      $( '#last-heartbeat' ).html( `${lastHeartbeat}s ago (usually every 15s)` );
    }, 1000 );
  }

  function validateSync() {
    let unpause = false;

    if ( !sync.paused ) {
      unpause = true;
      resyncControl();
    }
    clearProgress();
    $( '#es_output' ).text( '' );
    let html = '<div class="spinner is-active spinner-animation">';

    html += '<span class="spinner-text">Validating...</span>';
    html += '</div>';
    $( '.index-spinner' ).html( html );
    disableManage();
    $.ajax( {
      timeout: 120000,
      url: window.ajaxurl,
      type: 'POST',
      dataType: 'JSON',
      data: {
        action: 'gpalab_feeder_validate',
        security: nonce,
      },
      success( result ) {
        clearProgress();
        createProgress();
        $( '#es_output' ).text( JSON.stringify( result, null, 2 ) );
        if ( unpause ) resyncControl();
        else updateProgress();
      },
      error( result ) {
        console.error( result );
        clearProgress();
        createProgress();
        $( '#es_output' ).text( JSON.stringify( result, null, 2 ) );
        if ( unpause ) resyncControl();
        else updateProgress();
      },
    } ).always( enableManage );
  }

  /**
   * Send a basic request to the provided URL and print the response in the output container.
   */
  function testConnection() {
    $( '#es_output' ).text( '' );
    disableManage();
    $.ajax( {
      url: window.ajaxurl,
      type: 'POST',
      dataType: 'JSON',
      data: {
        action: 'gpalab_feeder_test',
        data: {
          method: 'GET',
          url: $( '#es_url' ).val(),
        },
        security: nonce,
      },
      success( result ) {
        $( '#es_output' ).text( JSON.stringify( result, null, 2 ) );
      },
      error( result ) {
        $( '#es_output' ).text( JSON.stringify( result, null, 2 ) );
      },
    } ).always( enableManage );
  }

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
   * Pause or resume the current sync process and update the UI accordingly.
   */
  function resyncControl() {
    if ( sync.paused ) {
      $( '#gpalab-feeder-resync-control' ).html( 'Pause Sync' );
      sync.paused = false;
      $( '#progress-bar' ).removeClass( 'paused' );
      $( '.spinner-text' ).html( 'Processing... Leaving this page will pause the resync.' );
      processQueue();
    } else {
      $( '#gpalab-feeder-resync-control' ).html( 'Resume Sync' );
      $( '#progress-bar' ).addClass( 'paused' );
      $( '.spinner-text' ).html( 'Paused.' );
      sync.paused = true;
    }
  }

  /**
   * Trigger backend processing of the next available Post in the sync queue
   * and relay the results to the result handler function.
   */
  function processQueue() {
    if ( sync.paused ) return;
    $.ajax( {
      type: 'POST',
      dataType: 'JSON',
      url: window.ajaxurl,
      data: {
        action: 'gpalab_feeder_next',
        security: nonce,
      },
      success( result ) {
        handleQueueResult( result );
      },
      error( result ) {
        console.error( result );
      },
    } );
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
    else if ( result.response ) $( '#es_output' ).prepend( `${JSON.stringify( result, null, 2 )}\r\n\r\n` );
  }

  /**
   * Add relevant markup for the progress bar and state UI/UX.
   */
  function createProgress() {
    let html = '<div class="spinner is-active spinner-animation">';

    html += `<span class="spinner-text">${
      sync.paused ? 'Paused.' : 'Processing... Leaving this page will pause the resync.'
    }</span> <span class="count"></span> <span class="current-post"></span>`;
    html += '</div>';
    $( '.index-spinner' ).html( html );
    $( '.progress-wrapper' ).html(
      `<div id="progress-bar" ${sync.paused ? 'class="paused"' : ''}><span></span></div>`,
    );
    $( '#gpalab-feeder-resync-control' )
      .html( sync.paused ? 'Resume Sync' : 'Pause Sync' )
      .show();
    $( '#es_output' ).empty();
  }

  /**
   * Update the pgoress bar and state UI using the local sync variable.
   */
  function updateProgress() {
    $( '.index-spinner .count' ).html( `${sync.complete} / ${sync.total}` );
    $( '#progress-bar span' ).animate( { width: `${( sync.complete / sync.total ) * 100}%` } );
    $( '.current-post' ).html(
      sync.post
        ? `Indexing post: ${
          sync.post.title ? sync.post.title : `${sync.post.type} post #${sync.post.post_id}`
        }`
        : '',
    );
  }

  /**
   * Remove progress bar and state UI.
   */
  function clearProgress() {
    sync.results = null;
    sync.post = null;
    $( '.index-spinner' ).empty();
    $( '.progress-wrapper' ).empty();
    $( '#gpalab-feeder-resync-control' ).hide();
  }

  /**
   * Disable the manage buttons.
   */
  function disableManage() {
    $( '.inside.manage-btns button' ).attr( 'disabled', true );
  }

  /**
   * Enable the manage buttons.
   */
  function enableManage() {
    $( '.inside.manage-btns button' ).attr( 'disabled', null );
  }
} )( jQuery );
