<?php
/**
 * Registers the Post_Actions class.
 *
 * @package ES_Feeder\Post_Actions
 * @since 3.0.0
 */

namespace ES_Feeder;

use Exception, GuzzleHttp;

/**
 * Handles Post_Actions calls needed to persist data on the server.
 *
 * @package ES_Feeder\Post_Actions
 * @since 3.0.0
 */
class Post_Actions {

  /**
   * The unique identifier this plugin.
   *
   * @var string $plugin
   *
   * @access protected
   * @since 3.0.0
   */
  protected $plugin;

  /**
   * Initializes the class with the plugin name and version.
   *
   * @since 3.0.0
   */
  public function __construct() {
    $this->plugin = ES_FEEDER_NAME;
  }

  /**
   * Update a given post's CDP-related metadata.
   *
   * @param int     $id      WordPress post id.
   * @param WP_Post $post    WordPress post object.
   *
   * @since 1.0.0
   */
  public function save_post( $id, $post ) {
    // Only proceed on intentional save/update action.
    $is_autosave = wp_is_post_autosave( $id );
    $is_revision = wp_is_post_revision( $id );

    if ( $is_autosave || $is_revision ) {
      return;
    }

    // Prevent the sync from occurring twice since Gutenberg
    // uses the Rest API to update/insert the post data.
    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
      return;
    }

    // Return early if not a post type set to be indexed to the CDP.
    if ( ! $this->check_if_indexable_post_type( $post ) ) {
      return;
    }

    /**
     * Update the post metadata if post is not using Gutenberg editor.
     *
     * Nonce verification occurs within the legacy_meta_update function
     * so we can safely ignore it here.
     *
     * phpcs:disable WordPress.Security.NonceVerification.Missing
     */
    if ( isset( $_POST['security'] ) ) {
      $this->legacy_meta_update( $id, $_POST );
    }
    // phpcs:enable

