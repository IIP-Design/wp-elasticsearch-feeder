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
   * An instance of the WordPress direct filesystem class.
   *
   * @var object $filesystem
   *
   * @access protected
   * @since 3.0.0
   */
  protected $filesystem;

  /**
   * Whether or not logging is enabled in the plugin settings.
   *
   * @var string $logs_enabled
   *
   * @access protected
   * @since 3.0.0
   */
  protected $logs_enabled;

  /**
   * The file name to be used for the default plugin log file.
   *
   * @var string $main_log
   *
   * @access protected
   * @since 3.0.0
   */
  protected $main_log;

  /**
   * The permission to apply to the log files when creating them.
   *
   * @var int $permissions
   *
   * @access protected
   * @since 3.0.0
   */
  protected $permissions;

  /**
   * Initializes the class with the log status and the main log filename.
   *
   * @since 3.0.0
   */
  public function __construct() {
    // Ensure that we can instantiate the WP Filesystem classes.
    require_once ABSPATH . '/wp-admin/includes/class-wp-filesystem-base.php';
    require_once ABSPATH . '/wp-admin/includes/class-wp-filesystem-direct.php';

    $this->filesystem   = new \WP_Filesystem_Direct( array() );
    $this->logs_enabled = get_option( ES_FEEDER_NAME )['es_enable_logs'];
    $this->main_log     = 'gpalab-feeder.log';

    // Respect the site's chmod settings if set.
    $this->permissions = defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : 0644;
  }

  /**
   * Append a line to the given log file.
   *
   * @param string $input  The text to be added to the log file.
   * @param string $file   The name of the file to write to.
   *
   * @since 2.0.0
   */
  public function log( $input, $file = null ) {
    $filename = null !== $file ? $file : $this->main_log;

    $path = ES_FEEDER_DIR . $filename;

    $str = 'NULL';

    if ( 'string' === gettype( $input ) ) {
      $str = trim( $input );
    } else {
      $str = wp_json_encode( $input, 0, 4 );
    }

    // Only write to log if logging is enabled.
    if ( $this->logs_enabled ) {
      $existing = '';

      if ( file_exists( $path ) ) {
        $existing = $this->filesystem->get_contents( $path );
      }

      $this->filesystem->put_contents(
        $path,
        gmdate( '[m/d/y H:i:s] ' ) . $str . "\r\n\r\n" . $existing,
        $this->permissions
      );
    }
  }

  /**
   * Clear all plugin error logs.
   * TODO: Check this function's operations.
   *
   * @since 2.0.0
   */
  public function clear_logs() {
    // The following rules are handled by the lab_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new \ES_Feeder\Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );
    // phpcs:enable

    // Get all log files at plugin directory root.
    $path = ES_FEEDER_DIR . '*.log';

    // Iterate over all log files setting their contents to an empty string.
    foreach ( glob( $path ) as $log ) {
      $this->filesystem->put_contents(
        $log,
        '',
        $this->permissions
      );
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
    // The following rules are handled by the lab_verify_nonce function and hence can be safely ignored.
    // phpcs:disable WordPress.Security.NonceVerification.Missing
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.MissingUnslash
    // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated
    $verification = new \ES_Feeder\Admin\Verification();
    $verification->lab_verify_nonce( $_POST['security'] );
    // phpcs:enable

    $log = $this->get_main_log();

    echo wp_json_encode( $log );

    exit;
  }

  /**
   * Retrieves the contents of the main plugin log file.
   *
   * @return string    The log file contents.
   *
   * @since 3.0.0
   */
  public function get_main_log() {
    $filepath = ES_FEEDER_DIR . $this->main_log;

    // Abort if the file doesn't exist.
    if ( ! file_exists( $filepath ) ) {
      return '';
    }

    return trim( $this->filesystem->get_contents( $filepath ) );
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
   * @deprecated
   */
  public function tail( $filepath, $lines = 1, $adaptive = true ) {
    // Abort if the file doesn't exist.
    if ( ! file_exists( $filepath ) ) {
      return;
    }

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
    if ( fread( $f, 1 ) !== "\n" ) {
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
