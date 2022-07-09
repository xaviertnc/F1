<?php namespace F1;

/**
 * F1 - Controller Class
 * 
 * @author  C. Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.3.0 - 09 Jul 2022
 *
 */

class Controller {

  public $name;
  public $baseDir;
  public $fileDir;
  public $notFound;


  public function __construct( array $config )
  {
    $filePath = $config[ 'filePath' ] ?? '';
    $baseDir = $config[ 'controllersBaseDir' ] ?? '';
    $this->name = $config[ 'name' ] ?? ( $filePath ? basename( $filePath ) : 'noname' );
    $this->fileDir = ( $baseDir && $filePath ) ? $baseDir . DIRECTORY_SEPARATOR . $filePath : $baseDir;
    $this->notFound = $config[ 'notFound' ] ?? '404.html';
    $this->baseDir = $baseDir;
  }


  public function getFile( $ext = '.php' )
  {
    $file = $this->fileDir . DIRECTORY_SEPARATOR . $this->name . $ext;
    return file_exists( $file ) ? $file : $this->notFound;
  }

}