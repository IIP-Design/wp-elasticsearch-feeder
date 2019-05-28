<?php
$sitename = get_bloginfo('name');
$selected = get_post_meta($post->ID, '_iip_owner', true) ?: $sitename;
$owners = $cdp_owner_helper->get_owners();
?>
<select id="cdp-owner" data-placeholder="Select Owner" name="cdp_owner" title="Owner" style="width: 100%">
    <option value="<?=$sitename?>" <?=($selected === $sitename ? 'selected="selected"' : '')?>><?=$sitename?></option>
    <?php foreach ($owners as $owner): ?>
        <option value="<?=$owner->name?>" <?=($selected === $owner->name ? 'selected="selected"' : '')?>><?=$owner->name?></option>
    <?php endforeach; ?>
</select>
