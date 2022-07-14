# F1 HTTP - Changelog

## 24 June 2022 - Ver 1.0.0
 - Move `Request` + `Response` under new `HTTP` service class.

## 08 July 2022 - Ver 1.1.0
 - Change how the `baseUri` property is initialized and used.
 - BaseUrl can now have slashes in-front and behind and still be ok.

## 14 July 2022 - Ver 1.2.0
 - Remove the `query part` after the '?' from the request URI  
   to ensure the controller interprets the path segments correctly. 