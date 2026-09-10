<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Kernel;

use Drupal\Core\Block\BlockManager;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\node\Entity\NodeType;
use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests saving views and clearing.
 *
 * @see \Drupal\views\ViewExecutable
 */
#[Group('views')]
#[RunTestsInSeparateProcesses]
class ViewsSaveProcessTest extends ViewsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'rest',
    'serialization',
    'text',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_views_save_process'];

  /**
   * Sets up the necessary fixtures for the test environment.
   */
  protected function setUpFixtures(): void {
    $this->installEntitySchema('node');
    $this->installConfig(['system', 'field', 'node']);

    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();
    parent::setUpFixtures();
  }

  /**
   * Tests cache rebuilding and route rebuilding are not duplicated.
   *
   * The test_views_save_process contains two block, page, and serialization
   * displays.
   */
  public function testCacheRebuildMethods(): void {
    // Resave, this will trigger block cache clear once, but no route
    // rebuilding.
    $this->setCallExpectations(1, 0);
    $view = Views::getView('test_views_save_process');
    $view->save();

    // Updating a path should trigger route rebuild.
    $this->setCallExpectations(1, 1);
    $view = Views::getView('test_views_save_process');
    $view->initDisplay();
    $path = $this->randomMachineName();
    $view->displayHandlers->get('page_1')->overrideOption('path', $path);
    $view->setDisplay('page_1');
    $this->assertEquals($path, $view->getPath());
    $view->save();

    // Updating multiple paths should trigger route rebuild once only.
    $this->setCallExpectations(1, 1);
    $view = Views::getView('test_views_save_process');
    $view->initDisplay();
    $path = $this->randomMachineName();
    $view->displayHandlers->get('page_1')->overrideOption('path', $path);
    $view->setDisplay('page_1');
    $this->assertEquals($path, $view->getPath());
    $path = $this->randomMachineName();
    $view->displayHandlers->get('page_2')->overrideOption('path', $path);
    $view->setDisplay('page_2');
    $this->assertEquals($path, $view->getPath());
    $view->save();

    // Updating serialization format should trigger route rebuild.
    $this->setCallExpectations(1, 1);
    $view = Views::getView('test_views_save_process');
    $view->initDisplay();
    $style = $view->displayHandlers->get('rest_export_1')->getOption('style');
    $style['options']['formats'] = ['xml' => 'xml'];
    $view->displayHandlers->get('rest_export_1')->overrideOption('style', $style);
    $view->setDisplay('rest_export_1');
    $view->save();

    // Updating multiple formats should trigger route rebuild once only.
    $this->setCallExpectations(1, 1);
    $view = Views::getView('test_views_save_process');
    $view->initDisplay();
    $style = $view->displayHandlers->get('rest_export_1')->getOption('style');
    $style['options']['formats'] = ['xml' => 'xml'];
    $view->displayHandlers->get('rest_export_1')->overrideOption('style', $style);
    $view->setDisplay('rest_export_1');
    $style = $view->displayHandlers->get('rest_export_2')->getOption('style');
    $style['options']['formats'] = ['json' => 'json'];
    $view->displayHandlers->get('rest_export_2')->overrideOption('style', $style);
    $view->setDisplay('rest_export_2');
    $view->save();

    // Updating multiple formats and paths should trigger route rebuild once
    // only.
    $this->setCallExpectations(1, 1);
    $view = Views::getView('test_views_save_process');
    $view->initDisplay();
    $style = $view->displayHandlers->get('rest_export_1')->getOption('style');
    $style['options']['formats'] = ['xml' => 'xml'];
    $view->displayHandlers->get('rest_export_1')->overrideOption('style', $style);
    $view->setDisplay('rest_export_1');
    $style = $view->displayHandlers->get('rest_export_2')->getOption('style');
    $style['options']['formats'] = ['json' => 'json'];
    $view->displayHandlers->get('rest_export_2')->overrideOption('style', $style);
    $view->setDisplay('rest_export_2');
    $path = $this->randomMachineName();
    $view->displayHandlers->get('page_1')->overrideOption('path', $path);
    $view->setDisplay('page_1');
    $path = $this->randomMachineName();
    $view->displayHandlers->get('page_2')->overrideOption('path', $path);
    $view->setDisplay('page_2');
    $view->save();

    // Updating the format to the existing format or path to the existing path
    // Should trigger no route rebuilding.
    $this->setCallExpectations(1, 0);
    $view = Views::getView('test_views_save_process');
    $view->initDisplay();
    $style = $view->displayHandlers->get('rest_export_1')->getOption('style');
    $style['options']['formats'] = ['xml' => 'xml'];
    $view->displayHandlers->get('rest_export_1')->overrideOption('style', $style);
    $view->setDisplay('rest_export_1');
    $style = $view->displayHandlers->get('rest_export_2')->getOption('style');
    $style['options']['formats'] = ['json' => 'json'];
    $view->displayHandlers->get('rest_export_2')->overrideOption('style', $style);
    $view->setDisplay('rest_export_2');
    $view->setDisplay('page_1');
    $view->displayHandlers->get('page_1')->overrideOption('path', $view->getPath());
    $view->setDisplay('page_2');
    $view->displayHandlers->get('page_2')->overrideOption('path', $view->getPath());
    $view->save();
  }

  /**
   * Sets call expectations for block cache and router rebuilding.
   *
   * @param int $blockCacheClearCalls
   *   How many times the block cache should be cleared.
   * @param int $routerRebuildCalls
   *   How many times the router rebuild should be called.
   */
  protected function setCallExpectations(int $blockCacheClearCalls, int $routerRebuildCalls): void {
    $blockManager = $this->createMock(BlockManager::class);
    $blockManager->expects($this->exactly($blockCacheClearCalls))
      ->method('clearCachedDefinitions');
    $routerRebuilder = $this->createMock(RouteBuilderInterface::class);
    $routerRebuilder->expects($this->exactly($routerRebuildCalls))
      ->method('setRebuildNeeded');

    $this->container->set('plugin.manager.block', $blockManager);
    $this->container->set('router.builder', $routerRebuilder);
  }

}
