<?php

declare(strict_types=1);

namespace Drupal\Tests\block\Unit\Plugin\DisplayVariant;

use Drupal\block\Plugin\DisplayVariant\BlockPageVariant;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
 * @group block
 */
class BlockPageVariantTest extends UnitTestCase {

  /**
   * The block repository.
   *
   * @var \Drupal\block\BlockRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $blockRepository;

  /**
   * The block view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $blockViewBuilder;

  /**
   * The plugin context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $contextHandler;

  /**
   * Sets up a display variant plugin for testing.
   *
   * @param array $configuration
   *   An array of plugin configuration.
   * @param array $definition
   *   The plugin definition array.
   *
   * @return \Drupal\block\Plugin\DisplayVariant\BlockPageVariant
   *   A test display variant plugin.
   */
  public function setUpDisplayVariant($configuration = [], $definition = []) {

    $container = new Container();
    $cache_context_manager = $this->getMockBuilder('Drupal\Core\Cache\Context\CacheContextsManager')
      ->disableOriginalConstructor()
      ->onlyMethods(['assertValidTokens'])
      ->getMock();
    $container->set('cache_contexts_manager', $cache_context_manager);
    $cache_context_manager->expects($this->any())
      ->method('assertValidTokens')
      ->willReturn(TRUE);
    \Drupal::setContainer($container);

    $this->blockRepository = $this->createMock('Drupal\block\BlockRepositoryInterface');
    $this->blockViewBuilder = $this->createMock('Drupal\Core\Entity\EntityViewBuilderInterface');

    return new BlockPageVariant($configuration, 'test', $definition, $this->blockRepository, $this->blockViewBuilder, ['config:block_list']);
  }

  public static function providerBuild() {
    $blocks_config = [
      'block1' => [
        // region, is main content block, is messages block, is title block
        'top', FALSE, FALSE, FALSE,
      ],
      // Test multiple blocks in the same region.
      'block2' => [
        'bottom', FALSE, FALSE, FALSE,
      ],
      'block3' => [
        'bottom', FALSE, FALSE, FALSE,
      ],
      // Test a block implementing MainContentBlockPluginInterface.
      'block4' => [
        'center', TRUE, FALSE, FALSE,
      ],
      // Test a block implementing MessagesBlockPluginInterface.
      'block5' => [
        'center', FALSE, TRUE, FALSE,
      ],
      // Test a block implementing TitleBlockPluginInterface.
      'block6' => [
        'center', FALSE, FALSE, TRUE,
      ],
    ];

    $test_cases = [];
    $test_cases[] = [$blocks_config, 6,
      [
        '#cache' => [
          'tags' => [
            'config:block_list',
            'route',
          ],
          'contexts' => [],
          'max-age' => -1,
        ],
        'top' => [
          'block1' => [],
          '#sorted' => TRUE,
        ],
        // The main content was rendered via a block.
        'center' => [
          'block4' => [],
          'block5' => [],
          'block6' => [],
          '#sorted' => TRUE,
        ],
        'bottom' => [
          'block2' => [],
          'block3' => [],
          '#sorted' => TRUE,
        ],
      ],
    ];
    unset($blocks_config['block5']);
    $test_cases[] = [$blocks_config, 5,
      [
        '#cache' => [
          'tags' => [
            'config:block_list',
            'route',
          ],
          'contexts' => [],
          'max-age' => -1,
        ],
        'top' => [
          'block1' => [],
          '#sorted' => TRUE,
        ],
        'center' => [
          'block4' => [],
          'block6' => [],
          '#sorted' => TRUE,
        ],
        'bottom' => [
          'block2' => [],
          'block3' => [],
          '#sorted' => TRUE,
        ],
        // The messages are rendered via the fallback in case there is no block
        // rendering the main content.
        'content' => [
          'messages' => [
            '#weight' => -1000,
            '#type' => 'status_messages',
            '#include_fallback' => TRUE,
          ],
        ],
      ],
    ];
    unset($blocks_config['block4']);
    unset($blocks_config['block6']);
    $test_cases[] = [$blocks_config, 3,
      [
        '#cache' => [
          'tags' => [
            'config:block_list',
            'route',
          ],
          'contexts' => [],
          'max-age' => -1,
        ],
        'top' => [
          'block1' => [],
          '#sorted' => TRUE,
        ],
        'bottom' => [
          'block2' => [],
          'block3' => [],
          '#sorted' => TRUE,
        ],
        // The main content & messages are rendered via the fallback in case
        // there are no blocks rendering them.
        'content' => [
          'system_main' => ['#markup' => 'Hello kittens!'],
          'messages' => [
            '#weight' => -1000,
            '#type' => 'status_messages',
            '#include_fallback' => TRUE,
          ],
        ],
      ],
    ];
    return $test_cases;
  }

  /**
   * Tests the building of a full page variant.
   *
   * @covers ::build
   *
   * @dataProvider providerBuild
   */
  public function testBuild(array $blocks_config, $visible_block_count, array $expected_render_array): void {
    $display_variant = $this->setUpDisplayVariant();
    $display_variant->setMainContent(['#markup' => 'Hello kittens!']);

    $blocks = ['top' => [], 'center' => [], 'bottom' => []];
    $block_plugin = $this->createMock('Drupal\Core\Block\BlockPluginInterface');
    $main_content_block_plugin = $this->createMock('Drupal\Core\Block\MainContentBlockPluginInterface');
    $messages_block_plugin = $this->createMock('Drupal\Core\Block\MessagesBlockPluginInterface');
    $title_block_plugin = $this->createMock('Drupal\Core\Block\TitleBlockPluginInterface');
    foreach ($blocks_config as $block_id => $block_config) {
      $block = $this->createMock('Drupal\block\BlockInterface');
      $block->expects($this->atLeastOnce())
        ->method('getPlugin')
        ->willReturn($block_config[1] ? $main_content_block_plugin : ($block_config[2] ? $messages_block_plugin : ($block_config[3] ? $title_block_plugin : $block_plugin)));
      $blocks[$block_config[0]][$block_id] = $block;
    }
    $this->blockViewBuilder->expects($this->exactly($visible_block_count))
      ->method('view')
      ->willReturn([]);
    $this->blockRepository->expects($this->once())
      ->method('getVisibleBlocksPerRegion')
      ->willReturnCallback(function (&$cacheable_metadata) use ($blocks) {
        $cacheable_metadata['top'] = (new CacheableMetadata())->addCacheTags(['route']);
        return $blocks;
      });

    $value = $display_variant->build();
    $this->assertSame($expected_render_array, $value);
  }

  /**
   * Tests the building of a full page variant with no main content set.
   *
   * @covers ::build
   */
  public function testBuildWithoutMainContent(): void {
    $display_variant = $this->setUpDisplayVariant();
    $this->blockRepository->expects($this->once())
      ->method('getVisibleBlocksPerRegion')
      ->willReturn([]);

    $expected = [
      '#cache' => [
        'tags' => [
          'config:block_list',
        ],
        'contexts' => [],
        'max-age' => -1,
      ],
      'content' => [
        'system_main' => [],
        'messages' => [
          '#weight' => -1000,
          '#type' => 'status_messages',
          '#include_fallback' => TRUE,
        ],
      ],
    ];
    $this->assertSame($expected, $display_variant->build());
  }

}
