<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray
 * @group layout_builder
 */
class BlockComponentRenderArrayTest extends UnitTestCase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->blockManager = $this->prophesize(BlockManagerInterface::class);
    $this->account = $this->prophesize(AccountInterface::class);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.block', $this->blockManager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::onBuildRender
   */
  public function testOnBuildRender() {
    $block = $this->prophesize(BlockPluginInterface::class);
    $access_result = AccessResult::allowed();
    $block->access($this->account->reveal(), TRUE)->willReturn($access_result)->shouldBeCalled();
    $block->getCacheContexts()->willReturn([]);
    $block->getCacheTags()->willReturn(['test']);
    $block->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $block->getConfiguration()->willReturn([]);
    $block->getPluginId()->willReturn('block_plugin_id');
    $block->getBaseId()->willReturn('block_plugin_id');
    $block->getDerivativeId()->willReturn(NULL);

    $block_content = ['#markup' => 'The block content.'];
    $block->build()->willReturn($block_content);
    $this->blockManager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn($block->reveal());

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $contexts = [];
    $in_preview = FALSE;
    $event = new SectionComponentBuildRenderArrayEvent($component, $contexts, $in_preview);

    $subscriber = new BlockComponentRenderArray($this->account->reveal());

    $expected_build = [
      '#theme' => 'block',
      '#weight' => 0,
      '#configuration' => [],
      '#plugin_id' => 'block_plugin_id',
      '#base_plugin_id' => 'block_plugin_id',
      '#derivative_plugin_id' => NULL,
      'content' => $block_content,
    ];

    $expected_cache = $expected_build + [
      '#cache' => [
        'contexts' => [],
        'tags' => ['test'],
        'max-age' => -1,
      ],
    ];

    $subscriber->onBuildRender($event);
    $result = $event->getBuild();
    $this->assertEquals($expected_build, $result);
    $event->getCacheableMetadata()->applyTo($result);
    $this->assertEquals($expected_cache, $result);
  }

  /**
   * @covers ::onBuildRender
   */
  public function testOnBuildRenderDenied() {
    $block = $this->prophesize(BlockPluginInterface::class);
    $access_result = AccessResult::forbidden();
    $block->access($this->account->reveal(), TRUE)->willReturn($access_result)->shouldBeCalled();
    $block->getCacheContexts()->shouldNotBeCalled();
    $block->getCacheTags()->shouldNotBeCalled();
    $block->getCacheMaxAge()->shouldNotBeCalled();
    $block->getConfiguration()->shouldNotBeCalled();
    $block->getPluginId()->shouldNotBeCalled();
    $block->getBaseId()->shouldNotBeCalled();
    $block->getDerivativeId()->shouldNotBeCalled();

    $block_content = ['#markup' => 'The block content.'];
    $block->build()->willReturn($block_content);
    $this->blockManager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn($block->reveal());

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $contexts = [];
    $in_preview = FALSE;
    $event = new SectionComponentBuildRenderArrayEvent($component, $contexts, $in_preview);

    $subscriber = new BlockComponentRenderArray($this->account->reveal());

    $expected_build = [];
    $expected_cache = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
    ];

    $subscriber->onBuildRender($event);
    $result = $event->getBuild();
    $this->assertEquals($expected_build, $result);
    $event->getCacheableMetadata()->applyTo($result);
    $this->assertEquals($expected_cache, $result);
  }

  /**
   * @covers ::onBuildRender
   */
  public function testOnBuildRenderInPreview() {
    $block = $this->prophesize(BlockPluginInterface::class);
    $block->access($this->account->reveal(), TRUE)->shouldNotBeCalled();
    $block->getCacheContexts()->willReturn([]);
    $block->getCacheTags()->willReturn(['test']);
    $block->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $block->getConfiguration()->willReturn([]);
    $block->getPluginId()->willReturn('block_plugin_id');
    $block->getBaseId()->willReturn('block_plugin_id');
    $block->getDerivativeId()->willReturn(NULL);

    $block_content = ['#markup' => 'The block content.'];
    $block->build()->willReturn($block_content);
    $this->blockManager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn($block->reveal());

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $contexts = [];
    $in_preview = TRUE;
    $event = new SectionComponentBuildRenderArrayEvent($component, $contexts, $in_preview);

    $subscriber = new BlockComponentRenderArray($this->account->reveal());

    $expected_build = [
      '#theme' => 'block',
      '#weight' => 0,
      '#configuration' => [],
      '#plugin_id' => 'block_plugin_id',
      '#base_plugin_id' => 'block_plugin_id',
      '#derivative_plugin_id' => NULL,
      'content' => $block_content,
    ];

    $expected_cache = $expected_build + [
      '#cache' => [
        'contexts' => [],
        'tags' => ['test'],
        'max-age' => 0,
      ],
    ];

    $subscriber->onBuildRender($event);
    $result = $event->getBuild();
    $this->assertEquals($expected_build, $result);
    $event->getCacheableMetadata()->applyTo($result);
    $this->assertEquals($expected_cache, $result);
  }

  /**
   * @covers ::onBuildRender
   */
  public function testOnBuildRenderNoBlock() {
    $this->blockManager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn(NULL);

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $contexts = [];
    $in_preview = FALSE;
    $event = new SectionComponentBuildRenderArrayEvent($component, $contexts, $in_preview);

    $subscriber = new BlockComponentRenderArray($this->account->reveal());

    $expected_build = [];
    $expected_cache = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
    ];

    $subscriber->onBuildRender($event);
    $result = $event->getBuild();
    $this->assertEquals($expected_build, $result);
    $event->getCacheableMetadata()->applyTo($result);
    $this->assertEquals($expected_cache, $result);
  }

}
