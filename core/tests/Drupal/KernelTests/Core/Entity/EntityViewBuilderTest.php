<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the entity view builder.
 *
 * @group Entity
 */
class EntityViewBuilderTest extends EntityKernelTestBase {

  use EntityReferenceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user', 'entity_test']);

    // Give anonymous users permission to view test entities.
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('view test entity')
      ->save();
  }

  /**
   * Tests entity render cache handling.
   */
  public function testEntityViewBuilderCache() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $cache_contexts_manager = \Drupal::service("cache_contexts_manager");
    $cache = \Drupal::cache();

    // Force a request via GET so cache is rendered.
    $request = \Drupal::request();
    $request_method = $request->server->get('REQUEST_METHOD');
    $request->setMethod('GET');

    $entity_test = $this->createTestEntity('entity_test');

    // Test that new entities (before they are saved for the first time) do not
    // generate a cache entry.
    $build = $this->container->get('entity_type.manager')->getViewBuilder('entity_test')->view($entity_test, 'full');
    $this->assertNotEmpty($build['#cache']);
    $this->assertEquals(['tags', 'contexts', 'max-age'], array_keys($build['#cache']), 'The render array element of new (unsaved) entities is not cached, but does have cache tags set.');

    // Get a fully built entity view render array.
    $entity_test->save();
    $build = $this->container->get('entity_type.manager')->getViewBuilder('entity_test')->view($entity_test, 'full');
    $cid_parts = array_merge($build['#cache']['keys'], $cache_contexts_manager->convertTokensToKeys(['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'user.permissions'])->getKeys());
    $cid = implode(':', $cid_parts);
    $bin = $build['#cache']['bin'];

    // Mock the build array to not require the theme registry.
    unset($build['#theme']);
    $build['#markup'] = 'entity_render_test';

    // Test that a cache entry is created.
    $renderer->renderRoot($build);
    $this->assertNotEmpty($this->container->get('cache.' . $bin)->get($cid), 'The entity render element has been cached.');

    // Re-save the entity and check that the cache entry has been deleted.
    $cache->set('kittens', 'Kitten data', Cache::PERMANENT, $build['#cache']['tags']);
    $entity_test->save();
    $this->assertFalse($this->container->get('cache.' . $bin)->get($cid), 'The entity render cache has been cleared when the entity was saved.');
    $this->assertFalse($cache->get('kittens'), 'The entity saving has invalidated cache tags.');

    // Rebuild the render array (creating a new cache entry in the process) and
    // delete the entity to check the cache entry is deleted.
    unset($build['#printed']);
    $renderer->renderRoot($build);
    $this->assertNotEmpty($this->container->get('cache.' . $bin)->get($cid), 'The entity render element has been cached.');
    $entity_test->delete();
    $this->assertFalse($this->container->get('cache.' . $bin)->get($cid), 'The entity render cache has been cleared when the entity was deleted.');

    // Restore the previous request method.
    $request->setMethod($request_method);
  }

  /**
   * Tests entity render cache with references.
   */
  public function testEntityViewBuilderCacheWithReferences() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $cache_contexts_manager = \Drupal::service("cache_contexts_manager");

    // Force a request via GET so we can get
    // \Drupal::service('renderer')->render() cache working.
    $request = \Drupal::request();
    $request_method = $request->server->get('REQUEST_METHOD');
    $request->setMethod('GET');

    // Create an entity reference field and an entity that will be referenced.
    $this->createEntityReferenceField('entity_test', 'entity_test', 'reference_field', 'Reference', 'entity_test');
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent('reference_field', [
        'type' => 'entity_reference_entity_view',
        'settings' => ['link' => FALSE],
      ])->save();
    $entity_test_reference = $this->createTestEntity('entity_test');
    $entity_test_reference->save();

    // Get a fully built entity view render array for the referenced entity.
    $build = $this->container->get('entity_type.manager')->getViewBuilder('entity_test')->view($entity_test_reference, 'full');
    $cid_parts = array_merge($build['#cache']['keys'], $cache_contexts_manager->convertTokensToKeys(['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'user.permissions'])->getKeys());
    $cid_reference = implode(':', $cid_parts);
    $bin_reference = $build['#cache']['bin'];

    // Mock the build array to not require the theme registry.
    unset($build['#theme']);
    $build['#markup'] = 'entity_render_test';
    $renderer->renderRoot($build);

    // Test that a cache entry was created for the referenced entity.
    $this->assertNotEmpty($this->container->get('cache.' . $bin_reference)->get($cid_reference), 'The entity render element for the referenced entity has been cached.');

    // Create another entity that references the first one.
    $entity_test = $this->createTestEntity('entity_test');
    $entity_test->reference_field->entity = $entity_test_reference;
    $entity_test->save();

    // Get a fully built entity view render array.
    $build = $this->container->get('entity_type.manager')->getViewBuilder('entity_test')->view($entity_test, 'full');
    $cid_parts = array_merge($build['#cache']['keys'], $cache_contexts_manager->convertTokensToKeys(['languages:' . LanguageInterface::TYPE_INTERFACE, 'theme', 'user.permissions'])->getKeys());
    $cid = implode(':', $cid_parts);
    $bin = $build['#cache']['bin'];

    // Mock the build array to not require the theme registry.
    unset($build['#theme']);
    $build['#markup'] = 'entity_render_test';
    $renderer->renderRoot($build);

    // Test that a cache entry is created.
    $this->assertNotEmpty($this->container->get('cache.' . $bin)->get($cid), 'The entity render element has been cached.');

    // Save the entity and verify that both cache entries have been deleted.
    $entity_test_reference->save();
    $this->assertFalse($this->container->get('cache.' . $bin)->get($cid), 'The entity render cache has been cleared when the entity was deleted.');
    $this->assertFalse($this->container->get('cache.' . $bin_reference)->get($cid_reference), 'The entity render cache for the referenced entity has been cleared when the entity was deleted.');

    // Restore the previous request method.
    $request->setMethod($request_method);
  }

  /**
   * Tests entity render cache toggling.
   */
  public function testEntityViewBuilderCacheToggling() {
    $entity_test = $this->createTestEntity('entity_test');
    $entity_test->save();

    // Test a view mode in default conditions: render caching is enabled for
    // the entity type and the view mode.
    $build = $this->container->get('entity_type.manager')->getViewBuilder('entity_test')->view($entity_test, 'full');
    $this->assertNotEmpty($build['#cache']);
    $this->assertEquals(['tags', 'contexts', 'max-age', 'keys', 'bin'], array_keys($build['#cache']), 'A view mode with render cache enabled has the correct output (cache tags, keys, contexts, max-age and bin).');

    // Test that a view mode can opt out of render caching.
    $build = $this->container->get('entity_type.manager')->getViewBuilder('entity_test')->view($entity_test, 'test');
    $this->assertNotEmpty($build['#cache']);
    $this->assertEquals(['tags', 'contexts', 'max-age'], array_keys($build['#cache']), 'A view mode with render cache disabled has the correct output (only cache tags, contexts and max-age).');

    // Test that an entity type can opt out of render caching completely.
    $this->installEntitySchema('entity_test_label');
    $entity_test_no_cache = $this->createTestEntity('entity_test_label');
    $entity_test_no_cache->save();
    $build = $this->container->get('entity_type.manager')->getViewBuilder('entity_test_label')->view($entity_test_no_cache, 'full');
    $this->assertNotEmpty($build['#cache']);
    $this->assertEquals(['tags', 'contexts', 'max-age'], array_keys($build['#cache']), 'An entity type can opt out of render caching regardless of view mode configuration, but always has cache tags, contexts and max-age set.');
  }

  /**
   * Tests weighting of display components.
   */
  public function testEntityViewBuilderWeight() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    // Set a weight for the label component.
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('entity_test', 'entity_test', 'full')
      ->setComponent('label', ['weight' => 20])
      ->save();

    // Create and build a test entity.
    $entity_test = $this->createTestEntity('entity_test');
    $view = $this->container->get('entity_type.manager')->getViewBuilder('entity_test')->view($entity_test, 'full');
    $renderer->renderRoot($view);

    // Check that the weight is respected.
    $this->assertEquals(20, $view['label']['#weight'], 'The weight of a display component is respected.');
  }

  /**
   * Tests EntityViewBuilder::viewField() language awareness.
   */
  public function testViewField() {
    // Allow access to view translations as well.
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('view test entity translations')
      ->save();
    $this->enableModules([
      'language',
      'content_translation',
    ]);
    $this->installEntitySchema('entity_test_mul');
    $en = ConfigurableLanguage::create(['id' => 'en']);
    $en->save();
    $es = ConfigurableLanguage::create(['id' => 'es']);
    $es->save();
    $this->container->get('content_translation.manager')->setEnabled('entity_test_mul', 'entity_test_mul', TRUE);

    $this->createEntityReferenceField('entity_test_mul', 'entity_test_mul', 'reference_field', 'Reference', 'entity_test_mul');

    // Make the entity reference field non-translatable to confirm it still
    // renders the correct language when displayed as an entity reference.
    $field = FieldConfig::loadByName('entity_test_mul', 'entity_test_mul', 'reference_field');
    $field->set('translatable', FALSE)->save();

    // Create fields and displays for the test entity.
    FieldStorageConfig::create([
      'field_name' => 'text',
      'entity_type' => 'entity_test_mul',
      'type' => 'string',
    ])->save();

    FieldConfig::create([
      'field_name' => 'text',
      'entity_type' => 'entity_test_mul',
      'bundle' => 'entity_test_mul',
      'label' => 'Translated text',
      'translatable' => TRUE,
    ])->save();

    EntityViewMode::create([
      'id' => 'entity_test_mul.full',
      'targetEntityType' => 'entity_test_mul',
      'status' => FALSE,
      'enabled' => TRUE,
      'label' => 'Full',
    ])->save();

    $display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test_mul',
      'bundle' => 'entity_test_mul',
      'mode' => 'full',
      'label' => 'My view mode',
      'status' => TRUE,
    ])
      ->setComponent('reference_field', [
        'type' => 'entity_reference_entity_view',
        'settings' => [
          'view_mode' => 'full',
        ],
      ])
      ->setComponent('text', [
        'type' => 'string',
        'region' => 'content',
      ]);
    $display->save();

    // Create the entity that will be displayed in the entity reference field
    // of the main entity.
    $referenced_entity = $this->createTestEntity('entity_test_mul');
    $referenced_entity->addTranslation('es', $referenced_entity->getTranslation('en')->toArray());
    $referenced_entity->set('text', 'Text in English');
    $referenced_entity->getTranslation('es')->text = 'Text in Spanish';

    // The entity that will reference $referenced_entity.
    $main_entity = $this->createTestEntity('entity_test_mul');
    $main_entity->addTranslation('es', $main_entity->getTranslation('en')->toArray());
    $main_entity->set('reference_field', $referenced_entity);

    $view_builder = $this->container->get('entity_type.manager')->getViewBuilder('entity_test_mul');
    $renderer = $this->container->get('renderer');

    // Build the view for the reference field and render in English - the site
    // default. Confirm the reference field shows the content of the English
    // translation.
    $reference_field = $main_entity->get('reference_field');
    $reference_field_array_english = $view_builder->viewField($reference_field, 'full');
    $rendered_reference_field_english = $renderer->renderRoot($reference_field_array_english);
    $this->assertStringContainsString('Text in English', (string) $rendered_reference_field_english);

    // Change the default language to Spanish and render the reference
    // field again. It should display the contents of the Spanish translation.
    \Drupal::service('language.default')->set($es);
    \Drupal::languageManager()->reset();
    \Drupal::languageManager()->getCurrentLanguage();
    $reference_field_array_spanish = $view_builder->viewField($reference_field, 'full');
    $rendered_reference_field_spanish = $renderer->renderRoot($reference_field_array_spanish);
    $this->assertStringContainsString('Text in Spanish', (string) $rendered_reference_field_spanish);
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
    $data = [
      'bundle' => $entity_type,
      'name' => $this->randomMachineName(),
    ];
    return $this->container->get('entity_type.manager')->getStorage($entity_type)->create($data);
  }

  /**
   * Tests that viewing an entity without template does not specify #theme.
   */
  public function testNoTemplate() {
    // Ensure that an entity type without explicit view builder uses the
    // default.
    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_type = $entity_type_manager->getDefinition('entity_test_base_field_display');
    $this->assertTrue($entity_type->hasViewBuilderClass());
    $this->assertEquals(EntityViewBuilder::class, $entity_type->getViewBuilderClass());

    // Ensure that an entity without matching template does not have a #theme
    // key.
    $entity = $this->createTestEntity('entity_test');
    $build = $entity_type_manager->getViewBuilder('entity_test')->view($entity);
    $this->assertEquals($entity, $build['#entity_test']);
    $this->assertArrayNotHasKey('#theme', $build);
  }

  /**
   * Tests an entity type with an external canonical rel.
   */
  public function testExternalEntity() {
    $this->installEntitySchema('entity_test_external');
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');
    $entity_test = $this->createTestEntity('entity_test_external');
    $entity_test->save();
    $view = $this->container->get('entity_type.manager')->getViewBuilder('entity_test_external')->view($entity_test);
    $renderer->renderRoot($view);
    $this->assertArrayNotHasKey('#contextual_links', $view);
  }

}
