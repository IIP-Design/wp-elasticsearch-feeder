( function( $ ) {
  const { gpalabFeederSyncStatus } = window;

  $( document ).on( 'heartbeat-send', ( event, data ) => {
    if ( gpalabFeederSyncStatus.postId ) data.es_sync_status = gpalabFeederSyncStatus.postId;
  } );
  $( document ).on( 'heartbeat-tick', ( event, data ) => {
    if ( !data.es_sync_status ) return;
    $( '#cdp_sync_status' ).html( data.es_sync_status );
  } );

  $( document ).ready( () => {
    $( '#cdp-terms' ).chosen( { width: '100%' } );
    toggleTaxBox();
    $( 'input[name=cdp_index_opt]' ).change( toggleTaxBox );
  } );

  function toggleTaxBox() {
    if ( $( '#index_cdp_yes' ).is( ':checked' ) ) {
      $( '#cdp-taxonomy' ).show();
    } else {
      $( '#cdp-taxonomy' ).hide();
    }
  }
} )( jQuery );
