<?php namespace F1;

/**
 * F1 - Controller Class
 * 
 * @author  C. Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.1.0 - 01 Jul 2022
 *
 */

class Controller {

  public $name;

  public $fileDir;


  public function __construct( array $config )
  {
    $baseDir = $config[ 'baseDir' ] ?? '';
    $filePath = $config[ 'filePath' ] ?? '';
    $this->name = $config[ 'name' ] ?? ( $filePath ? basename( $filePath ) : 'noname' );
    $this->fileDir = ( $baseDir && $filePath ) ? $baseDir . DIRECTORY_SEPARATOR . $filePath : $baseDir;
  }


  public function getFile( $ext = '.php' )
  {
    return $this->fileDir . DIRECTORY_SEPARATOR . $this->name . $ext;
  }

}