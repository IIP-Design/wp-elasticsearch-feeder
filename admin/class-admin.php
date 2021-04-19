<?php
/**
 * Registers the Admin class.
 *
 * @package ES_Feeder\Admin
 * @since 3.0.0
 */

namespace ES_Feeder;

/**
 * Registers Elasticsearch feeder admin scripts.
 *
 * @package ES_Feeder\Admin
 * @since 3.0.0
 */
class Admin {

  /**
   * Initializes the class with the plugin name and version.
   *
   * @param string $plugin     The plugin name.
   * @param string $version    The plugin version number.
   *
   * @since 3.0.0
   */
  public function __construct( $plugin, $version ) {
    $this->plugin  = $plugin;
    $this->version = $version;
  }

  /**
   * Register the scripts for the plugin's admin interface.
   *
   * @since 3.0.0
   */
  public function register_admin_scripts_styles() {
    wp_register_script(
      $this->plugin,
      ES_FEEDER_URL . 'admin/js/wp-es-feeder-admin.js',
      array( 'jquery' ),
      $this->version,
      false
    );

    wp_register_script(
      $this->plugin . '-sync-status',
      ES_FEEDER_URL . 'admin/js/wp-es-feeder-admin-post.js',
      array( 'jquery' ),
      $this->version,
      false
    );
  }

  /**
   * Register the styles for the plugin's admin interface.
   *
   * @param string $hook   The current admin page.
   *
   * @since 1.0.0
   */
  public function enqueue_styles( $hook ) {
    global $post;

    $post_helper = new Admin\Helpers\Post_Helper( $this->plugin );

    wp_enqueue_style(
      $this->plugin,
      ES_FEEDER_URL . 'admin/css/wp-es-feeder-admin.css',
      array(),
      $this->version,
      'all'
    );

    if (
      ( 'post.php' === $hook || 'post-new.php' === $hook )
      && in_array( $post->post_type, $post_helper->get_allowed_post_types(), true )
    ) {
      wp_enqueue_style(
        'chosen',
        ES_FEEDER_URL . 'admin/css/chosen.css',
        array(),
        $this->version,
        'all'
      );
    }
  }

  /**
   * Register the scripts for the plugin's admin interface.
   *
   * @param string $hook   The current admin page.
   *
   * @since 1.0.0
   */
  public function enqueue_scripts( $hook ) {
    global $post;

    $post_helper = new Admin\Helpers\Post_Helper( $this->plugin );
    $sync_helper = new Admin\Helpers\Sync_Helper( $this->plugin );

    $totals = $sync_helper->get_resync_totals();
    $sync   = array(
      'complete' => 0,
      'total'    => 0,
      'paused'   => false,
      'post'     => null,
    );

    if ( ! $totals['done'] ) {
      $sync['complete'] = $totals['complete'];
      $sync['total']    = $totals['total'];
      $sync['paused']   = true;
    }

    wp_localize_script( $this->plugin, 'es_feeder_sync', $sync );
    wp_enqueue_script( $this->plugin );

    if (
      ( 'post.php' === $hook || 'post-new.php' === $hook )
      && in_array( $post->post_type, $post_helper->get_allowed_post_types(), true )
    ) {
      wp_enqueue_script(
        'chosen',
        ES_FEEDER_URL . 'admin/js/chosen.jquery.min.js',
        array( 'jquery' ),
        $this->version,
        'all'
      );

      $handle = $this->plugin . '-sync-status';

      wp_localize_script(
        $handle,
        'es_feeder_sync_status',
        array( 'post_id' => $post ? $post->ID : null )
      );

      wp_enqueue_script( $handle );
    }
  }

  /**
   * Add an action link to the plugin's settings page to the plugin listing.
   *
   * @param array $links   The list of default action links displayed under the plugin name.
   * @return array         The original link with a 'Settings' link appended.
   *
   * @since 1.0.0
   */
  public function add_action_links( $links ) {
    $settings_link = array(
      '<a href="' . admin_url( 'options-general.php?page=' . $this->plugin ) . '">Settings</a>',
    );

    return array_merge( $links, $settings_link );
  }

