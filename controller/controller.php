<?php namespace F1;

/**
 * F1 - Controller Class
 * 
 * @author  C. Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.0.0 - 23 Jun 2022
 *
 */

class Controller {

  public $name;

  public $dir;


  public function __construct( $contentDir, $requestPathStr, $requestPage )
  {

    $this->name = $requestPage;
    $this->dir = $contentDir . '/' . $requestPathStr;
    
  }


  public function getFile( $ext = '.php' )
  {

    return $this->dir . '/' . $this->name . $ext;

  }

}