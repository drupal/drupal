<?php

namespace Drupal\FunctionalTests\Entity;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests field validation filtering on content entity forms.
 *
 * @group Entity
 */
class ContentEntityFormFieldValidationFilteringTest extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The single-valued field name being tested with the entity type.
   *
   * @var string
   */
  protected $fieldNameSingle;

  /**
   * The multi-valued field name being tested with the entity type.
   *
   * @var string
   */
  protected $fieldNameMultiple;

  /**
   * The name of the file field being tested with the entity type.
   *
   * @var string
   */
  protected $fieldNameFile;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'field_test', 'file', 'image'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $web_user = $this->drupalCreateUser(['administer entity_test content']);
    $this->drupalLogin($web_user);

    // Create two fields of field type "test_field", one with single cardinality
    // and one with unlimited cardinality on the entity type "entity_test". It
    // is important to use this field type because its default widget has a
    // custom \Drupal\Core\Field\WidgetInterface::errorElement() implementation.
    $this->entityTypeId = 'entity_test';
    $this->fieldNameSingle = 'test_single';
    $this->fieldNameMultiple = 'test_multiple';
    $this->fieldNameFile = 'test_file';

    FieldStorageConfig::create([
      'field_name' => $this->fieldNameSingle,
      'entity_type' => $this->entityTypeId,
      'type' => 'test_field',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_name' => $this->fieldNameSingle,
      'bundle' => $this->entityTypeId,
      'label' => 'Test single',
      'required' => TRUE,
      'translatable' => FALSE,
    ])->save();

    FieldStorageConfig::create([
      'field_name' => $this->fieldNameMultiple,
      'entity_type' => $this->entityTypeId,
      'type' => 'test_field',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_name' => $this->fieldNameMultiple,
      'bundle' => $this->entityTypeId,
      'label' => 'Test multiple',
      'translatable' => FALSE,
    ])->save();

    // Also create a file field to test its '#limit_validation_errors'
    // implementation.
    FieldStorageConfig::create([
      'field_name' => $this->fieldNameFile,
      'entity_type' => $this->entityTypeId,
      'type' => 'file',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_name' => $this->fieldNameFile,
      'bundle' => $this->entityTypeId,
      'label' => 'Test file',
      'translatable' => FALSE,
    ])->save();

    $this->container->get('entity_display.repository')
      ->getFormDisplay($this->entityTypeId, $this->entityTypeId, 'default')
      ->setComponent($this->fieldNameSingle, ['type' => 'test_field_widget'])
      ->setComponent($this->fieldNameMultiple, ['type' => 'test_field_widget'])
      ->setComponent($this->fieldNameFile, ['type' => 'file_generic'])
      ->save();
  }

  /**
   * Tests field widgets with #limit_validation_errors.
   */
  public function testFieldWidgetsWithLimitedValidationErrors() {
    $assert_session = $this->assertSession();
    $this->drupalGet($this->entityTypeId . '/add');

    // The 'Test multiple' field is the only multi-valued field in the form, so
    // try to add a new item for it. This tests the '#limit_validation_errors'
    // property set by \Drupal\Core\Field\WidgetBase::formMultipleElements().
    $assert_session->elementsCount('css', 'div#edit-test-multiple-wrapper div.form-type-textfield input', 1);
    $this->submitForm([], 'Add another item');
    $assert_session->elementsCount('css', 'div#edit-test-multiple-wrapper div.form-type-textfield input', 2);

    // Now try to upload a file. This tests the '#limit_validation_errors'
    // property set by
    // \Drupal\file\Plugin\Field\FieldWidget\FileWidget::process().
    $text_file = current($this->getTestFiles('text'));
    $edit = [
      'files[test_file_0]' => \Drupal::service('file_system')->realpath($text_file->uri),
    ];
    $assert_session->elementNotExists('css', 'input#edit-test-file-0-remove-button');
    $this->submitForm($edit, 'Upload');
    $assert_session->elementExists('css', 'input#edit-test-file-0-remove-button');

    // Make the 'Test multiple' field required and check that adding another
    // item throws a validation error.
    $field_config = FieldConfig::loadByName($this->entityTypeId, $this->entityTypeId, $this->fieldNameMultiple);
    $field_config->setRequired(TRUE);
    $field_config->save();

    $this->drupalPostForm($this->entityTypeId . '/add', [], 'Add another item');
    $assert_session->pageTextContains('Test multiple (value 1) field is required.');

    // Check that saving the form without entering any value for the required
    // field still throws the proper validation errors.
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('Test single field is required.');
    $assert_session->pageTextContains('Test multiple (value 1) field is required.');
  }

}
