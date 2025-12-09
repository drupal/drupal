<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\PageCache;

use Drupal\Core\PageCache\ChainResponsePolicy;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests Drupal\Core\PageCache\ChainResponsePolicy.
 */
#[CoversClass(ChainResponsePolicy::class)]
#[Group('PageCache')]
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

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->policy = new ChainResponsePolicy();
    $this->response = new Response();
    $this->request = new Request();
  }

  /**
   * Asserts that check() returns NULL if the chain is empty.
   *
   * @legacy-covers ::check
   */
  public function testEmptyChain(): void {
    $result = $this->policy->check($this->response, $this->request);
    $this->assertNull($result);
  }

  /**
   * Asserts that check() returns NULL if a rule returns NULL.
   *
   * @legacy-covers ::check
   */
  public function testNullRuleChain(): void {
    $rule = $this->createMock('Drupal\Core\PageCache\ResponsePolicyInterface');
    $rule->expects($this->once())
      ->method('check')
      ->with($this->response, $this->request)
      ->willReturn(NULL);

    $this->policy->addPolicy($rule);

    $result = $this->policy->check($this->response, $this->request);
    $this->assertNull($result);
  }

  /**
   * Asserts that check() throws an exception if a rule returns an invalid value.
   *
   * @legacy-covers ::check
   */
  #[DataProvider('providerChainExceptionOnInvalidReturnValue')]
  public function testChainExceptionOnInvalidReturnValue($return_value): void {
    $rule = $this->createMock('Drupal\Core\PageCache\ResponsePolicyInterface');
    $rule->expects($this->once())
      ->method('check')
      ->with($this->response, $this->request)
      ->willReturn($return_value);

    $this->policy->addPolicy($rule);

    $this->expectException(\UnexpectedValueException::class);
    $this->policy->check($this->response, $this->request);
  }

  /**
   * Provides test data for testChainExceptionOnInvalidReturnValue.
   *
   * @return array
   *   Test input and expected result.
   */
  public static function providerChainExceptionOnInvalidReturnValue(): array {
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
  public function testStopChainOnFirstDeny(): void {
    $rule1 = $this->createMock('Drupal\Core\PageCache\ResponsePolicyInterface');
    $rule1->expects($this->once())
      ->method('check')
      ->with($this->response, $this->request);
    $this->policy->addPolicy($rule1);

    $deny_rule = $this->createMock('Drupal\Core\PageCache\ResponsePolicyInterface');
    $deny_rule->expects($this->once())
      ->method('check')
      ->with($this->response, $this->request)
      ->willReturn(ResponsePolicyInterface::DENY);
    $this->policy->addPolicy($deny_rule);

    $ignored_rule = $this->createMock('Drupal\Core\PageCache\ResponsePolicyInterface');
    $ignored_rule->expects($this->never())
      ->method('check');
    $this->policy->addPolicy($ignored_rule);

    $actual_result = $this->policy->check($this->response, $this->request);
    $this->assertSame(ResponsePolicyInterface::DENY, $actual_result);
  }

}
