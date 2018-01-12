<?php

namespace Drupal\Tests\layout_builder\Unit;

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
use Drupal\Core\Session\AccountInterface;
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
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->prophesize(AccountInterface::class);
    $layout_plugin_manager = $this->prophesize(LayoutPluginManagerInterface::class);
    $this->blockManager = $this->prophesize(BlockManagerInterface::class);
    $this->contextHandler = $this->prophesize(ContextHandlerInterface::class);
    $this->contextRepository = $this->prophesize(ContextRepositoryInterface::class);

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
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::toRenderArray
   */
  public function testToRenderArray() {
    $block_content = ['#markup' => 'The block content.'];
    $render_array = [
      '#theme' => 'block',
      '#weight' => 0,
      '#configuration' => [],
      '#plugin_id' => 'block_plugin_id',
      '#base_plugin_id' => 'block_plugin_id',
      '#derivative_plugin_id' => NULL,
      'content' => $block_content,
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
    ];

    $block = $this->prophesize(BlockPluginInterface::class);
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
    $render_array = [
      '#theme' => 'block',
      '#weight' => 0,
      '#configuration' => [],
      '#plugin_id' => 'block_plugin_id',
      '#base_plugin_id' => 'block_plugin_id',
      '#derivative_plugin_id' => NULL,
      'content' => [],
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
    ];

    $block = $this->prophesize(BlockPluginInterface::class)->willImplement(ContextAwarePluginInterface::class);
    $this->blockManager->createInstance('block_plugin_id', ['id' => 'block_plugin_id'])->willReturn($block->reveal());

    $access_result = AccessResult::allowed();
    $block->access($this->account->reveal(), TRUE)->willReturn($access_result);
    $block->build()->willReturn([]);
    $block->getCacheContexts()->willReturn([]);
    $block->getCacheTags()->willReturn([]);
    $block->getCacheMaxAge()->willReturn(Cache::PERMANENT);
    $block->getContextMapping()->willReturn([]);
    $block->getPluginId()->willReturn('block_plugin_id');
    $block->getBaseId()->willReturn('block_plugin_id');
    $block->getDerivativeId()->willReturn(NULL);
    $block->getConfiguration()->willReturn([]);

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
