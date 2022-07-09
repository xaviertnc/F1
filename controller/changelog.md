# F1 Controller - Changelog

## 24 Jun 2022 - Ver 1.0.0
  - Very basic controller with VERY basic request routing logic.

## 01 Jul 2022 - Ver 1.1.0
  - Rename $dir to $fileDir
  - Replace multiple constructor arguments with single $config array arg.
  - Fix $fileDir generation logic.

## 08 Jul 2022 - Ver 1.2.0
  - Added $baseDir property
  - Set $baseDir via $config[ 'controllersBaseDir' ]

## 09 Jul 2022 - Ver 1.3.0
  - Add support for 404 Error page.
  - Added $notFound property. Set `notFound` to the filename of
    your 404 error view. e.g. [path-to-404-file]/404.html
  - Change $fileDir to $controllerDir
  - Change header comment format.
  - Add README content.