<?php
$sitename = get_bloginfo('name');
$selected = get_post_meta($post->ID, '_iip_owner', true) ?: $sitename;
$owners = $cdp_owner_helper->get_owners();
?>
<select id="cdp-owner" data-placeholder="Select Owner" name="cdp_owner" title="Owner" style="width: 100%">
    <?php foreach ($owners as $owner): ?>
        <option value="<?=$owner?>" <?=($selected === $owner ? 'selected="selected"' : '')?>><?=$owner?></option>
    <?php endforeach; ?>
</select>
