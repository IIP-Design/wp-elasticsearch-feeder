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
   * Initializes the class with the plugin name and version.
   *
   * @param string $namespace   The namespace to use for the API endpoint.
   * @param string $plugin      The plugin name.
   *
   * @since 3.0.0
   */
  public function __construct( $namespace, $plugin ) {
    $this->namespace = $namespace;
    $this->plugin    = $plugin;
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

    // Return early if missing parameters.
    $settings = get_option( $this->plugin );

    if (
      null === $post
      || ! array_key_exists( 'es_post_types', $settings )
      || ! array_key_exists( $post->post_type, $settings['es_post_types'] )
      || ! $settings['es_post_types'][ $post->post_type ]
    ) {
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
   * Only delete posts if the old status was 'publish'.
   * Otherwise, do nothing.
   *
   * @param string $new_status   The new post status.
   * @param string $old_status   Current post status.
   * @param int    $id           The WordPress post id.
   *
   * @since 1.0.0
   */
  public function delete_post( $new_status, $old_status, $id ) {
    $post_helper = new Admin\Helpers\Post_Helper();

    if ( $old_status === $new_status || 'publish' !== $old_status ) {
      return;
    }

    if ( is_object( $id ) ) {
      $post = $id;
    } else {
      $post = get_post( $id );
    }

    $settings  = get_option( $this->plugin );
    $post_type = $post->post_type;

    if ( null === $post || ! array_key_exists( 'es_post_types', $settings ) || ! array_key_exists( $post_type, $settings['es_post_types'] ) || ! $settings['es_post_types'][ $post_type ] ) {
      return;
    }

    $post_helper->delete( $post );
    $this->translate_post( $post );
  }

  /**
   * Fire PUT requests containing associated translations after save_post.
   *
   * @param int|WP_Post $id   A WordPress post id or post object.
   *
   * @since 2.1.0
   */
  private function translate_post( $id ) {
    global $wpdb;

    $language_helper = new Admin\Helpers\Language_Helper( $this->namespace, $this->plugin );
    $log_helper      = new Admin\Helpers\Log_Helper();
    $post_helper     = new Admin\Helpers\Post_Helper();
    $sync_helper     = new Admin\Helpers\Sync_Helper();

    $statuses = $sync_helper->statuses;

    if ( ! function_exists( 'icl_object_id' ) ) {
      return;
    }

    if ( is_object( $id ) ) {
      $post = $id;
    } else {
      $post = get_post( $id );
    }

    $settings  = get_option( $this->plugin );
    $post_type = $post->post_type;

    if ( null === $post || ! array_key_exists( 'es_post_types', $settings ) || ! array_key_exists( $post_type, $settings['es_post_types'] ) || ! $settings['es_post_types'][ $post_type ] ) {
      return;
    }

    // Get associated post IDs.
    $vars = $wpdb->get_row(
      $wpdb->prepare(
        "SELECT trid, element_type FROM {$wpdb->prefix}icl_translations WHERE element_id = %d",
        $post->ID
      )
    );

    if ( ! $vars || ! $vars->trid || ! $vars->element_type ) {
      return;
    }

    $post_ids = $wpdb->get_col(
      $wpdb->prepare(
        "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid = %d AND element_type = %s AND element_id != %d",
        $vars->trid,
        $vars->element_type,
        $post->ID
      )
    );

    if ( $log_helper->log_all ) {
      $log_helper->log( 'Found ' . count( $post_ids ) . " translations for: $post->ID", 'feeder.log' );
    }

    foreach ( $post_ids as $post_id ) {
      $post = get_post( $post_id );
      if ( 'publish' !== $post->post_status ) {
        continue;
      }
      $sync = get_post_meta( $post_id, '_iip_index_post_to_cdp_option', true );

      if ( 'no' === $sync ) {
        continue;
      }
      if ( ! $sync_helper->is_syncable( $post_id ) ) {
        continue;
      }

      $translations = $language_helper->get_translations( $post_id );
      $options      = array(
        'url'    => $post_helper->get_post_type_label( $post->post_type ) . '/' . $post_helper->get_uuid( $post_id ),
        'method' => 'PUT',
        'body'   => array(
          'languages' => $translations,
        ),
        'print'  => false,
      );
      $callback     = $post_helper->create_callback( $post_id );

      if ( $log_helper->log_all ) {
        $log_helper->log( "Sending off translations for: $post_id", 'feeder.log' );
      }

      $response = $this->request( $options, $callback, false );

      if ( $log_helper->log_all && $response ) {
        $log_helper->log( "IMMEDIATE RESPONSE (PUT):\r\n" . print_r( $response, 1 ), 'callback.log' );
      }

      if ( ! $response ) {
        error_log( print_r( $this->error . 'translate_post() request failed', true ) );
        update_post_meta( $post_id, '_cdp_sync_status', $statuses['ERROR'] );
        delete_post_meta( $post_id, '_cdp_sync_uid' );
      } elseif ( isset( $response->error ) && $response->error ) {
        update_post_meta( $post_id, '_cdp_sync_status', $statuses['ERROR'] );
        delete_post_meta( $post_id, '_cdp_sync_uid' );

        if ( ! $log_helper->log_all && $response ) {
          $log_helper->log( "IMMEDIATE RESPONSE (PUT):\r\n" . print_r( $response, 1 ), 'callback.log' );
        }
      }
    }
  }

  /**
   * Send an indexing request.
   *
   * @param array   $request                Options to be used when sending the AJAX request.
   * @param string  $callback               The callback url for failed requests.
   * @param boolean $callback_errors_only   Whether to only use callback for errors(?).
   * @param boolean $is_internal            Whether or not the origin is an AJAX request.
   *
   * @since 1.0.0
   */
  public function request( $request, $callback = null, $callback_errors_only = false, $is_internal = true ) {
    $log_helper = new Admin\Helpers\Log_Helper();

    // Get the plugin configurations.
    $config = get_option( $this->plugin );

    // Initialize response.
    $error   = null;
    $results = null;

    // Set headers.
    $headers                    = array();
    $headers['callback_errors'] = $callback_errors_only ? 1 : 0;

    if ( $callback ) {
      $headers['callback'] = $callback;
    }

    if ( ! empty( $config['es_token'] ) ) {
      $headers['Authorization'] = 'Bearer ' . $config['es_token'];
    }

    // Set request headers.
    $opts = array(
      'timeout'     => 30,
      'http_errors' => false,
    );

    if ( $is_internal ) {
      $opts['base_uri'] = trim( $config['es_url'], '/' ) . '/';
    }

    // Initialize the Guzzle client, which is used to send HTTP requests.
    $client = new GuzzleHttp\Client( $opts );

    $log_helper->log( 'Sending ' . $request['method'] . ' request to the ' . $request['url'] . ' endpoint' );

    try {
      // If a body is provided.
      if ( isset( $request['body'] ) ) {
        // Unwrap the post data from ajax call.
        if ( ! $is_internal ) {
          $body = urldecode( base64_decode( $request['body'] ) );
        } else {
          $body                    = wp_json_encode( $request['body'] );
          $headers['Content-Type'] = 'application/json';
        }

        $body = $this->map_domain( $body );

        $response = $client->request(
          $request['method'],
          $request['url'],
          array(
            'body'    => $body,
            'headers' => $headers,
          )
        );
      } else {
        $response = $client->request( $request['method'], $request['url'], array( 'headers' => $headers ) );
      }

      $response = $response->getBody()->getContents();

    } catch ( GuzzleHttp\Exception\ConnectException $e ) {
      $error = $e->getMessage();
    } catch ( GuzzleHttp\Exception\RequestException $e ) {
      $error = $e->getMessage();
    } catch ( Exception $e ) {
      $error = $e->getMessage();
    }

    if ( null !== $error ) {
      $log_helper->log( $error );

      if ( $is_internal || ( isset( $request['print'] ) && ! $request['print'] ) ) {
        return (object) array(
          'error'   => 1,
          'message' => $error,
        );
      } else {
        wp_send_json(
          array(
            'error'   => 1,
            'message' => $error,
          )
        );

        return null;
      }
    } elseif ( $is_internal || ( isset( $request['print'] ) && ! $request['print'] ) ) {
      return json_decode( $response );
    } else {
      wp_send_json( json_decode( $response ) );

      return null;
    }
  }

  /**
   * Check if the domain is properly set when domain mapping is in use.
   *
   * @param string $body   A stringified Ajax request body.
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
}
