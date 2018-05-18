<?php $value = get_post_meta($post->ID, '_iip_index_post_to_cdp_option', true); ?>
<?php $sync = get_post_meta($post->ID, '_cdp_sync_status', true) ?: 'Never synced'; ?>
<input 
  type="radio" id="index_cdp_yes" 
  name="index_post_to_cdp_option" 
  value="yes" 
  style="margin-top:-1px; vertical-align:middle;"
  <?php checked($value, ''); ?>
  <?php checked($value, 'yes'); ?>
/>
<label for="index_cdp_yes">Yes</label>
<input 
  type="radio" 
  id="index_cdp_no" 
  name="index_post_to_cdp_option" 
  value="no" 
  style="margin-top:-1px; margin-left: 10px; vertical-align:middle;"
  <?php checked($value, 'no'); ?>
/>
<label for="index_cdp_no">No</label>

<div style="margin-top: 6px;">
    Publish Status: <div id="cdp_sync_status" style="display: inline-block;"><?php $feeder->sync_status_indicator($sync, true, true);?></div>
</div>
