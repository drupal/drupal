<?php

namespace Drupal\Tests\Core\Logger;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Tests\UnitTestCase;

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
  public function testGet() {
    $factory = new LoggerChannelFactory();
    $factory->setContainer($this->createMock('Symfony\Component\DependencyInjection\ContainerInterface'));

    // Ensure that when called with the same argument, always the same instance
    // will be returned.
    $this->assertSame($factory->get('test'), $factory->get('test'));
  }

}
