<?php
/**
 * Radio toggle to determine whether or not to index a given post.
 *
 * @package ES_Feeder\Admin
 * @since 1.0.0
 */

?>
<label for="index_cdp_yes">
  <input 
    type="radio" id="index_cdp_yes" 
    name="index_post_to_cdp_option" 
    value="yes" 
    style="margin-top:-1px; vertical-align:middle;"
    <?php checked( $value, '' ); ?>
    <?php checked( $value, 'yes' ); ?>
  />
  <?php esc_html_e( 'Yes', 'gpalab-feeder' ); ?>
</label>
<label for="index_cdp_no">
  <input 
    type="radio" 
    id="index_cdp_no" 
    name="index_post_to_cdp_option" 
    value="no" 
    style="margin-top:-1px; margin-left: 10px; vertical-align:middle;"
    <?php checked( $value, 'no' ); ?>
  />
  <?php esc_html_e( 'No', 'gpalab-feeder' ); ?>
</label>

<div style="margin-top: 6px;">
  <?php esc_html_e( 'Publish Status:', 'gpalab-feeder' ); ?>
  <div id="cdp_sync_status" style="display: inline-block;"><?php $sync_helper->sync_status_indicator( $sync, true, true ); ?></div>
</div>
