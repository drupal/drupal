<?php

declare(strict_types=1);

namespace Drupal\basic_auth_test;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides routes for HTTP Basic Authentication testing.
 */
class BasicAuthTestController extends ControllerBase {

  public function __construct(
    #[Autowire(service: 'page_cache_kill_switch')]
    private ResponsePolicyInterface $pageCacheKillSwitch,
  ) {}

  /**
   * @see \Drupal\basic_auth\Tests\Authentication\BasicAuthTest::testControllerNotCalledBeforeAuth()
   */
  public function modifyState() {
    $this->state()->set('basic_auth_test.state.controller_executed', TRUE);
    return ['#markup' => 'Done'];
  }

  /**
   * @see \Drupal\basic_auth\Tests\Authentication\BasicAuthTest::testControllerNotCalledBeforeAuth()
   */
  public function readState() {
    // Mark this page as being uncacheable.
    $this->pageCacheKillSwitch->trigger();

    return [
      '#markup' => $this->state()->get('basic_auth_test.state.controller_executed') ? 'yep' : 'nope',
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

}
