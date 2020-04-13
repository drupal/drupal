<?php

namespace Drupal\Tests\views\Kernel;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\views\Tests\AssertViewsCacheTagsTrait;
use Drupal\views\Views;
use Drupal\views\Entity\View;

/**
 * Tests the general integration between views and the render cache.
 *
 * @group views
 */
class RenderCacheIntegrationTest extends ViewsKernelTestBase {

  use AssertViewsCacheTagsTrait;

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view', 'test_display', 'entity_test_fields', 'entity_test_row'];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
  }

  /**
   * Tests a field-based view's cache tags when using the "none" cache plugin.
   */
  public function testFieldBasedViewCacheTagsWithCachePluginNone() {
    $view = Views::getview('entity_test_fields');
    $view->getDisplay()->overrideOption('cache', [
      'type' => 'none',
    ]);
    $view->save();

    $this->assertCacheTagsForFieldBasedView(FALSE);
  }

  /**
   * Tests a field-based view's cache tags when using the "tag" cache plugin.
   */
  public function testFieldBasedViewCacheTagsWithCachePluginTag() {
    $view = Views::getview('entity_test_fields');
    $view->getDisplay()->overrideOption('cache', [
      'type' => 'tag',
    ]);
    $view->save();

    $this->assertCacheTagsForFieldBasedView(TRUE);
  }

  /**
   * Tests a field-based view's cache tags when using the "time" cache plugin.
   */
  public function testFieldBasedViewCacheTagsWithCachePluginTime() {
    $view = Views::getview('entity_test_fields');
    $view->getDisplay()->overrideOption('cache', [
      'type' => 'time',
      'options' => [
        'results_lifespan' => 3600,
        'output_lifespan' => 3600,
      ],
    ]);
    $view->save();

    $this->assertCacheTagsForFieldBasedView(TRUE);
  }

  /**
   * Tests cache tags on output & result cache items for a field-based view.
   *
   * @param bool $do_assert_views_caches
   *   Whether to check Views' result & output caches.
   */
  protected function assertCacheTagsForFieldBasedView($do_assert_views_caches) {
    $this->pass('Checking cache tags for field-based view.');
    $view = Views::getview('entity_test_fields');

    // Empty result (no entities yet).
    $this->pass('Test without entities');
    $base_tags = ['config:views.view.entity_test_fields', 'entity_test_list'];
    $this->assertViewsCacheTags($view, $base_tags, $do_assert_views_caches, $base_tags);
    $this->assertViewsCacheTagsFromStaticRenderArray($view, $base_tags, $do_assert_views_caches);

    // Non-empty result (1 entity).
    /** @var \Drupal\Core\Entity\EntityInterface[] $entities */
    $entities[] = $entity = EntityTest::create();
    $entity->save();

    $this->pass('Test with entities');
    $tags_with_entity = Cache::mergeTags($base_tags, $entities[0]->getCacheTags());
    $this->assertViewsCacheTags($view, $tags_with_entity, $do_assert_views_caches, $tags_with_entity);
    $this->assertViewsCacheTagsFromStaticRenderArray($view, $tags_with_entity, $do_assert_views_caches);

    // Paged result (more entities than the items-per-page limit).
    for ($i = 0; $i < 5; $i++) {
      $entities[] = $entity = EntityTest::create();
      $entity->save();
    }

    // Page 1.
    $this->pass('Test pager');
    $this->pass('Page 1');
    \Drupal::request()->query->set('page', 0);
    $tags_page_1 = Cache::mergeTags($base_tags, $entities[1]->getCacheTags());
    $tags_page_1 = Cache::mergeTags($tags_page_1, $entities[2]->getCacheTags());
    $tags_page_1 = Cache::mergeTags($tags_page_1, $entities[3]->getCacheTags());
    $tags_page_1 = Cache::mergeTags($tags_page_1, $entities[4]->getCacheTags());
    $tags_page_1 = Cache::mergeTags($tags_page_1, $entities[5]->getCacheTags());
    $this->assertViewsCacheTags($view, $tags_page_1, $do_assert_views_caches, $tags_page_1);
    $this->assertViewsCacheTagsFromStaticRenderArray($view, $tags_page_1, $do_assert_views_caches);
    $view->destroy();
    // Page 2.
    $this->pass('Page 2');
    $view->setCurrentPage(1);
    \Drupal::request()->query->set('page', 1);
    $tags_page_2 = Cache::mergeTags($base_tags, $entities[0]->getCacheTags());
    $this->assertViewsCacheTags($view, $tags_page_2, $do_assert_views_caches, $tags_page_2);
    $view->destroy();

    // Ensure that invalidation works on both pages.
    $this->pass('Page invalidations');
    $this->pass('Page 2');
    $view->setCurrentPage(1);
    \Drupal::request()->query->set('page', 1);
    $entities[0]->name->value = $random_name = $this->randomMachineName();
    $entities[0]->save();
    $build = $this->assertViewsCacheTags($view, $tags_page_2, $do_assert_views_caches, $tags_page_2);
    // @todo Static render arrays don't support different pages yet, see
    //   https://www.drupal.org/node/2500701.
    // $this->assertViewsCacheTagsFromStaticRenderArray($view, $tags_page_2, $do_assert_views_caches);
    $this->assertStringContainsString($random_name, (string) $build['#markup']);
    $view->destroy();

    $this->pass('Page 1');
    $view->setCurrentPage(0);
    \Drupal::request()->query->set('page', 0);
    $entities[1]->name->value = $random_name = $this->randomMachineName();
    $entities[1]->save();
    $build = $this->assertViewsCacheTags($view, $tags_page_1, $do_assert_views_caches, $tags_page_1);
    $this->assertViewsCacheTagsFromStaticRenderArray($view, $tags_page_1, $do_assert_views_caches);
    $this->assertStringContainsString($random_name, (string) $build['#markup']);
    $view->destroy();

    // Setup arguments to ensure that render caching also varies by them.
    $this->pass('Test arguments');

    // Custom assert for a single result row.
    $single_entity_assertions = function (array $build, EntityInterface $entity) {
      $this->setRawContent($build['#markup']);

      $result = $this->cssSelect('div.views-row');
      $count = count($result);
      $this->assertEqual($count, 1);

      $this->assertEqual((string) $result[0]->div->span, (string) $entity->id());
    };

    // Execute the view once with a static renderable and one with a full
    // prepared render array.
    $tags_argument = Cache::mergeTags($base_tags, $entities[0]->getCacheTags());
    $view->setArguments([$entities[0]->id()]);
    $build = $this->assertViewsCacheTags($view, $tags_argument, $do_assert_views_caches, $tags_argument);
    $single_entity_assertions($build, $entities[0]);

    $view->setArguments([$entities[0]->id()]);
    $build = $this->assertViewsCacheTagsFromStaticRenderArray($view, $tags_argument, $do_assert_views_caches);
    $single_entity_assertions($build, $entities[0]);

    // Set a different argument and ensure that the result is different.
    $tags2_argument = Cache::mergeTags($base_tags, $entities[1]->getCacheTags());
    $view->setArguments([$entities[1]->id()]);
    $build = $this->assertViewsCacheTagsFromStaticRenderArray($view, $tags2_argument, $do_assert_views_caches);
    $single_entity_assertions($build, $entities[1]);

    $view->destroy();
  }

  /**
   * Tests a entity-based view's cache tags when using the "none" cache plugin.
   */
  public function testEntityBasedViewCacheTagsWithCachePluginNone() {
    $view = Views::getview('entity_test_row');
    $view->getDisplay()->overrideOption('cache', [
      'type' => 'none',
    ]);
    $view->save();

    $this->assertCacheTagsForEntityBasedView(FALSE);
  }

  /**
   * Tests a entity-based view's cache tags when using the "tag" cache plugin.
   */
  public function testEntityBasedViewCacheTagsWithCachePluginTag() {
    $view = Views::getview('entity_test_row');
    $view->getDisplay()->overrideOption('cache', [
      'type' => 'tag',
    ]);
    $view->save();

    $this->assertCacheTagsForEntityBasedView(TRUE);
  }

  /**
   * Tests a entity-based view's cache tags when using the "time" cache plugin.
   */
  public function testEntityBasedViewCacheTagsWithCachePluginTime() {
    $view = Views::getview('entity_test_row');
    $view->getDisplay()->overrideOption('cache', [
      'type' => 'time',
      'options' => [
        'results_lifespan' => 3600,
        'output_lifespan' => 3600,
      ],
    ]);
    $view->save();

    $this->assertCacheTagsForEntityBasedView(TRUE);
  }

  /**
   * Tests cache tags on output & result cache items for an entity-based view.
   */
  protected function assertCacheTagsForEntityBasedView($do_assert_views_caches) {
    $this->pass('Checking cache tags for entity-based view.');
    $view = Views::getview('entity_test_row');

    // Empty result (no entities yet).
    $base_tags = $base_render_tags = ['config:views.view.entity_test_row', 'entity_test_list'];
    $this->assertViewsCacheTags($view, $base_tags, $do_assert_views_caches, $base_tags);
    $this->assertViewsCacheTagsFromStaticRenderArray($view, $base_tags, $do_assert_views_caches);

    // Non-empty result (1 entity).
    $entities[] = $entity = EntityTest::create();
    $entity->save();

    $result_tags_with_entity = Cache::mergeTags($base_tags, $entities[0]->getCacheTags());
    $render_tags_with_entity = Cache::mergeTags($base_render_tags, $entities[0]->getCacheTags());
    $render_tags_with_entity = Cache::mergeTags($render_tags_with_entity, ['entity_test_view']);
    $this->assertViewsCacheTags($view, $result_tags_with_entity, $do_assert_views_caches, $render_tags_with_entity);
    $this->assertViewsCacheTagsFromStaticRenderArray($view, $render_tags_with_entity, $do_assert_views_caches);

    // Paged result (more entities than the items-per-page limit).
    for ($i = 0; $i < 5; $i++) {
      $entities[] = $entity = EntityTest::create();
      $entity->save();
    }

    $new_entities_cache_tags = Cache::mergeTags($entities[1]->getCacheTags(), $entities[2]->getCacheTags());
    $new_entities_cache_tags = Cache::mergeTags($new_entities_cache_tags, $entities[3]->getCacheTags());
    $new_entities_cache_tags = Cache::mergeTags($new_entities_cache_tags, $entities[4]->getCacheTags());
    $new_entities_cache_tags = Cache::mergeTags($new_entities_cache_tags, $entities[5]->getCacheTags());
    $result_tags_page_1 = Cache::mergeTags($base_tags, $new_entities_cache_tags);
    $render_tags_page_1 = Cache::mergeTags($base_render_tags, $new_entities_cache_tags);
    $render_tags_page_1 = Cache::mergeTags($render_tags_page_1, ['entity_test_view']);
    $this->assertViewsCacheTags($view, $result_tags_page_1, $do_assert_views_caches, $render_tags_page_1);
    $this->assertViewsCacheTagsFromStaticRenderArray($view, $render_tags_page_1, $do_assert_views_caches);
  }

  /**
   * Ensure that the view renderable contains the cache contexts.
   */
  public function testBuildRenderableWithCacheContexts() {
    $view = View::load('test_view');
    $display =& $view->getDisplay('default');
    $display['cache_metadata']['contexts'] = ['views_test_cache_context'];
    $executable = $view->getExecutable();

    $build = $executable->buildRenderable();
    $this->assertEqual(['views_test_cache_context'], $build['#cache']['contexts']);
  }

  /**
   * Ensures that saving a view calculates the cache contexts.
   */
  public function testViewAddCacheMetadata() {
    $view = View::load('test_display');
    $view->save();

    $this->assertEqual(['languages:' . LanguageInterface::TYPE_CONTENT, 'languages:' . LanguageInterface::TYPE_INTERFACE, 'url.query_args', 'user.node_grants:view', 'user.permissions'], $view->getDisplay('default')['cache_metadata']['contexts']);
  }

}
