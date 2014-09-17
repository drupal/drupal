<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\CsrfAccessCheckTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\CsrfAccessCheck;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Access\CsrfAccessCheck
 * @group Access
 */
class CsrfAccessCheckTest extends UnitTestCase {

  /**
   * The mock CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $csrfToken;

  /**
   * The access checker.
   *
   * @var \Drupal\Core\Access\CsrfAccessCheck
   */
  protected $accessCheck;

  /**
   * The mock user account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  protected function setUp() {
    $this->csrfToken = $this->getMockBuilder('Drupal\Core\Access\CsrfTokenGenerator')
      ->disableOriginalConstructor()
      ->getMock();

    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');

    $this->accessCheck = new CsrfAccessCheck($this->csrfToken);
  }

  /**
   * Tests the access() method with a valid token.
   */
  public function testAccessTokenPass() {
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->with('test_query', '/test-path')
      ->will($this->returnValue(TRUE));

    $route = new Route('/test-path', array(), array('_csrf_token' => 'TRUE'));
    $request = Request::create('/test-path?token=test_query');
    $request->attributes->set('_system_path', '/test-path');
    // Set the _controller_request flag so tokens are validated.
    $request->attributes->set('_controller_request', TRUE);

    $this->assertEquals(AccessResult::allowed()->setCacheable(FALSE), $this->accessCheck->access($route, $request, $this->account));
  }

  /**
   * Tests the access() method with an invalid token.
   */
  public function testAccessTokenFail() {
    $this->csrfToken->expects($this->once())
      ->method('validate')
      ->with('test_query', '/test-path')
      ->will($this->returnValue(FALSE));

    $route = new Route('/test-path', array(), array('_csrf_token' => 'TRUE'));
    $request = Request::create('/test-path?token=test_query');
    $request->attributes->set('_system_path', '/test-path');
    // Set the _controller_request flag so tokens are validated.
    $request->attributes->set('_controller_request', TRUE);

    $this->assertEquals(AccessResult::forbidden()->setCacheable(FALSE), $this->accessCheck->access($route, $request, $this->account));
  }

  /**
   * Tests the access() method with no _controller_request attribute set.
   *
   * This will default to the AccessManagerInterface::ACCESS_MODE_ANY access conjunction.
   *
   * @see Drupal\Core\Access\AccessManagerInterface::ACCESS_MODE_ANY
   */
  public function testAccessTokenMissAny() {
    $this->csrfToken->expects($this->never())
      ->method('validate');

    $route = new Route('/test-path', array(), array('_csrf_token' => 'TRUE'));
    $request = new Request(array(
      'token' => 'test_query',
    ));

    $this->assertEquals(AccessResult::create()->setCacheable(FALSE), $this->accessCheck->access($route, $request, $this->account));
  }

  /**
   * Tests the access() method with no _controller_request attribute set.
   *
   * This will use the AccessManagerInterface::ACCESS_MODE_ALL access conjunction.
   *
   * @see Drupal\Core\Access\AccessManagerInterface::ACCESS_MODE_ALL
   */
  public function testAccessTokenMissAll() {
    $this->csrfToken->expects($this->never())
      ->method('validate');

    $route = new Route('/test-path', array(), array('_csrf_token' => 'TRUE'), array('_access_mode' => AccessManagerInterface::ACCESS_MODE_ALL));
    $request = new Request(array(
      'token' => 'test_query',
    ));

    $this->assertEquals(AccessResult::allowed()->setCacheable(FALSE), $this->accessCheck->access($route, $request, $this->account));
  }

}