    // We only care about modifying published posts.
    if ( 'publish' === $post->post_status ) {
      $post_helper = new Admin\Helpers\Post_Helper();

      $post_helper->post_sync_send( $post, false );

      // Update translations (to add a connection to this post).
      $this->translate_post( $post );
    }
  }

  /**
   * Check the for updates to post's the metadata.
   *
   * This will only run when using legacy metaboxes since the
   * Gutenberg saves metadata in a different fashion.
   *
   * @param int   $id          WordPress post id.
   * @param array $post_data   The post data returned in the $_POST array.
   *
   * @since 3.0.0
   */
  private function legacy_meta_update( $id, $post_data ) {
    // Check security nonce before updating metadata.
    $verification = new \ES_Feeder\Admin\Verification();
    $verification->lab_verify_nonce( $post_data['security'] );

    if ( array_key_exists( 'cdp_index_opt', $post_data ) ) {
      $sanitized_index = sanitize_text_field( $post_data['cdp_index_opt'] );

      update_post_meta( $id, '_iip_index_post_to_cdp_option', $sanitized_index );
    }

    if ( array_key_exists( 'cdp_language', $post_data ) ) {
      $sanitized_lang = sanitize_text_field( $post_data['cdp_language'] );

      update_post_meta( $id, '_iip_language', $sanitized_lang );
    }

    if ( array_key_exists( 'cdp_owner', $post_data ) ) {
      $sanitized_owner = sanitize_text_field( $post_data['cdp_owner'] );

      update_post_meta( $id, '_iip_owner', $sanitized_owner );
    }

    if ( array_key_exists( 'cdp_terms', $post_data ) ) {
      // TODO: sanitize terms array. Need to confirm the array shape before doing this.
      update_post_meta( $id, '_iip_taxonomy_terms', $post_data['cdp_terms'] );
    } elseif ( $post_data && is_array( $post_data ) ) {
      update_post_meta( $id, '_iip_taxonomy_terms', array() );
    }
  }

  /**
   * Delete the given post from the CDP API.
   *
   * @param string      $new_status   The new post status.
   * @param string      $old_status   Current post status.
   * @param int|WP_Post $post         A WordPress post id or post object.
   *
   * @since 1.0.0
   */
  public function delete_post( $new_status, $old_status, $post ) {
    $post_helper = new Admin\Helpers\Post_Helper();

    // Only delete posts if the old status was 'publish'.
    if ( $old_status === $new_status || 'publish' !== $old_status ) {
      return;
    }

    // Get the post data if only the id is provided.
    if ( ! is_object( $post ) ) {
      $post = get_post( $post );
    }

    // Return early if not a post type set to be indexed to the CDP.
    if ( ! $this->check_if_indexable_post_type( $post ) ) {
      return;
    }

    $post_helper->delete( $post );

    // Update translations (to remove the connection to this post).
    $this->translate_post( $post );
  }

  /**
   * Fire PUT requests containing associated translations after save_post.
   *
   * @param int|WP_Post $post   A WordPress post id or post object.
   *
   * @since 2.1.0
   */
  private function translate_post( $post ) {
    $language_helper = new Admin\Helpers\Language_Helper();
    $log_helper      = new Admin\Helpers\Log_Helper();
    $post_helper     = new Admin\Helpers\Post_Helper();
    $sync_helper     = new Admin\Helpers\Sync_Helper();

    // Get the post data if only the id is provided.
    if ( ! is_object( $post ) ) {
      $post = get_post( $post );
    }

    $post_type = $post->post_type;
    $post_id   = $post->ID;

    $log_helper->log( "Looking for translations for $post_type #$post_id" );

    // Retrieve the translations.
    $translations = $language_helper->get_translations( $post_id );

    // Exit the function if no translations found.
    if ( 0 === count( $translations ) ) {
      $log_helper->log( "No translations found for $post_type #$post_id" );

      return;
    } else {
      // Get list of translation post ids.
      $list = implode( ', ', array_column( $translations, 'post_id' ) );

      $log_helper->log( 'Found ' . count( $translations ) . " translations for $post_type #$post_id: ( $list )" );
    }

    // Loop through the translations, syncing each one.
    foreach ( $translations as $trans ) {
      $item = get_post( $trans['post_id'] );

      // Skip translations if not published in WP.
      if ( 'publish' !== $item->post_status ) {
        continue;
      }

      // Skip if translation is set to not index to the CDP.
      $sync = get_post_meta( $item->ID, '_iip_index_post_to_cdp_option', true );

      if ( 'no' === $sync ) {
        continue;
      }

      // Skip if translation is in a non-syncable state.
      if ( ! $sync_helper->is_syncable( $item->ID ) ) {
        continue;
      }

      // If all checks pass, sync the translation.
      $post_helper->post_sync_send( $item, false );
    }

    unset( $trans );
  }

  /**
   * Send an indexing request to the CDP API.
   *
   * @param array   $request        Options to be used when sending the CDP API request.
   * @param string  $callback       The destination for CDP API request responses.
   * @param boolean $errors_only    Whether to only use callback for errors.
   * @param boolean $is_internal    Whether the origin is a WP AJAX (as opposed to direct) request.
   *
   * @since 1.0.0
   */
  public function request( $request, $callback = null, $errors_only = false, $is_internal = true ) {
    $log_helper = new Admin\Helpers\Log_Helper();

    // Initialize response.
    $error    = null;
    $response = null;

    // Set request headers.
    $headers = $this->set_request_headers( $callback, $errors_only );

    // Initialize the Guzzle client, which is used to
    // send HTTP requests, with the relevant headers.
    $client = new GuzzleHttp\Client(
      array(
        'timeout'     => 30,
        'http_errors' => false,
      )
    );

    // Get the target request URL.
    $endpoint = $this->set_request_endpoint( $is_internal, $request['url'] );

    $log_helper->log( 'Sending ' . $request['method'] . ' request to the endpoint: ' . $endpoint );

    try {
      // Initialize request options.
      $options = array();

      // Set the body in the request options if required.
      $body = $this->set_request_body( $request, $is_internal );

      if ( null !== $body ) {
        $options['body'] = $body;

        // Non-direct API requests require an additional header.
        if ( $is_internal ) {
          $headers['Content-Type'] = 'application/json';
        }
      }

      // Set the headers in the request options.
      $options['headers'] = $headers;

      // Send the HTTP request to the CDP API.
      $res = $client->request( $request['method'], $endpoint, $options );

      // Log the response.
      $log_helper->log(
        $callback . ' Received response: "' . $res->getStatusCode() . ' - ' . $res->getReasonPhrase() . '" from ' . $endpoint
      );

      // Parse the API response.
      $response = $res->getBody()->getContents();

    } catch ( GuzzleHttp\Exception\ConnectException $e ) {
      $error = $e->getMessage();
    } catch ( GuzzleHttp\Exception\RequestException $e ) {
      $error = $e->getMessage();
    } catch ( Exception $e ) {
      $error = $e->getMessage();
    }

    // Determine whether or not to send the response as JSON.
    $no_print     = isset( $request['print'] ) && ! $request['print'];
    $no_send_json = $is_internal || $no_print;

    // Handle errors and various expected response types.
    if ( null !== $error ) {
      $log_helper->log( $error );

      return $this->handle_request_errors( $error, $no_send_json );
    } else {
      return $this->handle_request_response( $response, $no_send_json );
    }
  }

  /**
   * Handle errors received from the API depending on the request configuration.
   *
   * @param string  $error          The error that was encountered.
   * @param boolean $no_send_json   Whether or not to send a JSON response.
   * @return object|null            The response object to be passed on if required.
   *
   * @since 3.0.0
   */
  private function handle_request_errors( $error, $no_send_json ) {
    $response = null;
    $message  = array(
      'error'   => 1,
      'message' => $error,
    );

    if ( $no_send_json ) {
      $response = (object) $message;
    } else {
      wp_send_json( $message );
    }

    return $response;
  }

  /**
   * Handle errors received from the API depending on the request configuration.
   *
   * @param string  $res            The response received from the CDP API.
   * @param boolean $no_send_json   Whether or not to send a JSON response.
   * @return object|null            The response object to be passed on if required.
   *
   * @since 3.0.0
   */
  private function handle_request_response( $res, $no_send_json ) {
    $response = null;

    if ( $no_send_json ) {
      $response = json_decode( $res );
    } else {
      wp_send_json( json_decode( $res ) );
    }

    return $response;
  }

  /**
   * Set the appropriate headers depending on the request options.
   *
   * @param string  $callback        The destination for CDP API request responses.
   * @param boolean $errors_only     Whether to only use callback for errors.
   * @return array
   *
   * @since 3.0.0
   */
  private function set_request_headers( $callback, $errors_only ) {
    // Get the plugin configurations.
    $config = get_option( $this->plugin );

    $headers = array();

    $headers['callback_errors'] = $errors_only ? 1 : 0;

    if ( $callback ) {
      $headers['callback'] = $callback;
    }

    if ( ! empty( $config['es_token'] ) ) {
      $headers['Authorization'] = 'Bearer ' . $config['es_token'];
    }

    return $headers;
  }

  /**
   * Generate the full endpoint URL.
   *
   * @param boolean $is_internal    Whether the origin is a WP AJAX (as opposed to direct) request.
   * @param string  $url            The endpoint provided in the request.
   * @return string                 The final target URL for the request.
   *
   * @since 3.0.0
   */
  private function set_request_endpoint( $is_internal, $url ) {
    // Get the plugin configurations.
    $config = get_option( $this->plugin );

    // Get the base URL from the value stored in the plugin configuration.
    $base_uri = trim( $config['es_url'], '/' ) . '/';

    $endpoint = $is_internal ? $base_uri . $url : $url;

    return $endpoint;
  }

  /**
   * Convert the received body into a format usable by the CDP API.
   *
   * @param array   $request        Options to be used when sending the CDP API request.
   * @param boolean $is_internal    Whether the origin is a WP AJAX (as opposed to direct) request.
   * @return null|string            The body to be used in the CDP API request.
   *
   * @since 3.0.0
   */
  private function set_request_body( $request, $is_internal ) {
    // Short circuit if the request has no body.
    if ( ! isset( $request['body'] ) ) {
      return null;
    }

    // Unwrap the post data from ajax call.
    if ( ! $is_internal ) {
      $body = urldecode( base64_decode( $request['body'] ) );
    } else {
      $body = wp_json_encode( $request['body'] );
    }

    // Ensure that site URLs are set correctly in the body.
    return $this->map_domain( $body );
  }

  /**
   * Check if the domain is properly set when domain mapping is in use.
   *
   * @param string $body   A stringified Ajax request body.
   * @return string        The provided body with domains updated.
   *
   * @since 2.0.0
   */
  private function map_domain( $body ) {
    // Get the plugin configurations.
    $config     = get_option( $this->plugin );
    $set_domain = $config['es_wpdomain'];
    $site_url   = site_url();
    $protocol   = is_ssl() ? 'https://' : 'http://';

    // Normalize URLs for comparison.
    $normal_set_url  = str_replace( $protocol, '', $set_domain );
    $normal_site_url = str_replace( $protocol, '', $site_url );

    // If the set domain doesn't match the site URL, update in the body.
    if ( $normal_set_url !== $normal_site_url ) {
      $body = str_replace( $normal_site_url, $normal_set_url, $body );
    }

    return $body;
  }

  /**
   * Check whether the current post type is indexable.
   *
   * @param WP_Post $post    WordPress post object.
   * @return boolean         Whether or not the current type is of a type that can be indexed.
   *
   * @since 3.0.0
   */
  private function check_if_indexable_post_type( $post ) {
    // Return early if missing parameters.
    $config = get_option( $this->plugin );

    $not_indexable = (
      null === $post
      || ! array_key_exists( 'es_post_types', $config )
      || ! array_key_exists( $post->post_type, $config['es_post_types'] )
      || ! $config['es_post_types'][ $post->post_type ]
    );

    return ! $not_indexable;
  }
}
