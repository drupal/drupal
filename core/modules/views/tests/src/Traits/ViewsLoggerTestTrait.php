<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Traits;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Provides helper functions for logging in views.
 */
trait ViewsLoggerTestTrait {

  /**
   * Sets up a mock logger for when views can't load an entity.
   */
  public function setUpMockLoggerWithMissingEntity(): void {
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerInterface::class);
    $logger->expects($this->once())
      ->method('error')
      ->with(
        'The view %id failed to load an entity of type %entity_type at row %index for field %field',
        $this->anything(),
      );

    $loggerFactory->expects($this->once())
      ->method('get')
      ->willReturn($logger);

    $container = new ContainerBuilder();
    $container->set('logger.factory', $loggerFactory);
    \Drupal::setContainer($container);
  }

}
