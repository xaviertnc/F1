# F1 Debug - User Manual

use F1\Debug;  


$app->debugLevel = ( __ENV__ == 'Production' ) ? 1 : ( __DEBUG__ ? 3 : 2 );  
$app->debugLogFile = $app->storageDir . '/logs/' . date( Debug::$shortDateFormat ) . '.log';  


$app->debug = new Debug( $app->debugLevel, $app->debugLogFile );  


register_shutdown_function( [ $app->debug, 'onShutdown' ] );  


$app->debug->log( 'This is a log message without type...' );  
$app->debug->log( 'This is a log message with type INFO...', 'info' );  
$app->debug->log( $error, 'error' );  


$app->debug->dump( $app );  



## Debug Levels:

Level 0 - No debug log or errors display  
Level 1 - Only log and display Production safe info  
Level 2 - Only display Prod safe info, but log detail  
Level 3 - Log and display detail  