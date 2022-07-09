<?php namespace F1;

/**
 * F1 View Class - 23 June 2022
 *
 * @author C. Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.4.1 - 09 July 2022
 * 
 */

class View {

  public $name;
  public $viewDir;
  public $themesDir;
  public $viewFileBase;
  public $scripts = [];
  public $styles = [];
  public $variant;
  public $theme;
  public $title;


  public function __construct( array $config )
  {
    $this->name = $config[ 'name' ];
    $this->viewDir = $config[ 'viewDir' ] . DIRECTORY_SEPARATOR;
    $this->themesDir = $config[ 'themesDir' ] . DIRECTORY_SEPARATOR;
    $this->viewFileBase = $this->viewDir . $this->name;
    $this->title = $config[ 'title' ] ?? $this->name;
    $this->theme = $config[ 'theme' ] ?? 'default';
  }


  public function replaceContent( $match, $replace, $content )
  {
    $parts = explode( $match, $content );
    if ( count($parts) !== 2 ) return $content;
    $before = substr( strrchr( $parts[0], "\n" ), 1 );
    if ( ! trim( $before ) )
    {
      $lines = explode( "\n", $replace );
      if ( count( $lines ) > 1 ) $replace = implode( "\n$before", $lines );
    }
    else
    {
      $replace = trim( preg_replace( '/\s\s+/', ' ', $replace ) );
    }
    return $parts[0] . $replace . $parts[1];
  }


  public function compile( $uncompiledFile, $compiledFile,
    $manifestFile, &$manifest, $level = 0 )
  {
    if ( $level++ > 3 ) return '';
    $content = file_get_contents( $uncompiledFile );
    $matches = array();
    $pattern = '/\<include\>(.+)\<\/include\>/';
    preg_match_all($pattern, $content, $matches);
    if ( $matches[0] )
    {
      foreach ( $matches[0] as $index => $match )
      {
        $filePath = $matches[1][ $index ];
        $dependancy = $this->getThemeFile( $filePath );
        $subContent = '/* File not found. */';
        if ( file_exists( $dependancy ))
        {
          $manifest[ $dependancy ] = filemtime( $dependancy );
          $subContent = $this->compile( $dependancy, null, null, $manifest, $level );
        }
        $content = $this->replaceContent( $match, $subContent, $content );
      }
    }
    else
    {
      $matches = array();
      $pattern = '/\<\?php.*(get.*File)\((.*)\).*\?\>/';
      preg_match_all($pattern, $content, $matches);
      // debug_dump( $matches, 'VIEW::compile(), [get*File] matches:' );
      foreach ( $matches[0] as $index => $match )
      {
        $fileGetFn = $matches[1][ $index ];
        $filePath = trim( $matches[2][ $index ], ' \'"' );
        $dependancy = $this->{$fileGetFn}( $filePath );
        $subContent = '/* File not found. */';
        if ( file_exists( $dependancy ))
        {
          $manifest[ $dependancy ] = filemtime( $dependancy );
          $subContent = $this->compile( $dependancy, null, null, $manifest, $level );
        }
        $content = $this->replaceContent( $match, $subContent, $content );
      }
    }
    if ( $level === 1 )
    {
      $pattern = '/ +(?=\<\?php.+\?\>\s*$)/m';
      $content = preg_replace($pattern, '', $content);
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
    $this->viewFileBase .= $variant;
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
    return $this->viewFileBase . '_manifest.php';
  }


  public function getCompiledFile()
  {
    return $this->viewFileBase . '_compiled.php';
  }
  

  public function getUncompiledFile()
  {
    return $this->viewFileBase;
  }


  public function getStylesFile()
  {
    return $this->viewFileBase . '.css';
  }


  public function getScriptFile()
  {
    return $this->viewFileBase . '.js';
  }


  public function useStyleFile( $href )
  {
    $this->styles[] = $href;
  }


  public function useScriptFile( $href )
  {
    $this->scripts[] = $href;
  }  

}