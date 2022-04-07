<?php
/**
 * Domain dropdown for GPA Lab feeder plugin.
 *
 * @package ES_Feeder\Admin
 * @since 2.2.0
 */

/**
 * Get domain(s) - support for Domain Mapping.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 */
$site = site_url();

$dm_table = $wpdb->base_prefix . 'domain_mapping';

// Retrieve the list of indexable posts from cache.
$table_cache_key    = 'has_domain_mapping';
$has_domain_mapping = wp_cache_get( $table_cache_key, 'gpalab_feeder' );

if ( false === $has_domain_mapping ) {
  $has_domain_mapping = $wpdb->get_var(
    $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $dm_table ) )
  );

  // Cache the results of the query.
  wp_cache_set( $table_cache_key, $has_domain_mapping, 'gpalab_feeder' );
}

$domains;

// If domain mapping is in use, retrieve list of domains.
if ( ! empty( $has_domain_mapping ) ) {
  // Retrieve the list of domains from cache .
  $domains_cache_key = 'domains';
  $domains           = wp_cache_get( $domains_cache_key, 'gpalab_feeder' );

  if ( false === $domains ) {
    $domains = $wpdb->get_col(
      "SELECT domain FROM {$wpdb->prefix}domain_mapping"
    );

    // Cache the results of the query .
    wp_cache_set( $domains_cache_key, $domains, 'gpalab_feeder' );
  }
}
// phpcs:enable

$protocol = is_ssl() ? 'https://' : 'http://';
$selected = '';

if ( $site === $es_wpdomain || empty( $es_wpdomain ) ) {
  $selected = 'selected';
}

$domain_output = "<option value='$site' $selected>$site</option>";

if ( ! empty( $domains ) ) {
  foreach ( $domains as $wp_domain ) {
    $selected = '';

    if ( $protocol . $wp_domain === $es_wpdomain ) {
      $selected = 'selected';
    }

    $domain_output .= "<option value='$protocol$wp_domain' $selected>$protocol$wp_domain</option>";
  }

  unset( $wp_domain );
}

?>
<div class="inside gpalab-domain-select">
  <select id="es_wpdomain" name="<?php echo esc_html( $this->plugin ); ?>[es_wpdomain]">
    <?php
    $option_elements = array(
      'option' => array(
        'selected' => array(),
        'value'    => array(),
      ),
    );

    echo wp_kses( $domain_output, $option_elements );
    ?>
  </select>
  <span><?php echo esc_html( '* ' . __( 'If using domain mapping, mapped URLs will appear in dropdown', 'gpalab-feeder' ) . '.' ); ?></span>
</div>
