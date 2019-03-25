<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\PreviewFallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\EventSubscriber\BlockComponentRenderArray;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\layout_builder\Section
 * @group layout_builder
 */
class SectionRenderTest extends UnitTestCase {

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
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * The context manager service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * The event dispatcher.
   *
   * @var \Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $layout_plugin_manager = $this->prophesize(LayoutPluginManagerInterface::class);
    $this->blockManager = $this->prophesize(BlockManagerInterface::class);
    $this->contextHandler = $this->prophesize(ContextHandlerInterface::class);
    $this->contextRepository = $this->prophesize(ContextRepositoryInterface::class);
    // @todo Refactor this into some better tests in https://www.drupal.org/node/2942605.
    $this->eventDispatcher = (new \ReflectionClass(ContainerAwareEventDispatcher::class))->newInstanceWithoutConstructor();

    $this->account = $this->prophesize(AccountInterface::class);
    $subscriber = new BlockComponentRenderArray($this->account->reveal());
    $this->eventDispatcher->addSubscriber($subscriber);

    $layout = $this->prophesize(LayoutInterface::class);
    $layout->getPluginDefinition()->willReturn(new LayoutDefinition([]));
    $layout->build(Argument::type('array'))->willReturnArgument(0);
    $layout_plugin_manager->createInstance('layout_onecol', [])->willReturn($layout->reveal());

