<?php

global $cdp_owner_helper;
$cdp_owner_helper = new Owner_Helper();

class Owner_Helper {

  public $owners;

  /**
   * Retrieves the allowed owner list from the API and populates an array for use
   * in the owner dropdown.
   *
   * @return array
   */
  public static function get_owners() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
      $owners = get_option( 'cdp_owners' );
      if ( $owners ) {
        return $owners;
      }
    }
    global $feeder;
    if ( ! $feeder ) {
      return array();
    }
    $owners = array( '' );
    $args   = array(
      'method' => 'GET',
      'url'    => 'owner',
    );
    $data   = $feeder->es_request( $args );
    if ( $data && count( $data ) && ! is_string( $data )
      && ( ! is_array( $data ) || ! array_key_exists( 'error', $data ) || ! $data['error'] )
      && ( ! is_object( $data ) || ! $data->error ) ) {
      foreach ( $data as $owner ) {
        $owners[ $owner->name ] = $owner->name;
      }
    }
    update_option( 'cdp_owners', $owners );
    return $owners;
  }

}
