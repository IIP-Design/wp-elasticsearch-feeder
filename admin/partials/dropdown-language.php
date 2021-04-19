<?php
/**
 * Language dropdown for GPA Lab feeder plugin.
 *
 * @package ES_Feeder\Admin
 * @since 2.2.0
 */

?>
<select
  id="cdp-language"
  data-placeholder="Select Language"
  name="cdp_language"
  title="Language"
  style="width: 100%"
>
  <?php foreach ( $langs as $lang ) : ?>
    <option
      value="<?php echo esc_attr( $lang->locale ); ?>"
      <?php echo ( $selected === $lang->locale ? 'selected="selected"' : '' ); ?>
    >
      <?php echo esc_html( $lang->display_name ); ?>
    </option>
  <?php endforeach; ?>
</select>
