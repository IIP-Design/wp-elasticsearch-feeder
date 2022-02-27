<?php
/**
 * Provides an admin area view for the plugin
 *
 * This file is used to markup the logging section of the plugin's settings page.
 *
 * @package ES_Feeder\Settings
 * @since 3.0.0
 */

?>

<div class="postbox">
  <h3><?php esc_html_e( 'Log', 'gpalab-feeder' ); ?></h3>
  <div class="inside manage-btns">
    <button class="button-secondary" id="gpalab-feeder-clear-logs" type="button" >
      <?php esc_html_e( 'Clear Log', 'gpalab-feeder' ); ?>
    </button>
    <a class="button-secondary" href="<?php echo esc_url( ES_FEEDER_URL . 'gpalab-feeder.log' ); ?>">
      <?php esc_html_e( 'Download Log', 'gpalab-feeder' ); ?>
    </a>
  </div>
  <div class="inside gpalab-log-wrapper">
    <div  class="gpalab-log-wrapper-top">
      <p><?php esc_html_e( 'Last 100 Lines', 'gpalab-feeder' ); ?></p>
      <button
        class="button-primary"
        id="gpalab-feeder-reload-log"
        name="gpalab-feeder-reload-log"
        type="button"
      >
        <?php esc_html_e( 'Reload Log', 'gpalab-feeder' ); ?>
      </button>
    </div>
    <textarea class="gpalab-output" rows="20" id="log-text" readonly>
      <?php echo esc_textarea( $log ); ?>
    </textarea>
  </div>
</div>
