<?php

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
  public function testNoEntity() {
    $converted = $this->converter->convert(1, [
      'load_latest_revision' => TRUE,
      'type' => 'entity:entity_test_mulrev',
    ], 'foo', []);
    $this->assertEquals(NULL, $converted);
  }

  /**
   * Tests with no pending revision.
   */
  public function testEntityNoPendingRevision() {
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
  public function testEntityWithPendingRevision() {
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
  public function testWithTranslatedPendingRevision() {
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
  public function testOptimizedConvert() {
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
   * Test the latest revision flag and non-revisionable entities.
   */
  public function testConvertNonRevisionableEntityType() {
    $entity = EntityTest::create();
    $entity->save();

    $converted = $this->converter->convert(1, [
      'load_latest_revision' => TRUE,
      'type' => 'entity:entity_test',
    ], 'foo', []);

    $this->assertEquals($entity->id(), $converted->id());
  }

}