    $container = new ContainerBuilder();
    $container->set('current_user', $this->account->reveal());
    $container->set('plugin.manager.block', $this->blockManager->reveal());
    $container->set('plugin.manager.core.layout', $layout_plugin_manager->reveal());
    $container->set('context.handler', $this->contextHandler->reveal());
    $container->set('context.repository', $this->contextRepository->reveal());
    $container->set('event_dispatcher', $this->eventDispatcher);
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::toRenderArray
   */
  public function testToRenderArray() {
    $block_content = ['#markup' => 'The block content.'];
    $placeholder_label = 'Placeholder Label';
    $render_array = [
      '#theme' => 'block',
      '#weight' => 0,
      '#configuration' => [],
      '#plugin_id' => 'block_plugin_id',
      '#base_plugin_id' => 'block_plugin_id',
      '#derivative_plugin_id' => NULL,
      'content' => $block_content,
      '#attributes' => [
        'data-layout-content-preview-placeholder-label' => $placeholder_label,
        'class' => ['layout-builder-block'],
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
    ];

    $block = $this->prophesize(BlockPluginInterface::class)->willImplement(PreviewFallbackInterface::class);
    $this->blockManager->createInstance('block_plugin_id', ['id' => 'block_plugin_id'])->willReturn($block->reveal());

    $access_result = AccessResult::allowed();
    $block->access($this->account->reveal(), TRUE)->willReturn($access_result);
    $block->build()->willReturn($block_content);
    $block->getCacheContexts()->willReturn([]);
    $block->getCacheTags()->willReturn([]);
    $block->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $block->getPluginId()->willReturn('block_plugin_id');
    $block->getBaseId()->willReturn('block_plugin_id');
    $block->getDerivativeId()->willReturn(NULL);
    $block->getConfiguration()->willReturn([]);
    $block->getPreviewFallbackString()->willReturn($placeholder_label);

    $section = [
      new SectionComponent('some_uuid', 'content', ['id' => 'block_plugin_id']),
    ];
    $expected = [
      'content' => [
        'some_uuid' => $render_array,
      ],
    ];
    $result = (new Section('layout_onecol', [], $section))->toRenderArray();
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::toRenderArray
   */
  public function testToRenderArrayAccessDenied() {
    $block = $this->prophesize(BlockPluginInterface::class);
    $this->blockManager->createInstance('block_plugin_id', ['id' => 'block_plugin_id'])->willReturn($block->reveal());

    $access_result = AccessResult::forbidden();
    $block->access($this->account->reveal(), TRUE)->willReturn($access_result);
    $block->build()->shouldNotBeCalled();
    $block->getCacheContexts()->willReturn([]);
    $block->getCacheTags()->willReturn([]);
    $block->getCacheMaxAge()->willReturn(Cache::PERMANENT);

    $section = [
      new SectionComponent('some_uuid', 'content', ['id' => 'block_plugin_id']),
    ];
    $expected = [
      'content' => [
        'some_uuid' => [
          '#cache' => [
            'contexts' => [],
            'tags' => [],
            'max-age' => -1,
          ],
        ],
      ],
    ];
    $result = (new Section('layout_onecol', [], $section))->toRenderArray();
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::toRenderArray
   */
  public function testToRenderArrayPreview() {
    $block_content = ['#markup' => 'The block content.'];
    $placeholder_label = 'Placeholder Label';
    $render_array = [
      '#theme' => 'block',
      '#weight' => 0,
      '#configuration' => [],
      '#plugin_id' => 'block_plugin_id',
      '#base_plugin_id' => 'block_plugin_id',
      '#derivative_plugin_id' => NULL,
      'content' => $block_content,
      '#attributes' => [
        'data-layout-content-preview-placeholder-label' => $placeholder_label,
        'class' => ['layout-builder-block'],
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => 0,
      ],
    ];
    $block = $this->prophesize(BlockPluginInterface::class)->willImplement(PreviewFallbackInterface::class);
    $this->blockManager->createInstance('block_plugin_id', ['id' => 'block_plugin_id'])->willReturn($block->reveal());

    $block->access($this->account->reveal(), TRUE)->shouldNotBeCalled();
    $block->build()->willReturn($block_content);
    $block->getCacheContexts()->willReturn([]);
    $block->getCacheTags()->willReturn([]);
    $block->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $block->getConfiguration()->willReturn([]);
    $block->getPluginId()->willReturn('block_plugin_id');
    $block->getBaseId()->willReturn('block_plugin_id');
    $block->getDerivativeId()->willReturn(NULL);
    $block->getPreviewFallbackString()->willReturn($placeholder_label);

    $section = [
      new SectionComponent('some_uuid', 'content', ['id' => 'block_plugin_id']),
    ];
    $expected = [
      'content' => [
        'some_uuid' => $render_array,
      ],
    ];
    $result = (new Section('layout_onecol', [], $section))->toRenderArray([], TRUE);
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::toRenderArray
   */
  public function testToRenderArrayEmpty() {
    $section = [];
    $expected = [];
    $result = (new Section('layout_onecol', [], $section))->toRenderArray();
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::toRenderArray
   */
  public function testContextAwareBlock() {
    $block_content = ['#markup' => 'The block content.'];
    $placeholder_label = 'Placeholder Label';
    $render_array = [
      '#theme' => 'block',
      '#weight' => 0,
      '#configuration' => [],
      '#plugin_id' => 'block_plugin_id',
      '#base_plugin_id' => 'block_plugin_id',
      '#derivative_plugin_id' => NULL,
      'content' => $block_content,
      '#attributes' => [
        'data-layout-content-preview-placeholder-label' => $placeholder_label,
        'class' => ['layout-builder-block'],
      ],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
    ];

    $block = $this->prophesize(BlockPluginInterface::class)
      ->willImplement(ContextAwarePluginInterface::class)
      ->willImplement(PreviewFallbackInterface::class);
    $this->blockManager->createInstance('block_plugin_id', ['id' => 'block_plugin_id'])->willReturn($block->reveal());

    $access_result = AccessResult::allowed();
    $block->access($this->account->reveal(), TRUE)->willReturn($access_result);
    $block->build()->willReturn($block_content);
    $block->getCacheContexts()->willReturn([]);
    $block->getCacheTags()->willReturn([]);
    $block->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $block->getContextMapping()->willReturn([]);
    $block->getPluginId()->willReturn('block_plugin_id');
    $block->getBaseId()->willReturn('block_plugin_id');
    $block->getDerivativeId()->willReturn(NULL);
    $block->getConfiguration()->willReturn([]);
    $block->getPreviewFallbackString()->willReturn($placeholder_label);

    $section = [
      new SectionComponent('some_uuid', 'content', ['id' => 'block_plugin_id']),
    ];
    $expected = [
      'content' => [
        'some_uuid' => $render_array,
      ],
    ];
    $result = (new Section('layout_onecol', [], $section))->toRenderArray();
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::toRenderArray
   */
  public function testToRenderArrayMissingPluginId() {
    $this->setExpectedException(PluginException::class, 'No plugin ID specified for component with "some_uuid" UUID');
    (new Section('layout_onecol', [], [new SectionComponent('some_uuid', 'content')]))->toRenderArray();
  }

}
