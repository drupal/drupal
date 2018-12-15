<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\block_content\Access\RefinableDependentAccessInterface;
use Drupal\Component\Plugin\Context\ContextInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Render\PreviewFallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\Access\LayoutPreviewAccessAllowed;
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
   * Dataprovider for test functions that should test block types.
   */
  public function providerBlockTypes() {
    return [
      [TRUE],
      [FALSE],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->blockManager = $this->prophesize(BlockManagerInterface::class);
    $this->account = $this->prophesize(AccountInterface::class);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.block', $this->blockManager->reveal());
    $container->set('context.handler', $this->prophesize(ContextHandlerInterface::class));
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::onBuildRender
   *
   * @dataProvider providerBlockTypes
   */
  public function testOnBuildRender($refinable_dependent_access) {
    $contexts = [];
    if ($refinable_dependent_access) {
      $block = $this->prophesize(TestBlockPluginWithRefinableDependentAccessInterface::class);
      $layout_entity = $this->prophesize(EntityInterface::class);
      $layout_entity = $layout_entity->reveal();
      $context = $this->prophesize(ContextInterface::class);
      $context->getContextValue()->willReturn($layout_entity);
      $contexts['layout_builder.entity'] = $context->reveal();

      $block->setAccessDependency($layout_entity)->shouldBeCalled();
    }
    else {
      $block = $this->prophesize(BlockPluginInterface::class);
    }
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
   *
   * @dataProvider providerBlockTypes
   */
  public function testOnBuildRenderDenied($refinable_dependent_access) {
    $contexts = [];
    if ($refinable_dependent_access) {
      $block = $this->prophesize(TestBlockPluginWithRefinableDependentAccessInterface::class);

      $layout_entity = $this->prophesize(EntityInterface::class);
      $layout_entity = $layout_entity->reveal();
      $context = $this->prophesize(ContextInterface::class);
      $context->getContextValue()->willReturn($layout_entity);
      $contexts['layout_builder.entity'] = $context->reveal();

      $block->setAccessDependency($layout_entity)->shouldBeCalled();
    }
    else {
      $block = $this->prophesize(BlockPluginInterface::class);
    }

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
   *
   * @dataProvider providerBlockTypes
   */
  public function testOnBuildRenderInPreview($refinable_dependent_access) {
    $contexts = [];
    if ($refinable_dependent_access) {
      $block = $this->prophesize(TestBlockPluginWithRefinableDependentAccessInterface::class);
      $block->setAccessDependency(new LayoutPreviewAccessAllowed())->shouldBeCalled();

      $layout_entity = $this->prophesize(EntityInterface::class);
      $layout_entity = $layout_entity->reveal();
      $layout_entity->in_preview = TRUE;
      $context = $this->prophesize(ContextInterface::class);
      $context->getContextValue()->willReturn($layout_entity);
      $contexts['layout_builder.entity'] = $context->reveal();
    }
    else {
      $block = $this->prophesize(BlockPluginInterface::class);
    }

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
  public function testOnBuildRenderInPreviewEmptyBuild() {
    $block = $this->prophesize(BlockPluginInterface::class)->willImplement(PreviewFallbackInterface::class);

    $block->access($this->account->reveal(), TRUE)->shouldNotBeCalled();
    $block->getCacheContexts()->willReturn([]);
    $block->getCacheTags()->willReturn(['test']);
    $block->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $block->getConfiguration()->willReturn([]);
    $block->getPluginId()->willReturn('block_plugin_id');
    $block->getBaseId()->willReturn('block_plugin_id');
    $block->getDerivativeId()->willReturn(NULL);
    $placeholder_string = 'The placeholder string';
    $block->getPreviewFallbackString()->willReturn($placeholder_string);

    $block_content = [];
    $block->build()->willReturn($block_content);
    $this->blockManager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn($block->reveal());

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $event = new SectionComponentBuildRenderArrayEvent($component, [], TRUE);

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
    $expected_build['content']['#markup'] = $placeholder_string;

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
  public function testOnBuildRenderEmptyBuild() {
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

    $block->build()->willReturn([]);
    $this->blockManager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn($block->reveal());

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $event = new SectionComponentBuildRenderArrayEvent($component, [], FALSE);

    $subscriber = new BlockComponentRenderArray($this->account->reveal());

    $expected_build = [];

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

/**
 * Test interface for dependent access block plugins.
 */
interface TestBlockPluginWithRefinableDependentAccessInterface extends BlockPluginInterface, RefinableDependentAccessInterface {

}
