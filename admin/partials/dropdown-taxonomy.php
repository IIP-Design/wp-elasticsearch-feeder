<?php
/**
 * Taxonomy dropdown for GPA Lab feeder plugin.
 *
 * @package ES_Feeder\Admin
 * @since 2.0.0
 */

/**
 * Recursively populate a taxonomy dropdown.
 *
 * @param array  $terms      List of taxonomy terms to add as an option.
 * @param array  $selected   The currently selected taxonomy terms.
 * @param object $parent     A taxonomy term with nested child terms.
 */
function display_level( $terms, $selected, $parent = null ) {
  foreach ( $terms as $term ) :
    $id = $term->_id . ( $parent ? "<$parent->_id" : '' ); ?>
    <option
      id="cdp-term-<?php echo esc_attr( $id ); ?>"
      value="<?php echo esc_attr( $id ); ?>" <?php echo ( in_array( $id, $selected, true ) ? 'selected="selected"' : '' ); ?>
    >
      <?php echo esc_html( $parent ? $parent->language->en . ' > ' : '' ); ?>
      <?php echo esc_html( $term->language->en ); ?>
    </option>
    <?php
    if ( count( $term->children ) ) {
      display_level( $term->children, $selected, $term );}
    ?>
    <?php
  endforeach;
}

?>
<select
  data-placeholder="<?php esc_attr_e( 'Select Terms', 'gpalab-feeder' ); ?>" 
  id="cdp-terms" 
  multiple
  name="cdp_terms[]"
  style="width: 100%"
  title="<?php esc_attr_e( 'CDP Taxonomy Terms', 'gpalab-feeder' ); ?>"
>
  <?php display_level( $taxonomy, $selected ); ?>
</select>
