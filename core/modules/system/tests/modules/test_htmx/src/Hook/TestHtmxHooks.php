<?php

declare(strict_types=1);

namespace Drupal\test_htmx\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Hooks for the test_htmx module.
 */
class TestHtmxHooks {

  public function __construct(
    protected RouteMatchInterface $routeMatch,
  ) {}

  /**
   * Implements hook_preprocess_HOOK() for html.
   */
  #[Hook('preprocess_html')]
  public function boost(array &$variables): void {
    if ($this->routeMatch->getRouteName() === 'test_htmx.attachments.body') {
      $variables['#attached']['library'][] = 'core/drupal.htmx';
      $variables['attributes']['data-hx-boost'] = 'true';
    }
  }

}
