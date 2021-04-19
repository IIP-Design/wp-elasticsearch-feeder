<?php
$post_owner = get_post_meta( $post->ID, '_iip_owner', true );
$sitename   = get_bloginfo( 'name' );
$selected   = empty( $post_owner ) ? $post_owner : $sitename;

$owner_helper = new \ES_Feeder\Admin\Helpers\Owner_Helper( $this->plugin );
$owners       = $owner_helper->get_owners();

?>
<select id="cdp-owner" data-placeholder="Select Owner" name="cdp_owner" title="Owner" style="width: 100%">
    <?php foreach ( $owners as $owner ) : ?>
      <option
        value="<?php echo esc_attr( $owner ); ?>"
        <?php echo esc_attr( $selected === $owner ? 'selected="selected"' : '' ); ?>
      >
        <?php echo esc_html( $owner ); ?>
      </option>
    <?php endforeach; ?>
</select>
