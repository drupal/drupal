<?php

namespace Drupal\Tests\layout_builder\Unit;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Layout\LayoutInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\LayoutSectionBuilder;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\layout_builder\LayoutSectionBuilder
 * @group layout_builder
 */
class LayoutSectionBuilderTest extends UnitTestCase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

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
   * The object under test.
   *
   * @var \Drupal\layout_builder\LayoutSectionBuilder
   */
  protected $layoutSectionBuilder;

  /**
   * The layout plugin.
   *
   * @var \Drupal\Core\Layout\LayoutInterface
   */
  protected $layout;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->account = $this->prophesize(AccountInterface::class);
    $this->layoutPluginManager = $this->prophesize(LayoutPluginManagerInterface::class);
    $this->blockManager = $this->prophesize(BlockManagerInterface::class);
    $this->contextHandler = $this->prophesize(ContextHandlerInterface::class);
    $this->contextRepository = $this->prophesize(ContextRepositoryInterface::class);
    $this->layoutSectionBuilder = new LayoutSectionBuilder($this->account->reveal(), $this->layoutPluginManager->reveal(), $this->blockManager->reveal(), $this->contextHandler->reveal(), $this->contextRepository->reveal());

    $this->layout = $this->prophesize(LayoutInterface::class);
    $this->layoutPluginManager->createInstance('layout_onecol', [])->willReturn($this->layout->reveal());
  }

  /**
   * @covers ::buildSection
   */
  public function testBuildSection() {
    $block_content = ['#markup' => 'The block content.'];
    $render_array = [
      '#theme' => 'block',
      '#weight' => 0,
      '#configuration' => [],
      '#plugin_id' => 'block_plugin_id',
      '#base_plugin_id' => 'block_plugin_id',
      '#derivative_plugin_id' => NULL,
      'content' => $block_content,
    ];
    $this->layout->build(['content' => ['some_uuid' => $render_array]])->willReturnArgument(0);

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
      'content' => [
        'some_uuid' => [
          'block' => [
            'id' => 'block_plugin_id',
          ],
        ],
      ],
    ];
    $expected = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
      'content' => [
        'some_uuid' => $render_array,
      ],
    ];
    $result = $this->layoutSectionBuilder->buildSection('layout_onecol', [], $section);
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::buildSection
   */
  public function testBuildSectionAccessDenied() {
    $this->layout->build([])->willReturn([]);

    $block = $this->prophesize(BlockPluginInterface::class);
    $this->blockManager->createInstance('block_plugin_id', ['id' => 'block_plugin_id'])->willReturn($block->reveal());

    $access_result = AccessResult::forbidden();
    $block->access($this->account->reveal(), TRUE)->willReturn($access_result);
    $block->build()->shouldNotBeCalled();

    $section = [
      'content' => [
        'some_uuid' => [
          'block' => [
            'id' => 'block_plugin_id',
          ],
        ],
      ],
    ];
    $expected = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
    ];
    $result = $this->layoutSectionBuilder->buildSection('layout_onecol', [], $section);
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::buildSection
   */
  public function testBuildSectionEmpty() {
    $this->layout->build([])->willReturn([]);

    $section = [];
    $expected = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
    ];
    $result = $this->layoutSectionBuilder->buildSection('layout_onecol', [], $section);
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::buildSection
   * @covers ::getBlock
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
    ];
    $this->layout->build(['content' => ['some_uuid' => $render_array]])->willReturnArgument(0);

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

    $this->contextRepository->getRuntimeContexts([])->willReturn([]);
    $this->contextHandler->applyContextMapping($block->reveal(), [])->shouldBeCalled();

    $section = [
      'content' => [
        'some_uuid' => [
          'block' => [
            'id' => 'block_plugin_id',
          ],
        ],
      ],
    ];
    $expected = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => -1,
      ],
      'content' => [
        'some_uuid' => $render_array,
      ],
    ];
    $result = $this->layoutSectionBuilder->buildSection('layout_onecol', [], $section);
    $this->assertEquals($expected, $result);
  }

  /**
   * @covers ::buildSection
   * @covers ::getBlock
   */
  public function testBuildSectionMissingPluginId() {
    $section = [
      'content' => [
        'some_uuid' => [
          'block' => [],
        ],
      ],
    ];
    $this->setExpectedException(PluginException::class, 'No plugin ID specified for block with "some_uuid" UUID');
    $this->layoutSectionBuilder->buildSection('layout_onecol', [], $section);
  }

  /**
   * @covers ::buildSection
   *
   * @dataProvider providerTestBuildSectionMalformedData
   */
  public function testBuildSectionMalformedData($section, $message) {
    $this->layout->build(Argument::type('array'))->willReturnArgument(0);
    $this->layout->getPluginId()->willReturn('the_plugin_id');
    $this->setExpectedException(\InvalidArgumentException::class, $message);
    $this->layoutSectionBuilder->buildSection('layout_onecol', [], $section);
  }

  /**
   * Provides test data for ::testBuildSectionMalformedData().
   */
  public function providerTestBuildSectionMalformedData() {
    $data = [];
    $data['invalid_region'] = [
      ['content' => 'bar'],
      'The "content" region in the "the_plugin_id" layout has invalid configuration',
    ];
    $data['invalid_configuration'] = [
      ['content' => ['some_uuid' => 'bar']],
      'The block with UUID of "some_uuid" has invalid configuration',
    ];
    $data['invalid_blocks'] = [
      ['content' => ['some_uuid' => []]],
      'The block with UUID of "some_uuid" has invalid configuration',
    ];
    return $data;
  }

}
