<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\layout_builder\SectionComponent
 * @group layout_builder
 */
class SectionComponentTest extends UnitTestCase {

  /**
   * @covers ::toRenderArray
   */
  public function testToRenderArray(): void {
    $existing_block = $this->prophesize(BlockPluginInterface::class);
    $existing_block->getPluginId()->willReturn('block_plugin_id');

    $block_manager = $this->prophesize(BlockManagerInterface::class);
    $block_manager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn($existing_block->reveal());

    // Imitate an event subscriber by setting a resulting build on the event.
    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $event_dispatcher
      ->dispatch(Argument::type(SectionComponentBuildRenderArrayEvent::class), LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY)
      ->shouldBeCalled()
      ->will(function ($args) {
        /** @var \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event */
        $event = $args[0];
        $event->setBuild(['#markup' => $event->getPlugin()->getPluginId()]);
        return $event;
      });

    $layout_plugin = $this->prophesize(LayoutInterface::class);
    $layout_plugin->build(Argument::type('array'))->willReturnArgument(0);

    $layout_manager = $this->prophesize(LayoutPluginManagerInterface::class);
    $layout_manager->createInstance('layout_onecol', [])->willReturn($layout_plugin->reveal());

    $container = new ContainerBuilder();
    $container->set('plugin.manager.block', $block_manager->reveal());
    $container->set('event_dispatcher', $event_dispatcher->reveal());
    $container->set('plugin.manager.core.layout', $layout_manager->reveal());
    \Drupal::setContainer($container);

    $expected = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
      '#markup' => 'block_plugin_id',
    ];

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $result = $component->toRenderArray();
    $this->assertEquals($expected, $result);
  }

}
