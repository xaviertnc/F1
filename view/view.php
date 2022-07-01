<?php namespace F1;

/**
 * F1 - View Class
 *
 * @author C. Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.2.0 - 01 July 2022
 * 
 */

class View {

  public $name;
  public $title;
  public $fileDir;
  public $themesDir;


  public function __construct( array $config )
  {
    $this->name = $config[ 'name' ];
    $this->fileDir = $config[ 'fileDir' ];
    $this->themesDir = $config[ 'themesDir' ];
    $this->theme = $config[ 'theme' ] ?? 'default';
  }


  public function getTitle()
  {
    return $this->title ?: $this->name;
  }


  public function getFile( $ext = '.html.php' )
  {
    return $this->fileDir . DIRECTORY_SEPARATOR . $this->name . $ext;
  }


  public function getThemeFile( $name, $ext = '.html.php' )
  {
     return $this->themesDir . DIRECTORY_SEPARATOR . $this->theme . 
       DIRECTORY_SEPARATOR . $name . $ext;
  }


  public function getStylesFile()
  {
    return $this->getFile( '.css' );
  }


  public function getScriptFile()
  {
    return $this->getFile( '.js' );
  }

}