<?php namespace F1;

/**
 * F1 - View Class
 *
 * @author C. Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.3.0 - 02 July 2022
 * 
 */

class View {

  public $name;
  public $fileDir;
  public $themesDir;
  public $fileBasename;
  public $variant;
  public $theme;
  public $title;


  public function __construct( array $config )
  {
    $this->name = $config[ 'name' ];
    $this->fileDir = $config[ 'fileDir' ] . DIRECTORY_SEPARATOR;
    $this->themesDir = $config[ 'themesDir' ] . DIRECTORY_SEPARATOR;
    $this->fileBasename = $this->fileDir . $this->name;
    $this->title = $config[ 'title' ] ?? $this->name;
    $this->theme = $config[ 'theme' ] ?? 'default';
  }


  public function compile( $uncompiledFile, $compiledFile, $manifestFile )
  {
    $matches = array();
    $includes = array();
    $manifest = array();
    $content = file_get_contents( $uncompiledFile );
    // echo '<pre>Uncompiled:', PHP_EOL, htmlentities( $content ), '</pre>';
    $pattern = '/\<\?php.*getThemeFile\(\s*[\'"](.*)[\'"].+\?\>/';
    preg_match_all($pattern, $content, $matches);
    // echo '<pre>Matches: ', var_export( $matches, true ), '</pre>';
    foreach ( $matches[0] as $index => $match )
    {
      $templateFileID = $matches[1][$index];
      //echo '<pre>templateFileID: ', var_export( $templateFileID, true ), '</pre>';
      $dependantFile = $this->getThemeFile( $templateFileID );
      $manifest[ $dependantFile ] = filemtime( $dependantFile );
      $includeContent = file_get_contents( $dependantFile );
      //echo '<pre>Match: ', htmlentities( $match ), '</pre>';
      $content = str_replace( $match, $includeContent, $content);
    }
    $content .= PHP_EOL . '<!-- Compiled: ' . date('Y-m-d H:i:s') . ' -->';
    // echo '<pre>Compiled:', PHP_EOL, htmlentities( $content ), '</pre>';
    file_put_contents( $compiledFile, $content );  
    // echo '<pre>Manifest: ', var_export( $manifest, true ), '</pre>';
    file_put_contents( $manifestFile, '<?php return ' . var_export( $manifest, true ) . '; ?>' );
    return $compiledFile;
  }


  public function getFile( $variant = '.html' )
  {
    $compile = false;
    $this->variant = $variant;
    $this->fileBasename .= $variant;
    $manifestFile = $this->getManifestFile();
    $compiledFile = $this->getCompiledFile();
    if ( file_exists( $compiledFile ) )
    {
      $manifest = include( $manifestFile );
      $lastCompileTime = filemtime( $compiledFile );
      foreach ($manifest ?: [] as $dependantFile => $timestamp)
      {
        if ( ! file_exists( $dependantFile ) or 
          $timestamp < filemtime( $dependantFile ) )
        {
          $compile = true;
          break;
        }
      }
    }
    else
    {
      $compile = true;
    }
    $uncompiledFile = $this->getUncompiledFile();
    if ( $compile ) $this->compile( $uncompiledFile, $compiledFile, $manifestFile );
    return $compiledFile;
  }


  public function getThemeFile( $name, $ext = '.html.php' )
  {
     return $this->themesDir . $this->theme . DIRECTORY_SEPARATOR . $name . $ext;
  }


  public function getManifestFile()
  {
    return $this->fileBasename . '_manifest.php';
  }


  public function getCompiledFile()
  {
    return $this->fileBasename . '_compiled.php';
  }
  

  public function getUncompiledFile()
  {
    return $this->fileBasename;
  }


  public function getStylesFile()
  {
    return $this->fileBasename . '.css';
  }


  public function getScriptFile()
  {
    return $this->fileBasename . '.js';
  }

}