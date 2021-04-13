<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @since 1.0.0
 *
 * @package ES_Feeder\Settings
 */

  global $wpdb, $feeder;
?>

<div class="wrap wp_es_settings">
  <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
  <form method="post" name="elasticsearch_options" action="options.php">
  
    <?php

    $status_counts = $feeder->get_sync_status_counts();
    // Import all the options from the database.
    $options = get_option( $this->plugin ) ? get_option( $this->plugin ) : array();

    $es_wpdomain      = $options['es_wpdomain'] ? $options['es_wpdomain'] : null;
    $es_url           = $options['es_url'] ? $options['es_url'] : null;
    $es_token         = $options['es_token'] ? $options['es_token'] : null;
    $es_post_types    = $options['es_post_types'] ? $options['es_post_types'] : array();
    $es_api_data      = array_key_exists( 'es_api_data', $options ) && $options['es_api_data'] ? 1 : 0;
    $es_post_language = array_key_exists( 'es_post_language', $options ) && $options['es_post_language'] ? 1 : 0;
    $es_post_owner    = array_key_exists( 'es_post_owner', $options ) && $options['es_post_owner'] ? 1 : 0;

    // Get domain(s) - support for Domain Mapping.
    $site          = site_url();
    $wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
    $domains       = $wpdb->get_col( "SELECT domain FROM {$wpdb->dmtable}" );
    $protocol      = is_ssl() ? 'https://' : 'http://';

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
    }

    $pathname = $feeder->plugin_dir . 'callback.log';
    $log      = $feeder->tail( $pathname, 100 );

    settings_fields( $this->plugin );
    // do_settings_sections($this->plugin);
    ?>

    <div id="poststuff">
      <div id="post-body" class="metabox-holder columns-2">
        <div id="post-body-content">
          <div class="meta-box-sortables ui-sortable">
            <div class="postbox">						
              <h3><?php esc_html_e( 'Indexed URL', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <select id="es_wpdomain" name="<?php echo $this->plugin; ?>[es_wpdomain]">
                  <?php echo $domain_output; ?>
                </select>
                <span>* If using domain mapping, mapped URLs will appear in dropdown.</span>
              </div>

              <h3><?php esc_html_e( 'API Server URL', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <input
                  class="regular-text" id="es_url"
                  name="<?php echo $this->plugin; ?>[es_url]"
                  placeholder="http://localhost:9200/"
                  type="text"
                  value="<?php echo ( ! empty( $es_url ) ? $es_url : '' ); ?>"
                />
              </div>

              <h3><?php esc_html_e( 'API Token', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <input
                  class="regular-text"
                  id="es_token"
                  name="<?php echo $this->plugin . '[es_token]'; ?>"
                  placeholder="api token"
                  type="text"
                  value="<?php echo ( ! empty( $es_url ) ? $es_url : '' ); ?>"
                />
              </div>

              <h3><?php esc_html_e( 'API Data Display', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <label> 
                  <input
                    type="checkbox"
                    id="es_api_data"
                    name="<?php echo $this->plugin; ?>[es_api_data]"
                    value="1"
                    <?php echo $es_api_data ? 'checked' : ''; ?>
                  />
                  <?php esc_html_e( 'Show current API data when editing a post of a supported type', 'gpalab-feeder' ); ?>
                </label>
              </div>

              <h3><?php esc_html_e( 'Post Types', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <p><?php esc_html_e( 'Select the post-types to index into Elasticsearch.', 'gpalab-feeder' ); ?></p>
                <?php
                $post_types = get_post_types( array( 'public' => true ) );
                foreach ( $post_types as $key => $value ) {
                  if ( ! post_type_supports( $key, 'cdp-rest' ) ) {
                    continue;
                  }
                  $value_state = ( array_key_exists( $key, $es_post_types ) ) ? $es_post_types[ $value ] : 0;
                  $checked     = ( 1 === $value_state ) ? 'checked="checked"' : '';

                  ?>
                  <fieldset>
                    <legend class="screen-reader-text">
                      <span><?php 'es_post_type_' . $value; ?></span>
                    </legend>
                    <label for="<?php 'es_post_type_' . $value; ?>" class="post_type_label">
                      <input
                        type="checkbox"
                        id="<?php 'es_post_type_' . $value; ?>"
                        name="<?php $this->plugin . '[es_post_type_' . $value . ']'; ?>"
                        <?php echo $checked; ?>
                      />
                      <span data-type="<?php $value; ?>">
                        <?php ucfirst( ES_API_HELPER::get_post_type_label( $value, 'name' ) ); ?>
                      </span>
                    </label>
                  </fieldset>
                  <?php
                }
                ?>
              </div>

              <h3><?php esc_html_e( 'Post Language', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <label>
                  <input
                    id="es_post_language" 
                    name="<?php echo $this->plugin; ?>[es_post_language]" 
                    type="checkbox" 
                    value="1" 
                    <?php echo $es_post_language ? 'checked' : ''; ?>
                  />
                    <?php esc_html_e( 'Add language dropdown to the Post (default) content type.', 'gpalab-feeder' ); ?>
                </label>
              </div>

              <h3><?php esc_html_e( 'Post Owner', 'gpalab-feeder' ); ?></h3>
              <div class="inside">
                <label>
                  <input
                    id="es_post_owner" 
                    name="<?php echo $this->plugin; ?>[es_post_owner]" 
                    type="checkbox"
                    value="1" 
                    <?php echo $es_post_owner ? 'checked' : ''; ?>
                  />
                  <?php esc_html_e( 'Add owner dropdown to the Post (default) content type.', 'gpalab-feeder' ); ?>
                </label>
              </div>

              <div class="inside">
                <?php submit_button( 'Save all changes', 'primary', 'submit', true ); ?>
              </div>
            </div>

            <div class="postbox">
              <h3><?php esc_html_e( 'Live Status', 'gpalab-feeder' ); ?></h3>
              <strong style="margin: 0; padding: 4px 12px">
                <?php esc_html_e( 'Last update', 'gpalab-feeder' ) . ':'; ?>
                <span id="last-heartbeat"></span>
              </strong>
              <div class="inside live-status-wrapper">
                <table>
                    <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                    <tr>
                      <td><?php $feeder->sync_status_indicator( $i, true, false ); ?></td>
                      <td class="status-count status-<?php echo $i; ?>" data-status-id="<?php echo $i; ?>"><?php echo ( array_key_exists( $i, $status_counts ) ? $status_counts[ $i ] : 0 ); ?></td>
                    </tr>
                    <?php endfor; ?>
                </table>
              </div>

              <hr/>

              <h3><?php esc_html_e( 'Manage', 'gpalab-feeder' ); ?></h3>
              <div class="inside manage-btns">
                <button class="button-secondary" type="button" id="es_test_connection" name="es_test_connection">
                  <?php esc_html_e( 'Test Connection', 'gpalab-feeder' ); ?>
                </button>
                <button class="button-secondary" type="button" id="es_query_index" name="es_query_index">
                  <?php esc_html_e( 'Query Index', 'gpalab-feeder' ); ?>
                </button>
                <button class="button-secondary" type="button" id="es_resync_errors" name="es_resync_errors">
                  <?php esc_html_e( 'Fix Errors', 'gpalab-feeder' ); ?>
                </button>
                <button class="button-secondary" type="button" id="es_validate_sync" name="es_validate_sync">
                  <?php esc_html_e( 'Validate Statuses', 'gpalab-feeder' ); ?>
                </button>
                <button class="button-primary" type="button" id="es_resync_control" name="es_resync_control" style="display: none;">
                  <?php esc_html_e( 'Pause', 'gpalab-feeder' ); ?>
                </button>
                <button class="button-secondary button-danger" type="button" id="es_resync" name="es_reindex" style="float: right;">
                  <?php esc_html_e( 'Resync All Data', 'gpalab-feeder' ); ?>
                </button>
              </div>

              <div class="inside index-spinner"></div>
              <div class="inside progress-wrapper"></div>

              <hr/>

              <h3><?php esc_html_e( 'Results', 'gpalab-feeder' ); ?><span style="font-weight: normal;">(descending order)</span></h3>

              <div class="inside" style="margin-right: 10px;">
                <pre id="es_output" style="min-width: 100%; display: block;background-color:#eaeaea;padding:5px;overflow: scroll;"></pre>
              </div>
            </div>

            <div class="postbox">
              <h3><?php esc_html_e( 'Log', 'gpalab-feeder' ); ?></h3>
              <div class="inside manage-btns">
                <button class="button-secondary" type="button" id="truncate_logs">
                  <?php esc_html_e( 'Clear Log', 'gpalab-feeder' ); ?>
                </button>
                <a class="button-secondary" href="<?php echo plugin_dir_url( '/wp-elasticsearch-feeder/callback.log' ); ?>callback.log">
                  <?php esc_html_e( 'Download Log', 'gpalab-feeder' ); ?>
                </a>
              </div>
              <div class="inside log-wrapper">
                  <p style="float: left;"><?php esc_html_e( 'Last 100 Lines', 'gpalab-feeder' ); ?></p>
                  <button class="button-primary" type="button" id="reload_log" name="reload_log" style="float: right;">
                    <?php esc_html_e( 'Reload Log', 'gpalab-feeder' ); ?>
                  </button>
                  <textarea rows="20" id="log_text" readonly style="width: 100%; overflow-y: scroll;">
                    <?php echo $log; ?>
                  </textarea>
              </div>
            </div>

          </div> <!-- End .meta-box-sortables .ui-sortable -->
        </div> <!-- End #post-body-content -->
      </div> <!-- End #post-body -->
      <br class="clear">
    </div> <!-- End #poststuff -->
  </form>
</div>