  /**
   * @since 2.1.0
   */
  public function add_admin_meta_boxes() {
    $options          = get_option( $this->plugin );
    $es_post_types    = $options['es_post_types'] ? $options['es_post_types'] : null;
    $es_api_data      = ( current_user_can( 'manage_options' ) && array_key_exists( 'es_api_data', $options ) && $options['es_api_data'] );
    $es_post_language = array_key_exists( 'es_post_language', $options ) && $options['es_post_language'] ? 1 : 0;
    $es_post_owner    = array_key_exists( 'es_post_owner', $options ) && $options['es_post_owner'] ? 1 : 0;
    $screens          = array();

    if ( $es_post_types ) {
      foreach ( $es_post_types as $key => $value ) {
        if ( $value ) {
          array_push( $screens, $key );
        }
      }
    }

    foreach ( $screens as $screen ) {
      add_meta_box(
        'index-to-cdp-mb',
        'Publish to Content Commons',
        array( $this, 'index_to_cdp_display' ),
        $screen,
        'side',
        'high'
      );

      if ( $es_api_data ) {
        add_meta_box(
          'es-feeder-response',
          'API Data',
          array( $this, 'api_response_data' ),
          $screen
        );
      }

      if ( 'post' === $screen && $es_post_language ) {
        add_meta_box(
          'es-language',
          'Language',
          array( $this, 'language_dropdown' ),
          $screen,
          'side',
          'high'
        );
      }

      if ( 'post' === $screen && $es_post_owner ) {
        add_meta_box(
          'es-owner',
          'Owner',
          array( $this, 'owner_dropdown' ),
          $screen,
          'side',
          'high'
        );
      }
    }
  }

  /**
   * @since 1.0.0
   */
  public function index_to_cdp_display( $post ) {
    include_once ES_FEEDER_DIR . 'admin/partials/wp-es-feeder-index-to-cdp-display.php';
  }

  /**
   * @since 2.1.0
   */
  public function api_response_data( $post ) {
    include_once ES_FEEDER_DIR . 'admin/partials/wp-es-feeder-api-view-display.php';
  }

  /**
   * @since 2.2.0
   */
  public function language_dropdown( $post ) {
    include_once ES_FEEDER_DIR . 'admin/partials/wp-es-feeder-language-display.php';
  }

  /**
   * @since 2.5.0
   */
  public function owner_dropdown( $post ) {
    include_once ES_FEEDER_DIR . 'admin/partials/owner-view.php';
  }

  /**
   * @since 2.0.0
   */
  public function add_admin_cdp_taxonomy() {
    $options       = get_option( $this->plugin );
    $es_post_types = $options['es_post_types'] ? $options['es_post_types'] : null;
    $screens       = array();
    if ( $es_post_types ) {
      foreach ( $es_post_types as $key => $value ) {
        if ( $value ) {
          $taxes = get_object_taxonomies( $key ) ?: array();
          if ( ! in_array( 'category', $taxes ) ) {
            array_push( $screens, $key );
          }
        }
      }
    }
    foreach ( $screens as $screen ) {
      add_meta_box(
          'cdp-taxonomy',
          'Categories',
          array( $this, 'cdp_taxonomy_display' ),
          $screen,
          'side',
          'high'
      );
    }
  }

  /**
   * @since 2.0.0
   */
  private function cdp_taxonomy_display( $post ) {
    $taxonomy = $this->get_taxonomy();
    include_once ES_FEEDER_DIR . 'admin/partials/wp-es-feeder-cdp-taxonomy-display.php';
  }

