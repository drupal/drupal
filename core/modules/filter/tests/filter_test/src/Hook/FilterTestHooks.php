<?php

declare(strict_types=1);

namespace Drupal\filter_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for filter_test.
 */
class FilterTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('filter_format_insert')]
  public function filterFormatInsert($format) {
    \Drupal::messenger()->addStatus('hook_filter_format_insert invoked.');
  }

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('filter_format_update')]
  public function filterFormatUpdate($format) {
    \Drupal::messenger()->addStatus('hook_filter_format_update invoked.');
  }

  /**
   * Implements hook_filter_format_disable().
   */
  #[Hook('filter_format_disable')]
  public function filterFormatDisable($format) {
    \Drupal::messenger()->addStatus('hook_filter_format_disable invoked.');
  }

}
