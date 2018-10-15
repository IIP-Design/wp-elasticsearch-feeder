<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://github.com/MaxOrelus
 * @since      1.0.0
 *
 * @package    wp_es_feeder
 * @subpackage wp_es_feeder/admin/partials
 */

  global $wpdb, $feeder;
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap wp_es_settings">
    <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
    <form method="post" name="elasticsearch_options" action="options.php">
    <?php
            $status_counts = $feeder->get_sync_status_counts();
			// Import all the options from the databse
			$options = get_option($this->plugin_name);

			$es_wpdomain = $options['es_wpdomain']?$options['es_wpdomain']:null;
			$es_url = $options['es_url']?$options['es_url']:null;
            $es_token = $options['es_token']?$options['es_token']:null;
			$es_post_types = $options['es_post_types']?$options['es_post_types']:null;
			$es_api_data = array_key_exists('es_api_data', $options) && $options['es_api_data'] ? 1 : 0;
			$es_post_language = array_key_exists('es_post_language', $options) && $options['es_post_language'] ? 1 : 0;

			// Get domain(s) - support for Domain Mapping
			$site = site_url();
			$wpdb->dmtable = $wpdb->base_prefix . 'domain_mapping';
			$domains = $wpdb->get_col( "SELECT domain FROM {$wpdb->dmtable}" );
			$protocol = is_ssl() ? 'https://' : 'http://';

			$selected = '';
			if ( $site === $es_wpdomain || empty($es_wpdomain) )
				$selected = 'selected';

			$domain_output = "<option value='$site' $selected>$site</option>";

			if ( !empty($domains) ) {
				foreach($domains as $domain) {
					$selected = '';
					if ( $protocol.$domain === $es_wpdomain )
						$selected = 'selected';
					$domain_output .= "<option value='$protocol$domain' $selected>$protocol$domain</option>";
				}
			}

            $path = WP_CONTENT_DIR . '/plugins/wp-elasticsearch-feeder/callback.log';
            $log = $feeder->tail($path, 100);
    ?>

    <?php
        settings_fields($this->plugin_name);
        //do_settings_sections($this->plugin_name);
    ?>

    <div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<div class="postbox">						
						<h2><span><?php esc_attr_e( 'Indexed URL', 'wp_admin_style' ); ?></span></h2>
						<div class="inside">
							<select id="es_wpdomain" name="<?php echo $this->plugin_name; ?>[es_wpdomain]">
								<?php echo $domain_output; ?>
							</select>
							<span>* If using domain mapping, mapped URLs will appear in dropdown.</span>
						</div>

						<h2><span><?php esc_attr_e( 'API Server URL', 'wp_admin_style' ); ?></span></h2>
						<div class="inside">
                            <input type="text" placeholder="http://localhost:9200/" class="regular-text" id="es_url" name="<?php echo $this->plugin_name; ?>[es_url]" value="<?php if(!empty($es_url)) echo $es_url; ?>"/>
                            <!--<span class="description"><?php esc_attr_e( 'It must include the trailing slash "/"', 'wp_admin_style' ); ?></span><br>-->
                        </div>

                       <h2><span><?php esc_attr_e( 'API Token', 'wp_admin_style' ); ?></span></h2>
                       <div class="inside">
                          <input type="text" placeholder="api token" class="regular-text" id="es_token" name="<?php echo $this->plugin_name; ?>[es_token]" value="<?php if(!empty($es_token)) echo $es_token; ?>"/>
                       </div>