  /**
   * @since 1.0.0
   */
  public function validate( $input ) {

    $valid = array(
      'es_wpdomain'      => sanitize_text_field( $input['es_wpdomain'] ),
      'es_url'           => sanitize_text_field( $input['es_url'] ),
      'es_api_data'      => array_key_exists( 'es_api_data', $input ),
      'es_post_language' => array_key_exists( 'es_post_language', $input ),
      'es_post_owner'    => array_key_exists( 'es_post_owner', $input ),
      'es_token'         => sanitize_text_field( $input['es_token'] ),
    );

    $types      = array();
    $post_types = get_post_types( array( 'public' => true ) );

    if ( isset( $input['es_post_types'] ) ) {

      $types = $input['es_post_types'];

    } else {

      foreach ( $post_types as $key => $value ) {
        if ( ! isset( $input[ 'es_post_type_' . $value ] ) || ! $input[ 'es_post_type_' . $value ] ) {
          continue;
        }
        $types[ $value ] = ( isset( $input[ 'es_post_type_' . $value ] ) ) ? 1 : 0;
      }
    }

    $valid['es_post_types'] = $types;

    return $valid;
  }

  /**
   * @since 2.0.0
   */
  private function get_taxonomy() {
    $post_actions = new Post_Actions( $this->plugin );

    $args = array(
      'method' => 'GET',
      'url'    => 'taxonomy?tree',
    );

    $data = $post_actions->request( $args );

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
   * @since 1.0.0
   */
  public function options_update() {
    register_setting(
      $this->plugin,
      $this->plugin,
      array(
        $this,
        'validate',
      )
    );
  }

  /**
   * Checks for sync errors and displays an admin notice if there are errors and the notice
   * wasn't dismissed in the last 24 hours.
   *
   * @since 2.0.0
   */
  public function sync_errors_notice() {
    $sync_helper = new Admin\Helpers\Sync_Helper( $this->plugin );

    if ( ! current_user_can( 'manage_options' ) || isset( $_COOKIE['cdp-feeder-notice-dismissed'] ) ) {
      return;
    }

    $errors = $sync_helper->check_sync_errors();

    if ( $errors['errors'] ) {
      $plural = ( 1 != $errors['errors'] ? 's' : '' );?>
      <div class="notice notice-error feeder-notice is-dismissible">
          <p>WP ES Feeder has encountered <?php echo $errors['errors']; ?> error<?php echo $plural; ?>. Click <a href="<?php echo admin_url( 'options-general.php?page=wp-es-feeder' ); ?>">here</a> to go to the <a href="<?php echo admin_url( 'options-general.php?page=wp-es-feeder' ); ?>">settings page</a> where you can fix the error<?php echo $plural; ?>.</p>
      </div>
      <script type="text/javascript">
        jQuery(function($) {
          $(document).on('click', '.feeder-notice .notice-dismiss', function() {
            var today = new Date();
            var expire = new Date();
            expire.setTime(today.getTime() + 3600000*24); // 1 day
            document.cookie = 'cdp-feeder-notice-dismissed=1;expires=' + expire.toGMTString();
          });
        });
      </script>
      <?php
    }
  }

  /**
   * @since 2.0.0
   */
  public function columns_head( $defaults ) {
    $post_helper = new Admin\Helpers\Post_Helper( $this->plugin );

    if ( in_array( get_post_type(), $post_helper->get_allowed_post_types(), true ) ) {
      $defaults['sync_status'] = 'Publish Status';
    }

    return $defaults;
  }

  /**
   * @since 2.0.0
   */
  public function columns_content( $column_name, $post_ID ) {
    $sync_helper = new Admin\Helpers\Sync_Helper( $this->plugin );

    if ( 'sync_status' === $column_name ) {
      $status = get_post_meta( $post_ID, '_cdp_sync_status', true );
      $sync_helper->sync_status_indicator( $status, false, true );
    }
  }

  /**
   * @since 2.0.0
   */
  public function sortable_columns( $columns ) {
    $columns['sync_status'] = '_cdp_sync_status';

    return $columns;
  }
}
