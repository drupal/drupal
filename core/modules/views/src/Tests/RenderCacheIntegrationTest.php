<?php

/**
 * @file
 * Contains \Drupal\views\Tests\RenderCacheIntegrationTest.
 */

namespace Drupal\views\Tests;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\views\Views;
use Drupal\views\Entity\View;

/**
 * Tests the general integration between views and the render cache.
 *
 * @group views
 */
class RenderCacheIntegrationTest extends ViewUnitTestBase {

  use AssertViewsCacheTagsTrait;

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_view', 'test_display', 'entity_test_fields', 'entity_test_row'];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'user', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
  }

  /**
   * Tests a field-based view's cache tags when using the "none" cache plugin.
   */
  public function testFieldBasedViewCacheTagsWithCachePluginNone() {
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
    $base_tags =  ['config:views.view.entity_test_fields', 'entity_test_list'];
    $this->assertViewsCacheTags($view, $base_tags, $do_assert_views_caches, $base_tags);


    // Non-empty result (1 entity).
    $entities[] = $entity = EntityTest::create();
    $entity->save();

    $tags_with_entity = Cache::mergeTags($base_tags, $entities[0]->getCacheTags());
    $this->assertViewsCacheTags($view, $tags_with_entity, $do_assert_views_caches, $tags_with_entity);


    // Paged result (more entities than the items-per-page limit).
    for ($i = 0; $i < 5; $i++) {
      $entities[] = $entity = EntityTest::create();
      $entity->save();
    }
    // Page 1.
    $tags_page_1 = Cache::mergeTags($base_tags, $entities[1]->getCacheTags(), $entities[2]->getCacheTags(), $entities[3]->getCacheTags(), $entities[4]->getCacheTags(), $entities[5]->getCacheTags());
    $this->assertViewsCacheTags($view, $tags_page_1, $do_assert_views_caches, $tags_page_1);
    $view->destroy();
    // Page 2.
    $view->setCurrentPage(1);
    $tags_page_2 = Cache::mergeTags($base_tags, $entities[0]->getCacheTags());
    $this->assertViewsCacheTags($view, $tags_page_2, $do_assert_views_caches, $tags_page_2);
    $view->destroy();

    // Ensure that invalidation works on both pages.
    $view->setCurrentPage(1);
    $entities[0]->name->value = $random_name = $this->randomMachineName();
    $entities[0]->save();
    $build = $this->assertViewsCacheTags($view, $tags_page_2, $do_assert_views_caches, $tags_page_2);
    $this->assertTrue(strpos($build['#markup'], $random_name) !== FALSE);
    $view->destroy();

    $view->setCurrentPage(0);
    $entities[1]->name->value = $random_name = $this->randomMachineName();
    $entities[1]->save();
    $build = $this->assertViewsCacheTags($view, $tags_page_1, $do_assert_views_caches, $tags_page_1);
    $this->assertTrue(strpos($build['#markup'], $random_name) !== FALSE);
  }

  /**
   * Tests a entity-based view's cache tags when using the "none" cache plugin.
   */
  public function testEntityBasedViewCacheTagsWithCachePluginNone() {
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


    // Non-empty result (1 entity).
    $entities[] = $entity = EntityTest::create();
    $entity->save();

    $result_tags_with_entity = Cache::mergeTags($base_tags, $entities[0]->getCacheTags());
    $render_tags_with_entity = Cache::mergeTags($base_render_tags, $entities[0]->getCacheTags(), ['entity_test_view']);
    $this->assertViewsCacheTags($view, $result_tags_with_entity, $do_assert_views_caches, $render_tags_with_entity);


    // Paged result (more entities than the items-per-page limit).
    for ($i = 0; $i < 5; $i++) {
      $entities[] = $entity = EntityTest::create();
      $entity->save();
    }

    $new_entities_cache_tags = Cache::mergeTags($entities[1]->getCacheTags(), $entities[2]->getCacheTags(), $entities[3]->getCacheTags(), $entities[4]->getCacheTags(), $entities[5]->getCacheTags());
    $result_tags_page_1 = Cache::mergeTags($base_tags, $new_entities_cache_tags);
    $render_tags_page_1 = Cache::mergeTags($base_render_tags, $new_entities_cache_tags, ['entity_test_view']);
    $this->assertViewsCacheTags($view, $result_tags_page_1, $do_assert_views_caches, $render_tags_page_1);
  }

  /**
   * Ensure that the view renderable contains the cache contexts.
   */
  public function testBuildRenderableWithCacheContexts() {
    $view = View::load('test_view');
    $display =& $view->getDisplay('default');
    $display['cache_metadata']['contexts'] = ['beatles'];
    $executable = $view->getExecutable();

    $build = $executable->buildRenderable();
    $this->assertEqual(['beatles'], $build['#cache']['contexts']);
  }

  /**
   * Ensures that saving a view calculates the cache contexts.
   */
  public function testViewAddCacheMetadata() {
    $view = View::load('test_display');
    $view->save();

    $this->assertEqual(['languages:' . LanguageInterface::TYPE_CONTENT, 'languages:' . LanguageInterface::TYPE_INTERFACE, 'user.node_grants:view'], $view->getDisplay('default')['cache_metadata']['contexts']);
  }

}
