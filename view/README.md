# F1 View Class

```
include $app->vendorsDir . '/f1/view/view.php';

use F1\View;

$view = new View( [
  'name'      => $app->controller->name,
  'viewDir'   => $app->controller->controllerDir, 
  'themesDir' => $app->themesDir
] );

$app->view = $view;

```