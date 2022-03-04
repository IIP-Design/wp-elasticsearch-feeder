<?php
/**
 * Provides an admin area view for the plugin.
 *
 * This file is used to markup the admin settings page of the plugin.
 *
 * @package ES_Feeder\Settings
 * @since 1.0.0
 */

  global $wpdb;

  $api_helper  = new ES_Feeder\Admin\Helpers\API_Helper();
  $sync_helper = new ES_Feeder\Admin\Helpers\Sync_Helper();
?>

<div class="wrap wp_es_settings">
  <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
  <form method="post" name="elasticsearch_options" action="options.php">
  
    <?php

    $status_counts = $sync_helper->get_sync_status_counts();
    // Import all the options from the database.
    $options = get_option( $this->plugin ) ? get_option( $this->plugin ) : array();

    $es_wpdomain      = $options['es_wpdomain'] ? $options['es_wpdomain'] : null;
    $es_url           = $options['es_url'] ? $options['es_url'] : null;
    $es_token         = $options['es_token'] ? $options['es_token'] : null;
    $es_post_types    = $options['es_post_types'] ? $options['es_post_types'] : array();
    $es_api_data      = array_key_exists( 'es_api_data', $options ) && $options['es_api_data'] ? 1 : 0;
    $es_post_language = array_key_exists( 'es_post_language', $options ) && $options['es_post_language'] ? 1 : 0;
    $es_post_owner    = array_key_exists( 'es_post_owner', $options ) && $options['es_post_owner'] ? 1 : 0;
    $es_enable_logs   = array_key_exists( 'es_enable_logs', $options ) && $options['es_enable_logs'] ? 1 : 0;

    // Get domain(s) - support for Domain Mapping.
    $site = site_url();

    $dm_table = $wpdb->base_prefix . 'domain_mapping';

    $has_domain_mapping = $wpdb->get_results(
      $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $dm_table ) )
    );

    $domains;

    if ( ! empty( $use_domain_mapping ) ) {
      $domains = $wpdb->get_col( "SELECT domain FROM {$wpdb->prefix}domain_mapping" );
    }

    $protocol = is_ssl() ? 'https://' : 'http://';
    $selected = '';

    if ( $site === $es_wpdomain || empty( $es_wpdomain ) ) {
      $selected = 'selected';
    }

    $domain_output = "<option value='$site' $selected>$site</option>";

    if ( ! empty( $domains ) ) {
      foreach ( $domains as $wp_domain ) {
        $selected = '';
        if ( $protocol . $wp_domain === $es_wpdomain ) {
          $selected = 'selected';
        }
        $domain_output .= "<option value='$protocol$wp_domain' $selected>$protocol$wp_domain</option>";
      }

      unset( $wp_domain );
    }

    settings_fields( $this->plugin );
    ?>

    <div id="poststuff">
      <div class="gpalab-growl" id="gpalab-growl"></div>
      <div id="post-body" class="metabox-holder columns-1">
        <div id="post-body-content">
          <div class="meta-box-sortables ui-sortable">
            <div class="postbox">
              <h3><?php esc_html_e( 'Indexed URL', 'gpalab-feeder' ); ?></h3>
              <div class="inside gpalab-domain-select">
                <select id="es_wpdomain" name="<?php echo esc_html( $this->plugin ); ?>[es_wpdomain]">
                  <?php
                  $option_elements = array(
                    'option' => array(
                      'selected' => array(),
                      'value'    => array(),
                    ),
                  );

                  echo wp_kses( $domain_output, $option_elements );
                  ?>
                </select>
                <span><?php echo esc_html( '* ' . __( 'If using domain mapping, mapped URLs will appear in dropdown', 'gpalab-feeder' ) . '.' ); ?></span>
              </div>

              <h3><?php esc_html_e( 'API Server URL', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <input
                  class="regular-text"
                  id="gpalab-feeder-url-input"
                  name="<?php echo esc_html( $this->plugin ); ?>[es_url]"
                  placeholder="http://localhost:9200/"
                  type="text"
                  value="<?php echo esc_html( ( ! empty( $es_url ) ? $es_url : '' ) ); ?>"
                />
              </div>

              <h3><?php esc_html_e( 'API Token', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <input
                  class="regular-text"
                  id="gpalab-feeder-token-input"
                  name="<?php echo esc_html( $this->plugin . '[es_token]' ); ?>"
                  placeholder="api token"
                  type="password"
                  value="<?php echo esc_html( ( ! empty( $es_token ) ? $es_token : '' ) ); ?>"
                />
              </div>

              <h3><?php esc_html_e( 'Post Types', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <p><?php esc_html_e( 'Select the post types to index into Content Commons.', 'gpalab-feeder' ); ?></p>
                <?php
                // Get all public post types.
                $post_types = get_post_types( array( 'public' => true ) );
                foreach ( $post_types as $key => $value ) {

                  // Skip those post types not available for indexing into Content Commons.
                  if ( ! post_type_supports( $key, 'cdp-rest' ) ) {
                    continue;
                  }

                  $value_state = ( array_key_exists( $key, $es_post_types ) ) ? $es_post_types[ $value ] : 0;
                  $checked     = ( 1 === $value_state ) ? 'checked="checked"' : '';

                  ?>
                  <fieldset>
                    <legend class="screen-reader-text">
                      <span><?php echo esc_html( 'es_post_type_' . $value ); ?></span>
                    </legend>
                    <label
                      for="<?php echo esc_html( 'es_post_type_' . $value ); ?>"
                      class="post_type_label"
                    >
                      <input
                        id="<?php 'es_post_type_' . $value; ?>"
                        name="<?php echo esc_html( $this->plugin . '[es_post_type_' . $value . ']' ); ?>"
                        type="checkbox"
                        <?php echo esc_attr( $checked ); ?>
                      />
                      <span data-type="<?php echo esc_html( $value ); ?>">
                        <?php echo esc_html( ucfirst( $api_helper->get_post_type_label( $value, 'name' ) ) ); ?>
                      </span>
                    </label>
                  </fieldset>
                  <?php
                }

                unset( $value );
                ?>
              </div>

              <h3><?php esc_html_e( 'Post Language', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <label for="es_post_language" >
                  <input
                    id="es_post_language" 
                    name="<?php echo esc_html( $this->plugin ); ?>[es_post_language]" 
                    type="checkbox" 
                    value="1" 
                    <?php echo $es_post_language ? 'checked' : ''; ?>
                  />
                    <?php esc_html_e( 'Add language dropdown to indexable content types. Should be disabled if using a translation plugin like WPML or Polylang.', 'gpalab-feeder' ); ?>
                </label>
              </div>

              <h3><?php esc_html_e( 'Post Owner', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <label for="es_post_owner" >
                  <input
                    id="es_post_owner" 
                    name="<?php echo esc_html( $this->plugin ); ?>[es_post_owner]" 
                    type="checkbox"
                    value="1" 
                    <?php echo $es_post_owner ? 'checked' : ''; ?>
                  />
                  <?php esc_html_e( 'Add owner dropdown to indexable content types. This allows editors to select which team they represent.', 'gpalab-feeder' ); ?>
                </label>
              </div>

              <h3><?php esc_html_e( 'Debugging', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <label for="es_enable_logs">
                  <input
                    id="es_enable_logs"
                    name="<?php echo esc_html( $this->plugin ); ?>[es_enable_logs]"
                    type="checkbox"
                    value="1"
                    <?php echo $es_enable_logs ? 'checked' : ''; ?>
                  />
                  <?php esc_html_e( 'Enable Logging', 'gpalab-feeder' ); ?>
                </label>
              </div>

              <div class="inside">
                <label for="es_api_data">
                  <input
                    id="es_api_data"
                    name="<?php echo esc_html( $this->plugin ); ?>[es_api_data]"
                    type="checkbox"
                    value="1"
                    <?php echo $es_api_data ? 'checked' : ''; ?>
                  />
                  <?php esc_html_e( 'Show current API data when editing a post of a supported type', 'gpalab-feeder' ); ?>
                </label>
              </div>

              <div class="inside">
                <?php submit_button( 'Save all changes', 'primary', 'submit', true ); ?>
              </div>
            </div>

            <div class="postbox">
              <h3><?php esc_html_e( 'Live Status', 'gpalab-feeder' ); ?></h3>
              <strong class="gpalab-last-update">
                <?php esc_html_e( 'Last update', 'gpalab-feeder' ) . ':'; ?>
                <span id="gpalab-feeder-last-heartbeat"></span>
              </strong>
              <div class="inside live-status-wrapper">
                <table class="gpalab-status-table">
                    <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <tr>
                      <td><?php $sync_helper->sync_status_indicator( $i, true, false ); ?></td>
                      <td
                        class="status-count status-<?php echo esc_attr( $i ); ?>"
                        data-status-id="<?php echo esc_attr( $i ); ?>"
                      >
                        <?php echo esc_html( array_key_exists( $i, $status_counts ) ? $status_counts[ $i ] : 0 ); ?>
                      </td>
                    </tr>
                    <?php endfor; ?>
                </table>
              </div>

              <hr/>

              <h3><?php esc_html_e( 'Manage', 'gpalab-feeder' ); ?></h3>
              <div class="inside manage-btns">
                <button
                  class="button-secondary" 
                  id="gpalab-feeder-test-connection"
                  name="gpalab-feeder-test-connection"
                  type="button" 
                >
                  <?php esc_html_e( 'Test Connection', 'gpalab-feeder' ); ?>
                </button>
                <button 
                  class="button-secondary"
                  id="gpalab-feeder-fix-errors"
                  name="gpalab-feeder-fix-errors"
                  type="button" 
                >
                  <?php esc_html_e( 'Fix Errors', 'gpalab-feeder' ); ?>
                </button>
                <button
                  class="button-secondary"
                  id="gpalab-feeder-validate-sync"
                  name="gpalab-feeder-validate-sync"
                  type="button" 
                >
                  <?php esc_html_e( 'Validate Statuses', 'gpalab-feeder' ); ?>
                </button>
                <button
                  class="button-primary"
                  id="gpalab-feeder-resync-control"
                  name="gpalab-feeder-resync-control"
                  style="display: none;"
                  type="button"
                >
                  <?php esc_html_e( 'Pause', 'gpalab-feeder' ); ?>
                </button>
                <button
                  class="button-secondary button-danger"
                  id="gpalab-feeder-resync"
                  name="gpalab-feeder-resync"
                  style="float: right;"
                  type="button"
                  >
                  <?php esc_html_e( 'Resync All Data', 'gpalab-feeder' ); ?>
                </button>
              </div>

              <div class="inside gpalab-index-spinner" id="index-spinner">
                <div class="spinner is-active gpalab-spinner-animation">
                  <span id="index-spinner-text">Validating...</span>
                  <span id="index-spinner-count"></span>
                </div>
              </div>

              <div class="inside progress-wrapper">
                <div class="gpalab-progress-bar" id="progress-bar">
                  <span id="progress-bar-span"></span>
                </div>
              </div>

              <hr/>

              <h3>
                <?php esc_html_e( 'Results', 'gpalab-feeder' ); ?>
                <span style="font-weight: normal;">(<?php esc_html_e( 'descending order', 'gpalab-feeder' ); ?>)</span>
              </h3>

              <div class="inside">
                <pre class="gpalab-output" id="gpalab-feeder-output"></pre>
              </div>
            </div>

            <?php
            // Conditionally render the logging section.
            if ( 1 === $es_enable_logs ) {
              require_once ES_FEEDER_DIR . 'admin/partials/log-section.php';
            }
            ?>

          </div> <!-- End .meta-box-sortables .ui-sortable -->
          <div class="gpalab-feeder-settings-footer">
            <span>
              <?php
              echo esc_html__( 'GPA Lab Elasticsearch Feeder Plugin | Version ', 'gpalab-feeder' ) . esc_html( $this->version );
              ?>
            </span>
          </div>
        </div> <!-- End #post-body-content -->
      </div> <!-- End #post-body -->
      <br class="clear">
    </div> <!-- End #poststuff -->
  </form>
</div>
