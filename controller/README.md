# F1 Controller Class

```
include $app->vendorsDir . '/f1/controller/controller.php';

use F1\Controller;

$app->themeDir = $app->themesDir . DIRECTORY_SEPARATOR . $app->theme; 

$app->controller = new Controller( [
  'controllersBaseDir' => $app->contentDir,
  'controllerFilePath' => $http->req->path ?: $app->homePage,
  'notFound' => $app->themeDir . DIRECTORY_SEPARATOR . '404.html',
  'name' => $http->req->path ? end( $http->req->segments ) : $app->homePage
] );

```