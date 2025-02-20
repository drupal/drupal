<?php

declare(strict_types=1);

namespace Drupal\early_rendering_controller_test;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Cache\CacheableResponseTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cacheable response used for testing.
 */
class CacheableTestResponse extends Response implements CacheableResponseInterface {

  use CacheableResponseTrait;

}
