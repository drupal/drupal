<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\RouteProcessorCsrfTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Access\RouteProcessorCsrf;
use Symfony\Component\Routing\Route;

/**
 * Tests the CSRF route processor.
 *
 * @see Drupal
 * @see Routing
 *
 * @see \Drupal\Core\Access\RouteProcessorCsrf
 */
class RouteProcessorCsrfTest extends UnitTestCase {

  /**
   * The mock CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $csrfToken;

  /**
   * The route processor.
   *
   * @var \Drupal\Core\Access\RouteProcessorCsrf
   */
  protected $processor;

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

    $this->processor = new RouteProcessorCsrf($this->csrfToken);
  }

  /**
 * Tests the processOutbound() method with no _csrf_token route requirement.
 */
  public function testProcessOutboundNoRequirement() {
    $this->csrfToken->expects($this->never())
      ->method('get');

    $route = new Route('');
    $parameters = array();

    $this->processor->processOutbound($route, $parameters);
    // No parameters should be added to the parameters array.
    $this->assertEmpty($parameters);
  }

  /**
   * Tests the processOutbound() method with a _csrf_token route requirement.
   */
  public function testProcessOutbound() {
    $this->csrfToken->expects($this->once())
      ->method('get')
      ->with('test')
      ->will($this->returnValue('test_token'));

    $route = new Route('', array(), array('_csrf_token' => 'test'));
    $parameters = array();

    $this->processor->processOutbound($route, $parameters);
    // 'token' should be added to the parameters array.
    $this->assertArrayHasKey('token', $parameters);
    $this->assertSame($parameters['token'], 'test_token');
  }

}
