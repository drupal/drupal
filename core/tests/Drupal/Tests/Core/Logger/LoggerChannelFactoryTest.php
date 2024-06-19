<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Logger;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

  /**
   * @covers ::__construct
   * @group legacy
   */
  public function testConstructorDeprecation(): void {
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('request_stack')
      ->willReturn($this->prophesize(RequestStack::class)->reveal());
    $container->get('current_user')
      ->willReturn($this->prophesize(AccountProxy::class)->reveal());
    \Drupal::setContainer($container->reveal());

    $this->expectDeprecation('Calling Drupal\Core\Logger\LoggerChannelFactory::__construct without the $requestStack argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3416354');
    $this->expectDeprecation('Calling Drupal\Core\Logger\LoggerChannelFactory::__construct without the $currentUser argument is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3416354');
    new LoggerChannelFactory();
  }

  /**
   * @covers ::get
   * @group legacy
   */
  public function testWithoutConstructor(): void {
    $container = $this->prophesize(ContainerInterface::class);
    $container->get('request_stack')
      ->willReturn($this->prophesize(RequestStack::class)->reveal());
    $container->get('current_user')
      ->willReturn($this->prophesize(AccountProxy::class)->reveal());
    \Drupal::setContainer($container->reveal());

    $factory = new LoggerChannelWithoutConstructor();

    $this->expectDeprecation('Calling Drupal\Core\Logger\LoggerChannelFactory::get without calling the constructor is deprecated in drupal:10.3.0 and it will be required in drupal:11.0.0. See https://www.drupal.org/node/3416354');
    $this->assertSame($factory->get('test'), $factory->get('test'));
  }

  /**
   * @covers ::setContainer
   * @group legacy
   */
  public function testDeprecatedSetContainer(): void {
    $factory = new LoggerChannelFactory(
      $this->createMock(RequestStack::class),
      $this->createMock(AccountInterface::class),
    );

    $this->expectDeprecation('Calling Drupal\Core\Logger\LoggerChannelFactory::setContainer() is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use dependency injection instead. See https://www.drupal.org/node/3416354');
    $factory->setContainer();
  }

  /**
   * @covers ::__get
   * @group legacy
   */
  public function testDeprecatedGetContainer(): void {
    $factory = new LoggerChannelFactory(
      $this->createMock(RequestStack::class),
      $this->createMock(AccountInterface::class),
    );

    $container = $this->prophesize(ContainerInterface::class);
    $request_stack = $this->prophesize(RequestStack::class)->reveal();
    $container->get('request_stack')->willReturn($request_stack);
    \Drupal::setContainer($container->reveal());

    $this->expectDeprecation('Accessing the container property in Drupal\Core\Logger\LoggerChannelFactory is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use dependency injection instead. See https://www.drupal.org/node/3416354');
    $this->assertSame($request_stack, $factory->container->get('request_stack'));
  }

}

class LoggerChannelWithoutConstructor extends LoggerChannelFactory {

  public function __construct() {}

}
