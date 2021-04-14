<?php
$sync_helper = new ES_Feeder\Admin\Helpers\Sync_Helper( $this->plugin );

$sync_status = get_post_meta( $post->ID, '_cdp_sync_status', true );
$value       = get_post_meta( $post->ID, '_iip_index_post_to_cdp_option', true );
$sync        = ! empty( $sync_status ) ? $sync_status : 'Never synced';
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
    Publish Status: <div id="cdp_sync_status" style="display: inline-block;"><?php $sync_helper->sync_status_indicator( $sync, true, true ); ?></div>
</div>
