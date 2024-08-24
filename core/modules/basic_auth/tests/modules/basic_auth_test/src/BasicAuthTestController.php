<?php

declare(strict_types=1);

namespace Drupal\basic_auth_test;

class BasicAuthTestController {

  /**
   * @see \Drupal\basic_auth\Tests\Authentication\BasicAuthTest::testControllerNotCalledBeforeAuth()
   */
  public function modifyState() {
    \Drupal::state()->set('basic_auth_test.state.controller_executed', TRUE);
    return ['#markup' => 'Done'];
  }

  /**
   * @see \Drupal\basic_auth\Tests\Authentication\BasicAuthTest::testControllerNotCalledBeforeAuth()
   */
  public function readState() {
    // Mark this page as being uncacheable.
    \Drupal::service('page_cache_kill_switch')->trigger();

    return [
      '#markup' => \Drupal::state()->get('basic_auth_test.state.controller_executed') ? 'yep' : 'nope',
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
