# F1 View - Changelog

## 23 June 2022 - Ver 1.0.0
  - Initial commit

## 24 June 2022 - Ver 1.1.0
  - Remove $app object dependancies

## 01 July 2022 - Ver 1.2.0
  - Rename $dir to $fileDir
  - Replace multiple constructor arguments with single $config array arg.
  - Change default getFile extensions to ".html.php"

## 02 July 2022 - Ver 1.3.0
  - Add $variant
  - Add $fileBasename
  - Add basic compile & manifest functions (i.e. view caching)

## 02 July 2022 - Ver 1.3.1
  - Add replaceContent() to properly indent imported content.

## 08 July 2022 - Ver 1.4.0
  - Improve view->compile().
    * Compile now supports `<include>theme/file/path</include>`  
        in place of `<?php include getThemeFile( 'theme/file/path' ); ?>` !!!
    * Compile now removes white-space infront of single line `<?php` statements  
      with nothing else on the same line to allow indented `<?php` tags in  
      templates without breaking the final HTML output's formatting.  