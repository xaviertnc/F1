<?php namespace F1;

/**
 * F1 - Controller Class
 * 
 * @author  C. Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.2.0 - 08 Jul 2022
 *
 */

class Controller {

  public $name;

  public $baseDir;

  public $fileDir;


  public function __construct( array $config )
  {
    $filePath = $config[ 'filePath' ] ?? '';
    $baseDir = $config[ 'controllersBaseDir' ] ?? '';
    $this->name = $config[ 'name' ] ?? ( $filePath ? basename( $filePath ) : 'noname' );
    $this->fileDir = ( $baseDir && $filePath ) ? $baseDir . DIRECTORY_SEPARATOR . $filePath : $baseDir;
    $this->baseDir = $baseDir;
  }


  public function getFile( $ext = '.php' )
  {
    return $this->fileDir . DIRECTORY_SEPARATOR . $this->name . $ext;
  }

}