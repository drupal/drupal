<?php

declare(strict_types=1);

namespace Drupal\Tests\workspaces\Functional;

use Drupal\Core\Cache\CacheableMetadata;
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
  protected static $modules = ['block', 'node', 'workspaces', 'workspaces_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the 'workspace' cache context.
   */
  public function testWorkspaceCacheContext(): void {
    $renderer = \Drupal::service('renderer');
    $cache_contexts_manager = \Drupal::service("cache_contexts_manager");
    /** @var \Drupal\Core\Cache\VariationCacheFactoryInterface $variation_cache_factory */
    $variation_cache_factory = $this->container->get('variation_cache_factory');

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
    $this->assertContains('workspace', $build['#cache']['contexts']);

    $context_tokens = $cache_contexts_manager->convertTokensToKeys($build['#cache']['contexts'])->getKeys();
    $this->assertContains('[workspace]=live', $context_tokens);

    // Test that a cache entry is created.
    $cache_bin = $variation_cache_factory->get($build['#cache']['bin']);
    $this->assertInstanceOf(\stdClass::class, $cache_bin->get($build['#cache']['keys'], CacheableMetadata::createFromRenderArray($build)));

    // Switch to the test workspace and check that the correct workspace cache
    // context is used.
    $test_user = $this->drupalCreateUser(['view any workspace']);
    $this->drupalLogin($test_user);

    $vultures = Workspace::create([
      'id' => 'vultures',
      'label' => 'Vultures',
    ]);
    $vultures->save();

    $workspace_manager = \Drupal::service('workspaces.manager');
    $workspace_manager->setActiveWorkspace($vultures);

    $cache_context = new WorkspaceCacheContext($workspace_manager);
    $this->assertSame('vultures', $cache_context->getContext());

    $build = \Drupal::entityTypeManager()->getViewBuilder('node')->view($node, 'full');

    // Render it so the default cache contexts are applied.
    $renderer->renderRoot($build);
    $this->assertContains('workspace', $build['#cache']['contexts']);

    $context_tokens = $cache_contexts_manager->convertTokensToKeys($build['#cache']['contexts'])->getKeys();
    $this->assertContains('[workspace]=vultures', $context_tokens);

    // Test that a cache entry is created.
    $cache_bin = $variation_cache_factory->get($build['#cache']['bin']);
    $this->assertInstanceOf(\stdClass::class, $cache_bin->get($build['#cache']['keys'], CacheableMetadata::createFromRenderArray($build)));
  }

}
