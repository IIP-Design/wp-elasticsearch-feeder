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
    $this->namespace = $namespace;
    $this->plugin    = $plugin;
    $this->version   = $version;
  }

  /**
   * Register the scripts for the plugin's admin interface.
   *
   * @since 3.0.0
   */
  public function register_admin_scripts_styles() {
    wp_register_script(
      $this->plugin,
      ES_FEEDER_URL . 'admin/js/gpalab-feeder-settings.js',
      array( 'jquery' ),
      $this->version,
      false
    );

    wp_register_script(
      $this->plugin . '-sync-status',
      ES_FEEDER_URL . 'admin/js/gpalab-feeder-sync-status.js',
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

    $post_helper = new Admin\Helpers\Post_Helper( $this->namespace, $this->plugin );

    wp_enqueue_style(
      $this->plugin,
      ES_FEEDER_URL . 'admin/css/gpalab-feeder-admin.css',
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
        ES_FEEDER_URL . 'admin/css/gpalab-feeder-chosen.css',
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

    $post_helper = new Admin\Helpers\Post_Helper( $this->namespace, $this->plugin );
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
        ES_FEEDER_URL . 'admin/js/gpalab-feeder-chosen.jquery.min.js',
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
   * Register those metavalue keys that need to be made available in the REST API.
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
    $sync_helper = new Admin\Helpers\Sync_Helper( $this->plugin );

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
      $defaults['sync_status'] = __( 'Publish Status', 'gpalab-feeder' );
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
    $sync_helper = new Admin\Helpers\Sync_Helper( $this->plugin );

    if ( 'sync_status' === $column_name ) {
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
    $columns['sync_status'] = '_cdp_sync_status';

    return $columns;
  }
}
