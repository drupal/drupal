<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\Block\ViewsBlock;

/**
 * @coversDefaultClass \Drupal\views\Plugin\block\ViewsBlock
 * @group views
 */
class ViewsBlockTest extends UnitTestCase {

  /**
   * The view executable.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $executable;

  /**
   * The view executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $executableFactory;

  /**
   * The view entity.
   *
   * @var \Drupal\views\ViewEntityInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $view;

  /**
   * The view storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * The mocked user account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account;

  /**
   * The mocked display handler.
   *
   * @var \Drupal\views\Plugin\views\display\Block|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $displayHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();

    $cache_context_manager = $this->createMock(CacheContextsManager::class);
    $cache_context_manager->expects($this->any())
      ->method('getAll')
      ->willReturn([]);
    $cache_context_manager->expects($this->any())
      ->method('assertValidTokens')
      ->willReturn(TRUE);
    $container->set('cache_contexts_manager', $cache_context_manager);

    $condition_plugin_manager = $this->createMock('Drupal\Core\Executable\ExecutableManagerInterface');
    $condition_plugin_manager->expects($this->any())
      ->method('getDefinitions')
      ->willReturn([]);
    $container->set('plugin.manager.condition', $condition_plugin_manager);

    \Drupal::setContainer($container);

    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->onlyMethods(['buildRenderable', 'setDisplay', 'setItemsPerPage', 'getShowAdminLinks'])
      ->getMock();
    $this->executable->expects($this->any())
      ->method('setDisplay')
      ->with('block_1')
      ->willReturn(TRUE);
    $this->executable->expects($this->any())
      ->method('getShowAdminLinks')
      ->willReturn(FALSE);

    $this->executable->display_handler = $this->getMockBuilder('Drupal\views\Plugin\views\display\Block')
      ->disableOriginalConstructor()
      ->onlyMethods(['getCacheMetadata'])
      ->getMock();

    $this->view = $this->getMockBuilder('Drupal\views\Entity\View')
      ->disableOriginalConstructor()
      ->getMock();
    $this->view->expects($this->any())
      ->method('id')
      ->willReturn('test_view');
    $this->executable->storage = $this->view;

    $this->executableFactory = $this->getMockBuilder('Drupal\views\ViewExecutableFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $this->executableFactory->expects($this->any())
      ->method('get')
      ->with($this->view)
      ->willReturn($this->executable);

    $this->displayHandler = $this->getMockBuilder('Drupal\views\Plugin\views\display\Block')
      ->disableOriginalConstructor()
      ->getMock();

    $this->displayHandler->expects($this->any())
      ->method('blockSettings')
      ->willReturn([]);

    $this->displayHandler->expects($this->any())
      ->method('getPluginId')
      ->willReturn('block');

    $this->displayHandler->expects($this->any())
      ->method('getHandlers')
      ->willReturn([]);

    $this->executable->display_handler = $this->displayHandler;

    $this->storage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $this->storage->expects($this->any())
      ->method('load')
      ->with('test_view')
      ->willReturn($this->view);
    $this->account = $this->createMock('Drupal\Core\Session\AccountInterface');
  }

  /**
   * Tests the build method.
   *
   * @see \Drupal\views\Plugin\block\ViewsBlock::build()
   */
  public function testBuild(): void {
    $output = $this->randomMachineName(100);
    $build = [
      'view_build' => $output,
      '#view_id' => 'test_view',
      '#view_display_plugin_class' => '\Drupal\views\Plugin\views\display\Block',
      '#view_display_show_admin_links' => FALSE,
      '#view_display_plugin_id' => 'block',
      '#pre_rendered' => TRUE,
    ];
    $this->executable->expects($this->once())
      ->method('buildRenderable')
      ->with('block_1', [])
      ->willReturn($build);

    $block_id = 'views_block:test_view-block_1';
    $config = [];
    $definition = [];

    $definition['provider'] = 'views';
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account);

