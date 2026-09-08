<?php

declare(strict_types=1);

namespace Drupal\console_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Hook implementations for the Console test module.
 */
class ConsoleTestHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): string {
    return '';
  }

}
