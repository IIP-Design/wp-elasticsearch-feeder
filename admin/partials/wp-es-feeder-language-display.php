<?php
$selected = get_post_meta( $post->ID, '_iip_language', true ) ?: 'en-us';

$language_helper = new \ES_Feeder\Admin\Helpers\Language_Helper();
$langs           = $language_helper->get_languages();
?>
<select id="cdp-language" data-placeholder="Select Language" name="cdp_language" title="Language" style="width: 100%">
    <?php foreach ( $langs as $lang ) : ?>
        <option value="<?php echo $lang->locale; ?>" <?php echo ( $selected === $lang->locale ? 'selected="selected"' : '' ); ?>><?php echo $lang->display_name; ?></option>
    <?php endforeach; ?>
</select>
