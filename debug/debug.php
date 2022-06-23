<?php namespace F1;

/**
 * Debug Class
 * 
 * All things related to debugging runtime code.
 * 
 * @author  C. Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.0.0 - 23 Jun 2022
 */

class Debug {

  public $level;
  public $logFile = __DIR__ . '/' . 'debug.log';
  public static $longDateFormat = 'd M Y H:i:s';
  public static $shortDateFormat = 'Y-m-d';


  public function __construct( $level = 1, $logFile = null )
  {
    $this->level = $level;

    if ( ! $logFile ) exit;

    $this->logFile = $logFile;
    $logDir = dirname( $logFile );

    if ( ! is_dir( $logDir ) ) {
      $oldumask = umask( 0 );
      mkdir( $logDir, 0755, true );
      umask( $oldumask );
    }
  }


  // Override me!
  public function getError( $trace )
  {
    return $trace ? 'Oops, something went wrong!' : null; 
  }


  public function onShutdown()
  {
    if ( ! $this->level ) exit;

    $trace = error_get_last();
    $error = $this->getError( $trace );
    if ( ! $error ) exit;

    $this->log( $error, 'ERROR' );

    echo '<div class="error">', PHP_EOL;
    echo '<h3>', $error, '</h3>', PHP_EOL;
    if ( $this->level >= 2 and $trace ) $this->log( print_r( $trace, true ) );
    if ( $this->level >= 3 and $trace ) $this->dump( $trace );      
    echo '</div>', PHP_EOL;
  }


  // Override me!
  public function formatLog( $message, $type = null )
  {
    $typePrefix = $type ? '[' . str_pad( ucfirst( $type ), 5 ) . "]:\t" : '';
    return $typePrefix . date( Debug::$longDateFormat ) . ' - ' . $message . PHP_EOL;
  }


  public function log( $message, $type = null )
  {
    $formattedMessage = $this->formatLog( $message, $type );
    file_put_contents( $this->logFile, $formattedMessage, FILE_APPEND | LOCK_EX );
  }


  public function dump( $var )
  {
    echo '<pre>', PHP_EOL; print_r( $var ); echo '</pre>', PHP_EOL;
  }

} // end: Class Debug