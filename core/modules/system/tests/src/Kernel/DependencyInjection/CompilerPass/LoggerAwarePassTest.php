<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\DependencyInjection\CompilerPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\logger_aware_test\LoggerAwareStub;
use Drupal\logger_aware_test\LoggerStub;
use Psr\Log\LoggerInterface;

/**
 * Tests the logger aware compiler pass.
 *
 * @group system
 * @coversDefaultClass \Drupal\Core\DependencyInjection\Compiler\LoggerAwarePass
 */
class LoggerAwarePassTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'logger_aware_test',
  ];

  /**
   * Tests that the logger aware compiler pass works.
   *
   * @covers ::process
   */
  public function testLoggerAwarePass(): void {
    $container = $this->container;
    $logger = $container->get('logger.channel.logger_aware_test');
    $this->assertInstanceOf(LoggerInterface::class, $logger);
    $logger_aware_stub = $container->get('logger_aware_test.logger_aware_stub');
    $this->assertInstanceOf(LoggerAwareStub::class, $logger_aware_stub);
    $this->assertSame($logger, $logger_aware_stub->getLogger());
  }

  /**
   * Tests that existing loggers are not overwritten.
   *
   * @covers ::process
   */
  public function testExistingLogger(): void {
    $container = $this->container;
    $logger_aware_stub = $container->get('logger_aware_test.logger_aware_existing');
    $logger = $logger_aware_stub->getLogger();
    $this->assertInstanceOf(LoggerStub::class, $logger);
  }

}
