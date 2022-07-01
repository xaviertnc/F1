# F1 Template - Changelog

## 31 May 2014 - Ver 1.0.0
  - Added Tabs compiler + Improved @section / @stop regex

## 27 Nov 2016 - Ver 1.1.0
  - Fix issue with malformed .meta files caused by missing @include template files.
  - Added "$debug" option to Template::render to improve debugability of template errors.

## 10 Jan 2017 - Ver 1.2.0
   - If e.g @foreach(..) is NOT directly followed with HTML, insert NL before content HTML!
   - Improved @open, @else and @close tag type regexes! MUCH simpler and faster + Better output formatting.
   - Did same for @unless and {{\~ ... \~}} statements

## 06 Feb 2017 - Ver 1.2.1
  - Fix error with multi-line STATEMENT regex. Added single-line (/s) option to regex!  {{\~ ... \~}}

## 25 Mar 2017 - Ver 1.2.2
  - Fix if|while|for etc. detect Regex to properly handle nested brackets!

## 05 Oct 2017 - Ver 1.3.0
  - @include directive arguments can now be PHP statements.

## 09 Feb 2018 - Ver 1.4.0
 - Fix issue with muti-level template inheritance! Sections needed to be updated instead of
   the templateString for mid-level compile steps.
 - Added a new directive: @require('some/file/name') and made @include('another/file) optional.
   i.e. @include won't throw an error if the file doesn't exist, but @require will.

## 13 Feb 2018 - Ver 1.4.1
 - Fix issue with overriding section content over multiple levels. (More like completing unfinished features,
    since I noted that it doesn't work way back when! The same goes for the multi-level update above. )
 - Fix issue with not following @parent directives all the way to the top-most level.

## 25 May 2018 - Ver 1.5.0
 - Change "@section .. @show" directive to "@yieldDefault .. @show and make the feature work correctly!
 - Added Template::leftFlushContent() to ensure DEFAULT yield content AND section content
   always align correctly.
 - Improve Template::compileYield() to properly detect and handle the top-most template level.

## 23 Jun 2022 - Ver 1.6.0
 - Move general doc comments and version history into their own files.
 - Comment out more Log::template() statements.

> TODO: Remove rarely used features. Simplify as is the OneFile motto!
> TODO: Cleanup Code and Comments
