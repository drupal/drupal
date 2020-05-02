<?php

namespace Drupal\Tests\content_translation\Kernel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests the field synchronization logic when revisions are involved.
 *
 * @group content_translation
 */
class ContentTranslationFieldSyncRevisionTest extends EntityKernelTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
    'language',
    'content_translation',
    'content_translation_test',
  ];

  /**
   * The synchronized field name.
   *
   * @var string
   */
  protected $fieldName = 'sync_field';

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface|\Drupal\content_translation\BundleTranslationSettingsInterface
   */
  protected $contentTranslationManager;

  /**
   * The test entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $entity_type_id = 'entity_test_mulrev';
    $this->installEntitySchema($entity_type_id);
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    ConfigurableLanguage::createFromLangcode('it')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();

    /** @var \Drupal\field\Entity\FieldStorageConfig $field_storage */
    $field_storage_config = FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'type' => 'image',
      'entity_type' => $entity_type_id,
      'cardinality' => 1,
      'translatable' => 1,
    ]);
    $field_storage_config->save();

    $field_config = FieldConfig::create([
      'entity_type' => $entity_type_id,
      'field_name' => $this->fieldName,
      'bundle' => $entity_type_id,
      'label' => 'Synchronized field',
      'translatable' => 1,
    ]);
    $field_config->save();

    $property_settings = [
      'alt' => 'alt',
      'title' => 'title',
      'file' => 0,
    ];
    $field_config->setThirdPartySetting('content_translation', 'translation_sync', $property_settings);
    $field_config->save();

    $this->contentTranslationManager = $this->container->get('content_translation.manager');
    $this->contentTranslationManager->setEnabled($entity_type_id, $entity_type_id, TRUE);

    $this->storage = $this->entityTypeManager->getStorage($entity_type_id);

    foreach ($this->getTestFiles('image') as $file) {
      $entity = File::create((array) $file + ['status' => 1]);
      $entity->save();
    }

    $this->state->set('content_translation.entity_access.file', ['view' => TRUE]);

    $account = User::create([
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $account->save();
  }

  /**
   * Checks that field synchronization works as expected with revisions.
   *
   * @covers \Drupal\content_translation\Plugin\Validation\Constraint\ContentTranslationSynchronizedFieldsConstraintValidator::create
   * @covers \Drupal\content_translation\Plugin\Validation\Constraint\ContentTranslationSynchronizedFieldsConstraintValidator::validate
   * @covers \Drupal\content_translation\Plugin\Validation\Constraint\ContentTranslationSynchronizedFieldsConstraintValidator::hasSynchronizedPropertyChanges
   * @covers \Drupal\content_translation\FieldTranslationSynchronizer::getFieldSynchronizedProperties
   * @covers \Drupal\content_translation\FieldTranslationSynchronizer::synchronizeFields
   * @covers \Drupal\content_translation\FieldTranslationSynchronizer::synchronizeItems
   */
  public function testFieldSynchronizationAndValidation() {
    // Test that when untranslatable field widgets are displayed, synchronized
    // field properties can be changed only in default revisions.
    $this->setUntranslatableFieldWidgetsDisplay(TRUE);
    $entity = $this->saveNewEntity();
    $entity_id = $entity->id();
    $this->assertLatestRevisionFieldValues($entity_id, [1, 1, 1, 'Alt 1 EN']);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $en_revision */
    $en_revision = $this->createRevision($entity, FALSE);
    $en_revision->get($this->fieldName)->target_id = 2;
    $violations = $en_revision->validate();
    $this->assertViolations($violations);

    $it_translation = $entity->addTranslation('it', $entity->toArray());
    /** @var \Drupal\Core\Entity\ContentEntityInterface $it_revision */
    $it_revision = $this->createRevision($it_translation, FALSE);
    $metadata = $this->contentTranslationManager->getTranslationMetadata($it_revision);
    $metadata->setSource('en');
    $it_revision->get($this->fieldName)->target_id = 2;
    $it_revision->get($this->fieldName)->alt = 'Alt 2 IT';
    $violations = $it_revision->validate();
    $this->assertViolations($violations);
    $it_revision->isDefaultRevision(TRUE);
    $violations = $it_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($it_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [2, 2, 2, 'Alt 1 EN', 'Alt 2 IT']);

    $en_revision = $this->createRevision($en_revision, FALSE);
    $en_revision->get($this->fieldName)->alt = 'Alt 3 EN';
    $violations = $en_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($en_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [3, 2, 2, 'Alt 3 EN', 'Alt 2 IT']);

    $it_revision = $this->createRevision($it_revision, FALSE);
    $it_revision->get($this->fieldName)->alt = 'Alt 4 IT';
    $violations = $it_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($it_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [4, 2, 2, 'Alt 1 EN', 'Alt 4 IT']);

    $en_revision = $this->createRevision($en_revision);
    $en_revision->get($this->fieldName)->alt = 'Alt 5 EN';
    $violations = $en_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($en_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [5, 2, 2, 'Alt 5 EN', 'Alt 2 IT']);

    $en_revision = $this->createRevision($en_revision);
    $en_revision->get($this->fieldName)->target_id = 6;
    $en_revision->get($this->fieldName)->alt = 'Alt 6 EN';
    $violations = $en_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($en_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [6, 6, 6, 'Alt 6 EN', 'Alt 2 IT']);

    $it_revision = $this->createRevision($it_revision);
    $it_revision->get($this->fieldName)->alt = 'Alt 7 IT';
    $violations = $it_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($it_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [7, 6, 6, 'Alt 6 EN', 'Alt 7 IT']);

    // Test that when untranslatable field widgets are hidden, synchronized
    // field properties can be changed only when editing the default
    // translation. This may lead to temporarily desynchronized values, when
    // saving a pending revision for the default translation that changes a
    // synchronized property (see revision 11).
    $this->setUntranslatableFieldWidgetsDisplay(FALSE);
    $entity = $this->saveNewEntity();
    $entity_id = $entity->id();
    $this->assertLatestRevisionFieldValues($entity_id, [8, 1, 1, 'Alt 1 EN']);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $en_revision */
    $en_revision = $this->createRevision($entity, FALSE);
    $en_revision->get($this->fieldName)->target_id = 2;
    $en_revision->get($this->fieldName)->alt = 'Alt 2 EN';
    $violations = $en_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($en_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [9, 2, 2, 'Alt 2 EN']);

    $it_translation = $entity->addTranslation('it', $entity->toArray());
    /** @var \Drupal\Core\Entity\ContentEntityInterface $it_revision */
    $it_revision = $this->createRevision($it_translation, FALSE);
    $metadata = $this->contentTranslationManager->getTranslationMetadata($it_revision);
    $metadata->setSource('en');
    $it_revision->get($this->fieldName)->target_id = 3;
    $violations = $it_revision->validate();
    $this->assertViolations($violations);
    $it_revision->isDefaultRevision(TRUE);
    $violations = $it_revision->validate();
    $this->assertViolations($violations);

    $it_revision = $this->createRevision($it_translation);
    $metadata = $this->contentTranslationManager->getTranslationMetadata($it_revision);
    $metadata->setSource('en');
    $it_revision->get($this->fieldName)->alt = 'Alt 3 IT';
    $violations = $it_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($it_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [10, 1, 1, 'Alt 1 EN', 'Alt 3 IT']);

    $en_revision = $this->createRevision($en_revision, FALSE);
    $en_revision->get($this->fieldName)->alt = 'Alt 4 EN';
    $violations = $en_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($en_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [11, 2, 1, 'Alt 4 EN', 'Alt 3 IT']);

    $it_revision = $this->createRevision($it_revision, FALSE);
    $it_revision->get($this->fieldName)->alt = 'Alt 5 IT';
    $violations = $it_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($it_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [12, 1, 1, 'Alt 1 EN', 'Alt 5 IT']);

    $en_revision = $this->createRevision($en_revision);
    $en_revision->get($this->fieldName)->target_id = 6;
    $en_revision->get($this->fieldName)->alt = 'Alt 6 EN';
    $violations = $en_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($en_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [13, 6, 6, 'Alt 6 EN', 'Alt 3 IT']);

    $it_revision = $this->createRevision($it_revision);
    $it_revision->get($this->fieldName)->target_id = 7;
    $violations = $it_revision->validate();
    $this->assertViolations($violations);

    $it_revision = $this->createRevision($it_revision);
    $it_revision->get($this->fieldName)->alt = 'Alt 7 IT';
    $violations = $it_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($it_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [14, 6, 6, 'Alt 6 EN', 'Alt 7 IT']);

    // Test that creating a default revision starting from a pending revision
    // having changes to synchronized properties, without introducing new
    // changes works properly.
    $this->setUntranslatableFieldWidgetsDisplay(FALSE);
    $entity = $this->saveNewEntity();
    $entity_id = $entity->id();
    $this->assertLatestRevisionFieldValues($entity_id, [15, 1, 1, 'Alt 1 EN']);

    $it_translation = $entity->addTranslation('it', $entity->toArray());
    /** @var \Drupal\Core\Entity\ContentEntityInterface $it_revision */
    $it_revision = $this->createRevision($it_translation);
    $metadata = $this->contentTranslationManager->getTranslationMetadata($it_revision);
    $metadata->setSource('en');
    $it_revision->get($this->fieldName)->alt = 'Alt 2 IT';
    $violations = $it_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($it_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [16, 1, 1, 'Alt 1 EN', 'Alt 2 IT']);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $en_revision */
    $en_revision = $this->createRevision($entity);
    $en_revision->get($this->fieldName)->target_id = 3;
    $en_revision->get($this->fieldName)->alt = 'Alt 3 EN';
    $violations = $en_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($en_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [17, 3, 3, 'Alt 3 EN', 'Alt 2 IT']);

    $en_revision = $this->createRevision($entity, FALSE);
    $en_revision->get($this->fieldName)->target_id = 4;
    $en_revision->get($this->fieldName)->alt = 'Alt 4 EN';
    $violations = $en_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($en_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [18, 4, 3, 'Alt 4 EN', 'Alt 2 IT']);

    $en_revision = $this->createRevision($entity);
    $violations = $en_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($en_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [19, 4, 4, 'Alt 4 EN', 'Alt 2 IT']);

    $it_revision = $this->createRevision($it_revision);
    $it_revision->get($this->fieldName)->alt = 'Alt 6 IT';
    $violations = $it_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($it_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [20, 4, 4, 'Alt 4 EN', 'Alt 6 IT']);

    // Check that we are not allowed to perform changes to multiple translations
    // in pending revisions when synchronized properties are involved.
    $this->setUntranslatableFieldWidgetsDisplay(FALSE);
    $entity = $this->saveNewEntity();
    $entity_id = $entity->id();
    $this->assertLatestRevisionFieldValues($entity_id, [21, 1, 1, 'Alt 1 EN']);

    $it_translation = $entity->addTranslation('it', $entity->toArray());
    /** @var \Drupal\Core\Entity\ContentEntityInterface $it_revision */
    $it_revision = $this->createRevision($it_translation);
    $metadata = $this->contentTranslationManager->getTranslationMetadata($it_revision);
    $metadata->setSource('en');
    $it_revision->get($this->fieldName)->alt = 'Alt 2 IT';
    $violations = $it_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($it_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [22, 1, 1, 'Alt 1 EN', 'Alt 2 IT']);

    $en_revision = $this->createRevision($entity, FALSE);
    $en_revision->get($this->fieldName)->target_id = 2;
    $en_revision->getTranslation('it')->get($this->fieldName)->alt = 'Alt 3 IT';
    $violations = $en_revision->validate();
    $this->assertViolations($violations);

    // Test that when saving a new default revision starting from a pending
    // revision, outdated synchronized properties do not override more recent
    // ones.
    $this->setUntranslatableFieldWidgetsDisplay(TRUE);
    $entity = $this->saveNewEntity();
    $entity_id = $entity->id();
    $this->assertLatestRevisionFieldValues($entity_id, [23, 1, 1, 'Alt 1 EN']);

    $it_translation = $entity->addTranslation('it', $entity->toArray());
    /** @var \Drupal\Core\Entity\ContentEntityInterface $it_revision */
    $it_revision = $this->createRevision($it_translation, FALSE);
    $metadata = $this->contentTranslationManager->getTranslationMetadata($it_revision);
    $metadata->setSource('en');
    $it_revision->get($this->fieldName)->alt = 'Alt 2 IT';
    $violations = $it_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($it_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [24, 1, 1, 'Alt 1 EN', 'Alt 2 IT']);

    /** @var \Drupal\Core\Entity\ContentEntityInterface $en_revision */
    $en_revision = $this->createRevision($entity);
    $en_revision->get($this->fieldName)->target_id = 3;
    $en_revision->get($this->fieldName)->alt = 'Alt 3 EN';
    $violations = $en_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($en_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [25, 3, 3, 'Alt 3 EN', 'Alt 2 IT']);

    $it_revision = $this->createRevision($it_revision);
    $it_revision->get($this->fieldName)->alt = 'Alt 4 IT';
    $violations = $it_revision->validate();
    $this->assertEmpty($violations);
    $this->storage->save($it_revision);
    $this->assertLatestRevisionFieldValues($entity_id, [26, 3, 3, 'Alt 3 EN', 'Alt 4 IT']);
  }

  /**
   * Test changing the default language of an entity.
   */
  public function testChangeDefaultLanguageNonTranslatableFieldsHidden() {
    $this->setUntranslatableFieldWidgetsDisplay(FALSE);
    $entity = $this->saveNewEntity();
    $entity->langcode = 'it';
    $this->assertCount(0, $entity->validate());
  }

  /**
   * Sets untranslatable field widgets' display status.
   *
   * @param bool $display
   *   Whether untranslatable field widgets should be displayed.
   */
  protected function setUntranslatableFieldWidgetsDisplay($display) {
    $entity_type_id = $this->storage->getEntityTypeId();
    $settings = ['untranslatable_fields_hide' => !$display];
    $this->contentTranslationManager->setBundleTranslationSettings($entity_type_id, $entity_type_id, $settings);
    /** @var \Drupal\Core\Entity\EntityTypeBundleInfo $bundle_info */
    $bundle_info = $this->container->get('entity_type.bundle.info');
    $bundle_info->clearCachedBundles();
  }

  /**
   * @return \Drupal\Core\Entity\ContentEntityInterface
   */
  protected function saveNewEntity() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = EntityTestMulRev::create([
      'uid' => 1,
      'langcode' => 'en',
      $this->fieldName => [
        'target_id' => 1,
        'alt' => 'Alt 1 EN',
      ],
    ]);
    $metadata = $this->contentTranslationManager->getTranslationMetadata($entity);
    $metadata->setSource(LanguageInterface::LANGCODE_NOT_SPECIFIED);
    $violations = $entity->validate();
    $this->assertEmpty($violations);
    $this->storage->save($entity);
    return $entity;
  }

  /**
   * Creates a new revision starting from the latest translation-affecting one.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $translation
   *   The translation to be revisioned.
   * @param bool $default
   *   (optional) Whether the new revision should be marked as default. Defaults
   *   to TRUE.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   An entity revision object.
   */
  protected function createRevision(ContentEntityInterface $translation, $default = TRUE) {
    if (!$translation->isNewTranslation()) {
      $langcode = $translation->language()->getId();
      $revision_id = $this->storage->getLatestTranslationAffectedRevisionId($translation->id(), $langcode);
      /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
      $revision = $this->storage->loadRevision($revision_id);
      $translation = $revision->getTranslation($langcode);
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
    $revision = $this->storage->createRevision($translation, $default);
    return $revision;
  }

  /**
   * Asserts that the expected violations were found.
   *
   * @param \Drupal\Core\Entity\EntityConstraintViolationListInterface $violations
   *   A list of violations.
   */
  protected function assertViolations(EntityConstraintViolationListInterface $violations) {
    $entity_type_id = $this->storage->getEntityTypeId();
    $settings = $this->contentTranslationManager->getBundleTranslationSettings($entity_type_id, $entity_type_id);
    $message = !empty($settings['untranslatable_fields_hide']) ?
      'Non-translatable field elements can only be changed when updating the original language.' :
      'Non-translatable field elements can only be changed when updating the current revision.';

    $list = [];
    foreach ($violations as $violation) {
      if ((string) $violation->getMessage() === $message) {
        $list[] = $violation;
      }
    }
    $this->assertCount(1, $list);
  }

  /**
   * Asserts that the latest revision has the expected field values.
   *
   * @param $entity_id
   *   The entity ID.
   * @param array $expected_values
   *   An array of expected values in the following order:
   *   - revision ID
   *   - target ID (en)
   *   - target ID (it)
   *   - alt (en)
   *   - alt (it)
   */
  protected function assertLatestRevisionFieldValues($entity_id, array $expected_values) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->storage->loadRevision($this->storage->getLatestRevisionId($entity_id));
    @list($revision_id, $target_id_en, $target_id_it, $alt_en, $alt_it) = $expected_values;
    $this->assertEquals($revision_id, $entity->getRevisionId());
    $this->assertEquals($target_id_en, $entity->get($this->fieldName)->target_id);
    $this->assertEquals($alt_en, $entity->get($this->fieldName)->alt);
    if ($entity->hasTranslation('it')) {
      $it_translation = $entity->getTranslation('it');
      $this->assertEquals($target_id_it, $it_translation->get($this->fieldName)->target_id);
      $this->assertEquals($alt_it, $it_translation->get($this->fieldName)->alt);
    }
  }

}
