<?php

namespace Drupal\Tests\field\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests multilanguage fields logic that require a full environment.
 *
 * @group field
 */
class TranslationWebTest extends FieldTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['language', 'field_test', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The name of the field to use in this test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The name of the entity type to use in this test.
   *
   * @var string
   */
  protected $entityTypeId = 'entity_test_mulrev';

  /**
   * The field storage to use in this test.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field to use in this test.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fieldName = $this->randomMachineName() . '_field_name';

    $field_storage = [
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityTypeId,
      'type' => 'test_field',
      'cardinality' => 4,
    ];
    FieldStorageConfig::create($field_storage)->save();
    $this->fieldStorage = FieldStorageConfig::load($this->entityTypeId . '.' . $this->fieldName);

    $field = [
      'field_storage' => $this->fieldStorage,
      'bundle' => $this->entityTypeId,
    ];
    FieldConfig::create($field)->save();
    $this->field = FieldConfig::load($this->entityTypeId . '.' . $field['bundle'] . '.' . $this->fieldName);

    \Drupal::service('entity_display.repository')
      ->getFormDisplay($this->entityTypeId, $this->entityTypeId)
      ->setComponent($this->fieldName)
      ->save();

    for ($i = 0; $i < 3; ++$i) {
      ConfigurableLanguage::create([
        'id' => 'l' . $i,
        'label' => $this->randomString(),
      ])->save();
    }
  }

  /**
   * Tests field translations when creating a new revision.
   */
  public function testFieldFormTranslationRevisions() {
    $web_user = $this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
    ]);
    $this->drupalLogin($web_user);

    // Prepare the field translations.
    field_test_entity_info_translatable($this->entityTypeId, TRUE);
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId)
      ->create();
    $available_langcodes = array_flip(array_keys($this->container->get('language_manager')->getLanguages()));
    $field_name = $this->fieldStorage->getName();

    // Store the field translations.
    ksort($available_langcodes);
    $entity->langcode->value = key($available_langcodes);
    foreach ($available_langcodes as $langcode => $value) {
      $translation = $entity->hasTranslation($langcode) ? $entity->getTranslation($langcode) : $entity->addTranslation($langcode);
      $translation->{$field_name}->value = $value + 1;
    }
    $entity->save();

    // Create a new revision.
    $edit = [
      "{$field_name}[0][value]" => $entity->{$field_name}->value,
      'revision' => TRUE,
    ];
    $this->drupalGet($this->entityTypeId . '/manage/' . $entity->id() . '/edit');
    $this->submitForm($edit, 'Save');

    // Check translation revisions.
    $this->checkTranslationRevisions($entity->id(), $entity->getRevisionId(), $available_langcodes);
    $this->checkTranslationRevisions($entity->id(), $entity->getRevisionId() + 1, $available_langcodes);
  }

  /**
   * Tests translation revisions.
   *
   * Check if the field translation attached to the entity revision identified
   * by the passed arguments were correctly stored.
   */
  private function checkTranslationRevisions($id, $revision_id, $available_langcodes) {
    $field_name = $this->fieldStorage->getName();
    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $entity = $storage->loadRevision($revision_id);
    foreach ($available_langcodes as $langcode => $value) {
      $passed = $entity->getTranslation($langcode)->{$field_name}->value == $value + 1;
      $this->assertTrue($passed, new FormattableMarkup('The @language translation for revision @revision was correctly stored', ['@language' => $langcode, '@revision' => $entity->getRevisionId()]));
    }
  }

}
