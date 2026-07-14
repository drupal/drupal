<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\Block;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ContextualLinksHelper;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\Block\ViewsBlock;
use Drupal\views\Plugin\views\display\Block;
use Drupal\views\ViewExecutableFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests Drupal\views\Plugin\block\ViewsBlock.
 */
#[CoversClass(ViewsBlock::class)]
#[Group('views')]
class ViewsBlockTest extends UnitTestCase {

  /**
   * The view executable.
   *
   * @var \Drupal\views\ViewExecutable|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $executable;

  /**
   * The view executable factory.
   */
  protected ViewExecutableFactory&MockObject $executableFactory;

  /**
   * The view entity.
   *
   * @var \Drupal\views\ViewEntityInterface|\PHPUnit\Framework\MockObject\Stub
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
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit\Framework\MockObject\Stub
   */
  protected $account;

  /**
   * The mocked display handler.
   *
   * @var \Drupal\views\Plugin\views\display\Block|\PHPUnit\Framework\MockObject\Stub
   */
  protected $displayHandler;

  /**
   * The Views contextual links service.
   */
  protected ContextualLinksHelper|Stub $contextualLinks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();

    $cache_context_manager = $this->createStub(CacheContextsManager::class);
    $cache_context_manager
      ->method('getAll')
      ->willReturn([]);
    $cache_context_manager
      ->method('assertValidTokens')
      ->willReturn(TRUE);
    $container->set('cache_contexts_manager', $cache_context_manager);

    $condition_plugin_manager = $this->createStub(ExecutableManagerInterface::class);
    $condition_plugin_manager
      ->method('getDefinitions')
      ->willReturn([]);
    $container->set('plugin.manager.condition', $condition_plugin_manager);

    \Drupal::setContainer($container);

    $this->executable = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->onlyMethods(['buildRenderable', 'setDisplay', 'setItemsPerPage', 'getShowAdminLinks'])
      ->getMock();
    $this->executable->expects($this->once())
      ->method('setDisplay')
      ->with('block_1')
      ->willReturn(TRUE);
    $this->executable
      ->method('getShowAdminLinks')
      ->willReturn(FALSE);

    $this->view = $this->createStub(View::class);
    $this->view
      ->method('id')
      ->willReturn('test_view');
    $this->executable->storage = $this->view;

    $this->executableFactory = $this->createMock(ViewExecutableFactory::class);
    $this->executableFactory->expects($this->atLeastOnce())
      ->method('get')
      ->with($this->view)
      ->willReturn($this->executable);

    $this->displayHandler = $this->createStub(Block::class);

    $this->displayHandler
      ->method('blockSettings')
      ->willReturn([]);

    $this->displayHandler
      ->method('getPluginId')
      ->willReturn('block');

    $this->displayHandler
      ->method('getHandlers')
      ->willReturn([]);

    $this->executable->display_handler = $this->displayHandler;

    $this->storage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();

    $this->storage->expects($this->atLeastOnce())
      ->method('load')
      ->with('test_view')
      ->willReturn($this->view);
    $this->account = $this->createStub(AccountInterface::class);

    $this->contextualLinks = $this->createStub(ContextualLinksHelper::class);
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
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account, $this->contextualLinks);

    $this->assertEquals($build, $plugin->build());
  }

  /**
   * Tests that cacheable metadata is retrieved from the view and merged with block cacheable metadata.
   *
   * @see \Drupal\views\Plugin\block\ViewsBlock::build()
   */
  #[DataProvider('providerTestCacheableMetadata')]
  public function testCacheableMetadata(int $blockCacheMaxAge, int $viewCacheMaxAge, int $expectedCacheMaxAge): void {

    $blockCacheTags = ['block-cachetag-1', 'block-cachetag-2'];
    $blockCacheContexts = ['block-cache-context-1', 'block-cache-context-2'];

    $viewCacheTags = ['view-cachetag-1', 'view-cachetag-2'];
    $viewCacheContexts = ['view-cache-context-1', 'view-cache-context-2'];

    // Mock view cache metadata.
    $viewCacheMetadata = $this->createStub(CacheableMetadata::class);
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
    $blockContext = $this->createStub(ContextInterface::class);
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
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account, $this->contextualLinks);
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
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account, $this->contextualLinks);

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
    $plugin = new ViewsBlock($config, $block_id, $definition, $this->executableFactory, $this->storage, $this->account, $this->contextualLinks);

    $this->assertEquals([], $plugin->build());
  }

}
