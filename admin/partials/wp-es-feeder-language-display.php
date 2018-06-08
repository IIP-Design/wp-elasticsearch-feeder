<?php
$selected = get_post_meta($post->ID, '_iip_language', true) ?: 'en-us';
$langs = $cdp_language_helper->get_languages();
?>
<select id="cdp-language" data-placeholder="Select Language" name="cdp_language" title="Language" style="width: 100%">
    <?php foreach ($langs as $lang): ?>
        <option value="<?=$lang->locale?>" <?=($selected === $lang->locale ? 'selected="selected"' : '')?>><?=$lang->display_name?></option>
    <?php endforeach; ?>
</select>


