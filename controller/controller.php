<?php namespace F1;

/**
 * F1 Controller Class - 24 Jun 2022
 * 
 * @author  C. Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.3.0 - 09 Jul 2022
 *
 */

class Controller {

  public $name;
  public $baseDir;
  public $controllerDir;
  public $notFound;


  public function __construct( array $config )
  {
    $filePath = $config[ 'controllerFilePath' ] ?? '';
    $baseDir = $config[ 'controllersBaseDir' ] ?? '';
    $this->name = $config[ 'name' ] ?? ( $filePath ? basename( $filePath ) : 'noname' );
    $this->controllerDir = ( $baseDir && $filePath ) ? $baseDir . DIRECTORY_SEPARATOR . $filePath : $baseDir;
    $this->notFound = $config[ 'notFound' ] ?? '404.html';
    $this->baseDir = $baseDir;
  }


  public function getFile( $ext = '.php' )
  {
    $file = $this->controllerDir . DIRECTORY_SEPARATOR . $this->name . $ext;
    return file_exists( $file ) ? $file : $this->notFound;
  }

}