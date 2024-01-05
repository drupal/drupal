<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\DefaultAccessCheck;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Access\DefaultAccessCheck
 * @group Access
 */
class DefaultAccessCheckTest extends UnitTestCase {

  /**
   * The access checker to test.
   *
   * @var \Drupal\Core\Access\DefaultAccessCheck
   */
  protected $accessChecker;

  /**
   * The mocked account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->account = $this->createMock('Drupal\Core\Session\AccountInterface');
    $this->accessChecker = new DefaultAccessCheck();
  }

  /**
   * Tests the access method.
   */
  public function testAccess() {
    $request = new Request([]);

    $route = new Route('/test-route', [], ['_access' => 'NULL']);
    $this->assertEquals(AccessResult::neutral(), $this->accessChecker->access($route, $request, $this->account));

    $route = new Route('/test-route', [], ['_access' => 'FALSE']);
    $this->assertEquals(AccessResult::forbidden(), $this->accessChecker->access($route, $request, $this->account));

    $route = new Route('/test-route', [], ['_access' => 'TRUE']);
    $this->assertEquals(AccessResult::allowed(), $this->accessChecker->access($route, $request, $this->account));
  }

}
