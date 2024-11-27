<?php

declare(strict_types=1);

namespace Drupal\big_pipe_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for big_pipe_test.
 */
class BigPipeTestHooks {

  /**
   * Implements hook_page_top().
   */
  #[Hook('page_top')]
  public function pageTop(array &$page_top): void {
    // Ensure this hook is invoked on every page load.
    $page_top['#cache']['max-age'] = 0;
    $request = \Drupal::request();
    if ($request->query->get('trigger_session')) {
      $request->getSession()->set('big_pipe_test', TRUE);
    }
  }

}
