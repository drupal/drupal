<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Element\LayoutBuilder;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the deprecation notices of the layout builder element.
 *
 * @coversDefaultClass \Drupal\layout_builder\Element\LayoutBuilder
 *
 * @group layout_builder
 */
class LayoutBuilderElementTest extends KernelTestBase {

  /**
   * @group legacy
   */
  public function testConstructorTempStoreDeprecation() {
    $this->expectDeprecation('The event_dispatcher service should be passed to LayoutBuilder::__construct() instead of the layout_builder.tempstore_repository service since 9.1.0. This will be required in Drupal 10.0.0. See https://www.drupal.org/node/3152690');
    $layout_temp_storage = $this->prophesize(LayoutTempstoreRepositoryInterface::class);
    $element = new LayoutBuilder(
      [],
      'element_id',
      [],
      $layout_temp_storage->reveal()
    );
    $this->assertNotNull($element);
  }

  /**
   * @group legacy
   */
  public function testConstructorMessengerDeprecation() {
    $this->expectDeprecation('Calling LayoutBuilder::__construct() with the $messenger argument is deprecated in drupal:9.1.0 and will be removed in drupal:10.0.0. See https://www.drupal.org/node/3152690');
    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $messenger = $this->prophesize(MessengerInterface::class);

    $element = new LayoutBuilder(
      [],
      'element_id',
      [],
      $event_dispatcher->reveal(),
      $messenger->reveal()
    );
    $this->assertNotNull($element);
  }

}
