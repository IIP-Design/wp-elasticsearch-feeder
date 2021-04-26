<?php
/**
 * Registers the Log_Helper class.
 *
 * @package ES_Feeder\Admin\Helpers\Log_Helper
 * @since 3.0.0
 */

namespace ES_Feeder\Admin\Helpers;

/**
 * Registers logging helper functions.
 *
 * @package ES_Feeder\Admin\Helpers\Log_Helper
 * @since 3.0.0
 */
class Log_Helper {

  /**
   * Initializes the class with the plugin name and version.
   *
   * @since 3.0.0
   */
  public function __construct() {
    $this->log_all = false;
  }

  /**
   * Append a line to the given log file.
   *
   * @param string $str    The text to be added to the log file.
   * @param string $file   The name of the file to write to.
   *
   * @since 2.0.0
   */
  public function log( $str, $file = 'feeder.log' ) {
    $path = ES_FEEDER_DIR . $file;

    file_put_contents( $path, gmdate( '[m/d/y H:i:s] ' ) . trim( print_r( $str, 1 ) ) . "\r\n\r\n", FILE_APPEND );
  }

  /**
   * Clear all plugin error logs.
   *
   * @since 2.0.0
   */
  public function clear_logs() {
    // The following rules are handled by the slo_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new \ES_Feeder\Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );
    // phpcs:enable

    $path = ES_FEEDER_DIR . '*.log';

    foreach ( glob( $path ) as $log ) {
      file_put_contents( $log, '' );
    }
    echo 1;

    exit;
  }

  /**
   * Retrieve the content of the callback log.
   *
   * @since 2.4.0
   */
  public function reload_log() {
    // The following rules are handled by the slo_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new \ES_Feeder\Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );
    // phpcs:enable

    $path = ES_FEEDER_DIR . 'callback.log';

    $log = $this->tail( $path, 100 );

    echo wp_json_encode( $log );

    exit;
  }

  /**
   * Fetch the final x lines of a given file. Slightly modified version
   * of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/.
   *
   * @author Torleif Berger, Lorenzo Stanco
   * @link http://stackoverflow.com/a/15025877/995958
   * @license http://creativecommons.org/licenses/by/3.0/
   *
   * @param string  $filepath     The destination of the log file to be read.
   * @param int     $lines        The number of lines to return.
   * @param boolean $adaptive     Whether or not to adjust the buffer size as the file grows.
   * @return string
   *
   * @since 2.4.0
   */
  public function tail( $filepath, $lines = 1, $adaptive = true ) {
    // Open file.
    $f = @fopen( $filepath, 'rb' );
    if ( false === $f ) {
      return '';
    }
    // Sets buffer size, according to the number of lines to retrieve.
    // This gives a performance boost when reading a few lines from the file.
    if ( ! $adaptive ) {
      $buffer = 4096;
    } else {
      $buffer = ( $lines < 2 ? 64 : ( $lines < 10 ? 512 : 4096 ) );
    }
    // Jump to last character.
    fseek( $f, -1, SEEK_END );
    // Read it and adjust line number if necessary.
    // (Otherwise the result would be wrong if file doesn't end with a blank line).
    if ( fread( $f, 1 ) != "\n" ) {
      --$lines;
    }

    // Start reading.
    $output = '';
    $chunk  = '';
    // While we would like more.
    while ( ftell( $f ) > 0 && $lines >= 0 ) {
      // Figure out how far back we should jump.
      $seek = min( ftell( $f ), $buffer );
      // Do the jump (backwards, relative to where we are).
      fseek( $f, -$seek, SEEK_CUR );
      // Read a chunk and prepend it to our output.
      $chunk  = fread( $f, $seek );
      $output = ( $chunk ) . $output;
      // Jump back to where we started reading.
      fseek( $f, -mb_strlen( $chunk, '8bit' ), SEEK_CUR );
      // Decrease our line counter.
      $lines -= substr_count( $chunk, "\n" );
    }
    // While we have too many lines.
    // (Because of buffer size we might have read too many).
    while ( $lines++ < 0 ) {
      // Find first newline and remove all text before that.
      $output = substr( $output, strpos( $output, "\n" ) + 1 );
    }
    // Close file and return.
    fclose( $f );

    return trim( $output );
  }
}
