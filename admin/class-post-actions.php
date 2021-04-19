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
   * @param string $plugin   The plugin name.
   *
   * @since 3.0.0
   */
  public function __construct( $plugin ) {
    $this->plugin = $plugin;
  }

  /**
   *
   *
   * @since 1.0.0
   */
  public function save_post( $id, $post ) {
    $post_helper = new Admin\Helpers\Post_Helper( $this->plugin );

    $settings  = get_option( $this->plugin );
    $post_type = $post->post_type;

    if ( array_key_exists( 'index_post_to_cdp_option', $_POST ) ) {
      update_post_meta(
        $id,
        '_iip_index_post_to_cdp_option',
        $_POST['index_post_to_cdp_option']
      );
    }

    if ( array_key_exists( 'cdp_language', $_POST ) ) {
      update_post_meta( $id, '_iip_language', $_POST['cdp_language'] );
    }

    if ( array_key_exists( 'cdp_owner', $_POST ) ) {
      update_post_meta( $id, '_iip_owner', $_POST['cdp_owner'] );
    }

    if ( array_key_exists( 'cdp_terms', $_POST ) ) {
      update_post_meta( $id, '_iip_taxonomy_terms', $_POST['cdp_terms'] );
    } elseif ( $_POST && is_array( $_POST ) ) {
      update_post_meta( $id, '_iip_taxonomy_terms', array() );
    }

    // Return early if missing parameters.
    if (
      null === $post
      || ! array_key_exists( 'es_post_types', $settings )
      || ! array_key_exists( $post_type, $settings['es_post_types'] )
      || ! $settings['es_post_types'][ $post_type ]
    ) {
      return;
    }

    if ( 'publish' !== $post->post_status ) {
      return;
    }

    $post_helper->post_sync_send( $post, false );
    $this->translate_post( $post );
  }

  /**
   * Only delete posts if the old status was 'publish'.
   * Otherwise, do nothing.
   *
   * @param $new_status
   * @param $old_status
   * @param $id
   *
   * @since 1.0.0
   */
  public function delete_post( $new_status, $old_status, $id ) {
    $post_helper = new Admin\Helpers\Post_Helper( $this->plugin );

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
   * @param $id
   *
   * @since 2.1.0
   */
  private function translate_post( $id ) {
    global $wpdb;

    $language_helper = new Admin\Helpers\Language_Helper( $this->plugin );
    $log_helper      = new Admin\Helpers\Log_Helper();
    $post_actions    = new Post_Actions( $this->plugin );
    $post_helper     = new Admin\Helpers\Post_Helper( $this->plugin );
    $sync_helper     = new Admin\Helpers\Sync_Helper( $this->plugin );

    $statuses = $sync_helper->statuses;

    if ( ! function_exists( 'icl_object_id' ) ) {
      return;
    }

    if ( is_object( $id ) ) {
      $post = $id;
    } else {
      $post = get_post( $id );
    }

    $settings  = get_option( $this->plugin_name );
    $post_type = $post->post_type;

    if ( null === $post || ! array_key_exists( 'es_post_types', $settings ) || ! array_key_exists( $post_type, $settings['es_post_types'] ) || ! $settings['es_post_types'][ $post_type ] ) {
      return;
    }

    // Get associated post IDs.
    $query = "SELECT trid, element_type FROM {$wpdb->prefix}icl_translations WHERE element_id = $post->ID";
    $vars  = $wpdb->get_row( $query );

    if ( ! $vars || ! $vars->trid || ! $vars->element_type ) {
      return;
    }

    $query    = "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid = $vars->trid AND element_type = '$vars->element_type' AND element_id != $post->ID";
    $post_ids = $wpdb->get_col( $query );

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

      $response = $post_actions->request( $options, $callback, false );

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
     *
     *
     * @since 1.0.0
     */
  public function request( $request, $callback = null, $callback_errors_only = false ) {
    $log_helper = new Admin\Helpers\Log_Helper();

    $is_internal = false;
    $error       = false;
    $results     = null;

    $headers = array();
    if ( $callback ) {
      $headers['callback'] = $callback;
    }
    $headers['callback_errors'] = $callback_errors_only ? 1 : 0;

    $opts = array(
    'timeout'     => 30,
    'http_errors' => false,
    );

    $config = get_option( $this->plugin );

    $token = $config['es_token'];
    if ( ! empty( $token ) ) {
      $headers['Authorization'] = 'Bearer ' . $token;
    }

    if ( ! $request ) {
      $request = $_POST['data'];
    } else {
      $is_internal      = true;
      $opts['base_uri'] = trim( $config['es_url'], '/' ) . '/';
    }

    $client = new GuzzleHttp\Client( $opts );

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

        $body = $this->is_domain_mapped( $body );

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

      $body    = $response->getBody();
      $results = $body->getContents();
    } catch ( GuzzleHttp\Exception\ConnectException $e ) {
      $error = $e->getMessage();
    } catch ( GuzzleHttp\Exception\RequestException $e ) {
      $error = $e->getMessage();
    } catch ( Exception $e ) {
      $error = $e->getMessage();
    }

    if ( $log_helper->log_all && ! in_array( $request['url'], array( 'owner', 'language', 'taxonomy' ), true ) ) {
      $log_helper->log( 'Sending ' . $request['method'] . ' request to: ' . $request['url'] . ( array_key_exists( 'body', $request ) && array_key_exists( 'post_id', $request['body'] ) ? ', post_id : ' . $request['body']['post_id'] : '' ), 'feeder.log' );
      $log_helper->log( "\n\nREQUEST: " . print_r( $request, 1 ), 'es_request.log' );
      $log_helper->log( 'RESULTS: ' . print_r( $results, 1 ), 'es_request.log' );
      $log_helper->log( 'ERROR: ' . print_r( $error, 1 ), 'es_request.log' );
    }

    if ( $error ) {
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
      return json_decode( $results );
    } else {
      wp_send_json( json_decode( $results ) );
      return null;
    }
  }

  /**
   * @since 2.0.0
   */
  private function is_domain_mapped( $body ) {
    // Check if domain is mapped.
    $opt      = get_option( $this->plugin_name );
    $protocol = is_ssl() ? 'https://' : 'http://';
    $opt_url  = $opt['es_wpdomain'];
    $opt_url  = str_replace( $protocol, '', $opt_url );
    $site_url = site_url();
    $site_url = str_replace( $protocol, '', $site_url );

    if ( $opt_url !== $site_url ) {
      $body = str_replace( $site_url, $opt_url, $body );
    }

    return $body;
  }
}
