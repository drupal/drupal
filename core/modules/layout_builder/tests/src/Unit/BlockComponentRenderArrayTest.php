<?php

declare(strict_types=1);

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
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\layout_builder\Access\LayoutPreviewAccessAllowed;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

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
   * Data provider for test functions that should test block types.
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
  protected function setUp(): void {
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
      $block = $this->prophesize(TestBlockPluginWithRefinableDependentAccessInterface::class)->willImplement(PreviewFallbackInterface::class);
      $layout_entity = $this->prophesize(EntityInterface::class);
      $layout_entity = $layout_entity->reveal();
      $context = $this->prophesize(ContextInterface::class);
      $context->getContextValue()->willReturn($layout_entity);
      $contexts['layout_builder.entity'] = $context->reveal();

      $block->setAccessDependency($layout_entity)->shouldBeCalled();
    }
    else {
      $block = $this->prophesize(BlockPluginInterface::class)->willImplement(PreviewFallbackInterface::class);
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
    $placeholder_label = 'Placeholder Label';
    $block->getPreviewFallbackString()->willReturn($placeholder_label);

    $block_content = [
      '#markup' => 'The block content.',
      '#cache' => ['tags' => ['build-tag']],
    ];
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
      '#in_preview' => FALSE,
    ];

    $expected_build_with_expected_cache = $expected_build + [
      '#cache' => [
        'contexts' => [],
        'tags' => [
          'build-tag',
          'test',
        ],
        'max-age' => -1,
      ],
    ];

    $subscriber->onBuildRender($event);
    $result = $event->getBuild();
    $this->assertEquals($expected_build, $result);
    $event->getCacheableMetadata()->applyTo($result);
    $this->assertEqualsCanonicalizing($expected_build_with_expected_cache['#cache'], $result['#cache']);
  }

  /**
   * @covers ::onBuildRender
   *
   * @dataProvider providerBlockTypes
   */
  public function testOnBuildRenderWithoutPreviewFallbackString($refinable_dependent_access) {
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
    $placeholder_label = 'Placeholder Label';
    $block->label()->willReturn($placeholder_label);

    $block_content = ['#markup' => 'The block content.'];
    $block->build()->willReturn($block_content);
    $this->blockManager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn($block->reveal());

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $in_preview = FALSE;
    $event = new SectionComponentBuildRenderArrayEvent($component, $contexts, $in_preview);

    $subscriber = new BlockComponentRenderArray($this->account->reveal());

    $translation = $this->prophesize(TranslationInterface::class);
    $translation->translateString(Argument::type(TranslatableMarkup::class))
      ->willReturn($placeholder_label);
    $subscriber->setStringTranslation($translation->reveal());

    $expected_build = [
      '#theme' => 'block',
      '#weight' => 0,
      '#configuration' => [],
      '#plugin_id' => 'block_plugin_id',
      '#base_plugin_id' => 'block_plugin_id',
      '#derivative_plugin_id' => NULL,
      'content' => $block_content,
      '#in_preview' => FALSE,
    ];

    $expected_cache = $expected_build + [
      '#cache' => [
        'contexts' => [],
        'tags'     => ['test'],
        'max-age'  => -1,
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
      $block = $this->prophesize(TestBlockPluginWithRefinableDependentAccessInterface::class)->willImplement(PreviewFallbackInterface::class);
      $block->setAccessDependency(new LayoutPreviewAccessAllowed())->shouldBeCalled();

      $layout_entity = $this->prophesize(EntityInterface::class);
      $layout_entity = $layout_entity->reveal();
      $layout_entity->in_preview = TRUE;
      $context = $this->prophesize(ContextInterface::class);
      $context->getContextValue()->willReturn($layout_entity);
      $contexts['layout_builder.entity'] = $context->reveal();
    }
    else {
      $block = $this->prophesize(BlockPluginInterface::class)->willImplement(PreviewFallbackInterface::class);
    }

    $block->access($this->account->reveal(), TRUE)->shouldNotBeCalled();
    $block->getCacheContexts()->willReturn([]);
    $block->getCacheTags()->willReturn(['test']);
    $block->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $block->getConfiguration()->willReturn([]);
    $block->getPluginId()->willReturn('block_plugin_id');
    $block->getBaseId()->willReturn('block_plugin_id');
    $block->getDerivativeId()->willReturn(NULL);
    $placeholder_label = 'Placeholder Label';
    $block->getPreviewFallbackString()->willReturn($placeholder_label);

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
      '#attributes' => [
        'data-layout-content-preview-placeholder-label' => $placeholder_label,
      ],
      '#in_preview' => TRUE,
    ];

    $expected_cache = $expected_build + [
      '#cache' => [
        'contexts' => [],
        'tags' => ['test'],
        'max-age' => 0,
      ],
      '#in_preview' => TRUE,
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
    $block->getPluginDefinition()->willReturn(['admin_label' => 'admin']);
    $placeholder_string = 'The placeholder string';
    $block->getPreviewFallbackString()->willReturn($placeholder_string);

    $block_content = [];
    $block->build()->willReturn($block_content);
    $this->blockManager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn($block->reveal());

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $event = new SectionComponentBuildRenderArrayEvent($component, [], TRUE);

    $subscriber = new BlockComponentRenderArray($this->account->reveal());
    $translation = $this->prophesize(TranslationInterface::class);
    $translation->translateString(Argument::type(TranslatableMarkup::class))
      ->willReturn($placeholder_string);
    $subscriber->setStringTranslation($translation->reveal());

    $expected_build = [
      '#theme' => 'block',
      '#weight' => 0,
      '#configuration' => [],
      '#plugin_id' => 'block_plugin_id',
      '#base_plugin_id' => 'block_plugin_id',
      '#derivative_plugin_id' => NULL,
      'content' => $block_content,
      '#attributes' => [
        'data-layout-content-preview-placeholder-label' => $placeholder_string,
      ],
      '#in_preview' => TRUE,
    ];
    $expected_build['content']['#markup'] = $placeholder_string;

    $expected_cache = $expected_build + [
      '#cache' => [
        'contexts' => [],
        'tags' => ['test'],
        'max-age' => 0,
      ],
      '#in_preview' => TRUE,
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

    $block->build()->willReturn([
      '#cache' => ['tags' => ['build-tag']],
    ]);
    $this->blockManager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn($block->reveal());

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $event = new SectionComponentBuildRenderArrayEvent($component, [], FALSE);

    $subscriber = new BlockComponentRenderArray($this->account->reveal());

    $expected_build = [];

    $expected_cache = $expected_build + [
      '#cache' => [
        'contexts' => [],
        'tags' => [
          'build-tag',
          'test',
        ],
        'max-age' => -1,
      ],
    ];

    $subscriber->onBuildRender($event);
    $result = $event->getBuild();
    $this->assertEquals($expected_build, $result);
    $event->getCacheableMetadata()->applyTo($result);
    $this->assertEqualsCanonicalizing($expected_cache, $result);
  }

  /**
   * @covers ::onBuildRender
   */
  public function testOnBuildRenderEmptyBuildWithCacheTags() {
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

    $block_content = [
      '#cache' => [
        'tags' => ['empty_build_cache_test'],
      ],
    ];
    $block->build()->willReturn($block_content);
    $this->blockManager->createInstance('some_block_id', ['id' => 'some_block_id'])->willReturn($block->reveal());

    $component = new SectionComponent('some-uuid', 'some-region', ['id' => 'some_block_id']);
    $event = new SectionComponentBuildRenderArrayEvent($component, [], FALSE);

    $subscriber = new BlockComponentRenderArray($this->account->reveal());

    $expected_build = [];

    $expected_cache = $expected_build + [
      '#cache' => [
        'contexts' => [],
        'tags' => ['empty_build_cache_test', 'test'],
        'max-age' => -1,
      ],
    ];

    $subscriber->onBuildRender($event);
    $result = $event->getBuild();
    $this->assertEquals($expected_build, $result);
    $event->getCacheableMetadata()->applyTo($result);
    $this->assertEqualsCanonicalizing($expected_cache, $result);
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
