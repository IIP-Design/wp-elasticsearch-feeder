<?php
global $feeder, $post;

$options = get_option( $this->plugin );
$es_url  = $options['es_url'] ? $options['es_url'] : null;
$token   = $options['es_token'];

if ( $post && $post->ID && $es_url && $token ) :
  $uuid     = $feeder->get_uuid( $post );
  $endpoint = $es_url . $feeder->get_post_type_label( $post->post_type ) . '/' . $uuid;
  ?>
  <div style="text-align: right;">
    <a class="button-secondary" href="javascript:void(0);" id="populate_data">Retrieve Data</a>
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
          url: '<?php echo $endpoint; ?>',
          headers: { Authorization: 'Bearer <?php echo $token; ?>'},
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

<?php endif; ?>
