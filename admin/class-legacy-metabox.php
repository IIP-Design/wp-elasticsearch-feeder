<?php
/**
 * Registers the Legacy_Metabox class.
 *
 * @package ES_Feeder\Legacy_Metabox
 * @since 3.0.0
 */

namespace ES_Feeder;

/**
 * Adds legacy indexing metaboxes for sites where the Gutenberg editor is not enabled.
 *
 * @package ES_Feeder\Legacy_Metabox
 * @since 3.0.0
 */
class Legacy_Metabox {

  /**
   * Initializes the class with the plugin name and version.
   *
   * @param string $namespace   The namespace to use for the API endpoint.
   * @param string $plugin      The plugin name.
   * @param string $proxy       The URL for the Elasticsearch proxy API.
   *
   * @since 3.0.0
   */
  public function __construct( $namespace, $plugin, $proxy ) {
    $this->namespace = $namespace;
    $this->plugin    = $plugin;
    $this->proxy     = $proxy;
  }

  /**
   * Populate the indexable post type admin screens with the required metaboxes.
   *
   * @since 2.1.0
   */
  public function add_admin_metaboxes() {
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
        'high',
        array( '__back_compat_meta_box' => true )
      );

      if ( $es_api_data ) {
        add_meta_box(
          'es-feeder-response',
          'API Data',
          array( $this, 'api_debugger' ),
          $screen,
          'advanced',
          'default',
          array( '__back_compat_meta_box' => true )
        );
      }

      if ( 'post' === $screen && $es_post_language ) {
        add_meta_box(
          'es-language',
          'Language',
          array( $this, 'language_dropdown' ),
          $screen,
          'side',
          'high',
          array( '__back_compat_meta_box' => true )
        );
      }

      if ( 'post' === $screen && $es_post_owner ) {
        add_meta_box(
          'es-owner',
          'Owner',
          array( $this, 'owner_dropdown' ),
          $screen,
          'side',
          'high',
          array( '__back_compat_meta_box' => true )
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

    // Add a nonce field to allow for a security check when saving CDP metadata.
    wp_nonce_field( 'gpalab-feeder-nonce', 'security' );

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
    $es_url  = ! empty( $this->proxy ) ? $this->proxy : null;
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
  public function add_admin_taxonomy_metabox() {
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
        'high',
        array( '__back_compat_meta_box' => true )
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
    $tax_helper    = new Admin\Helpers\Taxonomy_Helper( $this->namespace, $this->plugin );
    $current_terms = get_post_meta( $post->ID, '_iip_taxonomy_terms', true );
    $selected      = ! empty( $current_terms ) ? $current_terms : array(); // Do not remove, used in the partial below.
    $taxonomy      = $tax_helper->get_taxonomy(); // Do not remove, used in the partial below.

    include_once ES_FEEDER_DIR . 'admin/partials/dropdown-taxonomy.php';
  }

}
