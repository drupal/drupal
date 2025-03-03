<?php

declare(strict_types=1);

namespace Drupal\help_topics_test\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for help_topics_test.
 */
class HelpTopicsTestHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.help_topics_test':
        return 'Some kind of non-empty output for testing';
    }
    return NULL;
  }

  /**
   * Implements hook_help_topics_info_alter().
   */
  #[Hook('help_topics_info_alter')]
  public function helpTopicsInfoAlter(array &$info): void {
    // To prevent false positive search results limit list to testing topis
    // only.
    $filter = fn(string $key) => str_starts_with($key, 'help_topics_test')
      || in_array($key, ['help_topics_test_direct_yml', 'help_topics_derivatives:test_derived_topic'], TRUE);
    $info = array_filter($info, $filter, ARRAY_FILTER_USE_KEY);
    $info['help_topics_test.test']['top_level'] = \Drupal::state()->get('help_topics_test.test:top_level', TRUE);
  }

}
