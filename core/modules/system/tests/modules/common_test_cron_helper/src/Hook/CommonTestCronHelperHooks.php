<?php

declare(strict_types=1);

namespace Drupal\common_test_cron_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for common_test_cron_helper.
 */
class CommonTestCronHelperHooks {

  /**
   * Implements hook_cron().
   *
   * Function common_test_cron() throws an exception, but the execution should
   * reach this function as well.
   *
   * @see common_test_cron()
   */
  #[Hook('cron')]
  public function cron(): void {
    \Drupal::state()->set('common_test.cron', 'success');
  }

}
