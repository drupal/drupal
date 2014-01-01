<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\CsrfAccessCheckTest.
 */

namespace Drupal\Tests\Core\Access;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Access\CsrfAccessCheck;
use Drupal\Core\Access\AccessInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the CSRF access checker.
 *
 * @group Drupal
 * @group Access
 *
 * @see \Drupal\Core\Access\CsrfAccessCheck
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

  public static function getInfo() {
    return array(
      'name' => 'CSRF access checker',
      'description' => 'Tests CSRF access control for routes.',
      'group' => 'Routing',
    );
  }

  public function setUp() {
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

    $this->assertSame(AccessInterface::ALLOW, $this->accessCheck->access($route, $request, $this->account));
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

    $this->assertSame(AccessInterface::KILL, $this->accessCheck->access($route, $request, $this->account));
  }

  /**
   * Tests the access() method with no _controller_request attribute set.
   *
   * This will default to the 'ANY' access conjunction.
   */
  public function testAccessTokenMissAny() {
    $this->csrfToken->expects($this->never())
      ->method('validate');

    $route = new Route('/test-path', array(), array('_csrf_token' => 'TRUE'));
    $request = new Request(array(
      'token' => 'test_query',
    ));

    $this->assertSame(AccessInterface::DENY, $this->accessCheck->access($route, $request, $this->account));
  }

  /**
   * Tests the access() method with no _controller_request attribute set.
   *
   * This will use the 'ALL' access conjunction.
   */
  public function testAccessTokenMissAll() {
    $this->csrfToken->expects($this->never())
      ->method('validate');

    $route = new Route('/test-path', array(), array('_csrf_token' => 'TRUE'), array('_access_mode' => 'ALL'));
    $request = new Request(array(
      'token' => 'test_query',
    ));

    $this->assertSame(AccessInterface::ALLOW, $this->accessCheck->access($route, $request, $this->account));
  }

}
