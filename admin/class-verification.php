<?php
/**
 * Registers the Verification class.
 *
 * @package ES_Feeder\Admin\Verification
 * @since 3.0.0
 */

namespace ES_Feeder\Admin;

/**
 * Methods used to ensure data integrity and conduct security checks.
 *
 * @package ES_Feeder\Admin\Verification
 * @since 3.0.0
 */
class Verification {

  /**
   * Checks that a security nonce is set, valid, and from a permitted referrer.
   *
   * @param string $security    A nonce provided in the Ajax call.
   *
   * @since 3.0.0
   */
  public function lab_verify_nonce( $security ) {
    $nonce = null;

    // Make sure nonce is set.
    if ( ! isset( $security ) ) {
      wp_send_json_error(
        array( 'message' => __( 'Nonce not set', 'gpalab-feeder' ) ),
        403
      );
    } else {
      $nonce = sanitize_text_field( wp_unslash( $security ) );
    }

    // Verify the nonce.
    if (
      wp_verify_nonce( $nonce, 'gpalab-feeder-nonce' ) === false ||
      check_ajax_referer( 'gpalab-feeder-nonce', 'security', false ) === false
    ) {
      wp_send_json_error(
        array( 'message' => __( 'Invalid nonce', 'gpalab-feeder' ) ),
        403
      );
    }
  }

  /**
   * Sanitize the API data provided in settings page before sending a CDP API request.
   *
   * @param array $data   The request data pulled off of $_POST.
   * @return array        The sanitized data.
   *
   * @since 3.0.0
   */
  public function sanitize_test_connect_data( $data ) {
    $unslashed = isset( $data ) ? wp_unslash( $data ) : array();

    $sanitized = array();

    if ( ! empty( $unslashed['method'] ) ) {
      $sanitized['method'] = sanitize_text_field( $unslashed['method'] );
    }

    if ( ! empty( $unslashed['url'] ) ) {
      $sanitized['url'] = sanitize_text_field( $unslashed['url'] );
    }

    return $sanitized;
  }

  /**
   * Sanitize the API data provided in settings page before initializing a sync.
   *
   * @param array $data   The request data pulled off of $_POST.
   * @return array        The sanitized data.
   *
   * @since 3.0.0
   */
  public function sanitize_init_sync_data( $data ) {
    $unslashed   = isset( $data ) ? wp_unslash( $data ) : array();
    $sync_errors = false;

    if ( ! empty( $unslashed['sync_errors'] ) ) {
      $sync_errors = rest_sanitize_boolean( $unslashed['sync_errors'] );
    }

    return $sync_errors;
  }
}
