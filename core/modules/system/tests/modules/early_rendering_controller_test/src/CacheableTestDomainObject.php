<?php

namespace Drupal\early_rendering_controller_test;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\UncacheableDependencyTrait;

class CacheableTestDomainObject extends TestDomainObject implements CacheableDependencyInterface {

  use UncacheableDependencyTrait;

}
