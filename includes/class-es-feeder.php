<?php
/**
 * Registers the ES_Feeder class.
 *
 * @package ES_Feeder
 */

if ( ! class_exists( 'ES_Feeder' ) ) {
  /**
   * Register all hooks to be run by the plugin.
   *
   * @package ES_Feeder
   * @since 3.0.0
   */
  class ES_Feeder {
    const LOG_ALL    = false;
    const SYNC_LIMIT = 25;

    /**
     * The loader that's responsible for maintaining and registering all hooks that power the plugin.
     *
     * @var Loader $loader    Maintains and registers all hooks for the plugin.
     *
     * @access protected
     * @since 1.0.0
     */
    protected $loader;

    /**
     * The unique identifier and version of this plugin.
     *
     * @var string $plugin_name
     *
     * @access protected
     * @since 1.0.0
     */
    protected $plugin_name;

    /**
     * The version of this plugin.
     *
     * @var string $version
     *
     * @access protected
     * @since 1.0.0
     */
    protected $version;

    /**
     * The URL for the Elasticsearch proxy API.
     *
     * @var string $proxy
     *
     * @access protected
     * @since 1.0.0
     */
    public $proxy;

    /**
     * A prefix to add to error log entries.
     *
     * @var string $error
     *
     * @access protected
     * @since 1.0.0
     */
    public $error;

    /**
     * Define the methods called whenever this class is initialized.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function __construct() {
      $this->namespace   = 'elasticsearch/v1';
      $this->plugin_name = 'wp-es-feeder';
      $this->version     = '2.5.0';
      $this->proxy       = get_option( $this->plugin_name )['es_url'];
      $this->error       = '[WP_ES_FEEDER] [:LOG] ';
      $this->load_dependencies();
      $this->define_admin_hooks();
    }

     /**
      * Load the required dependencies for this plugin.
      *
      * Include the following files that make up the plugin:
      *
      * - ES_Feeder\Loader. Orchestrates the hooks of the plugin.
      * - ES_Feeder\Admin. Defines all hooks for the admin area.
      *
      * Create an instance of the loader which will be used to register the hooks with WordPress.
      *
      * @access private
      * @since 1.0.0
      */
    private function load_dependencies() {
      if ( ! class_exists( 'GuzzleHttp\Client' ) ) {
        require_once ES_FEEDER_DIR . 'vendor/autoload.php';
      }

      // The classes  responsible for defining all actions that occur in the admin area.
      require_once ES_FEEDER_DIR . 'includes/class-loader.php';
      require_once ES_FEEDER_DIR . 'admin/api/class-api.php';
      require_once ES_FEEDER_DIR . 'admin/class-admin.php';
      require_once ES_FEEDER_DIR . 'admin/class-ajax.php';
      require_once ES_FEEDER_DIR . 'admin/class-settings.php';

      $this->loader = new ES_Feeder\Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality of the plugin.
     *
     * @since 1.0.0
     */
    private function define_admin_hooks() {
      $admin    = new ES_Feeder\Admin( $this->get_plugin_name(), $this->get_version() );
      $ajax     = new ES_Feeder\Ajax( $this->get_plugin_name(), $this->get_version() );
      $api      = new ES_Feeder\API( $this->get_namespace(), $this->get_plugin_name(), $this->get_version() );
      $logging  = new ES_Feeder\Admin\Helpers\Log_Helper();
      $settings = new ES_Feeder\Settings( $this->get_plugin_name(), $this->get_version() );

      $this->loader->add_action( 'init', $admin, 'register_admin_scripts_styles' );
      $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
      $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts', 10, 1 );

      // Add plugin settings page.
      $this->loader->add_action( 'admin_menu', $settings, 'add_plugin_admin_menu' );

      // Add "Do not index" box to posts and pages.
      $this->loader->add_action( 'add_meta_boxes', $admin, 'add_admin_meta_boxes' );
      $this->loader->add_action( 'add_meta_boxes', $admin, 'add_admin_cdp_taxonomy' );

      // Add settings link to plugin.
      $plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' );
      $this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $admin, 'add_action_links' );

      // Save/update our plugin options.
      $this->loader->add_action( 'admin_init', $admin, 'options_update' );

      // Admin notices.
      $this->loader->add_action( 'admin_notices', $admin, 'sync_errors_notice' );

      // Add sync status to list tables.
      $this->loader->add_filter( 'manage_posts_columns', $admin, 'columns_head' );
      $this->loader->add_action( 'manage_posts_custom_column', $admin, 'columns_content', 10, 2 );

      foreach ( $this->get_allowed_post_types() as $post_type ) {
        $this->loader->add_filter( 'manage_edit-' . $post_type . '_sortable_columns', $admin, 'sortable_columns' );
      }

      // Elasticsearch indexing hook actions.
      add_action( 'save_post', array( $this, 'save_post' ), 101, 2 );
      add_action( 'transition_post_status', array( $this, 'delete_post' ), 10, 3 );
      add_action( 'wp_ajax_es_request', array( $this, 'es_request' ) );
      add_action( 'wp_ajax_es_initiate_sync', array( $this, 'es_initiate_sync' ) );
      add_action( 'wp_ajax_es_process_next', array( $this, 'es_process_next' ) );
      add_action( 'wp_ajax_es_validate_sync', array( $this, 'validate_sync' ) );

      // Ajax hooks.
      $this->loader->add_filter( 'heartbeat_received', $ajax, 'heartbeat', 10, 2 );

      // Logging hooks.
      $this->loader->add_action( 'wp_ajax_es_reload_log', $logging, 'reload_log' );
      $this->loader->add_action( 'wp_ajax_es_truncate_logs', $logging, 'truncate_logs' );

      // API hooks.
      $this->loader->add_action( 'rest_api_init', $api, 'register_elasticsearch_rest_routes' );
      $this->loader->add_action( 'init', $api, 'add_posts_to_api' );
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return Loader    Orchestrates the hooks of the plugin.
     *
     * @since 1.0.0
     */
    public function get_loader() {
      return $this->loader;
    }

    /**
     * Retrieve the API namespace.
     *
     * @since 3.0.0
     */
    public function get_namespace() {
      return $this->namespace;
    }

    /**
     * Retrieve the name of the plugin.
     *
     * @since 1.0.0
     */
    public function get_plugin_name() {
      return $this->plugin_name;
    }

    /**
     * Retrieve the proxy URL.
     *
     * @since 1.0.0
     */
    public function get_proxy_server() {
      return $this->proxy;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since 1.0.0
     */
    public function get_version() {
      return $this->version;
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since 1.0.0
     */
    public function run() {
      $this->loader->run();
    }


    /**
     *
     *
     * @since 2.0.0
     */
    public function validate_sync() {
      set_time_limit( 600 );
      global $wpdb;

      $sync_helper = new ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin_name );
      $statuses    = $sync_helper->statuses;

      $size      = 500;
      $result    = null;
      $modifieds = array();
      $stats     = array(
      'updated'    => 0,
      'es_missing' => 0,
      'wp_missing' => 0,
      'mismatched' => 0,
      );
      $request   = array(
        'url'    => 'search',
        'method' => 'POST',
        'body'   => array(
          'query'   => 'site:' . $this->get_site(),
          'include' => array( 'post_id', 'modified' ),
          'size'    => $size,
          'from'    => 0,
          'scroll'  => '60s',
        ),
        'print'  => false,
      );
      do {
        $result = $this->es_request( $request );
        if ( $result && $result->hits && count( $result->hits->hits ) ) {
          foreach ( $result->hits->hits as $hit ) {
            $modifieds[ $hit->_source->post_id ] = $hit->_source->modified;
          }
        }
        $request = array(
          'url'    => 'search/scroll',
          'method' => 'POST',
          'body'   => array(
            'scrollId' => $result->_scroll_id,
            'scroll'   => '60s',
          ),
          'print'  => false,
        );
      } while ( $result && $result->hits && count( $result->hits->hits ) );

      if ( count( $modifieds ) ) {
        $opts          = get_option( $this->plugin_name );
        $post_types    = $opts['es_post_types'];
        $formats       = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
        $query         = "SELECT p.ID, p.post_modified, ms.meta_value as sync_status 
                  FROM $wpdb->posts p 
                      LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_status') ms ON p.ID = ms.post_id
                      LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_iip_index_post_to_cdp_option') m ON p.ID = m.post_id
                  WHERE p.post_type IN ($formats) AND p.post_status = 'publish' AND (m.meta_value IS NULL OR m.meta_value != 'no') AND ms.meta_value IS NOT NULL";
        $query         = $wpdb->prepare( $query, array_keys( $post_types ) );
        $rows          = $wpdb->get_results( $query );
        $update_errors = array();
        $update_synced = array();
        foreach ( $rows as $row ) {
          if ( array_key_exists( $row->ID, $modifieds ) ) {
            if ( $modifieds[ $row->ID ] == mysql2date( 'c', $row->post_modified ) ) {
              if ( $statuses['SYNCED'] != $row->sync_status ) {
                $update_synced[] = $row->ID;
                $stats['updated']++;
              }
            } else {
              $stats['mismatched']++;
              if ( $statuses['ERROR'] != $row->sync_status ) {
                $update_errors[] = $row->ID;
                $stats['updated']++;
              }
            }
            unset( $modifieds[ $row->ID ] );
          } else {
            $stats['es_missing']++;
            if ( $statuses['ERROR'] != $row->sync_status ) {
              $update_errors[] = $row->ID;
              $stats['updated']++;
            }
          }
        }

        if ( count( $update_synced ) ) {
          $query = "UPDATE $wpdb->postmeta SET meta_value = '" . $statuses['SYNCED'] . "' WHERE meta_key = '_cdp_sync_status' AND post_id IN (" . implode( ',', $update_synced ) . ')';
          $wpdb->query( $query );
        }

        if ( count( $update_errors ) ) {
          $query = "UPDATE $wpdb->postmeta SET meta_value = '" . $statuses['ERROR'] . "' WHERE meta_key = '_cdp_sync_status' AND post_id IN (" . implode( ',', $update_errors ) . ')';
          $wpdb->query( $query );
        }

        $stats['wp_missing'] = count( $modifieds );
      }

      if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        wp_send_json( $stats );
        exit;
      }
      return $stats;
    }

    /**
     * Iterate over posts in a syncing or erroneous state. If syncing for longer than
     * the SYNC_TIMEOUT time, escalate to error status.
     * Return stats on total errors (if any).
     *
     * @since 2.0.0
     */
    public function check_sync_errors() {
      global $wpdb;
      $sync_helper = new ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin_name );

      $result = array(
        'errors' => 0,
        'ids'    => array(),
      );

      $statuses = array(
        $sync_helper->statuses['ERROR'],
        $sync_helper->statuses['SYNCING'],
        $sync_helper->statuses['SYNC_WHILE_SYNCING'],
      );

      $imploded = implode( ',', $statuses );

      $query = "SELECT p.ID, p.post_type, m.meta_value as sync_status FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta m ON p.ID = m.post_id
                  WHERE m.meta_key = '_cdp_sync_status' AND m.meta_value IN ($imploded)";
      $rows  = $wpdb->get_results( $query );

      foreach ( $rows as $row ) {
        $status = $sync_helper->get_sync_status( $row->ID, $row->sync_status );
        if ( $sync_helper->statuses['ERROR'] === $status ) {
          $result['errors']++;
          if ( ! array_key_exists( $row->post_type, $result ) ) {
            $result[ $row->post_type ] = 0;
          }
          $result[ $row->post_type ]++;
          $result['ids'][] = $row->ID;
        }
      }

      return $result;
    }

    /**
     * Triggered via AJAX, clears out old sync data and initiates a new sync process.
     * If sync_errors is present, we will only initiate a sync for posts with a sync error.
     *
     * @since 2.0.0
     */
    public function es_initiate_sync() {
      global $wpdb;
      if ( isset( $_POST['sync_errors'] ) && $_POST['sync_errors'] ) {
        $errors   = $this->check_sync_errors();
        $post_ids = $errors['ids'];
        if ( count( $post_ids ) ) {
          $wpdb->query( "DELETE FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_status' AND post_id IN (" . implode( ',', $post_ids ) . ')' );
        } else {
          echo json_encode(
            array(
              'error'   => true,
              'message' => 'No posts found.',
            )
          );
          exit;
        }
        $results = $this->get_resync_totals();

        wp_send_json( $results );
      } else {
        $wpdb->delete( $wpdb->postmeta, array( 'meta_key' => '_cdp_sync_status' ) );
        $post_ids = $this->get_syncable_posts();

        if ( ! count( $post_ids ) ) {
          echo json_encode(
            array(
              'error'   => true,
              'message' => 'No posts found.',
            )
          );
          exit;
        }

        wp_send_json(
          array(
            'done'     => 0,
            'response' => null,
            'results'  => null,
            'total'    => count( $post_ids ),
            'complete' => 0,
          )
        );
      }
      exit;
    }

    /**
     * Grabs the next post in the queue and sends it to the API.
     * Updates the postmeta indicating that this post has been synced.
     * Returns a JSON object containing the API response for the current post
     * as well as stats on the sync queue.
     *
     * @since 2.0.0
     */
    public function es_process_next() {
      global $wpdb;
      $sync_helper = new ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin_name );
      $statuses    = $sync_helper->statuses;

      while ( get_option( $this->plugin_name . '_syncable_posts' ) !== false );
      update_option( $this->plugin_name . '_syncable_posts', 1, false );
      set_time_limit( 120 );
      $post_ids = $this->get_syncable_posts( self::SYNC_LIMIT );
      if ( ! count( $post_ids ) ) {
        delete_option( $this->plugin_name . '_syncable_posts' );
        $results = $this->get_resync_totals();
        wp_send_json(
             array(
        'done'     => 1,
        'total'    => $results['total'],
        'complete' => $results['complete'],
			 )
            );
        exit;
      } else {
        $results = array();
        $vals    = array();
        $query   = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES";
        foreach ( $post_ids as $post_id ) {
          $vals[] = "($post_id, '_cdp_sync_status', '1')";
        }
        $query .= implode( ',', $vals );
        $wpdb->query( $query );
        delete_option( $this->plugin_name . '_syncable_posts' );
        foreach ( $post_ids as $post_id ) {
          update_post_meta( $post_id, '_cdp_last_sync', gmdate( 'Y-m-d H:i:s' ) );
          $post = get_post( $post_id );
          $resp = $this->addOrUpdate( $post, false, true, false );
          $wpdb->update( $wpdb->posts, array( 'post_status' => 'publish' ), array( 'ID' => $post_id ) );
          if ( ! $resp ) {
            $results[] = array(
              'title'   => $post->post_title,
              'post_id' => $post->ID,
              'message' => 'ERROR: Connection failed.',
              'error'   => true,
            );
            update_post_meta( $post_id, '_cdp_sync_status', $statuses['ERROR'] );
          } elseif ( ! is_object( $resp ) || 'Sync in progress.' !== $resp->message ) {
            $results[] = array(
              'title'    => $post->post_title,
              'post_id'  => $post->ID,
              'response' => $resp,
              'message'  => 'See error response.',
              'error'    => true,
            );
            update_post_meta( $post_id, '_cdp_sync_status', $statuses['ERROR'] );
          }
        }
        $totals            = $this->get_resync_totals();
        $totals['done']    = 0;
        $totals['results'] = $results;
        wp_send_json( $totals );
      }
      exit;
    }

    /**
     * @since 2.1.0
     */
    private function get_syncable_posts( $limit = null ) {
      global $wpdb;
      $opts       = get_option( $this->plugin_name );
      $post_types = $opts['es_post_types'];
      $formats    = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
      $query      = "SELECT p.ID FROM $wpdb->posts p 
                  LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_status') ms ON p.ID = ms.post_id
                  LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_iip_index_post_to_cdp_option') m ON p.ID = m.post_id
                  WHERE p.post_type IN ($formats) AND p.post_status = 'publish' AND (m.meta_value IS NULL OR m.meta_value != 'no')
                    AND ms.meta_value IS NULL ORDER BY p.post_date DESC";
      if ( $limit ) {
        $query .= " LIMIT $limit";
      }
      $query    = $wpdb->prepare( $query, array_keys( $post_types ) );
      $post_ids = $wpdb->get_col( $query );
      return $post_ids ?: array();
    }

    /**
     * @since 2.1.0
     */
    public function get_resync_totals() {
      global $wpdb;
      $opts       = get_option( $this->plugin_name );
      $post_types = $opts['es_post_types'] ?: array();
      $formats    = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );
      $query      = "SELECT COUNT(*) as total, SUM(IF(ms.meta_value IS NOT NULL, 1, 0)) as complete FROM $wpdb->posts p 
                  LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_status') ms ON p.ID = ms.post_id
                  LEFT JOIN (SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_iip_index_post_to_cdp_option') m ON p.ID = m.post_id
                  WHERE p.post_type IN ($formats) AND p.post_status = 'publish' AND (m.meta_value IS NULL OR m.meta_value != 'no')";
      $query      = $wpdb->prepare( $query, array_keys( $post_types ) );
      $row        = $wpdb->get_row( $query );
      return array(
      'done'     => $row->total == $row->complete ? 1 : 0,
      'response' => null,
      'results'  => null,
      'total'    => $row->total,
      'complete' => $row->complete,
      );
    }

    /**
     *
     *
     * @since 1.0.0
     */
    public function save_post( $id, $post ) {
      $settings  = get_option( $this->plugin_name );
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

      // return early if missing parameters
      if ( $post == null || ! array_key_exists( 'es_post_types', $settings ) || ! array_key_exists( $post_type, $settings['es_post_types'] ) || ! $settings['es_post_types'][ $post_type ] ) {
        return;
      }

      if ( 'publish' !== $post->post_status ) {
        return;
      }

      $this->post_sync_send( $post, false );
      $this->translate_post( $post );
    }

    /**
     * @since 2.1.0
     */
    public function post_sync_send( $post, $print = true ) {
      // we only care about modifying published posts
      if ( 'publish' === $post->post_status ) {
        if ( array_key_exists( 'index_post_to_cdp_option', $_POST ) ) {
          // check to see if post should be indexed or removed from index
          $shouldIndex = $_POST['index_post_to_cdp_option'];
        } else {
          $shouldIndex = get_post_meta( $post->ID, '_iip_index_post_to_cdp_option', true ) ?: 'yes';
        }

        if ( isset( $shouldIndex ) && $shouldIndex ) {
          // default to indexing - post has to be specifically set to 'no'
          if ( 'no' === $shouldIndex ) {
            $this->delete( $post );
          } else {
            $this->addOrUpdate( $post, $print );
          }
        }
      }
    }

    /**
     * Fire PUT requests containing associated translations after save_post.
     *
     * @param $id
     *
     * @since 2.1.0
     */
    public function translate_post( $id ) {
      global $wpdb;
      $language_helper = new ES_Feeder\Admin\Helpers\Language_Helper();
      $log_helper      = new ES_Feeder\Admin\Helpers\Log_Helper();
      $sync_helper     = new ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin_name );
      $statuses        = $sync_helper->statuses;

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

      if ( self::LOG_ALL ) {
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
        if ( ! $this->is_syncable( $post_id ) ) {
          continue;
        }

        $translations = $language_helper->get_translations( $post_id );
        $options      = array(
          'url'    => $this->get_post_type_label( $post->post_type ) . '/' . $this->get_uuid( $post_id ),
          'method' => 'PUT',
          'body'   => array(
            'languages' => $translations,
          ),
          'print'  => false,
        );
        $callback     = $this->create_callback( $post_id );

        if ( self::LOG_ALL ) {
          $log_helper->log( "Sending off translations for: $post_id", 'feeder.log' );
        }

        $response = $this->es_request( $options, $callback, false );
        if ( self::LOG_ALL && $response ) {
          $log_helper->log( "IMMEDIATE RESPONSE (PUT):\r\n" . print_r( $response, 1 ), 'callback.log' );
        }

        if ( ! $response ) {
          error_log( print_r( $this->error . 'translate_post() request failed', true ) );
          update_post_meta( $post_id, '_cdp_sync_status', $statuses['ERROR'] );
          delete_post_meta( $post_id, '_cdp_sync_uid' );
        } elseif ( isset( $response->error ) && $response->error ) {
          update_post_meta( $post_id, '_cdp_sync_status', $statuses['ERROR'] );
          delete_post_meta( $post_id, '_cdp_sync_uid' );

          if ( ! self::LOG_ALL && $response ) {
            $log_helper->log( "IMMEDIATE RESPONSE (PUT):\r\n" . print_r( $response, 1 ), 'callback.log' );
          }
        }
      }
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
      if ( $old_status === $new_status || 'publish' !== $old_status ) {
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

      $this->delete( $post );
      $this->translate_post( $post );
    }

    /**
     *
     *
     * @since 1.0.0
     */
    public function addOrUpdate( $post, $print = true, $callback_errors_only = false, $check_syncable = true ) {
      $api_helper  = new ES_Feeder\Admin\Helpers\API_Helper( $this->plugin_name );
      $log_helper  = new ES_Feeder\Admin\Helpers\Log_Helper();
      $sync_helper = new ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin_name );
      $statuses    = $sync_helper->statuses;

      if ( $check_syncable && ! $this->is_syncable( $post->ID ) ) {
        $response = array(
        'error'   => 1,
        'message' => 'Could not publish while publish in progress.',
        );
        if ( $print ) {
          wp_send_json( $response );
        }
        return $response;
      }

      // Plural form of post type.
      $post_type_name = $api_helper->get_post_type_label( $post->post_type, 'name' );

      // API endpoint for wp-json.
      $wp_api_url   = '/' . $this->namespace . '/' . rawurlencode( $post_type_name ) . '/' . $post->ID;
      $request      = new WP_REST_Request( 'GET', $wp_api_url );
      $api_response = rest_do_request( $request );
      $api_response = $api_response->data;

      if ( ! $api_response || isset( $api_response['code'] ) ) {
        error_log( print_r( $this->error . 'addOrUpdate() calling wp rest failed', true ) );
        $api_response['error'] = true;
        $api_response['url']   = $wp_api_url;
        if ( $print ) {
          wp_send_json( $api_response );
        }
        return $api_response;
      }

      // Create callback for this post.
      $callback = $this->create_callback( $post->ID );

      $options = array(
        'url'    => $this->get_post_type_label( $post->post_type ),
        'method' => 'POST',
        'body'   => $api_response,
        'print'  => $print,
      );

      $response = $this->es_request( $options, $callback, $callback_errors_only );

      if ( self::LOG_ALL ) {
        $log_helper->log( "IMMEDIATE RESPONSE:\r\n" . print_r( $response, 1 ), 'callback.log' );
      }

      if ( ! $response ) {
        error_log( print_r( $this->error . 'addOrUpdate()[add] request failed', true ) );
        update_post_meta( $post->ID, '_cdp_sync_status', $statuses['ERROR'] );
        delete_post_meta( $post->ID, '_cdp_sync_uid' );
      } elseif ( isset( $response->error ) && $response->error ) {
        update_post_meta( $post->ID, '_cdp_sync_status', $statuses['ERROR'] );
        delete_post_meta( $post->ID, '_cdp_sync_uid' );

        if ( ! self::LOG_ALL && $response ) {
          $log_helper->log( "IMMEDIATE RESPONSE:\r\n" . print_r( $response, 1 ), 'callback.log' );
        }
      }

      return $response;
    }

    /**
     *
     *
     * @since 1.0.0
     */
    public function delete( $post ) {
      if ( ! $this->is_syncable( $post->ID ) ) {
        return;
      }

      $sync_helper = new ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin_name );
      $statuses    = $sync_helper->statuses;

      update_post_meta( $post->ID, '_cdp_sync_status', $statuses['SYNCING'] );

      $uuid       = $this->get_uuid( $post );
      $delete_url = $this->get_post_type_label( $post->post_type ) . '/' . $uuid;

      $options = array(
         'url'    => $delete_url,
         'method' => 'DELETE',
         'print'  => false,
      );

      $response = $this->es_request( $options );
      if ( ! $response ) {
        error_log( print_r( $this->error . 'addOrUpdate()[add] request failed', true ) );
        update_post_meta( $post->ID, '_cdp_sync_status', $statuses['ERROR'] );
      } elseif ( isset( $response->error ) && $response->error ) {
        update_post_meta( $post->ID, '_cdp_sync_status', $statuses['ERROR'] );
      } else {
        update_post_meta( $post->ID, '_cdp_sync_status', $statuses['NOT_SYNCED'] );
      }
      delete_post_meta( $post->ID, '_cdp_sync_uid' );
    }

    /**
     *
     *
     * @since 1.0.0
     */
    public function es_request( $request, $callback = null, $callback_errors_only = false ) {
      $log_helper = new ES_Feeder\Admin\Helpers\Log_Helper();

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

      $config = get_option( $this->plugin_name );

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
            $body                    = json_encode( $request['body'] );
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

      if ( self::LOG_ALL && ! in_array( $request['url'], array( 'owner', 'language', 'taxonomy' ) ) ) {
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
     * Determines if a post can be synced or not. Syncable means that it is not in the process
     * of being synced. If it is not syncable, update the sync status to inform the user that
     * they needs to wait until the sync is complete and then resync.
     *
     * @param $post_id
     * @return bool
     *
     * @since 2.0.0
     */
    public function is_syncable( $post_id ) {
      global $wpdb;

      $log_helper  = new ES_Feeder\Admin\Helpers\Log_Helper();
      $sync_helper = new ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin_name );
      $statuses    = $sync_helper->statuses;

      // check sync status by attempting to update and if rows updated then sync is in progress
      $query = "UPDATE $wpdb->postmeta 
                SET meta_value = '" . $statuses['SYNC_WHILE_SYNCING'] . "' 
                WHERE post_id = $post_id AND meta_key = '_cdp_sync_status' 
                    AND meta_value IN (" . $statuses['SYNCING'] . ',' . $statuses['SYNC_WHILE_SYNCING'] . ')';
      $rows  = $wpdb->query( $query );
      if ( $rows ) {
        if ( self::LOG_ALL ) {
            $log_helper->log( "Post not syncable so status updated to SYNC_WHILE_SYNCING: $post_id, sync_uid:" . get_post_meta( $post_id, '_cdp_sync_uid', true ) ?: 'none', 'feeder.log' );
        }
        return false;
      }
      return true;
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

    /**
     * @since 2.0.0
     */
    public function get_taxonomy() {
      $args = array(
        'method' => 'GET',
        'url'    => 'taxonomy?tree',
      );
      $data = $this->es_request( $args );
      if ( $data ) {
        if ( is_object( $data ) && $data->error ) {
            return array();
        }
        if ( is_array( $data ) && array_key_exists( 'error', $data ) && $data['error'] ) {
            return array();
        } elseif ( is_array( $data ) ) {
              return $data;
        }
      }
      return array();
    }

    /**
     * @since 2.0.0
     */
    public function get_allowed_post_types() {
      $settings = get_option( $this->plugin_name );
      $types    = array();
      if ( $settings && $settings['es_post_types'] ) {
        foreach ( $settings['es_post_types'] as $post_type => $val ) {
          if ( $val ) {
            $types[] = $post_type;
          }
        }
      }
      return $types;
    }

    /**
     * @since 2.1.0
     */
    private function create_callback( $post_id = null ) {
      $log_helper  = new ES_Feeder\Admin\Helpers\Log_Helper();
      $sync_helper = new ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin_name );
      $statuses    = $sync_helper->statuses;

      $options     = get_option( $this->plugin_name );
      $es_wpdomain = $options['es_wpdomain'] ? $options['es_wpdomain'] : null;
      if ( ! $es_wpdomain ) {
        $es_wpdomain = site_url();
      }
      if ( ! $post_id ) {
        return $es_wpdomain . '/wp-json/' . $this->namespace . '/callback/noop';
      }

      // Create callback for this post.
      global $wpdb;
      do {
        $uid   = uniqid();
        $query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_cdp_sync_uid' AND meta_value = '$uid'";
      } while ( $wpdb->get_var( $query ) );
      $options     = get_option( $this->plugin_name );
      $es_wpdomain = $options['es_wpdomain'] ? $options['es_wpdomain'] : null;
      if ( ! $es_wpdomain ) {
        $es_wpdomain = site_url();
      }
      $callback = $es_wpdomain . '/wp-json/' . $this->namespace . '/callback/' . $uid;
      if ( self::LOG_ALL ) {
        $log_helper->log( "Created callback for: $post_id with UID: $uid", 'feeder.log' );
      }
      update_post_meta( $post_id, '_cdp_sync_uid', $uid );
      update_post_meta( $post_id, '_cdp_sync_status', $statuses['SYNCING'] );
      update_post_meta( $post_id, '_cdp_last_sync', gmdate( 'Y-m-d H:i:s' ) );

      return $callback;
    }

    /**
     * Construct UUID which is site domain delimited by dashes and not periods, underscore, and post ID.
     *
     * @param $post
     * @return string
     *
     * @since 2.0.0
     */
    public function get_uuid( $post ) {
      $post_id = $post;
      if ( ! is_numeric( $post_id ) ) {
        $post_id = $post->ID;
      }
      $opt  = get_option( $this->plugin_name );
      $url  = $opt['es_wpdomain'];
      $args = parse_url( $url );
      $host = $url;

      if ( array_key_exists( 'host', $args ) ) {
        $host = $args['host'];
      } else {
        $host = str_ireplace( 'https://', '', str_ireplace( 'http://', '', $host ) );
      }

      return "{$host}_{$post_id}";
    }

    /**
     * @since 2.0.0
     */
    public function get_site() {
      $opt  = get_option( $this->plugin_name );
      $url  = $opt['es_wpdomain'];
      $args = wp_parse_url( $url );
      $host = $url;

      if ( array_key_exists( 'host', $args ) ) {
        $host = $args['host'];
      } else {
        $host = str_ireplace( 'https://', '', str_ireplace( 'http://', '', $host ) );
      }

      return $host;
    }

    /**
     * Retrieves the singular post type label for use in API end points.
     * Some post types are registered as plural but we want to use singular end point URLs.
     *
     * @param $post_type
     * @return string
     *
     * @since 2.0.0
     */
    public function get_post_type_label( $post_type ) {
      $obj = get_post_type_object( $post_type );

      if ( ! $obj ) {
        return $post_type;
      }

      return $obj->labels->singular_name;
    }
  }
}
