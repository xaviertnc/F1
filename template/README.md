# F1 Template (aka Switch-Blade)

Template is a PHP Templating class based largely on code from Laravel4's Blade Compiler  
Licensed under the MIT license. Please see LICENSE for more information.  

## What makes it different?

1. All framework and external dependancies are removed. I.e. Only one file!  

2. The templating process is different.  You still get inheritance and partials, but without any runtime  
   including of files!  The entire template is built and cached as one file with all partials and layouts included.  
   This takes care of a number of variable scope issues when dynamically including files at runtime.  
   It could also improve performance?  

3. Template cares about code structure and attempts to preserve indentation where possible.  
   You might want to use it to generate code that looks decent and not just cached files for runtime.  

4. Template rendering is included  

5. Options to cache/save compiled output and specify output filename  

6. Render() echo's the output unless you specify to return the result as a string  

7. Re-compiles if any dependant templates change.  

> TODO: Option to NOT check dependancies (i.e. Production mode)  
> TODO: Option to ignore indenting  
> TODO: Option to minify (Removing all redundant white space and comments)  
> TODO: Add @use('file.tpl', data_array) statement. Like @include, but the content is only fetched and evaluated at runtime.  
  - @use(..) should not compile to html, but rather compile to a PHP render function, like child templates in the old Blade system.  

### By: C. Moller - 11 May 2014  


## User Manual

    // Pattern = [M0:Full Match][M1:Leading Space][M2:Section Name][M3:Section Content]
    //
    // Matches = [
    //
    //   0:FullMatches: [
    //     FullPatternMatch0: [
    //       FullContent0: [match0-full-content],
    //       FullContentOffset0: [offset]
    //     ],
    //     FullPatternMatch1: [
    //       FullContent1: [match1-full-content],
    //       FullContentOffset1: [offset]
    //     ],
    //     ...
    //   ],
    //
    //   1:SubPattern1Matches: [
    //     SubPattern1Match0: [
    //       SubPatternContent0: [match0-leading-space],
    //       SubPatternContentOffset0: [offset]
    //     ],
    //     SubPattern1Match1: [
    //       SubPatternContent1: [match1-leading-space],
    //       SubPatternContentOffset1: [offset]
    //     ],
    //     ...
    //   ],
    //
    //   2:SubPattern2Matches: [
    //     SubPattern2Match0: [
    //       SubPatternContent0: [match0-section-name],
    //       SubPatternContentOffset0: [offset]
    //     ],
    //     SubPattern2Match1: [
    //       SubPatternContent1: [match1-section-name],
    //       SubPatternContentOffset1: [offset]
    //     ],
    //     ...
    //   ],
    //
    //   3:SubPattern3Matches: [
    //     SubPattern3Match0: [
    //       SubPatternContent0: [match0-content-string],
    //       SubPatternContentOffset0: [offset]
    //     ],
    //     SubPattern3Match1: [
    //       SubPatternContent1: [match1-content-string],
    //       SubPatternContentOffset1: [offset]
    //     ],
    //     ...
    //   ]
    // ]