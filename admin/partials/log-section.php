<?php
/**
 * Provides an admin area view for the plugin
 *
 * This file is used to markup the logging section of the plugin's settings page.
 *
 * @package ES_Feeder\Settings
 * @since 3.0.0
 */

$log_helper = new ES_Feeder\Admin\Helpers\Log_Helper();

$log = $log_helper->get_main_log();
?>

<div class="postbox">
  <h3><?php esc_html_e( 'Log', 'gpalab-feeder' ); ?></h3>
  <div  class="inside">
    <div class="gpalab-manage-group">
      <button class="button-secondary gpalab-manage-button" id="gpalab-feeder-clear-logs" type="button" >
        <?php esc_html_e( 'Clear Log', 'gpalab-feeder' ); ?>
      </button>
      <a class="button-secondary gpalab-manage-button" href="<?php echo esc_url( ES_FEEDER_URL . 'gpalab-feeder.log' ); ?>">
        <?php esc_html_e( 'Download Log', 'gpalab-feeder' ); ?>
      </a>
      <button
        class="button-secondary gpalab-manage-button"
        id="gpalab-feeder-reload-log"
        name="gpalab-feeder-reload-log"
        type="button"
      >
        <?php esc_html_e( 'Reload Log', 'gpalab-feeder' ); ?>
      </button>
    </div>
    <textarea class="gpalab-output" rows="20" id="log-text" readonly style="width: 100%"><?php echo esc_textarea( $log ); ?></textarea>
  </div>
</div>
