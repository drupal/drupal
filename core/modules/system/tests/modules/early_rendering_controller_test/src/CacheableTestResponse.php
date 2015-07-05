<?php

/**
 * @file
 * Contains \Drupal\early_rendering_controller_test\CacheableTestResponse.
 */

namespace Drupal\early_rendering_controller_test;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Symfony\Component\HttpFoundation\Response;

class CacheableTestResponse extends Response implements CacheableResponseInterface {

  use CacheableResponseTrait;

}
