<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Logger;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Logger\LoggerChannelFactory
 * @group Logger
 */
class LoggerChannelFactoryTest extends UnitTestCase {

  /**
   * Tests LoggerChannelFactory::get().
   *
   * @covers ::get
   */
  public function testGet(): void {
    $factory = new LoggerChannelFactory(
      $this->createMock(RequestStack::class),
      $this->createMock(AccountInterface::class),
    );

    // Ensure that when called with the same argument, always the same instance
    // will be returned.
    $this->assertSame($factory->get('test'), $factory->get('test'));
  }

}

/**
 * Call to test a logger channel class with no constructor.
 */
class LoggerChannelWithoutConstructor extends LoggerChannelFactory {

  public function __construct() {}

}
