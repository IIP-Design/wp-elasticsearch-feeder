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
      require_once ES_FEEDER_DIR . 'admin/api/class-api.php';
      require_once ES_FEEDER_DIR . 'admin/class-admin.php';
      require_once ES_FEEDER_DIR . 'admin/class-ajax.php';
      require_once ES_FEEDER_DIR . 'admin/class-gutenberg.php';
      require_once ES_FEEDER_DIR . 'admin/class-legacy-metabox.php';
      require_once ES_FEEDER_DIR . 'admin/class-post-actions.php';
      require_once ES_FEEDER_DIR . 'admin/class-settings.php';
      require_once ES_FEEDER_DIR . 'includes/class-loader.php';

      $this->loader = new ES_Feeder\Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality of the plugin.
     *
     * @since 1.0.0
     */
    private function define_admin_hooks() {
      $actions   = new ES_Feeder\Post_Actions( $this->get_namespace(), $this->get_plugin_name() );
      $admin     = new ES_Feeder\Admin( $this->get_namespace(), $this->get_plugin_name(), $this->get_version() );
      $ajax      = new ES_Feeder\Ajax( $this->get_namespace(), $this->get_plugin_name(), $this->get_version() );
      $api       = new ES_Feeder\API( $this->get_namespace(), $this->get_plugin_name(), $this->get_version() );
      $gutenberg = new ES_Feeder\Gutenberg( $this->get_namespace(), $this->get_plugin_name(), $this->get_proxy() );
      $logging   = new ES_Feeder\Admin\Helpers\Log_Helper();
      $metaboxes = new ES_Feeder\Legacy_Metabox( $this->get_namespace(), $this->get_plugin_name(), $this->get_proxy() );
      $posts     = new ES_Feeder\Admin\Helpers\Post_Helper( $this->get_namespace(), $this->get_plugin_name() );
      $settings  = new ES_Feeder\Settings( $this->get_namespace(), $this->get_plugin_name(), $this->get_version() );

      // Register and enqueue the admin scripts and styles.
      $this->loader->add_action( 'init', $admin, 'register_metakeys' );
      $this->loader->add_action( 'init', $admin, 'register_admin_scripts_styles' );
      $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
      $this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts', 10, 1 );

      // Add plugin settings page.
      $this->loader->add_action( 'admin_menu', $settings, 'add_plugin_admin_menu' );

      // Add legacy metaboxes to manage CDP settings on sites without Gutenberg.
      $this->loader->add_action( 'add_meta_boxes', $metaboxes, 'add_admin_metaboxes' );
      $this->loader->add_action( 'add_meta_boxes', $metaboxes, 'add_admin_taxonomy_metabox' );

      // Add Gutenberg-native metaboxes.
      $this->loader->add_action( 'init', $gutenberg, 'register_gutenberg_plugins' );
      $this->loader->add_action( 'admin_enqueue_scripts', $gutenberg, 'enqueue_gutenberg_plugin' );

      // Add settings link to plugin.
      $plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . $this->plugin_name . '.php' );
      $this->loader->add_filter( 'plugin_action_links_' . $plugin_basename, $admin, 'add_action_links' );

      // Register the wp_es_feeder site option for plugin data.
      $this->loader->add_action( 'admin_init', $admin, 'options_update' );

      // Admin notices.
      $this->loader->add_action( 'admin_notices', $admin, 'sync_errors_notice' );

      // Add sync status to list tables.
      $this->loader->add_filter( 'manage_posts_columns', $admin, 'add_cdp_sync_column' );
      $this->loader->add_action( 'manage_posts_custom_column', $admin, 'populate_custom_column', 10, 2 );

      foreach ( $posts->get_allowed_post_types() as $post_type ) {
        $this->loader->add_filter( 'manage_edit-' . $post_type . '_sortable_columns', $admin, 'make_sync_column_sortable' );
      }

      // Elasticsearch indexing hook actions.
      $this->loader->add_action( 'save_post', $actions, 'save_post', 101, 2 );
      $this->loader->add_action( 'transition_post_status', $actions, 'delete_post', 10, 3 );

      // Ajax hooks.
      $this->loader->add_filter( 'heartbeat_received', $ajax, 'heartbeat', 10, 2 );
      $this->loader->add_action( 'wp_ajax_gpalab_feeder_debug', $ajax, 'debug_post' );
      $this->loader->add_action( 'wp_ajax_gpalab_feeder_sync_init', $ajax, 'initiate_sync' );
      $this->loader->add_action( 'wp_ajax_gpalab_feeder_next', $ajax, 'process_next' );
      $this->loader->add_action( 'wp_ajax_gpalab_feeder_test', $ajax, 'test_connection' );
      $this->loader->add_action( 'wp_ajax_gpalab_feeder_validate', $ajax, 'validate_sync' );

      // Logging hooks.
      $this->loader->add_action( 'wp_ajax_gpalab_feeder_reload_log', $logging, 'reload_log' );
      $this->loader->add_action( 'wp_ajax_gpalab_feeder_clear_logs', $logging, 'clear_logs' );

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
    public function get_proxy() {
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
  }
}
