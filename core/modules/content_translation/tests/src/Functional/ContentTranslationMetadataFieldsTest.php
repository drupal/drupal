<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Functional;

/**
 * Tests the Content Translation metadata fields handling.
 *
 * @group content_translation
 */
class ContentTranslationMetadataFieldsTest extends ContentTranslationTestBase {

  /**
   * The entity type being tested.
   *
   * @var string
   */
  protected $entityTypeId = 'node';

  /**
   * The bundle being tested.
   *
   * @var string
   */
  protected $bundle = 'article';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'content_translation', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setupBundle(): void {
    parent::setupBundle();
    $this->createContentType(['type' => $this->bundle]);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->doSetup();
  }

  /**
   * Tests skipping setting non translatable metadata fields.
   */
  public function testSkipUntranslatable(): void {
    $this->drupalLogin($this->translator);
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->entityTypeId, $this->bundle);

    // Turn off translatability for the metadata fields on the current bundle.
    $metadata_fields = ['created', 'changed', 'uid', 'status'];
    foreach ($metadata_fields as $field_name) {
      $fields[$field_name]
        ->getConfig($this->bundle)
        ->setTranslatable(FALSE)
        ->save();
    }

    // Create a new test entity with original values in the default language.
    $default_langcode = $this->langcodes[0];
    $entity_id = $this->createEntity(['title' => $this->randomString()], $default_langcode);
    $storage = \Drupal::entityTypeManager()->getStorage($this->entityTypeId);
    $storage->resetCache();
    $entity = $storage->load($entity_id);

    // Add a content translation.
    $langcode = 'it';
    $values = $entity->toArray();
    // Apply a default value for the metadata fields.
    foreach ($metadata_fields as $field_name) {
      unset($values[$field_name]);
    }
    $entity->addTranslation($langcode, $values);

    $metadata_source_translation = $this->manager->getTranslationMetadata($entity->getTranslation($default_langcode));
    $metadata_target_translation = $this->manager->getTranslationMetadata($entity->getTranslation($langcode));

    $created_time = $metadata_source_translation->getCreatedTime();
    $changed_time = $metadata_source_translation->getChangedTime();
    $published = $metadata_source_translation->isPublished();
    $author = $metadata_source_translation->getAuthor();

    $this->assertEquals($created_time, $metadata_target_translation->getCreatedTime(), 'Metadata created field has the same value for both translations.');
    $this->assertEquals($changed_time, $metadata_target_translation->getChangedTime(), 'Metadata changed field has the same value for both translations.');
    $this->assertEquals($published, $metadata_target_translation->isPublished(), 'Metadata published field has the same value for both translations.');
    $this->assertEquals($author->id(), $metadata_target_translation->getAuthor()->id(), 'Metadata author field has the same value for both translations.');

    $metadata_target_translation->setCreatedTime(time() + 50);
    $metadata_target_translation->setChangedTime(time() + 50);
    $metadata_target_translation->setPublished(TRUE);
    $metadata_target_translation->setAuthor($this->editor);

    $this->assertEquals($created_time, $metadata_target_translation->getCreatedTime(), 'Metadata created field correctly not updated');
    $this->assertEquals($changed_time, $metadata_target_translation->getChangedTime(), 'Metadata changed field correctly not updated');
    $this->assertEquals($published, $metadata_target_translation->isPublished(), 'Metadata published field correctly not updated');
    $this->assertEquals($author->id(), $metadata_target_translation->getAuthor()->id(), 'Metadata author field correctly not updated');
  }

  /**
   * Tests setting translatable metadata fields.
   */
  public function testSetTranslatable(): void {
    $this->drupalLogin($this->translator);
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions($this->entityTypeId, $this->bundle);

    // Turn off translatability for the metadata fields on the current bundle.
    $metadata_fields = ['created', 'changed', 'uid', 'status'];
    foreach ($metadata_fields as $field_name) {
      $fields[$field_name]
        ->getConfig($this->bundle)
        ->setTranslatable(TRUE)
        ->save();
    }

    // Create a new test entity with original values in the default language.
    $default_langcode = $this->langcodes[0];
    $entity_id = $this->createEntity(['title' => $this->randomString(), 'status' => FALSE], $default_langcode);
    $storage = \Drupal::entityTypeManager()->getStorage($this->entityTypeId);
    $storage->resetCache();
    $entity = $storage->load($entity_id);

    // Add a content translation.
    $langcode = 'it';
    $values = $entity->toArray();
    // Apply a default value for the metadata fields.
    foreach ($metadata_fields as $field_name) {
      unset($values[$field_name]);
    }
    $entity->addTranslation($langcode, $values);

    $metadata_source_translation = $this->manager->getTranslationMetadata($entity->getTranslation($default_langcode));
    $metadata_target_translation = $this->manager->getTranslationMetadata($entity->getTranslation($langcode));

    $metadata_target_translation->setCreatedTime(time() + 50);
    $metadata_target_translation->setChangedTime(time() + 50);
    $metadata_target_translation->setPublished(TRUE);
    $metadata_target_translation->setAuthor($this->editor);

    $this->assertNotEquals($metadata_source_translation->getCreatedTime(), $metadata_target_translation->getCreatedTime(), 'Metadata created field correctly different on both translations.');
    $this->assertNotEquals($metadata_source_translation->getChangedTime(), $metadata_target_translation->getChangedTime(), 'Metadata changed field correctly different on both translations.');
    $this->assertNotEquals($metadata_source_translation->isPublished(), $metadata_target_translation->isPublished(), 'Metadata published field correctly different on both translations.');
    $this->assertNotEquals($metadata_source_translation->getAuthor()->id(), $metadata_target_translation->getAuthor()->id(), 'Metadata author field correctly different on both translations.');
  }

}
