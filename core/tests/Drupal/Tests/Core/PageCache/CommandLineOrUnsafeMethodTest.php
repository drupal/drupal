<?php

namespace Drupal\Tests\Core\PageCache;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\PageCache\RequestPolicy\CommandLineOrUnsafeMethod
 * @group PageCache
 */
class CommandLineOrUnsafeMethodTest extends UnitTestCase {

  /**
   * The request policy under test.
   *
   * @var \Drupal\Core\PageCache\RequestPolicy\CommandLineOrUnsafeMethod|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $policy;

  protected function setUp() {
    // Note that it is necessary to partially mock the class under test in
    // order to disable the isCli-check.
    $this->policy = $this->getMockBuilder('Drupal\Core\PageCache\RequestPolicy\CommandLineOrUnsafeMethod')
      ->setMethods(['isCli'])
      ->getMock();
  }

  /**
   * Asserts that check() returns DENY for unsafe HTTP methods.
   *
   * @dataProvider providerTestHttpMethod
   * @covers ::check
   */
  public function testHttpMethod($expected_result, $method) {
    $this->policy->expects($this->once())
      ->method('isCli')
      ->will($this->returnValue(FALSE));

    $request = Request::create('/', $method);
    $actual_result = $this->policy->check($request);
    $this->assertSame($expected_result, $actual_result);
  }

  /**
   * Provides test data and expected results for the HTTP method test.
   *
   * @return array
   *   Test data and expected results.
   */
  public function providerTestHttpMethod() {
    return [
      [NULL, 'GET'],
      [NULL, 'HEAD'],
      [RequestPolicyInterface::DENY, 'POST'],
      [RequestPolicyInterface::DENY, 'PUT'],
      [RequestPolicyInterface::DENY, 'DELETE'],
      [RequestPolicyInterface::DENY, 'OPTIONS'],
      [RequestPolicyInterface::DENY, 'TRACE'],
      [RequestPolicyInterface::DENY, 'CONNECT'],
    ];
  }

  /**
   * Asserts that check() returns DENY if running from the command line.
   *
   * @covers ::check
   */
  public function testIsCli() {
    $this->policy->expects($this->once())
      ->method('isCli')
      ->will($this->returnValue(TRUE));

    $request = Request::create('/', 'GET');
    $actual_result = $this->policy->check($request);
    $this->assertSame(RequestPolicyInterface::DENY, $actual_result);
  }

}
