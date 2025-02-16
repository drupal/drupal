<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin\Discovery;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator
 * @group Plugin
 */
class ContainerDerivativeDiscoveryDecoratorTest extends UnitTestCase {

  /**
   * @covers ::getDefinitions
   */
  public function testGetDefinitions(): void {
    $example_service = $this->createMock('Symfony\Contracts\EventDispatcher\EventDispatcherInterface');
    $example_container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
      ->onlyMethods(['get'])
      ->getMock();
    $example_container->expects($this->once())
      ->method('get')
      ->with($this->equalTo('example_service'))
      ->willReturn($example_service);

    \Drupal::setContainer($example_container);

    $definitions = [];
    $definitions['container_aware_discovery'] = [
      'id' => 'container_aware_discovery',
      'deriver' => '\Drupal\Tests\Core\Plugin\Discovery\TestContainerDerivativeDiscovery',
    ];
    $definitions['non_container_aware_discovery'] = [
      'id' => 'non_container_aware_discovery',
      'deriver' => '\Drupal\Tests\Core\Plugin\Discovery\TestDerivativeDiscovery',
    ];

    $discovery_main = $this->createMock('Drupal\Component\Plugin\Discovery\DiscoveryInterface');
    $discovery_main->expects($this->any())
      ->method('getDefinitions')
      ->willReturn($definitions);

    $discovery = new ContainerDerivativeDiscoveryDecorator($discovery_main);
    $definitions = $discovery->getDefinitions();

    // Ensure that both the instances from container and non-container test
    // derivatives got added.
    $this->assertCount(4, $definitions);
  }

}
