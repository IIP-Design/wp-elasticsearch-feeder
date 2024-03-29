<?php
/**
 * A debugger window to show API response data.
 * Used in conjunction with legacy metabox on non-Gutenberg sites.
 *
 * @package ES_Feeder\Admin
 * @since 2.1.0
 */

?>
<div style="text-align: right;">
  <a
    class="button-secondary"
    href="javascript:void(0);"
    id="populate_data"
  >
    <?php esc_html_e( 'Retrieve Data', 'gpalab-feeder' ); ?>
  </a>
</div>
<pre id="es-response"></pre>
<script type="text/javascript">
  jQuery(function($) {
    populate_data();
    $('#populate_data').click(populate_data);
    function populate_data() {
      $('#es-response').html('');
      $.ajax({
        type: 'GET',
        dataType: 'JSON',
        url: '<?php echo esc_html( $endpoint ); ?>',
        headers: { Authorization: 'Bearer <?php echo esc_html( $token ); ?>'},
        success: function (result) {
          if (result && result.content) result.content = "OMITTED";
          $('#es-response').html(JSON.stringify(result, null, 2));
        },
        error: function (result) {
          $('#es-response').html(JSON.stringify(result, null, 2));
        }
      });
    }
  });
</script>
