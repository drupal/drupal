<?php

namespace Drupal\FunctionalTests\Entity;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the correct mapping of user input on the correct field delta elements.
 *
 * @group Entity
 */
class ContentEntityFormCorrectUserInputMappingOnFieldDeltaElementsTest extends BrowserTestBase {

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The field name with multiple properties being test with the entity type.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $web_user = $this->drupalCreateUser(['administer entity_test content']);
    $this->drupalLogin($web_user);

    // Create a field of field type "shape" with unlimited cardinality on the
    // entity type "entity_test".
    $this->entityTypeId = 'entity_test';
    $this->fieldName = 'shape';

    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityTypeId,
      'type' => 'shape',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])
      ->save();
    FieldConfig::create([
      'entity_type' => $this->entityTypeId,
      'field_name' => $this->fieldName,
      'bundle' => $this->entityTypeId,
      'label' => 'Shape',
      'translatable' => FALSE,
    ])
      ->save();

    \Drupal::service('entity_display.repository')
      ->getFormDisplay($this->entityTypeId, $this->entityTypeId)
      ->setComponent($this->fieldName, ['type' => 'shape_only_color_editable_widget'])
      ->save();
  }

  /**
   * Tests the correct user input mapping on complex fields.
   */
  public function testCorrectUserInputMappingOnComplexFields() {
    /** @var ContentEntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage($this->entityTypeId);

    /** @var ContentEntityInterface $entity */
    $entity = $storage->create([
      $this->fieldName => [
        ['shape' => 'rectangle', 'color' => 'green'],
        ['shape' => 'circle', 'color' => 'blue'],
      ],
    ]);
    $entity->save();

    $this->drupalGet($this->entityTypeId . '/manage/' . $entity->id() . '/edit');

    // Rearrange the field items.
    $edit = [
      "$this->fieldName[0][_weight]" => 0,
      "$this->fieldName[1][_weight]" => -1,
    ];
    // Executing an ajax call is important before saving as it will trigger
    // form state caching and so if for any reasons the form is rebuilt with
    // the entity built based on the user submitted values with already
    // reordered field items then the correct mapping will break after the form
    // builder maps over the new form the user submitted values based on the
    // previous delta ordering.
    //
    // This is how currently the form building process works and this test
    // ensures the correct behavior no matter what changes would be made to the
    // form builder or the content entity forms.
    $this->submitForm($edit, 'Add another item');
    $this->submitForm([], 'Save');

    // Reload the entity.
    $entity = $storage->load($entity->id());

    // Assert that after rearranging the field items the user input will be
    // mapped on the correct delta field items.
    $this->assertEquals($entity->get($this->fieldName)->getValue(), [
      ['shape' => 'circle', 'color' => 'blue'],
      ['shape' => 'rectangle', 'color' => 'green'],
    ]);
  }

}
