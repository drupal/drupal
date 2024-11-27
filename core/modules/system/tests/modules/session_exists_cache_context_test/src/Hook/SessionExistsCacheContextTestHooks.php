<?php

declare(strict_types=1);

namespace Drupal\session_exists_cache_context_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for session_exists_cache_context_test.
 */
class SessionExistsCacheContextTestHooks {

  /**
   * Implements hook_page_top().
   */
  #[Hook('page_top')]
  public function pageTop(array &$page_top): void {
    // Ensure this hook is invoked on every page load.
    $page_top['#cache']['max-age'] = 0;
    $request = \Drupal::request();
    $session_exists = \Drupal::service('session_configuration')->hasSession($request);
    $page_top['session_exists_cache_context_test'] = [
      'label' => [
        '#markup' => '<p>' . ($session_exists ? 'Session exists!' : 'Session does not exist!') . '</p>',
      ],
      'cache_context_value' => [
        '#markup' => '<code>[session.exists]=' . \Drupal::service('cache_context.session.exists')->getContext() . '</code>',
      ],
    ];
    $request = \Drupal::request();
    if ($request->query->get('trigger_session')) {
      $request->getSession()->set('session_exists_cache_context_test', TRUE);
    }
  }

}
