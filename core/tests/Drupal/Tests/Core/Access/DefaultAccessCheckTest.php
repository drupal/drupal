<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\DefaultAccessCheckTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessInterface;
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
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->accessChecker = new DefaultAccessCheck();
  }

  /**
   * Test the access method.
   */
  public function testAccess() {
    $request = new Request(array());

    $route = new Route('/test-route', array(), array('_access' => 'NULL'));
    $this->assertSame(AccessInterface::DENY, $this->accessChecker->access($route, $request, $this->account));

    $route = new Route('/test-route', array(), array('_access' => 'FALSE'));
    $this->assertSame(AccessInterface::KILL, $this->accessChecker->access($route, $request, $this->account));

    $route = new Route('/test-route', array(), array('_access' => 'TRUE'));
    $this->assertSame(AccessInterface::ALLOW, $this->accessChecker->access($route, $request, $this->account));
  }

}
