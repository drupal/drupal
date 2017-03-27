<?php

namespace Drupal\Tests\Core\PageCache;

use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ChainRequestPolicy;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \Drupal\Core\PageCache\ChainRequestPolicy
 * @group PageCache
 */
class ChainRequestPolicyTest extends UnitTestCase {

  /**
   * The chain request policy under test.
   *
   * @var \Drupal\Core\PageCache\ChainRequestPolicy
   */
  protected $policy;

  /**
   * A request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  protected function setUp() {
    $this->policy = new ChainRequestPolicy();
    $this->request = new Request();
  }

  /**
   * Asserts that check() returns NULL if the chain is empty.
   *
   * @covers ::check
   */
  public function testEmptyChain() {
    $result = $this->policy->check($this->request);
    $this->assertSame(NULL, $result);
  }

  /**
   * Asserts that check() returns NULL if a rule returns NULL.
   *
   * @covers ::check
   */
  public function testNullRuleChain() {
    $rule = $this->getMock('Drupal\Core\PageCache\RequestPolicyInterface');
    $rule->expects($this->once())
      ->method('check')
      ->with($this->request)
      ->will($this->returnValue(NULL));

    $this->policy->addPolicy($rule);

    $result = $this->policy->check($this->request);
    $this->assertSame(NULL, $result);
  }

  /**
   * Asserts that check() throws an exception if a rule returns an invalid value.
   *
   * @dataProvider providerChainExceptionOnInvalidReturnValue
   * @covers ::check
   */
  public function testChainExceptionOnInvalidReturnValue($return_value) {
    $rule = $this->getMock('Drupal\Core\PageCache\RequestPolicyInterface');
    $rule->expects($this->once())
      ->method('check')
      ->with($this->request)
      ->will($this->returnValue($return_value));

    $this->policy->addPolicy($rule);

    $this->setExpectedException(\UnexpectedValueException::class);
    $this->policy->check($this->request);
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
   * Asserts that check() returns ALLOW if any of the rules returns ALLOW.
   *
   * @dataProvider providerAllowIfAnyRuleReturnedAllow
   * @covers ::check
   */
  public function testAllowIfAnyRuleReturnedAllow($return_values) {
    foreach ($return_values as $return_value) {
      $rule = $this->getMock('Drupal\Core\PageCache\RequestPolicyInterface');
      $rule->expects($this->once())
        ->method('check')
        ->with($this->request)
        ->will($this->returnValue($return_value));

      $this->policy->addPolicy($rule);
    }

    $actual_result = $this->policy->check($this->request);
    $this->assertSame(RequestPolicyInterface::ALLOW, $actual_result);
  }

  /**
   * Provides test data for testAllowIfAnyRuleReturnedAllow.
   *
   * @return array
   *   Test input and expected result.
   */
  public function providerAllowIfAnyRuleReturnedAllow() {
    return [
      [[RequestPolicyInterface::ALLOW]],
      [[NULL, RequestPolicyInterface::ALLOW]],
    ];
  }

  /**
   * Asserts that check() returns immediately when a rule returned DENY.
   */
  public function testStopChainOnFirstDeny() {
    $rule1 = $this->getMock('Drupal\Core\PageCache\RequestPolicyInterface');
    $rule1->expects($this->once())
      ->method('check')
      ->with($this->request)
      ->will($this->returnValue(RequestPolicyInterface::ALLOW));
    $this->policy->addPolicy($rule1);

    $deny_rule = $this->getMock('Drupal\Core\PageCache\RequestPolicyInterface');
    $deny_rule->expects($this->once())
      ->method('check')
      ->with($this->request)
      ->will($this->returnValue(RequestPolicyInterface::DENY));
    $this->policy->addPolicy($deny_rule);

    $ignored_rule = $this->getMock('Drupal\Core\PageCache\RequestPolicyInterface');
    $ignored_rule->expects($this->never())
      ->method('check');
    $this->policy->addPolicy($ignored_rule);

    $actual_result = $this->policy->check($this->request);
    $this->assertsame(RequestPolicyInterface::DENY, $actual_result);
  }

}
