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


  public function compile( $uncompiledFile, $compiledFile,
    $manifestFile, &$manifest, $level = 0 )
  {
    if ( $level++ > 3 ) return '';
    $content = file_get_contents( $uncompiledFile );    
    $pattern = '/\<\?php.*(get.*File)\((.*)\).*\?\>/';
    $matches = array();
    preg_match_all($pattern, $content, $matches);
    //echo '<pre>Matches: ', var_export( $matches, true ), '</pre>';
    foreach ( $matches[0] as $index => $match )
    {
      $getterFn = $matches[1][ $index ];
      $getterParam = trim( $matches[2][ $index ], ' \'"' );
      //echo '<pre>getterParam: ', var_export( $getterParam, true ), '</pre>';
      $dependancy = $this->{$getterFn}( $getterParam );
      $manifest[ $dependancy ] = filemtime( $dependancy );
      $subContent = $this->compile( $dependancy, null, null, $manifest, $level );
      $content = str_replace( $match, $subContent, $content );
    }
    if ( $level === 1 )
    {
      $manifestContent = '<?php return ' . var_export( $manifest, true ) . '; ?>';
      $content .= PHP_EOL . '<!-- Compiled: ' . date('Y-m-d H:i:s') . ' -->';
      file_put_contents( $manifestFile, $manifestContent );
      file_put_contents( $compiledFile, $content );  
    }
    return $content;
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
      foreach ($manifest ?: [] as $dependancy => $timestamp)
      {
        if ( ! file_exists( $dependancy ) or 
          $timestamp < filemtime( $dependancy ) )
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
    if ( $compile ) {
      $manifest = array( $uncompiledFile => filemtime( $uncompiledFile ) );
      $this->compile( $uncompiledFile, $compiledFile, $manifestFile, $manifest );
    }
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