    $this->assertEquals($build, $plugin->build());
  }

  /**
   * Tests that cacheable metadata is retrieved from the view and merged with block cacheable metadata.
   *
   * @dataProvider providerTestCacheableMetadata
   *
   * @see \Drupal\views\Plugin\block\ViewsBlock::build()
   */
  public function testCacheableMetadata(int $blockCacheMaxAge, int $viewCacheMaxAge, int $expectedCacheMaxAge): void {

    $blockCacheTags = ['block-cachetag-1', 'block-cachetag-2'];
    $blockCacheContexts = ['block-cache-context-1', 'block-cache-context-2'];

    $viewCacheTags = ['view-cachetag-1', 'view-cachetag-2'];
    $viewCacheContexts = ['view-cache-context-1', 'view-cache-context-2'];

    // Mock view cache metadata.
    $viewCacheMetadata = $this->createMock(CacheableMetadata::class);
    $viewCacheMetadata
      ->method('getCacheTags')
      ->willReturn($viewCacheTags);
    $viewCacheMetadata
      ->method('getCacheContexts')
      ->willReturn($viewCacheContexts);
    $viewCacheMetadata
      ->method('getCacheMaxAge')
      ->willReturn($viewCacheMaxAge);
    $this->executable->display_handler
      ->method('getCacheMetadata')
      ->willReturn($viewCacheMetadata);

    // Mock block context.
    $blockContext = $this->createMock(ContextInterface::class);
    $blockContext
      ->method('getCacheTags')
      ->willReturn($blockCacheTags);
    $blockContext
      ->method('getCacheContexts')
      ->willReturn($blockCacheContexts);
    $blockContext
      ->method('getCacheMaxAge')
      ->willReturn($blockCacheMaxAge);

    // Create the views block.
    $block_id = 'views_block:test_view-block_1';
    $config = [];
    $definition = [
      'provider' => 'views',
    ];
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account);
    $plugin->setContext('context_name', $blockContext);

    // Assertions.
    $this->assertEmpty(array_diff(Cache::mergeTags($viewCacheTags, $blockCacheTags), $plugin->getCacheTags()));
    $this->assertEmpty(array_diff(Cache::mergeContexts($viewCacheContexts, $blockCacheContexts), $plugin->getCacheContexts()));
    $this->assertEquals($expectedCacheMaxAge, $plugin->getCacheMaxAge());
  }

  /**
   * Data provider for ::testCacheableMetadata()
   */
  public static function providerTestCacheableMetadata(): array {
    return [
      'View expires before' => [500, 1000, 500],
      'Block expires before' => [1000, 500, 500],
      'Only block is permanent' => [Cache::PERMANENT, 500, 500],
      'Only view is permanent' => [500, Cache::PERMANENT, 500],
      'Both view and block are permanent' => [Cache::PERMANENT, Cache::PERMANENT, Cache::PERMANENT],
    ];
  }

  /**
   * Tests the build method.
   *
   * @covers ::build
   */
  public function testBuildEmpty(): void {
    $build = [
      'view_build' => [],
      '#view_id' => 'test_view',
      '#view_display_plugin_class' => '\Drupal\views\Plugin\views\display\Block',
      '#view_display_show_admin_links' => FALSE,
      '#view_display_plugin_id' => 'block',
      '#pre_rendered' => TRUE,
      '#cache' => ['contexts' => ['user']],
    ];
    $this->executable->expects($this->once())
      ->method('buildRenderable')
      ->with('block_1', [])
      ->willReturn($build);

    $block_id = 'views_block:test_view-block_1';
    $config = [];
    $definition = [];

    $definition['provider'] = 'views';
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account);

    $this->assertEquals(array_intersect_key($build, ['#cache' => TRUE]), $plugin->build());
  }

  /**
   * Tests the build method with a failed execution.
   *
   * @see \Drupal\views\Plugin\block\ViewsBlock::build()
   */
  public function testBuildFailed(): void {
    $output = FALSE;
    $this->executable->expects($this->once())
      ->method('buildRenderable')
      ->with('block_1', [])
      ->willReturn($output);

    $block_id = 'views_block:test_view-block_1';
    $config = [];
    $definition = [];

    $definition['provider'] = 'views';
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account);

    $this->assertEquals([], $plugin->build());
  }

}

// @todo https://www.drupal.org/node/2571679 replace
//   views_add_contextual_links().
namespace Drupal\views\Plugin\Block;

if (!function_exists('views_add_contextual_links')) {

  /**
   * Define method views_add_contextual_links for this test.
   */
  function views_add_contextual_links(&$render_element, $location, $display_id, ?array $view_element = NULL): void {
  }

}
