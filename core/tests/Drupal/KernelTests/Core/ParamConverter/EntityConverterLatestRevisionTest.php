<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\ParamConverter;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the entity converter when the "load_latest_revision" flag is set.
 *
 * @group ParamConverter
 * @coversDefaultClass \Drupal\Core\ParamConverter\EntityConverter
 */
class EntityConverterLatestRevisionTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'entity_test',
    'user',
    'language',
    'system',
  ];

  /**
   * The entity converter service.
   *
   * @var \Drupal\Core\ParamConverter\EntityConverter
   */
  protected $converter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpCurrentUser();

    $this->installEntitySchema('entity_test_mulrev');
    $this->installEntitySchema('entity_test');
    $this->installConfig(['system', 'language']);

    $this->converter = $this->container->get('paramconverter.entity');

    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Tests with no matching entity.
   */
  public function testNoEntity(): void {
    $converted = $this->converter->convert(1, [
      'load_latest_revision' => TRUE,
      'type' => 'entity:entity_test_mulrev',
    ], 'foo', []);
    $this->assertEquals(NULL, $converted);
  }

  /**
   * Tests with no pending revision.
   */
  public function testEntityNoPendingRevision(): void {
    $entity = EntityTestMulRev::create();
    $entity->save();

    $converted = $this->converter->convert(1, [
      'load_latest_revision' => TRUE,
      'type' => 'entity:entity_test_mulrev',
    ], 'foo', []);
    $this->assertEquals($entity->getLoadedRevisionId(), $converted->getLoadedRevisionId());
  }

  /**
   * Tests with a pending revision.
   */
  public function testEntityWithPendingRevision(): void {
    $entity = EntityTestMulRev::create();
    $entity->save();

    $entity->isDefaultRevision(FALSE);
    $entity->setNewRevision(TRUE);
    $entity->save();

    $converted = $this->converter->convert(1, [
      'load_latest_revision' => TRUE,
      'type' => 'entity:entity_test_mulrev',
    ], 'foo', []);

    $this->assertEquals($entity->getLoadedRevisionId(), $converted->getLoadedRevisionId());
  }

  /**
   * Tests with a translated pending revision.
   */
  public function testWithTranslatedPendingRevision(): void {
    // Enable translation for test entities.
    $this->container->get('state')->set('entity_test.translation', TRUE);
    $this->container->get('entity_type.bundle.info')->clearCachedBundles();

    // Create a new English entity.
    $entity = EntityTestMulRev::create();
    $entity->save();

    // Create a translated pending revision.
    $entity_type_id = 'entity_test_mulrev';
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage($entity_type_id);
    /** @var \Drupal\Core\Entity\ContentEntityInterface $translated_entity */
    $translated_entity = $storage->createRevision($entity->addTranslation('de'), FALSE);
    $translated_entity->save();

    // Change the site language so the converters will attempt to load entities
    // with language 'de'.
    $this->config('system.site')->set('default_langcode', 'de')->save();

    // The default loaded language is still 'en'.
    EntityTestMulRev::load($entity->id());
    $this->assertEquals('en', $entity->language()->getId());

    // The converter will load the latest revision in the correct language.
    $converted = $this->converter->convert(1, [
      'load_latest_revision' => TRUE,
      'type' => 'entity:entity_test_mulrev',
    ], 'foo', []);
    $this->assertEquals('de', $converted->language()->getId());
    $this->assertEquals($translated_entity->getLoadedRevisionId(), $converted->getLoadedRevisionId());

    // Revert back to English as default language.
    $this->config('system.site')->set('default_langcode', 'en')->save();

    // The converter will load the latest revision in the correct language.
    $converted = $this->converter->convert(1, [
      'load_latest_revision' => TRUE,
      'type' => 'entity:entity_test_mulrev',
    ], 'foo', []);
    $this->assertEquals('en', $converted->language()->getId());
    $this->assertEquals($entity->getLoadedRevisionId(), $converted->getLoadedRevisionId());
  }

  /**
   * Tests that pending revisions are loaded only when needed.
   */
  public function testOptimizedConvert(): void {
    $entity = EntityTestMulRev::create();
    $entity->save();

    // Populate static cache for the current entity.
    $entity = EntityTestMulRev::load($entity->id());

    // Delete the base table entry for the current entity, however, since the
    // storage will query the revision table to get the latest revision, the
    // logic handling pending revisions will work correctly anyway.
    /** @var \Drupal\Core\Database\Connection $database */
    $database = $this->container->get('database');
    $database->delete('entity_test_mulrev')
      ->condition('id', $entity->id())
      ->execute();

    // If optimization works, converting a default revision should not trigger
    // a storage load, thus making the following assertion pass.
    $converted = $this->converter->convert(1, [
      'load_latest_revision' => TRUE,
      'type' => 'entity:entity_test_mulrev',
    ], 'foo', []);
    $this->assertEquals($entity->getLoadedRevisionId(), $converted->getLoadedRevisionId());
  }

  /**
   * Tests the latest revision flag and non-revisionable entities.
   */
  public function testConvertNonRevisionableEntityType(): void {
    $entity = EntityTest::create();
    $entity->save();

    $converted = $this->converter->convert(1, [
      'load_latest_revision' => TRUE,
      'type' => 'entity:entity_test',
    ], 'foo', []);

    $this->assertEquals($entity->id(), $converted->id());
  }

  /**
   * Tests an entity route parameter having 'bundle' definition property.
   *
   * @covers ::convert
   */
  public function testRouteParamWithBundleDefinition(): void {
    $entity1 = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'type' => 'foo',
    ]);
    $entity1->save();
    $entity2 = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'type' => 'bar',
    ]);
    $entity2->save();
    $entity3 = EntityTestMulRev::create([
      'name' => $this->randomString(),
      'type' => 'baz',
    ]);
    $entity3->save();

    $definition = [
      'type' => 'entity:entity_test_mulrev',
      'bundle' => [
        'foo',
        'bar',
      ],
      'load_latest_revision' => TRUE,
    ];

    // An entity whose bundle is in the definition list is converted.
    $converted = $this->converter->convert($entity1->id(), $definition, 'qux', []);
    $this->assertSame($entity1->id(), $converted->id());

    // An entity whose bundle is in the definition list is converted.
    $converted = $this->converter->convert($entity2->id(), $definition, 'qux', []);
    $this->assertSame($entity2->id(), $converted->id());

    // An entity whose bundle is missed from definition is not converted.
    $converted = $this->converter->convert($entity3->id(), $definition, 'qux', []);
    $this->assertNull($converted);

    // A non-existing entity returns NULL.
    $converted = $this->converter->convert('some-non-existing-entity-id', $definition, 'qux', []);
    $this->assertNull($converted);

    $definition = [
      'type' => 'entity:entity_test_mulrev',
    ];

    // Check that all entities are returned when 'bundle' is not defined.
    $converted = $this->converter->convert($entity1->id(), $definition, 'qux', []);
    $this->assertSame($entity1->id(), $converted->id());
    $converted = $this->converter->convert($entity2->id(), $definition, 'qux', []);
    $this->assertSame($entity2->id(), $converted->id());
    $converted = $this->converter->convert($entity3->id(), $definition, 'qux', []);
    $this->assertSame($entity3->id(), $converted->id());
    $converted = $this->converter->convert('some-non-existing-entity-id', $definition, 'qux', []);
    $this->assertNull($converted);
  }

}
