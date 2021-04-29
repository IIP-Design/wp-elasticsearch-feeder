<?php
/**
 * Owner dropdown for GPA Lab feeder plugin.
 *
 * @package ES_Feeder\Admin
 * @since 2.5.0
 */

  $placeholder = __( 'Select Owner', 'gpalab-feeder' );
?>
<select
  data-placeholder="<?php echo esc_attr( $placeholder ); ?>"
  id="cdp-owner"
  name="cdp_owner"
  style="width: 100%"
  title=<?php esc_attr_e( 'Owner', 'gpalab-feeder' ); ?>
>
  <option
    value=""
    <?php echo esc_attr( '' === $selected ? 'selected="selected"' : '' ); ?>
  >
    <?php echo esc_html( $placeholder ); ?>
  </option>
  <option
    value="<?php echo esc_attr( $sitename ); ?>"
    <?php echo esc_attr( $selected === $sitename ? 'selected="selected"' : '' ); ?>
  >
    <?php echo esc_html( $sitename ); ?>
  </option>

  <?php foreach ( $owners as $owner ) : ?>
    <option
      value="<?php echo esc_attr( $owner ); ?>"
      <?php echo esc_attr( $selected === $owner ? 'selected="selected"' : '' ); ?>
    >
      <?php echo esc_html( $owner ); ?>
    </option>
  <?php endforeach; ?>
</select>