<!--						<h2><span>--><?php //esc_attr_e( 'Index Name', 'wp_admin_style' ); ?><!--</span></h2>-->
<!--						<div class="inside">-->
<!--							<input type="text" placeholder="sitename.com" class="regular-text" id="es_index" name="--><?php //echo $this->plugin_name; ?><!--[es_index]" value="--><?php //if(!empty($es_index)) echo $es_index; ?><!--"/><br/>-->
<!--						</div>-->

                        <h2><span><?php esc_attr_e( 'API Data Display', 'wp_admin_style' ); ?></span></h2>
                        <div class="inside">
                            <label>
                                <input type="checkbox" id="es_api_data" name="<?php echo $this->plugin_name; ?>[es_api_data]" value="1" <?=$es_api_data ? 'checked' : ''?>/>
                                Show current API data when editing a post of a supported type
                            </label>
                        </div>

						<h2><span><?php esc_attr_e( 'Post Types', 'wp_admin_style' ); ?></span></h2>
						<div class="inside">
							<p>Select the post-types to index into Elasticsearch.</p>
							<?php 
							$post_types = get_post_types( array( 'public' => true ) );
							foreach($post_types as $key => $value) {
                                if (!post_type_supports($key, 'cdp-rest')) continue;
								$value_state = (array_key_exists($key, $es_post_types))?$es_post_types[$value]:0;
								$checked = ($value_state == 1)?'checked="checked"':'';

								// html structure
								echo '<fieldset>
												<legend class="screen-reader-text"><span>es_post_type_'.$value.'</span></legend>
												<label for="es_post_type_'.$value.'" class="post_type_label">
													<input type="checkbox" id="es_post_type_'.$value.'" name="'.$this->plugin_name.'[es_post_type_'.$value.']" '.$checked.'/>
													<span data-type="'.$value.'">'.ucfirst(ES_API_HELPER::get_post_type_label($value, 'name')).'</span>
												</label>
											</fieldset>';
							}
							?>
						</div>

                        <h2><span><?php esc_attr_e( 'Post Language', 'wp_admin_style' ); ?></span></h2>
                        <div class="inside">
                            <label>
                                <input type="checkbox" id="es_post_language" name="<?php echo $this->plugin_name; ?>[es_post_language]" value="1" <?=$es_post_language ? 'checked' : ''?>/>
                                Add language dropdown to the Post (default) content type.
                            </label>
                        </div>

                        <div class="inside">
                          <?php submit_button('Save all changes', 'primary', 'submit', true); ?>
                        </div>
                    </div>
                    <div class="postbox">
                        <h2><span><?php esc_attr_e( 'Live Status', 'wp_admin_style' ); ?></span></h2>
                        <h5 style="margin: 0; padding: 4px 12px">Last update: <span id="last-heartbeat"></span></h5>
                        <div class="inside live-status-wrapper">
                            <table>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <tr>
                                    <td><?php $feeder->sync_status_indicator($i, true, false)?></td>
                                    <td class="status-count status-<?=$i?>" data-status-id="<?=$i?>"><?=(array_key_exists($i, $status_counts) ? $status_counts[$i] : 0)?></td>
                                </tr>
                                <?php endfor; ?>
                            </table>
                        </div>

                        <hr>

						<h2><span><?php esc_attr_e( 'Manage', 'wp_admin_style' ); ?></span></h2>
						<div class="inside manage-btns">
                            <button class="button-secondary" type="button" id="es_test_connection" name="es_test_connection"><?php esc_attr_e( 'Test Connection' ); ?></button>
<!--							<input class="button-secondary" type="button" id="es_create_index" name="es_create_index" value="--><?php //esc_attr_e( 'Create Index' ); ?><!--" />-->
                            <button class="button-secondary" type="button" id="es_query_index" name="es_query_index"><?php esc_attr_e( 'Query Index' ); ?></button>
                            <button class="button-secondary" type="button" id="es_resync_errors" name="es_resync_errors"><?php esc_attr_e( 'Fix Errors' ); ?></button>
                            <button class="button-secondary" type="button" id="es_validate_sync" name="es_validate_sync"><?php esc_attr_e( 'Validate Statuses' ); ?></button>
                            <button class="button-primary" type="button" id="es_resync_control" name="es_resync_control" style="display: none;">Pause</button>
<!--							<input class="button-secondary" type="button" id="es_delete_index" name="es_delete_index" value="--><?php //esc_attr_e( 'Delete Index' ); ?><!--" />-->
                            <button class="button-secondary button-danger" type="button" id="es_resync" name="es_reindex" style="float: right;"><?php esc_attr_e( 'Resync All Data' ); ?></button>
						</div>

						<div class="inside index-spinner"></div>
                        <div class="inside progress-wrapper"></div>
						<hr/>

						<h2><span><?php esc_attr_e( 'Results', 'wp_admin_style' ); ?></span> <span style="font-weight: normal;">(descending order)</span></h2>

						<div class="inside" style="margin-right: 10px;">
							<pre id="es_output" style="min-width: 100%; display: block;background-color:#eaeaea;padding:5px;overflow: scroll;"></pre>
						</div>
					</div>
                    <div class="postbox">
                        <h2><span><?php esc_attr_e( 'Log', 'wp_admin_style' ); ?></span></h2>
                        <div class="inside manage-btns">
                            <button class="button-secondary" type="button" id="truncate_logs"><?php esc_attr_e( 'Truncate Logs' ); ?></button>
                            <a class="button-secondary" href="/wp-content/plugins/wp-elasticsearch-feeder/callback.log"><?php esc_attr_e( 'Download Log' ); ?></a>
                        </div>
                        <div class="inside log-wrapper">
                            <p style="float: left;">Last 100 Lines</p>
                            <button class="button-primary" type="button" id="reload_log" name="reload_log" style="float: right;"><?php esc_attr_e( 'Reload Log' ); ?></button>
                            <textarea rows="20" id="log_text" readonly style="width: 100%; overflow-y: scroll;"><?=$log?></textarea>
                        </div>
                    </div>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>
	</form>
</div>
