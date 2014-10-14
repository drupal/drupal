<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Logger\LoggerChannelFactoryTest.
 */

namespace Drupal\Tests\Core\Logger;

use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

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
    $factory->setContainer($this->getMock('Symfony\Component\DependencyInjection\ContainerInterface'));

    // Ensure that when called with the same argument, always the same instance
    // will be returned.
    $this->assertSame($factory->get('test'), $factory->get('test'));
  }

}
