<?php
/**
 * A debugger window to show API response data.
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
<pre id="es_response"></pre>
<script type="text/javascript">
  jQuery(function($) {
    populate_data();
    $('#populate_data').click(populate_data);
    function populate_data() {
      $('#es_response').html('');
      $.ajax({
        type: 'GET',
        dataType: 'JSON',
        url: '<?php echo esc_html( $endpoint ); ?>',
        headers: { Authorization: 'Bearer <?php echo esc_html( $token ); ?>'},
        success: function (result) {
          if (result && result.content) result.content = "OMITTED";
          $('#es_response').html(JSON.stringify(result, null, 2));
        },
        error: function (result) {
          $('#es_response').html(JSON.stringify(result, null, 2));
        }
      });
    }
  });
</script>
