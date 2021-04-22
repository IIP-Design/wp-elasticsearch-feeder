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

    $post_helper = new Admin\Helpers\Post_Helper( $this->namespace, $this->plugin );

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
   * Populate the indexable post type admin screens with the required metaboxes.
   *
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
        array( $this, 'index_item_toggle' ),
        $screen,
        'side',
        'high'
      );

      if ( $es_api_data ) {
        add_meta_box(
          'es-feeder-response',
          'API Data',
          array( $this, 'api_debugger' ),
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
   * Render a radio toggle to determine whether or not to index a given post.
   *
   * @param object $post  WordPress post Object.
   *
   * @since 1.0.0
   */
  public function index_item_toggle( $post ) {
    $sync_helper = new Admin\Helpers\Sync_Helper( $this->plugin ); // Do not remove, used in the partial below.

    $sync_status = get_post_meta( $post->ID, '_cdp_sync_status', true );
    $value       = get_post_meta( $post->ID, '_iip_index_post_to_cdp_option', true ); // Do not remove, used in the partial below.
    $sync        = ! empty( $sync_status ) ? $sync_status : 'Never synced'; // Do not remove, used in the partial below.

    include_once ES_FEEDER_DIR . 'admin/partials/index-item-toggle.php';
  }

  /**
   * Render a debugger window to show API response data.
   *
   * @param object $post  WordPress post Object.
   *
   * @since 2.1.0
   */
  public function api_debugger( $post ) {
    $post_helper = new Admin\Helpers\Post_Helper( $this->namespace, $this->plugin );

    $options = get_option( $this->plugin );
    $es_url  = ! empty( $options['es_url'] ) ? $options['es_url'] : null;
    $token   = $options['es_token'];

    if ( $es_url && $token ) {
      $uuid     = $post_helper->get_uuid( $post );
      $endpoint = $es_url . $post_helper->get_post_type_label( $post->post_type ) . '/' . $uuid; // Do not remove, used in the partial below.

      include_once ES_FEEDER_DIR . 'admin/partials/api-debugger.php';
    }
  }

  /**
   * Renders an select element populated with the possible post languages.
   *
   * @param object $post  WordPress post Object.
   *
   * @since 2.2.0
   */
  public function language_dropdown( $post ) {
    // Get list of available languages.
    $language_helper = new Admin\Helpers\Language_Helper( $this->namespace, $this->plugin );
    $langs           = $language_helper->get_languages(); // Do not remove, used in the partial below.

    // Get the current language, falling back to English if not set.
    $language = get_post_meta( $post->ID, '_iip_language', true );
    $selected = ! empty( $language ) ? $language : 'en-us'; // Do not remove, used in the partial below.

    include_once ES_FEEDER_DIR . 'admin/partials/dropdown-language.php';
  }

  /**
   * Renders an select element populated with the possible post owners.
   *
   * @param object $post  WordPress post Object.
   *
   * @since 2.5.0
   */
  public function owner_dropdown( $post ) {
    // Get list of available owners.
    $owner_helper = new Admin\Helpers\Owner_Helper( $this->namespace, $this->plugin );
    $owners       = $owner_helper->get_owners(); // Do not remove, used in the partial below.

    $post_owner = get_post_meta( $post->ID, '_iip_owner', true );
    $sitename   = get_bloginfo( 'name' );
    $selected   = ! empty( $post_owner ) ? $post_owner : $sitename; // Do not remove, used in the partial below.

    include_once ES_FEEDER_DIR . 'admin/partials/dropdown-owner.php';
  }

  /**
   * Add taxonomy metabox where appropriate.
   *
   * @since 2.0.0
   */
  public function add_admin_cdp_taxonomy() {
    $options       = get_option( $this->plugin );
    $es_post_types = $options['es_post_types'] ? $options['es_post_types'] : null;
    $screens       = array();

    if ( $es_post_types ) {
      foreach ( $es_post_types as $key => $value ) {
        if ( $value ) {
          $tax_names = get_object_taxonomies( $key );
          $taxes     = ! empty( $tax_names ) ? $tax_names : array();

          if ( ! in_array( 'category', $taxes, true ) ) {
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
   * Renders an select element populated with the possible post taxonomy.
   *
   * @param object $post  WordPress post Object.
   *
   * @since 2.0.0
   */
  private function cdp_taxonomy_display( $post ) {
    $current_terms = get_post_meta( $post->ID, '_iip_taxonomy_terms', true );
    $selected      = ! empty( $current_terms ) ? $current_terms : array(); // Do not remove, used in the partial below.
    $taxonomy      = $this->get_taxonomy(); // Do not remove, used in the partial below.

    include_once ES_FEEDER_DIR . 'admin/partials/dropdown-taxonomy.php';
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
   * Fetch all the available taxonomy terms.
   *
   * @since 2.0.0
   */
  private function get_taxonomy() {
    $post_actions = new Post_Actions( $this->namespace, $this->plugin );

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
