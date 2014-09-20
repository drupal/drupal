<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\PageCache\ChainResponsePolicyTest.
 */

namespace Drupal\Tests\Core\PageCache;

use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\PageCache\ChainResponsePolicy;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \Drupal\Core\PageCache\ChainResponsePolicy
 * @group PageCache
 */
class ChainResponsePolicyTest extends UnitTestCase {

  /**
   * The chain response policy under test.
   *
   * @var \Drupal\Core\PageCache\ChainResponsePolicy
   */
  protected $policy;

  /**
   * A request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * A response object.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  protected $response;

  public function setUp() {
    $this->policy = new ChainResponsePolicy();
    $this->response = new Response();
    $this->request = new Request();
  }

  /**
   * Asserts that check() returns NULL if the chain is empty.
   *
   * @covers ::check
   */
  public function testEmptyChain() {
    $result = $this->policy->check($this->response, $this->request);
    $this->assertSame(NULL, $result);
  }

  /**
   * Asserts that check() returns NULL if a rule returns NULL.
   *
   * @covers ::check
   */
  public function testNullRuleChain() {
    $rule = $this->getMock('Drupal\Core\PageCache\ResponsePolicyInterface');
    $rule->expects($this->once())
      ->method('check')
      ->with($this->response, $this->request)
      ->will($this->returnValue(NULL));

    $this->policy->addPolicy($rule);

    $result = $this->policy->check($this->response, $this->request);
    $this->assertSame(NULL, $result);
  }

  /**
   * Asserts that check() throws an exception if a rule returns an invalid value.
   *
   * @expectedException \UnexpectedValueException
   * @dataProvider providerChainExceptionOnInvalidReturnValue
   * @covers ::check
   */
  public function testChainExceptionOnInvalidReturnValue($return_value) {
    $rule = $this->getMock('Drupal\Core\PageCache\ResponsePolicyInterface');
    $rule->expects($this->once())
      ->method('check')
      ->with($this->response, $this->request)
      ->will($this->returnValue($return_value));

    $this->policy->addPolicy($rule);

    $actual_result = $this->policy->check($this->response, $this->request);
    $this->assertSame(NULL, $actual_result);
  }

  /**
   * Provides test data for testChainExceptionOnInvalidReturnValue.
   *
   * @return array
   *   Test input and expected result.
   */
  public function providerChainExceptionOnInvalidReturnValue() {
    return [
      [FALSE],
      [0],
      [1],
      [TRUE],
      [[1, 2, 3]],
      [new \stdClass()],
    ];
  }

  /**
   * Asserts that check() returns immediately when a rule returned DENY.
   */
  public function testStopChainOnFirstDeny() {
    $rule1 = $this->getMock('Drupal\Core\PageCache\ResponsePolicyInterface');
    $rule1->expects($this->once())
      ->method('check')
      ->with($this->response, $this->request);
    $this->policy->addPolicy($rule1);

    $deny_rule = $this->getMock('Drupal\Core\PageCache\ResponsePolicyInterface');
    $deny_rule->expects($this->once())
      ->method('check')
      ->with($this->response, $this->request)
      ->will($this->returnValue(ResponsePolicyInterface::DENY));
    $this->policy->addPolicy($deny_rule);

    $ignored_rule = $this->getMock('Drupal\Core\PageCache\ResponsePolicyInterface');
    $ignored_rule->expects($this->never())
      ->method('check');
    $this->policy->addPolicy($ignored_rule);

    $actual_result = $this->policy->check($this->response, $this->request);
    $this->assertsame(ResponsePolicyInterface::DENY, $actual_result);
  }

}
