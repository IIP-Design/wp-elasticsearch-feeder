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
   * @param string $namespace   The namespace to use for the API endpoint.
   * @param string $plugin     The plugin name.
   * @param string $version    The plugin version number.
   *
   * @since 3.0.0
   */
  public function __construct( $namespace, $plugin, $version ) {
    $this->handle_settings = $plugin . '-settings';
    $this->handle_status   = $plugin . '-post-status';
    $this->namespace       = $namespace;
    $this->plugin          = $plugin;
    $this->version         = $version;
  }

  /**
   * Register the scripts for the plugin's admin interface.
   *
   * @since 3.0.0
   */
  public function register_admin_scripts_styles() {
    /* Register settings page styles/scripts */
    $settings_asset = require ES_FEEDER_DIR . 'admin/build/gpalab-feeder-settings.asset.php';

    wp_register_script(
      $this->handle_settings,
      ES_FEEDER_URL . 'admin/build/gpalab-feeder-settings.js',
      $settings_asset['dependencies'],
      $settings_asset['version'],
      false
    );

    wp_register_style(
      $this->handle_settings . '-css',
      ES_FEEDER_URL . 'admin/css/gpalab-feeder-settings.css',
      array(),
      $this->version
    );

    /* Register post status scripts */
    $status_asset = require ES_FEEDER_DIR . 'admin/build/gpalab-feeder-status.asset.php';

    wp_register_script(
      $this->handle_status,
      ES_FEEDER_URL . 'admin/build/gpalab-feeder-status.js',
      $status_asset['dependencies'],
      $status_asset['version'],
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
    // Check whether the current screen is the edit screen for an indexable post.
    $indexable_all_screens = $this->is_indexable_screen( $hook, 'all' );

    // Enqueue settings styles on settings page and allowed post type edit screens.
    if ( 'settings_page_wp-es-feeder' === $hook || $indexable_all_screens ) {
      wp_enqueue_style( $this->handle_settings . '-css' );

      wp_enqueue_style(
        $this->handle_settings,
        ES_FEEDER_URL . 'admin/css/gpalab-feeder-admin.css',
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

    // Only enqueue settings scripts on settings page.
    if ( 'settings_page_wp-es-feeder' === $hook ) {
      $this->localize_settings_script();

      wp_enqueue_script( $this->handle_settings );
    }

    // Check whether the current screen is the edit screen for an indexable post.
    $indexable_post_screen = $this->is_indexable_screen( $hook, 'post' );

    // Only enqueue post-specific admin scripts on edit page of allowed post types.
    if ( $indexable_post_screen ) {
      wp_localize_script(
        $this->handle_status,
        'gpalabFeederSyncStatus',
        array(
          'postId' => $post ? $post->ID : null,
        )
      );

      wp_enqueue_script( $this->handle_status );
    }
  }

  /**
   * Check whether the current screen is the edit screen for an indexable post.
   *
   * @param string $hook   The current admin page.
   * @param string $type   Which hook types should be accepted, accepts the values edit, post, and all.
   * @return boolean       Whether or not the current screen is an indexable post.
   *
   * @since 3.0.0
   */
  private function is_indexable_screen( $hook, $type ) {
    // Check that the hook matches the provided type.
    switch ( $type ) {
      case 'all':
        $hook_matches = 'edit.php' === $hook || 'post.php' === $hook || 'post-new.php' === $hook;
          break;
      case 'edit':
        $hook_matches = 'edit.php' === $hook;
          break;
      case 'post':
        $hook_matches = 'post.php' === $hook || 'post-new.php' === $hook;
          break;
      default:
        $hook_matches = false;
    }

    // Check whether the current post type is indexable.
    $is_indexable = $this->is_indexable( get_post_type() );

    return $hook_matches && $is_indexable;
  }

  /**
   * Check whether the provided post type is indexable to the CDP or not.
   *
   * @param string $post_type   A post type name.
   * @return boolean            Whether or not the post type is indexable.
   *
   * @since 3.0.0
   */
  private function is_indexable( $post_type ) {
    $post_helper = new Admin\Helpers\Post_Helper( $this->namespace, $this->plugin );

    $indexable = ! empty( $post_type ) ? in_array( $post_type, $post_helper->get_allowed_post_types(), true ) : false;

    return $indexable;
  }

  /**
   * Localize the settings page with sync data.
   *
   * @since 3.0.0
   */
  private function localize_settings_script() {
    $sync_helper = new Admin\Helpers\Sync_Helper();

    // Initialize sync status array with fallback values.
    $sync = array(
      'complete' => 0,
      'total'    => 0,
      'paused'   => false,
      'post'     => null,
    );

    $totals = $sync_helper->get_resync_totals();

    // Update the sync status witch current values.
    if ( ! $totals['done'] ) {
      $sync['complete'] = $totals['complete'];
      $sync['total']    = $totals['total'];
      $sync['paused']   = true;
    }

    wp_localize_script(
      $this->handle_settings,
      'gpalabFeederSettings',
      array(
        'feederNonce' => wp_create_nonce( 'gpalab-feeder-nonce' ),
        'syncTotals'  => $sync,
      )
    );
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
   * Register those meta value keys that need to be made available in the REST API.
   *
   * @since 3.0.0
   */
  public function register_metakeys() {
    $options   = get_option( $this->plugin );
    $indexable = ! empty( $options['es_post_types'] ) ? $options['es_post_types'] : array();

    foreach ( $indexable as $key => $post_type ) {
      // Language of the selected post.
      register_post_meta(
        $key,
        '_iip_language',
        array(
          'auth_callback' => '__return_true',
          'description'   => 'The given post\'s language code',
          'show_in_rest'  => true,
          'single'        => true,
          'type'          => 'string',
        )
      );

      // Owner of the selected post.
      register_post_meta(
        $key,
        '_iip_owner',
        array(
          'auth_callback' => '__return_true',
          'description'   => 'The given post\'s owner',
          'show_in_rest'  => true,
          'single'        => true,
          'type'          => 'string',
        )
      );

      // Whether or not the selected post should be indexed to the CDP.
      register_post_meta(
        $key,
        '_iip_index_post_to_cdp_option',
        array(
          'auth_callback' => '__return_true',
          'description'   => 'Whether or not to index the give post',
          'show_in_rest'  => true,
          'single'        => true,
          'type'          => 'string',
        )
      );
    }
  }

  /**
   * Validate and normalize data stored in the plugin's site-wide option.
   *
   * @param array $input   The data to be stored in the plugin's option value.
   * @return array         The normalized option value.
   *
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
      'es_enable_logs'   => array_key_exists( 'es_enable_logs', $input ),
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
   * Register the wp_es_feeder site option for plugin data.
   *
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
    $sync_helper = new Admin\Helpers\Sync_Helper();

    // Only show the error message if the user is an admin and has not dismissed the message previously.
    if ( ! current_user_can( 'manage_options' ) || isset( $_COOKIE['cdp-feeder-notice-dismissed'] ) ) {
      return;
    }

    // Get the errors.
    $errors = $sync_helper->check_sync_errors();

    // Set the error message and make translatable.
    $plural  = 1 !== $errors['errors'] ? __( 'errors', 'gpalab-feeder' ) : __( 'error', 'gpalab-feeder' );
    $message = sprintf(
      /* translators: %1$d: number of errors, %2$s: singular or plural error, %3$s: settings page link, %4$s: singular or plural error */
      __(
        'WP ES Feeder has encountered %1$d %2$s. Go to the <a href="%3$s">settings page</a> to fix the %4$s.',
        'gpalab-feeder'
      ),
      esc_html( $errors['errors'] ),
      $plural,
      admin_url( 'options-general.php?page=wp-es-feeder' ),
      $plural
    );

    // If there are errors, add the notification.
    if ( $errors['errors'] ) {
      $plural = ( 1 !== $errors['errors'] ? 's' : '' );?>
      <div class="notice notice-error feeder-notice is-dismissible">
        <p><?php echo wp_kses( $message, 'post' ); ?></p>
      </div>
      <script type="text/javascript">
        jQuery(function($) {
          $(document).on('click', '.feeder-notice .notice-dismiss', function() {
            var today = new Date();
            var expire = new Date();
            expire.setTime(today.getTime() + 3600000*24); // 1 day.
            document.cookie = 'cdp-feeder-notice-dismissed=1;expires=' + expire.toGMTString();
          });
        });
      </script>
      <?php
    }
  }

  /**
   * Add custom CDP sync status column to the list indexable posts.
   *
   * @param array $defaults  List of default columns.
   * @return array           List of updated columns.
   *
   * @since 2.0.0
   */
  public function add_cdp_sync_column( $defaults ) {
    $post_helper = new Admin\Helpers\Post_Helper( $this->namespace, $this->plugin );

    if ( in_array( get_post_type(), $post_helper->get_allowed_post_types(), true ) ) {
      $defaults['cdp_sync_status'] = __( 'Publish Status', 'gpalab-feeder' );
    }

    return $defaults;
  }

  /**
   * Populate the content of the CDP sync status column.
   *
   * @param string $column_name   Name of the given column.
   * @param int    $post_id       List of default columns.
   *
   * @since 2.0.0
   */
  public function populate_custom_column( $column_name, $post_id ) {
    $sync_helper = new Admin\Helpers\Sync_Helper();

    if ( 'cdp_sync_status' === $column_name ) {
      $status = get_post_meta( $post_id, '_cdp_sync_status', true );

      $sync_helper->sync_status_indicator( $status, false, true );
    }
  }

  /**
   * Make the CDP sync status custom columns sortable.
   *
   * @param array $columns  List of default columns.
   * @return array          List of updated sortable columns.
   *
   * @since 2.0.0
   */
  public function make_sync_column_sortable( $columns ) {
    $columns['cdp_sync_status'] = '_cdp_sync_status';

    return $columns;
  }
}
