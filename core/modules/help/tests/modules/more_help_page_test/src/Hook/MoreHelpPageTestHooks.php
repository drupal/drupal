<?php

declare(strict_types=1);

namespace Drupal\more_help_page_test\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for more_help_page_test.
 */
class MoreHelpPageTestHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      // Return help for the same route as the help_page_test module.
      case 'help_page_test.test_array':
        return ['#markup' => 'Help text from more_help_page_test_help module.'];
    }
  }

  /**
   * Implements hook_help_section_info_alter().
   */
  #[Hook('help_section_info_alter')]
  public function helpSectionInfoAlter(array &$info): void {
    $info['hook_help']['weight'] = 500;
  }

}
