<?php

declare(strict_types=1);

namespace Drupal\help_test\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for help_test.
 */
class HelpTestHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    // Do not implement a module overview page to test an empty implementation.
    // @see \Drupal\help\Tests\HelpTest
  }

}
