<?php

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;
use Drupal\workspaces\Entity\Workspace;
use Drupal\workspaces\WorkspaceCacheContext;

/**
 * Tests the workspace cache context.
 *
 * @group workspaces
 * @group Cache
 */
class WorkspaceCacheContextTest extends BrowserTestBase {

  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'node', 'workspaces'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the 'workspace' cache context.
   */
  public function testWorkspaceCacheContext() {
    $this->dumpHeaders = TRUE;

    $renderer = \Drupal::service('renderer');
    $cache_contexts_manager = \Drupal::service("cache_contexts_manager");

    // Check that the 'workspace' cache context is present when the module is
    // installed.
    $this->drupalGet('<front>');
    $this->assertCacheContext('workspace');

    $cache_context = new WorkspaceCacheContext(\Drupal::service('workspaces.manager'));
    $this->assertSame('live', $cache_context->getContext());

    // Create a node and check that its render array contains the proper cache
    // context.
    $this->drupalCreateContentType(['type' => 'page']);
    $node = $this->createNode();

    // Get a fully built entity view render array.
    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, 'full');

    // Render it so the default cache contexts are applied.
    $renderer->renderRoot($build);
    $this->assertTrue(in_array('workspace', $build['#cache']['contexts'], TRUE));

    $cid_parts = array_merge($build['#cache']['keys'], $cache_contexts_manager->convertTokensToKeys($build['#cache']['contexts'])->getKeys());
    $this->assertTrue(in_array('[workspace]=live', $cid_parts, TRUE));

    // Test that a cache entry is created.
    $cid = implode(':', $cid_parts);
    $bin = $build['#cache']['bin'];
    $this->assertInstanceOf(\stdClass::class, $this->container->get('cache.' . $bin)->get($cid), 'The entity render element has been cached.');

    // Switch to the 'stage' workspace and check that the correct workspace
    // cache context is used.
    $test_user = $this->drupalCreateUser(['view any workspace']);
    $this->drupalLogin($test_user);

    $stage = Workspace::load('stage');
    $workspace_manager = \Drupal::service('workspaces.manager');
    $workspace_manager->setActiveWorkspace($stage);

    $cache_context = new WorkspaceCacheContext($workspace_manager);
    $this->assertSame('stage', $cache_context->getContext());

    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, 'full');

    // Render it so the default cache contexts are applied.
    $renderer->renderRoot($build);
    $this->assertTrue(in_array('workspace', $build['#cache']['contexts'], TRUE));

    $cid_parts = array_merge($build['#cache']['keys'], $cache_contexts_manager->convertTokensToKeys($build['#cache']['contexts'])->getKeys());
    $this->assertTrue(in_array('[workspace]=stage', $cid_parts, TRUE));

    // Test that a cache entry is created.
    $cid = implode(':', $cid_parts);
    $bin = $build['#cache']['bin'];
    $this->assertInstanceOf(\stdClass::class, $this->container->get('cache.' . $bin)->get($cid), 'The entity render element has been cached.');
  }

}
