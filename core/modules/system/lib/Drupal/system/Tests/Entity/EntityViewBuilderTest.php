<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Entity\EntityViewBuilderTest.
 */

namespace Drupal\system\Tests\Entity;

/**
 * Tests the entity view builder.
 */
class EntityViewBuilderTest extends EntityUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_reference');

  public static function getInfo() {
    return array(
      'name' => 'Entity rendering',
      'description' => 'Tests the entity view builder.',
      'group' => 'Entity API',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installConfig(array('entity_test'));
  }

  /**
   * Tests entity render cache handling.
   */
  public function testEntityViewBuilderCache() {
    // Force a request via GET so we can get drupal_render() cache working.
    $request_method = $_SERVER['REQUEST_METHOD'];
    $this->container->get('request')->setMethod('GET');

    $entity_test = $this->createTestEntity('entity_test');

    // Test that new entities (before they are saved for the first time) do not
    // generate a cache entry.
    $build = $this->container->get('entity.manager')->getViewBuilder('entity_test')->view($entity_test, 'full');
    $this->assertFalse(isset($build['#cache']), 'The render array element of new (unsaved) entities is not cached.');

    // Get a fully built entity view render array.
    $entity_test->save();
    $build = $this->container->get('entity.manager')->getViewBuilder('entity_test')->view($entity_test, 'full');
    $cid = drupal_render_cid_create($build);
    $bin = $build['#cache']['bin'];

    // Mock the build array to not require the theme registry.
    unset($build['#theme']);
    $build['#markup'] = 'entity_render_test';

    // Test that a cache entry is created.
    drupal_render($build);
    $this->assertTrue($this->container->get('cache.' . $bin)->get($cid), 'The entity render element has been cached.');

    // Re-save the entity and check that the cache entry has been deleted.
    $entity_test->save();
    $this->assertFalse($this->container->get('cache.' . $bin)->get($cid), 'The entity render cache has been cleared when the entity was saved.');

    // Rebuild the render array (creating a new cache entry in the process) and
    // delete the entity to check the cache entry is deleted.
    unset($build['#printed']);
    drupal_render($build);
    $this->assertTrue($this->container->get('cache.' . $bin)->get($cid), 'The entity render element has been cached.');
    $entity_test->delete();
    $this->assertFalse($this->container->get('cache.' . $bin)->get($cid), 'The entity render cache has been cleared when the entity was deleted.');

    // Restore the previous request method.
    $this->container->get('request')->setMethod($request_method);
  }

  /**
   * Tests entity render cache with references.
   */
  public function testEntityViewBuilderCacheWithReferences() {
    // Force a request via GET so we can get drupal_render() cache working.
    $request_method = $_SERVER['REQUEST_METHOD'];
    $this->container->get('request')->setMethod('GET');

    // Create an entity reference field and an entity that will be referenced.
    entity_reference_create_instance('entity_test', 'entity_test', 'reference_field', 'Reference', 'entity_test');
    entity_get_display('entity_test', 'entity_test', 'full')->setComponent('reference_field')->save();
    $entity_test_reference = $this->createTestEntity('entity_test');
    $entity_test_reference->save();

    // Get a fully built entity view render array for the referenced entity.
    $build = $this->container->get('entity.manager')->getViewBuilder('entity_test')->view($entity_test_reference, 'full');
    $cid_reference = drupal_render_cid_create($build);
    $bin_reference = $build['#cache']['bin'];

    // Mock the build array to not require the theme registry.
    unset($build['#theme']);
    $build['#markup'] = 'entity_render_test';
    drupal_render($build);

    // Test that a cache entry was created for the referenced entity.
    $this->assertTrue($this->container->get('cache.' . $bin_reference)->get($cid_reference), 'The entity render element for the referenced entity has been cached.');

    // Create another entity that references the first one.
    $entity_test = $this->createTestEntity('entity_test');
    $entity_test->reference_field->entity = $entity_test_reference;
    $entity_test->save();

    // Get a fully built entity view render array.
    $build = $this->container->get('entity.manager')->getViewBuilder('entity_test')->view($entity_test, 'full');
    $cid = drupal_render_cid_create($build);
    $bin = $build['#cache']['bin'];

    // Mock the build array to not require the theme registry.
    unset($build['#theme']);
    $build['#markup'] = 'entity_render_test';
    drupal_render($build);

    // Test that a cache entry is created.
    $this->assertTrue($this->container->get('cache.' . $bin)->get($cid), 'The entity render element has been cached.');

    // Save the entity and verify that both cache entries have been deleted.
    $entity_test->save();
    $this->assertFalse($this->container->get('cache.' . $bin)->get($cid), 'The entity render cache has been cleared when the entity was deleted.');
    $this->assertFalse($this->container->get('cache.' . $bin_reference)->get($cid_reference), 'The entity render cache for the referenced entity has been cleared when the entity was deleted.');

    // Restore the previous request method.
    $this->container->get('request')->setMethod($request_method);
  }

  /**
   * Tests entity render cache toggling.
   */
  public function testEntityViewBuilderCacheToggling() {
    $entity_test = $this->createTestEntity('entity_test');
    $entity_test->save();

    // Test a view mode in default conditions: render caching is enabled for
    // the entity type and the view mode.
    $build = $this->container->get('entity.manager')->getViewBuilder('entity_test')->view($entity_test, 'full');
    $this->assertTrue(isset($build['#cache']), 'A view mode with render cache enabled has the correct output.');

    // Test that a view mode can opt out of render caching.
    $build = $this->container->get('entity.manager')->getViewBuilder('entity_test')->view($entity_test, 'test');
    $this->assertFalse(isset($build['#cache']), 'A view mode with render cache disabled has the correct output.');

    // Test that an entity type can opt out of render caching completely.
    $entity_test_no_cache = $this->createTestEntity('entity_test_label');
    $entity_test_no_cache->save();
    $build = $this->container->get('entity.manager')->getViewBuilder('entity_test_label')->view($entity_test_no_cache, 'full');
    $this->assertFalse(isset($build['#cache']), 'An entity type can opt out of render caching regardless of view mode configuration.');
  }

  /**
   * Creates an entity for testing.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity.
   */
  protected function createTestEntity($entity_type) {
    $data = array(
      'bundle' => $entity_type,
      'name' => $this->randomName(),
    );
    return $this->container->get('entity.manager')->getStorageController($entity_type)->create($data);
  }

}
