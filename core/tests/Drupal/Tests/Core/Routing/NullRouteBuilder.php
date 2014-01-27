<?php

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\Routing\RouteBuilderInterface;

class NullRouteBuilder implements RouteBuilderInterface {

  public function rebuild() {
  }

  public function rebuildIfNeeded() {
  }

  public function setRebuildNeeded() {
  }

}
